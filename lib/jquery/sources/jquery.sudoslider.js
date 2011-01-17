/*
 *  Sudo Slider ver 1.0.12 - jQuery plugin 
 *  Written by Erik Kristensen info@webbies.dk. 
 *  Based on Easy Slider 1.7 by Alen Grakalic http://cssglobe.com/post/5780/easy-slider-17-numeric-navigation-jquery-slider
 *	
 *	 Dual licensed under the MIT
 *	 and GPL licenses.
 * 
 *	 Built for jQuery library
 *	 http://jquery.com
 *
 */
 /* 
 TODO:
 Finding and fixing bugs. (No known bugs atm.) 

 TODO for next major version: (i've had 10 minor versions now, don't think i'm ever going to do a major). 
 */
(function($)
{
	$.fn.sudoSlider = function(options)
	{
		if (typeof(options) != 'object' && options != '' && options) 
		{ 
			// Here, i just trigger it to do something, it's way down the actual action happens. 
			this.each(function(){
				$(this).trigger("sudoSliderEvent", [options]);
			});
		}
		else
		{
			// default configuration properties
			var defaults = {
				prevNext:          true,
				prevHtml:          '<a href="#" class="prevBtn"> previous </a>',
				nextHtml:          '<a href="#" class="nextBtn"> next </a>',
				controlsShow:      true,
				controlsAttr:      'id="controls"',
				controlsFadeSpeed: '400',
				controlsFade:      true,
				insertAfter:       true,
				firstShow:         false,
				firstHtml:         '<a href="#" class="firstBtn"> first </a>',
				lastShow:          false,
				lastHtml:          '<a href="#" class="lastBtn"> last </a>',
				numericAttr:       'class="controls"',
				numericText:       ['1'],
				vertical:          false,
				speed:             '800',
				ease:              'swing',
				auto:              false,
				pause:             '2000',
				continuous:        false,
				clickableAni:      false,
				numeric:           false,
				updateBefore:      false,
				history:           false,
				speedhistory:      '400',
				autoheight:        true,
				customLink:        false,
				fade:              false,
				crossFade:         true,
				fadespeed:         '1000',
				ajax:              false,
				loadingText:       false,
				preloadAjax:       false,
				startSlide:        false,
				imgAjaxFunction:   false,
				docAjaxFunction:   false,
				beforeAniFunc:     false,
				afterAniFunc:      false,
				uncurrentFunc:     false,
				currentFunc:       false
			};
			var options = $.extend(defaults, options);
			
			// To make it smaller when minimized.
			// Not including the variables only used once in the code. 
			// I just found out that this (as in now, not the same as final release) makes the script 572 bytes smaller. (That's about 6%)
			//var optionscontrolsShow = options.controlsShow; // Only used once.
			var optionscontrolsFadeSpeed = options.controlsFadeSpeed;
			//var optionscontrolsBefore = options.controlsBefore; // Only used once.
			//var optionscontrolsAfter = options.controlsAfter; // Only used once.
			var optionscontrolsFade = options.controlsFade;
			//var optionsinsertAfter = options.insertAfter;// Only used once.
			var optionsfirstShow = options.firstShow;
			var optionslastShow = options.lastShow;
			var optionsvertical = options.vertical;
			var optionsspeed = options.speed;
			var optionsease = options.ease;
			var optionsauto = options.auto;
			var optionspause = options.pause;
			var optionscontinuous = options.continuous;
			var optionsprevNext = options.prevNext;
			var optionsnumeric = options.numeric;
			var optionsnumericAttr = options.numericAttr;
			var optionsnumericText = options.numericText;
			//var optionsclickableAni = options.clickableAni; // Only used once.
			var optionshistory = options.history;
			var optionsspeedhistory = options.speedhistory;
			var optionsautoheight = options.autoheight;
			var optionscustomLink = options.customLink;
			var optionsfade = options.fade;
			var optionscrossFade = options.crossFade;
			var optionsfadespeed = options.fadespeed;
			var optionsupdateBefore = options.updateBefore;
			var optionsajax = options.ajax;
			//var optionspreloadAjax= options.preloadAjax; // Only used once.
			var optionsstartSlide = options.startSlide;
			var optionsimgAjaxFunction = options.imgAjaxFunction;
			var optionsdocAjaxFunction = options.docAjaxFunction;
			var optionsbeforeAniFunc = options.beforeAniFunc;
			var optionsafterAniFunc = options.afterAniFunc;
			var optionsuncurrentFunc = options.uncurrentFunc;
			var optionscurrentFunc = options.currentFunc;
			
			return this.each(function()
			{	
				// There are some things we don't do at init. 
				var init = true; // I know it's an ugly workaround, but it works. 
				// If auto is on, so is continuous. (People tend to forget things they don't think about :p)
				if (optionsauto) optionscontinuous = true;
				
				// Setting up some variables. 
				var obj = $(this);
				
				// Fix for nested list items
				var ul = obj.children("ul");
				var li = ul.children("li");
				
				// Some variables i'm gonna use alot. 
				var s = li.length;
				var w = li.eq(0).width(); // All slides must be same width, so this shouldn't be a problem. 
				var h = obj.height();
				
				// Now we are going to fix the document, if it's 'broken'. (No <ul> or no <li>). 
				// I assume that it's can only be broken, if ajax is enabled. If it's broken without Ajax being enabled, the script doesn't have anything to fill the holes. 
				if (optionsajax)
				{
					// Is the ul element there?
					if (ul.length == 0)
					{
						// No it's not, lets create it. 
						obj.append('<ul></ul>');
						ul = obj.children("ul");
					}
				
					// Do we have enough list elements to fill out all the ajax documents. 
					if (optionsajax.length > s)
					{
						// No we dont. 
						for (var i = 1; i <= optionsajax.length - s; i++) ul.append('<li><p>' + returnFunnyLoadingText() + '</p></li>');
						li = ul.children("li");
						s = li.length;
						w = li.eq(0).width();
					}
				}				
				// i just love stackoverflow (http://stackoverflow.com/questions/182112/what-are-some-funny-loading-statements-to-keep-users-amused-closed)
				// i know this is just an easter egg, because you really should never see it. 
				// But what the heck, i want it. 
				function returnFunnyLoadingText()
				{
					var funnyLoadingTexts = [
						//"Locating the required gigapixels to render",
						"Spinning up the hamster",
						//"&pi; &times; 1337% == 42",
						//"&pi; &times; 1337% != 42!",
						//"Shovelling coal into the server",
						"Programming the flux capacitor",
						//"The last time I tried this the monkey didn't survive",
						"Testing data on Timmy... ... ... We need another Timmy",
						//"I should have had a V8 this morning",
						//"My other load screen is much faster. You should try that one instead",
						//"The version I have of this in testing has much funnier load screens",
						"Warming up Large Hadron Collider",
						"It looks like you're waiting for something to load"
					];
					return options.loadingText ? options.loadingText : (funnyLoadingTexts[Math.round(Math.random()*(funnyLoadingTexts.length-1))] + '...');
				};
				
				
				// Continuing with the variables. 
				var t = 0;
				var ot = t;
				var nt = t;
				var ts = s-1;
				
				var clickable = true;
				var buttonclicked = false;
				var fading = false;
				var ajaxloading = false;
				var autoheightdocument = 0;
				var numericControls = new Array();
				var numericContainer = false;
				var destroyed = false;
				
				// Set obj overflow to hidden (and position to relative <strike>, if fade is enabled. </strike>)
				obj.css("overflow","hidden");
				if (obj.css("position") == "static") obj.css("position","relative"); // Fixed a lot of IE6 + IE7 bugs. 
	
				// Float items to the left
				li.css('float', 'left');
				
				// They doens't always put a text in the numericText. 
				// With this, if the user dont, the code will. 
				for(var i=0;i<s;i++)
				{
					if (optionsnumericText[i] == undefined) optionsnumericText[i] = (i+1);
					// Same thing for ajax thingy. 
					if (optionsajax && optionsajax[i] == undefined) optionsajax[i] = false;
				}
				
				// Clone elements for continuous scrolling
				if(optionscontinuous)
				{
					if(optionsvertical)
					{
						// First we create the elements, pretending AJAX is a city in Russia. 
						ul.prepend(li.filter(":last-child").clone().css("margin-top","-"+ h +"px"));
						ul.append(li.filter(":nth-child(2)").clone());
						ul.height((s+1)*h);
					} else {
						// First we create the elements, pretending AJAX is a city in Russia. 
						ul.prepend(li.filter(":last-child").clone().css("margin-left","-"+ w +"px"));
						ul.append(li.filter(":nth-child(2)").clone());
						ul.width((s+1)*w);
					}
					// Now, lets check if AJAX really is a city in Russia.
					if (optionsajax)
					{
						// Now we move from Russia back to reallity (nothing bad about the Russians, it's just a saying in Denmark.)
						// Starting with putting the first document after the last. 
						if (optionsajax[0]) {
							ajaxLoad('last', 0, false, 0);
							// ajaxLoad(0, 0, false, 0); //And this would just be a waste of brandwith. 
						}
						// And putting the last document before the first. 
						if (optionsajax[s-1])
						{
							ajaxLoad('first', (s-1), false, 0);
							// And then preloading the last document (the same document, but into it's entended position). No need to preload the first slide, it gets loaded elsewhere. 
							ajaxLoad(ts, ts, false, 0);
							optionsajax[s-1] = false;
						}
					}
				}
				else // <strike>Bug fix</strike> feature.
				{
					if(optionsvertical)	ul.height(s*h);
					else ul.width(s*w);
				};
				
				// Display the controls.
				if(options.controlsShow)
				{
					var controls = $('<span ' + options.controlsAttr + '></span>');
					if (options.insertAfter) $(obj).after(controls);
					else $(obj).before(controls);
					
					var html = options.controlsBefore;
					if(optionsnumeric) {
						numericContainer = controls.prepend('<ol '+ optionsnumericAttr +'></ol>').children();
						for(var i=0;i<s;i++)
						{
							numericControls[i] = $(document.createElement("li"))
							.attr({'rel' : (i+1)})
							.html('<a href="#"><span>'+ optionsnumericText[i] +'</span></a>') 
							.appendTo(numericContainer)
							.click(function(){
								goToSlide($(this).attr('rel') - 1, true);
								return false;
							});
						};
					}
					if(optionsfirstShow) {
						var firstbutton = makecontrol(options.firstHtml, "first");
					}
					if(optionslastShow) {
						var lastbutton = makecontrol(options.lastHtml, "last");
					}
					if(optionsprevNext){
						var nextbutton = makecontrol(options.nextHtml, "next");
						var prevbutton = makecontrol(options.prevHtml, "prev");
					}
				};
				
				// Preload elements. 
				if (options.preloadAjax)
				{
					for (var i=0;i<=ts;i++) // Preload everything.
					{
						
						if (optionsajax[i])
						{
							// If somethings is to be loaded, lets load it. 
							ajaxLoad(i, i, false, 0);
							// Making sure it aint loaded again. 
							optionsajax[i] = false;
						}
					}
				}
				
				function goToSlide(i, clicked)
				{
					if (!destroyed)
					{
						if (optionsfade)
						{
							fadeto(i, clicked);
						} else {
							animate(i,clicked,true);
						}
					}
				};
				// I go a long way to save lines of code. 
				function makecontrol(html, action)
				{
					var button = $(html);
					controls.prepend(button);
					button.click(function(){
						goToSlide(action, true);
						return false;
					});
					return button;
				}
				
				// Lets make those fast/normal/fast into some numbers we can make calculations with.
				optionscontrolsFadeSpeed = textSpeedToNumber(optionscontrolsFadeSpeed);
				optionsspeed = textSpeedToNumber(optionsspeed);
				optionspause = textSpeedToNumber(optionspause);
				optionsspeedhistory = textSpeedToNumber(optionsspeedhistory);
				optionsfadespeed = textSpeedToNumber(optionsfadespeed);
				// The functions do the magic.
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
							break;
						case 'normal':
							returnspeed = 400;
							break;
						case 'medium':
							returnspeed = 400;
							break;
						case 'slow':
							returnspeed = 600;
							break;
						}
					}
					return returnspeed;
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
					var image = false;
					
					var len = file.length;
					var ext = file.substr(len-4, 4);
					
					if (ext == '.jpg' || ext == '.png' || ext == '.bmp' || ext == '.gif') 
					{
						image = true;
					}
					var ext = file.substr(len-5, 5);
					if (ext == '.jpeg')
					{
						image = true;
					}
					
					return image;
				}
				function fadeControl (fadeOpacity,fadetime,nextcontrol)
				{
					if (nextcontrol)
					{
						var eA = nextbutton;
						var eB = lastbutton;
						var directionA = 'next';
						var directionB = 'last';
						var firstlastshow = optionslastShow;
					}
					else
					{
						var eA = prevbutton;
						var eB = firstbutton;
						var directionA = 'prev';
						var directionB = 'first';
						var firstlastshow = optionsfirstShow;
					}
					if (!optionscontinuous)
					{
						if (optionsprevNext) eA.fadeTo(fadetime, fadeOpacity, function() { if (fadeOpacity == 0) $(this).hide();});
						if (firstlastshow) eB.fadeTo(fadetime, fadeOpacity, function() { if (fadeOpacity == 0) $(this).hide();});
						if(optionscustomLink)
						{
							$(optionscustomLink)
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
					if(a==0) fadeControl (0,fadetime,false);
					else fadeControl (1,fadetime,false);
					
					if(a==ts) fadeControl (0,fadetime,true);
					else fadeControl (1,fadetime,true);
				};
				
				
				
				// Updating the 'current' class
				function setCurrent(i)
				{
					i = parseInt((i>ts) ? i=0 : ((i<0)? i=ts: i)) + 1;
					for(var a=0;a<numericControls.length;a++) setCurrentElement(numericControls[a], i);
					if(optionscustomLink) setCurrentElement(optionscustomLink, i);
				};
				function setCurrentElement(element,i)
				{
					$(element)
						.filter(".current")
						.removeClass("current")
						.each(function() {
							if ($.isFunction(optionsuncurrentFunc)){ optionsuncurrentFunc.call(this, $(this).attr("rel")); }
						});
					$(element)
						.filter(function() { 
							return $(this).attr("rel") == i; 
						})
						.addClass("current")
						.each(function(index) {
							if ($.isFunction(optionscurrentFunc)){ optionscurrentFunc.call(this, i); }
						});
				};
				// Find out wich numericText fits the current url. 
				function filterUrlHash(t)
				{
					var te = 0;
					for (var i=0;i<=s;i=i+1) if (optionsnumericText[i] == t) te = i;
					return te;
				};
				// Automaticly adjust the height, i love this function. 
				function autoheight(i, speed)
				{
					if (i == s) i = 0;
					// First i run it. In case there are no images. 
					var target = li.eq(i);
					var nheight = target.height();
					if (nheight != 0) setHeight(nheight, speed);
					// Then i run it again after the images has been loaded. (If any)
					runOnImagesLoaded(target,function(imgtarget){
						nheight = $(imgtarget).height();
						if (nheight != 0) setHeight(nheight, speed);
					});
				};
				function setHeight(nheight, speed)
				{
					obj.animate(
						{ height:nheight},
						{
							queue:false,
							duration:speed,
							easing:optionsease
						}
					);
				};
				// When the animation finishes (fade or sliding), we need to adjust the slider. 
				function adjust()
				{
					if(t>ts) t=0;
					if(t<0) t=ts;
					if (!optionsupdateBefore) setCurrent(t);
					if(optionsvertical) ul.css("margin-top",(t*h*-1)); 
	 				else ul.css("margin-left",(t*w*-1)); 
					clickable = true;
					if(optionshistory && buttonclicked) window.location.hash = optionsnumericText[t];
					if (!fading)
					{
						// Lets run the after animation function.
						if ($.isFunction(optionsafterAniFunc)){optionsafterAniFunc.call(li.eq(t) , t + 1);}
					}
				};
				// Convert the direction into a usefull number.
				function filterDir(dir, ot)
				{
					var nt = t; // i dont want to mess with the 't' variable.
					switch(dir)
					{
						case "next":
							nt = (ot>=ts) ? (optionscontinuous ? nt+1 : ts) : nt+1;
							break;
						case "prev":
							nt = (t<=0) ? (optionscontinuous ? nt-1 : 0) : nt-1;
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
				function ajaxLoad(i, l, adjust, speed)
				{
					var targetslide = false;
					if (parseInt(i) || i == 0) targetslide = li.eq(i);
					else
					{
						if (i == 'last') targetslide = $('li:last', obj);
						else targetslide = $('li:first', obj);
					}
					// What speed should the autoheight function animate with?
					var ajaxspeed = (fading) ? (!optionscrossFade ? parseInt(optionsfadespeed * (2/5)) : optionsfadespeed) : speed;
					// The script itself is not using the 'tt' variable. But a custom function can use it. 
					var tt = l + 1;
					if (imageCheck(optionsajax[l])) 
					{
						// Load the image.
						targetslide.html(' ').append($(new Image()).attr('src', optionsajax[l]));
						// When the document is ready again, we launch a autoheight event. 
						runOnImagesLoaded(targetslide,function(img){
							var target = $(img).children();
							// If the image is to wide, shrink it. 
							var width = target.width();
							var height = target.height();
							target.attr({'oldheight' : height, 'oldwidth' : width});
							if (width > w) target.animate({ width: w, height: (height/width)*w}, 0);
							// If we want, we can launch a function here. 
							if ($.isFunction(optionsimgAjaxFunction)){optionsimgAjaxFunction.call($(img), tt);}
							// Then do the autoheight.
							if (optionsautoheight && adjust) autoheight(t, ajaxspeed);
						});
					}
					else
					{
						
						// Load the document into the list element. 
						targetslide.load(optionsajax[l], function(response, status, xhr) {
							if (status == "error" || !$(this).html()) $(this).html("Sorry but there was an error: " + (xhr.status ? xhr.status: 'no content') + " " + xhr.statusText);
							// If we want, we can launch a function here. 
							if (status != "error" && $.isFunction(optionsdocAjaxFunction)){optionsdocAjaxFunction.call($(this), tt);}
							// Lets adjust the height, i don't care if there's an error or not. 
							// var nheight = $(this).height(); // Why did i put that there??? Delete this comment when reason is found. 
							if (optionsautoheight && adjust) autoheight(l, ajaxspeed); // This is theoreticly a bug-fix, if there are no images in the loaded document. 
						});
					}
				};
				// It's not only a slider, it can also fade from slide to slide. 
				function fadeto(i, clicked)
				{
					if (i != t && !destroyed) // We doesn't want something to happen all the time. The URL can change a lot, and cause som "flickering". 
					{
						if (clickable)
						{
							ajaxloading = false;
							// Stop auto if cliked.
							if(clicked) clearTimeout(timeout);
							// Update the current class of the buttons. 
							if (optionsupdateBefore) setCurrent(filterDir(i, ot));
							// Only clickable if not clicked.
							clickable = !clicked;
							// Setting the speed. 
							var speed = (!clicked && !optionsauto && optionshistory) ? optionsfadespeed * (optionsspeedhistory / optionsspeed) : optionsfadespeed;
							var ll = filterDir(i, ot);
							// Lets make sure that the target actually exists. 
							if(ll>ts) ll=0; 
							if(ll<0) ll=ts;
							// Lets make sure the prev/next buttons also fade. 
							if(optionscontrolsFade) fadeControls (ll,optionscontrolsFadeSpeed);
							// Lets adjust the height, but not if the ajax document isn't loaded. 
							if (optionsautoheight) 
							{
								if (optionsajax)
								{
									// If Ajax is enabled
									if (!optionsajax[ll]) autoheight(ll,optionsfadespeed); // we only want to change the height, if the document we are fading to, is allready loaded.
								}
								else autoheight(ll,optionsfadespeed); // The height animation takes the full lenght of the fade animation (fadein + fadeout if it's not crossfading).  
							}
							// Define the target. 
							var target = li.eq(ll);
							// So lets run the function.
							if ($.isFunction(optionsbeforeAniFunc)){optionsbeforeAniFunc.call(target, ll + 1);}
							// Crossfading?
							if (optionscrossFade)
							{
								// I clone the target, and fade it in, then hide the cloned element while adjusting the slider to show the real target.
								// I dont hide it right away, because that breaks the autoheight function, and the function that auto-resizes ajax-loaded images.
								var fadeIntarget = target.clone().prependTo(obj).css({'z-index' : '100000', 'position' : 'absolute', 'list-style' : 'none', 'top' : '0', 'left' : '0'});
								// Maybe we need to load some content into it first?
								if (optionsajax[ll])
								{
									// I have to load the Ajax-content into the target clone, and the target itself. 
									// First the target clone.
									ajaxLoad(0, ll, false, speed);
									// Then the target.
									if (imageCheck(optionsajax[ll])) // Weird bugs, weird fixes. But this works. 
									{
										ajaxLoad(ll+1, ll, false, speed);
										runOnImagesLoaded(li.eq(ll+1),function(){
											if (optionsautoheight) autoheight(ll, optionsfadespeed);
										});
									}
									else
									{
										ajaxLoad(ll+1, ll, true, speed);
									}
									optionsajax[ll] = false;
								}
								// Lets fade it in. 
								fadeIntarget.hide().fadeIn(optionsfadespeed, function() {
									// So the animate function knows what to do. 
									clickable = true;
									fading = true;
									animate(i,false,false); // Moving to the correct place.
									// Removing it again, if i dont, it will just be a pain in the ....
									$(this).remove();
									if(optionshistory && clicked) window.location.hash = optionsnumericText[t]; // It's just one line of code, no need to make a function of it. 
									// Lets put that variable back to the default (and not during animation) value. 
									fading = false;
									// Now run that after animation function.
									// We already got the target and the slider number from earlier.
									// So lets run the function.
									if ($.isFunction(optionsafterAniFunc)){optionsafterAniFunc.call(target, ll + 1);}
								});
							}
							else
							{
								// fadeOut and fadeIn.
								var fadeinspeed = parseInt((speed)*(3/5));
								var fadeoutspeed = speed - fadeinspeed;
								// I set the opacity to something higher than 0, because if it's 0, the content that i try to read (to make the autoheight work etc.) aint there.
								var noncrossfadetargets = li.children();
								noncrossfadetargets.stop().fadeTo(fadeoutspeed, 0.0001, function(){
									// So the animation function knows what to do. 
									clickable = true;
									fading = true;
									animate(i,false,false); // Moving to the correct place.
									// Only clickable if not clicked.
									clickable = !clicked; 
									// Now, lets fade the slider back in. 
									// Got no idea why the .add(li) is nesecary, but it is. (If it isn't there, the first slide never fades back in). 
									noncrossfadetargets.add(li).stop().fadeTo(fadeinspeed, 1, function(){
										if(optionshistory && clicked) window.location.hash = optionsnumericText[t]; // It's just one line of code, no need to make a function of it. 
										clickable = true;
										fading = false;
										// Now run that after animation function.
										// We already got the target and the slider number from earlier.
										// So lets run the function.
										if ($.isFunction(optionsafterAniFunc)){optionsafterAniFunc.call(target, ll + 1);}
									});
								});
							}
						}
					}
				};
				function animate(dir,clicked,time) // (Direction, did the user click something, is this to be done in >1ms?) 
				{
					if (clickable && !destroyed && (filterDir(dir, ot) != t || init))
					{
						ajaxloading = false;
						clickable = (!clicked && !optionsauto) ? true : options.clickableAni;
						// to the adjust function. 
						buttonclicked = clicked;
						ot = t;
						t = filterDir(dir, ot);
						if (optionsupdateBefore) setCurrent(t);
						// Calculating the speed to do the animation with. 
						var diff = Math.sqrt(Math.abs(ot-t));
						var speed = parseInt(diff*optionsspeed);
						if (!clicked && !optionsauto) speed = parseInt(diff*optionsspeedhistory); // Auto:true and history:true doens't work well together, and they ain't supposed to. 
						if (!time) speed = 0;
						
						// Ajax begins here 
						// I also these variables in the below code (running custom function).
						var i = t;
						if(t>ts) i=0;
						if(t<0) i=ts;
						if (optionsajax)
						{
							// Loading the target slide, if not already loaded. 
							if (optionsajax[i]) 
							{
								ajaxLoad(i, i, true, speed);
								optionsajax[i] = false; 
								ajaxloading = true;
							}
							// It can look stupid the script scroll over some not-loaded slides. Therefore, they are loaded. 
							// It can produce some heavy load, so the script wont do it, it it's more than 10 slides.
							if (!fading)
							{
								// I dont like copypasting the same code, but this is the most efficient way i can think of atm. 
								var countajax = 0;
								if (ot > t)
								{
									for (a = t; a <= ot; a++)
									{
										if (a<=ts && a>=0)
										{
											if (optionsajax[a]) 
											{
												ajaxLoad(a, a, false, speed);
												optionsajax[a] = false; 
												countajax++;
											}
										}
										if (countajax == 10) a = ot;
									}
								}
								else
								{
									for (a = ot; a <= t; a++)
									{
										if (a<=ts && a>=0)
										{
											if (optionsajax[a]) 
											{
												ajaxLoad(a, a, false, speed);
												optionsajax[a] = false; 
												countajax++;
											}
										}
										if (countajax == 10) a = t;
									}
								}
							}
							// Then we have to preload the next one. 
						 	if (i + 1 <= ts)
							{
								if (optionsajax[i + 1]) 
								{
									ajaxLoad(i + 1, i + 1, false, 0);
									optionsajax[i + 1] = false;
								}
							}
							// And the previous one. 
							if (i - 1 >= 0)
							{
								if (optionsajax[i - 1]) 
								{
									ajaxLoad(i - 1, i - 1, false, 0);
									optionsajax[i - 1] = false;
								}
							}
						}
						// Ajax ends here
						if (!fading)
						{
							// Lets run the before animation function.
							if ($.isFunction(optionsbeforeAniFunc)){
								optionsbeforeAniFunc.call(li.eq(i), i + 1);
								if (t == -1 || t == s) optionsbeforeAniFunc.call(ul.children("li").eq((t == -1) ? 0 : -1), i + 1);
							}
							
						}
						// Start animation. 
						if(!optionsvertical) {
							if (optionsautoheight && !fading && !ajaxloading) autoheight(t, speed);
							p = (t*w*-1);
							ul.animate(
								{ marginLeft: p},
								{
									queue:false,
									duration:speed,
									easing:optionsease,
									complete:adjust
								}
							);
						} else {
							p = (t*h*-1);
							ul.animate(
								{ marginTop: p },
								{
									queue:false,
									duration:speed,
									easing:optionsease,
									complete:adjust
								}
							);
						};
						// End animation. 
						
						// Fading the next/prev/last/first controls in/out if needed. 
						if(optionscontrolsFade)
						{
							var fadetime = optionscontrolsFadeSpeed;
							if (!clicked && !optionsauto) fadetime = (optionsspeedhistory / optionsspeed) * optionscontrolsFadeSpeed;					
							if (!time) fadetime = 0;
							if (fading) fadetime = parseInt((optionsfadespeed)*(3/5));
							fadeControls (t,fadetime);
						}
						
						// Stopping auto if clicked. 
						if(clicked) clearTimeout(timeout);
						// Continuing if not clicked.
						if(optionsauto && dir=="next" && !clicked){
							timeout = startAuto(optionspause + optionsspeed);
						};
						// Stop init, first animation is done. 
						init = false; //nasty workaround, but it works. 
					};
				};
				// init
				var timeout;
				
				// Starting auto. 
				if(optionsauto) timeout = startAuto(optionspause);
				function startAuto(pause)
				{
					return setTimeout(function(){
						goToSlide("next", false);
					},pause);
				}
				if (optionscustomLink) // customLinks. Easy to make, great to use. 
				{
					// Using live, that way javascript ajax-loaded buttons and javascript generated content will work.
					$(optionscustomLink).live('click', function() {
						var a = $(this).attr('rel');
						if (a) doExternalInput(a);
						return false;
					}); 
				}
				obj.bind('sudoSliderEvent', function(e, a) { 
					doExternalInput(a);
				});
				function doExternalInput(a)
				{
					// Check for special events
					if (a == 'stop') clearTimeout(timeout)
					else if (a == 'start')
					{
						timeout = startAuto(optionspause);
						optionsauto = true;
					}
					else if (a == 'block') clickable = false; // Simple, beautifull.
					else if (a == 'unblock') clickable = true; // -||-
					else if (a == 'action') alert('The slider just performed an action'); // Kind of an easter egg. When explaining how to use "actions", i use '$("#slider").sudoSlider("action");', so i thought that the useless example should actually do something. 
					else if (a == 'destroy') // For good. 
					{
						// First, i remove the controls. 
						controls.remove(); // that's it.
						// Now to set a variable, so nothing is run. 
						destroyed = true; // No animation, no fading, no clicking from now. 
						// Then remove the customLink bindings:
						$(optionscustomLink).die("click");
						// Now remove the "continuous clones". 
						if (optionscontinuous)
						{
							ul.children("li").eq(0).remove();
							ul.children("li").eq(-1).remove();
						}
					}
					// The general case. 
					// That means, typeof(a) == numbers and first,last,next,prev
					// I dont make any kind of input validation, meaning that it's quite easy to break the script with non-valid input. 
					else if (clickable) goToSlide((a == parseInt(a)) ? a - 1 : a, true);
				};
				
				
				// Lets make those bookmarks and back/forward buttons work. 
				if (optionshistory) {
					// Going to the correct slide at load. 
					$.address.init(function(e) {
						var i = filterUrlHash(e.value);
				 	 	animate(i,false,false);
					})
					// Sliding/fading to the correct slide, on url change. 
					.change(function(e) {
						var i = filterUrlHash(e.value);
						if (i != t) goToSlide(i, false);
					});
				}
				// The startSlide setting only require one line of code. And here it is:
	 			else if (optionsstartSlide) animate(optionsstartSlide - 1,false,false); 
				// doing it anyway. good way to fix bugs. 
				// And i only preload the next and previous slide after init (which this is). So i'm doing it. 
				// + if i didn't do this, a lot of things wouldn't happen on page load. By always animating, i ensure that everthing that's supposed to happen, do happen. 
				else animate(0,false,false); 
			});
		}
	};
})(jQuery);
