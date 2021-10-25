<?php

/***************************************************************************
 *            postcomments.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/comments.class.php');

class _postComments extends comments {
	var $sqlTable = 'postcomments';
	var $sqlRow = 'PostID';
	var $sqlOwnerTable = 'posts';
	var $adminPath = array(
		'admin/content/menuitems/posts/postcomments',
		'admin/content/pages/posts/postcomments',
		'admin/content/postsatglance/postcomments');

	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'postComments::postComments', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'postComments::postComments', $this, $handled);

			return $handled;
		}

		parent::__construct();

		$this->selectedOwner = __('Post');
		$this->uriRequest = "posts/".$this->uriRequest;

		api::callHooks(API_HOOK_AFTER,
			'postComments::postComments', $this);
	}

	static function getCommentURL($comment = null) {
		if ($comment)
			return posts::getPostURL($comment['PostID']);

		if (isset($GLOBALS['ADMIN']) && (bool)$GLOBALS['ADMIN'])
			return posts::getPostURL(admin::getPathID());

		return parent::getCommentURL();
	}

	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'postComments::ajaxRequest', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'postComments::ajaxRequest', $this, $handled);

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
			'postComments::ajaxRequest', $this, $result);

		return $result;
	}
}

?>