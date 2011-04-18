<?php

/***************************************************************************
 *            contentforms.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/dynamicforms.class.php');

class _contentForms extends dynamicForms {
	function __construct($formid) {
		parent::__construct(null, $formid);
		$this->textsDomain = 'messages';
	}
	
	function verify($customhandling = false) {
		if (!parent::verify($customhandling))
			return false;
		
		$this->reset();
		
		if ($this->successMessage) {
			tooltip::display(
				__($this->successMessage, $this->textsDomain),
				TOOLTIP_SUCCESS);
			return true;
		}
		
		if ($this->sendNotificationEmail) {
			tooltip::display(
				__("Form has been successfully submitted and a notification email " .
					"has been sent to the webmaster."),
				TOOLTIP_SUCCESS);
			return true;
		}
		
		tooltip::display(
			__("Form has been successfully submitted."),
			TOOLTIP_SUCCESS);
		return true;
	}
	
	function display($formdesign = true) {
		$this->load();
		$this->verify();
		parent::display($formdesign);
	}
}

?>