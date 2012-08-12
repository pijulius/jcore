$.jCore = {
	functions: Array(),
	plugins: Array(),
	
	extend: function(funcorsel, plugin) {
		if (typeof(funcorsel) == 'function') {
			$.jCore.functions[$.jCore.functions.length] = funcorsel;
			$(document).each(funcorsel);
			return true;
		}
		
		if (typeof(funcorsel) == 'string' && typeof(plugin) == 'undefined') {
			plugin = funcorsel;
			funcorsel = '';
		}
		
		$.jCore.plugins[$.jCore.plugins.length] = {selector: funcorsel, plugin: plugin};
	},
	
	url: {
		genPathFromString: function(string) {
			string = string.replace(/(<([^>]+)>)/ig,"");
			string = string.replace(' ', "-");
			string = string.replace(/([^a-z^0-9^_^-]*)/ig, "");
			 
			return string;
		}
	},
	
	tooltip: {
		display: function(string, html) {
			$.loading(false);
			
			if (html == true)
				$.loading(true, {html: string, text: '', max: 5000});
			else
				$.loading(true, {text: string, max: 5000});
		}
	},
	
	form: {
		INPUT_TYPE_TEXT: 1,
		INPUT_TYPE_EMAIL: 2,
		INPUT_TYPE_FILE: 12,
		
		appendEntryTo: function(to, title, name, type, required, value, attribute) {
			if (typeof(to) == 'undefined')
				return false;
				
			var html = '';
				
			html += 
					"<div class='form-entry form-entry-"+$.jCore.url.genPathFromString(name)+"'>" +
						"<div class='form-entry-title'>" +
							title +
						"</div>" +
						"<div class='form-entry-content'>";
						
			if (type == this.INPUT_TYPE_TEXT || 
				type == this.INPUT_TYPE_EMAIL)
				html += 
						"<input type='text' " +
							"name='"+name+"' " +
							"class='text-entry' " +
							"value='"+value+"' " +
							attribute+"/> ";
						
			else if (type == this.INPUT_TYPE_FILE)
				html += 
						"<input type='file' " +
							"name='"+name+"' " +
							"class='file-entry' " +
							"value='"+value+"' " +
							attribute+"/> ";
							
			else
				html += 
						"This type is not supported yet! ";
						
			html +=
							"<a href='javascript://' class='remove-link' onclick=\"$.jCore.form.removeEntry(this);\">Remove</a>" +
						"</div>" +
					"</div>";
			
			$(to).append(html);
			
			return true;
		},
		
		removeEntry: function(entry) {
			if (typeof(entry) == 'undefined')
				return false;
			
			$(entry).closest('.form-entry').remove();
				
			return true;
		}
	},
	
	hasFlash: function() {
		if (navigator.plugins && navigator.plugins.length) {
			var plugin = navigator.plugins['Shockwave Flash'];
			if (plugin)
				return true;

			if (navigator.plugins['Shockwave Flash 2.0'])
				return true;
		
  		} else if (navigator.mimeTypes && navigator.mimeTypes.length) {
			var mimeType = navigator.mimeTypes['application/x-shockwave-flash'];
			return mimeType && mimeType.enabledPlugin;

		} else {
			try {
				var ax = new ActiveXObject('ShockwaveFlash.ShockwaveFlash.7');
				return true;
			} catch (e) {
				try {
					var ax = new ActiveXObject('ShockwaveFlash.ShockwaveFlash.6');
					return true;
				} catch (e) {
					try {
						var ax = new ActiveXObject('ShockwaveFlash.ShockwaveFlash');
						return true;
					} catch (e) {
						return false;
					}
				}
			}
		}
		return false;
	},
	
	modules: {}
};

$.fn.extend({
	jCore: function() {
		this.each(function() {
			var jthis = $(this);
			
			// Display loading message on all ajax requests
			if (typeof($.loading) != 'undefined')
				$.loading({onAjax:true, delay: 1000});
			
			// Frame containers expand/colapse
			if (typeof($().fcToggle) != 'undefined')
				jthis.find(".fc-title").fcToggle();
		
			// Mail me emails
			if (typeof($().mailme) != 'undefined')
				jthis.find('.mailme').mailme();
			
			// Star ratings
			if (typeof($().rating) != 'undefined')
				jthis.find('.star-rating').rating();
			
			// Apply corners
			if (typeof($().corner) != 'undefined') {
				jthis.find('.rounded-corners-top').corner('top 5px');
				jthis.find('.rounded-corners-bottom').corner('bottom 5px');
				jthis.find('.rounded-corners').corner('5px');
			}
			
			// Dropdown menus for the main menu
			if (typeof($().dropDownMenu) != 'undefined')
				jthis.find('#main-menu .menu').dropDownMenu();
			
			if (typeof(JCORE_VERSION) == 'undefined' || JCORE_VERSION < '0.6') {
				// Hilight list rows
				jthis.find('.list tbody tr').mouseenter(function() {
					$(this).addClass('hilight');
				});
				jthis.find('.list tbody tr').mouseleave(function() {
					$(this).removeClass('hilight');
				});
			}
			
			// Confirm a link submittion
			jthis.find('.confirm-link').click(function() {
				var jthis = $(this);
				var confirmed = confirm((
					jthis.attr('title') || 
					jthis.attr('original-title') || 
					jthis.attr('value') || 
					jthis.text() ||
					'Please confirm your action')+'?!');
				
				if (confirmed && this.tagName.toLowerCase() == 'a') {
					var faction = jthis.attr('href');
					var fvariable = faction.match(/.*&(.*?)=(.*?)$/);
					
					if (fvariable && fvariable[1] && fvariable[2]) {
						var jform = $(
							"<form action='"+faction+"' method='post'>"+
							"<input type='hidden' name='_SecurityToken' value='"+JCORE_SECURITY_TOKEN+"' />"+
							"<input type='hidden' name='"+fvariable[1]+"' value='"+fvariable[2]+"' />"+
							"</form>");
						
						$('body').append(jform);
						jform.submit();
						
						return false;
					}
				}
				
				return confirmed;
			});
			
			// Ajax links
			if (typeof($().ajaxLink) != 'undefined')
				jthis.find('.ajax-link').ajaxLink();
		
			// Ajax Download links
			if (typeof($().ajaxDownloadLink) != 'undefined')
				jthis.find('.ajax-download-link').ajaxDownloadLink();
		
			// Ajax Forms
			if (typeof($().ajaxForm) != 'undefined')
				jthis.find('form.ajax-form').ajaxForm();
			
			// Ajax content links
			if (typeof($().ajaxContentLink) != 'undefined')
				jthis.find('.ajax-content-link').ajaxContentLink();
		
			// Ajax lightbox links
			if (typeof($().ajaxLightboxLink) != 'undefined')
				jthis.find('.ajax-lightbox-link').ajaxLightboxLink();
		
			// Ajax paginating
			if (typeof($().ajaxPaging) != 'undefined')
				jthis.find('.paging-ajax a').ajaxPaging();
			
			// Check all checkboxes
			jthis.find(".checkbox-all").click(function(){
				var jthis = $(this);
				var checked_status = this.checked;
				var parent_element = "form";
				
				if (jthis.attr('alt'))
					parent_element = jthis.attr('alt');
				
				jthis.parents(parent_element).find("input[type=checkbox]").each(function(){
					this.checked = checked_status;
				});
			});
			
			// Calendar Inputs
			if (typeof($().datepicker) != 'undefined') {
				jthis.find(".calendar-input").each(function(){
					var dateformat = '';
					var jthis = $(this);
					
					if (jthis.is('.timestamp')) {
						var d = new Date();
						dateformat = 'yy-mm-dd '+d.getHours()+':'+d.getMinutes()+':00';
					} else {
						dateformat = 'yy-mm-dd';
					}
					
					jthis
						.datepicker({showAnim: 'fadeIn', dateFormat: dateformat})
						.after($("<a class='clear-calendar-input'>&nbsp;</a>")
							.click(function(){
									$(this).parent().find('input').val('');
									return false;
								}))
						.after($("<a class='show-calendar-input'>&nbsp;</a>")
							.click(function(){
									$(this).parent().find('input').focus();
									return false;
								}));
				});
			}
			
			// Color Inputs
			if (typeof($().ColorPicker) != 'undefined') {
				jthis.find(".color-input").each(function() {
					$(this)
						.ColorPicker({
							onBeforeShow: function () {
								$(this).ColorPickerSetColor(this.value);
							}})
						.after($("<a class='clear-color-input'>&nbsp;</a>")
							.click(function(){
								$(this).parent().find('input').val('');
								return false;
							}))
						.after($("<a class='show-color-input'>&nbsp;</a>")
							.click(function(){
								$(this).parent().find('input').focus();
								return false;
							}));
					});
			}
			
			// LightBox
			if (typeof($().lightBox) != 'undefined') {
				$(function() {
					var boxen = [];
					
					jthis.find('a[rel^=lightbox]').each(function() {
						if (typeof(boxen[this.rel]) == 'undefined') {
							boxen[this.rel] = this.rel;
							boxen.length++;
						}
					});
					
					if (boxen.length) {
						for (var key in boxen) {
							if (typeof(boxen[key]) == 'string')
								jthis.find('a[rel="'+boxen[key].replace(/([^a-z0-9\-_])/g, "\$1")+'"]').lightBox();
						}
					}
				});
			}
			
			// Video LightBox
			if (typeof($().lightBox) != 'undefined') {
				$(function() {
					var boxen = [];
					
					jthis.find('a[rel^=videolightbox]').each(function() {
						if (typeof(boxen[this.rel]) == 'undefined') {
							boxen[this.rel] = this.rel;
							boxen.length++;
						}
					});
					
					if (boxen.length) {
						for (var key in boxen) {
							if (typeof(boxen[key]) == 'string')
								jthis.find('a[rel="'+boxen[key].replace(/([^a-z0-9\-_])/g, "\$1")+'"]').lightBox({
									disableSlideShow: true,
									disableDownload: true,
									ajaxContent: true,
									fixedNavigation: true,
									txtImage: 'Video'});
						}
					}
				});
			}
			
			// qTooltips
			if (typeof($().qtip) != 'undefined')
				jthis.find('.qtip').qtip({ style: { name: 'cream', tip: true }, position: {adjust: {screen: true}}});
			
			// Tipsy tooltips
			if (typeof($().tipsy) != 'undefined') {
				jthis.find('.list td[title]').tipsy({delayIn: 100, html: true, gravity: 'w'});
				jthis.find('.tip,.list a[title],.admin-section-item [title],.button[title],.attachment [title],.star-rating [title],.comment-rating-up,.comment-rating-down').tipsy({delayIn: 100, html: true});
				jthis.find('.calendar-input[title],.timestamp[title],.color-input[title]').tipsy({trigger: 'focus', gravity: 'w', offset: 50});
				jthis.find('input[type=file][title]').tipsy({delayIn: 100, trigger: 'hover', gravity: 'w'});
				jthis.find('input[title],textarea[title],select[title]').tipsy({trigger: 'focus', gravity: 'w'});
			}
			
			// Code editor
			if (typeof($().tabby) != 'undefined')
				jthis.find('textarea.code-editor').tabby();
			
			// Virtual Keypad for password fields
			if (typeof($().keypad) != 'undefined')
				jthis.find('input[type="password"]').keypad(
					{showAnim: 'fadeIn', duration: 'fast', keypadOnly: false, layout: $.keypad.qwertyLayout});
		});
			
		// Run plugins and functions
		if($.jCore.plugins.length > 0) {
			for (var i = 0; i < $.jCore.plugins.length; i++) {
				if (!$.jCore.plugins[i].selector) {
					this[$.jCore.plugins[i].plugin]();
					continue;
				}
				
				if (typeof($.jCore.plugins[i].plugin) == 'function') {
					this.find($.jCore.plugins[i].selector).each($.jCore.plugins[i].plugin);
				} else if (typeof($.fn[$.jCore.plugins[i].plugin]) != 'undefined') {
					this.find($.jCore.plugins[i].selector)[$.jCore.plugins[i].plugin]();
				}
			}
		}
		
		if ($.jCore.functions.length > 0) {
			for (var i = 0; i < $.jCore.functions.length; i++) {
				this.each($.jCore.functions[i]);
			}
		}
		
		return this;
	}
});