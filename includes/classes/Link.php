<?php
/** The code for the Link class. */
namespace MeLeeCMS;

class Link extends Content
{
	public $url;
	public $text;
	public $attrs;
   
   public $active;
   public $menu;
   public $external;
   protected $resolved;
	
	public function __construct($url, $text="", $attrs=[])
	{
      $this->external = (bool)preg_match("!^[a-zA-Z]+://!", $url);
		$this->url = $url;
		$this->text = !empty($text) ? $text : $url;
		$this->attrs = $attrs;
      $this->active = false;
      $this->resolved = false;
	}
   
   public function set_cms($cms)
   {
      if(!$this->external && !$this->resolved)
      {
         $url_path = $cms->getSetting('url_path');
         if(substr($this->url, 0, strlen($url_path)) !== $url_path)
         {
            if($this->url{0} == "/")
               $this->url = $url_path . substr($this->url, 1);
            else
               $this->url = $url_path . $this->url;
         }
      }
      $this->setActive($cms->page->url_path == $this->url);
      $this->resolved = true;
      return parent::set_cms($cms);
   }
   
   public function setMenu($menu)
   {
      $this->menu = $menu;
      return $this;
   }
   
   public function setActive($active)
   {
      $this->active = $active;
      if(!empty($this->menu))
         $this->menu->updateActive($active);
      return $this;
   }
	
	public function get_properties()
	{
		return [
			'url' => [
				'type' => "string",
				'desc' => "URL of the link."
			],
			'text' => [
				'type' => "string",
				'desc' => "Text of the link."
			],
			'attrs' => [
				'type' => "dictionary",
				'desc' => "Attributes that the theme can use to decide how to display the link."
			]
		];
	}

	public function build_params()
	{
		$result = [];
		if(is_array($this->attrs))
			foreach($this->attrs as $k=>$v)
				$result["__attr:".$k] = $v;
      $result['url'] = $this->url;
      $result['text'] = $this->text;
      if($this->active)
         $result['__attr:active'] = true;
      return $result;
	}
}
