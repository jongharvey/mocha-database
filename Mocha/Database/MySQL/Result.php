<?php

namespace Mocha\Database\MySQL;

use Mocha\Database\IResult;
use mysqli_result;

class Result extends IResult {
	var $result;

	function __construct(mysqli_result $res) {
		$this->result = $res;
	}

	function __destruct() {
		$this->free();
	}

	public function num() {
		$row = $this->result->fetch_row();
		return $row;
	}

	public function numList() {
		for ($list = array(); $row = $this->result->fetch_row(); $list[] = $row) {
		}
		$this->free();
		return $list;
	}

	public function assoc() {
		$row = $this->result->fetch_assoc();
		return $row;
	}

	public function assocList() {
		for ($list = array(); $row = $this->result->fetch_assoc(); $list[] = $row) {
		}
		$this->free();
		return $list;
	}

	public function map() {
		for ($map = array(); $row = $this->result->fetch_row(); $map[$row[0]] = $row[1]) {
		}
		$this->free();
		return $map;
	}

	public function value() {
		$row = $this->result->fetch_row();
		return $row != false ? $row[0] : false;
	}

	public function listing() {
		for ($list = array(); $row = $this->result->fetch_row(); $list[] = $row[0]) {
		}
		$this->free();
		return $list;
	}

	function numRows() {
		return $this->result->num_rows;
	}

	public function free() {
		if ($this->result)
			$this->result->free();
		$this->result = null;
	}
}