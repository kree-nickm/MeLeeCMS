<?php
require_once("../includes/MeLeeCMS.php");

if($_REQUEST['ContentClass'] != "")
{
	$builder = new MeLeeCMS(1);
	$classes = Content::get_subclasses($builder);
	if(in_array($_REQUEST['ContentClass'], $classes))
	{
		$object = new $_REQUEST['ContentClass']();
		$content_data = [
			'__attr:page_content' => "1",
			'class' => get_class($object),
			'property' => [],
			'random' => hash("crc32", microtime()),
		];
		foreach($object->get_properties() as $prop=>$parr)
		{
			$content_data['property'][] = ['value'=>$object->$prop, 'name'=>$prop, 'desc'=>$parr['desc'], '__attr:type'=>$parr['type']];
		}
		$class_text = new Text($content_data);
		$classlist_text = new Text([
			'__attr:id' => "content-classes",
			'class' => $classes,
		]);
		$componentlist_text = new Text([
			'__attr:id' => "component-list",
			'component' => $builder->database->query("SELECT `index`,`title` FROM `page_components` WHERE `index`!=". (int)$component['index'] ." ORDER BY `title`", Database::RETURN_ALL),
		]);
		$dbtables = [];
		foreach($builder->database->metadata as $table=>$cols)
		{
			if(substr($table, 0, 7) == "custom_" || true)
			{
				$temp = [];
				foreach($cols as $col=>$meta)
				{
					if($col != "index ")
					{
						$temp[] = ['name'=>$col, 'type'=>$meta['type']];
					}
				}
				$dbtables[] = ['name'=>$table, 'column'=>$temp];
			}
		}
		$dbtablelist_text = new Text([
			'__attr:id' => "dbtable-list",
			'table' => $dbtables,
		]);
		
		$xmlRaw = '<?xml version="1.0"?>'. Transformer::array_to_xml("MeLeeCMS", ['content'=>[$class_text->build_params(), $classlist_text->build_params(), $componentlist_text->build_params(), $dbtablelist_text->build_params()]]);
	}
	else
		$xmlRaw = '<?xml version="1.0"?><MeLeeCMS><error>Invalid Content subclass.</error></MeLeeCMS>';
	
	// The theme folder used for the include below should be the same as defined in load_page.php
	$xslRaw = '<?xml version="1.0"?><xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"><xsl:output method="html"/><xsl:template match="/MeLeeCMS"><xsl:apply-templates select="content[@page_content]" mode="page-content"><xsl:with-param name="id" select="content[@page_content]/random"/>'. ($_REQUEST['idPrefix'] ? '<xsl:with-param name="id_prefix">'. $_REQUEST['idPrefix'] .'</xsl:with-param>' : '') . ($_REQUEST['namePrefix'] ? '<xsl:with-param name="name_prefix">'. $_REQUEST['namePrefix'] .'</xsl:with-param>' : '') . '</xsl:apply-templates></xsl:template><xsl:include href="'. $builder->get_setting('server_path') .'themes/'. $GlobalConfig['default_theme'] .'/templates/cpanel-content.xsl"/></xsl:stylesheet>';
	
	header("Content-type: text/html");
	$xml = new DOMDocument();
	$xml->loadXML($xmlRaw);
	$xsl = new DOMDocument();
	$xsl->loadXML($xslRaw);
	$proc = new XSLTProcessor();
	$proc->importStyleSheet($xsl);
	$result = $proc->transformToDoc($xml);
	echo(html_entity_decode($result->saveHTML(), ENT_QUOTES|ENT_HTML5, "UTF-8"));
	exit;
}

if($_REQUEST['theme'] != "" && $_REQUEST['file'] != "")
{
	header("Content-type: text/plain");
	$builder = new MeLeeCMS(7);
	$theme = $builder->themes[$_REQUEST['theme']];
	if(is_array($theme))
	{
		if(substr($_REQUEST['file'], -4) == ".css")
			$file = $builder->get_setting('server_path') ."themes/". $_REQUEST['theme'] ."/css/". $_REQUEST['file'];
		else if(substr($_REQUEST['file'], -3) == ".js")
			$file = $builder->get_setting('server_path') ."themes/". $_REQUEST['theme'] ."/js/". $_REQUEST['file'];
		else if(substr($_REQUEST['file'], -4) == ".xsl")
			$file = $builder->get_setting('server_path') ."themes/". $_REQUEST['theme'] ."/templates/". $_REQUEST['file'];
	}
	if(is_file($file))
	{
		echo(file_get_contents($file));
	}
	exit;
}

if($_REQUEST['XSL'] != "" && $_REQUEST['XML'] != "")
{
	$xml = new DOMDocument();
	$xml->loadXML($_REQUEST['XML']);
	$xsl = new DOMDocument();
	$xsl->loadXML($_REQUEST['XSL']);
	$proc = new XSLTProcessor();
	$proc->importStyleSheet($xsl);
	$result = $proc->transformToDoc($xml);
	echo($result);
	exit;
}