<?php

/***************************************************************************
 *            jquery.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
include_once('lib/url.class.php');

if (defined('COMPRESSION_DISABLED'))
	_jQuery::$compression = (COMPRESSION_DISABLED?false:true);

class _jQuery {
	static $compression = true;
	
	var $path;
	var $ajaxRequest = null;
	
	// Deprecated, only used for compatibility reasons with old sites
	var $plugins = array(
		'cookie', 'loading', 'mailme', 'pngfix',
		'qtip', 'rating', 'ajaxlinks', 'ajaxform',
		'lightbox', 'ui', 'ui.datepicker', 'corner',
		'numberformat', 'tabby', 'fctoggle', 'jcore');
	
	static $requiredPlugins = array(
		'cookie', 'loading', 'ajaxlinks', 'ajaxform', 
		'lightbox', 'fctoggle', 'tabby', 'ui', 
		'ui.datepicker', 'tipsy');
		
	function __construct() {
		$this->path = url::jCore();
	}
	
	static function update() {
		return @touch(SITE_PATH.'template/template.js');
	}
	
	static function compress($buffer) {
		$buffer = 
			str_replace('%SITE_URL%', url::site(),
			str_replace('%JCORE_URL%', url::jCore(),
			str_replace('%ICONS_URL%', url::jCore().'lib/icons/', $buffer)));
		
		if (!jQuery::$compression)
			return $buffer;
		
		if (false !== stripos((string)$_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
			header('Vary: Accept-Encoding');
			header('Content-Encoding: gzip');
			return gzencode($buffer);
		}
		
		if (false !== stripos((string)$_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate')) {
			header('Vary: Accept-Encoding');
			header('Content-Encoding: deflate');
			return gzdeflate($buffer);
		}
		
		return $buffer;
	}
	
	static function addPlugin($plugin) {
		if (!$plugin)
			return false;
		
		$plugin = trim($plugin);
		$plugins = jQuery::getPlugins();
		
		if (in_array($plugin, $plugins))
			return true;
		
		$plugins[] = $plugin;
		sql::run(
			" UPDATE `{settings}` SET" .
			" `Value` = '".sql::escape(implode(', ', $plugins))."'" .
			" WHERE `ID` = 'jQuery_Load_Plugins';");
		
		if (sql::error())
			return false;
		
		return true;
	}
	
	static function removePlugin($plugin) {
		if (!$plugin)
			return false;
		
		$plugin = trim($plugin);
		$plugins = jQuery::getPlugins();
		
		if (!in_array($plugin, $plugins))
			return true;
		
		unset($plugins[array_search($plugin, $plugins)]);
		sql::run(
			" UPDATE `{settings}` SET" .
			" `Value` = '".sql::escape(implode(', ', $plugins))."'" .
			" WHERE `ID` = 'jQuery_Load_Plugins';");
		
		if (sql::error())
			return false;
		
		return true;
	}
	
	static function getPlugins() {
		$plugins = sql::fetch(sql::run(
			" SELECT `Value` FROM `{settings}`" .
			" WHERE `ID` = 'jQuery_Load_Plugins'"));
		
		return explode(',', str_replace(' ', '', $plugins['Value']));
	}
	
	function ajaxRequest() {
		$admin = null;
		$request = null;
		
		if (isset($_GET['admin']))
			$admin = (int)$_GET['admin'];
		
		if (isset($_GET['request']))
			$request = (string)$_GET['request'];
		
		if ($admin && 
			(!defined('JQUERY_LOAD_ADMIN_PLUGINS') || 
			 !JQUERY_LOAD_ADMIN_PLUGINS))
			 return true;
		
		session_write_close();
		$cachetime = 60*60*24*365;
		
		header('Pragma: public');
		header('Cache-Control: public, max-age='.$cachetime);
		header('Expires: '.gmdate('D, d M Y H:i:s', time()+$cachetime).' GMT');
		
		if ($request == 'jquery') {
			jQuery::displayJS();
			return true;
		}
		
		jQuery::displayPluginsJS();
		
		return true;
	}
	
	static function displayPluginsJS() {
		$admin = null;
		
		if (isset($_GET['admin']))
			$admin = (int)$_GET['admin'];
		
		$filemtime = @filemtime(SITE_PATH.'template/template.js');
		
		if (defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
			(!$admin || WEBSITE_TEMPLATE_SETFORADMIN)) 
		{
			$tfilemtime = @filemtime(SITE_PATH.'template/' .
				WEBSITE_TEMPLATE.'/template.js');
			
			if ($tfilemtime > $filemtime)
				$filemtime = $tfilemtime;
		}
		
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $filemtime).' GMT');
		header('Content-Type: application/x-javascript');
		
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && 
			(strtotime((string)$_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $filemtime)) 
		{
			header('HTTP/1.0 304 Not Modified');
			return true;
		}
		
		ob_start(array('jQuery', 'compress'));
		
		$plugins = null;
		$jsfiles = array();
		
		if (defined('JQUERY_LOAD_PLUGINS')) {
			$plugins = JQUERY_LOAD_PLUGINS;
			
		} elseif (isset($this) && isset($this->plugins)) {
			$plugins = implode(',', (array)$this->plugins);
		
		} else {
			$jquery = new jQuery();
			$plugins = implode(',', $jquery->plugins);
			unset($jquery);
		}
		
		if ($admin && defined('JQUERY_LOAD_ADMIN_PLUGINS') &&
			JQUERY_LOAD_ADMIN_PLUGINS) 
			$plugins .= ','.JQUERY_LOAD_ADMIN_PLUGINS;
		
		if (JCORE_VERSION < '0.6')
			echo 
				@file_get_contents('lib/jquery/jquery.js', 
					FILE_USE_INCLUDE_PATH)."\n";
		
		echo
			"var JCORE_VERSION = '".JCORE_VERSION."';";
		
		if (JCORE_VERSION >= '0.5') {
			$modules = sql::run(
				" SELECT `Name`" .
				(JCORE_VERSION >= '0.9'?
					", `jQueryPlugins`":
					null) .
				" FROM `{modules}`" .
				" WHERE `Installed` = 1" .
				(JCORE_VERSION >= '0.9'?
					" AND `Deactivated` = 0":
					null));
				
			while($module = sql::fetch($modules)) {
				if (JCORE_VERSION >= '0.9' && $module['jQueryPlugins'])
					$plugins .= ','.$module['jQueryPlugins'];
				
				$jsfile = strtolower($module['Name']).'.js';
				
				if (defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
					(!$admin || WEBSITE_TEMPLATE_SETFORADMIN) &&
					@is_file(SITE_PATH.'template/'.WEBSITE_TEMPLATE .
						'/modules/js/'.$jsfile))
				{
					$jsfiles[] = SITE_PATH.'template/'.WEBSITE_TEMPLATE .
							'modules/js/'.$jsfile;
						
				} elseif (@is_file(SITE_PATH.'template/modules/js/'.$jsfile)) {
					$jsfiles[] = SITE_PATH.'template/modules/js/'.$jsfile;
				}
			}
			
			if (defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
				(!$admin || WEBSITE_TEMPLATE_SETFORADMIN))
			{
				$jsfiles[] = SITE_PATH.'template/' .
						WEBSITE_TEMPLATE.'/template.js';
				
				if (JCORE_VERSION >= '0.9') {
					$template = sql::fetch(sql::run(
						" SELECT `jQueryPlugins` FROM `{templates}`" .
						" WHERE `Name` = '".sql::escape(WEBSITE_TEMPLATE)."'"));
					
					if ($template && $template['jQueryPlugins'])
						$plugins .= ','.$template['jQueryPlugins'];
				}
				
			} else {
				$jsfiles[] = SITE_PATH.'template/template.js';
			}
		}
		
		$plugins = explode(',', str_replace(' ', '', $plugins));
		$plugins = array_unique($plugins);
		
		if (!in_array('jcore', $plugins))
			$plugins[] = 'jcore';
		
		if ($admin)
			foreach (jQuery::$requiredPlugins as $plugin)
				if (!in_array($plugin, $plugins))
					$plugins[] = $plugin;
		
		foreach($plugins as $plugin) {
			if (!$plugin || $plugin == 'delay')
				continue;
			
			echo 
				@file_get_contents('lib/jquery/jquery.'.$plugin.'.js', 
					FILE_USE_INCLUDE_PATH)."\n";
		}
		
		foreach($jsfiles as $jsfile)
			echo @file_get_contents($jsfile)."\n";
		
		ob_end_flush();
		return true;
	}
	
	static function displayPlugins() {
		if (defined('JQUERY_DISABLED') && JQUERY_DISABLED &&
			(!isset($GLOBALS['ADMIN']) || !(bool)$GLOBALS['ADMIN']))
			return;
		
		$filemtime = @filemtime(SITE_PATH.'template/template.js');
		
		if (defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
			(!isset($GLOBALS['ADMIN']) || !(bool)$GLOBALS['ADMIN'] ||
			WEBSITE_TEMPLATE_SETFORADMIN)) 
		{
			$tfilemtime = @filemtime(SITE_PATH.'template/' .
				WEBSITE_TEMPLATE.'/template.js');
			
			if ($tfilemtime > $filemtime)
				$filemtime = $tfilemtime;
		}
		
		echo 
			"<script src='".url::site()."static.php?request=js" .
				(isset($GLOBALS['ADMIN']) && (bool)$GLOBALS['ADMIN']?
					"&amp;admin=1":
					null) .
				"&amp;".$filemtime.'-v'.JCORE_VERSION .
				(defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
				 (!isset($GLOBALS['ADMIN']) || !(bool)$GLOBALS['ADMIN'] ||
				  WEBSITE_TEMPLATE_SETFORADMIN)?
					'-t'.urlencode(WEBSITE_TEMPLATE):
					null) .
				"' " .
				"type='text/javascript'>" .
			"</script>\n";
	}
	
	static function displayJS() {
		$admin = null;
		
		if (isset($_GET['admin']))
			$admin = (int)$_GET['admin'];
		
		$filemtime = @filemtime(SITE_PATH.'template/template.js');
		
		if (defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
			(!$admin || WEBSITE_TEMPLATE_SETFORADMIN)) 
		{
			$tfilemtime = @filemtime(SITE_PATH.'template/' .
				WEBSITE_TEMPLATE.'/template.js');
			
			if ($tfilemtime > $filemtime)
				$filemtime = $tfilemtime;
		}
		
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $filemtime).' GMT');
		header('Content-Type: application/x-javascript');
		
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && 
			(strtotime((string)$_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $filemtime)) 
		{
			header('HTTP/1.0 304 Not Modified');
			return true;
		}
		
		ob_start(array('jQuery', 'compress'));
		
		echo 
			@file_get_contents('lib/jquery/jquery.js', 
				FILE_USE_INCLUDE_PATH)."\n";
		
		ob_end_flush();
		return true;
	}
	
	static function display() {
		if (defined('JQUERY_DISABLED') && JQUERY_DISABLED &&
			(!isset($GLOBALS['ADMIN']) || !(bool)$GLOBALS['ADMIN']))
			return;
		
		$filemtime = @filemtime(SITE_PATH.'template/template.js');
		
		if (defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
			(!isset($GLOBALS['ADMIN']) || !(bool)$GLOBALS['ADMIN'] ||
			WEBSITE_TEMPLATE_SETFORADMIN)) 
		{
			$tfilemtime = @filemtime(SITE_PATH.'template/' .
				WEBSITE_TEMPLATE.'/template.js');
			
			if ($tfilemtime > $filemtime)
				$filemtime = $tfilemtime;
		}
		
		if (JCORE_VERSION >= '0.6')
			echo 
				"<script src='".url::site()."static.php?request=jquery" .
					(isset($GLOBALS['ADMIN']) && (bool)$GLOBALS['ADMIN']?
						"&amp;admin=1":
						null) .
					"&amp;".$filemtime.'-v'.JCORE_VERSION .
					(defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE &&
					 (!isset($GLOBALS['ADMIN']) || !(bool)$GLOBALS['ADMIN'] ||
					  WEBSITE_TEMPLATE_SETFORADMIN)?
						'-t'.urlencode(WEBSITE_TEMPLATE):
						null) .
					"' " .
					"type='text/javascript'>" .
				"</script>\n";
		else
			echo 
				"<script src='".url::site()."index.php?request=js" .
					"&amp;ajax=1" .
					"&amp;".$filemtime.'-v'.JCORE_VERSION."' " .
					"type='text/javascript'>" .
				"</script>\n";
	}
}

?>