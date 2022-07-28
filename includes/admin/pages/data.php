<?php
namespace MeLeeCMS;

require_once("nav.php");
$builder->setTitle("Data - Control Panel");

$metadata_cont = $builder->addContent(new Container("Database Metadata"));
foreach($builder->database->metadata as $table=>$coldata)
{
   $table = $metadata_cont->addContent(new Container($table, ['format'=>"table"]));
   $row = $table->addContent(new Container("", ['type'=>"header"]));
   $row->addContent(new Text("Column", ['raw'=>true]));
   $row->addContent(new Text("Default", ['raw'=>true]));
   $row->addContent(new Text("Type", ['raw'=>true]));
   $row->addContent(new Text("Type (Full)", ['raw'=>true]));
   $row->addContent(new Text("Type (Basic)", ['raw'=>true]));
   $row->addContent(new Text("Key", ['raw'=>true]));
   $row->addContent(new Text("Null", ['raw'=>true]));
   $row->addContent(new Text("Extra", ['raw'=>true]));
   foreach($coldata as $col=>$meta)
   {
      if($col !== "index ")
      {
         $row = $table->addContent(new Container("", ['type'=>"body"]));
         $row->addContent(new Text($col, ['raw'=>true]));
         $row->addContent(new Text(empty($meta['default']) ? "" : $meta['default'], ['raw'=>true]));
         $row->addContent(new Text(empty($meta['type']) ? "" : $meta['type'], ['raw'=>true]));
         $row->addContent(new Text(empty($meta['type_full']) ? "" : $meta['type_full'], ['raw'=>true]));
         $row->addContent(new Text(empty($meta['type_basic']) ? "" : $meta['type_basic'], ['raw'=>true]));
         $row->addContent(new Text(empty($meta['key']) ? "" : $meta['key'], ['raw'=>true]));
         $row->addContent(new Text(empty($meta['null']) ? "" : $meta['null'], ['raw'=>true]));
         $row->addContent(new Text(empty($meta['extra']) ? "" : $meta['extra'], ['raw'=>true]));
      }
      else
      {
         $indextable = $metadata_cont->addContent(new Container("", ['format'=>"table"]));
         $row = $indextable->addContent(new Container("", ['type'=>"header"]));
         $row->addContent(new Text("Index", ['raw'=>true]));
         $row->addContent(new Text("Contents", ['raw'=>true]));
         foreach($meta as $index=>$indexdata)
         {
            $row = $indextable->addContent(new Container("", ['type'=>"body"]));
            $row->addContent(new Text($index, ['raw'=>true]));
            $row->addContent(new Text(substr(array_reduce($indexdata,function($carry,$item){
               return $carry ." + `". $item['column'] ."`". (!empty($item['substr'])? "<".$item['substr'] :"");
            }, ""),3), ['raw'=>true]));
         }
      }
   }
}
