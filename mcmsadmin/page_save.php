<?php
namespace MeLeeCMS;

require_once("../includes/MeLeeCMS.php");
$builder = new MeLeeCMS(15);
$admin_perm = array_search("ADMIN", User::get_permissions($builder));
if($admin_perm)
	if(!$builder->require_permission($admin_perm))
	{
		$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");
		exit;
	}

//header("Content-type: text/plain");
//print_r($_REQUEST);

// ---------- Validate input. ----------
if(!empty($_POST['page_index']) && is_numeric($_POST['page_index']))
	$existing = $builder->database->query("SELECT * FROM `pages` WHERE `index`=". (int)$_POST['page_index'] ." LIMIT 0,1", Database::RETURN_ROW);
else if(!empty($_POST['page_special_index']) && is_numeric($_POST['page_special_index']))
{
	$special = true;
	$existing = $builder->database->query("SELECT * FROM `pages_special` WHERE `index`=". (int)$_POST['page_special_index'] ." LIMIT 0,1", Database::RETURN_ROW);
}
$is_file = false;
if(isset($_POST['page_file']) || !empty($existing['file']))
	$is_file = true;

$mysql_data = [];
$errors = [];
// index
if($existing['index'])
	$mysql_data['index'] = $existing['index'];
else if($special)
	$errors[] = ['__attr:type'=>"danger", "Invalid special page index."];
// title
if(!empty($_POST['page_title']) || $existing['title'] != "")
{
	if((!empty($_POST['page_title']) && $_POST['page_title'] != $existing['title']) || $_POST['save'] == 2)
		$mysql_data['title'] = $_POST['page_title'];
}
else if(!$is_file)
{
	$errors[] = ['__attr:type'=>"danger", "Page must have a title."];
}
// url
if(!$special)
{
	if($_POST['page_url'] == urlencode($_POST['page_url']) && ($_POST['page_url'] != "" || $existing['url'] != ""))
	{
		if((!empty($_POST['page_url']) && $_POST['page_url'] != $existing['url']) || $_POST['save'] == 2)
			$mysql_data['url'] = $_POST['page_url'];
	}
	else
	{
		if(empty($_POST['page_url']))
			$errors[] = ['__attr:type'=>"danger", "Page must have a URL."];
		else
			$errors[] = ['__attr:type'=>"danger", "Invalid page URL '". $_POST['page_url'] ."'."];
	}
}
// file
if($is_file)
{
	if($_POST['page_file'] != "" || $existing['file'] != "")
	{
		if(!empty($_POST['page_file']) && $_POST['page_file'] != $existing['file'])
		{
			$alreadyDB = $builder->database->query("SELECT `index` FROM pages WHERE `file`=". $builder->database->quote($_POST['page_file']) ." AND `index`!=". (int)$_POST['page_index'], Database::RETURN_FIELD);
			$path = $builder->get_setting('server_path') ."includes". DIRECTORY_SEPARATOR ."pages". DIRECTORY_SEPARATOR;
			$filepath = $path . $_POST['page_file'];
			$alreadyFile = is_file($filepath);
			// TODO: Deal with these possibilities rather than displaying an error and giving up.
			if(!empty($alreadyDB))
			{
				$errors[] = ['__attr:type'=>"danger", "PHP file '". $_POST['page_file'] ."' already belongs to another page."];
			}
			else if($alreadyFile)
			{
				$errors[] = ['__attr:type'=>"danger", "PHP file '". $_POST['page_file'] ."' already exists."];
			}
			else
			{
				$filedir = dirname($filepath);
				if(!is_dir($filedir))
				{
					//umask(7777);
					// Note: This is super annoying, but mkdir() "needs" an octal, but it will convert any non-octal to an octal for you. Unfortunately, it is really bad at identifying when it's given an octal, so rather than even attempting to give it an octal, let's just explicitly give it a decimal, so we know that it will convert.
					mkdir($filedir, octdec(fileperms($builder->get_setting('server_path') ."includes")), true);
				}
				$oldfilepath = $path . $existing['file'];
				if(!empty($existing['file']) && is_file($oldfilepath))
				{
					rename($oldfilepath, $filepath);
				}
				else
				{
					if(!empty($existing['file']) && !is_file($oldfilepath))
						$errors[] = ['__attr:type'=>"warning", "This page referred to '". $existing['file'] ."' but it doesn't exist. Creating new '". $_POST['page_file'] ."' instead of renaming file."];
					$fp = fopen($filepath, "w");
					fwrite($fp, "<?php\n");
					fclose($fp);
				}
				$mysql_data['file'] = $_POST['page_file'];
			}
		}
	}
	else
	{
		if(empty($_POST['page_file']))
			$errors[] = ['__attr:type'=>"danger", "Page must have a PHP file."];
		else
			$errors[] = ['__attr:type'=>"danger", "Invalid PHP file '". $_POST['page_file'] ."'."];
	}
}
// subtheme
if(!empty($_POST['subtheme']) || $existing['subtheme'] != "")
{
	if((!empty($_POST['subtheme']) && $_POST['subtheme'] != $existing['subtheme']) || $_POST['save'] == 2)
		$mysql_data['subtheme'] = $_POST['subtheme'];
}
else
{
	$mysql_data['subtheme'] = "default";
}
// permissions
if(!$special && !empty($_POST['permissions']) && is_array($_POST['permissions']))
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
if(!empty($_POST['page_css']) && is_array($_POST['page_css']))
{
	$stylesheets = [];
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
if(!empty($_POST['page_js']) && is_array($_POST['page_js']))
{
	$javascripts = [];
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
if(!empty($_POST['page_xsl']) && is_array($_POST['page_xsl']))
{
	$templates = [];
	foreach($_POST['page_xsl'] as $xsl)
		if($xsl != "")
			$templates[] = $xsl;
	$templates = json_encode($templates);
	if($templates != $existing['xsl'] || $_POST['save'] == 2)
		$mysql_data['xsl'] = $templates;
}

if(!empty($_POST['content']) && is_array($_POST['content']))
{
	$errors[] = ['__attr:type'=>"warning", "Page contents can only be updated with JavaScript."];
}
if(!empty($_POST['page_content']) && is_array($_POST['page_content']))
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
			// DatabaseView classes are handled specially, so do that separately here.
			if($content['content_class'] == "DatabaseView")
			{
				$object->config['columns'] = [];
				$object->config['format'] = [];
				foreach($content as $key=>$val)
				{
					if($key == "table")
						$object->table = $val;
					else if($key == "limit" && (int)$val > 0)
						$object->config['limit'] = (int)$val;
					else if($key == "sort" && !empty($val))
						$object->config['sort'] = $val;
					else if(substr($key, -7) == "_output" && !empty($val))
						$object->config['columns'][] = ['name'=>substr($key, 0, -7), 'output'=>$val];
					else if(substr($key, -5) == "_comp" && !empty($val))
						$object->config['filters'][] = ['column'=>substr($key,0,-5), 'comparator'=>$val, 'type'=>$content[substr($key,0,-5)."_type"], 'value'=>$content[substr($key,0,-5)."_value"]];
				}
			}
			// Other content classes are handled mostly the same way as one another, using the property types.
			else
			{
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
					else if(isset($content[$k]))
					{
						$errors[] = ['__attr:type'=>"warning", "Invalid property '". $v['type'] ."' in content type '". $content['content_class'] ."' can't be saved."];
					}
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

//print_r($mysql_data);
$_SESSION['onload_notification'] = $errors;
$exit_after = false;
foreach($errors as $err)
{
	if($err['__attr:type'] == "danger")
	{
		$_SESSION['onload_notification']['__attr:title'] = "Page Update Failed";
		$exit_after = true;
	}
}
if($exit_after)
{
	header("Location: page_edit.php?pageId=". (empty($_POST['page_index']) ? ($is_file ? "file" : "new") : $_POST['page_index']));
	exit;
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
	$mysql_data['page'] = $mysql_data['index'];
	unset($mysql_data['index']);
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
//print_r($_SESSION['onload_notification']);
header("Location: pages.php");
