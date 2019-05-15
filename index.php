<?php
require_once("includes". DIRECTORY_SEPARATOR ."MeLeeCMS.php");
$builder = new MeLeeCMS();
$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");
