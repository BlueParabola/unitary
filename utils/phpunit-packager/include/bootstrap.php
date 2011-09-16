<?php

/*
 * Unitary
 *
 * Copyright © Blue Parabola, LLC
 *
 * For license information, see LICENSE.TXT
 *
 */

error_reporting(E_ALL | E_STRICT);

// Note: the bootstrapper lives in the global namespace
// because we cannot make the assumption that namespaces exist until
// we've checked the version of PHP under which the script
// is running.

class Bootstrap {
	static function handleError($errNo, $errString) {
		throw new Exception($errString, $errNo);
	}

	static function validateEnvironment() {
		// Check PHP version

        /*
         *  NOTE: This substring is necessary because quite often the PHP
         *    version isn't a straight x.y.z but 5.3.3-1ubuntu9.5 instead. This
         *    guards against that by making sure we always get M.m.p
         */
		$phpVersion = substr(PHP_VERSION, 0, 5);

		if (version_compare($phpVersion, '5.3.0', '<')) {
			die("This script requires PHP 5.3 or higher\n");
		}

		if (!ini_get('allow_url_fopen')) {
			die("This script requires allow_url_fopen to be on.\n");
		}
		
	}
	
	static function autoload($className) {
		$elements = explode("\\", strtolower($className));
		
		if ($elements[0] !== "unitary") {
			return;
		}
		
		foreach ($elements as $element) {
			if (!preg_match('/[a-zA-Z_][a-zA-Z0-9_]*/', $element)) {
				return;
			}
		}
		
		array_shift($elements);
		
		require dirname(__FILE__) . "/" . implode("/", $elements) . ".php";
	}
	
	static function performBootstrap() {
		static::validateEnvironment();
		
		set_error_handler('Bootstrap::handleError');
		spl_autoload_register('static::autoload');
	}
}

Bootstrap::performBootstrap();
new Unitary\Packager();