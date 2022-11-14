.. toctree::
   :hidden:
   :maxdepth: 2

   pages
   forms
   permissions
   
Configuration
=============

Configuration of MeLeeCMS is done through the ``$GlobalConfig`` array in the ``includes/config.php`` file. Below is a list of all supported keys of that array and a description of each. Defaults for these values are provided in ``includes/defaultconfig.php`` where possible, but some of them, such as the MySQL options, you must specify yourself before the website will function.

MySQL Options
-------------
Settings to connect to the MySQL database where your website's data will be stored. As of now, only MySQL is supported, but other data storage software might be supported in the future.

dbhost
......
The hostname of the MySQL server you want to connect to. On most website setups, this will be ``"localhost"``.

Default: None; you must specify this.

dbuser
......
The username of the MySQL user that you will use to access your MySQL database.

Default: None; you must specify this.

dbpass
......
Password for the above user.

Default: None; you must specify this.

dbname
......
The name of the MySQL database that will store all MeLeeCMS data.

Default: None; you must specify this.

URI Settings
------------
These help MeLeeCMS find and link to various files.

server\_path
......
The path to your wesite's root directory within the server. This is usually something like ``"/home/user/public_html/"`` or ``"/var/www/"``.

Default: The directory that contains the directory that contains ``config.php``.

cpanel\_dir
......
This is the URL that will cause MeLeeCMS to attempt to load the control panel. The site should not have any page that shares this name, or an Exception will be thrown when that page attempts to load.

Default: ``"mcmsadmin"``

url\_path
......
The URL path to your website from the domain name. If your website loads right from `yourdomain.com <#>`_, this should be ``"/"``. If it loads from `yourdomain.com/yoursite <#>`_, this should be ``"/yoursite/"``. Etc.

Default: The URL of the directory containing the currently-executing ``index.php``.

force\_https
......
This will redirect any page request from HTTP to HTTPS automatically, making sure no one accidentally uses the non-secure URL. Make sure your website has an SSL certificate and HTTPS works before setting this to ``true``.

Default: ``false``

User Settings
-------------
Various other settings that determine how the site will appear to users and their browsers.

cookie\_prefix
......
This will be prepended to the names of any cookies set by MeLeeCMS. Generally, the session id cookie in the only cookie MeLeeCMS uses. Changing this in a production site will force all users to log back in, as well as lose any other data that is tracked using cookies.

Default: ``"melee_"``

user\_system
......
This is the class name of the class to use to handle user accounts. If invalid or blank, it will default to the ``User`` class found in ``includes/classes/User.php``. Note that the ``User`` class does not support logins or the control panel, so it is only suitable for extremely simple websites. You should change this to ``"MeLeeCMSUser"`` if you want to support simple logins and user accounts.

Default: ``"User"``

site\_title
......
This is what will appear in the title bar of your website. Each page has its own title, followed by a hyphen, followed by whatever you specify here. This value can be overwritten by the MeLeeCMS control panel.

Default: The domain name of the website.

default\_theme
......
This is the default theme that will be loaded on your site if no other theme is specified for a page or if the specified theme isn't valid. This value can be overwritten by the MeLeeCMS control panel if you add more themes.

Default: ``"bootstrap4"``

cpanel\_theme
......
This is the theme that will be used by the control panel. Do not change this without making sure that the new theme fully supports all control panel features, or else the control panel will not work, and you will be unable to manage your site (until you change this value back).

Default: ``"bootstrap4"``

index\_page
......
Page that will load if someone visits your website but doesn't specify a page. For example, if they visit the URL `www.yourdomain.com/ <#>`_, with nothing after the slash. This value can be overwritten by the MeLeeCMS control panel once you have created other pages on your site.

Default: ``"test-page"``

Error Reporting
-------------
MeLeeCMS records errors in three different ways: standard error log text files, the error\_log table in the database, and in the page XML output to users who have permission to view errors. You can determine what types of errors are reported to each location separately using the settings below. The format of these settings is exactly the same as the `error_reporting(int) <https://www.php.net/manual/en/function.error-reporting.php>`_ PHP native function.

error\_file\_reporting
......
Errors that will be reported to error log files. MeLeeCMS starts a new error log file every day, which will be in a new directory every month. At the start of a month, a directory will be created in ``includes/logs/`` named with the format ``errors-YYYY-MM``. In that directory, log files are named with the format ``YYYY-MM-DD.log``.

Note that your server configuration settings might cause these log files to have undesirable permissions. As the files are created by the PHP process, they will be owned by the user that owns the PHP process. If this is not the same user that owns the website files, then it's possible that the site owner will not be able to access these log files. A user with root permissions would be required to fix this. I have no idea how to fix this issue from within MeLeeCMS; it's likely not possible. The server would have to be configured in such a way that this doesn't happen.

Default: ``E_ALL & ~E_STRICT``

error\_database\_reporting
......
Errors that will be inserted into the error\_log table of the database. These errors can be reviewed on the control panel by users with the view\_errors permission.

Default: ``E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED | E_USER_NOTICE | E_USER_DEPRECATED)``

error\_xml\_reporting
......
Errors that will be included in the XML output for users with the view\_errors permission, which means that XSLT can process them and output them to the page in some way. In the bootstrap4 theme, a red exclaimation point will appear in the top right if errors occurred during the page load, and it can be clicked to reveal those errors.

Default: ``E_ALL``

Other Definitions
------------
You can define content such as pages and forms as well as alter the permissions used by your website from within config.php, if you would prefer to do that instead of using the control panel.

pages
.....
This is an associative array of other associative arrays, which define the properties of custom pages. Visit the `pages` section to learn more.

forms
.....
This is an associative array of other associative arrays, which define the properties of custom pages. Visit the `forms` section to learn more.

permissions
.....
This is an associative array of other arrays, which define the permissions that will be used by MeLeeCMS. Visit the `permissions` section to learn more.