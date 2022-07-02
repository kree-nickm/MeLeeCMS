<?php

class Theme
{
   public $cms;
   public $name;
   public $server_path;
   public $url_path;
   public $description = "";
   public $thumbnail = "";
   public $superthemes_raw = [];
   public $superthemes = [];
   public $subthemes = [];
   public $css = [];
   public $js = [];
   public $xsl = [];
   
   function __construct($cms, $directory)
   {
      // Only valid with MeLeeCMS.
      if(!empty($cms))
         $this->cms = $cms;
      else
         throw new Exception("Theme must be provided a MeLeeCMS reference.");
      
      // TODO: Validate $directory just in case something other than MeLeeCMS sent it in.
      $this->name = $directory;
      $this->server_path = $this->cms->get_setting('server_path') ."themes". DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR;
      $this->url_path = $this->cms->get_setting('url_path') ."themes/". $directory ."/";
      
      if(is_file($description_file = $this->server_path ."description.txt"))
         $this->description = file_get_contents($description_file);
      
      if(is_file($this->server_path . "thumbnail.png"))
         $this->thumbnail = $this->url_path ."thumbnail.png";
      
      // TODO: We need MeLeeCMS to tell us when themes are loaded before we can begin resolving them, but it would probably be better to do this with recursive Theme creation through MeLeeCMS.
      if(is_file($superthemes_file = $this->server_path ."superthemes.txt"))
      {
         $this->superthemes_raw = file($superthemes_file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
         foreach($this->superthemes_raw as $supertheme)
         {
            if($supertheme == "default")
            {
               trigger_error("Theme '{$this->name}' has 'default' as a supertheme. Do not do this, as it would break theme chaining, and 'default' is always checked last in the chain regardless. Ignoring it, but remove 'default' from the superthemes.txt file.", E_USER_NOTICE);
            }
            else if(!$this->cms->addTheme($supertheme))
            {
               trigger_error("Theme '{$this->name}' has the theme '{$supertheme}' as a supertheme, but '{$supertheme}' does not exist.", E_USER_WARNING);
            }
         }
      }
      
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
            // TODO: More than just the MeLeeCMS XSL file could have a subtheme, but some files might just have a hyphen for no reason.
            if(substr($file, 0, 9) == "MeLeeCMS-" && substr($file, -4) == ".xsl")
            {
               $this->subthemes[] = ['__attr:name'=>substr($file, 9, -4), $file];
            }
            else if(substr($file, -4) == ".xsl")
            {
               $this->xsl[] = $file;
            }
         }
      }
   }
	
	public function hasFile($type, $name, $name_extra="default", $recursion=[])
	{
      if(in_array($this->name, $recursion))
      {
         // Note: Maybe we don't need a warning here? What if ThemeA implements some unique stuff and wants to use ThemeB for the rest, but ThemeB also implements some unique stuff and wants to use ThemeA for the rest. Using one or the other would be different in the cases where they overlap, but the same everywhere else. It's a valid use case in my opinion, but would start logging these errors.
         trigger_error("Infinitely recursive superthemes detected. The chain was '". implode("'->'", $recursion) ."'->'{$this->name}'. While not a fatal error, this should probably still be corrected by editing the themes' superthemes.txt file(s).", E_USER_WARNING);
         return false;
      }
      else
         $recursion[] = $this->name;
      
		$path = $this->server_path . $type . DIRECTORY_SEPARATOR;
      if($type == "templates")
      {
         $fileA = "{$name}-{$name_extra}.xsl";
         $fileB = "{$name}-default.xsl";
      }
      else
         $fileA = $name;
      
		if(is_file($path . $fileA))
			return true;
		else if(!empty($fileB) && is_file($path . $fileB))
			return true;
		else
      {
         foreach($this->superthemes as $supertheme)
            if($supertheme->hasFile($type, $name, $name_extra, $recursion))
               return true;
			return false;
      }
	}
   
   public function resolveFile($type, $name)
   {
		$path = $this->server_path . $type . DIRECTORY_SEPARATOR;
		if(is_file($path . $name))
      {
         return $this->url_path . $type ."/". $name;
      }
		else
      {
         foreach($this->superthemes as $supertheme)
            if($supertheme->hasFile($type, $name, "", [$this->name]))
               return $supertheme->resolveFile($type, $name);
			return "";
      }
   }
	
	public function parseTemplate($data, $class, $subtheme="default", $added_xsl=[], $transformer=null)
	{
      if(empty($transformer))
         $transformer = new Transformer();
		$path = $this->server_path ."templates". DIRECTORY_SEPARATOR;
		if(is_file($file = ($path . $class."-".$subtheme.".xsl")))
      {
			$transformer->set_stylesheet("", $file);
         return $transformer->transform($data, $class, $added_xsl);
      }
		else if(is_file($file = ($path . $class."-default.xsl")))
      {
			$transformer->set_stylesheet("", $file);
         return $transformer->transform($data, $class, $added_xsl);
      }
		else
      {
         foreach($this->superthemes as $supertheme)
            if($supertheme->hasFile("templates", $class, $subtheme, [$this->name]))
               return $supertheme->parseTemplate($data, $class, $subtheme, $added_xsl, $transformer);
			return $data;
      }
		//echo("<!-- ". $class ."-". $subtheme ." => ". $file .": ". print_r($data, true) ." -->");
	}
}
