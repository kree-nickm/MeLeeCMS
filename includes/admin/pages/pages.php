<?php
namespace MeLeeCMS;

require_once("nav.php");

// First check if we are looking for a specific page, and redirect the script if so.
if(!empty($builder->page->args[0]))
{
  if(!empty($builder->page->args[1]) && $builder->page->args[0] === "special")
    $page = $builder->special_pages[$builder->page->args[1]];
  else
    list($page, $args) = $builder->findPage($builder->page->args);
  if(!empty($page))
  {
    require_once("page.php");
    return;
  }
  else
    $builder->addContent(new Content\Text("Requested page '${$builder->page->args[0]}' does not exist.", ['notification'=>true, 'type'=>"danger", 'title'=>"Invalid Page"]));
}

$builder->setTitle("Pages - Control Panel");

$container = $builder->addContent(new Content\Container("Pages"));

// Page List
$normal_table = $container->addContent(new Content\Container("Normal Pages", ['format'=>"table"]));
$normal_table->addContent(new Content\Text("Pages normally visible to users at the appropriate URL who have proper permissions."), "subtitle");
$row = $normal_table->addContent(new Content\Container("", ['type'=>"header"]));
$row->addContent(new Content\Text("Title", ['raw'=>true]));
$row->addContent(new Content\Text("URL", ['raw'=>true]));
$row->addContent(new Content\Text("Linked Files", ['raw'=>true]));
$row->addContent(new Content\Text("Permission", ['raw'=>true]));
$row->addContent(new Content\Text("", ['raw'=>true]));
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
    'view' => (new Content\Link($page->url))->set_cms($builder),
    'edit' => (new Content\Link($builder->getSetting('cpanel_dir')."/pages/".$page->url))->set_cms($builder),
    'reset' => ($page->db_stored && $page->hardcoded) ? true : null,
    'delete' => ($page->db_stored && !$page->hardcoded) ? true : null,
  ];
  
  $row = $normal_table->addContent(new Content\Container("", ['type'=>"body"]));
  $row->addContent(new Content\Text($page->title, ['raw'=>true]));
  $row->addContent(new Content\Text($page->url, ['raw'=>true]));
  $row->addContent(new Content\Text($linked_files), "cpanel-linked-files");
  $row->addContent(new Content\Text(implode(", ", $page->getPermissions()), ['raw'=>true]));
  $row->addContent(new Content\Text($buttons), "cpanel-page-buttons");
}

// Special Page List
$special_table = $container->addContent(new Content\Container("Special Pages", ['format'=>"table"]));
$special_table->addContent(new Content\Text("Special pages that only appear in certain conditions, generally for error pages."), "subtitle");
$row = $special_table->addContent(new Content\Container("", ['type'=>"header"]));
$row->addContent(new Content\Text("ID", ['raw'=>true]));
$row->addContent(new Content\Text("Linked Files", ['raw'=>true]));
$row->addContent(new Content\Text(""));
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
    'view' => (new Content\Link("?specialPage=".$page->id))->set_cms($builder),
    'edit' => (new Content\Link($builder->getSetting('cpanel_dir')."/pages/special/".$page->id))->set_cms($builder),
    'reset' => ($page->db_stored && $page->hardcoded) ? true : null,
    'delete' => ($page->db_stored && !$page->hardcoded) ? true : null,
  ];
  
  $row = $special_table->addContent(new Content\Container("", ['type'=>"body"]));
  $row->addContent(new Content\Text($page->id, ['raw'=>true]));
  $row->addContent(new Content\Text($linked_files), "cpanel-linked-files");
  $row->addContent(new Content\Text($buttons), "cpanel-page-buttons");
}
