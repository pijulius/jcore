<?php

/***************************************************************************
 *            index.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

	$ROOT_DIR = '';

	if (isset($_GET['path']) && $_GET['path']) {
		$values = explode('/', $_GET['path']);
		foreach ($values as $key => $value) {
			if ($key > 0) $ROOT_DIR .= '../';
		}
	}

	define('ROOT_DIR', $ROOT_DIR);

	include_once('config.inc.php');
	include_once('lib/includes.fxn.php');

	include_once('template/template.php');

	sql::logout();
?>