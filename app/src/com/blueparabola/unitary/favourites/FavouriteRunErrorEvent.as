package com.blueparabola.unitary.favourites
{
	import flash.events.Event;
	
	public class FavouriteRunErrorEvent extends Event
	{
		public static const favouriteRunErrorEvent:String = "favouriteRunError";
		
		public var errorString:String;
		
		public function FavouriteRunErrorEvent(errorString:String) {
			super(favouriteRunErrorEvent);
			this.errorString = errorString;
		}
	}
}