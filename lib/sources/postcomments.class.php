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
		parent::__construct();
		
		$this->selectedOwner = __('Post');
		$this->uriRequest = "posts/".$this->uriRequest;
	}
	
	static function getCommentURL($comment = null) {
		if ($comment)
			return
				posts::getPostURL($comment['PostID']);
		
		if ($GLOBALS['ADMIN'])
			return 
				posts::getPostURL(admin::getPathID());
		
		return 
			parent::getCommentURL();
	}
}

?>