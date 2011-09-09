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


class PackageDependency {
	protected $_channel;
	protected $_name;
	
	function __construct(Channel $channel, $name) {
		$this->_channel = $channel;
		$this->_name = $name;
	}
	
	function getPackage() {
		return $this->_channel->getPackage($this->_name);
	}
}


class Package {
	protected $_channel;
	protected $_name;
	protected $_latestVersion;
	protected $_downloadURL;
	
	protected $_requiredDependencies = array();
	protected $_optionalDependencies = array();
	
	function __construct(Channel $channel, $channelName) {
		$this->_channel = $channel;
		$this->_name = strtolower($channelName);
		
		$this->_loadPackageInfo();
	}
	
	protected function _loadPackageInfo() {
		Logger::log("Discovering package $this->_name.");
		Logger::indent();
		
		$url = $this->_channel->getReSTEntryPoint() . "p/" . $this->_name . "/info.xml";
		$packageInfo = simplexml_load_file($url);
		
		if (!$packageInfo) {
			throw new Exception("Cannot load information for package $this->_name");
		}
		
		$packageInfo->registerXPathNamespace("pear", "http://pear.php.net/dtd/rest.package");
		$baseLocation = $packageInfo->xpath('//pear:r');
		
		$attributes = $baseLocation[0]->attributes("http://www.w3.org/1999/xlink");
		$versionURL = (string) $attributes['href'];
		
		if (!$versionURL) {
			throw new Exception("Unable to discover the version information file for package $this->_name");
		}
		
		$versionURL = $this->_channel->getURL() . $versionURL;
		
		Logger::log("Loading package information file at $versionURL.");
		
		$this->_latestVersion = trim(file_get_contents($versionURL . "/stable.txt"));
		
		if (!$this->_latestVersion) {
			throw new Exception("Unable to determine latest stable version of package $this->_name");
		}
		
		Logger::log("Latest version of package is $this->_latestVersion.");
		
		$packageVersionData = simplexml_load_file($this->_channel->getReSTEntryPoint() . "/r/" . $this->_name . "/" . $this->_latestVersion . ".xml");
		
		if (!$packageVersionData) {
			throw new Exception("Unable to load version data for package $this->_name");
		}
		
		$this->_downloadURL = (string) $packageVersionData->children("http://pear.php.net/dtd/rest.release")->g . ".tgz";
		
		if (!$this->_downloadURL) {
			throw new Exception("Unable to determine download location of package $this->_name");
		}

		Logger::log("Package can be downloaded from $this->_downloadURL.");
		Logger::log("Discovery complete.");
		Logger::outdent();
	}
	
	function loadDependencies() {
		Logger::log("Discovering package dependencies...");
		Logger::indent();
		
		$dependencyDataFile = file_get_contents($this->_channel->getReSTEntryPoint() . "/r/" . $this->_name . "/deps." . $this->_latestVersion . ".txt");
		$dependencyData = unserialize($dependencyDataFile);
		
		if (!$dependencyData || !$dependencyDataFile) {
			throw new Exception("Unable to load dependency information for package $this->_name");
		}

		foreach ($dependencyData["required"]["package"] as $dependency) {
			$channel = Channel::getChannel($dependency["channel"]);
			$package = $channel->getPackage($dependency["name"]);
			Logger::log("Found required dependency: $channel/$package");
		}

		foreach ($dependencyData["optional"]["packages"] as $dependency) {
			$channel = Channel::getChannel($dependency["channel"]);
			$package = $channel->getPackage($dependency["name"]);
			Logger::log("Found optional dependency: $channel/$package");
		}
		
		Logger::log("Package dependencies discovered.");
		Logger::outdent();
	}
	
	function __toString() {
		return "$this->_name ($this->_latestVersion)";
	}
}