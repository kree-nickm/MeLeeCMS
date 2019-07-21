<?php
if(version_compare(PHP_VERSION, "5.5.0", "<"))
{
	echo("MeLeeCMS requires PHP version 5.5.0 or higher. Current version is ". PHP_VERSION .".");
}
else
{
	require_once("includes". DIRECTORY_SEPARATOR ."MeLeeCMS.php");
	$builder = new MeLeeCMS();

	if(!empty($GlobalConfig['force_https']) && empty($_SERVER['HTTPS']))
		header("Location: https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);

	foreach($builder->include_later as $file)
		include($file);

	$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");
}
