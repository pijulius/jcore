<?php

/***************************************************************************
 *            includes.fxn.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/

date_default_timezone_set(PAGE_TIMEZONE);
sql::setTimeZone();

if (((defined('MAINTENANCE_SUSPEND_WEBSITE') && MAINTENANCE_SUSPEND_WEBSITE) ||
	(defined('MAINTENANCE_WEBSITE_SUSPENDED') && MAINTENANCE_WEBSITE_SUSPENDED)) &&
	!preg_match('/admin\//', $_SERVER['PHP_SELF']) && !isset($_GET['ajax']))
{ 
	include_once('lib/url.class.php');
	include_once('lib/users.class.php');
	
	if (!users::fastCheck('Admin'))
		exit(MAINTENANCE_SUSPEND_TEXT);
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

if (!isset($GLOBALS['ADMIN']))
	$GLOBALS['ADMIN'] = false;

header('Content-Type: text/html; charset='.PAGE_CHARSET);

session_start();

include_once('lib/url.class.php');
include_once('lib/users.class.php');
include_once('lib/languages.class.php');
include_once('lib/menus.class.php');
include_once('lib/posts.class.php');
include_once('lib/rss.class.php');
include_once('lib/css.class.php');
include_once('lib/jquery.class.php');
include_once('lib/requests.class.php');
include_once('lib/favoritelinks.class.php');
include_once('lib/notificationemails.class.php');
include_once('lib/gdata.class.php');
include_once('lib/template.class.php');

// Globally check user and have him available in all classes we have
$GLOBALS['USER'] = new users();
$GLOBALS['USER']->check();

// We populate the followings so the page title and keywords
// and so on can be added to the html tags/titles
template::populate();
languages::populate();
menus::populate();
posts::populate();

// We look for requests (including ajax requests) and if the request
// isn't handled (for e.g. javascript disabled) then we forward the 
// variables to the rest of the code, otherwise we exit here if ajax
$requests =  new requests();
$requests->display();
unset($requests);

?>