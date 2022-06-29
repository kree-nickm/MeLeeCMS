<?php
require_once("load_page.php");

if(!empty($_GET['perPage']) && is_numeric($_GET['perPage']))
	$perpage = (int)$_GET['perPage'];
else
	$perpage = 10;
if(!empty($_GET['p']) && is_numeric($_GET['p']))
	$page = (int)$_GET['p'];
else
	$page = 0;

$change_count = $builder->database->query("SELECT COUNT(*) FROM `changelog`", Database::RETURN_FIELD);
$page_count = ceil($change_count/$perpage);
$pages = ['page'=>[]];
$pages['page'][] = ["&larr;", '__attr:number'=>$page-1];
if($page <= 0)
	$pages['page'][0]['__attr:disabled'] = "1";
for($i = 0; $i < $page_count; $i++)
{
	$pages['page'][] = [$i+1, '__attr:number'=>$i];
	if($page == $i)
		$pages['page'][count($pages['page'])-1]['__attr:current'] = "1";
}
$pages['page'][] = ["&rarr;", '__attr:number'=>$page+1];
if($page >= $page_count-1)
	$pages['page'][count($pages['page'])-1]['__attr:disabled'] = "1";

$changelog = $builder->database->query("SELECT * FROM `changelog` ORDER BY `timestamp` DESC, `index` DESC LIMIT ". $page*$perpage .",". $perpage, Database::RETURN_ALL);
$table = $builder->add_content(new Container("", []), "change_log");
$table->add_content(new Text($pages), "pages");
foreach($changelog as $change)
{
	$blame = $change['blame'];
	if(is_numeric($blame))
	{
		$user = $builder->database->query("SELECT `index`,`username` FROM `users` WHERE `index`=". (int)$blame, Database::RETURN_ROW);
		if($user['index'])
			$blame = $user['username'];
	}
	$row = $table->add_content(new Container());
	$row->add_content(new Text(date("j M Y @ g:ia T", $change['timestamp'])), "timestamp");
	$row->add_content(new Text($change['table']), "table");
	$row->add_content(new Text($blame), "blame");
	$current = [];
	if(is_array($changeData = json_decode($change['data'],true)))
		foreach($changeData as $index=>$data)
			$current[] = array_merge($data, ['__attr:index'=>$index]);
	$previous = [];
	if(is_array($changePrev = json_decode($change['previous'],true)))
		foreach($changePrev as $index=>$data)
			$previous[] = array_merge($data, ['__attr:index'=>$index]);
	$row->add_content(new Text(['row'=>$current]), "data");
	$row->add_content(new Text(['row'=>$previous]), "previous");
}

$builder->attach_js("https://kree-nickm.github.io/element-list-controller/elc.js", "", false);
$builder->attach_xsl("cpanel-change-log.xsl", "", true);
$builder->attach_css("cpanel.css", "", true);
$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");