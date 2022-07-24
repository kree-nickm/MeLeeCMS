<?php
namespace MeLeeCMS;

require_once("load_page.php");

/*if(!empty($_GET['perPage']) && is_numeric($_GET['perPage']))
	$perpage = (int)$_GET['perPage'];
else
	$perpage = 10;
if(!empty($_GET['p']) && is_numeric($_GET['p']))
	$page = (int)$_GET['p'];
else
	$page = 0;

$table_count = count($builder->database->metadata);
$page_count = ceil($table_count/$perpage);
$pages = ['page'=>[]];
$pages['page'][] = ["&larr;", '__attr:number'=>$page-1];
if($page == 0)
	$pages['page'][0]['__attr:disabled'] = "1";
for($i = 0; $i < $page_count; $i++)
{
	$pages['page'][] = [$i+1, '__attr:number'=>$i];
	if($page == $i)
		$pages['page'][count($pages['page'])-1]['__attr:current'] = "1";
}
$pages['page'][] = ["&rarr;", '__attr:number'=>$page+1];
if($page == $page_count-1)
	$pages['page'][count($pages['page'])-1]['__attr:disabled'] = "1";

$data = $builder->add_content(new Container("", []), "table_list");
$data->add_content(new Text($pages), "pages");
foreach($builder->database->metadata as $table=>$meta)
{
	$row = $data->add_content(new Container());
	$row->add_content(new Text($table), "table");
	$row->add_content(new Text(count($meta)-1), "cols");
	$row->add_content(new Text($builder->database->query("SELECT COUNT(*) FROM ". $table, Database::RETURN_FIELD)), "rows");
}

$builder->attach_xsl("cpanel-data-list.xsl", "", true);*/
$data = $builder->add_content(new Container("Database Metadata"));
$data->add_content(new Text("<pre>". print_r($builder->database->metadata, true) ."</pre>"));

$builder->attach_css("cpanel.css", "", true);
$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");