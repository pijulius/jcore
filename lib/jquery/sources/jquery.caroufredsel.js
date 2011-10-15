/*	
 *	jQuery carouFredSel 5.0.7
 *	Demo's and documentation:
 *	caroufredsel.frebsite.nl
 *	
 *	Copyright (c) 2011 Fred Heusschen
 *	www.frebsite.nl
 *
 *	Dual licensed under the MIT and GPL licenses.
 *	http://en.wikipedia.org/wiki/MIT_License
 *	http://en.wikipedia.org/wiki/GNU_General_Public_License
 */


(function($) {



	//	LOCAL

	if ($.fn.carouFredSel) return;

	$.fn.carouFredSel = function(options, configs) {
		if (this.length == 0) {
			debug(true, 'No element found for "'+this.selector+'".');
			return this;
		}
		if (this.length > 1) {
			return this.each(function() {
				$(this).carouFredSel(options, configs);
			});
		}

		var $cfs = this,
			$tt0 = this[0];

		$cfs.init = function(o, setOrig, start) {
			o = go_getObject($tt0, o);


			//	DEPRECATED
			if (o.debug) {
				conf.debug = o.debug;
				debug(conf, 'The "debug" option should be moved to the second configuration-object.');
			}
			//	/DEPRECATED


			var obs = ['items', 'scroll', 'auto', 'prev', 'next', 'pagination'];
			for (var a = 0, l = obs.length; a < l; a++) {
				o[obs[a]] = go_getObject($tt0, o[obs[a]]);
			}
			if (typeof o.scroll == 'number') {
				if (o.scroll <= 50)					o.scroll	= { 'items'		: o.scroll 	};
				else								o.scroll	= { 'duration'	: o.scroll 	};
			} else {
				if (typeof o.scroll == 'string')	o.scroll	= { 'easing'	: o.scroll 	};
			}
				 if (typeof o.items == 'number')	o.items		= { 'visible'	: o.items 	};
			else if (		o.items == 'variable')	o.items		= { 'visible'	: o.items,
																	'width'		: o.items, 
																	'height'	: o.items	};

			if (setOrig) opts_orig = $.extend(true, {}, $.fn.carouFredSel.defaults, o);

			opts = $.extend(true, {}, $.fn.carouFredSel.defaults, o);
			opts.d = {};
			opts.variableVisible = false;
			opts.visibleAdjust = false;

			if (opts.items.start == 0 && typeof start == 'number') {
				opts.items.start = start;
			}

			crsl.direction = (opts.direction == 'up' || opts.direction == 'left') ? 'next' : 'prev';

			var dims = [
				['width'	, 'innerWidth'	, 'outerWidth'	, 'height'	, 'innerHeight'	, 'outerHeight'	, 'left', 'top'	, 'marginRight'	, 0, 1, 2, 3],
				['height'	, 'innerHeight'	, 'outerHeight'	, 'width'	, 'innerWidth'	, 'outerWidth'	, 'top'	, 'left', 'marginBottom', 3, 2, 1, 0]
			];
			var dn = dims[0].length,
				dx = (opts.direction == 'right' || opts.direction == 'left') ? 0 : 1;

			for (var d = 0; d < dn; d++) {
				opts.d[dims[0][d]] = dims[dx][d];
			}

			var	all_itm = $cfs.children(),
				lrgst_b = ms_getTrueLargestSize(all_itm, opts, 'outerHeight', false);

			//	secondairy size set to auto -> measure largest size and set it
			if (opts[opts.d['height']] == 'auto') {
				opts[opts.d['height']] = lrgst_b;
				opts.items[opts.d['height']] = lrgst_b;
			}

			//	primairy item-size not set -> measure it or set to "variable"
			if (!opts.items[opts.d['width']]) {
				opts.items[opts.d['width']] = (ms_hasVariableSizes(all_itm, opts, 'outerWidth')) 
					? 'variable' 
					: all_itm[opts.d['outerWidth']](true);
			}

			//	secondairy item-size not set -> measure it or set to "variable"
			if (!opts.items[opts.d['height']]) {
				opts.items[opts.d['height']] = (ms_hasVariableSizes(all_itm, opts, 'outerHeight')) 
					? 'variable' 
					: all_itm[opts.d['outerHeight']](true);
			}

			//	secondairy size not set -> set to secondairy item-size
			if (!opts[opts.d['height']]) {
				opts[opts.d['height']] = opts.items[opts.d['height']];
			}

			switch (opts.items.visible) {
				case '+1':
				case '-1':
				case 'odd':
				case 'odd+':
				case 'even':
				case 'even+':
					opts.visibleAdjust = opts.items.visible;
					opts.items.visible = false;
					break;
			}

			//	visible-items not set
			if (!opts.items.visible) {
				//	primairy item-size variable -> set visible items variable
				if (opts.items[opts.d['width']] == 'variable') {
					opts.items.visible = 'variable';
				} else {
					//	primairy size is number -> calculate visible-items
					if (typeof opts[opts.d['width']] == 'number') {
						opts.items.visible = Math.floor(opts[opts.d['width']] / opts.items[opts.d['width']]);
					} else {
						//	measure and calculate primairy size and visible-items
						var maxS = ms_getTrueInnerSize($wrp.parent(), opts, 'innerWidth');
						opts.items.visible = Math.floor(maxS / opts.items[opts.d['width']]);
						opts[opts.d['width']] = opts.items.visible * opts.items[opts.d['width']];
						if (!opts.visibleAdjust) opts.align = false;
					}
					if (opts.items.visible == 'Infinity' ||	opts.items.visible < 0) {
						debug(true, 'Not a valid number of visible items: Set to "1".');
						opts.items.visible = 1;
					}
				}
			}

			//	primairy size not set -> calculate it or set to "variable"
			if (!opts[opts.d['width']]) {
				if (opts.items.visible != 'variable' && opts.items[opts.d['width']] != 'variable') {
					opts[opts.d['width']] = opts.items.visible * opts.items[opts.d['width']];
					opts.align = false;
				} else {
					opts[opts.d['width']] = 'variable';
				}
			}

			//	variable primairy item-sizes with variabe visible-items
			if (opts.items.visible == 'variable') {
				opts.variableVisible = true;
				opts.maxDimention = (opts[opts.d['width']] == 'variable')
					? ms_getTrueInnerSize($wrp.parent(), opts, 'innerWidth')
					: opts[opts.d['width']];
				if (opts.align === false) {
					opts[opts.d['width']] = 'variable';
				}
				opts.items.visible = gn_getVisibleItemsNext(all_itm, opts, 0);
				if (opts.items.visible > itms.total) {
					opts.items.visible = itms.total;
				}
			}

			if (typeof opts.padding == 'undefined') {
				opts.padding = 0;
			}

			//	align not set -> set to center if primairy size is number
			if (typeof opts.align == 'undefined') {
				opts.align = (opts[opts.d['width']] == 'variable')
					? false
					: 'center';
			}

			opts.items.visible = cf_getVisibleItemsAdjust(opts.items.visible, opts);
			opts.items.oldVisible = opts.items.visible;
			opts.usePadding = false;
			opts.padding = cf_getPadding(opts.padding);

			if (opts.align == 'top') 		opts.align = 'left';
			if (opts.align == 'bottom') 	opts.align = 'right';

			switch (opts.align) {
				//	align: center, left or right
				case 'center':
				case 'left':
				case 'right':
					if (opts[opts.d['width']] != 'variable') {
						var p = cf_getAlignPadding(gi_getCurrentItems(all_itm, opts), opts);
						opts.usePadding = true;
						opts.padding[opts.d[1]] = p[1];
						opts.padding[opts.d[3]] = p[0];
					}
					break;

				//	padding
				default:
					opts.align = false;
					opts.usePadding = (
						opts.padding[0] == 0 && 
						opts.padding[1] == 0 && 
						opts.padding[2] == 0 && 
						opts.padding[3] == 0
					) ? false : true;
					break;
			}

			if (typeof opts.cookie == 'boolean' && opts.cookie)			opts.cookie 					= 'caroufredsel_cookie_'+$cfs.attr('id');
			if (typeof opts.items.minimum				!= 'number')	opts.items.minimum				= opts.items.visible;
			if (typeof opts.scroll.items				!= 'number')	opts.scroll.items				= (opts.variableVisible) ? 'variable' : opts.items.visible;
			if (typeof opts.scroll.duration				!= 'number')	opts.scroll.duration			= 500;

			opts.auto		= go_getNaviObject($tt0, opts.auto, 'auto');
			opts.prev		= go_getNaviObject($tt0, opts.prev);
			opts.next		= go_getNaviObject($tt0, opts.next);
			opts.pagination	= go_getNaviObject($tt0, opts.pagination, 'pagination');

			opts.auto		= $.extend(true, {}, opts.scroll, opts.auto);
			opts.prev		= $.extend(true, {}, opts.scroll, opts.prev);
			opts.next		= $.extend(true, {}, opts.scroll, opts.next);
			opts.pagination	= $.extend(true, {}, opts.scroll, opts.pagination);

			if (typeof opts.pagination.keys				!= 'boolean')	opts.pagination.keys 			= false;
			if (typeof opts.pagination.anchorBuilder	!= 'function')	opts.pagination.anchorBuilder	= $.fn.carouFredSel.pageAnchorBuilder;
			if (typeof opts.auto.play					!= 'boolean')	opts.auto.play					= true;
			if (typeof opts.auto.delay					!= 'number')	opts.auto.delay					= 0;
			if (typeof opts.auto.pauseDuration			!= 'number')	opts.auto.pauseDuration			= (opts.auto.duration < 10) ? 2500 : opts.auto.duration * 5;

			opts.auto.pauseOnHover = (opts.auto.pauseOnHover) ? opts.auto.pauseOnHover.toString() : '';

			if (opts.synchronise) {
				opts.synchronise = cf_getSynchArr(opts.synchronise);
			}
			if (conf.debug) {
				debug(conf, 'Carousel width: '+opts.width);
				debug(conf, 'Carousel height: '+opts.height);
				if (opts[opts.d['width']] == 'variable') debug(conf, 'Available '+opts.d['width']+': '+opts.maxDimention);
				debug(conf, 'Item widths: '+opts.items.width);
				debug(conf, 'Item heights: '+opts.items.height);
				debug(conf, 'Number of items visible: '+opts.items.visible);
				if (opts.auto.play)		debug(conf, 'Number of items scrolled automatically: '+opts.auto.items);
				if (opts.prev.button)	debug(conf, 'Number of items scrolled backward: '+opts.prev.items);
				if (opts.next.button)	debug(conf, 'Number of items scrolled forward: '+opts.next.items);
			}
		};	//	/init

		$cfs.build = function() {
			$cfs.data('cfs_isCarousel', true);

			if ($cfs.css('position') == 'absolute' || $cfs.css('position') == 'fixed') {
				debug(conf, 'Carousels CSS-attribute "position" should be "static" or "relative".');
			}

			var orgCSS = {
				'float'			: $cfs.css('float'),
				'position'		: $cfs.css('position'),
				'top'			: $cfs.css('top'),
				'right'			: $cfs.css('right'),
				'bottom'		: $cfs.css('bottom'),
				'left'			: $cfs.css('left'),
				'width'			: $cfs.css('width'),
				'height'		: $cfs.css('height'),
				'marginTop'		: $cfs.css('marginTop'),
				'marginRight'	: $cfs.css('marginRight'),
				'marginBottom'	: $cfs.css('marginBottom'),
				'marginLeft'	: $cfs.css('marginLeft')
			};

			$wrp.css(orgCSS).css({
				'overflow'		: 'hidden',
				'position'		: (orgCSS.position == 'absolute') ? 'absolute' : 'relative'
			});

			$cfs.data('cfs_origCss', orgCSS).css({
				'float'			: 'none',
				'position'		: 'absolute',
				'top'			: 0,
				'left'			: 0,
				'marginTop'		: 0,
				'marginRight'	: 0,
				'marginBottom'	: 0,
				'marginLeft'	: 0
			});

			if (opts.usePadding) {
				$cfs.children().each(function() {
					var m = parseInt($(this).css(opts.d['marginRight']));
					if (isNaN(m)) m = 0;
					$(this).data('cfs_origCssMargin', m);
				});
			}
		};	//	/build

		$cfs.bind_events = function() {
			$cfs.unbind_events();

			//	stop event
			$cfs.bind('stop.cfs'+serial, function(e, imm) {
				e.stopPropagation();
				crsl.isStopped = true;
				if (opts.auto.play) {
					opts.auto.play = false;
					$cfs.trigger('pause', imm);
				}
			});

			//	finish event
			$cfs.bind('finish.cfs'+serial, function(e) {
				e.stopPropagation();
				if (crsl.isScrolling) {
					sc_stopScroll(scrl);
				}
			});

			//	pause event
			$cfs.bind('pause.cfs'+serial, function(e, imm, res) {
				e.stopPropagation();
				tmrs = sc_clearTimers(tmrs);

				//	immediately pause
				if (imm && crsl.isScrolling) {
					scrl.isStopped = true;
					var nst = getTime() - scrl.startTime;
					scrl.duration -= nst;
					if (scrl.pre) scrl.pre.duration -= nst;
					if (scrl.post) scrl.post.duration -= nst;
					sc_stopScroll(scrl, false);
				}

				//	update remaining pause-time
				if (!crsl.isPaused && !crsl.isScrolling) {
					if (res) tmrs.timePassed += getTime() - tmrs.startTime;
				}
				crsl.isPaused = true;

				//	pause pause callback
				if (opts.auto.onPausePause) {
					var dur1 = opts.auto.pauseDuration - tmrs.timePassed,
						perc = 100 - Math.ceil( dur1 * 100 / opts.auto.pauseDuration );
					opts.auto.onPausePause.call($tt0, perc, dur1);
				}
			});

			//	play event
			$cfs.bind('play.cfs'+serial, function(e, dir, del, res) {
				e.stopPropagation();
				tmrs = sc_clearTimers(tmrs);

				//	sort params
				var v = [dir, del, res],
					t = ['string', 'number', 'boolean'],
					a = cf_sortParams(v, t);

				var dir = a[0],
					del = a[1],
					res = a[2];

				if (dir != 'prev' && dir != 'next') dir = crsl.direction;
				if (typeof del != 'number') 		del = 0;
				if (typeof res != 'boolean') 		res = false;

				//	stopped?
				if (res) {
					crsl.isStopped = false;
					opts.auto.play = true;
				}
				if (!opts.auto.play) {
					e.stopImmediatePropagation();
					return debug(conf, 'Carousel stopped: Not scrolling.');
				}

				//	set playing
				crsl.isPaused = false;
				tmrs.startTime = getTime();

				//	timeout the scrolling
				var dur1 = opts.auto.pauseDuration + del;
					dur2 = dur1 - tmrs.timePassed;
					perc = 100 - Math.ceil(dur2 * 100 / dur1);

				tmrs.auto = setTimeout(function() {
					if (opts.auto.onPauseEnd) {
						opts.auto.onPauseEnd.call($tt0, perc, dur2);
					}
					if (crsl.isScrolling) {
						$cfs.trigger('play', dir);
					} else {
						$cfs.trigger(dir, opts.auto);
					}
				}, dur2);

				//	pause start callback
				if (opts.auto.onPauseStart) {
					opts.auto.onPauseStart.call($tt0, perc, dur2);
				}
			});

			//	resume event
			$cfs.bind('resume.cfs'+serial, function(e) {
				e.stopPropagation();
				if (scrl.isStopped) {
					scrl.isStopped = false;
					crsl.isPaused = false;
					crsl.isScrolling = true;
					scrl.startTime = getTime();
					sc_startScroll(scrl);
				} else {
					$cfs.trigger('play');
				}
			});

			//	prev + next events
			$cfs.bind('prev.cfs'+serial+' next.cfs'+serial, function(e, obj, num, clb) {
				e.stopPropagation();

				//	stopped or hidden carousel, don't scroll, don't queue
				if (crsl.isStopped || $cfs.is(':hidden')) {
					e.stopImmediatePropagation();
					return debug(conf, 'Carousel stopped or hidden: Not scrolling.');
				}

				//	get config
				var v = [obj, num, clb],
					t = ['object', 'number/string', 'function'],
					a = cf_sortParams(v, t);

				var obj = a[0],
					num = a[1],
					clb = a[2];

				if (typeof obj != 'object' || obj == null)			obj = opts[e.type];
				if (typeof clb == 'function')						obj.onAfter = clb;
				
				if (typeof num != 'number') {
					if (num == 'visible') {
						if (!opts.variableVisible)						num = opts.items.visible;
					} else {
							 if (typeof obj.items == 'number') 			num = obj.items;
						else if (typeof opts[e.type].items == 'number')	num = opts[e.type].items;
						else if (opts.variableVisible)					num = 'visible';
						else											num = opts.items.visible;
					}
				}

				//	resume animation, add current to queue
				if (scrl.isStopped) {
					$cfs.trigger('resume');
					$cfs.trigger('queue', [e.type, [obj, num, clb]]);
					e.stopImmediatePropagation();
					return debug(conf, 'Carousel resumed scrolling.');
				}

				//	not enough items
				if (opts.items.minimum >= itms.total) {
					e.stopImmediatePropagation();
					return debug(conf, 'Not enough items ('+itms.total+', '+opts.items.minimum+' needed): Not scrolling.');
				}

				//	queue if scrolling
				if (obj.duration > 0) {
					if (crsl.isScrolling) {
						if (obj.queue) $cfs.trigger('queue', [e.type, [obj, num, clb]]);
						e.stopImmediatePropagation();
						return debug(conf, 'Carousel currently scrolling.');
					}
				}

				//	test conditions callback
				if (obj.conditions && !obj.conditions.call($tt0)) {
					e.stopImmediatePropagation();
					return debug(conf, 'Callback "conditions" returned false.');
				}

				tmrs.timePassed = 0;
				$cfs.trigger('slide_'+e.type, [obj, num]);

				//	synchronise
				if (opts.synchronise) {
					var s = opts.synchronise,
						c = [obj, num];
					for (var j = 0, l = s.length; j < l; j++) {
						var d = e.type;
						if (!s[j][1]) c[0] = s[j][0].triggerHandler('configuration', e.type);
						if (!s[j][2]) d = (d == 'prev') ? 'next' : 'prev';
						c[1] = num + s[j][3];
						s[j][0].trigger('slide_'+d, c);
					}
				}
			});

			//	prev event
			$cfs.bind('slide_prev.cfs'+serial, function(e, sO, nI) {
				e.stopPropagation();
				var a_itm = $cfs.children();

				//	non-circular at start, scroll to end
				if (!opts.circular) {
					if (itms.first == 0) {
						if (opts.infinite) {
							$cfs.trigger('next', itms.total-1);
						}
						return e.stopImmediatePropagation();
					}
				}

				if (opts.usePadding) sz_resetMargin(a_itm, opts);

				//	find number of items to scroll
				if (opts.variableVisible) {
					if (typeof nI != 'number') {
						nI = gn_getVisibleItemsPrev(a_itm, opts, itms.total-1);
					}
				}

				//	prevent non-circular from scrolling to far
				if (!opts.circular) {
					if (itms.total - nI < itms.first) {
						nI = itms.total - itms.first;
					}
				}

				//	set new number of visible items
				if (opts.variableVisible) {
					var vI = gn_getVisibleItemsNext(a_itm, opts, itms.total-nI);
					opts.items.oldVisible = opts.items.visible;
					opts.items.visible = cf_getVisibleItemsAdjust(vI, opts);
				}

				if (opts.usePadding) sz_resetMargin(a_itm, opts, true);

				//	scroll 0, don't scroll
				if (nI == 0) {
					e.stopImmediatePropagation();
					return debug(conf, '0 items to scroll: Not scrolling.');
				}
				debug(conf, 'Scrolling '+nI+' items backward.');

				//	save new config
				itms.first += nI;
				while (itms.first >= itms.total) itms.first -= itms.total;

				//	non-circular callback
				if (!opts.circular) {
					if (itms.first == 0 && sO.onEnd) sO.onEnd.call($tt0);
					if (!opts.infinite) nv_enableNavi(opts, itms.first);
				}

				//	rearrange items
				$cfs.children().slice(itms.total-nI).prependTo($cfs);
				if (itms.total < opts.items.visible + nI) {
					$cfs.children().slice(0, (opts.items.visible+nI)-itms.total).clone(true).appendTo($cfs);
				}

				//	the needed items
				var a_itm = $cfs.children(),
					c_old = gi_getOldItemsPrev(a_itm, opts, nI),
					c_new = gi_getNewItemsPrev(a_itm, opts),
					l_cur = a_itm.eq(nI-1),
					l_old = c_old.last(),
					l_new = c_new.last();

				if (opts.usePadding) sz_resetMargin(a_itm, opts);
				if (opts.align) var p = cf_getAlignPadding(c_new, opts);

				//	hide items for fx directscroll
				if (sO.fx == 'directscroll' && opts.items.oldVisible < nI) {
					var hiddenitems = a_itm.slice(opts.items.oldVisible, nI).hide(),
						orgW = opts.items[opts.d['width']];
					opts.items[opts.d['width']] = 'variable';
				} else {
					var hiddenitems = false;
				}

				//	save new sizes
				var i_siz = ms_getTotalSize(a_itm.slice(0, nI), opts, 'width'),
					w_siz = cf_mapWrapperSizes(ms_getSizes(c_new, opts, true), opts, !opts.usePadding);

				if (hiddenitems) opts.items[opts.d['width']] = orgW;

				if (opts.usePadding) {
					sz_resetMargin(a_itm, opts, true);
					sz_resetMargin(l_old, opts, opts.padding[opts.d[1]]);
					sz_resetMargin(l_cur, opts, opts.padding[opts.d[3]]);
				}
				if (opts.align) {
					opts.padding[opts.d[1]] = p[1];
					opts.padding[opts.d[3]] = p[0];
				}

				//	animation configuration
				var a_cfs = {},
					a_new = {},
					a_cur = {},
					a_old = {},
					a_dur = sO.duration;

					 if (sO.fx == 'none')	a_dur = 0;
				else if (a_dur == 'auto')	a_dur = opts.scroll.duration / opts.scroll.items * nI;
				else if (a_dur <= 0)		a_dur = 0;
				else if (a_dur < 10)		a_dur = i_siz / a_dur;

				scrl = sc_setScroll(a_dur, sO.easing);

				//	animate wrapper
				if (opts[opts.d['width']] == 'variable' || opts[opts.d['height']] == 'variable') {
					scrl.anims.push([$wrp, w_siz]);
				}

				//	animate items
				if (opts.usePadding) {
					var new_m = opts.padding[opts.d[3]];
					a_cur[opts.d['marginRight']] = l_cur.data('cfs_origCssMargin');
					a_new[opts.d['marginRight']] = l_new.data('cfs_origCssMargin') + opts.padding[opts.d[1]];
					a_old[opts.d['marginRight']] = l_old.data('cfs_origCssMargin');

					if (l_new.not(l_cur).length) {
						scrl.anims.push([l_cur, a_cur]);
					}
					scrl.anims.push([l_new, a_new]);
					scrl.anims.push([l_old, a_old]);
				} else {
					var new_m = 0;
				}

				//	animate carousel
				a_cfs[opts.d['left']] = new_m;

				//	onBefore callback
				var args = [c_old, c_new, w_siz, a_dur];
				if (sO.onBefore) sO.onBefore.apply($tt0, args);
				clbk.onBefore = sc_callCallbacks(clbk.onBefore, $tt0, args);



				//	ALTERNATIVE EFFECTS

				//	extra animation arrays
				switch(sO.fx) {
					case 'fade':
					case 'crossfade':
					case 'cover':
					case 'uncover':
						scrl.pre = sc_setScroll(scrl.duration, scrl.easing);
						scrl.post = sc_setScroll(scrl.duration, scrl.easing);
						scrl.duration = 0;
						break;
				}

				//	create copy
				switch(sO.fx) {
					case 'crossfade':
					case 'cover':
					case 'uncover':
						var $cf2 = $cfs.clone().appendTo($wrp);
						break;
				}
				switch(sO.fx) {
					case 'uncover':
						$cf2.children().slice(0, nI).remove();
					case 'crossfade':
					case 'cover':
						$cf2.children().slice(opts.items.visible).remove();
						break;
				}

				//	animations
				switch(sO.fx) {
					case 'fade':
						scrl.pre.anims.push([$cfs, { 'opacity': 0 }]);
						break;
					case 'crossfade':
						$cf2.css({ 'opacity': 0 });
						scrl.pre.anims.push([$cfs, { 'width': '+=0' }, function() { $cf2.remove(); }]);
						scrl.post.anims.push([$cf2, { 'opacity': 1 }]);
						break;
					case 'cover':
						scrl = fx_cover(scrl, $cfs, $cf2, opts, true);
						break;
					case 'uncover':
						scrl = fx_uncover(scrl, $cfs, $cf2, opts, true, nI);
						break;
				}

				//	/ALTERNATIVE EFFECTS


				//	complete callback
				var a_complete = function() {

					var overFill = opts.items.visible+nI-itms.total;
					if (overFill > 0) {
						$cfs.children().slice(itms.total).remove();
						c_old = $cfs.children().slice(itms.total-(nI-overFill)).get().concat( $cfs.children().slice(0, overFill).get() );
					}
					if (hiddenitems) hiddenitems.show();
					if (opts.usePadding) {
						var l_itm = $cfs.children().eq(opts.items.visible+nI-1);
						l_itm.css(opts.d['marginRight'], l_itm.data('cfs_origCssMargin'));
					}

					scrl.anims = [];
					if (scrl.pre) scrl.pre = sc_setScroll(scrl.orgDuration, scrl.easing);

					var fn = function() {
						switch(sO.fx) {
							case 'fade':
							case 'crossfade':
								$cfs.css('filter', '');
								break;
						}

						scrl.post = sc_setScroll(0, null);
						crsl.isScrolling = false;

						var args = [c_old, c_new, w_siz];
						if (sO.onAfter) sO.onAfter.apply($tt0, args);
						clbk.onAfter = sc_callCallbacks(clbk.onAfter, $tt0, args);

						if (queu.length) {
							$cfs.trigger(queu[0][0], queu[0][1]);
							queu.shift();
						}
						if (!crsl.isPaused) $cfs.trigger('play');
					};
					switch(sO.fx) {
						case 'fade':
							scrl.pre.anims.push([$cfs, { 'opacity': 1 }, fn]);
							sc_startScroll(scrl.pre);
							break;
						case 'uncover':
							scrl.pre.anims.push([$cfs, { 'width': '+=0' }, fn]);
							sc_startScroll(scrl.pre);
							break;
						default:
							fn();
							break;
					}
				};

				scrl.anims.push([$cfs, a_cfs, a_complete]);
				crsl.isScrolling = true;
				$cfs.css(opts.d['left'], -i_siz);
				tmrs = sc_clearTimers(tmrs);
				sc_startScroll(scrl);
				cf_setCookie(opts.cookie, $cfs.triggerHandler('currentPosition'));

				$cfs.trigger('updatePageStatus', [false, w_siz]);
			});

			//	next event
			$cfs.bind('slide_next.cfs'+serial, function(e, sO, nI) {
				e.stopPropagation();
				var a_itm = $cfs.children();

				//	non-circular at end, scroll to start
				if (!opts.circular) {
					if (itms.first == opts.items.visible) {
						if (opts.infinite) {
							$cfs.trigger('prev', itms.total-1);
						}
						return e.stopImmediatePropagation();
					}
				}

				if (opts.usePadding) sz_resetMargin(a_itm, opts);

				//	find number of items to scroll
				if (opts.variableVisible) {
					if (typeof nI != 'number') {
						nI = opts.items.visible;
					}
				}

				var lastItemNr = (itms.first == 0) ? itms.total : itms.first;

				//	prevent non-circular from scrolling to far
				if (!opts.circular) {
					if (opts.variableVisible) {
						var vI = gn_getVisibleItemsNext(a_itm, opts, nI),
							xI = gn_getVisibleItemsPrev(a_itm, opts, lastItemNr-1);
					} else {
						var vI = opts.items.visible,
							xI = opts.items.visible;
					}

					if (nI + vI > lastItemNr) {
						nI = lastItemNr - xI;
					}
				}

				//	set new number of visible items
				if (opts.variableVisible) {
					var vI = gn_getVisibleItemsNextTestCircular(a_itm, opts, nI, lastItemNr);
					while (opts.items.visible - nI >= vI && nI < itms.total) {
						nI++;
						vI = gn_getVisibleItemsNextTestCircular(a_itm, opts, nI, lastItemNr);
					}
					opts.items.oldVisible = opts.items.visible;
					opts.items.visible = cf_getVisibleItemsAdjust(vI, opts);
				}

				if (opts.usePadding) sz_resetMargin(a_itm, opts, true);

				//	scroll 0, don't scroll
				if (nI == 0) {
					e.stopImmediatePropagation();
					return debug(conf, '0 items to scroll: Not scrolling.');
				}
				debug(conf, 'Scrolling '+nI+' items forward.');

				//	save new config
				itms.first -= nI;
				while (itms.first < 0) itms.first += itms.total;

				//	non-circular callback
				if (!opts.circular) {
					if (itms.first == opts.items.visible && sO.onEnd) sO.onEnd.call($tt0);
					if (!opts.infinite) nv_enableNavi(opts, itms.first);
				}

				//	rearrange items
				if (itms.total < opts.items.visible + nI) {
					$cfs.children().slice(0, (opts.items.visible+nI)-itms.total).clone(true).appendTo($cfs);
				}

				//	the needed items
				var a_itm = $cfs.children(),
					c_old = gi_getOldItemsNext(a_itm, opts),
					c_new = gi_getNewItemsNext(a_itm, opts, nI),
					l_cur = a_itm.eq(nI-1),
					l_old = c_old.last(),
					l_new = c_new.last();

				if (opts.usePadding) sz_resetMargin(a_itm, opts);
				if (opts.align) var p = cf_getAlignPadding(c_new, opts);

				//	hide items for fx directscroll
				if (sO.fx == 'directscroll' && opts.items.oldVisible < nI) {
					var hiddenitems = a_itm.slice(opts.items.oldVisible, nI).hide(),
						orgW = opts.items[opts.d['width']];
					opts.items[opts.d['width']] = 'variable';
				} else {
					var hiddenitems = false;
				}

				//	save new sizes
				var i_siz = ms_getTotalSize(a_itm.slice(0, nI), opts, 'width'),
					w_siz = cf_mapWrapperSizes(ms_getSizes(c_new, opts, true), opts, !opts.usePadding);

				if (hiddenitems) opts.items[opts.d['width']] = orgW;

				if (opts.usePadding) {
					sz_resetMargin(a_itm, opts, true);
					sz_resetMargin(l_old, opts, opts.padding[opts.d[1]]);
					sz_resetMargin(l_new, opts, opts.padding[opts.d[1]]);
				}
				if (opts.align) {
					opts.padding[opts.d[1]] = p[1];
					opts.padding[opts.d[3]] = p[0];
				}

				//	animation configuration
				var a_cfs = {},
					a_old = {},
					a_cur = {},
					a_dur = sO.duration;

					 if (sO.fx == 'none')	a_dur = 0;
				else if (a_dur == 'auto')	a_dur = opts.scroll.duration / opts.scroll.items * nI;
				else if (a_dur <= 0)		a_dur = 0;
				else if (a_dur < 10)		a_dur = i_siz / a_dur;

				scrl = sc_setScroll(a_dur, sO.easing);

				//	animate wrapper
				if (opts[opts.d['width']] == 'variable' || opts[opts.d['height']] == 'variable') {
					scrl.anims.push([$wrp, w_siz]);
				}

				//	animate items
				if (opts.usePadding) {
					a_old[opts.d['marginRight']] = l_old.data('cfs_origCssMargin');
					a_cur[opts.d['marginRight']] = l_cur.data('cfs_origCssMargin') + opts.padding[opts.d[3]];
					l_new.css(opts.d['marginRight'], l_new.data('cfs_origCssMargin') + opts.padding[opts.d[1]]);

					if (l_cur.not(l_old).length) {
						scrl.anims.push([l_old, a_old]);
					}
					scrl.anims.push([l_cur, a_cur]);
				}

				//	animate carousel
				a_cfs[opts.d['left']] = -i_siz;

				//	onBefore callback
				var args = [c_old, c_new, w_siz, a_dur];
				if (sO.onBefore) sO.onBefore.apply($tt0, args);
				clbk.onBefore = sc_callCallbacks(clbk.onBefore, $tt0, args);



				//	ALTERNATIVE EFFECTS

				//	extra animation arrays
				switch(sO.fx) {
					case 'fade':
					case 'crossfade':
					case 'cover':
					case 'uncover':
						scrl.pre = sc_setScroll(scrl.duration, scrl.easing);
						scrl.post = sc_setScroll(scrl.duration, scrl.easing);
						scrl.duration = 0;
						break;
				}

				//	create copy
				switch(sO.fx) {
					case 'crossfade':
					case 'cover':
					case 'uncover':
						var $cf2 = $cfs.clone().appendTo($wrp);
						break;
				}
				switch(sO.fx) {
					case 'uncover':
						$cf2.children().slice(opts.items.oldVisible).remove();
						break;
					case 'crossfade':
					case 'cover':
						$cf2.children().slice(0, nI).remove();
						$cf2.children().slice(opts.items.visible).remove();
						break;
				}

				//	animations
				switch(sO.fx) {
					case 'fade':
						scrl.pre.anims.push([$cfs, { 'opacity': 0 }]);
						break;
					case 'crossfade':
						$cf2.css({ 'opacity': 0 });
						scrl.pre.anims.push([$cfs, { 'width': '+=0' }, function() { $cf2.remove(); }]);
						scrl.post.anims.push([$cf2, { 'opacity': 1 }]);
						break;
					case 'cover':
						scrl = fx_cover(scrl, $cfs, $cf2, opts, false);
						break;
					case 'uncover':
						scrl = fx_uncover(scrl, $cfs, $cf2, opts, false, nI);
						break;
				}

				//	/ALTERNATIVE EFFECTS


				//	complete callback
				var a_complete = function() {

					var overFill = opts.items.visible+nI-itms.total,
						new_m = (opts.usePadding) ? opts.padding[opts.d[3]] : 0;
					$cfs.css(opts.d['left'], new_m);
					if (overFill > 0) {
						$cfs.children().slice(itms.total).remove();
					}
					var l_itm = $cfs.children().slice(0, nI).appendTo($cfs).last();
					if (overFill > 0) {
						c_new = gi_getCurrentItems(a_itm, opts);
					}
					if (hiddenitems) hiddenitems.show();
					if (opts.usePadding) {
						if (itms.total < opts.items.visible+nI) {
							var l_cur = $cfs.children().eq(opts.items.visible-1);
							l_cur.css(opts.d['marginRight'], l_cur.data('cfs_origCssMargin') + opts.padding[opts.d[3]]);
						}
						l_itm.css(opts.d['marginRight'], l_itm.data('cfs_origCssMargin'));
					}

					scrl.anims = [];
					if (scrl.pre) scrl.pre = sc_setScroll(scrl.orgDuration, scrl.easing);

					var fn = function() {
						switch(sO.fx) {
							case 'fade':
							case 'crossfade':
								$cfs.css('filter', '');
								break;
						}

						scrl.post = sc_setScroll(0, null);
						crsl.isScrolling = false;

						var args = [c_old, c_new, w_siz];
						if (sO.onAfter) sO.onAfter.apply($tt0, args);
						clbk.onAfter = sc_callCallbacks(clbk.onAfter, $tt0, args);

						if (queu.length) {
							$cfs.trigger(queu[0][0], queu[0][1]);
							queu.shift();
						}
						if (!crsl.isPaused) $cfs.trigger('play');
					};
					switch(sO.fx) {
						case 'fade':
							scrl.pre.anims.push([$cfs, { 'opacity': 1 }, fn]);
							sc_startScroll(scrl.pre);
							break;
						case 'uncover':
							scrl.pre.anims.push([$cfs, { 'width': '+=0' }, fn]);
							sc_startScroll(scrl.pre);
							break;
						default:
							fn();
							break;
					}
				};

				scrl.anims.push([$cfs, a_cfs, a_complete]);
				crsl.isScrolling = true;
				tmrs = sc_clearTimers(tmrs);
				sc_startScroll(scrl);
				cf_setCookie(opts.cookie, $cfs.triggerHandler('currentPosition'));

				$cfs.trigger('updatePageStatus', [false, w_siz]);
			});

			//	slideTo event
			$cfs.bind('slideTo.cfs'+serial, function(e, num, dev, org, obj, dir) {
				e.stopPropagation();

				var v = [num, dev, org, obj, dir],
					t = ['string/number/object', 'number', 'boolean', 'object', 'string'],
					a = cf_sortParams(v, t);
				
				var obj = a[3],
					dir = a[4];

				num = gn_getItemIndex(a[0], a[1], a[2], itms, $cfs);

				if (num == 0) return;
				if (typeof obj != 'object') obj = false;

				if (crsl.isScrolling) {
					if (typeof obj != 'object' || obj.duration > 0) return;
				}

				if (dir != 'prev' && dir != 'next') {
					if (opts.circular) {
						if (num <= itms.total / 2) 	dir = 'next';
						else 						dir = 'prev';
					} else {
						if (itms.first == 0 ||
							itms.first > num)		dir = 'next';
						else						dir = 'prev';
					}
				}

				if (dir == 'prev')	$cfs.trigger('prev', [obj, itms.total-num]);
				else 				$cfs.trigger('next', [obj, num]);
			});

			//	jumpToStart event
			$cfs.bind('jumpToStart.cfs'+serial, function(e, s) {
				if (s)	s = gn_getItemIndex(s, 0, true, itms, $cfs);
				else 	s = 0;

				s += itms.first;
				if (s != 0) {
					while (s > itms.total) s -= itms.total;
					$cfs.prepend($cfs.children().slice(s));
				}
			});

			//	synchronise event
			$cfs.bind('synchronise.cfs'+serial, function(e, s) {
					 if (s) 				s = cf_getSynchArr(s);
				else if (opts.synchronise)	s = opts.synchronise;
				else return debug(conf, 'No carousel to synchronise.');

				var n = $cfs.triggerHandler('currentPosition');
				for (var j = 0, l = s.length; j < l; j++) {
					s[j][0].trigger('slideTo', [n, s[j][3], true]);
				}
			});

			//	queue event
			$cfs.bind('queue.cfs'+serial, function(e, dir, opt) {
				if (typeof dir == 'function') {
					dir.call($tt0, queu);
				} else if (is_array(dir)) {
					queu = dir;
				} else if (typeof dir != 'undefined') {
					queu.push([dir, opt]);
				}
				return queu;
			});

			//	insertItem event
			$cfs.bind('insertItem.cfs'+serial, function(e, itm, num, org, dev) {
				e.stopPropagation();

				var v = [itm, num, org, dev],
					t = ['string/object', 'string/number/object', 'boolean', 'number'],
					a = cf_sortParams(v, t);
				
				var itm = a[0],
					num = a[1],
					org = a[2],
					dev = a[3];

				if (typeof itm == 'object' && 
					typeof itm.jquery == 'undefined')	itm = $(itm);
				if (typeof itm == 'string') 			itm = $(itm);
				if (typeof itm != 'object' ||
					typeof itm.jquery == 'undefined' || 
					itm.length == 0) return debug(conf, 'Not a valid object.');

				if (typeof num == 'undefined') num = 'end';

				if (opts.usePadding) {
					itm.each(function() {
						var m = parseInt($(this).css(opts.d['marginRight']));
						if (isNaN(m)) m = 0;
						$(this).data('cfs_origCssMargin', m);
					});
				}

				var orgNum = num,
					before = 'before';

				if (num == 'end') {
					if (org) {
						if (itms.first == 0) {
							num = itms.total-1;
							before = 'after';
						} else {
							num = itms.first;
							itms.first += itm.length
						}
						if (num < 0) num = 0;
					} else {
						num = itms.total-1;
						before = 'after';
					}
				} else {
					num = gn_getItemIndex(num, dev, org, itms, $cfs);
				}
				if (orgNum != 'end' && !org) {
					if (num < itms.first) itms.first += itm.length;
				}
				if (itms.first >= itms.total) itms.first -= itms.total;

				var $cit = $cfs.children().eq(num);
				if ($cit.length) {
					$cit[before](itm);
				} else {
					$cfs.append(itm);
				}

				itms.total = $cfs.children().length;
				var sz = sz_setSizes($cfs, opts);
				nv_showNavi(opts, itms.total, conf);
				nv_enableNavi(opts, itms.first);
				$cfs.trigger('linkAnchors');
				$cfs.trigger('updatePageStatus', [true, sz]);
			});

			//	removeItem event
			$cfs.bind('removeItem.cfs'+serial, function(e, num, org, dev) {
				e.stopPropagation();
				
				var v = [num, org, dev],
					t = ['string/number/object', 'boolean', 'number'],
					a = cf_sortParams(v, t);
				
				var num = a[0],
					org = a[1],
					dev = a[2];

				if (typeof num == 'undefined' || num == 'end') {
					$cfs.children().last().remove();
				} else {
					num = gn_getItemIndex(num, dev, org, itms, $cfs);
					var $cit = $cfs.children().eq(num);
					if ($cit.length){
						if (num < itms.first) itms.first -= $cit.length;
						$cit.remove();
					}
				}
				itms.total = $cfs.children().length;
				var sz = sz_setSizes($cfs, opts);
				nv_showNavi(opts, itms.total, conf);
				nv_enableNavi(opts, itms.first);
				$cfs.trigger('updatePageStatus', [true, sz]);
			});

			//	onBefore and onAfter event
			$cfs.bind('onBefore.cfs'+serial+' onAfter.cfs'+serial, function(e, fn) {
				e.stopPropagation();
				if (is_array(fn))				clbk[e.type] = fn;
				if (typeof fn == 'function')	clbk[e.type].push(fn);
				return clbk[e.type];
			});

			//	currentPosition event
			$cfs.bind('currentPosition.cfs'+serial, function(e, fn) {
				e.stopPropagation();
				if (itms.first == 0) var val = 0;
				else var val = itms.total - itms.first;
				if (typeof fn == 'function') fn.call($tt0, val);
				return val;
			});

			//	currentPage event
			$cfs.bind('currentPage.cfs'+serial, function(e, fn) {
				e.stopPropagation();
				var ipp = opts.pagination.items || opts.items.visible,
					max = Math.ceil(itms.total/ipp-1);
				if (itms.first == 0) 							var nr = 0;
				else if (itms.first < itms.total % ipp) 		var nr = 0;
				else if (itms.first == ipp && !opts.circular) 	var nr = max;
				else 											var nr = Math.round((itms.total-itms.first)/ipp);
				if (nr < 0) nr = 0;
				if (nr > max) nr = max;
				if (typeof fn == 'function') fn.call($tt0, nr);
				return nr;
			});

			//	currentVisible event
			$cfs.bind('currentVisible.cfs'+serial, function(e, fn) {
				e.stopPropagation();
				$i = gi_getCurrentItems($cfs.children(), opts);
				if (typeof fn == 'function') fn.call($tt0, $i);
				return $i;
			});

			//	isPaused, isStopped and isScrolling events
			$cfs.bind('isPaused.cfs'+serial+' isStopped.cfs'+serial+' isScrolling.cfs'+serial, function(e, fn) {
				e.stopPropagation();
				if (typeof fn == 'function') fn.call($tt0, crsl[e.type]);
				return crsl[e.type];
			});

			//	configuration event
			$cfs.bind('configuration.cfs'+serial, function(e, a, b, c) {
				e.stopPropagation();
				var reInit = false;

				//	return entire configuration-object
				if (typeof a == 'function') {
					a.call($tt0, opts);

				//	set multiple options via object
				} else if (typeof a == 'object') {
					opts_orig = $.extend(true, {}, opts_orig, a);
					if (b !== false) reInit = true;
					else opts = $.extend(true, {}, opts, a);

				} else if (typeof a != 'undefined') {

					//	callback function for specific option
					if (typeof b == 'function') {
						var val = eval('opts.'+a);
						if (typeof val == 'undefined') val = '';
						b.call($tt0, val);

					//	set individual option
					} else if (typeof b != 'undefined') {
						if (typeof c !== 'boolean') c = true;
						eval('opts_orig.'+a+' = b');
						if (c !== false) reInit = true;
						else eval('opts.'+a+' = b');

					//	return value for specific option
					} else {
						return eval('opts.'+a);
					}
				}
				if (reInit) {
					sz_resetMargin($cfs.children(), opts);
					$cfs.init(opts_orig);
					$cfs.bind_buttons();
					var siz = sz_setSizes($cfs, opts);
					$cfs.trigger('updatePageStatus', [true, siz]);					
				}
				return opts;
			});

			//	linkAnchors event
			$cfs.bind('linkAnchors.cfs'+serial, function(e, $con, sel) {
				e.stopPropagation();
				if (typeof $con == 'undefined' || $con.length == 0) $con = $('body');
				else if (typeof $con == 'string') $con = $($con);
				if (typeof $con != 'object') return debug(conf, 'Not a valid object.');
				if (typeof sel != 'string' || sel.length == 0) sel = 'a.caroufredsel';
				$con.find(sel).each(function() {
					var h = this.hash || '';
					if (h.length > 0 && $cfs.children().index($(h)) != -1) {
						$(this).unbind('click').click(function(e) {
							e.preventDefault();
							$cfs.trigger('slideTo', h);
						});
					}
				});
			});

			//	updatePageStatus event
			$cfs.bind('updatePageStatus.cfs'+serial, function(e, build, sizes) {
				e.stopPropagation();
				if (!opts.pagination.container) return;
				if (typeof build == 'boolean' && build) {
					opts.pagination.container.children().remove();
					var ipp = opts.pagination.items || opts.items.visible;
					for (var a = 0, l = Math.ceil(itms.total/ipp); a < l; a++) {
						var i = $cfs.children().eq( gn_getItemIndex(a*ipp, 0, true, itms, $cfs) );
						opts.pagination.container.append(opts.pagination.anchorBuilder(a+1, i));
					}
					opts.pagination.container.each(function() {
						$(this).children().unbind(opts.pagination.event).each(function(a) {
							$(this).bind(opts.pagination.event, function(e) {
								e.preventDefault();
								$cfs.trigger('slideTo', [a*ipp, 0, true, opts.pagination]);
							});
						});
					});
				}
				opts.pagination.container.each(function() {
					$(this).children().removeClass('selected').eq($cfs.triggerHandler('currentPage')).addClass('selected');
				});
			});

			//	destroy event
			$cfs.bind('destroy.cfs'+serial, function(e, orgOrder) {
				e.stopPropagation();
				tmrs = sc_clearTimers(tmrs);

				$cfs.data('cfs_isCarousel', false);
				$cfs.trigger('finish');
				if (orgOrder) {
					$cfs.trigger('jumpToStart');
				}
				if (opts.usePadding) {
					sz_resetMargin($cfs.children(), opts);
				}
				$cfs.css($cfs.data('cfs_origCss'));
				$cfs.unbind_events();
				$cfs.unbind_buttons();
				$wrp.replaceWith($cfs);
			});
		};	//	/bind_events

		$cfs.unbind_events = function() {
			$cfs.unbind('.cfs'+serial);
		};	//	/unbind_events

		$cfs.bind_buttons = function() {
			$cfs.unbind_buttons();
			nv_showNavi(opts, itms.total, conf);
			nv_enableNavi(opts, itms.first);

			if (opts.auto.pauseOnHover) {
				var pC = bt_pauseOnHoverConfig(opts.auto.pauseOnHover);
				$wrp.bind('mouseenter.cfs'+serial, function() { $cfs.trigger('pause', [pC[0], pC[1]]);	})
					.bind('mouseleave.cfs'+serial, function() { $cfs.trigger('resume');					});
			}

			if (opts.prev.button) {
				opts.prev.button.bind(opts.prev.event+'.cfs'+serial, function(e) {
					e.preventDefault();
					$cfs.trigger('prev');
				});
				if (opts.prev.pauseOnHover) {
					var pC = bt_pauseOnHoverConfig(opts.prev.pauseOnHover);
					opts.prev.button.bind('mouseenter.cfs'+serial, function() { $cfs.trigger('pause', [pC[0], pC[1]]);	})
									.bind('mouseleave.cfs'+serial, function() { $cfs.trigger('resume');					});
				}
			}

			if (opts.next.button) {
				opts.next.button.bind(opts.next.event+'.cfs'+serial, function(e) {
					e.preventDefault();
					$cfs.trigger('next');
				});
				if (opts.next.pauseOnHover) {
					var pC = bt_pauseOnHoverConfig(opts.next.pauseOnHover);
					opts.next.button.bind('mouseenter.cfs'+serial, function() { $cfs.trigger('pause', [pC[0], pC[1]]); 	})
									.bind('mouseleave.cfs'+serial, function() { $cfs.trigger('resume');					});
				}
			}
			if ($.fn.mousewheel) {
				if (opts.prev.mousewheel) {
					if (!crsl.mousewheelPrev) {
						crsl.mousewheelPrev = true;
						$wrp.mousewheel(function(e, delta) { 
							if (delta > 0) {
								e.preventDefault();
								var num = bt_mousesheelNumber(opts.prev.mousewheel);
								$cfs.trigger('prev', num);
							}
						});
					}
				}
				if (opts.next.mousewheel) {
					if (!crsl.mousewheelNext) {
						crsl.mousewheelNext = true;
						$wrp.mousewheel(function(e, delta) { 
							if (delta < 0) {
								e.preventDefault();
								var num = bt_mousesheelNumber(opts.next.mousewheel);
								$cfs.trigger('next', num);
							}
						});
					}
				}
			}
			if ($.fn.touchwipe) {
				var wP = (opts.prev.wipe) ? function() { $cfs.trigger('prev') } : null,
					wN = (opts.next.wipe) ? function() { $cfs.trigger('next') } : null;

				if (wN || wN) {
					if (!crsl.touchwipe) {
						crsl.touchwipe = true;
						var twOps = {
							'min_move_x': 30,
							'min_move_y': 30,
							'preventDefaultEvents': true
						};
						switch (opts.direction) {
							case 'up':
							case 'down':
								twOps.wipeUp = wN;
								twOps.wipeDown = wP;
								break;
							default:
								twOps.wipeLeft = wN;
								twOps.wipeRight = wP;
						}
						$wrp.touchwipe(twOps);
					}
				}
			}
			if (opts.pagination.container) {
				if (opts.pagination.pauseOnHover) {
					var pC = bt_pauseOnHoverConfig(opts.pagination.pauseOnHover);
					opts.pagination.container.bind('mouseenter.cfs'+serial, function() { $cfs.trigger('pause', [pC[0], pC[1]]);	})
											 .bind('mouseleave.cfs'+serial, function() { $cfs.trigger('resume');				});
				}
			}
			if (opts.prev.key || opts.next.key) {
				$(document).bind('keyup.cfs'+serial, function(e) {
					var k = e.keyCode;
					if (k == opts.next.key)	{
						e.preventDefault();
						$cfs.trigger('next');
					}
					if (k == opts.prev.key) {
						e.preventDefault();
						$cfs.trigger('prev');
					}
				});
			}
			if (opts.pagination.keys) {
				$(document).bind('keyup.cfs'+serial, function(e) {
					var k = e.keyCode;
					if (k >= 49 && k < 58) {
						k = (k-49) * opts.items.visible;
						if (k <= itms.total) {
							e.preventDefault();
							$cfs.trigger('slideTo', [k, 0, true, opts.pagination]);
						}
					}
				});
			}
			if (opts.auto.play) {
				$cfs.trigger('play', opts.auto.delay);
			}
		};	//	/bind_buttons

		$cfs.unbind_buttons = function() {
			$(document).unbind('.cfs'+serial);
			$wrp.unbind('.cfs'+serial);
			if (opts.prev.button) 			opts.prev.button.unbind('.cfs'+serial);
			if (opts.next.button) 			opts.next.button.unbind('.cfs'+serial);
			if (opts.pagination.container)	opts.pagination.container.unbind('.cfs'+serial);
			nv_showNavi(opts, 'hide', conf);
			nv_enableNavi(opts, 'removeClass');
			if (opts.pagination.container) {
				opts.pagination.container.children().remove();
			}
		};	//	/unbind_buttons



		//	START

		if ($cfs.data('cfs_isCarousel')) {
			var strt = $cfs.triggerHandler('currentPosition');
			$cfs.trigger('destroy', true);
		} else {
			var strt = false;
		}

		var crsl = {
				'direction'		: 'next',
				'isPaused'		: true,
				'isScrolling'	: false,
				'isStopped'		: false,

				'mousewheelNext': false,
				'mousewheelPrev': false,
				'touchwipe'		: false
			},
			itms = {
				'total'			: $cfs.children().length,
				'first'			: 0
			},
			tmrs = {
				'timer'			: null,
				'auto'			: null,
				'queue'			: null,
				'startTime'		: getTime(),
				'timePassed'	: 0
			},
			scrl = {
				'isStopped'		: false,
				'duration'		: 0,
				'startTime'		: 0,
				'easing'		: '',
				'anims'			: []
			},
			clbk = {
				'onBefore'		: [],
				'onAfter'		: []
			},
			queu = [],
			conf = $.extend(true, {}, $.fn.carouFredSel.configs, configs),
			opts = {},
			opts_orig = options,
			serial = $.fn.carouFredSel.serial++,
			$wrp = $cfs.wrap('<'+conf.wrapper.element+' class="'+conf.wrapper.classname+'" />').parent();

		conf.selector = $cfs.selector;

		$cfs.init(opts_orig, true, strt);
		$cfs.build();
		$cfs.bind_events();
		$cfs.bind_buttons();

		if (opts.cookie) {
			opts.items.start = cf_readCookie(opts.cookie);
			var cn = opts.cookie+'=';
			var ca = document.cookie.split(';');
			for (var a = 0, l = ca.length; a < l; a++) {
				var c = ca[a];
				while (c.charAt(0) == ' ') {
					c = c.substring(1, c.length);
				}
				if (c.indexOf(cn) == 0) {
					opts.items.start = c.substring(cn.length, c.length);
					break;
				}
			}
		}
		if (opts.items.start != 0) {
			var s = opts.items.start;
			if (s === true) {
				s = window.location.hash;
				if (!s.length) s = 0;
			} else if (s === 'random') {
				s = Math.floor(Math.random() * itms.total);
			}
			$cfs.trigger('slideTo', [s, 0, true, { fx: 'none' }]);
		}
		var siz = sz_setSizes($cfs, opts, false),
			itm = gi_getCurrentItems($cfs.children(), opts);

		if (opts.onCreate) {
			opts.onCreate.call($tt0, itm, siz);
		}

		$cfs.trigger('updatePageStatus', [true, siz]);
		$cfs.trigger('linkAnchors');

		return $cfs;
	};



	//	GLOBAL PUBLIC

	$.fn.carouFredSel.serial = 0;
	$.fn.carouFredSel.defaults = {
		'synchronise'	: false,
		'infinite'		: true,
		'circular'		: true,
		'direction'		: 'left',
		'items'			: {
			'start'			: 0
		},
		'scroll'		: {
			'easing'		: 'swing',
			'pauseOnHover'	: false,
			'mousewheel'	: false,
			'wipe'			: false,
			'event'			: 'click',
			'queue'			: false
		}
	};
	$.fn.carouFredSel.configs = {
		'debug'			: false,
		'wrapper'		: {
			'element'		: 'div',
			'classname'		: 'caroufredsel_wrapper'
		}
	};

	$.fn.carouFredSel.pageAnchorBuilder = function(nr, itm) {
		return '<a href="#"><span>'+nr+'</span></a>';
	};



	//	GLOBAL PRIVATE

	//	scrolling functions
	function sc_setScroll(d, e) {
		return {
			anims		: [],
			duration	: d,
			orgDuration	: d,
			easing		: e,
			startTime	: getTime()
		};
	}
	function sc_startScroll(s) {
		if (typeof s.pre == 'object') {
			sc_startScroll(s.pre);
		}
		for (var a = 0, l = s.anims.length; a < l; a++) {
			var b = s.anims[a];
			if (!b) continue;
			if (b[3]) b[0].stop();
			b[0].animate(b[1], {
				complete: b[2],
				duration: s.duration,
				easing: s.easing
			});
		}
		if (typeof s.post == 'object') {
			sc_startScroll(s.post);
		}
	}
	function sc_stopScroll(s, finish) {
		if (typeof finish != 'boolean') finish = true;
		if (typeof s.pre == 'object') {
			sc_stopScroll(s.pre, finish);
		}
		for (var a = 0, l = s.anims.length; a < l; a++) {
			var b = s.anims[a];
			b[0].stop(true);
			if (finish) {
				b[0].css(b[1]);
				if (typeof b[2] == 'function') b[2]();
			}
		}
		if (typeof s.post == 'object') {
			sc_stopScroll(s.post, finish);
		}
	}
	function sc_clearTimers(t) {
		if (t.auto) clearTimeout(t.auto);
		return t;
	}
	function sc_callCallbacks(cbs, t, args) {
		if (cbs.length) {
			for (var a = 0, l = cbs.length; a < l; a++) {
				cbs[a].apply(t, args);
			}
		}
		return [];
	}

	//	fx functions
	function fx_fade(sO, c, x, d, f) {
		var o = {
			'duration'	: d,
			'easing'	: sO.easing
		};
		if (typeof f == 'function') o.complete = f;
		c.animate({
			opacity: x
		}, o);
	}
	function fx_cover(sc, c1, c2, o, prev) {
		var old_w = ms_getSizes(gi_getOldItemsNext(c1.children(), o), o, true)[0],
			new_w = ms_getSizes(c2.children(), o, true)[0],
			cur_l = (prev) ? -new_w : old_w,
			css_o = {},
			ani_o = {};

		css_o[o.d['width']] = new_w;
		css_o[o.d['left']] = cur_l;
		ani_o[o.d['left']] = 0;
		
		sc.pre.anims.push([c1, { 'opacity': 1 }]);
		sc.post.anims.push([c2, ani_o, function() { $(this).remove(); }]);
		c2.css(css_o);
		return sc;
	}
	function fx_uncover(sc, c1, c2, o, prev, n) {
		var new_w = ms_getSizes(gi_getNewItemsNext(c1.children(), o, n), o, true)[0],
			old_w = ms_getSizes(c2.children(), o, true)[0],
			cur_l = (prev) ? -old_w : new_w,
			css_o = {},
			ani_o = {};

		css_o[o.d['width']] = old_w;
		css_o[o.d['left']] = 0;
		ani_o[o.d['left']] = cur_l;
		sc.post.anims.push([c2, ani_o, function() { $(this).remove(); }]);
		c2.css(css_o);
		return sc;
	}

	//	navigation functions
	function nv_showNavi(o, t, c) {
		if (t == 'show' || t == 'hide') {
			var f = t;
		} else if (o.items.minimum >= t) {
			debug(c, 'Not enough items: hiding navigation ('+t+' items, '+o.items.minimum+' needed).');
			var f = 'hide';
		} else {
			var f = 'show';
		}
		var s = (f == 'show') ? 'removeClass' : 'addClass';
		if (o.prev.button) o.prev.button[f]()[s]('hidden');
		if (o.next.button) o.next.button[f]()[s]('hidden');
		if (o.pagination.container) o.pagination.container[f]()[s]('hidden');
	}
	function nv_enableNavi(o, f) {
		if (o.circular || o.infinite) return;
		var fx = (f == 'removeClass' || f == 'addClass') ? f : false;
		if (o.next.button) {
			var fn = fx || (f == o.items.visible) ? 'addClass' : 'removeClass';
			o.next.button[fn]('disabled');
		}
		if (o.prev.button) {
			var fn = fx || (f == 0) ? 'addClass' : 'removeClass';
			o.prev.button[fn]('disabled');
		}
	}

	//	get object functions
	function go_getObject($tt, obj) {
		if (typeof obj == 'function')	obj = obj.call($tt);
		if (typeof obj == 'undefined')	obj = {};
		return obj;
	}
	function go_getNaviObject($tt, obj, type) {
		if (typeof type != 'string') type = '';

		obj = go_getObject($tt, obj);
		if (typeof obj == 'string') {
			var temp = cf_getKeyCode(obj);
			if (temp == -1) obj = $(obj);
			else 			obj = temp;
		}

		//	pagination
		if (type == 'pagination') {
			if (typeof obj 				== 'boolean')	obj = { 'keys': obj };
			if (typeof obj.jquery 		!= 'undefined')	obj = { 'container': obj };
			if (typeof obj.container	== 'function')	obj.container = obj.container.call($tt);
			if (typeof obj.container	== 'string')	obj.container = $(obj.container);
			if (typeof obj.items		!= 'number')	obj.items = false;

		//	auto
		} else if (type == 'auto') {
			if (typeof obj == 'boolean')				obj = { 'play': obj };
			if (typeof obj == 'number')					obj = { 'pauseDuration': obj };

		//	prev + next
		} else {
			if (typeof obj.jquery	!= 'undefined')		obj = { 'button': obj };
			if (typeof obj 			== 'number')		obj = { 'key': obj };
			if (typeof obj.button	== 'function')		obj.button = obj.button.call($tt);
			if (typeof obj.button	== 'string')		obj.button = $(obj.button);
			if (typeof obj.key		== 'string')		obj.key = cf_getKeyCode(obj.key);
		}			

		return obj;
	}

	//	get number functions
	function gn_getItemIndex(num, dev, org, items, $cfs) {
		if (typeof num == 'string') {
			if (isNaN(num)) num = $(num);
			else 			num = parseInt(num);
		}
		if (typeof num == 'object') {
			if (typeof num.jquery == 'undefined') num = $(num);
			num = $cfs.children().index(num);
			if (num == -1) num = 0;
			if (typeof org != 'boolean') org = false;
		} else {
			if (typeof org != 'boolean') org = true;
		}
		if (isNaN(num))	num = 0;
		else 			num = parseInt(num);
		if (isNaN(dev))	dev = 0;
		else 			dev = parseInt(dev);

		if (org) {
			num += items.first;
		}
		num += dev;
		if (items.total > 0) {
			while (num >= items.total)	{	num -= items.total; }
			while (num < 0)				{	num += items.total; }
		}
		return num;
	}
	function gn_getVisibleItemsPrev(i, o, s) {
		var t = 0,
			x = 0;

		for (var a = s; a >= 0; a--) {
			t += i.eq(a)[o.d['outerWidth']](true);
			if (t > o.maxDimention) return x;
			if (a == 0) a = i.length;
			x++;
		}
	}
	function gn_getVisibleItemsNext(i, o, s) {
		var t = 0,
			x = 0;

		for (var a = s, l = i.length-1; a <= l; a++) {
			t += i.eq(a)[o.d['outerWidth']](true);
			if (t > o.maxDimention) return x;
			if (a == l) a = -1;
			x++;
		}
	}
	function gn_getVisibleItemsNextTestCircular(i, o, s, l) {
		var v = gn_getVisibleItemsNext(i, o, s);
		if (!o.circular) {
			if (s + v > l) v = l - s;
		}
		return v;
	}

	//	get items functions
	function gi_getCurrentItems(i, o) {
		return i.slice(0, o.items.visible);
	}
	function gi_getOldItemsPrev(i, o, n) {
		return i.slice(n, o.items.oldVisible+n);
	}
	function gi_getNewItemsPrev(i, o) {
		return i.slice(0, o.items.visible);
	}
	function gi_getOldItemsNext(i, o) {
		return i.slice(0, o.items.oldVisible);
	}
	function gi_getNewItemsNext(i, o, n) {
		return i.slice(n, o.items.visible+n);
	}

	//	sizes functions
	function sz_resetMargin(i, o, m) {
		var x = (typeof m == 'boolean') ? m : false;
		if (typeof m != 'number') m = 0;
		i.each(function() {
			var t = parseInt($(this).css(o.d['marginRight']));
			if (isNaN(t)) t = 0;
			$(this).data('cfs_tempCssMargin', t);
			$(this).css(o.d['marginRight'], ((x) ? $(this).data('cfs_tempCssMargin') : m + $(this).data('cfs_origCssMargin')));
		});
	}
	function sz_setSizes($c, o, p) {
		var $w = $c.parent(),
			$i = $c.children(),
			$v = gi_getCurrentItems($i, o),
			sz = cf_mapWrapperSizes(ms_getSizes($v, o, true), o, p);

		$w.css(sz);

		if (o.usePadding) {
			var $l = $v.last();
			$l.css(o.d['marginRight'], $l.data('cfs_origCssMargin') + o.padding[o.d[1]]);
			$c.css(o.d['top'], o.padding[o.d[0]]);
			$c.css(o.d['left'], o.padding[o.d[3]]);
		}
		$c.css(o.d['width'], sz[o.d['width']]+(ms_getTotalSize($i, o, 'width')*2));
		$c.css(o.d['height'], ms_getLargestSize($i, o, 'height'));
		return sz;
	}

	//	measuring functions
	function ms_getSizes(i, o, wrapper) {
		s1 = ms_getTotalSize(i, o, 'width', wrapper);
		s2 = ms_getLargestSize(i, o, 'height', wrapper);
		return [s1, s2];
	}
	function ms_getLargestSize(i, o, dim, wrapper) {
		if (typeof wrapper != 'boolean') wrapper = false;
		if (typeof o[o.d[dim]] == 'number' && wrapper) return o[o.d[dim]];
		if (typeof o.items[o.d[dim]] == 'number') return o.items[o.d[dim]];
		var di2 = (dim.toLowerCase().indexOf('width') > -1) ? 'outerWidth' : 'outerHeight';
		return ms_getTrueLargestSize(i, o, di2);
	}
	function ms_getTrueLargestSize(i, o, dim) {
		var s = 0;
		i.each(function() {
			var m = $(this)[o.d[dim]](true);
			if (s < m) s = m;
		});
		return s;
	}
	function ms_getTrueInnerSize($el, o, dim) {
		var siz = $el[o.d[dim]](),
			arr = (o.d[dim].toLowerCase().indexOf('width') > -1) ? ['paddingLeft', 'paddingRight'] : ['paddingTop', 'paddingBottom'];
		for (a = 0, l = arr.length; a < l; a++) {
			var m = parseInt($el.css(arr[a]));
			if (isNaN(m)) m = 0;
			siz -= m;
		}
		return siz;
	}
	function ms_getTotalSize(i, o, dim, wrapper) {
		if (typeof wrapper != 'boolean') wrapper = false;
		if (typeof o[o.d[dim]] == 'number' && wrapper) return o[o.d[dim]];
		if (typeof o.items[o.d[dim]] == 'number') return o.items[o.d[dim]] * i.length;
		var d = (dim.toLowerCase().indexOf('width') > -1) ? 'outerWidth' : 'outerHeight',
			s = 0;
		i.each(function() {
			var j = $(this);
			if (j.is(':visible')) {
				s += j[o.d[d]](true);
			}
		});
		return s;
	}
	function ms_hasVariableSizes(i, o, dim) {
		var s = false,
			v = false;
		i.each(function() { 
			c = $(this)[o.d[dim]](true);
			if (s === false) s = c;
			else if (s != c) v = true;
			if (s == 0)		 v = true;
		});
		return v;
	}

	//	config functions
	function cf_mapWrapperSizes(ws, o, p) {
		if (typeof p != 'boolean') p = true;
		var pad = (o.usePadding && p) ? o.padding : [0, 0, 0, 0];
		var wra = {};
			wra[o.d['width']] = ws[0] + pad[1] + pad[3];
			wra[o.d['height']] = ws[1] + pad[0] + pad[2];

		return wra;
	}
	function cf_sortParams(vals, typs) {
		var arr = [];
		for (var a = 0, l1 = vals.length; a < l1; a++) {
			for (var b = 0, l2 = typs.length; b < l2; b++) {
				if (typs[b].indexOf(typeof vals[a]) > -1 && !arr[b]) {
					arr[b] = vals[a];
					break;
				}
			}
		}
		return arr;
	}
	function cf_getPadding(p) {
		if (typeof p == 'undefined') return [0, 0, 0, 0];
		
		if (typeof p == 'number') return [p, p, p, p];
		else if (typeof p == 'string') p = p.split('px').join('').split('em').join('').split(' ');

		if (!is_array(p)) {
			return [0, 0, 0, 0];
		}
		for (var i = 0; i < 4; i++) {
			p[i] = parseInt(p[i]);
		}
		switch (p.length) {
			case 0:	return [0, 0, 0, 0];
			case 1: return [p[0], p[0], p[0], p[0]];
			case 2: return [p[0], p[1], p[0], p[1]];
			case 3: return [p[0], p[1], p[2], p[1]];
			default: return [p[0], p[1], p[2], p[3]];
		}
	}
	function cf_getAlignPadding(itm, o) {
		var x = (typeof o[o.d['width']] == 'number') ? Math.ceil(o[o.d['width']] - ms_getTotalSize(itm, o, 'width')) : 0;
		switch (o.align) {
			case 'left': return [0, x];
			case 'right': return [x, 0];
			case 'center':
			default:
				return [Math.ceil(x/2), Math.floor(x/2)];
		}
	}
	function cf_getVisibleItemsAdjust(x, o) {
		switch (o.visibleAdjust) {
			case '+1': return x + 1;
			case '-1': return x - 1;
			case 'odd': return (x % 2 == 0) ? x - 1 : x;
			case 'odd+': return (x % 2 == 0) ? x + 1 : x;
			case 'even': return (x % 2 == 1) ? x - 1 : x;
			case 'even+': return (x % 2 == 1) ? x + 1 : x;
			default: return x;
		}
	}
	function cf_getSynchArr(s) {
		if (!is_array(s)) 		s = [[s]];
		if (!is_array(s[0]))	s = [s];
		for (var j = 0, l = s.length; j < l; j++) {
			if (typeof s[j][0] == 'string')		s[j][0] = $(s[j][0]);
			if (typeof s[j][1] != 'boolean')	s[j][1] = true;
			if (typeof s[j][2] != 'boolean')	s[j][2] = true;
			if (typeof s[j][3] != 'number')		s[j][3] = 0;
		}
		return s;
	}
	function cf_getKeyCode(k) {
		if (k == 'right')	return 39;
		if (k == 'left')	return 37;
		if (k == 'up')		return 38;
		if (k == 'down')	return 40;
		return -1;
	}
	function cf_setCookie(n, v) {
		if (n) document.cookie = n+'='+v+'; path=/';
	}
	function cf_readCookie(n) {
		n += '=';
		var ca = document.cookie.split(';');
		for (var a = 0, l = ca.length; a < l; a++) {
			var c = ca[a];
			while (c.charAt(0) == ' ') {
				c = c.substring(1, c.length);
			}
			if (c.indexOf(n) == 0) {
				return c.substring(n.length, c.length);
			}
		}
		return 0;
	}

	//	buttons functions
	function bt_pauseOnHoverConfig(p) {
		var i = (p.indexOf('immediate') > -1) ? true : false,
			r = (p.indexOf('resume') 	> -1) ? true : false;
		return [i, r];
	}
	function bt_mousesheelNumber(mw) {
		return (typeof mw == 'number') ? mw : null
	}

	//	helper functions
	function is_array(a) {
		return typeof(a) == 'object' && (a instanceof Array);
	}
	
	function getTime() {
		return new Date().getTime();
	}

	function debug(d, m) {
		if (typeof d == 'object') {
			var s = ' ('+d.selector+')';
			d = d.debug;
		} else {
			var s = '';
		}
		if (!d) return false;
		
		if (typeof m == 'string') m = 'carouFredSel'+s+': ' + m;
		else m = ['carouFredSel'+s+':', m];

		if (window.console && window.console.log) window.console.log(m);
		return false;
	}



	$.fn.caroufredsel = function(o) {
		return this.carouFredSel(o);
	};

})(jQuery);