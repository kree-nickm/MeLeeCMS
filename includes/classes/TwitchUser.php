<?php
namespace MeLeeCMS;

class TwitchUser extends User
{
	const PERM_VIEW = 1;
	const PERM_ADMIN = 2;
	
	public $user_api;
	public $login_error = false;

	protected $obscured_cols = ["permission"];
	
	public function __construct($cms)
	{
		global $GlobalConfig;
		$this->cms = $cms;
		if(isset($_REQUEST['logout']))
		{
			session_destroy();
			$this->cms->requestRefresh($cms->get_setting('url_path'), "logout");
		}
		else
		{
			$this->user_api = new OAuth2Client(
            "https://id.twitch.tv",
            $GlobalConfig['twitch_client_id'],
            $GlobalConfig['twitch_client_secret'],
            "authorization_code",
            [
               'scope' => $GlobalConfig['twitch_scope'],
               'redirect_uri' => $GlobalConfig['twitch_redirect_uri']
            ]
         );
			$this->user_api->api_url = "https://api.twitch.tv";
         
         // Check if we have a valid access token from the API.
			if(!empty($this->user_api->token->access_token))
			{
				// Check if we have a recent ID stored in the session, use that instead of bothering the Twitch API again.
				if(!empty($_SESSION['user_data']['twitch_id']))
				{
					$this->user_info = $this->cms->database->query("SELECT * FROM `users` WHERE `twitch_id`=". (int)$_SESSION['user_data']['twitch_id'], Database::RETURN_ROW);
				}
            
				// If we didn't have user info stored for one reason or another, fetch it from the Twitch API.
				if(empty($this->user_info))
				{
					$user_data = $this->api_request("/helix/users");
					if(!empty($user_data->data[0]))
					{
						$user_data = $user_data->data[0];
					}
				}
            
				// At this point, either $this->user_info will be our account data, or $user_data will be a response from the API with basic user data.
				if(!empty($user_data->id))
				{
					// We have a response, which means we need to use it to update the account data from the API and use the updated data.
					$this->cms->database->insert("users", ['twitch_id'=>$user_data->id, 'jointime'=>time(), 'permission'=>1], false);
               if(!empty($user_data->email))
                  unset($user_data->email);
               $mysql_data = [
                  'id' => $user_data->id,
                  'login' => $user_data->login,
                  'data' => json_encode($user_data),
                  'last_api_query' => time(),
               ];
					$this->cms->database->insert("custom_twitchusercache", $mysql_data, true);
					$this->user_info = $this->cms->database->query("SELECT * FROM `users` WHERE `twitch_id`=". (int)$user_data->id, Database::RETURN_ROW);
				}
            
				// At this point, $this->user_info should contain our account data. If it doesn't, then an error must have occurred with the API.
				if(is_array($this->user_info))
				{
					$this->logged_in = true;
					$_SESSION['user_data']['twitch_id'] = $this->user_info['twitch_id'];
				}
				else
				{
					if(!empty($user_data))
						$this->login_error = $user_data;
					unset($_SESSION['user_data']);
				}
			}
			else if(!empty($this->user_api->token) && is_object($this->user_api->token))
			{
				// We don't have a valid access token, but we have an error response from the API.
				$this->login_error = $this->user_api->token;
			}
         
			// At this point, $this->user_info should contain our account data. If it doesn't, then there simply is no account data to get.
			if(is_array($this->user_info))
			{
				if(!empty($this->user_info['custom_data']))
					$this->user_info['custom_data'] = json_decode($this->user_info['custom_data'], true);
				else
					$this->user_info['custom_data'] = [];
				
				if(!empty($this->user_info['follows']))
					$this->user_info['follows'] = json_decode($this->user_info['follows']);
				else
					$this->user_info['follows'] = [];
            
            $tzset = date_default_timezone_set($this->user_info['timezone']);
            if(!$tzset)
            {
               // TODO: Some sort of code that determines user timezone until they manually set it.
            }
			}
			else
			{
				$this->user_info = [];
				$this->user_info['permission'] = self::PERM_VIEW;
				$this->user_info['custom_data'] = [];
				$this->user_info['follows'] = [];
				$this->logged_in = false;
			}
			
			$this->user_info['ip'] = $_SERVER['REMOTE_ADDR'];
			if(!empty($this->user_api->error['code']))
			{
				switch($this->user_api->error['code'])
				{
					case OAuth2Client::E_STATE_MISMATCH:
						$sessions = $this->cms->database->query("SELECT `session_id` FROM sessions WHERE `ip`=". $this->cms->database->quote($_SERVER['REMOTE_ADDR']), Database::RETURN_COLUMN);
						trigger_error("State mismatch during login from \"". $_SERVER['REMOTE_ADDR'] ."\". Expected \"". $this->user_api->error['expected'] ."\" but got \"". $this->user_api->error['got'] ."\". Either someone is trying to break something, or two different sessions were created for this person during the login attempt. Current session is \"". session_id() ."\". That IP has the following sessions currently open:\n". implode("\n", $sessions), E_USER_WARNING);
						break;
					case OAuth2Client::E_FAILED_LOGIN:
						trigger_error("Login failed after code was obtained from OAuth2. Response from server: ". print_r($this->user_api->error['token'],true) ."\n Header from that response: \n". $this->user_api->error['lastheader'], E_USER_WARNING);
						break;
				}
			}
		}
	}
	
	public function api_request($url="", $request="GET", $data="", $headers=[])
	{
		global $GlobalConfig;
		$headers = array_merge($headers, ["Client-ID: {$GlobalConfig['twitch_client_id']}"]);
		if(substr($url, 0, 8) == "/kraken/")
			$headers = array_merge($headers, ["Accept: application/vnd.twitchtv.v5+json", "Authorization: OAuth ". $this->user_api->token->access_token]);
		else if(substr($url, 0, 7) == "/helix/")
			$headers = array_merge($headers, ["Authorization: Bearer ". $this->user_api->token->access_token]);
		return $this->user_api->api_request($url, $request, $data, $headers);
	}
	
	public function updateFollows()
	{
		$this->user_info['follows'] = $this->getPagedResponse("/helix/users/follows?first=100&from_id={$this->user_info['twitch_id']}");
		$this->cms->database->insert("users", ['index'=>$this->user_info['index'], 'follows'=>json_encode($this->user_info['follows'])], true, ['index']);
	}
   
   public function getUser($updateAge=86400)
   {
      if(!empty($this->user_info['twitch_id']))
      {
      }
   }
   
   public function getUsers($users=[], $updateAge=86400)
   {
      // Divvy up the users array by the data type given so we can query MySQL, and also store them in a normalized array for later.
      $userArrs = [];
      $userIdsQuoted = [];
      $userLoginsQuoted = [];
      foreach($users as $user)
      {
         if(is_object($user) && !empty($user->user_id)) // Array passed in from API "Get Followed Streams" etc
         {
            $userArrs[$user->user_id] = ['id'=>$user->user_id, 'login'=>null];
            $userIdsQuoted[$user->user_id] = $this->cms->database->quote($user->user_id);
         }
         if(is_object($user) && !empty($user->to_id)) // Array passed in from API "Get Users Follows" when querying users that are being followed
         {
            $userArrs[$user->to_id] = ['id'=>$user->to_id, 'login'=>null];
            $userIdsQuoted[$user->to_id] = $this->cms->database->quote($user->to_id);
         }
         if(is_object($user) && !empty($user->from_id)) // Array passed in from API "Get Users Follows" when querying users who are following
         {
            $userArrs[$user->from_id] = ['id'=>$user->from_id, 'login'=>null];
            $userIdsQuoted[$user->from_id] = $this->cms->database->quote($user->from_id);
         }
         if(is_object($user) && !empty($user->broadcaster_id)) // Array passed in from API "Get Channel Information" etc
         {
            $userArrs[$user->broadcaster_id] = ['id'=>$user->broadcaster_id, 'login'=>null];
            $userIdsQuoted[$user->broadcaster_id] = $this->cms->database->quote($user->broadcaster_id);
         }
         else if(is_object($user) && !empty($user->broadcaster_login)) // Array passed in from API "Search Channels" etc
         {
            $userArrs[$user->broadcaster_login] = ['login'=>$user->broadcaster_login, 'id'=>null];
            $userLoginsQuoted[$user->broadcaster_login] = $this->cms->database->quote($user->broadcaster_login);
         }
         if(is_object($user) && !empty($user->creator_id)) // Array passed in from API "Get Clips" etc
         {
            $userArrs[$user->creator_id] = ['id'=>$user->creator_id, 'login'=>null];
            $userIdsQuoted[$user->creator_id] = $this->cms->database->quote($user->creator_id);
         }
         if(is_object($user) && !empty($user->moderator_id)) // Array passed in from API "Get Banned Users" etc
         {
            $userArrs[$user->moderator_id] = ['id'=>$user->moderator_id, 'login'=>null];
            $userIdsQuoted[$user->moderator_id] = $this->cms->database->quote($user->moderator_id);
         }
         if(is_object($user) && !empty($user->gifter_id)) // Array passed in from API "Get Broadcaster Subscriptions" etc
         {
            $userArrs[$user->gifter_id] = ['id'=>$user->gifter_id, 'login'=>null];
            $userIdsQuoted[$user->gifter_id] = $this->cms->database->quote($user->gifter_id);
         }
         else if(is_object($user) && !empty($user->gifter_login)) // Array passed in from API "Check User Subscription" etc
         {
            $userArrs[$user->gifter_login] = ['login'=>$user->gifter_login, 'id'=>null];
            $userLoginsQuoted[$user->gifter_login] = $this->cms->database->quote($user->gifter_login);
         }
         if(is_object($user) && !empty($user->login)) // Array passed in from API "Get Users" etc
         {
            $userArrs[$user->login] = ['login'=>$user->login, 'id'=>null];
            $userLoginsQuoted[$user->login] = $this->cms->database->quote($user->login);
         }
         if(is_numeric($user))
         {
            $userArrs[$user] = ['id'=>$user, 'login'=>null];
            $userIdsQuoted[$user] = $this->cms->database->quote($user);
         }
         else if(is_scalar($user))
         {
            $userArrs[$user] = ['login'=>$user, 'id'=>null];
            $userLoginsQuoted[$user] = $this->cms->database->quote($user);
         }
         //if(<everything above failed>)
         //   trigger_error("Invalid user format '". gettype($user) ."' given in the array parameter of TwitchUser->getUsers(): ". print_r($user, true));
      }
      // Build the WHERE clause and do the MySQL query.
      $where = [];
      if(count($userIdsQuoted))
         $where[] = "`id` IN (". implode(",", $userIdsQuoted) .")";
      if(count($userLoginsQuoted))
         $where[] = "`login` IN (". implode(",", $userLoginsQuoted) .")";
      $mysqlResult = $this->cms->database->query("SELECT * FROM custom_twitchusercache WHERE `last_api_query`>". (time()-(int)$updateAge) . (count($where) ? " AND (".implode(" OR ", $where).")" : ""), Database::RETURN_ALL);
      
      $output = [];
      // Build a list of user to query the API for, from the users that were either not in MySQL or had not been queried in a day.
      $usersToFetch = [];
      foreach($userArrs as $user)
      {
         $found = false;
         foreach($mysqlResult as $row)
         {
            if($user['id'] == $row['id'] || $user['login'] == $row['login'])
            {
               $found = true;
               $output[] = json_decode($row['data']);
               break;
            }
         }
         if(!$found)
            $usersToFetch[] = $user;
      }

      // Fetch the requested users from the API.
      $mysql_data = [];
      $numQueries = ceil(count($usersToFetch)/100);
      for($i=0; $i<$numQueries; $i++)
      {
         $usersAPIQuery = "";
         for($k=0; $k<100 && count($usersToFetch)>(100*$i+$k); $k++)
         {
            if($k > 0)
               $usersAPIQuery .= "&";
            $user = $usersToFetch[100*$i+$k];
            if(!empty($user['id']))
               $usersAPIQuery .= "id={$user['id']}";
            else if(!empty($user['login']))
               $usersAPIQuery .= "login={$user['login']}";
         }
         $responseUsers = $this->api_request("/helix/users?{$usersAPIQuery}");
         if(is_object($responseUsers))
         {
            if(isset($responseUsers->data) && is_array($responseUsers->data))
            {
               foreach($responseUsers->data as $user)
               {
                  if(!empty($user->id))
                  {
                     if(!empty($user->email))
                        unset($user->email);
                     $mysql_data[] = [
                        'id' => $user->id,
                        'login' => $user->login,
                        'data' => json_encode($user),
                        'last_api_query' => time(),
                     ];
                     $output[] = $user;
                  }
                  else
                     trigger_error("Response from API query to get users (page {$i}) contained an invalid user. Content is: ". print_r($user, true));
               }
            }
            else
               trigger_error("Response from API query to get users (page {$i}) did not contain a data array. Response content is: ". print_r($responseUsers, true));
         }
         else
            trigger_error("Response from API query to get users (page {$i}) is not an object, it is: ". print_r($responseUsers, true));
      }
      if(count($mysql_data))
         $this->cms->database->insert("custom_twitchusercache", $mysql_data, true);
      return $output;
   }
	
   // TODO: Could maybe move the below paged queries to OAuth2Client
	// For a typical Twitch API query where the API returns up to 100 results, and gives you a cursor to retreive additional results if there are more than 100.
	public function getPagedResponse($endpoint="/", $containerKey="data")
	{
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
			$response = $this->api_request("{$endpoint}{$after}");
			if(!empty($response) && is_object($response) && isset($response->$containerKey) && is_array($response->$containerKey))
			{
				if(!empty($response->total))
					$total = $response->total;
				$results = array_merge($results, $response->$containerKey);
			}
			else
			{
				trigger_error("Error retreiving results for endpoint '{$endpoint}{$after}'. Response from server: ". print_r($response,true) ."\n Header from that response: \n". $this->user_api->curl->lastheader, E_USER_WARNING);
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
	
	// For a more advanced Twitch API query where you need to specify a huge list of parameters, and must split them between multiple different queries because of the parameter limit. Additionally, each one of those queries could potentially return multiple pages as above.
	public function getMultiPagedResponse($endpoint="/", $repeatedParam="", $paramValues=[], $paramLimit=100)
	{
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
	
	public function setCustomDataGroup($array)
	{
		foreach($array as $key=>$data)
			$this->setCustomData($key, $data, false);
		$this->saveCustomData();
	}
	
	public function setCustomData($key, $data, $save=true)
	{
		if(!isset($this->user_info['custom_data']) || !is_array($this->user_info['custom_data']))
			$this->user_info['custom_data'] = [];
		$this->user_info['custom_data'][$key] = $data;
		if($save)
			$this->saveCustomData();
		return true;
	}
	
	protected function saveCustomData()
	{
		$this->cms->database->insert("users", ['index'=>$this->user_info['index'], 'custom_data'=>json_encode($this->user_info['custom_data'])], true, ['index']);
	}
	
	public function getCodeURL()
	{
		if(is_object($this->user_api))
			return $this->user_api->getCodeURL();
		else
			return "";
	}
}