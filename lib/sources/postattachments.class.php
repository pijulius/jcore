<?php

/***************************************************************************
 *            postattachments.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/attachments.class.php');

class _postAttachments extends attachments {
	var $sqlTable = 'postattachments';
	var $sqlRow = 'PostID';
	var $sqlOwnerTable = 'posts';
	var $adminPath = array(
		'admin/content/menuitems/posts/postattachments',
		'admin/content/pages/posts/postattachments',
		'admin/content/postsatglance/postattachments');
	
	function __construct() {
		api::callHooks(API_HOOK_BEFORE,
			'postAttachments::postAttachments', $this);
		
		parent::__construct();
		
		$this->selectedOwner = __('Post');
		$this->uriRequest = "posts/".$this->uriRequest;
		
		api::callHooks(API_HOOK_AFTER,
			'postAttachments::postAttachments', $this);
	}
	
	function ajaxRequest() {
		api::callHooks(API_HOOK_BEFORE,
			'postAttachments::ajaxRequest', $this);
		
		if (!posts::checkAccess($this->selectedOwnerID)) {
			$page = new pages();
			$page->displayLogin();
			unset($page);
			$result = true;
			
		} else {
			$result = parent::ajaxRequest();
		}
		
		api::callHooks(API_HOOK_AFTER,
			'postAttachments::ajaxRequest', $this, $result);
		
		return $result;
	}
}

?>