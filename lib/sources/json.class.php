<?php

/***************************************************************************
 *            json.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 * 
 *  Based on boukeversteegh@gmail.com's work
 *  http://php.net/manual/en/function.json-encode.php
 * 
 ****************************************************************************/

class _json {
	static function encode($data) {
		if (function_exists('json_encode'))
			return json_encode($data);
		
		if (is_array($data) || is_object($data)) { 
			$islist = is_array($data) && (empty($data) || array_keys($data) === range(0,count($data)-1)); 
			
			if ($islist) { 
				$json = '[' . implode(',', array_map('__json_encode', $data) ) . ']'; 
			} else { 
				$items = array(); 
				foreach($data as $key => $value) { 
					$items[] = json::encode("$key") . ':' . json::encode($value); 
				} 
				$json = '{' . implode(',', $items) . '}'; 
			} 
		} elseif (is_string($data)) { 
			# Escape non-printable or Non-ASCII characters. 
			$string = '"' . addcslashes($data, "\"\\\n\r\t\f/" . chr(8)) . '"'; 
			$json = ''; 
			$len = strlen($string); 
			
			# Convert UTF-8 to Hexadecimal Codepoints. 
	        for ($i = 0; $i < $len; $i++) { 
				$char = $string[$i]; 
				$c1 = ord($char); 
				
				# Single byte; 
				if ($c1 < 128) { 
					$json .= ($c1 > 31) ? $char : sprintf("\\u%04x", $c1); 
					continue; 
				} 
				
				# Double byte 
				$c2 = ord($string[++$i]); 
				if (($c1 & 32) === 0) { 
					$json .= sprintf("\\u%04x", ($c1 - 192) * 64 + $c2 - 128); 
					continue; 
				} 
				
				# Triple 
				$c3 = ord($string[++$i]); 
				if(($c1 & 16) === 0) { 
					$json .= sprintf("\\u%04x", (($c1 - 224) <<12) + (($c2 - 128) << 6) + ($c3 - 128)); 
					continue; 
				} 
				
				# Quadruple 
				$c4 = ord($string[++$i]); 
				if(($c1 & 8 ) === 0) { 
					$u = (($c1 & 15) << 2) + (($c2>>4) & 3) - 1; 
					
					$w1 = (54<<10) + ($u<<6) + (($c2 & 15) << 2) + (($c3>>4) & 3); 
					$w2 = (55<<10) + (($c3 & 15)<<6) + ($c4-128); 
					$json .= sprintf("\\u%04x\\u%04x", $w1, $w2); 
				} 
			} 
		} else { 
			# int, floats, bools, null 
			$json = strtolower(var_export($data, true)); 
		} 
		return $json; 
	}
}

?>