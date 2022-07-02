<?php
require_once("../includes/MeLeeCMS.php");
$builder = new MeLeeCMS(15);
$builder->set_title("Control Panel");
$builder->add_content(new Text("<span class='fas fa-cogs'></span> Control Panel"), "branding");

$admin_perm = array_search("ADMIN", User::get_permissions($builder));
if($admin_perm)
	if(!$builder->user->has_permission($admin_perm))
	{
		$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");
		exit;
	}

$nav = $builder->add_content(new Container("Navigation"), "nav");
$nav->add_content(new Text(['url'=>"index.php", 'text'=>"Settings"], ['type'=>"link",'active'=>stripos($_SERVER['PHP_SELF'],"index.php")!==false]));
$nav->add_content(new Text(['url'=>"pages.php", 'text'=>"Pages"], ['type'=>"link",'active'=>stripos($_SERVER['PHP_SELF'],"pages.php")!==false]));
$nav->add_content(new Text(['url'=>"components.php", 'text'=>"Components"], ['type'=>"link",'active'=>stripos($_SERVER['PHP_SELF'],"components.php")!==false]));
$nav->add_content(new Text(['url'=>"themes.php", 'text'=>"Themes"], ['type'=>"link",'active'=>stripos($_SERVER['PHP_SELF'],"themes.php")!==false]));
$nav->add_content(new Text(['url'=>"data.php", 'text'=>"Data"], ['type'=>"link",'active'=>stripos($_SERVER['PHP_SELF'],"data.php")!==false]));
$nav->add_content(new Text(['url'=>"changes.php", 'text'=>"Changes"], ['type'=>"link",'active'=>stripos($_SERVER['PHP_SELF'],"changes.php")!==false]));

$builder->attach_js("modal-extras.js", "", true);
if(!empty($_SESSION['onload_notification']))
{
	$builder->add_content(new Text($_SESSION['onload_notification'], ['notification'=>"onload"]));
	//$_GET['output'] = "xml";
}
unset($_SESSION['onload_notification']);