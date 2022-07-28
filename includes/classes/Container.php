<?php
namespace MeLeeCMS;

class Container extends Content
{
	public $title;
	public $attrs;
	public $content;
	
	public function __construct($title="", $attrs=[])
	{
		$this->title = $title;
		$this->attrs = $attrs;
		$this->content = [];
	}
	
	public function get_properties()
	{
		return [
			'title' => [
				'type' => "string",
				'desc' => "Optional title of the container."
			],
			'attrs' => [
				'type' => "dictionary",
				'desc' => "Attributes that the theme can use to decide how to display the container."
			],
			'content' => [
				'type' => "container",
				'desc' => "Content contained by this container."
			]
		];
	}
	
	protected function getSimpleArray()
	{
		$result = ['content'=>[]];
		if(is_array($this->attrs))
			foreach($this->attrs as $k=>$v)
				$result["__attr:".$k] = $v;
		if($this->title != "")
			$result['title'] = $this->title;
		return $result;
	}
	
	public function build_params()
	{
		$result = $this->getSimpleArray();
		foreach($this->content as $c=>$content)
			$result['content@class='.$content->getContentClass().($c?'@id='.$c:'')][] = $content->build_params();
		return $result;
	}
   
   public function findXSLFiles($theme)
   {
      if(!empty($this->attrs['format']))
         $format = $this->attrs['format'];
      else
         $format = "default";
      $content_xsl = [];
      if(!empty($xsl_filepath = $theme->resolveXSLFile($this->getContentClass(), $format)))
         $content_xsl[] = ['href'=>$xsl_filepath];
		foreach($this->content as $c=>$content)
      {
         $content_xsl = array_merge($content_xsl, $content->findXSLFiles($theme));
      }
      return $content_xsl;
   }
	
	public function set_cms($cms)
	{
		$this->cms = $cms;
		if(is_array($this->content)) foreach($this->content as $content)
			$content->set_cms($cms);
		return $this;
	}
	
	public function add_content($content, $x="")
   {
      trigger_error("Container->add_content() is deprecated; use Container->addContent() instead.", E_USER_DEPRECATED);
      return $this->addContent($content, $x);
   }
   
	public function addContent($content, $x="")
	{
		if(is_numeric($x))
			$x = "__". $x;
		else if($x == "")
			$x = "__". count($this->content);
		if(is_a($content, "MeLeeCMS\\Content"))
		{
			return $this->content[$x] = $content->set_cms($this->cms);
		}
		else
		{
			trigger_error("'". get_class($content) ."' is not a subclass of Content, cannot add it as content (". get_class($this) .": ". $this->title .", ". $x .").", E_USER_WARNING);
			return null;
		}
	}
}