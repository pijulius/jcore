jQuery.fn.ajaxLink = function(){
	jQuery(this).click(function(){
		var url = jQuery(this).attr('href');
		
		if (url.indexOf('ajax=') == -1) {
			if (url.indexOf('?') == -1)
				url = url+'?ajax=1';
			else
				url = url+'&ajax=1';
		}
		
		jQuery.get(url, function(data, textStatus){
			scripts = data.match(/<script.*?>.*?<\/script>/gi);
			data = data.replace(/<script.*?>.*?<\/script>/gi, '');
			
			for (var i in scripts)
				eval(scripts[0].replace(/<\/?script.*?>/gi, ''));
			
			jQuery.loading(false);
			jQuery.loading(true, {html: data, text: '', max: 5000});
		});
		
		return false;
	});
};

/* DEPRECATED! don't use it */
jQuery.fn.ajaxDownloadLink = function(){
	jQuery(this).click(function(){
		var url = jQuery(this).attr('href');
		
		if (url.indexOf('ajax=') == -1) {
			if (url.indexOf('?') == -1)
				url = url+'?ajax=1';
			else
				url = url+'&ajax=1';
		}
		
		jQuery.get(url+'&check=1', function(data, textStatus){
			error = data.match(/error/gi);
			jQuery.loading(true, {html: data, text: '', max: 5000});
			
			if (!error)
				window.location = url;
		});
		
		return false;
	});
};

jQuery.fn.ajaxPaging = function(){
	jQuery(this).click(function(){
		var contentholder = jQuery(this).parent().parent().parent().parent();
		var url = jQuery(this).attr('href');
		
		if (url.indexOf('ajax=') == -1) {
			if (url.indexOf('?') == -1)
				url = url+'?ajax=1';
			else
				url = url+'&ajax=1';
		}
			
		jQuery.get(url, function(data){
			contentholder.html(data).jCore();
			
			if (jQuery(window).scrollTop() > contentholder.offset().top)
				jQuery('html,body').animate({scrollTop: contentholder.offset().top-10}, 50, 'linear');
		});
		
		return false;
	});
};

jQuery.fn.ajaxContentLink = function(){
	jQuery(this).click(function(){
		var jthis = jQuery(this);
		var url = jthis.attr('href');
		var tooltip = jthis.attr('title');
		
		if (url.indexOf('ajax=') == -1) {
			if (url.indexOf('?') == -1)
				url = url+'?ajax=1';
			else
				url = url+'&ajax=1';
		}
		
		if (jthis.attr('target')) {
			var target = jQuery(jthis.attr('target'));
			
			jQuery.get(url, function(data){
				target.html(data).jCore();
			});
		
			return false;
		}
		
		if (JCORE_VERSION < '0.7') {
			if (typeof(jQuery(this).qtip) == 'undefined')
				return true;
		
			if (jQuery(this).data('qtip')) {
				jQuery(this).qtip("show");
				return false;
			}
			
			jQuery(this).qtip({
				content: { 
					url: url,
					title: {
						text: tooltip,
						button: '<span>Close</span>'
					}
				},
				style: { name: 'cream', tip: true, width: {max: 700} },
				position: {adjust: {screen: true}},
				show: false,
				hide: false,
				api: {onContentUpdate: function() {
					this.elements.content.jCore();
				}}
			});
			
			jQuery(this).qtip("show");
			return false;
		}
		
		if (typeof(jthis.tipsy) == 'undefined')
			return true;
		
		if (jthis.data('tipsy')) {
			if (jthis.data('tipsy').$tip.is(':visible')) {
				jthis.data('tipsy').$tip.hide();
			} else {
				jthis.data('tipsy').$tip.show();
				jthis.tipsy("update");
			}
			
			return false;
		}
		
		jthis.attr('title', 'Loading '+tooltip+' ... &nbsp; &nbsp; &nbsp;');
		
		jthis.tipsy({
			trigger: 'manual', 
			html: true,
			opacity: 1.0,
			additionalClassName: 'tipsy-big',
			closeButton: true,
			gravity: 'n'});
		
		jthis.tipsy("show");
		
		jQuery.get(url, function(data) {
				jthis.data('tipsy').$tip.find('.tipsy-inner')
					.html(data)
					.jCore();
				jthis.tipsy("update");
			});
		
		return false;
	});
};

jQuery.fn.ajaxLightboxLink = function(){
	jQuery(this).lightBox({
		hideDetails: true,
		disableNavigation: true,
		ajaxContent: true});
};