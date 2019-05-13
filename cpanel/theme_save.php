<?php
global $mode;
$mode = "templates";
require_once("../includes/MellinBuilder.php");

// Check if a valid template has been chosen
if(in_array($_GET['template'], $builder->templates))
{
	$tempName = $builder->templates[array_search($_GET['template'], $builder->templates)];
	$tempData = db_query("SELECT * FROM `templates` WHERE `name`='". db_secure_string($tempName) ."' LIMIT 0,1");
	if(is_array($tempData))
		$tempData = current($tempData);
}

if($tempName != "")
{
	$classes = get_TextContent_subclasses();
	$template_data = get_template_data($tempName, 1);
	$xsl_save = array();
	if($_POST['xsl_builder'] != "")
	{
		$xsl_save['MellinBuilder-render.xsl'] = str_replace("\r", "", stripslashes($_POST['xsl_builder']));
		if($xsl_save['MellinBuilder-render.xsl'] == $template_data['xsl']['MellinBuilder-render.xsl'])
			unset($xsl_save['MellinBuilder-render.xsl']);
	}
	if($_POST['xsl_default'] != "")
	{
		$xsl_save['default.xsl'] = str_replace("\r", "", stripslashes($_POST['xsl_default']));
		if($xsl_save['default.xsl'] == $template_data['xsl']['default.xsl'])
			unset($xsl_save['default.xsl']);
	}
	foreach($classes as $class)
	{
		if($_POST['xsl_'. $class] != "")
		{
			$file = $class .'-'. 'output' .'.xsl';
			$xsl_save[$file] = str_replace("\r", "", stripslashes($_POST['xsl_'. $class]));
			if($xsl_save[$file] == $template_data['xsl'][$file])
				unset($xsl_save[$file]);
		}
	}
			
	$css_save = array();
	
	// TODO: Remove from the _save arrays each file that has the same contents as the corresponding file on disk
	// That way, if the files on disk are updated, the database won't be trying to retain the older versions
	
	if(is_array($tempData))
	{
		$err = db_query("UPDATE `templates` SET ".
			"`doctype`='". db_secure_string(stripslashes($_POST['doctype'])) ."',".
			"`xsl`='". db_secure_string(serialize($xsl_save)) ."',".
			"`css`='". db_secure_string(serialize($css_save)) ."'".
		" WHERE `name`='". db_secure_string($tempName) ."' LIMIT 1");
	}
	else
	{
		$err = db_query("INSERT INTO `templates` (`name`, `doctype`, `xsl`, `css`) VALUES (".
			"'". db_secure_string($tempName) ."',".
			"'". db_secure_string(stripslashes($_POST['doctype'])) ."',".
			"'". db_secure_string(serialize($xsl_save)) ."',".
			"'". db_secure_string(serialize($css_save)) ."'".
		")");
	}
}
else
{
	header("Location: templates.php?error=". urlencode("Invalid template."));
	exit;
}

if($err == "")
	header("Location: template_edit.php?save&template=". $tempName);
else
	header("Location: templates.php?error=". urlencode($err));