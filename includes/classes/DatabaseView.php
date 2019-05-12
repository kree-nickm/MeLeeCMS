<?

class DatabaseView extends Content
{
	public $table;
	public $config;
	public $attrs;
	protected $data = [];
	
	public function __construct($table="", $config=['columns'=>[]], $attrs=[])
	{
		$this->table = $table;
		$this->config = $config;
		$this->attrs = $attrs;
	}
	
	public function set_cms($cms)
	{
		$this->cms = $cms;
		$this->loadData();
		return $this;
	}
	
	public function loadData()
	{
		if(is_object($this->cms) && is_object($this->cms->database))
		{
			if(is_array($this->cms->database->metadata[$this->table]))
			{
				$columns = "*";
				if(is_array($this->config) && is_array($this->config['columns']))
				{
					$temp = array_filter($this->config['columns'], function($val){return $val!="index " && is_array($this->cms->database->metadata[$this->table][$val]);});
					if(count($temp))
						$columns = "`". implode("`,`", $temp) ."`";
				}
				$this->data = $this->cms->database->query("SELECT ". $columns ." FROM ". $this->table ." WHERE 1", Database::RETURN_ALL);
			}
			else
				$this->data = [];
		}
	}
	
	public function get_properties()
	{
		return array(
			'table' => array(
				'type' => "database_table",
				'desc' => "Table of the database to be viewed."
			),
			'config' => array(
				// Needs to be here so it can be serialized, but can be empty since it doesn't appear by itself on the control panel.
			),
			'attrs' => array(
				'type' => "dictionary",
				'desc' => "Attributes that the theme can use to decide how to display the table."
			)
		);
	}

	public function build_params()
	{
		$result = ['row'=>[]];
		if(is_array($this->attrs))
			foreach($this->attrs as $k=>$v)
				$result["__attr:".$k] = $v;
		if($this->table != "")
			$result['table'] = $this->table;
		foreach($this->data as $row)
		{
			$result['row'][] = $row;
		}
		return $result;
	}
}