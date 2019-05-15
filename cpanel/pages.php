<?php
require_once("load_page.php");

// Page List
$pages = $builder->database->query("SELECT * FROM `pages`");
$table = $builder->add_content(new Container("", ['display'=>"table"]), "pages_table");
foreach($pages as $page)
{
	$children = [
		'index' => $page['index'],
		'title' => $page['title'],
		'url' => $page['url'],
		'css' => [],
		'js' => [],
		'xsl' => [],
		'permission' => $page['permission'] ? [] : "N/A",
	];
	if($page['subtheme'] == "")
		$children['subtheme@default'] = "default";
	else
	{
		foreach($builder->themes[$builder->get_theme()]['subtheme'] as $subtheme)
			if($page['subtheme'] == $subtheme['__attr:name'])
				$children['subtheme'] = $page['subtheme'];
		if($children['subtheme'] == "")
			$children['subtheme@invalid'] = $page['subtheme'];
	}
	
	$page_css = json_decode($page['css'], true);
	if(is_array($page_css)) foreach($page_css as $css)
	{
		$children['css'][] = $css['file'];
	}
	$page_js = json_decode($page['js'], true);
	if(is_array($page_js)) foreach($page_js as $js)
	{
		$children['js'][] = $js['file'];
	}
	$page_xsl = json_decode($page['xsl'], true);
	if(is_array($page_xsl)) foreach($page_xsl as $xsl)
	{
		$children['xsl'][] = $xsl;
	}
	
	if(is_array($children['permission'])) foreach(User::get_permissions($builder) as $num=>$perm)
		if(($num & $page['permission']) == $num)
			$children['permission'][] = $perm;
	$table->add_content(new Text($children, []));
}

// Special Page List
$types = [
	1 => "access denied",
	2 => "not found",
	3 => "db error",
];
$spages = $builder->database->query("SELECT * FROM `pages_special`");
$table = $builder->add_content(new Container("", ['display'=>"table"]), "special_pages_table");
foreach($spages as $page)
{
	$children = [
		'index' => $page['index'],
		'title' => $page['title'],
		'css' => [],
		'js' => [],
		'xsl' => [],
		'type' => $types[$page['index']],
	];
	if($page['subtheme'] == "")
		$children['subtheme@default'] = "default";
	else
	{
		foreach($builder->themes[$builder->get_theme()]['subtheme'] as $subtheme)
			if($page['subtheme'] == $subtheme['__attr:name'])
				$children['subtheme'] = $page['subtheme'];
		if($children['subtheme'] == "")
			$children['subtheme@invalid'] = $page['subtheme'];
	}
	
	$page_css = json_decode($page['css'], true);
	if(is_array($page_css)) foreach($page_css as $css)
	{
		$children['css'][] = $css['file'];
	}
	$page_js = json_decode($page['js'], true);
	if(is_array($page_js)) foreach($page_js as $js)
	{
		$children['js'][] = $js['file'];
	}
	$page_xsl = json_decode($page['xsl'], true);
	if(is_array($page_xsl)) foreach($page_xsl as $xsl)
	{
		$children['xsl'][] = $xsl;
	}
	$table->add_content(new Text($children, []));
}

// Finalize
$builder->attach_xsl("cpanel-pages-table.xsl", "", true);
$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");