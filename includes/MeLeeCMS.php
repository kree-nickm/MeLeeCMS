<?php
ini_set("display_errors", 0);
ini_set("log_errors", 1);
// Note: Don't know if we should care about this, but using __DIR__ means we require PHP>=5.3.0, and it appears in multiple files.
$error_dir = __DIR__ . DIRECTORY_SEPARATOR ."logs". DIRECTORY_SEPARATOR ."errors-". date("Y-m");
$error_file = $error_dir . DIRECTORY_SEPARATOR . date("Y-m-d") .".log";
if(!is_dir($error_dir))
{
   mkdir($error_dir, 0770, true);
}
if(!is_file($error_file))
{
   touch($error_file);
   chmod($error_file, 0660);
}
ini_set("error_log", $error_file);
// Note: Permissions for the log directory and log files is going to be totally screwed. They will be owned by the PHP/Apache user, and the group will also be the PHP/Apache group like every other file here, so the actual logged-in linux user is just SOL. No clue how to fix this other than with a root script, way outside the jurisdiction of this CMS.

/** Define these for PHP<5.3.0, just in case we ever care about backwards-compatibility. */
defined("E_DEPRECATED") OR define("E_DEPRECATED", 8192);
defined("E_USER_DEPRECATED") OR define("E_USER_DEPRECATED", 16384);
/** The current memory usage at the time the page starts loading. */
define("START_MEMORY", memory_get_usage());
/** The current microsecond timestamp at the time the page starts loading. */
define("START_TIME", microtime(true));
/** Prints out the time elapsed and net memory usage since the page first started loading, in the form of an HTML comment. */
function print_load_statistics()
{
	$time = (round((microtime(true) - START_TIME)*1000000)/1000) ." ms";
	
	$mem = memory_get_usage() - START_MEMORY;
	if($mem > 1048576*1.5)
		$mem = round($mem/1048576, 3) ." MB";
	else if($mem > 1024*1.5)
		$mem = round($mem/1024, 2) ." kB";
	else
		$mem = $mem ." B";
	
	$peak = memory_get_peak_usage() - START_MEMORY;
	if($peak > 1048576*1.5)
		$peak = round($peak/1048576, 3) ." MB";
	else if($peak > 1024*1.5)
		$peak = round($peak/1024, 2) ." kB";
	else
		$peak = $peak ." B";
	
	echo("<!-- MeLeeCMS Load Statistics; Time: ". $time .", Memory: ". $mem ." (Peak: ". $peak .") -->");
}

/**
 * The core class of MeLeeCMS, containing all of the code that sets up the back-end and handles the displaying of pages.
 */
class MeLeeCMS
{
   const SETUP_DATABASE = 1;
   const SETUP_SETTINGS = 2;
   const SETUP_THEMES = 4;
   const SETUP_USER = 8;
   const SETUP_FORMS = 32;
   const SETUP_PAGES = 64;
   const SETUP_PAGE = 16;
   
   const MODE_AUTH = self::SETUP_DATABASE | self::SETUP_SETTINGS | self::SETUP_USER;
   const MODE_FORM = self::SETUP_DATABASE | self::SETUP_SETTINGS | self::SETUP_USER | self::SETUP_FORMS;
   const MODE_PAGE = self::SETUP_DATABASE | self::SETUP_SETTINGS | self::SETUP_THEMES | self::SETUP_USER | self::SETUP_PAGE;
   const MODE_ALL  = self::SETUP_DATABASE | self::SETUP_SETTINGS | self::SETUP_THEMES | self::SETUP_USER | self::SETUP_FORMS | self::SETUP_PAGES | self::SETUP_PAGE;
   
	protected $mode;
	protected $settings = [];
	protected $page_title = "";
	protected $current_theme = "";
	protected $page_css = [];
	protected $page_js = [];
	protected $page_xsl = [];
	protected $page_content = [];
	protected $cpanel;
	
	public $class_paths = [];
	public $path_info;
	public $database;
	public $user;
	public $page;
	public $themes = [];
	public $pages = [];
	public $special_pages = [];
	public $forms = [];
	public $temp_data = [];
	public $include_later = [];
	public $refresh_requested = ['strip'=>[]];

	public function __construct($mode=self::MODE_ALL)
	{
		$this->mode = $mode;
		// Note: Don't know if we should care about this, but using [] to create arrays means we require PHP>=5.4.0, and it appears in just about every file.
		set_error_handler([$this, "errorHandler"]);
      
		// Load and validate $GlobalConfig settings.
		global $GlobalConfig;
		require_once(__DIR__ . DIRECTORY_SEPARATOR ."defaultconfig.php");
      // TODO: Move config.php into includes directory. No reason it should be in the unprotected website root.
		include_once("config.php");
      
      if(!empty($GlobalConfig['force_https']) && empty($_SERVER['HTTPS']))
      {
         header("Location: https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
         exit;
      }
      
      // Store most of the settings from the config file in MeLeeCMS.
		$this->settings['server_path'] = realpath($GlobalConfig['server_path']) . DIRECTORY_SEPARATOR;
		$this->settings['url_path'] = $GlobalConfig['url_path'];
      if($this->settings['url_path']{0} != "/")
         $this->settings['url_path'] = "/". $this->settings['url_path'];
      if($this->settings['url_path']{-1} != "/")
         $this->settings['url_path'] = $this->settings['url_path'] ."/";
		$this->settings['cpanel_dir'] = $GlobalConfig['cpanel_dir'];
		$this->settings['cookie_prefix'] = $GlobalConfig['cookie_prefix'];
		$this->settings['user_system'] = $GlobalConfig['user_system'];
		$this->settings['site_title'] = $GlobalConfig['site_title'];
		$this->settings['default_theme'] = $GlobalConfig['default_theme'];
		$this->settings['cpanel_theme'] = $GlobalConfig['cpanel_theme'];
		$this->settings['index_page'] = $GlobalConfig['index_page'];
         
		// Setup the load paths for classes.
		array_unshift($this->class_paths, __DIR__ . DIRECTORY_SEPARATOR ."classes". DIRECTORY_SEPARATOR);
		if(substr(__DIR__, 0, strlen($this->settings['server_path'])) != $this->settings['server_path'])
			array_unshift($this->class_paths, $this->settings['server_path'] ."includes". DIRECTORY_SEPARATOR ."classes". DIRECTORY_SEPARATOR);
		spl_autoload_register(array($this, "load_class"), true);
      
      // Detmine if we are in the control panel.
      $this->cpanel = (dirname($_SERVER['SCRIPT_FILENAME']) == $this->settings['server_path'] . $this->settings['cpanel_dir']);
      
		// Setup the rest of MeLeeCMS based on the $mode.
		$this->path_info = (isset($_SERVER['PATH_INFO']) ? substr($_SERVER['PATH_INFO'],1) : "");
		if(($this->mode & self::SETUP_DATABASE) > 0)
         $this->setup_database();
		if(($this->mode & self::SETUP_SETTINGS) > 0)
         $this->setup_settings();
		if(($this->mode & self::SETUP_THEMES) > 0)
         $this->setup_themes();
		if(($this->mode & self::SETUP_USER) > 0)
         $this->setup_user();
		if(($this->mode & self::SETUP_FORMS) > 0)
         $this->setup_forms();
		if(($this->mode & (self::SETUP_PAGES | self::SETUP_PAGE)) > 0)
         $this->setup_special_pages();
		if(($this->mode & self::SETUP_PAGES) > 0)
         $this->setup_pages();
		if(($this->mode & self::SETUP_PAGE) > 0)
         $this->setup_page();
      
      // If a refresh was requested at some point during initialization, do it now.
		if(isset($this->refresh_requested['url']))
			$this->refreshPage();
	}
	
	public function requestRefresh($destination=null, $strip_query=[])
	{
		if($destination === null)
			$destination = $_SERVER['REQUEST_URI'];
		if(empty($this->refresh_requested['url']) || $destination !== $_SERVER['REQUEST_URI'] && ($destination !== $_SERVER['HTTP_REFERER'] || $this->refresh_requested['url'] === $_SERVER['REQUEST_URI']))
			$this->refresh_requested['url'] = $destination;
		if(is_array($strip_query))
			$this->refresh_requested['strip'] = array_merge($this->refresh_requested['strip'], $strip_query);
		else if(!empty($strip_query))
			$this->refresh_requested['strip'][] = $strip_query;
		//register_shutdown_function([$this, "refreshPage"]);
	}
	
	public function refreshPage()
	{
		if(isset($this->refresh_requested['url']))
		{
			$url = $this->refresh_requested['url'];
			foreach($this->refresh_requested['strip'] as $param)
			{
				$url = str_replace("?".$param."&", "?", $url);
				$url = preg_replace("/[?&]". $param ."\\b/i", "", $url);
			}
			header("Location: ". $url);
		}
	}
   
	public function debugLog(...$input)
   {
      foreach($input as $in)
      {
         echo("<!--");
         //echo("<pre>");
         print_r($in);
         //echo("</pre>");
         echo("-->");
      }
   }
	
   
	public function errorHandler($level, $message, $file, $line, $context)
	{
      // TODO: We ignore error_reporting() right now. In the future I think a better approach would be letting the owner set what errors they want reported in config.php, perhaps even three separate times for the three different logs (file, database, XML output).
      // Convert the error level to a string.
		switch($level)
		{
			case E_ERROR: $type = 'E_ERROR'; break;
			case E_WARNING: $type = 'E_WARNING'; break;
			case E_PARSE: $type = 'E_PARSE'; break;
			case E_NOTICE: $type = 'E_NOTICE'; break;
			case E_CORE_ERROR: $type = 'E_CORE_ERROR'; break;
			case E_CORE_WARNING: $type = 'E_CORE_WARNING'; break;
			case E_COMPILE_ERROR: $type = 'E_COMPILE_ERROR'; break;
			case E_COMPILE_WARNING: $type = 'E_COMPILE_WARNING'; break;
			case E_USER_ERROR: $type = 'E_USER_ERROR'; break;
			case E_USER_WARNING: $type = 'E_USER_WARNING'; break;
			case E_USER_NOTICE: $type = 'E_USER_NOTICE'; break;
			case E_STRICT: $type = 'E_STRICT'; break;
			case E_RECOVERABLE_ERROR: $type = 'E_RECOVERABLE_ERROR'; break;
			case E_DEPRECATED: $type = 'E_DEPRECATED'; break;
			case E_USER_DEPRECATED: $type = 'E_USER_DEPRECATED'; break;
			default: $type = 'unknown';
		}
      
      // Write the error to a log file.
		error_log($type .": ". $message ." in ". $file ." on line ". $line);
      
      // Send the error to the XML output if the user has permission to view errors.
		if(is_object($this->user) && $this->user->has_permission("ADMIN"))
		{
			$this->addData_protected('errors', [
				'type' => $type,
				'message' => $message,
				'file' => $file,
				'line' => $line,
			]);
		}
      
      // Log it to the error database, if it exists.
		if(is_object($this->database) && !empty($this->database->metadata['error_log']) && ($level & (E_NOTICE|E_USER_NOTICE|E_STRICT)) == 0)
		{
			$mysql_data = [
				'time' => time(),
				'user' => !empty($this->user->get_property('index')) ? (int)$this->user->get_property('index') : 0,
				'level' => $level,
				'type' => $type,
				'message' => $message,
				'file' => $file,
				'line' => $line,
			];
			$this->database->insert("error_log", $mysql_data, false);
		}
		return true;
	}
	
	public function get_setting($key)
	{
		return $this->settings[$key];
	}
	
	public function get_page($key)
	{
      trigger_error("MeLeeCMS->get_page() is deprecated and will be removed eventually. Access MeLeeCMS->page directly instead.", E_USER_DEPRECATED);
		return empty($this->page->$key) ? "" : $this->page->$key;
	}

	public function load_class($class)
	{
		foreach($this->class_paths as $path)
		{
			if(is_file($path . $class .".php"))
			{
				require_once($path . $class .".php");
				return true;
			}
		}
		throw new Exception("Unable to load class '". $class ."'.");
	}
	
	public function setup_database()
	{
		global $GlobalConfig;
		try
		{
			$this->database = new Database("mysql", $GlobalConfig['dbhost'], $GlobalConfig['dbname'], $GlobalConfig['dbuser'], $GlobalConfig['dbpass'], $this);
			return true;
		}
		catch(PDOException $x)
		{
			trigger_error($x, E_USER_ERROR);
			return false;
		}
	}
	
	public function setup_settings()
	{
      $settings = is_object($this->database) ? $this->database->query("SELECT `setting`,`value` FROM `settings`", Database::RETURN_ALL) : null;
		if(is_array($settings))
		{
			foreach($settings as $s)
				$this->settings[$s['setting']] = $s['value'];
			return true;
		}
		else
		{
			trigger_error("Unable to load settings table; ". (is_object($this->database) ? "SQLSTATE={$this->database->error[0]}, \"{$this->database->error[2]}\"" : "Database is not loaded.") .".", E_USER_ERROR);
			return false;
		}
	}
	
	public function setup_themes()
	{
		$themesDir = dir($this->get_setting('server_path') ."themes");
		while(false !== $theme = $themesDir->read())
		{
         // Check if this entry is a valid directory.
			if($theme{0} != "." && is_dir($themesDir->path . DIRECTORY_SEPARATOR . $theme))
			{
				$this->themes[$theme] = [
					'url_path' => $this->get_setting('url_path') ."themes/". $theme ."/",
					'description' => "",
					'thumbnail' => "",
					'superthemes' => [],
					'subthemes' => [],
					'css' => [],
					'js' => [],
					'xsl' => [],
				];
				if(is_file($desc_file = $themesDir->path . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . "description.txt"))
					$this->themes[$theme]['description'] = file_get_contents($desc_file);
				if(is_file($themesDir->path . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . "thumbnail.png"))
					$this->themes[$theme]['thumbnail'] = $this->get_setting('url_path') ."themes/". $theme ."/thumbnail.png";
				if(is_file($super_file = $themesDir->path . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . "superthemes.png"))
					$this->themes[$theme]['superthemes'] = file($super_file);
				// Find CSS
				$css_path = $themesDir->path . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . "css";
				if(is_dir($css_path))
				{
					$css_dir = dir($css_path);
					while(false !== ($file2 = $css_dir->read()))
					{
						if(substr($file2, -4) == ".css")
						{
							$this->themes[$theme]['css'][] = $file2;
						}
					}
				}
				// Find JS
				$js_path = $themesDir->path . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . "js";
				if(is_dir($js_path))
				{
					$js_dir = dir($js_path);
					while(false !== ($file2 = $js_dir->read()))
					{
						if(substr($file2, -3) == ".js")
						{
							$this->themes[$theme]['js'][] = $file2;
						}
					}
				}
				// Find XSL
				$template_path = $themesDir->path . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR;
				if(is_dir($template_path))
				{
					$template_dir = dir($template_path);
					while(false !== ($file2 = $template_dir->read()))
					{
						if(substr($file2, 0, 9) == "MeLeeCMS-" && substr($file2, -4) == ".xsl")
						{
							$this->themes[$theme]['subthemes'][] = ['__attr:name'=>substr($file2, 9, -4), $file2];
						}
						else if(substr($file2, -4) == ".xsl")
						{
							$this->themes[$theme]['xsl'][] = $file2;
						}
					}
				}
			}
		}
		$this->set_theme($this->get_setting('default_theme'));
		return count($this->themes)>0;
	}
	
	public function setup_user()
	{
		session_set_save_handler(new MeLeeSessionHandler($this), true);
		session_name($this->get_setting('cookie_prefix') ."sessid");
		session_start();
		if(isset($_SESSION['form_response']))
		{
			$this->addData_protected('form_response', $_SESSION['form_response'], false);
			unset($_SESSION['form_response']);
		}
		if($this->get_setting('user_system') == "")
			$user_class = "User";
		else
			$user_class = $this->get_setting('user_system');
		try
		{
			$this->user = new $user_class($this);
			return true;
		}
		catch(Exception $x)
		{
			trigger_error("Unable to create user object using the '". $this->get_setting('user_system') ."' user system.", E_USER_WARNING);
			$this->user = new User($this);
			return false;
		}
	}
	
	public function setup_forms()
	{
      global $GlobalConfig;
      if(isset($GlobalConfig['forms']) && is_array($GlobalConfig['forms']))
         $this->forms = $GlobalConfig['forms'];
	}
	
	public function setup_special_pages()
	{
      global $GlobalConfig;
      // I'm not sure if it's worth it to isolate only the special pages from $GlobalConfig.
      if(isset($GlobalConfig['pages']) && is_array($GlobalConfig['pages']))
         foreach($GlobalConfig['pages'] as $page)
            $this->addPage($page);
	}
	
	public function setup_pages()
	{
      global $GlobalConfig;
		if(is_object($this->database))
		{
         $pages = $this->database->query("SELECT * FROM `pages`", Database::RETURN_ALL);
         foreach($pages as $page)
            $this->addPage($page);
      }
	}
   
	public function addPage($pageData)
   {
      $page = new Page($this, $pageData);
      if($page->is_special)
      {
         if(empty($this->special_pages[$page->id]))
         {
            $this->special_pages[$page->id] = $page;
            return true;
         }
         else
         {
            // TODO: Probably allow it, but would also need to allow special pages to load from database.
            trigger_error("Special page already exists with the ID '{$page->id}'. Overwriting special pages with MeLeeCMS->addPage() is not allowed.", E_USER_WARNING);
            return false;
         }
      }
      else
      {
         if(empty($this->pages[$page->id]))
         {
            $this->pages[$page->id] = $page;
            return true;
         }
         else
         {
            trigger_error("Page already exists with the URL '{$page->id}'. Overwriting pages with MeLeeCMS->addPage() is not allowed.", E_USER_WARNING);
            return false;
         }
      }
   }
	
	public function setup_page()
	{
      global $GlobalConfig;
      // Special case for viewing/debugging the special pages.
      if(!empty($_GET['specialPage']) && !empty($this->special_pages[$_GET['specialPage']]))
      {
         $this->page = $this->special_pages[$_GET['specialPage']];
      }
      
      // Normal page request.
      if(empty($this->page))
      {
         $pageId = !empty($this->path_info) ? $this->path_info : $this->get_setting('index_page');
         // See if the page is already stored in $this->pages.
         if(!empty($this->pages[$pageId]))
            $this->page = $this->pages[$pageId];
         else
         {
            // First check if it's in $GlobalConfig. 
            /* Note: Not needed at the moment, because $GlobalConfig is always stored in $this->pages
            foreach($GlobalConfig['pages'] as $page)
            {
               if(!empty($page['url']) && $page['url'] == $pageId)
               {
                  $this->page = new Page($this, $page);
                  $this->pages[] = $this->page;
                  break;
               }
            }
            */
            // If not, check if its in the database.
            if(empty($this->page))
            {
               $page = $this->database->query("SELECT * FROM `pages` WHERE `url`={$this->database->quote($pageId)}", Database::RETURN_ROW);
               if(!empty($page))
               {
                  $this->page = new Page($this, $page);
                  $this->pages[] = $this->page;
               }
            }
         }
      }
      
      // Determine any problems with the request.
      if(!empty($this->page))
      {
         if(empty($this->page->permission) || !empty($this->user) && $this->user->has_permission($this->page->permission))
         {
            // No problems.
         }
         else if(empty($this->user) || !$this->user->is_logged())
         {
            http_response_code(401);
            $this->page = null;
         }
         else
         {
            http_response_code(403);
            $this->page = null;
         }
      }
      else
      {
         http_response_code(404);
      }
      
      // Resolve problematic requests into a special page.
      if(empty($this->page))
      {
         foreach($this->special_pages as $spage)
         {
            // Note: Page->select a callable property and not a method, so we have to call it like this.
            if(($spage->select)($this))
            {
               $this->page = $spage;
               break;
            }
         }
         if(empty($this->page))
         {
            trigger_error("Page request '{$this->path_info}' couldn't resolve to a page or a special page. HTTP response would have been ". http_response_code() .". Make sure a special page is defined for that response code.", E_USER_WARNING);
            $this->page = new Page($this, false);
         }
      }
      
      // Finish setting up the page output.
      $this->page->loadToCMS();
	}

	public function set_theme($theme)
	{
		if(!empty($this->themes[$theme]))
			$this->current_theme = $theme;
		else if($this->cpanel && !empty($this->themes[$this->get_setting('cpanel_theme')]))
			$this->current_theme = $this->get_setting('cpanel_theme');
		else if(!empty($this->themes[$this->get_setting('default_theme')]))
			$this->current_theme = $this->get_setting('default_theme');
		else if(!empty($this->themes["default"]))
			$this->current_theme = "default";
		else
		{
			reset($this->themes);
			if(!empty(current($this->themes)))
				$this->current_theme = key($this->themes);
			else
				$this->current_theme = null;
		}
		return $this->current_theme;
	}

	public function get_theme()
	{
		return $this->current_theme;
	}

	public function set_subtheme($subtheme)
	{
		return $this->page->subtheme = $subtheme;
	}

	public function set_title($title)
	{
		return $this->page_title = $title ." - ". $this->get_setting('site_title');
	}

	public function attach_css($href="", $code="", $fromtheme=false)
	{
		if($href != "" || $code != "")
		{
			$this->page_css[] = array('href'=>$href, 'code'=>$code, 'fromtheme'=>$fromtheme);
			return true;
		}
		else
			return false;
	}

	public function attach_js($src="", $code="", $fromtheme=false)
	{
		if($src != "" || $code != "")
		{
			$this->page_js[] = array('src'=>$src, 'code'=>$code, 'fromtheme'=>$fromtheme);
			return true;
		}
		else
			return false;
	}

	public function attach_xsl($href="", $code="", $fromtheme=false)
	{
		// TODO: $fromtheme doesn't currently do anything, but it might be totally unnecessary. When would you ever include an XSL stylesheet from outside the theme?
		if($href != "" || $code != "")
		{
			$this->page_xsl[] = array('href'=>$href, 'code'=>$code, 'fromtheme'=>$fromtheme);
			return true;
		}
		else
			return false;
	}

	public function add_content($content, $x="")
	{
		if(is_numeric($x))
			$x = "__". $x;
		else if($x == "")
			$x = "__". count($this->page_content);
		if(is_subclass_of($content, "Content"))
		{
			return $this->page_content[$x] = $content->set_cms($this);
		}
		else
		{
			trigger_error("'". $content ."' is not a subclass of Content, cannot add it as content (". $x .").", E_USER_WARNING);
			return null;
		}
	}
	
   // $allowArray will cause the data at $index to be converted to an array if it isn't already, then add $data to it.
   // $allowOverwrite is only checked if $allowArray is false. Determines whether to overwrite the old data or ignore the new data.
   // $errorIfExists is only checked if $allowArray is false. If the data exists and this is non-zero, an error will be reported at the specified level of this argument.
	protected function addData_protected($index, $data, $allowArray=true, $notCustom=true, $allowOverwrite=true, $errorIfExists=E_USER_NOTICE)
	{
      if(empty($index) || empty($data))
         return false;
      
      // Determine where to store the data ($target)
		if($notCustom)
			$target =& $this->temp_data;
		else
		{
			if(!isset($this->temp_data['custom']) || !is_array($this->temp_data['custom']))
				$this->temp_data['custom'] = [];
			$target =& $this->temp_data['custom'];
		}
      
      // If $data is an array, make sure it is not numerically indexed, as that messes everything up.
      if(is_array($data))
         foreach(array_keys($data) as $key)
            if(is_numeric($key))
            {
               $data["__".$key] = $data[$key];
               unset($data[$key]);
            }
      
		if(empty($target[$index]))
      {
			$target[$index] = $data;
         return true;
      }
		else if($allowArray)
		{
			if(is_array($target[$index]) && !empty($target[$index][0]))
				$target[$index][] = $data;
			else
				$target[$index] = [$target[$index], $data];
         return true;
		}
		else
		{
			if(!empty($errorIfExists))
				trigger_error("Attempting to set MeLeeCMS ". ($notCustom ? "" : "custom ") ."data with index '". $index ."', but it is already set and isn't allowing an array. ". ($allowOverwrite ? "Overwriting previous data." : "Ignoring new data."), $errorIfExists);
			if($allowOverwrite)
         {
				$target[$index] = $data;
            return true;
         }
         else
            return false;
		}
	}

	public function addData($index, $data, $allowArray=true, $allowOverwrite=true, $errorIfExists=E_USER_NOTICE)
	{
		return $this->addData_protected($index, $data, $allowArray, false, $allowOverwrite, $errorIfExists);
	}
	
	public function parse_template($data, $class, $subtheme)
	{
		$trans = new Transformer();
		$path = $this->get_setting('server_path') ."themes". DIRECTORY_SEPARATOR . $this->current_theme . DIRECTORY_SEPARATOR ."templates". DIRECTORY_SEPARATOR;
		if(is_file($file = ($path . $class."-".$subtheme.".xsl")))
			$trans->set_stylesheet("", $file);
		else if(is_file($file = ($path . $class."-default.xsl")))
			$trans->set_stylesheet("", $file);
		else
			return $data;
		// TODO: Should this even look for XSL files for each element? Basic elements can be in the main XSL file, they don't need their own.
		//echo("<!-- ". $class ."-". $subtheme ." => ". $file .": ". print_r($data, true) ." -->");
		return $trans->transform($data, $class, $this->page_xsl);
	}

	public function render($subtheme="")
	{
		if(!is_file("config.php"))
		{
			echo("No configuration file. MeLeeCMS may not have been installed. Refer to the installation instructions.");
			return false;
		}
		if(isset($this->refresh_requested['url']))
		{
			$this->refreshPage();
			return false;
		}
		register_shutdown_function("print_load_statistics");
		if($subtheme == "")
			$subtheme = $this->page->subtheme;
		if($subtheme == "")
			$subtheme = "default";
		
		$params = [
			'title' => $this->page_title,
			'url_path' => $this->get_setting('url_path'),
			'theme' => $this->get_theme(),
			'content' => [],
			'css' => [],
			//'js' => [],
			//'data' => [],
		];
		if(!empty($this->user))
			$this->addData_protected('user', $this->user->myInfo(), false);
		if(!empty($_POST))
			$this->addData_protected('post', $_POST, false);
		if(!empty($_GET))
			$this->addData_protected('get', $_GET, false);
		
		if($subtheme == "__xml") // also check if xml output is allowed
			foreach($this->page_content as $tag=>$content)
				$params['content@class='.get_class($content).($tag?'@id='.$tag:'')][] = $content->build_params();
		else
			foreach($this->page_content as $tag=>$content)
				$params['content@class='.get_class($content).($tag?'@id='.$tag:'')][] = $content->render($subtheme);
		foreach($this->page_css as $css)
			$params['css'][] = [
				'href' => ($css['fromtheme'] ? $this->get_setting('url_path') ."themes/". $this->get_theme() ."/css/". $css['href'] : $css['href']),
				'code' => $css['code'],
			];
		$params['js'] = [[
			'code' => 
            "window.MeLeeCMS = new (function MeLeeCMS(){".
               "this.url_path=\"". addslashes($this->get_setting('url_path')) ."\";".
               "this.theme=\"". addslashes($this->get_theme()) ."\";".
               "this.data=". json_encode($this->temp_data).
            "})();",
		]];
		foreach($this->page_js as $js)
			$params['js'][] = [
				'src' => ($js['fromtheme'] ? $this->get_setting('url_path') ."themes/". $this->get_theme() ."/js/". $js['src'] : $js['src']),
				'code' => $js['code'],
			];
		// TODO: This won't include errors during XSLT conversion. Don't know how to fix that.
		$params['data'] = $this->temp_data;
		if($subtheme == "__xml") // also check if xml output is allowed
		{
			header("Content-type: text/xml");
			echo("<?xml version=\"1.0\"?>");
			echo(Transformer::array_to_xml("MeLeeCMS", $params));
		}
		else
		{
			$html = $this->parse_template($params, "MeLeeCMS", $subtheme);
			if(is_array($html))
				echo("No theme was loaded. There may be an error in the MeLeeCMS setup for this page.");
			else
				echo($html);
		}
		return true;
	}
}