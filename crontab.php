<?php

/***************************************************************************
 *            crontab.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('config.inc.php');
include_once('lib/includes.fxn.php');

$code = null;
$do = null;

if (isset($_GET['code']))
	$code = $_GET['code'];

if (isset($_GET['do']))
	$do = $_GET['do'];

if (!$code || !$do) {
	exit("Didn't you forget something?!");
}

if ($code != '98430123') {
	include_once('lib/email.class.php');

	$email = new email();

	$email->load('WebmasterWarning');
	$email->to = WEBMASTER_EMAIL;

	$email->variables = array(
		"Warning" => 'Hmmmm...');

	$email->send();
	unset($email);

	exit("Hmmmmm...");
}

?>