/*!
 * Contained Sticky Scroll v1.1
 * http://blog.echoenduring.com/2010/11/15/freebie-contained-sticky-scroll-jquery-plugin/
 *
 * Copyright 2010, Matt Ward
*/
(function( $ ){

  $.fn.containedStickyScroll = function( options ) {
  
	var defaults = {  
		unstick : true,
		easing: 'linear',
		duration: 500,
		queue: false,
		closeChar: '^',
		closeTop: 0,
		closeRight: 0  
	}  
                  
	var options =  $.extend(defaults, options);
    var $getObject = $(this).selector;
    
	if(options.unstick == true){  
		this.css('position','relative');
		this.append('<a class="scrollFixIt">' + options.closeChar + '</a>');
		jQuery($getObject + ' .scrollFixIt').css('position','absolute');
		jQuery($getObject + ' .scrollFixIt').css('top',options.closeTop + 'px');
		jQuery($getObject + ' .scrollFixIt').css('right',options.closeTop + 'px');
		jQuery($getObject + ' .scrollFixIt').css('cursor','pointer');
		jQuery($getObject + ' .scrollFixIt').click(function() {
			jQuery($getObject).animate({ top: "0px" },
				{ queue: options.queue, easing: options.easing, duration: options.duration });
			jQuery(window).unbind();
			jQuery('.scrollFixIt').remove();
		});
	} 
  	jQuery(window).scroll(function() {
        if(jQuery(window).scrollTop() > (jQuery($getObject).parent().offset().top) &&
           (jQuery($getObject).parent().height() + jQuery($getObject).parent().position().top - 30) > (jQuery(window).scrollTop() + jQuery($getObject).height())){
        	jQuery($getObject).animate({ top: (jQuery(window).scrollTop() - jQuery($getObject).parent().offset().top) + "px" }, 
            { queue: options.queue, easing: options.easing, duration: options.duration });
        }
        else if(jQuery(window).scrollTop() < (jQuery($getObject).parent().offset().top)){
        	jQuery($getObject).animate({ top: "0px" },
            { queue: options.queue, easing: options.easing, duration: options.duration });
        }
	});

  };
})( jQuery );
