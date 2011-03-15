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
		if (defined($type))
			$type = constant($type);
		
		return 
			"<div class='tooltip ".$type." rounded-corners'>" .
				"<span>" .
				$message .
				"</span>" .
			"</div>";
	}
	
	static function display($message = null, $type = null) {
		if (!$message) {
			echo tooltip::$cache;
			tooltip::$cache = '';
			
			return;
		}
		
		if (tooltip::$caching)
			tooltip::$cache .= tooltip::construct($message, $type);
		else 
			echo tooltip::construct($message, $type);
	}
}

?>