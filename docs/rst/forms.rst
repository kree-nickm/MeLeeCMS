Forms
============
This page describes how to configure custom forms in ``config.php``. The guide on how to create forms with the control panel is elsewhere.

A form definition will look something like below::

   $GlobalConfig['forms']['formId'] = [
      'file' => 'form-logic.php',
      'select' => function($cms){
         return isset($_POST['formId']);
      },
   ];
