<?php

/***************************************************************************
 *            security.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
if (!defined('MINIMUM_PASSWORD_LENGTH'))
	define('MINIMUM_PASSWORD_LENGTH', 5);

if (!defined('SECURITY_ITERATION_COUNT'))
	define('SECURITY_ITERATION_COUNT', 7);

// Only used in fallback mode (sha1 passwords)
if (!defined('SECURITY_SALT_LENGTH'))
	define('SECURITY_SALT_LENGTH', 7);

if (defined('USE_RECAPTCHA') && USE_RECAPTCHA)
	include_once('lib/recaptcha/recaptchalib.php');

class _security {
	static $fonts = array(
		'arial.ttf',
		'AntykwaBold.ttf',
		'Candice.ttf',
		'Duality.ttf',
		'Heineken.ttf',
		'Jura.ttf',
		'StayPuft.ttf',
		'TimesNewRomanBold.ttf',
		'VeraSansBold.ttf');
	
	static $bots = array(
		'Googlebot',
		'Mediapartners-Google',
		'Adsbot-Google',
		'MSNBot',
		'Slurp',
		'YahooSeeker',
		'Teoma',
		'BaiDuSpider',
		'WISENutbot',
		'Scooter',
		'FAST-WebCrawler',
		'Ask Jeeves',
		'Speedy Spider',
		'SurveyBot',
		'IBM_Planetwide',
		'GigaBot',
		'ia_archiver');
	
	var $ajaxRequest = null;
	var $adminPath = 'admin/site/security';
	
	function countAdminItems() {
		api::callHooks(API_HOOK_BEFORE,
			'security::countAdminItems', $this);
		
		$bfrow = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{bfprotectionbans}`" .
			" LIMIT 1"));
		
		$ptrow = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{ptprotectionbans}`" .
			" LIMIT 1"));
		
		$result = $bfrow['Rows']+$ptrow['Rows'];
		
		api::callHooks(API_HOOK_AFTER,
			'security::countAdminItems', $this, $result);
		
		return $result;
	}
	
	function setupAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'security::setupAdmin', $this);
		
		favoriteLinks::add(
			__('More Settings'), 
			'?path=admin/site/settings');
		favoriteLinks::add(
			__('Users'), 
			'?path=admin/members/users');
		
		api::callHooks(API_HOOK_AFTER,
			'security::setupAdmin', $this);
	}
	
	function verifyAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'security::verifyAdmin', $this);
		
		$bfon = null;
		$pton = null;
		$ip = null;
		$userid = null;
		$delete = null;
		
		if (isset($_POST['bfon']))
			$bfon = (bool)$_POST['bfon'];
		if (isset($_POST['pton']))
			$pton = (bool)$_POST['pton'];
		if (isset($_GET['ip']))
			$ip = (float)$_GET['ip'];
		if (isset($_GET['userid']))
			$userid = (int)$_GET['userid'];
		if (isset($_POST['delete']))
			$delete = (bool)$_POST['delete'];
			
		if (isset($bfon)) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'security::verifyAdmin', $this);
				return false;
			}
			
			if ($bfon) {
				$result = $this->switchBF(true);
				tooltip::display(
					__("Brute Force Protection has been turned ON."),
					TOOLTIP_SUCCESS);
				
			} else {
				$result = $this->switchBF(false);
				tooltip::display(
					__("Brute Force Protection has been turned OFF."),
					TOOLTIP_SUCCESS);
			}
			
			api::callHooks(API_HOOK_AFTER,
				'security::verifyAdmin', $this, $bfon, $result);
			
			return true;
		}
		
		if (isset($pton)) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'security::verifyAdmin', $this);
				return false;
			}
			
			if ($pton) {
				$result = $this->switchPT(true);
				tooltip::display(
					__("Password Trading Protection has been turned ON."),
					TOOLTIP_SUCCESS);
				
			} else {
				$result = $this->switchPT(false);
				tooltip::display(
					__("Password Trading Protection has been turned OFF."),
					TOOLTIP_SUCCESS);
			}
			
			api::callHooks(API_HOOK_AFTER,
				'security::verifyAdmin', $this, $pton, $result);
			
			return true;
		}
		
		if ($delete && isset($ip)) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'security::verifyAdmin', $this);
				return false;
			}
			
			$result = $this->deleteBFBan($ip);
			
			if ($result)
				tooltip::display(
					sprintf(__("IP \"%s\" has been successfully removed from the list."),
						security::long2ip($ip)),
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'security::verifyAdmin', $this, $ip, $result);
			
			return $result;
		}
		
		if ($delete && $userid) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'security::verifyAdmin', $this);
				return false;
			}
			
			$result = $this->deletePTBan($userid);
			
			if ($result) {
				$user = $GLOBALS['USER']->get($userid);
				
				tooltip::display(
					sprintf(__("User \"%s\" has been successfully removed from the list."),
						$user['UserName']),
					TOOLTIP_SUCCESS);
			}
			
			api::callHooks(API_HOOK_AFTER,
				'security::verifyAdmin', $this, $userid, $result);
			
			return $result;
		}
	}
	
	function displayAdminBFListHeader() {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminBFListHeader', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("IP")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Usernames used")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Expires on")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminBFListHeader', $this);
	}
	
	function displayAdminBFListHeaderOptions() {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminBFListHeaderOptions', $this);
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminBFListHeaderOptions', $this);
	}
	
	function displayAdminBFListHeaderFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminBFListHeaderFunctions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminBFListHeaderFunctions', $this);
	}
	
	function displayAdminBFListItem(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminBFListItem', $this, $row);
		
		echo
			"<td>" .
				"<span class='nowrap bold'>" .
				security::long2ip($row['IP']) .
				"</span>" .
			"</td>" .
			"<td class='auto-width'>" .
				$row['Usernames'] .
			"</td>" .
			"<td style='text-align: right;'>" .
				"<span class='nowrap'>" .
				calendar::datetime($row['EndTimeStamp']) .
				"</span>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminBFListItem', $this, $row);
	}
	
	function displayAdminBFListItemOptions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminBFListItemOptions', $this, $row);
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminBFListItemOptions', $this, $row);
	}
	
	function displayAdminBFListItemFunctions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminBFListItemFunctions', $this, $row);
		
		echo
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('bfon, pton, ip, userid, delete') .
					"&amp;ip=".$row['IP']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminBFListItemFunctions', $this, $row);
	}
	
	function displayAdminBFListFooter() {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminBFListFooter', $this);
		
		echo 
			"<p class='comment'>" .
				__("For additional brute force protection settings please see") .
				" <a href='".url::uri('ALL') .
					"?path=admin/site/settings'>" .
					__("Global Settings") .
				"</a>." .
			"</p>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminBFListFooter', $this);
	}
	
	function displayAdminBFList(&$rows) {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminBFList', $this, $rows);
		
		echo "<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>";
		
		$this->displayAdminBFListHeader();
		$this->displayAdminBFListHeaderOptions();
					
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$this->displayAdminBFListHeaderFunctions();
		
		echo
				"</tr>" .
				"</thead>" .
				"<tbody>";
				
		$i = 0;		
		while($row = sql::fetch($rows)) {
			echo 
				"<tr".($i%2?" class='pair'":NULL).">";
			
			$this->displayAdminBFListItem($row);
			$this->displayAdminBFListItemOptions($row);
			
			if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
				$this->displayAdminBFListItemFunctions($row);
					
			echo
				"</tr>";
			
			$i++;
		}
		
		echo 
				"</tbody>" .
			"</table>";
		
		$this->displayAdminBFListFooter();
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminBFList', $this, $rows);
	}
	
	function displayAdminBFFunctions($bfenabled = true) {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminBFFunctions', $this, $bfenabled);
		
		echo
			"<form action='".url::uri('bfon, pton, ip, userid, delete')."' method='post'>" .
				"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />" .
				"<input type='hidden' name='bfon' value='" .
					($bfenabled?
						0:
						1) .
					"' />" .
				"<input title='".htmlspecialchars(__("Turn Protection On/Off"), ENT_QUOTES)."'" .
					" type='submit' class='button' name='bfonsubmit' value='" .
					($bfenabled?
						__("Turn OFF"):
						__("Turn ON")) .
					"'>" .
			"</form>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminBFFunctions', $this, $bfenabled);
	}
	
	function displayAdminBFTitle($bfenabled = true) {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminBFTitle', $this, $bfenabled);
		
		echo
			"<b>" .
			__("Brute Force Protection") .
				" (" .
				($bfenabled?
					"<span class='green'>".__("ON")."</span>":
					"<span class='red'>".__("OFF")."</span>") .
				")" .
			"</b>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminBFTitle', $this, $bfenabled);
	}
	
	function displayAdminBFDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminBFDescription', $this);
		
		echo
			"<p class='comment'>" .
			__("The logged IP adresses in the list below have been banned " .
				"(because trying to login with different users in a short time) " .
				"and won't be able to login until expiration date / time. " .
				"To unban an IP just delete it from the list.") .
			"</p>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminBFDescription', $this);
	}
	
	function displayAdminPTListHeader() {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminPTListHeader', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("User")."</span></th>" .
			"<th><span class='nowrap'>".
				__("IPs used")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Expires on")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminPTListHeader', $this);
	}
	
	function displayAdminPTListHeaderOptions() {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminPTListHeaderOptions', $this);
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminPTListHeaderOptions', $this);
	}
	
	function displayAdminPTListHeaderFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminPTListHeaderFunctions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminPTListHeaderFunctions', $this);
	}
	
	function displayAdminPTListItem(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminPTListItem', $this, $row);
		
		$user = $GLOBALS['USER']->get($row['UserID']);
			
		echo
			"<td>" .
				"<span class='nowrap bold'>";
	
		$GLOBALS['USER']->displayUserName($user);
		
		echo
				"</span>" .
			"</td>" .
			"<td class='auto-width'>" .
				$row['IPs'] .
			"</td>" .
			"<td style='text-align: right;'>" .
				"<span class='nowrap'>" .
				calendar::datetime($row['EndTimeStamp']) .
				"</span>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminPTListItem', $this, $row);
	}
	
	function displayAdminPTListItemOptions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminPTListItemOptions', $this, $row);
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminPTListItemOptions', $this, $row);
	}
	
	function displayAdminPTListItemFunctions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminPTListItemFunctions', $this, $row);
		
		echo
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('bfon, pton, ip, userid, delete') .
					"&amp;userid=".$row['UserID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminPTListItemFunctions', $this, $row);
	}
	
	function displayAdminPTListFooter() {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminPTListFooter', $this);
		
		echo 
			"<p class='comment'>" .
				__("For additional password trading protection settings please see") .
				" <a href='".url::uri('ALL') .
					"?path=admin/site/settings'>" .
					__("Global Settings") .
				"</a>." .
			"</p>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminPTListFooter', $this);
	}
	
	function displayAdminPTList(&$rows) {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminPTList', $this, $rows);
		
		echo "<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>";
		
		$this->displayAdminPTListHeader();
		$this->displayAdminPTListHeaderOptions();
					
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$this->displayAdminPTListHeaderFunctions();
		
		echo
				"</tr>" .
				"</thead>" .
				"<tbody>";
				
		$i = 0;		
		while($row = sql::fetch($rows)) {
			echo 
				"<tr".($i%2?" class='pair'":NULL).">";
			
			$this->displayAdminPTListItem($row);
			$this->displayAdminPTListItemOptions($row);
			
			if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
				$this->displayAdminPTListItemFunctions($row);
					
			echo
				"</tr>";
			
			$i++;
		}
		
		echo 
				"</tbody>" .
			"</table>";
		
		$this->displayAdminPTListFooter();
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminPTList', $this, $rows);
	}
	
	function displayAdminPTFunctions($ptenabled = true) {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminPTFunctions', $this, $ptenabled);
		
		echo
			"<form action='".url::uri('bfon, pton, ip, userid, delete')."' method='post'>" .
				"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />" .
				"<input type='hidden' name='pton' value='" .
					($ptenabled?
						0:
						1) .
					"' />" .
				"<input title='".htmlspecialchars(__("Turn Protection On/Off"), ENT_QUOTES)."'" .
					" type='submit' class='button' name='ptonsubmit' value='" .
					($ptenabled?
						__("Turn OFF"):
						__("Turn ON")) .
					"'>" .
			"</form>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminPTFunctions', $this, $ptenabled);
	}
	
	function displayAdminPTTitle($ptenabled = true) {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminPTTitle', $this, $ptenabled);
		
		echo
			"<b>" .
			__("Password Trading Protection") .
				" (" .
				($ptenabled?
					"<span class='green'>".__("ON")."</span>":
					"<span class='red'>".__("OFF")."</span>") .
				")" .
			"</b>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminPTTitle', $this, $ptenabled);
	}
	
	function displayAdminPTDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminPTDescription', $this);
		
		echo
			"<p class='comment'>" .
			__("The logged Users in the list below have been suspended " .
				"(because of simultaneously logins from different IPs) " .
				"and won't be able to login until expiration date / time. " .
				"To unsuspend a user just delete it from the list.") .
			"</p>";
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminPTDescription', $this);
	}
	
	function displayAdminTitle($ownertitle = null) {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminTitle', $this, $ownertitle);
		
		admin::displayTitle(
			__("Security Alerts Administration"),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdminDescription', $this);
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'security::displayAdmin', $this);
		
		$ip = null;
		$userid = null;
		$delete = null;
		
		if (isset($_GET['ip']))
			$ip = (float)$_GET['ip'];
		if (isset($_GET['userid']))
			$userid = (int)$_GET['userid'];
		if (isset($_GET['delete']))
			$delete = (bool)$_GET['delete'];
		
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		$this->verifyAdmin();
		
		$bfenabled = settings::get('Brute_Force_Protection_Enabled');
		$ptenabled = settings::get('Password_Trading_Protection_Enabled');
		
		if (!isset($bfenabled))
			$bfenabled = BRUTE_FORCE_PROTECTION_ENABLED;
		
		if (!isset($ptenabled))
			$ptenabled = PASSWORD_TRADING_PROTECTION_ENABLED;
		
		echo
			"<div class='admin-content'>";
		
		if ($delete && ($ip || $userid) && empty($_POST['delete'])) {
			if ($userid) {
				$selected = $GLOBALS['USER']->get($userid);
				$selected = $selected['UserName'];
			} else {
				$selected = security::long2ip($ip);
			}
			
			url::displayConfirmation(
				'<b>'.__('Delete').'?!</b> "'.$selected.'"');
		}
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE && 
			JCORE_VERSION >= '0.5') 
		{
			echo
				"<div style='float: right;'>";
			
			$this->displayAdminBFFunctions($bfenabled);
			
			echo
				"</div>";
		}
		
		$this->displayAdminBFTitle($bfenabled);
		$this->displayAdminBFDescription();
		
		$rows = sql::run(
			" SELECT * FROM `{bfprotectionbans}`" .
			" ORDER BY `EndTimeStamp`");
		
		if (sql::rows($rows))
			$this->displayAdminBFList($rows);
		else
			tooltip::display(
				__("No brute force attempts have been logged."),
				TOOLTIP_NOTIFICATION);
		
		echo
			"<div class='separator'></div>" .
			"<br />";
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE && 
			JCORE_VERSION >= '0.5') 
		{
			echo
				"<div style='float: right;'>";
			
			$this->displayAdminPTFunctions($ptenabled);
			
			echo
				"</div>";
		}
		
		$this->displayAdminPTTitle($ptenabled);
		$this->displayAdminPTDescription();	
		
		$rows = sql::run(
			" SELECT * FROM `{ptprotectionbans}`" .
			" ORDER BY `EndTimeStamp`");
		
		if (sql::rows($rows))
			$this->displayAdminPTList($rows);
		else
			tooltip::display(
				__("No password trading attempts have been logged."),
				TOOLTIP_NOTIFICATION);
		
		echo
			"</div>"; //admin-content
		
		api::callHooks(API_HOOK_AFTER,
			'security::displayAdmin', $this);
	}
	
	function switchBF($on) {
		if ($on)
			return settings::set('Brute_Force_Protection_Enabled', 1);
		
		return settings::set('Brute_Force_Protection_Enabled', 0);
	}
	
	function switchPT($on) {
		if ($on)
			return settings::set('Password_Trading_Protection_Enabled', 1);
		
		return settings::set('Password_Trading_Protection_Enabled', 0);
	}
	
	function deleteBFBan($ip) {
		if (!isset($ip))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'security::deleteBFBan', $this, $ip);
		
		sql::run(
			" DELETE FROM `{bfprotectionbans}`" .
			" WHERE `IP` = '".$ip."'");
		
		sql::run(
			" DELETE FROM `{bfprotection}`" .
			" WHERE `IP` = '".$ip."'");
		
		api::callHooks(API_HOOK_AFTER,
			'security::deleteBFBan', $this, $ip);
		
		return true;
	}
	
	function deletePTBan($userid) {
		if (!$userid)
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'security::deletePTBan', $this, $userid);
		
		sql::run(
			" DELETE FROM `{ptprotectionbans}`" .
			" WHERE `UserID` = '".$userid."'");
		
		api::callHooks(API_HOOK_AFTER,
			'security::deletePTBan', $this, $userid);
		
		return true;
	}
	
	function ajaxRequest() {
		api::callHooks(API_HOOK_BEFORE,
			'security::ajaxRequest', $this);
		
		$scimage = null;
		$newsession = null;
		
		if (isset($_GET['scimage']))
			$scimage = (int)$_GET['scimage'];
		
		if (isset($_GET['regeneratesessionid']))
			$newsession = (int)$_GET['regeneratesessionid'];
		
		$result = false;
		
		if ($scimage) {
			$this->genImageCode();
			$result = true;
		}
		
		if ($newsession) {
			session_start();
  			session_regenerate_id();
  			$result = true;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'security::ajaxRequest', $this, $result);
		
		return $result;
	}
	
	static function closeTags($html) {
		preg_match_all("#<([a-z0-9]+)( .*)?(?!/)>#iU", $html, $result, PREG_OFFSET_CAPTURE);
		
		if (!isset($result[1]))
			return $html;
		
		$openedtags = $result[1];
		$len_opened = count($openedtags);
		
		if (!$len_opened)
			return $html;
		
		preg_match_all("#</([a-z0-9]+)>#iU", $html, $result, PREG_OFFSET_CAPTURE);
		$closedtags = array();
		
		foreach($result[1] as $tag)
			$closedtags[$tag[1]] = $tag[0];
		
		$openedtags = array_reverse($openedtags);
		
		for($i = 0; $i < $len_opened; $i++) {
			if (preg_match('/(img|br|hr)/i', $openedtags[$i][0]))
				continue;
			
			$found = array_search($openedtags[$i][0], $closedtags);
			
			if (!$found || $found < $openedtags[$i][1])
				$html .= "</".$openedtags[$i][0].">";
			
			if ($found)
				unset($closedtags[$found]);
		}
		
		return $html;
	}
	
	static function strRand($length, $numbers = true) {
		$str = "";
		
		while(strlen($str)<$length) {
			if ($numbers)
				$random=rand(48,122);
			else
				$random=rand(97,122);
			
			if(($random>47 && $random<58) || ($random>96 && $random<123)) {
				$str.=chr($random);
			}
		}
		
		return $str;
	}

	static function verifyImageCode($scimagecode = null) {
		if (defined('USE_RECAPTCHA') && USE_RECAPTCHA) {
			$resp = recaptcha_check_answer(
				RECAPTCHA_PRIVATE_KEY,
				$_SERVER["REMOTE_ADDR"],
				@$_POST["recaptcha_challenge_field"],
				@$_POST["recaptcha_response_field"]);
			
			return $resp->is_valid;
			
		}
		
		global $_COOKIE;
		
		if ($scimagecode && $_COOKIE['scimagestr'] && 
			$_COOKIE['scimagestr'] == md5(SITE_PATH.WEBMASTER_EMAIL.SQL_USER.$scimagecode)) 
			return true;
		
		return false;
	}
	
	static function genImageCode() {
		if (defined('USE_RECAPTCHA') && USE_RECAPTCHA)
			return recaptcha_get_html(RECAPTCHA_PUBLIC_KEY);
		
		$fontsize = 30;
		$codelength = 7;
		$textangle = rand(-3, 3);
		$randomlines = 7;
		$foregroundcolor = array(0, 0, 0);
		$backgroundcolor = array(255, 255, 200);
		
		$font = 'arial.ttf';
		
		if (defined('SECURITY_IMAGE_RANDOM_FONTS') && SECURITY_IMAGE_RANDOM_FONTS)
			$font = security::$fonts[array_rand(security::$fonts)];
		
		if (defined('JCORE_PATH'))
			$ttffont = JCORE_PATH."lib/fonts/".$font;
		else
			$ttffont = SITE_PATH."lib/fonts/".$font;
		
		if (defined('SECURITY_IMAGE_FONT_SIZE') && SECURITY_IMAGE_FONT_SIZE)
			$fontsize = (int)SECURITY_IMAGE_FONT_SIZE;
		
		if (defined('SECURITY_IMAGE_CODE_LENGTH') && SECURITY_IMAGE_CODE_LENGTH)
			$codelength = (int)SECURITY_IMAGE_CODE_LENGTH;
		
		if (defined('SECURITY_IMAGE_RANDOM_LINES'))
			$randomlines = (int)SECURITY_IMAGE_RANDOM_LINES;
		
		if (defined('SECURITY_IMAGE_FONT') && SECURITY_IMAGE_FONT &&
			(!defined('SECURITY_IMAGE_RANDOM_FONTS') || !SECURITY_IMAGE_RANDOM_FONTS)) 
		{
			if (strpos(SECURITY_IMAGE_FONT, '/') !== 0 && 
				strpos(SECURITY_IMAGE_FONT, '://') === false)
			{
				if (defined('JCORE_PATH'))
					$ttffont = JCORE_PATH.'lib/'.SECURITY_IMAGE_FONT;
				else
					$ttffont = SITE_PATH.'lib/'.SECURITY_IMAGE_FONT;
				
			} else {
				$ttffont = SECURITY_IMAGE_FONT;
			}
		}
			
		if (defined('SECURITY_IMAGE_FOREGROUND_COLOR') && SECURITY_IMAGE_FOREGROUND_COLOR) {
			preg_match_all('/[0-9abcdef]{2}/', SECURITY_IMAGE_FOREGROUND_COLOR, $matches);
			
			if (!isset($matches[0][0]))
				$matches[0][0] = 0;
			
			if (!isset($matches[0][1]))
				$matches[0][1] = 0;
			
			if (!isset($matches[0][2]))
				$matches[0][2] = 0;
			
			$foregroundcolor = array(
				hexdec($matches[0][0]), hexdec($matches[0][1]), hexdec($matches[0][2]));
		}
		
		if (defined('SECURITY_IMAGE_BACKGROUND_COLOR') && SECURITY_IMAGE_BACKGROUND_COLOR) {
			preg_match_all('/[0-9abcdef]{2}/', SECURITY_IMAGE_BACKGROUND_COLOR, $matches);
			
			if (!isset($matches[0][0]))
				$matches[0][0] = 0;
			
			if (!isset($matches[0][1]))
				$matches[0][1] = 0;
			
			if (!isset($matches[0][2]))
				$matches[0][2] = 0;
			
			$backgroundcolor = array(
				hexdec($matches[0][0]), hexdec($matches[0][1]), hexdec($matches[0][2]));
		}
		
		if (JCORE_VERSION < '0.6' && $fontsize == 15)
			$fontsize = $fontsize*2; 
		
		if (JCORE_VERSION < '0.6' || 
			defined('SECURITY_IMAGE_RANDOM_WORDS') && SECURITY_IMAGE_RANDOM_WORDS)
			$text = substr(security::randomWord(), 0, $codelength);
		else
			$text = security::strRand($codelength, false);
		
		setcookie ("scimagestr", md5(SITE_PATH.WEBMASTER_EMAIL.SQL_USER.$text));
		
		$bbox = @imagettfbbox($fontsize, 0, $ttffont, $text);
		if ($bbox[4] < 0)
			$bbox[4] = $bbox[4]*-1;
		
		$width = $bbox[4]+20;
		
		$bbox = @imagettfbbox($fontsize, 0, $ttffont, 'j');
		if ($bbox[5] < 0)
			$bbox[5] = $bbox[5]*-1;
		
		$height = $bbox[5]+10;
		
		$img = ImageCreateTrueColor($width, $height);
		
		$fontcolor = imagecolorallocate($img, 
			$foregroundcolor[0], $foregroundcolor[1], $foregroundcolor[2]);
		
		$backcolor = imagecolorallocate($img,
			$backgroundcolor[0], $backgroundcolor[1], $backgroundcolor[2]);
	
		imagefill($img,0,0,$backcolor);
		
		if ($randomlines) {
			for ($i = 0; $i <= $randomlines; $i++)
				imageline ($img, 
					rand(0, $width), rand(0, $height), 
					rand(0, $width), rand(0, $height), 
					$fontcolor);
		}
		
        $x = rand(5, 20);
        $y = ceil($height/1.2);
        $length = strlen($text);
        $fontsizemin = ceil($fontsize*0.8);
        
        for ($i=0; $i<$length; $i++) {
            $coords = imagettftext($img, 
            	rand($fontsizemin, $fontsize), rand(8*-1, 8),
                $x, $y, $fontcolor, $ttffont, substr($text, $i, 1));
            
            $x += $coords[2]-$x;
        }
        
		header("Content-type: image/png");
		return imagepng($img);
	}
	
	static function permute($items, $perms = array()) { 
		static $permutedarray;
		 
		if (empty($items)) { 
			$permutedarray[]=$perms;
			 
		}  else { 
			for ($i = count($items)-1; $i >= 0; --$i) { 
				$newitems = $items; 
				$newperms = $perms;
				 
				list($foo) = array_splice($newitems, $i, 1);
				 
				array_unshift($newperms, $foo); 
				security::permute($newitems, $newperms); 
			}
			
			return $permutedarray; 
		}
	}
	
	static function randomWord($extended = false) {
		if (defined('JCORE_PATH'))
			$wordsfile = JCORE_PATH.'lib/security.words.php';
		else
			$wordsfile = SITE_PATH.'lib/security.words.php';
		
		if (!is_file($wordsfile))
			return false;

		$fp = fopen($wordsfile, "r");
		$length = strlen(fgets($fp));
		
		if (!$length)
			return false;
		
		$line = rand(1, (filesize($wordsfile)/$length)-2);
		if (fseek($fp, $length*$line) == -1)
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'security::randomWord', $_ENV, $extended);
		
		$word = trim(fgets($fp));
		fclose($fp);
		
		if ($extended) {
			$word = preg_split('//', $word, -1, PREG_SPLIT_NO_EMPTY);
			$vocals = array('a', 'e', 'i', 'o', 'u');
			
			foreach ($word as $i => $char) {
				if (mt_rand(0, 1) && in_array($char, $vocals)) {
					$word[$i] = $vocals[mt_rand(0, 4)];
				}
			}
			
			$word = implode('', $word);
		}

		api::callHooks(API_HOOK_AFTER,
			'security::randomWord', $_ENV, $extended, $word);
		
		return $word;
	}
	 
	static function randomChars($length = 5, $numbers = true) {
		api::callHooks(API_HOOK_BEFORE,
			'security::randomChars', $_ENV, $length, $numbers);
		
		$chars = null;
		
		for ($i = 0; $i < $length; $i++) {
			$r = rand(
				($numbers?
					0:
					1),
				2);
			
			if ($r == 2)
				$chars .= chr(rand(65, 90));
			elseif ($r)
				$chars .= chr(rand(97, 122));
			else
				$chars .= rand(0, 9);
		}
			
		api::callHooks(API_HOOK_AFTER,
			'security::randomChars', $_ENV, $length, $numbers);
		
		return $chars;		
	}
	
	static function genPassword($salt = null) {
		api::callHooks(API_HOOK_BEFORE,
			'security::genPassword', $_ENV, $salt);
		
    	if ($salt === null) {
			$salt = security::salt();
		} else {
			$salt = substr($salt, 0, SECURITY_SALT_LENGTH);
		}
		
		$result =
			'P' .
			substr(md5($salt.time()), 0, 5) .
			security::randomChars();
				
		api::callHooks(API_HOOK_AFTER,
			'security::genPassword', $_ENV, $salt, $result);
		
		return $result;
	}
	
	static function genToken($lifetime = 10800) {
		$i = ceil(time() / $lifetime);
		
		if (isset($GLOBALS['USER']))
			$u = implode((array)$GLOBALS['USER']->data);
		
		return sha1(session_id().$i.$u);
	}
	
	static function checkToken($token = null, $lifetime = 10800) {
		if (!$token && isset($_POST['_SecurityToken']))
			$token = (string)$_POST['_SecurityToken'];
		
		if (!$token)
			return false;
		
		$i = ceil(time() / $lifetime);
		$u = implode((array)$GLOBALS['USER']->data);

		if (sha1(session_id().$i.$u) == $token)
			return true;
		
		return false;
	}
	
	static function genHash($text, $salt = null) {
		if (CRYPT_BLOWFISH == 1) {
			$bfsalt = $salt;
			
	    	if ($bfsalt === null)
				$bfsalt = security::salt(22);
			elseif (strlen($bfsalt) > 22)
				$bfsalt = substr($bfsalt, 0, 22);
			elseif (strlen($bfsalt) < 22)
				$bfsalt = $bfsalt.security::salt(22 - strlen($bfsalt));
			
			$iteration = SECURITY_ITERATION_COUNT;
			if ($iteration < 4 || $iteration > 31)
				$iteration = 7;
			
			$hash = crypt($text, '$2a$' .
				($iteration<10?'0':null).$iteration.'$' .
				substr($bfsalt, 0, 22));
			
			if ($hash)
				return $hash;
		}
		
		if (CRYPT_MD5 == 1) {
			$md5salt = $salt;
			
	    	if ($md5salt === null)
				$md5salt = security::salt(9);
			elseif (strlen($md5salt) > 9)
				$md5salt = substr($md5salt, 0, 9);
			elseif (strlen($md5salt) < 9)
				$md5salt = $md5salt.security::salt(9 - strlen($md5salt));
			
			$hash = crypt($text, '$1$'.substr($md5salt, 0, 9));
			
			if ($hash)
				return $hash;
		}
		
    	if ($salt === null)
			$salt = security::salt(SECURITY_SALT_LENGTH);
		elseif (strlen($salt) > SECURITY_SALT_LENGTH)
			$salt = substr($salt, 0, SECURITY_SALT_LENGTH);
		elseif (strlen($salt) < SECURITY_SALT_LENGTH)
			$salt = $salt.security::salt(SECURITY_SALT_LENGTH - strlen($salt));
		
    	$hash =
    		$salt.sha1($salt.$text);
    	
    	return $hash;
	}
	
	static function checkHash($text, $hash) {
		if (strpos($hash, '$') === 0)
			return (crypt($text, $hash) == $hash);
			
		$salt = substr($hash, 0, SECURITY_SALT_LENGTH);
		return ($salt.sha1($salt.$text) == $hash);
	}
	
	static function salt($length = SECURITY_SALT_LENGTH) {
		$salt = '';
		
		if (@is_readable('/dev/urandom') && ($fp = @fopen('/dev/urandom', 'rb'))) {
			$salt = md5(fread($fp, $length));
			fclose($fp);
		}
		
		if (strlen($salt) < $length)
			for ($i = 0; $i < $length; $i += 16)
				$salt .= md5(uniqid(rand(), true).microtime().$i);
		
		return substr($salt, 0, $length);
	}
	
	// Kept only for compatibility reasons, you should use genHash and checkHash
	static function text2Hash($text, $salt = null) {
    	if ($salt === null)
			$salt = security::salt(SECURITY_SALT_LENGTH);
		elseif (strlen($salt) > SECURITY_SALT_LENGTH)
			$salt = substr($salt, 0, SECURITY_SALT_LENGTH);
		elseif (strlen($salt) < SECURITY_SALT_LENGTH)
			$salt = $salt.security::salt(SECURITY_SALT_LENGTH - strlen($salt));
		
    	return 
    		$salt.sha1($salt.$text);
	}
	
	static function isBot($useragent = null) {
		if (!$useragent)
			$useragent = (string)$_SERVER['HTTP_USER_AGENT'];
		
		if (!is_array(security::$bots))
			return false;
		
		foreach(security::$bots as $bot) {
			if (strpos($useragent, $bot) !== false)
				return $bot;
		}
		
		return false;
	}
	
	static function isIPv6($ip) {
		if (strpos($ip, ':') !== false)
			return true;
		
		return false;
	}
	
	static function ip2long($ip) {
		if (!$ip)
			return 0;
		
		if (security::isIPv6($ip)) {
			if (strpos($ip, '.') !== false)
				return ip2long(substr($ip, strrpos($ip, ':')+1));
			
			if (function_exists('gmp_strval')) {
				$ip_n = inet_pton($ip);
				$ipv6long = null;
				$bits = 15;
				
				while ($bits >= 0) {
					$bin = sprintf("%08b",(ord($ip_n[$bits])));
					$ipv6long = $bin.$ipv6long;
					$bits--;
				}
				
				return gmp_strval(gmp_init($ipv6long,2),10);
			}
			
			if (substr_count($ip, '::'))
				$ip = str_replace('::', str_repeat(':0000', 8 - substr_count($ip, ':')) . ':', $ip) ;
			
			$ip = explode(':', $ip) ;
			
			$r_ip = '' ;
			foreach ($ip as $v)
    			$r_ip .= str_pad(base_convert($v, 16, 2), 16, 0, STR_PAD_LEFT) ;
    		
			return base_convert($r_ip, 2, 10);
			
		}
		
		return ip2long($ip);
	}
	
	static function long2ip($long) {
		if (!$long)
			return '';
		
		if ($long > 4294967295) {
			$bin = gmp_strval(gmp_init($long,10),2);
			if (strlen($bin) < 128) {
				$pad = 128 - strlen($bin);
				
				for ($i = 1; $i <= $pad; $i++)
					$bin = "0".$bin;
			}
			
			$ipv6 = null;
			$bits = 0;
			
			while ($bits <= 7) {
				$bin_part = substr($bin,($bits*16),16);
				$ipv6 .= dechex(bindec($bin_part)).":";
				$bits++;
			}
			
			return inet_ntop(inet_pton(substr($ipv6,0,-1)));
			
		}
		
		return long2ip($long);
	}
	
	static function checkOutOfMemory($datalength, $multiplier = 1) {
		$memoryneeded = round($datalength*$multiplier);
		
		$availablememory = settings::iniGet('memory_limit', true);
		
		if (!$availablememory)
			return false;
			
		if ($memoryneeded+memory_get_usage() > $availablememory)
			return true;
			
		return false;
	}
}

?>