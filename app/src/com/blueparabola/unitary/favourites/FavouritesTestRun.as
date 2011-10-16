package com.blueparabola.unitary.favourites
{
	import flash.desktop.NativeProcess;
	import flash.desktop.NativeProcessStartupInfo;
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.events.NativeProcessExitEvent;
	import flash.events.ProgressEvent;
	import flash.filesystem.File;
	import flash.system.Capabilities;
	
	import mx.controls.Alert;

	[Event(name="favouritesRunComplete", type="flash.events.Event")]
	[Event(name="favouritesRunError", type="com.blueparabola.unitary.favourites.FavouriteRunErrorEvent")]
	public class FavouritesTestRun extends EventDispatcher {		
		static public const FavouritesRunComplete:String = "favouritesRunComplete";
		
		protected var _runDate:Date;
		
		protected var _testsSucceeded:int;
		protected var _testsFailed:int;
		protected var _testsWarned:int;
		
		[Bindable]
		public function get runDate():Date {
			return _runDate;
		}
		
		protected function set runDate(value:Date):void {
			_runDate = value;
		}
		
		[Bindable]
		public function get testsSucceeded():int {
			return _testsSucceeded;
		}
		
		protected function set testsSucceeded(value:int):void {
			_testsSucceeded = value;
		}
		
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
				throw new Error("No Windows support yet.");
			} else {
				throw new Error("Unknown operating system «" + Capabilities.os + "»");
			}
			
			var phpUnitDirectory:File = File.applicationDirectory.resolvePath("com/blueparabola/unitary/phpunit/PEAR");
			var phpUnitFile:File = phpUnitDirectory.resolvePath("phpunit.php");

			var nativeProcessStartupInfo:NativeProcessStartupInfo = new NativeProcessStartupInfo();

			nativeProcessStartupInfo.executable = executableFile;
			nativeProcessStartupInfo.arguments = new Vector.<String>;
			
			// Set up include path to point to our copy of PEAR
			nativeProcessStartupInfo.arguments.push("-d");
			nativeProcessStartupInfo.arguments.push("include_path=.:" + phpUnitDirectory.nativePath);
			
			// Add the path to PHPUnit's entry point
			
			nativeProcessStartupInfo.arguments.push(phpUnitFile.nativePath);
			
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
				if (e.exitCode != 0) {
					hadError = true;
					dispatchEvent(new FavouriteRunErrorEvent(phpOutput));
				} else {
					dispatchEvent(new Event(FavouritesTestRun.FavouritesRunComplete));
				}
			})
			
			nativeProcess.start(nativeProcessStartupInfo);
		}
	}
}