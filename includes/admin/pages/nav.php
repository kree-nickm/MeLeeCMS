<?php
namespace MeLeeCMS;

$builder->attachXSL("cpanel.xsl", "", true);
$builder->attachJS("cpanel.js", "", true);

$nav = $builder->addContent(new Menu(), "nav");
if($builder->user->hasPermission("view_cpanel"))
{
	$nav->addLink("mcmsadmin/settings", "Settings");
	$nav->addLink("mcmsadmin/pages", "Pages");
	$nav->addLink("mcmsadmin/components", "Components");
	$nav->addLink("mcmsadmin/themes", "Themes");
	$nav->addLink("mcmsadmin/errors", "Errors");
	$nav->addLink("mcmsadmin/changes", "Changes");
	$nav->addLink("mcmsadmin/data", "Data");
	$nav->addLink("/", "Back to Site");
}

if(!empty($_SESSION['onload_notification']))
	$builder->addContent(new Text($_SESSION['onload_notification'], ['notification'=>"onload"]));
unset($_SESSION['onload_notification']);
