<?php

class Data extends Content
{
	public $data;
	public $attrs;
	protected $already_handled;
	
	public function __construct($data="", $attrs=[])
	{
		$this->data = $data;
		$this->attrs = $attrs;
	}
	
	public function get_properties()
	{
		return [
			'data' => [
				'type' => "paragraph",
				'desc' => "Miscellanious data to be included for use by XSLT."
			],
			'attrs' => [
				'type' => "dictionary",
				'desc' => "Attributes that the theme can use to decide how to display the data."
			]
		];
	}
	
	protected function handleData($data, $path)
	{
		
		if(is_array($data))
		{
			foreach($data as $i=>&$d)
			{
				$d = $this->handleData($d, array_merge($path, [$i]));
			}
			return array_filter($data, function($val){return $val !== null;});
		}
		else if(is_object($data))
		{
			$handledKey = "/". implode("/", $path);
			if(($key = array_search($data, $this->already_handled, true)) !== false)
			{
				if(substr($handledKey, 0, strlen($key)) == $key)
					return "!RECURSION:". $key;
				else
					$array = ['__attr:duplicate-of'=>$key];
			}
			else
				$array = [];
			$this->already_handled[$handledKey] = $data;
			foreach(get_object_vars($data) as $p=>$v)
			{
				$array[$p] = $this->handleData($v, array_merge($path, [$p]));
			}
			$array['__attr:object'] = get_class($data);
			return array_filter($array, function($val){return $val !== null;});
		}
		else if(is_resource($data) || gettype($data) == "resource (closed)" || gettype($data) == "unknown type")
		{
			return null;
		}
		else
		{
			return $data;
		}
	}

	public function build_params()
	{
		$this->already_handled = [$this];
		$result = [];
		if(is_array($this->attrs))
			foreach($this->attrs as $k=>$v)
				$result["__attr:".$k] = $v;
		if(count($result))
		{
			array_unshift($result, $this->handleData($this->data, []));
			return $result;
		}
		else
			return $this->handleData($this->data, []);
	}
}