<?php
/** The code for the OAuth2\ClientRateLimit class. */
namespace MeLeeCMS\OAuth2;

/**
Handles checks and other logic with OAuth 2.0 API rate limits.

Consolodates the handling of rate limits to a separate class in order to keep the {@see Client} class more tidy.
*/
class ClientRateLimit
{
   /** @var string The name of the response header containing the number of remaining requests in the current bucket for this API. */
   public $remaining_header;
   /** @var string The name of the response header containing the time at which the request count will reset for this API. */
   public $reset_header;
   /** @var string What kind of time the reset response header is returning. Valid values are "timestamp" if it's a UNIX epoch timestamp, or "countdown" if it's the number of second until the reset. */
   public $reset_type;
   /** @var string The name of the response header containing the maximum number of requests in this bucket for this API. */
   public $limit_header;
   
   /** @var bool Whether or not rate limits are being used by the API. If false, all rate limit checking will be bypassed. */
   protected $has_limits;
   /** @var int The HTTP response code of the last API query loaded by {@see ClientRateLimit::loadHeaders()}. */
   protected $response_code;
   /** @var int The number of remaining requests in this bucket, as reported by the last API query loaded by {@see ClientRateLimit::loadHeaders()}. */
   protected $requests_remaining;
   /** @var int The UNIX epoch timestamp when the rate limit resets, as reported by the last API query loaded by {@see ClientRateLimit::loadHeaders()}. Will be calculated if the response header did not report a timestamp. */
   protected $reset_timestamp;
   /** @var int The number of seconds until the rate limit resets, as reported by the last API query loaded by {@see ClientRateLimit::loadHeaders()}. Will be calculated if the response header did not report a countdown. */
   protected $reset_countdown;
   /** @var int The maximum number of requests in this bucket, as reported by the last API query loaded by {@see ClientRateLimit::loadHeaders()}. */
   protected $requests_limit;
   
   /**
   Constructed by a specific API implementation, since different APIs will specify rate limits in different ways.
   
   If `$remaining_header` is `null`, then this instance will assume there are no rate limits and not perform any checks related to them.
   
   @param string $remaining_header The name of the response header containing the number of remaining requests in the current bucket for this API.
   @param string $reset_header The name of the response header containing the time at which the request count will reset for this API.
   @param string $reset_type What kind of time the reset response header is returning. Valid values are "timestamp" if it's a UNIX epoch timestamp, or "countdown" if it's the number of second until the reset.
   @param string $limit_header The name of the response header containing the maximum number of requests in this bucket for this API.
   */
   function __construct($remaining_header=null, $reset_header=null, $reset_type="timestamp", $limit_header=null)
   {
      if($remaining_header === null)
      {
         $this->has_limits = false;
      }
      else if(!empty($remaining_header) && !empty($reset_header) && !empty($reset_type) && !empty($limit_header))
      {
         $this->has_limits = true;
         $this->remaining_header = $remaining_header;
         $this->reset_header = $reset_header;
         $this->reset_type = $reset_type;
         $this->limit_header = $limit_header;
      }
      else
      {
         $this->has_limits = false;
         trigger_error("Invalid arguments given to OAuth2\ClientRateLimit constructor. Must provide either 0 arguments (or a null first argument), or 4 non-empty arguments. Only ". explode(", ", array_keys(array_filter(func_get_args()))) ." arguments provided.", E_USER_ERROR);
      }
   }
   
   /**
   Reads the response headers to find the rate limit data and stores it in this object.
   @param array<string,string> $headers An array of headers as returned by {@see CURLWrapper::getLastHeaders()}.
   @return self
   */
   public function loadHeaders($headers)
   {
      if(!$this->has_limits)
         return $this;
      if(!empty($headers['code']))
         $this->response_code = $headers['code'];
      else
         trigger_error("OAuth2\ClientRateLimit->loadHeaders() was not provided a valid array. Argument must be the parsed headers from a cURL response, with the HTTP status code in the 'code' index.", E_USER_ERROR);
      
      if(!empty($headers[$this->remaining_header]) && !empty($headers[$this->reset_header]) && !empty($headers[$this->limit_header]))
      {
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
         $this->requests_limit = $headers[$this->limit_header];
      }
      else
      {
         trigger_error("Array provided to OAuth2\ClientRateLimit->loadHeaders() did not have the specified rate limit headers.", E_USER_NOTICE);
      }
      return $this;
   }
   
   /**
   Whether or not the last API request failed because we hit the rate limit.
   @return boolean Whether or not the last API request failed because we hit the rate limit.
   */
   public function isHit()
   {
      return $this->has_limits && $this->response_code == 429;
   }
   
   /**
   Pauses PHP execution until the rate limit resets.
   @return int The number of seconds that were waited.
   */
   public function waitFor()
   {
      if(!empty($this->reset_countdown))
      {
         sleep($this->reset_countdown);
         return $this->reset_countdown;
      }
      return 0;
   }
   
   /**
   The number of remaining requests in this bucket.
   @see ClientRateLimit::loadHeaders() Must be called before this object has the appropriate data.
   @uses ClientRateLimit::$requests_remaining The getter for this property.
   @return int The number of remaining requests in this bucket.
   */
   public function getRemaining()
   {
      return $this->requests_remaining;
   }
   
   /**
   The UNIX epoch timestamp when the rate limit resets.
   @see ClientRateLimit::loadHeaders() Must be called before this object has the appropriate data.
   @uses ClientRateLimit::$reset_timestamp The getter for this property.
   @return int The UNIX epoch timestamp when the rate limit resets.
   */
   public function getResetTimestamp()
   {
      return $this->reset_timestamp;
   }
   
   /**
   The number of seconds until the rate limit resets.
   @see ClientRateLimit::loadHeaders() Must be called before this object has the appropriate data.
   @uses ClientRateLimit::$reset_countdown The getter for this property.
   @return int The number of seconds until the rate limit resets.
   */
   public function getResetCountdown()
   {
      return $this->reset_countdown;
   }
   
   /**
   The maximum number of requests in this bucket.
   @see ClientRateLimit::loadHeaders() Must be called before this object has the appropriate data.
   @uses ClientRateLimit::$requests_limit The getter for this property.
   @return int The maximum number of requests in this bucket.
   */
   public function getLimit()
   {
      return $this->requests_limit;
   }
   
   /**
   @link https://www.php.net/manual/en/language.oop5.magic.php#object.tostring Official documentation of this method.
   @return string
   */
   public function __toString()
   {
      return "(". get_class($this) ." instance: ". ($this->has_limits ? "{$this->remaining_header},{$this->reset_header},{$this->limit_header}" : "empty") .")";
   }
}
