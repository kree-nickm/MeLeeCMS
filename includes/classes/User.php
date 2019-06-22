<?php

/**
 * Superclass of all classes that can be used to hold user properties.
 * 
 * Any subclass of `User` must be declared in a file with the same name as the class and end in `.php`, ie. `User_MeLeeCMS.php` for the `User_MeLeeCMS` class. Additionally, the class declaration within that file must match the following regex: `/\bclass\s+ClassName\s+extends\s+User\b/i`. Any subclass can be in place of `User`, as long as it extends from `User` and each ancestor follows the same declaration rules.
 * MeLeeCMS also expects that you have a permission constant defined in your class called `PERM_ADMIN`. This is used to determine which users have access to the control panel. Otherwise, the control panel will be entirely unprotected by MeLeeCMS and you must protect it yourself with another method.
 * All permissions must be constants that begin with `PERM_`.
 * Other features of MeLeeCMS, such as the database changelog, require that users can be uniquely identified by the 'index' element of the `$user_info` array. If a user cannot be uniquely identified with said index, then leave the 'index' element empty and the feature in question will use something else.
 */
class User
{
	const PERM_VIEW = 1;
	
	protected $cms;
	protected $logged_in;
	public $user_info;
	protected $obscured_cols = ["permission"];

	public function __construct($cms)
	{
		$this->cms = $cms;
		$this->logged_in = false;
		$this->user_info = self::default_user();
	}

	public static function default_user()
	{
		$result['ip'] = $_SERVER['REMOTE_ADDR'];
		$result['username'] = "guest". rand(1000,9999);
		$result['jointime'] = time();
		$result['permission'] = self::PERM_VIEW;
		return $result;
	}

	public function get_property($property)
	{
		return empty($this->user_info[$property]) ? null : $this->user_info[$property];
	}
	
	public function myInfo()
	{
		$info = [];
		if($this->is_logged())
			$info['logged'] = true;
		foreach($this->user_info as $k=>$v)
		{
			if(!in_array($k, $this->obscured_cols))
				$info[$k] = $v;
		}
		return $info;
	}

	public function has_permission($perm)
	{
		return ($this->get_property('permission') & $perm) == $perm;
	}

	public function is_logged()
	{
		return $this->logged_in;
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
			$dirs = array(__DIR__);
			trigger_error("User has been loaded without MeLeeCMS.", E_USER_NOTICE);
		}
		$ignore_classes = array();
		self::$subclasses = array();
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
	
	/** @var string[] Stores the last result of {@see User::get_permissions()} so that it won't be run more than once per page load. */
	protected static $permissions;
	
	/**
	 * @param MeLeeCMS $cms A reference to the MeLeeCMS object.
	 * @return array An associative array of all permissions defined in the class of the CMS's User handler, or this class if no CMS is given. The keys are the integer representations of the bits that define the permission (powers of 2), and the values are the names of the `PERM_*` constants in the class, with the `PERM_` portion removed.
	 */
	public static function get_permissions($cms)
	{
		if(is_array(self::$permissions))
			return self::$permissions;
		if(is_object($cms) && is_object($cms->user))
			$class = get_class($cms->user);
		else
		{
			$class = self::class;
			trigger_error("User has been loaded without MeLeeCMS.", E_USER_NOTICE);
		}
		self::$permissions = array();
		foreach((new ReflectionClass($class))->getConstants() as $con=>$val)
			if(substr($con, 0, 5) == "PERM_")
				self::$permissions[$val] = substr($con, 5);
		ksort(self::$permissions);
		return self::$permissions;
	}
}