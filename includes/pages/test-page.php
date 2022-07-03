<?php
/*
 * You should declare the MeLeeCMS namespace in every single PHP file that is going to use MeLeeCMS.
 * Review how to use namespaces at https://www.php.net/manual/en/language.namespaces.php
 * The most important thing to keep in mind is that, after this namespace declaration, all class names that you use will be expected to be in the MeLeeCMS namespace. If you need a class from outside the MeLeeCMS namespace, you must prepend a \ to the class name every single time you refer to it.
 */
namespace MeLeeCMS;

$builder->set_title("Test Page");

$container = $builder->add_content(new Container("Testing Grounds"), "test-container");
$container->add_content(new Text("Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."), "test-text");
