<?php
namespace MeLeeCMS;

trigger_error($_SERVER['HTTP_REFERER'] ." sent POST data to form_handler.php.", E_USER_DEPRECATED);
require("index.php");
