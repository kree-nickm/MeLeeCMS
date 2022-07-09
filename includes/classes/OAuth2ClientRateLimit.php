<?php
namespace MeLeeCMS;

class OAuth2ClientRateLimit
{
   public $empty;
   public $remaining_header;
   public $reset_header;
   public $reset_type;
   public $limit_header;
   
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
   
   //public function 
}
