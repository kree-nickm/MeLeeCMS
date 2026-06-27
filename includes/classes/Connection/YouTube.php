<?php
namespace MeLeeCMS\Connection;
use MeLeeCMS\OAuth2\Client;
use MeLeeCMS\OAuth2\ClientRateLimit;
use MeLeeCMS\Database;

class YouTube extends Connection
{
  const NAME = "YouTube";
  const AUTH_URL = "https://accounts.google.com/o/oauth2/v2/auth";
  const TOKEN_URL = "https://oauth2.googleapis.com/token";
  const API_URL = "https://www.googleapis.com";
  //const RATELIMIT_REMAINING = "";
  //const RATELIMIT_LIMIT = "";
  //const RATELIMIT_RESET = "";
  //const RATELIMIT_TYPE = ClientRateLimit::RESET_TIMESTAMP;
  
  protected function onConnect()
  {
  }
  
  public function loadAPISelf()
  {
    // If we have an ID already, use it to get user data.
    if(false)
    {
    }
    // No ID yet, so use the first result from a user API query, which should be us.
    else
    {
      $channels = $this->request("/youtube/v3/channels?mine=true&part=id,snippet");
      if(!empty($channels->items[0]))
      {
        $this->api_id = $channels->items[0]->id;
        $this->api_self = $channels->items[0];
        return true;
      }
    }
    return false;
  }

  public function getDisplayName()
  {
    if(!empty($this->api_self->snippet->title))
      return $this->api_self->snippet->title;
    else
      return parent::getDisplayName();
  }
}
