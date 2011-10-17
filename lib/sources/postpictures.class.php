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
		$handled = api::callHooks(API_HOOK_BEFORE,
			'postPictures::postPictures', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'postPictures::postPictures', $this, $handled);
			
			return $handled;
		}
		
		parent::__construct();
		
		$this->selectedOwner = __('Post');
		$this->uriRequest = "posts/".$this->uriRequest;
		
		api::callHooks(API_HOOK_AFTER,
			'postPictures::postPictures', $this);
	}
	
	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'postPictures::ajaxRequest', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'postPictures::ajaxRequest', $this, $handled);
			
			return $handled;
		}
		
		if (!posts::checkAccess($this->selectedOwnerID)) {
			$page = new pages();
			$page->displayLogin();
			unset($page);
			$result = true;
			
		} else {
			$result = parent::ajaxRequest();
		}
		
		api::callHooks(API_HOOK_AFTER,
			'postPictures::ajaxRequest', $this, $result);
		
		return $result;
	}
}

?>