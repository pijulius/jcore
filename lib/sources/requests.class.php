<?php

/***************************************************************************
 *            requests.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/modules.class.php');
include_once('lib/ads.class.php');

class _requests {
	static $result = null;
	static $path = '';
	static $ajax = false;
	
	var $variable = 'request';
	var $method = 'get';
	
	function clearURI() {
		preg_match('/(\?|\&)'.$this->variable.'=.*?&(.*)/', 
			str_replace('&amp;', '&', url::uri()), $matches);

		if (isset($matches[2])) {
			preg_match_all('/(.*?)=.*?(\&|$)/', $matches[2], $variables);
			
			foreach($variables[1] as $variable)
				url::setURI(url::uri($variable));
		}
			
		url::setURI(url::uri($this->variable));
	}
	
	function clear() {
		preg_match('/(\?|\&)'.$this->variable.'=.*?&(.*)/', 
			str_replace('&amp;', '&', url::uri()), $matches);
			
		if (isset($matches[2])) {
			preg_match_all('/(.*?)=.*?(\&|$)/', $matches[2], $variables);
			
			if (isset($variables[1])) {
				foreach($variables[1] as $variable) {
					unset($GLOBALS['_'.strtoupper($this->method)][$variable]);
					url::setURI(url::uri($variable));
				}
			}
		}
		
		unset($GLOBALS['_'.strtoupper($this->method)][$this->variable]);
		url::setURI(url::uri($this->variable));
	}
	
	static function displayResult() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'requests::displayResult', $_ENV);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'requests::displayResult', $_ENV, $handled);
			
			return $handled;
		}
		
		echo requests::$result;
		url::flushDisplay();
		requests::$result = null;
		
		api::callHooks(API_HOOK_AFTER,
			'requests::displayResult', $_ENV);
	}
	
	function display() {
		if (isset($GLOBALS['_'.strtoupper($this->method)][$this->variable]))
			requests::$path = strip_tags((string)$GLOBALS['_'.strtoupper($this->method)][$this->variable]);
		
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']))
			requests::$ajax = true;
		
		if (isset($GLOBALS['_'.strtoupper($this->method)]['ajax']))
			requests::$ajax = (bool)$GLOBALS['_'.strtoupper($this->method)]['ajax'];
		
		if (!requests::$path)
			return;
		
		$requests = explode('/', preg_replace('/(^|\/)[0-9]+/', '', requests::$path));
		$classname = null;
		
		switch($requests[0]) {
			case 'jquery':
			case 'js':
				$classname = "jquery";
				break;
	
			case 'css':
				$classname = "css";
				break;
	
			case 'security':
				$classname = "security";
				break;
			
			case 'gdata':
				$classname = "gdata";
				break;
			
			case 'ads':
				$classname = "ads";
				break;
	
			case 'users':
				$classname = "users";
				break;
	
			case 'pages':
				$classname = "pages";
				break;
			
			case 'ckeditor':
			case 'posts':
				$classname = preg_replace('/[^a-zA-Z0-9\_\-]/', '', 
					$requests[count($requests)-1]);
				break;
	
			case 'modules':
				$modules = new modules();
				
				if (!$modules->load($requests[1], true)) {
					unset($modules);
					break;
				}
				
				unset($modules);
				
				$classname = preg_replace('/[^a-zA-Z0-9\_\-]/', '', 
					$requests[count($requests)-1]);
				
				break;
				
			case 'admin':
				if (!$GLOBALS['USER']->loginok || 
					!$GLOBALS['USER']->data['Admin']) 
					break;
				
				include_once('lib/admin.class.php');
				
				$userpermission = userPermissions::check((int)$GLOBALS['USER']->data['ID'], requests::$path);
				if (!$userpermission['PermissionType'])
					break;
				
				if ($requests[1] == 'modules') {
					$modules = new modules();
					
					if (!$modules->load($requests[2], true)) {
						unset($modules);
						break;
					}
					
					unset($modules);
				}
					
				$classname = preg_replace('/[^a-zA-Z0-9\_\-]/', '', 
					$requests[count($requests)-1]);
				
				break;
		}
		
		if (!class_exists($classname) || 
			(requests::$ajax && !method_exists($classname,'ajaxRequest')) ||
			(!requests::$ajax && !method_exists($classname,'request')))
		{
			unset($GLOBALS['_'.strtoupper($this->method)][$this->variable]);
			url::setURI(url::uri($this->variable));
			
			if (requests::$ajax) {
				tooltip::display(
					__("Invalid or not enough permission to access this request!"),
					TOOLTIP_ERROR);
				
				sql::logout();
				exit();
			}
			
			return;
		}
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'requests::display', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'requests::display', $this, $handled);
			
			return $handled;
		}
		
		$class = new $classname;
		
		$class->uriRequest = requests::$path;
		$class->ajaxRequest = false;
		
		ob_start();
		$requestsuccess = false;
		
		if (requests::$ajax) {
			$class->ajaxRequest = true;
			$requestsuccess = $class->ajaxRequest();
			
		} else {
			$requestsuccess = $class->request();
		}
		
		requests::$result = ob_get_contents();
		ob_end_clean();
		
		unset($class);
		
		if (requests::$ajax && $requestsuccess) {
			requests::displayResult();
			sql::logout();
			
			api::callHooks(API_HOOK_AFTER,
				'requests::display', $this);
			
			exit();
		}
		
		$this->clear();
		
		if (!$requestsuccess) {
			unset($GLOBALS['_'.strtoupper($this->method)][$this->variable]);
			url::setURI(url::uri($this->variable));
		}
		
		api::callHooks(API_HOOK_AFTER,
			'requests::display', $this);
		
		return;
	}
}

?>