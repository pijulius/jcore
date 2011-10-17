<?php

/***************************************************************************
 *            tooltip.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

define('TOOLTIP_DEFAULT', '');
define('TOOLTIP_SUCCESS', 'success');
define('TOOLTIP_ERROR', 'error');
define('TOOLTIP_NOTIFICATION', 'notification');
 
class _tooltip {
	static $cache = "";
	static $caching = false;
	
	static function caching($onoff) {
		tooltip::$caching = $onoff;
	}
	
	static function construct($message, $type = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'tooltip::construct', $_ENV, $message, $type);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'tooltip::construct', $_ENV, $message, $type, $handled);
			
			return $handled;
		}
		
		if (defined($type))
			$type = constant($type);
		
		$result = 
			"<div class='tooltip ".$type." rounded-corners'>" .
				"<span>" .
				$message .
				"</span>" .
			"</div>";
		
		api::callHooks(API_HOOK_AFTER,
			'tooltip::construct', $_ENV, $message, $type, $result);
		
		return $result;
	}
	
	static function display($message = null, $type = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'tooltip::display', $_ENV, $message, $type);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'tooltip::display', $_ENV, $message, $type, $handled);
			
			return $handled;
		}
		
		if (!$message) {
			echo tooltip::$cache;
			tooltip::$cache = '';
			
		} else {
			if (tooltip::$caching)
				tooltip::$cache .= tooltip::construct($message, $type);
			else 
				echo tooltip::construct($message, $type);
		}
		
		api::callHooks(API_HOOK_AFTER,
			'tooltip::display', $_ENV, $message, $type);
	}
}

?>