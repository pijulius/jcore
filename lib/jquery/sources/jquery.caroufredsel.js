/*	
 *	jQuery carouFredSel 4.2.0
 *	Demo's and documentation:
 *	caroufredsel.frebsite.nl
 *	
 *	Copyright (c) 2010 Fred Heusschen
 *	www.frebsite.nl
 *
 *	Dual licensed under the MIT and GPL licenses.
 *	http://en.wikipedia.org/wiki/MIT_License
 *	http://en.wikipedia.org/wiki/GNU_General_Public_License
 */


(function($) {
	if ($.fn.carouFredSel) return;

	$.fn.carouFredSel = function(o) {
		if (this.length == 0) return log('No element selected.');
		if (this.length > 1) {
			return this.each(function() {
				$(this).carouFredSel(o);
			});
		}
		var $ttt = this,
			$tt0 = $ttt[0],
			$cfs = $(this);

		$ttt.init = function(o, setOrig) {
			var obs = ['items', 'scroll', 'auto', 'prev', 'next', 'pagination'];
			o = getObject(o);
			for (var a = 0; a < obs.length; a++) {
				o[obs[a]] = getObject(o[obs[a]]);
			}
			if (typeof o.scroll == 'number') {
				if (o.scroll <= 50)					o.scroll	= { items		: o.scroll 	};
				else								o.scroll	= { duration	: o.scroll 	};
			} else {
				if (typeof o.scroll == 'string')	o.scroll	= { easing		: o.scroll 	};
			}
				 if (typeof o.items == 'number')	o.items		= { visible		: o.items 	};
			else if (typeof o.items == 'string')	o.items		= { visible		: o.items,
																	width		: o.items, 
																	height		: o.items	};

			if (setOrig) {
				opts_orig = $.extend(true, {}, $.fn.carouFredSel.defaults, o);
			}

			opts = $.extend(true, {}, $.fn.carouFredSel.defaults, o);
			opts.variableVisible = false;


			direction = (opts.direction == 'up' || opts.direction == 'left') ? 'next' : 'prev';

			if (opts.direction == 'right' || opts.direction == 'left') {
				opts.dimensions = ['width', 'innerWidth', 'outerWidth', 'height', 'innerHeight', 'outerHeight', 'left', 'top', 'marginRight', 0, 1, 2, 3];
			} else {
				opts.dimensions = ['height', 'innerHeight', 'outerHeight', 'width', 'innerWidth', 'outerWidth', 'top', 'left', 'marginBottom', 3, 2, 1, 0];
			}

			var all_itm = getItems($cfs);
			var lrgst_b = getTrueLargestSize(all_itm, opts, 5, false);

			//	secondairy size set to auto -> measure largest size and set it
			if (opts[opts.dimensions[3]] == 'auto') {
				opts[opts.dimensions[3]] = lrgst_b;
				opts.items[opts.dimensions[3]] = lrgst_b;
			}

			//	secondairy item-size not set -> measure it or set to "variable"
			if (!opts.items[opts.dimensions[3]]) {
				opts.items[opts.dimensions[3]] = (hasVariableSizes(all_itm, opts, 5)) 
					? 'variable' 
					: all_itm[opts.dimensions[5]](true);
			}

			//	secondairy size not set -> set to secondairy item-size
			if (!opts[opts.dimensions[3]]) {
				opts[opts.dimensions[3]] = opts.items[opts.dimensions[3]];
			}

			//	primairy item-size not set -> measure it or set to "variable"
			if (!opts.items[opts.dimensions[0]]) {
				opts.items[opts.dimensions[0]] = (hasVariableSizes(all_itm, opts, 2)) 
					? 'variable' 
					: all_itm[opts.dimensions[2]](true);
			}

			//	visible-items not set -> set it to "variable" if primairy item-size is "variable"
			if (!opts.items.visible && opts.items[opts.dimensions[0]] == 'variable') {
				opts.items.visible = 'variable';
			}

			//	visible-items and primairy size not set -> measure if primairy item-size not "variable"
			if (!opts.items.visible && !opts[opts.dimensions[0]]) {
				if (opts.items[opts.dimensions[0]] != 'variable') {
					opts[opts.dimensions[0]] = getTrueInnerSize($wrp.parent(), opts, 1);
				}
			}

			//	visible-items not set -> calculate it or set to "variable"
			if (!opts.items.visible) {
				opts.items.visible = (typeof opts[opts.dimensions[0]] == 'number' && opts.items[opts.dimensions[0]] != 'variable')
					? Math.floor( opts[opts.dimensions[0]] / opts.items[opts.dimensions[0]] )
					: 'variable';
			}

			//	primairy size not set -> calculate it or set to "variable"
			if (!opts[opts.dimensions[0]]) {
				opts[opts.dimensions[0]] = (opts.items.visible != 'variable' && opts.items[opts.dimensions[0]] != 'variable')
					? opts.items.visible * opts.items[opts.dimensions[0]]
					: 'variable';
			}

			//	variable primairy item-sizes with variabe visible-items
			if (opts.items.visible == 'variable') {
				opts.variableVisible = true;
				opts.maxDimention = (opts[opts.dimensions[0]] == 'variable')
					? getTrueInnerSize($wrp.parent(), opts, 1)
					: opts[opts.dimensions[0]];
				if (opts.padding === false || opts.padding === 0) {
					opts[opts.dimensions[0]] = 'variable';
				}
				opts.items.visible = getVisibleItemsNext($cfs, opts, 0);
			} else {
				if (opts.padding === false) {
					opts.padding = 0;
				}
			}

			//	padding not set -> set to auto if primairy size is number
			if (typeof opts.padding == 'undefined') {
				opts.padding = (opts[opts.dimensions[0]] == 'variable')
					? 0
					: 'auto';
			}

			opts.items.oldVisible = opts.items.visible;
			opts.usePadding = false;

			//	auto-padding, only on fixed sizes
			if (opts.padding == 'auto') {
				opts.padding = [0,0,0,0];
				//	 for primairy size
				if (opts[opts.dimensions[0]] != 'variable') {
					opts.usePadding = 'auto';
					var p = getAutoPadding(getCurrentItems($cfs, opts), opts);
					opts.padding[opts.dimensions[10]] = p[0];
					opts.padding[opts.dimensions[12]] = p[0];
				}
				//	for secondairy size
				if (opts[opts.dimensions[3]] != 'variable') {
					var p = (opts[opts.dimensions[3]] - lrgst_b) / 2;
					if ( p < 0 ) p = 0;
					opts.padding[opts.dimensions[9]]  = p;
					opts.padding[opts.dimensions[11]] = p;
				}
			//	default-padding
			} else {
				opts.padding = getPadding(opts.padding);
				opts.usePadding = (
					opts.padding[0] == 0 && 
					opts.padding[1] == 0 && 
					opts.padding[2] == 0 && 
					opts.padding[3] == 0
				) ? false : true;
			}

			if (typeof opts.items.minimum	!= 'number')	opts.items.minimum		= (opts.variableVisible) ? 1 : opts.items.visible;
			if (typeof opts.scroll.items	!= 'number')	opts.scroll.items		= (opts.variableVisible) ? 'variable' : opts.items.visible;
			if (typeof opts.scroll.duration	!= 'number')	opts.scroll.duration	= 500;

			opts.auto		= getNaviObject(opts.auto, false, true);
			opts.prev		= getNaviObject(opts.prev);
			opts.next		= getNaviObject(opts.next);
			opts.pagination	= getNaviObject(opts.pagination, true);

			opts.auto		= $.extend(true, {}, opts.scroll, opts.auto);
			opts.prev		= $.extend(true, {}, opts.scroll, opts.prev);
			opts.next		= $.extend(true, {}, opts.scroll, opts.next);
			opts.pagination	= $.extend(true, {}, opts.scroll, opts.pagination);

			if (typeof opts.pagination.keys				!= 'boolean')	opts.pagination.keys 			= false;
			if (typeof opts.pagination.anchorBuilder	!= 'function')	opts.pagination.anchorBuilder	= $.fn.carouFredSel.pageAnchorBuilder;
			if (typeof opts.auto.play					!= 'boolean')	opts.auto.play					= true;
			if (typeof opts.auto.nap					!= 'boolean')	opts.auto.nap					= true;
			if (typeof opts.auto.delay					!= 'number')	opts.auto.delay					= 0;
			if (typeof opts.auto.pauseDuration			!= 'number')	opts.auto.pauseDuration			= (opts.auto.duration < 10) ? 2500 : opts.auto.duration * 5;

		};	//	/init

		$ttt.build = function() {
			if ($cfs.css('position') == 'absolute' || $cfs.css('position') == 'fixed') {
				log('Carousels CSS-attribute "position" should be "static" or "relative".');
			}
			$wrp.css({
				position	: 'relative',
				overflow	: 'hidden',
				marginTop	: $cfs.css('marginTop'),
				marginRight	: $cfs.css('marginRight'),
				marginBottom: $cfs.css('marginBottom'),
				marginLeft	: $cfs.css('marginLeft')
			});
			$cfs.data('cfs_origCss', {
				width		: $cfs.css('width'),
				height		: $cfs.css('height'),
				marginTop	: $cfs.css('marginTop'),
				marginRight	: $cfs.css('marginRight'),
				marginBottom: $cfs.css('marginBottom'),
				marginLeft	: $cfs.css('marginLeft'),
				'float'		: $cfs.css('float'),
				position	: $cfs.css('position'),
				top			: $cfs.css('top'),
				left		: $cfs.css('left')
			}).css({
				marginTop	: 0,
				marginRight	: 0,
				marginBottom: 0,
				marginLeft	: 0,
				'float'		: 'none',
				position	: 'absolute'
			});
			if (opts.usePadding) {
				getItems($cfs).each(function() {
					var m = parseInt($(this).css(opts.dimensions[8]));
					if (isNaN(m)) m = 0;
					$(this).data('cfs_origCssMargin', m);
				});
			}
		};	//	/build

		$ttt.bind_events = function() {
			$ttt.unbind_events();

			$cfs.bind('pause', function(e, g) {
				if (typeof g != 'boolean') g = false;
				if (g) pausedGlobal = true;
				if (autoTimeout != null)	clearTimeout(autoTimeout);
				if (autoInterval != null)	clearInterval(autoInterval);
				if (timerInterval != null)	clearInterval(timerInterval);
				
				var dur1 = opts.auto.pauseDuration - pauseTimePassed,
					perc = 100 - Math.ceil( dur1 * 100 / opts.auto.pauseDuration );

				if (perc != 0) {
					if (opts.auto.onPausePause) opts.auto.onPausePause.call($tt0, perc, dur1);
				}
			});

			$cfs.bind('play', function(e, d, f, g) {
				$cfs.trigger('pause');
				if (!opts.auto.play) return;

				if (typeof g != 'boolean') {
					if (typeof f == 'boolean') 		g = f;
					else if (typeof d == 'boolean')	g = d;
					else 							g = false;
				}
				if (typeof f != 'number') {
					if (typeof d == 'number')		f = d;
					else							f = 0;
				}
				if (d != 'prev' && d != 'next')		d = direction;

				if (g) pausedGlobal = false;
				if (pausedGlobal) return;

				var dur1 = opts.auto.pauseDuration - pauseTimePassed,
					dur2 = dur1 + f;
					perc = 100 - Math.ceil(dur1 * 100 / opts.auto.pauseDuration);

				autoTimeout = setTimeout(function() {
					if ($cfs.is(':animated')) {
						$cfs.trigger('play', d);
					} else {
						pauseTimePassed = 0;
						$cfs.trigger(d, opts.auto);
					}
				}, dur2);

				if (opts.auto.pauseOnHover === 'resume') {
					autoInterval = setInterval(function() {
						pauseTimePassed += 50;
					}, 50);
				}
				if (opts.auto.onPauseEnd && perc == 0) {
					opts.auto.onPauseEnd.call($tt0, perc, dur1);
				}
				if (opts.auto.onPauseStart) {
					timerInterval = setTimeout(function() {
						opts.auto.onPauseStart.call($tt0, perc, dur1);
					}, f);
				}
			});

			$cfs.bind('prev next', function(e) {
				if (pausedGlobal || $cfs.is(':animated') || $cfs.is(':hidden')) {
					e.stopImmediatePropagation();
					return;
				}
				if (opts.items.minimum >= totalItems) {
					log('Not enough items: not scrolling');
					e.stopImmediatePropagation();
					return;
				}
				pauseTimePassed = 0;
			});

			if (opts.variableVisible) {
				$cfs.bind('prev', function(e, sO, nI) {
					if (typeof sO == 'number') nI = sO;
					if (typeof sO != 'object') sO = opts.prev;
					if (typeof nI != 'number') nI = (typeof sO.items == 'number') ? sO.items : opts.items.visible;

					oI = nI;
					opts.items.oldVisible = opts.items.visible;

					var itm = getItems($cfs);
					if (opts.usePadding) {
						resetMargin(itm, opts);
					}
					opts.items.visible = getVisibleItemsPrev($cfs, opts, oI);
					nI = opts.items.visible - opts.items.oldVisible + oI;

					if (nI <= 0) {
						opts.items.visible = getVisibleItemsNext($cfs, opts, totalItems-oI);
						nI = oI;
					}
					if (opts.usePadding) {
						resetMargin(itm, opts, true);
					}

					$cfs.trigger('slidePrev', [sO, nI]);
				});
				$cfs.bind('next', function(e, sO, nI) {
					if (typeof sO == 'number') nI = sO;
					if (typeof sO != 'object') sO = opts.next;
					if (typeof nI != 'number') nI = (typeof sO.items == 'number') ? sO.items : opts.items.visible;

					opts.items.oldVisible = opts.items.visible;

					var itm = getItems($cfs);
					resetMargin(itm, opts);
					opts.items.visible = getVisibleItemsNext($cfs, opts, nI);

					if (opts.items.oldVisible - nI >= opts.items.visible) {
						opts.items.visible = getVisibleItemsNext($cfs, opts, ++nI);
					}
					resetMargin(itm, opts, true);
					$cfs.trigger('slideNext', [sO, nI]);
				});
			} else {
				$cfs.bind('prev', function(e, sO, nI) {
					$cfs.trigger('slidePrev', [sO, nI]);
				});
				$cfs.bind('next', function(e, sO, nI) {
					$cfs.trigger('slideNext', [sO, nI]);
				});
			}

			$cfs.bind('slidePrev', function(e, sO, nI) {
				if (typeof sO == 'number') nI = sO;
				if (typeof sO != 'object') sO = opts.prev;
				if (typeof nI != 'number') nI = (typeof sO.items == 'number') ? sO.items : opts.items.visible;
				if (typeof nI != 'number') return log('Not a valid number: not scrolling');

				if (sO.conditions && !sO.conditions.call($tt0)) return;

				if (!opts.circular) {
					var nulItem = totalItems - firstItem;
					if (nulItem - nI < 0) {
						nI = nulItem;
					}
					if (firstItem == 0) {
						nI = 0;
					}
				}

				firstItem += nI;
				if (firstItem >= totalItems) firstItem -= totalItems;

				if (!opts.circular) {
					if (firstItem == 0 && nI != 0 && sO.onEnd) sO.onEnd.call($tt0);
					if (opts.infinite) {
						if (nI == 0) {
							$cfs.trigger('next', totalItems-opts.items.visible);
							return;
						}
					} else enableNavi(opts, firstItem);
				}
				

				if (nI == 0) return;

				getItems($cfs, ':gt('+(totalItems-nI-1)+')').prependTo($cfs);
				if (totalItems < opts.items.visible + nI) getItems($cfs, ':lt('+((opts.items.visible+nI)-totalItems)+')').clone(true).appendTo($cfs);

				var c_old = getOldItemsPrev($cfs, opts, nI),
					c_new = getNewItemsPrev($cfs, opts),
					l_cur = getItems($cfs, ':eq('+(nI-1)+')'),
					l_old = c_old.filter(':last'),
					l_new = c_new.filter(':last');

				if (opts.usePadding) {
					resetMargin(l_old, opts);
					resetMargin(l_new, opts);
				}
				if (opts.usePadding == 'auto') {
					var p = getAutoPadding(getNewItemsPrev($cfs, opts, nI), opts);
				}

				var i_siz = getTotalSize(getItems($cfs, ':lt('+nI+')'), opts, 0),
					w_siz = mapWrapperSizes(getSizes(c_new, opts, true), opts, !opts.usePadding);

				if (opts.usePadding) {
					resetMargin(l_old, opts, opts.padding[opts.dimensions[10]]);
					resetMargin(l_cur, opts, opts.padding[opts.dimensions[12]]);
				}
				if (opts.usePadding == 'auto') {
					opts.padding[opts.dimensions[9]]  = p[1];
					opts.padding[opts.dimensions[10]] = p[0];
					opts.padding[opts.dimensions[11]] = p[1];
					opts.padding[opts.dimensions[12]] = p[0];
				}

				var a_cfs = {},
					a_new = {},
					a_cur = {},
					a_old = {},
					a_dur = sO.duration;

					 if (sO.fx == 'none')	a_dur = 0;
				else if (a_dur == 'auto')	a_dur = opts.scroll.duration / opts.scroll.items * nI;
				else if (a_dur <= 0)		a_dur = 0;
				else if (a_dur < 10)		a_dur = i_siz / a_dur;

				if (sO.onBefore) sO.onBefore.call($tt0, c_old, c_new, w_siz, a_dur);

				if (opts.usePadding) {
					var new_m = opts.padding[opts.dimensions[12]];
					a_cur[opts.dimensions[8]] = l_cur.data('cfs_origCssMargin');
					a_new[opts.dimensions[8]] = l_new.data('cfs_origCssMargin') + opts.padding[opts.dimensions[10]];
					a_old[opts.dimensions[8]] = l_old.data('cfs_origCssMargin');

					l_cur.stop().animate(a_cur, {
						duration: a_dur,
						easing	: sO.easing
					});
					l_new.stop().animate(a_new, {
						duration: a_dur,
						easing	: sO.easing
					});
					l_old.stop().animate(a_old, {
						duration: a_dur,
						easing	: sO.easing
					});
				} else {
					var new_m = 0;
				}
				a_cfs[opts.dimensions[6]] = new_m;

				if (opts[opts.dimensions[0]] == 'variable' || opts[opts.dimensions[3]] == 'variable') {
					$wrp.stop().animate(w_siz, {
						duration: a_dur,
						easing	: sO.easing
					});
				}

				//	alternative effects
				switch(sO.fx) {
					case 'crossfade':
					case 'cover':
					case 'uncover':
						var $cf2 = $cfs.clone(true).appendTo($wrp);
						break;
				}
				switch(sO.fx) {
					case 'uncover':
						getItems($cf2, ':lt('+nI+')').remove();
					case 'crossfade':
					case 'cover':
						getItems($cf2, ':gt('+(opts.items.oldVisible-1)+')').remove();
						break;
				}
				switch(sO.fx) {
					case 'fade':
						fx_fade(sO, $cfs, 0, a_dur);
						break;
					case 'crossfade':
						$cf2.css({ opacity: 0 });
						fx_fade(sO, $cf2, 1, a_dur);
						fx_fade(sO, $cfs, 1, a_dur, function() { $cf2.remove(); });
						break;
					case 'cover':
						fx_cover(sO, $cfs, $cf2, opts, a_dur, true);
						break;
					case 'uncover':
						fx_uncover(sO, $cf2, opts, a_dur, true);
						break;
				}
				switch(sO.fx) {
					case 'fade':
					case 'crossfade':
					case 'cover':
					case 'uncover':
						f_dur = a_dur;
						a_dur = 0;
						break;
				}
				//	/alternative effects

				var c_nI = nI;
				$cfs.css(opts.dimensions[6], -i_siz);
				$cfs.animate(a_cfs, {
					duration: a_dur,
					easing: sO.easing,
					complete: function() {
						var fn = (sO.onAfter) ? function() { sO.onAfter.call($tt0, c_old, c_new, w_siz); } : false;
						switch(sO.fx) {
							case 'fade':
								fx_fade(sO, $cfs, 1, f_dur, fn);
								break;
							case 'uncover':
								$cfs.animate({ opacity: '+=0' }, {
									duration: f_dur,
									complete: fn
								});
								break;
							default:
								if (fn) fn();
								break;
						}
						if (totalItems < opts.items.visible + nI) {
							getItems($cfs, ':gt('+(totalItems-1)+')').remove();
						}
						var l_itm = getItems($cfs, ':eq('+(opts.items.visible+nI-1)+')');
						if (opts.usePadding) l_itm.css(opts.dimensions[8], l_itm.data('cfs_origCssMargin'));
					}
				});
				$cfs.trigger('updatePageStatus').trigger('play', a_dur);
			});

			$cfs.bind('slideNext', function(e, sO, nI) {
				if (typeof sO == 'number') nI = sO;
				if (typeof sO != 'object') sO = opts.next;
				if (typeof nI != 'number') nI = (typeof sO.items == 'number') ? sO.items : opts.items.visible;
				if (typeof nI != 'number') return log('Not a valid number: not scrolling');

				if (sO.conditions && !sO.conditions.call($tt0)) return;

				if (!opts.circular) {
					if (firstItem == 0) {
						if (nI > totalItems - opts.items.visible) {
							nI = totalItems - opts.items.visible;
						}
					} else {
						if (firstItem - nI < opts.items.visible) {
							nI = firstItem - opts.items.visible;
						}
					}
				}

				firstItem -= nI;
				if (firstItem < 0) firstItem += totalItems;

				if (!opts.circular) {
					if (firstItem == opts.items.visible && nI != 0 && sO.onEnd) sO.onEnd.call($tt0);
					if (opts.infinite) {
						if (nI == 0) {
							$cfs.trigger('prev', totalItems-opts.items.visible);
							return;
						}
					} else enableNavi(opts, firstItem);
				}

				if (nI == 0) return;					
				if (totalItems < opts.items.visible + nI) getItems($cfs, ':lt('+((opts.items.visible+nI)-totalItems)+')').clone(true).appendTo($cfs);

				var c_old = getOldItemsNext($cfs, opts),
					c_new = getNewItemsNext($cfs, opts, nI),
					l_cur = c_old.filter(':eq('+(nI-1)+')'),
					l_old = c_old.filter(':last'),
					l_new = c_new.filter(':last');

				if (opts.usePadding) {
					resetMargin(l_old, opts);
					resetMargin(l_new, opts);
				}

				if (opts.usePadding == 'auto') {
					var p = getAutoPadding(getNewItemsNext($cfs, opts, nI), opts);
				}

				var i_siz = getTotalSize(getItems($cfs, ':lt('+nI+')'), opts, 0),
					w_siz = mapWrapperSizes(getSizes(c_new, opts, true), opts, !opts.usePadding);

				if (opts.usePadding) {
					resetMargin(l_old, opts, opts.padding[opts.dimensions[10]]);
					resetMargin(l_new, opts, opts.padding[opts.dimensions[10]]);
				}
				if (opts.usePadding == 'auto') {
					opts.padding[opts.dimensions[9]]  = p[1];
					opts.padding[opts.dimensions[10]] = p[0];
					opts.padding[opts.dimensions[11]] = p[1];
					opts.padding[opts.dimensions[12]] = p[0];
				}

				var a_cfs = {},
					a_old = {},
					a_cur = {},
					a_dur = sO.duration;

					 if (sO.fx == 'none')	a_dur = 0;
				else if (a_dur == 'auto')	a_dur = opts.scroll.duration / opts.scroll.items * nI;
				else if (a_dur <= 0)		a_dur = 0;
				else if (a_dur < 10)		a_dur = i_siz / a_dur;

				if (sO.onBefore) sO.onBefore.call($tt0, c_old, c_new, w_siz, a_dur);

				if (opts.usePadding) {
					a_old[opts.dimensions[8]] = l_old.data('cfs_origCssMargin');
					a_cur[opts.dimensions[8]] = l_cur.data('cfs_origCssMargin') + opts.padding[opts.dimensions[12]];
					l_new.css(opts.dimensions[8], l_new.data('cfs_origCssMargin') + opts.padding[opts.dimensions[10]]);

					l_old.stop().animate(a_old, {
						duration: a_dur,
						easing	: sO.easing
					});
					l_cur.stop().animate(a_cur, {
						duration: a_dur,
						easing	: sO.easing
					});
				}
				a_cfs[opts.dimensions[6]] = -i_siz;

				if (opts[opts.dimensions[0]] == 'variable' || opts[opts.dimensions[3]] == 'variable') {
					$wrp.stop().animate(w_siz, {
						duration: a_dur,
						easing	: sO.easing
					});
				}

				//	alternative effects
				switch(sO.fx) {
					case 'crossfade':
					case 'cover':
					case 'uncover':
						var $cf2 = $cfs.clone(true).appendTo($wrp);
						break;
				}
				switch(sO.fx) {
					case 'crossfade':
					case 'cover':
						getItems($cf2, ':lt('+nI+')').remove();
					case 'uncover':
						getItems($cf2, ':gt('+(opts.items.visible-1)+')').remove();
						break;
				}
				switch(sO.fx) {
					case 'fade':
						fx_fade(sO, $cfs, 0, a_dur);
						break;
					case 'crossfade':
						$cf2.css({ opacity: 0 });
						fx_fade(sO, $cf2, 1, a_dur);
						fx_fade(sO, $cfs, 1, a_dur, function() { $cf2.remove(); });
						break;
					case 'cover':
						fx_cover(sO, $cfs, $cf2, opts, a_dur, false);
						break;
					case 'uncover':
						fx_uncover(sO, $cf2, opts, a_dur, false);
						break;
				}
				switch(sO.fx) {
					case 'fade':
					case 'crossfade':
					case 'cover':
					case 'uncover':
						f_dur = a_dur;
						a_dur = 0;
						break;
				}
				//	/alternative effects

				var c_nI = nI;
				$cfs.animate(a_cfs, {
					duration: a_dur,
					easing	: sO.easing,
					complete: function() {
						var fn = (sO.onAfter) ? function() { sO.onAfter.call($tt0, c_old, c_new, w_siz); } : false;
						switch(sO.fx) {
							case 'fade':
								fx_fade(sO, $cfs, 1, f_dur, fn);
								break;
							case 'uncover':
								$cfs.animate({ opacity: '+=0' }, {
									duration: f_dur,
									complete: fn
								});
								break;
							default:
								if (fn) fn();
								break;
						}
						if (totalItems < opts.items.visible+c_nI) {
							getItems($cfs, ':gt('+(totalItems-1)+')').remove();
						}
						var org_m = (opts.usePadding) ? opts.padding[opts.dimensions[12]] : 0;
						$cfs.css(opts.dimensions[6], org_m);
						
						var l_itm = getItems($cfs, ':lt('+c_nI+')').appendTo($cfs).filter(':last');
						if (opts.usePadding) l_itm.css(opts.dimensions[8], l_itm.data('cfs_origCssMargin'));
					}
				});
				$cfs.trigger('updatePageStatus').trigger('play', a_dur);
			});

			$cfs.bind('slideTo', function(e, num, dev, org, obj) {
				if ($cfs.is(':animated')) return;

				num = getItemIndex(num, dev, org, firstItem, totalItems, $cfs);
				if (num == 0) return;
				if (typeof obj != 'object') obj = false;

				if (opts.circular) {
					if (num < totalItems / 2) 	$cfs.trigger('next', [obj, num]);
					else 						$cfs.trigger('prev', [obj, totalItems-num]);
				} else {
					if (firstItem == 0 ||
						firstItem > num)		$cfs.trigger('next', [obj, num]);
					else						$cfs.trigger('prev', [obj, totalItems-num]);
				}
			});

			$cfs.bind('insertItem', function(e, itm, num, org, dev) {
				if (typeof itm == 'object' && 
					typeof itm.jquery == 'undefined')	itm = $(itm);
				if (typeof itm == 'string') 			itm = $(itm);
				if (typeof itm != 'object' || 
					typeof itm.jquery == 'undefined' || 
					itm.length == 0) return log('Not a valid object.');

				if (typeof num == 'undefined' || num == 'end') {
					$cfs.append(itm);
				} else {
					num = getItemIndex(num, dev, org, firstItem, totalItems, $cfs);
					var $cit = getItems($cfs, ':eq('+num+')');
					if (opts.usePadding) {
						itm.each(function() {
							var m = parseInt($(this).css(opts.dimensions[8]));
							if (isNaN(m)) m = 0;
							$(this).data('cfs_origCssMargin', m);
						});
					}
					if ($cit.length) {
						if (num < firstItem) firstItem += itm.length;
						if (firstItem >= totalItems) firstItem -= totalItems;
						$cit.before(itm);
					} else {
						$cfs.append(itm);
					}
				}
				totalItems = getItems($cfs).length;
				$cfs.trigger('linkAnchors');
				setSizes($cfs, opts);
				showNavi(opts, totalItems);
				enableNavi(opts, firstItem);
				$cfs.trigger('updatePageStatus', true);
			});

			$cfs.bind('removeItem', function(e, num, org, dev) {
				if (typeof num == 'undefined' || num == 'end') {
					getItems($cfs, ':last').remove();
				} else {
					num = getItemIndex(num, dev, org, firstItem, totalItems, $cfs);
					var $cit = getItems($cfs, ':eq('+num+')');
					if ($cit.length){
						if (num < firstItem) firstItem -= $cit.length;
						$cit.remove();
					}
				}
				totalItems = getItems($cfs).length;
				setSizes($cfs, opts);
				showNavi(opts, totalItems);
				enableNavi(opts, firstItem);
				$cfs.trigger('updatePageStatus', true);
			});
			
			$cfs.bind('linkAnchors', function(e, $con, sel) {
				if (typeof $con == 'undefined' || $con.length == 0) $con = $('body');
				else if (typeof $con == 'string') $con = $($con);
				if (typeof $con != 'object') return log('Not a valid object.');
				if (typeof sel != 'string' || sel.length == 0) sel = 'a.caroufredsel';
				$con.find(sel).each(function() {
					var h = this.hash || '';
					if (h.length > 0 && getItems($cfs).index($(h)) != -1) {
						$(this).unbind('click').click(function(e) {
							e.preventDefault();
							$cfs.trigger('slideTo', h);
						});
					}
				});
			});

			$cfs.bind('currentPosition', function(e, fn) {
				if (firstItem == 0) var val = 0;
				else var val = totalItems - firstItem;
				if (typeof fn == 'function') fn.call($tt0, val);
			});

			$cfs.bind('configuration', function(e, a, b) {
				if (typeof a == 'function') {
					a.call($tt0, opts);
				} else if (typeof b == 'function') {
					var val = eval('opts.'+a);
					if (typeof val == 'undefined') val = '';
					b.call($tt0, val);
				} else if (typeof a != 'undefined' && typeof b != 'undefined') {
					eval('opts_orig.'+a+' = b');
					$ttt.init(opts_orig);
					setSizes($cfs, opts);
				}
			});

			$cfs.bind('destroy', function(e, orgOrder) {
				if (orgOrder) {
					$cfs.trigger('slideTo', [0, 0, true, { duration: 0 }]);
				}
				if (opts.usePadding) {
					getItems($cfs).each(function() {
						$(this).css(opts.dimensions[8], $(this).data('cfs_origCssMargin'));
					});
				}
				
				$cfs.trigger('pause').css($cfs.data('cfs_origCss'));
				$ttt.unbind_events();
				$ttt.unbind_buttons();
				$wrp.replaceWith($cfs);
			});

			$cfs.bind('updatePageStatus', function(e, build) {
				if (!opts.pagination.container) return;
				if (typeof build == 'boolean' && build) {
					getItems(opts.pagination.container).remove();
					for (var a = 0; a < Math.ceil(totalItems/opts.items.visible); a++) {
						var i = getItems($cfs, ':eq(' + getItemIndex(a*opts.items.visible, 0, true, firstItem, totalItems, $cfs) + ')');
						opts.pagination.container.append(opts.pagination.anchorBuilder(a+1, i));
					}
					getItems(opts.pagination.container).unbind('click').each(function(a) {
						$(this).click(function(e) {
							e.preventDefault();
							$cfs.trigger('slideTo', [a * opts.items.visible, 0, true, opts.pagination]);
						});
					});
				}
				var max = Math.ceil(totalItems/opts.items.visible-1);
				if (firstItem == 0) var nr = 0;
				else if (firstItem < totalItems % opts.items.visible) var nr = 0;
				else if (firstItem == opts.items.visible && !opts.circular) var nr = max;
				else var nr = Math.round((totalItems-firstItem)/opts.items.visible);
				if (nr < 0) nr = 0;
				if (nr > max) nr = max;
				getItems(opts.pagination.container).removeClass('selected').filter(':eq('+nr+')').addClass('selected');
			});
		};	//	/bind_events

		$ttt.unbind_events = function() {
			$cfs.unbind('pause')
				.unbind('play')
				.unbind('prev')
				.unbind('next')
				.unbind('slidePrev')
				.unbind('slideNext')
				.unbind('slideTo')
				.unbind('insertItem')
				.unbind('removeItem')
				.unbind('linkAnchors')
				.unbind('destroy')
				.unbind('updatePageStatus')
				.unbind('currentPosition')
				.unbind('configuration');
		};	//	/unbind_events

		$ttt.bind_buttons = function() {
			showNavi(opts, 'show');
			enableNavi(opts, firstItem);
			if (opts.auto.pauseOnHover && opts.auto.play) {
				$wrp.hover(
					function() { $cfs.trigger('pause'); },
					function() { $cfs.trigger('play');	}
				);
			}
			if (opts.prev.button) {
				opts.prev.button.click(function(e) {
					e.preventDefault();
					$cfs.trigger('prev');
				});
				if (opts.prev.pauseOnHover && opts.auto.play) {
					opts.prev.button.hover(
						function() { $cfs.trigger('pause');	},
						function() { $cfs.trigger('play');	}
					);
				}
			}
			if (opts.next.button) {
				opts.next.button.click(function(e) {
					e.preventDefault();
					$cfs.trigger('next');
				});
				if (opts.next.pauseOnHover && opts.auto.play) {
					opts.next.button.hover(
						function() { $cfs.trigger('pause');	},
						function() { $cfs.trigger('play');	}
					)
				}
			}
			if ($.fn.mousewheel) {
				if (opts.prev.mousewheel) {
					$wrp.mousewheel(function(e, delta) { 
						if (delta > 0) {
							e.preventDefault();
							num = (typeof opts.prev.mousewheel == 'number') ? opts.prev.mousewheel : '';
							$cfs.trigger('prev', num);
						}
					});
				}
				if (opts.next.mousewheel) {
					$wrp.mousewheel(function(e, delta) { 
						if (delta < 0) {
							e.preventDefault();
							num = (typeof opts.next.mousewheel == 'number') ? opts.next.mousewheel : '';
							$cfs.trigger('next', num);
						}
					});
				}
			}
			if (opts.pagination.container) {
				$cfs.trigger('updatePageStatus', true);
				if (opts.pagination.pauseOnHover && opts.auto.play) {
					opts.pagination.container.hover(
						function() { $cfs.trigger('pause');	},
						function() { $cfs.trigger('play');	}
					);
				}
			}
			if (opts.next.key || opts.prev.key) {
				$(document).keyup(function(e) {
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
				$(document).keyup(function(e) {
					var k = e.keyCode;
					if (k >= 49 && k < 58) {
						k = (k-49) * opts.items.visible;
						if (k <= totalItems) {
							e.preventDefault();
							$cfs.trigger('slideTo', [k, 0, true, opts.pagination]);
						}
					}
				});
			}
			if (opts.auto.play) {
				$cfs.trigger('play', opts.auto.delay);
				if ($.fn.nap && opts.auto.nap) {
					$cfs.nap('pause', 'play');
				}
			}
		};	//	/bind_buttons

		$ttt.unbind_buttons = function() {
			showNavi(opts, 'hide');
			enableNavi(opts, 'removeClass');
			if (opts.pagination.container) {
				getItems(opts.pagination.container).remove();
			}
		};



		//	DEPRECATED
		$ttt.configuration = function(a, b) {
			log('The "configuration" public method is deprecated, use the "configuration" custom event.');
			var cr = false;
			var fn = function(val) { cr = val; };
			if (!a) a = fn;
			if (!b) b = fn;
			$cfs.trigger('configuration', [a, b]);
			return cr;
		};
		$ttt.current_position = function() {
			log('The "current_position" public method is deprecated, use the "currentPosition" custom event.');
			var cp = false;
			$cfs.trigger('currentPosition', function(val) { cp = val; });
			return cp;
		};
		$ttt.destroy = function() {
			log('The "destroy" public method is deprecated, use the "destroy" custom event.');
			$cfs.trigger('destroy');
			return $ttt;
		};
		$ttt.link_anchors = function($c, se) {
			log('The "link_anchors" public method is deprecated, use the "linkAnchors" custom event.');
			$cfs.trigger('linkAnchors', [$c, se]);
			return $ttt;
		};
		//	/DEPRECATED




		if ($cfs.parent().is('.caroufredsel_wrapper')) {
			var $wrp = $cfs.parent();
			$cfs.trigger('destroy');
		}
		var $wrp			= $cfs.wrap('<div class="caroufredsel_wrapper" />').parent(),
			opts 			= {},
			opts_orig		= o,
			totalItems		= getItems($cfs).length,
			firstItem 		= 0,
			autoTimeout		= null,
			autoInterval	= null,
			timerInterval	= null,
			pauseTimePassed	= 0,
			pausedGlobal	= false,
			direction		= 'next';

		$ttt.init(opts_orig, true);
		$ttt.build();
		$ttt.bind_events();
		$ttt.bind_buttons();

		$cfs.trigger('linkAnchors');
		setSizes($cfs, opts, false);

		if (opts.items.start !== 0 && opts.items.start !== false) {
			var s = opts.items.start;
			if (s === true) {
				s = window.location.hash;
				if (!s.length) s = 0;
			}
			if (s === 'random') {
				s = Math.floor(Math.random() * totalItems);
			}
			$cfs.trigger('slideTo', [s, 0, true, { duration: 0 }]);
		}
		return this;
	};


	//	public
	$.fn.carouFredSel.defaults = {
		infinite: true,
		circular: true,
		direction: 'left',
		items: {
			start: 0
		},
		scroll: {
			easing: 'swing',
			pauseOnHover: false,
			mousewheel: false
		}
	};

	$.fn.carouFredSel.pageAnchorBuilder = function(nr, itm) {
		return '<a href="#"><span>'+nr+'</span></a>';
	};


	//	private
	function fx_fade(sO, c, x, d, f) {
		var o = {
			duration: d,
			easing: sO.easing
		};
		if (typeof f == 'function') o.complete = f;
		c.animate({
			opacity: x
		}, o);
	}
	function fx_cover(sO, c1, c2, o, d, prev) {
		var cur_w = getSizes(getItems(c2), o, true)[0],
			cur_p = (prev) ? -cur_w : cur_w,
			css_o = {},
			ani_o = {};
		css_o[o.dimensions[0]] = cur_w;
		css_o[o.dimensions[6]] = cur_p;
		ani_o[o.dimensions[6]] = 0;
		c1.animate({ opacity: '+=0' }, d);
		c2.css(css_o).animate(ani_o, {
			duration: d,
			easing: sO.easing,
			complete: function() {
				$(this).remove();
			}
		});
	}
	function fx_uncover(sO, c, o, d, prev) {
		var cur_w = getSizes(getItems(c), o, true)[0],
			cur_p = (prev) ? cur_w : -cur_w,
			css_o = {},
			ani_o = {};
		css_o[o.dimensions[0]] = cur_w;
		ani_o[o.dimensions[6]] = cur_p;
		c.css(css_o).animate(ani_o, {
			duration: d,
			easing: sO.easing,
			complete: function() {
				$(this).remove();
			}
		});
	}

	function showNavi(o, t) {
		if (t == 'show' || t == 'hide') {
			var f = t;
		} else if (o.items.minimum >= t) {
			log('Not enough items: not scrolling');
			var f = 'hide';
		} else {
			var f = 'show';
		}
		if (o.prev.button) o.prev.button[f]();
		if (o.next.button) o.next.button[f]();
		if (o.pagination.container) o.pagination.container[f]();
	}
	function enableNavi(o, f) {
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
	function getKeyCode(k) {
		if (k == 'right')	return 39;
		if (k == 'left')	return 37;
		if (k == 'up')		return 38;
		if (k == 'down')	return 40;
		return -1;
	}
	function getObject(obj) {
		if (typeof obj == 'undefined') obj = {};
		return obj;
	}
	function getNaviObject(obj, pagi, auto) {
		if (typeof pagi != 'boolean') pagi = false;
		if (typeof auto != 'boolean') auto = false;

		obj = getObject(obj);
		if (typeof obj == 'string') {
			var temp = getKeyCode(obj);
			if (temp == -1) obj = $(obj);
			else 			obj = temp;
		}

		//	pagination
		if (pagi) {
			if (typeof obj 				== 'boolean')	obj = { keys: obj };
			if (typeof obj.jquery 		!= 'undefined')	obj = { container: obj };
			if (typeof obj.container	== 'string')	obj.container = $(obj.container);

		//	auto
		} else if (auto) {
			if (typeof obj == 'boolean')				obj = { play: obj };
			if (typeof obj == 'number')					obj = { pauseDuration: obj };
			if (typeof obj.timer != 'object')			obj.timer = {};


		//	prev + next
		} else {
			if (typeof obj.jquery	!= 'undefined')		obj = { button: obj };
			if (typeof obj 			== 'number')		obj = { key: obj };
			if (typeof obj.button	== 'string')		obj.button = $(obj.button);
			if (typeof obj.key		== 'string')		obj.key = getKeyCode(obj.key);
		}
		return obj;
	}
	function getItemIndex(num, dev, org, firstItem, totalItems, $cfs) {
		if (typeof num == 'string') {
			if (isNaN(num)) num = $(num);
			else 			num = parseInt(num);
		}
		if (typeof num == 'object') {
			if (typeof num.jquery == 'undefined') num = $(num);
			num = getItems($cfs).index(num);
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
			num += firstItem;
		}
		num += dev;
		if (totalItems > 0) {
			while (num >= totalItems)	{	num -= totalItems; }
			while (num < 0)				{	num += totalItems; }
		}
		return num;
	}

	function getItems(c, f) {
		if (typeof f != 'string') f = '';
		return $('> *'+f, c);
	}
	function getCurrentItems(c, o) {
		return getItems(c, ':lt('+o.items.visible+')');
	}
	function getOldItemsPrev(c, o, n) {
		return getItems(c, ':lt('+(o.items.oldVisible+n)+'):gt('+(n-1)+')');
	}
	function getNewItemsPrev(c, o) {
		return getItems(c, ':lt('+o.items.visible+')');
	}
	function getOldItemsNext(c, o) {
		return getItems(c, ':lt('+o.items.oldVisible+')');
	}
	function getNewItemsNext(c, o, n) {
		return getItems(c, ':lt('+(o.items.visible+n)+'):gt('+(n-1)+')');
	}

	function resetMargin(i, o, m) {
		var x = (typeof m == 'boolean') ? m : false;
		if (typeof m != 'number') m = 0;
		i.each(function() {
			var t = parseInt($(this).css(o.dimensions[8]));
			if (isNaN(t)) t = 0;
			$(this).data('cfs_tempCssMargin', t);
			$(this).css(o.dimensions[8], ((x) ? $(this).data('cfs_tempCssMargin') : m + $(this).data('cfs_origCssMargin')));
		});
	}
	function getSizes(i, o, wrapper) {
		s1 = getTotalSize(i, o, 0, wrapper);
		s2 = getLargestSize(i, o, 3, wrapper);
		return [s1, s2];
	}
	function getLargestSize(i, o, dim, wrapper) {
		if (typeof wrapper != 'boolean') wrapper = false;
		if (typeof o[o.dimensions[dim]] == 'number' && wrapper) return o[o.dimensions[dim]];
		if (typeof o.items[o.dimensions[dim]] == 'number') return o.items[o.dimensions[dim]];
		return getTrueLargestSize(i, o, dim+2);
	}
	function getTrueLargestSize(i, o, dim) {
		var s = 0;
		i.each(function() {
			var m = $(this)[o.dimensions[dim]](true);
			if (s < m) s = m;
		});
		return s;
	}
	function getTrueInnerSize($el, o, dim) {
		var siz = $el[o.dimensions[dim]](),
			arr = (o.dimensions[dim].toLowerCase().indexOf('width') > -1) ? ['paddingLeft', 'paddingRight'] : ['paddingTop', 'paddingBottom'];
		for (a = 0; a < arr.length; a++) {
			var m = parseInt($el.css(arr[a]));
			if (isNaN(m)) m = 0;
			siz -= m;
		}
		return siz;
	}
	function getTotalSize(i, o, dim, wrapper) {
		if (typeof wrapper != 'boolean') wrapper = false;
		if (typeof o[o.dimensions[dim]] == 'number' && wrapper) return o[o.dimensions[dim]];
		if (typeof o.items[o.dimensions[dim]] == 'number') return o.items[o.dimensions[dim]] * i.length;
		return getTotalSizeVariable(i, o, dim+2);
	}
	function getTotalSizeVariable(i, o, dim) {
		var s = 0;
		i.each(function() { 
			s += $(this)[o.dimensions[dim]](true);
		});
		return s;
	}

	function hasVariableSizes(i, o, dim) {
		var s = false,
			v = false;
		i.each(function() { 
			c = $(this)[o.dimensions[dim]]();
			if (s === false) s = c;
			else if (s != c) v = true;
		});
		return v;
	}

	function mapWrapperSizes(ws, o, p) {
		if (typeof p != 'boolean') p = true;
		var pad = (o.usePadding && p) ? o.padding : [0, 0, 0, 0];
		var wra = {};
			wra[o.dimensions[0]] = ws[0] + pad[1] + pad[3];
			wra[o.dimensions[3]] = ws[1] + pad[0] + pad[2];

		return wra;
	}
	function setSizes($c, o, p) {
		var $w = $c.parent(),
			$i = getItems($c),
			$l = $i.filter(':eq('+(o.items.visible-1)+')');

		$w.css(mapWrapperSizes(getSizes($i.filter(':lt('+o.items.visible+')'), o, true), o, p));

		if (o.usePadding) {
			$l.css(o.dimensions[8], $l.data('cfs_origCssMargin') + o.padding[o.dimensions[10]]);
			$c.css(o.dimensions[7], o.padding[o.dimensions[9]]);
			$c.css(o.dimensions[6], o.padding[o.dimensions[12]]);
		}
		$c.css(o.dimensions[0], getTotalSize($i, o, 0)*2);
		$c.css(o.dimensions[3], getLargestSize($i, o, 3));
	}

	function getPadding(p) {
		if (typeof p == 'undefined') return [0, 0, 0, 0];
		
		if (typeof p == 'number') return [p, p, p, p];
		else if (typeof p == 'string') p = p.split('px').join('').split(' ');

		if (typeof p != 'object') {
			return [0, 0, 0, 0];
		}
		for (i in p) {
			p[i] = parseInt(p[i]);
		}
		switch (p.length) {
			case 0: return [0, 0, 0, 0];
			case 1: return [p[0], p[0], p[0], p[0]];
			case 2: return [p[0], p[1], p[0], p[1]];
			case 3: return [p[0], p[1], p[2], p[1]];
			default: return [p[0], p[1], p[2], p[3]];
		}
	}
	function getAutoPadding(itm, o) {
		var	wiz = (typeof o[o.dimensions[3]] == 'number') ? o[o.dimensions[3]] : getLargestSize(itm, o, 3);
		return [(o[o.dimensions[0]] - getTotalSize(itm, o, 0)) / 2, (wiz - getLargestSize(itm, o, 3)) / 2];
	}

	function getVisibleItemsPrev($c, o, nI) {
		var items = getItems($c),
			total = 0,
			start = o.items.visible - nI - 1,
			x = 0;

		if (start < 0) start = items.length-1;
		for (var a = start; a >= 0; a--) {
			total += items.filter(':eq('+ a +')')[o.dimensions[2]](true);
			if (total > o.maxDimention) return x;
			if (a == 0) a = items.length;
			x++;
		}
	}
	function getVisibleItemsNext($c, o, nI) {
		var items = getItems($c),
			total = 0,
			x = 0;

		for (var a = nI; a <= items.length-1; a++) {
			total += items.filter(':eq('+ a +')')[o.dimensions[2]](true);
			if (total > o.maxDimention) return x;
			if (a == items.length-1) a = -1;
			x++;
		}
	}

	function log(m) {
		if (typeof m == 'string') m = 'carouFredSel: ' + m;
		if (window.console && window.console.log) window.console.log(m);
		else try { console.log(m); } catch(err) { }
		return false;
	}



	$.fn.caroufredsel = function(o) {
		return this.carouFredSel(o);
	};

})(jQuery);