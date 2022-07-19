<?php
/** The code for the OAuth2\Client class. */
namespace MeLeeCMS\OAuth2;

/**
An additional wrapper class for the CURLWrapper class, to make cURL requests even easier in the case of interacting with an OAuth 2.0 API.
This class should theoretically work for any OAuth 2.0 API, but only a small number are fully supported by MeLeeCMS.
APIs currently supported:
- Twitch

APIs with support pending:
- Discord
- YouTube
- Salesforce (maybe)
@uses \MeLeeCMS\CURLWrapper Server-to-server API requests are done through the cURL wrapper.
@uses ClientRateLimit Separate class for handling rate limits imposed by certain APIs.
*/
class Client
{
	const E_STATE_MISMATCH = 1;
	const E_FAILED_LOGIN = 2;
	
	public $curl;
	public $auth_url;
	public $api_url;
	protected $client_id;
	protected $client_secret;
	protected $grant_type;
	protected $parameters = [];
	public $login_attempted = false;
	public $login_succeeded = false;
	public $token;
	public $implicit_token;
	public $error = [];
	public $rate_limit;
	public $get_implicit;
	
	public function __construct($url, $client_id, $client_secret, $grant_type, $parameters=[], $rate_limit=null, $get_implicit=false)
	{
		// Setup the properties that we'll need.
		if(is_array($url))
			$this->auth_url = implode("/", $url);
		else
			$this->auth_url = $url;
		$this->api_url = $this->auth_url;
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->grant_type = $grant_type;
		if($this->grant_type === "authorization_code")
		{
         // scope is required
         if(isset($parameters['scope']) && is_array($parameters['scope']))
            $this->parameters['scope'] = $parameters['scope'];
         else
            $this->parameters['scope'] = [];
         // redirect_uri is required
			if(!empty($parameters['redirect_uri']))
				$this->parameters['redirect_uri'] = $parameters['redirect_uri'];
			else
			{
				if(($i = strpos($_SERVER['REQUEST_URI'], "?")) !== false)
					$uri = substr($_SERVER['REQUEST_URI'], 0, $i);
				else
					$uri = $_SERVER['REQUEST_URI'];
				$this->parameters['redirect_uri'] = (!empty($_SERVER['HTTPS']) ? "https" : "http") ."://". $_SERVER['HTTP_HOST'] . $uri;
			}
         // state is not usually required but we are going to use it
			if(!empty($parameters['state']))
				$this->parameters['state'] = $parameters['state'];
			else
				$this->parameters['state'] = md5(session_id());
         $implicit_relog_state = "implicit_relog";
		}
		else if($this->grant_type === "password")
		{
			$this->parameters['username'] = $parameters['username'];
			$this->parameters['password'] = $parameters['password'];
		}
      
      if(empty($rate_limit))
         $this->rate_limit = new ClientRateLimit();
      else
         $this->rate_limit = $rate_limit;
      
      $this->get_implicit = $get_implicit;
		
		// Begin the login process.
		$this->curl = new \MeLeeCMS\CURLWrapper([], true, false);
		
		// W-I-P code for password logins.
		if($this->grant_type === "password")
		{
			// TODO: Make sure this isn't run on every single page load.
			if(!$this->login())
			{
				$this->error = [
					'code' => self::E_FAILED_LOGIN,
					'last_reponse' => $this->curl->getLastHeaders(),
					'last_request' => $this->curl->getLastRequestInfo(),
					'token' => $this->token,
				];
			}
		}
		// Check if this request is redirected from a successful authentication.
		else if(!empty($_REQUEST['code']) && $_REQUEST['state'] == $this->parameters['state'])
		{
			if($this->login($_REQUEST['code']))
			{
            // Note: MeLeeCMS has its own method for requesting a page "refresh", but I think it's fine to do this here.
            if($get_implicit)
            {
               header("Location: ". $this->auth_url ."/oauth2/authorize?response_type=token&client_id=". urlencode($this->client_id) ."&scope=". urlencode(implode(" ", $this->parameters['scope'])) ."&redirect_uri=". urlencode($this->parameters['redirect_uri']) . "&state=". urlencode($this->parameters['state']));
            }
            else
               // Reload to get rid of the REQUEST stuff.
               header("Location: ". $this->parameters['redirect_uri']);
			}
			else
			{
				// We get a login code and everything seems ok, but OAuth2 API rejects the login.
				$this->error = [
					'code' => self::E_FAILED_LOGIN,
					'last_reponse' => $this->curl->getLastHeaders(),
					'last_request' => $this->curl->getLastRequestInfo(),
					'token' => $this->token,
				];
			}
		}
		else if(!empty($_REQUEST['code']))
		{
			// Someone tried to log in, but there was a state mismatch.
			$this->error = [
				'code' => self::E_STATE_MISMATCH,
				'expected' => $this->parameters['state'],
				'got' => $_REQUEST['state'],
			];
		}
      // TODO: We can have a session var update on every page load with a new state and send it to JavaScript for implicit grant relog states
		else if($get_implicit && !empty($_REQUEST['implicit_grant']) && !empty($_REQUEST['access_token']) && !empty($_REQUEST['token_type']) && ($_REQUEST['state'] == $this->parameters['state'] || substr($_REQUEST['state'], 0, strlen($implicit_relog_state)) == $implicit_relog_state))
		{
         // Implicit code granted.
         $this->implicit_token = [
            'access_token' => $_REQUEST['access_token'],
            'token_type' => $_REQUEST['token_type'],
         ];
         $_SESSION[$this->client_id."_implicit_token"] = $this->implicit_token;
         // Reload to get rid of the REQUEST stuff.
         if(substr($_REQUEST['state'], 0, strlen($implicit_relog_state)) == $implicit_relog_state)
            header("Location: ". substr($_REQUEST['state'], strlen($implicit_relog_state)));
         else
            header("Location: ". $this->parameters['redirect_uri']);
		}
		else if($get_implicit && !empty($_REQUEST['implicit_grant']) && !empty($_REQUEST['access_token']) && !empty($_REQUEST['token_type']))
		{
			// Someone tried to log in, but there was a state mismatch.
			$this->error = [
				'code' => self::E_STATE_MISMATCH,
				'expected' => $this->parameters['state'],
				'got' => $_REQUEST['state'],
			];
		}
		
		if(isset($_SESSION[$this->client_id."_token"]) && is_object($_SESSION[$this->client_id."_token"]))
			$this->token = $_SESSION[$this->client_id."_token"];
		
		if($get_implicit && isset($_SESSION[$this->client_id."_implicit_token"]) && is_array($_SESSION[$this->client_id."_implicit_token"]))
			$this->implicit_token = $_SESSION[$this->client_id."_implicit_token"];
		
		if(!empty($this->token->instance_url))
		{
			// Note: This implementation is Salesforce-specific, so hopefully any other API that uses it follows the same format.
			if(is_array($url))
				$this->api_url = $this->token->instance_url ."/". $url[1];
			else
				$this->api_url = $this->token->instance_url . substr($url, strpos($url, "/", strpos($url, "//")+2));
		}
	}
	
	public function logout()
	{
		$this->token = null;
		unset($_SESSION[$this->client_id."_token"]);
		unset($_SESSION[$this->client_id."_token_time"]);
		unset($_SESSION[$this->client_id."_implicit_token"]);
		$this->login_attempted = false;
		$this->login_succeeded = false;
	}
	
	public function login($code=null)
	{
		if($this->login_attempted)
			return $this->login_succeeded;
		$post = [
			'grant_type' => $this->grant_type,
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
		];
		if($this->grant_type == "authorization_code" && $code !== null)
		{
			$post['code'] = $code;
			$post['redirect_uri'] = $this->parameters['redirect_uri'];
			$post['scope'] = implode(" ", $this->parameters['scope']);
		}
		else if($this->grant_type == "password")
		{
			$post['username'] = $this->parameters['username'];
			$post['password'] = $this->parameters['password'];
		}
		else if(!empty($this->token->access_token))
		{
			return true;
		}
		else
		{
			return false;
		}
		$raw = $this->curl->request($this->auth_url ."/oauth2/token", [CURLOPT_POSTFIELDS => $post]);
		if(is_object($this->token = json_decode($raw)))
		{
			if(!empty($this->token->access_token))
			{
				$_SESSION[$this->client_id."_token"] = $this->token;
				$_SESSION[$this->client_id."_token_time"] = time();
				$this->login_succeeded = true;
			}
			else
			{
				unset($_SESSION[$this->client_id."_token"]);
				unset($_SESSION[$this->client_id."_token_time"]);
				$this->login_succeeded = false;
			}
			$this->login_attempted = true;
			return $this->login_succeeded;
		}
		else
		{
			$this->token = $raw;
			unset($_SESSION[$this->client_id."_token"]);
			unset($_SESSION[$this->client_id."_token_time"]);
			$this->login_attempted = true;
			$this->login_succeeded = false;
			return $this->login_succeeded;
		}
	}
	
	public function refresh()
	{
		if(empty($this->token->refresh_token))
			return false;
		$post = [
			'grant_type' => "refresh_token",
			'refresh_token' => $this->token->refresh_token,
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
		];
		$raw = $this->curl->request($this->auth_url ."/oauth2/token", [CURLOPT_POSTFIELDS => $post]);
      $this->rate_limit->loadHeaders($this->curl->getLastHeaders());
		if(is_object($json = json_decode($raw)))
		{
			if(!empty($json->access_token))
			{
				foreach(get_object_vars($json) as $prop=>$val)
				{
					$this->token->$prop = $json->$prop;
				}
				$_SESSION[$this->client_id."_token"] = $this->token;
				$_SESSION[$this->client_id."_token_time"] = time();
				$this->login_succeeded = true;
			}
			else
			{
				unset($_SESSION[$this->client_id."_token"]);
				unset($_SESSION[$this->client_id."_token_time"]);
				$this->login_succeeded = false;
			}
			$this->login_attempted = true;
			return $this->login_succeeded;
		}
		else
		{
			$this->token = $raw;
			unset($_SESSION[$this->client_id."_token"]);
			unset($_SESSION[$this->client_id."_token_time"]);
			$this->login_attempted = true;
			$this->login_succeeded = false;
			return $this->login_succeeded;
		}
	}
	
	public function api_request($url="", $request="GET", $data="", $headers=[])
	{
		if(!empty($this->token->expires_in) && ($_SESSION[$this->client_id."_token_time"] + $this->token->expires_in) <= time())
			$this->refresh();
		if($this->login_attempted && !$this->login_succeeded) // If we failed to login, let's not send more failed API queries.
			return $this->token;
		if(!empty($this->token->access_token))
		{
			$response = $this->perform_api_request($url, $request, $data, $headers);
			if(!empty($response->status) && $response->status == 401) // Allegedly supposed to use this, but I don't know that it is reliable: strpos($this->curl->response_headers_raw, "\nWWW-Authenticate:") !== false
         {
            trigger_error("User OAuth2 access token needed a refresh even though it wasn't detected by the automatic refresh.", E_USER_NOTICE);
				if($this->refresh())
					$response = $this->perform_api_request($url, $request, $data, $headers);
         }
         else if($this->rate_limit->isHit())
         {
            // TODO: Maybe do more than this. If the reset is in a while, this could be rough.
            $wait = $this->rate_limit->waitFor();
            trigger_error("User exceeded the OAuth2 API's rate limit and needed to wait {$wait} seconds.", E_USER_NOTICE);
            $response = $this->perform_api_request($url, $request, $data, $headers);
         }
			return $response;
		}
		else
			return null; //TODO: Might be more useful to return the error response here, but also save it so you don't keep making API queries with the same error.
	}
	
	protected function perform_api_request($url="", $request="GET", $data="", $headers=[])
	{
		$options = [
			CURLOPT_CUSTOMREQUEST => $request,
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_HTTPHEADER => $headers,
         // TODO: Cookie access token might be redundant now with most APIs.
			CURLOPT_COOKIE => "ACCESS_TOKEN=". $this->token->access_token,
		];
      // If the caller didn't provide these required headers, set them now with OAuth2 defaults.
		$hasAuth = false;
		$hasCType = false;
		foreach($options[CURLOPT_HTTPHEADER] as $head)
		{
			if(substr($head, 0, 14) == "Authorization:")
				$hasAuth = true;
			if(substr($head, 0, 13) == "Content-Type:")
				$hasCType = true;
		}
		if(!$hasAuth)
      {
         if(!empty($this->token->token_type))
            // Note: ucfirst() is because Twitch sends "bearer" in lowercase, but requires it to be sent capitalized. Leave it to Twitch to be stupid literally all the time.
            $options[CURLOPT_HTTPHEADER][] = "Authorization: ". ucfirst($this->token->token_type) ." {$this->token->access_token}";
         else
            throw new \Exception("No token type provided by either the calling class or the OAuth 2.0 token.");
      }
		if(!$hasCType)
			$options[CURLOPT_HTTPHEADER][] = "Content-Type: application/json";
		$raw = $this->curl->request($this->api_url . $url, $options);
      $this->rate_limit->loadHeaders($this->curl->getLastHeaders());
		return json_decode($raw);
	}
	
	public function getCodeURL()
	{
		return $this->auth_url ."/oauth2/authorize?response_type=code&client_id=". urlencode($this->client_id) ."&scope=". urlencode(implode(" ", $this->parameters['scope'])) ."&redirect_uri=". urlencode($this->parameters['redirect_uri']) . "&state=". urlencode($this->parameters['state']);
	}
   
   public function getReport()
   {
      $request_info = array_column($this->curl->log, 'request_info');
      return [
         'request_count' => count($this->curl->log),
         'total_request_time' => array_sum(array_column($request_info, 'total_time')),
         'request_log' => $this->curl->log,
      ];
   }
   
   public function getJSONData()
   {
      return [
         'auth_url' => $this->auth_url,
         'api_url' => $this->api_url,
         'client_id' => $this->client_id,
         'scope' => $this->parameters['scope'],
         'redirect_uri' => $this->parameters['redirect_uri'],
         'token' => $this->implicit_token,
      ];
   }
}
