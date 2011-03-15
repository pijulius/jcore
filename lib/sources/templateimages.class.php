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
		parent::__construct();
		
		$this->rootPath = SITE_PATH.'template/images/';
		$this->uriRequest = "admin/site/template&amp;filemanager=1";
		$this->picturesPreview = true;
		$this->directLinks = true;
		
		if (template::$selected)
			$this->rootPath = SITE_PATH.'template/' .
				template::$selected['Name'].'/images/';
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
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
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Template'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$this->displayAdminTitle(__("Images")." ".$this->selectedPath);
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