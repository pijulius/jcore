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
		return strftime ($format,
			calendar::time2unix($timestamp));
	}
	
	static function time($timestamp = null, $format = PAGE_TIME_FORMAT) {
		return strftime ($format,
			calendar::time2unix($timestamp));
	}
	
	static function datetime($timestamp = null, $format = null) {
		if (!isset($format))
			$format = PAGE_DATE_FORMAT." ".PAGE_TIME_FORMAT;
		
		return strftime ($format,
			calendar::time2unix($timestamp));
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
		
		return true;
	}
	
	function display() {
		if ($this->displayArguments())
			return;
	}
}
 
?>