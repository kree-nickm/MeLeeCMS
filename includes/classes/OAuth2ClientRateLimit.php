<?php
namespace MeLeeCMS;

class OAuth2ClientRateLimit
{
   public $empty;
   public $remaining_header;
   public $reset_header;
   public $reset_type;
   public $limit_header;
   
   protected $response_code;
   protected $requests_remaining;
   protected $reset_timestamp;
   protected $reset_countdown;
   protected $requests_limit;
   
   function __construct($remaining_header=null, $reset_header=null, $reset_type="timestamp", $limit_header=null)
   {
      if($remaining_header === null && $reset_header === null)
      {
         $this->empty = true;
      }
      else
      {
         $this->empty = false;
         $this->remaining_header = $remaining_header;
         $this->reset_header = $reset_header;
         $this->reset_type = $reset_type;
         $this->limit_header = $limit_header;
      }
   }
   
   public function loadHeaders($headers)
   {
      if($this->empty)
         return $this;
      $this->response_code = $headers['code'];
      $this->requests_remaining = $headers[$this->remaining_header];
      if($this->reset_type == "timestamp")
      {
         $this->reset_timestamp = $headers[$this->reset_header];
         $this->reset_countdown = $headers[$this->reset_header] - time();
      }
      else if($this->reset_type == "countdown")
      {
         $this->reset_timestamp = $headers[$this->reset_header] + time();
         $this->reset_countdown = $headers[$this->reset_header];
      }
      if(!empty($this->limit_header))
         $this->requests_limit = $headers[$this->limit_header];
      return $this;
   }
   
   public function isHit()
   {
      return !$this->empty && $this->response_code == 429;
   }
   
   public function waitFor()
   {
      if(!empty($this->reset_countdown))
      {
         sleep($this->reset_countdown);
         return $this->reset_countdown;
      }
      return 0;
   }
}
