<?php

/***************************************************************************
 *            templateimages.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
include_once('lib/template.class.php');
include_once('lib/filemanager.class.php');

class _templateImages extends fileManager {
	var $adminPath = 'admin/site/template/templateimages';
	
	function __construct() {
		api::callHooks(API_HOOK_BEFORE,
			'templateImages::templateImages', $this);
		
		parent::__construct();
		
		$this->rootPath = SITE_PATH.'template/images/';
		$this->uriRequest = "admin/site/template/".$this->uriRequest;
		$this->picturesPreview = true;
		$this->directLinks = true;
		
		if (template::$selected)
			$this->rootPath = SITE_PATH.'template/' .
				template::$selected['Name'].'/images/';
		
		api::callHooks(API_HOOK_AFTER,
			'templateImages::templateImages', $this);
	}
	
	function setupAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'templateImages::setupAdmin', $this);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New File'),
				'?path='.admin::path().
					(url::getarg('dir')?
						'&amp;'.url::arg('dir'):
						null).
					'#form');
		
		favoriteLinks::add(
			__('CSS Editor'), 
			'?path=admin/site/template/templatecsseditor');
		favoriteLinks::add(
			__('JS Editor'), 
			'?path=admin/site/template/templatejseditor');
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=' .
			(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));
		favoriteLinks::add(
			__('Content Files'), 
			'?path=admin/content/contentfiles');
		
		api::callHooks(API_HOOK_AFTER,
			'templateImages::setupAdmin', $this);
	}
	
	function displayAdminTitle($ownertitle = null) {
		api::callHooks(API_HOOK_BEFORE,
			'templateImages::displayAdminTitle', $this, $ownertitle);
		
		$ownertitle = "<a href='?path=".admin::path()."'>images</a> / ";
		
		ob_start();
		$this->displayPath();
		$ownertitle .= ob_get_contents();
		ob_end_clean();
		
		admin::displayTitle(
			__('Template'),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'templateImages::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'templateImages::displayAdminDescription', $this);
		api::callHooks(API_HOOK_AFTER,
			'templateImages::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'templateImages::displayAdmin', $this);
		
		$this->displayAdminTitle();
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
			'templateImages::displayAdmin', $this);
	}
	
	function ajaxRequest() {
		api::callHooks(API_HOOK_BEFORE,
			'templateImages::ajaxRequest', $this);
		
		if (!$GLOBALS['USER']->loginok || 
			!$GLOBALS['USER']->data['Admin']) 
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'templateImages::ajaxRequest', $this);
			
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
				'templateImages::ajaxRequest', $this);
			
			return true;
		}
		
		$result = parent::ajaxRequest();
		
		api::callHooks(API_HOOK_AFTER,
			'templateImages::ajaxRequest', $this, $result);
		
		return $result;
	}
}

?>