<?php
namespace MeLeeCMS;

if(version_compare(PHP_VERSION, "5.6.0beta1", "<"))
{
	echo("MeLeeCMS requires PHP version 5.6.0 or higher. Current version is ". PHP_VERSION .".");
}
else
{
   if($_SERVER['REQUEST_METHOD'] == "GET")
   {
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
         trigger_error("User attempted a POST request of type '{$content_type}'.", E_USER_NOTICE);
         echo("Post data of type '{$content_type}' is not allowed.");
      }
   }
   else
   {
      http_response_code(405);
      trigger_error("User attempted a {$_SERVER['REQUEST_METHOD']} request.", E_USER_NOTICE);
      echo($_SERVER['REQUEST_METHOD'] ." requests are not allowed.");
   }
}
