<?php

namespace Mocha\Database;

trait Model {
	function populateFromTable($id, $table, IConnection $connection = null) {
		if (!$connection)
			$connection = DbConnections::$default;

		$data = $connection->query("SELECT * FROM $table WHERE id = $1", $id)->assoc();
		if ($data)
			$this->populateFromArray($data);
	}

	function populateFromArray(array $opts) {
		if (empty($opts))
			return;

		foreach ($opts as $key => $val) {
			if (property_exists($this, $key))
				$this->{$key} = $val;
		}
	}
}