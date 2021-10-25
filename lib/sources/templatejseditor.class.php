<?php

/***************************************************************************
 *            templatejseditor.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/template.class.php');
include_once('lib/fileeditor.class.php');

class _templateJSEditor extends fileEditor {
	var $adminPath = 'admin/site/template/templatejseditor';

	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateJSEditor::templateJSEditor', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateJSEditor::templateJSEditor', $this, $handled);

			return $handled;
		}

		parent::__construct();

		$this->file = SITE_PATH.'template/template.js';
		$this->uriRequest = "admin/site/template/".$this->uriRequest;

		if (template::$selected)
			$this->file = SITE_PATH.'template/' .
				template::$selected['Name'].'/template.js';

		api::callHooks(API_HOOK_AFTER,
			'templateJSEditor::templateJSEditor', $this);
	}

	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateJSEditor::setupAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateJSEditor::setupAdmin', $this, $handled);

			return $handled;
		}

		favoriteLinks::add(
			__('CSS Editor'),
			'?path=admin/site/template/templatecsseditor');
		favoriteLinks::add(
			__('Template Files'),
			'?path=admin/site/template/templateimages');
		favoriteLinks::add(
			__('Pages / Posts'),
			'?path=' .
			(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));

		api::callHooks(API_HOOK_AFTER,
			'templateJSEditor::setupAdmin', $this);
	}

	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateJSEditor::displayAdminTitle', $this, $ownertitle);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateJSEditor::displayAdminTitle', $this, $ownertitle, $handled);

			return $handled;
		}

		admin::displayTitle(
			__('Template'),
			$ownertitle);

		api::callHooks(API_HOOK_AFTER,
			'templateJSEditor::displayAdminTitle', $this, $ownertitle);
	}

	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateJSEditor::displayAdminDescription', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateJSEditor::displayAdminDescription', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'templateJSEditor::displayAdminDescription', $this);
	}

	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateJSEditor::displayAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateJSEditor::displayAdmin', $this, $handled);

			return $handled;
		}

		$this->displayAdminTitle(__("JS Editor"));
		$this->displayAdminDescription();

		echo
			"<div class='admin-content'>";

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$this->display();
		else
			tooltip::display(
				__("Write access is required to access this area!"),
				TOOLTIP_NOTIFICATION);

		echo
			"</div>";

		api::callHooks(API_HOOK_AFTER,
			'templateJSEditor::displayAdmin', $this);
	}

	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateJSEditor::ajaxRequest', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateJSEditor::ajaxRequest', $this, $handled);

			return $handled;
		}

		if (!$GLOBALS['USER']->loginok ||
			!$GLOBALS['USER']->data['Admin'])
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'templateJSEditor::ajaxRequest', $this);

			return true;
		}

		$permission = userPermissions::check(
			(int)$GLOBALS['USER']->data['ID'],
			$this->adminPath);

		if (~$permission['PermissionType'] & USER_PERMISSION_TYPE_WRITE) {
			tooltip::display(
				__("You do not have permission to access this path!"),
				TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'templateJSEditor::ajaxRequest', $this);

			return true;
		}

		$result = parent::ajaxRequest();

		api::callHooks(API_HOOK_AFTER,
			'templateJSEditor::ajaxRequest', $this, $result);

		return $result;
	}
}

?>