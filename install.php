<?php
if(!is_file("config.php"))
{
	echo("<tt>config.php</tt> has not been setup. You need to create a file named <tt>config.php</tt> in the same directory as <tt>index.php</tt> (usually the root directory) and configure it using the instructions in <tt>includes/defaultconfig.php</tt> in order to setup MeleeCMS.");
}
else
{
	require_once("includes/MeLeeCMS.php");
	$builder = new MeLeeCMS(0);

	if($builder->setup_database() && is_array($builder->database->metadata))
	{
		if(count($builder->database->metadata))
			echo("The database specifed in <tt>config.php</tt> already has data in it. MeleeCMS must be installed to an empty database. If you've already successfully installed MeLeeCMS, you can delete this file.");
		else
		{
			$sql = file_get_contents("includes/install.sql");
			$builder->database->query($sql);
			echo("The database has been setup. MeleeCMS should now be fully installed. You can delete this file.");
		}
	}
	else
	{
		echo("Error connecting to the database. Check <tt>config.php</tt> to make sure the database information is correct. Also check <tt>includes/builder_php_errors.log</tt> to see if a useful error was logged during the failed connection.");
	}
}