<?php

class Database
{
	const RETURN_NONE = 0;
	const RETURN_FIELD = 1;
	const RETURN_ROW = 2;
	const RETURN_ALL = 3;
	const RETURN_COLUMN = 4;
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
	public $metadata = array();
	public $error = array();
	
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
		$this->metadata = array();
		$columns = $this->query("SELECT TABLE_NAME,COLUMN_NAME,COLUMN_DEFAULT,DATA_TYPE,COLUMN_TYPE,COLUMN_KEY,EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=". $this->quote($this->database) ." ORDER BY TABLE_NAME");
		foreach($columns as $row)
		{
			if(empty($this->metadata[$row['TABLE_NAME']]))
				$this->metadata[$row['TABLE_NAME']] = array();
			if(empty($this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']]))
				$this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']] = array();
			$this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']]['default'] = $row['COLUMN_DEFAULT'];
			$this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']]['type'] = $row['DATA_TYPE'];
			$this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']]['type_full'] = $row['COLUMN_TYPE'];
			$this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']]['type_basic'] = !empty(Database::$basic_types[$row['DATA_TYPE']]) ? Database::$basic_types[$row['DATA_TYPE']] : "text";
			$this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']]['key'] = $row['COLUMN_KEY'];
			$this->metadata[$row['TABLE_NAME']][$row['COLUMN_NAME']]['extra'] = $row['EXTRA'];
		}
		foreach(array_keys($this->metadata) as $table)
		{
			$this->metadata[$table]['index '] = array();
			$indexes = $this->query("SHOW INDEX FROM ". $table ." WHERE Non_unique=0");
			foreach($indexes as $row)
			{
				if(empty($this->metadata[$table]['index '][$row['Key_name']]))
					$this->metadata[$table]['index '][$row['Key_name']] = array();
				if(empty($this->metadata[$table]['index '][$row['Key_name']][$row['Seq_in_index']]))
					$this->metadata[$table]['index '][$row['Key_name']][$row['Seq_in_index']] = array();
				$this->metadata[$table]['index '][$row['Key_name']][$row['Seq_in_index']]['column'] = $row['Column_name'];
				$this->metadata[$table]['index '][$row['Key_name']][$row['Seq_in_index']]['substr'] = $row['Sub_part'];
			}
			foreach(array_keys($this->metadata[$table]['index ']) as $key)
				ksort($this->metadata[$table]['index '][$key]);
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
	 * 
	 * If you do not specify one of those values, the function will attempt to guess which one you want.
	 * @return boolean|string|string[]|array[] Either a boolean indicating the success of the query, or some part of the result returned from the database after the query. See above for details.
	 */
	public function query($string, $result=-1, $col=0) // 0 = return boolean ... 1 = single field ... 2 = one whole row ... 3 = entire result set
	{
		$sta = $this->pdo->query($string);
		if($sta)
		{
			$this->error = $this->pdo->errorInfo();
			switch($result)
			{
				case self::RETURN_NONE:
					$return = true;
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
	public function insert($table, $mysql_data, $update=true, $leave_cols=array(), $log=false)
	{
		$log = $log && is_array($this->metadata["changelog"]);
		if(!is_array($this->metadata[$table]))
		{
			trigger_error("Failed to insert/update database: `". $table ."` is not a valid table.", E_USER_WARNING);
			return false;
		}
		if(!is_array($mysql_data))
		{
			trigger_error("Failed to insert/update database: No data array specified.", E_USER_WARNING);
			return false;
		}
		$uni = "";
		$upd = "";
		$badcols = array();
		foreach($mysql_data as $k=>$v)
		{
			if(is_array($this->metadata[$table][$k]))
			{
				$mysql_data[$k] = $this->smart_quote($table, $k, $v);
				if($log && ($this->metadata[$table][$k]['key'] == "PRI" || $this->metadata[$table][$k]['key'] == "UNI"))
				{
					if($uni != "")
						$uni .= " OR ";
					$uni .= "`". $k ."`=". $mysql_data[$k];
				}
				if($update && !in_array($k, $leave_cols))
				{
					if($upd != "")
						$upd .= ",";
					$upd .= "`". $k ."`=VALUES(`". $k ."`)";
				}
			}
			else
			{
				$badcols[] = $k;
				unset($mysql_data[$k]);
			}
		}
		if(count($badcols))
			trigger_error("The following invalid columns were sent to Database->insert(): ". implode(", ", $badcols), E_USER_WARNING);
		
		if($log && $uni != "")
		{
			$previous = $this->query("SELECT `". implode("`,`",array_keys($mysql_data)) ."` FROM ". $table ." WHERE ". $uni, self::RETURN_ALL);
			if(!count($previous))
				$previous = null;
			else
			{
				foreach($previous as $i=>$row)
				{
					foreach($row as $k=>$v)
					{
						$previous[$i][$k] = $this->smart_quote($table, $k, $v);
					}
				}
			}
		}
		$success = $this->query("INSERT". (!$update ? " IGNORE" : "") ." INTO ". $table ." (`". implode("`,`",array_keys($mysql_data)) ."`) VALUES (". implode(",", $mysql_data) .")". ($upd!="" ? " ON DUPLICATE KEY UPDATE ".$upd : ""), self::RETURN_NONE);
		if($log && $success)
		{
			$this->insert("changelog", array(
				'table' => $table,
				'timestamp' => time(),
				'data' => json_encode($mysql_data),
				'previous'=> is_array($previous) ? json_encode($previous) : "",
				'blame'=> is_object($this->cms) && is_object($this->cms->user) && $this->cms->user->get_property('index')>0 ? $this->cms->user->get_property('index') : $_SERVER['REMOTE_ADDR'],
			), false, null, false);
		}
		return $success;
	}
	
	public function delete($table, $mysql_data, $log=false)
	{
		$log = $log && is_array($this->metadata["changelog"]);
		if(!is_array($this->metadata[$table]))
		{
			trigger_error("Failed to delete from database: `". $table ."` is not a valid table.", E_USER_WARNING);
			return false;
		}
		if(!is_array($mysql_data))
		{
			trigger_error("Failed to delete from database: No identifiable row data specified.", E_USER_WARNING);
			return false;
		}
		$uni = "";
		$badcols = array();
		foreach($mysql_data as $k=>$v)
		{
			if(is_array($this->metadata[$table][$k]))
			{
				$mysql_data[$k] = $this->smart_quote($table, $k, $v);
				if($uni != "")
					$uni .= " AND ";
				$uni .= "`". $k ."`=". $mysql_data[$k];
			}
			else
			{
				$badcols[] = $k;
				unset($mysql_data[$k]);
			}
		}
		if(count($badcols))
			trigger_error("The following invalid columns were sent to Database->delete(): ". implode(", ", $badcols), E_USER_WARNING);
		
		if($uni != "")
		{
			$previous = $this->query("SELECT * FROM ". $table ." WHERE ". $uni, self::RETURN_ALL);
			if(count($previous))
			{
				foreach($previous as $i=>$row)
				{
					foreach($row as $k=>$v)
					{
						$previous[$i][$k] = $this->smart_quote($table, $k, $v);
					}
				}
			}
			else
				return false;
		}
		else
			return false;
		$success = $this->query("DELETE FROM ". $table ." WHERE ". $uni, self::RETURN_NONE);
		if($log && $success)
		{
			$this->insert("changelog", array(
				'table' => $table,
				'timestamp' => time(),
				'data' => "",
				'previous'=> is_array($previous) ? json_encode($previous) : "",
				'blame'=> is_object($this->cms) && is_object($this->cms->user) && $this->cms->user->get_property('index')>0 ? $this->cms->user->get_property('index') : $_SERVER['REMOTE_ADDR'],
			), false, null, false);
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
		if(!is_array($this->metadata[$table][$k]))
			return null;
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