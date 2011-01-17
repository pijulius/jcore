<?php

/***************************************************************************
 *            fileeditor.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/

class _fileEditor {
	var $file;
	var $uriRequest;
	
	function __construct() {
		$this->uriRequest = strtolower(get_class($this));
	}
	
	function verify(&$form) {
		if (!$form->verify())
			return false;
		
		if (!$this->file) {
			tooltip::display(
				__("No file defined to be saved!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!$this->save($this->file, 
				str_replace("\r", '', $form->get('FileContent')))) 
		{
			tooltip::display(
				__("File couldn't be saved!")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					 $this->file),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		tooltip::display(
			__("File has been successfully saved."),
			TOOLTIP_SUCCESS);
			
		return true;
	}
	
	function save($file, $content) {
		return files::save($file, $content); 
	}
	
	function ajaxRequest() {
		$this->display();
		return true;
	}
	
	function setupForm(&$form) {
		$form->add(
			__('File Editor'),
			'FileContent',
			FORM_INPUT_TYPE_CODE_EDITOR);
		$form->setValueType(FORM_VALUE_TYPE_HTML);
	}
	
	function displayForm(&$form) {
		$form->display();
	}
	
	function display() {
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
		
		if (isset($this->ajaxRequest))
			return;
			
		$this->displayForm($form);
		unset($form);
	}
}

?>