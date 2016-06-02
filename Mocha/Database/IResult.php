<?php

namespace Mocha\Database;


abstract class IResult {
	abstract function num();
	abstract function numList();
	abstract function assoc();
	abstract function assocList();
	abstract function map();
	abstract function listing();
	abstract function value();
	abstract function free();
}