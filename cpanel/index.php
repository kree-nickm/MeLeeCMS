<?php
require_once("load_page.php");

$settings = $builder->database->query("SELECT * FROM `settings` ORDER BY `setting` ASC");
$themes = array_keys($builder->themes);
$pages = $builder->database->query("SELECT `index`,`title` FROM `pages`");
$user_systems = User::get_subclasses($builder);
array_unshift($user_systems, "none");

$table = $builder->add_content(new Container("", []), "settings_table");
foreach($settings as $sett)
{
	$textprops = ['value'=>$sett['value']];
	$textattrs = [];
	if($sett['type'] == "user_system")
	{
		$textprops['option'] = $user_systems;
		$textattrs['type'] = "select";
	}
	else if($sett['type'] == "page")
	{
		$textprops['option'] = [];
		foreach($pages as $p)
			$textprops['option'][] = [$p['title'],'__attr:value'=>$p['index']];
		$textattrs['type'] = "select";
	}
	else if($sett['type'] == "theme")
	{
		$textprops['option'] = $themes;
		$textattrs['type'] = "select";
	}
	else
	{
		$textprops['size'] = "50";
		$textattrs['type'] = "input-text";
	}
	$row = $table->add_content(new Container("", []));
	$row->add_content(new Text($sett['setting'], []), "setting");
	$row->add_content(new Text($textprops, $textattrs), "value");
	$row->add_content(new Text($sett['description'], []), "description");
}

$builder->attach_xsl("cpanel-settings-table.xsl", "", true);
$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");