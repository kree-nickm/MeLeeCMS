<?php
namespace MeLeeCMS;

require_once("nav.php");
$builder->setTitle("Themes - Control Panel");

$page = $builder->addContent(new Content\Container("Themes", ['full-width'=>true]));
foreach($builder->themes as $dir=>$theme)
{
  if($dir != "default")
    $page->addContent(new Content\Text("theme", ['type'=>"cpanel-theme-card",'current'=>$builder->getTheme()->name==$theme->name?true:null]));
}

$builder->attachXSL("cpanel.xsl", "", true);
