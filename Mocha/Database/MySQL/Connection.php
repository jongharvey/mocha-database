<?php

namespace Mocha\Database\MySQL;

use Exception;
use mysqli;
use Mocha\Database\DbConnections;

class Connection {
	/** @var  Result */
	protected $lastResult;

	/** @var  string Last query executed */
	protected $lastQuery;

	/** @var mysqli */
	public $conn;

	protected $host, $user, $pass, $database;

	function __construct($host, $user, $pass, $database) {
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->database = $database;

		if (!DbConnections::$default)
			DbConnections::$default = $this;
	}

	function connect() {
		if ($this->conn)
			return;

		$this->conn = new mysqli($this->host, $this->user, $this->pass, $this->database);
		if (!$this->conn)
			throw new Exception("Failed connecting to the database");

		$this->conn->set_charset('utf8');
	}

	function query($query) {
		$this->connect();
		if (func_num_args() > 1) {
			$args = func_get_args();
			$params = array_splice($args, 1);
			$idx = array();
			$has_dollar = false;
			foreach ($params as $i => &$val) {
				if (strpos($val, '$') !== false) {
					$val = str_replace('$', '%DOLLAR_SIGN%', $val);
					$has_dollar = true;
				}

				$idx[] = '$'.($i + 1);
				$val = $this->quoteValue($val);
			}
			$query = str_replace($idx, $params, $query);
			if ($has_dollar)
				$query = str_replace('%DOLLAR_SIGN%', '$', $query);
		}

		$result = $this->conn->query($this->lastQuery = $query);
		if (!$result)
			throw new Exception("Query failed due to error: {$this->conn->error}\nQuery was:\n{$query}");

		if (is_bool($result))
			return $this->lastResult = $result;

		return $this->lastResult = new Result($result);
	}

	function insert($table, array $columns, $keys = 'id') {
		$this->connect();

		if (is_string($keys))
			$keys = array($keys);

		/* Make insert object and column list */
		$cols = array();
		$vals = array();

		foreach ($columns as $col => $val) {
			if (in_array($col, $keys) && empty($val))
				continue;
			$cols[] = $this->quoteName($col);
			$vals[] = $this->quoteValue($val);
		}
		$table = $this->quoteName($table);
		$cols = implode(', ', $cols);
		$vals = implode(', ', $vals);

		$this->query("INSERT INTO $table ($cols) VALUES ($vals)");
	}

	function update($table, array $columns, $keys = 'id') {
		$this->connect();

		if (!is_array($columns))
			throw new Exception("Insert object is not a valid data array.");

		if (is_string($keys))
			$keys = array($keys);

		/* Check key objects */
		$where = array();
		foreach ($keys as $key) {
			if (!isset($columns[$key]))
				throw new Exception("Update on $table requires column '$key', which isn't set.");

			$where[] = $this->quoteName($key).' = '.$this->quoteValue($columns[$key]);
			unset($columns[$key]);
		}
		$where = implode(' AND ', $where);

		/* Make insert object and column list */
		$set = array();
		foreach ($columns as $col => $val)
			$set[] = $this->quoteName($col).' = '.$this->quoteValue($val);
		$set = implode(', ', $set);

		$table = $this->quoteName($table);

		$this->query("UPDATE $table SET $set WHERE $where LIMIT 1");
	}

	function quoteName($name) {
		if (strpos($name, '.') !== false) {
			$parts = explode('.', $name);
			foreach ($parts as &$p)
				$p = "`$p`";
			return implode('.', $parts);
		}
		return "`$name`";
	}

	function quoteValue($val) {
		if ($val === 'NOW()')
			return "NOW()";
		else if ($val === 'CURDATE()')
			return "CURDATE()";
		else if ($val === 'NULL' || $val === null)
			return "NULL";
		else if (is_int($val) || is_float($val) || is_double($val))
			return $val;
		else if (is_bool($val))
			return $val ? 1 : 0;
		else
			return '\''.$this->escape($val).'\'';
	}

	function escape($str) {
		return $this->conn->real_escape_string($str);
	}

	function insertId() {
		return $this->conn->insert_id;
	}

	function affectedRows() {
		return $this->conn->affected_rows;
	}
}