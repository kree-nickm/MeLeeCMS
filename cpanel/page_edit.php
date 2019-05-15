<?php
require_once("load_page.php");

if(isset($_GET['confirmdelete']) && !empty($_GET['pageId']) && is_numeric($_GET['pageId']))
{
	if($builder->database->delete("pages", ['index'=>(int)$_GET['pageId']], true))
		$_SESSION['onload_notification'] = [['__attr:type'=>"primary", "Page was deleted successfully."], '__attr:title'=>"Page Deleted"];
	else
		$_SESSION['onload_notification'] = [['__attr:type'=>"warning", "No page matching the given index was found."], '__attr:title'=>"Page Not Deleted"];
	header("Location: pages.php");
	exit;
}

if(!empty($_GET['pageId']) && is_numeric($_GET['pageId']))
	$page = $builder->database->query("SELECT * FROM `pages` WHERE `index`=". (int)$_GET['pageId'] ." LIMIT 0,1", 2);
else if(!empty($_GET['specialPageId']) && is_numeric($_GET['specialPageId']))
{
	$special = true;
	$page = $builder->database->query("SELECT * FROM `pages_special` WHERE `index`=". (int)$_GET['specialPageId'] ." LIMIT 0,1", 2);
}

if(!empty($page) && is_array($page) || !empty($_GET['pageId']) && $_GET['pageId'] == "new")
{
	$builder->add_content(new Text(['component'=>$builder->database->query("SELECT `index`,`title` FROM `page_components` ORDER BY `title`", Database::RETURN_ALL)], ['hidden'=>"1"]), "component-list");
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
	$builder->add_content(new Text(['table'=>$dbtables], ['hidden'=>"1"]), "dbtable-list");
	$builder->add_content(new Text(['class'=>Content::get_subclasses($builder)], ['hidden'=>"1"]), "content-classes");
	$form = $builder->add_content(new Container("", []), "page_edit");
	$data = [
		'title' => empty($page['title']) ? "" : $page['title'],
		'site_title' => $builder->get_setting('site_title'),
		'select@id=subtheme' => ['value'=>empty($page['subtheme'])?"":$page['subtheme'], 'option'=>[]],
		'select@id=page_css@multiple' => ['value'=>[],'option'=>[]],
		'select@id=page_js@multiple' => ['value'=>[],'option'=>[]],
		'select@id=page_xsl@multiple' => ['value'=>[],'option'=>[]],
	];
	foreach($builder->themes[$builder->get_theme()]['subtheme'] as $subtheme)
		$data['select@id=subtheme']['option'][] = $subtheme['__attr:name'];
	if(empty($special))
	{
		if(!empty($page['index']))
			$data['index'] = $page['index'];
		$data['url'] = empty($page['url']) ? "" : $page['url'];
		$data['url_path'] = $builder->get_setting('url_path');
		$data['select@id=permissions@multiple'] = ['value'=>[],'option'=>[]];
	}
	else
	{
		$data['sindex'] = $page['index'];
	}
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
	$page_css = json_decode(empty($page['css'])?"":$page['css'], true);
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
	$page_js = json_decode(empty($page['js'])?"":$page['js'], true);
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
	$page_xsl = json_decode(empty($page['xsl'])?"":$page['xsl'], true);
	if(is_array($page_xsl)) foreach($page_xsl as $xsl)
	{
		$data['select@id=page_xsl@multiple']['value'][] = $xsl;
	}
	
	if(empty($special))
	{
		foreach(User::get_permissions($builder) as $num=>$perm)
		{
			$data['select@id=permissions@multiple']['option'][] = [$perm, "__attr:value"=>$num];
			if(($num & (empty($page['permission'])?0:$page['permission'])) == $num)
				$data['select@id=permissions@multiple']['value'][] = $num;
		}
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
				$content_data['random'] = hash("crc32", microtime().$x);
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
	$page_contents = $form->add_content(new Text( empty($page['content']) ? "" : getContentData(unserialize($page['content'])) ), "page_content");
	//$page_contents->add_content(getContentData(unserialize($page['content'])));

	$builder->attach_xsl("cpanel-page-edit.xsl", "", true);
	$builder->attach_xsl("cpanel-content.xsl", "", true);
	$builder->attach_js("cpanel-page-content.js", "", true);
	$builder->attach_css("cpanel.css", "", true);
	$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");
}
else
{
	header("Location: pages.php");
	exit;
}