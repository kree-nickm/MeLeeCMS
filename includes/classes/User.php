<?php
namespace MeLeeCMS;

/**
 * Superclass of all classes that can be used to hold user properties.
 * 
 * Any subclass of `User` must be declared in a file with the same name as the class and end in `.php`, ie. `MeLeeCMSUser.php` for the `MeLeeCMSUser` class. Additionally, the class declaration within that file must match the following regex: `/\bclass\s+ClassName\s+extends\s+User\b/i`. Any subclass can be in place of `User`, as long as it extends from `User` and each ancestor follows the same declaration rules.
 * Other features of MeLeeCMS, such as the database changelog, require that users can be uniquely identified by the 'index' element of the `$user_info` array. If a user cannot be uniquely identified with said index, then leave the 'index' element empty and the feature in question will use something else.
 */
class User
{
  public $cms;
  protected $logged_in;
  protected $permission_defs;
  public $user_info;
  protected $obscured_cols = [];
  protected $connections = [];

  public function __construct($cms)
  {
    global $GlobalConfig;
    $this->permission_defs = $GlobalConfig['permissions'];
    $this->cms = $cms;
    $this->logged_in = false;
    $this->user_info = self::getDefaultUser();
    
    // Load all enabled connections.
    if($GlobalConfig['twitch_enabled'])
      $this->connections['Twitch'] = new Connection\Twitch($this);
    
    if(isset($_REQUEST['logout']))
    {
      foreach($this->connections as $name=>$connection)
        $connection->logout();
      session_destroy();
      $this->cms->requestRefresh($cms->getSetting('url_path'), "logout");
    }
    else
    {
      /* At this point, here are the possible states for each connection:
        * User is logged in.
          * Connection is loaded. --> Done.
          * No connection to load, but OAuth2 has been authorized.
            1. Create the connection.
            2. Load it.
          * No connection or OAuth2. --> Ignore.
        * User exists but is NOT logged in.
          * Connection is loaded.
            1. Use it to log in.
          * No connection to load, but OAuth2 has been authorized.
            1. User will need to be logged in through something other than this connection.
              ?? Is it possible that now that we've logged in, we can find a connection? That would mean we already had a different connection established to this API than the one we just used.
            2. Create the connection.
            3. Load it.
          * No connection or OAuth2. --> Ignore.
        * User does not exist.
          * OAuth2 has been authorized.
            1. Verify that user can't be logged in through something other than this connection.
            2. Create the user.
            3. Create the connection.
            4. Load it.
          * No OAuth2. --> Ignore.
      */
      
      // See if any connection has our user ID.
      $pending_connections = [];
      foreach($this->connections as $name=>$connection)
      {
        if($connection->loaded)
        {
          if(!$this->logged_in)
          {
            $this->load($connection->user_id);
          }
          else if($connection->user_id !== $this->user_info['index'])
          {
            trigger_error("User ID mismatch between the currently logged in user ({$this->user_info['index']}) and the user ID listed for the {$name} connection ({$connection->user_id})", E_USER_WARNING);
          }
        }
        else if($connection->connected)
        {
          $pending_connections[$name] = $connection;
        }
      }
      
      foreach($pending_connections as $name=>$connection)
      {
        // If we still aren't logged in, then this must be a new user that we need to create.
        if(!$this->logged_in)
        {
          $token = sha1($name . $connection->api_id);
          $mysql_data = [
            'jointime' => time(),
            'permissions' => ["LOGGED"],
            'token' => $token,
          ];
          $this->cms->database->insert("users", $mysql_data, true, [], true);
          $this->load(null, $token);
          $this->cms->database->insert("users", ['index'=>$this->user_info['index'],'token'=>""], true, ['index']);
        }
        $connection->save(true);
        $connection->load();
      }
    }
    
    if(!empty($GlobalConfig['admin_ip']) && $_SERVER['REMOTE_ADDR'] == $GlobalConfig['admin_ip'])
      $this->user_info['permissions'] = ["ADMIN"];
  }
  
  private function load($index, $token=null)
  {
    if(empty($token))
      $this->user_info = $this->cms->database->query("SELECT * FROM `users` WHERE `index`=". (int)$index, Database::RETURN_ROW);
    else
      $this->user_info = $this->cms->database->query("SELECT * FROM `users` WHERE `token`=". $this->cms->database->quote($token), Database::RETURN_ROW);
    if(is_array($this->user_info))
    {
      if(!empty($this->user_info['custom_data']))
        $this->user_info['custom_data'] = json_decode($this->user_info['custom_data'], true);
      else
        $this->user_info['custom_data'] = [];
      
      $this->user_info['permissions'] = json_decode($this->user_info['permissions'], true);
      
      /*$tzset = date_default_timezone_set($this->user_info['timezone']);
      if(!$tzset)
      {
        // TODO: Some sort of code that determines user timezone until they manually set it.
      }*/
      $this->logged_in = true;
    }
  }

  public function get_property($property)
  {
    trigger_error("User->get_property() is deprecated; use User->getProperty() instead", E_USER_DEPRECATED);
    return $this->getProperty($property);
  }
  
  public function getProperty($property)
  {
    return empty($this->user_info[$property]) ? null : $this->user_info[$property];
  }
  
  public function getConnection($name)
  {
    if(isset($this->connections[$name]))
      return $this->connections[$name];
    else
    {
      trigger_error("Invalid connection '{$name}' requested with User->getConnection()", E_USER_ERROR);
      return null;
    }
  }
  
  public function myInfo()
  {
    $info = [];
    if($this->isLogged())
      $info['logged'] = true;
    foreach($this->user_info as $k=>$v)
    {
      if(!in_array($k, $this->obscured_cols))
        $info[$k] = $v;
    }
    $info['class'] = get_class($this);
    $info['permissions'] = [];
    foreach($this->user_info['permissions'] as $perm)
    {
      if(!empty($this->permission_defs[$perm]))
      {
        foreach($this->permission_defs[$perm] as $p)
        {
          $info['permissions'][$p] = true;
        }
      }
      else
      {
        $info['permissions'][$perm] = true;
      }
    }
    return $info;
  }

  public function has_permission(...$permissions)
  {
    trigger_error("User->has_permission() is deprecated; use User->hasPermission() instead", E_USER_DEPRECATED);
    return $this->hasPermission(...$permissions);
  }
  
  public function hasPermission(...$permissions)
  {
    $has_all = true;
    foreach($permissions as $permission)
    {
      if(empty($permission))
        continue;
      $found = false;
      // First check if we have the exact permission.
      if(in_array($permission, $this->user_info['permissions']))
      {
        $found = true;
      }
      else
      {
        // Then check if one of our permission groups contains the permission.
        foreach($this->user_info['permissions'] as $perm_group)
        {
          if(!empty($this->permission_defs[$perm_group]) && in_array($permission, $this->permission_defs[$perm_group]))
          {
            $found = true;
          }
        }
      }
      $has_all &= $found;
    }
    return $has_all;
  }

  public function is_logged()
  {
    trigger_error("User->is_logged() is deprecated; use User->isLogged() instead", E_USER_DEPRECATED);
    return $this->isLogged();
  }
  
  public function isLogged()
  {
    return $this->logged_in;
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

  public function getDisplayName($index)
  {
    return $index;
  }

  public static function default_user()
  {
    trigger_error("User::default_user() is deprecated; use User::getDefaultUser() instead", E_USER_DEPRECATED);
    return self::getDefaultUser();
  }
  
  public static function getDefaultUser()
  {
    $result['ip'] = $_SERVER['REMOTE_ADDR'];
    $result['username'] = "guest". rand(1000,9999);
    $result['jointime'] = time();
    $result['permissions'] = ["ANON"];
    $result['custom_data'] = [];
    return $result;
  }
  
  /** @var string[] Stores the last result of {@see User::get_subclasses()} so that it won't be run more than once per page load. */
  protected static $subclasses;
  
  /**
   * @param MeLeeCMS $cms A reference to the MeLeeCMS object.
   * @return string[] A list of all includeable classes that extend User, and thus can be selected as user systems via the control panel.
   */
  public static function get_subclasses($cms=null)
  {
    if(is_array(self::$subclasses))
      return self::$subclasses;
    if(is_object($cms) && is_array($cms->class_paths))
      $dirs = array_unique($cms->class_paths);
    else
    {
      $dirs = [__DIR__];
      trigger_error("User has been loaded without MeLeeCMS.", E_USER_NOTICE);
    }
    $ignore_classes = [];
    self::$subclasses = [];
    $regex_list = "User";
    do{
      $changed = false;
      foreach($dirs as $dir)
      {
        $dirobj = dir($dir);
        while($file = $dirobj->read())
        {
          if(substr($file, 0, 1) != ".")
          {
            $filepath = $dirobj->path ."/". $file;
            $class = substr($file, 0, -4);
            if(is_dir($filepath))
            {
              $dirs[] = $filepath;
            }
            else if(is_file($filepath) && substr($file, -4) == ".php" && !in_array($class, self::$subclasses) && !in_array($class, $ignore_classes))
            {
              $read = file_get_contents($filepath);
              if(preg_match("/\\bclass\\s+". $class ."\\s+extends\\s+(". $regex_list .")\\b/i", $read))
              {
                self::$subclasses[] = $class;
                $regex_list = "User|". implode("|", self::$subclasses);
                $changed = true;
              }
            }
          }
        }
      }
    }while($changed);
    self::$subclasses = array_unique(self::$subclasses);
    sort(self::$subclasses);
    return self::$subclasses;
  }
}