<?php

/***************************************************************************
 *            sql.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/tooltip.class.php');
include_once('lib/languages.class.php');

if (!defined('SQL_PREFIX'))
	define('SQL_PREFIX', '');

if (DEBUG) {
	include_once('lib/debug.class.php');
	debug::start();
}

class _sql {
	static $link = null;
	static $debug = false;
	static $quiet = false;
	static $lastQuery = null;
	static $lastQueryTime = 0;
	
	static function setTimeZone() {
		sql::run("SET `time_zone` = '".
			(phpversion() < '5.1.3'?
				preg_replace('/(..)$/', ':\1', date('O')):
				date('P')).
			"'");
	}
	
	static function mtimetosec($current, $start) {
		$exp_current = explode(" ", $current);
		$exp_start = explode(" ", $start);
		
		if (!isset($exp_current[1]))
			$exp_current[1] = 0;
		
		if (!isset($exp_start[1]))
			$exp_start[1] = 0;
		
		$msec = $exp_current[0] - $exp_start[0];
		$sec = $exp_current[1] - $exp_start[1];
		
		return number_format($sec+$msec, 5);
	}

	static function connect($host, $user, $pass) {
		sql::$link = @mysql_connect($host, $user, $pass);
		return sql::$link;
	}

	static function selectDB($db) {
		if (!sql::$link)
			return false;
		
		return @mysql_select_db($db, sql::$link);
	}

	static function login() {
		sql::$link = sql::connect(SQL_HOST, SQL_USER, SQL_PASS);
		
		if (!sql::$link || !sql::selectDB(SQL_DATABASE))
			sql::fatalError();
	
    	// I have no idea why this is needed but unless I set the character set
    	// manually all my Hungarian/Romanian characters are messed up.
    	
		$character_set = sql::fetch(sql::run(
			" SHOW VARIABLES LIKE 'character_set_database'"));
		
		if ($character_set)
	  		sql::run("SET CHARACTER SET '".$character_set['Value']."'");
	}
	
	static function prefixTable($query) {
		if (!defined('SQL_PREFIX') || !SQL_PREFIX)
			return preg_replace(
				'/`{([a-zA-Z0-9\_\-]*?)}`/', 
				'`\1`', 
				$query);
		
		return preg_replace(
			'/`{([a-zA-Z0-9\_\-]*?)}`/', 
			'`'.preg_replace('/[^a-zA-Z0-9\_\-]/',
				'', SQL_PREFIX).'_\1`', 
			$query);
	}
	
	static function regexp2txt($string) {
		$string = preg_replace('/^\^|\$$/', '', $string);
		$string = str_replace('.*', '*', $string);
		$string = str_replace('$|^', ', ', $string);
		
		return $string;
	}
	
	static function txt2regexp($string) {
		if (!trim($string))
			return '';
		
		$string = '^'.$string.'$';
		$string = preg_replace('/, ?/', ', ', $string);
		$string = str_replace('*', '.*', $string);
		$string = str_replace(', ', '$|^', $string);
		
		return $string;
	}
	
	static function run($query, $debug = false) {
		if (!trim($query))
			return false;
		
		$optimization = false;
		if (DEBUG &&
			preg_match('/^ *?SELECT.*?WHERE/i', $query) && !preg_match('/`\{TMP[a-zA-Z0-9]+\}`/', $query) &&
			$explains = @mysql_query('EXPLAIN '.sql::prefixTable($query), sql::$link))
		{
			$explain = sql::fetch($explains);
			if (!$explain['key'] && !$explain['possible_keys'] && 
				!in_array($explain['Extra'], array(
					'Impossible WHERE noticed after reading const tables',
					'Select tables optimized away')))
				$optimization = true;
		}
		
		sql::$lastQuery = $query;
		
		if ($optimization || $debug || sql::$debug)
			$time_start = microtime(true);
	
		if (!sql::$link) 
			sql::login();
			
		$query = sql::prefixTable($query);
	    $result = @mysql_query($query, sql::$link);
	    
		if ($optimization || $debug || sql::$debug) {
			sql::$lastQueryTime = sql::mtimetosec(microtime(true), $time_start);
			
			if ($optimization) 
				ob_start();
			
			sql::display();
			
			if ($optimization) {
				$sqlexplain = ob_get_contents();
				ob_end_clean();
				
				debug::log('SQL Optimization', $sqlexplain);
			}
			
		} elseif (!$result && !sql::$quiet) {
	    	if (mysql_errno(sql::$link) == 1146 && !headers_sent())
	    		sql::fatalError(sql::error());
	    	
			sql::displayError();
	    	return false;
	    }
		
		if (preg_match('/^ *?INSERT/i', $query)) 
			$result = mysql_insert_id(sql::$link);
		
		return $result;
	}
	
	static function fetch($rows) {
	    if (!$rows)
	    	return false;
		
		return mysql_fetch_array($rows, MYSQL_ASSOC);
	}
	
	static function seek(&$rows, $to = 0) {
	    if (!$rows)
	    	return false;
		
		return mysql_data_seek($rows, $to);
	}
	
	static function rows($rows) {
	    if (!$rows)
	    	return false;
		
		return mysql_num_rows($rows);
	}
	
	static function affected() {
		return mysql_affected_rows(sql::$link);
	}

	static function escape($string) {
		return mysql_real_escape_string($string, sql::$link);
	}
	
	static function count($tblkey = '*', $debug = false) {
		if (sql::$lastQuery) {
			$query = sql::$lastQuery;
			preg_match("/FROM (.*?) (GROUP|ORDER|LIMIT)/is", $query, $found);
			
			if (stristr($tblkey, 'SELECT')) {
				$query = 
					$tblkey .
					" LIMIT 1";
			} else {
				$query = 
					" SELECT COUNT(".$tblkey.") AS `Rows` FROM " .
					$found[1] .
					" LIMIT 1";
			}
			
			$query = sql::prefixTable($query);
			$row = sql::fetch(sql::run($query, $debug));
			
		} else {
			$query = "SELECT FOUND_ROWS() AS `Rows`";
			
			$row = sql::fetch(sql::run($query, $debug));
		}
		
		if (isset($row['Rows']))
			return $row['Rows'];
		
		return 0;	
	}
	
	static function search($search, $fields = array('Title'), $type = 'AND', 
		$commandfields = array()) 
	{
		if (!trim($search))
			return null;
			
		if (strpos($search, ',') !== false) {
			$separator = ',';
			$search = trim($search, ', ');
		} else {
			$separator = ' ';
			$search = trim($search);
		}
		
		$searchquery = null;
		$commandquery = null;
		
		$keywords = array();
		$commands = array();
		
		if (preg_match_all('/([^ :]+:(".+?"|[^ ]+)( |$))/', $search, $matches))
			$commands = $matches[1];
		
		foreach($commands as $command) {
			$search = str_replace($command, '', $search);
			@list($commandid, $commanddata) = explode(":", $command);
			
			if (!isset($commandfields[$commandid]) || !$commandfields[$commandid])
				continue;
			
			if ($commandquery)
				$commandquery .= " ".$type;
		
			$commandquery .= " `".$commandfields[$commandid]."` LIKE '%".
				sql::escape(trim($commanddata, ' "'))."%'";
		}
		
		if ($commandquery)
			$commandquery = " (".$commandquery.") ";
		
		if (preg_match_all('/(".+?"|[^'.$separator.']+)('.$separator.'|$)/', $search, $matches))
			$keywords = $matches[1];
		
		if (count($keywords) > 21)
			$keywords = array_slice($keywords, 0, 21);
		
		if (is_array($fields) && count($fields) && count($keywords)) {
			foreach($fields as $field) {
				if (!$field)
					continue;
				
				if ($searchquery)
					$searchquery .= " OR";
				
				$keywordsquery = null;
				
				foreach($keywords as $keyword) {
					if ($keywordsquery)
						$keywordsquery .= " ".$type;
				
					$keywordsquery .= " `".$field."` LIKE '%".
						sql::escape(trim($keyword, ' "'))."%'";
				}
				
				if ($keywordsquery)
					$searchquery .= " (".$keywordsquery.") ";
			}
		}
		
		if (!$commandquery && !$searchquery)
			return " AND (NOT 1)";
			
		
		return " AND (" .
			($commandquery?
				" (".$commandquery.")":
				null) .
			($commandquery && $searchquery?
				" AND ":
				null) .
			($searchquery?
				" (".$searchquery.")":
				null) .
			")";
	}
	
	static function lastQuery() {
		return sql::$lastQuery;
	}
	
	static function error() {
		return mysql_error(sql::$link);
	}

	static function logout() {
    	$result = mysql_close(sql::$link);
		
    	if (DEBUG && !requests::$ajax) {
    		debug::end();
    		debug::display();
    	}
    	
		return $result;
	}

	static function link() {
		return sql::$link;
	}
	
	static function fatalError($message = null) {
		if (!isset($message))
			$message = __("Could not establish a connection to the database.");
		
		if (((SQL_DATABASE == 'yourclient_DB' && SQL_USER == 'yourclient_mysqlusername' && 
			SQL_PASS == 'mysqlpassword') ||
			(SQL_DATABASE == 'yourdomain_DB' && SQL_USER == 'yourdomain_mysqluser' &&
			SQL_PASS == 'mysqlpass')) && @file_exists('install.php') &&
			isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'])
		{
			header('Location: install.php');
			exit();
		}
		
		api::callHooks(API_HOOK_BEFORE,
			'sql::fatalError', $_ENV, $message);
		
		echo
			"<html>" .
			"<head>" .
			"<title>" .
				__("Site Temporary Unavailable") .
			"</title>" .
			"</head>" .
			"<body>" .
			"<div style='margin: 100px auto; border: solid 1px #CCCCCC; " .
				"width: 500px; padding: 10px; text-align: center; " .
				"font-family: Arial, Helvetica, Sans-serif;'>" .
				"<h1>" .
					__("Site Temporary Unavailable") .
				"</h1>" .
				"<p>" .
					($message?
						$message .
						"<br />":
						null) .
					sprintf(
						__("We are sorry for the inconvenience and " .
						"appreciate your patience during this time. " .
						"Please wait for a few minutes and <a href='%s'>" .
						"try again</a>."), 
						strip_tags((string)$_SERVER['REQUEST_URI'])) .
				"</p>" .
			"</div>" .
			"</body>" .
			"</html>";
		
		api::callHooks(API_HOOK_AFTER,
			'sql::fatalError', $_ENV, $message);
		
		exit;
	}
	
	static function displayError() {
		$error = sql::error();
		
		if (!$error)
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'sql::displayError', $_ENV);
		
		if (isset($GLOBALS['USER']) && 
			$GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin']) 
		{
			$backtrace = null;
			
			foreach(debug_backtrace() as $key => $trace) {
				$backtrace .=
					"<li>".$trace['file'] .
						" @".$trace['line']." - " .
						(isset($trace['class'])?
							$trace['class']:
							null) .
						(isset($trace['type'])?
							$trace['type']:
							null) .
						$trace['function']."()" .
					"</li>";
			}
			
			tooltip::display(
				__("SQL Error:"). " " .
				$error .
				"<br /><br />" .
				htmlspecialchars(sql::$lastQuery),
				TOOLTIP_ERROR);
			
			tooltip::display(
				"<b>".__("Backtrace"). "</b><ul>" .
					$backtrace."</ul>",
				TOOLTIP_NOTIFICATION);
			
		} else {
			tooltip::display(
				(!isset($GLOBALS['USER']) || !$GLOBALS['USER']->loginok?
					__("SQL Error! Please contact site administrator or " .
						"login to learn more about this error."):
					__("SQL Error! Please contact site administrator.")),
				TOOLTIP_ERROR);
		}
		
		api::callHooks(API_HOOK_AFTER,
			'sql::displayError', $_ENV, $error);
		
		return $error;
	}
	
	static function display($quiet = false) {
		$error = sql::error();
		if ($quiet)
			return $error;
		
		api::callHooks(API_HOOK_BEFORE,
			'sql::display', $_ENV);
		
		echo 
			"<p class='sql-query'>" .
				"<code>".
					htmlspecialchars(sql::$lastQuery).";<br />";
		
		if (!$error && preg_match('/^ *?SELECT/i', sql::$lastQuery) &&
			$explains = @mysql_query('EXPLAIN '.sql::prefixTable(sql::$lastQuery), sql::$link))
		{
			echo
						"<span class='comment'><b>EXPLAIN</b>: ";
			
			$explains = sql::fetch($explains);
			foreach($explains as $key => $explain) {
				if ($key == 'id' || $key == 'table')
					continue;
				
				echo $key.'='.($explain?$explain:'NULL').'; ';
			}
			
			echo
					"</span>";
		}
		
		echo
				"</code><br />";
		
		if ($error)
			echo "<b class='red'>" .
					strtoupper(__("Error")) .
				"</b> " .
				"(".$error.")";
		else
			echo "<b>" .
					strtoupper(__("Ok")) .
				"</b> (" .
				sprintf(__("affected rows: %s"),
					sql::affected()) .
					(sql::$lastQueryTime?
						", " .
						sprintf(__("query took: %s seconds"), 
							sql::$lastQueryTime):
						null).
					")";
		
		echo
				"</br>" .
			"</p>";
		
		api::callHooks(API_HOOK_AFTER,
			'sql::display', $_ENV, $error);
		
		return $error;
	}
} 

?>