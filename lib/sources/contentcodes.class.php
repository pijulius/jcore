<?php

/***************************************************************************
 *            contentcodes.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

define('NOW', date('Y-m-d H:i:s'));
define('NOW_DATE', date('Y-m-d'));
define('NOW_TIME', date('H:i:s'));
define('NOW_YEAR', date('Y'));
define('NOW_MONTH', date('m'));
define('NOW_DAY', date('d'));
define('CURRENT_URL', url::get());
define('REMOTE_ADDR', (string)$_SERVER['REMOTE_ADDR']);

include_once('lib/calendar.class.php');
 
class _contentCodes {
	var $fixParagraph = false;
	var $contentLimit = 0;
	var $ignoreCodes = null;
	
	function run($code, $arguments) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'contentCodes::run', $this, $code, $arguments);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'contentCodes::run', $this, $code, $arguments, $handled);
			
			return $handled;
		}
		
		switch($code) {
			case 'translate':
				if ($this->ignoreCodes && in_array('translate', $this->ignoreCodes))
					break;
				
				echo __($arguments);
				break;
				
			case 'random':
				if ($this->ignoreCodes && in_array('random', $this->ignoreCodes))
					break;
				
				echo contentCodes::random($arguments);
				break;
				
			case 'variables':
				if ($this->ignoreCodes && in_array('variables', $this->ignoreCodes))
					break;
				
				echo contentCodes::variables($arguments);
				break;
				
			case 'calendar':
				if ($this->ignoreCodes && in_array('calendar', $this->ignoreCodes))
					break;
				
				$calendar = new calendar();
				$calendar->arguments = $arguments;
				$calendar->display();
				unset($calendar);
				break;
				
			case 'url':
				if ($this->ignoreCodes && in_array('url', $this->ignoreCodes))
					break;
				
				$url = new url();
				$url->arguments = $arguments;
				$url->display();
				unset($url);
				break;
				
			case 'languages':
				if ($this->ignoreCodes && in_array('languages', $this->ignoreCodes))
					break;
				
				$languages = new languages();
				$languages->arguments = $arguments;
				$languages->display();
				unset($languages);
				break;
				
			case 'menus':
				if ($this->ignoreCodes && in_array('menus', $this->ignoreCodes))
					break;
				
				$menus = new menus();
				$menus->arguments = $arguments;
				$menus->display();
				unset($menus);
				break;
				
			case 'pages':
				if ($this->ignoreCodes && in_array('pages', $this->ignoreCodes))
					break;
				
				$pages = new pages();
				$pages->arguments = $arguments;
				$pages->display();
				unset($pages);
				break;
				
			case 'posts':
				if ($this->ignoreCodes && in_array('posts', $this->ignoreCodes))
					break;
				
				$posts = new posts();
				$posts->arguments = $arguments;
				
				if ($this->contentLimit)
					$posts->limit = $this->contentLimit;
				
				$posts->display();
				unset($posts);
				break;
				
			case 'blocks':
				if ($this->ignoreCodes && in_array('blocks', $this->ignoreCodes))
					break;
				
				$blocks = new blocks();
				$blocks->arguments = $arguments;
				$blocks->display();
				unset($blocks);
				break;
				
			case 'modules':
				if ($this->ignoreCodes && in_array('modules', $this->ignoreCodes))
					break;
				
				preg_match('/(.*?)(\/|$)(.*)/', $arguments, $matches);
					
				$modules = new modules();
				if (!isset(modules::$loaded[$matches[1]]) || !modules::$loaded[$matches[1]])
					break;
					
				$modulename = new $matches[1]();
				$modulename->arguments = $matches[3];
				
				if ($this->contentLimit)
					$modulename->limit = $this->contentLimit;
				
				$modulename->display();
				unset($modulename);
				
				unset($modules);
				break;
				
			case 'forms':
				if ($this->ignoreCodes && in_array('forms', $this->ignoreCodes))
					break;
				
				$form = new dynamicForms($arguments);
				$form->load();
				$form->verify();
				$form->display();
				unset($form);
				break;
				
			default:
				break;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'contentCodes::run', $this, $code, $arguments);
	}
	
	static function random($arguments) {
		preg_match('/(.*?)\/(.*)/', $arguments, $matches);
		
		$start = 0;
		$end = 1000;
		
		if (isset($matches[1]))
			$start = (int)$matches[1];
			
		if (isset($matches[2]))
			$end = (int)$matches[2];
		
		return rand($start, $end);
	}
	
	static function variables($arguments) {
		$expargs = explode('/', $arguments);
		
		if (!isset($expargs[0]))
			return null;
		
		$postget = strtoupper($expargs[0]);
		if (!in_array($postget, array('POST', 'GET')))
			return null;
		
		$variable = $GLOBALS['_'.$postget];
		
		foreach($expargs as $key => $exparg) {
			if ($key < 1)
				continue;				
			
			if (!isset($variable[$exparg])) {
				$variable = null;
				break;
			}
				
			$variable = strip_tags((string)$variable[$exparg]);
		}
		
		return (string)$variable;
	}
	
	static function replaceDefinitions(&$content) {
		$content = preg_replace_callback('/%([A-Z0-9-_]+?)%/', 
			array('contentCodes', 'displayDefinitions'), 
			$content);
	}
	
	static function displayDefinitions($constant) {
		if (!$constant)
			return null;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'contentCodes::displayDefinitions', $_ENV, $constant);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'contentCodes::displayDefinitions', $_ENV, $constant, $handled);
			
			return $handled;
		}
		
		if (is_array($constant))
			$constant = $constant[1];
		
		if ($constant == 'SITE_URL')
			$result = url::site();
		
		else if ($constant == 'JCORE_URL')
			$result = url::jCore();
		
		else if ($constant == 'SECURITY_TOKEN')
			$result = security::genToken();
		
		else if (!defined($constant))
			$result = null;
		
		else if (in_array($constant, array(
			'SQL_HOST',
			'SQL_DATABASE',
			'SQL_USER',
			'SQL_PASS',
			'SITE_PATH',
			'JCORE_PATH')))
			$result = null;
			
		else
			$result = constant($constant);
		
		api::callHooks(API_HOOK_AFTER,
			'contentCodes::displayDefinitions', $_ENV, $constant, $result);
		
		return $result;
	}
	
	function display($content) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'contentCodes::display', $this, $content);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'contentCodes::display', $this, $content, $handled);
			
			return $handled;
		}
		
		contentCodes::replaceDefinitions($content);
		
		preg_match_all('/(<p>[^>]+)?\{([a-zA-Z0-9\-\_]+?)\}(.*?)\{\/\2\}/', $content, $matches);
		
		if (!isset($matches[2]) || !count($matches[2])) {
			echo $content;
			
			api::callHooks(API_HOOK_AFTER,
				'contentCodes::display', $this, $content);
			
			return;
		}
		
		$contents = preg_split('/\{([a-zA-Z0-9\-\_]+?)\}(.*?)\{\/\1\}/is', $content);
		
		foreach($matches[2] as $key => $code) {
			echo $contents[$key];
			
			if ($this->fixParagraph && $matches[1][$key])
				echo "</p>";
			
			$this->run(strtolower($code), $matches[3][$key]);
			
			if ($this->fixParagraph && $matches[1][$key])
				echo "<p>";
		}
		
		echo $contents[count($contents)-1];
		
		api::callHooks(API_HOOK_AFTER,
			'contentCodes::display', $this, $content);
	}
}

?>