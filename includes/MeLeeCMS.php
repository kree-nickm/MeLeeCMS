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
   const MODE_PAGE = self::SETUP_DATABASE | self::SETUP_SETTINGS | self::SETUP_THEMES | self::SETUP_USER | self::SETUP_PAGES | self::SETUP_PAGE;
   const MODE_ALL  = self::SETUP_DATABASE | self::SETUP_SETTINGS | self::SETUP_THEMES | self::SETUP_USER | self::SETUP_FORMS | self::SETUP_PAGES | self::SETUP_PAGE;
   
	protected $mode;
	protected $settings = [];
	protected $page = [];
	protected $page_title = "";
	protected $current_theme = "";
	protected $page_css = [];
	protected $page_js = [];
	protected $page_xsl = [];
	protected $page_content = [];
	protected $cpanel = false;
	
	public $class_paths = [];
	public $database;
	public $themes = [];
	public $user;
	public $path_info;
	public $refresh_requested = ['strip'=>[]];
	public $temp_data = [];
	public $include_later = [];
	public $pages = [];
	public $special_pages = [];
	public $forms = [];

	public function __construct($mode=self::MODE_ALL)
	{
		// Note: Don't know if we should care about this, but using [] to create arrays means we require PHP>=5.4.0, and it appears in just about every file.
		set_error_handler([$this, "errorHandler"]);
		// Load and validate $GlobalConfig settings.
		global $GlobalConfig;
		require_once(__DIR__ . DIRECTORY_SEPARATOR ."defaultconfig.php");
		if(is_file("config.php"))
		{
			include_once("config.php");
			$GlobalConfig['server_path'] = realpath($GlobalConfig['server_path']) . DIRECTORY_SEPARATOR;
			if(substr($GlobalConfig['url_path'], 0, 1) != "/")
				$GlobalConfig['url_path'] = "/". $GlobalConfig['url_path'];
			if(substr($GlobalConfig['url_path'], -1) != "/")
				$GlobalConfig['url_path'] = $GlobalConfig['url_path'] ."/";
			$this->mode = $mode;
		}
		else
		{
			$this->mode = 0;
		}
      if(!empty($GlobalConfig['force_https']) && empty($_SERVER['HTTPS']))
      {
         header("Location: https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
         exit;
      }
		$this->settings['server_path'] = $GlobalConfig['server_path'];
		$this->settings['url_path'] = $GlobalConfig['url_path'];
		$this->settings['user_system'] = $GlobalConfig['user_system'];
		$this->settings['site_title'] = $GlobalConfig['site_title'];
		$this->settings['default_theme'] = $GlobalConfig['default_theme'];
		$this->settings['cpanel_theme'] = $GlobalConfig['cpanel_theme'];
		$this->settings['index_page'] = $GlobalConfig['index_page'];
		// Setup the initial object properties.
		array_unshift($this->class_paths, __DIR__ . DIRECTORY_SEPARATOR ."classes". DIRECTORY_SEPARATOR);
		if(substr(__DIR__, 0, strlen($GlobalConfig['server_path'])) != $GlobalConfig['server_path'])
			array_unshift($this->class_paths, $GlobalConfig['server_path'] ."includes". DIRECTORY_SEPARATOR ."classes". DIRECTORY_SEPARATOR);
		spl_autoload_register(array($this, "load_class"), true);
		// Setup the rest of MeLeeCMS based on the $mode.
		$this->path_info = (isset($_SERVER['PATH_INFO']) ? substr($_SERVER['PATH_INFO'],1) : "");
		if(($this->mode & self::SETUP_DATABASE) == self::SETUP_DATABASE) $this->setup_database();
		if(($this->mode & self::SETUP_SETTINGS) == self::SETUP_SETTINGS) $this->setup_settings();
		if(($this->mode & self::SETUP_THEMES) == self::SETUP_THEMES) $this->setup_themes();
		if(($this->mode & self::SETUP_USER) == self::SETUP_USER) $this->setup_user();
		if(($this->mode & self::SETUP_FORMS) == self::SETUP_FORMS) $this->setup_forms();
		if(($this->mode & self::SETUP_PAGES) == self::SETUP_PAGES) $this->setup_pages();
		if(($this->mode & self::SETUP_PAGE) == self::SETUP_PAGE) $this->setup_page();
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
         //echo("<!--");
         echo("<pre>");
         print_r($in);
         echo("</pre>");
         //echo("-->");
      }
   }
	
	public function errorHandler($level, $message, $file, $line, $context)
	{
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
		error_log($type .": ". $message ." in ". $file ." on line ". $line);
		if(is_object($this->user) && $this->user->has_permission("ADMIN"))
		{ // TODO: This seems to be causing errors in the XSLT.
			/*$this->addData_protected('errors', [
				'type' => $type,
				'message' => $message,
				'file' => $file,
				'line' => $line,
			], true, true);*/
		}
		if(is_object($this->database) && !empty($this->database->metadata['error_log']) && ($level & (E_NOTICE|E_USER_NOTICE|E_STRICT)) == 0)
		{
			$mysql_data = [
				'time' => time(),
				'user' => 0,
				'level' => $level,
				'type' => $type,
				'message' => $message,
				'file' => $file,
				'line' => $line,
			];
			if(!empty($this->user->get_property('index')))
				$mysql_data['user'] = (int)$this->user->get_property('index');
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
		return empty($this->page[$key]) ? "" : $this->page[$key];
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
		$this->current_theme = $this->verify_theme($this->get_setting('default_theme'));
		return count($this->themes)>0;
	}
	
	public function setup_user()
	{
		global $GlobalConfig;
		session_set_save_handler(new MeLeeSessionHandler($this), true);
		session_name($GlobalConfig['cookie_prefix'] ."sessid");
		session_start();
		if(isset($_SESSION['form_response']))
		{
			$this->addData_protected('form_response', $_SESSION['form_response'], false, true, 3);
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
	
	public function setup_pages()
	{
      global $GlobalConfig;
      if(isset($GlobalConfig['pages']) && is_array($GlobalConfig['pages']))
         foreach($GlobalConfig['pages'] as $id=>$page)
            $this->addPage($page, $id);
		if(is_object($this->database))
		{
         $pages = $this->database->query("SELECT * FROM `pages`", Database::RETURN_ALL);
         foreach($pages as $page)
            $this->addPage($page);
      }
	}
   
	public function addPage($pageData)
   {
      // First figure out if it's a normal page, a special page, or invalid.
      if(!empty($pageData['url']) && is_string($pageData['url']))
      {
         if(empty($this->pages[$pageData['url']]))
         {
            $pageVar = "pages";
            $pageId = $pageData['url'];
            $this->$pageVar[$pageId] = ['url' => $pageData['url']];
         }
         else
         {
            trigger_error("Page already exists with the URL '{$pageData['url']}'. Overwriting pages with MeLeeCMS->addPage() is not allowed.", E_USER_WARNING);
            return false;
         }
      }
      else if(!empty($pageData['select']) && is_callable($pageData['select']) && !empty($pageData['id']))
      {
         if(empty($this->special_pages[$pageData['id']]))
         {
            $pageVar = "special_pages";
            $pageId = $pageData['id'];
            $this->$pageVar[$pageId] = ['id' => $pageData['id'], 'select' => $pageData['select']];
         }
         else
         {
            // TODO: Probably allow it. Will also need to allow special pages to load from database.
            trigger_error("Special page already exists with the ID '{$pageData['id']}'. Overwriting special pages with MeLeeCMS->addPage() is not allowed.", E_USER_WARNING);
            return false;
         }
      }
      else
      {
         trigger_error("Pages must have either a 'url' (non-empty string), or both an 'id' (any) and 'select' (function) parameter.", E_USER_WARNING);
         return false;
      }
      
      // Now figure out if it's a hard-coded PHP file or a file built with the control panel.
      if(!empty($pageData['file']) && is_file("includes". DIRECTORY_SEPARATOR ."pages". DIRECTORY_SEPARATOR . $pageData['file']))
      {
         $this->$pageVar[$pageId]['file'] = $pageData['file'];
         $this->$pageVar[$pageId]['subtheme'] = $pageData['subtheme'] ?? "";
         if(!empty($pageData['css']) || !empty($pageData['js']) || !empty($pageData['xsl']) || !empty($pageData['permission']) || !empty($pageData['content']))
            trigger_error("CSS, JS, XSL, permissions, and content are all ignored when page is loaded from a file, but some of them were provided to a file page.", E_USER_NOTICE);
      }
      else if(isset($pageData['content']))
      {
         $this->$pageVar[$pageId]['title'] = $pageData['title'] ?? $pageData['url'] ?? $pageData['id'];
         $this->$pageVar[$pageId]['subtheme'] = $pageData['subtheme'] ?? "";
         $this->$pageVar[$pageId]['permission'] = $pageData['permission'] ?? 1;
         $this->$pageVar[$pageId]['content'] = $pageData['content'] ?? "";
         // Verify that css, js, and xsl and properly defined.
         foreach(['css','js','xsl'] as $type)
         {
            if(!empty($pageData[$type]))
            {
               if(is_string($pageData[$type]))
                  $this->$pageVar[$pageId][$type] = json_decode($pageData[$type], true);
               else if(!is_array($pageData[$type]))
                  $this->$pageVar[$pageId][$type] = [];
            }
            else
               $this->$pageVar[$pageId][$type] = [];
            if($type != "xsl")
               foreach($this->$pageVar[$pageId][$type] as $k=>$v)
                  if(is_string($v))
                     $this->$pageVar[$pageId][$type][$k] = ['file'=>$v, 'fromtheme'=>true];
         }
      }
      else
      {
         trigger_error("Pages must have either a 'file' (valid file in includes/pages/ directory) or 'content' (serialized PHP objects or blank) parameter.", E_USER_WARNING);
         return false;
      }
      return true;
   }
	
	public function setup_page()
	{
      // Special case for viewing/debugging the special pages.
      if(!empty($_GET['specialPage']) && !empty($this->special_pages[$_GET['specialPage']]))
      {
         $this->page = $this->special_pages[$_GET['specialPage']];
      }
      
      // Normal page request.
      if(empty($this->page))
      {
         $pageId = !empty($this->path_info) ? $this->path_info : $this->get_setting('index_page');
         if(!empty($this->pages[$pageId]))
            $this->page = $this->pages[$pageId];
         // TODO: Allow page to load without loading all pages first. Gotta manually check $GlobalConfig pages and the database.
      }
      
      // Determine any problems with the request.
      if(!empty($this->page))
      {
         if(empty($this->page['permission']) || !empty($this->user) && $this->user->has_permission($this->page['permission']))
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
            if($spage['select']($this) && false)
            {
               $this->page = $spage;
               break;
            }
         }
         if(empty($this->page))
         {
            trigger_error("Page request '{$this->path_info}' couldn't resolve to a page or a special page. HTTP response would have been ". http_response_code() .". Make sure a special page is defined for that response code.", E_USER_WARNING);
            $this->page = [
               'subtheme' => "default",
               'content' => "a:1:{s:7:\"content\";O:9:\"Container\":3:{s:5:\"title\";s:17:\"An Error Occurred\";s:5:\"attrs\";a:0:{}s:7:\"content\";a:1:{s:4:\"text\";O:4:\"Text\":2:{s:4:\"text\";s:45:\"An unknown error occurred. Response code ". http_response_code() .".\";s:5:\"attrs\";a:0:{}}}}}",
               'title' => "Error",
               'css' => [],
               'js' => [],
               'xsl' => [],
            ];
         }
      }
      
      // Finish setting up the page output.
		if(!empty($this->page['file']))
		{
			$file = $this->get_setting("server_path") ."includes". DIRECTORY_SEPARATOR ."pages". DIRECTORY_SEPARATOR . $this->page['file'];
			if(is_file($file))
				$this->include_later[] = $file;
			else
				trigger_error("Page refers to file {$this->page['file']}, but it does not exist.", E_USER_ERROR);
		}
		else
		{
			$this->set_title($this->page['title']);
			$content = unserialize($this->page['content']);
			if(is_array($content))
				foreach($content as $x=>$object)
					$this->add_content($object, $x);
			foreach($this->page['js'] as $js)
				$this->attach_js($js['file'], "", $js['fromtheme']);
			foreach($this->page['css'] as $css)
				$this->attach_css($css['file'], "", $css['fromtheme']);
			foreach($this->page['xsl'] as $xsl)
				$this->attach_xsl($xsl);
		}
	}

	public function set_cpanel($cp)
	{
		return $this->cpanel = (bool)$cp;
	}

	public function set_theme($theme)
	{
		$this->current_theme = $this->verify_theme($theme);
		return $this->current_theme == $theme;
	}

	public function get_theme()
	{
		return $this->current_theme;
	}

	public function set_subtheme($subtheme)
	{
		return $this->page['subtheme'] = $subtheme;
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
	
	protected function addData_protected($index, $data, $allowArray=true, $notCustom=true, $overwriteBehavior=3)
	{
		if($notCustom)
			$target =& $this->temp_data;
		else
		{
			if(!isset($this->temp_data['custom']) || !is_array($this->temp_data['custom']))
				$this->temp_data['custom'] = [];
			$target =& $this->temp_data['custom'];
		}
		if(empty($target[$index]))
			$target[$index] = $data;
		else if($allowArray)
		{
			if(is_array($target[$index]))
				$target[$index][] = $data;
			else
				$target[$index] = [$target[$index], $data];
		}
		else
		{
			if($overwriteBehavior & 1 == 1)
				$target[$index] = $data;
			if($overwriteBehavior & 2 == 2)
				trigger_error("Attempting to set MeLeeCMS ". ($notCustom ? "" : "custom ") ."data with index '". $index ."', but it is already set and isn't allowing an array. ". ($overwriteBehavior&1==1 ? "Overwriting previous data." : "Ignoring new data."));
		}
	}

	public function addData($index, $data, $allowArray=true, $overwriteBehavior=3)
	{
		$this->addData_protected($index, $data, $allowArray, false, $overwriteBehavior);
	}
	
	public function verify_theme($theme)
	{
		if(isset($this->themes[$theme]))
			return $theme;
		else if(isset($this->themes[$this->get_setting('default_theme')]))
			return $this->get_setting('default_theme');
		else if(isset($this->themes["default"]))
			return "default";
		else
		{
			reset($this->themes);
			if(current($this->themes) !== false)
				return key($this->themes);
			else
				return null;
		}
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
		// TODO: Should this even look for XLS files for each element? Basic elements can be in the main XSL file, they don't need their own.
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
		$this->page['theme'] = $this->verify_theme($this->cpanel ? $this->get_setting('cpanel_theme') : $this->get_page('theme'));
		if($subtheme == "")
			$subtheme = $this->get_page("subtheme");
		if($subtheme == "")
			$subtheme = "default";
		$this->current_theme = $this->verify_theme($this->cpanel ? $this->get_setting('cpanel_theme') : $this->current_theme);
		
		$params = [
			'title' => $this->page_title,
			'url_path' => $this->get_setting('url_path'),
			'theme' => $this->current_theme,
			'content' => [],
			'css' => [],
			//'js' => [],
			//'data' => [],
		];
		if(!empty($this->user))
			$this->addData_protected('user', $this->user->myInfo(), false, true, 3);
		if(!empty($_POST))
			$this->addData_protected('post', $_POST, false, true, 3);
		if(!empty($_GET))
			$this->addData_protected('get', $_GET, false, true, 3);
		
		if($subtheme == "__xml") // also check if xml output is allowed
			foreach($this->page_content as $tag=>$content)
				$params['content@class='.get_class($content).($tag?'@id='.$tag:'')][] = $content->build_params();
		else
			foreach($this->page_content as $tag=>$content)
				$params['content@class='.get_class($content).($tag?'@id='.$tag:'')][] = $content->render($subtheme);
		foreach($this->page_css as $css)
			$params['css'][] = [
				'href' => ($css['fromtheme'] ? $this->get_setting('url_path') ."themes/". $this->current_theme ."/css/". $css['href'] : $css['href']),
				'code' => $css['code'],
			];
		$params['js'] = [[
			'code' => "window.MeLeeCMS = new (function MeLeeCMS(){this.url_path=\"". addslashes($this->get_setting('url_path')) ."\";this.theme=\"". addslashes($this->current_theme) ."\";this.data=". json_encode($this->temp_data) ."})();",
		]];
		foreach($this->page_js as $js)
			$params['js'][] = [
				'src' => ($js['fromtheme'] ? $this->get_setting('url_path') ."themes/". $this->current_theme ."/js/". $js['src'] : $js['src']),
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