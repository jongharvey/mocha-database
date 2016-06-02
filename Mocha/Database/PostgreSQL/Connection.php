<?php

namespace Mocha\Database\PostgreSQL;


use Exception;
use Mocha\Database\IConnection;

class Connection extends IConnection {
	/**
	 * @var resource Connection from pg_connect
	 */
	protected $conn;

	protected $lastId;

	/**
	 * @var resource Result from pg_query
	 */
	protected $result;

	function connect() {
		if (!($this->conn = pg_connect("host={$this->host} user={$this->user} password={$this->pass} dbname={$this->database}")))
			throw new Exception("Error while connecting to database server");
	}

	function query($query, ...$params) {
		if (!$this->conn)
			$this->connect();
		
		$this->lastQuery = $query;
		if (sizeof($params) > 0) {
			$result = @pg_query_params($this->conn, $query, $params);
		} else
			$result = @pg_query($this->conn, $query);

		if (!$result)
			throw new Exception("Query failed due to error: " . pg_last_error() . "\nQuery was:\n" . $query);

		return $this->lastResult = new Result($this->result = $result);
	}

	public function insert($table, array $columns, $keys = 'id') {
		if (!is_array($columns))
			throw new Exception("Insert object is not a valid data array.");

		/* Clean up the table name, in case we're using a separate schema */
		$table = explode('.', $table);
		foreach ($table as &$part)
			$part = "\"$part\"";
		$table = implode('.', $table);

		/* Make insert object and column list */
		$cols = array();
		$phs = array();
		$vals = array();

		foreach ($columns as $col => $val) {
			if ($col == $keys && empty($val))
				continue;

			$cols[] = '"' . $col . '"';
			$phs[] = '$' . sizeof($cols);
			$vals[] = ($val === false || $val === null)
				? null
				: (get_magic_quotes_gpc() ? stripslashes($val) : $val);
		}

		$query = sprintf('INSERT INTO $table (%s) VALUES (%s) RETURNING "%s"',
			implode(', ', $cols), implode(', ', $phs), $keys);

		$result = $this->query($query, $vals);
		$this->lastId = $result->value();
		return $result;
	}

	public function update($table, array $columns, $keys = 'id') {
		if (!is_array($columns) || empty($keys))
			throw new Exception("Insert object is not a valid data array.");

		/* Clean up the table name, in case we're using a separate schema */
		$table = explode('.', $table);
		foreach ($table as &$part)
			$part = "\"$part\"";
		$table = implode('.', $table);

		$refs = array();

		/* Check primary key objects */
		if (!is_array($keys))
			$keys = array($keys);
		if (sizeof($keys) < 1)
			throw new Exception("No primary key defined");

		$keycols = array();
		foreach ($keys as $key) {
			if (!isset($columns[$key])) continue;
			$ref = '$' . (sizeof($refs) + 1);
			$refs[] = $columns[$key];
			$keycols[] = sprintf('"%s" = %s', $key, $ref);
			unset($columns[$key]);
		}
		$keycols = implode(' AND ', $keycols);

		/* Make insert object and column list */
		$setcols = array();
		foreach ($columns as $col => $val) {
			if ($val === false || $val === NULL)
				$setcols[] = "\"$col\" = NULL";
			else if ($val === 'now()')
				$setcols[] = "\"$col\" = now()";
			else {
				$ref = '$' . (sizeof($refs) + 1);
				$refs[] = $val;
				$setcols[] = "\"$col\" = $ref";
			}
		}
		$setcols = implode(', ', $setcols);

		$query = "UPDATE $table SET $setcols WHERE $keycols";
		return $this->query($query, $refs);
	}

	public function insertId() {
		return $this->lastId;
	}

	public function copyFrom($table, $rows) {
		pg_copy_from($this->conn, $table, $rows);
	}

	public function putLine($line) {
		return pg_put_line($this->conn, $line);
	}

	public function copyFile($table, $col, $fp, $chunk = 10000) {
		$this->query("CREATE TEMPORARY TABLE $table ($col text)");
		$data = array();
		while ($line = fgets($fp, 4096)) {
			$line = trim($line);
			if (!empty($line))
				$data[] = $line;
			if (sizeof($data) >= $chunk) {
				$this->copyFrom($table, $data);
				$data = array();
			}
		}
		if (sizeof($data) >= 0) {
			$this->copyFrom($table, $data);
		}
	}

	public function affectedRows() {
		return pg_affected_rows($this->result);
	}
}