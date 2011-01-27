<?php

/***************************************************************************
 *            form.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
define('FORM_INPUT_TYPE_TEXT', 1);
define('FORM_INPUT_TYPE_EMAIL', 2);
define('FORM_INPUT_TYPE_CHECKBOX', 3);
define('FORM_INPUT_TYPE_RADIO', 4);
define('FORM_INPUT_TYPE_SELECT', 5);
define('FORM_INPUT_TYPE_TEXTAREA', 6);
define('FORM_INPUT_TYPE_HIDDEN', 7);
define('FORM_INPUT_TYPE_SUBMIT', 8);
define('FORM_INPUT_TYPE_RESET', 9);
define('FORM_INPUT_TYPE_BUTTON', 10);
define('FORM_INPUT_TYPE_VERIFICATION_CODE', 11);
define('FORM_INPUT_TYPE_FILE', 12);
define('FORM_OPEN_FRAME_CONTAINER', 13);
define('FORM_CLOSE_FRAME_CONTAINER', 14);
define('FORM_INPUT_TYPE_MULTISELECT', 15);
define('FORM_INPUT_TYPE_TIMESTAMP', 16);
define('FORM_INPUT_TYPE_DATE', 17);
define('FORM_STATIC_TEXT', 18);
define('FORM_INPUT_TYPE_EDITOR', 19);
define('FORM_INPUT_TYPE_PASSWORD', 20);
define('FORM_INPUT_TYPE_CONFIRM', 21);
define('FORM_INPUT_TYPE_REVIEW', 22);
define('FORM_INPUT_TYPE_CODE_EDITOR', 23);
define('FORM_INPUT_TYPE_COLOR', 24);
define('FORM_INPUT_TYPE_SEARCH', 25);
define('FORM_INPUT_TYPE_TEL', 26);
define('FORM_INPUT_TYPE_URL', 27);
define('FORM_INPUT_TYPE_RANGE', 28);
define('FORM_INPUT_TYPE_NUMBER', 29);
define('FORM_INPUT_TYPE_TIME', 30);
define('FORM_PAGE_BREAK', 31);
define('FORM_INPUT_TYPE_RECIPIENT_SELECT', 32);

define('FORM_VALUE_TYPE_STRING', 1);
define('FORM_VALUE_TYPE_INT', 2);
define('FORM_VALUE_TYPE_ARRAY', 3);
define('FORM_VALUE_TYPE_TIMESTAMP', 4);
define('FORM_VALUE_TYPE_DATE', 5);
define('FORM_VALUE_TYPE_HTML', 6);
define('FORM_VALUE_TYPE_URL', 7);
define('FORM_VALUE_TYPE_LIMITED_STRING', 8);
define('FORM_VALUE_TYPE_TEXT', 9);
define('FORM_VALUE_TYPE_BOOL', 10);
define('FORM_VALUE_TYPE_FILE', 11);
define('FORM_VALUE_TYPE_FLOAT', 12);

define('FORM_INSERT_AFTER', 1);
define('FORM_INSERT_BEFORE', 2);

define('FORM_ELEMENT_SET', 1);
define('FORM_ELEMENT_ADD', 2);
define('FORM_ELEMENT_ARRAY', 3);

define('FORM_ERROR_NONE', 0);
define('FORM_ERROR_REQUIRED', 1);
define('FORM_ERROR_NOFILE', 2);
define('FORM_ERROR_NOMATCH', 3);
define('FORM_ERROR_VERIFICATION_CODE', 4);
define('FORM_ERROR_EMAIL', 5);
define('FORM_ERROR_PASSWORD', 6);

include_once('lib/ckeditor.class.php');
 
class _form {
	var $title;
	var $id;
	var $action;
	var $method;
	var $attributes = '';
	var $elements = array();
	var $fileElements = array();
	var $recipientElements = array();
	var $pageBreakElements = array();
	var $ignorePageBreaks = false;
	var $selectedPage = 0;
	var $footer = null;
	var $preview = false;
	var $verifyPassword = true;
	var $displayDesign = true;
	var $displayFormElement = true;
	
	var $emptyElement = array(
		'Title' => '',
		'Name' => 'PlaceholderElement',
		'EntryID' => '',
		'Type' => FORM_INPUT_TYPE_HIDDEN,
		'Required' => false,
		'OriginalValue' => null,
		'Attributes' => '',
		'ValueType' => FORM_VALUE_TYPE_TEXT,
		'Value' => null);
	
	function __construct($title = null, $id = null, $method = 'post') {
		$this->title = $title;
		$this->id = ($id?$id:preg_replace('/ /', '', strtolower($title)));
		$this->action = url::uri();
		$this->method = $method;
		
		if (isset($GLOBALS['_'.strtoupper($this->method)]['formpage']))
			$this->selectedPage = (int)$GLOBALS['_'.strtoupper($this->method)]['formpage'];
	}
	
	function submitted() {
		if ($this->get($this->id."submit"))
			return $this->get($this->id."submit");
		
		foreach($this->elements as $element) {
			if ($element['Type'] == FORM_INPUT_TYPE_SUBMIT && 
				$this->get($element['Name']))
			{
				return $this->get($element['Name']);
			}
		}
		
		return false;
	}
	
	function reset($elementname = null) {
		foreach($this->elements as $elementnum => $element) {
			if (!$elementname || $elementname == $element['Name'])
				unset($GLOBALS['_'.strtoupper($this->method)][$element['Name']]);
				unset($this->elements[$elementnum]['VerifyResult']);
				
				if (in_array($element['Type'], array(
					FORM_INPUT_TYPE_CHECKBOX,
					FORM_INPUT_TYPE_RADIO,
					FORM_INPUT_TYPE_SELECT,
					FORM_INPUT_TYPE_MULTISELECT,
					FORM_INPUT_TYPE_RECIPIENT_SELECT)))
				{
					$this->elements[$elementnum]['Value'] = null;
					continue;
				}
				
				$originalvalue = null;
				
				if (isset($this->elements[$elementnum]['OriginalValue']))
					$originalvalue = $this->elements[$elementnum]['OriginalValue'];
				
				$this->elements[$elementnum]['Value'] = $originalvalue;
		}
		
		$this->selectedPage = 1;
	}
	
	function clear() {
		$this->elements = array();
	}
	
	function getPostArray() {
		$post = array();
		
		foreach($this->elements as $element) {
			$elementid = preg_replace('/\[.*\]/i', '', $element['Name']);
			
			if (isset($post[$elementid]))
				continue;
			
			$post[$elementid] = $this->get($elementid);
		}
			
		return $post;
	}
	
	function getElementID($elementname = null) {
		if (!$elementname)
			return count($this->elements)-1;
		
		$elementid = null;
		
		foreach($this->elements as $elementnum => $element) {
			if (!isset($element['Name']))
				continue;
			
			if ($element['Name'] == $elementname || 
				preg_replace('/\[.*\]/', '', $element['Name']) == $elementname) 
			{
				$elementid = $elementnum;
				break;
			}
		}
		
		return $elementid;
	}
			
	function getElementIDByTitle($elementtitle = null) {
		if (!$elementtitle)
			return count($this->elements)-1;
		
		$elementid = null;
		
		foreach($this->elements as $elementnum => $element) {
			if (!isset($element['Title']))
				continue;
			
			if ($element['Title'] == $elementtitle || 
				preg_replace('/\[.*\]/', '', $element['Title']) == $elementtitle) 
			{
				$elementid = $elementnum;
				break;
			}
		}
		
		return $elementid;
	}
			
	function updateElement($title, $name, $type = FORM_INPUT_TYPE_TEXT, 
				$required = false, $value = null, $elementid = null) 
	{
		if (isset($elementid) && !isset($this->elements[$elementid]))
			return false;
		
		if (!isset($elementid)) {
			if (isset($this->elements) && is_array($this->elements))
				$elementid = count($this->elements);
			else
				$elementid = 0;
		}
		
		if ($type == FORM_INPUT_TYPE_VERIFICATION_CODE) {
			if ($GLOBALS['USER']->loginok && !$this->preview)
				return false;
			
			$this->elements[$elementid]['Title'] = 
				"<b>".$title."</b>";
					
			$this->elements[$elementid]['AdditionalTitle'] =
				"<div class='comment'>" .
					__("Enter the code shown on the right") .
				"<p>&nbsp;</p></div>";
				
			$this->elements[$elementid]['EntryID'] = "scimagecode";				
			$this->elements[$elementid]['Name'] = "scimagecode";
			$this->elements[$elementid]['Type'] = $type;
			$this->elements[$elementid]['Required'] = true;
			$this->elements[$elementid]['Value'] = $value;
					
			if (!isset($this->elements[$elementid]['Attributes']))
				$this->elements[$elementid]['Attributes'] = '';
			
			if (!isset($this->elements[$elementid]['ValueType']))
				$this->elements[$elementid]['ValueType'] = FORM_VALUE_TYPE_TEXT;
			
			$this->elements[$elementid]['AdditionalPreText'] = 
				"<div class='security-image ".$this->id."-scimage'>" .
					"<img src='".url::uri().
						"&amp;request=security&amp;scimage=1&amp;ajax=1' " .
						(JCORE_VERSION < '0.6'?
							"border='2' ":
							null) .
						"alt='".htmlspecialchars(__("Security Image"), ENT_QUOTES)."' />" .
					"<a class='reload-link' href='javascript://' " .
						"onclick=\"jQuery('.".$this->id."-scimage img').attr('src', '".
							url::uri().
							"&amp;request=security&amp;scimage=1&amp;ajax=1'+Math.random());\">".
						__("Reload").
					"</a>" .
				"</div>";
			
			return $elementid;
		}
		
		$this->elements[$elementid]['EntryID'] = 
			preg_replace('/([^a-z^0-9^_^-]*)/i', '', $name);
		$this->elements[$elementid]['Title'] = $title;
		$this->elements[$elementid]['Name'] = $name;
		$this->elements[$elementid]['Type'] = $type;
		$this->elements[$elementid]['Required'] = $required;
		$this->elements[$elementid]['OriginalValue'] = $value;
		
		if (!isset($this->elements[$elementid]['Attributes']))
			$this->elements[$elementid]['Attributes'] = '';
		
		if (!isset($this->elements[$elementid]['ValueType']))
			$this->elements[$elementid]['ValueType'] = FORM_VALUE_TYPE_TEXT;
		
		if (JCORE_VERSION >= '0.6' && 
			!isset($this->elements[$elementid]['TooltipText'])) 
		{
			if ($type == FORM_INPUT_TYPE_EMAIL)
				$this->elements[$elementid]['TooltipText'] = 
					__("e.g. user@domain.com");
			elseif ($type == FORM_INPUT_TYPE_TIMESTAMP)
				$this->elements[$elementid]['TooltipText'] = 
					__("e.g. 2010-07-21 21:00:00");
			elseif ($type == FORM_INPUT_TYPE_DATE)
				$this->elements[$elementid]['TooltipText'] = 
					__("e.g. 2010-07-21");
			elseif ($type == FORM_INPUT_TYPE_TIME)
				$this->elements[$elementid]['TooltipText'] = 
					__("e.g. 21:00:00");
			elseif ($type == FORM_INPUT_TYPE_COLOR)
				$this->elements[$elementid]['TooltipText'] = 
					__("e.g. #ff9933");
			elseif ($type == FORM_INPUT_TYPE_URL)
				$this->elements[$elementid]['TooltipText'] = 
					__("e.g. http://domain.com");
			elseif ($type == FORM_INPUT_TYPE_TEL)
				$this->elements[$elementid]['TooltipText'] = 
					__("e.g. +1 (202) 555-1234");
		}
		
		if ($type == FORM_INPUT_TYPE_FILE)
			$this->elements[$elementid]['ValueType'] = FORM_VALUE_TYPE_FILE;
		
		if ($type == FORM_INPUT_TYPE_FILE &&
			!isset($this->fileElements[$elementid]))
		{
			$this->fileElements[$elementid] = $elementid;
			
		} elseif (isset($this->fileElements[$elementid])) {
			unset($this->fileElements[$elementid]);
		}
		
		if ($type == FORM_INPUT_TYPE_RECIPIENT_SELECT &&
			!isset($this->recipientElements[$elementid]))
		{
			$this->recipientElements[$elementid] = $elementid;
			
		} elseif (isset($this->recipientElements[$elementid])) {
			unset($this->recipientElements[$elementid]);
		}
		
		if ($type == FORM_PAGE_BREAK && 
			!isset($this->pageBreakElements[$elementid])) 
		{
			$this->pageBreakElements[$elementid] = $elementid;
			
			if (!$this->selectedPage)
				$this->selectedPage = 1;
		
		} elseif (isset($this->pageBreakElements[$elementid])) {
			unset($this->pageBreakElements[$elementid]);
		}
		
		$submittedvalue = null;
		
		if (isset($GLOBALS['_'.strtoupper($this->method)][$name]))
			$submittedvalue = $GLOBALS['_'.strtoupper($this->method)][$name];
		
		if (preg_match('/\[(.*)\]/', $name, $matches) &&
			isset($GLOBALS['_'.strtoupper($this->method)][preg_replace('/\[.*\]/', '', $name)]) && 
			is_array($GLOBALS['_'.strtoupper($this->method)][preg_replace('/\[.*\]/', '', $name)]))
		{
			$submittedvalue = 
				$GLOBALS['_'.strtoupper($this->method)]
					[preg_replace('/\[.*\]/', '', $name)];
			
			if (isset($matches[1])) {
				$arraykeys = explode('][', $matches[1]);
				foreach($arraykeys as $arraykey) {
					if (isset($submittedvalue[$arraykey]))
						$submittedvalue = $submittedvalue[$arraykey];
					else
						$submittedvalue = null;
				}
			}
		}
		
		if ($type == FORM_INPUT_TYPE_CHECKBOX || 
			$type == FORM_INPUT_TYPE_RADIO) 
		{
			$this->elements[$elementid]['Value'] = $submittedvalue;
			return $elementid;
		}
		
		$this->elements[$elementid]['Value'] = 
			(isset($submittedvalue)?
				$submittedvalue:
				$value);
		
		return $elementid;
	}
	
	function getFile($elementname) {
		if (!$elementname)
			return false;
		
		if (is_numeric($elementname)) {
			$elementid = $elementname;
			$elementname = $this->elements[$elementid]['Name'];
		} else {
			$elementid = $this->getElementID($elementname);
		}
		
		if (!isset($elementid))
			return false;
		
 		$fileid = preg_replace('/\[.*?\]/', '', $elementname);
 		$filearrayid = null;
 		
 		preg_match('/\[(.*?)\]/', $elementname, $matches);
 		
 		if (isset($matches[1]))
	 		$filearrayid = $matches[1];
 		
 		if (isset($filearrayid))
			$file = $_FILES[$fileid]['tmp_name'][$filearrayid];
 		else
			$file = $_FILES[$fileid]['tmp_name'];
		
		if (is_array($file)) {
			$tmpfiles = null;
			
			foreach($file as $tmpname) {
				if (!$tmpname)
					continue;
				
				$tmpfiles[] = $tmpname;
			}
			
			$file = $tmpfiles;
		}
		
		if (!$file)
			return false;
		
		return $file;
	}
	
	function get($elementname) {
		if (!isset($elementname))
			return false;
		
		if (is_numeric($elementname)) {
			$elementid = $elementname;
			$elementname = $this->elements[$elementid]['Name'];
		} else {
			$elementid = $this->getElementID($elementname);
		}
		
		if (!isset($elementid))
			return false;
		
		if (preg_match('/\[(.*)\]/', $elementname, $arraykeys))	
			$elementname = preg_replace('/\[.*\]/', '', $elementname);
		
		$value = null;
		
		if (isset($GLOBALS['_'.strtoupper($this->method)][$elementname]))
			$value = $GLOBALS['_'.strtoupper($this->method)][$elementname];
			
		if (($this->elements[$elementid]['ValueType'] == FORM_VALUE_TYPE_FILE ||
			$this->elements[$elementid]['Type'] == FORM_INPUT_TYPE_FILE) &&
			!$value && isset($GLOBALS['_FILES'][$elementname]))
		{
			if (is_array($GLOBALS['_FILES'][$elementname]['name'])) {
				foreach($GLOBALS['_FILES'][$elementname]['name'] as $filename) {
					if (!$filename)
						continue;
					
					$value[] = $filename;
				}
				
			} else {
				$value = $GLOBALS['_FILES'][$elementname]['name'];
			}
		}
		
		if ($value && isset($arraykeys[1])) {
			$arraykeys = explode('][', $arraykeys[1]);
			
			foreach($arraykeys as $arraykey) {
				if (!$value)
					continue;
				
				if (isset($value[$arraykey]))
					$value = $value[$arraykey];
				else
					$value = null;
			}
		}
		
		if ($this->elements[$elementid]['Type'] == FORM_INPUT_TYPE_RECIPIENT_SELECT &&
			$value && isset($this->elements[$elementid]['Values'][(int)($value-1)]))
			$value = $this->elements[$elementid]['Values'][(int)($value-1)]['Value'];
		
		if (!isset($value))
			return null;
		
		switch ($this->elements[$elementid]['ValueType']) {
			case FORM_VALUE_TYPE_FILE:
				if (is_array($value))
					return form::parseArray($value);
				
				return trim(strip_tags($value));
			
			case FORM_VALUE_TYPE_INT:
				return (strlen($value)?
							(int)$value:
							null);
				
			case FORM_VALUE_TYPE_BOOL:
				return (strlen($value)?
							(bool)$value:
							false);
		
			case FORM_VALUE_TYPE_FLOAT:
				return form::parseFloat($value);
		
			case FORM_VALUE_TYPE_ARRAY:
				return form::parseArray($value);
			
			case FORM_VALUE_TYPE_TIMESTAMP:
				return 
					($value?
						date('Y-m-d H:i:s', 
							strtotime($value)):
						null);
			
			case FORM_VALUE_TYPE_DATE:
				return 
					($value?
						date('Y-m-d', 
							strtotime($value)):
						null);
			
			case FORM_VALUE_TYPE_HTML:
				return $value;
		
			case FORM_VALUE_TYPE_URL:
				return url::fix($value);
		
			case FORM_VALUE_TYPE_LIMITED_STRING:
				return trim(preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '',
							strip_tags($value)));
		
			case FORM_VALUE_TYPE_STRING:
			case FORM_VALUE_TYPE_TEXT:
			
			default:
				return form::parseString($value);
		}
	}
	
	function set($element, $value) {
		$GLOBALS['_'.strtoupper($this->method)][$element] = $value;
	}
	
	function insert($insertto, $title, $name, $type = FORM_INPUT_TYPE_TEXT, 
				$required = false, $value = null, $inserttype = FORM_INSERT_AFTER) 
	{
		$inserttoid = $this->getElementID($insertto);
		
		if (!$insertto)
			return false;
		
		if ($inserttype == FORM_INSERT_AFTER)
			$inserttoid++;
		
		array_splice($this->elements, $inserttoid, count($this->elements), 
			array_merge(array($this->emptyElement), array_slice($this->elements, $inserttoid)));
		
		return $this->updateElement(
			$title, $name, $type, $required, $value, $inserttoid); 
	}
	
	function add($title, $name, $type = FORM_INPUT_TYPE_TEXT, 
				$required = false, $value = null) 
	{
		return $this->updateElement(
			$title, $name, $type, $required, $value); 
	}
	
	function edit($elementname, $title = null, $name = null, 
		$type = null, $required = null, $value = null) 
	{
		$elementid = $this->getElementID($elementname);
		
		if (!isset($elementid))
			return false;
		
		if (!isset($title))
			$title = $this->elements[$elementid]['Title'];
		
		if (!isset($name))
			$name = $this->elements[$elementid]['Name'];
		
		if (!isset($type))
			$type = $this->elements[$elementid]['Type'];
		
		if (!isset($required))
			$required = $this->elements[$elementid]['Required'];
		
		if (!isset($value))
			$value = $this->elements[$elementid]['OriginalValue'];
		
		return $this->updateElement(
			$title, $name, $type, $required, $value, $elementid); 
	}
	
	function editByTitle($elementtitle, $title = null, $name = null, 
		$type = null, $required = null, $value = null) 
	{
		$elementid = $this->getElementIDByTitle($elementtitle);
		
		if (!isset($elementid))
			return false;
		
		if (!isset($title))
			$title = $this->elements[$elementid]['Title'];
		
		if (!isset($name))
			$name = $this->elements[$elementid]['Name'];
		
		if (!isset($type))
			$type = $this->elements[$elementid]['Type'];
		
		if (!isset($required))
			$required = $this->elements[$elementid]['Required'];
		
		if (!isset($value))
			$value = $this->elements[$elementid]['OriginalValue'];
		
		return $this->updateElement(
			$title, $name, $type, $required, $value, $elementid); 
	}
	
	function delete($elementname) 
	{
		$elementid = $this->getElementID($elementname);
		
		if (!isset($elementid))
			return false;
		
		array_splice($this->elements, $elementid, 1);
		return true;
	}
	
	static function parseFloat($floatString){
		return preg_replace('/[^0-9\.]/', '', $floatString);
	}
	
	static function parseArray($array) {
		if (!is_array($array))
			return array();
		
		$strippedarray = array();
		
		foreach($array as $key => $value) {
			if ($value == "")
				continue;
			
			if (is_array($value))
				$strippedarray[$key] = form::parseArray($value);
			else
				$strippedarray[$key] = trim(strip_tags($value));
		}
			
		return $strippedarray;
	}
	
	static function parseString($content) {
		if (!$content)
			return null;
		
		$content = (string)$content;
		
		$content = strip_tags($content, 
			'<a><b><i><u><span><br><hr><em><blockquote><code><strong>');
		
		$content = preg_replace(
			'/<(\/?blockquote|strong|code|span|br|hr|em|b|i|u).*?( ?\/?)>/i', 
			'<\1\2>', $content);
		
		$content = preg_replace(
			'/<(\/?a).*?(( href=(\'|")(ht|f)tps?:\/\/.*?(\'|"))| ?\/?)>/i', 
			'<\1\3>', $content);
		
		return trim($content);
	}

	static function type2Text($type) {
		if (!$type)
			return;
		
		switch($type) {
			case FORM_INPUT_TYPE_TEXT:
				return __('Text');
			case FORM_INPUT_TYPE_EMAIL:
				return __('Email');
			case FORM_INPUT_TYPE_CHECKBOX:
				return __('Checkbox');
			case FORM_INPUT_TYPE_RADIO:
				return __('Radio');
			case FORM_INPUT_TYPE_SELECT:
				return __('Select');
			case FORM_INPUT_TYPE_MULTISELECT:
				return __('Multi Select');
			case FORM_INPUT_TYPE_RECIPIENT_SELECT:
				return __('Recipient Select');
			case FORM_INPUT_TYPE_TEXTAREA:
				return __('Textarea');
			case FORM_INPUT_TYPE_EDITOR:
				return __('Text Editor');
			case FORM_INPUT_TYPE_CODE_EDITOR:
				return __('Code Editor');
			case FORM_INPUT_TYPE_HIDDEN:
				return __('Hidden');
			case FORM_INPUT_TYPE_REVIEW:
				return __('Review');
			case FORM_INPUT_TYPE_PASSWORD:
				return __('Password');
			case FORM_INPUT_TYPE_CONFIRM:
				return __('Confirm Previous Field');
			case FORM_INPUT_TYPE_TIMESTAMP:
				return __('Date Time');
			case FORM_INPUT_TYPE_DATE:
				return __('Date');
			case FORM_INPUT_TYPE_TIME:
				return __('Time');
			case FORM_STATIC_TEXT:
				return __('Static Text');
			case FORM_INPUT_TYPE_SUBMIT:
				return __('Button Submit');
			case FORM_INPUT_TYPE_RESET:
				return __('Button Reset');
			case FORM_INPUT_TYPE_BUTTON:
				return __('Button');
			case FORM_INPUT_TYPE_VERIFICATION_CODE:
				return __('Verification code');
			case FORM_INPUT_TYPE_FILE:
				return __('File');
			case FORM_OPEN_FRAME_CONTAINER:
				return __('Open Form Area');
			case FORM_CLOSE_FRAME_CONTAINER:
				return __('Close Form Area');
			case FORM_INPUT_TYPE_COLOR:
				return __('Color');
			case FORM_INPUT_TYPE_SEARCH:
				return __('Search');
			case FORM_INPUT_TYPE_TEL:
				return __('Telephone');
			case FORM_INPUT_TYPE_URL:
				return __('URL');
			case FORM_INPUT_TYPE_RANGE:
				return __('Range');
			case FORM_INPUT_TYPE_NUMBER:
				return __('Number');
			case FORM_PAGE_BREAK:
				return __('Page Break');
			default:
				return __('Undefined!');
		}
	}
	
	static function valueType2Text($type) {
		if (!$type)
			return;
		
		switch($type) {
			case FORM_VALUE_TYPE_STRING:
				return __('String');
			case FORM_VALUE_TYPE_INT:
				return __('Int');
			case FORM_VALUE_TYPE_FLOAT:
				return __('Float');
			case FORM_VALUE_TYPE_ARRAY:
				return __('Array');
			case FORM_VALUE_TYPE_TIMESTAMP:
				return __('TimeStamp');
			case FORM_VALUE_TYPE_DATE:
				return __('Date');
			case FORM_VALUE_TYPE_HTML:
				return __('HTML');
			case FORM_VALUE_TYPE_URL:
				return __('URL');
			case FORM_VALUE_TYPE_LIMITED_STRING:
				return __('LimitedString');
			case FORM_VALUE_TYPE_TEXT:
				return __('Text');
			case FORM_VALUE_TYPE_BOOL:
				return __('Boolean');
			default:
				return __('Undefined!');
		}
	}
	
	static function fcState($name, $state = false) {
		$name = preg_replace('/^fc/', '', $name);
		
		if (!$name)
			if (!$state)
				return null;
			else
				return ' expanded';
		
		if ($state && (!isset($_COOKIE['fcstates']) || 
			!in_array($name, explode('|', $_COOKIE['fcstates']))) ||
			(!$state && isset($_COOKIE['fcstates']) && 
			in_array($name, explode('|', $_COOKIE['fcstates']))))
			return ' expanded';
		
		return null;
	}
	
	function addSubmitButtons() {
		form::add(
			__('Submit'),
			$this->id.'submit',
			FORM_INPUT_TYPE_SUBMIT);
		
		form::add(
			__('Reset'),
			$this->id.'reset',
			FORM_INPUT_TYPE_RESET);
	}
	
	function setAttributes($elementname, $value = null) {
		return $this->setElementKey('Attributes', $elementname, $value);
	}
	
	function setPlaceholderText($elementname, $value = null) {
		return $this->setElementKey('PlaceholderText', $elementname, $value);
	}
	
	function setTooltipText($elementname, $value = null) {
		return $this->setElementKey('TooltipText', $elementname, $value);
	}
	
	function setAdditionalTitle($elementname, $value = null) {
		return $this->setElementKey('AdditionalTitle', $elementname, $value);
	}
	
	function setAdditionalText($elementname, $value = null) {
		return $this->setElementKey('AdditionalText', $elementname, $value);
	}
	
	function setAdditionalPreText($elementname, $value = null) {
		return $this->setElementKey('AdditionalPreText', $elementname, $value);
	}
	
	function setAutoFocus($elementname, $value = null) {
		return $this->setElementKey('AutoFocus', $elementname, $value);
	}
	
	function setError($elementname, $value = null) {
		return $this->setElementKey('VerifyResult', $elementname, $value);
	}
	
	function addAttributes($elementname, $value = null) {
		return $this->setElementKey('Attributes', $elementname, $value, FORM_ELEMENT_ADD);
	}
	
	function addAdditionalTitle($elementname, $value = null) {
		return $this->setElementKey('AdditionalTitle', $elementname, $value, FORM_ELEMENT_ADD);
	}
	
	function addAdditionalText($elementname, $value = null) {
		return $this->setElementKey('AdditionalText', $elementname, $value, FORM_ELEMENT_ADD);
	}
	
	function addAdditionalPreText($elementname, $value = null) {
		return $this->setElementKey('AdditionalPreText', $elementname, $value, FORM_ELEMENT_ADD);
	}
	
	function addValue($elementname, $value = null, $valuetext = null) {
		if (isset($valuetext)) {
			$value = array(
				'Value' => $value,
				'ValueText' => $valuetext);
		} else {
			$elementname = array(
				'Value' => $elementname,
				'ValueText' => $value);
			$value = null;
		}
		
		return $this->setElementKey('Values', $elementname, $value, FORM_ELEMENT_ARRAY);
	}
	
	function disableValues($elementname, $values = null) {
		return $this->setElementKey('DisabledValues', $elementname, $values);
	}
	
	function groupValues($elementname, $values = null) {
		return $this->setElementKey('GroupValues', $elementname, $values);
	}
	
	function setStyle($elementname, $value = null) {
		if (isset($value))
			$value = " style='".$value."'";
		else
			$elementname = " style='".$elementname."'";
		
		return $this->setElementKey('Attributes', $elementname, $value, FORM_ELEMENT_ADD);
	}
	
	function setValue($elementname, $value = null) {
		if (isset($value))
			$this->set($elementname, $value);
		
		return $this->setElementKey('Value', $elementname, $value);
	}
	
	function setValues($values = array()) {
		if (!$values || !is_array($values) || !count($values))
			return false;
		
		foreach($this->elements as $key => $element) {
			if (!isset($values[$element['Name']]))
				continue;
			
			if ($element['ValueType'] == FORM_VALUE_TYPE_ARRAY)
				$value = explode('|', $values[$element['Name']]);
			else
				$value = $values[$element['Name']];
		
			$this->setValue($element['Name'], $value);
		}
		
		return true;
	}
	
	function setValueType($elementname, $type = null) {
		return $this->setElementKey('ValueType', $elementname, $type);
	}
	
	function setElementKey($key, $elementname, $value = null, $method = null) {
		if (!count($this->elements))
			return false;
			
		if (!$key)
			return false;
			
		if (!isset($elementname))
			return false;
		
		if (isset($value)) {	
			$elementid = $this->getElementID($elementname);
			
			if (!isset($elementid))
				return false;
			
			$elementname = $value;
		} else {
			$elementid = $this->getElementID();
		}
		
		if (!isset($this->elements[$elementid][$key]))
			$this->elements[$elementid][$key] = null;
		
		if ($method == FORM_ELEMENT_ARRAY) {
			$this->elements[$elementid][$key][] = $elementname;
			return true;
		}
		
		if ($method == FORM_ELEMENT_ADD) {
			$this->elements[$elementid][$key] .= $elementname;
			return true;
		}
		
		$this->elements[$elementid][$key] = $elementname;
		return true;
	}
	
	static function isInput($element) {
		if (!isset($element))
			return false;
		
		if (!isset($element['Type']))
			return false;
		
		if (in_array($element['Type'], array( 
				FORM_INPUT_TYPE_TEXT,
				FORM_INPUT_TYPE_EMAIL,
				FORM_INPUT_TYPE_CHECKBOX,
				FORM_INPUT_TYPE_RADIO,
				FORM_INPUT_TYPE_SELECT,
				FORM_INPUT_TYPE_RECIPIENT_SELECT,
				FORM_INPUT_TYPE_TEXTAREA,
				FORM_INPUT_TYPE_HIDDEN,
				FORM_INPUT_TYPE_FILE,
				FORM_INPUT_TYPE_MULTISELECT,
				FORM_INPUT_TYPE_TIMESTAMP,
				FORM_INPUT_TYPE_DATE,
				FORM_INPUT_TYPE_TIME,
				FORM_INPUT_TYPE_EDITOR,
				FORM_INPUT_TYPE_COLOR)))
			return true;
		
		return false;
	}
	
	function verify() {
		if (!$this->submitted())
			return false;
		
		$currentpage = 1;
		$errors = array();
		
		foreach($this->elements as $elementnum => $element) {
			$value = $this->get($elementnum);
			$this->elements[$elementnum]['VerifyResult'] = 0;
			
			if ($element['Type'] == FORM_PAGE_BREAK) {
				if ($this->ignorePageBreaks)
					continue;
				
				$currentpage++;
				
				if ($currentpage > $this->selectedPage) {
					if (!count($errors)) {
						$this->selectedPage++;
						return false;
					}
					
					break;
				}
				
				continue;
			}
			
			if (!$element['Required'] && !$value &&
				$element['Type'] != FORM_INPUT_TYPE_CONFIRM)
				continue;
			
			if (in_array($element['Type'], array(
					FORM_INPUT_TYPE_EMAIL, FORM_INPUT_TYPE_RECIPIENT_SELECT)) &&
				!email::verify($value))
			{
				$this->elements[$elementnum]['VerifyResult'] = 5;
				$errors[] = 5;
				
			} elseif ($element['Type'] == FORM_INPUT_TYPE_PASSWORD &&
				$this->verifyPassword &&
				strlen($value) < MINIMUM_PASSWORD_LENGTH)
			{
				$this->elements[$elementnum]['VerifyResult'] = 6;
				$errors[] = 6;
				
			} elseif ($element['Type'] == FORM_INPUT_TYPE_VERIFICATION_CODE &&
				!security::verifyImageCode($value))
			{
				$this->elements[$elementnum]['VerifyResult'] = 4;
				$errors[] = 4;
				
			} elseif ($element['Type'] == FORM_INPUT_TYPE_CONFIRM &&
				isset($this->elements[($elementnum-1)]) && 
				$this->get($elementnum-1) != $value)
			{
				$this->elements[$elementnum]['VerifyResult'] = 3;
				$errors[] = 3;
				
			} elseif ($element['Type'] == FORM_INPUT_TYPE_FILE && !$value) 
			{
				$this->elements[$elementnum]['VerifyResult'] = 2;
				$errors[] = 2;
				
			} elseif (!in_array($element['Type'], array(
				FORM_INPUT_TYPE_CONFIRM,
				FORM_OPEN_FRAME_CONTAINER,
				FORM_CLOSE_FRAME_CONTAINER,
				FORM_STATIC_TEXT)) && 
				!$value) 
			{
				$this->elements[$elementnum]['VerifyResult'] = 1;
				$errors[] = 1;
			}
		}
		
		$error = null;
		
		if (in_array(1, $errors))
			$error .=
				(JCORE_VERSION >= '0.6'?
					__("Fields marked with an asterisk (*) are required."): 
					__("Field(s) marked with an asterisk (*) is/are required.")) .
				" ";
		
		if (in_array(2, $errors))
			$error .= 
				__("No file selected for upload.")." ";
		
		if (in_array(3, $errors))
			$error .= 
				__("Some fields do not match. Please make sure to enter " .
					"the same value when asked to confirm a previous field.")." ";
		
		if (in_array(4, $errors))
			$error .= 
				__("Incorrect verification code. " .
					"Please enter the code shown on the image.")." ";
		
		if (in_array(5, $errors))
			$error .= 
				__("Invalid email address. Please make sure you enter " .
					"a valid email address.")." ";
		
		if (in_array(6, $errors))
			$error .= 
				sprintf(__("The password you entered is too short. Your " .
					"password must be at least %s characters long."), 
					MINIMUM_PASSWORD_LENGTH)." ";
		
		if ($error) {
			$error .= 
				__("Please review / correct the marked fields in the form below " .
					"and try again.");
			
			tooltip::display($error, TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	function displayData($values, $ignoreelements = null) {
		$prevelement = null;
		
		foreach($this->elements as $element) {
			if (in_array($element['Type'], array(
				FORM_INPUT_TYPE_PASSWORD,
				FORM_INPUT_TYPE_CONFIRM)))
				continue;
				
			$isinput = form::isInput($element);
			
			if ($isinput && (!isset($values[$element['Name']]) || 
				!$values[$element['Name']]))
				continue;
			
			if ($ignoreelements && is_array($ignoreelements) &&
				in_array($element['Name'], $ignoreelements))
				continue;
			
			if (($isinput || $element['Type'] == FORM_STATIC_TEXT) && 
				$prevelement && $prevelement['Type'] == FORM_OPEN_FRAME_CONTAINER) 
			{
				echo
					"<div class='fc" .
						($prevelement['Name']?
							" fc-".url::genPathFromString($prevelement['Name']):
							null) .
						(isset($GLOBALS['ADMIN']) && $GLOBALS['ADMIN']?
							" expanded":
							form::fcState(
								'fc'.url::genPathFromString($prevelement['Name']),
								$prevelement['Required'])) .
						"' ".$prevelement['Attributes'].">" .
						"<a class='fc-title' ".
							($prevelement['Name']?
								"name='fc".url::genPathFromString($prevelement['Name'])."'":
								null) .
							">" .
							$prevelement['Title'] .
						"</a>" .
						"<div class='fc-content'>";
			}
			
			if ($isinput || $element['Type'] == FORM_STATIC_TEXT) 
				echo
					"<div class='form-entry" .
						($element['Name'] || $element['Title']?
							" form-entry-".
							($element['Name']?
								url::genPathFromString($element['Name']):
								url::genPathFromString($element['Title'])):
							null) .
						" preview'>";
			
			if ($isinput) {
				if (!isset($values[$element['Name']]))
					$value = null;
				elseif ($element['ValueType'] == FORM_VALUE_TYPE_ARRAY)
					$value = str_replace('|', '; ', $values[$element['Name']]);
				elseif ($element['ValueType'] == FORM_VALUE_TYPE_BOOL)
					$value = ($values[$element['Name']]?__("Yes"):__("No"));
				else
					$value = $values[$element['Name']];
		
				if ($element['Type'] == FORM_INPUT_TYPE_EDITOR)
					echo 
						$value;
				else
					echo
						"<div class='form-entry-title'>" .
						$element['Title'].":" .
						"</div>" .
						"<div class='form-entry-content'>" .
							"<b>";
				
				if (isset($element['AdditionalPreText']) && $element['AdditionalPreText'])
					echo $element['AdditionalPreText']." ";
					
				if ($element['Type'] == FORM_INPUT_TYPE_TEXTAREA)
					echo nl2br($value);
				else
					echo $value;
				
				if (isset($element['AdditionalText']) && $element['AdditionalText'])
					echo " ".$element['AdditionalText'];
				
				echo
							"</b>" .
						"</div>";
		
			} elseif ($element['Type'] == FORM_STATIC_TEXT) {
				echo 
					$element['Title'];
		
			} elseif ($element['Type'] == FORM_OPEN_FRAME_CONTAINER) {
				if ($prevelement && $prevelement['Type'] != FORM_CLOSE_FRAME_CONTAINER)
					$element['_SpacerRequired'] = true;
				
			} elseif ($element['Type'] == FORM_CLOSE_FRAME_CONTAINER) {
				if ($prevelement && $prevelement['Type'] != FORM_OPEN_FRAME_CONTAINER)
					echo 
							"<div class='clear-both'></div>" .
						"</div>" .
					"</div>";
			}
			
			if ($isinput || $element['Type'] == FORM_STATIC_TEXT) 
				echo
					"</div>";
			
			$prevelement = $element;
		}
	}
	
	function displayElements($elements) {
		if (!is_array($elements))
			return false;
		
		if (!$this->ignorePageBreaks && $this->selectedPage)
			echo
				"<input type='hidden' name='formpage' value='".$this->selectedPage."' />";
		
		$pages = count((array)$this->pageBreakElements);
		$currentpage = 1;
		$nextpage = null;
		
		$requiredelements = 0;
		$totalelements = count($elements)-1;
		$submitbuttonid = 0;		
		
		foreach($elements as $elementnum => $element) {
			if (!isset($element['Type']))
				continue;
			
			if ($element['Required'])
				$requiredelements++;
			
			if ($element['Type'] == FORM_INPUT_TYPE_VERIFICATION_CODE && 
				isset($element['VerifyResult']) && !$element['VerifyResult']) 
			{
				echo 
					"<input type='hidden' name='".$element['Name']."' " .
						"value='".htmlspecialchars($element['Value'], ENT_QUOTES)."' />";
				
				continue;
			}
			 
			if ($element['Type'] == FORM_PAGE_BREAK) {
				if ($this->ignorePageBreaks) {
					echo
						"<div class='clear-both'></div>" .
						"<div class='form-page-break comment'>" .
							$element['Title'] .
						"</div>" .
						"<hr />" .
						"<br />";
					
					continue;
				}
				
				if ($this->selectedPage == $currentpage)
					$nextpage = $element;
				
				$currentpage++;
				continue;
			}
			
			if (!$this->ignorePageBreaks && $this->selectedPage && 
				!in_array($element['Type'], array(
					FORM_INPUT_TYPE_SUBMIT,
					FORM_INPUT_TYPE_RESET, 
					FORM_INPUT_TYPE_BUTTON))) 
			{
				if ($currentpage < $this->selectedPage)
					$element['Type'] = FORM_INPUT_TYPE_HIDDEN;
				elseif ($currentpage > $this->selectedPage)
					continue;
			}
			
			if (in_array($element['Type'], array(
				FORM_INPUT_TYPE_HIDDEN, 
				FORM_INPUT_TYPE_REVIEW))) 
			{
				if ($element['ValueType'] == FORM_VALUE_TYPE_ARRAY) {
					foreach($element['Value'] as $value)
						echo 
							"<input type='hidden' name='".$element['Name']."[]' " .
								"value='".htmlspecialchars($value, ENT_QUOTES)."' />";
				} else {
					echo 
						"<input type='hidden' name='".$element['Name']."' " .
							"value='".htmlspecialchars($element['Value'], ENT_QUOTES)."' />";
				}
				
				if ($element['Type'] == FORM_INPUT_TYPE_HIDDEN)
					continue;
			}
			 
			if (in_array($element['Type'], array(
				FORM_INPUT_TYPE_SUBMIT,
				FORM_INPUT_TYPE_RESET, 
				FORM_INPUT_TYPE_BUTTON)))
			{
				if (isset($elements[($elementnum-1)]) && !in_array(
					$elements[($elementnum-1)]['Type'], array(
						FORM_INPUT_TYPE_SUBMIT,
						FORM_INPUT_TYPE_RESET,
						FORM_INPUT_TYPE_BUTTON)))
					echo
						"<div class='clear-both'></div>";
				
				if (isset($element['AdditionalPreText']) && $element['AdditionalPreText'])
					echo $element['AdditionalPreText'];
				
				if ($element['Type'] == FORM_INPUT_TYPE_SUBMIT) {
				
					if (!$this->ignorePageBreaks && $this->selectedPage && 
						$this->selectedPage <= $pages) 
					{
						$element['Title'] = __("Next") .
							($nextpage && trim($nextpage['Title'])?
								" (".$nextpage['Title'].")":
								null);
					}
					
					echo 
						"<input type='submit' " .
							"name='".$element['Name']."' " .
							"id='button".$element['EntryID']."' " .
							"class='button " .
							($submitbuttonid?
								"additional-":
								null) .
								"submit button-".$element['Name']."' " .
							"value='".htmlspecialchars($element['Title'], ENT_QUOTES)."' " .
							(isset($element['TooltipText']) &&
							 $element['TooltipText']?
							 	"title='".htmlspecialchars($element['TooltipText'], ENT_QUOTES)."' ":
							 	null) .
							$element['Attributes'] .
							" /> ";
					
					if (!$this->ignorePageBreaks && $this->selectedPage && 
						$this->selectedPage > 1) 
					{
						echo 
							"<input type='button' " .
								"class='button button-back' " .
								"value='".htmlspecialchars(__("Back"), ENT_QUOTES)."' " .
								"onclick=\"this.form.formpage.value=".
									(int)($this->selectedPage-1).
									"; this.form.submit();\" /> ";
					}
					
					if (!$submitbuttonid)
						$submitbuttonid = key($element);
				}
					
				if ($element['Type'] == FORM_INPUT_TYPE_RESET) {
					echo 
						"<input type='reset' " .
							"name='".$element['Name']."' " .
							"id='button".$element['EntryID']."' " .
							"class='button reset button-".$element['Name']."' " .
							"value='".htmlspecialchars($element['Title'], ENT_QUOTES)."' " .
							(isset($element['TooltipText']) &&
							 $element['TooltipText']?
							 	"title='".htmlspecialchars($element['TooltipText'], ENT_QUOTES)."' ":
							 	null) .
							$element['Attributes'] .
							" /> ";
				}
					
				if ($element['Type'] == FORM_INPUT_TYPE_BUTTON) {
					echo 
						"<input type='button' " .
							"name='".$element['Name']."' " .
							"id='button".$element['EntryID']."' " .
							"class='button button-".$element['Name']."' " .
							"value='".htmlspecialchars($element['Title'], ENT_QUOTES)."' " .
							(isset($element['TooltipText']) &&
							 $element['TooltipText']?
							 	"title='".htmlspecialchars($element['TooltipText'], ENT_QUOTES)."' ":
							 	null) .
							$element['Attributes'] .
							" /> ";
				}
				
				if (isset($element['AdditionalText']) && $element['AdditionalText'])
					echo $element['AdditionalText'];
					
				continue;
			}
			
			if ($element['Type'] == FORM_OPEN_FRAME_CONTAINER) {
				echo
				"<div class='fc" .
					($element['Name']?
						" fc-".url::genPathFromString($element['Name']):
						null) .
					form::fcState(
						'fc'.url::genPathFromString($element['Name']),
						$element['Required']) .
					"' ".$element['Attributes'].">" .
					"<a class='fc-title' ".
						($element['Name']?
							"name='fc".url::genPathFromString($element['Name'])."'":
							null) .
						">" .
						$element['Title'] .
					"</a>" .
					"<div class='fc-content'>";
				
				continue;
			} 
			
			if ($element['Type'] == FORM_CLOSE_FRAME_CONTAINER) {
				echo
						"<div class='clear-both'></div>" .
					"</div>" .
				"</div>";
				
				continue;
			}
			
			if ($element['Type'] == FORM_INPUT_TYPE_CONFIRM) {
				if (!$elements[($elementnum-1)])
					continue;
				
				$element['Type'] = $elements[($elementnum-1)]['Type'];
			}
			
			echo
				"<div class='form-entry" .
					($element['Name'] || $element['Title']?
						" form-entry-".
						($element['Name']?
							url::genPathFromString($element['Name']):
							url::genPathFromString($element['Title'])):
						null) .
					($element['Required']?
						" form-entry-required":
						null) .
					(isset($element['VerifyResult']) && $element['VerifyResult']?
						" form-entry-error":
						null) .
					($elementnum == 0?
						" first":
						null) .
					($elementnum == $totalelements?
						" last":
						null) .
					($element['Type'] == FORM_INPUT_TYPE_REVIEW?
						" preview":
						null) .
					"'>";
			
			if (in_array($element['Type'], array(
				FORM_INPUT_TYPE_TEXT,
				FORM_INPUT_TYPE_EMAIL,
				FORM_INPUT_TYPE_CHECKBOX, 
				FORM_INPUT_TYPE_RADIO,
				FORM_INPUT_TYPE_SELECT,
				FORM_INPUT_TYPE_MULTISELECT,
				FORM_INPUT_TYPE_RECIPIENT_SELECT,
				FORM_INPUT_TYPE_TEXTAREA,
				FORM_INPUT_TYPE_VERIFICATION_CODE,
				FORM_INPUT_TYPE_FILE,
				FORM_INPUT_TYPE_TIMESTAMP,
				FORM_INPUT_TYPE_DATE,
				FORM_INPUT_TYPE_TIME,
				FORM_INPUT_TYPE_PASSWORD,
				FORM_INPUT_TYPE_REVIEW,
				FORM_INPUT_TYPE_COLOR,
				FORM_INPUT_TYPE_SEARCH,
				FORM_INPUT_TYPE_TEL,
				FORM_INPUT_TYPE_URL,
				FORM_INPUT_TYPE_RANGE,
				FORM_INPUT_TYPE_NUMBER)))
			{
				echo
						"<div class='form-entry-title" .
							(isset($element['VerifyResult']) && $element['VerifyResult']?
								" red":
								null) .
							"'>".
							($element['Title']?
								$element['Title'].
								($element['Required']?
									'*':
									null) .
								":" .
								($element['Type'] == FORM_INPUT_TYPE_FILE?
									"<br /><span class='comment'>(" .
										sprintf(__("max %s"), files::humanSize(files::getUploadMaxFilesize())) .
									")</span>":
									null):
								null) .
							(isset($element['AdditionalTitle']) && $element['AdditionalTitle']?
								$element['AdditionalTitle']:
								null).
						"</div>" .
						"<div class='form-entry-content" .
							($element['Type'] == FORM_INPUT_TYPE_REVIEW?
								" bold":
								null) .
							"'>";
					
				if (isset($element['AdditionalPreText']) && $element['AdditionalPreText'])
					echo $element['AdditionalPreText'];
					
				if (in_array($element['Type'], array(
					FORM_INPUT_TYPE_TEXT,
					FORM_INPUT_TYPE_EMAIL,
					FORM_INPUT_TYPE_VERIFICATION_CODE,
					FORM_INPUT_TYPE_TIMESTAMP,
					FORM_INPUT_TYPE_DATE,
					FORM_INPUT_TYPE_TIME,
					FORM_INPUT_TYPE_COLOR,
					FORM_INPUT_TYPE_SEARCH,
					FORM_INPUT_TYPE_TEL,
					FORM_INPUT_TYPE_URL,
					FORM_INPUT_TYPE_RANGE,
					FORM_INPUT_TYPE_NUMBER))) 
				{
					echo 
						"<input type='";
					
					if (JCORE_VERSION >= '0.6') {
						if ($element['Type'] == FORM_INPUT_TYPE_EMAIL)
							echo "email";
						// Not using for now as date definition is a mess:
						// http://dev.w3.org/html5/markup/input.datetime.html#input.datetime
						/*elseif ($element['Type'] == FORM_INPUT_TYPE_TIMESTAMP)
							echo "datetime";*/
						elseif ($element['Type'] == FORM_INPUT_TYPE_DATE)
							echo "date";
						elseif ($element['Type'] == FORM_INPUT_TYPE_TIME)
							echo "time";
						elseif ($element['Type'] == FORM_INPUT_TYPE_COLOR)
							echo "color";
						elseif ($element['Type'] == FORM_INPUT_TYPE_SEARCH)
							echo "search";
						elseif ($element['Type'] == FORM_INPUT_TYPE_TEL)
							echo "tel";
						elseif ($element['Type'] == FORM_INPUT_TYPE_URL)
							echo "url";
						elseif ($element['Type'] == FORM_INPUT_TYPE_RANGE)
							echo "range";
						elseif ($element['Type'] == FORM_INPUT_TYPE_NUMBER)
							echo "number";
						else
							echo "text";
						
					} else {
						echo "text";
					}
					
					echo
							"' " .
							"name='".$element['Name']."' " .
							(isset($element['PlaceholderText']) &&
							 $element['PlaceholderText']?
							 	"placeholder='".htmlspecialchars($element['PlaceholderText'], ENT_QUOTES)."' ":
							 	null) .
							(isset($element['TooltipText']) &&
							 $element['TooltipText']?
							 	"title='".htmlspecialchars($element['TooltipText'], ENT_QUOTES)."' ":
							 	null) .
							"id='entry".$element['EntryID']."' " .
							"class='text-entry" .
								($element['Type'] == FORM_INPUT_TYPE_TIMESTAMP ||
								 $element['Type'] == FORM_INPUT_TYPE_DATE?
								 	" calendar-input":
								 	null).
								($element['Type'] == FORM_INPUT_TYPE_TIMESTAMP?
									" timestamp":
									null).
								($element['Type'] == FORM_INPUT_TYPE_COLOR?
									" color-input":
									null).
								"' " .
							"value='".htmlspecialchars($element['Value'], ENT_QUOTES)."' " .
							(isset($element['AutoFocus']) &&
							 $element['AutoFocus']?
							 	"autofocus='autofocus' ":
							 	null) .
							$element['Attributes'] .
							" /> ";
				}
				
				if ($element['Type'] == FORM_INPUT_TYPE_PASSWORD) {
					echo 
						"<input type='password' " .
							"name='".$element['Name']."' " .
							"id='entry".$element['EntryID']."' " .
							"class='text-entry' " .
							(isset($element['TooltipText']) &&
							 $element['TooltipText']?
							 	"title='".htmlspecialchars($element['TooltipText'], ENT_QUOTES)."' ":
							 	null) .
							// We do not want password fields to have predefined values
							//"value='".htmlspecialchars($element['Value'], ENT_QUOTES)."' " .
							$element['Attributes'] .
							" /> ";
				}
				
				if ($element['Type'] == FORM_INPUT_TYPE_CHECKBOX) {
					if (isset($element['Values']) && is_array($element['Values'])) {
						foreach($element['Values'] as $key => $value) {
							echo
								"<label>" .
								"<input type='checkbox' " .
									"name='".$element['Name']."[]' " .
									"id='entry".$element['EntryID'].$key."' " .
									"class='checkbox-entry' " .
									"value='".htmlspecialchars($value['Value'], ENT_QUOTES)."' " .
									(isset($element['TooltipText']) &&
									 $element['TooltipText']?
									 	"title='".htmlspecialchars($element['TooltipText'], ENT_QUOTES)."' ":
									 	null) .
									$element['Attributes'] .
									(is_array($element['Value']) && 
									 in_array($value['Value'], $element['Value'])?
										"checked='checked' ":
										null) .
									(isset($element['DisabledValues']) && 
									 is_array($element['DisabledValues']) && 
									 in_array($value['Value'], $element['DisabledValues'])?
										"disabled='disabled' ":
										null) .
									" /> " .
									($value['ValueText']?
											$value['ValueText']:
											$value['Value']).
								"</label> ";
						}
						
					} else {
						echo 
							"<input type='checkbox' " .
								"name='".$element['Name']."' " .
								"id='entry".$element['EntryID']."' " .
								"class='checkbox-entry' " .
								"value='".htmlspecialchars($element['OriginalValue'], ENT_QUOTES)."' " .
								(isset($element['TooltipText']) &&
								 $element['TooltipText']?
								 	"title='".htmlspecialchars($element['TooltipText'], ENT_QUOTES)."' ":
								 	null) .
								$element['Attributes'] .
								($element['OriginalValue'] == $element['Value'] ||
								 ($element['ValueType'] == FORM_VALUE_TYPE_BOOL && $element['Value'])?
									"checked='checked'":
									null) .
								" /> ";
					}
				}
						
				if ($element['Type'] == FORM_INPUT_TYPE_RADIO) {
					if (isset($element['Values']) && is_array($element['Values'])) {
						foreach($element['Values'] as $key => $value) {
							echo
								"<label>" .
								"<input type='radio' " .
									"name='".$element['Name']."' " .
									"id='entry".$element['EntryID'].$key."' " .
									"class='radio-entry' " .
									"value='".htmlspecialchars($value['Value'], ENT_QUOTES)."' " .
									(isset($element['TooltipText']) &&
									 $element['TooltipText']?
									 	"title='".htmlspecialchars($element['TooltipText'], ENT_QUOTES)."' ":
									 	null) .
									$element['Attributes'] .
									($value['Value'] == $element['Value']?
										"checked='checked' ":
										null) .
									(isset($element['DisabledValues']) &&
									 is_array($element['DisabledValues']) && 
									 in_array($value['Value'], $element['DisabledValues'])?
										"disabled='disabled' ":
										null) .
									" /> " .
									(isset($value['ValueText']) && $value['ValueText']?
											$value['ValueText']:
											$value['Value']).
								"</label> ";
						}
						
					} else {
						echo 
							"<input type='radio' " .
								"name='".$element['Name']."' " .
								"id='entry".$element['EntryID']."' " .
								"class='radio-entry' " .
								"value='".htmlspecialchars($element['OriginalValue'], ENT_QUOTES)."' " .
								(isset($element['TooltipText']) &&
								 $element['TooltipText']?
								 	"title='".htmlspecialchars($element['TooltipText'], ENT_QUOTES)."' ":
								 	null) .
								$element['Attributes'] .
								($element['OriginalValue'] == $element['Value'] ||
								 ($element['ValueType'] == FORM_VALUE_TYPE_BOOL && $element['Value'])?
									"checked='checked'":
									null) .
								" /> ";
					}
				}
						
				if (in_array($element['Type'], array(
					FORM_INPUT_TYPE_SELECT, FORM_INPUT_TYPE_RECIPIENT_SELECT))) 
				{
					echo 
						"<select " .
							"name='".$element['Name']."' " .
							"id='entry".$element['EntryID']."' " .
							"class='select-entry' " .
							(isset($element['TooltipText']) &&
							 $element['TooltipText']?
							 	"title='".htmlspecialchars($element['TooltipText'], ENT_QUOTES)."' ":
							 	null) .
							$element['Attributes'] .
							">";
					
					if (is_array($element['Values'])) {
						$optgroup = false;
						
						foreach($element['Values'] as $key => $value) {
							if (isset($element['GroupValues']) && 
								is_array($element['GroupValues']) && 
								in_array($value['Value'], $element['GroupValues']))
							{
								if ($optgroup)
									echo "</optgroup>";
								
								echo "<optgroup label='" .
									htmlspecialchars(
										(isset($value['ValueText']) && $value['ValueText']?
												$value['ValueText']:
												$value['Value']), ENT_QUOTES)."'>";
								
								$optgroup = true;
								continue;
							}
							
							echo
								"<option value='".
									($element['Type'] == FORM_INPUT_TYPE_RECIPIENT_SELECT?
										(int)($key+1):
										htmlspecialchars($value['Value'], ENT_QUOTES)) .
									"' " .
									(isset($element['DisabledValues']) && 
									 is_array($element['DisabledValues']) && 
									 in_array($value['Value'], $element['DisabledValues'])?
										"disabled='disabled' ":
										($value['Value'] == $element['Value'] ||
										 ($element['Type'] == FORM_INPUT_TYPE_RECIPIENT_SELECT &&
										 (int)($key+1) == $element['Value'])?
											"selected='selected' ":
											null)) .
									">" .
									(isset($value['ValueText']) && $value['ValueText']?
											$value['ValueText']:
											$value['Value']).
								"</option>";
						}
						
						if ($optgroup)
							echo "</optgroup>";
					}
					
					echo
						"</select>";
				}
					
				if ($element['Type'] == FORM_INPUT_TYPE_MULTISELECT) {
					echo 
						"<select multiple='multiple' " .
							"name='".$element['Name']."[]' " .
							"id='entry".$element['EntryID']."' " .
							"class='select-entry' " .
							(isset($element['TooltipText']) &&
							 $element['TooltipText']?
							 	"title='".htmlspecialchars($element['TooltipText'], ENT_QUOTES)."' ":
							 	null) .
							$element['Attributes'] .
							">";
					
					if (is_array($element['Values'])) {
						$optgroup = false;
						
						foreach($element['Values'] as $value) {
							if (isset($element['GroupValues']) && 
								is_array($element['GroupValues']) && 
								in_array($value['Value'], $element['GroupValues']))
							{
								if ($optgroup)
									echo "</optgroup>";
								
								echo "<optgroup label='" .
									htmlspecialchars(
										(isset($value['ValueText']) && $value['ValueText']?
												$value['ValueText']:
												$value['Value']), ENT_QUOTES)."'>";
								
								$optgroup = true;
								continue;
							}
							
							echo
								"<option value='".htmlspecialchars($value['Value'], ENT_QUOTES)."' " .
									(isset($element['DisabledValues']) && 
									 is_array($element['DisabledValues']) && 
									 in_array($value['Value'], $element['DisabledValues'])?
										"disabled='disabled' ":
										(is_array($element['Value']) && 
										 in_array($value['Value'], $element['Value'])?
											"selected='selected'":
											null)) .
									">" .
									(isset($value['ValueText']) && $value['ValueText']?
											$value['ValueText']:
											$value['Value']).
								"</option>";
						}
						
						if ($optgroup)
							echo "</optgroup>";
					}
					
					echo
						"</select>";
				}
				
				if ($element['Type'] == FORM_INPUT_TYPE_TEXTAREA) {
					echo 
						"<textarea " .
							(!stristr($element['Attributes'], 'rows=')?
								"rows='5' ":
								null) .
							(!stristr($element['Attributes'], 'cols=')?
								"cols='40' ":
								null) .
							"name='".$element['Name']."' " .
							"id='entry".$element['EntryID']."' " .
							"class='text-entry' " .
							(isset($element['TooltipText']) &&
							 $element['TooltipText']?
							 	"title='".htmlspecialchars($element['TooltipText'], ENT_QUOTES)."' ":
							 	null) .
							$element['Attributes'] .
							">" .
							htmlspecialchars($element['Value']) .
						"</textarea>";
				}
				
				if ($element['Type'] == FORM_INPUT_TYPE_FILE) {
					echo 
						($element['Value']?
							"<b>".$element['Value']."</b><br />":
							null).
						"<input type='file' " .
							"name='".$element['Name']."' " .
							"id='entry".$element['EntryID']."' " .
							"class='file-entry' " .
							"value='".htmlspecialchars($element['Value'], ENT_QUOTES)."' " .
							(isset($element['TooltipText']) &&
							 $element['TooltipText']?
							 	"title='".htmlspecialchars($element['TooltipText'], ENT_QUOTES)."' ":
							 	null) .
							$element['Attributes'] . " /> ";
				}
				
				if ($element['Type'] == FORM_INPUT_TYPE_REVIEW) {
					if ($element['ValueType'] == FORM_VALUE_TYPE_ARRAY)
						echo nl2br(implode('; ', $element['Value']));
					elseif ($element['ValueType'] == FORM_VALUE_TYPE_BOOL)
						echo ($element['Value']?__("Yes"):__("No"));
					else
						echo nl2br($element['Value']);
				}
					
				if (isset($element['AdditionalText']) && $element['AdditionalText'])
					echo $element['AdditionalText'];
				
				echo
						"</div>";
			}
					
			if ($element['Type'] == FORM_INPUT_TYPE_EDITOR) {
				echo
						"<textarea " .
							"style='width: 98%;' spellcheck='false' " .
							(!stristr($element['Attributes'], 'rows=')?
								"rows='15' ":
								null) .
							(!stristr($element['Attributes'], 'cols=')?
								"cols='40' ":
								null) .
							"name='".$element['Name']."' " .
							"id='entry".$element['EntryID']."' " .
							"class='text-entry ck-editor' " .
							$element['Attributes'] .
							">" .
							htmlspecialchars($element['Value']) .
						"</textarea>";
				
				ckEditor::display("entry".$element['EntryID']);
			}
			
			if ($element['Type'] == FORM_INPUT_TYPE_CODE_EDITOR) {
					echo 
						"<textarea " .
							"style='width: 98%;'  spellcheck='false' " .
							(!stristr($element['Attributes'], 'rows=')?
								"rows='15' ":
								null) .
							(!stristr($element['Attributes'], 'cols=')?
								"cols='40' ":
								null) .
							"name='".$element['Name']."' " .
							"id='entry".$element['EntryID']."' " .
							"class='text-entry code-editor' " .
							(isset($element['TooltipText']) &&
							 $element['TooltipText']?
							 	"title='".htmlspecialchars($element['TooltipText'], ENT_QUOTES)."' ":
							 	null) .
							$element['Attributes'] .
							">" .
							htmlspecialchars($element['Value']) .
						"</textarea>";
			}
			
			if ($element['Type'] == FORM_STATIC_TEXT) {
				echo $element['Title'];
			}
						
			echo
				"</div>";
		}
		
		if (!isset($this->footer) || $this->footer)
			echo
				"<div class='form-footer comment'>";
				
		$this->displayFooter($requiredelements);
		
		if (!isset($this->footer))
			$this->displayDefaultFooter($requiredelements);
		
		if (!isset($this->footer) || $this->footer)
			echo
				"</div>";
	}
	
	function displayTitle() {
		echo $this->title;
		
		if (!$this->ignorePageBreaks && count((array)$this->pageBreakElements))
			echo " ".sprintf(__("(Step %s of %s)"),
				$this->selectedPage, count($this->pageBreakElements)+1);
	}
	
	function displayContent() {
		if ($this->displayFormElement)		
			echo
				"<form action='".$this->action."' method='".$this->method."' " .
					"enctype='multipart/form-data' ".$this->attributes.">";
		
		$this->displayElements($this->elements);
		
		if ($this->displayFormElement)		
			echo
				"</form>";
	}
	
	function displayFooter($requiredelements = 0) {
		echo
			$this->footer;
	}
	
	function displayDefaultFooter($requiredelements = 0) {
		if (!$requiredelements)
			return;
		
		if (JCORE_VERSION >= '0.6') {
			echo
				"<p>";
			
			if ($requiredelements > 1)
				echo
					__("Fields marked with an asterisk (*) are required.");
			else
				echo
					__("Field marked with an asterisk (*) is required.");
			
			echo
				"</p>";
			
			return;
		}
		
		echo
			"<br />" .
			__("Field(s) marked with an asterisk (*) is/are required.");
	}
	
	function display($formdesign = true) {
		if (!is_array($this->elements))
			return false;
		
		if (!$formdesign)
			$this->displayDesign = false;
		
		echo 
			"<div id='".$this->id."form'" .
				" class='" .
				(JCORE_VERSION >= '0.6'?
					"form ":
					null) .
				"rounded-corners'>";
			
		if ($this->displayDesign) {
			echo
				"<div class='form-title rounded-corners-top'>";
			
			$this->displayTitle();
			
			echo
				"</div>" .
				"<div class='" .
					(JCORE_VERSION >= '0.6'?
						"form-content":
						"form") .
					" rounded-corners-bottom'>";
		}
			
		$this->displayContent();
		
		if ($this->displayDesign)
			echo
				"</div>"; //#form
		
		echo
			"</div>"; //#formid
	}
}

?>