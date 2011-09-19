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
use \Phar as Phar;


class PackageDependency {
	protected $_channel;
	protected $_name;
	
	function __construct(Channel $channel, $name) {
		$this->__construct = $channel;
		$this->_name = $name;
	}
	
	function getPackage() {
		return $this->_channel->getPackage($this->_name);
	}
}


class Package {
	protected $_channel;
	protected $_fullName;
	protected $_name;
	protected $_latestVersion;
	protected $_downloadURL;
	
	protected $_requiredDependencies = array();
	protected $_optionalDependencies = array();
	
	protected $_downloaded;
	
	function __construct(Channel $channel, $channelName) {
		$this->_channel = $channel;
		$this->_fullName = $channelName;
		$this->_name = strtolower($channelName);
		
		$this->_loadPackageInfo();
	}
	
	protected function _loadPackageInfo() {
		Logger::log("Discovering package $this->_name.");
		Logger::indent();
		
		$versionURL = $this->_channel->getReSTEntryPoint() . "r/" . $this->_name;
		
		Logger::log("Loading package information file at $versionURL.");

		try {
			$this->_latestVersion = trim(file_get_contents($versionURL . "/stable.txt"));
		} catch (Exception $e) {
			$this->_latestVersion = trim(file_get_contents($versionURL . "/latest.txt"));
		}
		
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
		
		$dependencyDataFileName = $this->_channel->getReSTEntryPoint() . "/r/" . $this->_name . "/deps." . $this->_latestVersion . ".txt";
		
		Logger::log("Dependency file is at $dependencyDataFileName");

		try{
			$dependencyDataFile = file_get_contents($dependencyDataFileName);
			$dependencyData = unserialize($dependencyDataFile);
		} catch (Exception $e) {
			throw new Exception("Unable to load dependency information for package $this->_name");
		}
		
		if (isset($dependencyData["required"]["package"])) {
			if (!isset($dependencyData["required"]["package"][0])) {
				$dependencyData["required"]["package"] = array($dependencyData["required"]["package"]);
			}
			
			foreach ($dependencyData["required"]["package"] as $dependency) {
				$channel = Channel::getChannel("http://" . $dependency["channel"]);
				$package = $channel->getPackage($dependency["name"]);
				$this->_requiredDependencies[] = $package;
				Logger::log("Found required dependency: $channel/$package");
			}
		}

		if (isset($dependencyData["optional"]["package"]) && is_array($dependencyData["optional"]["package"])) {
			if (!isset($dependencyData["optional"]["package"][0])) {
				$dependencyData["optional"]["package"] = array($dependencyData["optional"]["package"]);
			}
			
			foreach ($dependencyData["optional"]["package"] as $dependency) {
				$channel = Channel::getChannel("http://" . $dependency["channel"]);
				$package = $channel->getPackage($dependency["name"]);
				$this->_optionalDependencies[] = $package;
				Logger::log("Found required dependency: $channel/$package");
			}
		}
		
		Logger::log("Package dependencies discovered.");
		Logger::outdent();
	}

    protected function _downloaded($destinationDirectory, $downloadURL) {
        $directory = substr($downloadURL, strrpos($downloadURL, "/") + 1);
        $directory = substr($directory, 0, strrpos($directory, "."));

        return is_dir($destinationDirectory."/".$directory);
    }

	function downloadTo($destinationDirectory) {
		if ($this->_downloaded) {
			return;
		}

		$this->_downloaded = true;

        Logger::indent();
        if ($this->_downloaded($destinationDirectory, $this->_downloadURL)) {
            Logger::log("Using previously downloaded package $this->_fullName...");
        } else {
            Logger::log("Downloading package $this->_fullName...");

            $this->loadDependencies();

            $fileContents = file_get_contents($this->_downloadURL);
            $fileLocation = tempnam(sys_get_temp_dir(), "unitary");
            file_put_contents($fileLocation, $fileContents);

            $tar = new Tar($fileLocation);
            $tar->extract($destinationDirectory, '/package2?.(sig|xml)/');

            unlink($fileLocation);

            Logger::log("Downloaded.");
        }
        Logger::outdent();

		Logger::log("Resolving dependencies...");
		Logger::indent();
		
		foreach ($this->_requiredDependencies as $dependency) {
			$dependency->downloadTo($destinationDirectory);
		}
		
		foreach ($this->_optionalDependencies as $dependency) {
			$dependency->downloadTo($destinationDirectory);
		}
		
		Logger::outdent();
		Logger::log("Download sequence complete.");
	}
	
	function __toString() {
		return "$this->_name ($this->_latestVersion)";
	}
}