/*
 * jQuery Orbit Plugin 1.1 
 * www.ZURB.com/playground
 * Copyright 2010, ZURB
 * Free to use under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
*/


(function($) {

    $.fn.orbit = function(options) {

        //Yo' defaults
        var defaults = {  
            animation: 'fade', //fade, horizontal-slide, vertical-slide
            animationSpeed: 800, //how fast animtions are
            advanceSpeed: 4000, //if auto advance is enabled, time between transitions 
            startClockOnMouseOut: true, //if clock should start on MouseOut
            startClockOnMouseOutAfter: 3000, //how long after mouseout timer should start again
            directionalNav: true, //manual advancing directional navs
            captions: true, //do you want captions?
            captionAnimationSpeed: 800, //if so how quickly should they animate in
            timer: false, //true or false to have the timer
            bullets: false //true or false to activate the bullet navigation
            };  
        
        //Extend those options
        var options = $.extend(defaults, options); 
	
        return this.each(function() {
        
            //important global goodies
            var activeImage = 0;
            var numberImages = 0;
            var orbitWidth;
            var orbitHeight;
            var locked;
            
            //Grab each Shifter and add the class
            var orbit = $(this).addClass('orbit')
            
            //Collect all images and set slider size to biggest o' da images
            var images = orbit.find('img, a img');
            images.each(function() {
                var _img = $(this);
                var _imgWidth = _img.width();
                var _imgHeight = _img.height();
                orbit.width(_imgWidth);
                orbitWidth = orbit.width()
                orbit.height(_imgHeight)
                orbitHeight = orbit.height();
                numberImages++;
            });
            
            //set initial front photo z-index
            images.eq(activeImage).css({"z-index" : 3});
            
            //Timer info
            if(options.timer) {         	
                var timerHTML = '<div class="timer"><span class="mask"><span class="rotator"></span></span><span class="pause"></span></div>'
                orbit.append(timerHTML);
                var timer = $('div.timer')
                var timerRunning;
                if(timer.length != 0) {
                    var speed = (options.advanceSpeed)/180;
                    var rotator = $('div.timer span.rotator')
                    var mask = $('div.timer span.mask')
                    var pause = $('div.timer span.pause')
                    var degrees = 0;
                    var clock;
                    function startClock() {
                        timerRunning = true;
                        pause.removeClass('active')
                        clock = setInterval(function(e){
                            var degreeCSS = "rotate("+degrees+"deg)"
                            degrees += 2
                            rotator.css({ 
                                "-webkit-transform": degreeCSS,
                                "-moz-transform": degreeCSS,
                                "-o-transform": degreeCSS
                            })
                            if(degrees > 180) {
                                rotator.addClass('move')
                                mask.addClass('move')
                            }
                            if(degrees > 360) {
                                rotator.removeClass('move')
                                mask.removeClass('move')
                                degrees = 0;
                                shift("next")
                            }
                        }, speed);
                    };  
                    function stopClock() {
                        timerRunning = false;
                        clearInterval(clock)
                        pause.addClass('active')
                    }   
                    startClock();
                    timer.click(function() {
                        if(!timerRunning) {
                            startClock();
                        } else { 
                            stopClock();
                        }
                    })
                    if(options.startClockOnMouseOut){
                        var outTimer;
                        orbit.mouseleave(function() {
                            outTimer = setTimeout(function() {
                                if(!timerRunning){
                                    startClock();
                                }
                            }, options.startClockOnMouseOutAfter)
                        })
                        orbit.mouseenter(function() {
                            clearTimeout(outTimer);
                        })
                    }
                }
            }           
            //animation locking functions
            function unlock() {
                locked = false;
            }
            function lock() { 
                locked = true;
            }
            
            //CaptionComputing
            if(options.captions) {
                var captionHTML = '<div class="caption"><span class="orbit-caption"></span></div>';
                orbit.append(captionHTML);
                var caption = orbit
                	.children('div.caption')
                	.children('span')
                	.addClass('orbit-caption')
                	.show();
                	
                function setCaption() {
                	var _captionLocation = images.eq(activeImage).attr('rel'); //get ID from rel tag on image 
                    var _captionHTML = $("#"+_captionLocation).html(); //get HTML from the matching HTML entity
                    var _captionHeight = caption.height() + 20; //set height of the caption
                             	
                	caption
                		.attr('id',"#"+_captionLocation) // Add ID caption
                    	.html(_captionHTML); // Change HTML in Caption 
                    
                    if(!_captionHTML) {
                        caption.parent().stop().animate({"bottom" : -_captionHeight}, options.captionAnimationSpeed);
                    } else { 
                        caption.parent().stop().animate({"bottom" : 0}, options.captionAnimationSpeed);
                    }
                }
            	setCaption();
            }
            

            //DirectionalNav { rightButton --> shift("next"), leftButton --> shift("prev");
            if(options.directionalNav) {
                var directionalNavHTML = '<div class="slider-nav"><span class="right">Right</span><span class="left">Left</span></div>';
                orbit.append(directionalNavHTML);
                var leftBtn = orbit.children('div.slider-nav').children('span.left');
                var rightBtn = orbit.children('div.slider-nav').children('span.right');
                leftBtn.click(function() { 
                    if(options.timer) { stopClock(); }
                    shift("prev");
                });
                rightBtn.click(function() {
                    if(options.timer) { stopClock(); }
                    shift("next")
                });
            }
            
            //BulletControls
            if(options.bullets) { 
            	var bulletHTML = '<ul class="orbit-bullets"></ul>';            	
            	orbit.append(bulletHTML);
            	var bullets = $('ul.orbit-bullets');
            	for( i=0; i<numberImages; i++) {
            		var liMarkup = $('<li>'+i+'</li>') 
            		$('ul.orbit-bullets').append(liMarkup);
            		liMarkup.data('index',i);
            		liMarkup.click(function() {
            			if(options.timer) { stopClock(); }
            			shift($(this).data('index'));
            		});
            	}
            	function setActiveBullet() { 
            		bullets.children('li').removeClass('active').eq(activeImage).addClass('active')
            	}
            	setActiveBullet();
            }
            
            //Animating the shift!
            function shift(direction) {
        	    //remember previous activeImg
                var prevActiveImage = activeImage;
                var slideDirection = direction;
                //exit function if bullet clicked is same as the current image
                if(prevActiveImage == slideDirection) { return false; }
                //reset Z & Unlock
                function resetAndUnlock() {
                    images.eq(prevActiveImage).css({"z-index" : 1});
                    unlock();
                }
                if(!locked) {
                    lock();
					 //deduce the proper activeImage
                    if(direction == "next") {
                        activeImage++
                        if(activeImage == numberImages) {
                            activeImage = 0;
                        }
                    } else if(direction == "prev") {
                        activeImage--
                        if(activeImage < 0) {
                            activeImage = numberImages-1;
                        }
                    } else {
                        activeImage = direction;
                        if (prevActiveImage < activeImage) { 
                            slideDirection = "next";
                        } else if (prevActiveImage > activeImage) { 
                            slideDirection = "prev"
                        }
                    }
                    //set to correct bullet
                     if(options.bullets) { setActiveBullet(); }              
                    
                    //fade
                    if(options.animation == "fade") {
                        images.eq(prevActiveImage).css({"z-index" : 2});
                        images.eq(activeImage).css({"opacity" : 0, "z-index" : 3})
                        .animate({"opacity" : 1}, options.animationSpeed, resetAndUnlock);
                        if(options.captions) { setCaption(); }
                    }
                    //horizontal-slide
                    if(options.animation == "horizontal-slide") {
                        images.eq(prevActiveImage).css({"z-index" : 2});
                        if(slideDirection == "next") {
                            images.eq(activeImage).css({"left": orbitWidth, "z-index" : 3})
                            .animate({"left" : 0}, options.animationSpeed, resetAndUnlock);
                        }
                        if(slideDirection == "prev") {
                            images.eq(activeImage).css({"left": -orbitWidth, "z-index" : 3})
                            .animate({"left" : 0}, options.animationSpeed, resetAndUnlock);
                        }
                        if(options.captions) { setCaption(); }
                    }
                    //vertical-slide
                    if(options.animation == "vertical-slide") { 
                        images.eq(prevActiveImage).css({"z-index" : 2});
                        if(slideDirection == "prev") {
                            images.eq(activeImage).css({"top": orbitHeight, "z-index" : 3})
                            .animate({"top" : 0}, options.animationSpeed, resetAndUnlock);
                        }
                        if(slideDirection == "next") {
                            images.eq(activeImage).css({"top": -orbitHeight, "z-index" : 3})
                            .animate({"top" : 0}, options.animationSpeed, resetAndUnlock);
                        }
                        if(options.captions) { setCaption(); }
                    }
                } //lock
            }//orbit function
        });//each call
    }//orbit plugin call
})(jQuery);
        