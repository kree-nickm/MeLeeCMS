<?php

class OAuth2Client
{
	const E_STATE_MISMATCH = 1;
	const E_FAILED_LOGIN = 2;
	
	public $curl;
	public $url;
	protected $client_id;
	protected $client_secret;
	protected $grant_type;
	protected $parameters = [];
	public $login_attempted = false;
	public $login_succeeded = false;
	public $token;
	public $error = [];
	
	public function __construct($url, $client_id, $client_secret, $grant_type, $parameters=[])
	{
		if(is_array($url))
			$this->url = implode("/", $url);
		else
			$this->url = $url;
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->grant_type = $grant_type;
		if($this->grant_type === "authorization_code")
		{
			$this->parameters['scope'] = $parameters['scope'];
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
			if(!empty($parameters['state']))
				$this->parameters['state'] = $parameters['state'];
			else
				$this->parameters['state'] = md5(session_id());
		}
		else if($this->grant_type === "password")
		{
			$this->parameters['username'] = $parameters['username'];
			$this->parameters['password'] = $parameters['password'];
		}
		
		$this->curl = new CURLWrapper();
		
		if($this->grant_type === "password")
		{
			// TODO: Make sure this isn't run on every single page load.
			if(!$this->login())
			{
				$this->error = [
					'code' => self::E_FAILED_LOGIN,
					'lastheader' => $this->curl->lastheader,
					'curlinfo' => $this->curl->curlinfo,
					'token' => $this->token,
				];
			}
		}
		else if(!empty($_REQUEST['code']) && $_REQUEST['state'] == $this->parameters['state'])
		{
			if($this->login($_REQUEST['code']))
			{
				// Reload to get rid of the REQUEST stuff.
				// Note: MeLeeCMS has its own method for requesting a page refresh, but I think it's fine to do this here.
				header("Location: ". $this->parameters['redirect_uri']);
			}
			else
			{
				// We get a login code and everything seems ok, but OAuth2 API rejects the login.
				$this->error = [
					'code' => self::E_FAILED_LOGIN,
					'lastheader' => $this->curl->lastheader,
					'curlinfo' => $this->curl->curlinfo,
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
		
		if(!empty($_SESSION[$this->client_id."_token"]) && is_object($_SESSION[$this->client_id."_token"]))
			$this->token = $_SESSION[$this->client_id."_token"];
		
		if(!empty($this->token->instance_url))
		{
			// Note: This implementation is Salesforce-specific, so hopefully any other API that uses it follows the same format.
			if(is_array($url))
				$this->url = $this->token->instance_url ."/". $url[1];
			else
				$this->url = $this->token->instance_url . substr($url, strpos($url, "/", strpos($url, "//")+2));
		}
	}
	
	public function logout()
	{
		$this->token = null;
		unset($_SESSION[$this->client_id."_token"]);
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
		$raw = $this->curl->request($this->url ."/oauth2/token", [CURLOPT_POSTFIELDS => $post]);
		if(is_object($this->token = json_decode($raw)))
		{
			if(!empty($this->token->access_token))
			{
				$_SESSION[$this->client_id."_token"] = $this->token;
				$this->login_succeeded = true;
			}
			else
			{
				unset($_SESSION[$this->client_id."_token"]);
				$this->login_succeeded = false;
			}
			$this->login_attempted = true;
			return $this->login_succeeded;
		}
		else
		{
			$this->token = $raw;
			unset($_SESSION[$this->client_id."_token"]);
			$this->login_attempted = true;
			$this->login_succeeded = false;
			return $this->login_succeeded;
		}
	}
	
	public function api_request($url="", $request="GET", $data="")
	{
		if($this->login_attempted && !$this->login_succeeded) // If we failed to login, let's not send more failed API queries.
			return $this->token;
		if(!empty($this->token->access_token))
			return $this->perform_api_request($url, $request, $data);
		else
			return null; //TODO: Might be more useful to return the error response here, but also save it so you don't keep making API queries with the same error.
	}
	
	protected function perform_api_request($url="", $request="GET", $data="")
	{
		$raw = $this->curl->request($this->url . $url, [
			CURLOPT_CUSTOMREQUEST => $request,
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_HTTPHEADER => ["Authorization: Bearer ". $this->token->access_token, "Content-Type: application/json"],
			CURLOPT_COOKIE => "ACCESS_TOKEN=". $this->token->access_token,
		]);
		return json_decode($raw);
	}
	
	public function getCodeURL()
	{
		return $this->url ."/oauth2/authorize?response_type=code&client_id=". urlencode($this->client_id) ."&scope=". urlencode(implode(" ", $this->parameters['scope'])) ."&redirect_uri=". urlencode($this->parameters['redirect_uri']) . "&state=". urlencode($this->parameters['state']);
	}
}