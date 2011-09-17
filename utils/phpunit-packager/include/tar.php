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


class TarFile {
	public $fileName;
	public $fileMode;
	public $fileSize;
	
	protected $_inputFile;
	
	function __construct($inputFile) {
		$endHeader = pack("a512", "");
		$headerData = fread($inputFile, 512);
		
		if ($headerData === $endHeader) {
			return;
		}
		
		// Borrowed from PEAR/Archive_TAR
		
		$header = unpack("a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/"
		                 ."a8checksum/a1typeflag/a100link/a6magic/a2version/"
						 ."a32uname/a32gname/a8devmajor/a8devminor", $headerData);
						
		if ($header) {
			$this->fileName = trim($header['filename']);
			$this->fileMode = octdec(trim($header['mode']));
			$this->fileSize = octdec(trim($header['size']));
		}
		
		$this->_inputFile = $inputFile;
	}
	
	protected function _getContents() {
		$fileData = fread($this->_inputFile, $this->fileSize);
		
		if ($this->fileSize % 512) {
			fread($this->_inputFile, 512 - ($this->fileSize % 512));
		}
		
		return $fileData;
	}
	
	function saveTo($destination, $ignoreRegex = null) {
		$fileData = $this->_getContents();
		
		if ($ignoreRegex && preg_match($ignoreRegex, $this->fileName)) {
			return;
		}
		
		$fileName = $destination . "/" . $this->fileName;
		
		$dirName = dirname($fileName);
		
		if (!file_exists($dirName)) {
			mkdir($dirName, 0777, true);
		}
		
		file_put_contents($fileName, $fileData);
	}
	
	static function tarFile($inputFile) {
		$result = new TarFile($inputFile);
		
		if ($result->fileName == "././@LongLink") {
			$longFileName = $result->_getContents();
			
			$result = new TarFile($inputFile);
			$result->fileName = $longFileName;
		}

		if ($result->fileName) {
			Logger::log("$result->fileName ($result->fileSize byte" . ($result->fileSize != 1 ? 's' : '') . ")");
		}
		
		return $result;
	}
}


class Tar {
	protected $_fileName;
	
	function __construct($fileName) {
		$this->_fileName = $fileName;
	}
	
	function extract($destination, $ignoreRegex) {
		$inputFile = gzopen($this->_fileName, "r");
		
		do {
			$f = TarFile::tarFile($inputFile);
			
			if ($f->fileName) {
				$f->saveTo($destination, $ignoreRegex);
			}
		} while ($f->fileName);
		
		fclose($inputFile);
	}
}
