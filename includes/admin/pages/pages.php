<?php
namespace MeLeeCMS;

require_once("nav.php");
$builder->setTitle("Pages - Control Panel");

$container = $builder->addContent(new Container("Pages"));

// Page List
$normal_table = $container->addContent(new Container("Normal Pages", ['format'=>"table"]));
$normal_table->addContent(new Text("Pages normally visible to users at the appropriate URL who have proper permissions."), "subtitle");
$row = $normal_table->addContent(new Container("", ['type'=>"header"]));
$row->addContent(new Text("Title", ['raw'=>true]));
$row->addContent(new Text("URL", ['raw'=>true]));
$row->addContent(new Text("Linked Files", ['raw'=>true]));
$row->addContent(new Text("Permission", ['raw'=>true]));
$row->addContent(new Text("", ['raw'=>true]));
foreach($builder->pages as $page)
{
   if($page->is_cpanel)
      continue;
   
   $linked_files = [];
   if(!empty($page->file))
		$linked_files[] = basename($page->file);
	foreach($page->css as $css)
      $linked_files[] = $css['href'];
	foreach($page->js as $js)
      $linked_files[] = $js['src'];
	foreach($page->xsl as $xsl)
      $linked_files[] = $xsl['href'];
      
   $buttons = [
      'view' => (new Link($page->url))->set_cms($builder),
      'edit' => (new Link($builder->getSetting('cpanel_dir')."/pages/".$page->url))->set_cms($builder),
      'reset' => ($page->db_stored && $page->hardcoded) ? true : null,
      'delete' => ($page->db_stored && !$page->hardcoded) ? true : null,
   ];
   
   $row = $normal_table->addContent(new Container("", ['type'=>"body"]));
   $row->addContent(new Text($page->title, ['raw'=>true]));
   $row->addContent(new Text($page->url, ['raw'=>true]));
   $row->addContent(new Text($linked_files), "cpanel-linked-files");
   $row->addContent(new Text(implode(", ", $page->getPermissions()), ['raw'=>true]));
   $row->addContent(new Text($buttons), "cpanel-page-buttons");
}

// Special Page List
$special_table = $container->addContent(new Container("Special Pages", ['format'=>"table"]));
$special_table->addContent(new Text("Special pages that only appear in certain conditions, generally for error pages."), "subtitle");
$row = $special_table->addContent(new Container("", ['type'=>"header"]));
$row->addContent(new Text("ID", ['raw'=>true]));
$row->addContent(new Text("Linked Files", ['raw'=>true]));
$row->addContent(new Text(""));
foreach($builder->special_pages as $page)
{
   $linked_files = [];
   if(!empty($page->file))
		$linked_files[] = basename($page->file);
	foreach($page->css as $css)
      $linked_files[] = $css['href'];
	foreach($page->js as $js)
      $linked_files[] = $js['src'];
	foreach($page->xsl as $xsl)
      $linked_files[] = $xsl['href'];
      
   $buttons = [
      'view' => (new Link("?specialPage=".$page->id))->set_cms($builder),
      'edit' => (new Link($builder->getSetting('cpanel_dir')."/pages/special/".$page->id))->set_cms($builder),
      'reset' => ($page->db_stored && $page->hardcoded) ? true : null,
      'delete' => ($page->db_stored && !$page->hardcoded) ? true : null,
   ];
   
   $row = $special_table->addContent(new Container("", ['type'=>"body"]));
   $row->addContent(new Text($page->id, ['raw'=>true]));
   $row->addContent(new Text($linked_files), "cpanel-linked-files");
   $row->addContent(new Text($buttons), "cpanel-page-buttons");
}
