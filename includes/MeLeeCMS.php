<?php
/** The code for the MeLeeCMS class.
@todo Guilty admission: This project has inconsistent indentations and naming conventions. This is a consequence of being developed over more than a decade, and my preferences and text editors/settings changing over that time period. So, to clarify the project's conventions in the hope of eventually making every file conform to them:
- Indentations should be 2 spaces.
- Class names should be PascalCase
- Constants should be CAPITAL_UNDER_SCORED
- Class methods should be camelCase()
- All other variables and functions should be under_scored
The naming conventions are my best attempt to conform to PHP's own naming conventions, which are themselves inconsistent.*/
namespace MeLeeCMS;

/**
 * The core class of MeLeeCMS, containing all of the code that sets up the back-end and handles the displaying of pages.
 */
class MeLeeCMS
{
  /** @var int Bit for loading the MySQL database. */
  const SETUP_DATABASE = 1;
  /** @var int Bit for loading site settings from the MySQL database to overwrite specific `config.php` settings. */
  const SETUP_SETTINGS = 2;
  /** @var int Bit for loading all themes from the `themes/` directory. */
  const SETUP_THEMES = 4;
  /** @var int Bit for loading only the `default_theme` as specifid in the settings, plus its superthemes. */
  const SETUP_THEME = 128;
  /** @var int Bit for loading the user class specified in the settings and attempting to authenticate the user. */
  const SETUP_USER = 8;
  /** @var int Bit for loading all forms so that POST requests can be handled. */
  const SETUP_FORMS = 32;
  /** @var int Bit for loading all pages and special pages. */
  const SETUP_PAGES = 64;
  /** @var int Bit for loading only the page specified in the URL, plus all special pages. */
  const SETUP_PAGE = 16;
  
  /** @var int Bit union for loading only the features needed to authenticate a user. */
  const MODE_AUTH = self::SETUP_DATABASE | self::SETUP_SETTINGS | self::SETUP_USER;
  /** @var int Bit union for loading only the features needed to handle a form-submitted POST request. */
  const MODE_FORM = self::SETUP_DATABASE | self::SETUP_SETTINGS | self::SETUP_USER | self::SETUP_FORMS;
  /** @var int Bit union for loading only the features needed to handle a JSON POST request. */
  const MODE_AJAX = self::SETUP_DATABASE | self::SETUP_SETTINGS | self::SETUP_USER | self::SETUP_THEME;
  /** @var int Bit union for loading only the features needed to display a page. */
  const MODE_PAGE = self::SETUP_DATABASE | self::SETUP_SETTINGS | self::SETUP_USER | self::SETUP_THEME | self::SETUP_PAGE;
  /** @var int Bit union for loading all MeLeeCMS features. This is the default. */
  const MODE_ALL  = self::SETUP_DATABASE | self::SETUP_SETTINGS | self::SETUP_USER | self::SETUP_THEMES | self::SETUP_FORMS | self::SETUP_PAGES | self::SETUP_PAGE;
  
  /** @var int The bit union used in the MeLeeCMS constructor. */
  protected $mode;
  /** @var array<string,mixed> Array of key/value pairs for all loaded MeLeeCMS settings. */
  protected $settings = [];
  /** @var boolean Whether to load the control panel instead of a normal page. */
  protected $cpanel = false;
  /** @var string The full title that will appear on the browser window, can be set by {@see MeLeeCMS::setTitle()}. */
  protected $page_title = "";
  /**
  @var Theme The Theme that will be used to render the page and resolve JS and CSS files.
  @see MeLeeCMS::setTheme() The setter for this property.
  @see MeLeeCMS::getTheme() The getter for this property.
  */
  protected $page_theme;
  /** @var array<int,array> The CSS that the page will use, including files, code, and whether to load a file from the Theme or externally. Generally added by {@see MeLeeCMS::attachCSS()}. */
  protected $page_css = [];
  /** @var array<int,array> The JavaScript that the page will use, including files, code, and whether to load a file from the Theme or externally. Generally added by {@see MeLeeCMS::attachJS()}. */
  protected $page_js = [];
  /** @var array<int,array> The XSL files that the page will use. Generally added by {@see MeLeeCMS::attachXSL()}. */
  protected $page_xsl = [];
  /** @var array<string,Content> The various objects that are subclasses of Content, which will be used to display the page content. Generally added by {@see MeLeeCMS::addContent()}. */
  protected $page_content = [];
  /** @var array<int,array> Arrays of mixed types containing debug output, which will be printed in HTML comments at the bottom of the page source. Added by {@see MeLeeCMS::debugLog()}. */
  protected $debug_log = [];
  /**
  @var int Expiration time of the session cookie.
  @todo Include some way for the user to specify how long they want this to be, even something as simple as a "remember me" checkbox.
  */
  protected $session_expiration = 86400*30;
  
  /** @var array<int,string> The system file paths from which MeLeeCMS will attempt to autoload classes. */
  public $class_paths = [];
  /** @var array<int,string> The parsed `$_SERVER['PATH_INFO']` that MeLeeCMS will use to determine what page to load. */
  public $path_info;
  /**
  @var Database The loaded database that all MeLeeCMS components will use to make MySQL queries.
  @see MeLeeCMS::setupDatabase() The method that defines this property.
  */
  public $database;
  /**
  @var User The object representing the current user, including any authentication and external APIs (such as OAuth) used to identify them.
  @see MeLeeCMS::setupUser() The method that defines this property.
  */
  public $user;
  /**
  @var Page The page that MeLeeCMS will render on this request.
  @see MeLeeCMS::setupPage() The method that defines this property.
  */
  public $page;
  /**
  @var Theme[] All of the currently loaded themes.
  @see MeLeeCMS::addTheme() The method that populates this array.
  */
  public $themes = [];
  /**
  @var Page[] All of the currently loaded pages.
  @see MeLeeCMS::addPage() The method that populates this array.
  */
  public $pages = [];
  /**
  @var Page[] All of the currently loaded special pages.
  Special pages are pages not selected by a URL, but rather by certain conditions (such as error codes) if no page was selected by the URL.
  @see MeLeeCMS::addPage() The method that populates this array.
  */
  public $special_pages = [];
  /** @var array[] All of the currently loaded forms. */
  public $forms = [];
  /**
  @var Data All arbitrary non-content data that MeLeeCMS is to include with the page, which will be sent to the user in the `window.MeLeeCMS` JavaScript object, as well as provided to XSLT, unless instructed otherwise.
  @see MeLeeCMS::addData() The method that can be used to add data. Forwards the call to {@see Data::add()}.
  */
  public $out_data;
  /** @var string[] PHP files to include between the instantiation of MeLeeCMS and the final page render. Mostly used when you want to have a PHP file construct the page, as opposed to the control panel building the page. */
  public $include_later = [];
  /** @var mixed[] Stores the parameters of the last call to {@see MeLeeCMS::requestRefresh()}. */
  public $refresh_requested = ['strip'=>[]];
  public $maintenance_until = 0;

  /**
  Loads MeLeeCMS.
  @param int $mode A bitwise union of all features to load. You can use one of the predefined MODE_* constants or create a union of SETUP_* constants yourself.
  */
  public function __construct($mode=self::MODE_ALL)
  {
    $this->mode = $mode;
    // TODO: Set an error handler in init.php that doesn't require MeLeeCMS, but overwrite it with this one. This one should also call that one.
    set_error_handler([$this, "errorHandler"]);
    global $GlobalConfig;
    
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
    $this->settings['index_page'] = $GlobalConfig['index_page'];
    
    // Setup the load paths for classes.
    // 1. Start with 'classes/' in the same location as this MeLeeCMS.php file.
    array_unshift($this->class_paths, __DIR__ . DIRECTORY_SEPARATOR ."classes". DIRECTORY_SEPARATOR);
    // 2. Then add 'includes/classes/' in the web root directory of this site to the front of the list, if it's not the same.
    if(substr(__DIR__, 0, strlen($this->settings['server_path'])) != $this->settings['server_path'])
      array_unshift($this->class_paths, $this->settings['server_path'] ."includes". DIRECTORY_SEPARATOR ."classes". DIRECTORY_SEPARATOR);
    spl_autoload_register(array($this, "loadClass"), true);
    
    $this->out_data = new Data();
    
    // Load what we need to identify the current user.
    if(($this->mode & self::SETUP_DATABASE) > 0)
      $this->setupDatabase();
    if(($this->mode & self::SETUP_SETTINGS) > 0)
      $this->setupSettings();
    if(($this->mode & self::SETUP_USER) > 0)
      $this->setupUser();
    
    // Parse out the URL so we can determine the requested page later.
    $this->path_info = isset($_SERVER['PATH_INFO']) ? array_values(array_filter(explode("/", $_SERVER['PATH_INFO']))) : [];
    // Determine if we need to load the control panel.
    if(count($this->path_info) && $this->path_info[0] == $this->settings['cpanel_dir'] && (!empty($this->user) && $this->user->hasPermission("view_cpanel") || !empty($GlobalConfig['admin_ip']) && $_SERVER['REMOTE_ADDR'] == $GlobalConfig['admin_ip']))
    {
      $this->cpanel = true;
      $this->settings['default_theme'] = $GlobalConfig['cpanel_theme'];
      // Set the "index" of the control panel.
      if(count($this->path_info) == 1)
        $this->path_info[] = "settings";
      require_once($this->settings['server_path'] ."includes". DIRECTORY_SEPARATOR ."admin". DIRECTORY_SEPARATOR ."init.php");
    }
    else
    {
      // Ignore maintenance mode inside the control panel. TODO: Probably move this to setupPage()
      $this->maintenance_until = empty($this->settings['maintenance_until']) ? 0 : $this->settings['maintenance_until'];
    }
    
    // Load the rest of what we need to generate a response.
    if($this->cpanel || ($this->mode & self::SETUP_THEMES) > 0)
      $this->setupThemes();
    // Note: This is 'else if' because setupThemes() does everything that setupTheme() does.
    else if(($this->mode & self::SETUP_THEME) > 0)
      $this->setupTheme();
    if($this->cpanel || ($this->mode & self::SETUP_FORMS) > 0)
      $this->setupForms();
    if($this->cpanel || ($this->mode & (self::SETUP_PAGES | self::SETUP_PAGE)) > 0)
      $this->setupSpecialPages();
    if($this->cpanel || ($this->mode & self::SETUP_PAGES) > 0)
      $this->setupPages();
    if($this->cpanel || ($this->mode & self::SETUP_PAGE) > 0)
      $this->setupPage();
    
    // If a refresh was requested at some point during initialization, do it now.
    if(isset($this->refresh_requested['url']))
      $this->refreshPage();
  }
  
  /**
  Informs MeLeeCMS that the current page needs to reload before the user can view the site properly.
  Allows for a few parameters to be set that will change the full URL query string, rather than a plain refresh. The request is delayed so that the page can finish any important processing before exiting and reloading.
  @see MeLeeCMS::refreshPage() The method that actually causes the page to refresh.
  @param string|null $destination The URL to load instead of refreshing the current one. If null, defaults to `$_SERVER['REQUEST_URI']`.
  @param string|string[] $strip_query An array of query parameters to strip from the current URL before loading the page again. The strings must match the full parameter name and value, not just the name. A string will be treated as a single-element array containing that string.
  @return void
  */
  public function requestRefresh($destination=null, $strip_query=[])
  {
    if($destination === null)
      $destination = $_SERVER['REQUEST_URI'];
    if(
      empty($this->refresh_requested['url']) ||
        $destination !== $_SERVER['REQUEST_URI'] &&
        ($destination !== $_SERVER['HTTP_REFERER'] || $this->refresh_requested['url'] === $_SERVER['REQUEST_URI'])
    )
      $this->refresh_requested['url'] = $destination;
      
    if(is_array($strip_query))
      $this->refresh_requested['strip'] = array_merge($this->refresh_requested['strip'], $strip_query);
    else if(!empty($strip_query))
      $this->refresh_requested['strip'][] = $strip_query;
    //register_shutdown_function([$this, "refreshPage"]);
  }
  
  /**
  Sends the Location header prepared by a previous method call and exits the application.
  The URL that is loaded and how the query string is altered depend on the previous call to {@see MeLeeCMS::requestRefresh()}.
  @return void
  */
  protected function refreshPage()
  {
    if(isset($this->refresh_requested['url']))
    {
      $url = $this->refresh_requested['url'];
      foreach($this->refresh_requested['strip'] as $param)
      {
        // TODO: Why do we need both? Check it out.
        $url = str_replace("?".$param."&", "?", $url);
        $url = preg_replace("/[?&]". $param ."\\b/i", "", $url);
      }
      header("Location: ". $url);
      exit;
    }
  }
  
  public function debugLog($permission, ...$input)
  {
    $mute_meleecms = function(&$in) use(&$mute_meleecms)
    {
      if($in instanceof MeLeeCMS)
      {
        $in = "<MeLeeCMS instance>";
      }
      else if(is_array($in))
      {
        foreach($in as $k=>$v)
        {
          $mute_meleecms($in[$k]);
        }
      }
      else if(is_object($in))
      {
        foreach(get_object_vars($in) as $k=>$t)
        {
          $mute_meleecms($in->$k);
        }
      }
    };
    $mute_meleecms($input);
    if(empty($permission) || !empty($this->user) && $this->user->hasPermission($permission))
      $this->debug_log[] = $input;
  }
  
  public function errorHandler($level, $message, $file, $line)
  {
    global $GlobalConfig;
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
    if(($level & $GlobalConfig['error_file_reporting']) > 0)
      error_log($type .": ". $message ."\n". stack_trace_string(0));
    
    $backtrace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1);
    
    // Send the error to the XML output if the user has permission to view errors.
    if(($level & $GlobalConfig['error_xml_reporting']) > 0 && is_object($this->user) && $this->user->hasPermission("view_errors"))
    {
      $this->addDataProtected('server_path', $this->getSetting('server_path'), Data::NO_AUTO_ARRAY, 0);
      $this->addDataProtected('errors', [
        'type' => $type,
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'stack' => $backtrace,
      ]);
    }
    
    // Log it to the error database, if it exists.
    if(($level & $GlobalConfig['error_database_reporting']) > 0 && is_object($this->database) && !empty($this->database->metadata['error_log']) && ($level & (E_NOTICE|E_USER_NOTICE|E_STRICT)) == 0)
    {
      $mysql_data = [
        'time' => time(),
        'user' => !empty($this->user) ? (int)$this->user->getProperty('index') : 0,
        'level' => $level,
        'type' => $type,
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'stack' => $backtrace,
      ];
      $this->database->insert("error_log", $mysql_data, false);
    }
    return true;
  }

  public function loadClass($class)
  {
    $parts = explode("\\", $class);
    $classname = array_pop($parts);
    $namespace = array_shift($parts);
    if($namespace == __namespace__)
    {
      foreach($this->class_paths as $path)
      {
        foreach($parts as $dir)
          $path .= $dir . DIRECTORY_SEPARATOR;
        if(is_file($path . $classname .".php"))
        {
          require_once($path . $classname .".php");
          return true;
        }
      }
      trigger_error("Unable to load class '". $class ."' in MeLeeCMS namespace. Class file not found.", E_USER_ERROR);
      return false;
    }
    else
    {
      return false;
    }
  }
  
  protected function setupDatabase()
  {
    global $GlobalConfig;
    try
    {
      $this->database = new Database("mysql", $GlobalConfig['dbhost'], $GlobalConfig['dbname'], $GlobalConfig['dbuser'], $GlobalConfig['dbpass'], $this);
      return true;
    }
    catch(\PDOException $x)
    {
      trigger_error($x, E_USER_ERROR);
      return false;
    }
  }
  
  protected function setupSettings()
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
  
  protected function setupUser()
  {
    global $GlobalConfig;
    // TODO: Need to figure out when we are allowed to set cookie expiration. A not-logged-in user can't set the "remember me" flag, so their session cookie will expire with the browser window. However they retain the same session ID after logging in, so the cookie will need to be updated with a new expiration if they want to be remembered.
    //$this->session_expiration = (!empty($_POST['remember_me']) && is_numeric($_POST['remember_me'])) ? (int)$_POST['remember_me'] : 0;
    session_set_save_handler(new MeLeeSessionHandler($this), true);
    session_name($this->getSetting('cookie_prefix') ."sessid");
    $session_options = [
      'cookie_lifetime' => $this->session_expiration,
      'cookie_secure' => empty($GlobalConfig['force_https']) ? 0 : 1,
      'cookie_httponly' => 1,
      //'cookie_samesite' => "strict", // only works in PHP>=7.3.0
    ];
    session_start($session_options);
    if(isset($_SESSION['form_response']))
    {
      $this->addDataProtected('form_response', $_SESSION['form_response'], Data::NO_AUTO_ARRAY);
      unset($_SESSION['form_response']);
    }
    //if($this->getSetting('user_system') == "")
      $user_class = "\\MeLeeCMS\\User";
    //else
    //  $user_class = "\\MeLeeCMS\\". $this->getSetting('user_system');
    
    $this->user = new $user_class($this);
    if(!empty($this->user))
      return true;
    else
    {
      trigger_error("Unable to create user object using the '". $this->getSetting('user_system') ."' user system.", E_USER_ERROR);
      return false;
    }
  }
  
  public function addTheme($directory)
  {
    if(!empty($this->themes[$directory]))
      return $this->themes[$directory];
    else if($directory == "." || $directory == ".." || !is_dir($this->getSetting('server_path') ."themes". DIRECTORY_SEPARATOR . $directory))
    {
      trigger_error("Tried to add '{$directory}' as a theme, but it is not a valid directory.", E_USER_NOTICE);
      return null;
    }
    else
    {
      $this->themes[$directory] = new Theme($this, $directory);
      // Theme->resolveSuperthemes() needs to be called after this, but it might call MeLeeCMS->addTheme() again, so we don't want to do it here to prevent recursion.
      return $this->themes[$directory];
    }
  }
  
  protected function setupTheme()
  {
    $this->addTheme($this->getSetting('default_theme'))->resolveSuperthemes();
    $this->setTheme($this->getSetting('default_theme'));
    return count($this->themes)>0;
  }
  
  protected function setupThemes()
  {
    // Add all the themes in the themes directory.
    $themesDir = dir($this->getSetting('server_path') ."themes");
    while(false !== ($theme = $themesDir->read()))
      if($theme != "." && $theme != "..")
        $this->addTheme($theme);
    // Then resolve their superthemes lists. Theme->resolveSuperthemes() calls MeLeeCMS->addTheme() as needed, but since we just added them all, it shouldn't be... unless there are invalid themes in a superthemes list, in which case you'll see some errors.
    foreach($this->themes as $theme)
      $theme->resolveSuperthemes();
    $this->setTheme($this->getSetting('default_theme'));
    return count($this->themes)>0;
  }
  
  protected function setupForms()
  {
    global $GlobalConfig;
    if(isset($GlobalConfig['forms']) && is_array($GlobalConfig['forms']))
      $this->forms = $GlobalConfig['forms'];
  }
  
  protected function setupSpecialPages()
  {
    global $GlobalConfig;
    // I'm not sure if it's worth it to isolate only the special pages from $GlobalConfig.
    if(isset($GlobalConfig['pages']) && is_array($GlobalConfig['pages']))
      foreach($GlobalConfig['pages'] as $page)
        $this->addPage($page, 1);
    // TODO: Also load specials from database, once they are stored there.
  }
  
  protected function setupPages()
  {
    if(is_object($this->database))
    {
      $pages = $this->database->query("SELECT * FROM `pages`", Database::RETURN_ALL);
      foreach($pages as $page)
        $this->addPage($page, 2);
    }
  }
  
  /**
  Creates a Page object from given associative array.
  @param array<string,mixed> $pageData Associative array of page data, either pulled from the database, or defined in config.php using the same fields.
  @param int $defined_in Whether the page is defined in config.php or the database. '1' if it's in config.php. '2' if it's in the database. '3' for both.
  @return Page The page object that was created, or the saved Page object if a page with the same url or id already existed.
  */
  public function addPage($pageData, $defined_in=0)
  {
    $page = new Page($this, $pageData, $defined_in);
    if($page->is_special)
    {
      if(empty($this->special_pages[$page->id]))
      {
        $this->special_pages[$page->id] = $page;
        return $this->special_pages[$page->id];
      }
      else
      {
        // TODO: Probably allow it, but would also need to allow special pages to load from database.
        trigger_error("Special page already exists with the ID '{$page->id}'. Overwriting special pages with MeLeeCMS->addPage() is not allowed.", E_USER_WARNING);
        return $this->special_pages[$page->id];
      }
    }
    else
    {
      if(empty($this->pages[$page->id]))
      {
        $this->pages[$page->id] = $page;
        return $this->pages[$page->id];
      }
      else
      {
        // TODO: Probably allow it here too.
        trigger_error("Page already exists with the URL '{$page->id}'. Overwriting pages with MeLeeCMS->addPage() is not allowed.", E_USER_WARNING);
        return $this->pages[$page->id];
      }
    }
    // In both of the above TODOs, make sure to combine fields where approriate, not just full-on overwrite.
  }
  
  protected function findPage($path_info=[], $args=[])
  {
    if(count($path_info) == 0)
      return [null, $args];
    $pageId = implode("/", $path_info);
    if(!empty($this->pages[$pageId]))
      return [$this->pages[$pageId], $args];
    else
    {
      $page = $this->database->query("SELECT * FROM `pages` WHERE `url`={$this->database->quote($pageId)}", Database::RETURN_ROW);
      if(!empty($page))
        return [$this->addPage($page), $args];
      else
      {
        array_unshift($args, array_pop($path_info));
        return $this->findPage($path_info, $args);
      }
    }
  }
  
  protected function setupPage()
  {
    // Special case for viewing/debugging the special pages.
    if(!empty($_GET['specialPage']) && !empty($this->special_pages[$_GET['specialPage']]))
    {
      $this->page = $this->special_pages[$_GET['specialPage']];
    }
    
    // Attempt to load the page until we encounter an error state.
    if(time() < $this->maintenance_until && (empty($this->user) || !$this->user->hasPermission("ignore_maintenance")))
    {
      http_response_code(503);
      header("Retry-After: ". date("r", $this->maintenance_until));
      header("Retry-After: ". ($this->maintenance_until - time()), false);
    }
    else
    {
      // Normal page request.
      if(empty($this->page))
        list($this->page, $args) = $this->findPage(!empty($this->path_info) ? $this->path_info : explode("/", $this->getSetting('index_page')));
      
      // Determine any problems with the request.
      if(!empty($this->page))
      {
        $req_perms = $this->page->getPermissions();
        // TODO: I think ...array expansion requires PHP7. Have to check and then provide fallback implementation if so.
        if(empty($req_perms) || !empty($this->user) && $this->user->hasPermission(...$req_perms))
        {
          // No problems, this should be a successful load.
          $this->page->args = $args;
        }
        else if(empty($this->user) || !$this->user->isLogged())
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
    }
    
    // Resolve problematic requests into a special page.
    if(empty($this->page))
    {
      foreach($this->special_pages as $spage)
      {
        // Note: Page->select a callable property and not a method, so we have to call it with parinthesis.
        if(($spage->select)($this))
        {
          $this->page = $spage;
          break;
        }
      }
      if(empty($this->page))
      {
        trigger_error("Page request '". implode("/",$this->path_info) ."' couldn't resolve to a page or a special page. HTTP response would have been ". http_response_code() .". Make sure a special page is defined for that response code.", E_USER_WARNING);
        $this->page = new Page($this, false);
      }
    }
    
    // Finish setting up the page output.
    $this->page->loadToCMS();
  }
  
  public function get_setting($key)
  {
    trigger_error("MeLeeCMS->get_setting() is deprecated; use MeLeeCMS->getSetting() instead.", E_USER_DEPRECATED);
    return $this->getSetting($key);
  }
  
  public function getSetting($key)
  {
    return $this->settings[$key];
  }
  
  public function get_page($key)
  {
    trigger_error("MeLeeCMS->get_page() is deprecated and will be removed eventually. Access MeLeeCMS->page directly instead.", E_USER_DEPRECATED);
    return empty($this->page->$key) ? "" : $this->page->$key;
  }
  
  public function setTheme($theme)
  {
    if(!empty($this->themes[$theme]))
      $this->page_theme = $this->themes[$theme];
    else if($this->cpanel && !empty($this->themes[$this->getSetting('cpanel_theme')]))
      $this->page_theme = $this->themes[$this->getSetting('cpanel_theme')];
    else if(!empty($this->themes[$this->getSetting('default_theme')]))
      $this->page_theme = $this->themes[$this->getSetting('default_theme')];
    else if(!empty($this->themes["default"]))
      $this->page_theme = $this->themes["default"];
    else
    {
      reset($this->themes);
      if(!empty(current($this->themes)))
        $this->page_theme = current($this->themes);
      else
        throw new \Exception("Page has no valid themes.");
    }
    return $this->page_theme;
  }

  public function getTheme()
  {
    return $this->page_theme;
  }

  public function set_title($title)
  {
    trigger_error("MeLeeCMS->set_title() is deprecated; use MeLeeCMS->setTitle() instead.", E_USER_DEPRECATED);
    return $this->setTitle($title);
  }
  
  public function setTitle($title)
  {
    return $this->page_title = $title ." - ". $this->getSetting('site_title');
  }

  public function attach_css($href="", $code="", $fromtheme=false, $attrs=[])
  {
    trigger_error("MeLeeCMS->attach_css() is deprecated; use MeLeeCMS->attachCSS() instead.", E_USER_DEPRECATED);
    return $this->attachCSS($href, $code, $fromtheme, $attrs);
  }
  
  public function attachCSS($href="", $code="", $fromtheme=false, $attrs=[])
  {
    if($href != "" || $code != "")
    {
      $this->page_css[] = array('href'=>$href, 'code'=>$code, 'fromtheme'=>$fromtheme, 'attrs'=>$attrs);
      return true;
    }
    else
      return false;
  }

  public function attach_js($src="", $code="", $fromtheme=false, $attrs=[])
  {
    trigger_error("MeLeeCMS->attach_js() is deprecated; use MeLeeCMS->attachJS() instead.", E_USER_DEPRECATED);
    return $this->attachJS($src, $code, $fromtheme, $attrs);
  }
  
  public function attachJS($src="", $code="", $fromtheme=false, $attrs=[])
  {
    if($src != "" || $code != "")
    {
      $this->page_js[] = array('src'=>$src, 'code'=>$code, 'fromtheme'=>$fromtheme, 'attrs'=>$attrs);
      return true;
    }
    else
      return false;
  }

  public function attach_xsl($href="", $code="", $fromtheme=false)
  {
    trigger_error("MeLeeCMS->attach_xsl() is deprecated; use MeLeeCMS->attachXSL() instead.", E_USER_DEPRECATED);
    return $this->attachXSL($href, $code, $fromtheme);
  }
  
  public function attachXSL($href="", $code="", $fromtheme=true)
  {
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
    trigger_error("MeLeeCMS->add_content() is deprecated; use MeLeeCMS->addContent() instead.", E_USER_DEPRECATED);
    return $this->addContent($content, $x);
  }
  
  public function addContent($content, $x="")
  {
    if(is_numeric($x))
      $x = "__". $x;
    else if($x == "")
      $x = "__". count($this->page_content);
    if(is_a($content, "MeLeeCMS\\Content"))
    {
      return $this->page_content[$x] = $content->set_cms($this);
    }
    else
    {
      trigger_error("'". get_class($content) ."' is not a subclass of Content, cannot add it as content (". $x .").", E_USER_WARNING);
      return null;
    }
  }
  
  protected function addDataProtected($index, $data, $flags=0, $errorIfExists=E_USER_NOTICE)
  {
    return $this->out_data->add($index, $data, $flags, $errorIfExists);
  }

  public function addData($index, $data, $flags=Data::CUSTOM, $errorIfExists=E_USER_NOTICE)
  {
    return $this->out_data->add($index, $data, $flags|Data::CUSTOM, $errorIfExists);
  }
  
  public function parse_template($data, $class, $format)
  {
    trigger_error("MeLeeCMS->parse_template() is deprecated; use MeLeeCMS->parseTemplate() instead.", E_USER_DEPRECATED);
    return $this->parseTemplate($data, $class, $format);
  }
  
  /**
  Converts the provided data to HTML using XSLT files determined from the other parameters.
  Forwards the call to {@see Theme::parseTemplate()} after adding all of the extra XSL files that were attached to this MeLeeCMS instance.
  @param array $data Multi-dimensional array of content data generated by {@see Content::build_params()}
  @param string $class The name of the content class, or "MeLeeCMS", which determines which XSL file to use at the base for XSLT.
  @param string $format Any format, which will use a different XSL file if one exists for that format.
  @param array<int,array> $xsl A list of XSL to include along with the base XSL file. Each element should have either an absolute path to an XSL file as the 'href' index, or raw XSLT code as the 'code' index.
  @return string HTML code that has been transformed by XSLT using the provided parameters.
  */
  public function parseTemplate($data, $class, $format, $xsl_includes=[])
  {
    $page_xsl = [];
    foreach($this->page_xsl as $xsl)
    {
      if(!$xsl['fromtheme'])
        $page_xsl[] = ['href'=>$xsl['href'], 'code'=>$xsl['code']];
      else if($xsl_filepath = $this->getTheme()->resolveFile("templates", $xsl['href']))
      {
        //$this->debugLog("ADMIN", $xsl_filepath);
        $page_xsl[] = ['href'=>$xsl_filepath];
      }
      else
        trigger_error("Invalid XSL file '{$xsl['href']}' (Has code? ". !empty($xsl['code']) .". From theme? ". !empty($xsl['fromtheme']) .") has been attached to the page.", E_USER_WARNING);
    }
    return $this->getTheme()->parseTemplate($data, $class, $format, array_merge($xsl_includes, $page_xsl));
  }

  public function render($format="")
  {
    if(isset($this->refresh_requested['url']))
    {
      $this->refreshPage();
      return false;
    }
    register_shutdown_function("MeLeeCMS\\print_load_statistics");
    if($format == "")
      $format = "default";
    
    $params = [
      'title' => $this->page_title,
      'url_path' => $this->getSetting('url_path'),
      'theme' => $this->getTheme()->name,
      'content' => [],
      'css' => [],
    ];
    if(!empty($this->user))
      $this->addDataProtected('user', $this->user->myInfo(), Data::NO_AUTO_ARRAY);
    if(!empty($_POST))
      $this->addDataProtected('post', $_POST, Data::NO_AUTO_ARRAY);
    if(!empty($_GET))
      $this->addDataProtected('get', $_GET, Data::NO_AUTO_ARRAY);
    
    $content_xsl = [];
    foreach($this->page_content as $tag=>$content)
    {
      $params['content@class='.$content->getContentClass().($tag?'@id='.$tag:'')][] = $content->build_params();
      $content_xsl = array_merge($content_xsl, $content->findXSLFiles($this->getTheme()));
    }
    
    foreach($this->page_css as $css)
      $params['css'][] = [
        'href' => ($css['fromtheme'] ? $this->getTheme()->resolveFile("css", $css['href']) : $css['href']),
        'code' => $css['code'],
        'attrs' => $css['attrs'],
      ];
    $params['js'] = [[
      'code' =>
        "window.MeLeeCMS = new (function MeLeeCMS(){".
          "this.url_path=\"". addslashes($this->getSetting('url_path')) ."\";".
          "this.theme=\"". addslashes($this->getTheme()->name) ."\";".
          "this.data=". $this->out_data->toJSON().
        "})();",
    ]];
    foreach($this->page_js as $js)
      $params['js'][] = [
        'src' => ($js['fromtheme'] ? $this->getTheme()->resolveFile("js", $js['src']) : $js['src']),
        'code' => $js['code'],
        'attrs' => $js['attrs'],
      ];
    // Note: data/errors won't include errors during XSLT conversion. Pretty sure it's impossible to fix that.
    $params['data'] = $this->out_data->toArray();
    if($format == "__xml" && $this->user->hasPermission("view_xml"))
    {
      header("Content-type: text/xml");
      echo("<?xml version=\"1.0\"?>");
      echo(Transformer::array_to_xml("MeLeeCMS", $params));
    }
    else
    {
      $html = $this->parseTemplate($params, "MeLeeCMS", $format, $content_xsl);
      if(is_array($html))
        echo("No theme was loaded. There may be an error in the MeLeeCMS setup for this page.");
      else
        echo($html);
    }
    
    foreach($this->debug_log as $input)
    {
      echo("<!--\n");
      foreach($input as $in)
      {
        print_r($in);
        echo("\n");
      }
      echo("-->\n");
    }
    return true;
  }
}
