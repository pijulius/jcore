/*
	bMap - © 2010 Darren Dignam
	darren.dignam@blocsoft.com
	http://www.blocsoft.com/bMap
	Released under the GPL License
	http://www.gnu.org/licenses/gpl-3.0.txt
*/

/*
	This plugin requires the Google Maps API and jQuery (1.2.3+) to be loaded.
	This plugin does not try to wrap the entire Google Maps API. I created this to aid marker management, AJAX overlays, and custom icons.
	There have been some issues with multiple maps on one page. Please let me know of any bugs you find. For lots of maps, perhaps use iFrames, or try jMap.
*/

/*
	The jQuery extending function
	options:
		mapCenter		array		latitude and logitude
		mapZoom			integer		starting zoom
		mapCanvas		string		the ID of the div to render the map
		mapSidebar		string		the ID of the div to render a clickable sidebar of the markers
		mapLayerbar		string		the ID of the div to render a clickable sidebar of the layers visible on the map
		mapType			integer		the type of map tiles to load (G_NORMAL_MAP G_SATELLITE_MAP G_HYBRID_MAP) refer to the google docs
		loadMsg			string		message shown on map during AJAX operations
		markers			object		bMap markers object, used to pre-draw some markers on the map
		icons			array		array of GIcon objects
*/
(function($){
	$.fn.bMap = function(options) {
		eachOptions = options;
		return this.each(function() {
			obj = $(this);
			var defaults = {  
				mapCenter: [51,0],
				mapZoom: 1,
				mapCanvas: obj.attr('id'),
				mapSidebar: "none",
				mapLayerbar: "none",
				mapType: google.maps.MapTypeId.ROADMAP,
				loadMsg: "<h2>Loading...</h2>"
			}; 
			var thisOptions = $.extend(defaults, eachOptions);	
			obj.data("bMap",  new bMap(thisOptions) );
		});
		return this;
	};
})(jQuery);
/*
	The bMap object constructor
	options:
		mapCenter		array		latitude and logitude
		mapZoom			integer		starting zoom
		mapCanvas		string		the ID of the div to render the map
		mapSidebar		string		the ID of the div to render a clickable sidebar of the markers
		mapLayerbar		string		the ID of the div to render a clickable sidebar of the layers visible on the map
		mapType			integer		the type of map tiles to load (G_NORMAL_MAP G_SATELLITE_MAP G_HYBRID_MAP) refer to the google docs
		loadMsg			string		message shown on map during AJAX operations
		markers			object		bMap markers object, used to pre-draw some markers on the map
		icons			array		array of GIcon objects
*/
function bMap(options) {
	//check for compatibility
//	if (!GBrowserIsCompatible()) {
//		alert('This browser is unable to render the map!');
//		return;
//	}
	//object defaults
	var defaults = {  
		mapCenter: [51,0],
		mapZoom: 1,
		mapCanvas: "map",
		mapSidebar: "none",
		mapLayerbar: "none",
		mapType: google.maps.MapTypeId.ROADMAP,
		loadMsg: "<h2>Loading...</h2>"
	};  
	//overide with options
	var options = $.extend(defaults, options);
	
	//sidebar control
	this.mapSidebar = options.mapSidebar;
	this.useSidebar = (this.mapSidebar != "none") ? true : false;
	//layerbar control
	this.mapLayerbar = options.mapLayerbar;
	this.useLayerbar = (this.mapLayerbar != "none") ? true : false;
	
	//Layer array of ojects {data:[],name,type}
	this.layerMgrArray = [];
	
	//render map
//	this.map = new GMap2(document.getElementById(options.mapCanvas));
//	this.map.setUIToDefault();
//	this.map.setCenter(new GLatLng(options.mapCenter[0],options.mapCenter[1]), options.mapZoom);
//	this.map.setMapType(options.mapType);
	var mapOptions = {
		zoom: options.mapZoom,
		center: new google.maps.LatLng(options.mapCenter[0],options.mapCenter[1]),
		mapTypeId: options.mapType
	};	
	this.map = new google.maps.Map(document.getElementById(options.mapCanvas),mapOptions);

	
	this.mapCanvas = options.mapCanvas;
	//add the loading div
	$("#"+this.mapCanvas).append("<div id='"+this.mapCanvas+"bMapLoadMsg' class='bMapLoadMsg'>"+options.loadMsg+"</div>");
	//position loading
	$("#"+this.mapCanvas+"bMapLoadMsg").css('left', ($("#map").width()/2)-50 );
	$("#"+this.mapCanvas+"bMapLoadMsg").css('top', ($("#map").height()/2)-50 );	

	//get custom icon array
	if(options.icons){
		this.icons=options.icons;
	}
	//draw markers from init vars????
	if (options.markers){
		this.insertMarkers(options.markers);
	}
	
	//infowindow object
	this.infoWindow = new google.maps.InfoWindow();	
	
	//tools
	this.geoCoder = new google.maps.Geocoder();
};

/*
	Adds a layer of markers to the map
	incomingMarkers:
		name			string		visual name for the layer, appears in layerbar
		type			string		describes the type of layer
		visible			string		("true"/"false") if the layer should start visible
		data			array		array of bMap marker objects, rendered as GMarkers
		icon			integer		renders the marker with a custom icon from the icons array
*/
bMap.prototype.insertMarkers = function(incomingMarkers){
	tmpThis = this;
	var newIndex = tmpThis.layerMgrArray.length;
 
	//function defaults
	var markersDefaults = {  
		name:   "Layer"+newIndex,
		type:   "marker",
		visible:"true"
	};  
	//overide with options
	var incomingMarkers = $.extend(markersDefaults, incomingMarkers);	
		
	tmpThis.layerMgrArray[newIndex]=incomingMarkers;           //build object	
 
	tmpThis.layerMgrArray[newIndex].toggleLayer = function(){
			if( this.visible!="false" ){
				this.visible="false";
				for(i=0, j=this.data.length; i < j; i++){
					this.data[i].setMap(null);
					tmpThis.infoWindow.close();
				}
				$('#bMapLyr'+newIndex).addClass('bLyrHide');
				$('#'+tmpThis.mapSidebar+' div[rel^="'+newIndex+'"]').slideUp('fast');
				return false;
			}else{
				this.visible="true";
				for(i=0, j=this.data.length; i < j; i++){
					this.data[i].setMap(tmpThis.map);
				}
				$('#bMapLyr'+newIndex).removeClass('bLyrHide');
				$('#'+tmpThis.mapSidebar+' div[rel^="'+newIndex+'"]').slideDown('fast');
				return true;
			}
	}		
 
	//build pointer array
	jQuery.each(incomingMarkers.data, function(i,val) {
		//layerMgrArray[refID].push( new GLatLng(val.lat, val.lng) );
		var point = new google.maps.LatLng(val.lat, val.lng);
		//create point on map, possible with custom icon
		if (val.icon){
			tmpThis.layerMgrArray[newIndex].data[i] = new google.maps.Marker({position:point,map:tmpThis.map,icon:tmpThis.icons[parseInt(val.icon)]});
		}else{
			tmpThis.layerMgrArray[newIndex].data[i] = new google.maps.Marker({position:point,map:tmpThis.map});
		}
		//if supplied with marker text, create popup...
		if(val.title){
			var html = "<h2>"+val.title+"</h2>";
			if(val.body){html+=val.body}
			google.maps.event.addListener(tmpThis.layerMgrArray[newIndex].data[i], "click", function(){
				
				tmpThis.infoWindow.setContent(html);
				tmpThis.infoWindow.open(tmpThis.map, tmpThis.layerMgrArray[newIndex].data[i]);				
				
				$('#'+tmpThis.mapSidebar+' div').removeClass('bSideSelect');
				//highlight the related sidebar div
				$('#'+tmpThis.mapSidebar+' div[rel="'+newIndex+' '+i+'"]').addClass('bSideSelect');
				//scroll sidebar to item
				var x = $('#'+tmpThis.mapSidebar).scrollTop() + $('#'+tmpThis.mapSidebar+' div[rel="'+newIndex+' '+i+'"]').position().top - (  $('#'+tmpThis.mapSidebar).offset().top  + ( $('#'+tmpThis.mapSidebar).height()/2 ) );
				$('#'+tmpThis.mapSidebar).animate({scrollTop: x}, 500);				
			});
			google.maps.event.addListener(tmpThis.layerMgrArray[newIndex].data[i], "infowindowclose", function(){
				$('#'+tmpThis.mapSidebar+' div[rel="'+newIndex+' '+i+'"]').removeClass('bSideSelect');			
			});			
		}	
		//tmpThis.map.addOverlay( tmpThis.layerMgrArray[newIndex].data[i] );
		//populate sidebar.... need the closure, so this BAD way will do
		if(tmpThis.useSidebar){
			$('<div rel="'+newIndex+' '+i+'">' + val.title + '</div>').click(function(){ 
				google.maps.event.trigger(tmpThis.layerMgrArray[newIndex].data[i], 'click');
				//the following line is duplicated in the marker click, but might not always fire there...
				$('#'+tmpThis.mapSidebar+' div').removeClass('bSideSelect');
				$(this).addClass('bSideSelect');
			}).appendTo("#"+tmpThis.mapSidebar);
		}
	});
	if(incomingMarkers.visible!="true"){
		for(i=0, j=tmpThis.layerMgrArray[newIndex].data.length; i < j; i++){
			tmpThis.layerMgrArray[newIndex].data[i].setMap(null);
			$('#'+tmpThis.mapSidebar+' div[rel^="'+newIndex+'"]').hide();
		}
		$('#bMapLyr'+newIndex).addClass('bLyrHide');
		return false;
	}
	this.refreshLayerbar();
	//return the object, so that chaining is possible with this function ;)
	return this;	
}

/*
	Adds a layer of markers to the map from an external file (AJAX post)
	pointsOptions:
		serviceURL		string		the target page for the AJAX request
		action			string		string posted to the AJAX page
		vars			array		array of strings posted to the target page to aid the developer
*/
bMap.prototype.AJAXMarkers = function(pointsOptions){
	//function defaults
	var pointsDefaults = {  
		serviceURL: "mapService.php",
		action: "getMarkers",
		vars: [],
		options:{}
	};  
	//overide with options
	var pointsOptions = $.extend(pointsDefaults, pointsOptions); 
	var tmpThis = this;
	$("#"+this.mapCanvas+"bMapLoadMsg").show();
	//post an AJAX for the points
	$.getJSON(pointsOptions.serviceURL, { action: pointsOptions.action, vars: pointsOptions.vars }, function(json){
		pointsOptions.options = $.extend(json, pointsOptions.options); 
		tmpThis.insertMarkers(pointsOptions.options);
		$("#"+tmpThis.mapCanvas+"bMapLoadMsg").hide();
	});
	//return the object, so that chaining is possible with this function ;)
	return this;
};
//////////////////////  polyline and polygon /////////////////////
/*
	Adds a polyline layer to the map
	insertLine:
		name			string		visual name for the layer, appears in layerbar
		type			string		describes the type of layer
		visible			string		("true"/"false") if the layer should start visible
		data			array		array of latlng used to construct line
		color			string		line color, HTML style strings
		weight			integer		the line thickness
		opacity			real		0.0 - 1.0 line opacity
*/
bMap.prototype.insertLine = function(incomingLine){
	tmpThis = this;
	var newIndex = tmpThis.layerMgrArray.length;
	
	//function defaults
	var lineDefaults = {  
		name:   "Layer"+newIndex,
		type:   "line",
		visible:"true",
		color:  "#00F",
		weight: 5,
		opacity:1
	};  
	//overide with options
	var incomingLine = $.extend(lineDefaults, incomingLine);	
		
	tmpThis.layerMgrArray[newIndex]=incomingLine;           //build object	
	
	tmpThis.layerMgrArray[newIndex].toggleLayer = function(){
			if( this.visible!="false" ){
				this.visible="false";
				this.data.setMap(null);
				$('#bMapLyr'+newIndex).addClass('bLyrHide');
				return false;
			}else{
				this.visible="true";
				this.data.setMap(tmpThis.map);			
				$('#bMapLyr'+newIndex).removeClass('bLyrHide');
				return true;
			}
	}		

	//build polyline
	var tmpArray=[];
	//build LatLng array
	jQuery.each(incomingLine.data, function(i,val) {
		tmpArray.push( new google.maps.LatLng(val.lat, val.lng) );
	});

	tmpThis.layerMgrArray[newIndex].data = new google.maps.Polyline({
		path: tmpArray,
		strokeColor: tmpThis.layerMgrArray[newIndex].color,
		strokeOpacity: parseFloat(tmpThis.layerMgrArray[newIndex].opacity),
		strokeWeight: parseInt(tmpThis.layerMgrArray[newIndex].weight)
	});

	tmpThis.layerMgrArray[newIndex].data.setMap(tmpThis.map);	
	
	if(incomingLine.visible!="true"){tmpThis.layerMgrArray[newIndex].data.setMap(null);}
	this.refreshLayerbar();
	//return the object, so that chaining is possible with this function ;)
	return this;	
}
/*
	Adds a polyLine layer from an AJAX source
	pointsOptions:
		serviceURL		string		the target page for the AJAX request
		action			string		string posted to the AJAX page
		vars			array		array of strings posted to the target page to aid the developer
*/
bMap.prototype.AJAXLine = function(lineOptions){
	//function defaults
	var lineDefaults = {  
		serviceURL: "mapService.php",
		action: "getLine",
		vars: []	
	};  
	//overide with options
	var lineOptions = $.extend(lineDefaults, lineOptions); 
	var tmpThis = this;
	$("#"+this.mapCanvas+"bMapLoadMsg").show();
	//post an AJAX for the points
	$.post(lineOptions.serviceURL, { action: lineOptions.action, vars: lineOptions.vars }, function(json){
		tmpThis.insertLine(json);
		$("#"+tmpThis.mapCanvas+"bMapLoadMsg").hide();
	}, "json");
	//return the object, so that chaining is possible with this function ;)
	return this;
};
/*
	Adds a polyline layer to the map
	insertPolygon:
		name			string		visual name for the layer, appears in layerbar
		type			string		describes the type of layer
		visible			string		("true"/"false") if the layer should start visible
		data			array		array of latlng used to construct polygon edges
		color			string		polygon color, HTML style strings
		weight			integer		the edge line thickness
		opacity			real		0.0 - 1.0 interior opacity
*/
bMap.prototype.insertPolygon = function(incomingPolygon){
	tmpThis = this;
	var newIndex = tmpThis.layerMgrArray.length;
	
	//function defaults
	var polygonDefaults = {  
		name:   "Layer"+newIndex,
		type:   "polygon",
		visible:"true",
		color:  "#00F",
		weight: 5,
		opacity:0.5
	};  
	//overide with options
	var incomingPolygon = $.extend(polygonDefaults, incomingPolygon);	
		
	tmpThis.layerMgrArray[newIndex]=incomingPolygon;           //build object

	tmpThis.layerMgrArray[newIndex].toggleLayer = function(){
			if( this.visible!="false" ){
				this.visible="false";
				this.data.setMap(null);
				$('#bMapLyr'+newIndex).addClass('bLyrHide');
				return false;
			}else{
				this.visible="true";
				this.data.setMap(tmpThis.map);			
				$('#bMapLyr'+newIndex).removeClass('bLyrHide');
				return true;
			}
	}		
	
	//build polygon
	var tmpArray=[];
	//build LatLng array
	jQuery.each(incomingPolygon.data, function(i,val) {
		tmpArray.push( new google.maps.LatLng(val.lat, val.lng) );
	});

	tmpThis.layerMgrArray[newIndex].data = new google.maps.Polygon({
		path: tmpArray,
		strokeColor: tmpThis.layerMgrArray[newIndex].color,
		strokeOpacity:1,
		strokeWeight: parseInt(tmpThis.layerMgrArray[newIndex].weight),
		fillColor: tmpThis.layerMgrArray[newIndex].color,
		fillOpacity: parseFloat(tmpThis.layerMgrArray[newIndex].opacity)
	});
	tmpThis.layerMgrArray[newIndex].data.setMap(tmpThis.map);
	
	if(incomingPolygon.visible!="true"){tmpThis.layerMgrArray[newIndex].data.setMap(null);}	
	
	this.refreshLayerbar();
	//return the object, so that chaining is possible with this function ;)
	return this;
}
/*
	Adds a polygon layer from an AJAX source
	pointsOptions:
		serviceURL		string		the target page for the AJAX request
		action			string		string posted to the AJAX page
		vars			array		array of strings posted to the target page to aid the developer
*/
bMap.prototype.AJAXPolygon = function(polygonOptions){
	//function defaults
	var polygonDefaults = {  
		serviceURL: "mapService.php",
		action: "getPolygon",
		vars: []	
	};  
	//overide with options
	var polygonOptions = $.extend(polygonDefaults, polygonOptions); 
	var tmpThis = this;
	$("#"+this.mapCanvas+"bMapLoadMsg").show();
	//post an AJAX for the points
	$.post(polygonOptions.serviceURL, { action: polygonOptions.action, vars: polygonOptions.vars }, function(json){
		tmpThis.insertPolygon(json);
		$("#"+tmpThis.mapCanvas+"bMapLoadMsg").hide();
	}, "json");
	//return the object, so that chaining is possible with this function ;)
	return this;
};
/*
	removes all the layers fromt he map, and tidys the arrays ect
*/
bMap.prototype.removeAllLayers = function(){
	//loop all sets of markers, and then loop all markers, and remove everything
	for(i=0, j=this.layerMgrArray.length; i < j; i++){
		if (this.layerMgrArray[i].type=="marker"){
			for(i2=0, j2=this.layerMgrArray[i].data.length; i2 < j2; i2++){
				this.layerMgrArray[i].data[i2].setMap(null);
			}
			this.infoWindow.close();
		} else { 
			this.layerMgrArray[i].data.setMap(null);
		}
		
		this.layerMgrArray[i].data = 0;
		if(this.useSidebar){
			$('#'+this.mapSidebar+' div[rel^="'+i+'"]').remove();
		}
	}
	this.layerMgrArray.length = 0;
	this.refreshLayerbar();
	//return the object, so that chaining is possible with this function ;)
	return this;	
}
/*
	remove a single layer from the map (and tidy)
		i				integer		the array index of the laer to remove
*/
bMap.prototype.removeLayer = function(i){
	//loop all markers, and remove everything
	if (this.layerMgrArray[i].type=="marker"){
		for(i2=0, j2=this.layerMgrArray[i].data.length; i2 < j2; i2++){
			this.layerMgrArray[i].data[i2].setMap(null);
		}
	} else { 
		this.layerMgrArray[i].data.setMap(null);
	}

	this.layerMgrArray[i].data = 0;
	if(this.useSidebar){
		$('#'+this.mapSidebar+' div[rel^="'+i+'"]').remove();
	}
	this.refreshLayerbar();
	//return the object, so that chaining is possible with this function ;)
	return this;	
}
/*
	removes the last item in the layer array
*/
bMap.prototype.popLayer = function(){
	var i = this.layerMgrArray.length - 1; //the actual index is one less than length
	var tmpArray = this.layerMgrArray.pop();
	
	if (tmpArray.type=="marker"){
		for(i2=0, j2=tmpArray.data.length; i2 < j2; i2++){
			tmpArray.data[i2].setMap(null);
		}
	} else { 
		tmpArray.data.setMap(null);
	}
	
	tmpArray.data = 0;
	if(this.useSidebar){
		$('#'+this.mapSidebar+' div[rel^="'+i+'"]').remove();
	}
	this.refreshLayerbar();
	//return the object, so that chaining is possible with this function ;)
	return this;	
}
/*
	Removes the first ACTIVE item from the layers array (remaining items DO NOT get shifted afterwards)
*/
bMap.prototype.shiftLayer = function(){
	for(i3=0, j3=this.layerMgrArray.length; i3 < j3; i3++){
		if(this.layerMgrArray[i3].data != 0){
			var i = i3;
			break;
		}
	}
	if (this.layerMgrArray[i].type=="marker"){
		for(i2=0, j2=this.layerMgrArray[i].data.length; i2 < j2; i2++){
			this.layerMgrArray[i].data[i2].setMap(null);
		}
	} else { 
		this.layerMgrArray[i].data.setMap(null);
	}
	
	//cleanup
	this.layerMgrArray[i].data = 0;
	this.layerMgrArray[i].name="";this.layerMgrArray[i].type="";
	if(this.useSidebar){
		$('#'+this.mapSidebar+' ^"'+i+'"]').remove();
	}
	this.refreshLayerbar();
	//return the object, so that chaining is possible with this function ;)
	return this;	
}
/*
	After changes to the layers, this updates the sidebar
*/
bMap.prototype.refreshLayerbar = function(){
	if(this.mapLayerbar){
		var tmpThis = this;
		$("#"+this.mapLayerbar).html('');
		for(var i=0, j=this.layerMgrArray.length; i < j; i++){
			if(this.layerMgrArray[i].data != 0){
				//if(this.layerMgrArray[i].visible!="true"){tmpStr="class='bLyrHide' "}
				if( this.layerMgrArray[i].visible!="false" ){
					var tmpStr ="";
				}else{
					tmpStr ="class='bLyrHide' ";
				}	
				$("<div "+tmpStr+"id='bMapLyr"+i+"' rel='"+i+"'>"+this.layerMgrArray[i].name+"</div>").click(function(){
						//using the objects toggle
						tmpThis.layerMgrArray[ $(this).attr("rel") ].toggleLayer();
				}).appendTo("#"+this.mapLayerbar);
			}
		}
	}
}
/*
	A wrapper for the client geocoder that takes an address and centers the map at that location
		addr			string		the target address/post code/zip code/location
*/
bMap.prototype.centerAtAddress = function(addr){
	var tmpThis = this;

    this.geoCoder.geocode({'address': addr}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
			tmpThis.map.setCenter(results[0].geometry.location);
		}
	});	
	//return the object, so that chaining is possible with this function ;)
	return this;	
}
