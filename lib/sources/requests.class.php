<?php

/***************************************************************************
 *            requests.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/

include_once('lib/modules.class.php');
include_once('lib/ads.class.php');

class _requests {
	static $result = null;
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
		echo requests::$result;
		url::flushDisplay();
		requests::$result = null;
	}
	
	function display() {
		$request = null;
		$ajax = null;
		
		if (isset($GLOBALS['_'.strtoupper($this->method)][$this->variable]))
			$request = $GLOBALS['_'.strtoupper($this->method)][$this->variable];
		
		if (isset($GLOBALS['_'.strtoupper($this->method)]['ajax']))
			$ajax = $GLOBALS['_'.strtoupper($this->method)]['ajax'];
		
		if (!$request)
			return;
		
		$requests = explode('/', preg_replace('/(^|\/)[0-9]+/', '', $request));
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
				
				$userpermission = userPermissions::check($GLOBALS['USER']->data['ID'], $request);
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
			($ajax && !method_exists($classname,'ajaxRequest')) ||
			(!$ajax && !method_exists($classname,'request')))
		{
			unset($GLOBALS['_'.strtoupper($this->method)][$this->variable]);
			url::setURI(url::uri($this->variable));
			
			if ($ajax) {
				tooltip::display(
					__("Invalid or not enough permission to access this request!"),
					TOOLTIP_ERROR);
				
				sql::logout();
				exit();
			}
			
			return;
		}
		
		$class = new $classname;
		
		$class->uriRequest = $request;
		$class->ajaxRequest = false;
		
		ob_start();
		$requestsuccess = false;
		
		if ($ajax) {
			$class->ajaxRequest = true;
			$requestsuccess = $class->ajaxRequest();
			
		} else {
			$requestsuccess = $class->request();
		}
		
		requests::$result = ob_get_contents();
		ob_end_clean();
		
		unset($class);
		
		if ($ajax && $requestsuccess) {
			requests::displayResult();
			sql::logout();
			exit();
		}
		
		if ($requestsuccess) {
			$this->clear();
			return;
		}
		
		unset($GLOBALS['_'.strtoupper($this->method)][$this->variable]);
		url::setURI(url::uri($this->variable));
	}
}

?>