<?php

/***************************************************************************
 *            weekcalendar.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/calendar.class.php');

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
		$handled = api::callHooks(API_HOOK_BEFORE,
			'weekCalendar::weekCalendar', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'weekCalendar::weekCalendar', $this, $handled);
			
			return $handled;
		}
		
		$this->time = time();
		$this->uriRequest = strtolower(get_class($this));
		
		if (defined('PAGE_FIRST_WEEKDAY'))
			$this->firstWeekDay = calendar::day2Int(PAGE_FIRST_WEEKDAY);
		
		if (!$this->variable)
			$this->variable = strtolower(get_class($this)).'time';
		
		if (!$this->cssClass)
			$this->cssClass = strtolower(get_class($this));
		
		if (isset($_GET[$this->variable]))
			$this->time = (int)$_GET[$this->variable];
		
		api::callHooks(API_HOOK_AFTER,
			'weekCalendar::weekCalendar', $this);
	}
	
	function startDay() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'weekCalendar::startDay', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'weekCalendar::startDay', $this, $handled);
			
			return $handled;
		}
		
		$time = strtotime('+'.$this->offset.' week', $this->time);
		$weekday = date('w', $time);
		
		if ($this->firstWeekDay == $weekday)
			$result = $time;
		
		else if ($this->firstWeekDay > $weekday)
			$result = mktime(0, 0, 0, date('m', $time), date('d', $time)+($this->firstWeekDay-$weekday), date('Y', $time));
		
		else
			$result = mktime(0, 0, 0, date('m', $time), date('d', $time)-($weekday-$this->firstWeekDay), date('Y', $time));
		
		api::callHooks(API_HOOK_AFTER,
			'weekCalendar::startDay', $this, $result);
		
		return $result;
	}
	
	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'weekCalendar::ajaxRequest', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'weekCalendar::ajaxRequest', $this, $handled);
			
			return $handled;
		}
		
		$this->display();
		
		$result = true;
		api::callHooks(API_HOOK_AFTER,
			'weekCalendar::ajaxRequest', $this, $result);
		
		return true;
	}
	
	function displayNavigation($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'weekCalendar::displayNavigation', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'weekCalendar::displayNavigation', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			"<div class='calendar-title'>";
		
		$this->displayPrevMonthButton($time);
		$this->displayPrevButton($time);
		$this->displayNextMonthButton($time);
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
		
		api::callHooks(API_HOOK_AFTER,
			'weekCalendar::displayNavigation', $this, $time);
	}
	
	function displayTime($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'weekCalendar::displayTime', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'weekCalendar::displayTime', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			date($this->timeFormat, $time);
		
		api::callHooks(API_HOOK_AFTER,
			'weekCalendar::displayTime', $this, $time);
	}
	
	function displayPrevMonthButton($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'weekCalendar::displayPrevMonthButton', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'weekCalendar::displayPrevMonthButton', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			"<a class='calendar-prev more ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('-1 month', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"target='.week-calendar.".$this->cssClass."'>" .
				"<span>&lt;&lt;</span>" .
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'weekCalendar::displayPrevMonthButton', $this, $time);
	}
	
	function displayPrevButton($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'weekCalendar::displayPrevButton', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'weekCalendar::displayPrevButton', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			"<a class='calendar-prev ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('-1 week', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"target='.week-calendar.".$this->cssClass."'>" .
				"<span>&lt;</span>" .
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'weekCalendar::displayPrevButton', $this, $time);
	}
	
	function displayNextMonthButton($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'weekCalendar::displayNextMonthButton', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'weekCalendar::displayNextMonthButton', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			"<a class='calendar-next more ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('+1 month', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"target='.week-calendar.".$this->cssClass."'>" .
				"<span>&gt;&gt;</span>" .
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'weekCalendar::displayNextMonthButton', $this, $time);
	}
	
	function displayNextButton($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'weekCalendar::displayNextButton', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'weekCalendar::displayNextButton', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			"<a class='calendar-next ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('+1 week', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"target='.week-calendar.".$this->cssClass."'>" .
				"<span>&gt;</span>" .
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'weekCalendar::displayNextButton', $this, $time);
	}
	
	function displayDayTitle($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'weekCalendar::displayDayTitle', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'weekCalendar::displayDayTitle', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			__(date($this->weekDaysFormat, $time));
		
		api::callHooks(API_HOOK_AFTER,
			'weekCalendar::displayDayTitle', $this, $time);
	}
	
	function displayHalfHour($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'weekCalendar::displayHalfHour', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'weekCalendar::displayHalfHour', $this, $time, $handled);
			
			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'weekCalendar::displayHalfHour', $this, $time);
	}
	
	function displayHour($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'weekCalendar::displayHour', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'weekCalendar::displayHour', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			date($this->hourFormat, $time);
		
		api::callHooks(API_HOOK_AFTER,
			'weekCalendar::displayHour', $this, $time);
	}
	
	function display() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'weekCalendar::display', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'weekCalendar::display', $this, $handled);
			
			return $handled;
		}
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'weekCalendar::display', $this);
	}
}
 
?>