<?php
namespace MeLeeCMS;

require_once("nav.php");
$builder->setTitle("Pages - Control Panel");

$container = $builder->addContent(new Container("Pages"));

// Page List
$normal_table = $container->addContent(new Container("Normal Pages", ['subtheme'=>"table"]));
$normal_table->addContent(new Text("Pages normally visible to users at the appropriate URL who have proper permissions."), "subtitle");
$row = $normal_table->addContent(new Container("", ['type'=>"header"]));
$row->addContent(new Text("Title", ['raw'=>true]));
$row->addContent(new Text("URL", ['raw'=>true]));
$row->addContent(new Text("Subtheme", ['raw'=>true]));
$row->addContent(new Text("Linked Files", ['raw'=>true]));
$row->addContent(new Text("Permission", ['raw'=>true]));
foreach($builder->pages as $page)
{
   if($page->is_cpanel)
      continue;
   
   $linked_files = [];
   if(!empty($page->file))
		$linked_files[] = $page->file;
	foreach($page->css as $css)
      $linked_files[] = $css['href'];
	foreach($page->js as $js)
      $linked_files[] = $js['src'];
	foreach($page->xsl as $xsl)
      $linked_files[] = $xsl['href'];
   
   $row = $normal_table->addContent(new Container("", ['type'=>"body"]));
   $row->addContent(new Text($page->title, ['raw'=>true]));
   $row->addContent(new Text($page->url, ['raw'=>true]));
   $row->addContent(new Text(!empty($page->subtheme) ? $page->subtheme : "default", ['raw'=>true]));
   $row->addContent(new Text($linked_files, ['raw'=>true]));
   $row->addContent(new Text(implode(", ", $page->getPermission()), ['raw'=>true]));
}

// Special Page List
$special_table = $container->addContent(new Container("Special Pages", ['subtheme'=>"table"]));
$special_table->addContent(new Text("Special pages that only appear in certain conditions, generally for error pages."), "subtitle");
$row = $special_table->addContent(new Container("", ['type'=>"header"]));
$row->addContent(new Text("ID", ['raw'=>true]));
$row->addContent(new Text("Subtheme", ['raw'=>true]));
$row->addContent(new Text("Linked Files", ['raw'=>true]));
foreach($builder->special_pages as $page)
{
   $linked_files = [];
   if(!empty($page->file))
		$linked_files[] = $page->file;
	foreach($page->css as $css)
      $linked_files[] = $css['href'];
	foreach($page->js as $js)
      $linked_files[] = $js['src'];
	foreach($page->xsl as $xsl)
      $linked_files[] = $xsl['href'];
   
   $row = $special_table->addContent(new Container("", ['type'=>"body"]));
   $row->addContent(new Text($page->id, ['raw'=>true]));
   $row->addContent(new Text(!empty($page->subtheme) ? $page->subtheme : "default", ['raw'=>true]));
   $row->addContent(new Text($linked_files, ['raw'=>true]));
}
