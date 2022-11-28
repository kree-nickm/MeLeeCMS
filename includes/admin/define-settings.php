<?php
namespace MeLeeCMS;

/** Describe all the database-stored settings of MeLeeCMS here. These will overwrite what is defined in config.php.
What determines if a setting goes here, or only in config.php? These settings won't render the website completely inoperable if someone were to change them carelessly. Because, if the site breaks to the point that the control panel won't even load as a result of a setting changed here, then there is no way to fix it other than manually accessing the database and changing the faulty value. This includes settings like the database options, cpanel_dir, cpanel_theme, user_system, etc.
Additionally, any setting that would only ever need to change if there was a change in the underlying server configuration, or some other change that only a developer could facilitate, has no business being here. This includes settings like force_https, server_path, url_path, etc.
Lastly, settings that shouldn't ever need to be changed for any reason after the website is set up have no business being here. This includes settings like cookie_prefix, etc.
*/
$settings = [];

$settings['site_title'] = [
  'default' => $GlobalConfig['site_title'],
  'type' => "string",
  'description' => "Text that will be appended to the title bar of the browser on all pages of the site.",
  'section' => "main",
];
$settings['default_theme'] = [
  'default' => $GlobalConfig['default_theme'],
  'type' => "theme",
  'description' => "Theme that will apply to all pages of the site. Note that the control panel theme can only be set in config.php.",
  'section' => "main",
];
$settings['index_page'] = [
  'default' => $GlobalConfig['index_page'],
  'type' => "page",
  'description' => "The page that will load if the user requests the URL of the website root.",
  'section' => "main",
];
$settings['maintenance_until'] = [
  'default' => date("Y-m-d\TH:i"),
  'type' => "datetime",
  'tohtml' => function($value){
    return date("Y-m-d\TH:i", $value);
  },
  'fromhtml' => function($value){
    return strtotime($value);
  },
  'description' => "When the maintenance will end (". date("T") ."), or when the last maintenance ended. The 'default' button will set it to the current time (when the page loaded).",
  'section' => "main",
];

if(!empty($GlobalConfig['twitch_enabled']))
{
  $settings['twitch_implicit_enabled'] = [
    'default' => $GlobalConfig['twitch_implicit_enabled'],
    'type' => "boolean",
    'description' => "Whether to use the client-side (JavaScript) Twitch.tv API on this site in addition to the server-side one.",
    'section' => "twitch",
  ];
}
