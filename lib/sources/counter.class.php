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
		if (is_array($items)) {
			if (!$type && isset($items['Type']))
				$type = $items['Type'];
			
			if (isset($items['Rows']))
				$items = (int)$items['Rows'];
			else
				$items = 0;
		}
		
		return
				"<span class='counter" .
					($type?
						' '.$type:
						null) .
					"'>" .
					"<span>" .
						"<span>" .
						(int)$items .
						"</span>" .
					"</span>" .
				"</span>";
	}
	
	static function display($items, $type = COUNTER_NORMAL) {
		echo counter::construct($items, $type);
	}
}

?>