<?php

/***************************************************************************
 *            counter.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

define('COUNTER_NORMAL', '');
define('COUNTER_IMPORTANT', 'important');
define('COUNTER_NOTIFICATION', 'notification');

class _counter {
	static function construct($items, $type = COUNTER_NORMAL) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'counter::construct', $_ENV, $items, $type);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'counter::construct', $_ENV, $items, $type, $handled);
			
			return $handled;
		}
		
		if (is_array($items)) {
			if (!$type && isset($items['Type']))
				$type = $items['Type'];
			
			if (isset($items['Rows']))
				$items = $items['Rows'];
			else
				$items = 0;
		}
		
		$result =
				"<span class='counter" .
					($type?
						' '.$type:
						null) .
					"'>" .
					"<span>" .
						"<span>" .
							$items .
						"</span>" .
					"</span>" .
				"</span>";
		
		api::callHooks(API_HOOK_AFTER,
			'counter::construct', $_ENV, $items, $type, $result);
		
		return $result;
	}
	
	static function display($items, $type = COUNTER_NORMAL) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'counter::display', $_ENV, $items, $type);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'counter::display', $_ENV, $items, $type, $handled);
			
			return $handled;
		}
		
		echo counter::construct($items, $type);
		
		api::callHooks(API_HOOK_AFTER,
			'counter::display', $_ENV, $items, $type);
	}
}

?>