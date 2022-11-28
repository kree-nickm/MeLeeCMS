<?php
namespace MeLeeCMS\Connection;
use MeLeeCMS\OAuth2\Client;
use MeLeeCMS\OAuth2\ClientRateLimit;
use MeLeeCMS\Database;
// TODO: Twitch API mentions something about validating tokens periodically even while the app is not in use, which I haven't been doing.

class Twitch extends Base
{
  const NAME = "Twitch";
  const AUTH_URL = "https://id.twitch.tv";
  const API_URL = "https://api.twitch.tv";
  
  public function __construct($user)
  {
    $this->rate_limit = new ClientRateLimit("Ratelimit-Remaining", "Ratelimit-Reset", "timestamp", "Ratelimit-Limit");
    parent::__construct($user);
  }
  
  protected function onConnect()
  {
    if(empty($this->api_data))
      $this->updateFollows(false);
  }
  
  public function loadAPISelf()
  {
    // If we have an ID already, use it to get Twitch user data.
    if(!empty($this->api_id))
    {
      $users1 = $this->getUsers([$this->api_id], 1);
      if(count($users1))
      {
        $this->api_id = $users1[0]->id;
        $this->api_self = $users1[0];
        return true;
      }
    }
    // No ID yet, so use the first result from a user API query, which should be us.
    else
    {
      $users2 = $this->request("/helix/users");
      if(!empty($users2->data[0]))
      {
        $this->api_id = $users2->data[0]->id;
        $this->api_self = $users2->data[0];
        // TODO: Move update of twitch_usercache.
        if(!empty($users2->data[0]->email))
          unset($users2->data[0]->email);
        $mysql_data = [
          'id' => $users2->data[0]->id,
          'login' => $users2->data[0]->login,
          'data' => json_encode($users2->data[0]),
          'last_api_query' => time(),
        ];
        $this->user->cms->database->insert("twitch_usercache", $mysql_data, true, ['id']);
        return true;
      }
    }
    return false;
  }
  
  public function updateFollows($save=true)
  {
    $this->api_data = $this->getPagedResponse("/helix/users/follows?first=100&from_id={$this->api_id}");
    // Clean up the result a bit. from_* is always going to be the curent user, we don't need it to be defined in every single element of the following array.
    foreach($this->api_data as $follow)
    {
      unset($follow->from_id);
      unset($follow->from_name);
      unset($follow->from_login);
    }
    if($save)
      $this->save();
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
      //  trigger_error("Invalid user format '". gettype($user) ."' given in the array parameter of TwitchUser->getUsers(): ". print_r($user, true));
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

  public function getDisplayName($index)
  {
    $id = $this->user->cms->database->query("SELECT `twitch_id` FROM `users` WHERE `index`=". (int)$index, Database::RETURN_FIELD);
    if(!empty($id))
      return $this->user->cms->database->query("SELECT `data`->>'$.display_name' FROM `twitch_usercache` WHERE `id`=". (int)$id, Database::RETURN_FIELD);
    else
      return $index;
  }
}