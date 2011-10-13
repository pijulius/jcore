<?php

/***************************************************************************
 *            calendar.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

if (!defined('PAGE_DATE_FORMAT'))
	define('PAGE_DATE_FORMAT', '%a, %d %b %Y');

if (!defined('PAGE_TIME_FORMAT'))
	define('PAGE_TIME_FORMAT', '%H:%M:%S %Z');

if (!defined('PAGE_FIRST_WEEKDAY'))
	define('PAGE_FIRST_WEEKDAY', 'Sunday');

class _calendar {
	var $arguments = null;
	
	static function time2unix($datetime = null) {
		if (is_int($datetime))
			return $datetime;
			
		if (!$datetime)
			$datetime = date('Y-m-d H:i:s');
		
		return strtotime($datetime);
	}
	
	static function date($timestamp = null, $format = PAGE_DATE_FORMAT) {
		api::callHooks(API_HOOK_BEFORE,
			'calendar::date', $_ENV, $timestamp, $format);
		
		$result = strftime ($format,
			calendar::time2unix($timestamp));
		
		api::callHooks(API_HOOK_AFTER,
			'calendar::date', $_ENV, $timestamp, $format, $result);
		
		return $result;
	}
	
	static function time($timestamp = null, $format = PAGE_TIME_FORMAT) {
		api::callHooks(API_HOOK_BEFORE,
			'calendar::time', $_ENV, $timestamp, $format);
		
		$result = strftime ($format,
			calendar::time2unix($timestamp));
		
		api::callHooks(API_HOOK_AFTER,
			'calendar::time', $_ENV, $timestamp, $format, $result);
		
		return $result;
	}
	
	static function datetime($timestamp = null, $format = null) {
		api::callHooks(API_HOOK_BEFORE,
			'calendar::datetime', $_ENV, $timestamp, $format);
		
		if (!isset($format))
			$format = PAGE_DATE_FORMAT." ".PAGE_TIME_FORMAT;
		
		$result = strftime ($format,
			calendar::time2unix($timestamp));
		
		api::callHooks(API_HOOK_AFTER,
			'calendar::datetime', $_ENV, $timestamp, $format, $result);
		
		return $result;
	}
	
	static function int2Month($month) {
		return strftime("%B", mktime(0,0,0,$month,1,2000));
	}
	
	static function int2Day($day) {
		return strftime("%A", mktime(0,0,0,1,$day+4,2004));
	}
	
	static function day2Int($day) {
		if (is_numeric($day))
			return $day;
		
		return date("w", strtotime($day));
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'calendar::displayArguments', $this);
		
		$args = explode('/', $this->arguments);
		
		$argument = null;
		$parameters = null;
		
		if (isset($args[0]))
			$argument = $args[0];
			
		if (isset($args[1]))
			$parameters = $args[1];
			
		switch(strtolower($argument)) {
			case 'date':
				echo date($parameters);
				break;
			default:
				break;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'calendar::displayArguments', $this);
		
		return true;
	}
	
	function display() {
		if ($this->displayArguments())
			return;
		
		api::callHooks(API_HOOK_BEFORE,
			'calendar::display', $this);
		api::callHooks(API_HOOK_AFTER,
			'calendar::display', $this);
	}
}
 
?>