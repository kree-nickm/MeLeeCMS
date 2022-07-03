<?php
namespace MeLeeCMS;

require_once("load_page.php");

$page = $builder->add_content(new Container(), "theme-edit");
if(isset($builder->themes[$_GET['t']]))
{
	foreach($builder->themes[$_GET['t']]['css'] as $i=>$file)
		$builder->themes[$_GET['t']]['css'][$i] = [$file, '__attr:id'=>str_replace(".", "_", $file)];
	foreach($builder->themes[$_GET['t']]['js'] as $i=>$file)
		$builder->themes[$_GET['t']]['js'][$i] = [$file, '__attr:id'=>str_replace(".", "_", $file)];
	foreach($builder->themes[$_GET['t']]['xsl'] as $i=>$file)
		$builder->themes[$_GET['t']]['xsl'][$i] = [$file, '__attr:id'=>str_replace(".", "_", $file)];
	$page->add_content(new Text($builder->themes[$_GET['t']], ['name'=>$_GET['t']]), "main");
}
else
{
	header("Location: themes.php");
	exit;
}

$builder->attach_css("../../../addons/codemirror.css", "", true);
$builder->attach_css("cpanel.css", "", true);
$builder->attach_js("../../../addons/codemirror.js", "", true);
$builder->attach_js("cpanel-theme-content.js", "", true);
$builder->attach_xsl("cpanel-theme-edit.xsl", "", true);
$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");