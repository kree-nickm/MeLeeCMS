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

Other Definitions
------------
You can define content such as pages and forms, as well as alter the permissions used by your website.

pages
.....
This is an associative array of other associative arrays, which define the properties of custom pages. Visit the `pages` section to learn more.

forms
.....
This is an associative array of other associative arrays, which define the properties of custom pages. Visit the `forms` section to learn more.

permissions
.....
This is an associative array of other arrays, which define the permissions that will be used by MeLeeCMS. Visit the `permissions` section to learn more.