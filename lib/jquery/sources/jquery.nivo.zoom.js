/*
 * jQuery Nivo Zoom v1.0
 * http://nivozoom.dev7studios.com
 *
 * Copyright 2010, Gilbert Pellegrom
 * Free to use and abuse under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 * 
 * April 2010
 */

(function($) {

	$.fn.nivoZoom = function(options) {

		//Defaults are below
		var settings = $.extend({}, $.fn.nivoZoom.defaults, options);
		
		if(settings.overlay){
			//Disable overly in IE7 due to z-index bug
			if(!($.browser.msie && $.browser.version.substr(0,1)<8)){
				$('body').prepend('<div id="nivoOverlay" />');
				$('#nivoOverlay').css({
					position:'fixed',
					top:0,
					left:0,
					width:'100%',
					height:'100%',
					background:settings.overlayColor,
					opacity:settings.overlayOpacity,
					'z-index':90,
					display:'none'
				});
			}	
		}

		return this.each(function(){
			var context = $(this);
			
			var nivoZooms = $('a.nivoZoom', context);
			nivoZooms.each(function(){
				var link = $(this);
				if(link.is('a')){
					var img = $(this).find('img:first');
					
					//Setup link
					link.css({
						position:'relative',
						display:'inline-block'
					});
					link.attr('title','Click to zoom');
					
					//Add ZoomHover
					link.append('<div class="nivoZoomHover" />');
					var nivoZoomHover = $('.nivoZoomHover', link);
					nivoZoomHover.css('opacity','0');					
					link.hover(function(){
						if(!link.hasClass('zoomed')){
							nivoZoomHover.stop().animate({ opacity:settings.zoomHoverOpacity }, 300);
						}
					}, function(){
						if(!nivoZoomHover.hasClass('loading')){
							nivoZoomHover.stop().animate({ opacity:0 }, 300);
						}
					});
					
					link.bind('click', function(){
						//Check to see if large image is loaded
						if($('img.nivoLarge', link).length == 0){
							nivoZoomHover.addClass('loading');
							loadImg(img, link, function(){
								nivoZoomHover.removeClass('loading');
								doZoom(img, link, nivoZoomHover); 
							});
						} else {
							doZoom(img, link, nivoZoomHover);
						}
						return false;
					});
				}
			});
			
		});
		
		function doZoom(img, link, nivoZoomHover){
			var imgLarge = $('img.nivoLarge', link);
			if(link.hasClass('zoomed')){
				//Hide Overlay
				if(settings.overlay) $('#nivoOverlay').fadeOut(settings.speed/2);
				//Hide Caption
				if($('.nivoCaption', link).length > 0){
					$('.nivoCaption', link).fadeOut(settings.speed/2);
				}
				//Hide Image
				imgLarge.fadeOut(settings.speed/2, function(){
					img.animate({ opacity:1 }, settings.speed/2);
				});
				link.removeClass('zoomed');
			} else {
				//Show Overlay
				if(settings.overlay) $('#nivoOverlay').fadeIn(settings.speed/2);
				//Hide ZoomHover
				nivoZoomHover.css('opacity','0');
				//Show Image
				img.animate({ opacity:0 }, settings.speed/2, function(){
					imgLarge.fadeIn(settings.speed/2, function(){
						showCaption(img, imgLarge, link);
					});
				});
				link.addClass('zoomed');
			}
		}
		
		function showCaption(img, imgLarge, link){
			if($('.nivoCaption', link).length > 0){
				var nivoCaption = $('.nivoCaption:first', link);
				if(!nivoCaption.hasClass('nivo-processed')){
					//Calculate the image dimensions
					var imgWidth = img.width();
					if(imgWidth == 0) imgWidth = img.attr('width');
					var imgHeight = img.height();
					if(imgHeight == 0) imgHeight = img.attr('height');
					var bigImgWidth = imgLarge.width();
					if(bigImgWidth == 0) bigImgWidth = imgLarge.attr('width');
					var bigImgHeight = imgLarge.height();
					if(bigImgHeight == 0) bigImgHeight = imgLarge.attr('height');
					nivoCaption.css({
						width:bigImgWidth,
						opacity:settings.captionOpacity
					});
					
					if(link.hasClass('topRight')){
						nivoCaption.css({
							top:(bigImgHeight - nivoCaption.outerHeight()) + 'px',
							right:'0px'
						});
					} 
					else if(link.hasClass('bottomRight')){
						nivoCaption.css({
							bottom:'0px',
							right:'0px'
						});
					}
					else if(link.hasClass('bottomLeft')){
						nivoCaption.css({
							bottom:'0px',
							left:'0px'
						});
					} 
					else if(link.hasClass('center')){
						nivoCaption.css({
							top:Math.ceil(imgHeight/2 - bigImgHeight/2) + (bigImgHeight - nivoCaption.outerHeight()) + 'px',
							left:(imgWidth/2 - bigImgWidth/2) +'px'
						});
					} else {
						nivoCaption.css({
							top:(bigImgHeight - nivoCaption.outerHeight()) + 'px',
							left:'0px'
						});
					}
					nivoCaption.addClass('nivo-processed');
				}
				nivoCaption.fadeIn(settings.speed/2);
			}
		}
		
		function loadImg(img, link, callback){
			//Load large image
			var newImg = new Image();
			$(newImg).load(function (){   
				$(this).addClass('nivoLarge');
				$(this).css({
					position:'absolute',
					display:'none',
					'z-index':99
				});
				
				//Fix IE7 z-index bug
				if(navigator.userAgent.match(/MSIE \d\.\d+/)){
					link.css('z-index','100');
				}
				
				if(link.hasClass('topRight')){
					$(this).css({
						top:'0px',
						right:'0px'
					});
				} 
				else if(link.hasClass('bottomRight')){
					$(this).css({
						bottom:'0px',
						right:'0px'
					});
				}
				else if(link.hasClass('bottomLeft')){
					$(this).css({
						bottom:'0px',
						left:'0px'
					});
				} 
				else if(link.hasClass('center')){
					//Calculate the image dimensions
					var imgWidth = img.width();
					if(imgWidth == 0) imgWidth = img.attr('width');
					var imgHeight = img.height();
					if(imgHeight == 0) imgHeight = img.attr('height');
					var bigImgWidth = $(this).width();
					if(bigImgWidth == 0) bigImgWidth = $(this).attr('width');
					var bigImgHeight = $(this).height();
					if(bigImgHeight == 0) bigImgHeight = $(this).attr('height');
					$(this).css({
						top:(imgHeight/2 - bigImgHeight/2) +'px',
						left:(imgWidth/2 - bigImgWidth/2) +'px'
					});
				} else {
					$(this).css({
						top:'0px',
						left:'0px'
					});
				}
				
				$(this).attr('title','Click to close');
				link.append($(this));
				callback.call(this);
			}).attr('src', link.attr('href'));
		}
	};
	
	//Default settings
	$.fn.nivoZoom.defaults = {
		speed:500,
		zoomHoverOpacity:0.8,
		overlay:false,
		overlayColor:'#333',
		overlayOpacity:0.5,
		captionOpacity:0.8
	};
		
})(jQuery);