<?php 

/***************************************************************************
 *            static.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
include_once('config.inc.php'); 


$request = null;
$ttl = 60*60*24*365;

if (isset($_GET['request']))
	$request = $_GET['request'];

if (isset($_GET['ttl']))
	$ttl = $_GET['ttl'];

header('Pragma: public');
header('Cache-Control: public, max-age='.$ttl);
header('Expires: '.gmdate('D, d M Y H:i:s', time()+$ttl).' GMT');

switch(strtolower($request)) {
	case 'jquery':
		include_once('lib/jquery.class.php');
		jQuery::displayJS();
		break;
		
	case 'js':
		include_once('lib/jquery.class.php');
		jQuery::displayPluginsJS();
		break;
		
	case 'css':
		include_once('lib/css.class.php');
		css::displayCSS();
		break;
		
	case 'css3pie':
		include_once('lib/css.class.php');
		css::display3PIE();
		break;
		
	case 'ckeditor':
		include_once('lib/ckeditor.class.php');
		ckEditor::displayJS();
		break;
		
	default:
		break;
}

?>