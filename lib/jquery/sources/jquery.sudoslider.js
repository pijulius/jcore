/*
 *  Sudo Slider ver 2.0.10 - jQuery plugin 
 *  Written by Erik Kristensen info@webbies.dk. 
 *  Based on Easy Slider 1.7 by Alen Grakalic http://cssglobe.com/post/5780/easy-slider-17-numeric-navigation-jquery-slider
 *  Although the two scripts doesn't share much code anymore. But Sudo Slider is still based on it. 
 *	
 *	 Dual licensed under the MIT
 *	 and GPL licenses.
 * 
 *	 Built for jQuery library
 *	 http://jquery.com
 *
 */
(function($)
{
	$.fn.sudoSlider = function(options)
	{
		// Saves space in the minified version.
		var truev = true;
		var falsev = !truev;
		// default configuration properties 
		var defaults = {
			prevNext:          truev,
			prevHtml:          '<a href="#" class="prevBtn"> previous </a>',
			nextHtml:          '<a href="#" class="nextBtn"> next </a>',
			controlsShow:      truev,
			controlsAttr:      'id="controls"',
			controlsFadeSpeed: '400',
			controlsFade:      truev,
			insertAfter:       truev,
			firstShow:         falsev,
			firstHtml:         '<a href="#" class="firstBtn"> first </a>',
			lastShow:          falsev,
			lastHtml:          '<a href="#" class="lastBtn"> last </a>',
			numericAttr:       'class="controls"',
			numericText:       ['1'],
			vertical:          falsev,
			speed:             '800',
			ease:              'swing',
			auto:              falsev,
			pause:             '2000',
			continuous:        falsev,
			clickableAni:      falsev,
			numeric:           falsev,
			updateBefore:      falsev,
			history:           falsev,
			speedhistory:      '400',
			autoheight:        truev,
			customLink:        falsev,
			fade:              falsev,
			crossFade:         truev,
			fadespeed:         '1000',
			ajax:              falsev,
			loadingText:       'Loading Content...',
			preloadAjax:       falsev,
			startSlide:        falsev,
			ajaxLoadFunction:  falsev,
			beforeAniFunc:     falsev,
			afterAniFunc:      falsev,
			uncurrentFunc:     falsev,
			currentFunc:       falsev,
			autowidth:         truev,
			slideCount:        1,
			resumePause:       false
		};
		var options = $.extend(defaults, options);
		
		// To make it smaller. 
		// There is a way to make it even smaller, but that doesn't work with the public functions setOption() and getOption(). 
		var option = [
			options.controlsShow, /* option[0]/*controlsShow*/
			options.controlsFadeSpeed,/* option[1]/*controlsFadeSpeed*/
			options.controlsFade,/* option[2]/*controlsFade*/
			options.insertAfter,/* option[3]/*insertAfter*/
			options.firstShow,/* option[4]/*firstShow*/
			options.lastShow,/* option[5]/*lastShow*/
			options.vertical,/* option[6]/*vertical*/
			options.speed,/* option[7]/*speed*/
			options.ease,/* option[8]/*ease*/
			options.auto,/* option[9]/*auto*/
			options.pause,/* option[10]/*pause*/
			options.continuous,/* option[11]/*continuous*/
			options.prevNext,/* option[12]/*prevNext*/
			options.numeric,/* option[13]/*numeric*/
			options.numericAttr,/* option[14]/*numericAttr*/
			options.numericText,/* option[15]/*numericText*/
			options.clickableAni,/* option[16]/*clickableAni*/
			options.history,/* option[17]/*history*/
			options.speedhistory,/* option[18]/*speedhistory*/
			options.autoheight,/* option[19]/*autoheight*/
			options.customLink,/* option[20]/*customLink*/
			options.fade,/* option[21]/*fade*/
			options.crossFade,/* option[22]/*crossFade*/
			options.fadespeed,/* option[23]/*fadespeed*/
			options.updateBefore,/* option[24]/*updateBefore*/
			options.ajax,/* option[25]/*ajax*/
			options.preloadAjax,/* option[26]/*preloadAjax*/
			options.startSlide,/* option[27]/*startSlide*/
			options.ajaxLoadFunction,/* option[28]/*ajaxLoadFunction*/
			options.beforeAniFunc,/* option[29]/*beforeAniFunc*/
			options.afterAniFunc,/* option[30]/*afterAniFunc*/
			options.uncurrentFunc,/* option[31]/*uncurrentFunc*/
			options.currentFunc,/* option[32]/*currentFunc*/
			options.prevHtml,/* option[33]/*prevHtml*/
			options.nextHtml,/* option[34]/*nextHtml*/
			options.loadingText,/* option[35]/*loadingText*/
			options.firstHtml,/* option[36]/*firstHtml*/
			options.controlsAttr,/* option[37]/*controlsAttr*/
			options.lastHtml,/* option[38]/*lastHtml*/
			options.autowidth,/*  option[39]/*autowidth*/
			options.slideCount,/*  option[40]/*slideCount*/
			options.resumePause/* option[41]/*resumePause*/
		];

		// Defining the base element. 
		// This is needed if i want to have public functions (And i want public functions).
		var baseSlider = this;
		
		return this.each(function()
		{
			/*
			 * Lets start this baby. 
			 */
			// First we declare a lot of variables. 
			// Some of the names may be long, but they get minified. 
			var init, 
				ul, 
				li, 
				liConti,
				s, 
				w, 
				h, 
				t, 
				ot, 
				nt, 
				ts, 
				clickable, 
				buttonclicked, 
				fading,
				ajaxloading, 
				autoheightdocument, 
				numericControls, 
				numericContainer, 
				destroyed, 
				fontsmoothing, 
				controls, 
				html, 
				firstbutton, 
				lastbutton, 
				nextbutton, 
				prevbutton, 
				timeout,
				destroyT,
				oldSpeed,
				dontCountinue,
				dontCountinueInit,
				obj = $(this);
			initSudoSlider(obj, falsev, falsev);
			function initSudoSlider(obj, destroyT, ajaxcallback)
			{
				//if (option[6]/*vertical*/) option[19]/*autoheight*/ = falsev; // With the new system of determining the position of the slides, this line is no longer needed. 
				destroyed = falsev; // In case this isn't the first init. 
				// There are some things we don't do at init. 
				init = truev; // I know it's an ugly workaround, but it works. 
				// If auto is on, so is continuous. (People tend to forget things they don't think about :p)
				if (option[9]/*auto*/) option[11]/*continuous*/ = truev;
				
				// Fix for nested list items
				ul = obj.children("ul");
				li = ul.children("li");
				liConti = li;
				// Some variables i'm gonna use alot. 
				s = li.length;
				w = li.eq(0).outerWidth(truev); // All slides must be same width, so this shouldn't be a problem. And it makes it posibble to mave multiple slides pr. "page" (2 slides in the viewport).
				h = li.eq(0).outerHeight(truev); // Same, just the height. 
				
				// Now we are going to fix the document, if it's 'broken'. (No <ul> or no <li>). 
				// I assume that it's can only be broken, if ajax is enabled. If it's broken without Ajax being enabled, the script doesn't have anything to fill the holes. 
				if (option[25]/*ajax*/)
				{
					// Is the ul element there?
					if (ul.length == 0)
					{
						// No it's not, lets create it. 
						obj.append('<ul></ul>');
						ul = obj.children("ul");
					}
				
					// Do we have enough list elements to fill out all the ajax documents. 
					if (option[25]/*ajax*/.length > s)
					{
						// No we dont. 
						for (var i = 1; i <= option[25]/*ajax*/.length - s; i++) ul.append('<li><p>' + option[35] + '</p></li>');
						li = ul.children("li");
						s = li.length;
						w = li.eq(0).width();
					}
				}				
				
				// Continuing with the variables. 
				t = 0;
				ot = t;
				nt = t;
				ts = s-1;
				
				clickable = truev;
				buttonclicked = falsev;
				fading = falsev;
				ajaxloading = falsev;
				autoheightdocument = 0;
				numericControls = new Array();
				numericContainer = falsev;
				destroyed = falsev;
				fontsmoothing = screen.fontSmoothingEnabled;
				
				// Set obj overflow to hidden (and position to relative <strike>, if fade is enabled. </strike>)
				obj.css("overflow","hidden");
				if (obj.css("position") == "static") obj.css("position","relative"); // Fixed a lot of IE6 + IE7 bugs. 
	
				// Float items to the left
				li.css('float', 'left');
				
				// Okay, now we have a lot of the variables in place, now we can check for some special conditions. 
				
				// If continuous is on, ajax is on and slideCount != 1. Then we have a problem.
				// I the most efficiant way to fix it, is to preload everything before continuing.
				if (ajaxcallback || ajaxcallback === 0){
					/* Do a check if it can continue. */ 
					if (dontCountinueInit) dontCountinueInit--;
					if (!dontCountinueInit) option[25]/*ajax*/ = false; // If i don't do this, the making of the continuous clones below fuck up. 
				}
				else if (option[25]/*ajax*/ && option[11]/*continuous*/ && option[40]/*slideCount*/ != 1)
				{
					// If those 3 things are true, i basicly remove the ajax part, then continue the script. 
					
					// To do that, i have to preload everyone of them. 
					// This part is basicly a copy-paste of the preloading in the animate() function. 
					dontCountinueInit = 0;
					for (var a = 0; a < s; a++)
					{
						if (option[25]/*ajax*/[a])
						{
							ajaxLoad(a, a, falsev, 0, function(){
								initSudoSlider(obj, destroyT, a);
							});
							option[25]/*ajax*/[a] = falsev; 
							dontCountinueInit++;
						}
					}
				}
				if (!dontCountinueInit)
				{
					// The user doens't always put a text in the numericText. 
					// With this, if the user dont, the code will. 
					for(var i=0;i<s;i++)
					{
						if (option[15]/*numericText*/[i] == undefined) option[15]/*numericText*/[i] = (i+1);
						// Same thing for ajax thingy. 
						if (option[25]/*ajax*/ && option[25]/*ajax*/[i] == undefined) option[25]/*ajax*/[i] = falsev;
					}

					// Clone elements for continuous scrolling
					if(option[11]/*continuous*/)
					{
						a = 0;
						if(option[6]/*vertical*/)
						{
							
							for (i = option[40]/*slideCount*/;i >= 1 ;i--)
							{
								// First we create the elements, pretending AJAX is a city in Russia. 
								// ul.prepend(li.filter(":last-child").clone().css("margin-top","-"+ h +"px"));
								var tmpchild = li.eq(-option[40]/*slideCount*/+i-1).clone();
								a = a + tmpchild.outerWidth(truev);
								tmpchild.css("margin-top","-"+ a +"px")
								ul.prepend(tmpchild);
								ul.append(li.eq(option[40]/*slideCount*/-i).clone().css({'z-index' : '0'}));
							}
						} 
						// IE6 bugs if i just use else here. No idea why, but it's IE6 and i'm not supprised. 
						if (!option[6]/*vertical*/)
						{
							for (i = option[40]/*slideCount*/;i >= 1 ;i--)
							{
								// First we create the elements, pretending AJAX is a city in Russia. 
								var tmpchild = li.eq(-option[40]/*slideCount*/+i-1).clone();
								a = a + tmpchild.outerWidth(truev);
								tmpchild.css("margin-left","-"+ a +"px")
								ul.prepend(tmpchild);
								ul.append(li.eq(option[40]/*slideCount*/-i).clone().css({'z-index' : '0'}));
							}
								
						}
						// Now, lets check if AJAX really is a city in Russia.
						if (option[25]/*ajax*/)
						{
							// Now we move from Russia back to reallity (nothing bad about the Russians, it's just a saying in Denmark.)
							// Starting with putting the first document after the last. 
							if (option[25]/*ajax*/[0]) ajaxLoad('last', 0, falsev, 0, falsev);
							// And putting the last document before the first. 
							if (option[25]/*ajax*/[s-1])
							{
								ajaxLoad('first', (s-1), falsev, 0, falsev);
								// And then preloading the last document (the same document, but into it's entended position). No need to preload the first slide, it gets loaded elsewhere. 
								ajaxLoad(ts, ts, falsev, 0, falsev);
								option[25]/*ajax*/[s-1] = falsev;
							}
						}
					}
					
					// Now that the slide content is in place, some adjustments can be made. 
					heightWidthAdjust();
					
					// And i can make this variable for later use. 
					// The variable contains every <li> element. 
					liConti = ul.children("li");
					
					// Display the controls.
					controls = falsev;
					if(option[0]/*controlsShow*/)
					{
						controls = $('<span ' + option[37]/*controlsAttr*/ + '></span>');
						if (option[3]/*insertAfter*/) $(obj).after(controls);
						else $(obj).before(controls);
						
						if(option[13]/*numeric*/) {
							numericContainer = controls.prepend('<ol '+ option[14]/*numericAttr*/ +'></ol>').children();
							var substract = option[11]/*continuous*/ ? 1 : option[40]/*slideCount*/;
							for(var i=0;i<s-substract+1;i++)
							{
								numericControls[i] = $(document.createElement("li"))
								.attr({'rel' : (i+1)})
								.html('<a href="#"><span>'+ option[15]/*numericText*/[i] +'</span></a>') 
								.appendTo(numericContainer)
								.click(function(){
									goToSlide($(this).attr('rel') - 1, truev);
									return falsev;
								});
							};
						}
						if(option[4]/*firstShow*/) firstbutton = makecontrol(option[36]/*firstHtml*/, "first");
						if(option[5]/*lastShow*/) lastbutton = makecontrol(option[38]/*lastHtml*/, "last");
						if(option[12]/*prevNext*/){
							nextbutton = makecontrol(option[34]/*nextHtml*/, "next");
							prevbutton = makecontrol(option[33]/*prevHtml*/, "prev");
						}
					};
					
					// Preload elements. 
					if (option[26]/*preloadAjax*/)
					{
						preloadEverything();
					}
					
					
					// Lets make those fast/normal/fast into some numbers we can make calculations with.
					option[1]/*controlsFadeSpeed*/ = textSpeedToNumber(option[1]/*controlsFadeSpeed*/);
					option[7]/*speed*/ = textSpeedToNumber(option[7]/*speed*/);
					option[10]/*pause*/ = textSpeedToNumber(option[10]/*pause*/);
					option[18]/*speedhistory*/ = textSpeedToNumber(option[18]/*speedhistory*/);
					option[23]/*fadespeed*/ = textSpeedToNumber(option[23]/*fadespeed*/);
					
					// Starting auto. 
					if(option[9]/*auto*/) timeout = startAuto(option[10]/*pause*/);
					
					// customLinks. Easy to make, great to use. 
					if (option[20]/*customLink*/) 
					{
						// Using live, that way javascript ajax-loaded buttons and javascript generated content will work.
						$(option[20]/*customLink*/).live('click', function() {
							a = $(this).attr('rel');
							if (a) {
								// Check for special events
								if (a == 'stop') clearTimeout(timeout)
								else if (a == 'start')
								{
									timeout = startAuto(option[10]/*pause*/);
									option[9]/*auto*/ = truev;
								}
								else if (a == 'block') clickable = falsev; // Simple, beautifull.
								else if (a == 'unblock') clickable = truev; // -||-
								// The general case. 
								// That means, typeof(a) == numbers and first,last,next,prev
								// I dont make any kind of input validation, meaning that it's quite easy to break the script with non-valid input. 
								else if (clickable) goToSlide((a == parseInt(a)) ? a - 1 : a, truev);
							}
							return falsev;
						}); 
					}
					
					// Lets make those bookmarks and back/forward buttons work. 
					// And startslide etc. 
					// + If re-initiated, the slider will be at the same slide. 
					if (destroyT) animate(destroyT,falsev,falsev,falsev); 
					else if (option[17]/*history*/) {
						// Going to the correct slide at load. 
						$.address.init(function(e) {
							if (!destroyT) animate(filterUrlHash(e.value),falsev,falsev,falsev);
						})
						// Sliding/fading to the correct slide, on url change. 
						.change(function(e) {
							var i = filterUrlHash(e.value);
							if (i != t) goToSlide(i, falsev);
						});
					}
					// The startSlide setting only require one line of code. And here it is:
					else if (option[27]/*startSlide*/) animate(option[27]/*startSlide*/ - 1,falsev,falsev,falsev); 
					// doing it anyway. good way to fix bugs. 
					// And i only preload the next and previous slide after init (which this is). So i'm doing it. 
					// + if i didn't do this, a lot of things wouldn't happen on page load. By always animating, i ensure that everthing that's supposed to happen, do happen. 
					else animate(0,falsev,falsev,falsev); 
				}
			}
			
			/*
			 * The functions do the magic. 
			 */
			function preloadEverything()
			{
				for (var i=0;i<=ts;i++) // Preload everything.
				{	
					if (option[25]/*ajax*/[i])
					{
						// If somethings is to be loaded, lets load it. 
						ajaxLoad(i, i, falsev, 0, falsev);
						// Making sure it aint loaded again. 
						option[25]/*ajax*/[i] = falsev;
					}
				}
			}
			
			function heightWidthAdjust()
			{
				a = 0;
				if(option[6]/*vertical*/)
				{
					for (var i = -1;i<=s;i++) a = a + li.eq(i).outerHeight(truev);
					ul.height(a*4);
				}
				else
				{
					for (var i = -1;i<=s;i++) a = a + li.eq(i).outerWidth(truev);
					ul.width(a*4);
				}
			}
			function startAuto(pause)
			{
				return setTimeout(function(){
					goToSlide("next", falsev);
				},pause);
			}
			function textSpeedToNumber(speed)
			{
				if (parseInt(speed)) var returnspeed = parseInt(speed);
				else 
				{
					var returnspeed = 400;
					switch(speed)
					{
					case 'fast':
						returnspeed = 200;
					case 'normal':
						returnspeed = 400;
					case 'medium':
						returnspeed = 400;
					case 'slow':
						returnspeed = 600;
					}
				}
				return returnspeed;
			};
			// I go a long way to save lines of code. 
			function makecontrol(html, action)
			{
				return $(html).prependTo(controls).click(function(){
					goToSlide(action, truev);
					return falsev;
				});
			}
			// Simple function, great work. 
			function goToSlide(i, clicked)
			{
				if (!destroyed)
				{
					// Ahhh, recursive functions. I love me. 
					if (option[9]/*auto*/)
					{
						var delay = option[7]/*speed*/;
						if (fading && option[22]/*crossFade*/) delay = parseInt((delay)*(3/5));
						else if (fading) delay = 0;
						// Stopping auto if clicked. And also continuing after X seconds of inactivity. 
						if(clicked){
							
							clearTimeout(timeout);
							if (option[41]/*resumePause*/) timeout = startAuto(delay + option[41]/*resumePause*/);
						}
						// Continuing if not clicked.
						else timeout = startAuto(option[10]/*pause*/ + delay); 
					}
					
					if (option[21]/*fade*/)
					{
						fadeto(i, clicked);
					}
					else 
					{
						if (option[11]/*continuous*/)
						{
							// Just a little smart thing, that stops the slider from performing way to "large" animations. 
							// Not necessary when using fade, therefore i placed it here. 
							a = t;
							// Finding the "real" slide we are at. (-2 == 4). 
							if (a<0) a = a + s;
							if (a>ts) a = a - s;
							// And if i try to navigate to the neighbour, then why do it by sliding across the entire slider. 
							if (i == a + 1) i = 'next';
							if (i == a - 1) i = 'prev';
							// And when navigating from the first to the last (and the other way around). 
							if (a == 0 && i == ts) i = 'prev';
							if (a == ts && i == 0) i = 'next';
							
						}
						// And now the animation itself. 
						animate(i,clicked,truev,falsev);
					}
				}
			};
			function runOnImagesLoaded(e,_cb)
			{
				// This function is based on the onImagesLoaded plugin by soundphed, that was in a comment on this page "http://engineeredweb.com/blog/09/12/preloading-images-jquery-and-javascript#comment-92". 
				e.each(function() {
					var $imgs = (this.tagName.toLowerCase()==='img')?$(this):$('img',this),
					_cont = this,
					i = 0,
					_done=function() {
						if( typeof _cb === 'function' ) _cb(_cont);
					};
					
					if( $imgs.length ) {
						$imgs.each(function() {
							var _img = this,
							_checki=function(e) {
								if((_img.complete) || (_img.readyState=='complete'&&e.type=='readystatechange') )
								{
									if( ++i===$imgs.length ) _done();
								}
								else if( _img.readyState === undefined ) // dont for IE
								{
									$(_img).attr('src',$(_img).attr('src')); // re-fire load event
								}
							}; // _checki \\
							$(_img).bind('load readystatechange', function(e){_checki(e);});
							_checki({type:'readystatechange'}); // bind to 'load' event...
						});
					} else _done();
				});
			};
			
			// Is the file a image? (This function is not only used in the Ajaxload function)
			function imageCheck(file)
			{
				var image = falsev;
				
				var len = file.length;
				var ext = file.substr(len-4, 4);
				
				if (ext == '.jpg' || ext == '.png' || ext == '.bmp' || ext == '.gif') image = truev;
				
				var ext = file.substr(len-5, 5);
				if (ext == '.jpeg') image = truev;
				
				return image;
			}
			function fadeControl (fadeOpacity,fadetime,nextcontrol)
			{
				if (!option[11]/*continuous*/)
				{
					if (nextcontrol)
					{
						var eA = nextbutton,
						eB = lastbutton,
						directionA = 'next',
						directionB = 'last',
						firstlastshow = option[5]/*lastShow*/;
					}
					else
					{
						var eA = prevbutton,
						eB = firstbutton,
						directionA = 'prev',
						directionB = 'first',
						firstlastshow = option[4]/*firstShow*/;
					}
				
					if (option[12]/*prevNext*/)
					{
						if (fadeOpacity == 0) eA.fadeOut(fadetime);
						else eA.fadeIn(fadetime);
					}
					if (firstlastshow)
					{
						if (fadeOpacity == 0) eB.fadeOut(fadetime);
						else eB.fadeIn(fadetime);
					}
					if(option[20]/*customLink*/)
					{
						$(option[20]/*customLink*/)
						.filter(function(index) { 
							return ($(this).attr("rel") == directionA || $(this).attr("rel") == directionB);
						})
						.fadeTo(fadetime, fadeOpacity, function() { if (fadeOpacity == 0) $(this).hide();});
					} 
				}
			};
			// Fade the controls, if we are at the end of the slide. 
			// It's all the different kind of controls. 
			function fadeControls (a,fadetime)
			{
				if(a==0) fadeControl (0,fadetime,falsev);
				else fadeControl (1,fadetime,falsev);
				
				/* if(a==ts) fadeControl (0,fadetime,truev);
				else fadeControl (1,fadetime,truev);*///høns
				if (a >= s - parseInt(option[40]/*slideCount*/,10)) fadeControl(0, fadetime, truev);
				// Not using a else, because IE6 doesn't like that. 
				if (a < s - parseInt(option[40]/*slideCount*/,10))fadeControl(1, fadetime, truev);
			};
			
			
			
			// Updating the 'current' class
			function setCurrent(i)
			{
				i = parseInt((i>ts) ? i=0 : ((i<0)? i= ts+i+1 : i)) + 1;
				for(var a=0;a<numericControls.length;a++) setCurrentElement(numericControls[a], i);
				if(option[20]/*customLink*/) setCurrentElement(option[20]/*customLink*/, i);
			};
			function setCurrentElement(element,i)
			{
				$(element)
					.filter(".current")
					.removeClass("current")
					.each(function() {
						if ($.isFunction(option[31]/*uncurrentFunc*/)){ option[31]/*uncurrentFunc*/.call(this, $(this).attr("rel")); }
					});
				$(element)
					.filter(function() { 
						return $(this).attr("rel") == i; 
					})
					.addClass("current")
					.each(function(index) {
						if ($.isFunction(option[32]/*currentFunc*/)){ option[32]/*currentFunc*/.call(this, i); }
					});
			};
			// Find out wich numericText fits the current url. 
			function filterUrlHash(t)
			{
				var te = 0;
				for (var i=0;i<=s;i=i+1) if (option[15]/*numericText*/[i] == t) te = i;
				return te;
			};
			function autoadjust(i, speed)
			{
				if (option[19]/*autoheight*/) autoheight(i, speed);
				if (option[39]/*autowidth*/) autowidth(i, speed);
			}
			// Automaticly adjust the height, i love this function. 
			function autoheight(i, speed)
			{
				obj.ready(function() {
					if (i == s) i = 0;
					// First i run it. In case there are no images. 
					var nheight = li.eq(i).height();
					if (nheight != 0) setHeight(nheight, speed);
					// Then i run it again after the images has been loaded. (If any)
					runOnImagesLoaded(li.eq(i),function(imgtarget){
						obj.ready(function() {
							nheight = $(imgtarget).height();
							if (nheight != 0) setHeight(nheight, speed);
						});
					});
				});
			};
			// Automaticly adjust the width (sick). 
			function autowidth(i, speed)
			{
				obj.ready(function() {
					if (i == s) i = 0;
					// First i run it. In case there are no images. 
					var nwidth = li.eq(i).width();
					if (nwidth != 0) setWidth(nwidth, speed);
					// Then i run it again after the images has been loaded. (If any)
					runOnImagesLoaded(li.eq(i),function(imgtarget){
						obj.ready(function() {
							nwidth = $(imgtarget).width();
							if (nwidth != 0) setWidth(nwidth, speed);
						});
					});
				});
			};
			function setWidth(nwidth, speed)
			{
				obj.animate(
					{ width:nwidth},
					{
						queue:falsev,
						duration:speed,
						easing:option[8]/*ease*/
					}
				);
			};
			function setHeight(nheight, speed)
			{
				obj.animate(
					{ height:nheight},
					{
						queue:falsev,
						duration:speed,
						easing:option[8]/*ease*/
					}
				);
			};
			function adjustPosition()
			{
				if (option[6]/*vertical*/) ul.css("margin-top",getSlidePos(t, falsev)); //hønsesuppe 
				else ul.css("margin-left",getSlidePos(t, truev)); 
			};
			// This is a bit complicated, because Firefox won't handle it right. 
			// If i just used .position(), Firefox gets the position 1-2 px off pr. slide (i have no idea why). 
			function getSlidePos(slide, width) // getSlidePosWidth
			{
				var p = 0;
				var substract = 1;
				if(option[11]/*continuous*/) substract = 1-option[40]/*slideCount*/;
				var add = 0;
				if (option[11]/*continuous*/) add = option[40]/*slideCount*/;
				for (var i = 0;i<=slide-substract;i++){
					if (width) p = p - liConti.eq(i + add).outerWidth(truev);
					else p = p - liConti.eq(i + add).outerHeight(truev);
				}
				return p;
			};

			// When the animation finishes (fade or sliding), we need to adjust the slider. 
			function adjust()
			{
				
				if (option[11]/*continuous*/)
				{
					if(t>ts) t=0;
					if(t<1-option[40]/*slideCount*/) t=s-option[40]/*slideCount*/;
				}
				else
				{
					if(t>ts) t=0;
					if(t<0) t=ts;
				}
				if (t==ts && !option[21]/*fade*/) preloadEverything();
				if(!option[24]/*updateBefore*/) setCurrent(t);
				adjustPosition();
				clickable = truev;
				if(option[17]/*history*/ && buttonclicked) window.location.hash = option[15]/*numericText*/[t];
				if (!fading)
				{
					a = t + 1;
					if (a<1) a = a + s;
					if (a>s) a = a - s;
					// Lets run the after animation function.
					afterAniCall(li.eq(t), a)
					if (option[11]/*continuous*/ && !option[21]/*fade*/) // Never needed if it's a fade animation. 
					{
						// Might look complicated, and it is.
						// To see the logic, you need to 'uncompress' the code. 
						// But it's basicly just determining which continuous-"clone" i need to call afterAniFunc on. 
						if (t < option[40]/*slideCount*/)
						{
							afterAniCall(liConti.eq((t<0) ? t + option[40]/*slideCount*/ : t - option[40]/*slideCount*/), a);
						}
						if (t > ts - option[40]/*slideCount*/)
						{
							afterAniCall(liConti.eq(option[40]/*slideCount*/ + t - ts - 1), a);
						}
					}
				}
			};
			function afterAniCall(el, a)
			{
				if ($.isFunction(option[30]/*afterAniFunc*/)) option[30]/*afterAniFunc*/.call(el, a);
			};
			function beforeAniCall(el, a)
			{
				if ($.isFunction(option[29]/*beforeAniFunc*/)) option[29]/*beforeAniFunc*/.call(el, a);
			};
			// Convert the direction into a usefull number.
			function filterDir(dir, ot)
			{
				var nt = t; // i dont want to mess with the 't' variable. This function doens't allways return the t-value, it can be used for other things.
				switch(dir)
				{
					case "next":
						nt = (ot>=ts) ? (option[11]/*continuous*/ ? nt+1 : ts) : nt+1;
						break;
					case "prev":
						nt = (nt<=0) ? (option[11]/*continuous*/ ? nt-1 : 0) : nt-1;
						break;
					case "first":
						nt = 0;
						break;
					case "last":
						nt = ts;
						break;
					default:
						nt = parseInt(dir);
					break;
				};
				return nt;
			};
			// Load a ajax document (or i image) into a list element. 
			// If testing this locally (loading everything from a harddisk instead of the internet), it may not work. 
			// But then try to upload it to a server, and see it shine. 
			function ajaxLoad(i, l, adjust, speed, ajaxCallBack)
			{
				var targetslide = falsev;
				if (parseInt(i) || i == 0) targetslide = li.eq(i);
				else
				{
					if (i == 'last') targetslide = $('li:last', obj);
					else targetslide = $('li:first', obj);
					var conti = truev; // This could get complicated. 
				}
				// What speed should the autoheight function animate with?
				var ajaxspeed = (fading) ? (!option[22]/*crossFade*/ ? parseInt(option[23]/*fadespeed*/ * (2/5)) : option[23]/*fadespeed*/) : speed;
				// The script itself is not using the 'tt' variable. But a custom function can use it. 
				var tt = l + 1;
				if (imageCheck(option[25]/*ajax*/[l])) 
				{
					// Load the image.
					targetslide.html(' ').append($(new Image()).attr('src', option[25]/*ajax*/[l]));
					// When the document is ready again, we launch a autoheight event. 
					runOnImagesLoaded(targetslide,function(img){
						var target = $(img).children();
						// If the image is to wide, shrink it. 
						finishImageLoading(target);
						function finishImageLoading(target)
						{
							var width = target.width(),
							height = target.height(),
							slidewidth = target.parent().width();
							// If width == 0, that means it's not ready yet. 
							// So we waint <strike>5</strike> 1 ms. 
							if (width == 0) setTimeout(function() { finishImageLoading(target); }, 1);
							else
							{
								
								target.attr({'oldheight' : height, 'oldwidth' : width});
								 // The last part (height:auto) forces the browser to think about the <li> elements height. 
								if (width > slidewidth) target.animate({ width: slidewidth, height: (height/width)*slidewidth}, 0).parent().animate({height: (height/width)*slidewidth}, 0).css('height', 'auto');
								// If we want, we can launch a function here. 
								if ($.isFunction(option[28]/*ajaxLoadFunction*/)){option[28]/*ajaxLoadFunction*/.call($(img), tt, truev);}
								// Lets just make some adjustments
								ajaxAdjust(target.parent(), conti, ajaxCallBack);
								if (adjust) autoadjust(i, ajaxspeed);
							}
						}
						
					});
				}
				else
				{
					
					// Load the document into the list element. 
					targetslide.load(option[25]/*ajax*/[l], function(response, status, xhr) {
						// Lets just make some adjustments
						ajaxAdjust($(this), conti, ajaxCallBack);
						if (adjust) adjustPosition();
						
						if (status == "error" || !$(this).html()) $(this).html("Sorry but there was an error: " + (xhr.status ? xhr.status: 'no content') + " " + xhr.statusText);
						// If we want, we can launch a function here. 
						if (status != "error" && $.isFunction(option[28]/*ajaxLoadFunction*/)){option[28]/*ajaxLoadFunction*/.call($(this), tt, falsev);}
						// Lets adjust the height, i don't care if there's an error or not. 
						// var nheight = $(this).height(); // Why did i put that there??? Delete this comment when reason is found. 
						if (adjust) autoadjust(l, ajaxspeed); 
					});
				}
			};
			function ajaxAdjust(target, conti, ajaxCallBack){
				if ($.isFunction(ajaxCallBack)) ajaxCallBack();
				// If this is the "continuous" elements (the ones before the first and after the last). 
				if (conti) 
				{
					adjustPosition ();
					//if(option[6]/*vertical*/) target.css("margin-top","-"+ target.outerHeight(truev) +"px");
					//else target.css("margin-left","-"+ target.outerWidth(truev) +"px");
				}
				heightWidthAdjust();
				
			};
			// It's not only a slider, it can also fade from slide to slide. 
			function fadeto(i, clicked)
			{
				if (i != t && !destroyed && clickable) // We doesn't want something to happen all the time. The URL can change a lot, and cause som "flickering". 
				{
					ajaxloading = falsev;
					// Update the current class of the buttons. 
					if (option[24]/*updateBefore*/) setCurrent(filterDir(i, ot));
					// Only clickable if not clicked.
					clickable = !clicked;
					// Setting the speed. 
					var speed = (!clicked && !option[9]/*auto*/ && option[17]/*history*/) ? option[23]/*fadespeed*/ * (option[18]/*speedhistory*/ / option[7]/*speed*/) : option[23]/*fadespeed*/;
					var ll = filterDir(i, ot);
					// I don't want to fade to a continuous clone, i go directly to the target. 
					if(ll>ts) ll=0; 
					if(ll<0) ll=ts;
					// Lets make sure the prev/next buttons also fade. 
					if(option[2]/*controlsFade*/) fadeControls (ll,option[1]/*controlsFadeSpeed*/);

					// autoadjust
					if (option[25]/*ajax*/ && option[25]/*ajax*/[ll])
					{
						ajaxLoad(ll, ll, falsev, speed, function(){
							option[25]/*ajax*/[ll] = falsev;
							clickable = truev;
							adjustPosition();
							fadeto(ll, truev);
						});
					}
					else
					{
						autoadjust(ll,option[23]/*fadespeed*/); // The height animation takes the full lenght of the fade animation (fadein + fadeout if it's not crossfading).  
					
						// Define the target. 
						var target = li.eq(ll);
						// So lets run the function.
						beforeAniCall(target, ll + 1)
						// Crossfading?
						if (option[22]/*crossFade*/)
						{
							// I clone the target, and fade it in, then hide the cloned element while adjusting the slider to show the real target.
							target.clone().prependTo(obj).css({'z-index' : '100000', 'position' : 'absolute', 'list-style' : 'none', 'top' : '0', 'left' : '0'}).
							// Lets fade it in. 
							hide().fadeIn(option[23]/*fadespeed*/, function() {
								if (fontsmoothing) this.style.removeAttribute("filter"); // Fix clearype
								// So the animate function knows what to do. 
								clickable = truev;
								fading = truev;
								animate(ll,falsev,falsev,falsev); // Moving to the correct place.
								// Removing it again, if i dont, it will just be a pain in the ....
								$(this).remove();
								if(option[17]/*history*/ && clicked) window.location.hash = option[15]/*numericText*/[t]; // It's just one line of code, no need to make a function of it. 
								// Lets put that variable back to the default (and not during animation) value. 
								fading = falsev;
								// Now run that after animation function.
								// We already got the target and the slider number from earlier.
								// So lets run the function.
								afterAniCall(target, ll + 1);
							});
						}
						else
						{
							// fadeOut and fadeIn.
							var fadeinspeed = parseInt((speed)*(3/5)),
							fadeoutspeed = speed - fadeinspeed,
							// I set the opacity to something higher than 0, because if it's 0, the content that i try to read (to make the autoheight work etc.) aint there.
							noncrossfadetargets = li.children();
							noncrossfadetargets.stop().fadeTo(fadeoutspeed, 0.0001, function(){
								// So the animation function knows what to do. 
								clickable = truev;
								fading = truev;
								animate(ll,falsev,falsev,falsev); // Moving to the correct place.
								// Only clickable if not clicked.
								clickable = !clicked; 
								// Now, lets fade the slider back in. 
								// Got no idea why the .add(li) is nesecary, but it is. (If it isn't there, the first slide never fades back in). 
								noncrossfadetargets.add(li).stop().fadeTo(fadeinspeed, 1, function(){
									if (fontsmoothing) this.style.removeAttribute("filter"); // Fix clearype
									if(option[17]/*history*/ && clicked) window.location.hash = option[15]/*numericText*/[t]; // It's just one line of code, no need to make a function of it. 
									clickable = truev;
									fading = falsev;
									// Now run that after animation function.
									// We already got the target and the slider number from earlier.
									// So lets run the function.
									afterAniCall(target, ll + 1);
								});
							});
						}
					}
				}
			};
			function animate(dir,clicked,time,ajaxcallback) // (Direction, did the user click something, is this to be done in >1ms?) 
			{
				if ((clickable && !destroyed && (filterDir(dir, ot) != t || init)) || ajaxcallback)
				{
					ajaxloading = falsev;
					clickable = (!clicked && !option[9]/*auto*/) ? truev : option[16]/*clickableAni*/;
					// to the adjust function. 
					buttonclicked = clicked;
					ot = t;
					t = filterDir(dir, ot);
					if (option[24]/*updateBefore*/) setCurrent(t);
					// Calculating the speed to do the animation with. 
					var diff = Math.sqrt(Math.abs(ot-t)),
					speed = parseInt(diff*option[7]/*speed*/);
					if (!clicked && !option[9]/*auto*/) speed = parseInt(diff*option[18]/*speedhistory*/); // Auto:truev and history:truev doens't work well together, and they ain't supposed to. 
					if (!time) speed = 0;
					
					// Ajax begins here 
					// I also these variables in the below code (running custom function).
					var i = t;
					if (i<0) i = i + s;
					if (i>ts) i = i - s;
					if (ajaxcallback)
					{
						speed = oldSpeed;
						// Do a check if it can continue.
						if (dontCountinue) dontCountinue--;
					}
					else if (option[25]/*ajax*/)
					{
						// Loading the target slide, if not already loaded. 
						if (option[25]/*ajax*/[i]) 
						{
							ajaxLoad(i, i, truev, speed, falsev);
							option[25]/*ajax*/[i] = falsev; 
							ajaxloading = truev;
						}
						// The slider need to have all slides that are scrolled over loaded, before it can do the animation.
						// That's not easy, because the slider is only loaded once a callback is fired. 
						if (!fading)
						{
							var aa = (ot>t) ? t : ot,
							ab = (ot>t) ? ot : t;
							dontCountinue = 0;
							oldSpeed = speed;
							for (var a = aa; a <= ab; a++)
							{
								if (a<=ts && a>=0 && option[25]/*ajax*/[a])
								{
									ajaxLoad(a, a, falsev, speed, function(){
										animate(dir,clicked,time, a);
									});
									option[25]/*ajax*/[a] = falsev; 
									dontCountinue++;
								}
							}
						}
						// Then we have to preload the next one. 
						if (i + 1 <= ts && option[25]/*ajax*/[i + 1])
						{
							ajaxLoad(i + 1, i + 1, falsev, 0, falsev);
							option[25]/*ajax*/[i + 1] = falsev;
						}
					}
					if (!dontCountinue)
					{
						// Lets create a variable for later use
						// Here, IE6 bugs if i write " if (t < 0) ". 
						if (t < 1-1)
						{
							var contiElement = liConti.eq(option[40]/*slideCount*/+t);
						}
						if (t > ts)
						{
							var contiElement = liConti.eq(t-s-option[40]/*slideCount*/);
						}
						// Ajax ends here
						if (!fading)
						{
							// Lets run the before animation function.
							beforeAniCall(li.eq(i), i+1)
							if (option[11]/*continuous*/) 
							{
								if (t < option[40]/*slideCount*/)
								{
									beforeAniCall(liConti.eq((t<0) ? t + option[40]/*slideCount*/ : t - option[40]/*slideCount*/),i+1);
								}
								if (t > ts - option[40]/*slideCount*/ || t == -option[40]/*slideCount*/)
								{
									beforeAniCall(liConti.eq((t == -option[40]/*slideCount*/) ? -1 : option[40]/*slideCount*/ + t - ts - 1),i+1);
								}
							}
						}
						// Start animation. 
						if (!fading && !ajaxloading) autoadjust(t, speed);
						if(!option[6]/*vertical*/) {
							var p = getSlidePos(t, true);
							//if (t < 0 || t == s) var p = - contiElement.position().left - parseFloat(contiElement.css('marginLeft'),10) + ul.position().left;
							ul.animate(
								{ marginLeft: p},
								{
									queue:falsev,
									duration:speed,
									easing:option[8]/*ease*/,
									complete:adjust
								}
							);
						} else {
							var p = getSlidePos(t, false);
							ul.animate(
								{ marginTop: p },
								{
									queue:falsev,
									duration:speed,
									easing:option[8]/*ease*/,
									complete:adjust
								}
							);
						};
						// End animation. 
						
						// Fading the next/prev/last/first controls in/out if needed. 
						if(option[2]/*controlsFade*/)
						{
							var fadetime = option[1]/*controlsFadeSpeed*/;
							if (!clicked && !option[9]/*auto*/) fadetime = (option[18]/*speedhistory*/ / option[7]/*speed*/) * option[1]/*controlsFadeSpeed*/;					
							if (!time) fadetime = 0;
							if (fading) fadetime = parseInt((option[23]/*fadespeed*/)*(3/5));
							fadeControls (t,fadetime);
						}
						// Stop init, first animation is done. 
						init = falsev; //nasty workaround, but it works. 
					};
				}
			};
			function returnOptionNumber(name) // Get the number from the name. 
			{
				var optionsUserName = [
					'controlsShow',
					'controlsFadeSpeed',
					'controlsFade',
					'insertAfter',
					'firstShow',
					'lastShow',
					'vertical',
					'speed',
					'ease',
					'auto',
					'pause',
					'continuous',
					'prevNext',
					'numeric',
					'numericAttr',
					'numericText',
					'clickableAni',
					'history',
					'speedhistory',
					'autoheight',
					'customLink',
					'fade',
					'crossFade',
					'fadespeed',
					'updateBefore',
					'ajax',
					'preloadAjax',
					'startSlide',
					'ajaxLoadFunction',
					'beforeAniFunc',
					'afterAniFunc',
					'uncurrentFunc',
					'currentFunc',
					'prevHtml',
					'nextHtml',
					'loadingText',
					'firstHtml',
					'controlsAttr',
					'lastHtml',
					'autowidth',
					'slideCount',
					'resumePause'
				];
				for(var i=0;i<optionsUserName.length;i++) if (optionsUserName[i] == name) var optionnumber = i;
				return optionnumber;
			}
		   /*
			* Public functions. 
			*/
			baseSlider.getOption = function(a){
				return option[returnOptionNumber(a)];
			}
			baseSlider.setOption = function(a, val){
				baseSlider.destroy();
				option[returnOptionNumber(a)] = val;
				baseSlider.init();
			}
			baseSlider.insertSlide = function(html, pos, numtext){
				// First we make it easier to work. 
				baseSlider.destroy();
				// pos = 0 means before everything else. 
				// pos = 1 means after the first slide.
				if (pos > s) pos = s; // If you try to add a slide after the last slide fix. 
				var html = '<li>' + html + '</li>';
				if (!pos || pos == 0) ul.prepend(html);
				else li.eq(pos -1).after(html);
				// Finally, we make it work again. 
				if (pos < destroyT || (!pos || pos == 0)) destroyT++;
				// Maybe i'll do somethings before init. option[15]/*numericText*/
				if (option[15]/*numericText*/.length < pos){ option[15]/*numericText*/.length = pos;}
				if (!numtext) numtext = parseInt(pos,10)+1;
				option[15]/*numericText*/.splice(pos,0,numtext);
				baseSlider.init();
			}
			baseSlider.removeSlide = function(pos){
				pos--; // 1 == the first. 
				// First we make it easier to work. 
				baseSlider.destroy();
				// Then we work. 
				li.eq(pos).remove();
				option[15]/*numericText*/.splice(pos,1);
				if (pos < destroyT) destroyT--;
				// Finally, we make it work again. 
				baseSlider.init();
			}
			baseSlider.goToSlide = function(a){
				goToSlide((a == parseInt(a)) ? a - 1 : a, truev);
			}
			baseSlider.block = function(){
				clickable = falsev; // Simple, beautifull.
			}
			
			baseSlider.unblock = function(){
				clickable = truev; // Simple, beautifull.
			}
			
			baseSlider.startAuto = function(){
				option[9]/*auto*/ = truev;
				timeout = startAuto(option[10]/*pause*/);
			}
			
			baseSlider.stopAuto = function(){
				clearTimeout(timeout);
			}
			
			baseSlider.destroy = function(){
				destroyT = t;
				// First, i remove the controls. 
				if (controls) controls.remove(); // that's it.
				// Now to set a variable, so nothing is run. 
				destroyed = truev; // No animation, no fading, no clicking from now. 
				// Then remove the customLink bindings:
				$(option[20]/*customLink*/).die("click");
				// Now remove the "continuous clones". 
				if (option[11]/*continuous*/)
				{
					for (a=1;a<=option[40]/*slideCount*/;a++)
					{
						liConti.eq(a-1).remove();
						liConti.eq(-a).remove();
					}
				}
			}
			baseSlider.init = function(){
				if (destroyed) {
					initSudoSlider(obj, destroyT, falsev);	
				}
			}
			baseSlider.adjust = function(speed){
				if (!speed) speed = 0;
				heightWidthAdjust();
				autoadjust(i, speed)
			}
			baseSlider.getValue = function(a){
				switch(a)
				{
					case 'currentSlide':
						return t + 1;
					case 'totalSlides':
						return s;
					case 'clickable':
						return clickable;
					case 'destroyed':
						return destroyed;
				}
				return undefined;
			}
			
		});
	};
})(jQuery);