<?php
class TwitchApplication
{
	public $curl;
	public $url;
	public $client_id;
	public $client_secret;
	
	public function __construct($url, $client_id, $client_secret="")
	{
		$this->url = $url;
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->curl = new CURLWrapper();
	}
	
	public function api_request($url="", $request="GET", $data="")
	{
		$raw = $this->curl->request($this->url . $url, [
			CURLOPT_HTTPHEADER => array("Client-ID: {$this->client_id}"),
			CURLOPT_CUSTOMREQUEST => $request,
			CURLOPT_POSTFIELDS => $data,
		]);
		return json_decode($raw);
	}
}