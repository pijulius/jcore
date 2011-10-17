<?php

/***************************************************************************
 *            notecomments.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/comments.class.php');

class _noteComments extends comments {
	var $sqlTable = 'notecomments';
	var $sqlRow = 'NoteID';
	var $sqlOwnerTable = 'notes';
	var $adminPath = 'admin/site/notes/notecomments';
	
	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'noteComments::noteComments', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'noteComments::noteComments', $this, $handled);
			
			return $handled;
		}
		
		parent::__construct();
		
		$this->selectedOwner = __('Note');
		$this->uriRequest = "notes&amp;comments=1";
		
		api::callHooks(API_HOOK_AFTER,
			'noteComments::noteComments', $this);
	}
}

?>