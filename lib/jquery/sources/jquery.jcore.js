$.jCore = {
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
		return this.each(function() {
			// Display loading message on all ajax requests
			if (typeof($.loading) != 'undefined')
				$.loading({onAjax:true, delay: 1000});
			
			// Frame containers expand/colapse
			if (typeof($(this).fcToggle) != 'undefined')
				$(this).find(".fc-title").fcToggle();
		
			// Mail me emails
			if (typeof($(this).mailme) != 'undefined')
				$(this).find('.mailme').mailme();
			
			// Star ratings
			if (typeof($(this).rating) != 'undefined')
				$(this).find('.star-rating').rating();
			
			// Apply corners
			if (typeof($(this).corner) != 'undefined') {
				$(this).find('.rounded-corners-top').corner('top 5px');
				$(this).find('.rounded-corners-bottom').corner('bottom 5px');
				$(this).find('.rounded-corners').corner('5px');
			}
			
			// Dropdown menus for the main menu
			if (typeof($(this).dropDownMenu) != 'undefined')
				$(this).find('#main-menu .menu').dropDownMenu();
			
			if (typeof(JCORE_VERSION) == 'undefined' || JCORE_VERSION < '0.6') {
				// Hilight list rows
				$(this).find('.list tbody tr').mouseenter(function() {
					$(this).addClass('hilight');
				});
				$(this).find('.list tbody tr').mouseleave(function() {
					$(this).removeClass('hilight');
				});
			}
			
			// Confirm a link submittion
			$(this).find('.confirm-link').click(function() {
				var jthis = $(this);
				return confirm((
					jthis.attr('title') || 
					jthis.attr('original-title') || 
					jthis.attr('value') || 
					jthis.text() ||
					'Please confirm your action')+'?!');
			});
			
			// Ajax links
			if (typeof($(this).ajaxLink) != 'undefined')
				$(this).find('.ajax-link').ajaxLink();
		
			// Ajax Download links
			if (typeof($(this).ajaxDownloadLink) != 'undefined')
				$(this).find('.ajax-download-link').ajaxDownloadLink();
		
			// Ajax Forms
			if (typeof($(this).ajaxForm) != 'undefined')
				$(this).find('form.ajax-form').ajaxForm();
			
			// Ajax content links
			if (typeof($(this).ajaxContentLink) != 'undefined')
				$(this).find('.ajax-content-link').ajaxContentLink();
		
			// Ajax lightbox links
			if (typeof($(this).ajaxLightboxLink) != 'undefined')
				$(this).find('.ajax-lightbox-link').ajaxLightboxLink();
		
			// Ajax paginating
			if (typeof($(this).ajaxPaging) != 'undefined')
				$(this).find('.paging-ajax a').ajaxPaging();
			
			// Check all checkboxes
			$(this).find(".checkbox-all").click(function(){
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
			if (typeof($(this).datepicker) != 'undefined') {
				$(this).find(".calendar-input").each(function(){
					var dateformat = '';
					
					if ($(this).is('.timestamp')) {
						var d = new Date();
						dateformat = 'yy-mm-dd '+d.getHours()+':'+d.getMinutes()+':00';
					} else {
						dateformat = 'yy-mm-dd';
					}
					
					$(this)
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
			if (typeof($(this).ColorPicker) != 'undefined') {
				$(this).find(".color-input").each(function() {
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
			if (typeof($(this).lightBox) != 'undefined') {
				$(function() {
					var boxen = [];
					
					$(this).find('a[rel^=lightbox]').each(function() {
						if (typeof(boxen[this.rel]) == 'undefined') {
							boxen[this.rel] = this.rel;
							boxen.length++;
						}
					});
					
					if (boxen.length) {
						for (var key in boxen) {
							if (typeof(boxen[key]) == 'string')
								$(this).find('a[rel="'+boxen[key].replace(/([^a-z0-9\-_])/g, "\$1")+'"]').lightBox();
						}
					}
				});
			}
			
			// Video LightBox
			if (typeof($(this).lightBox) != 'undefined') {
				$(function() {
					var boxen = [];
					
					$(this).find('a[rel^=videolightbox]').each(function() {
						if (typeof(boxen[this.rel]) == 'undefined') {
							boxen[this.rel] = this.rel;
							boxen.length++;
						}
					});
					
					if (boxen.length) {
						for (var key in boxen) {
							if (typeof(boxen[key]) == 'string')
								$(this).find('a[rel="'+boxen[key].replace(/([^a-z0-9\-_])/g, "\$1")+'"]').lightBox({
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
			if (typeof($(this).qtip) != 'undefined')
				$(this).find('.qtip').qtip({ style: { name: 'cream', tip: true }, position: {adjust: {screen: true}}});
			
			// Tipsy tooltips
			if (typeof($(this).tipsy) != 'undefined') {
				$(this).find('.list td[title]').tipsy({delayIn: 100, html: true, gravity: 'w'});
				$(this).find('.tip,.list a[title],.admin-section-item [title],.button[title],.attachment [title],.star-rating [title],.comment-rating-up,.comment-rating-down').tipsy({delayIn: 100, html: true});
				$(this).find('.calendar-input[title],.timestamp[title],.color-input[title]').tipsy({trigger: 'focus', gravity: 'w', offset: 50});
				$(this).find('input[type=file][title]').tipsy({delayIn: 100, trigger: 'hover', gravity: 'w'});
				$(this).find('input[title],textarea[title],select[title]').tipsy({trigger: 'focus', gravity: 'w'});
			}
			
			// Code editor
			if (typeof($(this).tabby) != 'undefined')
				$(this).find('textarea.code-editor').tabby();
			
			// Virtual Keypad for password fields
			if (typeof($(this).keypad) != 'undefined')
				$(this).find('input[type="password"]').keypad(
					{showAnim: 'fadeIn', duration: 'fast', keypadOnly: false, layout: $.keypad.qwertyLayout});
		});
	}
});