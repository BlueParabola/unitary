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

class Logger {
	static protected $indentLevel = 0;
	
	static function indent() {
		static::$indentLevel += 1;
	}
	
	static function outdent() {
		if (!static::$indentLevel) {
			throw new Exception("Unbalanced indentation level.");
		}
		
		static::$indentLevel -= 1;
	}
	
	static function log($message) {
		echo str_repeat(' ', static::$indentLevel), $message, "\n";
	}
}