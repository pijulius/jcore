/**
 * Copyright (c) 2010 Seamus P. H. Leahy, http://moronicbajebus.com
 *
 * This code is released under the MIT License (which basically means
 * you are free to use it.
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *  
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *  
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 *
 */
 
 /**
  * 
  * jStackmenu Methods
  *   toggle( [showFlag], [callback] )
  *   show( [callback] )
  *   hide( [callback] )
  *
  * jQuery UI inherited methods
  *   enable( )
  *   disable( )
  *   destroy( )
  *   option( )
  *   
  * Events
  *   jstackmenushow
  *   jstackmenushowBefore
  *   jstackmenuhide
  *   jstackmenuhideBefore
  *   
  * Options
  *   show: function( event ){ } 
  *   hide: function( event ){ }
  *   radius: 1000, any positive number
  *   clockwise: true, or false for counter-clockwise
  *   direction: 'top', or 'right', 'bottom', 'left'
  *   time: 500, in milliseconds
  * 
  */
(function( $ ){
	// Some constant values
	var undefined; // To make sure undefined is actually undefined
	var radsToDeg = 180 / Math.PI;
	var nofn = function(){};
	
	// Some helper functions
	var proxy = function( context, fn ){
		return function( ){
			if( $.isFunction( fn ) ){
				return fn.apply( context, arguments );
			} else {
				return context[ fn ].apply( context, arguments );
			}
		};
	};
	var getNow = function( ){
		return ( new Date() ).getMilliseconds( ); 
	};
	
	
	var log = function( ){
		// do nothing by default
		// uncomment the next lines for debugging
		/*
		debugger;
		if( console && console.log ){
			console.log.apply( console, arguments );
		}
		*/
	};
	
	
	
	// Check what CSS features are supported by the browser
	// Set flags for if transforms and if transition is supported
	//
	// The Opera support is commented out because Opera 10.5b does support
	// transforms, but element.style.OTransform returns an object instead of a
	// string like the others do. As of writing this, there is not documentation
	// for how Opera's handling. Perhapes once Opera 10.5 is released, 
	// support can be added.
	//
	var docEl = document.documentElement, supportedStyles, supportsTransform = false, supportsTransition = false ;
	
	if( docEl && ( supportedStyles = docEl.style ) ){
		
		supportsTransform = !!( typeof supportedStyles.transform == 'string'
			|| typeof supportedStyles.MozTransform == 'string'
			|| typeof supportedStyles.WebkitTransform == 'string' );
	//		||typeof  supportedStyles.OTransform == 'string' );
	
		supportsTransition = !!( typeof supportedStyles.transition == 'string' 
			|| typeof supportedStyles.MozTransition == 'string' 
			|| typeof supportedStyles.WebkitTransition == 'string' );
	//		|| typeof supportedStyles.OTransition == 'string' );
	}
	
	
	//
	// The functions for basic positioning setup, show and hide with multiple
	// version depending upon what CSS features are supported
	//
	// _________| w/o transform | w/ transform | w/ transition and transform
	// Position |       X       |       X      |             -     
	// Show     |       X       |       X      |             X     
	// Hide     |       X       |       X      |             X     
	//
	
	
	//
	// Position Function
	//
	
	// Position without transform
	var position_wo_transform = function( ){
		// will save the offset in pixels for each element
		var positioning = [];
		
		var is_y = this.options.direction in { 'top': '', 'bottom': '' };
		
		this.element.children( )
			.css( { 'left': '', 'top':'', 'right':'', 'bottom':'' } )
			.each( function( ){
				var v;
				
				if( is_y ){
					v = $( this ).outerHeight( true );
				} else {
					v = $( this ).outerWidth( true );
				}
				v = -1 * v;
				
				positioning.push( v );
			} );
		
		this.positioning = positioning;
	};
	
	
	// Position with transform
	var position_w_transform = function( ){
		// will save for each element an object with three values
		//  1) t: the value to move the element from the center so that the corners
		//        of the element touch the edge of the circle
		//  2) a: the angle to rotate the element around the circle
		//  3) d: the value the element sticks out from the edge of the circle
		var positioning = [ ];
		
		// grab all the values that will be used through out making the measurements for each element
		var direction = this.options.direction;
		var is_y = this.options.direction in { 'top': '', 'bottom': '' };
		var is_neg = this.options.direction in { 'left': '', 'top': '' };
		var neg = is_neg ? -1: 1;
		var r = parseInt( this.options.radius, 10 );
		r = r == 0 ? 1 : r; // prevent dividing by zero
		var clockwise = this.options.clockwise;
		
		
		// The common CSS to setup each element
		// clear out the positioning
		var css = {
			'top': '',
			'right': '',
			'bottom': '',
			'left': '',
			'transform': 'rotate(0deg) translate(0,0)',
			'MozTransform': 'rotate(0deg) translate(0,0)',
			'WebkitTransform': 'rotate(0deg) translate(0,0)',
			'OTransform': 'rotate(0deg) translate(0,0)'
		};
		
		if( direction == 'right' && !clockwise ||
		    direction == 'left' && clockwise ){
			css.top = 0;   
		}
		
		if( direction == 'bottom' && !clockwise ||
		    direction == 'top' && clockwise ){
			css.right = 0;   
		}
		
		if( direction == 'left' && !clockwise ||
		    direction == 'right' && clockwise ){
			css.bottom = 0;   
		}
		
		if( direction == 'top' && !clockwise ||
		    direction == 'bottom' && clockwise ){
			css.left = 0;   
		}
		
		
		this.element.children( )
			.css( css )
			.each( function( ){
				var $this = $( this );
				
				// t, a and d are the values to be stored for positioning; dd is the d without margins
				var t, a, d, dd; 
				if( is_y ){
					d = $this.outerHeight( true );
					dd = $this.outerWidth( );
				} else {
					d = $this.outerWidth( true );
					dd = $this.outerHeight( );
				}
				
				// set the origin
				var transRadius = r;
				
				// since the transform-origin of (0,0) is at the top-left corner of the box,
				// and the radius is from the middle of the edge facing the circle origin, 
				// the width/height needs to be added sometimes to the transform-origin to 
				// make it match up with the circle origin.
				if( direction in { 'top': '', 'right': '' } && clockwise ||
				    direction in { 'bottom': '', 'left': '' } && !clockwise ){
					transRadius += dd;
				}
				
				// The transform-origin of the circle for the element
				// R = transRadius
				// ___________|   top   |  right  |  bottom  | left
				// clockwise  | +R, 50% | 50%, +R | -R, 50%  | 50%, -R
				// counter-cw | -R, 50% | 50%, -R | +R, 50%  | 50%, +R
				//
				transRadius = direction in { 'top': '', 'right': '' } ? transRadius : -1*transRadius;
				transRadius = clockwise ? transRadius : -1 * transRadius;
				// now add the 50% to the origin.
				var origin = is_y ? transRadius+'px 50%' : '50% '+transRadius+'px';
				
				$this.css( {
					'transformOrigin': origin,
					'MozTransformOrigin' : origin,
					'OTransformOrigin': origin,
					'WebkitTransformOrigin': origin
				} );
				
				// A line from the center of the circle intersects at a right angle
				// with the middle of the element's edge facing. Because of this,
				// we need to align all the elements to their facing edge middle 
				// so that their transform-radius are all at the same X,Y with respect
				// to the document overall.
				$this.css( direction, (d*-0.5)+'px' );
				
				
				// Calculate the angle
				// Note: The corners of the element's facine edge are on the edge of the 
				// circle which means the rest of the edge is inside of the circle.
				// 
				//  opposite = half of the length of the element's facing edge
				//  hypotenuse = the radius of the circle
				//  adjacent = will be t, r-t is the amount to adjust it later
				a = Math.asin( d/r*0.5 ); // a = arcsine( opposite/hypotenuse )
				t = Math.cos( a ) * r;  // cosine( a ) = adjacent / hypotenuse; adjacent = ( adjacent / hypotenuse ) * hypotenuse
				a = a*radsToDeg; // convert a to degrees from radians
				
				
				// Get the amount to move the element towards the circle center so 
				// that the corners of the element are one the circle's edge.
				// ___________| top | right | bottom | left
				// clockwise  | +t  |  -t   |   -t   |  +t
				// counter-cw | -t  |  +t   |   +t   |  -t
				t = r-t; 
				t = clockwise? t : -1*t;
				if( is_y ){
					t = -1*t*neg;
					t = t+'px,0';
				} else {
					t = t*neg;
					t = '0,'+t+'px';
				}
				positioning.push( { t: t, a: a, d: d } );
			} );
		
		this.positioning = positioning;
	};
	
	var position_function = supportsTransform ? position_w_transform : position_wo_transform;
	
	
	
	
	// Show Function
	
	// Show without transform
	var show_wo_transform = function( ){
		// data needed to setup the animation
		var time = this.animationDuration;
		var dir = this.options.direction;
		var positioning = this.positioning;
		var offset = 0; // a running value for the offset
		var callback = proxy( this, '_show' );
		var i = this.element.children( ).size( );
		
		this.element.children( )
			.stop( )
			.css( 'display', '' )
			.each( function( i ){
				offset += positioning[ i ];
				var ani = { 'opacity': 1 };
				ani[ dir ] = offset;
				$( this ).animate( ani, time, 'swing', function( ){
					// run callback after the last item finishes animates
					if( --i == 0 ){
						callback( );
					}
				});
		} );
	};
	
	// Show with transform without transition
	var show_w_transform = function( ){
		// kill the currently running animation
		this.element.children( ).stop( );
		
		var positioning = this.positioning;
		var angle = 0; // running value for the angle
		var c = this.options.clockwise ? 1: -1;
		var time = this.animationDuration;
		var callback = proxy( this, '_show' );
		
		this.element.children( )
			.css( 'display', '' )
			.each( function( i ){
				var p = positioning[ i ];
				angle += c*p.a;
				
				$( this )
					.css( 'transform', 'translate('+(p.t)+')' )
					.animate( 
					{ 'rotate': angle, 'opacity': 1 },
					time,
					callback
				);
				
				callback = nofn; // only need to run the callback once
				angle += c*p.a; // add the angle again since the angle is for the middle of the element
			} );
	};
	
	
	// Show with transform and transition
	var show_w_transition = function( ){
		clearTimeout( this._timeout );
		
		var positioning = this.positioning;
		var angle = 0; // running value for the angle
		var c = this.options.clockwise ? 1: -1;
		var time = this.animationDuration;
		var timeSec = (time/1000)+'s';
		var aniBase = { 
			'display': '', 
			'transition': 'transform '+timeSec+', opacity '+timeSec,
			'MozTransition': '-moz-transform '+timeSec+', opacity '+timeSec,
			'WebkitTransition': '-webkit-transform '+timeSec+', opacity '+timeSec,
			'OTransition': '-o-transform '+timeSec+', opacity '+timeSec
		};
		
		this.element.children( )
			.each( function( i ){
				var p = positioning[ i ];
				angle += c*p.a;
				var trans = 'rotate('+angle+'deg) translate('+p.t+')';
				var ani = {
					'opacity': 1,
					'transform': trans,
					'MozTransform': trans,
					'WebkitTransform': trans,
					'OTransform': trans
				};
				
				$( this ).css( aniBase );
				// Cannot apply transition and transform at the same time.
				// If you do, then the transition are ignored. Wait a really
				// short moment after transition, then apply the transform.
				setTimeout( proxy( this, function( ){ 
					$( this ).css( ani );
				} ), 10 );
				
				angle += c*p.a;
			} );
		
		// Set a timer to finish up the animation and run the callbacks
		this._timeout = setTimeout( proxy( this, '_show' ), time );
	};
	
	var show_function = supportsTransform ? ( supportsTransition ? show_w_transition : show_w_transform ) : show_wo_transform;
	
	
	
	
	// Hide Functions
	
	// Hide without transform
	var hide_wo_transform = function( ){
		var ani = { 'opacity': 0 };
		ani[ this.options.direction ] = 0;
		var callback = proxy( this, '_hide' );
		var i = this.element.children( ).size( );
		
		this.element.children( )
			.stop( )
			.animate( 
				ani, 
				this.animationDuration,
				function( ){
					$(this).css( 'display', 'none' );
					if( --i == 0 ){
						callback( );
					}
				}
			);
	};
	
	
	// Hide with transform
	var hide_w_transform = function( ){
		// kill the currently running animation
		this.element.children( ).stop( );
		
		var callback = proxy( this, '_hide' );
		var i = this.element.children( ).size( );
		
		this.element.children( )
			.animate(
				{ 'rotate': 0, 'opacity': 0 },
				this.animationDuration,
				function( ){
					$( this ).css( 'display', 'none' );
					if( --i == 0 ){
						callback( );
					}
				}
			);
	};
	
	// Hide with transition and transform
	var hide_w_transition = function( ){
		clearTimeout( this._timeout );
		
		var timeSec = (this.animationDuration/1000)+'s'; // convert from milliseconds to seconds
		var aniBase = { 
			'transition': 'transform '+timeSec+', opacity '+timeSec,
			'MozTransition': '-moz-transform '+timeSec+', opacity '+timeSec,
			'WebkitTransition': '-webkit-transform '+timeSec+', opacity '+timeSec,
			'OTransition': '-o-transform '+timeSec+', opacity '+timeSec
		};
		
		this.element.children( ).css( aniBase );
		setTimeout( proxy( this, function( ){
			this.element.children( ).css( {
				'opacity': 0,
				'transform': 'rotate(0deg) translate(0,0)',
				'MozTransform': 'rotate(0deg) translate(0,0)',
				'WebkitTransform': 'rotate(0deg) translate(0,0)',
				'OTransform': 'rotate(0deg) translate(0,0)'
			} );
		}), 10 );
		
		this._timeout = setTimeout( proxy( this, function( ){
			this.element.children( ).css( 'display', 'none' );
			this._hide( );
		} ), this.animationDuration );
		
	};
	
	
	
	var hide_function = supportsTransform ? ( supportsTransition ? hide_w_transition : hide_w_transform ) : hide_wo_transform;
	
	
	
	$.widget( 'ui.stackmenu', {
		_init: function( ){
			// basic CSS setup
			this.element.addClass( 'ui-stackmenu' );
			this.element
				.children( )
				.addClass( 'ui-stackmenu-item' )
				.css( { 'opacity': 0, 'display': 'none' } )
				.css( this.options.direction, 0 );
			
			
			this.isShowing = false;
			
			// The time the animation started and
			// the amount of the time the animation was to run.
			this.animationStartTime = false;
			this.animationDuration = this.options.time;
			
			// for saving the callbacks passed into the method
			this._hideCallbackFn = nofn;
			this._showCallbackFn = nofn;
			
			
			// Calculate position
			position_function.call( this );
		},
		
		
		
		/**
		 * Shows the stack menu.
		 */
		show: function( callback ){
			this.toggle( true, callback );
		},
		
		
		
		/**
		 * Hides the stack menu.
		 */
		hide: function( callback ){
			this.toggle( false, callback );
		},
		
		
		
		/**
		 * Toggle the display of the stack menu.
		 *
		 * $( el ).stackmenu( 'toggle' [, show] [, callback] );
		 *
		 * @param show (optional) - a boolean flag if true, then it shows; if false,
		 *   then it hides; if undefined (not passed), then it will toggle
		 * @param callback (optional) - a function for the callback when finished running
		 */
		toggle: function( show, callback ){
			if( this.options.disabled == true ){
				return;
			}
			
			
			// sort out the parameters
			if( $.isFunction( show ) ){
				callback = show;
				show = undefined;
			}
			var nextState = show === undefined? !this.isShowing : show;
			callback = $.isFunction( callback )? callback : nofn;
			
			
			// quit early if we are already in the state
			if( nextState == this.isShowing ){
				return;
			}
			
			// save the method callback functions
			this._hideCallbackFn = nofn;
			this._showCallbackFn = nofn;
			
			// calculate the animation time
			if( this.animationStartTime === false ){
				// There is no animation running, so use the whole time
				this.animationDuration = this.options.time;
			} else {
				// Since there is time on the clock, then we are in mid-animation.
				// The time to animate back is the same as it took to animate
				// to the current position.
				var now = getNow( );
				var timeSince = now - this.animationStartTime;
				timeSince = timeSince > this.options.time? this.options.time : timeSince;
				this.animationDuration = timeSince;
			}
			
			try{
				// now either show or hide
				if( nextState ){
					// Show
					this._trigger( 'showBefore', {}, [this.element.get( 0 )] );
					this._showCallbackFn = callback;
					show_function.call( this );
				} else {
					// Hide
					this._trigger( 'hideBefore', {}, [this.element.get( 0 )] );
					this._hideCallbackFn = callback;
					hide_function.call( this );
				}
			} catch( err ) {
				log( err );
			}
			
			this.isShowing = nextState;
			
		},
		
		
		
		/**
		 * Removes the instance from the encapsulated DOM element, 
		 * which was stored on instance creation.
		 */
		destroy: function( ){
			this.element.removeClass( 'ui-stackmenu' );
			this.element.children( )
				.removeClass( 'ui-stackmenu-item' )
				.css( { 
					'opacity': '', 
					'display': '', 
					'top': '', 
					'right': '',
					'bottom': '',
					'left': '',
					'transformOrigin': '',
					'MozTransformOrigin' : '',
					'OTransformOrigin': '',
					'WebkitTransformOrigin': '',
					'transform': '',
					'MozTransform' : '',
					'OTransform': '',
					'WebkitTransform': '',
					'transition': '',
					'MozTransition': '',
					'WebkitTransition': '',
					'OTransition': ''
					} );
			
			// Final clean up
			$.widget.prototype.destroy.apply( this, arguments );
		},
		
		/**
		 * Gets or sets an option for this instance
		 */
		option: function( key, value ){
			// If no value is given, then have the default action happen
			if( value === undefined ){
				return $.widget.prototype.option.apply( this, arguments );
			}
			
			// Short circut if nothing is changing
			if( this.options[ key ] === value ){
				return;
			}
			
			
			if( key in { 'direction': '', 'clockwise': '', 'radius': '' } ){
				if( key == 'direction' && !( value in { 'top': '', 'right': '', 'bottom': '', 'left': '' } ) ){
					return; // invalid direction
				}
				
				var reposition = proxy( this, position_function );
				if( this.isShowing ){
					var reshow = proxy( this, 'show' );
					var changeOption = proxy( this, function( ){ 
						this.options[ key ] = value;
					});
					this.hide( function( ){
						changeOption( );
						reposition( );
						setTimeout( reshow, 10 ); // Place in a setTimeout to resolve sync error
						// that causes the first element's opacity not to animate.
					} );
				} else {
					this.options[ key ] = value;
					reposition( );
				}
				return value;
			}
			
			if( key == 'time' ){
				this.options.time = value;
				return;
			}
			
			return $.widget.prototype.option.apply( this, arguments );
		},
		
		/**
		 * Handles the cleanup for "show"
		 */
		_show: function( ){
			this.animationStartTime = false;
			this._call_callbacks( 'show' );
		},
		
		/**
		 * Handles the cleanup for "hide"
		 */
		_hide: function( ){
			this.animationStartTime = false;
			this._call_callbacks( 'hide' );
		},
		
		/**
		 * Handles all the event callbacks for an event type
		 */
		_call_callbacks: function( type ){
			
			// avoid bad code in the callbacks from taking down the widget
			try {
				if( this['_'+type+'CallbackFn'] ){
					var event = $.Event( {} ); 
					event.type = this.widgetEventPrefix + type;
					this['_'+type+'CallbackFn'].call( this.element.get( 0  ), event, this.element.get( 0  ) );
				}
			} catch( err ) { }
			
			this._trigger( type, {}, this.element.get( 0 ) );
		}
		
	} );
	
	
	$.extend( $.ui.stackmenu, {
		defaults: {
			'direction': 'top',
			'clockwise': true,
			'radius': '1000px',
			'time': 500
		}
	} );
	
} )( jQuery );