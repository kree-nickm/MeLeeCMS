<?
require_once("includes/MeLeeCMS.php");
$builder = new MeLeeCMS();
$builder->render($_GET['output']=="xml"?"__xml":"default");
