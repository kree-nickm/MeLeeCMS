<?php
namespace MeLeeCMS;

require_once("MeLeeCMS.php");
$builder = new MeLeeCMS(MeLeeCMS::MODE_PAGE);

foreach($builder->include_later as $file)
   include($file);

$builder->render((!empty($_GET['output']) && $_GET['output']=="xml") ? "__xml" : "default");
