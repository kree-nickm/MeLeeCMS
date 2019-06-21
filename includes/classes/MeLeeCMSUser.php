<?php

/**
 * A User class that provides basic login features and a permission for the control panel.
 */
class MeLeeCMSUser extends User
{
	const PERM_VIEW = 1;
	const PERM_ADMIN = 2;
	
	public $error = "";
	protected $obscured_cols = ["password", "permission", "token", "custom_data", "custom_data_keys"];

	public function __construct($cms)
	{
		parent::__construct($cms);
		if(isset($_REQUEST['logout']))
		{
			$_SESSION['username'] = "";
			$_SESSION['password'] = "";
			// This isn't enough, close session entirely.
			$this->cms->requestRefresh($cms->get_setting('url_path'), "logout");
		}
		else
		{
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
						$this->logged_in = true;
						$this->user_info = $user;
						$this->user_info['ip'] = $_SERVER['REMOTE_ADDR'];
						$this->user_info['custom_data_keys'] = [];
						$custom_data = json_decode($this->user_info['custom_data'], true);
						if(is_array($custom_data))
							foreach($custom_data as $key=>$val)
								if(!isset($this->user_info[$key]))
								{
									$this->user_info['custom_data_keys'][] = $key;
									$this->user_info[$key] = $val;
								}
						// Load a new page if the login was just submitted in order to clear the POST data.
						if($username != "" || $password != "")
							$this->cms->requestRefresh();
					}
				}
			}
			if(!$this->logged_in)
			{
				$_SESSION['username'] = "";
				$_SESSION['password'] = "";
			}
		}
	}
	
	public function setCustomDataGroup($array)
	{
		foreach($array as $key=>$data)
		{
			$this->setCustomData($key, $data, false);
		}
		$this->saveCustomData();
	}
	
	public function setCustomData($key, $data, $save=true)
	{
		if(in_array($key, $this->user_info['custom_data_keys']))
		{
			$this->user_info[$key] = $data;
			if($save)
				$this->saveCustomData();
			return true;
		}
		else if(!in_array($key, $this->user_info['custom_data_keys']) && !isset($this->user_info[$key]))
		{
			$this->user_info[$key] = $data;
			$this->user_info['custom_data_keys'][] = $key;
			if($save)
				$this->saveCustomData();
			return true;
		}
		return false;
	}
	
	protected function saveCustomData()
	{
		$custom_data = [];
		foreach($this->user_info['custom_data_keys'] as $key)
		{
			$custom_data[$key] = $this->user_info[$key];
		}
		$this->cms->database->insert("users", ['index'=>$this->user_info['index'],'custom_data'=>json_encode($custom_data)], true, ['index'], true);
	}
	
	public function register($username, $password1, $password2, $permission=MeLeeCMSUser::PERM_VIEW, $custom_data=[])
	{
		$this->error = [];
		if($username == "")
			$this->error[] = "Username cannot be blank.";
		else
		{
			$existing = $this->cms->database->query("SELECT `index` FROM `users` WHERE `username`=". $this->cms->database->quote($username), Database::RETURN_FIELD);
			if($existing)
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
			'permission' => $permission,
			'custom_data' => json_encode($custom_data),
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
}