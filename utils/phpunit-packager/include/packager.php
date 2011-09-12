<?php

/*
 * Unitary
 *
 * Copyright © Blue Parabola, LLC
 *
 * For license information, see LICENSE.TXT
 *
 */

namespace Unitary;

class Packager {
	function __construct() {
		global $argc, $argv;
		
		if ($argc != 2) {
			die("Usage: $argv[0] destination-folder\n\n");
		}
		
		$destination = $argv[1];
		
		$channel = Channel::getChannel("http://pear.phpunit.de");
		$package = $channel->getPackage("PHPUnit");
		
		$package->downloadTo($destination);
	}
}