<?php

class MeLeeSessionHandler implements SessionHandlerInterface
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
	}
	
	public function open($save_path, $session_name)
	{
		return true;
	}
	
	public function read($session_id)
	{
		$session = $this->cms->database->query("SELECT * FROM sessions WHERE `session_id`=". $this->cms->database->quote($session_id) ." ORDER BY `time` DESC", Database::RETURN_ROW);
		return $session['session_data'];
	}
	
	public function write($session_id, $session_data)
	{
		$mysql_data = [
			'session_id' => $session_id,
			'session_data' => $session_data,
			'user' => $this->cms->user->get_property("index"),
			'time' => time(),
			'ip' => $_SERVER['REMOTE_ADDR'],
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
		];
		if(isset($_POST['remember_me']))
			$mysql_data['session_indefinite'] = !empty($_POST['remember_me']);
		$this->cms->database->insert("sessions", $mysql_data);
		return true;
	}
	
	public function close()
	{
		return true;
	}
	
	public function destroy($session_id)
	{
		$this->cms->database->delete("sessions", ['session_id'=>$session_id]);
		return true;
	}
	
	public function gc($maxlifetime)
	{
		$this->cms->database->query("DELETE FROM sessions WHERE `time`<". (int)(time()-$maxlifetime) ." AND (`user`=0 OR `session_indefinite`=0)");
		return true;
	}
}