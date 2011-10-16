package com.blueparabola.unitary.favourites
{
	import flash.events.EventDispatcher;
	import flash.net.SharedObject;
	import flash.net.registerClassAlias;
	
	import mx.collections.ArrayCollection;
	import mx.utils.ObjectProxy;

	public class FavouritesModel extends EventDispatcher {
		
		[Bindable]
		public var projectName:String;
		
		[Bindable]
		public var projectLocation:String;
		
		[Bindable]
		public var additionalCommandLineParameters:String;
		
		protected static var _GlobalFavourites:ArrayCollection;

		public static function get GlobalFavourites():ArrayCollection {
			if (!_GlobalFavourites) {
				registerClassAlias("FavouritesModelAlias", com.blueparabola.unitary.favourites.FavouritesModel);
				
				var so:SharedObject = SharedObject.getLocal("com.blueparabola.unitary.favourites");
				var favourites:Array = so.data["favourites"];
				
				if (!favourites) {
					favourites = new Array();
				}
				
				_GlobalFavourites = new ArrayCollection(favourites);
			}
			
			return _GlobalFavourites;
		}
		
		public function FavouritesModel() {
			super();
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
	}
}