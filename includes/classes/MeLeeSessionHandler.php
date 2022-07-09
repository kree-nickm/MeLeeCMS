<?php
namespace MeLeeCMS;

// Note: Don't know if we should care about this, but using SessionHandlerInterface means we require PHP>=5.4.0
class MeLeeSessionHandler implements \SessionHandlerInterface
{
	protected $cms;
	
	public function __construct($cms)
	{
		$this->cms = $cms;
		if(ini_get("session.gc_maxlifetime") > 86400 || ini_get("session.gc_maxlifetime") <= 0)
			ini_set("session.gc_maxlifetime", "3600");
		if(ini_get("session.gc_probability") <= 0)
			ini_set("session.gc_probability", "1");
		if(ini_get("session.gc_divisor") <= 0)
			ini_set("session.gc_divisor", "100");
		ini_set("session.use_strict_mode", true);
	}
	
	public function open($save_path, $session_name)
	{
		// This is only useful for file sessions, which this handler doesn't use.
		return true;
	}
	
	public function read($session_id)
	{
		$session_data = $this->cms->database->query("SELECT `session_data` FROM sessions WHERE `session_id`=". $this->cms->database->quote($session_id) ." ORDER BY `time` DESC LIMIT 0,1", Database::RETURN_FIELD);
		return empty($session_data) ? "" : $session_data;
	}
	
	public function write($session_id, $session_data)
	{
		$mysql_data = [
			'session_id' => $session_id,
			'session_data' => $session_data,
			'user' => (int)$this->cms->user->get_property("index"),
			'time' => time(),
			'ip' => $_SERVER['REMOTE_ADDR'],
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
         // TODO: below setting does not seem to be working.
			'session_indefinite' => (int)!empty($this->cms->session_expiration),
		];
		$this->cms->database->insert("sessions", $mysql_data);
		return true;
	}
	
	public function close()
	{
		return true;
	}
	
	public function destroy($session_id)
	{
		$_SESSION = [];
		if(ini_get("session.use_cookies"))
		{
			$params = session_get_cookie_params();
			setcookie(
				session_name(),
				'',
				time() - 42000,
				$params["path"],
				$params["domain"],
				$params["secure"],
				$params["httponly"]
			);
		}
		$this->cms->database->delete("sessions", ['session_id'=>$session_id]);
		return true;
	}
	
	public function gc($maxlifetime)
	{
		$count = $this->cms->database->query("DELETE FROM sessions WHERE `time`<". (int)(time()-$maxlifetime) ." AND (`user`=0 OR `session_indefinite`=0)", Database::RETURN_COUNT);
		return $count;
	}
}