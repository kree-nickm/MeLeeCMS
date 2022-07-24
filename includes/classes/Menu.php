<?php
/** The code for the Menu and Link classes.
Normally, classes used by MeLeeCMS each have their own file with the same name as the class. However, because Link is only ever meant to be used by the Menu class, they are declared in the same file (for now). While this means that Link cannot be auto-loaded, it should never be required before the Menu class is loaded.
*/
namespace MeLeeCMS;

class Menu extends Container
{
   public $active;
   public $menu;
   
	public function __construct($title="", $attrs=[])
	{
		$this->title = $title;
		$this->attrs = $attrs;
		$this->content = [];
      $this->active = false;
	}
	
	public function get_properties()
	{
		return [
			'title' => [
				'type' => "string",
				'desc' => "Text to display if the menu is within another menu."
			],
			'attrs' => [
				'type' => "dictionary",
				'desc' => "Attributes that the theme can use to decide how to display the menu."
			],
			'content' => [
				'type' => "container",
				'desc' => "Links contained by this menu."
			]
		];
	}
   
   public function addLink($url, $text="")
   {
      $url_path = $this->cms->getSetting('url_path');
      if(substr($url, 0, strlen($url_path)) !== $url_path)
      {
         if($url{0} == "/")
            $url = $url_path . substr($url, 1);
         else
            $url = $url_path . $url;
      }
      return $this->addContent(new Link($url, $text))
         ->setMenu($this)
         ->setActive($this->cms->page->url_path == $url);
   }
   
   public function addMenu($title)
   {
      return $this->addContent(new Menu($title))->setMenu($this);
   }
   
   public function setMenu($menu)
   {
      $this->menu = $menu;
      return $this;
   }
   
   public function updateActive($active)
   {
      $this->active = $this->active || $active;
      if(!empty($this->menu))
         $this->menu->updateActive($active);
      return $this;
   }
	
	protected function getSimpleArray()
	{
		$result = ['content'=>[]];
		if(is_array($this->attrs))
			foreach($this->attrs as $k=>$v)
				$result["__attr:".$k] = $v;
		$result['title'] = $this->title;
      if($this->active)
         $result['__attr:active'] = true;
      if(empty($this->menu))
         $result['__attr:root'] = true;
		return $result;
	}
}

class Link extends Content
{
	public $url;
	public $text;
	public $attrs;
   
   public $active;
   public $menu;
	
	public function __construct($url, $text="", $attrs=[])
	{
		$this->url = $url;
		$this->text = !empty($text) ? $text : $url;
		$this->attrs = $attrs;
      $this->active = false;
	}
   
   public function setMenu($menu)
   {
      $this->menu = $menu;
      return $this;
   }
   
   public function setActive($active)
   {
      $this->active = $active;
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
