/*
 * InputNotes - jQuery plugin to add notes below textareas and input fields based on regex patterns
 * 
 * Copyright (c) 2010 Fredi Bach
 * www.fredibach.ch
 *
 * Usage:
 
	$("#texareaid").inputNotes( 
		{
			warning1: {
				pattern: /(^|\s)sex(\s|$)/ig,
				type: 'warning',
				text: 'Do not type "sex"!' 
			},
			note1: {
				pattern: /[0-9]/,
				type: 'note',
				text: 'Do not type numbers!' 
			}
		}
	);

 * Plugin page: http://fredibach.ch/jquery-plugins/inputnotes.php
 *
 */
(function($) {
	
	$.fn.inputNotes=function(notes) {
		
		var area = this;
		
		if (notes.config == undefined){
			var config = {};
		} else {
			var config = notes.config;
		}
		
		delete notes.config;
		
		if (config.containerTag == '' || config.containerTag == undefined){
			config.containerTag = 'div';
		}
		if (config.noteTag == '' || config.noteTag == undefined){
			config.noteTag = 'div';
		}
		var containerHTML = '<'+config.containerTag+' id="'+this.attr('id')+'_inputnotes" class="inputnotes"></'+config.containerTag+'>'; 
		if (config.notePosition == 'before'){
			area.before(containerHTML);
		} else {
			area.after(containerHTML);
		}
		var c = '#'+area.attr('id')+'_inputnotes'; // container id
		$(c).hide();
	
		var notebox = $('#'+area.attr('id')+'_inputnotes');
		notebox.css({ 'width': area.outerWidth() });
		
		showNotes();
		
		function showNotes(){
			var content = area.val();
			var removeItems = [];
			for(var item in notes){
				var n = notes[item];
				var itemdiv = c+' #note_'+item;
				if (n.inversedBehavior != true) { 
					n.inversedBehavior = false;
				};
				if ((!n.inversedBehavior && content.match(n.pattern)) || (n.inversedBehavior && !content.match(n.pattern))){
					if ($(itemdiv).size() == 0){
						$(c).append('<'+config.noteTag+' id="note_'+item+'" class="'+n.type+'">'+n.text+'</'+config.noteTag+'>');
						if ($(c).is(':hidden')){
							$(c).show();
						}
						$(area).trigger('inputnote_added',{ note: item });
						if (typeof n.addCallback == 'function'){
							n.addCallback.call(area,item,n.type);
						}
						$(itemdiv).hide().slideDown('fast');
					}
				} else {
					if ($(itemdiv).size() > 0){
						removeItems[item] = true;
					}
				}
			}
			for(var item in removeItems){
				var n = notes[item];
				var itemdiv = c+' #note_'+item;
				$(area).trigger('inputnote_removed',{ note: item });
				if (typeof n.removeCallback == 'function'){
					n.removeCallback.call(area,item,n.type);
				}
				$(itemdiv).slideUp('fast', function(){
					$(itemdiv).remove();
					if ($(c).html() == ''){
						$(c).hide();
					}
				});
			}
		}
	
		this.keyup(function(e){
			showNotes();
		});
		
		return this;
	
	};
	
	$.fn.hasInputNotes=function(type) {
		if ($(this).is('form')){
			var hasNotes = false;
			$(this).find('input, textarea').each(function(i) {
				if ($(this).attr('id')){
					if (type != undefined){
						if ($('#'+$(this).attr('id')+'_inputnotes div.'+type).size() > 0){ 
							hasNotes = true; 
						}
					} else {
						if ($('#'+$(this).attr('id')+'_inputnotes div').size() > 0){ 
							hasNotes = true;
						}
					}
				}
			});
			return hasNotes;
		} else {
			if (type != undefined){
				if ($('#'+this.attr('id')+'_inputnotes div.'+type).size() > 0){ 
					return true; 
				} else { 
					return false; 
				}
			} else {
				if ($('#'+this.attr('id')+'_inputnotes div').size() > 0){ 
					return true; 
				} else { 
					return false; 
				}
			}
		}
		return this;
	};
	
})(jQuery);