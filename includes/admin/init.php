<?php

ini_set("display_errors", 1);

// Note: When the control panel is loaded, 'file' looks inside /includes/admin/pages, not /includes/pages as normal.
$GlobalConfig['pages']['adminSettings'] = [
   'url' => $GlobalConfig['cpanel_dir'] ."/settings",
   'file' => "settings.php",
   'permissions' => ["view_cpanel"],
];
$GlobalConfig['pages']['adminPages'] = [
   'url' => $GlobalConfig['cpanel_dir'] ."/pages",
   'file' => "pages.php",
   'permissions' => ["view_cpanel"],
];
$GlobalConfig['pages']['adminThemes'] = [
   'url' => $GlobalConfig['cpanel_dir'] ."/themes",
   'file' => "themes.php",
   'permissions' => ["view_cpanel"],
];
$GlobalConfig['pages']['adminData'] = [
   'url' => $GlobalConfig['cpanel_dir'] ."/data",
   'file' => "data.php",
   'permissions' => ["view_cpanel"],
];
$GlobalConfig['pages']['adminChanges'] = [
   'url' => $GlobalConfig['cpanel_dir'] ."/changes",
   'file' => "changes.php",
   'permissions' => ["view_cpanel"],
];

$GlobalConfig['forms']['adminSaveSettings'] = [
   'file' => "../admin/forms/save-settings.php",
   'select' => function($cms){
      return !empty($_POST['adminSaveSettings']);
   },
];
