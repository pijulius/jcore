<?php

/***************************************************************************
 *            daycalendar.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

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
		api::callHooks(API_HOOK_BEFORE,
			'dayCalendar::dayCalendar', $this);
		
		$this->time = time();
		$this->uriRequest = strtolower(get_class($this));
		
		if (!$this->variable)
			$this->variable = strtolower(get_class($this)).'time';
		
		if (!$this->cssClass)
			$this->cssClass = strtolower(get_class($this));
		
		if (isset($_GET[$this->variable]))
			$this->time = (int)$_GET[$this->variable];
		
		api::callHooks(API_HOOK_AFTER,
			'dayCalendar::dayCalendar', $this);
	}
	
	function ajaxRequest() {
		api::callHooks(API_HOOK_BEFORE,
			'dayCalendar::ajaxRequest', $this);
		
		$this->display();
		
		api::callHooks(API_HOOK_AFTER,
			'dayCalendar::ajaxRequest', $this);
		
		return true;
	}
	
	function displayNavigation($time) {
		api::callHooks(API_HOOK_BEFORE,
			'dayCalendar::displayNavigation', $this, $time);
		
		echo
			"<div class='calendar-navigation'>";
		
		$this->displayPrevWeekButton($time);
		$this->displayPrevButton($time);
		$this->displayNextWeekButton($time);
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
			'dayCalendar::displayNavigation', $this, $time);
	}
	
	function displayTime($time) {
		api::callHooks(API_HOOK_BEFORE,
			'dayCalendar::displayTime', $this, $time);
		
		echo
			date($this->timeFormat, $time);
		
		api::callHooks(API_HOOK_AFTER,
			'dayCalendar::displayTime', $this, $time);
	}
	
	function displayPrevWeekButton($time) {
		api::callHooks(API_HOOK_BEFORE,
			'dayCalendar::displayPrevWeekButton', $this, $time);
		
		echo
			"<a class='calendar-prev more ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('-1 week', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"target='.day-calendar.".$this->cssClass."'>" .
				"<span>&lt;&lt;</span>" .
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'dayCalendar::displayPrevWeekButton', $this, $time);
	}
	
	function displayPrevButton($time) {
		api::callHooks(API_HOOK_BEFORE,
			'dayCalendar::displayPrevButton', $this, $time);
		
		echo
			"<a class='calendar-prev ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('-1 day', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"target='.day-calendar.".$this->cssClass."'>" .
				"<span>&lt;</span>" .
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'dayCalendar::displayPrevButton', $this, $time);
	}
	
	function displayNextWeekButton($time) {
		api::callHooks(API_HOOK_BEFORE,
			'dayCalendar::displayNextWeekButton', $this, $time);
		
		echo
			"<a class='calendar-next more ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('+1 week', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"target='.day-calendar.".$this->cssClass."'>" .
				"<span>&gt;&gt;</span>" .
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'dayCalendar::displayNextWeekButton', $this, $time);
	}
	
	function displayNextButton($time) {
		api::callHooks(API_HOOK_BEFORE,
			'dayCalendar::displayNextButton', $this, $time);
		
		echo
			"<a class='calendar-next ajax-content-link' href='" .
				url::uri($this->variable.', request') .
				"&amp;".$this->variable."=".strtotime('+1 day', $time) .
				"&amp;request=".$this->uriRequest."' " .
				"target='.day-calendar.".$this->cssClass."'>" .
				"<span>&gt;</span>" .
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'dayCalendar::displayNextButton', $this, $time);
	}
	
	function displayDayTitle($time) {
		api::callHooks(API_HOOK_BEFORE,
			'dayCalendar::displayDayTitle', $this, $time);
		
		echo
			__(date($this->dayFormat, $time));
		
		api::callHooks(API_HOOK_AFTER,
			'dayCalendar::displayDayTitle', $this, $time);
	}
	
	function displayHalfHour($time) {
		api::callHooks(API_HOOK_BEFORE,
			'dayCalendar::displayHalfHour', $this, $time);
		api::callHooks(API_HOOK_AFTER,
			'dayCalendar::displayHalfHour', $this, $time);
	}
	
	function displayHour($time) {
		api::callHooks(API_HOOK_BEFORE,
			'dayCalendar::displayHour', $this, $time);
		
		echo
			date($this->hourFormat, $time);
		
		api::callHooks(API_HOOK_AFTER,
			'dayCalendar::displayHour', $this, $time);
	}
	
	function display() {
		api::callHooks(API_HOOK_BEFORE,
			'dayCalendar::display', $this);
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'dayCalendar::display', $this);
	}
}
 
?>