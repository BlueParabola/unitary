<?php

require "script.php";

class addTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		
	}
	
	public function tearDown() {
		
	}
	
	public function testAddition() {
		$this->assertTrue(add(1, 2) == 3);
	}
	
	public function testFailedAddition() {
		$this->assertTrue(add(1, 2) == 3);
	}
}