<?php

if(version_compare(PHP_VERSION, "5.6.0beta1", "<"))
{
	echo("MeLeeCMS requires PHP version 5.6.0 or higher. Current version is ". PHP_VERSION .".");
}
else
{
	require_once("includes". DIRECTORY_SEPARATOR ."MeLeeCMS.php");
	$builder = new MeLeeCMS();

	foreach($builder->include_later as $file)
		include($file);

	$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");
}
