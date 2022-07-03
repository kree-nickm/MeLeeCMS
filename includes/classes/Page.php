<?php
namespace MeLeeCMS;

class Page
{
   public $cms;
   public $is_special;
   public $id;
   public $url;
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
         $this->permission = 0;
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
         
         // Now figure out if it's a hard-coded PHP file or a file built with the control panel.
         if(!empty($page_data['file']) && is_file($cms->get_setting("server_path") ."includes". DIRECTORY_SEPARATOR ."pages". DIRECTORY_SEPARATOR . $page_data['file']))
         {
            $this->file = $page_data['file'];
            $this->subtheme = $page_data['subtheme'] ?? "";
            $check = ["title","css","js","xsl","permission","content"];
            $check = array_filter($check, function($val) use($page_data){ return !empty($page_data[$val]); });
            if(count($check))
               trigger_error("Provided data for '". implode("', '", $check) ."' field(s), which are ignored for the file-based page '{$this->id}'. Remove them from the page definition and add them via the file.", E_USER_NOTICE);
            // TODO: There's no reason we can't let the user specify title, css, js, and xsl and add them before including the page. Maybe even permission. A case could even be made for initial content.
         }
         else if(isset($page_data['content']))
         {
            // TODO: Maybe throw an exception if an invalid file was provided?
            if(!empty($page_data['file']))
               trigger_error("File '{$page_data['file']}' was provided for the page, but it was invalid. Content was also provided, so using that instead.", E_USER_WARNING);
            $this->title = $page_data['title'] ?? $page_data['url'] ?? $page_data['id'];
            $this->subtheme = $page_data['subtheme'] ?? "";
            $this->permission = $page_data['permission'] ?? 1;
            $this->content_serialized = $page_data['content'] ?? "";
            // Verify that css, js, and xsl and properly defined.
            foreach(['css','js','xsl'] as $type)
            {
               if(!empty($page_data[$type]))
               {
                  if(is_string($page_data[$type]))
                     $this->$type = json_decode($page_data[$type], true);
                  else if(!is_array($page_data[$type]))
                     $this->$type = [];
               }
               else
                  $this->$type = [];
               if($type != "xsl")
                  foreach($this->$type as $k=>$v)
                     if(is_string($v))
                        $this->$type[$k] = ['file'=>$v, 'fromtheme'=>true];
            }
         }
         else
            throw new \Exception("Pages must have either a 'file' (valid file in includes/pages/ directory) or 'content' (serialized PHP objects or blank) parameter.");
      }
   }
   
   public function unserializeContent()
   {
      if(empty($this->content_serialized))
      {
         trigger_error("No content to unserialize for page '{$this->id}'.", E_USER_NOTICE);
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
		if(!empty($this->file))
		{
			$file = $this->cms->get_setting("server_path") ."includes". DIRECTORY_SEPARATOR ."pages". DIRECTORY_SEPARATOR . $this->file;
			if(is_file($file))
            // Note: Has to be included later, because the file is going to expect a MeLeeCMS instance to be initilized, but it is not at this point, because this function is called during the MeLeeCMS constructor.
				$this->cms->include_later[] = $file;
			else
				trigger_error("Page refers to file {$this->file}, but it does not exist.", E_USER_ERROR);
		}
		else
		{
			$this->cms->set_title($this->title);
			$this->unserializeContent();
			if(is_array($this->content))
				foreach($this->content as $x=>$object)
					$this->cms->add_content($object, $x);
			foreach($this->js as $js)
				$this->cms->attach_js($js['file'], "", $js['fromtheme']);
			foreach($this->css as $css)
				$this->cms->attach_css($css['file'], "", $css['fromtheme']);
			foreach($this->xsl as $xsl)
				$this->cms->attach_xsl($xsl);
		}
   }
}
