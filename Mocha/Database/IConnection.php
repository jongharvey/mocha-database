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

	abstract function connect();
	abstract function query($query);
	abstract function insert($table, array $columns, $keys = 'id');
	abstract function update($table, array $columns, $keys = 'id');
	abstract function insertId();
	abstract function affectedRows();
}