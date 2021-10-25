<?php

/***************************************************************************
 *            url.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

if (isset($_GET['path']))
	_url::$pagePath = strip_tags((string)$_GET['path']);

class _url {
	static $pageTitle = PAGE_TITLE;
	static $pageDescription = META_DESCRIPTION;
	static $pageKeywords = META_KEYWORDS;
	static $pagePath = '';

	static function addPageTitle($title) {
		if (!$title)
			return false;

		url::$pageTitle =
			strip_tags($title) .
			(url::$pageTitle?
				' - '.url::$pageTitle:
				null);

		return true;
	}

	static function addPageDescription($description) {
		if (!$description)
			return false;

		url::$pageDescription =
			strip_tags($description) .
			(url::$pageDescription?
				' '.url::$pageDescription:
				null);

		return true;
	}

	static function addPageKeywords($keywords) {
		if (!$keywords)
			return false;

		url::$pageKeywords =
			strip_tags($keywords) .
			(url::$pageKeywords?
				', '.url::$pageKeywords:
				null);

		return true;
	}

	static function setPageTitle($title) {
		url::$pageTitle = strip_tags($title);
	}

	static function setPageDescription($description) {
		url::$pageDescription = strip_tags($description);
	}

	static function setPageKeywords($keywords) {
		url::$pageKeywords = strip_tags($keywords);
	}

	static function getPageTitle() {
		return url::$pageTitle;
	}

	static function getPageDescription() {
		return url::$pageDescription;
	}

	static function getPageKeywords() {
		return url::$pageKeywords;
	}

	static function displayPageTitle($level = 0) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'url::displayPageTitle', $_ENV, $level);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'url::displayPageTitle', $_ENV, $level, $handled);

			return $handled;
		}

		$title = url::getPageTitle();

		if ($level) {
			$titles = explode(' - ', $title);

			for($i = 0; $i < $level; $i++) {
				if ($i > 0) echo ' - ';
				echo $titles[$i];
			}

		} else {
			echo $title;
		}

		api::callHooks(API_HOOK_AFTER,
			'url::displayPageTitle', $_ENV, $level);
	}

	static function displayPageDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'url::displayPageDescription', $_ENV);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'url::displayPageDescription', $_ENV, $handled);

			return $handled;
		}

		echo htmlchars(url::getPageDescription(), ENT_QUOTES);

		api::callHooks(API_HOOK_AFTER,
			'url::displayPageDescription', $_ENV);
	}

	static function displayPageKeywords() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'url::displayPageKeywords', $_ENV);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'url::displayPageKeywords', $_ENV, $handled);

			return $handled;
		}

		echo htmlchars(url::getPageKeywords(), ENT_QUOTES);

		api::callHooks(API_HOOK_AFTER,
			'url::displayPageKeywords', $_ENV);
	}

	static function site() {
		if (url::https())
			return str_replace('http://', 'https://', SITE_URL);

		return SITE_URL;
	}

	static function jCore() {
		$url = (defined('JCORE_URL')?JCORE_URL:SITE_URL);

		if (url::https())
			$url = str_replace('http://', 'https://', $url);

		return $url;
	}

	static function arg($argument) {
		if (!isset($_GET[$argument]))
			return null;

		return $argument.'='.strip_tags((string)$_GET[$argument]);
	}

	static function args($notincludeargs = null) {
		$uri = @parse_url(strip_tags((string)$_SERVER['REQUEST_URI']));

		if (!isset($uri['query']))
			return null;

		if (!$notincludeargs)
			return str_replace('&', '&amp;', $uri['query']);

		$args = explode('&', $uri['query']);
		$notincludeargs = explode(",", str_replace(" ", "", trim($notincludeargs, ' ,')));

		$rargs = null;
		foreach($args as $arg) {
			$expargs = explode('=', $arg);

			if (in_array($arg, $notincludeargs) ||
				in_array($expargs[0], $notincludeargs))
				continue;

			$rargs .= $arg."&amp;";
		}

		return substr($rargs, 0, strlen($rargs)-5);
	}

	static function getarg($argument) {
		if (!isset($_GET[$argument]))
			return null;

		return strip_tags((string)$_GET[$argument]);
	}

	static function delargs($args = null) {
		url::setURI(url::uri($args));
	}

	static function referer($striprequests = false, $notincludeargs = null, $inverse = false) {
		if (!isset($_SERVER['HTTP_REFERER']))
			return null;

		$referer = strip_tags((string)$_SERVER['HTTP_REFERER']);

		if ($striprequests)
			$referer = preg_replace('/((\?)|&)request=.*/i', '\\1', $referer);

		if (!$notincludeargs)
			return $referer;

		$uri = @parse_url($referer);

		if ($notincludeargs == 'ALL')
			return (isset($uri['path'])?$uri['path']:'');

		if (!isset($uri['query']))
			return (isset($uri['path'])?$uri['path']:'').'?';

		$args = explode('&', $uri['query']);
		$notincludeargs = explode(",", str_replace(" ", "", trim($notincludeargs, ' ,')));

		$rargs = null;
		foreach($args as $arg) {
			$expargs = explode('=', $arg);

			if ((!$inverse &&
				!in_array($arg, $notincludeargs) &&
				!in_array($expargs[0], $notincludeargs)) ||
				($inverse &&
				in_array($expargs[0], $notincludeargs)))
			{
				$rargs .= $arg."&";
			}
		}

		return (isset($uri['path'])?$uri['path']:'').'?' .
			substr($rargs, 0, strlen($rargs)-5);
	}

	static function setURI($uri) {
		$_SERVER['REQUEST_URI'] = str_replace('&amp;', '&', $uri);
	}

	static function uri($notincludeargs = null, $inverse = false) {
		if (!$notincludeargs)
			return str_replace('&', '&amp;', strip_tags((string)$_SERVER['REQUEST_URI'])).
				(strpos((string)$_SERVER['REQUEST_URI'], '?') === false?
					'?':
					null);

		$uri = @parse_url(strip_tags((string)$_SERVER['REQUEST_URI']));

		if ($notincludeargs == 'ALL')
			return (isset($uri['path'])?$uri['path']:'');

		if (!isset($uri['query']))
			return (isset($uri['path'])?$uri['path']:'').'?';

		$args = explode('&', $uri['query']);
		$notincludeargs = explode(",", str_replace(" ", "", trim($notincludeargs, ' ,')));

		$rargs = null;
		foreach($args as $arg) {
			$expargs = explode('=', $arg);

			if ((!$inverse &&
				!in_array($arg, $notincludeargs) &&
				!in_array($expargs[0], $notincludeargs)) ||
				($inverse &&
				in_array($expargs[0], $notincludeargs)))
			{
				$rargs .= $arg."&amp;";
			}
		}

		return (isset($uri['path'])?$uri['path']:'').'?' .
			substr($rargs, 0, strlen($rargs)-5);
	}

	static function get($args = null) {
		$https = url::https();
		$url = 'http'.($https?'s':null).'://'.
			(isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']?
				strip_tags((string)$_SERVER['HTTP_HOST']):
				strip_tags((string)$_SERVER['SERVER_NAME']));

		if (($_SERVER['SERVER_PORT'] != 80 && !$https) ||
			($_SERVER['SERVER_PORT'] != 443 && $https))
			$url .= ':'.(int)$_SERVER['SERVER_PORT'];

		$url .= url::uri($args);
		return $url;
	}

	static function fix($url, $reverse = false) {
		if (!$url)
			return null;

		$url = strip_tags($url);

		if (strpos($url, " ") !== false)
			$url = substr($url, 0, strpos($url, " "));

		if ($reverse)
			$url = preg_replace('/^(.*?):\/\//', null, strtolower($url));

		else if (!preg_match('/^(\/|.*?:\/\/)/', $url) &&
			preg_match('/(www|(.*?\..*))/', $url))
				$url = "http://".$url;

		return $url;
	}

	static function https() {
		if (!isset($_SERVER['HTTPS']) || !$_SERVER['HTTPS'] || $_SERVER['HTTPS'] == 'off')
			return false;

		return true;
	}

	static function parse($url, $component = -1) {
		return @parse_url($url, $component);
	}

	static function parseLinks($content) {
		return preg_replace_callback(
			"'(\"|\\'|>)?([[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/])'i",
				function ($matches) {
					if ($matches[1]) {
						return $matches[1].$matches[2];
					} else {
						if (strlen($matches[2]) > 70)
							return '<a href=\''.$matches[2].'\' target=\'_blank\'>'.substr($matches[2], 0, 70).'...</a>';
						else
							return '<a href=\''.$matches[2].'\' target=\'_blank\'>'.$matches[2].'</a>';
					}
				}, $content);
	}

	static function path($level= 0) {
		if (!url::$pagePath)
			return '';

		if (!$level)
			return trim(url::$pagePath, '/');

		$path = null;
		$exppaths = explode('/', url::$pagePath);

		foreach($exppaths as $key => $exppath) {
			if (!$exppath)
				continue;

			if ($path)
				$path .= '/';

			$path .= $exppath;

			if ($key == count($exppaths)-1-$level)
				break;
		}

		return $path;
	}

	static function setPath($path) {
		url::$pagePath = $path;
	}

	static function rootDomain($url = null) {
		$url = @parse_url($url?$url:url::site());

		if (isset($url['host']))
			return preg_replace('/(\/.*|^www\.)/', '', $url['host']);

		return null;
	}

	static function getPathID($level = 0, $path = null) {
		if (!$path)
			$path = url::path($level);

		if (!$path)
			return 0;

		preg_match('/.*\/([0-9]*)(\/|$|&)/', $path, $matches);

		if (isset($matches[1]))
			return (int)$matches[1];

		return 0;
	}

	static function generateLink($link) {
		return
			(!preg_match('/^\/|^javascript:|^(.*?):\/\//', $link)?
				url::site():
				null).
			$link;
	}

	static function genPathFromString($string, $lowercase = true) {
		$chars = array(
			'A' => '/&Agrave;|&Aacute;|&Acirc;|&Atilde;|&Auml;|&Aring;/',
			'a' => '/&agrave;|&aacute;|&acirc;|&atilde;|&auml;|&aring;|&ordf;/',
			'C' => '/&Ccedil;/',
			'c' => '/&ccedil;/',
			'E' => '/&Egrave;|&Eacute;|&Ecirc;|&Euml;/',
			'e' => '/&egrave;|&eacute;|&ecirc;|&euml;/',
			'I' => '/&Igrave;|&Iacute;|&Icirc;|&Iuml;/',
			'i' => '/&igrave;|&iacute;|&icirc;|&iuml;/',
			'N' => '/&Ntilde;/',
			'n' => '/&ntilde;/',
			'O' => '/&Ograve;|&Oacute;|&Ocirc;|&Otilde;|&Ouml;/',
			'o' => '/&ograve;|&oacute;|&ocirc;|&otilde;|&ouml;|&ordm;/',
			'U' => '/&Ugrave;|&Uacute;|&Ucirc;|&Uuml;/',
			'u' => '/&ugrave;|&uacute;|&ucirc;|&uuml;/',
			'Y' => '/&Yacute;/',
			'y' => '/&yacute;|&yuml;/',
			'-' => '/&nbsp;| - |  | |\/|\\\|\|/'
			);

		$string = preg_replace('/([^a-zA-Z0-9_-]*)/', '',
			preg_replace($chars, array_keys($chars),
				@htmlentities(strip_tags(trim($string)),
					ENT_NOQUOTES,
					PAGE_CHARSET)));

		if (!$lowercase)
			return $string;

		return strtolower($string);
	}

	static function escapeRegexp($string) {
		$patterns = array(
			'/\//', '/\^/', '/\./', '/\$/', '/\|/',
			'/\(/', '/\)/', '/\[/', '/\]/', '/\*/', '/\+/',
			'/\?/', '/\{/', '/\}/', '/\,/');

		$replace = array(
			'\/', '\^', '\.', '\$', '\|', '\(', '\)',
			'\[', '\]', '\*', '\+', '\?', '\{', '\}', '\,');

		return preg_replace($patterns,$replace, $string);
	}

	static function flushDisplay($delay = false) {
		@ob_flush();
		flush();

		if ($delay)
			usleep(50000);
	}

	static function searchQuery($search, $fields = array('Title'), $type = 'AND') {
		return sql::search($search, $fields, $type);
	}

	static function displayCSSLinks() {
		modules::displayCSSLinks();

		if (isset($GLOBALS['ADMIN']) && (bool)$GLOBALS['ADMIN'])
			admin::displayCSSLinks();
	}

	static function displayPath($level = 0, $displaypath = null) {
		if (!$displaypath)
			$displaypath = url::$pagePath;

		if (!$displaypath)
			return;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'url::displayPath', $_ENV, $level, $displaypath);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'url::displayPath', $_ENV, $level, $displaypath, $handled);

			return $handled;
		}

		$path = null;
		$exppaths = explode('/', $displaypath);

		$i = 0;
		foreach($exppaths as $key => $exppath) {
			if (!$exppath)
				continue;

			if ($path)
				$path .= '/';

			$path .= $exppath;

			if ($key < $level)
				continue;

			if (!(int)$exppath) {
				if ($i > 0)
					echo "<span class='path-separator'> / </span>";

				if (SEO_FRIENDLY_LINKS &&
					(!isset($GLOBALS['ADMIN']) || !$GLOBALS['ADMIN']))
				{
					echo
						"<a class='url-path".(!$i?' first':null).($i == count($exppaths)?' last':null)."' " .
							"href='". url::site() .
							htmlchars($path, ENT_QUOTES)."'>".__($exppath)."</a>";

				} else {
					echo
						"<a class='url-path".(!$i?' first':null).($i == count($exppaths)-1?' last':null)."' " .
							"href='". url::uri('ALL') .
							"?path=".htmlchars($path, ENT_QUOTES)."'>".__($exppath)."</a>";
				}

				$i++;
			}
		}

		api::callHooks(API_HOOK_AFTER,
			'url::displayPath', $_ENV, $level, $displaypath);
	}

	static function displayRootPath() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'url::displayRootPath', $_ENV);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'url::displayRootPath', $_ENV, $handled);

			return $handled;
		}

		echo url::site();

		api::callHooks(API_HOOK_AFTER,
			'url::displayRootPath', $_ENV);
	}

	static function displayError() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'url::displayError', $_ENV);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'url::displayError', $_ENV, $handled);

			return $handled;
		}

		$codes = new contentCodes();
		$codes->display(PAGE_404_ERROR_TEXT);
		unset($codes);

		api::callHooks(API_HOOK_AFTER,
			'url::displayError', $_ENV);
	}

	static function displayValidXHTML() {
		echo
			"<p class='validXHTML'>" .
				"<a href='http://validator.w3.org/check?uri=referer' " .
					"target='_blank'>" .
					"<img style='border:0;width:88px;height:31px' " .
						"src='http://www.w3.org/Icons/valid-xhtml10' " .
						"alt='Valid XHTML 1.0 Transitional' />" .
				"</a>" .
			"</p>";
	}

	static function displayValidCSS() {
		echo
			"<p class='validCSS'>" .
				"<a href='http://jigsaw.w3.org/css-validator/check/referer?profile=css3' " .
					"target='_blank'>" .
					"<img style='border:0;width:88px;height:31px' " .
						"src='http://jigsaw.w3.org/css-validator/images/vcss' " .
						"alt='Valid CSS!' />" .
				"</a>" .
			"</p>";
	}

	static function displaySearch($search, $results = null) {
		if (!$search)
			return;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'url::displaySearch', $_ENV, $search, $results);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'url::displaySearch', $_ENV, $search, $results, $handled);

			return $handled;
		}

		$searchstr = $search;
		$searches = array();
		$keywords = array();
		$commands = array();

		$tooltipcontent =
			__("Searching for").": ";

		if (strpos($search, ',') !== false)
			$separator = ',';
		else
			$separator = ' ';

		if (preg_match_all('/([^ :]+:(".+?"|[^ ]+)( |$))/', $search, $matches))
			$commands = $matches[1];

		foreach($commands as $command)
			$search = str_replace($command, '', $search);

		if (preg_match_all('/(".+?"|[^'.$separator.']+)('.$separator.'|$)/', trim($search), $matches))
			$keywords = $matches[1];

		$keywords = array_merge($keywords, $commands);

		foreach($keywords as $key => $searchtag) {
			if (!$searchtag = trim($searchtag))
				continue;

			if (in_array($searchtag, $searches))
				continue;

			$searches[] = $searchtag;
			$tooltipcontent .=
				"<a href='".url::uri('search').
					"&amp;search=" .
					urlencode(
						trim(
							preg_replace(
								'/'.
									($key?'(^|'.$separator.')':'').
									url::escapeRegexp($searchtag).
									(!$key?'('.$separator.'|$)':'').
								'/i',
								'',
								$searchstr))) .
					"'>".
				strtoupper($searchtag)."</a>" .
				"<sup class='red'>x</sup> &nbsp;";
		}

		$tooltipcontent .=
			"(<a href='".url::uri('search, searchin')."'>" .
				__("clear") .
			"</a>)";

		tooltip::display(
			$tooltipcontent,
			TOOLTIP_NOTIFICATION);

		if (isset($results) && !$results)
			tooltip::display(
				__("Your search returned no results. Please make sure all " .
					"words are spelled correctly or try fewer keywords by " .
					"clicking on them to remove."),
				TOOLTIP_NOTIFICATION);

		api::callHooks(API_HOOK_AFTER,
			'url::displaySearch', $_ENV, $search, $results);
	}

	static function displayConfirmation($message = null, $argument = 'delete') {
		if (!$message)
			$message = __("Are you sure you want to continue?");

		tooltip::display(
			"<form action='".url::uri()."' method='post'>" .
				"<input type='hidden' name='_SecurityToken'" .
					" value='".security::genToken()."' />" .
				"<input type='hidden' name='".$argument."'" .
					" value='".(isset($_GET[$argument])?$_GET[$argument]:1)."' />" .
					$message .
				"<br />" .
				"<br />" .
				"<input type='submit' class='button submit' value='".__("Yes")."' /> " .
				"<input type='button' class='button submit' value='".__("No")."'" .
					" onclick=\"window.location='".url::uri($argument)."';\"/>" .
			"</form>");
	}

	function displayArguments() {
		if (!isset($this->arguments))
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'url::displayArguments', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'url::displayArguments', $this, $handled);

			return $handled;
		}

		$encode = false;
		$decode = false;

		if (preg_match('/(^|\/)encode($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)encode($|\/)/', '\1', $this->arguments);
			$encode = true;
		}

		if (preg_match('/(^|\/)decode($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)decode($|\/)/', '\1', $this->arguments);
			$decode = true;
		}

		preg_match('/(.*?)(\/|$)(.*)/', $this->arguments, $matches);

		if (!isset($matches[1]) || !$matches[1]) {
			if ($encode)
				echo urlencode(url::get());
			elseif ($decode)
				echo urldecode(url::get());
			else
				echo url::get();

			$result = true;
			api::callHooks(API_HOOK_AFTER,
				'url::displayArguments', $this, $result);

			return true;
		}

		$argument = strtolower($matches[1]);
		$parameters = null;
		$path = null;

		if (isset($matches[3]))
			$parameters = $matches[3];

		if (isset($_GET['path']))
			$path = strip_tags((string)$_GET['path']);

		ob_start();

		switch($argument) {
			case 'uri':
				echo url::uri($parameters);
				break;

			case 'server':
				echo strip_tags((string)$_SERVER['SERVER_NAME']);
				break;

			case 'host':
				echo strip_tags((string)$_SERVER['HTTP_HOST']);
				break;

			case 'sessionid':
				echo session_id();
				break;

			case 'root':
				echo url::site();
				break;

			case 'srcpath':
				echo $path;
				break;

			case 'title':
				url::displayPageTitle($parameters);
				break;

			case 'description':
				url::displayPageDescription();
				break;

			case 'path':
				url::displayPath($parameters, $path);
				break;

			case 'validxhtml':
				url::displayValidXHTML();
				break;

			case 'validcss':
				url::displayValidCSS();
				break;

			default:
				echo $this->arguments;
				break;
		}

		$content = ob_get_contents();
		ob_end_clean();

		if ($encode)
			echo urlencode(htmlspecialchars_decode($content, ENT_QUOTES));
		elseif ($decode)
			echo htmlchars(urldecode($content), ENT_QUOTES);
		else
			echo $content;

		$result = true;
		api::callHooks(API_HOOK_AFTER,
			'url::displayArguments', $this, $result);

		return true;
	}

	function display() {
		if ($this->displayArguments())
			return;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'url::display', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'url::display', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'url::display', $this);
	}
}

?>