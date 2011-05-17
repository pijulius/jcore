<?php

/***************************************************************************
 *            css.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
include_once('lib/url.class.php');

if (defined('COMPRESSION_DISABLED'))
	_css::$compression = (COMPRESSION_DISABLED?false:true);

class _css {
	static $parseURLs = true;
	static $compression = true;
	
	var $ajaxRequest = null;
	
	// CSS Browser Selector based on Bastian Allgeier's work
	// http://bastian-allgeier.de/css_browser_selector
	static function browserSelector($ua = null) {
		$ua = ($ua?strtolower($ua):strtolower($_SERVER['HTTP_USER_AGENT']));		

		$g = 'gecko';
		$w = 'webkit';
		$s = 'safari';
		$b = array();
		
		// browser
		if(!preg_match('/opera|webtv/i', $ua) && preg_match('/msie\s(\d)/', $ua, $array))
			$b[] = 'ie ie' . $array[1];
		else if(strpos($ua, 'firefox/2') !== false)
			$b[] = $g . ' ff2';		
		else if(strpos($ua, 'firefox/3.5') !== false)
			$b[] = $g . ' ff3 ff3_5';
		else if(strpos($ua, 'firefox/3') !== false)
			$b[] = $g . ' ff3';
		else if(strpos($ua, 'gecko/') !== false)
			$b[] = $g;
		else if(preg_match('/opera(\s|\/)(\d+)/', $ua, $array))
			$b[] = 'opera opera' . $array[2];
		else if(strpos($ua, 'konqueror') !== false)
			$b[] = 'konqueror';
		else if(strpos($ua, 'chrome') !== false)
			$b[] = $w . ' ' . $s . ' chrome';
		else if(strpos($ua, 'iron') !== false)
			$b[] = $w . ' ' . $s . ' iron';
		else if(strpos($ua, 'applewebkit/') !== false)
			$b[] = (preg_match('/version\/(\d+)/i', $ua, $array)) ? $w . ' ' . $s . ' ' . $s . $array[1] : $w . ' ' . $s;
		else if(strpos($ua, 'mozilla/') !== false)
			$b[] = $g;
		
		// platform				
		if(strpos($ua, 'iphone') !== false)
			$b[] = 'iphone';		
		else if(strpos($ua, 'ipod') !== false)
			$b[] = 'ipod';		
		else if(strpos($ua, 'mac') !== false)
			$b[] = 'mac';		
		else if(strpos($ua, 'darwin') !== false)
			$b[] = 'mac';		
		else if(strpos($ua, 'webtv') !== false)
			$b[] = 'webtv';		
		else if(strpos($ua, 'win') !== false)
			$b[] = 'win';		
		else if(strpos($ua, 'freebsd') !== false)
			$b[] = 'freebsd';		
		else if(strpos($ua, 'x11') !== false || strpos($ua, 'linux') !== false)
			$b[] = 'linux';		
		
		if (MOBILE_BROWSER)
			$b[] = 'mobile';
		
		return join(' ', $b);
	}
	
	static function parseURL($matches) {
		$url = str_replace('../', '', trim($matches[2]));
		
		if (!preg_match('/^(https?:\/|\/)/i', $url)) {
			$admin = null;
			
			if (isset($_GET['admin']))
				$admin = $_GET['admin'];
			
			if (defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
				(!$admin || WEBSITE_TEMPLATE_SETFORADMIN))
				$url = url::site().'template/'.WEBSITE_TEMPLATE.'/'.$url;
			else
				$url = url::site().'template/'.$url;
		}
		
		return "url(".$matches[1].$url.$matches[1].")";
	}
	
	static function update() {
		return @touch(SITE_PATH.'template/template.css');
	}
	
	static function compress($buffer) {
		$buffer = 
			str_replace('http://icons.jcore.net/', url::jCore().'lib/icons/', 
			str_replace('%SITE_URL%', url::site(),
			str_replace('%JCORE_URL%', url::jCore(),
			str_replace('%ICONS_URL%', url::jCore().'lib/icons/',
			preg_replace(',/\*.*?\*/|\s+,s',' ',$buffer)))));
		
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) {
			if (SITE_URL != url::site())
				$buffer = str_replace(SITE_URL, url::site(), $buffer);
			
			if (defined('JCORE_URL') && JCORE_URL != url::jCore())
				$buffer = str_replace(JCORE_URL, url::jCore(), $buffer);
		}
		
		if (css::$parseURLs)
			$buffer = preg_replace_callback('/url ?+\((\'|")?(.*?)(\1)?\)/i',
				array('css', 'parseURL'), $buffer);
		
		if (!css::$compression)
			return $buffer;
		
		if (false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
			header('Vary: Accept-Encoding');
			header('Content-Encoding: gzip');
			return gzencode($buffer);
		}
		
		if (false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate')) {
			header('Vary: Accept-Encoding');
			header('Content-Encoding: deflate');
			return gzdeflate($buffer);
		}
		
		return $buffer;
	}
	
	function ajaxRequest() {
		session_write_close();
		$cachetime = 60*60*24*365;
		
		header('Pragma: public');
		header('Cache-Control: public, max-age='.$cachetime);
		header('Expires: '.gmdate('D, d M Y H:i:s', time()+$cachetime).' GMT');
		
		css::displayCSS();
		
		return true;
	}
	
	static function display3PIE() {
		if (defined('JCORE_PATH'))
			$filemtime = @filemtime(JCORE_PATH.'lib/jquery/css3pie.htc');
		else
			$filemtime = @filemtime(SITE_PATH.'lib/jquery/css3pie.htc');
		
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $filemtime).' GMT');
		header('Content-Type: text/x-component');
		
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && 
			(strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $filemtime)) 
		{
			header('HTTP/1.0 304 Not Modified');
			return true;
		}
		
		ob_start(array('css', 'compress'));
		css::$parseURLs = false;
		
		echo 
			@file_get_contents('lib/jquery/css3pie.htc', 
				FILE_USE_INCLUDE_PATH)."\n";
		
		ob_end_flush();
		return true;
	}
	
	static function displayCSS() {
		$admin = null;
		
		if (isset($_GET['admin']))
			$admin = $_GET['admin'];
		
		$filemtime = @filemtime(SITE_PATH.'template/template.css');
		
		if (defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
			(!$admin || WEBSITE_TEMPLATE_SETFORADMIN))
		{
			$tfilemtime = @filemtime(SITE_PATH.'template/' .
				WEBSITE_TEMPLATE.'/template.css');
			
			if ($tfilemtime > $filemtime)
				$filemtime = $tfilemtime;
		}
		
		if ($admin) {
			if (defined('WEBSITE_TEMPLATE') && 
				WEBSITE_TEMPLATE && WEBSITE_TEMPLATE_SETFORADMIN)
				$afilemtime = @filemtime(SITE_PATH.'template/' .
					WEBSITE_TEMPLATE.'/admin.css');
			else
				$afilemtime = @filemtime(SITE_PATH.'template/admin.css');
			
			if ($afilemtime > $filemtime)
				$filemtime = $afilemtime;
			
			if (defined('JCORE_PATH')) {
				$jfilemtime = @filemtime(JCORE_PATH.'template/admin.css');
				
				if ($jfilemtime > $filemtime)
					$filemtime = $jfilemtime;
			}
		}
		
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $filemtime).' GMT');
		header('Content-Type: text/css');
		
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && 
			(strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $filemtime)) 
		{
			header('HTTP/1.0 304 Not Modified');
			return true;
		}
		
		ob_start(array('css', 'compress'));
		css::$parseURLs = true;
		
		if ($admin) {
			if (defined('JCORE_PATH'))
				echo 
					@file_get_contents(JCORE_PATH.'template/admin.css')."\n";
			else
				echo 
					@file_get_contents(SITE_PATH.'template/admin.css')."\n";
			
			if (defined('WEBSITE_TEMPLATE') && 
				WEBSITE_TEMPLATE && WEBSITE_TEMPLATE_SETFORADMIN)
				echo 
					@file_get_contents(SITE_PATH.'template/' .
						WEBSITE_TEMPLATE.'/admin.css')."\n";
		}
		
		$modules = sql::run(
			" SELECT `Name` FROM `{modules}`" .
			" WHERE `Installed`" .
			(JCORE_VERSION >= '0.9'?
				" AND !`Deactivated`":
				null));
			
		while($module = sql::fetch($modules)) {
			$cssfile = strtolower($module['Name']).'.css';
			
			if (defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
				(!$admin || WEBSITE_TEMPLATE_SETFORADMIN) &&
				@is_file(SITE_PATH.'template/'.WEBSITE_TEMPLATE .
					'/modules/css/'.$cssfile))
			{
				echo 
					@file_get_contents(SITE_PATH.'template/'.WEBSITE_TEMPLATE .
						'modules/css/'.$cssfile)."\n";
					
			} elseif (@is_file(SITE_PATH.'template/modules/css/'.$cssfile)) {
				echo 
					@file_get_contents(SITE_PATH.'template/modules/css/'.
						$cssfile)."\n";
			}
		}
			
		if (defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
			(!$admin || WEBSITE_TEMPLATE_SETFORADMIN))
			echo 
				@file_get_contents(SITE_PATH.'template/' .
					WEBSITE_TEMPLATE.'/template.css')."\n";
		else
			echo 
				@file_get_contents(SITE_PATH.'template/template.css')."\n";
		
		ob_end_flush();
		return true;
	}
	
	static function display() {
		$filemtime = @filemtime(SITE_PATH.'template/template.css');
		
		if (defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
			(!isset($GLOBALS['ADMIN']) || !$GLOBALS['ADMIN'] ||
			WEBSITE_TEMPLATE_SETFORADMIN)) 
		{
			$tfilemtime = @filemtime(SITE_PATH.'template/' .
				WEBSITE_TEMPLATE.'/template.css');
			
			if ($tfilemtime > $filemtime)
				$filemtime = $tfilemtime;
		}
		
		if (isset($GLOBALS['ADMIN']) && $GLOBALS['ADMIN']) {
			if (defined('WEBSITE_TEMPLATE') && 
				WEBSITE_TEMPLATE && WEBSITE_TEMPLATE_SETFORADMIN)
				$afilemtime = @filemtime(SITE_PATH.'template/' .
					WEBSITE_TEMPLATE.'/admin.css');
			else
				$afilemtime = @filemtime(SITE_PATH.'template/admin.css');
		
			if ($afilemtime > $filemtime)
				$filemtime = $afilemtime;
			
			if (defined('JCORE_PATH')) {
				$jfilemtime = @filemtime(JCORE_PATH.'template/admin.css');
				
				if ($jfilemtime > $filemtime)
					$filemtime = $jfilemtime;
			}
		}
		
		if (JCORE_VERSION >= '0.6')
			echo 
				"<link href='".url::site()."static.php?request=css" .
					(isset($GLOBALS['ADMIN']) && $GLOBALS['ADMIN']?
						"&amp;admin=1":
						null) .
					"&amp;".$filemtime.'-v'.JCORE_VERSION .
					(defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
					 (!isset($GLOBALS['ADMIN']) || !$GLOBALS['ADMIN'] ||
					  WEBSITE_TEMPLATE_SETFORADMIN)?
						'-t'.urlencode(WEBSITE_TEMPLATE):
						null) .
					"' " .
					"type='text/css' rel='stylesheet' />\n";
		else
			echo 
				"<link href='".url::site()."index.php?request=css" .
					(isset($GLOBALS['ADMIN']) && $GLOBALS['ADMIN']?
						"&amp;admin=1":
						null) .
					"&amp;ajax=1" .
					"&amp;".$filemtime.'-v'.JCORE_VERSION .
					(defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
					 (!isset($GLOBALS['ADMIN']) || !$GLOBALS['ADMIN'] ||
					  WEBSITE_TEMPLATE_SETFORADMIN)?
						'-t'.urlencode(WEBSITE_TEMPLATE):
						null) .
					"' " .
					"type='text/css' rel='stylesheet' />\n";
	}
}

?>