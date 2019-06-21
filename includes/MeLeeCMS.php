<?php
ini_set("display_errors", 0);
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . DIRECTORY_SEPARATOR ."builder_php_errors.log");

/** The current memory usage at the time the page starts loading. */
define("START_MEMORY", memory_get_usage());
/** The current millisecond timestamp at the time the page starts loading. */
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

require_once(__DIR__ . DIRECTORY_SEPARATOR ."functions.php");

/**
 * The core class of MeLeeCMS, containing all of the code that sets up the back-end and handles the displaying of pages.
 */
class MeLeeCMS
{
	protected $mode;
	protected $settings = array();
	protected $page = array();
	protected $page_title = "";
	protected $current_theme = "";
	protected $page_css = array();
	protected $page_js = array();
	protected $page_xsl = array();
	protected $page_content = array();
	protected $cpanel = false;
	
	public $class_paths = array();
	public $database;
	public $themes = array();
	public $user;
	public $path_info;
	public $refresh_requested = ['strip'=>[]];
	public $temp_data = [];

	public function __construct($mode=31)
	{
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
			$this->mode = $mode & 4;
		}
		$this->settings['server_path'] = $GlobalConfig['server_path'];
		$this->settings['url_path'] = $GlobalConfig['url_path'];
		$this->settings['user_system'] = $GlobalConfig['user_system'];
		$this->settings['site_title'] = $GlobalConfig['site_title'];
		$this->settings['default_theme'] = $GlobalConfig['default_theme'];
		$this->settings['cpanel_theme'] = $GlobalConfig['cpanel_theme'];
		// Setup the initial object properties.
		array_unshift($this->class_paths, __DIR__ . DIRECTORY_SEPARATOR ."classes". DIRECTORY_SEPARATOR);
		if(substr(__DIR__, 0, strlen($GlobalConfig['server_path'])) != $GlobalConfig['server_path'])
			array_unshift($this->class_paths, $GlobalConfig['server_path'] ."includes". DIRECTORY_SEPARATOR ."classes". DIRECTORY_SEPARATOR);
		spl_autoload_register(array($this, "load_class"), true);
		// Setup the rest of MeLeeCMS based on the $mode.
		$this->path_info = substr(isset($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : ""), 1);
		if(($this->mode &  1) ==  1) $this->setup_database();
		if(($this->mode &  2) ==  2) $this->setup_settings();
		if(($this->mode &  4) ==  4) $this->setup_themes();
		if(($this->mode &  8) ==  8) $this->setup_user();
		if(($this->mode & 16) == 16) $this->setup_page();
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
			error_log($x);
			return false;
		}
	}
	
	public function setup_settings()
	{
		global $GlobalConfig;
		if(is_object($this->database))
			$settings = $this->database->query("SELECT `setting`,`value` FROM `settings`", Database::RETURN_ALL);
		if(is_array($settings))
		{
			foreach($settings as $s)
				$this->settings[$s['setting']] = $s['value'];
			return true;
		}
		else
		{
			error_log("Unable to load settings table". (is_object($this->database) ? "; SQLSTATE=". $this->database->error[0] .", \"". $this->database->error[2] ."\"" : "") .".");
			return false;
		}
	}
	
	public function setup_themes()
	{
		$dir = dir($this->get_setting('server_path') ."themes");
		while(false !== $file = $dir->read())
		{
			if($file{0} != "." && is_dir($dir->path . DIRECTORY_SEPARATOR . $file))
			{
				$this->themes[$file] = [
					'url_path' => $this->get_setting('url_path') ."themes/". $file ."/",
					'subtheme' => [],
					'css' => [],
					'js' => [],
					'xsl' => [],
				];
				if(is_file($desc_file = $dir->path . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . "description.txt"))
					$this->themes[$file]['description'] = file_get_contents($desc_file);
				if(is_file($thumb_file = $dir->path . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . "thumbnail.png"))
					$this->themes[$file]['thumbnail'] = $this->get_setting('url_path') ."themes/". $file ."/thumbnail.png";
				// Find CSS
				$css_path = $dir->path . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . "css" . DIRECTORY_SEPARATOR;
				if(is_dir($css_path))
				{
					$css_dir = dir($css_path);
					while(false !== ($file2 = $css_dir->read()))
					{
						if(substr($file2, -4) == ".css")
						{
							$this->themes[$file]['css'][] = $file2;
						}
					}
				}
				// Find JS
				$js_path = $dir->path . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . "js" . DIRECTORY_SEPARATOR;
				if(is_dir($js_path))
				{
					$js_dir = dir($js_path);
					while(false !== ($file2 = $js_dir->read()))
					{
						if(substr($file2, -3) == ".js")
						{
							$this->themes[$file]['js'][] = $file2;
						}
					}
				}
				// Find XSL
				$template_path = $dir->path . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR;
				if(is_dir($template_path))
				{
					$template_dir = dir($template_path);
					while(false !== ($file2 = $template_dir->read()))
					{
						if(substr($file2, 0, 9) == "MeLeeCMS-" && substr($file2, -4) == ".xsl")
						{
							$this->themes[$file]['subtheme'][] = ['__attr:name'=>substr($file2, 9, -4), $file2];
						}
						else if(substr($file2, -4) == ".xsl")
						{
							$this->themes[$file]['xsl'][] = $file2;
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
			$this->temp_data['form_response'] = $_SESSION['form_response'];
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
			error_log("Unable to create user object using the '". $this->get_setting('user_system') ."' user system.");
			$this->user = new User($this);
			return false;
		}
	}
	
	public function setup_page()
	{
		if(is_object($this->database))
		{
			$query  = "SELECT * FROM `pages` ";
			if($this->path_info != "")
				$query .= "WHERE `url`=". $this->database->quote($this->path_info) ." ";
			else if(!empty($_GET['specialPage']) && is_numeric($_GET['specialPage']))
				$query = "SELECT * FROM `pages_special` WHERE `index`=". (int)$_GET['specialPage'] ." ";
			else
				$query .= "WHERE `index`=". (int)$this->get_setting('index_page') ." ";
			$query .= "LIMIT 0,1";
			$this->page = $this->database->query($query, Database::RETURN_ROW);
			// Note: build_page() only needs to be called if everything is good. require_permission() calls setup_special_page() on a failure, which calls build_page() for itself.
			if(is_array($this->page))
			{
				if($this->require_permission($this->get_page('permission')))
				{
					$this->build_page();
					return true;
				}
			}
			else
				$this->setup_special_page(2, "Page Not Found");
		}
		else
			// TODO: This is pointless. If there's no database, we can't access the database error page.
			$this->setup_special_page(3, "Database Error");
		return false;
	}
	
	public function setup_special_page($id, $title="")
	{
		if(is_object($this->database))
			$this->page = $this->database->query("SELECT * FROM `pages_special` WHERE `index`=". $id, Database::RETURN_ROW);
		else
			$this->page = ""; // Makes sure the below check will generate the necessary error.
		if(!is_array($this->page))
		{
			error_log("No '". $title ."' page data (". $id .") found.");
			$this->page = array('title' => $title);
		}
		$this->build_page();
		// TODO: Return, maybe?
	}
	
	protected function build_page()
	{
		$this->set_title($this->get_page('title'));
		$content = unserialize($this->get_page('content'));
		if(is_array($content))
			foreach($content as $x=>$object)
				$this->add_content($object, $x);
		// Note: The above may be similar to unserializing content directly into $page_content, but the key difference is add_content() sets the $cms reference and makes sure $x is not an integer.
		$page_js = json_decode($this->get_page('js'), true);
		if(is_array($page_js)) foreach($page_js as $js)
			$this->attach_js($js['file'], "", $js['fromtheme']);
		$page_css = json_decode($this->get_page('css'), true);
		if(is_array($page_css)) foreach($page_css as $css)
			$this->attach_css($css['file'], "", $css['fromtheme']);
		$page_xsl = json_decode($this->get_page('xsl'), true);
		if(is_array($page_xsl)) foreach($page_xsl as $xsl)
			$this->attach_xsl($xsl);
		// TODO: Return, maybe?
	}

	public function require_permission($level=0)
	{
		if($level == 0 || $this->user->has_permission($level))
			return true;
		$this->setup_special_page(1, "Permission Denied");
		return false;
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
			$this->page_xsl[] = array('href'=>$href, 'fromtheme'=>$fromtheme);
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
			error_log("'". $content ."' is not a subclass of Content, cannot add it as content (". $x .").");
			return null;
		}
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
		{
			error_log("No stylesheet found for ". $class ." (". $subtheme ."), returning raw data instead.");
			return $data;
		}
		//echo("<!-- ". $class ."-". $subtheme ." => ". $file .": ". print_r($data, true) ." -->");
		return $trans->transform($data, $class, $this->page_xsl);
	}

	public function render($subtheme="")
	{
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
		$params = array();
		if(!empty($this->user))
			$params['user'] = $this->user->myInfo();
		if(!empty($this->temp_data['form_response']))
			$params['form_response'] = $this->temp_data['form_response'];
		if($subtheme == "__xml") // also check if xml output is allowed
			foreach($this->page_content as $tag=>$content)
				$params['content@class='.get_class($content).($tag?'@id='.$tag:'')][] = $content->build_params();
		else
			foreach($this->page_content as $tag=>$content)
				$params['content@class='.get_class($content).($tag?'@id='.$tag:'')][] = $content->render($subtheme);
		$params['title'] = $this->page_title;
		$params['css'] = array();
		foreach($this->page_css as $css)
			$params['css'][] = array(
				'href' => ($css['fromtheme'] ? $this->get_setting('url_path') ."themes/". $this->current_theme ."/css/". $css['href'] : $css['href']),
				'code' => $css['code'],
			);
		$params['js'] = array();
		foreach($this->page_js as $js)
			$params['js'][] = array(
				'src' => ($js['fromtheme'] ? $this->get_setting('url_path') ."themes/". $this->current_theme ."/js/". $js['src'] : $js['src']),
				'code' => $js['code'],
			);
		$params['content'] = array();
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