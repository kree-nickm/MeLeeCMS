Installation
============

Requirements
------------
Before you can install MeLeeCMS, there are a few other things that must be installed first:

- Apache 2.4

  - If you use other HTTP software such as nginx, you'll have to figure out how to replicate the function of the .htaccess file yourself.

- PHP >= 5.6.0 (PHP7 recommended)

  - Fallbacks are provided for features that require PHP7.

- MySQL >= 5.7.8

  - Not tested with MySQL 8, but it should work, as long as 8 didn't remove key features.

Steps
-----
Note: These instructions could be out-dated, as the install process is being revamped.

1. Download the files into the web root of your server.

   - Easiest way is to use the GitHub repository at https://github.com/kree-nickm/MeLeeCMS
   - If you checkout the repo into your public directory, consider securing the .git folder from web access.

2. Edit install.php by inserting a password of your choice into the string at the top.
3. Load your site in a browser and go to the install.php file.