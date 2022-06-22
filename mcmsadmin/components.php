<?php
require_once("load_page.php");

$components = $builder->database->query("SELECT * FROM `page_components`");
$table = $builder->add_content(new Container("", []), "components_table");
foreach($components as $comp)
{
	$children = [
		'index' => $comp['index'],
		'title' => $comp['title'],
		'css' => [],
		'js' => [],
		'xsl' => [],
		'in_page' => [],
		'in_component' => [],
	];
	
	$page_css = json_decode($comp['css'], true);
	if(is_array($page_css)) foreach($page_css as $css)
	{
		$children['css'][] = $css['file'];
	}
	$page_js = json_decode($comp['js'], true);
	if(is_array($page_js)) foreach($page_js as $js)
	{
		$children['js'][] = $js['file'];
	}
	$page_xsl = json_decode($comp['xsl'], true);
	if(is_array($page_xsl)) foreach($page_xsl as $xsl)
	{
		$children['xsl'][] = $xsl;
	}
	
	$children['in_page'] = $builder->database->query("SELECT `index`,`title`,`url` FROM `pages` WHERE `content` LIKE '%;O:9:\"Component\":2:{s:5:\"index\";i:". (int)$comp['index'] .";%'", Database::RETURN_ALL);
	$children['in_component'] = $builder->database->query("SELECT `index`,`title` FROM `page_components` WHERE `content` LIKE '%;O:9:\"Component\":2:{s:5:\"index\";i:". (int)$comp['index'] .";%'", Database::RETURN_ALL);
	
	$table->add_content(new Text($children, []));
}

$builder->attach_xsl("cpanel-component-list.xsl", "", true);
$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");