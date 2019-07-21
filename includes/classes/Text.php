<?php

class Text extends Content
{
	public $text;
	public $attrs;
	
	public function __construct($text="", $attrs=[])
	{
		$this->text = $text;
		$this->attrs = $attrs;
	}
	
	public function get_properties()
	{
		return [
			'text' => [
				'type' => "paragraph",
				'desc' => "HTML to be inserted into the page. Avoid using theme-dependant HTML and CSS. Use attributes and include custom XSL instead."
			],
			'attrs' => [
				'type' => "dictionary",
				'desc' => "Attributes that the theme can use to decide how to display the text."
			]
		];
	}

	public function build_params()
	{
		$result = [];
		if(is_array($this->attrs))
			foreach($this->attrs as $k=>$v)
				$result["__attr:".$k] = $v;
		if(is_array($this->text))
		{
			foreach($this->text as $k=>$v)
				$result[is_numeric($k) ? "__".$k : $k] = $v;
			return $result;
		}
		else
		{
			if(count($result))
			{
				array_unshift($result, $this->text);
				return $result;
			}
			else
				return $this->text;
		}
	}
}