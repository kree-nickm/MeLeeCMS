<?php
namespace MeLeeCMS;

$response[$formId] = [];

if(empty($builder->user) || !$builder->user->has_permission("view_cpanel"))
{
  $response[$formId]['success'] = false;
  $response[$formId]['status'] = "denied";
  $response[$formId]['error'] = "Not logged in.";
  return;
}

require_once("includes". DIRECTORY_SEPARATOR ."admin". DIRECTORY_SEPARATOR ."define-settings.php");

if(empty($settings[$_POST['setting']]))
{
  $response[$formId]['success'] = false;
  $response[$formId]['status'] = "invalid";
  $response[$formId]['error'] = "Invalid setting.";
  return;
}

$mysql_data = ['setting' => $_POST['setting']];
if(!empty($settings[$_POST['setting']]['fromhtml']))
  $mysql_data['value'] = ($settings[$_POST['setting']]['fromhtml'])($_POST['value']);
else
  $mysql_data['value'] = $_POST['value'];

$builder->database->insert("settings", $mysql_data, true, ['setting'], true);
$dbsettings = $builder->database->query("SELECT * FROM settings", Database::RETURN_ALL);
$response[$formId]['success'] = true;
$response[$formId]['settings'] = [];
foreach($dbsettings as $setting)
{
  if(!empty($settings[$setting['setting']]['tohtml']))
    $response[$formId]['settings'][$setting['setting']] = ($settings[$setting['setting']]['tohtml'])($setting['value']);
  else
    $response[$formId]['settings'][$setting['setting']] = $setting['value'];
}
