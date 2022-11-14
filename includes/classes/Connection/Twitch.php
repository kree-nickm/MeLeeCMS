<?php
namespace MeLeeCMS\Connection;
use MeLeeCMS\OAuth2\Client;
use MeLeeCMS\OAuth2\ClientRateLimit;
use MeLeeCMS\Database;

class Twitch
{
	public $user;
	public $stored_data;
	public $user_data;
   
	public $api;
	public $login_error = false;
	
	public function __construct($user)
	{
		global $GlobalConfig;
		$this->user = $user;
      $scope = empty($GlobalConfig['twitch_scope']) ? [] : $GlobalConfig['twitch_scope'];
      $redirect_uri = empty($GlobalConfig['twitch_redirect_uri']) ? (empty($_SERVER['HTTPS'])?"http://":"https://").$_SERVER['SERVER_NAME'] : $GlobalConfig['twitch_redirect_uri'];
      $implicit = !empty($GlobalConfig['twitch_implicit_enabled']);
      $this->api = new Client(
         "https://id.twitch.tv",
         $GlobalConfig['twitch_client_id'],
         $GlobalConfig['twitch_client_secret'],
         "authorization_code",
         [
            'scope' => $scope,
            'redirect_uri' => $redirect_uri
         ],
         new ClientRateLimit("Ratelimit-Remaining", "Ratelimit-Reset", "timestamp", "Ratelimit-Limit"),
         $implicit
      );
      $this->api->api_url = "https://api.twitch.tv";
      // TODO: Twitch API mentions something about validating tokens periodically even while the app is not in use, which I haven't been doing.
      
      // See if we're a logged in user.
      if($this->user->isLogged())
      {
         // TODO: With this implementation it's possible for someone to have multiple connected Twitch accounts. Load them all.
         list($id, $data) = $this->user->cms->database->query("SELECT `id`,`data` FROM connections WHERE `user`=${$this->user->getProperty('index')} AND `api`='Twitch'", Database::RETURN_ROW);
         if(!empty($id))
            $this->stored_data = json_decode($data);
      }
      else if(!empty($_SESSION['twitch_id']))
      {
         list($id, $data) = $this->user->cms->database->query("SELECT `id`,`data` FROM connections WHERE `id`=${$_SESSION['twitch_id']} AND `api`='Twitch'", Database::RETURN_ROW);
         if(!empty($id))
            $this->stored_data = json_decode($data);
      }
      
      // Check if we have a valid access token from the API.
      if(!empty($this->api->token->access_token))
      {
         // Check if we have a recent ID stored in the session, and use that instead of bothering the Twitch API.
         if(!empty($id))
         {
            $users = $this->getUsers([$id], 1);
            if(count($users))
               $this->user_data = $users[0];
         }
         else
         {
            $users = $this->request("/helix/users");
            if(!empty($users->data[0]))
            {
               $this->user_data = $users->data[0];
               if(!empty($this->user_data->email))
                  unset($this->user_data->email);
               $mysql_data = [
                  'id' => $this->user_data->id,
                  'login' => $this->user_data->login,
                  'data' => json_encode($this->user_data),
                  'last_api_query' => time(),
               ];
               $this->user->cms->database->insert("twitch_usercache", $mysql_data, true, ['id']);
            }
         }
         
         if(empty($this->user_data->id))
         {
            // Invalid user even though we logged into the API? I don't think this should happen unless maybe we're banned.
            trigger_error("Twitch API would not return data for this user even though we logged in. Response: ". print_r($this->user_data,true), E_USER_ERROR);
            unset($_SESSION['twitch_id']);
         }
         else
         {
            if(empty($this->stored_data))
               $this->updateFollows();
            $_SESSION['twitch_id'] = $this->user_data->id;
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
      // TODO: Might need to unset $_SESSION['twitch_id'] if a logout is triggered from the user.
      
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
	}
	
	public function request($url="", $request="GET", $data="", $headers=[])
	{
		$headers = array_merge($headers, ["Client-Id: {$this->api->client_id}"]);
		return $this->api->api_request($url, $request, $data, $headers);
	}
	
	public function updateFollows()
	{
		$this->stored_data = $this->getPagedResponse("/helix/users/follows?first=100&from_id={$this->user_data->id}");
      // Clean up the result a bit. from_* is always going to be the curent user, we don't need it to be defined in every single element of the following array.
      foreach($this->stored_data as $follow)
      {
         unset($follow->from_id);
         unset($follow->from_name);
         unset($follow->from_login);
      }
      $this->user->cms->database->insert("connection", [
         'api' => "Twitch",
         'id' => $this->user_data->id,
         'data' => json_encode($this->stored_data)
      ], true, ['id','api']);
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
            $userIdsQuoted[$user->user_id] = $this->user->cms->database->quote($user->user_id);
         }
         if(is_object($user) && !empty($user->to_id)) // Array passed in from API "Get Users Follows" when querying users that are being followed
         {
            $userArrs[$user->to_id] = ['id'=>$user->to_id, 'login'=>null];
            $userIdsQuoted[$user->to_id] = $this->user->cms->database->quote($user->to_id);
         }
         if(is_object($user) && !empty($user->from_id)) // Array passed in from API "Get Users Follows" when querying users who are following
         {
            $userArrs[$user->from_id] = ['id'=>$user->from_id, 'login'=>null];
            $userIdsQuoted[$user->from_id] = $this->user->cms->database->quote($user->from_id);
         }
         if(is_object($user) && !empty($user->broadcaster_id)) // Array passed in from API "Get Channel Information" etc
         {
            $userArrs[$user->broadcaster_id] = ['id'=>$user->broadcaster_id, 'login'=>null];
            $userIdsQuoted[$user->broadcaster_id] = $this->user->cms->database->quote($user->broadcaster_id);
         }
         else if(is_object($user) && !empty($user->broadcaster_login)) // Array passed in from API "Search Channels" etc
         {
            $userArrs[$user->broadcaster_login] = ['login'=>$user->broadcaster_login, 'id'=>null];
            $userLoginsQuoted[$user->broadcaster_login] = $this->user->cms->database->quote($user->broadcaster_login);
         }
         if(is_object($user) && !empty($user->creator_id)) // Array passed in from API "Get Clips" etc
         {
            $userArrs[$user->creator_id] = ['id'=>$user->creator_id, 'login'=>null];
            $userIdsQuoted[$user->creator_id] = $this->user->cms->database->quote($user->creator_id);
         }
         if(is_object($user) && !empty($user->moderator_id)) // Array passed in from API "Get Banned Users" etc
         {
            $userArrs[$user->moderator_id] = ['id'=>$user->moderator_id, 'login'=>null];
            $userIdsQuoted[$user->moderator_id] = $this->user->cms->database->quote($user->moderator_id);
         }
         if(is_object($user) && !empty($user->gifter_id)) // Array passed in from API "Get Broadcaster Subscriptions" etc
         {
            $userArrs[$user->gifter_id] = ['id'=>$user->gifter_id, 'login'=>null];
            $userIdsQuoted[$user->gifter_id] = $this->user->cms->database->quote($user->gifter_id);
         }
         else if(is_object($user) && !empty($user->gifter_login)) // Array passed in from API "Check User Subscription" etc
         {
            $userArrs[$user->gifter_login] = ['login'=>$user->gifter_login, 'id'=>null];
            $userLoginsQuoted[$user->gifter_login] = $this->user->cms->database->quote($user->gifter_login);
         }
         if(is_object($user) && !empty($user->login)) // Array passed in from API "Get Users" etc
         {
            $userArrs[$user->login] = ['login'=>$user->login, 'id'=>null];
            $userLoginsQuoted[$user->login] = $this->user->cms->database->quote($user->login);
         }
         if(is_numeric($user))
         {
            $userArrs[$user] = ['id'=>$user, 'login'=>null];
            $userIdsQuoted[$user] = $this->user->cms->database->quote($user);
         }
         else if(is_scalar($user))
         {
            $userArrs[$user] = ['login'=>$user, 'id'=>null];
            $userLoginsQuoted[$user] = $this->user->cms->database->quote($user);
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
      $mysqlResult = $this->user->cms->database->query("SELECT * FROM twitch_usercache WHERE `last_api_query`>". (time()-(int)$updateAge) . (count($where) ? " AND (".implode(" OR ", $where).")" : ""), Database::RETURN_ALL);
      
      $output = [];
      // Build a list of users to query the API for, from the users that were either not in MySQL or had not been queried in $updateAge.
      $usersToFetch = [];
      foreach($userArrs as $user)
      {
         $found = false;
         foreach($mysqlResult as $row)
         {
            if($user['id'] === $row['id'] || $user['login'] === $row['login'])
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
         $responseUsers = $this->request("/helix/users?{$usersAPIQuery}");
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
      
      // Check to see if we actually fetched all the users we wanted. Maybe a user was specified multiple times, but maybe the API did not return them at all, as is the case if they were banned. All these lines of code because Twitch can't be F'd to mark banned users in the API, instead of just pretending they don't exist.
      if(count($mysql_data) != count($usersToFetch))
      {
         $notFetched = array_filter($usersToFetch, function($val) use($mysql_data){
            foreach($mysql_data as $row)
            {
               if($row['id'] === $val['id'] || $row['login'] === $val['login'])
                  return false;
            }
            return true;
         });
         foreach($notFetched as $nf)
         {
            $newRow = $this->user->cms->database->query("SELECT * FROM `twitch_usercache` WHERE ". (!empty($nf['id']) ? "`id`=".$this->user->cms->database->quote($nf['id']) : "`login`=".$this->user->cms->database->quote($nf['login'])), Database::RETURN_ROW);
            if(empty($newRow))
            {
               $newRow = ['last_api_query' => time()];
               $data = [];
               if(!empty($nf['id']))
               {
                  $newRow['id'] = $nf['id'];
                  $data['id'] = $nf['id'];
               }
               if(!empty($nf['login']))
               {
                  $newRow['login'] = $nf['login'];
                  $data['login'] = $nf['login'];
               }
            }
            else
            {
               $newRow['last_api_query'] = time();
               $data = json_decode($newRow['data'], true);
            }
            if(empty($data))
               $data = [];
            $data['type'] = "missing";
            $newRow['data'] = json_encode($data);
            $mysql_data[] = $newRow;
            $output[] = json_decode($newRow['data']);
         }
         if(count($notFetched))
         {
            foreach($notFetched as $k=>$v)
            {
               if(!empty($v['login']))
                  $notFetched[$k] = $v['login'];
               else if(!empty($v['id']))
                  $notFetched[$k] = $v['id'];
            }
            trigger_error("User(s) '". implode("', '", $notFetched) ."' failed to query from the Twitch API. Either invalid user IDs were specified, or the user was removed from Twitch (possiblity temporarily, as in a ban)", E_USER_NOTICE);
         }
      }
      
      if(count($mysql_data))
         $this->user->cms->database->insert("twitch_usercache", $mysql_data, true);
      
      return $output;
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
				trigger_error("Error retreiving results for endpoint '{$endpoint}{$after}'. Response from server: ". print_r($response,true), E_USER_WARNING);
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

	public function getDisplayName($index)
	{
      $id = $this->user->cms->database->query("SELECT `twitch_id` FROM `users` WHERE `index`=". (int)$index, Database::RETURN_FIELD);
      if(!empty($id))
         return $this->user->cms->database->query("SELECT `data`->>'$.display_name' FROM `twitch_usercache` WHERE `id`=". (int)$id, Database::RETURN_FIELD);
      else
         return $index;
	}
}