<?php
namespace MeLeeCMS;

require_once("nav.php");
$builder->setTitle("Settings - Control Panel");

/** Describe all the database-stored settings of MeLeeCMS here. These will overwrite what is defined in config.php.
What determines if a setting goes here, or only in config.php? These settings won't render the website completely inoperable if someone were to change them carelessly. Because, if the site breaks to the point that the control panel won't even load as a result of a setting changed here, then there is no way to fix it other than manually accessing the database and changing the faulty value. This includes settings like the database options, cpanel_dir, cpanel_theme, user_system, etc.
Additionally, any setting that would only ever need to change if there was a change in the underlying server configuration, or some other change that only the site owner could facilitate, has no business being here. This includes settings like force_https, server_path, url_path, etc.
Lastly, settings that shouldn't ever need to be changed for any reason after the website is set up have no business being here. This includes settings like cookie_prefix, etc.
*/
$settings = [];

// General settings
$settings['site_title'] = [
   'setting' => "site_title",
   'value' => $GlobalConfig['site_title'],
   'default' => $GlobalConfig['site_title'],
   'type' => "string",
   'description' => "Text that will be appended to the title bar of the browser on all pages of the site.",
   'section' => "main",
];
$settings['default_theme'] = [
   'setting' => "default_theme",
   'value' => $GlobalConfig['default_theme'],
   'default' => $GlobalConfig['default_theme'],
   'type' => "theme",
   'description' => "Theme that will apply to all pages of the site. Note that the control panel theme can only be set in config.php.",
   'section' => "main",
];
$settings['index_page'] = [
   'setting' => "index_page",
   'value' => $GlobalConfig['index_page'],
   'default' => $GlobalConfig['index_page'],
   'type' => "page",
   'description' => "The page that will load if the user requests the URL of the website root.",
   'section' => "main",
];

// TODO: Twitch.tv settings, only display if needed.
$twitch = false;
if($twitch)
{
   $settings['twitch_implicit_enabled'] = [
      'setting' => "twitch_implicit_enabled",
      'value' => $GlobalConfig['twitch_implicit_enabled'],
      'default' => $GlobalConfig['twitch_implicit_enabled'],
      'type' => "boolean",
      'description' => "Whether to use the client-side (JavaScript) Twitch.tv API on this site in addition to the server-side one.",
      'section' => "twitch",
   ];
}

$db_settings = $builder->database->query("SELECT * FROM `settings`", Database::RETURN_ALL);
foreach($db_settings as $dbset)
{
   if(!empty($settings[$dbset['setting']]))
      $settings[$dbset['setting']]['value'] = $dbset['value'];
}

$container = $builder->addContent(new Container("Site Settings"));

$tables = [];
$tables['main'] = $container->addContent(new Container("Main Settings", ['format'=>"table"]));
$tables['main']->addContent(new Text("General settings that apply to any site."), "subtitle");
if($twitch)
{
   $tables['twitch'] = $container->addContent(new Container("Twitch Settings", ['format'=>"table"]));
   $tables['twitch']->addContent(new Text("Settings that apply to sites with Twitch.tv integration."), "subtitle");
}
foreach($tables as $section=>$container)
{
   $row = $container->addContent(new Container("", ['type'=>"header"]));
   $row->addContent(new Text("Setting", ['raw'=>true]));
   $row->addContent(new Text("Value", ['raw'=>true]));
   $row->addContent(new Text("Description", ['raw'=>true]));
}
foreach($settings as $setting)
{
	$textprops = [
      'value' => $setting['value'],
      'default' => $setting['default'],
      'name' => $setting['setting'],
   ];
	$textattrs = [];
	if($setting['type'] == "page")
	{
		$textprops['option'] = [];
		foreach($builder->pages as $page)
         if(!$page->is_cpanel)
            $textprops['option'][] = [$page->title, '__attr:value'=>$page->url];
		$textattrs['type'] = "select";
	}
	else if($setting['type'] == "theme")
	{
		$textprops['option'] = [];
		foreach($builder->themes as $theme)
			$textprops['option'][] = [$theme->name, '__attr:value'=>$theme->name];
		$textattrs['type'] = "select";
	}
	else if($setting['type'] == "boolean")
	{
      if($setting['value'])
         $textprops['checked'] = true;
		$textattrs['type'] = "input-check";
	}
	else
	{
		$textprops['size'] = "50";
		$textattrs['type'] = "input-text";
	}
   $row = $tables[$setting['section']]->addContent(new Container("", ['type'=>"body"]));
	$row->addContent(new Text($setting['setting'], ['raw'=>true]));
	$row->addContent(new Text($textprops, $textattrs), "cpanel-setting");
	$row->addContent(new Text($setting['description'], ['raw'=>true]));
}
