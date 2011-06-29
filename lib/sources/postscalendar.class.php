<?php

/***************************************************************************
 *            postscalendar.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/monthcalendar.class.php');

class _postsCalendar extends monthCalendar {
	var $searchURL = null;
	var $pageID = null;
	var $weekDaysFormat = 'D';
	
	function __construct($pageid = null) {
		parent::__construct();
		
		$this->uriRequest = "posts/" .
			($pageid?
				$pageid."/":
				null) .
			$this->uriRequest;
		
		if (isset($_GET['searchin']) && isset($_GET['search']) && 
			$_GET['searchin'] == 'posts' && !isset($_GET['postscalendartime']))
		{
			$search = trim(strip_tags($_GET['search']));
			
			if (preg_match('/.*?:date=([0-9\-]+)/', $search))
				$this->time = strtotime(preg_replace('/.*?:date=([0-9\-]+)/', '\1', 
					$search));
		}
	}
	
	function displayDay($time) {
		$posts = sql::rows(sql::run(
			" SELECT `ID` FROM `{posts}`" .
			" WHERE 1" .
			($this->pageID?
				" AND `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
					(int)$this->pageID."'":
				null) .
			" AND `TimeStamp` >= '".date('Y-m-d', $time)." 00:00:00'" .
			" AND `TimeStamp` <= '".date('Y-m-d', $time)." 23:59:59'" .
			" LIMIT 1"));
		
		if ($posts)
			echo "<a href='".$this->searchURL .
					(strpos($this->searchURL, '?') === false?
						'?':
						'&amp;') .
					"search=date:".date('Y-m-d', $time) .
					"&amp;searchin=posts'>";
		
		parent::displayDay($time);
		
		if ($posts)
			echo "</a>";
	}
	
	function display() {
		$page = null;
		
		if (!$this->pageID) {
			$this->pageID = url::getPathID(0, $this->uriRequest);
			$page = pages::get($this->pageID);
		}
		
		if ($page)
			$this->searchURL = pages::generateLink($page);
		else
			$this->searchURL = modules::getOwnerURL('Search');
		
		if (!$this->searchURL)
			$this->searchURL = url::site()."index.php?";
		
		parent::display();
	}
}

?>