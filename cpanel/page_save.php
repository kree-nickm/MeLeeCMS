<?php
require_once("../includes/MeLeeCMS.php");
$builder = new MeLeeCMS(15);
$admin_perm = array_search("ADMIN", User::get_permissions($builder));
if($admin_perm)
	if(!$builder->require_permission($admin_perm))
	{
		$builder->render($_GET['output']=="xml"?"__xml":"cpanel");
		exit;
	}

header("Content-type: text/plain");
print_r($_REQUEST);

// ---------- Validate input. ----------
if(is_numeric($_POST['page_index']))
	$existing = $builder->database->query("SELECT * FROM `pages` WHERE `index`=". (int)$_POST['page_index'] ." LIMIT 0,1", Database::RETURN_ROW);
else if(is_numeric($_POST['page_special_index']))
{
	$special = true;
	$existing = $builder->database->query("SELECT * FROM `pages_special` WHERE `index`=". (int)$_POST['page_special_index'] ." LIMIT 0,1", Database::RETURN_ROW);
}
$mysql_data = array();
$errors = array();
// index
if($existing['index'])
	$mysql_data['index'] = $existing['index'];
else if($special)
	$errors[] = ['__attr:type'=>"danger", "Invalid special page index."];
// title
if($_POST['page_title'] != "" || $existing['title'] != "")
{
	if(($_POST['page_title'] != "" && $_POST['page_title'] != $existing['title']) || $_POST['save'] == 2)
		$mysql_data['title'] = $_POST['page_title'];
}
else
{
	$errors[] = ['__attr:type'=>"danger", "Page must have a title."];
}
// url
if(!$special)
{
	if($_POST['page_url'] == urlencode($_POST['page_url']) && ($_POST['page_url'] != "" || $existing['url'] != ""))
	{
		if(($_POST['page_url'] != "" && $_POST['page_url'] != $existing['url']) || $_POST['save'] == 2)
			$mysql_data['url'] = $_POST['page_url'];
	}
	else
	{
		if($_POST['page_url'] == "")
			$errors[] = ['__attr:type'=>"danger", "Page must have a URL."];
		else
			$errors[] = ['__attr:type'=>"danger", "Invalid page URL '". $_POST['page_url'] ."'."];
	}
}
// subtheme
if($_POST['subtheme'] != "" || $existing['subtheme'] != "")
{
	if(($_POST['subtheme'] != "" && $_POST['subtheme'] != $existing['subtheme']) || $_POST['save'] == 2)
		$mysql_data['subtheme'] = $_POST['subtheme'];
}
else
{
	$mysql_data['subtheme'] = "default";
}
// permissions
if(!$special && is_array($_POST['permissions']))
{
	$permission = 0;
	$all_perms = User::get_permissions($builder);
	foreach($_POST['permissions'] as $perm)
	{
		if(isset($all_perms[$perm]))
			$permission |= $perm;
		else if($perm != "")
			$errors[] = ['__attr:type'=>"warning", "Unknown permission on page '". $perm ."'."];
	}
	if($permission != $existing['permission'] || $_POST['save'] == 2)
		$mysql_data['permission'] = $permission;
}
// css
if(is_array($_POST['page_css']))
{
	$stylesheets = array();
	foreach($_POST['page_css'] as $css)
		if($css != "")
			$stylesheets[] = ['fromtheme'=>true, 'file'=>$css];
}
if(is_array($stylesheets))
{
	$stylesheets = json_encode($stylesheets);
	if($stylesheets != $existing['css'] || $_POST['save'] == 2)
		$mysql_data['css'] = $stylesheets;
}
// js
if(is_array($_POST['page_js']))
{
	$javascripts = array();
	foreach($_POST['page_js'] as $js)
		if($js != "")
			$javascripts[] = ['fromtheme'=>true, 'file'=>$js];
}
if(is_array($javascripts))
{
	$javascripts = json_encode($javascripts);
	if($javascripts != $existing['js'] || $_POST['save'] == 2)
		$mysql_data['js'] = $javascripts;
}
// xsl
if(is_array($_POST['page_xsl']))
{
	$templates = array();
	foreach($_POST['page_xsl'] as $xsl)
		if($xsl != "")
			$templates[] = $xsl;
	$templates = json_encode($templates);
	if($templates != $existing['xsl'] || $_POST['save'] == 2)
		$mysql_data['xsl'] = $templates;
}

if(is_array($_POST['content']))
{
	$errors[] = ['__attr:type'=>"warning", "Page contents can only be updated with JavaScript."];
}
if(is_array($_POST['page_content']))
{
	function contentsToArray($contents)
	{
		global $errors;
		$contents_result = [];
		foreach($contents as $i=>$content)
		{
			if(is_numeric($i))
				$i = "__".$i;
			$object = new $content['content_class']();
			$props = $object->set_cms($builder)->get_properties();
			foreach($props as $k=>$v)
			{
				if(($v['type'] == "string" || $v['type'] == "paragraph") && isset($content[$k]))
				{
					$object->$k = $content[$k];
				}
				else if(($v['type'] == "component") && is_numeric($content[$k]))
				{
					$object->$k = (int)$content[$k];
				}
				else if(($v['type'] == "dictionary") && is_array($content[$k]))
				{
					$object->$k = $content[$k];
				}
				else if(($v['type'] == "container") && is_array($content[$k]))
				{
					$object->$k = contentsToArray($content[$k]);
				}
				else if($v['type'] == "database_table" && isset($content[$k]))
				{
					$object->$k = $content[$k];
					if(is_array($content[$k.'_cols']))
						$object->config['columns'] = $content[$k.'_cols'];
				}
				else if(isset($content[$k]))
				{
					$errors[] = ['__attr:type'=>"warning", "Invalid property '". $v['type'] ."' in content type '". $content['content_class'] ."' can't be saved."];
				}
			}
			$contents_result[$i] = $object;
		}
		return $contents_result;
	}
	$page_contents = contentsToArray($_POST['page_content']);
	//print_r($page_contents);
	$page_contents = serialize($page_contents);
	if($page_contents != $existing['content'] || $_POST['save'] == 2)
		$mysql_data['content'] = $page_contents;
}

print_r($mysql_data);
$_SESSION['onload_notification'] = $errors;
foreach($errors as $err)
{
	if($err['__attr:type'] == "danger")
	{
		$_SESSION['onload_notification']['__attr:title'] = "Page Update Failed";
		header("Location: page_edit.php?pageId=". $_POST['page_index']);
		exit;
	}
}
if(count($mysql_data) <= 1)
{
	$_SESSION['onload_notification']['__attr:title'] = "Page Not Updated";
	$_SESSION['onload_notification'][] = ['__attr:type'=>"primary", "No changes were made to the page."];
	header("Location: pages.php");
	exit;
}

// ---------- Handle MySQL ----------
$types = [
	1 => "access denied",
	2 => "not found",
	3 => "db error",
];
if($special)
{
	$_SESSION['onload_notification']['__attr:title'] = "Page Updated";
	$builder->database->insert("pages_special", $mysql_data, true, [], true);
	$_SESSION['onload_notification'][] = ['__attr:type'=>"primary", "Changes to '". $types[$mysql_data['index']] ."' page have been saved."];
}
else if($_POST['save'] == 2)
{
	$_SESSION['onload_notification']['__attr:title'] = "Page Draft Saved";
	$mysql_data['user'] = $builder->user->get_property('index');
	$mysql_data['timestamp'] = time();
	$builder->database->insert("pages_drafts", $mysql_data, true, [], false);
	$savedpage = $builder->database->query("SELECT `index`,`title`,`url` FROM pages WHERE `index`=". (int)$mysql_data['index'], Database::RETURN_ROW);
	$_SESSION['onload_notification'][] = ['__attr:type'=>"primary", "Your draft of #". $savedpage['index'] ." ". $savedpage['title'] ." (/". $savedpage['url'] .") has been saved."];
}
else
{
	$_SESSION['onload_notification']['__attr:title'] = "Page Updated";
	$builder->database->insert("pages", $mysql_data, true, [], true);
	$savedpage = $builder->database->query("SELECT `index`,`title`,`url` FROM pages WHERE `index`=". (int)$mysql_data['index'], Database::RETURN_ROW);
	$_SESSION['onload_notification'][] = ['__attr:type'=>"primary", "Changes to #". $savedpage['index'] ." ". $savedpage['title'] ." (/". $savedpage['url'] .") have been saved."];
}
print_r($_SESSION['onload_notification']);
header("Location: pages.php");
