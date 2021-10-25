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
		$handled = api::callHooks(API_HOOK_BEFORE,
			'flash::flash', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'flash::flash', $this, $handled);

			return $handled;
		}

		$this->uriRequest = strtolower(get_class($this));
		$this->subFolder = date('Ym');
		$this->rootPath = SITE_PATH.'sitefiles/flash/';
		$this->rootURL = url::site().'sitefiles/flash/';

		api::callHooks(API_HOOK_AFTER,
			'flash::flash', $this);
	}

	function upload($file, $to) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'flash::upload', $this, $file, $to);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'flash::upload', $this, $file, $to, $handled);

			return $handled;
		}

		$result = files::upload($file, $to.$this->subFolder.'/');

		api::callHooks(API_HOOK_AFTER,
			'flash::upload', $this, $file, $to, $result);

		return $result;
	}
}

?>