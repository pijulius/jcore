<?php

/***************************************************************************
 *            monthcalendar.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
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
 
?>