<?php

namespace Mocha\Database\PostgreSQL;


use Mocha\Database\IResult;

class Result extends IResult {
	protected $result;

	public function __construct($result) {
		$this->result = $result;
	}

	public function __destruct() {
		if ($this->result)
			$this->free();
	}

	public function num() {
		$row = pg_fetch_row($this->result);
		return $row;
	}

	public function numList() {
		for ($list = array(); $row = pg_fetch_row($this->result); $list[] = $row) {
		}
		$this->free();
		return $list;
	}

	public function assoc() {
		$row = pg_fetch_assoc($this->result);
		return $row;
	}

	public function assocList() {
		for ($list = array(); $row = pg_fetch_assoc($this->result); $list[] = $row) {
		}
		$this->free();
		return $list;
	}

	public function map() {
		for ($map = array(); $row = pg_fetch_row($this->result); $map[$row[0]] = $row[1]) {
		}
		$this->free();
		return $map;
	}

	public function value() {
		$row = pg_fetch_row($this->result);
		$this->free();
		return $row != false ? $row[0] : false;
	}

	public function listing() {
		for ($list = array(); $row = pg_fetch_row($this->result); $list[] = $row[0]) {
		}
		$this->free();
		return $list;
	}

	// Miscellaneous
	public function free() {
		if (is_resource($this->result))
			pg_free_result($this->result);
		$this->result = false;
	}
}