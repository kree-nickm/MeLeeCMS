<?php
require_once("includes". DIRECTORY_SEPARATOR ."MeLeeCMS.php");
$builder = new MeLeeCMS();

if(!empty($GlobalConfig['force_https']) && $_SERVER['HTTPS'] == "")
	header("Location: https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);

$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");
