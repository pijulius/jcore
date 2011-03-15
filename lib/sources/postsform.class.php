<?php

/***************************************************************************
 *            postsform.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/dynamicforms.class.php');

class _postsForm extends dynamicForms {
	function __construct() {
		parent::__construct(
			__('Posts'), 'posts');
		
		$this->textsDomain = 'messages';
	}
	
	function verify($customdatahandling = true) {
		if (!parent::verify(true))
			return false;
		
		return true;
	}
}

?>