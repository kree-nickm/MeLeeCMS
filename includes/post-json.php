<?php
namespace MeLeeCMS;

ob_start();
require_once("MeLeeCMS.php");
$builder = new MeLeeCMS(MeLeeCMS::MODE_AJAX);

$response = "";
$input = json_decode(file_get_contents('php://input'));

if(!empty($input->process))
{
   $class = empty($input->class) ? "MeLeeCMS" : preg_replace("/[^a-zA-Z0-9]/", "", $input->class);
   $format = empty($input->format) ? "ajax" : preg_replace("/[^a-zA-Z0-9]/", "", $input->format);
   $process = empty($input->process) ? "" : preg_replace("/[^a-zA-Z0-9]/", "", $input->process);
   $process_file = $builder->getSetting('server_path') ."includes". DIRECTORY_SEPARATOR ."pages". DIRECTORY_SEPARATOR ."includes". DIRECTORY_SEPARATOR . $process .".php";
   $xsl_files = [];
   if(isset($input->xsl) && is_array($input->xsl))
   {
      foreach($input->xsl as $xsl)
      {
         if(!empty($xsl->href))
            //$xsl_files[] = $xsl;
            $builder->attachXSL($xsl->href);
         else if(is_string($xsl))
            //$xsl_files[] = ['href'=>$xsl];
            $builder->attachXSL($xsl);
      }
   }
   if(is_file($process_file))
   {
      $included_input = [];
      if(isset($input->inputs) && is_object($input->inputs))
      {
         // TODO: Do we want to convert it, or force the included file to use objects?
         $included_input = get_object_vars($input->inputs);
      }
      $result = require($process_file);
      $result_content = [];
      foreach($result as $tag=>$content)
      {
         $content->set_cms($builder);
         $result_content['content@class='.$content->getContentClass().($tag?'@id='.$tag:'')][] = $content->build_params();
      }
      $response = $builder->parseTemplate($result_content, $class, $format);
   }
   else
      http_response_code(406);
}
else
   http_response_code(406);

ob_end_clean();
header("Content-type: text/html");
echo($response);
