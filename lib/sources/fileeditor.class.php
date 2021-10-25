<?php

/***************************************************************************
 *            fileeditor.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

class _fileEditor {
	var $file;
	var $uriRequest;
	var $ajaxRequest = null;

	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileEditor::fileEditor', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileEditor::fileEditor', $this, $handled);

			return $handled;
		}

		$this->uriRequest = strtolower(get_class($this));

		api::callHooks(API_HOOK_AFTER,
			'fileEditor::fileEditor', $this);
	}

	function verify(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileEditor::verify', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileEditor::verify', $this, $form, $handled);

			return $handled;
		}

		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'fileEditor::verify', $this, $form);

			return false;
		}

		if (!$this->file) {
			tooltip::display(
				__("No file defined to be saved!"),
				TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'fileEditor::verify', $this, $form);

			return false;
		}

		$result = $this->save($this->file,
				str_replace("\r", '', $form->get('FileContent')));

		if (!$result)
			tooltip::display(
				__("File couldn't be saved!")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					 $this->file),
				TOOLTIP_ERROR);
		else
			tooltip::display(
				__("File has been successfully saved."),
				TOOLTIP_SUCCESS);

		api::callHooks(API_HOOK_AFTER,
			'fileEditor::verify', $this, $form, $result);

		return $result;
	}

	function save($file, $content) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileEditor::save', $this, $file, $content);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileEditor::save', $this, $file, $content, $handled);

			return $handled;
		}

		$result = files::save($file, $content);

		api::callHooks(API_HOOK_AFTER,
			'fileEditor::save', $this, $file, $content, $result);

		return $result;
	}

	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileEditor::ajaxRequest', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileEditor::ajaxRequest', $this, $handled);

			return $handled;
		}

		$this->display();

		api::callHooks(API_HOOK_AFTER,
			'fileEditor::ajaxRequest', $this);

		return true;
	}

	function setupForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileEditor::setupForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileEditor::setupForm', $this, $form, $handled);

			return $handled;
		}

		$form->add(
			__('File Editor'),
			'FileContent',
			FORM_INPUT_TYPE_CODE_EDITOR);
		$form->setValueType(FORM_VALUE_TYPE_HTML);

		api::callHooks(API_HOOK_AFTER,
			'fileEditor::setupForm', $this, $form);
	}

	function displayForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileEditor::displayForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileEditor::displayForm', $this, $form, $handled);

			return $handled;
		}

		$form->display();

		api::callHooks(API_HOOK_AFTER,
			'fileEditor::displayForm', $this, $form);
	}

	function display() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileEditor::display', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileEditor::display', $this, $handled);

			return $handled;
		}

		preg_match('/([^(\/|\\\)]*)$/', $this->file, $matches);

		if ($matches[1])
			$filename = $matches[1];
		else
			$filename = __('No File defined!');

		$form = new form(
			__('File Editor'),
			'fileeditor');

		$form->textsDomain = 'messages';

		$form->attributes = "class='ajax-form' " .
				"onsubmit=\"this.FileContent.focus();\"";

		$form->action = url::uri().
			"&amp;request=".$this->uriRequest;

		$form->add(
			__("File").": <b>".$filename."</b>",
			'',
			FORM_STATIC_TEXT);

		$this->setupForm($form);
		$form->addSubmitButtons();

		if ($this->file && files::exists($this->file) && !$form->submitted())
			$form->setValue('FileContent', files::get($this->file));

		$this->verify($form);

		if (!$this->ajaxRequest)
			$this->displayForm($form);

		unset($form);

		api::callHooks(API_HOOK_AFTER,
			'fileEditor::display', $this);
	}
}

?>