/*
Copyright (c) 2003-2009, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	// config.language = 'en';
	//config.skin = 'office2003';
	
	config.uiColor = '#f7f7f7';
	config.height = '300px';
	config.dialog_backgroundCoverColor = '#000';
	config.dialog_backgroundCoverOpacity = 0.8; 
	
	config.extraPlugins = 'mediaembed';
	config.scayt_autoStartup = false;
	config.disableNativeSpellChecker = false;
	
 	config.toolbar =
	[
		['Image','Flash','MediaEmbed','Table','HorizontalRule','Smiley','SpecialChar','PageBreak','Templates'],
		['Link','Unlink','Anchor'],
		['Paste','PasteText','PasteFromWord'],
		['Undo','Redo','-','Find','Replace','-','SelectAll'],
		'/',
		['Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat'],
		['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
		['Source'],
		'/',
		['Styles','Format','Font','FontSize'],
		['TextColor','BGColor'],
		['Maximize', 'ShowBlocks','-','About']
	];
};
