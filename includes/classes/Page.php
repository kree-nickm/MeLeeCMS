<?php
namespace MeLeeCMS;

/**
Manages all of the data that a page needs in order to be rendered to the user.
*/
class Page
{
   public $cms;
   public $is_special;
   public $is_cpanel = false;
   public $id;
   public $url;
   public $url_path;
   public $select;
   public $file;
   public $theme;
   public $subtheme;
   public $permission;
   public $content;
   public $content_serialized;
   public $css;
   public $js;
   public $xsl;
   public $args = [];
   
   function __construct($cms, $page_data)
   {
      // Only valid with MeLeeCMS.
      if(!empty($cms))
         $this->cms = $cms;
      else
         throw new \Exception("Page must be provided a MeLeeCMS reference.");
      
      // Special case for generating a generic error page, when $page_data is explicitly false
      if($page_data === false)
      {
         $this->is_special = true;
         $this->id = null;
         $this->select = function(){ return true; };
         $this->subtheme = "default";
         $this->content_serialized = "a:1:{s:7:\"content\";O:9:\"Container\":3:{s:5:\"title\";s:17:\"An Error Occurred\";s:5:\"attrs\";a:0:{}s:7:\"content\";a:1:{s:4:\"text\";O:4:\"Text\":2:{s:4:\"text\";s:45:\"An unknown error occurred. Response code ". http_response_code() .".\";s:5:\"attrs\";a:0:{}}}}}";
         $this->title = http_response_code() ." Error";
         $this->permission = "";
         $this->css = [];
         $this->js = [];
         $this->xsl = [];
      }
      else
      {
         // Figure out if it's a normal page, a special page, or invalid.
         if(!empty($page_data['url']) && is_string($page_data['url'])) // TODO: Check for valid URL characters, not just "is_string".
         {
            $this->is_special = false;
            $this->id = $page_data['url'];
            $this->url = $page_data['url'];
            $url_path = $cms->getSetting('url_path');
            if(substr($page_data['url'], 0, strlen($url_path)) != $url_path)
            {
               if($page_data['url']{0} == "/")
                  $this->url_path = $url_path . substr($page_data['url'], 1);
               else
                  $this->url_path = $url_path . $page_data['url'];
            }
            else
               $this->url_path = $page_data['url'];
            if(substr($this->url_path, 0, strlen($url_path . $cms->getSetting('cpanel_dir') ."/")) == $url_path . $cms->getSetting('cpanel_dir') ."/")
               $this->is_cpanel = true;
         }
         else if(!empty($page_data['select']) && is_callable($page_data['select']) && !empty($page_data['id']))
         {
            $this->is_special = true;
            $this->id = $page_data['id'];
            $this->select = $page_data['select'];
         }
         else
         {
            throw new \Exception("Pages must have either a 'url' (non-empty string), or both an 'id' (any) and 'select' (function) parameter.");
         }
         
         // TODO: These checks aren't very comprehensive, just thrown together.
         $url_parts = explode("/", $this->url);
         if($url_parts[0] == "themes" || $url_parts[0] == "addons")
         {
            throw new \Exception("Page is attempting to use '{$this->url}' as its URL, but that is taken by a MeLeeCMS directory.");
         }
         else if($this->url == $cms->getSetting('cpanel_dir'))
         {
            throw new \Exception("Page is attempting to use '{$this->url}' as its URL, but that is the same as the control panel directory. One of them must be changed.");
         }
         else if($url_parts[0] == "includes")
         {
            trigger_error("Page is using '{$this->url}' as its URL, which matches the MeLeeCMS private directory. However, since attempts to access that directory from the web are redirected, this will technically still work, but it is not recommended in case private directory behavior changes in the future.", E_USER_WARNING);
         }
         
         // Set up the page with any initial data that has been provided.
         $this->title = !empty($page_data['title']) ? $page_data['title'] : (!empty($page_data['url']) ? ucfirst($page_data['url']) : $page_data['id']);
         $this->subtheme = !empty($page_data['subtheme']) ? $page_data['subtheme'] : "";
         $this->permission = !empty($page_data['permission']) ? $page_data['permission'] : "view_pages";
         foreach(['css','js','xsl'] as $type)
         {
            // Set $this->$type to something appropriate.
            if(!empty($page_data[$type]))
            {
               if(is_string($page_data[$type]))
               {
                  $decoded = json_decode($page_data[$type], true);
                  if(is_array($decoded))
                     $this->$type = $decoded;
                  else
                     $this->$type = array_filter(preg_split("![,;|&+]!", $page_data[$type]));
               }
               else if(is_array($page_data[$type]))
                  $this->$type = $page_data[$type];
               else
                  $this->$type = [];
            }
            else
               $this->$type = [];
            // Make sure $this->$type is formatted correctly.
            foreach($this->$type as $k=>$v)
               if(is_string($v))
                  $this->$type[$k] = [($type=="js"?'src':'href')=>$v, 'fromtheme'=>true];
         }
         $this->content_serialized = !empty($page_data['content']) ? $page_data['content'] : "";
         
         // Now figure out if it's a hard-coded PHP file or a file built with the control panel.
         if(!empty($page_data['file']))
         {
            if(is_file($cms->getSetting("server_path") ."includes". DIRECTORY_SEPARATOR ."pages". DIRECTORY_SEPARATOR . $page_data['file']))
               $this->file = $page_data['file'];
            else
               trigger_error("Invalid file '{$page_data['file']}' was provided for the page '{$this->id}'.", E_USER_WARNING);
         }
         else if(!isset($page_data['content']))
            throw new \Exception("Pages must have either a 'file' (valid file in includes/pages/ directory) or 'content' (serialized PHP objects or blank) parameter.");
      }
   }
   
   public function getPermission()
   {
      // TODO: Move all the validation to setPermission() so it only has to be done once.
      if(is_string($this->permission))
      {
         $decoded = json_decode($this->permission, true);
         if(is_array($decoded))
            return array_filter($decoded);
         else
            return array_filter(preg_split("![,./;|&+]!", $this->permission));
      }
      else if(is_array($this->permission))
         return array_filter($this->permission);
      else
      {
         trigger_error("Page '{$this->id}' permissions not defined correctly. Must be an array, or a string with each permission separated by one of these \",./;|&+\". Instead, we have this: ". $this->permission, E_USER_ERROR);
         return ["ADMIN"];
      }
   }
   
   public function unserializeContent()
   {
      if(empty($this->content_serialized))
      {
         return false;
      }
      else if(!empty($this->content))
      {
         trigger_error("Content already unserialized for page '{$this->id}'.", E_USER_NOTICE);
         return false;
      }
      else
      {
			$this->content = unserialize($this->content_serialized);
         return !empty($this->content);
      }
   }
   
   public function loadToCMS()
   {
      $this->cms->setTitle($this->title);
      $this->unserializeContent();
      if(is_array($this->content))
         foreach($this->content as $x=>$object)
            $this->cms->addContent($object, $x);
      foreach($this->js as $js)
         $this->cms->attachJS($js['file'], "", $js['fromtheme']);
      foreach($this->css as $css)
         $this->cms->attachCSS($css['file'], "", $css['fromtheme']);
      foreach($this->xsl as $xsl)
         $this->cms->attachXSL($xsl);
      
		if(!empty($this->file))
		{
			$file = $this->cms->getSetting("server_path") ."includes". DIRECTORY_SEPARATOR ."pages". DIRECTORY_SEPARATOR . $this->file;
			if(is_file($file))
            // Note: Has to be included later, because the file is going to expect a MeLeeCMS instance to be initilized, but it is not at this point, because this function is called during the MeLeeCMS constructor.
				$this->cms->include_later[] = $file;
			else
				trigger_error("Page refers to file {$this->file}, but it does not exist.", E_USER_ERROR);
		}
   }
}
