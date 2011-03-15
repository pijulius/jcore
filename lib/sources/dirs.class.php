<?php

/***************************************************************************
 *            dirs.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

class _dirs {
 	static function exists($dir) {
 		return is_dir($dir);
 	}
 	
 	static function delete($dir) {
 		return files::delete($dir);
 	}
 	
 	static function rename($dir, $to) {
 		return @rename($dir, $to);
 	}
 	
 	static function create($dir, $mode = 0777) {
 		return @mkdir($dir, $mode, true);
 	}
 	
 	static function get($dir) {
		$dirs = array_diff(scandir($dir), array(".", ".."));
		$dir_array = array();
		 
		foreach($dirs as $d) { 
			if(is_dir($dir."/".$d)) 
				$dir_array[$d] = dirs::get($dir."/".$d); 
			else 
				$dir_array[$d] = $d; 
		}
		
		return $dir_array; 
 	}
}

?>