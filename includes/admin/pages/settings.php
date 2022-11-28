<?php
namespace MeLeeCMS;

require_once("nav.php");
$builder->setTitle("Settings - Control Panel");

require_once("includes". DIRECTORY_SEPARATOR ."admin". DIRECTORY_SEPARATOR ."define-settings.php");

$db_settings = $builder->database->query("SELECT * FROM `settings`", Database::RETURN_ALL);
foreach($db_settings as $dbset)
{
  if(!empty($settings[$dbset['setting']]))
  {
    if(!empty($settings[$dbset['setting']]['tohtml']))
      $settings[$dbset['setting']]['value'] = ($settings[$dbset['setting']]['tohtml'])($dbset['value']);
    else
      $settings[$dbset['setting']]['value'] = $dbset['value'];
  }
}

$container = $builder->addContent(new Container("Site Settings"));

$tables = [];
$tables['main'] = $container->addContent(new Container("Main Settings", ['format'=>"table"]));
$tables['main']->addContent(new Text("General settings that apply to any site."), "subtitle");
if(!empty($GlobalConfig['twitch_enabled']))
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
foreach($settings as $name=>$setting)
{
  $textprops = [
    'value' => empty($setting['value']) ? $setting['default'] : $setting['value'],
    'default' => $setting['default'],
    'name' => $name,
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
    if(!empty($textprops['value']))
      $textprops['checked'] = true;
    $textattrs['type'] = "input-check";
  }
  else
  {
    //$textprops['size'] = "50";
    $textattrs['type'] = "normal";
    if($setting['type'] == "number")
      $textattrs['subtype'] = "number";
    if($setting['type'] == "datetime")
      $textattrs['subtype'] = "datetime";
  }
  $row = $tables[$setting['section']]->addContent(new Container("", ['type'=>"body"]));
  $row->addContent(new Text($name, ['raw'=>true]));
  $row->addContent(new Text($textprops, $textattrs), "cpanel-setting");
  $row->addContent(new Text($setting['description'], ['raw'=>true]));
}
