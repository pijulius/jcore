jQuery.jCore = {
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
			jQuery.loading(false);
			
			if (html == true)
				jQuery.loading(true, {html: string, text: '', max: 5000});
			else
				jQuery.loading(true, {text: string, max: 5000});
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
					"<div class='form-entry form-entry-"+jQuery.jCore.url.genPathFromString(name)+"'>" +
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
							"<a href='javascript://' class='remove-link' onclick=\"jQuery.jCore.form.removeEntry(this);\">Remove</a>" +
						"</div>" +
					"</div>";
			
			jQuery(to).append(html);
			
			return true;
		},
		
		removeEntry: function(entry) {
			if (typeof(entry) == 'undefined')
				return false;
			
			jQuery(entry).closest('.form-entry').remove();
				
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
					return false;
				}
				return false;
			}
			return false;
		}
		return false;
	},
	
	modules: {}
};

jQuery.fn.extend({
	jCore: function() {
		return this.each(function() {
			// Display loading message on all ajax requests
			if (typeof(jQuery.loading) != 'undefined')
				jQuery.loading({onAjax:true, delay: 1000});
			
			// Frame containers expand/colapse
			if (typeof(jQuery(this).fcToggle) != 'undefined')
				jQuery(this).find(".fc-title").fcToggle();
		
			// Mail me emails
			if (typeof(jQuery(this).mailme) != 'undefined')
				jQuery(this).find('.mailme').mailme();
			
			// Star ratings
			if (typeof(jQuery(this).rating) != 'undefined')
				jQuery(this).find('.star-rating').rating();
			
			// Apply corners
			if (typeof(jQuery(this).corner) != 'undefined') {
				jQuery(this).find('.rounded-corners-top').corner('top 5px');
				jQuery(this).find('.rounded-corners-bottom').corner('bottom 5px');
				jQuery(this).find('.rounded-corners').corner('5px');
			}
			
			// Dropdown menus for the main menu
			if (typeof(jQuery(this).dropDownMenu) != 'undefined')
				jQuery(this).find('#main-menu .menu').dropDownMenu();
			
			if (typeof(JCORE_VERSION) == 'undefined' || JCORE_VERSION < '0.6') {
				// Hilight list rows
				jQuery(this).find('.list tbody tr').mouseenter(function() {
					jQuery(this).addClass('hilight');
				});
				jQuery(this).find('.list tbody tr').mouseleave(function() {
					jQuery(this).removeClass('hilight');
				});
			}
			
			// Confirm a link submittion
			jQuery(this).find('.confirm-link').click(function() {
				var jthis = jQuery(this);
				return confirm((
					jthis.attr('title') || 
					jthis.attr('original-title') || 
					jthis.attr('value') || 
					jthis.text() ||
					'Please confirm your action')+'?!');
			});
			
			// Ajax links
			if (typeof(jQuery(this).ajaxLink) != 'undefined')
				jQuery(this).find('.ajax-link').ajaxLink();
		
			// Ajax Download links
			if (typeof(jQuery(this).ajaxDownloadLink) != 'undefined')
				jQuery(this).find('.ajax-download-link').ajaxDownloadLink();
		
			// Ajax Forms
			if (typeof(jQuery(this).ajaxForm) != 'undefined')
				jQuery(this).find('form.ajax-form').ajaxForm();
			
			// Ajax content links
			if (typeof(jQuery(this).ajaxContentLink) != 'undefined')
				jQuery(this).find('.ajax-content-link').ajaxContentLink();
		
			// Ajax lightbox links
			if (typeof(jQuery(this).ajaxLightboxLink) != 'undefined')
				jQuery(this).find('.ajax-lightbox-link').ajaxLightboxLink();
		
			// Ajax paginating
			if (typeof(jQuery(this).ajaxPaging) != 'undefined')
				jQuery(this).find('.paging-ajax a').ajaxPaging();
			
			// Check all checkboxes
			jQuery(this).find(".checkbox-all").click(function(){
				var jthis = jQuery(this);
				var checked_status = this.checked;
				var parent_element = "form";
				
				if (jthis.attr('alt'))
					parent_element = jthis.attr('alt');
				
				jthis.parents(parent_element).find("input[type=checkbox]").each(function(){
					this.checked = checked_status;
				});
			});
			
			// Calendar Inputs
			if (typeof(jQuery(this).datepicker) != 'undefined') {
				jQuery(this).find(".calendar-input").each(function(){
					var dateformat = '';
					
					if (jQuery(this).is('.timestamp')) {
						var d = new Date();
						dateformat = 'yy-mm-dd '+d.getHours()+':'+d.getMinutes()+':00';
					} else {
						dateformat = 'yy-mm-dd';
					}
					
					jQuery(this)
						.datepicker({showAnim: 'fadeIn', dateFormat: dateformat})
						.after(jQuery("<a class='clear-calendar-input'>&nbsp;</a>")
							.click(function(){
									jQuery(this).parent().find('input').val('');
									return false;
								}))
						.after(jQuery("<a class='show-calendar-input'>&nbsp;</a>")
							.click(function(){
									jQuery(this).parent().find('input').focus();
									return false;
								}));
				});
			}
			
			// Color Inputs
			if (typeof(jQuery(this).ColorPicker) != 'undefined') {
				jQuery(this).find(".color-input").each(function() {
					jQuery(this)
						.ColorPicker({
							onBeforeShow: function () {
								$(this).ColorPickerSetColor(this.value);
							}})
						.after(jQuery("<a class='clear-color-input'>&nbsp;</a>")
							.click(function(){
								jQuery(this).parent().find('input').val('');
								return false;
							}))
						.after(jQuery("<a class='show-color-input'>&nbsp;</a>")
							.click(function(){
								jQuery(this).parent().find('input').focus();
								return false;
							}));
					});
			}
			
			// LightBox
			if (typeof(jQuery(this).lightBox) != 'undefined') {
				jQuery(function() {
					var boxen = [];
					
					jQuery(this).find('a[rel^=lightbox]').each(function() {
						if (typeof(boxen[this.rel]) == 'undefined') {
							boxen[this.rel] = this.rel;
							boxen.length++;
						}
					});
					
					if (boxen.length) {
						for (var key in boxen) {
							if (typeof(boxen[key]) == 'string')
								jQuery(this).find('a[rel="'+boxen[key].replace(/([^a-z0-9\-_])/g, "\$1")+'"]').lightBox();
						}
					}
				});
			}
			
			// Video LightBox
			if (typeof(jQuery(this).lightBox) != 'undefined') {
				jQuery(function() {
					var boxen = [];
					
					jQuery(this).find('a[rel^=videolightbox]').each(function() {
						if (typeof(boxen[this.rel]) == 'undefined') {
							boxen[this.rel] = this.rel;
							boxen.length++;
						}
					});
					
					if (boxen.length) {
						for (var key in boxen) {
							if (typeof(boxen[key]) == 'string')
								jQuery(this).find('a[rel="'+boxen[key].replace(/([^a-z0-9\-_])/g, "\$1")+'"]').lightBox({
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
			if (typeof(jQuery(this).qtip) != 'undefined')
				jQuery(this).find('.qtip').qtip({ style: { name: 'cream', tip: true }, position: {adjust: {screen: true}}});
			
			// Tipsy tooltips
			if (typeof(jQuery(this).tipsy) != 'undefined') {
				jQuery(this).find('.list td[title]').tipsy({delayIn: 100, html: true, gravity: 'w'});
				jQuery(this).find('.tip,.list a[title],.admin-section-item [title],.button[title],.attachment [title],.star-rating [title],.comment-rating-up,.comment-rating-down').tipsy({delayIn: 100, html: true});
				jQuery(this).find('.calendar-input[title],.timestamp[title],.color-input[title]').tipsy({trigger: 'focus', gravity: 'w', offset: 50});
				jQuery(this).find('input[type=file][title]').tipsy({delayIn: 100, trigger: 'hover', gravity: 'w'});
				jQuery(this).find('input[title],textarea[title],select[title]').tipsy({trigger: 'focus', gravity: 'w'});
			}
			
			// Code editor
			if (typeof(jQuery(this).tabby) != 'undefined')
				jQuery(this).find('textarea.code-editor').tabby();
			
			// Virtual Keypad for password fields
			if (typeof(jQuery(this).keypad) != 'undefined')
				jQuery(this).find('input[type="password"]').keypad(
					{showAnim: 'fadeIn', duration: 'fast', keypadOnly: false, layout: $.keypad.qwertyLayout});
		});
	}
});