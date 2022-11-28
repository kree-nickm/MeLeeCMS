<?php
namespace MeLeeCMS;

class CURLWrapper
{
  public $curl;
  public $default_options;
  public $log = [];
  public $log_postdata;
  public $log_responses;
  
  public function __construct($options=[], $log_postdata=false, $log_responses=false)
  {
    $this->curl = curl_init();
    $this->log_postdata = $log_postdata;
    $this->log_responses = $log_responses;
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
          if($this->log_postdata)
            $post_data = $val;
          break;
        default:
          $curl_options[$opt] = $val;
      }
    }
    curl_setopt_array($this->curl, $curl_options);
    // TODO: Stop cURL from sending Expect: 100-continue header on large requests.
    if(($response = curl_exec($this->curl)) !== false)
    {
      list($response_headers, $raw) = $this->parseHeaders(str_replace("\r", "", $response));
    }
    else
    {
      $response_headers = [];
      $raw = ['type'=>curl_strerror(curl_errno($this->curl)), 'message'=>curl_error($this->curl)];
    }
    
    // Parse the request_header field into an array before storing it.
    $request_info = curl_getinfo($this->curl);
    if(!empty($request_info['request_header']))
    {
      $request_headers = [];
      foreach(explode("\n", $request_info['request_header']) as $i=>$line)
      {
        $line = trim($line);
        if($i == 0) // Request status line
        {
          // This weird code is in case the URI has spaces.
          $req = explode(" ", $line);
          $request_headers['method'] = array_shift($req);
          $request_headers['protocol'] = array_pop($req);
          $request_headers['uri'] = implode(" ", $req);
        }
        else if(!empty($line))
        {
          list($key, $val) = explode(": ", $line);
          $request_headers[$key] = $val;
        }
      }
      $request_info['request_header'] = $request_headers;
    }
    $this->log[] = [
      'request_info' => $request_info,
      'response_headers' => $response_headers,
    ];
    if($this->log_postdata)
      $this->log[count($this->log)-1]['post_data'] = !empty($post_data) ? $post_data : "";
    if($this->log_responses)
      $this->log[count($this->log)-1]['response'] = $raw;
    
    curl_reset($this->curl);
    curl_setopt_array($this->curl, $this->default_options);
    return $raw;
  }
  
  protected function parseHeaders($response)
  {
    $response_headers = [];
    list($response_headers_raw, $raw) = explode("\n\n", $response, 2);
    foreach(explode("\n", $response_headers_raw) as $i=>$line)
    {
      if($i == 0) // Response status line
        list($response_headers['protocol'], $response_headers['code'], $response_headers['status']) = explode(" ", $line, 3);
      else
      {
        list($key, $val) = explode(": ", $line);
        $response_headers[$key] = $val;
      }
    }
    // If this is a Continue, we can't use it until the complete response is sent. I have no idea how to handle this, but this seemed to fix the problems caused by the first time I ever had to deal with code 100.
    if($response_headers['code'] === 100)
    {
      $old_response_headers = $response_headers;
      list($response_headers, $raw) = $this->parseHeaders($raw);
      $response_headers['original-headers'] = $old_response_headers;
    }
    return [$response_headers, $raw];
  }
  
  public function getLastRequestInfo()
  {
    return $this->log[count($this->log)-1]['request_info'];
  }
  
  public function getLastHeaders()
  {
    return $this->log[count($this->log)-1]['response_headers'];
  }
}