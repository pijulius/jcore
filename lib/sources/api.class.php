<?php

/***************************************************************************
 *            api.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

define('API_HOOK_BEFORE', 1);
define('API_HOOK_AFTER', 2);
define('API_HOOK_RETURN', 3);

class _api {
	static $hooks = null;
	static $contentCodes = null;
	
	static function addHook($type, $hook, $function = null) {
		if (!$type || !$hook)
			return false;
		
		if (!$function) {
			$function = $hook;
			$hook = $type;
			$type = API_HOOK_AFTER;
		}
		
		$object = true;
		if (is_object($function))
			$function = array($function, 'display');
		
		if (is_array($function)) {
			if (!isset($function[0]))
				return false;
			
			if (!isset($function[1]))
				$function[1] = 'display';
			
			if (is_object($function[0])) {
				$object = $function[0];
				$function[0] = get_class($object);
			}
			
			$function = $function[0].'::'.$function[1];
		}
		
		if (isset(api::$hooks[$type][$hook][$function]) &&
			api::$hooks[$type][$hook][$function] === $object)
			return true;
		
		api::$hooks[$type][$hook][$function] = $object;
		return true;
	}
	
	static function rmHook($type, $hook, $function = null) {
		if (!$type || !$hook)
			return false;
		
		if (!$function) {
			$function = $hook;
			$hook = $type;
			$type = API_HOOK_AFTER;
		}
		
		$object = true;
		if (is_array($function)) {
			if (!isset($function[0]) || !is_object($function[0]))
				return false;
			
			if (!isset($function[1]))
				$function[1] = 'display';
			
			$function = get_class($function[0]).'::'.$function[1];
		}
		
		if (!isset(api::$hooks[$type][$hook][$function]))
			return true;
		
		unset(api::$hooks[$type][$hook][$function]);
		return true;
	}
	
	static function callHooks($type, $hook, &$argument1 = null, &$argument2 = null, &$argument3 = null, &$argument4 = null, &$argument5 = null, &$argument6 = null, &$argument7 = null, &$argument8 = null, &$argument9 = null, &$argument10 = null) {
		if (!api::$hooks || !$type || !$hook)
			return false;
		
		if ($type == API_HOOK_BEFORE && isset(api::$hooks[API_HOOK_RETURN][$hook])) {
			foreach(api::$hooks[API_HOOK_RETURN][$hook] as $function => $object) {
				if (!$object)
					continue;
				
				ob_start();
			}
		}
		
		if (isset(api::$hooks[$type][$hook])) {
			foreach(api::$hooks[$type][$hook] as $function => $object) {
				if (!$object)
					continue;
				
				$class = null;
				$method = null;
				
				if (strpos($function, '::'))
					list($class, $method) = explode('::', $function);
				
				if ($class && $method) {
					if (!method_exists($class, $method))
						continue;
					
					if (is_object($object))
						$class = $object;
					else
						$class = new $class();
					
					$class->$method(
						$argument1, $argument2, $argument3,
						$argument4, $argument5, $argument6,
						$argument7, $argument8, $argument9,
						$argument10);
					unset($class);
					
					continue;
				}
				
				if (!function_exists($function))
					continue;
				
				$function(
					$argument1, $argument2, $argument3,
					$argument4, $argument5, $argument6,
					$argument7, $argument8, $argument9,
					$argument10);
			}
		}
		
		if ($type == API_HOOK_AFTER && isset(api::$hooks[API_HOOK_RETURN][$hook])) {
			foreach(api::$hooks[API_HOOK_RETURN][$hook] as $function => $object) {
				if (!$object)
					continue;
				
				$content = ob_get_contents();
				ob_end_clean();
				
				$class = null;
				$method = null;
				
				if (strpos($function, '::'))
					list($class, $method) = explode('::', $function);
				
				if ($class && $method) {
					if (!method_exists($class, $method))
						continue;
					
					if (is_object($object))
						$class = $object;
					else
						$class = new $class();
					
					$class->$method(
						$content,
						$argument1, $argument2, $argument3,
						$argument4, $argument5, $argument6,
						$argument7, $argument8, $argument9,
						$argument10);
					unset($class);
					
					continue;
				}
				
				if (!function_exists($function))
					continue;
				
				$function(
					$content,
					$argument1, $argument2, $argument3,
					$argument4, $argument5, $argument6,
					$argument7, $argument8, $argument9,
					$argument10);
			}
		}
		
		return true;
	}
	
	static function addContentCode() {
		
	}
	
	static function rmContentCode() {
		
	}
	
	static function runContentCodes() {
		
	}
}

?>