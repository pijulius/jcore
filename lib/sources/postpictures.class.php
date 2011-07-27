<?php

/***************************************************************************
 *            postpictures.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/pictures.class.php');

class _postPictures extends pictures {
	var $sqlTable = 'postpictures';
	var $sqlRow = 'PostID';
	var $sqlOwnerTable = 'posts';
	var $adminPath = array(
		'admin/content/menuitems/posts/postpictures',
		'admin/content/pages/posts/postpictures',
		'admin/content/postsatglance/postpictures');
	
	function __construct() {
		parent::__construct();
		
		$this->selectedOwner = __('Post');
		$this->uriRequest = "posts/".$this->uriRequest;
	}
	
	function ajaxRequest() {
		if (!posts::checkAccess($this->selectedOwnerID)) {
			$page = new pages();
			$page->displayLogin();
			unset($page);
			return true;
		}
		
		return parent::ajaxRequest();
	}
}

?>