<?php
/********************** MeLeeCMS Default Configuration ************************
 * This file provides descriptions of various configuration options that you
 * can use to setup and modify the way MeLeeCMS works. This file is loaded by
 * the CMS to provide default values for these options where applicable. To use
 * your own configuration options, copy this file into this same directory and
 * rename it to config.php, and make your changes there. If you edit this file
 * without first copying it, your changes will be overwritten when MeLeeCMS is
 * updated.
 *****************************************************************************/

// The array that contains all of the settings that the site needs to function. All of the elements are described below.
$GlobalConfig = [];

/****************************** MySQL Settings ********************************
 * MySQL Connection settings. Pretty self-explanatory. For now, MySQL is
 * required for MeLeeCMS to be able to do much of anything. In the future,
 * other data storage types might be implemented.
******************************************************************************/
 
// Location of the MySQL database. IP address or hostname. In most cases this should be "localhost".
$GlobalConfig['dbhost'] = "";

// Username to login to the MySQL database.
$GlobalConfig['dbuser'] = "";

// Password for the above username.
$GlobalConfig['dbpass'] = "";

// Name of the MySQL database to read the tables from.
$GlobalConfig['dbname'] = "";

/**************************** File Path Settings ******************************
 * These help MeLeeCMS find and link to various files. These default settings
 * will take care of the vast majority of cases. However, you would need to
 * manually specify the paths if you have PHP files inside of subdirectories on
 * your website that also need to use MeLeeCMS. For example, if MeLeeCMS.php is
 * in "anydirectory/includes/", and your index.php is in "anydirectory/site/"
 * and includes that copy of MeLeeCMS, then you would need to manually specify
 * these settings to point to the "anydirectory/site/" directory in order for
 * MeLeeCMS to function. However, if your index.php is in "anydirectory/" and
 * MeLeeCMS.php is in "anydirectory/includes/", then these defaults will work
 * just fine.
 *****************************************************************************/

// The path to your wesite's root directory within the server. This is usually something like "/home/user/public_html/".
$GlobalConfig['server_path'] = dirname(__DIR__) . DIRECTORY_SEPARATOR;

// This is the directory that contains the control panel files. If you change this, also make sure to change the actual directory name. If you are only changing this to try to make the control panel URL harder to find, don't bother. You should make sure the directory is protected whether its URL is public knowledge or not. Note: That should already be taken care of if you use any user_system other than User.
$GlobalConfig['cpanel_dir'] = "mcmsadmin"; // TODO: This can be programmatically determined by scanning the directories in $GlobalConfig['server_path'] for which one contains "load_page.php", or a similar unique control panel PHP file.

// The URL path to your website from the domain name. If your website loads right from "yourdomain.com", this should be "/". If it loads from "yourdomain.com/yoursite", this should be "/yoursite/". Etc.
$GlobalConfig['url_path'] = dirname(str_replace("/". $GlobalConfig['cpanel_dir'] ."/", "/", $_SERVER['SCRIPT_NAME']));
// You don't need this 'if' check in your config.php file. It's only here to correct the default value. Also, MeLeeCMS already does this check.
if($GlobalConfig['url_path']{-1} != "/")
   $GlobalConfig['url_path'] .= "/";

// This will redirect any page request from HTTP to HTTPS automatically, making sure no one accidentally uses the non-secure URL. Make sure your website has an SSL certificate and HTTPS works before setting this to true.
// TODO: Currently only works with pages that use the index.php in $GlobalConfig['url_path']. In other words, not the control panel.
$GlobalConfig['force_https'] = false;

/******************************* User Settings ********************************
 * Various other settings that determine how the site will appear to users and
 * their browsers.
******************************************************************************/
 
// This will be prepended to the names of any cookies set by MeLeeCMS. Generally this is only the session id cookie. Changing this in a production site will force all users to log back in, as well as lose any other data that is tracked using cookies.
$GlobalConfig['cookie_prefix'] = "melee_";

// This is the class name of the class to use to handle user accounts. If invalid or blank, it will default to the User class found in includes/classes/User.php. It is strongly recommended that you use another class, as the default User class does not support logins and will not protect the control panel directory at all. If you want to use the User class because of its simplicity and lack of logins, then you should set up Apache password protection on the control panel directory to protect it, if you have nothing else.
$GlobalConfig['user_system'] = "User";

// This is what will appear in the title bar of your website. Each page has its own title, followed by a hyphen, followed by whatever you specify here. This value can be overwritten by the MeLeeCMS control panel.
$GlobalConfig['site_title'] = $_SERVER['HTTP_HOST'];

// This is the default theme that will be loaded on your site if no other theme is specified for a page or if the specified theme isn't valid. This value can be overwritten by the MeLeeCMS control panel.
$GlobalConfig['default_theme'] = "bootstrap4";

// This is the theme that will be used by the control panel. DO NOT CHANGE THIS, without first making absolutely sure that the new theme fully supports all control panel features, or else the control panel will not work, and you will be unable to manage your site (until you change this value back).
$GlobalConfig['cpanel_theme'] = "bootstrap4";

// Page that will load if someone visits your website but doesn't specify a page. For example, if they visit the URL www.yourdomain.com/, with nothing after the slash. This value can be overwritten by the MeLeeCMS control panel.
$GlobalConfig['index_page'] = "";

/******************************* Page Handlers ********************************
 * Defines some of the pages the site can load. These can be defined in the control panel for simpler pages, but if you want to manually write PHP code to display your page, the control panel can't really help you. Define such pages in your config.php using the below format.
******************************************************************************/

$GlobalConfig['pages'] = [];
// You don't need this 'if' check in your config.php file, it's only here to prevent error messages from triggering once you replace all the defaults with your own pages.
if(is_file("includes". DIRECTORY_SEPARATOR ."pages". DIRECTORY_SEPARATOR ."test-page.php"))
{
   // Copy/paste the below definition in your own config.php file as needed with different values for each page.
   $GlobalConfig['pages'][] = [
      'url' => "test-page", // The URL the page is accessed from, in this case: yoursite.com/test-page
      'subtheme' => "default", // A subtheme to load for the XSLT. Subthemes are defined in the names of XSL files, eg. for MeLeeCMS-default.xsl, the 'default' specifies that the file is for the 'default' subtheme.
      'file' => "test-page.php", // Specify the file name that contains the page logic in place of test-page.php
   ];
}
// These are special pages, like the 404 page, etc. You can overwrite them in your config.php if you follow the same format.
$GlobalConfig['pages']['404'] = [ // Arbitrary array key, '404' in this case. Only useful if you want to overwrite this definition in your own config.php.
   'id' => "404", // A unique ID for the special page.
   'select' => function($cms){ return http_response_code()==404; }, // A function that returns true if this page should load, or false if not. A normal page that matches the requested URL will load first if possible. If that load fails, then, special pages are checked in the order they are defined, and the first matching page is displayed. A reference to the MeLeeCMS instance is provided in case you need it.
   'subtheme' => "default", // See above.
   'content' => "a:1:{s:7:\"content\";O:9:\"Container\":3:{s:5:\"title\";s:14:\"Page Not Found\";s:5:\"attrs\";a:0:{}s:7:\"content\";a:1:{s:4:\"text\";O:4:\"Text\":2:{s:4:\"text\";s:45:\"The page you are looking for cannot be found.\";s:5:\"attrs\";a:0:{}}}}}", // Serialized PHP data. Not recommended that you do this if you are defining your own special pages; just use a file instead.
   'title' => "Page Not Found",
   'css' => [], // List of CSS files to include on this page, relative to the theme's CSS directory.
   'js' => [], // List of JS files to include on this page, relative to the theme's JS directory.
   'xsl' => [], // List of XSL files to include on this page, relative to the theme's templates directory.
   'permission' => 0, // Permission integer, a bitwise union of required permissions to view the page. Special pages don't check permissions.
];
$GlobalConfig['pages']['403'] = [
   'id' => "403",
   'select' => function($cms){ return http_response_code()==403; },
   'subtheme' => "default",
   'content' => "a:1:{s:7:\"content\";O:9:\"Container\":3:{s:5:\"title\";s:13:\"Access Denied\";s:5:\"attrs\";a:0:{}s:7:\"content\";a:1:{s:4:\"text\";O:4:\"Text\":2:{s:4:\"text\";s:45:\"You do not have permission to view this page.\";s:5:\"attrs\";a:0:{}}}}}",
   'title' => "Access Denied",
   'css' => [],
   'js' => [],
   'xsl' => [],
];
$GlobalConfig['pages']['401'] = [
   'id' => "401",
   'select' => function($cms){ return http_response_code()==401; },
   'subtheme' => "default",
   'content' => "a:1:{s:7:\"content\";O:9:\"Container\":3:{s:5:\"title\";s:14:\"Login Required\";s:5:\"attrs\";a:0:{}s:7:\"content\";a:1:{s:4:\"text\";O:4:\"Text\":2:{s:4:\"text\";s:40:\"You must be logged in to view this page.\";s:5:\"attrs\";a:0:{}}}}}",
   'title' => "Login Required",
   'css' => [],
   'js' => [],
   'xsl' => [],
];

/******************************* Form Handlers ********************************
 * List of forms the site can handle. First element is a function that returns
 * true or false for whether the user is apparently trying to use a form, and
 * the second element is the file name of that form in the includes/forms/
 * directory.
******************************************************************************/

$GlobalConfig['forms'] = [];
// You don't need this 'if' check in your config.php file, it's only here to prevent error messages from triggering once you replace all the defaults with your own forms.
if(is_file("includes". DIRECTORY_SEPARATOR ."forms". DIRECTORY_SEPARATOR ."test-form.php"))
{
   // Copy/paste the below definition in your own config.php file as needed with different values for each form.
   $GlobalConfig['forms']['testForm'] = [ // Give the form a unique name here instead of testForm
      'file' => 'test-form.php', // Specify the file name that contains the form logic in place of test-form.php
      'select' => function($cms){ // Define a function to determine if the user wants this form. A reference to MeLeeCMS is provided, but you shouldn't need it, as the $_REQUEST/$_POST/$_GET vars should be all you need to determine the user's intent. All matching forms will be run in a single page request, allowing you to submit multiple forms simultaneously if you so desire.
         return isset($_REQUEST['testForm']);
      },
   ];
}

/*************************** HTML Source Includes *****************************
 * List of external JS/CSS to include into the site's HTML. Use this to specify
 * if you want jQuery, Bootstrap, FontAwesome, etc. as well as what versions.
 * These will be used on all pages of the site. NOTE: These don't work yet.
 * Still figuring out how I want them to work.
******************************************************************************/

$GlobalConfig['html_css'] = [
];

$GlobalConfig['html_scripts'] = [
];
