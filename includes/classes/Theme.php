<?php
namespace MeLeeCMS;

class Theme
{
   public $cms;
   public $name;
   public $server_path;
   public $url_path;
   public $description = "";
   public $thumbnail = "";
   public $init_func;
   public $superthemes_raw = [];
   public $superthemes = [];
   public $are_superthemes_resolved = false;
   public $css = [];
   public $js = [];
   public $xsl = [];
   
   function __construct($cms, $directory)
   {
      // Only valid with MeLeeCMS.
      if(!empty($cms))
         $this->cms = $cms;
      else
         throw new \Exception("Theme must be provided a MeLeeCMS reference.");
      
      // TODO: Validate $directory just in case something other than MeLeeCMS sent it in.
      $this->name = $directory;
      $this->server_path = $this->cms->getSetting('server_path') ."themes". DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR;
      $this->url_path = $this->cms->getSetting('url_path') ."themes/". $directory ."/";
      
      if(is_file($description_file = $this->server_path ."description.txt"))
         $this->description = file_get_contents($description_file);
      
      if(is_file($this->server_path . "thumbnail.png"))
         $this->thumbnail = $this->url_path ."thumbnail.png";
      else
         $this->thumbnail = $this->cms->getSetting('url_path') ."themes/default/thumbnail.png";
      
      // TODO: For init.php to return anything but a function (which it should), this will have to be moved to MeLeeCMS->addTheme().
      if(is_file($this->server_path . "init.php"))
         $this->init_func = include($this->server_path ."init.php");
      
      if(is_file($superthemes_file = $this->server_path ."superthemes.txt"))
         $this->superthemes_raw = file($superthemes_file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
      
      // Find CSS
      $css_path = $this->server_path ."css";
      if(is_dir($css_path))
      {
         $css_dir = dir($css_path);
         while(false !== ($file = $css_dir->read()))
         {
            if(substr($file, -4) == ".css")
            {
               $this->css[] = $file;
            }
         }
      }
      
      // Find JS
      $js_path = $this->server_path ."js";
      if(is_dir($js_path))
      {
         $js_dir = dir($js_path);
         while(false !== ($file = $js_dir->read()))
         {
            if(substr($file, -3) == ".js")
            {
               $this->js[] = $file;
            }
         }
      }
      
      // Find XSL
      $template_path = $this->server_path ."templates";
      if(is_dir($template_path))
      {
         $template_dir = dir($template_path);
         while(false !== ($file = $template_dir->read()))
         {
            if(substr($file, -4) == ".xsl")
            {
               $this->xsl[] = $file;
            }
         }
      }
   }
   
   public function init()
   {
      $this->cms->debugLog("ADMIN", "Initializing theme:", $this->name);
      if(!empty($this->init_func) && is_callable($this->init_func))
         $continue = ($this->init_func)($this->cms);
      else
         $continue = true;
      if($continue)
      {
         foreach($this->superthemes as $theme)
         {
            $this->cms->debugLog("ADMIN", "Initializing supertheme:", $theme->name);
            $theme->init();
         }
      }
   }
   
   public function resolveSuperthemes()
   {
      if($this->name != "default")
         $this->cms->addTheme("default");
      foreach($this->superthemes_raw as $supertheme)
      {
         if($supertheme == "default")
         {
            trigger_error("Theme '{$this->name}' has 'default' as a supertheme. Do not do this, as it would break theme chaining, and 'default' is always checked last in the chain regardless. Ignoring it, but you should still remove 'default' from the superthemes.txt file.", E_USER_NOTICE);
         }
         else if($supertheme == $this->name)
         {
            trigger_error("Theme '{$this->name}' has itself in its own superthemes.txt file. Don't. Ignoring it.", E_USER_NOTICE);
         }
         else if(($theme = $this->cms->addTheme($supertheme)) != null)
         {
            $this->superthemes[] = $theme;
         }
         else
         {
            trigger_error("Theme '{$this->name}' has the theme '{$supertheme}' as a supertheme, but '{$supertheme}' does not exist.", E_USER_WARNING);
         }
      }
      $this->are_superthemes_resolved = true;
      foreach($this->superthemes as $theme)
      {
         if(!$theme->are_superthemes_resolved)
            $theme->resolveSuperthemes();
      }
   }
	
	public function resolveFile($directory, $name, $recursion=[])
	{
      if(in_array($this->name, $recursion))
      {
         // Note: Maybe we don't need a warning here? What if ThemeA implements some unique stuff and wants to use ThemeB for the rest, but ThemeB also implements some unique stuff and wants to use ThemeA for the rest. Using one or the other would be different in the cases where they overlap, but the same everywhere else. It's a valid use case in my opinion, but would start logging these errors.
         trigger_error("Infinitely recursive superthemes detected. The chain was '". implode("'->'", $recursion) ."'->'{$this->name}'. While not a fatal error, this should probably still be corrected by editing the themes' superthemes.txt file(s).", E_USER_NOTICE);
         return false;
      }
      else
         $recursion[] = $this->name;
      
		$path = $this->server_path . $directory . DIRECTORY_SEPARATOR;
		if(is_file($path . $name))
      {
         // Since XSLT is all done internally within PHP, we need server path instead of URL path.
         if($directory == "templates")
            return $path . $name;
         else
            return $this->url_path . $directory ."/". $name;
      }
		else if($this->name != "default")
      {
         foreach($this->superthemes as $supertheme)
            if($result = $supertheme->resolveFile($directory, $name, $recursion))
               return $result;
         if(!empty($this->cms->themes['default']))
            if($result = $this->cms->themes['default']->resolveFile($directory, $name, $recursion))
               return $result;
      }
		return false;
	}
	
	public function resolveXSLFile($class, $format="default")
	{
      $result = $this->resolveFile("templates", "{$class}-{$format}.xsl");
      if($result === false && $format != "default")
         $result = $this->resolveFile("templates", "{$class}-default.xsl");
      return $result;
	}
	
	public function parseTemplate($data, $class="MeLeeCMS", $format="default", $added_xsl=[])
	{
      $xsl_file = $this->resolveXSLFile($class, $format);
      if(!empty($xsl_file))
      {
         $transformer = new Transformer();
         $transformer->set_stylesheet("", $xsl_file);
         return $transformer->transform($data, $class, $added_xsl);
      }
      return $data;
	}
}
