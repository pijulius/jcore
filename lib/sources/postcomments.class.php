<?php

/***************************************************************************
 *            postcomments.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
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
		
		if ($GLOBALS['ADMIN'])
			$this->commentURL = SITE_URL .
				"?pageid=".admin::getPathID(2) . 
				"&postid=".admin::getPathID();
	}
}

?>