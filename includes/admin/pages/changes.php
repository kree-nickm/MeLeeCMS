<?php
namespace MeLeeCMS;

require_once("nav.php");
$builder->setTitle("Changes - Control Panel");

// Get the current page of changes.
if(!empty($_GET['perPage']) && is_numeric($_GET['perPage']))
	$perpage = (int)$_GET['perPage'];
else
	$perpage = 10;
if(!empty($_GET['p']) && is_numeric($_GET['p']))
	$page = (int)$_GET['p'];
else
	$page = 0;
$changelog = $builder->database->query("SELECT * FROM `changelog` ORDER BY `timestamp` DESC, `index` DESC LIMIT ". $page*$perpage .",". $perpage, Database::RETURN_ALL);

// Build the paginator.
$change_count = $builder->database->query("SELECT COUNT(*) FROM `changelog`", Database::RETURN_FIELD);
$page_count = ceil($change_count/$perpage);
$pages = ['page'=>[]];
$pages['page'][] = ["&larr;", '__attr:number'=>$page-1];
if($page <= 0)
	$pages['page'][0]['__attr:disabled'] = "1";
// TODO: I want this part of the paginator to be built in XSLT, as far limiting the number of buttons on it to prevent overflow. Good luck.
if($page_count < 10)
{
	for($i = 0; $i < $page_count; $i++)
	{
		$pages['page'][] = [$i+1, '__attr:number'=>$i];
		if($page == $i)
			$pages['page'][count($pages['page'])-1]['__attr:current'] = "1";
	}
}
else
{
	if($page == 3 || $page == $page_count-4)
	{
		$range = 2;
		$offrange = 1;
	}
	else if($page == 2 || $page == $page_count-3)
	{
		$range = 2;
		$offrange = 2;
	}
	else if($page == 1 || $page == $page_count-2)
	{
		$range = 3;
		$offrange = 2;
	}
	else if($page == 0 || $page == $page_count-1)
	{
		$range = 3;
		$offrange = 3;
	}
	else
	{
		$range = 2;
		$offrange = 0;
	}
   $dotsbefore = false;
   $dotsafter = false;
	for($i = 0; $i < $page_count; $i++)
	{
		if($i <= $offrange || $i >= $page_count-1-$offrange || ($page-$range <= $i && $page+$range >= $i) || ($i == 1 && $page == 4) || ($i == $page_count-2 && $page == $page_count-5))
		{
			$pages['page'][] = [$i+1, '__attr:number'=>$i];
			if($page == $i)
				$pages['page'][count($pages['page'])-1]['__attr:current'] = "1";
		}
		else if(!$dotsbefore && $i < $page)
		{
			$pages['page'][] = ["...", '__attr:number'=>"", '__attr:disabled'=>"1"];
			$dotsbefore = true;
		}
		else if(!$dotsafter && $i > $page)
		{
			$pages['page'][] = ["...", '__attr:number'=>"", '__attr:disabled'=>"1"];
			$dotsafter = true;
		}
	}
}
$pages['page'][] = ["&rarr;", '__attr:number'=>$page+1];
if($page >= $page_count-1)
	$pages['page'][count($pages['page'])-1]['__attr:disabled'] = "1";

// Build content.
$container = $builder->addContent(new Container("Changes"));
$table = $container->addContent(new Container("Change Log", ['format'=>"table"]));
$table->addContent(new Text("List of recent changes made to rows of the database."), "subtitle");
$row = $table->addContent(new Container("", ['type'=>"header"]));
$row->addContent(new Text("Time", ['raw'=>true]));
$row->addContent(new Text("Table", ['raw'=>true]));
$row->addContent(new Text("Columns Updated", ['raw'=>true]));
$row->addContent(new Text("New", ['raw'=>true]));
$row->addContent(new Text("Blame", ['raw'=>true]));
$row->addContent(new Text("", ['raw'=>true]));
foreach($changelog as $change)
{
	$blame = $change['blame'];
	if(is_numeric($blame))
	{
      $name = $builder->user->getDisplayName($blame);
		if(!empty($name))
			$blame = $name;
	}
   
	$current = [];
	if(is_array($changeData = json_decode($change['data'],true)))
		foreach($changeData as $index=>$data)
			$current[] = array_merge($data, ['__attr:index'=>$index]);
   if(count($current) == 0)
      $new = "row(s) deleted";
   else if(count($current) > 1)
      $new = "multiple rows";
   else
      $new = implode(", ", array_filter(array_keys($current[0]), function($val){ return substr($val,0,7)!="__attr:"; }));
   
	$previous = [];
	if(is_array($changePrev = json_decode($change['previous'],true)))
		foreach($changePrev as $index=>$data)
			$previous[] = array_merge($data, ['__attr:index'=>$index]);
         
	$row = $table->addContent(new Container("", ['index'=>$change['index'],'type'=>"body"]));
	$row->addContent(new Text(date("j M Y @ g:ia T", $change['timestamp']), ['raw'=>true]));
	$row->addContent(new Text($change['table'], ['raw'=>true]));
	$row->addContent(new Text($new, ['raw'=>true]));
	$row->addContent(new Text(count($previous)==0 ? 1 : 0), "cpanel-new");
	$row->addContent(new Text($blame, ['raw'=>true]));
	$change_popup = $row->addContent(new Container("Change", ['format'=>"modal",'button'=>true]), "cpanel-changes");
   $change_popup->addContent(new Text(['new'=>$current, 'previous'=>$previous]), "data");
}
$container->addContent(new Text($pages, ['type'=>"pagination"]));

$builder->attachXSL("cpanel.xsl", "", true);
