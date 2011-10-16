<?php

require "script.php";

class addTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		
	}
	
	public function tearDown() {
		
	}
	
	public function testAddition() {
		$this->assssertTrue(add(1, 2) == 3);
	}
}