<?php

class CURLWrapper
{
	public $curl;
	public $default_options;
	public $lastheader;
	public $curlinfo;
	
	public function __construct($options=[])
	{
		$this->curl = curl_init();
		$default_options = [
			CURLOPT_FRESH_CONNECT => true,
			CURLOPT_HEADER => true,
			CURLINFO_HEADER_OUT => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_AUTOREFERER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 5,
			CURLOPT_ENCODING => "",
		];
		$this->default_options = $options + $default_options;
		curl_setopt_array($this->curl, $this->default_options);
	}
	
	public function __destruct()
	{
		curl_close($this->curl);
	}
	
	public function request($url, $options=[])
	{
		$curl_options = [CURLOPT_URL => $url];
		foreach($options as $opt=>$val)
		{
			switch($opt)
			{
				case "header":
				case "headers":
				case CURLOPT_HTTPHEADER:
					$curl_options[CURLOPT_HTTPHEADER] = $val;
					break;
				case "cookie":
				case "cookies":
				case CURLOPT_COOKIE:
					$curl_options[CURLOPT_COOKIE] = $val;
					break;
				case "method":
				case CURLOPT_CUSTOMREQUEST:
					$curl_options[CURLOPT_CUSTOMREQUEST] = $val;
					break;
				case "post":
				case "data":
				case CURLOPT_POSTFIELDS:
					$curl_options[CURLOPT_POSTFIELDS] = $val;
					break;
				default:
					$curl_options[$opt] = $val;
			}
		}
		curl_setopt_array($this->curl, $curl_options);
		if(($response = curl_exec($this->curl)) !== false)
		{
			list($this->lastheader, $raw) = explode("\n\n", str_replace("\r", "", $response), 2);
		}
		else
		{
			$this->lastheader = curl_strerror(curl_errno($this->curl));
			$raw = curl_error($this->curl);
		}
		$this->curlinfo = curl_getinfo($this->curl);
		curl_reset($this->curl);
		curl_setopt_array($this->curl, $this->default_options);
		return $raw;
	}
}