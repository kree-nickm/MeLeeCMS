<?
/****************************************************************
 * This file provides descriptions of various configuration options
 * that you can use to setup and modify the way MeLeeCMS works. This
 * file is loaded by the CMS to provide default values for these
 * options where applicable. To use your own configuration options,
 * copy this file into your base website directory and rename it to
 * config.php, and make your changes there. If you edit this file
 * without first copying it, your changes will be overwritten when
 * MeLeeCMS is updated.
 ***************************************************************/

// Location of the MySQL database. IP address or hostname. In most cases this should be "localhost".
$GlobalConfig['dbhost'] = "";

// Username to login to the MySQL database.
$GlobalConfig['dbuser'] = "";

// Password for the above username.
$GlobalConfig['dbpass'] = "";

// Name of the MySQL database to read the tables from.
$GlobalConfig['dbname'] = "";

/****************************************************************
 * These help MeLeeCMS find and link to various files. These default settings will take care of the vast majority of cases. However, you would need to manually specify the paths if you have PHP files inside of subdirectories on your website that also need to use MeLeeCMS. For example, if MeLeeCMS.php is in "anydirectory/includes/", and your index.php is in "anydirectory/site/" and includes that copy of MeLeeCMS, then you would need to manually specify these settings to point to the "anydirectory/site/" directory in order for MeLeeCMS to function. However, if your index.php is in "anydirectory/" and MeLeeCMS.php is in "anydirectory/includes/", then these defaults will work just fine.
 ***************************************************************/
// The path to your wesite's root directory within the server. This is usually something like "/home/user/public_html/".
$GlobalConfig['server_path'] = dirname($_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR;

// The URL path to your website from the domain name. If your website loads right from "yourdomain.com", this should be "/". If it loads from "yourdomain.com/yoursite", this should be "/yoursite/". Etc.
$GlobalConfig['url_path'] = dirname($_SERVER['SCRIPT_NAME']) ."/";

/***************************************************************/
// This will be prepended to the names of any cookies set by MeLeeCMS. Generally this is only the session id cookie. Changing this in a production site will force all users to log back in, as well as lose any other data that is tracked using cookies.
$GlobalConfig['cookie_prefix'] = "melee_";

// This is the class name of the class to use to handle user accounts. If invalid or blank, it will default to the User class found in includes/classes/User.php. It is strongly recommended that you use another class, as the default User class does not support logins and will not protect the control panel directory at all. If you want to use the User class because of its simplicity and lack of logins, then you should set up Apache password protection on the control panel directory to protect it if you have nothing else.
$GlobalConfig['user_system'] = "User";

// This is what will appear in the title bar of your website. Each page has its own title, followed by a hyphen, followed by whatever you specify here. This value will be overwritten by the MeLeeCMS control panel, so setting it up in this config file is only useful for websites that do not use a database to store site settings.
$GlobalConfig['site_title'] = $_SERVER['HTTP_HOST'];

// This is the default theme that will be loaded on your site if no other theme is specified for a page or if the specified theme isn't valid. This value will be overwritten by the MeLeeCMS control panel, so setting it up in this config file is only useful for websites that do not use a database to store site settings.
$GlobalConfig['default_theme'] = "default";
