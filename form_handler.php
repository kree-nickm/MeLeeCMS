<?php
require_once("includes". DIRECTORY_SEPARATOR ."MeLeeCMS.php");
$builder = new MeLeeCMS(15);

// Process POST data.
$response = [];
// NOTE: As a general rule, the "if" checks below should only be used to determine which form the page request is trying to use. It should not perform any sort of validation of the form input - save that for after the proper form has been identified.

if(!empty($_POST['addToCategory']))
{
	include("includes". DIRECTORY_SEPARATOR ."forms". DIRECTORY_SEPARATOR ."addToCategory.php");
}

if(!empty($_POST['orderCategories']))
{
	include("includes". DIRECTORY_SEPARATOR ."forms". DIRECTORY_SEPARATOR ."orderCategories.php");
}

if(!empty($_POST['editCategory']))
{
	include("includes". DIRECTORY_SEPARATOR ."forms". DIRECTORY_SEPARATOR ."editCategory.php");
}

// End of processing.
echo(json_encode($response));
if(!isset($_REQUEST['AJAX']))
{
	$_SESSION['form_response'] = $response;
	if(empty($destination))
	{
		if(empty($_REQUEST['callback']))
			$destination = $_SERVER['HTTP_REFERER'];
		else
			$destination = $_REQUEST['callback'];
	}
	header("Location: ". $destination);
}
