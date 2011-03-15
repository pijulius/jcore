/************************************************************************
*************************************************************************
@Name :       	jRating - jQuery Plugin
@Revison :    	2.0
@Date : 		26/01/2011
@Author:     	 Surrel Mickael (www.myjqueryplugins.com - www.msconcept.fr) 
@License :		 Open Source - MIT License : http://www.opensource.org/licenses/mit-license.php
@Modified :		Modified by Istvan Petres : http://jcore.net
 
**************************************************************************
*************************************************************************/
jQuery.rating = {
	build : function(options)
	{
        var defaults = {
			/** Boolean vars **/
			step: true, // if true,  mouseover binded star by star,
			isDisabled:false,
			
			/** Integer vars **/
			length:5, // number of star to display
			decimalLength : 0, // number of decimals.. Max 3, but you can complete the function 'getNote'
			rateMax : 10, // maximal rate - integer from 0 to 9999 (or more)
			
			/** Functions **/
			onSuccess : null,
			onError : null
        };   
		
		if(this.length>0)
		return jQuery(this).each(function(i) {
			var opts = $.extend(defaults, options);       
			var $this = $(this);
			var newWidth = 0;
			var starWidth = $this.width();
			var starHeight = 0;
			
			if($this.hasClass('disabled') || opts.isDisabled)
				var jDisabled = true;
			else
				var jDisabled = false;
				
			if (starWidth != 16)
				starWidth = parseInt(starWidth/opts.length);
			
			starHeight = $this.height();
			
			var average = parseFloat($this.attr('data')); // get the average
			var phpPath = $this.attr('data-url'); // get the url to rate
			var widthRatingContainer = starWidth*opts.length; // Width of the Container
			var widthColor = average/opts.rateMax*widthRatingContainer; // Width of the color Container
			var widthStep = Math.round(widthRatingContainer/opts.rateMax);
			
			var $average = 
				jQuery('<a>', 
				{
					'class' : 'star-rating-average',
					css:{
						display: 'inline-block',
						width:widthColor,
						height:starHeight,
						'background-repeat': 'repeat-x'
					}
				}).appendTo($this);
			
			var $quotient = 
				jQuery('<a>', 
				{
					'class' : 'star-rating-default',
					css:{
						display: 'inline-block',
						width:widthRatingContainer-widthColor,
						height:starHeight,
						'background-position': '100% 0',
						'background-repeat': 'repeat-x'
					}
				}).appendTo($this);
			
			$average.css({'-moz-opacity': 1.0, '-webkit-opacity': 1.0, 'opacity': 1.0,	'filter': 'none'});
			$this.css({overflow:'hidden', 'white-space': 'nowrap', width: widthRatingContainer});
			
			if (typeof($this.tipsy) != 'undefined')
				$this.tipsy();
			
			/** Events & functions **/
			if(!jDisabled)
			$this.bind(
			{
				mouseenter : function(e){
					var realOffsetLeft = findRealLeft(this);
					var relativeX = e.pageX - realOffsetLeft;
				},
				mouseover : function(e){
					$this.css('cursor','pointer');	
				},
				mouseleave : function(){
					$this.css('cursor','default');
					$average.width(widthColor);
					$quotient.width(widthRatingContainer-widthColor);					
				},
				mousemove : function(e){
					var realOffsetLeft = findRealLeft(this);
					var relativeX = e.pageX - realOffsetLeft;
					if(opts.step) newWidth = Math.floor(relativeX/widthStep)*widthStep + widthStep;
					else newWidth = relativeX;
					$average.width(newWidth);					
					$quotient.width(widthRatingContainer-newWidth);
										
					if (typeof($this.tipsy) != 'undefined') {
						$this.attr('title', $this.attr('original-title').replace(/[0-9]+/, getNote(newWidth)))
							.tipsy('show');
					} else {
						$this.attr('title', $this.attr('title').replace(/[0-9]+/, getNote(newWidth)));
					}
				},
				click : function(e){
					$this.unbind().css('cursor','default').addClass('disabled');
					
					if (typeof($this.tipsy) != 'undefined')
						$this.tipsy();
					
					e.preventDefault();
					var rate = getNote(newWidth);
					$average.width(newWidth);
					$quotient.width(widthRatingContainer-newWidth);					
					
					$.post(phpPath,{
							rate : rate
						},
						function(data) {
							jQuery.loading(false);
							jQuery.loading(true, {html: data, text: '', max: 5000});
						}
					);
				}
			});
			
			function getNote(relativeX) {
				var noteBrut = parseFloat((relativeX*100/widthRatingContainer)*opts.rateMax/100);
				switch(opts.decimalLength) {
					case 1 :
						var note = Math.ceil(noteBrut*10)/10;
						break;
					case 2 :
						var note = Math.ceil(noteBrut*100)/100;
						break;
					case 3 :
						var note = Math.ceil(noteBrut*1000)/1000;
						break;
					default :
						var note = Math.ceil(noteBrut*1)/1;
				}
				return note;
			};
			
			function findRealLeft(obj) {
			  if( !obj ) return 0;
			  return obj.offsetLeft + findRealLeft( obj.offsetParent );
			};
		});
	}
}; jQuery.fn.rating = jQuery.rating.build;