<?php

/***************************************************************************
 *            index.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/

	$GLOBALS['ADMIN'] = true;
	
	if (!ini_get('safe_mode'))
		@set_time_limit(0);
	
	if (!isset($_GET['path']) || !$_GET['path'])
		$_GET['path'] = 'admin';
	
	if (!defined('ROOT_DIR'))
		define('ROOT_DIR', '../');
	
	if (defined('JCORE_PATH'))
		include_once(JCORE_PATH.'config.inc.php');
	else  
		include_once('../config.inc.php');
	
	include_once('lib/includes.fxn.php');
	
	include_once('lib/admin.class.php');
	
	include_once('../template/template.php');
	
	sql::logout();
?>