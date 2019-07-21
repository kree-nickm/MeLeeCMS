<?php

class DatabaseView extends Content
{
	public $table;
	public $config;
	public $attrs;
	public $parent_data = [];
	protected $data = [];
	protected $full_count = 0;
	
	public function __construct($table="", $config=[], $attrs=[])
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
	
	public function processFilters($filters)
	{
		//$this->cms->add_content(new Text("<pre>". print_r(array_keys($filters),true) ."</pre>"));
		$where = [];
		$conj = " AND ";
		foreach($filters as $i=>$filter)
		{
			//$this->cms->add_content(new Text("<pre>". $i ." => ". print_r($filter,true) ."</pre>"));
			if((string)$i == "subgroup")
			{
				//$this->cms->add_content(new Text("conj"));
				if($filter == "or")
					$conj = " OR ";
			}
			else if(is_array($filter) && isset($filter['value']) && $filter['column'] != "index " && is_array($this->cms->database->metadata[$this->table][$filter['column']]))
			{
				//$this->cms->add_content(new Text("filter"));
				$type = $this->cms->database->metadata[$this->table][$filter['column']]['type_basic'];
				if(
					//($type == "integer" || $type == "decimal") && in_array($filter['comparator'], [])
					//||
					//($type == "text") && in_array($filter['comparator'], [])
					//||
					in_array($filter['comparator'], ["=","!=",">","<",">=","<=","IN","LIKE","BETWEEN"])
				)
				{
					if($filter['type'] == "get")
						$value = $_GET[$filter['value']];
					else if($filter['type'] == "post")
						$value = $_POST[$filter['value']];
					else if($filter['type'] == "request")
						$value = $_REQUEST[$filter['value']];
					else if($filter['type'] == "parent")
						$value = $this->parent_data[$filter['value']];
					else
						$value = $filter['value'];
					
					if($filter['comparator'] == "LIKE")
						$filter['comparator'] = "LIKE ";
					
					if($filter['comparator'] == "BETWEEN" && is_array($value))
					{
						$where[] = "`". $filter['column'] ."`BETWEEN ". $this->cms->database->smart_quote($this->table, $filter['column'], $value[0]) ." AND ". $this->cms->database->smart_quote($this->table, $filter['column'], $value[1]);
					}
					else
					{
						if(is_array($value))
						{
							array_walk($value, function(&$v,$k,$obj)use($filter){ $v=$obj->cms->database->smart_quote($obj->table, $filter['column'],$v); }, $this);
							$value = "(". implode(",",$value) .")";
						}
						else
							$value = $this->cms->database->smart_quote($this->table, $filter['column'], $value);
						
						$where[] = "`". $filter['column'] ."`". $filter['comparator'] . $value;
					}
					
				}
			}
			else if(is_array($filter) && !empty($filter['subgroup']))
			{
				//$this->cms->add_content(new Text("subgroup"));
				if(!empty($processed = $this->processFilters($filter)))
					$where[] = $processed;
			}
			else
			{
				//$this->cms->add_content(new Text("nothing"));
			}
		}
		return count($where) ? "(". implode($conj, $where) .")" : "";
	}
	
	public function loadData()
	{
		if(is_object($this->cms) && is_object($this->cms->database))
		{
			if(is_array($this->cms->database->metadata[$this->table]))
			{
				$columns = "*";
				if(is_array($this->config))
				{
					if(is_array($this->config['columns']))
					{
						$outputs = [];
						$all = false;
						foreach($this->config['columns'] as $column)
						{
							if($column == "*")
								$all = true;
							else if($column['name'] != "index " && is_array($this->cms->database->metadata[$this->table][$column['name']]))
							{
								$outputs[$column['name']] = $column['output'];
							}
						}
						if(!$all && count($outputs))
							$columns = "`". implode("`,`", array_keys($outputs)) ."`";
					}
					if(is_array($this->config['filters']))
					{
						if(!empty($processed = $this->processFilters($this->config['filters'])))
							$where = " WHERE ". $processed;
					}
					if(is_array($this->config['order']))
					{
						if(is_array($this->cms->database->metadata[$this->table][$this->config['order'][0]]))
						{
							if(!empty($this->config['order'][1]) && substr($this->config['order'][1], 0, 1) == "d")
								$order = " ORDER BY `". $this->config['order'][0] ."` DESC";
							else
								$order = " ORDER BY `". $this->config['order'][0] ."` ASC";
						}
					}
					if(is_array($this->config['limit']))
					{
						$this->config['limit'][0] = (int)$this->config['limit'][0];
						$this->config['limit'][1] = (int)$this->config['limit'][1];
						if($this->config['limit'][0] >= 0 && $this->config['limit'][1] > 0)
							$limit = " LIMIT ". $this->config['limit'][0] .",". $this->config['limit'][1];
					}
				}
				$query = "SELECT ". $columns ." FROM ". $this->table . (!empty($where) ? $where : "") . (!empty($order) ? $order : "") . (!empty($limit) ? $limit : "");
				$query_count = "SELECT COUNT(*) FROM ". $this->table . (!empty($where) ? $where : "");
				$this->data = $this->cms->database->query($query, Database::RETURN_ALL);
				$this->full_count = $this->cms->database->query($query_count, Database::RETURN_FIELD);
				$child_data = [];
				foreach($this->data as $i=>$row)
				{
					foreach($row as $col=>$val)
					{
						if($outputs[$col] instanceof DatabaseView)
						{
							if(empty($child_data[$col][$val]))
							{
								$outputs[$col]->parent_data = $this->data[$i];
								$child_data[$col][$val] = $outputs[$col]->set_cms($this->cms)->build_params();
							}
							$this->data[$i][$col] = ['__attr:original'=>$val, $child_data[$col][$val]];
						}
						else if($outputs[$col] == "json")
						{
							$this->data[$i][$col] = json_decode($val, true);
						}
						else if(substr($outputs[$col], 0, 5) == "date:")
						{
							$this->data[$i][$col] = ['__attr:original'=>$val, date(substr($outputs[$col], 5), $val)];
						}
					}
				}
				//$this->data['query '] = $query;
			}
			else
				$this->data = [];
		}
	}
	
	public function get_properties()
	{
		return [
			'table' => [
				'type' => "database_table",
				'desc' => "Table of the database to be viewed."
			],
			'config' => [
				// Needs to be here so it can be serialized, but can be empty since it doesn't appear by itself on the control panel.
			],
			'attrs' => [
				'type' => "dictionary",
				'desc' => "Attributes that the theme can use to decide how to display the table."
			]
		];
	}

	public function build_params()
	{
		$result = ['row'=>[]];
		if(is_array($this->attrs))
			foreach($this->attrs as $k=>$v)
				$result["__attr:".$k] = $v;
		if($this->table != "")
			$result['table'] = $this->table;
		if(is_array($this->data))
		{
			$result['count'] = $this->full_count;
			if(is_array($this->config['limit']))
			{
				$result['start'] = $this->config['limit'][0];
				$result['limit'] = $this->config['limit'][1];
				$result['end'] = $this->config['limit'][0] + min($this->config['limit'][1], count($this->data)) - 1;
			}
			foreach($this->data as $row)
			{
				$result['row'][] = $row;
			}
		}
		return $result;
	}
}