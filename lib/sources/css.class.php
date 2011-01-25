<?php

/***************************************************************************
 *            css.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
include_once('lib/url.class.php');

class _css {
	static $parseURLs = true;
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
		if(!preg_match('/opera|webtv/i', $ua) && preg_match('/msie\s(\d)/', $ua, $array)) {
			$b[] = 'ie ie' . $array[1];
		} else if(strstr($ua, 'firefox/2')) {
			$b[] = $g . ' ff2';		
		} else if(strstr($ua, 'firefox/3.5')) {
			$b[] = $g . ' ff3 ff3_5';
		} else if(strstr($ua, 'firefox/3')) {
			$b[] = $g . ' ff3';
		} else if(strstr($ua, 'gecko/')) {
			$b[] = $g;
		} else if(preg_match('/opera(\s|\/)(\d+)/', $ua, $array)) {
			$b[] = 'opera opera' . $array[2];
		} else if(strstr($ua, 'konqueror')) {
			$b[] = 'konqueror';
		} else if(strstr($ua, 'chrome')) {
			$b[] = $w . ' ' . $s . ' chrome';
		} else if(strstr($ua, 'iron')) {
			$b[] = $w . ' ' . $s . ' iron';
		} else if(strstr($ua, 'applewebkit/')) {
			$b[] = (preg_match('/version\/(\d+)/i', $ua, $array)) ? $w . ' ' . $s . ' ' . $s . $array[1] : $w . ' ' . $s;
		} else if(strstr($ua, 'mozilla/')) {
			$b[] = $g;
		}

		// platform				
		if(strstr($ua, 'iphone')) {
			$b[] = 'iphone';		
		} else if(strstr($ua, 'ipod')) {
			$b[] = 'ipod';		
		} else if(strstr($ua, 'mac')) {
			$b[] = 'mac';		
		} else if(strstr($ua, 'darwin')) {
			$b[] = 'mac';		
		} else if(strstr($ua, 'webtv')) {
			$b[] = 'webtv';		
		} else if(strstr($ua, 'win')) {
			$b[] = 'win';		
		} else if(strstr($ua, 'freebsd')) {
			$b[] = 'freebsd';		
		} else if(strstr($ua, 'x11') || strstr($ua, 'linux')) {
			$b[] = 'linux';		
		}
		
		// Detect if mobile
		if (preg_match('/android|avantgo|blackberry|blazer|compal|elaine|fennec|' .
			'hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|' .
			'opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|' .
			'symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|' .
			'xda|xiino/i',$ua) || 
			preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|' .
			'ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|' .
			'ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|' .
			'bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|' .
			'cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|' .
			'dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|' .
			'er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|' .
			'gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|' .
			'hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|' .
			'hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|' .
			'im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|' .
			'klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|' .
			'\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|' .
			'm\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|' .
			't(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|' .
			'n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|' .
			'nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|' .
			'pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|' .
			'psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|' .
			'raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|' .
			'sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|' .
			'sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|' .
			'sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|' .
			'tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|' .
			'v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|' .
			'53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|' .
			'wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i',substr($ua,0,4)))
		{
			$b[] = 'mobile';
		}
		
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
	
	static function display3PIE($compress = true) {
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
		
		if ($compress)
			ob_start(array('css', 'compress'));
		
		css::$parseURLs = false;
		
		echo 
			@file_get_contents('lib/jquery/css3pie.htc', 
				FILE_USE_INCLUDE_PATH)."\n";
		
		if ($compress)
			ob_end_flush();
		
		return true;
	}
	
	static function displayCSS($compress = true) {
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
		
		if ($compress)
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
			" WHERE `Installed`");
			
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
		
		if ($compress)
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