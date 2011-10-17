package com.blueparabola.unitary.phpunit.library
{
	import com.adobe.serializers.json.JSONDecoder;
	
	import mx.collections.ArrayCollection;

	public class PHPUnitResult
	{
		[Bindable]
		public var eventType:String;
		
		[Bindable]
		public var testSuite:String;
		
		[Bindable]
		public var tests:int;
		
		[Bindable]
		public var testName:String;
		
		[Bindable]
		public var status:String;
		
		[Bindable]
		public var time:Number;
		
		[Bindable]
		public var executionTrace:ArrayCollection;
		
		[Bindable]
		public var message:String;
		
		public function get isTest():Boolean {
			return (status !== null);
		}
		
		public function get isSuccessfulTest():Boolean {
			return (status === "pass");
		}
		
		public function get isFailedTest():Boolean {
			return (status === "fail");
		}
		
		
		public function PHPUnitResult() {
		}
		
		static public function unitResultFromObject(object:Object):PHPUnitResult {
			var result:PHPUnitResult = new PHPUnitResult;
			
			result.eventType = object["event"];
			result.testSuite = object["suite"];
			
			if (object["tests"]) {
				result.tests = object["tests"];
			}
			
			if (object["test"]) {
				result.testName = object["test"];
			}
			
			if (object["status"]) {
				result.status = object["status"];
			}
			
			if (object["time"]) {
				result.time = object["time"];
			}
			
			if (object["trace"]) {
				result.executionTrace = object["trace"];
			}
			
			if (object["message"]) {
				result.message = object["message"];
			}
			
			return result;
		}
		
		static public function unitResultsFromJSONString(jsonString:String):Array {
			var inputObject:Array = ((new JSONDecoder).decode("[" + jsonString + "]")).source;
			
			var result:Array = new Array();
			
			for each (var object:Object in inputObject) {
				result.push(unitResultFromObject(object));
			}
			
			return result;
		}
	}
}