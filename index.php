<?php
namespace MeLeeCMS;

if(version_compare(PHP_VERSION, "5.6.0beta1", "<"))
{
   http_response_code(500);
	echo("MeLeeCMS requires PHP version 5.6.0 or higher. Current version is ". PHP_VERSION .".");
}
else
{
   require_once("includes/init.php");
   if($_SERVER['REQUEST_METHOD'] == "GET")
   {
      // PHPVersion: Throwable requires PHP >= 7.0.0, but this check should provide a fallback for older versions.
      if(class_exists("\\Throwable"))
      {
         try
         {
            require_once("includes/get.php");
         }
         catch(\Throwable $ex)
         {
            http_response_code(500);
            trigger_error("MeLeeCMS failed to process the GET request because \"{$ex->getMessage()}\" ({$ex->getCode()}) in get.php on line {$ex->getLine()}", E_USER_ERROR);
            echo("The server encountered an unrecoverable error while attempting to load the page. If this problem persists for more than a few minutes, it may be worthwhile to get in contact with the website staff to report the error.");
         }
      }
      else
         require_once("includes/get.php");
   }
   else if($_SERVER['REQUEST_METHOD'] == "POST")
   {
      $content_type_header = explode(";", $_SERVER['CONTENT_TYPE']);
      $content_type = trim($content_type_header[0]);
      if($content_type == "application/x-www-form-urlencoded" || $content_type == "multipart/form-data")
      {
         require_once("includes/post-form.php");
      }
      else if($content_type == "application/json")
      {
         require_once("includes/post-json.php");
      }
      else
      {
         http_response_code(415);
         trigger_error("User attempted a POST request of type '{$content_type}'.", E_USER_WARNING);
         echo("Post data of type '{$content_type}' is not allowed.");
      }
   }
   else
   {
      http_response_code(405);
      trigger_error("User attempted a {$_SERVER['REQUEST_METHOD']} request.", E_USER_WARNING);
      echo($_SERVER['REQUEST_METHOD'] ." requests are not allowed.");
   }
}
