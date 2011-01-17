/**
 * jQuery lightBox plugin
 * This jQuery plugin was inspired and based on Lightbox 2 by Lokesh Dhakar (http://www.huddletogether.com/projects/lightbox2/)
 * and adapted to me for use like a plugin from jQuery.
 * @name jquery-lightbox-0.5.js
 * @author Leandro Vieira Pinho - http://leandrovieira.com
 * @version 0.5
 * @date April 11, 2008
 * @category jQuery plugin
 * @copyright (c) 2008 Leandro Vieira Pinho (leandrovieira.com)
 * @license CC Attribution-No Derivative Works 2.5 Brazil - http://creativecommons.org/licenses/by-nd/2.5/br/deed.en_US
 * @example Visit http://leandrovieira.com/projects/jquery/lightbox/ for more informations about this jQuery plugin
 */

// Offering a Custom Alias suport - More info: http://docs.jquery.com/Plugins/Authoring#Custom_Alias
(function(jQuery) {
	/**
	 * jQuery is an alias to jQuery object
	 *
	 */
	jQuery.fn.lightBox = function(settings) {
		// Settings to configure the jQuery lightBox plugin how you like
		settings = jQuery.extend({
			ajaxContent:			false,
			hideDetails:			false,
			slideShow:				0,
			slideShowTimeOut:		5000,
			disableSlideShow: 		false,
			// Configuration related to overlay
			overlayBgColor: 		'#000',		// (string) Background color to overlay; inform a hexadecimal value like: #RRGGBB. Where RR, GG, and BB are the hexadecimal values for the red, green, and blue values of the color.
			overlayOpacity:			0.8,		// (integer) Opacity value to overlay; inform: 0.X. Where X are number from 0 to 9
			// Configuration related to navigation
			fixedNavigation:		false,		// (boolean) Boolean that informs if the navigation (next and prev button) will be fixed or not in the interface.
			disableNavigation: 		false,
			disableDownload:		false,
			maximumImageWidth:		960,
			// Configuration related to container image box
			containerBorderSize:	0,			// (integer) If you adjust the padding in the CSS for the container, #lightbox-container-image-box, you will need to update this value
			containerResizeSpeed:	400,		// (integer) Specify the resize duration of container image. These number are miliseconds. 400 is default.
			// Configuration related to texts in caption. For example: Image 2 of 8. You can alter either "Image" and "of" texts.
			txtImage:				'Image',	// (string) Specify text "Image"
			txtOf:					'of',		// (string) Specify text "of"
			// Configuration related to keyboard navigation
			keyToClose:				'c',		// (string) (c = close) Letter to close the jQuery lightBox interface. Beyond this letter, the letter X and the SCAPE key is used to.
			keyToPrev:				'p',		// (string) (p = previous) Letter to show the previous image
			keyToNext:				'n',		// (string) (n = next) Letter to show the next image.
			// Don't alter these variables in any way
			imageArray:				[],
			activeImage:			0
		},settings);
		// Caching the jQuery object with all elements matched
		var jQueryMatchedObj = this; // This, in this context, refer to jQuery object
		/**
		 * Initializing the plugin calling the start function
		 *
		 * @return boolean false
		 */
		function _initialize() {
			_start(this,jQueryMatchedObj); // This, in this context, refer to object (link) which the user have clicked
			return false; // Avoid the browser following the link
		}
		
		function _walk_image() {
			if (settings.activeImage == ( settings.imageArray.length -1 ))
				settings.activeImage = -1;
			
			settings.activeImage = settings.activeImage + 1;
			_set_image_to_view();
		}
		/**
		 * Start the jQuery lightBox plugin
		 *
		 * @param objClicked object The object (link) whick the user have clicked
		 * @param jQueryMatchedObj object The jQuery object with all elements matched
		 */
		function _start(objClicked,jQueryMatchedObj) {
			// Hime some elements to avoid conflict with overlay in IE. These elements appear above the overlay.
			jQuery('embed, object, select').css({ 'visibility' : 'hidden' });
			// Call the function to create the markup structure; style some elements; assign events in some elements.
			_set_interface();
			// Unset total images in imageArray
			settings.imageArray.length = 0;
			// Unset image active information
			settings.activeImage = 0;
			// We have an image set? Or just an image? Let's see it.
			if ( jQueryMatchedObj.length == 1 ) {
				var imgtitle = objClicked.getAttribute('title');
				
				if (!imgtitle || imgtitle == '')
					imgtitle = objClicked.getAttribute('original-title');
				
				settings.imageArray.push(new Array(objClicked.getAttribute('href'),imgtitle));
			} else {
				// Add an Array (as many as we have), with href and title atributes, inside the Array that storage the images references		
				for ( var i = 0; i < jQueryMatchedObj.length; i++ ) {
					var imgtitle = jQueryMatchedObj[i].getAttribute('title');
					
					if (!imgtitle || imgtitle == '')
						imgtitle = jQueryMatchedObj[i].getAttribute('original-title');
					
					settings.imageArray.push(new Array(jQueryMatchedObj[i].getAttribute('href'),imgtitle));
				}
			}
			while ( settings.imageArray[settings.activeImage][0] != objClicked.getAttribute('href') ) {
				settings.activeImage++;
			}
			// Call the function that prepares image exibition
			_set_image_to_view(true);
		}
		
		function _set_interface() {
			// Apply the HTML markup into body tag
			jQuery('body').append(
				'<div id="jquery-overlay">'+
				'</div>' +
				'<div id="jquery-lightbox">' +
					'<div id="lightbox-container-image-box" style="z-index: 101">' +
						'<div id="lightbox-container-image">' +
							(settings.ajaxContent?
								'<div id="lightbox-image"></div>':
								'<img id="lightbox-image" />') +
								'<div style="" id="lightbox-nav">' +
									'<a href="javascript://" id="lightbox-nav-btnPrev" title="Prev"></a>' +
									'<a href="javascript://" id="lightbox-nav-btnNext" title="Next"></a>' +
								'</div>' +
							'<div id="lightbox-loading">' +
								'<a href="javascript://" id="lightbox-loading-link"></a>' +
							'</div>' +
						'</div>' +
					'</div>' +
					'<div id="lightbox-container-image-data-box">' +
						'<a href="javascript://" id="lightbox-secNav-btnSlideshow" title="Slideshow"></a>' +
						'<a href="javascript://" id="lightbox-secNav-btnClose" title="Close"></a>' +
						'<a href="javascript://" id="lightbox-secNav-btnDownload" title="Download"></a>' +
						'<a href="javascript://" id="lightbox-secNav-btnNext" title="Next"></a>' +
						'<a href="javascript://" id="lightbox-secNav-btnPrev" title="Prev"></a>' +
						'<div id="lightbox-container-image-data">' +
							'<div id="lightbox-image-details">' +
								'<span id="lightbox-image-details-caption"></span>' +
								'<span id="lightbox-image-details-currentNumber"></span>' + 
							'</div>' +
						'</div>' +
					'</div>' +
				'</div>');
			
			//For compatibility with older jCore releases
			if (jQuery('#lightbox-container-image-box').css('border-top-style') == 'none')
				settings.containerBorderSize = 10;
				
			// Get page sizes
			var arrPageSizes = ___getPageSize();
			// Style overlay and show it
			jQuery('#jquery-overlay').css({
				backgroundColor:	settings.overlayBgColor,
				opacity:			settings.overlayOpacity,
				width:				arrPageSizes[0],
				height:				arrPageSizes[1]
			}).fadeIn();
			// Get page scroll
			var arrPageScroll = ___getPageScroll();
			// Calculate top and left offset for the jquery-lightbox div object and show it
			jQuery('#jquery-lightbox').css({
				top:	arrPageScroll[1] + (arrPageSizes[3] / 10),
				left:	arrPageScroll[0]
			}).show();
			// Assigning click events in elements to close overlay
			jQuery('#jquery-overlay,#jquery-lightbox').click(function(event) {
				if (event.target === this)
					_finish();									
			});
			
			if (!settings.disableSlideShow) {
				jQuery('#lightbox-secNav-btnSlideshow').click(function() {
					if(settings.slideShow) {
						jQuery(this).removeClass('pause');
						clearTimeout(settings.slideShow);
						settings.slideShow = 0;
					} else {
						jQuery(this).addClass('pause');
						settings.slideShow = setTimeout(function() {_walk_image()}, 
							settings.slideShowTimeOut);
					}
					
					return false;
				});
			}
			
			if (!settings.disableDownload) {
				jQuery('#lightbox-secNav-btnDownload').click(function() {
					picsrc = jQuery('#lightbox-image').attr('src').replace(/view=/, 'download=');
					
					if (picsrc.indexOf('download=') != -1)
						window.location = picsrc;
					else
						window.open(picsrc);
					
					return false;
				});
			}
			
			// Assign the _finish function to lightbox-loading-link and lightbox-secNav-btnClose objects
			jQuery('#lightbox-loading-link,#lightbox-secNav-btnClose').click(function() {
				_finish();
				return false;
			});
			// If window was resized, calculate the new overlay dimensions
			jQuery(window).resize(function() {
				// Get page sizes
				var arrPageSizes = ___getPageSize();
				// Style overlay and show it
				jQuery('#jquery-overlay').css({
					width:		arrPageSizes[0],
					height:		arrPageSizes[1]
				});
				// Get page scroll
				var arrPageScroll = ___getPageScroll();
				// Calculate top and left offset for the jquery-lightbox div object and show it
				jQuery('#jquery-lightbox').css({
					top:	arrPageScroll[1] + (arrPageSizes[3] / 10),
					left:	arrPageScroll[0]
				});
			});
		}
		/**
		 * Prepares image exibition; doing a image's preloader to calculate it's size
		 *
		 */
		function _set_image_to_view(firststart) { // show the loading
			jQuery('#lightbox-nav-btnNext,#lightbox-nav-btnPrev,#lightbox-secNav-btnNext,#lightbox-secNav-btnPrev')
				.css('display', 'none');
			
			// Show the loading
			if (!settings.slideShow)
				jQuery('#lightbox-loading').show();
			
			if (!settings.ajaxContent && !settings.containerBorderSize && !firststart)
				jQuery('#lightbox-image')
					.clone(false)
					.attr('id', 'lightbox-tmp-image')
					.prependTo('#lightbox-container-image');
			
			jQuery('#lightbox-image,#lightbox-container-image-data-box,#lightbox-image-details-currentNumber')
				.hide();
			
			if (settings.ajaxContent) {
				url = settings.imageArray[settings.activeImage][0];
		
				if (url.indexOf('ajax=') == -1) {
					if (url.indexOf('?') == -1)
						url = url+'?ajax=1';
					else
						url = url+'&ajax=1';
				}
				
				jQuery.get(url, function(data){
					jc = jQuery('#lightbox-image').html(data).jCore();
					_resize_container_image_box(jc.width(), jc.height());
				});
				
			} else {
				// Image preload process
				var objImagePreloader = new Image();
				objImagePreloader.onload = function() {
					jQuery('#lightbox-image').attr('src',settings.imageArray[settings.activeImage][0]);
					// Perfomance an effect in the image container resizing it
					
					if (settings.maximumImageWidth && objImagePreloader.width > settings.maximumImageWidth) {
						widthpercent = settings.maximumImageWidth*100/objImagePreloader.width;
						objImagePreloader.width = settings.maximumImageWidth;
						objImagePreloader.height = widthpercent*objImagePreloader.height/100;
						
						jQuery('#lightbox-image').attr('width', objImagePreloader.width);
						jQuery('#lightbox-image').attr('height', objImagePreloader.height);
					} else {
						jQuery('#lightbox-image').attr('width', objImagePreloader.width);
						jQuery('#lightbox-image').attr('height', objImagePreloader.height);
					}
					
					jQuery('#lightbox-image')
						.css({'position':'absolute', 'left': '50%', 'margin-left': '-'+(objImagePreloader.width/2)+'px'});
					
					_resize_container_image_box(objImagePreloader.width,objImagePreloader.height);
					//	clear onLoad, IE behaves irratically with animated gifs otherwise
					objImagePreloader.onload=function(){};
				};
			
				if (settings.imageArray[settings.activeImage])
					objImagePreloader.src = settings.imageArray[settings.activeImage][0];
			}
		};
		/**
		 * Perfomance an effect in the image container resizing it
		 *
		 * @param intImageWidth integer The image's width that will be showed
		 * @param intImageHeight integer The image's height that will be showed
		 */
		function _resize_container_image_box(intImageWidth,intImageHeight) {
			// Get current width and height
			var intCurrentWidth = jQuery('#lightbox-container-image-box').width();
			var intCurrentHeight = jQuery('#lightbox-container-image-box').height();
			// Get the width and height of the selected image plus the padding
			var intWidth = (intImageWidth + (settings.containerBorderSize * 2)); // Plus the image's width and the left and right padding value
			var intHeight = (intImageHeight + (settings.containerBorderSize * 2)); // Plus the image's height and the left and right padding value
			// Diferences
			var intDiffW = intCurrentWidth - intWidth;
			var intDiffH = intCurrentHeight - intHeight;
			
			if (!settings.ajaxContent && !settings.containerBorderSize)
				_show_image();
			
			jQuery('#lightbox-container-image-box')
				.animate({ width: intWidth, height: intHeight }, settings.containerResizeSpeed, function() { 
						if (settings.ajaxContent || settings.containerBorderSize) 
							_show_image(); 
						});
			
			if ( ( intDiffW == 0 ) && ( intDiffH == 0 ) ) {
				if ( jQuery.browser.msie ) {
					___pause(250);
				} else {
					___pause(100);	
				}
			} 
			jQuery('#lightbox-container-image-data-box').css({ width: intImageWidth });
			
			if (!settings.fixedNavigation)
				jQuery('#lightbox-nav-btnPrev,#lightbox-nav-btnNext').css({ height: intImageHeight + (settings.containerBorderSize * 2) });
		};
		/**
		 * Show the prepared image
		 *
		 */
		function _show_image() {
			if (!settings.slideShow)
				jQuery('#lightbox-loading').hide();
			
			jQuery('#lightbox-image').fadeIn(function() {
				jQuery('#lightbox-tmp-image').remove();
			
				if (!settings.hideDetails)
					_show_image_data();
				
				if (!settings.disableNavigation)
					_set_navigation();
					
				if (!settings.disableSlideShow && settings.slideShow)
					settings.slideShow = setTimeout(function() {_walk_image()}, 
						settings.slideShowTimeOut);
			});
			
			if (settings.slideShow)
				_preload_neighbor_images();
		};
		/**
		 * Show the image information
		 *
		 */
		function _show_image_data() {
			jQuery('#lightbox-container-image-data-box').slideDown('fast');
			jQuery('#lightbox-image-details-caption').hide();
			
			if (settings.disableSlideShow || settings.imageArray.length <= 1) {
				jQuery('#lightbox-secNav-btnSlideshow').hide();
				jQuery('#lightbox-container-image-data').css('padding-left', '0');
			}
			
			if (settings.disableDownload) {
				jQuery('#lightbox-secNav-btnDownload').hide();
			}
			
			if ( settings.imageArray[settings.activeImage][1] ) {
				jQuery('#lightbox-image-details-caption').html(settings.imageArray[settings.activeImage][1]).show();
			}
			// If we have a image set, display 'Image X of X'
			if (settings.imageArray.length > 1 ) {
				jQuery('#lightbox-image-details-currentNumber').html(settings.txtImage + ' ' + ( settings.activeImage + 1 ) + ' ' + settings.txtOf + ' ' + settings.imageArray.length).show();
			}		
		}
		/**
		 * Display the button navigations
		 *
		 */
		function _set_navigation() {
			if (settings.fixedNavigation) {
				jQuery('#lightbox-nav').css('display', 'none');
			} else {
				jQuery('#lightbox-nav').show();
				jQuery('#lightbox-secNav-btnPrev,#lightbox-secNav-btnNext').css('display', 'none');
			}
			
			if (settings.imageArray.length <= 1)
				return;
			
			if (settings.fixedNavigation)
				navb = '#lightbox-secNav-btnPrev';
			else
				navb = '#lightbox-nav-btnPrev';
			
			jQuery(navb).css('display', 'block');
			jQuery(navb)
				.unbind()
				.bind('click',function() {
					if (settings.activeImage != 0)
						settings.activeImage = settings.activeImage - 1;
					else
						settings.activeImage = ( settings.imageArray.length -1 );
					
					_set_image_to_view();
					return false;
				});
			
			if (settings.fixedNavigation)
				navb = '#lightbox-secNav-btnNext';
			else
				navb = '#lightbox-nav-btnNext';
			
			jQuery(navb).css('display', 'block');
			jQuery(navb)
				.unbind()
				.bind('click',function() {
					if ( settings.activeImage != ( settings.imageArray.length -1 ) )
						settings.activeImage = settings.activeImage + 1;
					else
						settings.activeImage = 0;
					
					_set_image_to_view();
					return false;
				});
			
			// Enable keyboard navigation
			_enable_keyboard_navigation();
		}
		/**
		 * Enable a support to keyboard navigation
		 *
		 */
		function _enable_keyboard_navigation() {
			jQuery(document).keydown(function(objEvent) {
				_keyboard_action(objEvent);
			});
		}
		/**
		 * Disable the support to keyboard navigation
		 *
		 */
		function _disable_keyboard_navigation() {
			jQuery(document).unbind();
		}
		/**
		 * Perform the keyboard actions
		 *
		 */
		function _keyboard_action(objEvent) {
			// To ie
			if ( objEvent == null ) {
				keycode = event.keyCode;
				escapeKey = 27;
			// To Mozilla
			} else {
				keycode = objEvent.keyCode;
				escapeKey = objEvent.DOM_VK_ESCAPE;
			}
			// Get the key in lower case form
			key = String.fromCharCode(keycode).toLowerCase();
			// Verify the keys to close the ligthBox
			if ( ( key == settings.keyToClose ) || ( key == 'x' ) || ( keycode == escapeKey ) ) {
				_finish();
			}
			// Verify the key to show the previous image
			if ( ( key == settings.keyToPrev ) || ( keycode == 37 ) ) {
				// If we're not showing the first image, call the previous
				if ( settings.activeImage != 0 ) {
					settings.activeImage = settings.activeImage - 1;
					_set_image_to_view();
					_disable_keyboard_navigation();
				}
			}
			// Verify the key to show the next image
			if ( ( key == settings.keyToNext ) || ( keycode == 39 ) ) {
				// If we're not showing the last image, call the next
				if ( settings.activeImage != ( settings.imageArray.length - 1 ) ) {
					settings.activeImage = settings.activeImage + 1;
					_set_image_to_view();
					_disable_keyboard_navigation();
				}
			}
		}
		/**
		 * Preload prev and next images being showed
		 *
		 */
		function _preload_neighbor_images() {
			if ( (settings.imageArray.length -1) > settings.activeImage ) {
				objNext = new Image();
				objNext.src = settings.imageArray[settings.activeImage + 1][0];
			}
			if ( settings.activeImage > 0 ) {
				objPrev = new Image();
				objPrev.src = settings.imageArray[settings.activeImage -1][0];
			}
		}
		/**
		 * Remove jQuery lightBox plugin HTML markup
		 *
		 */
		function _finish() {
			if (settings.slideShow)
				jQuery('#lightbox-secNav-btnSlideshow').click();
			
			jQuery('#jquery-lightbox').remove();
			jQuery('#jquery-overlay').fadeOut(function() { jQuery('#jquery-overlay').remove(); });
			// Show some elements to avoid conflict with overlay in IE. These elements appear above the overlay.
			jQuery('embed, object, select').css({ 'visibility' : 'visible' });
		}
		/**
		 / THIRD FUNCTION
		 * getPageSize() by quirksmode.com
		 *
		 * @return Array Return an array with page width, height and window width, height
		 */
		function ___getPageSize() {
			var xScroll, yScroll;
			if (window.innerHeight && window.scrollMaxY) {	
				xScroll = window.innerWidth + window.scrollMaxX;
				yScroll = window.innerHeight + window.scrollMaxY;
			} else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
				xScroll = document.body.scrollWidth;
				yScroll = document.body.scrollHeight;
			} else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
				xScroll = document.body.offsetWidth;
				yScroll = document.body.offsetHeight;
			}
			var windowWidth, windowHeight;
			if (self.innerHeight) {	// all except Explorer
				if(document.documentElement.clientWidth){
					windowWidth = document.documentElement.clientWidth; 
				} else {
					windowWidth = self.innerWidth;
				}
				windowHeight = self.innerHeight;
			} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
				windowWidth = document.documentElement.clientWidth;
				windowHeight = document.documentElement.clientHeight;
			} else if (document.body) { // other Explorers
				windowWidth = document.body.clientWidth;
				windowHeight = document.body.clientHeight;
			}	
			// for small pages with total height less then height of the viewport
			if(yScroll < windowHeight){
				pageHeight = windowHeight;
			} else { 
				pageHeight = yScroll;
			}
			// for small pages with total width less then width of the viewport
			if(xScroll < windowWidth){	
				pageWidth = xScroll;		
			} else {
				pageWidth = windowWidth;
			}
			arrayPageSize = new Array(pageWidth,pageHeight,windowWidth,windowHeight);
			return arrayPageSize;
		};
		/**
		 / THIRD FUNCTION
		 * getPageScroll() by quirksmode.com
		 *
		 * @return Array Return an array with x,y page scroll values.
		 */
		function ___getPageScroll() {
			var xScroll, yScroll;
			if (self.pageYOffset) {
				yScroll = self.pageYOffset;
				xScroll = self.pageXOffset;
			} else if (document.documentElement && document.documentElement.scrollTop) {	 // Explorer 6 Strict
				yScroll = document.documentElement.scrollTop;
				xScroll = document.documentElement.scrollLeft;
			} else if (document.body) {// all other Explorers
				yScroll = document.body.scrollTop;
				xScroll = document.body.scrollLeft;	
			}
			arrayPageScroll = new Array(xScroll,yScroll);
			return arrayPageScroll;
		};
		 /**
		  * Stop the code execution from a escified time in milisecond
		  *
		  */
		 function ___pause(ms) {
			var date = new Date(); 
			curDate = null;
			do { var curDate = new Date(); }
			while ( curDate - date < ms);
		 };
		// Return the jQuery object for chaining. The unbind method is used to avoid click conflict when the plugin is called more than once
		return this.unbind('click').click(_initialize);
	};
})(jQuery); // Call and execute the function immediately passing the jQuery object