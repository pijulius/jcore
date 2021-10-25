<?php

/***************************************************************************
 *            postrating.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/starrating.class.php');

class _postRating extends starRating {
	var $sqlTable = 'postratings';
	var $sqlRow = 'PostID';
	var $sqlOwnerTable = 'posts';
	var $adminPath = array(
		'admin/content/menuitems/posts/postrating',
		'admin/content/pages/posts/postrating',
		'admin/content/postsatglance/postrating');

	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'postRating::postRating', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'postRating::postRating', $this, $handled);

			return $handled;
		}

		parent::__construct();

		$this->selectedOwner = __('Post');
		$this->uriRequest = "posts/".$this->uriRequest;

		api::callHooks(API_HOOK_AFTER,
			'postRating::postRating', $this);
	}

	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'postRating::ajaxRequest', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'postRating::ajaxRequest', $this, $handled);

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
			'postRating::ajaxRequest', $this, $result);

		return $result;
	}
}

?>