<?php
namespace MeLeeCMS;

require_once("nav.php");
$builder->setTitle("Data - Control Panel");

$metadata_cont = $builder->addContent(new Content\Container("Database Metadata"));
foreach($builder->database->metadata as $table=>$coldata)
{
   $table = $metadata_cont->addContent(new Content\Container($table, ['format'=>"table"]));
   $row = $table->addContent(new Content\Container("", ['type'=>"header"]));
   $row->addContent(new Content\Text("Column", ['raw'=>true]));
   $row->addContent(new Content\Text("Default", ['raw'=>true]));
   $row->addContent(new Content\Text("Type", ['raw'=>true]));
   $row->addContent(new Content\Text("Type (Full)", ['raw'=>true]));
   $row->addContent(new Content\Text("Type (Basic)", ['raw'=>true]));
   $row->addContent(new Content\Text("Key", ['raw'=>true]));
   $row->addContent(new Content\Text("Null", ['raw'=>true]));
   $row->addContent(new Content\Text("Extra", ['raw'=>true]));
   foreach($coldata as $col=>$meta)
   {
      if($col !== "index ")
      {
         $row = $table->addContent(new Content\Container("", ['type'=>"body"]));
         $row->addContent(new Content\Text($col, ['raw'=>true]));
         $row->addContent(new Content\Text(empty($meta['default']) ? "" : $meta['default'], ['raw'=>true]));
         $row->addContent(new Content\Text(empty($meta['type']) ? "" : $meta['type'], ['raw'=>true]));
         $row->addContent(new Content\Text(empty($meta['type_full']) ? "" : $meta['type_full'], ['raw'=>true]));
         $row->addContent(new Content\Text(empty($meta['type_basic']) ? "" : $meta['type_basic'], ['raw'=>true]));
         $row->addContent(new Content\Text(empty($meta['key']) ? "" : $meta['key'], ['raw'=>true]));
         $row->addContent(new Content\Text(empty($meta['null']) ? "" : $meta['null'], ['raw'=>true]));
         $row->addContent(new Content\Text(empty($meta['extra']) ? "" : $meta['extra'], ['raw'=>true]));
      }
      else
      {
         $indextable = $metadata_cont->addContent(new Content\Container("", ['format'=>"table"]));
         $row = $indextable->addContent(new Content\Container("", ['type'=>"header"]));
         $row->addContent(new Content\Text("Index", ['raw'=>true]));
         $row->addContent(new Content\Text("Contents", ['raw'=>true]));
         foreach($meta as $index=>$indexdata)
         {
            $row = $indextable->addContent(new Content\Container("", ['type'=>"body"]));
            $row->addContent(new Content\Text($index, ['raw'=>true]));
            $row->addContent(new Content\Text(substr(array_reduce($indexdata,function($carry,$item){
               return $carry ." + `". $item['column'] ."`". (!empty($item['substr'])? "<".$item['substr'] :"");
            }, ""),3), ['raw'=>true]));
         }
      }
   }
}
