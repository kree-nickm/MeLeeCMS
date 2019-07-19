<?
class OAuth2Client
{
	const E_STATE_MISMATCH = 1;
	const E_FAILED_LOGIN = 2;
	
	protected $curl;
	public $curlinfo;
	public $lastheader;
	public $url;
	public $client_id;
	public $client_secret;
	public $scopes;
	public $redirect_uri;
	public $state;
	public $login_attempted = false;
	public $login_succeeded = false;
	public $token;
	public $error = [];
	
	public function __construct($url, $client_id, $client_secret, $scopes, $redirect_uri="", $state="")
	{
		$this->url = $url;
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->scopes = $scopes;
		if($redirect_uri != "")
			$this->redirect_uri = $redirect_uri;
		else
		{
			if(($i = strpos($_SERVER['REQUEST_URI'], "?")) !== false)
				$uri = substr($_SERVER['REQUEST_URI'], 0, $i);
			else
				$uri = $_SERVER['REQUEST_URI'];
			$this->redirect_uri = ($_SERVER['HTTPS']==="on" ? "https" : "http") ."://". $_SERVER['HTTP_HOST'] . $uri;
		}
		if($state != "")
			$this->state = $state;
		else
			$this->state = md5(session_id());
		
		if($_REQUEST['code'] != "" && $_REQUEST['state'] == $this->state)
		{
			if($this->login($_REQUEST['code']))
				header("Location: ". $this->redirect_uri);
			else
			{
				// We get a login code and everything seems ok, but OAuth2 API rejects the login.
				$this->error = [
					'code' => self::E_FAILED_LOGIN,
					'lastheader' => $this->lastheader,
					'curlinfo' => $this->curlinfo,
					'token' => $this->token,
				];
			}
		}
		else if($_REQUEST['code'] != "")
		{
			// Someone tried to log in, but there was a state mismatch.
			$this->error = [
				'code' => self::E_STATE_MISMATCH,
				'expected' => $this->state,
				'got' => $_REQUEST['state'],
			];
		}
		
		if(is_object($_SESSION[$this->client_id."_token"]))
			$this->token = $_SESSION[$this->client_id."_token"];
	}
	
	public function initCURL()
	{
		if(!is_object($this->curl))
		{
			$this->curl = curl_init();
			curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, true);
			curl_setopt($this->curl, CURLOPT_HEADER, true);
			curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl, CURLOPT_AUTOREFERER, true);
			curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($this->curl, CURLOPT_MAXREDIRS, 5);
			//curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl, CURLOPT_ENCODING, "");
		}
	}
	
	public function login($code)
	{
		if($this->login_attempted)
			return $this->login_succeeded;
		$this->initCURL();
		curl_setopt($this->curl, CURLOPT_URL, $this->url ."/oauth2/token");
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, "grant_type=authorization_code"
			."&client_id=". urlencode($this->client_id)
			."&client_secret=". urlencode($this->client_secret)
			."&code=". urlencode($code)
			."&redirect_uri=". urlencode($this->redirect_uri)
			."&scope=". urlencode(implode(" ", $this->scopes))
		);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array("Content-type: application/x-www-form-urlencoded"));
		list($this->lastheader, $raw) = explode("\n\n", str_replace("\r", "", curl_exec($this->curl)), 2);
		$this->token = json_decode($raw);
		if($this->token->access_token)
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
	
	public function api_request($url="", $request="GET", $data="")
	{
		if($this->login_attempted && !$this->login_succeeded) // If we failed to login, let's not send more failed API queries.
			return $this->token;
		$this->initCURL();
		if(!empty($this->token->access_token))
			return $this->perform_api_request($url, $request, $data);
		else
			return null; //TODO: Might be more useful to return the error response here, but also save it so you don't keep making API queries with the same error.
	}
	
	protected function perform_api_request($url="", $request="GET", $data="")
	{
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer ". $this->token->access_token, "Content-Type: application/json"));
		//curl_setopt($this->curl, CURLOPT_COOKIE, "ACCESS_TOKEN=". $this->token->access_token);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $request);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($this->curl, CURLOPT_URL, $this->url . $url);
		list($this->lastheader, $raw) = explode("\n\n", str_replace("\r", "", curl_exec($this->curl)), 2);
		$this->curlinfo = curl_getinfo($this->curl);
		return json_decode($raw);
	}
	
	public function getCodeURL()
	{
		return $this->url ."/oauth2/authorize?response_type=code&client_id=". urlencode($this->client_id) ."&scope=". urlencode(implode(" ", $this->scopes)) ."&redirect_uri=". urlencode($this->redirect_uri) . "&state=". urlencode($this->state);
	}
}