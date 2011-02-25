<?php

/***************************************************************************
 *            noteattachments.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/

include_once('lib/attachments.class.php');

class _noteAttachments extends attachments {
	var $sqlTable = 'noteattachments';
	var $sqlRow = 'NoteID';
	var $sqlOwnerTable = 'notes';
	var $adminPath = 'admin/site/notes/noteattachments';
	
	function __construct() {
		parent::__construct();
		
		$this->selectedOwner = __('Note');
		$this->uriRequest = "notes&amp;attachments=1";
	}
}

?>