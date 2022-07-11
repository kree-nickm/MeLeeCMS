<?php
namespace MeLeeCMS;

//ob_start();
require_once("includes". DIRECTORY_SEPARATOR ."MeLeeCMS.php");
$builder = new MeLeeCMS(MeLeeCMS::SETUP_DATABASE | MeLeeCMS::SETUP_SETTINGS | MeLeeCMS::SETUP_THEME);

if($_SERVER['CONTENT_TYPE'] == "application/json")
{
   $input = json_decode(file_get_contents('php://input'));
}
else
{
   $response = "ajaxXSLT requires JSON input.";
}

if(!empty($input->process))
{
   $class = empty($input->class) ? "MeLeeCMS" : preg_replace("/[^a-zA-Z0-9]/", "", $input->class);
   $subtheme = empty($input->subtheme) ? "default" : preg_replace("/[^a-zA-Z0-9]/", "", $input->subtheme);
   $process = empty($input->process) ? "" : preg_replace("/[^a-zA-Z0-9]/", "", $input->process);
   $process_file = "includes/pages/includes/{$process}.php";
   $xsl_files = [];
   if(isset($input->xsl) && is_array($input->xsl))
   {
      foreach($input->xsl as $xsl)
      {
         if(!empty($xsl->href))
            //$xsl_files[] = $xsl;
            $builder->attach_xsl($xsl->href);
         else if(is_string($xsl))
            //$xsl_files[] = ['href'=>$xsl];
            $builder->attach_xsl($xsl);
      }
   }
   if(is_file($process_file))
   {
      $included_input = [];
      if(isset($input->inputs) && is_object($input->inputs))
      {
         $included_input = get_object_vars($input->inputs);
      }
      $result = require($process_file);
      $result_content = [];
      if($subtheme == "__xml")
         foreach($result as $tag=>$content)
         {
            $content->set_cms($builder);
            $result_content['content@class='.$content->getContentClass().($tag?'@id='.$tag:'')][] = $content->build_params();
         }
      else
         foreach($result as $tag=>$content)
         {
            $content->set_cms($builder);
            $result_content['content@class='.$content->getContentClass().($tag?'@id='.$tag:'')][] = $content->render($subtheme);
         }
      $response = $builder->parse_template($result_content, $class, $subtheme);
   }
   else
      $response = "Invalid process '{$process}'.";
}
else
   $response = "No data.";

// End of processing.
//ob_end_clean();

echo($response);
