/*
 *  Sudo Slider ver 2.1.0 - jQuery plugin 
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
	$.fn.sudoSlider = function(optionsOrg)
	{
		// Saves space in the minified version.
		// It might look complicated, but it isn't. It's easy to make using "replace all" and it saves a bit in the minified version (only .1KB after i started using Closure Compiler). 
		var falsev = !1,
		truev = !falsev,
		// default configuration properties 
		defaults = {
			controlsShow:      truev, /* option[0]/*controlsShow*/
			controlsFadeSpeed: 400, /* option[1]/*controlsFadeSpeed*/
			controlsFade:      truev, /* option[2]/*controlsFade*/
			insertAfter:       truev, /* option[3]/*insertAfter*/
			firstShow:         falsev, /* option[4]/*firstShow*/
			lastShow:          falsev, /* option[5]/*lastShow*/
			vertical:          falsev, /* option[6]/*vertical*/
			speed:             800, /* option[7]/*speed*/
			ease:              'swing', /* option[8]/*ease*/
			auto:              falsev, /* option[9]/*auto*/
			pause:             2000, /* option[10]/*pause*/
			continuous:        falsev, /* option[11]/*continuous*/
			prevNext:          truev, /* option[12]/*prevNext*/
			numeric:           falsev, /* option[13]/*numeric*/
			numericAttr:       'class="controls"', /* option[14]/*numericAttr*/
			numericText:       [], /* option[15]/*numericText*/
			clickableAni:      falsev, /* option[16]/*clickableAni*/
			history:           falsev, /* option[17]/*history*/
			speedhistory:      400, /* option[18]/*speedhistory*/
			autoheight:        truev, /* option[19]/*autoheight*/
			customLink:        falsev, /* option[20]/*customLink*/
			fade:              falsev, /* option[21]/*fade*/
			crossFade:         truev, /* option[22]/*crossFade*/
			fadespeed:         1000, /* option[23]/*fadespeed*/
			updateBefore:      falsev, /* option[24]/*updateBefore*/
			ajax:              falsev, /* option[25]/*ajax*/
			preloadAjax:       100, /* option[26]/*preloadAjax*/
			startSlide:        falsev, /* option[27]/*startSlide*/
			ajaxLoadFunction:  falsev, /* option[28]/*ajaxLoadFunction*/
			beforeAniFunc:     falsev, /* option[29]/*beforeAniFunc*/
			afterAniFunc:      falsev, /* option[30]/*afterAniFunc*/
			uncurrentFunc:     falsev, /* option[31]/*uncurrentFunc*/
			currentFunc:       falsev, /* option[32]/*currentFunc*/
			prevHtml:          '<a href="#" class="prevBtn"> previous </a>', /* option[33]/*prevHtml*/
			nextHtml:          '<a href="#" class="nextBtn"> next </a>', /* option[34]/*nextHtml*/
			loadingText:       'Loading Content...', /* option[35]/*loadingText*/
			firstHtml:         '<a href="#" class="firstBtn"> first </a>', /* option[36]/*firstHtml*/
			controlsAttr:      'id="controls"', /* option[37]/*controlsAttr*/
			lastHtml:          '<a href="#" class="lastBtn"> last </a>', /* option[38]/*lastHtml*/
			autowidth:         truev, /*  option[39]/*autowidth*/
			slideCount:        1, /*  option[40]/*slideCount*/
			resumePause:       falsev, /* option[41]/*resumePause*/
			moveCount:         1 /* option[42]/*moveCount*/
		},
		// Defining the base element. 
		// This is needed if i want to have public functions (And i want public functions).
		baseSlider = this;
		optionsOrg = $.extend(defaults, optionsOrg);
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
			t, 
			ot, 
			ts, 
			clickable, 
			buttonclicked, 
			fading,
			ajaxloading, 
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
			dontCountinueFade,
			autoOn,
			a,
			b,
			i,
			continuousClones,
			orgSlideCount,
			beforeAniFuncFired = falsev,
			asyncTimedLoad,
			obj = $(this),
			// Making sure that changes in options stay where they belong, very local. 
			options = optionsOrg,
			option = [];
			initSudoSlider(obj, falsev);
			function initSudoSlider(obj, destroyT) // høns
			{
				
				// First i rename the options (thereby saving space in the minified version). 
				// This also allows me to change the values of the options, without having to think about what happens if the user re initializes the slider. 
				b = 0;
				for (a in options) {
					option[b] = options[a];
					b++;
				}
				destroyed = falsev; // In case this isn't the first init. 
				// There are some things we don't do (and some things we do) at init. 
				init = truev; // I know it's an ugly workaround, but it works. 
				
				// Fix for nested list items
				ul = obj.children("ul");
				li = ul.children("li");
				// Some variables i'm gonna use alot. 
				s = li.length;
				
				// Now we are going to fix the document, if it's 'broken'. (No <ul> or no <li>). 
				// I assume that it's can only be broken, if ajax is enabled. If it's broken without Ajax being enabled, the script doesn't have anything to fill the holes. 
				if (option[25]/*ajax*/)
				{
					// Is the ul element there?
					if (ul.length == 0) obj.append(ul = $('<ul></ul>'));// No it's not, lets create it. 
					
					// Do we have enough list elements to fill out all the ajax documents. 
					if (option[25]/*ajax*/.length > s)
					{
						// No we dont. 
						for (a = 1; a <= option[25]/*ajax*/.length - s; a++) ul.append('<li><p>' +  option[35]/*loadingText*/ + '</p></li>');
						li = ul.children("li");
						s = li.length;
					}
				}				
				// Continuing with the variables. 
				t = 0;
				ot = t;
				ts = s-1;
				
				clickable = truev;
				buttonclicked = falsev;
				fading = falsev;
				ajaxloading = falsev;
				numericControls = new Array();
				destroyed = falsev;
				fontsmoothing = screen.fontSmoothingEnabled; // Does it look like an IE fix?
				
				// Set obj overflow to hidden (and position to relative <strike>, if fade is enabled. </strike>)
				obj.css("overflow","hidden");
				if (obj.css("position") == "static") obj.css("position","relative"); // Fixed a lot of IE6 + IE7 bugs. 
	
				// Float items to the left, and make sure that all elements are shown. 
				li.css({'float': 'left', 'display': 'block'});
				
				// I use slideCount very early, so i have to make sure that it's a number.
				option[40]/*slideCount*/ = parseInt10(option[40]/*slideCount*/)
				
				// I use moveCount starting with 0 (meaning that i move 1 slide at the time) i convert it here, because it makes no sense to non-coding folks. 
				option[42]--/*moveCount*/;
				// Lets just redefine slideCount
				orgSlideCount = option[40]/*slideCount*/;
				// If fade is on, i do not need extra clones. 
				if (!option[21]/*fade*/) option[40]/*slideCount*/ += option[42]/*moveCount*/;
				// slideCount can't be bigger than the number of slides. (That just bugs, i tried). 
				if (option[40]/*slideCount*/ > s) option[40]/*slideCount*/ = s;
				
				// startSlide can only be a number. 
				option[27]/*startSlide*/ = parseInt10(option[27]/*startSlide*/) || 1;
				
				// Am i going to make continuous clones?
				// If using fade, continuous clones are only needed if more than one slide is shown at the time. 
				continuousClones = option[11]/*continuous*/ && (!option[21]/*fade*/ || option[40]/*slideCount*/ > 1);
				
				// Okay, now we have a lot of the variables in place, now we can check for some special conditions. 
				
				// The user doens't always put a text in the numericText. 
				// With this, if the user dont, the code will. 
				for(a=0;a<s;a++)
				{
					option[15]/*numericText*/[a] = option[15]/*numericText*/[a] || (a+1);
					// Same thing for ajax. 
					option[25]/*ajax*/[a] = option[25]/*ajax*/[a] || falsev;
				}

				// Clone elements for continuous scrolling
				if(continuousClones)
				{
					for (i = option[40]/*slideCount*/;i >= 1 ;i--)
					{
						ul
							.prepend(li.eq(-option[40]/*slideCount*/+i-1).clone())
							.append(li.eq(option[40]/*slideCount*/-i).clone());
					}
					// This variable is also defined later, that's for the cases where Ajax is off, i also need to define it now, because the below code needs it. 
					liConti = ul.children("li");
					if (option[25]/*ajax*/)
					{
						for(a = s - option[40]/*slideCount*/;a<s;a++)
						{
							if (option[25]/*ajax*/[a] && a != option[27]/*startSlide*/ - 1) ajaxLoad(a, falsev, 0, falsev); // I still do not wan't to load the current slide at this point. 
						}
					}
					
				}
				// I don't fade the controls if continuous is enabled. 
				option[2]/*controlsFade*/ = option[2]/*controlsFade*/ && !option[11]/*continuous*/;
				
				// Now that the slide content is in place, some adjustments can be made. 
				// First i make sure that i have enough room in the <ul> (Through testing, i found out that the max supported size (height or width) in Firefox is 17895697px, Chrome supports up to 134217726px, and i didn't find any limits in IE (6/7/8/9)). 
				ul[option[6]/*vertical*/ ? 'height' : 'width'](10000000); // That gives room for about 14000 slides of 700px each. 
				
				// And i can make this variable for later use. 
				// The variable contains every <li> element. 
				liConti = ul.children("li");
				
				// Display the controls.
				controls = falsev;
				if(option[0]/*controlsShow*/)
				{
					// Instead of just generating HTML, i make it a little smarter. 
					controls = $('<span ' + option[37]/*controlsAttr*/ + '></span>');
					$(obj)[option[3]/*insertAfter*/ ? 'after' : 'before'](controls);
					
					if(option[13]/*numeric*/) {
						numericContainer = controls.prepend('<ol '+ option[14]/*numericAttr*/ +'></ol>').children();
						b = option[13]/*numeric*/ == 'pages' ? orgSlideCount : 1;
						for(a=0;a<s-((option[11]/*continuous*/ || option[13]/*numeric*/ == 'pages') ? 1 : orgSlideCount)+1;a += b)
						{
							numericControls[a] = $("<li rel='" + (a+1) + "'><a href='#'><span>"+ option[15]/*numericText*/[a] +"</span></a></li>")
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
				
				// Preload elements. // Not the startSlide, i let the animate function load that. 
				if (option[26]/*preloadAjax*/ === truev) for (i=0;i<=ts;i++) if (option[25]/*ajax*/[i] && option[27]/*startSlide*/-1 != i) ajaxLoad(i, falsev, 0, falsev);
				
				
				// Lets make those fast/normal/fast into some numbers we can make calculations with.
				b = [1/*controlsFadeSpeed*/,7/*speed*/,10/*pause*/,18/*speedhistory*/,23/*fadespeed*/];
				for (a in b) {
					option[parseInt10(b[a])] = textSpeedToNumber(option[parseInt10(b[a])]);
				}
				// customLinks. Easy to make, great to use. 
				// And if you wan't it even more flexible, you can use the public methods (http://webbies.dk/SudoSlider/help/) like sudoSlider.goToSlide('next');
				if (option[20]/*customLink*/) 
				{
					// Using live, that way javascript ajax-loaded buttons and javascript generated content will work.
					$(option[20]/*customLink*/).live('click', function() { // When i started making this script, the .live() was brand new. 
						if (a = $(this).attr('rel')) {
							// Check for special events
							if (a == 'stop') 
							{
								option[9]/*auto*/ = falsev;
								stopAuto();
							}
							else if (a == 'start')
							{
								timeout = startAuto(option[10]/*pause*/);
								option[9]/*auto*/ = truev;
							}
							else if (a == 'block') clickable = falsev; // Simple, beautifull.
							else if (a == 'unblock') clickable = truev; // -||-
							// The general case. 
							// That means, typeof(a) == numbers and first,last,next,prev
							else if (clickable) goToSlide((a == parseInt10(a)) ? a - 1 : a, truev);
						}
						return falsev;
					}); 
				}
				
				
				// From here, i only do it when the continuous clones to the left (and the first slide) is ready. 
				runOnImagesLoaded (liConti.slice(0,option[40]/*slideCount*/), truev, function ()
				{
					// Starting auto
					if (option[9]/*auto*/) timeout = startAuto(option[10]/*pause*/);
					
					// Lets make those bookmarks and back/forward buttons work. 
					// And startslide etc. 
					// + If re-initiated, the slider will be at the same slide. 
					if (destroyT) animate(destroyT,falsev,falsev,falsev); 
					else if (option[17]/*history*/) {
						// I support the jquery.address plugin, Ben Alman's hashchange plugin and Ben Alman's jQuery.BBQ. 
						// First jQuery.adress
						if ($.address)
						{
							$.address.change(function(e) {
								URLChange();
							});
						}
						else if ($.hashchange)
						{
							$(window).hashchange(URLChange);
						}
						// This means that the user must be using jQuery BBQ (I hope so, if not, back/forward buttons wont work in old browsers.)
						else
						{
							$(window).bind('hashchange', URLChange);
						}
						// In any case, i want to run that function once. 
						URLChange();
					}
					// The startSlide setting only require one line of code. And here it is:
					// startSlide is allways enabled, if not by the user, then by the code. 
					else animate(option[27]/*startSlide*/ - 1,falsev,falsev,falsev); 
					
					// Okay, now i want to do one last thing, loading the slides slowly one by one, so they are ready when needed. 
					//startAsyncDelayedLoad(); // To early. 
				})
			}
			
			/*
			 * The functions do the magic. 
			 */
			function startAsyncDelayedLoad ()
			{
				if (option[25]/*ajax*/ && parseInt10(option[26]/*preloadAjax*/))
				{
					for (a in option[25]/*ajax*/)
					{
						if (option[25][a])
						{
							clearTimeout(asyncTimedLoad);
							asyncTimedLoad = setTimeout(function(){
								//ajaxLoad(a, falsev, 0, falsev);
							},parseInt10(option[26]/*preloadAjax*/));
							break;
						}
					}
				}
			}
			function URLChange()
			{
				i = filterUrlHash(location.hash.substr(1));
				if (init) animate(i,falsev,falsev,falsev);
				else if (i != t) goToSlide(i, falsev);
			}
			function startAuto(pause)
			{
				autoOn = truev; // The variable telling that an automatic slideshow is running. 
				return setTimeout(function(){
					goToSlide("next", falsev);
				},pause);
			}
			function stopAuto()
			{
				clearTimeout(timeout);
				autoOn = falsev; // The variable telling that auto is no longer in charge. 
			}
			function textSpeedToNumber(speed)
			{	
				return (parseInt10(speed) || speed == 0) ?
						parseInt10(speed) :
					speed == 'fast' ?
						200 :
					(speed == 'normal' || speed == 'medium') ? 
						400 :
					speed == 'slow' ?
						600 :
					400;
			};
			// I go a long way to save lines of code. 
			function makecontrol(html, action)
			{
				return $(html).prependTo(controls).click(function(){
					goToSlide(action, truev);
					return falsev;
				});
			}
			// <strike>Simple function</strike><b>A litle complecated function after moving the auto-slideshow code and introducing some "smart" animations</b>. great work. 
			function goToSlide(i, clicked)
			{
				beforeAniFuncFired = falsev;
				if (!destroyed)
				{
					// Ahhh, recursive functions. I love me. 
					if (option[9]/*auto*/)
					{
						var delay = option[7]/*speed*/;
						if (fading && option[22]/*crossFade*/) delay = parseInt10((delay)*(3/5));
						else if (fading) delay = 0;
						// Stopping auto if clicked. And also continuing after X seconds of inactivity. 
						if(clicked){
							stopAuto();
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
							// Finding the "real" slide we are at. (-2 == 4). 
							i = filterDir(i, t);
							a = getRealPos(i);
							// Trying to do some magic, lets se if it works. 
							// I would like to find the shortest distance to the slide i want to slide to. 
							// First the standard route, to the one actually requested. 
							var diff = Math.abs(t-i);
							
							if (a < option[40]/*slideCount*/-orgSlideCount+1 && Math.abs(t - a - s)/* t - (a + s) */ < diff) // if (does any clone exist && is the route the shortest by going to that clone? )
							{
								i = a + s;
								diff = Math.abs(t - a - s); // Setting the new "standard", for how long the animation can be. 
							}
							if (a > ts - option[40]/*slideCount*/ && Math.abs(t- a + s)/* t - (a - s) */  < diff)
							{
								i = a - s;
							}
							
							// This is the old way of doing it, keeping it as a comment here for a while. 
							// And if i try to navigate to the neighbour, then why do it by sliding across the entire slider. 
							// getRealPos is magic. 
							//if (getRealPos(i) == getRealPos(a + 1 + option[42]/*moveCount*/)) i = 'next';
							//if (getRealPos(i) == getRealPos(a - 1 - option[42]/*moveCount*/)) i = 'prev';
						}
						// And now the animation itself. 
						animate(i,clicked,truev,falsev);
					}
				}
			};
			function fadeControl (fadeOpacity,fadetime,nextcontrol) // It may not sound like it, but the variable fadeOpacity is only for truev/falsev. 
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
				if (option[0]/*controlsShow*/)
				{
					if (option[12]/*prevNext*/) eA[fadeOpacity ? 'fadeIn' : 'fadeOut'](fadetime);
					if (firstlastshow) eB[fadeOpacity ? 'fadeIn' : 'fadeOut'](fadetime);
				}
				if(option[20]/*customLink*/)
				{
					$(option[20]/*customLink*/)
					.filter(function(index) { 
						return ($(this).attr("rel") == directionA || $(this).attr("rel") == directionB);
					})
					[fadeOpacity ? "fadeIn" : "fadeOut"](fadetime);
				} 
			};
			// Fade the controls, if we are at the end of the slide. 
			// It's all the different kind of controls. 
			function fadeControls (a,fadetime)
			{
				
				fadeControl (a,fadetime,falsev); // abusing that the number 0 == falsev. 
				// The new way of doing it. 
				fadeControl(a < s - orgSlideCount, fadetime, truev);
			};
			
			// Updating the 'current' class
			function setCurrent(i)
			{
				i = parseInt10((i>ts) ? 0 : ((i<0)? s+i : i)) + 1;
				for (a in numericControls) setCurrentElement(numericControls[a], i);
				if(option[20]/*customLink*/) setCurrentElement($(option[20]/*customLink*/), i);
			};
			function setCurrentElement(element,i)
			{
				if (element)
				{
					element
						.filter(".current")
						.removeClass("current")
						.each(function() {
							if (isFunc(option[31]/*uncurrentFunc*/)){ option[31]/*uncurrentFunc*/.call(this, $(this).attr("rel")); }
						});
						
					element
						.filter(function() { 
							// Tried to do it other ways, but i found that this is the only reliable way of doing it.
							b = $(this).attr("rel");
							if (option[13]/*numeric*/ == 'pages')
							{
								for (a = 0; a < orgSlideCount; a++)
								{
									if (b == i - a) return truev;
								}
							}
							else return b == i;
							return falsev; 
						})
						.addClass("current")
						.each(function(index) {
							if (isFunc(option[32]/*currentFunc*/)){ option[32]/*currentFunc*/.call(this, i); }
						});
					}
			};
			// Find out wich numericText fits the current url. 
			function filterUrlHash(a)
			{
				for (i in option[15]/*numericText*/) if (option[15]/*numericText*/[i] == a) return i;
				return a ? t : 0;
			};
			function runOnImagesLoaded (target, all, callback)
			{
				// Following code based on this plugin: https://gist.github.com/797120/7176db676f1e0e20d7c23933f9fc655c2f120c58
				var elems = target.add(target.find('img')).filter('img');
				var len = elems.length;
				if (!len)
				{
					callback();
				}
				elems.load(function() {
					// Webkit/Chrome (not sure) fix. 
					if (this.naturalHeight && !this.clientHeight)
					{
						$(this).height(this.naturalHeight).width(this.naturalWidth);
					}
					if (all)
					{
						len--;
						if (len == 0) callback();
					}
					else
					{
						callback();
					}
				}).each(function(){
					// cached images don't fire load sometimes, so we reset src.
					if ((this.complete || this.complete === undefined) && all)
					{
						var src = this.src;
						// webkit hack from http://groups.google.com/group/jquery-dev/browse_thread/thread/eee6ab7b2da50e1f
						// data uri bypasses webkit log warning (thx doug jones)
						this.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";
						this.src = src;
					}  
				}); 
			}	
			function autoadjust(i, speed)
			{
				// Both autoheight and autowidth can be enabled at the same time. It's like magic. 
				if (option[19]/*autoheight*/) autoheightwidth(i, speed, truev);//autoheight(i, speed);
				if (option[39]/*autowidth*/) autoheightwidth(i, speed, falsev);//autowidth(i, speed);
			}
			// Automaticly adjust the height and width, i love this function. 
			// Before i had one function for adjusting height, and one for width. Combining the two saved 134 chars in the minified version. 
			function autoheightwidth(i, speed, axis) // Axis: truev == height, falsev == width.
			{
				obj.ready(function() {// Not using .load(), because that only triggers when something is loaded.
					 adjustHeightWidth (i, speed, axis);
					// Then i run it again after the images has been loaded. (If any)
					// I know everything should be loaded, but just in case. 
					runOnImagesLoaded (li.eq(i), falsev, function(){
						adjustHeightWidth (i, speed, axis);
					});
				});
			};
			function adjustHeightWidth (i, speed, axis)
			{
				var i = getRealPos(i); // I assume that the continuous clones, and the original element is the same height. So i allways adjust acording to the original element.
				var target = li.eq(i);
				// First i run it. In case there are no images to be loaded. 
				b = target[axis ? "height" : "width"]();
				obj.animate(
					axis ? {height : b} : {width : b},
					{
						queue:falsev,
						duration:speed,
						easing:option[8]/*ease*/
					}
				);
			}
			function adjustPosition()
			{
				// Anything complicated here? No, so move on. The good stuff comes in the next function. 
				ul.css(option[6]/*vertical*/ ? "margin-top" : "margin-left",getSlidePos(t));
			};
			// <strike>This is a bit complicated, because Firefox won't handle it right. 
			// If i just used .position(), Firefox gets the position 1-2 px off pr. slide (i have no idea why). </strike>
			// Using display:block on #slider li, #slider ul seems to solve the problem. So i'm using .position now.
			function getSlidePos(slide)
			{
				// The new way <strike>Doesn't work well in some cases when ajax-loading stuff. </strike>
				return - liConti.eq(slide + (continuousClones ? option[40]/*slideCount*/ : 0)).position()[option[6]/*vertical*/ ? 'top' : 'left'];
				// The old way
				//b = 0;
				//for (var i = 0;i<=slide - ((option[11]/*continuous*/ && !option[21]/*fade*/) ? 1-option[40]/*slideCount*/ : 1);i++) b -= liConti.eq(i + ((option[11]/*continuous*/ && !option[21]/*fade*/) ? option[40]/*slideCount*/ : 0))[option[6]/*vertical*/ ? 'outerHeight' : 'outerWidth'](truev);
				//return b;
			};
			// 8 Lines of comments and 2 lines of code for one function. That can only be good. 

			// When the animation finishes (fade or sliding), we need to adjust the slider. 
			function adjust()
			{
				t = getRealPos(t); // Going to the real slide, away from the clone. 
				if(!option[24]/*updateBefore*/) setCurrent(t);
				adjustPosition();
				clickable = truev;
				if(option[17]/*history*/ && buttonclicked) window.location.hash = option[15]/*numericText*/[t];
				if (!fading && beforeAniFuncFired)
				{
					AniCall (t, truev); // I'm not running it at init, if i'm loading the slide. 
				}
			};
			// This function is called when i need a callback on the current element and it's continuous clones (if they are there).
			function AniCall (i, after) // after ? truev == afterAniFunc : falsev == beforeAniFunc;
			{
				// Lets run the after/before animation function.
				(after ? afterAniCall : beforeAniCall)(li.eq(i), getRealPos(i) + 1);
				// Now lets take the continuous clones.
				if (continuousClones)
				{
					// Don't even look at the below two lines, THEY WORK THE END!
					if (i < option[40]/*slideCount*/) (after ? afterAniCall : beforeAniCall)(liConti.eq((i<0) ? i + option[40]/*slideCount*/ : i - option[40]/*slideCount*/),i+1);
					if (i > ts - option[40]/*slideCount*/ || i == -option[40]/*slideCount*/) (after ? afterAniCall : beforeAniCall)(liConti.eq((i == -option[40]/*slideCount*/) ? -1 : option[40]/*slideCount*/ + i - ts - 1),i+1);
					// Things were a lot easier before i introduced slideCount, but i couldn't stop developing. 
				}
			
			}
			function afterAniCall(el, a)
			{
				if (isFunc(option[30]/*afterAniFunc*/)) option[30]/*afterAniFunc*/.call(el, a);
			}
			function beforeAniCall(el, a)
			{
				if (isFunc(option[29]/*beforeAniFunc*/)) option[29]/*beforeAniFunc*/.call(el, a);
			}
			// Convert the direction into a usefull number.
			function filterDir(dir, ot)
			{
				// This could be squeezed into 1 line. But setup as this, it's easy to understand :D (Its is actually one line, spread across more lines for readability)
				return dir == 'next' ?
						(ot>=ts) ? (option[11]/*continuous*/ ? t+1+option[42]/*moveCount*/ : (t==0) ? 1+option[42]/*moveCount*/ : 0) : t+1+option[42]/*moveCount*/ :
					dir == 'prev' ?
						(t<=0) ? (option[11]/*continuous*/ ? t-1-option[42]/*moveCount*/ : (t==ts) ? ts-1-option[42]/*moveCount*/ : ts) : t-1-option[42]/*moveCount*/ :
					dir == 'first' ?
						0 :
					dir == 'last' ?
						ts :
					parseInt10(dir);
			};
			// Load a ajax document (or i image) into a list element. 
			// If testing this locally (loading everything from a harddisk instead of the internet), it may not work. 
			// But then try to upload it to a server, and see it shine. 
			function ajaxLoad(i, adjust, speed, ajaxCallBack)
			{
				if (asyncTimedLoad) clearTimeout(asyncTimedLoad);// I dont want it to run to often. 
				// <strike>Not as complicated as it looks. </strike> Everything complicated about this line disappeared in version 2.0.12
				var target = option[25]/*ajax*/[i],
				targetslide = li.eq(i),
				// parsing the init variable. 
				ajaxInit = speed === truev,
				speed = (speed === truev) ? 0 : speed,
				// What speed should the autoheight function animate with?
				ajaxspeed = (fading) ? (!option[22]/*crossFade*/ ? parseInt10(option[23]/*fadespeed*/ * (2/5)) : option[23]/*fadespeed*/) : speed,
				// The script itself is not using the 'tt' variable. But a custom function can use it. 
				tt = i + 1,
				textloaded = falsev;
				
				// The thing that loads it. 
				$.ajax({
					url: target,
					success: function(data, textStatus, jqXHR){
						var type = jqXHR.getResponseHeader('Content-Type').substr(0,5);
						if (type != 'image')
						{
							textloaded = truev;
							targetslide.html(data);
							ajaxAdjust(i, speed, ajaxCallBack, adjust, ajaxInit, falsev);
						}
					},
					complete: function(jqXHR){
						// Some browsers wont load images this way, so i treat an error as an image. 
						// There is no stable way of determining if it's a real error or if i tried to load an image in a old browser, so i do it this way. 
						if (!textloaded)
						{
							// Load the image.
							image = new Image();
							targetslide.html('').append(image);
							// Just in case it isn't an image after all. 
							// Sometimes this fires, when the content is an image. Havn't found out why yet, so i keep it commented out. 
							/*image.onerror = function () {
								targetslide.html('<p>The content could not be loaded.</p>');
								if (adjust) autoadjust(i, speed);
							}^*/ 
							image.src = target;
							// Lets just make some adjustments
							ajaxAdjust(i, speed, ajaxCallBack, adjust, ajaxInit, truev);
						}
					}
				});
				// It is loaded, we dont need to do that again. 
				option[25]/*ajax*/[i] = falsev;
				// It is the only option that i need to change for good. 
				options.ajax[i] = falsev;
			};
			function ajaxAdjust(i, speed, ajaxCallBack, adjust, ajaxInit, img){
				
				var target = li.eq(i);
				// Now to see if the generated content needs to be inserted anywhere else. 
				if (continuousClones)
				{
					if (i < option[40]/*slideCount*/) liConti.eq((i<0) ? i + option[40]/*slideCount*/ : i - option[40]/*slideCount*/).replaceWith($(target).clone());
					if (i > ts - option[40]/*slideCount*/) liConti.eq(option[40]/*slideCount*/ + i - ts - 1).replaceWith($(target).clone());
					// The liConti gets messed up a bit in the above code, therefore i fix it. 
					liConti = ul.children("li");
					if (ajaxInit === truev) adjustPosition();// Only doing this little trick at init. 
				}
				if (adjust) autoadjust(i, speed);
				
				runOnImagesLoaded (target, truev, function(){
					if (ajaxInit === truev) adjustPosition();// Doing this little trick after the images are done. 
					// And the callback. 
					if (isFunc(ajaxCallBack)) ajaxCallBack();
					startAsyncDelayedLoad();
				});
				// If we want, we can launch a function here. 
				// This line is after the "runOnImagesLoaded" function has run, because that might fix some height/width == 0 problems in webkit browsers. 
				if (isFunc(option[28]/*ajaxLoadFunction*/)){option[28]/*ajaxLoadFunction*/.call(target, parseInt10(i)+1, img);}
				// In some cases, i want to call the beforeAniFunc here. 
				if (ajaxCallBack == 2)
				{
					AniCall(i, falsev);
					if (!beforeAniFuncFired)
					{
						AniCall(i, truev);
						beforeAniFuncFired = truev;
					}
					
				}
				
			};
			// It's not only a slider, it can also fade from slide to slide. 
			function fadeto(i, clicked, ajaxcallback)
			{
				if (filterDir(i, ot) != t && !destroyed && clickable) // We doesn't want something to happen all the time. The URL can change a lot, and cause som "flickering". 
				{
					// Just leave the below code as it is, i've allready spent enough time trying to improve it, it allways ended up in me making nothing that worked like it should. 
					ajaxloading = falsev;
					// Update the current class of the buttons. 
					if (option[24]/*updateBefore*/) setCurrent(filterDir(i, ot));
					// Setting the speed. 
					var speed = (!clicked && !option[9]/*auto*/ && option[17]/*history*/) ? option[23]/*fadespeed*/ * (option[18]/*speedhistory*/ / option[7]/*speed*/) : option[23]/*fadespeed*/,
					// I don't want to fade to a continuous clone, i go directly to the target. 
					ll = getRealPos(filterDir(i, ot));
					// Lets make sure the prev/next buttons also fade. 
					if(option[2]/*controlsFade*/) fadeControls (ll,option[1]/*controlsFadeSpeed*/);

					
					if (ajaxcallback)
					{
						speed = oldSpeed;
						// Do a check if it can continue.
						if (dontCountinueFade) dontCountinueFade--; // It is nice that 0 == falsev;
					}
					else if (option[25]/*ajax*/)
					{
						// Before i can fade anywhere, i need to load the slides that i'm fading too (needs to be done before the animation, since the animation includes cloning of the target elements. 
						dontCountinueFade = 0;
						oldSpeed = speed;
						for (a = ll; a < ll + orgSlideCount; a++)
						{
							if (option[25]/*ajax*/[a])
							{
								ajaxLoad(getRealPos(a), falsev, speed, function(){
									fadeto(i, clicked, truev);
								});
								dontCountinueFade++;
							}
						}
					}
					else
					{
						dontCountinueFade = falsev;
					}
					if (!dontCountinueFade) // if (dontCountinueFade == 0)
					{
						// Only clickable if not clicked.
						clickable = !clicked;
						autoadjust(ll,option[23]/*fadespeed*/); // The height animation takes the full lenght of the fade animation (fadein + fadeout if it's not crossfading).  
						// So lets run the function.
						AniCall(ll, falsev);
						// Crossfading?
						if (option[22]/*crossFade*/)
						{
							var firstRun = truev,
							push = 0;
							// Define the target. Maybe more than one. 
							for (a = ll; a < ll + orgSlideCount; a++)
							{
								// I clone the target, and fade it in, then hide the cloned element while adjusting the slider to show the real target.
								li.eq(getRealPos(a)).clone().prependTo(obj).css({'z-index' : '100000', 'position' : 'absolute', 'list-style' : 'none', 'top' : option[6]/*vertical*/ ? push : 0, 'left' : option[6]/*vertical*/ ? 0 : push}).
								// Lets fade it in. 
								hide().fadeIn(option[23]/*fadespeed*/, function() {
									if (fontsmoothing) this.style.removeAttribute("filter"); // Fix cleartype
									// So the animate function knows what to do. 
									clickable = truev;
									fading = truev;
									if (firstRun)
									{
										animate(ll,falsev,falsev,falsev); // Moving to the correct place.
										if(option[17]/*history*/ && clicked) window.location.hash = option[15]/*numericText*/[t]; // It's just one line of code, no need to make a function of it. 
										// Now run that after animation function.
										AniCall(ll, truev);
										firstRun = falsev;
									}
									// Removing the clone, if i dont, it will just be a pain in the ....
									$(this).remove();
									
									// Lets put that variable back to the default (and not during animation) value. 
									fading = falsev;
									
								});
								push += li.eq(a)[option[6]/*vertical*/ ? 'outerHeight' : 'outerWidth'](truev);
							}
						}
						else
						{
							// fadeOut and fadeIn.
							var fadeinspeed = parseInt10((speed)*(3/5)),
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
									if (fontsmoothing) this.style.removeAttribute("filter"); // Fix cleartype
									if(option[17]/*history*/ && clicked) window.location.hash = option[15]/*numericText*/[t]; // It's just one line of code, no need to make a function of it. 
									clickable = truev;
									fading = falsev;
									// Now run that after animation function.
									AniCall(ll, truev);
								});
							});
						}
					}
				}
			};
			function animate(dir,clicked,time,ajaxcallback) // (Direction, did the user click something, is this to be done in >1ms?, is this inside a ajaxCallBack?) 
			{
				if ((clickable && !destroyed && (filterDir(dir, ot) != t || init)) || ajaxcallback)
				{
					if (!ajaxcallback) ajaxloading = falsev;
					clickable = (!clicked && !option[9]/*auto*/) ? truev : option[16]/*clickableAni*/;
					// to the adjust function. 
					buttonclicked = clicked;
					ot = t;
					t = filterDir(dir, ot);
					if (option[24]/*updateBefore*/) setCurrent(t);
					// Calculating the speed to do the animation with. 
					var diff = Math.sqrt(Math.abs(ot-t)),
					speed = (!time) ? 0 : ((!clicked && !option[9]/*auto*/) ? parseInt10(diff*option[18]/*speedhistory*/) : parseInt10(diff*option[7]/*speed*/)),
					// Ajax begins here 
					// I also these variables in the below code (running custom function).
					i = getRealPos(t);
					if (ajaxcallback)
					{
						speed = oldSpeed;
						// Do a check if it can continue.
						if (dontCountinue) dontCountinue--; // It is nice that 0 == falsev;
					}
					else if (option[25]/*ajax*/)
					{
						// Loading the target slide, if not already loaded. 
						if (option[25]/*ajax*/[i]) 
						{
							ajaxLoad(i, truev, init || speed, 2); // 2 for AniCall
							ajaxloading = truev;
						}
						// The slider need to have all slides that are scrolled over loaded, before it can do the animation.
						// That's not easy, because the slider is only loaded once a callback is fired. 
						if (!fading)
						{
							// A tiny dragon do live within this cave.
							var aa = (ot>t) ? t : ot,
							ab = (ot>t) ? ot : t;
							dontCountinue = 0;
							oldSpeed = speed;
							for (a = aa; a <= ab; a++)
							{
								if (a<=ts && a>=0 && option[25]/*ajax*/[a])
								{
									ajaxLoad(a, falsev, speed, function(){
										animate(dir,clicked,time, truev);
									});
									dontCountinue++;
								}
							}
							// The tiny dragon just shrunk.
						}
						// Then we have to preload the next ones. 
						for (a = i+1; a <= i + orgSlideCount; a++)
						{
							if (option[25]/*ajax*/[a]) ajaxLoad(a, falsev, 0, falsev);
						}
					}
					if (!dontCountinue)
					{
						if (!fading && !ajaxloading)
						{
							// Lets run the beforeAniCall
							AniCall(i, falsev);
							beforeAniFuncFired = truev;
						}
						if (!fading) autoadjust(t, speed);
						b = getSlidePos(t);
						ul.animate(
							option[6]/*vertical*/ ? { marginTop: b } : { marginLeft: b},
							{
								queue:falsev,
								duration:speed,
								easing:option[8]/*ease*/,
								complete:adjust
							}
						);
						// End animation. 
						
						// Fading the next/prev/last/first controls in/out if needed. 
						if(option[2]/*controlsFade*/)
						{
							var fadetime = option[1]/*controlsFadeSpeed*/;
							if (!clicked && !option[9]/*auto*/) fadetime = (option[18]/*speedhistory*/ / option[7]/*speed*/) * option[1]/*controlsFadeSpeed*/;					
							if (!time) fadetime = 0;
							if (fading) fadetime = parseInt10((option[23]/*fadespeed*/)*(3/5));
							fadeControls (t,fadetime);
						}
						// startAsyncDelayedLoad doesn't start by itself, it does only when another ajax load has finished (or in the below line). 
						if (init && option[25]/*ajax*/) if (!option[25]/*ajax*/[i]) startAsyncDelayedLoad();
						// Stop init, first animation is done. 
						init = falsev; //nasty workaround, but it works. 
						
					};
				}
			};
			function getRealPos(a) //instead of the position of the "continuous-clone"
			{
				return a < 0 ?
						a + s :
					a > ts ?
						a - s :
					a;
			}
			function isFunc(func) //Closure compiler inlines this. But i still keep it. 
			{
				return $.isFunction(func);
			}
			// This fixes rare but potential bugs and saves space (when i talk about saving space, i allways talk about the minified version, this version (the unminified) is used when people want to debug or change the code (yes, that happens)). 
			function parseInt10 (num)
			{
				return parseInt(num,10);
			}
		   /*
			* Public functions. 
			*/
			baseSlider.getOption = function(a){
				return options[a];
			}
			baseSlider.setOption = function(a, val){
				// I only change it, if you did input a value. 
				if (val)
				{
					baseSlider.destroy(); // Make it easy to work. 
					options[a] = val; // Sets the semi-global option. 
					baseSlider.init(); // This makes sure that the semi-local options is inserted into the slide again. 
				}
				return baseSlider;
			}
			baseSlider.insertSlide = function(html, pos, numtext){
				// If there's no content, this doesn't make sense. 
				if (html)
				{
					// First we make it easier to work. 
					baseSlider.destroy();
					// pos = 0 means before everything else. 
					// pos = 1 means after the first slide.
					if (pos > s) pos = s; // If you try to add a slide after the last slide fix. 
					var html = '<li>' + html + '</li>';
					if (!pos || pos == 0) ul.prepend(html);
					else li.eq(pos -1).after(html);
					// Finally, we make it work again. 
					if (pos <= destroyT || (!pos || pos == 0)) destroyT++;
					if (option[15]/*numericText*/.length < pos){ option[15]/*numericText*/.length = pos;}
					option[15]/*numericText*/.splice(pos,0,numtext || parseInt10(pos)+1);
					baseSlider.init();
				}
				return baseSlider;
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
				return baseSlider;
			}
			baseSlider.goToSlide = function(a){
				goToSlide((a == parseInt10(a)) ? a - 1 : a, truev);
				return baseSlider;
			}
			baseSlider.block = function(){
				clickable = falsev; // Simple, beautifull.
				return baseSlider;
			}
			
			baseSlider.unblock = function(){
				clickable = truev; // Simple, beautifull.
				return baseSlider;
			}
			
			baseSlider.startAuto = function(){
				option[9]/*auto*/ = truev;
				timeout = startAuto(option[10]/*pause*/);
				return baseSlider;
			}
			
			baseSlider.stopAuto = function(){
				option[9]/*auto*/ = falsev;
				stopAuto();
				return baseSlider;
			}
			
			baseSlider.destroy = function(){
				// Saving the current position.
				destroyT = t;
				// First, i remove the controls. 
				if (controls) controls.remove(); // that's it.
				// Now to set a variable, so nothing is run. 
				destroyed = truev; // No animation, no fading, no clicking from now. 
				// Then remove the customLink bindings:
				$(option[20]/*customLink*/).die("click");
				// Now remove the "continuous clones". 
				if (continuousClones) for (a=1;a<=option[40]/*slideCount*/;a++) liConti.eq(a-1).add(liConti.eq(-a)).remove();
				// I need the slider to be at the same place.
				ul.css(option[6]/*vertical*/ ? "margin-top" : "margin-left",getSlidePos(t));
				// And now it's done. The only way to make this slider do something visible, is by making a new init. 
				return baseSlider;
			}
			baseSlider.init = function(){
				// Two inits can really fuck things up. 
				if (destroyed) {
					initSudoSlider(obj, destroyT);	
				}
				return baseSlider;
			}
			baseSlider.adjust = function(speed){
				if (!speed) speed = 0;
				autoadjust(i, speed)
				return baseSlider;
			}
			baseSlider.getValue = function(a){
				return a == 'currentSlide' ?
						t + 1 :
					a == 'totalSlides' ?
						s :
					a == 'clickable' ?
						clickable :
					a == 'destroyed' ?
						destroyed :
					a == 'autoAnimation' ?
						autoOn :
					undefined;
			}
		});
	};
})(jQuery);
// If you did just read the entire code, congrats. 
// Did you find a bug? I didn't, so plz tell me if you did. (http://webbies.dk/SudoSlider/help/ask-me.html)