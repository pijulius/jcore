<?php

/***************************************************************************
 *            ckeditorfilemanager.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
include_once('lib/filemanager.class.php');

class _ckEditorFileManager extends fileManager {
	var $picturesPreview = true;
	var $directLinks = true;
	
	function __construct() {
		parent::__construct();
		
		$this->rootPath = SITE_PATH.'sitefiles/';
		$this->uriRequest = "ckeditor/".$this->uriRequest;
	}
	
	function ajaxRequest() {
		if (!$GLOBALS['USER']->loginok || 
			!$GLOBALS['USER']->data['Admin']) 
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		return parent::ajaxRequest();
	}
	
	function displayTitle(&$row) {
		$ckeditorfuncnum = 1;
		
		if (isset($_GET['CKEditorFuncNum']))
			$ckeditorfuncnum = (int)$_GET['CKEditorFuncNum'];
		
		if (!$row['_IsDir']) {
			echo
				"<a href='javascript://' " .
					"onclick=\"window.opener.CKEDITOR.tools.callFunction(" .
						$ckeditorfuncnum.", '" .
						$row['_URL']."');window.close();\" " .
					"title='".htmlspecialchars(sprintf(__("Link to %s"), 
						$row['_File']), ENT_QUOTES)."'>" .
					$row['_File'] .
				"</a>";
			
			return;
		}
		
		parent::displayTitle($row);
	}
	
	function display() {
		include_once('lib/userpermissions.class.php');
		
		$permission = userPermissions::check(
			(int)$GLOBALS['USER']->data['ID'],
			'admin/content/contentfiles');
		
		if (!$permission['PermissionType'])
			$permission = userPermissions::check(
				(int)$GLOBALS['USER']->data['ID'],
				(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));
		
		if (!$permission['PermissionType'])
			$permission = userPermissions::check(
				(int)$GLOBALS['USER']->data['ID'],
				'admin/content/postsatglance');
		
		if (~$permission['PermissionType'] & USER_PERMISSION_TYPE_WRITE)
			$this->readOnly = true;
		
		if (AJAX_PAGING && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
			parent::display();
			return;
		}
		
		echo 
			"<!DOCTYPE html>" .
			"<html>" .
			"<head>" .
			"<title>File Mananger - CKEditor - ".PAGE_TITLE."</title>";
		
		jQuery::display();
		css::display();
		
		echo
			"</head>" .
			"<body>" .
				"<div class='ckeditor-file-manager'>";
		
		parent::display();
		
		echo 
				"</div>";
				
		jQuery::displayPlugins();
		
		echo
			"</body>" .
			"</html>";
	}
}

?>