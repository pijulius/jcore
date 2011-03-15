<?php

/***************************************************************************
 *            flash.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/files.class.php');
include_once('lib/calendar.class.php');

class _flash {
	var $uriRequest;
	var $subFolder;
	var $rootPath;
	var $rootURL;
	
	function __construct() {
		$this->uriRequest = strtolower(get_class($this));
		$this->subFolder = date('Ym');
		$this->rootPath = SITE_PATH.'sitefiles/flash/';
		$this->rootURL = url::site().'sitefiles/flash/';
	}
	
	function upload($file, $to) {
		return files::upload($file, $to.$this->subFolder.'/');
	}
}

?>