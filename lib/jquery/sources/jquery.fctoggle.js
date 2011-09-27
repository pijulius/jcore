$.fn.extend({
	fcToggle: function() {
		var cookie = '';
		if (typeof($.cookie) != 'undefined')
			cookie = $.cookie('fcstates');
		
		//$.cookie('fcstates', '');
		if (cookie == null)
			cookie = '';
		
		function saveState(fcname) {
			if (typeof(fcname) != 'string')
				return;
			
			if (cookie.indexOf('|'+fcname.replace(/^fc/, '')) == -1)
				cookie = cookie+'|'+fcname.replace(/^fc/, '');
			else
				cookie = cookie.replace(new RegExp('\\|'+fcname.replace(/^fc/, ''), 'g'), "");
			
			if (typeof($.cookie) != 'undefined')
				$.cookie('fcstates', cookie, { expires: 10 });
		}
		
		function toggle(fc, preload) {
			fc = $(fc);
			
			if (fc.is(".expanded") || fc.parent().is(".expanded")) {
				fc.nextAll('.fc-content').stop().css({'opacity': '', 'height': ''}).animate({opacity: "hide", height: "hide"}, 
					(preload?0:"slow"), function() {
						if (fc.parent().is(".expanded"))
							fc.parent().removeClass("expanded");
						
						fc.removeClass("expanded").addClass("colapsed");
						
						if (!preload && fc.attr('name'))
							saveState(fc.attr('name'));
					});
			
			} else {
				fc.nextAll('.fc-content').stop().css({'opacity': '', 'height': ''}).animate({opacity: "show", height: "show"}, 
					(preload?0:"fast"), function() {
						fc.removeClass("colapsed").addClass("expanded");
						
						if (!preload && fc.attr('name'))
							saveState(fc.attr('name'));
					});
			}
		}
		
		return this.click(function(){
			toggle(this);
		}).parent().focus(function() {
			$(this).keypress(function(event) {
				if (event.which == '13' || event.which == '32' || event.which == '43' || event.which == '45') {
					toggle($(this).find('.fc-title:first'));
					return false;
				}
			});
		}).blur(function() {
			$(this).unbind('keypress');
		});
	}
});