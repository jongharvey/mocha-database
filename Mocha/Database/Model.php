<?php

namespace Mocha\Database;


use Mocha\Database\MySQL\Connection;

trait Model {
	/** @var  string Name of the table containing the object */
	protected $_table;

	/** @var  Connection Connection to the desired db server */
	protected $_connection;

	function __construct($id = null) {
		$conn = $this->_connection ? $this->_connection : DbConnections::$default;

		if (is_array($id))
			$this->__configure($id);
		else if (!empty($id)) {
			$data = $conn->query("SELECT * FROM {$this->_table} WHERE id = $1", $id)->assoc();
			if ($data)
				$this->__configure($data);
		}
	}

	protected function __configure(array $opts) {
		if (empty($opts))
			return;

		foreach ($opts as $key => $val) {
			if (property_exists($this, $key))
				$this->{$key} = $val;
		}
	}
}