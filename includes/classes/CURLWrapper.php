<?php

class CURLWrapper
{
	public $curl;
	public $default_options;
	public $response_headers_raw = [];
	public $response_headers = [];
	public $request_info = [];
	
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
      $response_headers = [];
		if(($response = curl_exec($this->curl)) !== false)
		{
			list($response_headers_raw, $raw) = explode("\n\n", str_replace("\r", "", $response), 2);
         foreach(explode("\n", $response_headers_raw) as $i=>$line)
         {
            if($i == 0) // Response status line
               list($response_headers['protocol'], $response_headers['code'], $response_headers['status']) = explode(" ", $line);
            else
            {
               list($key, $val) = explode(": ", $line);
               $response_headers[$key] = $val;
            }
         }
		}
		else
		{
         // TODO: Review what a failed response looks like to verify this is how we want to handle it.
			// Note: Don't know if we should care about this, but using curl_strerror() means we require PHP>=5.5.0
			$response_headers_raw = curl_strerror(curl_errno($this->curl));
			$raw = curl_error($this->curl);
		}
		$this->response_headers_raw[] = $response_headers_raw;
		$this->response_headers[] = $response_headers;
		$request_info = curl_getinfo($this->curl);
      if(!empty($request_info['request_header']))
      {
         $request_headers = [];
         foreach(explode("\n", $request_info['request_header']) as $i=>$line)
         {
            $line = trim($line);
            if($i == 0) // Request status line
               list($request_headers['method'], $request_headers['uri'], $request_headers['protocol']) = explode(" ", $line);
            else if(!empty($line))
            {
               list($key, $val) = explode(": ", $line);
               $request_headers[$key] = $val;
            }
         }
         $request_info['request_header'] = $request_headers;
      }
		$this->request_info[] = $request_info;
		// Note: Don't know if we should care about this, but using curl_reset() means we require PHP>=5.5.0
		curl_reset($this->curl);
		curl_setopt_array($this->curl, $this->default_options);
		return $raw;
	}
   
   public function getLastHeaders()
   {
      return $this->response_headers[count($this->response_headers)-1];
   }
   
   public function getLastRawHeaders()
   {
      return $this->response_headers_raw[count($this->response_headers_raw)-1];
   }
   
   public function getLastRequestInfo()
   {
      return $this->request_info[count($this->request_info)-1];
   }
}