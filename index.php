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
    // PHPVersion: Throwable requires PHP >= 7.0.0. This code would catch certain fatal errors during page load in PHP7 so that PHP fails a little more gracefully. In earlier versions of PHP, the response will just be a blank page with a 500 status code.
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
        echo("The server encountered an unrecoverable error while attempting to load the page. If this problem persists for more than a few minutes, it may be worthwhile to report the error to the website staff");
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
