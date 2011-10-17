<?php

/***************************************************************************
 *            sitemapfileeditor.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
include_once('lib/fileeditor.class.php');

class _sitemapFileEditor extends fileEditor {
	function displayForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'sitemapFileEditor::displayForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'sitemapFileEditor::displayForm', $this, $form, $handled);
			
			return $handled;
		}
		
		$form->add(
			__("Regenerate"),
			'regeneratesubmit',
			FORM_INPUT_TYPE_BUTTON);
		
		$form->addAttributes(
			"onclick=\"this.form.regenerate.value=1;this.form.submit();\"");
		
		$form->add(
			__("Regenerate"),
			'regenerate',
			FORM_INPUT_TYPE_HIDDEN);
		
		parent::displayForm($form);
		
		api::callHooks(API_HOOK_AFTER,
			'sitemapFileEditor::displayForm', $this, $form);
	}
}

?>