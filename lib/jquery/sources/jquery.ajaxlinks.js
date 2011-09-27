$.ajaxParseScript = function (data) {
	var re  = /<script.*?>(.*?)<\/script>/ig;
	var scripts;
	
	while (scripts = re.exec(data)) {
		var scriptsrc = scripts[0].match(/src=('|")?(.*?)('|"| )/i);
		
		if (scriptsrc && $.trim(scriptsrc[2]) != "") {
			var rscript = document.createElement('script');
			rscript.type  = 'text/javascript';
			rscript.src = scriptsrc[2];
			$('body').append(rscript);
		}
		
		if ($.trim(scripts[1]) != "")
			eval(scripts[1]);
	}
}

$.fn.ajaxLink = function(){
	$(this).click(function(){
		var url = $(this).attr('href');
		
		if (url.indexOf('ajax=') == -1) {
			if (url.indexOf('?') == -1)
				url = url.replace(/#.*/, '')+'?ajax=1';
			else
				url = url.replace(/#.*/, '')+'&ajax=1';
		}
		
		$.get(url, function(data, textStatus){
			$.loading(false);
			$.loading(true, {
				html: data.replace(/<script.*?>.*?<\/script>/gi, ''), 
				text: '', max: 5000
			});
			$.ajaxParseScript(data);
		});
		
		return false;
	});
};

/* DEPRECATED! don't use it */
$.fn.ajaxDownloadLink = function(){
	$(this).click(function(){
		var url = $(this).attr('href');
		
		if (url.indexOf('ajax=') == -1) {
			if (url.indexOf('?') == -1)
				url = url.replace(/#.*/, '')+'?ajax=1';
			else
				url = url.replace(/#.*/, '')+'&ajax=1';
		}
		
		$.get(url+'&check=1', function(data, textStatus){
			error = data.match(/error/gi);
			$.loading(true, {html: data, text: '', max: 5000});
			
			if (!error)
				window.location = url;
		});
		
		return false;
	});
};

$.fn.ajaxPaging = function(){
	$(this).click(function(){
		var contentholder = $(this).parent().parent().parent().parent();
		var url = $(this).attr('href');
		
		if (url.indexOf('ajax=') == -1) {
			if (url.indexOf('?') == -1)
				url = url.replace(/#.*/, '')+'?ajax=1';
			else
				url = url.replace(/#.*/, '')+'&ajax=1';
		}
			
		$.get(url, function(data){
			contentholder.html(data).jCore();
			
			if ($(window).scrollTop() > contentholder.offset().top)
				$('html,body').animate({scrollTop: contentholder.offset().top-10}, 50, 'linear');
		});
		
		return false;
	});
};

$.fn.ajaxContentLink = function(){
	$(this).click(function(){
		var jthis = $(this);
		var url = jthis.attr('href');
		var tooltip = jthis.attr('title');
		
		if (url.indexOf('ajax=') == -1) {
			if (url.indexOf('?') == -1)
				url = url.replace(/#.*/, '')+'?ajax=1';
			else
				url = url.replace(/#.*/, '')+'&ajax=1';
		}
		
		if (jthis.attr('target')) {
			var target = $(jthis.attr('target'));
			
			$.get(url, function(data){
				target.html(data.replace(/<script.*?>.*?<\/script>/gi, '')).jCore();
				$.ajaxParseScript(data);
			});
		
			return false;
		}
		
		if (JCORE_VERSION < '0.7') {
			if (typeof($(this).qtip) == 'undefined')
				return true;
		
			if ($(this).data('qtip')) {
				$(this).qtip("show");
				return false;
			}
			
			$(this).qtip({
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
			
			$(this).qtip("show");
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
		
		$.get(url, function(data) {
				var target = jthis.data('tipsy').$tip.find('.tipsy-inner');
				target.html(data.replace(/<script.*?>.*?<\/script>/gi, '')).jCore();
				$.ajaxParseScript(data);
				jthis.tipsy("update");
			});
		
		return false;
	});
};

$.fn.ajaxLightboxLink = function(){
	$(this).lightBox({
		hideDetails: true,
		disableNavigation: true,
		ajaxContent: true});
};