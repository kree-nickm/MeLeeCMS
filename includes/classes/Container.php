<?

class Container extends Content
{
	public $title;
	public $attrs;
	public $content;
	
	public function __construct($title="", $attrs=array())
	{
		$this->title = $title;
		$this->attrs = $attrs;
		$this->content = array();
	}
	
	public function get_properties()
	{
		return array(
			'title' => array(
				'type' => "string",
				'desc' => "Optional title of the container."
			),
			'attrs' => array(
				'type' => "dictionary",
				'desc' => "Attributes that the theme can use to decide how to display the container."
			),
			'content' => array(
				'type' => "container",
				'desc' => "Content contained by this container."
			)
		);
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
			$result['content@class='.get_class($content).($c?'@id='.$c:'')][] = $content->build_params();
		return $result;
	}

	public function render($subtheme="default")
	{
		$params = $this->getSimpleArray();
		foreach($this->content as $c=>$content)
			$params['content@class='.get_class($content).($c?'@id='.$c:'')][] = $content->render($subtheme);
		return $this->cms->parse_template($params, get_class($this), $subtheme);
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
		if(is_numeric($x))
			$x = "__". $x;
		else if($x == "")
			$x = "__". count($this->content);
		if(is_subclass_of($content, "Content"))
		{
			return $this->content[$x] = $content->set_cms($this->cms);
		}
		else
		{
			error_log("'". get_class($content) ."' is not a subclass of Content, cannot add it as content (". get_class($this) .": ". $this->title .", ". $x .").");
			return null;
		}
	}
}