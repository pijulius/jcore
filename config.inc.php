<?php

/***************************************************************************
 *            config.inc.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
@define('SQL_HOST', 'localhost');
@define('SQL_DATABASE', 'yourdomain_DB');
@define('SQL_USER', 'yourdomain_mysqluser');
@define('SQL_PASS', 'mysqlpass');
@define('SQL_PREFIX', '');

@define('SITE_URL', 'http://yourdomain.com/');
@define('SITE_PATH', '/home/yourdomain/public_html/');

@define('SEO_FRIENDLY_LINKS', true);

/*
 *  Do Not touch these unless you know what to do
 */
 
@define('JCORE_VERSION', '0.9');

if (!defined('ROOT_DIR')) 
	define('ROOT_DIR', '');

set_include_path(get_include_path().PATH_SEPARATOR.SITE_PATH);

if (defined('JCORE_PATH'))
	set_include_path(get_include_path().PATH_SEPARATOR.JCORE_PATH);

include_once('lib/settings.class.php');

$settings = new settings();
$settings->defineSettings();
unset($settings);

if (defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE) {
	if (defined('JCORE_PATH'))
		set_include_path(str_replace(SITE_PATH, 
			SITE_PATH.PATH_SEPARATOR.SITE_PATH.'template/'.
			preg_replace('/[^a-zA-Z0-9\@\.\_\- ]/', '', WEBSITE_TEMPLATE), 
			get_include_path()));
	else
		set_include_path(SITE_PATH.'template/'.
			preg_replace('/[^a-zA-Z0-9\@\.\_\- ]/', '', WEBSITE_TEMPLATE) .
			PATH_SEPARATOR.get_include_path());
}

?>