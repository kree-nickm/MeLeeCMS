<?php

/**
 * Superclass of all classes that can be added to a page as content.
 * 
 * Any subclass of `Content` must be declared in a file with the same name as the class and end in `.php`, ie. `Text.php` for the `Text` class. Additionally, the class declaration within that file must match the following regex: `/\bclass\s+ClassName\s+extends\s+Content\b/i`.
 * In addition to implementing the abstract functions of Content, the subclass should have a constructor that can be called with no parameters. In other words, make sure every parameter of the constructor has a default value specified.
 * Immediately after any object that extends Content is created, you should call `set_cms()` on it with a reference to the active MeLeeCMS object, so that the `render()` function will work, among other reasons. Adding such an object to MeLeeCMS with add_content will do this automatically.
 */
abstract class Content
{
	protected $cms;
	
	public abstract function build_params();
	
	/**
	 * Returns the class' important properties that contain the data that users need to define.
	 * If not overwritten, this function will return an array of each object property except for the internal MeLeeCMS reference. The array is associative, with the keys being the names of the properties and the values being their types (string, boolean, etc). The keys will also be used by __sleep() when the object is serialized. The values will be used by the control panel to determine how users will see the property and define it.
	 * Subclasses should overwrite this function in order to provide more specificity with the property types if needed, as well as have better control over which properties are returned, if there are some that need to be excluded.
	 * @return string[] An associative array with each key being a property name and each value being that property's type.
	 */
	public function get_properties()
	{
		$result = [];
		foreach(get_object_vars($this) as $p=>$v)
			if($p != "cms")
				$result[$p] = [];
		return $result;
	}

	public function render($subtheme="default")
	{
		return $this->cms->parse_template($this->build_params(), get_class($this), $subtheme);
	}
	
	/**
	 * Sets the internal reference to the active MeLeeCMS object.
	 *
	 * The MeLeeCMS object reference must be set with this function before render() will work.
	 * @return Content A reference to this Content object.
	 */
	public function set_cms($cms)
	{
		$this->cms = $cms;
		return $this;
	}
	
	public function __sleep()
	{
		return array_keys($this->get_properties());
	}
	
	public function __wakeup()
	{
		// This will fix problems with property visibility in case the class changed since this object was serialized.
		foreach(get_object_vars($this) as $p=>$v)
			$this->$p = $v;
	}
	
	/** @var string[] Stores the last result of {@see Content::get_subclasses()} so that it won't be run more than once per page load. */
	protected static $subclasses;
	
	/**
	 * @param MeLeeCMS $cms A reference to the MeLeeCMS object.
	 * @return string[] A list of all includeable classes that extend Content, and thus are valid classes to add to a page as content.
	 */
	public static function get_subclasses($cms)
	{
		if(is_array(self::$subclasses))
			return self::$subclasses;
		if(is_object($cms) && is_array($cms->class_paths))
			$dirs = array_unique($cms->class_paths);
		else
		{
			$dirs = [__DIR__];
			trigger_error("Content has been loaded without MeLeeCMS.", E_USER_NOTICE);
		}
		$ignore_classes = [];
		self::$subclasses = [];
		$regex_list = "Content";
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
								$regex_list = "Content|". implode("|", self::$subclasses);
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