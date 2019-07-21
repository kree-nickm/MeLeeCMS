<?php

class Database
{
	const RETURN_NONE = 0;
	const RETURN_FIELD = 1;
	const RETURN_ROW = 2;
	const RETURN_ALL = 3;
	const RETURN_COLUMN = 4;
	const RETURN_COUNT = 5;

	/** This will be used in the metadata array to designate where the table indexes will be stored. Cannot match an existing column in any table of the database. The default is based on the assumption that MySQL columns cannot have spaces in them, but array indexes can. */
	const INDEX_KEY = "index ";

	protected static $basic_types = [
		'tinyint' => "integer",
		'smallint' => "integer",
		'mediumint' => "integer",
		'int' => "integer",
		'integer' => "integer",
		'bigint' => "integer",
		'bit' => "integer",
		'year' => "integer",
		'decimal' => "decimal",
		'numeric' => "decimal",
		'float' => "decimal",
		'double' => "decimal",
		'binary' => "binary",
		'varbinary' => "binary",
		'tinyblob' => "binary",
		'blob' => "binary",
		'mediumblob' => "binary",
		'longblob' => "binary",
	];
	
	protected $database;
	protected $pdo;
	protected $cms;
	public $metadata = [];
	public $error = [];
	
	public function __construct($type, $host, $database, $user, $pass, $cms=null)
	{
		$this->database = $database;
		$this->cms = $cms;
		$this->pdo = new PDO($type .":host=". $host .";dbname=". $database .";charset=utf8", $user, $pass);
		if($this->pdo)
			$this->refresh_metadata();
	}
	
	/**
	 * Gets all of the needed metadata from the INFORMATION_SCHEMA for this database.
	 * 
	 * This is called when the object is instantiated. It should only need to be manually called if a query is run that changes the table metadata in this database.
	 */
	public function refresh_metadata()
	{
		// TODO generate errors for tables with no PRIMARY key that is also AUTO_INCREMENT
		$this->metadata = [];
		$columns = $this->query("SELECT TABLE_NAME,COLUMN_NAME,COLUMN_DEFAULT,DATA_TYPE,COLUMN_TYPE,COLUMN_KEY,EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=". $this->quote($this->database) ." ORDER BY TABLE_NAME");
		foreach($columns as $row)
		{
			if(empty($this->metadata[$row['TABLE_NAME']]))
				$this->metadata[$row['TABLE_NAME']] = [];
			if(empty($this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']]))
				$this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']] = [];
			$this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']]['default'] = $row['COLUMN_DEFAULT'];
			$this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']]['type'] = $row['DATA_TYPE'];
			$this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']]['type_full'] = $row['COLUMN_TYPE'];
			$this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']]['type_basic'] = !empty(self::$basic_types[$row['DATA_TYPE']]) ? self::$basic_types[$row['DATA_TYPE']] : "text";
			$this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']]['key'] = $row['COLUMN_KEY'];
			$this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']]['extra'] = $row['EXTRA'];
		}
		foreach(array_keys($this->metadata) as $table)
		{
			$this->metadata[$table][self::INDEX_KEY] = [];
			$indexes = $this->query("SHOW INDEX FROM ". $table ." WHERE Non_unique=0");
			foreach($indexes as $row)
			{
				if(empty($this->metadata[$table][self::INDEX_KEY][$row['Key_name']]))
					$this->metadata[$table][self::INDEX_KEY][$row['Key_name']] = [];
				if(empty($this->metadata[$table][self::INDEX_KEY][$row['Key_name']][$row['Seq_in_index']]))
					$this->metadata[$table][self::INDEX_KEY][$row['Key_name']][$row['Seq_in_index']] = [];
				$this->metadata[$table][self::INDEX_KEY][$row['Key_name']][$row['Seq_in_index']]['column'] = $row['Column_name'];
				$this->metadata[$table][self::INDEX_KEY][$row['Key_name']][$row['Seq_in_index']]['substr'] = $row['Sub_part'];
			}
			foreach(array_keys($this->metadata[$table][self::INDEX_KEY]) as $key)
				ksort($this->metadata[$table][self::INDEX_KEY][$key]);
		}
	}
	
	/**
	 * Calls the quote function of the stored PDO object.
	 * 
	 * See: http://php.net/manual/en/pdo.quote.php
	 * 
	 * @param string $string The string to be quoted.
	 * @param string $type Provides a data type hint for drivers that have alternate quoting styles.
	 * @return boolean|string A quoted string that is theoretically safe to pass into an SQL statement. Returns __FALSE__ if the driver does not support quoting in this way.
	 */
	public function quote($string, $type=PDO::PARAM_STR)
	{
		return $this->pdo->quote($string, $type);
	}
	
	/**
	 * Runs the PDO query and returns an array with the result from the database depending on the value of the second parameter.
	 * 
	 * Simlifies the amount of code needed to cleanly run a query on a database using PDO. The function handles the PDOStatement as well as error logging if the query fails. The `$this->error` property will be updated with the latest `PDO::errorInfo()` after this function has finished. You can specify `$result` to indicate what you want this function to return: a single field (string), a single row or column (one-dimensional array), the entire result set (two-dimensional array), or simply a boolean indicating whether an error occurred. If you do not specify `$result`, the function will attempt to guess based on what is returned from the database: if nothing is returned from the database, this function will return a boolean; if anything is returned from the database, this function will return that result in a two-dimensional array.
	 * 
	 * @param string $string The database query string. It is taken as-is and passed directly to the database, so make sure you have properly escaped it so that it is safe to run.
	 * @param int $result One of the following:
	 * + `Database::RETURN_NONE` Indicates that you don't need anything returned from the database, as in UPDATE, INSERT, DELETE, etc. statements.
	 * + `Database::RETURN_FIELD` Indicates that you only want a single value, as in a SELECT statement with only one column selected and a WHERE or LIMIT clause ensuring only one row is found by the database.
	 * + `Database::RETURN_ROW` Indicates that you only want a single row, as in a SELECT statement with a WHERE or LIMIT clause ensuring only one row is found by the database.
	 * + `Database::RETURN_COLUMN` Indicates that you only want a single column but multiple rows, as in a SELECT statement with only one column selected.
	 * + `Database::RETURN_ALL` Indicates that you want every result that the database returns, as in a typical SELECT statement.
	 * + `Database::RETURN_COUNT` Indicates that you want a count of rows affected by the last query. Note that this uses the `PDOStatement::rowCount()` method, which has stipulations about when it will actually work. Notably, it is only meant to work on DELETE, INSERT, or UPDATE statements, but even then, might not return correctly depending on the database type.
	 * 
	 * If you do not specify one of those values, the function will attempt to guess which one you want.
	 * @return boolean|string|string[]|array[] Either a boolean indicating the success of the query, or some part of the result returned from the database after the query. See above for details.
	 */
	public function query($string, $result=-1, $col=0)
	{
		$sta = $this->pdo->query($string);
		if($sta)
		{
			$this->error = $this->pdo->errorInfo();
			switch($result)
			{
				case self::RETURN_NONE:
					$return = !is_array($this->error) || $this->error[0] == "00000";
					break;
				case self::RETURN_COUNT:
					$return = $sta->rowCount();
					break;
				case self::RETURN_FIELD:
					$return = $sta->fetch(PDO::FETCH_NUM);
					if(is_array($return))
						$return = $return[0];
					else
						$return = false;
					break;
				case self::RETURN_ROW:
					$return = $sta->fetch(PDO::FETCH_ASSOC);
					if(!is_array($return))
						$return = false;
					break;
				case self::RETURN_COLUMN:
					$return = $sta->fetchAll(PDO::FETCH_BOTH);
					if(!is_array($return))
						$return = false;
					else
						// Note: Don't know if we should care about this, but using array_column() means we require PHP>=5.5.0
						$return = array_column($return, $col);
					break;
				case self::RETURN_ALL:
					$return = $sta->fetchAll(PDO::FETCH_ASSOC);
					if(!is_array($return))
						$return = false;
					break;
				default:
					$cols = $sta->columnCount();
					if($cols == 0)
						$return = true;
					else
					{
						$return = $sta->fetchAll(PDO::FETCH_ASSOC);
						if(!is_array($return))
							$return = false;
					}
					break;
			}
			$sta->closeCursor();
			return $return;
		}
		else
		{
			$this->error = $this->pdo->errorInfo();
			trigger_error("PDO query() failed, error info as follows: SQLSTATE=". $this->error[0] . ($this->error[1]!=null ? "; (". $this->error[1] .") Message: ". $this->error[2] : "; No driver-specific info.") ."\n\tQuery: ". $string, E_USER_WARNING);
			return false;
		}
	}
	
	public function isRowSet($table, $mysql_data)
	{
		// This is find as long as we enforce that $mysql_data is either an array of scalar values (for one row), or a two-dimensional array of scalar values (for multiple rows).
		return is_array(current($mysql_data));
		/*reset($mysql_data);
		if(key($mysql_data) !== 0)
			return false;
		else
			return is_array($mysql_data[0]);*/
	}
	
	/*
	 * Returns a column identifier based on the tables given in the statement and the desired column, or null if it's invalid. If the column appears in multiple of the given tables, the last one takes precidence. A specific table can also be specified, and the function will return null if it doesn't match anything passed in the first argument.
	 */
	public function getValidColumn($table, $column, $returnArray=false)
	{
		$return = null;
		if(is_array($column))
		{
			if(is_array($table) && in_array($column[0], $table) && $column[1] != self::INDEX_KEY && !empty($this->metadata[$column[0]][$column[1]]))
			{
				if($returnArray)
					$return = [$column[0], $column[1]];
				else
					$return = "`". $column[0] ."`.". ($column[1]=="*" ? "*" : "`".$column[1]."`");
			}
			else if(!is_array($table) && $column[0] == $table && $column[1] != self::INDEX_KEY && !empty($this->metadata[$column[0]][$column[1]]))
			{
				if($returnArray)
					$return = [$column[0], $column[1]];
				else
					$return = ($column[1]=="*" ? "*" : "`".$column[1]."`");
			}
		}
		else if(is_array($table))
		{
			foreach($table as $t)
				if($column != self::INDEX_KEY && !empty($this->metadata[$t][$column]))
				{
					if($returnArray)
						$return = [$t, $column];
					else
						$return = "`". $t ."`.". ($column=="*" ? "*" : "`".$column."`");
				}
		}
		else if($column != self::INDEX_KEY && !empty($this->metadata[$table][$column]))
		{
			if($returnArray)
				$return = [$table, $column];
			else
				$return = ($column=="*" ? "*" : "`".$column."`");
		}
		return $return;
	}
	
	/**
	 * Build valid MySQL clauses from the given parameters.
	 * 
	 * Returns part of a WHERE clause built from the input array that consists only of complete table indexes. For example, an auto_increment column would be in this clause. Also if there's a unique index involving two columns, then there would be a clause in here with those two columns if they are both in the input array.
	 */
	public function buildWhereUnique($table, $mysql_data)
	{
		if(!$this->isRowSet($table, $mysql_data))
			$mysql_data = [$mysql_data];
		$or_clause = [];
		foreach($mysql_data as $row)
		{
			foreach($this->metadata[$table][self::INDEX_KEY] as $index=>$columns)
			{
				$and_clause = []; // This will build the union of all columns that are part of a multi-column index.
				foreach($columns as $seq=>$options)
				{
					if(!empty($this->metadata[$table][$options['column']]))
					{
						if(isset($row[$options['column']]))
						{
							if(!empty($options['substr']))
								$and_clause[] = "LEFT(`". $options['column'] ."`,". (int)$options['substr'] .")=LEFT(". $this->smart_quote($table, $options['column'], $row[$options['column']]) .",". (int)$options['substr'] .")";
							else
								$and_clause[] = "`". $options['column'] ."`=". $this->smart_quote($table, $options['column'], $row[$options['column']]);
						}
						else
						{
							// TODO use default values, but not if there's another unique index, in which case we need to get that row and use its values instead. Also have to factor in whether the column has a valid default value (for example, AUTO_INCREMENT columns will not).
							$and_clause = null;
							break;
						}
					}
					else
					{
						trigger_error("Invalid column '". $options['column'] ."' is part of a column index in MySQL for table '". $table ."'.", E_USER_WARNING);
					}
				}
				if(is_array($and_clause))
					$or_clause[] = "(". implode(" AND ", $and_clause) .")";
			}
		}
		if(count($or_clause))
			return "(". implode(" OR ", $or_clause) .")";
		else
			return "";
	}
	
	/**
	 * Removes invalid columns from the input data set, and returns an array of the invalid column names.
	 */
	public function validateColumns($table, &$mysql_data)
	{
		$result = [];
		if($this->isRowSet($table, $mysql_data))
		{
			foreach($mysql_data as $i=>$row)
			{
				foreach($row as $column=>$value)
				{
					if(empty($this->metadata[$table][$column]))
					{
						$result[] = $column;
						unset($mysql_data[$i][$column]);
					}
				}
			}
		}
		else
		{
			foreach($mysql_data as $column=>$value)
			{
				if(empty($this->metadata[$table][$column]))
				{
					$result[] = $column;
					unset($mysql_data[$column]);
				}
			}
		}
		return $result;
	}
	
	/**
	 * Builds the part of the MySQL INSERT query string that comes after ON DUPLICATE KEY UPDATE.
	 */
	public function buildODKUpdate($table, $mysql_data, $leave_cols)
	{
		if($this->isRowSet($table, $mysql_data))
			$columns = array_keys($mysql_data[0]);
		else
			$columns = array_keys($mysql_data);
		$update_clause = [];
		foreach($columns as $column)
			if(!empty($this->metadata[$table][$column]) && !in_array($column, $leave_cols))
				$update_clause[] = "`". $column ."`=VALUES(`". $column ."`)";
		return implode(",", $update_clause);
	}
	
	/**
	 * Builds the part of the MySQL INSERT query string that comes after the table name.
	 * 
	 * Takes the given data array for the given table, and builds the parinthized list of columns, followed by the word VALUES, then the parinthized list of values to insert. Supports multiple-row inserts. The elements of the data array must have identical keys, or MySQL will generate an error.
	 */
	public function buildInsertClause($table, $mysql_data)
	{
		if(!$this->isRowSet($table, $mysql_data))
			$mysql_data = [$mysql_data];
		$rows = [];
		foreach($mysql_data as $row)
		{
			$fields = [];
			foreach($row as $column=>$value)
			{
				$fields[] = $this->smart_quote($table, $column, $value);
			}
			if(count($fields))
				$rows[] = "(". implode(",", $fields) .")";
		}
		if(count($rows))
			return "(`". implode("`,`",array_keys($mysql_data[0])) ."`) VALUES". implode(",", $rows);
		else
			return "() VALUES()";
	}
	
	/**
	 * Takes two rows of the given table and removes all columns in which they share an equal value. The PRIMARY KEY column is also kept in order to allow the rows to still be identified.
	 */
	public function identifyChanges($table, &$current, &$previous)
	{
		$newCurrent = [];
		$newPrevious = [];
		foreach($current as $currentRow)
		{
			$foundPrevious = false;
			foreach($previous as $previousRow)
			{
				if($currentRow[$this->metadata[$table][self::INDEX_KEY]['PRIMARY'][1]['column']] == $previousRow[$this->metadata[$table][self::INDEX_KEY]['PRIMARY'][1]['column']])
				{
					$foundPrevious = true;
					$newCurrentRow = [];
					$newPreviousRow = [];
					foreach($currentRow as $column=>$value)
					{
						if($value != $previousRow[$column])
						{
							$newCurrentRow[$column] = $value;
							$newPreviousRow[$column] = $previousRow[$column];
						}
					}
					if(count($newCurrentRow))
						$newCurrent[$currentRow[$this->metadata[$table][self::INDEX_KEY]['PRIMARY'][1]['column']]] = $newCurrentRow;
					if(count($newPreviousRow))
						$newPrevious[$previousRow[$this->metadata[$table][self::INDEX_KEY]['PRIMARY'][1]['column']]] = $newPreviousRow;
					break;
				}
			}
			if(!$foundPrevious)
				$newCurrent[$currentRow[$this->metadata[$table][self::INDEX_KEY]['PRIMARY'][1]['column']]] = $currentRow;
		}
		$current = $newCurrent;
		$previous = $newPrevious;
	}
	
	public function processFilters($table, $filters)
	{
		$where = [];
		$conj = " AND ";
		foreach($filters as $i=>$filter)
		{
			if($i === "subgroup")
			{
				if($filter == "or")
					$conj = " OR ";
			}
			else if(is_array($filter) && isset($filter['value']) && ($tableColumn = $this->getValidColumn($table, $filter['column'])) !== null)
			{
				//$type = $this->metadata[$table][$filter['column']]['type_basic'];
				if(in_array($filter['comparator'], ["=","!=",">","<",">=","<=","IN","LIKE","BETWEEN"]))
				{
					if($filter['type'] == "get")
						$value = $_GET[$filter['value']];
					else if($filter['type'] == "post")
						$value = $_POST[$filter['value']];
					else if($filter['type'] == "request")
						$value = $_REQUEST[$filter['value']];
					else if($filter['type'] == "column")
						$value = $this->getValidColumn($table, $filter['value']);
					else
						$value = $filter['value'];
					
					if($filter['comparator'] == "LIKE")
						$filter['comparator'] = "LIKE ";
					
					if($filter['comparator'] == "BETWEEN" && is_array($value))
					{
						$where[] = $tableColumn ." BETWEEN ". $this->smart_quote($table, $filter['column'], $value[0]) ." AND ". $this->smart_quote($table, $filter['column'], $value[1]);
					}
					else
					{
						if(is_array($value))
						{
							array_walk($value, function(&$v,$k,$obj)use($filter){ $v=$obj->smart_quote($obj->table, $filter['column'], $v); }, $this);
							$value = "(". implode(",",$value) .")";
						}
						else if($filter['type'] == "column")
						{
							// $value is already fine.
						}
						else
							$value = $this->smart_quote($table, $filter['column'], $value);
						
						$where[] = $tableColumn . $filter['comparator'] . $value;
					}
					
				}
			}
			else if(is_array($filter) && !empty($filter['subgroup']))
			{
				if(!empty($processed = $this->processFilters($table, $filter)))
					$where[] = $processed;
			}
			else
			{
			}
		}
		return count($where) ? "(". implode($conj, $where) .")" : "";
	}
	
	/**
	 * Execute a SELECT statement based on the provided arguments.
	 *
	 * @param string|string[] $table The name of a table(, or an array of table names that will be used in the SELECT statement).
	 * @param array $options An array of options from which the statement will be built. The array structure is as follows (every key is optional):
	 * + `$options['columns']` An array of column names. The array can include `*` to select all columns. You can provide an alias by using an array instead, with the column name as element 0 and the alias specified with the 'alias' key. In place of a column name in either case, you can use a two-element array `[table,column]`. Omitting `$options['column']` will also select all columns.
	 * + `$options['filters']` ... Adds the WHERE clause.
	 * + `$options['order']` An array of two elements, where the first is the column name, and the second is the order (ASC/DESC or similar). Alternatively, this can be a multi-dimensional array where each element follows that aformentioned format. Adds the ORDER BY clause.
	 * + `$options['limit']` An array of two integers, with the format: `[offset, row_count]`. Adds the LIMIT clause.
	 */
	public function select($table, $options, $result=Database::RETURN_ALL)
	{
		if(!is_array($table))
			$table = [$table];
		foreach($table as $t)
			if(empty($this->metadata[$t]))
			{
				trigger_error("Failed to select from database: `". $t ."` is not a valid table.", E_USER_WARNING);
				return false;
			}
		$columns = "*";
		if(is_array($options))
		{
			if(isset($options['columns']) && is_array($options['columns']))
			{
				$selected_columns = [];
				$all = false;
				foreach($options['columns'] as $column)
				{
					if($column == "*")
						$selected_columns[] = "*";
					else if(!is_array($column) && ($tableColumn = $this->getValidColumn($table, $column)) !== null)
						$selected_columns[] = $tableColumn;
					else if(is_array($column))
					{
						if(!empty($column['alias']))
						{
							if(($tableColumn = $this->getValidColumn($table, $column[0])) !== null)
								$selected_columns[] = $tableColumn ." AS ". $this->quote($column['alias']);
						}
						else if(($tableColumn = $this->getValidColumn($table, $column)) !== null)
							$selected_columns[] = $tableColumn;
					}
				}
				if(count($selected_columns))
					$columns = implode(",", $selected_columns);
			}
			if(isset($options['filters']) && is_array($options['filters']))
			{
				if(!empty($processed = $this->processFilters($table, $options['filters'])))
					$where = " WHERE ". $processed;
			}
			if(isset($options['order']) && is_array($options['order']))
			{
				// Figure out the format of the order option.
				if(!is_array(current($options['order'])))
				{
					if(($tableColumn = $this->getValidColumn($table, $options['order'][0])) !== null)
						$order = [[$tableColumn, $options['order'][1]]];
					else
						$order = [];
				}
				else if(count($options['order']) < 3 && // More elements than just a column and ord? False.
					(empty($options['order'][1]) || !is_array($options['order'][1])) && // Second element can't be order string? False.
					count($options['order'][0]) == 2 && // First element can't be table & column? False.
					($tableColumn = $this->getValidColumn($table, $options['order'][0])) !== null) // First element doesn't match table & column? False.
				{
					$order = [[$tableColumn, $options['order'][1]]];
				}
				else
				{
					$order = [];
					foreach($options['order'] as $o)
					{
						if(($tableColumn = $this->getValidColumn($table, $o[0])) !== null)
							$order[] = [$tableColumn, $o[1]];
					}
				}
				// Convert $order to a string.
				foreach($order as &$o)
				{
					if(!empty($o[1]) && (substr($o[1], 0, 1) == "D" || substr($o[1], 0, 1) == "d"))
						$o = $o[0] ." DESC";
					else
						$o = $o[0] ." ASC";
				}
				if(count($order))
					$order = " ORDER BY ". implode(",", $order);
				else
					$order = "";
			}
			if(isset($options['limit']) && is_array($options['limit']))
			{
				$options['limit'][0] = (int)$options['limit'][0];
				$options['limit'][1] = (int)$options['limit'][1];
				if($options['limit'][0] >= 0 && $options['limit'][1] > 0)
					$limit = " LIMIT ". $options['limit'][0] .",". $options['limit'][1];
			}
		}
		$query = "SELECT ". $columns ." FROM `". implode("`,`", $table) ."`". (!empty($where) ? $where : "") . (!empty($order) ? $order : "") . (!empty($limit) ? $limit : "");
		return $this->query($query, $result);
	}
	
	/**
	 * Runs an insert statement that can also update with the provided data. Can also be used to add an entry to the changelog for queries that should be tracked.
	 * 
	 * Simlifies the amount of code needed to cleanly run an insert or update query on a database using PDO. The function will correctly handle the syntax for the various data types as well as prevent SQL injections. Depending on the parameters, you can run an insert statement with no update, an insert statement that does update, as well as add an entry to the changelog in order to track who ran this statement and when.
	 * 
	 * @param string $table The table on which to run the query.
	 * @param array $mysql_data An array of data, with the keys being columns in the database table and the values being the data to insert or update.
	 * @param boolean $update Whether to include the `ON DUPLICATE KEY UPDATE` clause with all of the data.
	 * @param array $leave_cols An array of columns to exclude from the `ON DUPLICATE KEY UPDATE` clause.
	 * @param boolean $log Whether to add an entry to the changelog for this statement.
	 * 
	 * @return boolean A boolean indicating the success of the query.
	 */
	public function insert($table, $mysql_data, $update=true, $leave_cols=[], $log=false)
	{
		if(empty($this->metadata[$table]))
		{
			trigger_error("Failed to insert/update database: `". $table ."` is not a valid table.", E_USER_WARNING);
			return false;
		}
		if(!is_array($mysql_data))
		{
			trigger_error("Failed to insert/update database: No data array specified.", E_USER_WARNING);
			return false;
		}
		$log = $log
			&& !empty($this->metadata["changelog"])
			&& count($this->metadata[$table][self::INDEX_KEY]['PRIMARY']) == 1
			&& $this->metadata[$table][$this->metadata[$table][self::INDEX_KEY]['PRIMARY'][1]['column']]['extra'] == "auto_increment";
		
		$invalid_cols = $this->validateColumns($table, $mysql_data);
		if(count($invalid_cols))
			trigger_error("The following invalid columns were sent to Database->insert(): ". implode(", ", $invalid_cols), E_USER_WARNING);
		if($log)
			$unique_where = $this->buildWhereUnique($table, $mysql_data);
		if($update)
			$update_clause = $this->buildODKUpdate($table, $mysql_data, $leave_cols);
		
		// TODO if there are multiple indexes, and this insert causes a single row to combine two separate existing ones, weird stuff happens. MySQL strongly suggests that people not attempt that.
		if(!empty($unique_where))
			$previous = $this->query("SELECT * FROM ". $table ." WHERE ". $unique_where, self::RETURN_ALL);
		$insert_clause = $this->buildInsertClause($table, $mysql_data);
		// Note: ON DUPLICATE KEY UPDATE causes this to not return an actual count. For each row, it returns 1 for an insert or 2 for an update. For multiple rows, it returns the sum of those numbers.
		$success = $this->query("INSERT". (!$update ? " IGNORE" : "") ." INTO ". $table ." ". $insert_clause . (!empty($update_clause) ? " ON DUPLICATE KEY UPDATE ".$update_clause : ""), self::RETURN_COUNT);
		if($log && $success)
		{
			$last_id = $this->query("SELECT LAST_INSERT_ID()", self::RETURN_FIELD);
			$affected_ids = [];
			if(!empty($previous) && count($previous))
				foreach($previous as $row)
					$affected_ids[] = (int)$row[$this->metadata[$table][self::INDEX_KEY]['PRIMARY'][1]['column']];
			if(!empty($last_id))
			{
				$num = ($this->isRowSet($table, $mysql_data)?count($mysql_data):1) - count($affected_ids);
				for($i=0; $i<$num; $i++)
					$affected_ids[] = $i + (int)$last_id;
			}
			$current = $this->query("SELECT * FROM ". $table ." WHERE `". $this->metadata[$table][self::INDEX_KEY]['PRIMARY'][1]['column'] ."` IN (". implode(",",$affected_ids) .")", self::RETURN_ALL);
			if(!empty($previous) && count($previous))
				$this->identifyChanges($table, $current, $previous);
			$this->insert("changelog", [
				'table' => $table,
				'timestamp' => time(),
				'data' => json_encode($current),
				'previous'=> is_array($previous) ? json_encode($previous) : "",
				'blame'=> is_object($this->cms) && is_object($this->cms->user) && !empty($this->cms->user->get_property('index')) ? $this->cms->user->get_property('index') : $_SERVER['REMOTE_ADDR'],
			], false, null, false);
		}
		return $success;
	}
	
	//TODO Remake this in the image of the new SELECT
	public function delete($table, $mysql_data, $log=false)
	{
		if(empty($this->metadata[$table]))
		{
			trigger_error("Failed to delete from database: `". $table ."` is not a valid table.", E_USER_WARNING);
			return false;
		}
		if(!is_array($mysql_data))
		{
			trigger_error("Failed to delete from database: No identifiable row data specified.", E_USER_WARNING);
			return false;
		}
		$log = $log && !empty($this->metadata["changelog"]);
		
		$invalid_cols = $this->validateColumns($table, $mysql_data);
		if(count($invalid_cols))
			trigger_error("The following invalid columns were sent to Database->delete(): ". implode(", ", $invalid_cols), E_USER_WARNING);
		
		$uni = "";
		foreach($mysql_data as $k=>$v)
		{
			if(!empty($this->metadata[$table][$k]))
			{
				if($uni != "")
					$uni .= " AND ";
				$uni .= "`". $k ."`=". $this->smart_quote($table, $k, $v);
			}
		}
		if(!empty($uni))
		{
			$previous = $this->query("SELECT * FROM ". $table ." WHERE ". $uni, self::RETURN_ALL);
			if(empty($previous) || !count($previous))
				return false;
		}
		else
			return false;
		$success = $this->query("DELETE FROM ". $table ." WHERE ". $uni, self::RETURN_COUNT);
		if($log && $success)
		{
			$this->insert("changelog", [
				'table' => $table,
				'timestamp' => time(),
				'data' => "",
				'previous'=> is_array($previous) ? json_encode($previous) : "",
				'blame'=> is_object($this->cms) && is_object($this->cms->user) && $this->cms->user->get_property('index')>0 ? $this->cms->user->get_property('index') : $_SERVER['REMOTE_ADDR'],
			], false, null, false);
		}
		return $success;
	}
	
	/**
	 * Returns a quoted value that is safe to use in a database query on the specific table for the specified column.
	 * 
	 * @param string $table The table of the value wish to quote.
	 * @param string $k The column of the value you wish to quote.
	 * @param string $v The value you wish to quote.
	 * 
	 * @return int|float|string A value that can be safely inserted directly into a database query statement, or `null` if `$table` and `$k` do not correspond to an existing table column.
	 */
	public function smart_quote($table, $k, $v)
	{
		if(is_array($k) || is_array($table))
			list($table, $k) = $this->getValidColumn($table, $k, true);
		if(empty($this->metadata[$table][$k]))
		{
			trigger_error("Database->smart_quote could not quote the given value (". $table .", ". $k .", ". $v .").");
			return null;
		}
		switch($this->metadata[$table][$k]['type_basic'])
		{
			case "integer":
				return (int)$v;
			case "decimal":
				return (real)$v;
			case "binary":
				if($v == "" || ctype_print($v))
					return $this->quote($v);
				else
					return "0x". bin2hex($v);
			default:
				return $this->quote($v);
		}
	}
}