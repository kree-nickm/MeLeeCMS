<?php
namespace MeLeeCMS;

$password = "";

// TODO: Copy most of this into various files in includes/install/
ini_set("display_errors", 0);
ini_set("log_errors", 1);
if(empty($password))
{
	echo("Before you can begin the installation, you must set a password to prevent unauthorized installations. Open <tt>install.php</tt> in a text editor and change the line near the very top that says <tt>\$password = \"\";</tt> and enter your desired password between the quotes, so that it looks like <tt>\$password = \"mypassword\";</tt><br/>Then, reload this page and enter that password to begin installation. The password does not need to be very secure, as it is one-time-use for this installation. It should just be letters and/or numbers, as any symbols might not work.");
}
else if(empty($_POST['password']) || $_POST['password'] != $password)
{
	echo("Enter your password below to begin the installation.<form method='post'><input type='text' name='password'/><button type='submit'>Enter</button></form>");
	if(isset($_POST['password']))
		echo("Password incorrect. You can literally just copy what you entered from between the quotes in <tt>install.php</tt>. ");
}
else
{
	ini_set("display_errors", 1);
	if(!empty($_POST['config_submit']))
	{
		if(is_file("config.php"))
		{
			rename("config.php", "config.php.old");
			echo("<tt>config.php</tt> already exists. Renaming the existing one to <tt>config.php.old</tt> and creating a new one with your new settings. ");
		}
		$fp = fopen("config.php", "w");
		fwrite($fp, "<?php". PHP_EOL);
		fwrite($fp, "\$GlobalConfig['dbhost'] = \"". addslashes($_POST['dbhost']) ."\";". PHP_EOL);
		fwrite($fp, "\$GlobalConfig['dbname'] = \"". addslashes($_POST['dbname']) ."\";". PHP_EOL);
		fwrite($fp, "\$GlobalConfig['dbuser'] = \"". addslashes($_POST['dbuser']) ."\";". PHP_EOL);
		fwrite($fp, "\$GlobalConfig['dbpass'] = \"". addslashes($_POST['dbpass']) ."\";". PHP_EOL);
		fwrite($fp, "\$GlobalConfig['cpanel_dir'] = \"". addslashes($_POST['cpanel_dir']) ."\";". PHP_EOL);
		fwrite($fp, "\$GlobalConfig['server_path'] = \"". addslashes($_POST['server_path']) ."\";". PHP_EOL);
		fwrite($fp, "\$GlobalConfig['url_path'] = \"". addslashes($_POST['url_path']) ."\";". PHP_EOL);
		fwrite($fp, "\$GlobalConfig['force_https'] = ". (empty($_POST['force_https']) ? "false" : "true") .";". PHP_EOL);
		fwrite($fp, "\$GlobalConfig['cookie_prefix'] = \"". addslashes($_POST['cookie_prefix']) ."\";". PHP_EOL);
		fwrite($fp, "\$GlobalConfig['user_system'] = \"". addslashes($_POST['user_system']) ."\";". PHP_EOL);
		fwrite($fp, "\$GlobalConfig['site_title'] = \"". addslashes($_POST['site_title']) ."\";". PHP_EOL);
		fwrite($fp, "\$GlobalConfig['default_theme'] = \"". addslashes($_POST['default_theme']) ."\";". PHP_EOL);
		fwrite($fp, "\$GlobalConfig['cpanel_theme'] = \"". addslashes($_POST['cpanel_theme']) ."\";". PHP_EOL);
		fclose($fp);
	}
	
	if(!is_file("config.php"))
	{
		$show_config = true;
	}
	else
	{
		require_once("includes/MeLeeCMS.php");
		$builder = new MeLeeCMS(0);

		if($builder->setup_database() && is_array($builder->database->metadata))
		{
			if(!empty($builder->database->metadata['users']) && !empty($_POST['reg_submit']))
			{
				if($builder->setup_user())
				{
					if($builder->user->register($_POST['reg_username'], $_POST['reg_password'], $_POST['reg_password2'], MeLeeCMSUser::PERM_VIEW|MeLeeCMSUser::PERM_ADMIN))
					{
						echo("Your admin account has been registered. You should now delete <tt>install.php</tt>. ");
					}
					else
					{
						echo("Errors occurred during your registration: ". implode(" ", $builder->user->error));
						$show_register = true;
					}
				}
				else
				{
					echo("An error occurred trying to access the user database.");
					$show_register = true;
				}
			}
			else if(count($builder->database->metadata))
			{
				if(!empty($builder->database->metadata['changelog']) && !empty($builder->database->metadata['pages']) && !empty($builder->database->metadata['page_components']) && !empty($builder->database->metadata['settings']) && !empty($builder->database->metadata['users']))
				{
					// This is where we can check for updates, or check to see if this is a MeLeeCMS database or something else, and display an appropriate message.
					echo("MeLeeCMS appears to have been installed to this database already. ");
					$admins = $builder->database->query("SELECT `index` FROM users WHERE `permission`&". (int)MeLeeCMSUser::PERM_ADMIN ."=". (int)MeLeeCMSUser::PERM_ADMIN, Database::RETURN_FIELD);
					if(!empty($admins))
					{
						echo("Additionally, at least one admin account appears to have been already set up. You should delete <tt>install.php</tt>. However, if you need to create another admin account, you can do so below. ");
					}
					else
					{
						echo("However, there is no admin account. Use the form below to create one. ");
					}
					$show_register = true;
				}
				else
				{
					echo("The database specifed in <tt>config.php</tt> already has data in it. MeleeCMS must be installed to an empty database. ");
				}
			}
			else
			{
				$sql = file_get_contents("includes/install.sql");
				$builder->database->query($sql);
				$builder->database->refresh_metadata();
				if(count($builder->database->metadata))
				{
					//$builder->database->insert("settings", ['setting'=>"site_title", 'value'=>$GlobalConfig['site_title']], true, ["setting"]);
					//$builder->database->insert("settings", ['setting'=>"default_theme", 'value'=>$GlobalConfig['default_theme']], true, ["setting"]);
					echo("The database has been setup. MeleeCMS should now be fully installed. However, you should now create an admin account for yourself. ");
					$show_register = true;
				}
				else
				{
					echo("The database failed to create. Check <tt>config.php</tt> to make sure the database information is correct. ");
				}
			}
		}
		else
		{
			echo("Error connecting to the database. Check <tt>config.php</tt> to make sure the database information is correct. ");
		}
	}
}

if(!empty($show_config))
{
?><!DOCTYPE html><html><body>
<tt>config.php</tt> has not been set up. Use this form to set it up now.
<form method="post">
	<input type="hidden" name="password" value="<?=$_POST['password']?>"/>
	<table>
		<tbody>
			<tr>
				<th>Database Hostname:</th>
				<td><input type="text" name="dbhost" placeholder="ie. localhost"/></td>
				<td style="font-size:0.9em;">IP or hostname of the MySQL database server to use for this MeLeeCMS installation.</td>
			</tr>
			<tr>
				<th>Database Name:</th>
				<td><input type="text" name="dbname" placeholder="ie. mydb_meleecms"/></td>
				<td style="font-size:0.9em;">The name of the MySQL database to install the MeLeeCMS tables to.</td>
			</tr>
			<tr>
				<th>Database Username:</th>
				<td><input type="text" name="dbuser" placeholder="ie. mydb_meleecmsuser"/></td>
				<td style="font-size:0.9em;">The username to login to the above MySQL database.</td>
			</tr>
			<tr>
				<th>Database Password:</th>
				<td><input type="password" name="dbpass"/></td>
				<td style="font-size:0.9em;">The password to go with the username to login to the above MySQL database.</td>
			</tr>
<?php
if(empty($_POST['cpanel_dir']))
{
	$dir = dir(__DIR__);
	while(($file = $dir->read()) !== false)
	{
		if(is_dir($file) && is_file($file . DIRECTORY_SEPARATOR . "load_page.php"))
		{
			$cpanel_dir = $file;
			break;
		}
	}
}
else
	$cpanel_dir = $_POST['cpanel_dir'];
?>
			<tr>
				<th>Admin Panel Directory:</th>
				<td><input type="text" name="cpanel_dir" value="<?=$cpanel_dir?>"/></td>
				<td style="font-size:0.9em;">The directory that contains the files for the admin panel. This has been automatically detected, so you shouldn't need to change it unless the detection failed.</td>
			</tr>
<?php
if(empty($_POST['server_path']))
	$server_path = __DIR__ . DIRECTORY_SEPARATOR;
else
	$server_path = $_POST['server_path'];
?>
			<tr>
				<th>Server Path to Site:</th>
				<td><input type="text" name="server_path" value="<?=$server_path?>"/></td>
				<td style="font-size:0.9em;">The path from the server root to the website root. This has been automatically detected, so you shouldn't need to change it unless this is a non-standard MeLeeCMS installation.</td>
			</tr>
<?php
if(empty($_POST['url_path']))
	$url_path = dirname($_SERVER['SCRIPT_NAME']) ."/";
else
	$url_path = $_POST['url_path'];
?>
			<tr>
				<th>URL Path to Site:</th>
				<td><input type="text" name="url_path" value="<?=$url_path?>"/></td>
				<td style="font-size:0.9em;">The URL path to access this website from the domain name. This has been automatically detected, so you shouldn't need to change it unless this is a non-standard MeLeeCMS installation.</td>
			</tr>
			<tr>
				<th>Force HTTPS:</th>
				<td><input type="checkbox" name="force_https" value="1"<?=(empty($_SERVER['HTTPS'])?"":" checked")?>/></td>
				<td style="font-size:0.9em;">Only check in this box if your website has an SSL certificate and HTTPS is working.</td>
			</tr>
			<tr>
				<th>Cookie Prefix:</th>
				<td><input type="text" name="cookie_prefix" value="melee_"/></td>
				<td style="font-size:0.9em;">The prefix to use for cookies set by this website. It can be anything as long as it consists of only letters and underscores. Changing this value after the site has been set up will force all users to login again.</td>
			</tr>
			<tr>
				<th>User Management System:</th>
				<td>
					<select name="user_system">
					<?php include_once("includes/classes/User.php"); foreach(User::get_subclasses() as $v) { echo("<option value=\"". $v ."\">". $v ."</option>"); } ?>
						<option value="User">User (not recommended)</option>
					</select>
				</td>
				<td style="font-size:0.9em;">How to handle user logins. <tt>User</tt> bypasses logins entirely (note that this will leave your admin panel unprotected unless you setup some other form of protection). <tt>MeLeeCMSUser</tt> has all the basic functionality needed to use all built-in site features. Anything else is a custom implementation.</td>
			</tr>
			<tr>
				<th>Site Title:</th>
				<td><input type="text" name="site_title" placeholder="ie. My Website"/></td>
				<td style="font-size:0.9em;">The title of your site, which will be used in the title bar of all of the site pages.</td>
			</tr>
			<tr>
				<th>Default Theme:</th>
				<td><input type="text" name="default_theme" value="bootstrap4"/></td>
				<td style="font-size:0.9em;">The theme to use for your website. Only change this if you know you have another theme installed that works with your website.</td>
			</tr>
			<tr>
				<th>Admin Panel Theme:</th>
				<td><input type="text" name="cpanel_theme" value="bootstrap4"/></td>
				<td style="font-size:0.9em;">The theme to use for the admin panel. Do not change this unless you know for certain that you have a custom theme that has been fully tested to work with the admin panel, because the vast majority of themes will not. You will not be able to use this admin panel if this theme is invalid.</td>
			</tr>
		</tbody>
	</table>
	<button type="submit" name="config_submit" value="1">Save config.php</button>
</form>
</body></html><?php
}

if(!empty($show_register))
{
?><!DOCTYPE html><html><body>
Use this form to create your admin account.
<form method="post">
	<input type="hidden" name="password" value="<?=$_POST['password']?>"/>
	<table>
		<tbody>
			<tr>
				<th>Username:</th>
				<td><input type="text" name="reg_username"/></td>
			</tr>
			<tr>
				<th>Password:</th>
				<td><input type="password" name="reg_password"/></td>
			</tr>
			<tr>
				<th>Password Again:</th>
				<td><input type="password" name="reg_password2"/></td>
			</tr>
		</tbody>
	</table>
	<button type="submit" name="reg_submit" value="1">Register Your Account</button>
</form>
</body></html><?php
}