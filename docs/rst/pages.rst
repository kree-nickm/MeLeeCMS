Pages
============
This page describes how to configure custom pages in ``config.php``. The guide on how to create pages with the control panel is elsewhere.

A normal page definition will look something like below::

   $GlobalConfig['pages']['key'] = [
      'url' => "page-url",
      'file' => "page-code.php",
      'title' => "Page Title",
      'css' => [],
      'js' => [],
      'xsl' => [],
      'permissions' => [],
   ];

Alternatively, you can also define special pages, such as error pages, like this::

   $GlobalConfig['pages']['404'] = [
      'id' => "404",
      'select' => function($cms){ return http_response_code()==404; },
      'file' => "404.php",
      'title' => "Page Title",
      'css' => [],
      'js' => [],
      'xsl' => [],
   ];

Notice the difference between the two is that normal pages have a 'url', whereas special pages have both an 'id' and a 'select'.

Page Fields
-----------
The page arrays use the following keys, which we will refer to as fields: url, id, select, file, title, css, js, xsl, permissions, and content.

Array Key
.........
While not technically a field, it's valuable to mention that the array key of the page array ('key' in the first example and '404' in the second) serves only one purpose, and that is to allow you to easily overwrite it later. While there's not much of a reason why you'd overwrite a normal page, it might be desirable to overwrite the default error pages with your own. To do that, simply use the same array key when defining your custom error page. Error page keys should be the same as the error code, and they are definied for the following error codes by default: 401, 403, 404, 503.

Feel free to leave the key blank to have the key automatically assigned to the next index, but just note that if you have more than 400 pages defined this way, the system might confuse subsequent pages with the '401' error page, etc.

url
........
What a user will need to type into the URL bar to access the page. This value will always be relative to your website root, or ``$GlobalConfig['url_path']``. A page with a URL is considered a normal page. A page with no URL is considered a special page, and it must have both the 'id' and 'select' fields instead.

id
........
A unique ID that MeLeeCMS will use to distinguish the page from others. This field is only required for a special page (ie. one that has no URL). This field is not required for a normal page, because a normal page will automatically use its URL as its ID, since URLs should already be unique. Note that normal pages and special pages are stored separately, so there shouldn't be any conflict if there's an ID overlap between them (keyword: shouldn't).

select
........
A function that will be run to determine if this special page should be loaded. If the function returns ``true``, this page will load. If it returns ``false``, then the next special page on the list will be checked, and so on. This field is required, along with 'id' for all special pages.

The way special pages are loaded is that first, MeLeeCMS will attempt to load the page at the URL given by the user. Along the way, if any errors are encountered, the page load is stopped and an HTTP error code is set, e.g. 401 if the user needs to log in, 403 if the user doesn't have permission, 404 if the page doesn't exist, or 503 if the site is in maintenence mode (other error codes may be added in the future). Then, MeLeeCMS begins to cycle through all defined special pages, checking their 'select' function as it goes. It stops at the first such function that returns true, and uses that special page as the current page to send to the user. All of the default special pages are simply error pages, and so their 'select' function simply checks the current HTTP error code.

file
........
A PHP file containing code to execute when this page loads. The PHP file must be in the ``includes/pages/`` directory. This is the most common way to load pages on a complex website, as the control panel is still a bit too primitive to handle a lot of advanced database-driven website functionality. The file is executed by `includes/get.php <files/includes-get.html>`_ after the MeLeeCMS object has been instantiated into the ``$builder`` variable, so it is available to this PHP file. This file will also be executed after all of the below fields have been applied to the page, so it can overwrite them.

title
........
The title of the page, which will appear in the browser title bar. This string will be prepended to the string specified in ``$GlobalConfig['site_title']``, separated by a hypen. This behavior cannot be changed at this time.

css
........
An array of CSS files to include with the page. The format of each field of this array can either be a string or another associative array. If it's an **associative array**, the fields are as follows:

**href** : *string*
      Filename of the CSS file, or full URL to the file if it's external, to include inside a ``&lt;link&gt;`` tag.

**code** : *string*
      Raw CSS code, which will be included onto the page within ``&lt;style&gt;`` tags.

**fromtheme** : *boolean*
      Whether to load this file from the current theme. If true, then 'href' is expected to be relative to the theme's CSS directory.

If you include both 'href' and 'code', they will be included onto the page separately as though you had provided them separately.

However, if you provide a **string** instead of an array, then the string will be treated as the 'href' field above, and 'fromtheme' will be determined based on whether your string begins with a protocol or not (e.g. ``http://``, which would set 'fromtheme' to ``false``).

Alternatively, you can provide a string for the 'css' field instead of an array at all. If you do, it should be a list of files separated by the regex character class ``[,;|]`` which will be split into an array as above. Lastly, you can also provide raw JSON that decodes into any of the above arrays, if you are so inclined.

js
........
Everything is exactly the same as above with the 'css' field, but replace all instances of "css" with "js", and "href" with "src", and ``&lt;style&gt;`` or ``&lt;link&gt;`` with ``&lt;script&gt;``.

xsl
........
The definition is exactly the same as above with the 'css' and 'js' fields. However, using 'fromtheme' with a ``false`` value is not supported and will probably break your page. XSLT is done server-side, so there is nothing to include onto the HTML of the page.

permissions
........
A list of permissions that are required for a user to be able to view the page. This field is not checked for special pages, as their loading conditions are separate. These permissions can be specified in a number of ways. For a single permission, you can simply provide it as a string. For multiple permissions, you can use a string with each permission separated by the regex character class ``[,.;|&+ ]`` which will be split into an array. You can also provide the permissions as that array of strings yourself. Lastly, you can provide raw JSON that decodes to such an array, if you are so inclined.

content
........
Serialized PHP object data that corresponds to an array of Content objects to include onto the page. This is exactly as annoying as it sounds, so it's extremely unlikely that you will ever actually specify this field in ``config.php``. To add content to the page, you should either use the control panel (which means you can't specify the page in ``config.php``), or do it via the PHP file specified in the 'file' field.