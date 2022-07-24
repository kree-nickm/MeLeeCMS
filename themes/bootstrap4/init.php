<?php
/**
Included when this theme is loaded by MeLeeCMS.
This file should return a function that {@see Theme} will store for later. The function will be called by {@see Theme::init()} during a GET request after {@see MeLeeCMS} has been instantiated, but before any PHP files are included as part of the current page.
*/
namespace MeLeeCMS;

/**
Executes some code that needs to be run on every page that uses this theme.
Typical code that might go here would be things like {@see MeLeeCMS::attachCSS()}, {@see MeLeeCMS::attachJS()}, etc. to make sure required theme files are loaded by the browser on every page.
@param MeLeeCMS $cms A reference to the MeLeeCMS instance.
@returns boolean If true, then {@see Theme::init()} will be called for this theme's superthemes. If false, then it will not.
*/
return function($cms)
{
   $cms->attachJS("modal-extras.js", "", true);
   return true; // This is technically irrelevent for bootstrap4 since it has no superthemes, and the default theme has no init.php.
};
