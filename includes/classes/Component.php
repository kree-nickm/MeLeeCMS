<?php
namespace MeLeeCMS;

class Component extends Container
{
	public $index;
	public $attrs;
	public $content;
	
	public function __construct($index=0, $attrs=[])
	{
		$this->index = $index;
		$this->attrs = $attrs;
		$this->content = [];
	}
	
	public function get_properties()
	{
		return [
			'index' => [
				'type' => "component",
				'desc' => "The component to insert into the page at this point."
			],
			'attrs' => [
				'type' => "dictionary",
				'desc' => "Attributes that the theme can use to decide how to display the component on this page."
			]
		];
	}
	
	protected function loadComponent()
	{
		$component = $this->cms->database->query("SELECT * FROM `page_components` WHERE `index`=". (int)$this->index, Database::RETURN_ROW);
		if($component['index'])
		{
			$this->title = $component['title'];
			$content = unserialize($component['content']);
			if(is_array($content))
				foreach($content as $x=>$object)
					$this->add_content($object, $x);
			$page_js = json_decode($component['js'], true);
			if(is_array($page_js)) foreach($page_js as $js)
				$this->cms->attach_js($js['file'], "", $js['fromtheme']);
			$page_css = json_decode($component['css'], true);
			if(is_array($page_css)) foreach($page_css as $css)
				$this->cms->attach_css($css['file'], "", $css['fromtheme']);
			$page_xsl = json_decode($component['xsl'], true);
			if(is_array($page_xsl)) foreach($page_xsl as $xsl)
				$this->cms->attach_xsl($xsl);
		}
	}
	
	protected function getSimpleArray()
	{
		$this->loadComponent();
		return parent::getSimpleArray();
	}
}