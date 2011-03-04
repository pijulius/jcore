<?php

/***************************************************************************
 *            postattachments.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
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
		parent::__construct();
		
		$this->selectedOwner = __('Post');
		$this->uriRequest = "posts/".$this->uriRequest;
	}
}

?>