<?
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
if(is_numeric($_POST['component_index']))
	$existing = $builder->database->query("SELECT * FROM `page_components` WHERE `index`=". (int)$_POST['component_index'] ." LIMIT 0,1", Database::RETURN_ROW);
$mysql_data = array();
$errors = array();
// index
if($existing['index'])
	$mysql_data['index'] = $existing['index'];
// title
if($_POST['component_title'] != "" || $existing['title'] != "")
{
	if($_POST['component_title'] != "" && $_POST['component_title'] != $existing['title'])
		$mysql_data['title'] = $_POST['component_title'];
}
else
{
	$errors[] = ['__attr:type'=>"warning", "Component does not have a title."];
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
	if($stylesheets != $existing['css'])
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
	if($javascripts != $existing['js'])
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
	if($templates != $existing['xsl'])
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
			}
			$contents_result[$i] = $object;
		}
		return $contents_result;
	}
	$page_contents = contentsToArray($_POST['page_content']);
	//print_r($page_contents);
	$page_contents = serialize($page_contents);
	if($page_contents != $existing['content'])
		$mysql_data['content'] = $page_contents;
}

print_r($mysql_data);
$_SESSION['onload_notification'] = $errors;
foreach($errors as $err)
{
	if($err['__attr:type'] == "danger")
	{
		$_SESSION['onload_notification']['__attr:title'] = "Component Update Failed";
		header("Location: component_edit.php?compId=". $_POST['component_index']);
		exit;
	}
}
if(count($mysql_data) <= 1)
{
	$_SESSION['onload_notification']['__attr:title'] = "Component Not Updated";
	$_SESSION['onload_notification'][] = ['__attr:type'=>"primary", "No changes were made to the component."];
	header("Location: components.php");
	exit;
}

// ---------- Handle MySQL ----------
$_SESSION['onload_notification']['__attr:title'] = "Component Updated";
$builder->database->insert("page_components", $mysql_data, true, [], true);
header("Location: components.php");
