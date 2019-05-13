<?php

/**
 * A User class that provides basic login features and a permission for the control panel.
 */
class MeLeeCMSUser extends User
{
	const PERM_VIEW = 1;
	const PERM_ADMIN = 2;
	
	public $error = "";

	public function __construct($cms)
	{
		parent::__construct($cms);
		$username = stripslashes($_POST['username']);
		$password = stripslashes($_POST['password']);
		if($username != "")
			$_SESSION['username'] = $username;
		if($_SESSION['username'] != "")
		{
			$user = $cms->database->query("SELECT * FROM users WHERE `username`=". $cms->database->quote($_SESSION['username']) ." LIMIT 0,1", Database::RETURN_ROW);
			if($user['index'])
			{
				if($password != "")
					$_SESSION['password'] = crypt($password, $user['password']);
				if(hash_equals($user['password'], $_SESSION['password']))
				{
					if($username != "" || $password != "")
					{
						if($_SERVER['HTTP_REFERER'] != "")
							header("Location: ". $_SERVER['HTTP_REFERER']);
						else
							header("Location: ". $_SERVER['REQUEST_URI']);
						exit;
					}
					$this->logged_in = true;
					$this->user_info = $user;
					$this->user_info['ip'] = $_SERVER['REMOTE_ADDR'];
				}
			}
		}
		if(!$this->logged_in)
		{
			$_SESSION['username'] = "";
			$_SESSION['password'] = "";
		}
	}
	
	public function register($username, $password1, $password2)
	{
		$this->error = [];
		if($username == "")
			$this->error[] = "Username cannot be blank.";
		else
		{
			$existing = $this->cms->database->query("SELECT `index` FROM `users` WHERE `username`=". $this->cms->database->quote($username), Database::RETURN_FIELD);
			if($existing['index'])
				$this->error[] = "Username is taken.";
		}
		if($password1 == "")
			$this->error[] = "Password cannot be blank.";
		else if($password1 != $password2)
			$this->error[] = "Passwords do not match.";
		
		if(count($this->error))
			return false;
		
		$mysql_array = [
			'username' => $username,
			'password' => password_hash($password1, PASSWORD_DEFAULT),
			'jointime' => time(),
			'permission' => self::PERM_VIEW,
		];
		$result = $this->cms->database->insert("users", $mysql_array, false);
		if($result)
			return $result;
		else
		{
			$this->error[] = $this->cms->database->error[2];
			return $result;
		}
	}
	
	public function change_password($password1, $password2)
	{
		$this->error = [];
		if(!$this->logged_in)
		{
			$this->error[] = "Not logged in.";
			return false;
		}
		if($password1 == "")
			$this->error[] = "Password cannot be blank.";
		else if($password1 != $password2)
			$this->error[] = "Passwords do not match.";
		
		if(count($this->error))
			return false;
		
		$mysql_array = [
			'index' => $this->user_info['index'],
			'password' => password_hash($password1, PASSWORD_DEFAULT),
		];
		$result = $this->cms->database->insert("users", $mysql_array, true, ['index']);
		if($result)
			return $result;
		else
		{
			$this->error[] = $this->cms->database->error[2];
			return $result;
		}
	}

	/*public static function default_user()
	{
		$result['ip'] = $_SERVER['REMOTE_ADDR'];
		$result['username'] = "guest". rand(1000,9999);
		$result['jointime'] = time();
		$result['permission'] = self::PERM_VIEW;
		return $result;
	}*/
}