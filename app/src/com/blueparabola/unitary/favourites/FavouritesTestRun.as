package com.blueparabola.unitary.favourites
{
	import com.blueparabola.unitary.phpunit.library.PHPUnitResult;
	
	import flash.desktop.NativeProcess;
	import flash.desktop.NativeProcessStartupInfo;
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.events.NativeProcessExitEvent;
	import flash.events.ProgressEvent;
	import flash.filesystem.File;
	import flash.filesystem.FileMode;
	import flash.filesystem.FileStream;
	import flash.system.Capabilities;
	
	import mx.collections.ArrayCollection;
	import mx.controls.Alert;

	[Event(name="favouritesRunComplete", type="flash.events.Event")]
	[Event(name="favouritesRunError", type="com.blueparabola.unitary.favourites.FavouriteRunErrorEvent")]
	public class FavouritesTestRun extends EventDispatcher {		
		static public const FavouritesRunComplete:String = "favouritesRunComplete";
		
		protected var _runDate:Date;
		
		protected var _testCount:int;
		
		protected var _testsSucceeded:int;
		protected var _testsFailed:int;
		protected var _testsWarned:int;
		
		protected var _executionResults:ArrayCollection;
		
		[Bindable]
		public function get runDate():Date {
			return _runDate;
		}
		
		protected function set runDate(value:Date):void {
			_runDate = value;
		}
		
		[Bindable]
		public function get testCount():int {
			return _testCount;
		}
		
		protected function set testCount(value:int):void {
			_testCount = value;
		}
		
		[Bindable]
		public function get testsSucceeded():int {
			return _testsSucceeded;
		}
		
		protected function set testsSucceeded(value:int):void {
			_testsSucceeded = value;
		}
		
		[Bindable]
		public function get testsFailed():int {
			return _testsFailed;
		}
		
		protected function set testsFailed(value:int):void {
			_testsFailed = value;
		}
		
		[Bindable]
		public function get testsWarned():int {
			return _testsWarned;
		}
		
		protected function set testsWarned(value:int):void {
			_testsWarned = value;
		}
		
		[Bindable]
		public function get executionResults():ArrayCollection {
			return _executionResults;
		}
		
		protected function set executionResults(value:ArrayCollection):void {
			_executionResults = value;
		}
		
		[Bindable]
		public function get chartData():ArrayCollection {
			return new ArrayCollection([
				{
					label : "Successes",
					value : this.testsSucceeded
				},
				{
					label : "Failures",
					value : this.testsFailed
				}
			]);
		}
		
		public function set chartData(value:ArrayCollection):void {
			// Do nothing.
		}
		
		
		[Bindable]
		public function get coverageChartData():ArrayCollection {
			return new ArrayCollection([
				{
					label : "Covered",
					value : this.linesOfCodeCovered
				},
				{
					label : "Uncovered",
					value : this.linesOfCodeInProject - this.linesOfCodeCovered
				}
			]);
		}
		
		public function set coverageChartData(value:ArrayCollection):void {
			// Do nothing.
		}
		
		protected var _cloverLog:String;
		
		[Bindable]
		public function set cloverLog(value:String):void {
			_cloverLog = value;
			
			var xml:XML = new XML(_cloverLog);
			
			linesOfCodeInProject = xml.project.metrics.@loc
			linesOfCodeCovered = xml.project.metrics.@ncloc;
		}
		
		public function get cloverLog():String {
			return _cloverLog;
		}
		
		[Bindable]
		public var linesOfCodeInProject:int;
		
		[Bindable]
		public var linesOfCodeCovered:int;
		
		protected var _hadError:Boolean = false;
		
		[Bindable]
		public function get hadError():Boolean {
			return _hadError;
		}
		
		protected function set hadError(value:Boolean):void {
			_hadError = value;
		}
		
		protected var _hasRun:Boolean = false;
		
		// Class methods
		
		public function FavouritesTestRun() {
		}
		
		public function run(testScriptPath:String, additionalParameters:Array = null):void {
			if (_hasRun) {
				throw new Error("Attempt to recycle used FavouriteTestRun instance");
			}
			
			_hasRun = true;
			
			runDate = new Date();
			
			if (!NativeProcess.isSupported) {
				throw new Error("Invalid compilation options: native processes are not supported.");
			}
			
			var executableFile:File;
			
			if (Capabilities.os.toLowerCase().indexOf("mac") > -1) {
				// On a Mac, PHP comes bundled in.
				executableFile = new File("/usr/bin/php");
			} else if (Capabilities.os.toLowerCase().indexOf("win") > -1) {
				executableFile = File.applicationDirectory.resolvePath("com/blueparabola/unitary/phpunit/php/windows/php.exe");
			} else {
				throw new Error("Unknown operating system «" + Capabilities.os + "»");
			}
			
			var phpUnitDirectory:File = File.applicationDirectory.resolvePath("com/blueparabola/unitary/phpunit/PEAR");
			var phpUnitFile:File = phpUnitDirectory.resolvePath("phpunit.php");

			var nativeProcessStartupInfo:NativeProcessStartupInfo = new NativeProcessStartupInfo();

			nativeProcessStartupInfo.executable = executableFile;
			nativeProcessStartupInfo.arguments = new Vector.<String>;
			
			// Set up path to Xdebug on OS X
			
			if (Capabilities.os.toLowerCase().indexOf("mac") > -1) {
				nativeProcessStartupInfo.arguments.push("-d");
				nativeProcessStartupInfo.arguments.push("zend_extension=" + File.applicationDirectory.resolvePath("com/blueparabola/unitary/phpunit/php/osx/xdebug.so").nativePath);
			} else if (Capabilities.os.toLowerCase().indexOf("win") > -1) {
				nativeProcessStartupInfo.arguments.push("-d");
				nativeProcessStartupInfo.arguments.push("zend_extension=" + File.applicationDirectory.resolvePath("com/blueparabola/unitary/phpunit/php/windows/xdebug.dll").nativePath);
			}
			
			// Set up include path to point to our copy of PEAR
			nativeProcessStartupInfo.arguments.push("-d");
			
			if (Capabilities.os.toLowerCase().indexOf("mac") > -1) {
				nativeProcessStartupInfo.arguments.push("include_path=.:" + phpUnitDirectory.nativePath);
			} else if (Capabilities.os.toLowerCase().indexOf("win") > -1) {
				nativeProcessStartupInfo.arguments.push("include_path=.;" + phpUnitDirectory.nativePath);
			}
			
			// Add the path to PHPUnit's entry point
			
			nativeProcessStartupInfo.arguments.push(phpUnitFile.nativePath);
			
			// Add a request for a Clover coverage log
			
			var coverageLogLocation:File = File.createTempFile();
			
			nativeProcessStartupInfo.arguments.push("--coverage-clover");
			nativeProcessStartupInfo.arguments.push(coverageLogLocation.nativePath);
			
			// Add a request for a JSON log
			
			var jsonLogLocation:File = File.createTempFile();
			
			nativeProcessStartupInfo.arguments.push("--log-json");
			nativeProcessStartupInfo.arguments.push(jsonLogLocation.nativePath);
			
			// Add the path to the testing script
			
			nativeProcessStartupInfo.arguments.push(testScriptPath);
			
			// Add any additional parameters
			
			for each (var additionalParameter:String in additionalParameters) {
				nativeProcessStartupInfo.arguments.push(additionalParameter);
			}
			
			var nativeProcess:NativeProcess = new NativeProcess();
			
			var phpOutput:String = "";
			var phpError:String = "";

			nativeProcess.addEventListener(ProgressEvent.STANDARD_OUTPUT_DATA, function(e:ProgressEvent):void {
				phpOutput += nativeProcess.standardOutput.readUTFBytes(nativeProcess.standardOutput.bytesAvailable);
			});
			
			nativeProcess.addEventListener(ProgressEvent.STANDARD_ERROR_DATA, function(e:ProgressEvent):void {
				phpError += nativeProcess.standardError.readUTFBytes(nativeProcess.standardError.bytesAvailable);
			});
			
			nativeProcess.addEventListener(NativeProcessExitEvent.EXIT, function(e:NativeProcessExitEvent):void {
				if (e.exitCode == 255) {
					// We do not handle PHP errors (e.g.: compile-time failures)
					// other than to tell the user.
					
					hadError = true;
					dispatchEvent(new FavouriteRunErrorEvent(phpOutput));
				} else {
					var fileStream:FileStream = new FileStream;
					
					fileStream.open(jsonLogLocation, FileMode.READ);
					
					var resultArray:Array = PHPUnitResult.unitResultsFromJSONString(fileStream.readUTFBytes(fileStream.bytesAvailable));
					
					executionResults = new ArrayCollection;
					
					for each (var unitResult:PHPUnitResult in resultArray) {
						if (unitResult.isTest) {
							testCount += 1;
						}
						
						if (unitResult.isSuccessfulTest) {
							testsSucceeded += 1;
						}
						
						if (unitResult.isFailedTest) {
							testsFailed += 1;
						}
						
						//TODO: Warnings?
						
						executionResults.addItem(unitResult);
					}			
					
					fileStream.open(coverageLogLocation, FileMode.READ);
					
					cloverLog = fileStream.readUTFBytes(fileStream.bytesAvailable);
					
					dispatchEvent(new Event(FavouritesTestRun.FavouritesRunComplete));
				}
			})
			
			nativeProcess.start(nativeProcessStartupInfo);
		}
	}
}