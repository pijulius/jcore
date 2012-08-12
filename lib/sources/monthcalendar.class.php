<?php

/***************************************************************************
 *            monthcalendar.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/calendar.class.php');

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
		$handled = api::callHooks(API_HOOK_BEFORE,
			'monthCalendar::monthCalendar', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'monthCalendar::monthCalendar', $this, $handled);
			
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
			'monthCalendar::monthCalendar', $this);
	}
	
	function firstDay() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'monthCalendar::firstDay', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'monthCalendar::firstDay', $this, $handled);
			
			return $handled;
		}
		
		$result = mktime(0,0,0, 
			date('m', $this->time)+$this->offset, 1, date('Y', $this->time));
		
		api::callHooks(API_HOOK_AFTER,
			'monthCalendar::firstDay', $this, $result);
		
		return $result;
	}
	
	function startDay() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'monthCalendar::startDay', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'monthCalendar::startDay', $this, $handled);
			
			return $handled;
		}
		
		$time = $this->firstDay();
		$weekday = date('w', $time);
		
		if ($this->firstWeekDay == $weekday)
			$result = $time;
		
		else if ($this->firstWeekDay > $weekday)
			$result = mktime(0, 0, 0, date('m', $time), -6+($this->firstWeekDay-$weekday), date('Y', $time));
			
		else
			$result = mktime(0, 0, 0, date('m', $time), 1-($weekday-$this->firstWeekDay), date('Y', $time));
		
		api::callHooks(API_HOOK_AFTER,
			'monthCalendar::startDay', $this, $result);
		
		return $result;
	}
	
	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'monthCalendar::ajaxRequest', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'monthCalendar::ajaxRequest', $this, $handled);
			
			return $handled;
		}
		
		$result = $this->display();
		
		api::callHooks(API_HOOK_AFTER,
			'monthCalendar::ajaxRequest', $this, $result);
		
		return true;
	}
	
	function displayNavigation($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'monthCalendar::displayNavigation', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'monthCalendar::displayNavigation', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			"<div class='calendar-navigation'>";
		
		$this->displayPrevYearButton($time);
		$this->displayPrevButton($time);
		$this->displayNextYearButton($time);
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
			'monthCalendar::displayNavigation', $this, $time);
	}
	
	function displayTime($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'monthCalendar::displayTime', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'monthCalendar::displayTime', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			date($this->timeFormat, $time);
		
		api::callHooks(API_HOOK_AFTER,
			'monthCalendar::displayTime', $this, $time);
	}
	
	function displayPrevYearButton($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'monthCalendar::displayPrevYearButton', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'monthCalendar::displayPrevYearButton', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			"<a class='calendar-prev more ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('-1 year', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"data-target='.month-calendar.".$this->cssClass."'>" .
				"<span>&lt;&lt;</span>" .
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'monthCalendar::displayPrevYearButton', $this, $time);
	}
	
	function displayPrevButton($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'monthCalendar::displayPrevButton', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'monthCalendar::displayPrevButton', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			"<a class='calendar-prev ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('-1 month', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"data-target='.month-calendar.".$this->cssClass."'>" .
				"<span>&lt;</span>" .
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'monthCalendar::displayPrevButton', $this, $time);
	}
	
	function displayNextYearButton($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'monthCalendar::displayNextYearButton', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'monthCalendar::displayNextYearButton', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			"<a class='calendar-next more ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('+1 year', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"data-target='.month-calendar.".$this->cssClass."'>" .
				"<span>&gt;&gt;</span>" .
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'monthCalendar::displayNextYearButton', $this, $time);
	}
	
	function displayNextButton($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'monthCalendar::displayNextButton', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'monthCalendar::displayNextButton', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			"<a class='calendar-next ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('+1 month', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"data-target='.month-calendar.".$this->cssClass."'>" .
				"<span>&gt;</span>" .
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'monthCalendar::displayNextButton', $this, $time);
	}
	
	function displayDayTitle($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'monthCalendar::displayDayTitle', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'monthCalendar::displayDayTitle', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			__(date($this->weekDaysFormat, $time));
		
		api::callHooks(API_HOOK_AFTER,
			'monthCalendar::displayDayTitle', $this, $time);
	}
	
	function displayDay($time) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'monthCalendar::displayDay', $this, $time);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'monthCalendar::displayDay', $this, $time, $handled);
			
			return $handled;
		}
		
		echo
			date('j', $time);
		
		api::callHooks(API_HOOK_AFTER,
			'monthCalendar::displayDay', $this, $time);
	}
	
	function display() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'monthCalendar::display', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'monthCalendar::display', $this, $handled);
			
			return $handled;
		}
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'monthCalendar::display', $this);
	}
}
 
?>