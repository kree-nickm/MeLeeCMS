<?php
require_once("load_page.php");

$page = $builder->add_content(new Container(), "themes-list");
foreach($builder->themes as $name=>$props)
{
	$page->add_content(new Text($props, $builder->get_theme()==$name?['current'=>"1"]:[]), $name);
}

$builder->attach_xsl("cpanel-themes-list.xsl", "", true);
$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");