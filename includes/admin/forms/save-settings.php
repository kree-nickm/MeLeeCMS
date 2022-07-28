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

// TODO: Check for valid setting.

$mysql_data = [
   'setting' => $_POST['setting'],
   'value' => $_POST['value'],
];
$builder->database->insert("settings", $mysql_data, true, ['setting'], true);
$settings = $builder->database->query("SELECT * FROM settings", Database::RETURN_ALL);
$response[$formId]['success'] = true;
$response[$formId]['settings'] = [];
foreach($settings as $setting)
{
   $response[$formId]['settings'][$setting['setting']] = $setting['value'];
}
