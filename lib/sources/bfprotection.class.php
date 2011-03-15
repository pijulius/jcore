<?php

/***************************************************************************
 *            bfprotection.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
include_once('lib/email.class.php');

if (!defined('BRUTE_FORCE_PROTECTION_ENABLED'))
	define('BRUTE_FORCE_PROTECTION_ENABLED', true);

class _BFProtection {
	var $failureAttempts = 0;
	var $emailNotification = 1;
	var $maximumFailureAttempts = 5;
	var $protectionTimeMinutes = 60;
	var $maximumFailureAttemptsBeforeTwoWeeksBan = 30;
	
	function __construct() {
		if (defined('BRUTE_FORCE_EMAIL_NOTIFICATION'))
			$this->emailNotification = BRUTE_FORCE_EMAIL_NOTIFICATION;
		
		if (defined('BRUTE_FORCE_MAXIMUM_FAILURE_ATTEMPTS'))
			$this->maximumFailureAttempts = BRUTE_FORCE_MAXIMUM_FAILURE_ATTEMPTS;
			
		if (defined('BRUTE_FORCE_PROTECTION_TIME_MINUTES'))
			$this->protectionTimeMinutes = BRUTE_FORCE_PROTECTION_TIME_MINUTES;
		
		if (defined('BRUTE_FORCE_MAXIMUM_FAILURE_ATTEMPTS_BEFORE_TWOWEEKS_BAN'))
			$this->maximumFailureAttemptsBeforeTwoWeeksBan = BRUTE_FORCE_MAXIMUM_FAILURE_ATTEMPTS_BEFORE_TWOWEEKS_BAN;
	}
	
	function add($user, $ip) {
		if (!BRUTE_FORCE_PROTECTION_ENABLED)
			return false;
		
		return sql::run(
			" INSERT INTO `{bfprotection}` SET" .
			" `Username` = '".sql::escape($user)."'," .
			" `IP` = '".ip2long($ip)."'," .
			" `TimeStamp` = NOW()");
	}
	
	function clear($ip) {
		sql::run(
			" DELETE FROM `{bfprotection}` " .
			" WHERE `IP` = '".ip2long($ip)."'");
	}
	
	function banIP($ip, $minutes) {
		$usernames = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(DISTINCT `Username` SEPARATOR ', ') AS `Usernames`" .
			" FROM `{bfprotection}` " .
			" WHERE `IP` = '".ip2long($ip)."'"));
		
		$oldban = sql::fetch(sql::run(
			" SELECT `IP` FROM `{bfprotectionbans}`" .
			" WHERE `IP` = '".ip2long($ip)."'"));
		
		if (isset($oldban['IP'])) {
			sql::run(
				" UPDATE `{bfprotectionbans}` SET" .
				" `EndTimeStamp` = DATE_ADD(NOW(), INTERVAL ".(int)$minutes." MINUTE)," .
				" `Usernames` = '".$usernames['Usernames']."'" .
				" WHERE `IP` = '".$oldban['IP']."'");
			
			return true;
		}
		
		sql::run(
			" INSERT INTO `{bfprotectionbans}` SET" .
			" `IP` = '".ip2long($ip)."'," .
			" `EndTimeStamp` = DATE_ADD(NOW(), INTERVAL ".(int)$minutes." MINUTE)," .
			" `Usernames` = '".$usernames['Usernames']."'");
		
		if ($this->emailNotification) {	
			$email = new email();
			
			$email->quiet = true;
			$email->load('WebmasterWarning');
			$email->to = WEBMASTER_EMAIL;
				
			if ($minutes > $this->protectionTimeMinutes)	
				$email->variables = array(
					'Warning' => 
						"A HUGE number of Failed Login Attempts have been noticed on ".
						date("Y-m-d H:i:s")."\n\n" .
						"From IP: ".$ip."\n" .
						"Usernames: ".$usernames['Usernames']."\n" .
						"Failed Login Attempts: ".$this->failureAttempts."\n\n" .
						"The IP has been banned from the site for two weeks!");
			else
				$email->variables = array(
					'Warning' =>
						"A large number of Failed Login Attempts have been noticed on ".
						date("Y-m-d H:i:s")."\n\n" .
						"From IP: ".$ip."\n" .
						"Usernames: ".$usernames['Usernames']."\n" .
						"Failed Login Attempts: ".$this->failureAttempts."\n\n" .
						"Future login attempts have been blocked from the site for the " .
						"next ".$this->protectionTimeMinutes." minutes!");
			
			$email->send();
			unset($email);
		}
		
		return true;
	}
	
	function get($ip) {
		// Delete expired bans
		sql::run(
			" DELETE FROM `{bfprotectionbans}`" .
			" WHERE `EndTimeStamp` < NOW()");
				
		// Delete attempts older than protectionTimeMinutes
		sql::run(
			" DELETE FROM `{bfprotection}`" .
			" WHERE `TimeStamp` < DATE_SUB(NOW(), INTERVAL ".(int)$this->protectionTimeMinutes." MINUTE)");
				
		$row = sql::fetch(sql::run(
			" SELECT COUNT(`IP`) AS `Rows` FROM `{bfprotection}`" .
			" WHERE `IP` = '".ip2long($ip)."'"));
			
		$this->failureAttempts = (int)$row['Rows'];
		return $this->failureAttempts;
	}
	
	function verify() {
		if (!BRUTE_FORCE_PROTECTION_ENABLED)
			return false;
		
		$row = sql::fetch(sql::run(
			" SELECT `IP` FROM `{bfprotectionbans}`" .
			" WHERE `IP` = '".ip2long($_SERVER['REMOTE_ADDR'])."'" .
			" AND (UNIX_TIMESTAMP(`EndTimeStamp`) - UNIX_TIMESTAMP(NOW()))/60 > ".
				(int)$this->protectionTimeMinutes));
			
		// If ip is banned for more than protectionTimeMinutes we exit the whole code/site as the 
		// ip should be banned
		 
		if ($row)
			exit();
		
		$this->get($_SERVER['REMOTE_ADDR']);
		
		if ($this->failureAttempts >= $this->maximumFailureAttempts) {
			if ($this->failureAttempts >= $this->maximumFailureAttemptsBeforeTwoWeeksBan)
				$this->banIP($_SERVER['REMOTE_ADDR'], 60*24*14); //two weeks ban in minutes
			else
				$this->banIP($_SERVER['REMOTE_ADDR'], $this->protectionTimeMinutes);
			
			return true;
		}
		
		return false;
	}
}

?>