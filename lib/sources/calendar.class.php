<?php

/***************************************************************************
 *            calendar.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/

if (!defined('PAGE_DATE_FORMAT'))
	define('PAGE_DATE_FORMAT', '%a, %d %b %Y');

if (!defined('PAGE_TIME_FORMAT'))
	define('PAGE_TIME_FORMAT', '%H:%M:%S %Z');

if (!defined('PAGE_FIRST_WEEKDAY'))
	define('PAGE_FIRST_WEEKDAY', 1);

class _dayCalendar {
	var $time;
	var $offset = 0;
	var $startHour = 7;
	var $endHour = 21;
	var $dayFormat = 'l';
	var $hourFormat = 'g a';
	var $timeFormat = 'F j, Y';
	var $variable = null;
	var $cssClass = null;
	var $uriRequest;
	var $ajaxRequest = null;
	
	function __construct() {
		$this->time = time();
		$this->uriRequest = strtolower(get_class($this));
		
		if (!$this->variable)
			$this->variable = strtolower(get_class($this)).'time';
		
		if (!$this->cssClass)
			$this->cssClass = strtolower(get_class($this));
		
		if (isset($_GET[$this->variable]))
			$this->time = (int)$_GET[$this->variable];
	}
	
	function ajaxRequest() {
		$this->display();
		return true;
	}
	
	function displayNavigation($time) {
		echo
			"<div class='calendar-navigation'>";
		
		$this->displayPrevButton($time);
		$this->displayNextButton($time);
		
		echo
				"<div class='calendar-time'>" .
					"<span>";
		
		$this->displayTime($time);
		
		echo
					"</span>" .
				"</div>" .
				"<div class='clear-both'></div>" .
			"</div>";
	}
	
	function displayTime($time) {
		echo
			date($this->timeFormat, $time);
	}
	
	function displayPrevButton($time) {
		echo
			"<a class='calendar-prev ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('-1 day', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"target='.day-calendar.".$this->cssClass."'>" .
				"<span>&lt;</span>" .
			"</a>";
	}
	
	function displayNextButton($time) {
		echo
			"<a class='calendar-next ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('+1 day', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"target='.day-calendar.".$this->cssClass."'>" .
				"<span>&gt;</span>" .
			"</a>";
	}
	
	function displayDayTitle($time) {
		echo
			__(date($this->dayFormat, $time));
	}
	
	function displayHalfHour($time) {
	}
	
	function displayHour($time) {
		echo
			date($this->hourFormat, $time);
	}
	
	function display() {
		$offsettime = strtotime('+'.$this->offset.' day', $this->time);
		
		if (JCORE_VERSION >= '0.7') {
			if (!$this->ajaxRequest)
				echo
					"<div class='day-calendar ".$this->cssClass."'>";
			
			$this->displayNavigation($this->time);
		}
		
		echo
			"<table cellpadding='0' cellspacing='0' class='calendar day-calendar" .
				(date('Ymd', $this->time) == date('Ymd', $offsettime)?
					" selected":
					null) .
				(date('Ymd', $offsettime) == date('Ymd')?
					" calendar-today":
					null) .
				" list'>" .
			"<thead>" .
			"<tr class='lheader'>" .
				"<th colspan='2'><span class='nowrap'>";
		
		$this->displayDayTitle($offsettime);
		
		echo
				"</span></th>" .
			"</tr>" .
			"</thead>" .
			"<tbody>";
		
		$day = mktime($this->startHour, 0, 0, 
			date('m', $offsettime), date('d', $offsettime), date('Y', $offsettime));
			
		for ($i = $this->startHour; $i < $this->endHour; $i+=0.5) {
			$halfhour = $i-floor($i);
			
			echo
				"<tr class='calendar-hour" .
					($i%2?" pair":null) .
					"'>";
			
			if (!$halfhour) { 
				echo
						"<td class='calendar-hour-time' rowspan='2'>";
			
				$this->displayHour($day);
					
				echo
						"</td>";
			}
			
			echo
					"<td class='calendar-half-hour" .
						($day-60*30 < time() &&
						 $day > time()?
							" calendar-timeline":
							null) .
						(date('Ymd', $offsettime) == date('Ymd')?
							" calendar-today":
							null) .
						" auto-width'>";
				
			$this->displayHalfHour($day);
			
			echo
					"</td>";
			
			$day += 60*30;
			
			echo
				"</tr>";
		}
		
		echo
			"</tbody>" .
			"</table>";
		
		if (JCORE_VERSION >= '0.7') {
			if (!$this->ajaxRequest)
				echo
					"</div>";
		}
	}
}

class _weekCalendar {
	var $time;
	var $offset = 0;
	var $firstWeekDay = 0;
	var $dayStartHour = 7;
	var $dayEndHour = 21;
	var $weekDaysFormat = 'l, M j';
	var $hourFormat = 'g a';
	var $timeFormat = 'F, Y';
	var $variable = null;
	var $cssClass = null;
	var $uriRequest;
	var $ajaxRequest = null;
	
	function __construct() {
		$this->time = time();
		$this->uriRequest = strtolower(get_class($this));
		$this->firstWeekDay = calendar::day2Int(PAGE_FIRST_WEEKDAY);
		
		if (!$this->variable)
			$this->variable = strtolower(get_class($this)).'time';
		
		if (!$this->cssClass)
			$this->cssClass = strtolower(get_class($this));
		
		if (isset($_GET[$this->variable]))
			$this->time = (int)$_GET[$this->variable];
	}
	
	function startDay() {
		$time = strtotime('+'.$this->offset.' week', $this->time);
		$weekday = date('w', $time);
		
		if ($this->firstWeekDay == $weekday)
			return $time;
		
		if ($this->firstWeekDay > $weekday)
			return mktime(0, 0, 0, date('m', $time), date('d', $time)+($this->firstWeekDay-$weekday), date('Y', $time));
		
		return mktime(0, 0, 0, date('m', $time), date('d', $time)-($weekday-$this->firstWeekDay), date('Y', $time));
	}
	
	function ajaxRequest() {
		$this->display();
		return true;
	}
	
	function displayNavigation($time) {
		echo
			"<div class='calendar-title'>";
		
		$this->displayPrevButton($time);
		$this->displayNextButton($time);
		
		echo
				"<div class='calendar-time'>" .
					"<span>";
		
		$this->displayTime($time);
		
		echo
					"</span>" .
				"</div>" .
				"<div class='clear-both'></div>" .
			"</div>";
	}
	
	function displayTime($time) {
		echo
			date($this->timeFormat, $time);
	}
	
	function displayPrevButton($time) {
		echo
			"<a class='calendar-prev ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('-1 week', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"target='.week-calendar.".$this->cssClass."'>" .
				"<span>&lt;</span>" .
			"</a>";
	}
	
	function displayNextButton($time) {
		echo
			"<a class='calendar-next ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('+1 week', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"target='.week-calendar.".$this->cssClass."'>" .
				"<span>&gt;</span>" .
			"</a>";
	}
	
	function displayDayTitle($time) {
		echo
			__(date($this->weekDaysFormat, $time));
	}
	
	function displayHalfHour($time) {
	}
	
	function displayHour($time) {
		echo
			date($this->hourFormat, $time);
	}
	
	function display() {
		$startday = $this->startDay();
		
		if (JCORE_VERSION >= '0.7') {
			if (!$this->ajaxRequest)
				echo
					"<div class='week-calendar ".$this->cssClass."'>";
			
			$this->displayNavigation($this->time);
		}
		
		echo
			"<table cellpadding='0' cellspacing='0' class='calendar week-calendar" .
				(date('Ymd', $this->time) >= date('Ymd', $startday) && 
				 date('Ymd', $this->time) <= date('Ymd', strtotime('+6 day', $startday))?
					" selected":
					null) .
				" list'>" .
			"<thead>" .
			"<tr class='lheader'>" .
				"<th></th>";
			
		for ($i = 0; $i <= 6; $i++) {
			echo
				"<th><span class='nowrap'>";
			
			$this->displayDayTitle(strtotime('+'.$i.' day', $startday));
			
			echo
				"</span></th>";
		}
		
		echo
			"</tr>" .
			"</thead>" .
			"<tbody>";
		
		for ($i = $this->dayStartHour; $i < $this->dayEndHour; $i+=0.5) {
			$halfhour = $i-floor($i);
			
			$day = mktime($i, ($halfhour?30:0), 0, 
				date('m', $startday), date('d', $startday), date('Y', $startday));
			
			echo
				"<tr class='calendar-hour" .
					($i%2?" pair":null) .
					"'>";
			
			if (!$halfhour) {
				echo
						"<td class='calendar-hour-time' rowspan='2'>";
				
				$this->displayHour($day);
				
				echo
						"</td>";
			}
			
			for ($ii = 1; $ii <= 7; $ii++) {
				echo
					"<td class='calendar-half-hour" .
						(date('Ymd', $day) == date('Ymd', $this->time)?
							" selected":
							null) .
						(date('Ymd', $day) == date('Ymd')?
							" calendar-today":
							null) .
						($day-60*30 < time() &&
						 $day > time()?
							" calendar-timeline":
							null) .
						"'>";
				
				$this->displayHalfHour($day);
				
				echo
					"</td>";
				
				$day = strtotime('+1 day', $day);
			}
			
			echo
				"</tr>";
		}
		
		echo
			"</tbody>" .
			"</table>";
		
		if (JCORE_VERSION >= '0.7') {
			if (!$this->ajaxRequest)
				echo
					"</div>";
		}
	}
}

class _monthCalendar {
	var $time;
	var $offset = 0;
	var $firstWeekDay = 0;
	var $weekDaysFormat = 'l';
	var $timeFormat = 'F, Y';
	var $variable = null;
	var $cssClass = null;
	var $uriRequest;
	var $ajaxRequest = null;
	
	function __construct() {
		$this->time = time();
		$this->uriRequest = strtolower(get_class($this));
		$this->firstWeekDay = calendar::day2Int(PAGE_FIRST_WEEKDAY);
		
		if (!$this->variable)
			$this->variable = strtolower(get_class($this)).'time';
		
		if (!$this->cssClass)
			$this->cssClass = strtolower(get_class($this));
		
		if (isset($_GET[$this->variable]))
			$this->time = (int)$_GET[$this->variable];
	}
	
	function firstDay() {
		return mktime(0,0,0, 
			date('m', $this->time)+$this->offset, 1, date('Y', $this->time));
	}
	
	function startDay() {
		$time = $this->firstDay();
		$weekday = date('w', $time);
		
		if ($this->firstWeekDay == $weekday)
			return $time;
		
		if ($this->firstWeekDay > $weekday)
			return mktime(0, 0, 0, date('m', $time), -6+($this->firstWeekDay-$weekday), date('Y', $time));
		
		return mktime(0, 0, 0, date('m', $time), 1-($weekday-$this->firstWeekDay), date('Y', $time));
	}
	
	function ajaxRequest() {
		$this->display();
		return true;
	}
	
	function displayNavigation($time) {
		echo
			"<div class='calendar-navigation'>";
		
		$this->displayPrevButton($time);
		$this->displayNextButton($time);
		
		echo
				"<div class='calendar-time'>" .
					"<span>";
		
		$this->displayTime($time);
		
		echo
					"</span>" .
				"</div>" .
				"<div class='clear-both'></div>" .
			"</div>";
	}
	
	function displayTime($time) {
		echo
			date($this->timeFormat, $time);
	}
	
	function displayPrevButton($time) {
		echo
			"<a class='calendar-prev ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('-1 month', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"target='.month-calendar.".$this->cssClass."'>" .
				"<span>&lt;</span>" .
			"</a>";
	}
	
	function displayNextButton($time) {
		echo
			"<a class='calendar-next ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('+1 month', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"target='.month-calendar.".$this->cssClass."'>" .
				"<span>&gt;</span>" .
			"</a>";
	}
	
	function displayDayTitle($time) {
		echo
			__(date($this->weekDaysFormat, $time));
	}
	
	function displayDay($time) {
		echo
			date('j', $time);
	}
	
	function display() {
		$firstday = $this->firstDay();
		$startday = $this->startDay();
		
		if (JCORE_VERSION >= '0.7') {
			if (!$this->ajaxRequest)
				echo
					"<div class='month-calendar ".$this->cssClass."'>";
			
			$this->displayNavigation($this->time);
		}
		
		echo
			"<table cellpadding='0' cellspacing='0' class='calendar" .
				(date('Ym', $firstday) != date('Ym', $this->time)?
					" selected":
					null) .
				" list'>" .
			"<thead>" .
			"<tr class='lheader'>";
			
		for ($i = 0; $i <= 6; $i++) {
			echo
				"<th><span class='nowrap'>";
			
			$this->displayDayTitle(strtotime('+'.$i.' day', $startday));
			
			echo
				"</span></th>";
		}
		
		echo
			"</tr>" .
			"</thead>" .
			"<tbody>";
		
		for ($i = 1; $i <= 6; $i++) {
			echo
				"<tr class='calendar-week" .
					(date('Ymd', $this->time) >= date('Ymd', $startday) && 
					 date('Ymd', $this->time) <= date('Ymd', strtotime('+6 day', $startday))?
						" selected":
						null) .
					($i%2?" pair":null) .
					"'>";
			
			for ($ii = 0; $ii <= 6; $ii++) {
				echo
					"<td class='calendar-day" .
						(date('Ymd', $startday) == date('Ymd', $this->time)?
							" selected":
							null) .
						(date('Ym', $startday) != date('Ym', $firstday)?
							" comment":
							null) .
						(date('Ymd', $startday) == date('Ymd')?
							" calendar-today":
							null) .
						"'>";
				
				$this->displayDay($startday);
				
				echo
					"</td>";
				
				$startday = strtotime('+1 day', $startday);
			}
			
			echo
				"</tr>";
			
			if (date('Ym', $startday) > date('Ym', $firstday)) 
				break;
		}
		
		echo
			"</tbody>" .
			"</table>";
		
		if (JCORE_VERSION >= '0.7') {
			if (!$this->ajaxRequest)
				echo
					"</div>";
		}
	}
}

class _calendar {
	var $arguments = '';
	
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