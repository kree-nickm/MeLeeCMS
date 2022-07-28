<?php
/** The code for the Menu class. */
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
      return $this->addContent(new Link($url, $text));
   }
   
   public function addMenu($title)
   {
      return $this->addContent(new Menu($title));
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
   
	public function addContent($content, $x="")
	{
      // TODO: Make a MenuItem interface.
		if(is_a($content, "MeLeeCMS\\Link") || is_a($content, "MeLeeCMS\\Menu"))
		{
         $content->setMenu($this);
         return parent::addContent($content, $x);
		}
		else
		{
			trigger_error("'". get_class($content) ."' is not a subclass of Menu or Link, cannot add it as content (". $x .").", E_USER_WARNING);
			return null;
		}
	}
}
