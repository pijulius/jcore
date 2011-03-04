<?php

/***************************************************************************
 *            postrating.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
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
		parent::__construct();
		
		$this->selectedOwner = __('Post');
		$this->uriRequest = "posts/".$this->uriRequest;
	}
}

?>