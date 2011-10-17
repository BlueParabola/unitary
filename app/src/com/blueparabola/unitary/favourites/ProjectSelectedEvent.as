package com.blueparabola.unitary.favourites
{
	import flash.events.Event;
	
	public class ProjectSelectedEvent extends Event
	{
		public static const FavouritesSelected:String = "FavouritesSelected"
			
		public var favouritesModel:FavouritesModel;
		
		public function ProjectSelectedEvent(favouritesModel:FavouritesModel) {
			this.favouritesModel = favouritesModel;
			
			super("FavouritesSelected", false, false);
		}
	}
}