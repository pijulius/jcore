<?php

/***************************************************************************
 *            templatecsseditor.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
include_once('lib/template.class.php');
include_once('lib/fileeditor.class.php');

class _templateCSSEditor extends fileEditor {
	var $adminPath = 'admin/site/template/templatecsseditor';
	
	function __construct() {
		parent::__construct();
		
		$this->file = SITE_PATH.'template/template.css';
		$this->uriRequest = "admin/site/template&amp;csseditor=1";
		
		if (template::$selected)
			$this->file = SITE_PATH.'template/' .
				template::$selected['Name'].'/template.css';
	}
	
	function setupAdmin() {
		favoriteLinks::add(
			__('Template Files'), 
			'?path=admin/site/template/templateimages');
		favoriteLinks::add(
			__('JS Editor'), 
			'?path=admin/site/template/templatejseditor');
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=admin/content/pages');
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Template'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$this->displayAdminTitle(__("CSS Editor"));
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
			
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			$this->display();
		else
			tooltip::display(
				__("Write access is required to access this area!"),
				TOOLTIP_NOTIFICATION);
		
		echo
			"</div>";
	}
}

?>