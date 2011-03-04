<?php

/***************************************************************************
 *            contentfiles.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
include_once('lib/filemanager.class.php');

class _contentFiles extends fileManager {
	var $picturesPreview = true;
	var $directLinks = true;
	var $adminPath = 'admin/content/contentfiles';
	
	function __construct() {
		parent::__construct();
		
		$this->rootPath = SITE_PATH.'sitefiles/';
		$this->uriRequest = "admin/content/contentfiles";
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
			__('Pages / Posts'), 
			'?path=' .
			(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));
		favoriteLinks::add(
			__('Template Files'), 
			'?path=admin/site/template/templateimages');
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Content Files'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$this->displayAdminTitle("sitefiles/". $this->selectedPath);
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
			
		$this->display();
		
		echo
			"</div>";
	}
}

?>