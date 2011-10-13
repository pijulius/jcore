<?php

/***************************************************************************
 *            noteattachments.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/attachments.class.php');

class _noteAttachments extends attachments {
	var $sqlTable = 'noteattachments';
	var $sqlRow = 'NoteID';
	var $sqlOwnerTable = 'notes';
	var $adminPath = 'admin/site/notes/noteattachments';
	
	function __construct() {
		api::callHooks(API_HOOK_BEFORE,
			'noteAttachments::noteAttachments', $this);
		
		parent::__construct();
		
		$this->selectedOwner = __('Note');
		$this->uriRequest = "notes&amp;attachments=1";
		
		api::callHooks(API_HOOK_AFTER,
			'noteAttachments::noteAttachments', $this);
	}
}

?>