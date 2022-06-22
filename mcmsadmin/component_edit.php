<?php
require_once("load_page.php");

if(isset($_GET['confirmdelete']) && !empty($_GET['compId']) && is_numeric($_GET['compId']))
{
	if($builder->database->delete("page_components", ['index'=>(int)$_GET['compId']], true))
		$_SESSION['onload_notification'] = [['__attr:type'=>"primary", "Component was deleted successfully."], '__attr:title'=>"Component Deleted"];
	else
		$_SESSION['onload_notification'] = [['__attr:type'=>"warning", "No component matching the given index was found."], '__attr:title'=>"Component Not Deleted"];
	header("Location: components.php");
	exit;
}

if(!empty($_GET['compId']) && is_numeric($_GET['compId']))
	$component = $builder->database->query("SELECT * FROM `page_components` WHERE `index`=". (int)$_GET['compId'] ." LIMIT 0,1", 2);

if(!empty($component) && is_array($component) || !empty($_GET['compId']) && $_GET['compId'] == "new")
{
	$builder->add_content(new Text(['component'=>empty($component['index']) ? [] : $builder->database->query("SELECT `index`,`title` FROM `page_components` WHERE `index`!=". (int)$component['index'] ." ORDER BY `title`", Database::RETURN_ALL)], ['hidden'=>"1"]), "component-list");
	$builder->add_content(new Text(['class'=>Content::get_subclasses($builder)], ['hidden'=>"1"]), "content-classes");
	$form = $builder->add_content(new Container("", []), "component_edit");
	$data = [
		'index' => empty($component['index'])?"":$component['index'],
		'title' => empty($component['title'])?"":$component['title'],
		'select@id=page_css@multiple' => ['value'=>[],'option'=>[]],
		'select@id=page_js@multiple' => ['value'=>[],'option'=>[]],
		'select@id=page_xsl@multiple' => ['value'=>[],'option'=>[]],
	];
	$theme = $builder->get_theme();
	
	$path = $builder->get_setting('server_path') ."themes/". $theme ."/css/";
	if(is_dir($path))
	{
		$dir = dir($path);
		while(false !== ($entry = $dir->read()))
			if($entry{0} != ".")
			{
				$data['select@id=page_css@multiple']['option'][] = [$entry];
			}
	}
	$page_css = empty($component['css'])?"":json_decode($component['css'], true);
	if(is_array($page_css)) foreach($page_css as $css)
	{
		$data['select@id=page_css@multiple']['value'][] = $css['file'];
	}
	
	$path = $builder->get_setting('server_path') ."themes/". $theme ."/js/";
	if(is_dir($path))
	{
		$dir = dir($path);
		while(false !== ($entry = $dir->read()))
			if($entry{0} != ".")
			{
				$data['select@id=page_js@multiple']['option'][] = [$entry];
			}
	}
	$page_js = empty($component['js'])?"":json_decode($component['js'], true);
	if(is_array($page_js)) foreach($page_js as $js)
	{
		$data['select@id=page_js@multiple']['value'][] = $js['file'];
	}
	
	$path = $builder->get_setting('server_path') ."themes/". $theme ."/templates/";
	if(is_dir($path))
	{
		$dir = dir($path);
		while(false !== ($entry = $dir->read()))
			if($entry{0} != "." && substr($entry, 0, 9) != "MeLeeCMS-")
			{
				$data['select@id=page_xsl@multiple']['option'][] = [$entry];
			}
	}
	$page_xsl = empty($component['xsl'])?"":json_decode($component['xsl'], true);
	if(is_array($page_xsl)) foreach($page_xsl as $xsl)
	{
		$data['select@id=page_xsl@multiple']['value'][] = $xsl;
	}
	$form->add_content(new Text($data, []), "props");
	
	function getContentData($contents)
	{
		$result = [];
		if(is_array($contents)) foreach($contents as $x=>$object)
		{
			$content_data = [
				'__attr:page_content' => "1",
				'class' => get_class($object),
				'property' => [],
			];
			if(substr($x, 0, 2) == "__" && is_numeric(substr($x, 2)))
				$content_data['id'] = hash("crc32", microtime().$x);
			else
				$content_data['id'] = $x;
			foreach($object->get_properties() as $prop=>$parr)
			{
				$temp = ['name'=>$prop, 'desc'=>$parr['desc'], '__attr:type'=>$parr['type']];
				if($parr['type'] == "dictionary")
				{
					$temp['value'] = [];
					if(is_array($object->$prop)) foreach($object->$prop as $k=>$v)
						$temp['value'][] = ['__attr:key'=>$k, $v];
				}
				else if($parr['type'] == "container")
				{
					$temp = array_merge($temp, getContentData($object->$prop));
				}
				else
					$temp['value'] = $object->$prop;
				$content_data['property'][] = $temp;
			}
			$result[] = $content_data;
			
		}
		return ['content'=>$result];
	}
	$page_contents = $form->add_content(new Text(empty($component['content'])?"":getContentData(unserialize($component['content']))), "page_content");

	$builder->attach_xsl("cpanel-component-edit.xsl", "", true);
	$builder->attach_xsl("cpanel-content.xsl", "", true);
	$builder->attach_js("cpanel-page-content.js", "", true);
	$builder->attach_css("cpanel.css", "", true);
	$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");
}
else
{
	header("Location: components.php");
	exit;
}