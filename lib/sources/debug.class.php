<?php

/***************************************************************************
 *            debug.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

define('D_OPTIMIZATION', 1);
define('D_NOTIFICATION', 2);
define('D_WARNING', 4);
define('D_ERROR', 8);
define('D_ALL', 9);

class _debug {
	static $data = null;
	static $customData = null;
	static $runtime = 0;
	
	static function start() {
		if (!isset($_COOKIE['XDEBUG_PROFILE'])) {
			setcookie('XDEBUG_PROFILE', true);
			header('Location: '.$_SERVER['REQUEST_URI']);
			exit;
		}
		
		debug::$runtime = microtime(true);
	}
	
	static function end() {
		debug::$runtime = sql::mtimetosec(microtime(true), debug::$runtime);
		debug::parse();
	}
	
	static function parse($file = null) {
		if (!$file && debug::xdebug())
			$file = xdebug_get_profiler_filename();
		
		if (!$file)
			return false;
		
		debug::$data = null;
		
		$ffile = null;
		$function = null;
		$cfunc = false;
		
		foreach(preg_split("/\r?\n/", file_get_contents($file)) as $line) {
			if (!$line)
				continue;
			
			if (strpos($line, 'fl=') !== false) {
				$ffile = substr($line, strpos($line, '=')+1);
				
			} else if (strpos($line, 'fn=') !== false) {
				$function = substr($line, strpos($line, '=')+1);
				
				if (strpos($line, 'cfn=') !== false)
					$cfunc = true;
				else
					$cfunc = false;
				
				if (!isset(debug::$data[0][$function])) {
					debug::$data[0][$function] = '0.0';
					
				} elseif ($cfunc) {
					list($time, $calls) = explode('.', debug::$data[0][$function]);
					$calls++;
					debug::$data[0][$function] = $time.'.'.$calls;
				}
				
			} else {
				if ($function && (int)$line) {
					list($fline, $ftime) = explode(' ', $line);
					
					if (!$cfunc) {
						list($time, $calls) = explode('.', debug::$data[0][$function]);
						$time += (int)$ftime;
						debug::$data[0][$function] = $time.'.'.$calls;
					}
					
					if ($ffile) {
						if (!isset(debug::$data[1][$function][$ffile]))
							debug::$data[1][$function][$ffile] = 0;
						
						if ($cfunc)
							debug::$data[1][$function][$ffile]++;
					}
					
					$function = null;
				}
			}
		}
		
		if (isset(debug::$data[0]) && is_array(debug::$data[0]))
			arsort(debug::$data[0]);
		
		return true;
	}
	
	static function xdebug() {
		if (function_exists('xdebug_get_profiler_filename'))
			return true;
		
		return false;
	}
	
	static function log($type, $data) {
		if (!isset(debug::$customData[$type]))
			debug::$customData[$type] = '';
		 
		debug::$customData[$type] .= $data;
	}
	
	static function display() {
		echo 
			"<div class='fc'>" .
				"<a class='fc-title'>" .
					"<span class='align-right'>" .
						"PHP Run Time: ~".debug::$runtime .
					"</span>" .
					"DEBUG Info" .
				"</a>" .
				"<div class='fc-content'>";
		
		if (debug::$customData && is_array(debug::$customData)) {
			foreach(debug::$customData as $title => $data)
				echo
					"<h2>".$title."</h2>" .
					$data;
			
			echo
				"<p>&nbsp;</p>";
		}
		
		if (!debug::$data || !isset(debug::$data[0]) || !debug::$data[0]) {
			if (!debug::xdebug())
				echo
					"<h3>XDebug not available!</h3>" .
					"<p>Tu turn on debugging in jCore please complete the following steps.</p>" .
					"<ul>" .
						"<li>" .
							"Download and install " .
							"<a href='http://xdebug.org' target='_blank'>" .
								"xDebug" .
							"</a>" .
						"</li>" .
						"<li>" .
							"Turn on xDebug by adding " .
								"<code style='padding: 0; margin: 0; overflow: visible;'>" .
									"xdebug.profiler_enable_trigger = On" .
								"</code> " .
								"to your php.ini" .
						"</li>" .
						"<li>" .
							"Turn OFF xDebug auto profiler by setting " .
								"<code style='padding: 0; margin: 0; overflow: visible;'>" .
									"xdebug.profiler_enable = Off" .
								"</code> " .
								"in your php.ini" .
						"</li>" .
						"<li>" .
							"Restart apache and " .
							"<a href='javascript:window.location.reload();'>" .
								"reload" .
							"</a> the website." .
						"</li>" .
					"</ul>";
			else 
				echo
					"<h3>Empty xDebug data!</h3>" .
					"<p>Please make sure that the followings are met.</p>" .
					"<ul>" .
						"<li>" .
							"Always call " .
							"<code style='padding: 0; margin: 0; overflow: visible;'>" .
								"debug::end();" .
							"</code> " .
							"before calling debug::display();" .
						"</li>" .
						"<li>" .
							"Check your debug file " .
							"<code style='padding: 0; margin: 0; overflow: visible;'>" .
								xdebug_get_profiler_filename() .
							"</code> " .
							"to be accessible by me and that it contains any data." .
						"</li>" .
						"<li>" .
							"NOTE: this has been only tested on Linux and with xDebug 0.9.6 " .
							"so if you are having problems please first try with a setup " .
							"like this." .
						"</li>" .
					"</ul>";
			
		} else {
			echo
				(debug::$customData?
					"<h2>Code Profiling</h2>":
					null) .
				"<p>" .
					"Below you will find the top 300 functions ordered by run time (descending). " .
					"For a more detailed output please see " .
					"<a href='http://xdebug.org/docs/profiler' target='_blank'>" .
						"xDebug profiler" .
					"</a> programs." .
				"</p>" .
				"<table class='list'>" .
				"<thead>" .
					"<tr>" .
						"<th><span class='nowrap'>Function / Called from [times]</span></th>" .
						"<th><span class='nowrap'>Run Time</span></th>" .
						"<th><span class='nowrap'>Called</span></th>" .
					"</tr>" .
				"</thead>" .
				"<tbody>";
			
			$i = 0;
			$totaltime = 0;
			
			foreach(debug::$data[0] as $function => $data) {
				list($time, $calls) = explode('.', $data);
				$sec = number_format($time/1000000, 5);
				
				if ($i < 300) {
					echo
					"<tr>" .
						"<td class='auto-width".($sec >= 0.001?" bold":null)."'>" .
							$function;
					
					if (isset(debug::$data[1][$function])) {
						echo
							"<div class='comment' style='padding-left: 10px;'>";
						
						foreach(debug::$data[1][$function] as $file => $fcalls)
							echo
								$file.' ['.$fcalls.']<br/>';
					
						echo
							"</div>";
					}
					
					echo
						"</td>" .
						"<td style='text-align: right;'>" .
							$sec .
						"</td>" .
						"<td style='text-align: right;'>" .
							$calls .
						"</td>" .
					"</tr>";
				}
				
				$i++;
				$totaltime += $time;
			}
			
			echo
				"</tbody>" .
				"</table>" .
				"<h3>" .
					"Total run time: " .
					number_format($totaltime/1000000, 5) .
				"</h3>";
		}
			
		echo
				"</div>" .
			"</div>";
	}
}

?>