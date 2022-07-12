<?php
namespace MeLeeCMS;

ob_start();
require_once("MeLeeCMS.php");
$builder = new MeLeeCMS(MeLeeCMS::MODE_FORM);

// Process request data.
$response = [];

foreach($builder->forms as $formId=>$form)
{
   if(is_callable($form['select']) && is_file($filepath = $builder->get_setting('server_path') ."includes". DIRECTORY_SEPARATOR ."forms". DIRECTORY_SEPARATOR . $form['file']))
   {
      if($form['select']($builder))
      {
         if(empty($form['permit']) || !is_callable($form['permit']) || $form['permit']($builder))
         {
            // Note: $form['permit'] should only be specified for forms pulled from the database (which isn't yet supported). Forms specified in config.php shouldn't use it, and should instead handle permission checks in the form's PHP file, because otherwise, config.php will get cluttered with too much form logic.
            include($filepath);
         }
         else
         {
            $response[$formId] = [];
            $response[$formId]['success'] = false;
            $response[$formId]['status'] = "denied";
            $response[$formId]['error'] = "You don't have permission to use this form.";
         }
      }
   }
   else
   {
      trigger_error("Invalid form \"{$formId}\" in config file:". (is_callable($form['select'])?"":" \$form['select'] is not a callable function, \"{$form['select']}\" given instead.") . (is_file($filepath)?"":" {$form['file']} is not a valid file in the 'includes/forms/' directory."), E_USER_WARNING);
   }
}

// End of processing.
ob_end_clean();
ob_start();
header("Content-Type: application/json");
if(empty($response))
   http_response_code(406);
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
ob_end_flush();
