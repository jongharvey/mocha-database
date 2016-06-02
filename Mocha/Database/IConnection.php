<?php

namespace Mocha\Database;


abstract class IConnection {
	/** @var  IResult */
	protected $lastResult;

	/** @var  string Last query executed */
	protected $lastQuery;

	protected $host, $user, $pass, $database;

	function __construct($host, $user, $pass, $database) {
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->database = $database;

		if (!DbConnections::$default)
			DbConnections::$default = $this;
	}

	/**
	 * Connect to the server
	 * @return null
	 */
	abstract function connect();

	/**
	 * @param $query string Query string
	 * @param array ...$params
	 * @return IResult Result
	 */
	abstract function query($query, ...$params);
	
	abstract function insert($table, array $columns, $keys = 'id');
	abstract function update($table, array $columns, $keys = 'id');
	abstract function insertId();
	abstract function affectedRows();
}