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
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ckEditorFileManager::ckEditorFileManager', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ckEditorFileManager::ckEditorFileManager', $this, $handled);

			return $handled;
		}

		parent::__construct();

		$this->rootPath = SITE_PATH.'sitefiles/';
		$this->uriRequest = "ckeditor/".$this->uriRequest;

		api::callHooks(API_HOOK_AFTER,
			'ckEditorFileManager::ckEditorFileManager', $this);
	}

	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ckEditorFileManager::ajaxRequest', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ckEditorFileManager::ajaxRequest', $this, $handled);

			return $handled;
		}

		if (!$GLOBALS['USER']->loginok ||
			!$GLOBALS['USER']->data['Admin'])
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);

			$result = false;

		} else {
			$result = parent::ajaxRequest();
		}

		api::callHooks(API_HOOK_AFTER,
			'ckEditorFileManager::ajaxRequest', $this, $result);

		return $result;
	}

	function displayTitle(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ckEditorFileManager::displayTitle', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ckEditorFileManager::displayTitle', $this, $row, $handled);

			return $handled;
		}

		$ckeditorfuncnum = 1;

		if (isset($_GET['CKEditorFuncNum']))
			$ckeditorfuncnum = (int)$_GET['CKEditorFuncNum'];

		if (!$row['_IsDir']) {
			echo
				"<a href='javascript://' " .
					"onclick=\"window.opener.CKEDITOR.tools.callFunction(" .
						$ckeditorfuncnum.", '" .
						$row['_URL']."');window.close();\" " .
					"title='".htmlchars(sprintf(__("Link to %s"),
						$row['_File']), ENT_QUOTES)."'>" .
					$row['_File'] .
				"</a>";

		} else {
			parent::displayTitle($row);
		}

		api::callHooks(API_HOOK_AFTER,
			'ckEditorFileManager::displayTitle', $this, $row);
	}

	function display() {
		include_once('lib/userpermissions.class.php');

		$handled = api::callHooks(API_HOOK_BEFORE,
			'ckEditorFileManager::display', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ckEditorFileManager::display', $this, $handled);

			return $handled;
		}

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

			api::callHooks(API_HOOK_AFTER,
				'ckEditorFileManager::display', $this);

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

		api::callHooks(API_HOOK_AFTER,
			'ckEditorFileManager::display', $this);
	}
}

?>