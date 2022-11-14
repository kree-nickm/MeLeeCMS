<?php
namespace MeLeeCMS;

require_once("nav.php");
$builder->setTitle("Errors - Control Panel");

// Get the current page of changes.
if(!empty($_GET['perPage']) && is_numeric($_GET['perPage']))
	$perpage = (int)$_GET['perPage'];
else
	$perpage = 10;
if(!empty($_GET['p']) && is_numeric($_GET['p']))
	$page = (int)$_GET['p'];
else
	$page = 0;
$errorlog = $builder->database->query("SELECT * FROM `error_log` ORDER BY `time` DESC, `index` DESC LIMIT ". $page*$perpage .",". $perpage, Database::RETURN_ALL);

// Build the paginator.
$error_count = $builder->database->query("SELECT COUNT(*) FROM `error_log`", Database::RETURN_FIELD);
$page_count = ceil($error_count/$perpage);
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
$container = $builder->addContent(new Container("Errors"));
$table = $container->addContent(new Container("Error Log", ['format'=>"table"]));
$table->addContent(new Text("List of recent errors that occurred during page load for any user."), "subtitle");
$row = $table->addContent(new Container("", ['type'=>"header"]));
$row->addContent(new Text("Time", ['raw'=>true]));
$row->addContent(new Text("Type", ['raw'=>true]));
$row->addContent(new Text("Message", ['raw'=>true]));
$row->addContent(new Text("File", ['raw'=>true]));
foreach($errorlog as $error)
{
	$blame = $error['user'];
	if(is_numeric($blame))
	{
      $name = $builder->user->getDisplayName($blame);
		if(!empty($name))
			$blame = $name;
	}
   
   if(substr($error['file'], 0, strlen($builder->getSetting('server_path'))) == $builder->getSetting('server_path'))
      $location = substr($error['file'], strlen($builder->getSetting('server_path')));
   else
      $location = $error['file'];
   $location .= " line ". $error['line'];
         
	$row = $table->addContent(new Container("", ['index'=>$error['index'],'type'=>"body"]));
	$row->addContent(new Text(date("j M Y @ g:ia T", $error['time']), ['raw'=>true]));
	$row->addContent(new Text($error['type'], ['raw'=>true]));
	$row->addContent(new Text($error['message'], ['raw'=>true]));
	$row->addContent(new Text($location, ['raw'=>true]));
}
$container->addContent(new Text($pages, ['type'=>"pagination"]));

$builder->attachXSL("cpanel.xsl", "", true);
