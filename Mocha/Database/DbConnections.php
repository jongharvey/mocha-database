<?php

namespace Mocha\Database;

use Mocha\Database\MySQL\Connection;

class DbConnections {
	/** @var Connection Connection to the default server */
	public static $default = null;
}