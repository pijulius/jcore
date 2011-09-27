$.fn.extend({
	dropDownMenu: function(){
		return this.each(function() {
			var jtag = $(this);
			jtag.find(".sub-menu").css({position: 'absolute', display: 'none'});
			
			jtag.mouseenter(function() {
				var leftx = 0;
				var topy = 0;
				var menu = $(this);
				var submenu = $(this).children('ul,div');
				
				if (menu.parent().css('position') != 'absolute') {
					leftx = menu.offset().left;
					topy = menu.offset().top+menu.height();
				} else {
					leftx = menu.width();
					topy = menu.position().top;
				}
				
				submenu.css({top: topy+'px', left: leftx+'px', position: 'absolute'});
				
				if (submenu.is(":animated")) {
					submenu.stop().css({'opacity': '', height: '', overflow: ''}).show();
				} else {
					submenu.animate({ delay:1 }, 100, function() {
							$(this).animate({height: 'show', opacity: 'show'}, 'fast');
						});
				}
			});
			
			jtag.mouseleave(function() {
				var submenu = $(this).children('ul,div');
				
				if (submenu.is(":animated")) {
					submenu.stop().css({'opacity': '', height: '', overflow: ''}).hide();
				} else {
					submenu.animate({ delay:1 }, 100, function() {
							$(this).animate({height: 'hide', opacity: 'hide'}, 'slow');
						});
				}
			});
		});
	}
});