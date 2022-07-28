<?php
namespace MeLeeCMS;

require_once("MeLeeCMS.php");
$builder = new MeLeeCMS(MeLeeCMS::MODE_PAGE);

// PHPVersion: Throwable requires PHP >= 7.0.0, but this check should provide a fallback for older versions. Same as below.
// TODO: Probably move the Throwable checks and exception handling to Theme->init()
if(class_exists("\\Throwable"))
{
   try
   {
      $builder->getTheme()->init();
   }
   catch(\Throwable $ex)
   {
      http_response_code(500);
      $builder->setTitle("Error");
      $info = $builder->addContent(new Container("500 - Internal Server Error"));
      $info->addContent(new Text("The server encountered an unrecoverable error while attempting to initialize the theme. If this problem persists for more than a few minutes, it may be worthwhile to get in contact with the website staff to report the error."));
      trigger_error("Theme '{$builder->getTheme()->name}' failed to initialize because \"{$ex->getMessage()}\" ({$ex->getCode()}) on line {$ex->getLine()}", E_USER_ERROR);
   }
}
else
   $builder->getTheme()->init();

foreach($builder->include_later as $file)
{
   if(class_exists("\\Throwable"))
   {
      try
      {
         include($file);
      }
      catch(\Throwable $ex)
      {
         http_response_code(500);
         $builder->setTitle("Error");
         $info = $builder->addContent(new Container("500 - Internal Server Error"));
         $info->addContent(new Text("The server encountered an unrecoverable error while attempting to load the page. If this problem persists for more than a few minutes, it may be worthwhile to get in contact with the website staff to report the error."));
         trigger_error("File '{$file}' failed to include because \"{$ex->getMessage()}\" ({$ex->getCode()}) on line {$ex->getLine()}", E_USER_ERROR);
      }
   }
   else
      include($file);
}

$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");
