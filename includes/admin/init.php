<?php

ini_set("display_errors", 1);

// TODO: Both make it impossible to put ../ in the 'file' field, and make the cpanel load from the admin/ dir automatically.
$GlobalConfig['pages']['adminSettings'] = [
   'url' => $GlobalConfig['cpanel_dir'] ."/settings",
   'file' => "../admin/pages/settings.php",
];
$GlobalConfig['pages']['adminPages'] = [
   'url' => $GlobalConfig['cpanel_dir'] ."/pages",
   'file' => "../admin/pages/pages.php",
];
$GlobalConfig['pages']['adminChanges'] = [
   'url' => $GlobalConfig['cpanel_dir'] ."/changes",
   'file' => "../admin/pages/changes.php",
];

$GlobalConfig['forms']['adminSaveSettings'] = [
   'file' => "../admin/forms/save-settings.php",
   'select' => function($cms){
      return !empty($_POST['saveSettings']);
   },
];
