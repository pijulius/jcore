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
		api::callHooks(API_HOOK_BEFORE,
			'postsForm::postsForm', $this);
		
		parent::__construct(
			__('Posts'), 'posts');
		
		$this->textsDomain = 'messages';
		
		api::callHooks(API_HOOK_AFTER,
			'postsForm::postsForm', $this);
	}
	
	function verify($customdatahandling = true) {
		api::callHooks(API_HOOK_BEFORE,
			'postsForm::verify', $this, $customdatahandling);
		
		$result = parent::verify(true);
		
		api::callHooks(API_HOOK_AFTER,
			'postsForm::verify', $this, $customdatahandling, $result);
		
		return $result;
	}
}

?>