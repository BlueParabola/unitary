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
		$channel = Channel::getChannel("http://pear.phpunit.de");
		$package = $channel->getPackage("PHPUnit");
	}
}