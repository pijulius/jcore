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

if (!defined('SQL_PREFIX'))
	define('SQL_PREFIX', '');

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
		
		sql::$lastQuery = $query;
		
		if ($debug || sql::$debug)
			$time_start = microtime(true);
	
		if (!sql::$link) 
			sql::login();
			
		$query = sql::prefixTable($query);
	    $result = @mysql_query($query, sql::$link);
	    
		if ($debug || sql::$debug) {
			sql::$lastQueryTime = sql::mtimetosec(microtime(true), $time_start);
			sql::display();
			
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
	
	static function fetch($result) {
	    if (!$result)
	    	return false;
		
		return mysql_fetch_array($result, MYSQL_ASSOC);
	}
	
	static function seek(&$rows, $to = 0) {
	    if (!$rows)
	    	return false;
		
		return mysql_data_seek($rows, $to);
	}
	
	static function rows($result) {
	    if (!$result)
	    	return false;
		
		return mysql_num_rows($result);
	}
	
	static function affected() {
		return mysql_affected_rows(sql::$link);
	}

	static function escape($string) {
		return mysql_real_escape_string($string, sql::$link);
	}
	
	static function count($tblkey = '`ID`', $debug = false) {
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
		
		return $row['Rows'];	
	}
	
	static function search($search, $fields = array('Title'), $type = 'AND', 
		$commandfields = array()) 
	{
		if (!trim($search) || !is_array($fields) || !count($fields))
			return null;
			
		if (strpos($search, ',') !== false) {
			$separator = ',';
			$search = trim($search, ', ');
		} else {
			$separator = ' ';
			$search = trim($search);
		}
		
		$query = null;
		$commands = array();
		
		preg_match_all('/(".+?"|[^'.$separator.']+)('.$separator.'|$)/', trim($search), $matches);
		$keywords = $matches[1];
		
		if (count($keywords) > 21)
			$keywords = array_slice($keywords, 0, 21);
		
		foreach($fields as $field) {
			if (!$field)
				continue;
			
			if ($query)
				$query .= " OR";
			
			$keywordsquery = null;
			
			foreach($keywords as $keyword) {
				if (strpos(trim($keyword), ':') === 0) {
					$commands[] = trim($keyword, ' :');
					continue;
				}
				
				if ($keywordsquery)
					$keywordsquery .= " ".$type;
			
				$keywordsquery .= " `".$field."` LIKE '%".
					sql::escape(trim($keyword, ' "'))."%'";
			}
			
			if ($keywordsquery)
				$query .= " (".$keywordsquery.") ";
		}
		
		if (count($commandfields) && count($commands)) {
			if ($query)
				$query .= " AND";
			
			$commandsquery = null;
			
			foreach($commands as $command) {
				list($commandid, $commanddata) = explode("=", $command);
				
				if (!isset($commandfields[$commandid]) || !$commandfields[$commandid])
					continue;
				
				if ($commandsquery)
					$commandsquery .= " ".$type;
			
				$commandsquery .= " `".$commandfields[$commandid]."` LIKE '%".
					sql::escape(trim($commanddata, ' "'))."%'";
			}
			
			if ($commandsquery)
				$query .= " (".$commandsquery.") ";
		}
		
		if (!$query)
			return null;
		
		return " AND (".$query.")";
	}
	
	static function lastQuery() {
		return sql::$lastQuery;		
	}
	
	static function error() {
		return mysql_error(sql::$link);
	}

	static function logout() {
    	return mysql_close(sql::$link);
	}

	static function link() {
		return sql::$link;
	}
	
	static function fatalError($message = null) {
		if (!isset($message))
			$message = __("Could not establish a connection to the database.");
		
		exit(
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
						$_SERVER['REQUEST_URI']) .
				"</p>" .
			"</div>" .
			"</body>" .
			"</html>");
	}
	
	static function displayError() {
		$error = sql::error();
		
		if (!$error)
			return false;
		
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
		
		return $error;
	}
	
	static function display($quiet = false) {
		if ($quiet)
			return sql::error();
		
		echo 
			"<p class='sql-query'>" .
				"<code>".
					htmlspecialchars(sql::$lastQuery).";<br />";
		
		if (!sql::error() && preg_match('/^ *?SELECT/i', sql::$lastQuery) &&
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
		
		if (sql::error())
			echo "<b class='red'>" .
					strtoupper(__("Error")) .
				"</b> " .
				"(".sql::error().")";
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
		
		return sql::error();
	}
} 

?>