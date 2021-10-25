<?php

/***************************************************************************
 *            includes.fxn.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

date_default_timezone_set(PAGE_TIMEZONE);
sql::setTimeZone();

$sitehost = strtolower(@parse_url(SITE_URL, PHP_URL_HOST));
$currenthost = strtolower(
	(isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']?
		$_SERVER['HTTP_HOST']:
		$_SERVER['SERVER_NAME']));

if ($sitehost && $sitehost != $currenthost &&
	($sitehost == 'www.'.$currenthost || 'www.'.$sitehost == $currenthost) &&
	(!isset($_GET['ajax']) || !$_GET['ajax'] || !isset($_GET['request']) || !$_GET['request']))
{
	$https = false;
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off')
		$https = true;

	$redirecturl =
		'http'.($https?'s':null) .
		'://' .
		(isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER']?
			$_SERVER['PHP_AUTH_USER']:
			null) .
		(isset($_SERVER['PHP_AUTH_PW']) && $_SERVER['PHP_AUTH_PW']?
			':'.$_SERVER['PHP_AUTH_PW']:
			null) .
		((isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER']) ||
		 (isset($_SERVER['PHP_AUTH_PW']) && $_SERVER['PHP_AUTH_PW'])?
			'@':
			null) .
		$sitehost .
		(($_SERVER['SERVER_PORT'] != 80 && !$https) ||
		 ($_SERVER['SERVER_PORT'] != 443 && $https)?
			':'.$_SERVER['SERVER_PORT']:
			null) .
		$_SERVER['REQUEST_URI'];

	header('HTTP/1.1 301 Moved Permanently');
	header('Location: '.$redirecturl);

	exit();
}

if (!function_exists('ereg')) {
	function ereg($pattern, $string) {
		return preg_match('/'.$pattern.'/', $string);
	}
}

if (!function_exists('eregi')) {
	function eregi($pattern, $string) {
		return preg_match('/'.$pattern.'/i', $string);
	}
}

if (!function_exists('split')) {
	function split($pattern, $string) {
		return preg_split('/'.$pattern.'/', $string);
	}
}

if (!function_exists('spliti')) {
	function spliti($pattern, $string) {
		return preg_split('/'.$pattern.'/i', $string);
	}
}

// Dirty fix for websites with ISO-8859-2 charsets
function htmlchars($string, $flags = null) {
	return @htmlspecialchars($string, $flags, (strtoupper(PAGE_CHARSET) != 'ISO-8859-2'?PAGE_CHARSET:'ISO-8859-1'));
}

// If magic quotes are enabled, strip slashes from all user data
function stripslashes_recursive($var) {
	return (is_array($var) ? array_map('stripslashes_recursive', $var) : stripslashes($var));
}

if (get_magic_quotes_gpc()) {
	$_GET = stripslashes_recursive($_GET);
	$_POST = stripslashes_recursive($_POST);
	$_COOKIE = stripslashes_recursive($_COOKIE);
}

header('Content-Type: text/html; charset='.PAGE_CHARSET);
session_start();

include_once('lib/api.class.php');

if (((defined('MAINTENANCE_SUSPEND_WEBSITE') && MAINTENANCE_SUSPEND_WEBSITE) ||
	(defined('MAINTENANCE_WEBSITE_SUSPENDED') && MAINTENANCE_WEBSITE_SUSPENDED)) &&
	!preg_match('/admin\//', $_SERVER['PHP_SELF']) &&
	(!isset($_GET['ajax']) || !$_GET['ajax'] || !isset($_GET['request']) || !$_GET['request']))
{
	include_once('lib/url.class.php');
	include_once('lib/users.class.php');

	if (!users::fastCheck('Admin'))
		exit(MAINTENANCE_SUSPEND_TEXT);
}

if (!isset($GLOBALS['ADMIN']))
	$GLOBALS['ADMIN'] = false;

if (!isset($_SESSION['HTTP_REFERER']))
	$_SESSION['HTTP_REFERER'] = (isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'');

include_once('lib/url.class.php');
include_once('lib/users.class.php');
include_once('lib/languages.class.php');
include_once('lib/menus.class.php');
include_once('lib/pages.class.php');
include_once('lib/posts.class.php');
include_once('lib/rss.class.php');
include_once('lib/css.class.php');
include_once('lib/jquery.class.php');
include_once('lib/requests.class.php');
include_once('lib/counter.class.php');
include_once('lib/favoritelinks.class.php');
include_once('lib/notificationemails.class.php');
include_once('lib/gdata.class.php');
include_once('lib/template.class.php');

// Globally check user and have him available in all classes we have
$GLOBALS['USER'] = new users();
$GLOBALS['USER']->check();

// Starting from 1.0 we load all installed modules because
// these modules are now able to interact with the code using the API
modules::loadModules();

// We populate the followings so the page title and keywords
// and so on can be added to the html tags/titles
template::populate();
languages::populate();
pages::populate();
posts::populate();

// We look for requests (including ajax requests) and if the request
// isn't handled (for e.g. javascript disabled) then we forward the
// variables to the rest of the code, otherwise we exit here if ajax
$requests =  new requests();
$requests->display();
unset($requests);

?>