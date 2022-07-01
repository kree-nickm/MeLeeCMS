<?php

$response[$formId] = []; // $formId should be defined by the array iterator in form_handler.php. If not, replace it with a unique string in this file.
/* The $response[$formId] array should be populated by the result of this form submission. Its elements should look something like this:

   $response[$formId]['success'] = false; // This should be true or false to indicate if the form was submitted successfully.
   
   $response[$formId]['status'] = "denied"; // This can be any unique identifier for this type of result. Normally you'd only specify this on a failed submission, so the page can use this to determine what feedback to give to the user about why the form submission failed. It can be helpful to use the same identifier for common failure cases. For example, every single form that fails because of permission denial should probably use the same status here. It's less useful to specify this on a success, but you can if you want.
   
   $response[$formId]['error'] = "You don't have permission to use this form."; // Only useful on a failed submission. A brief explanation for the failure. Not neccessary if the page is going to use the above status value to handle all feedback, but writing something here can still be useful for developer clarity.
   
   $response[$formId]['data'] = $builder->user->myInfo(); // Anything beyond the success indicator is optional on a form submission, but any data you put in the $response array can be used by the website to give the user additional feedback. The 'data' index is arbitrary; you can use any index or even spread the data across multiple elements. These responses are more useful to an AJAX request than a normal form submission, and the structure of the data depends on how the receiving JavaScript code is going to use it.
   
   You can see examples of all of the above options in the code below.
*/

/* If you want to force a form to use AJAX, you can include this line. When a form uses AJAX, it will print the $response array as JSON so that the browser can handle it as such. Non-AJAX forms will execute this file and then reload the page (or load a specified page) so that all form handling is seamless. That way, if the user refreshes, they won't get the "confirm form resubmission" popup from their browser or whatever. Normally, when a request is sent, it will specify in the POST data if its expecting an AJAX response, so you don't need this line. However, it can be helpful for debugging. */
$_REQUEST['AJAX'] = true;

// The rest of the file will usually contain a series of validation checks to see if the user has permission to use this form and if they specified the form input correctly.
if($builder->user->has_permission("VIEW"))
{
   if($_REQUEST['testForm'] == "myInfo")
   {
      $response[$formId]['success'] = true;
      $response[$formId]['userinfo'] = $builder->user->myInfo();
   }
   else if($_REQUEST['testForm'] == "themes")
   {
      $response[$formId]['success'] = true;
      $response[$formId]['themes'] = $builder->themes;
   }
   else
   {
      $response[$formId]['success'] = false;
      $response[$formId]['status'] = "invalid";
      $response[$formId]['error'] = "No valid option was specified.";
   }
}
else
{
   $response[$formId]['success'] = false;
   $response[$formId]['status'] = "denied";
   $response[$formId]['error'] = "You don't have permission to use this form.";
}