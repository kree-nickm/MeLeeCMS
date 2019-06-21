<?php
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

// This will redirect any page request from HTTP to HTTPS automatically, making sure no one accidentally uses the non-secure URL. Make sure your website has an SSL certificate and HTTPS works before setting this to true.
// TODO: Currently only works with pages that use the base URL. Not cpanel.
$GlobalConfig['force_https'] = false;

/***************************************************************/
// This will be prepended to the names of any cookies set by MeLeeCMS. Generally this is only the session id cookie. Changing this in a production site will force all users to log back in, as well as lose any other data that is tracked using cookies.
$GlobalConfig['cookie_prefix'] = "melee_";

// This is the class name of the class to use to handle user accounts. If invalid or blank, it will default to the User class found in includes/classes/User.php. It is strongly recommended that you use another class, as the default User class does not support logins and will not protect the control panel directory at all. If you want to use the User class because of its simplicity and lack of logins, then you should set up Apache password protection on the control panel directory to protect it if you have nothing else.
$GlobalConfig['user_system'] = "User";

// This is what will appear in the title bar of your website. Each page has its own title, followed by a hyphen, followed by whatever you specify here. This value will be overwritten by the MeLeeCMS control panel, so setting it up in this config file is only useful for websites that do not use a database to store site settings.
$GlobalConfig['site_title'] = $_SERVER['HTTP_HOST'];

// This is the default theme that will be loaded on your site if no other theme is specified for a page or if the specified theme isn't valid. This value will be overwritten by the MeLeeCMS control panel, so setting it up in this config file is only useful for websites that do not use a database to store site settings.
$GlobalConfig['default_theme'] = "default";

// This is the theme that will be used by the control panel. DO NOT CHANGE THIS, without first making absolutely sure that the new theme fully support all control panel features, because most themes will not.
$GlobalConfig['cpanel_theme'] = "bootstrap4";

// This is the directory that contains the control panel files. If you change this, also make sure to change the actual directory name. This might be necessary if your site also uses the actual CPanel software, as the URLs will conflict. If you are only changing this to try to make the control panel URL harder to find, don't bother. You should make sure the directory is protected whether its URL is public knowledge or not. Note: That should already be taken care of if you use any user_system other than User.
$GlobalConfig['cpanel_dir'] = "cpanel";