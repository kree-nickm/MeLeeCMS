<?php
/**
Specifies default values for the various configuration options of MeLeeCMS.
This file provides descriptions of various configuration options that you can use to setup and modify the way MeLeeCMS works. This file is loaded by the CMS to provide default values for these options where applicable. To use your own configuration options, copy this file into this same directory and rename it to config.php, and make your changes there. If you edit this file without first copying it, your changes will be overwritten when MeLeeCMS is updated.
@filesource
*/

/**
@var array<string,mixed> The array that contains all of the settings that the site needs to function. All of the elements are described below.
*/
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

// This is the URL that will cause MeLeeCMS to attempt to load the control panel. The site should not have any page that shares this name, or an Exception will be thrown when that page attempts to load.
$GlobalConfig['cpanel_dir'] = "mcmsadmin";

// The URL path to your website from the domain name. If your website loads right from "yourdomain.com", this should be "/". If it loads from "yourdomain.com/yoursite", this should be "/yoursite/". Etc.
$GlobalConfig['url_path'] = dirname(str_replace("/". $GlobalConfig['cpanel_dir'] ."/", "/", $_SERVER['SCRIPT_NAME']));

// This will redirect any page request from HTTP to HTTPS automatically, making sure no one accidentally uses the non-secure URL. Make sure your website has an SSL certificate and HTTPS works before setting this to true.
$GlobalConfig['force_https'] = false;

/******************************* User Settings ********************************
 * Various other settings that determine how the site will appear to users and
 * their browsers.
******************************************************************************/
 
// This will be prepended to the names of any cookies set by MeLeeCMS. Generally this is only the session id cookie. Changing this in a production site will force all users to log back in, as well as lose any other data that is tracked using cookies.
$GlobalConfig['cookie_prefix'] = "melee_";

// This is the class name of the class to use to handle user accounts. If invalid or blank, it will default to the User class found in includes/classes/User.php.
$GlobalConfig['user_system'] = "User";

// This is what will appear in the title bar of your website. Each page has its own title, followed by a hyphen, followed by whatever you specify here. This value can be overwritten by the MeLeeCMS control panel.
$GlobalConfig['site_title'] = $_SERVER['HTTP_HOST'];

// This is the default theme that will be loaded on your site if no other theme is specified for a page or if the specified theme isn't valid. This value can be overwritten by the MeLeeCMS control panel.
$GlobalConfig['default_theme'] = "bootstrap4";

// This is the theme that will be used by the control panel. DO NOT CHANGE THIS, without first making absolutely sure that the new theme fully supports all control panel features, or else the control panel will not work, and you will be unable to manage your site (until you change this value back).
$GlobalConfig['cpanel_theme'] = "bootstrap4";

// Page that will load if someone visits your website but doesn't specify a page. For example, if they visit the URL www.yourdomain.com/, with nothing after the slash. This value can be overwritten by the MeLeeCMS control panel.
$GlobalConfig['index_page'] = "test-page";

/******************************* Page Handlers ********************************
 * Defines some of the pages the site can load. These can be defined in the control panel for simpler pages, but if you want to manually write PHP code to display your page, the control panel can't really help you. Define such pages in your config.php using the below format.
******************************************************************************/

$GlobalConfig['pages'] = [];
// You don't need this 'if' check in your config.php file, it's only here to prevent error messages from triggering if/when you delete test-page.php since you don't need it.
if(is_file("includes". DIRECTORY_SEPARATOR ."pages". DIRECTORY_SEPARATOR ."test-page.php"))
{
   // Copy/paste the below definition in your own config.php file as needed with different values for each page.
   $GlobalConfig['pages'][] = [
      'url' => "test-page",
      'file' => "test-page.php",
      'title' => "Testing Grounds",
      'css' => [],
      'js' => [],
      'xsl' => [],
      'permissions' => [],
   ];
}
// TODO: These error pages have no way of including a navbar. Need to figure out how to implement a navbar in a way that any site can use, such that it appears on these generic error pages.
// These are special pages, like the 404 page, etc. You can overwrite them in your config.php if you follow the same format.
$GlobalConfig['pages']['503'] = [
   'id' => "503",
   'select' => function($cms){ return http_response_code()==503; },
   'content' => "a:1:{s:7:\"content\";O:19:\"\\MeLeeCMS\\Container\":3:{s:5:\"title\";s:11:\"Maintenance\";s:5:\"attrs\";a:0:{}s:7:\"content\";a:1:{s:4:\"text\";O:14:\"\\MeLeeCMS\\Text\":2:{s:4:\"text\";s:42:\"Website is currently down for maintenance.\";s:5:\"attrs\";a:0:{}}}}}",
   'title' => "Maintenance",
   'css' => [],
   'js' => [],
   'xsl' => [],
];
$GlobalConfig['pages']['401'] = [
   'id' => "401",
   'select' => function($cms){ return http_response_code()==401; },
   'content' => "a:1:{s:7:\"content\";O:19:\"\\MeLeeCMS\\Container\":3:{s:5:\"title\";s:14:\"Login Required\";s:5:\"attrs\";a:0:{}s:7:\"content\";a:1:{s:4:\"text\";O:14:\"\\MeLeeCMS\\Text\":2:{s:4:\"text\";s:40:\"You must be logged in to view this page.\";s:5:\"attrs\";a:0:{}}}}}",
   'title' => "Login Required",
   'css' => [],
   'js' => [],
   'xsl' => [],
];
$GlobalConfig['pages']['403'] = [
   'id' => "403",
   'select' => function($cms){ return http_response_code()==403; },
   'content' => "a:1:{s:7:\"content\";O:19:\"\\MeLeeCMS\\Container\":3:{s:5:\"title\";s:13:\"Access Denied\";s:5:\"attrs\";a:0:{}s:7:\"content\";a:1:{s:4:\"text\";O:14:\"\\MeLeeCMS\\Text\":2:{s:4:\"text\";s:45:\"You do not have permission to view this page.\";s:5:\"attrs\";a:0:{}}}}}",
   'title' => "Access Denied",
   'css' => [],
   'js' => [],
   'xsl' => [],
];
$GlobalConfig['pages']['404'] = [
   'id' => "404",
   'select' => function($cms){ return http_response_code()==404; },
   'content' => "a:1:{s:7:\"content\";O:19:\"\\MeLeeCMS\\Container\":3:{s:5:\"title\";s:14:\"Page Not Found\";s:5:\"attrs\";a:0:{}s:7:\"content\";a:1:{s:4:\"text\";O:14:\"\\MeLeeCMS\\Text\":2:{s:4:\"text\";s:45:\"The page you are looking for cannot be found.\";s:5:\"attrs\";a:0:{}}}}}",
   'title' => "Page Not Found",
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
// You don't need this 'if' check in your config.php file, it's only here to prevent error messages from triggering if/when you delete test-form.php since you don't need it.
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

/******************************************************************************
 * Permission group definitions for various site pages and features. Most secured pages and features will specify a permission (eg. "view_errors") that is required to use it. However, most users will possess a set of permission groups (eg. "ADMIN") to define what they are allowed to do. The below array is where you define which permission groups (the capitalized array keys) contain which permissions (the array of lowercase strings). This file should list every single permission available in default MeLeeCMS under the ADMIN group, which you can use to create your own groups. When you copy this array into your own config.php, be very careful that you are only adding to these arrays and not overwriting them with your changes.
******************************************************************************/

$GlobalConfig['permissions'] = [
   'ANON' => [
      "view_pages",
   ],
   'LOGGED' => [
      "view_pages",
      "view_user_pages",
   ],
   'ADMIN' => [
      "view_pages",
      "view_user_pages",
      "view_xml",
      "view_errors",
      "view_cpanel",
   ],
];
