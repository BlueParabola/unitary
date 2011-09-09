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
use \Exception as Exception;


class Channel {
	protected $_url;
	protected $_restEntryPoint;
	
	protected $_packages = array();
	
	protected function __construct($url) {
		$this->_url = $url;
		$this->_discover();
		$this->_loadPackages();
	}
	
	protected function _discover() {
		Logger::log("Beginning discovery of channel $this->_url.");
		Logger::indent();
		
		$channelData = simplexml_load_file($this->_url . "/channel.xml");
		
		if (!$channelData) {
			throw new Exception("Cannot load channel information for $this->_url");
		}
		
		$channelData->registerXPathNamespace("pear", "http://pear.php.net/channel-1.0");
		$entryPoints = $channelData->xpath("//pear:servers/pear:primary/pear:rest/pear:baseurl[@type='REST1.3']");
		
		if (count($entryPoints) != 1) {
			throw new Exception("Cannot find ReST 1.3 entry point for this channel.");
		}
		
		$this->_restEntryPoint = $entryPoints[0];
		
		Logger::log("Discovery complete. The ReST entry point is $this->_restEntryPoint.");
		
		Logger::outdent();
	}
	
	protected function _loadPackages() {
		Logger::log("Beginning discovery of available packages.");
		Logger::indent();
		
		$packageData = simplexml_load_file($this->_restEntryPoint . "/p/packages.xml");
		
		if (!$packageData) {
			throw new Exception("Cannot load package data for this channel");
		}
		
		$packageData->registerXPathNamespace("pear", "http://pear.php.net/dtd/rest.allpackages");
		$packageElements = $packageData->xpath("//pear:p");
		
		foreach ($packageElements as $packageElement) {
			$packageName = (string) $packageElement;
			
			Logger::log("Found package $packageName");
			$this->_packages[$packageName] = $packageName;
		}
		
		Logger::log("Package discovery complete.");
		Logger::outdent();
	}
	
	function __toString() {
		return $this->_url;
	}
	
	function getReSTEntryPoint() {
		return $this->_restEntryPoint;
	}
	
	function getURL() {
		return $this->_url;
	}
	
	function getPackage($packageName) {
		if (!isset($this->_packages[$packageName])) {
			throw new Exception("Cannot resolve package $this->_url::$packageName");
		}
		
		if (!$this->_packages[$packageName] instanceof Package) {
			$package = new Package($this, $packageName);
			$this->_packages[$packageName] = $package;
		}
		
		return $this->_packages[$packageName];
	}
	
	static function getChannel($channelURL = "http://pear.php.net/channel.xml") {
		static $channels = array();
		
		if (!isset($channels[$channelURL])) {
			$channels[$channelURL] = new Channel($channelURL);
		}
		
		return $channels[$channelURL];
	}
}