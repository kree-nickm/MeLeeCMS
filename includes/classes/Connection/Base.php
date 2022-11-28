<?php
namespace MeLeeCMS\Connection;
use MeLeeCMS\OAuth2\Client;
use MeLeeCMS\Database;

class Base
{
  const NAME = "";
  const AUTH_URL = "";
  const API_URL = "";
  
  protected $rate_limit;
  
  public $user;
  public $user_id;
  public $api;
  public $api_id;
  public $api_data;
  public $api_self;
  public $loaded = false;
  public $connected = false;
  public $login_error = false;
  
  public function __construct($user)
  {
    $this->user = $user;
    if(!$this->load())
      $this->connect();
  }
  
  public function connect()
  {
    global $GlobalConfig;
    $scope = $GlobalConfig[strtolower(static::NAME)."_scope"];
    $redirect_uri = $GlobalConfig[strtolower(static::NAME)."_redirect_uri"];
    
    // Create a client object, which logs into the API.
    $this->api = new Client(
      static::AUTH_URL,
      $GlobalConfig[strtolower(static::NAME)."_client_id"],
      $GlobalConfig[strtolower(static::NAME)."_client_secret"],
      "authorization_code",
      [
        'scope' => empty($scope) ? [] : $scope,
        'redirect_uri' => empty($redirect_uri) ? (empty($_SERVER['HTTPS'])?"http://":"https://").$_SERVER['SERVER_NAME'] : $redirect_uri
      ],
      $this->rate_limit,
      !empty($GlobalConfig[strtolower(static::NAME)."_implicit_enabled"])
    );
    if(!empty(static::API_URL))
      $this->api->api_url = static::API_URL;
    
    // Check if we have a valid access token from the API.
    if(!empty($this->api->token->access_token))
    {
      $this->connected = true;
      // Load our user data as defined by the API.
      if($this->loadAPISelf())
      {
        $_SESSION[static::NAME.'_id'] = $this->api_id;
        if(!$this->loaded)
          $this->load();
        $this->onConnect();
        return true;
      }
      else
      {
        // Invalid user even though we logged into the API? I don't think this should happen unless maybe we're banned.
        trigger_error(static::NAME ." API would not return data for this user even though we logged in. Response: ". print_r($this->api_self,true), E_USER_ERROR);
        $this->logout();
        $this->connected = false;
      }
    }
    else if(!empty($this->api->token))
    {
      // We don't have a valid access token, but we have an error response from the API.
      $this->login_error = $this->api->token;
    }
    else
    {
      // No API login was attempted. Don't think we need anything here.
    }
    
    if(!empty($this->api->error['code']))
    {
      switch($this->api->error['code'])
      {
        case Client::E_STATE_MISMATCH:
          $sessions = $this->user->cms->database->query("SELECT `session_id` FROM sessions WHERE `ip`=". $this->user->cms->database->quote($_SERVER['REMOTE_ADDR']), Database::RETURN_COLUMN);
          trigger_error("State mismatch during login from \"". $_SERVER['REMOTE_ADDR'] ."\". Expected \"". $this->api->error['expected'] ."\" but got \"". $this->api->error['got'] ."\". Either someone is trying to break something, or two different sessions were created for this person during the login attempt. Current session is \"". session_id() ."\". That IP has the following sessions currently open:\n". implode("\n", $sessions), E_USER_ERROR);
          break;
        case Client::E_FAILED_LOGIN:
          trigger_error("Login failed after code was obtained from OAuth2. Response from server: ". print_r($this->api->error['token'],true) ."\n Header from that response: \n". $this->api->error['lastheader'], E_USER_ERROR);
          break;
      }
    }
    return false;
  }
  
  protected function onConnect()
  {
  }
  
  public function load()
  {
    if($this->user->isLogged())
    {
      $result = $this->user->cms->database->query("SELECT `id`,`data` FROM connections WHERE `user`={$this->user->getProperty('index')} AND `api`={$this->user->cms->database->quote(static::NAME)}", Database::RETURN_ROW);
      if(!empty($result['id']))
      {
        $this->loaded = true;
        $this->user_id = $this->user->getProperty('index');
        $this->api_id = $result['id'];
        $this->api_data = json_decode($result['data']);
        return true;
      }
    }
    else if(!empty($_SESSION[static::NAME.'_id']))
    {
      $id = $_SESSION[static::NAME.'_id'];
      $result = $this->user->cms->database->query("SELECT `user`,`data` FROM connections WHERE `id`={$this->user->cms->database->quote($id)} AND `api`={$this->user->cms->database->quote(static::NAME)}", Database::RETURN_ROW);
      if(!empty($result['user']))
      {
        $this->loaded = true;
        $this->user_id = $result['user'];
        $this->api_id = $id;
        $this->api_data = json_decode($result['data']);
        return true;
      }
    }
    return false;
  }
  
  public function save($log=false)
  {
    if(!empty($this->user->getProperty('index')) && !empty(static::NAME) && !empty($this->api_id))
    {
      $this->user->cms->database->insert("connections", [
        'user' => $this->user->getProperty('index'),
        'api' => static::NAME,
        'id' => $this->api_id,
        'data' => json_encode($this->api_data)
      ], true, ['id','api'], $log);
      return true;
    }
    else
    {
      trigger_error("Failed to save connection; one of these is invalid: User ID: {$this->user->getProperty('index')}, API: ". static::NAME .", API ID: {$this->api_id}", E_USER_WARNING);
      return false;
    }
  }
  
  public function logout()
  {
    if(!empty($this->api))
      $this->api->logout();
    unset($_SESSION[static::NAME.'_id']);
  }
  
  /**
  Loads an object from the current API that represents our own user data as saved on that API.
  Must be implemented by subclasses, since every API will have a different method for retrieving that data.
  @return boolean True if we were able to get the desired response from the API and store it in $this->api_self, false otherwise.
  */
  public function loadAPISelf()
  {
    return false;
  }
  
  public function request($url="", $request="GET", $data="", $headers=[], $id_header="Client-Id")
  {
    if(!$this->connected)
      $this->connect();
    $headers = array_merge($headers, ["{$id_header}: {$this->api->client_id}"]);
    return $this->api->api_request($url, $request, $data, $headers);
  }
  
  // TODO: Could maybe move the below paged queries to OAuth2\Client
  public function getPagedResponse($endpoint="/", $containerKey="data")
  {
    // For a typical Twitch API query where the API returns up to 100 results, and gives you a cursor to retreive additional results if there are more than 100.
    $hasParams = (strpos($endpoint, "?") !== false);
    $results = [];
    $currentCount = 0;
    $total = null;
    $loops = 0;
    $looplimit = 20;
    do
    {
      $previousCount = $currentCount;
      if(!empty($response) && !empty($response->pagination) && !empty($response->pagination->cursor))
      {
        if($hasParams)
          $after = "&after={$response->pagination->cursor}";
        else
          $after = "?after={$response->pagination->cursor}";
      }
      else if(!empty($response) && empty($response->pagination))
      {
        if($hasParams)
          $after = "&offset={$currentCount}";
        else
          $after = "?offset={$currentCount}";
      }
      else
      {
        $after = "";
      }
      $response = $this->request("{$endpoint}{$after}");
      if(!empty($response) && is_object($response) && isset($response->$containerKey) && is_array($response->$containerKey))
      {
        if(!empty($response->total))
          $total = $response->total;
        $results = array_merge($results, $response->$containerKey);
      }
      else
      {
        trigger_error("Error retreiving results for endpoint '{$endpoint}{$after}'. Response from server: ". print_r($response,true) ."\n Header from that response: \n". $this->api->curl->lastheader, E_USER_WARNING);
        break;
      }
      $loops++;
      $currentCount = count($results);
      $hasNextPage = !empty($response) && ($currentCount>$previousCount) && ($total==null || $currentCount<$total) && (empty($response->pagination) || !empty($response->pagination->cursor));
    }while(
      $hasNextPage &&
      $loops < $looplimit
    );
    return $results;
  }
  
  public function getMultiPagedResponse($endpoint="/", $repeatedParam="", $paramValues=[], $paramLimit=100)
  {
    // For a more advanced Twitch API query where you need to specify a huge list of parameters, and must split them between multiple different queries because of the parameter limit. Additionally, each one of those queries could potentially return multiple pages as above.
    $hasParams = (strpos($endpoint, "?") !== false);
    $results = [];
    $paramBlocks = [];
    for($i=0; $i<count($paramValues); $i+=$paramLimit)
    {
      $paramBlocks[] = array_slice($paramValues, $i, $paramLimit);
    }
    foreach($paramBlocks as $block)
    {
      if($hasParams)
        $after = "&{$repeatedParam}=". implode("&{$repeatedParam}=", $block);
      else
        $after = "?{$repeatedParam}=". implode("&{$repeatedParam}=", $block);
      $results = array_merge($results, $this->getPagedResponse("{$endpoint}{$after}"));
    }
    return $results;
  }
}
