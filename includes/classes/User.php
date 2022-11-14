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
	protected $cms;
	protected $logged_in;
	protected $permission_defs;
	public $user_info;
	protected $obscured_cols = [];

	public function __construct($cms)
	{
      global $GlobalConfig;
      $this->permission_defs = $GlobalConfig['permissions'];
		$this->cms = $cms;
		$this->logged_in = false;
		$this->user_info = self::getDefaultUser();
      if(!empty($GlobalConfig['admin_ip']) && $_SERVER['REMOTE_ADDR'] == $GlobalConfig['admin_ip'])
         $result['permissions'] = ["ADMIN"];
	}

	public function get_property($property)
   {
      trigger_error("User->get_property() is deprecated; use User->getProperty() instead.", E_USER_DEPRECATED);
      return $this->getProperty($property);
   }
   
	public function getProperty($property)
	{
		return empty($this->user_info[$property]) ? null : $this->user_info[$property];
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
      trigger_error("User->has_permission() is deprecated; use User->hasPermission() instead.", E_USER_DEPRECATED);
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
      trigger_error("User->is_logged() is deprecated; use User->isLogged() instead.", E_USER_DEPRECATED);
      return $this->isLogged();
   }
   
	public function isLogged()
	{
		return $this->logged_in;
	}

	public static function default_user()
   {
      trigger_error("User::default_user() is deprecated; use User::getDefaultUser() instead.", E_USER_DEPRECATED);
      return self::getDefaultUser();
   }
   
	public static function getDefaultUser()
	{
		$result['ip'] = $_SERVER['REMOTE_ADDR'];
		$result['username'] = "guest". rand(1000,9999);
		$result['jointime'] = time();
		$result['permissions'] = ["ANON"];
		return $result;
	}

	public function getDisplayName($index)
	{
		return $index;
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
					if($file{0} != ".")
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