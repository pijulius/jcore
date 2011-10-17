<?php

/***************************************************************************
 *            templatecsseditor.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
include_once('lib/template.class.php');
include_once('lib/fileeditor.class.php');

class _templateCSSEditor extends fileEditor {
	var $adminPath = 'admin/site/template/templatecsseditor';
	
	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateCSSEditor::templateCSSEditor', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateCSSEditor::templateCSSEditor', $this, $handled);
			
			return $handled;
		}
		
		parent::__construct();
		
		$this->file = SITE_PATH.'template/template.css';
		$this->uriRequest = "admin/site/template/".$this->uriRequest;
		
		if (template::$selected)
			$this->file = SITE_PATH.'template/' .
				template::$selected['Name'].'/template.css';
		
		api::callHooks(API_HOOK_AFTER,
			'templateCSSEditor::templateCSSEditor', $this);
	}
	
	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateCSSEditor::setupAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateCSSEditor::setupAdmin', $this, $handled);
			
			return $handled;
		}
		
		favoriteLinks::add(
			__('Template Files'), 
			'?path=admin/site/template/templateimages');
		favoriteLinks::add(
			__('JS Editor'), 
			'?path=admin/site/template/templatejseditor');
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=' .
			(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));
		
		api::callHooks(API_HOOK_AFTER,
			'templateCSSEditor::setupAdmin', $this);
	}
	
	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateCSSEditor::displayAdminTitle', $this, $ownertitle);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateCSSEditor::displayAdminTitle', $this, $ownertitle, $handled);
			
			return $handled;
		}
		
		admin::displayTitle(
			__('Template'),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'templateCSSEditor::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateCSSEditor::displayAdminDescription', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateCSSEditor::displayAdminDescription', $this, $handled);
			
			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'templateCSSEditor::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateCSSEditor::displayAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateCSSEditor::displayAdmin', $this, $handled);
			
			return $handled;
		}
		
		$this->displayAdminTitle(__("CSS Editor"));
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
			'templateCSSEditor::displayAdmin', $this);
	}
	
	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateCSSEditor::ajaxRequest', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateCSSEditor::ajaxRequest', $this, $handled);
			
			return $handled;
		}
		
		if (!$GLOBALS['USER']->loginok || 
			!$GLOBALS['USER']->data['Admin']) 
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'templateCSSEditor::ajaxRequest', $this);
			
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
				'templateCSSEditor::ajaxRequest', $this);
			
			return true;
		}
		
		$result = parent::ajaxRequest();
		
		api::callHooks(API_HOOK_AFTER,
			'templateCSSEditor::ajaxRequest', $this, $result);
		
		return $result;
	}
}

?>