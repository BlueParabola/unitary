package com.blueparabola.unitary.favourites
{
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.net.SharedObject;
	import flash.net.registerClassAlias;
	
	import mx.collections.ArrayCollection;
	import mx.utils.ObjectProxy;

	[Event(name="favouriteRunError", type="com.blueparabola.unitary.favourites.FavouriteRunErrorEvent")]
	[Event(name="favouritesRunComplete", type="flash.events.Event")]
	public class FavouritesModel extends EventDispatcher {
		// Static global favourites array
		
		protected static var _GlobalFavourites:ArrayCollection;		
		
		public static function get GlobalFavourites():ArrayCollection {
			if (!_GlobalFavourites) {
				registerClassAlias("FavouritesModelAlias", com.blueparabola.unitary.favourites.FavouritesModel);
				registerClassAlias("ArrayCollection", mx.collections.ArrayCollection);
				registerClassAlias("FavouritesTestRun", com.blueparabola.unitary.favourites.FavouritesTestRun);
				
				var so:SharedObject = SharedObject.getLocal("com.blueparabola.unitary.favourites");
				var favourites:Array = so.data["favourites"];
				
				if (!favourites) {
					favourites = new Array();
				}
				
				_GlobalFavourites = new ArrayCollection(favourites);
			}
			
			return _GlobalFavourites;
		}
		
		
		// Class properties
		
		protected var _runHistory:ArrayCollection;
		
		[Bindable]
		public var projectName:String;
		
		[Bindable]
		public var projectLocation:String;
		
		[Bindable]
		public var additionalCommandLineParameters:String;
		
		[Bindable]
		public function get runHistory():ArrayCollection {
			return _runHistory;
		}
		
		protected function set runHistory(value:ArrayCollection):void {
			_runHistory = value;
		}
		
		public function FavouritesModel() {
			super();
			
			runHistory = new ArrayCollection();
		}
		
		public function save():void {
			if (!FavouritesModel._GlobalFavourites.contains(this)) {
				FavouritesModel._GlobalFavourites.addItem(this);
			}
			
			var so:SharedObject = SharedObject.getLocal("com.blueparabola.unitary.favourites");
			so.data["favourites"] = FavouritesModel.GlobalFavourites.source;
		}
		
		public function remove():void {
			if (FavouritesModel._GlobalFavourites.contains(this)) {
				FavouritesModel._GlobalFavourites.removeItemAt(FavouritesModel._GlobalFavourites.getItemIndex(this));
				
				var so:SharedObject = SharedObject.getLocal("com.blueparabola.unitary.favourites");
				so.data["favourites"] = FavouritesModel.GlobalFavourites.source;
			}
		}
		
		public function runNewTest():void {
			var testRun:FavouritesTestRun = new FavouritesTestRun();
			
			var self:FavouritesModel = this;
			
			testRun.addEventListener("favouritesRunComplete", function(e:Event):void {
				dispatchEvent(e);
			});
			
			testRun.addEventListener(FavouriteRunErrorEvent.favouriteRunErrorEvent, function(e:FavouriteRunErrorEvent):void {
				dispatchEvent(new FavouriteRunErrorEvent(e.errorString));
			});
			
			testRun.run(projectLocation, additionalCommandLineParameters.split(" "));
		}
	}
}