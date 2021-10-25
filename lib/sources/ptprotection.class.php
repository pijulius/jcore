<?php

/***************************************************************************
 *            ptprotection.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/email.class.php');

if (!defined('PASSWORD_TRADING_PROTECTION_ENABLED'))
	define('PASSWORD_TRADING_PROTECTION_ENABLED', true);

class _PTProtection {
	var $simultaneousLogins = 0;
	var $emailNotification = 1;
	var $maximumSimultaneousLogins = 3;
	var $protectionTimeMinutes = 60;

	function __construct() {
		if (defined('PASSWORD_TRADING_EMAIL_NOTIFICATION'))
			$this->emailNotification = PASSWORD_TRADING_EMAIL_NOTIFICATION;

		if (defined('PASSWORD_TRADING_MAXIMUM_SIMULTANEOUS_LOGINS'))
			$this->maximumSimultaneousLogins = PASSWORD_TRADING_MAXIMUM_SIMULTANEOUS_LOGINS;

		if (defined('PASSWORD_TRADING_PROTECTION_TIME_MINUTES'))
			$this->protectionTimeMinutes = PASSWORD_TRADING_PROTECTION_TIME_MINUTES;
	}

	function banUser($userid, $minutes) {
		if (!PASSWORD_TRADING_PROTECTION_ENABLED)
			return false;

		$ips = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(DISTINCT `FromIP` SEPARATOR ', ') AS `IPs`" .
			" FROM `{userlogins}` " .
			" WHERE `UserID` = '".(int)$userid."'"));

		$humanreadableips = explode(', ', $ips['IPs']);
		foreach($humanreadableips as $key => $humanreadableip)
			$humanreadableips[$key] = security::long2ip($humanreadableip);

		// Delete old bans so the new one gets the latest
		sql::run(
			" DELETE FROM `{ptprotectionbans}` " .
			" WHERE `UserID` = '".(int)$userid."'");

		sql::run(
			" INSERT INTO `{ptprotectionbans}` SET" .
			" `UserID` = '".(int)$userid."'," .
			" `EndTimeStamp` = DATE_ADD(NOW(), INTERVAL ".(int)$minutes." MINUTE)," .
			" `IPs` = '".implode(', ', $humanreadableips)."'");

		if ($this->emailNotification) {
			$user = sql::fetch(sql::run(
				" SELECT `UserName` FROM `{users}`" .
				" WHERE `ID` = '".(int)$userid."'"));

			$email = new email();

			$email->quiet = true;
			$email->load('WebmasterWarning');
			$email->to = WEBMASTER_EMAIL;

			$email->variables = array(
				'Warning' =>
					"The Maximum Simultaneous Logins (".
					$this->maximumSimultaneousLogins.
					") has been exceeded on ".date("Y-m-d H:i:s")."\n\n" .
					"For User: ".$user['UserName']."\n" .
					"IP's: ".implode(', ', $humanreadableips)."\n\n" .
					"Further login attempts have been blocked from the site for the " .
					"next ".$minutes." minutes!");

			$email->send();
			unset($email);
		}
	}

	function get($userid) {
		// Delete expired bans
		sql::run(
			" DELETE FROM `{ptprotectionbans}`" .
			" WHERE `EndTimeStamp` < NOW()");

		// Check logins from different ips
		$this->simultaneousLogins = sql::rows(sql::run(
			" SELECT `UserID` FROM `{userlogins}`" .
			" WHERE `UserID` = '".(int)$userid."'" .
			" GROUP BY `FromIP`"));

		return $this->simultaneousLogins;
	}

	function verify($userid) {
		if (!PASSWORD_TRADING_PROTECTION_ENABLED)
			return false;

		$row = sql::fetch(sql::run(
			" SELECT `UserID` FROM `{ptprotectionbans}`" .
			" WHERE `UserID` = '".(int)$userid."'" .
			" AND `EndTimeStamp` > NOW()"));

		$result = false;

		// If UserID is banned return error message, otherwise return false
		if ($row['UserID']) {
			$result = true;

		} else {
			$this->get($userid);

			if ($this->simultaneousLogins >= $this->maximumSimultaneousLogins) {
				$this->banUser($userid, $this->protectionTimeMinutes);

				$result = true;
			}
		}

		return $result;
	}
}

?>