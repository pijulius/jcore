<?php

/***************************************************************************
 *            settings.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

define('SETTINGS_TYPE_CONTAINER', 0);
define('SETTINGS_TYPE_TEXT', 1);
define('SETTINGS_TYPE_NUMBER', 2);
define('SETTINGS_TYPE_CHECKBOX', 3);
define('SETTINGS_TYPE_TEXTAREA', 4);
define('SETTINGS_TYPE_HIDDEN', 5);
define('SETTINGS_TYPE_SELECT', 6);
define('SETTINGS_TYPE_DATE', 7);
define('SETTINGS_TYPE_TIMESTAMP', 8);
define('SETTINGS_TYPE_PASSWORD', 9);
define('SETTINGS_TYPE_COLOR', 10);

if (!defined('DEBUG'))
	define('DEBUG', null);

if (!defined('COMPRESSION_DISABLED') && function_exists('ob_gzhandler') && @ini_get('zlib.output_compression'))
	define('COMPRESSION_DISABLED', true);

if (!defined('MOBILE_BROWSER')) {
	if (preg_match('/android|avantgo|blackberry|blazer|compal|elaine|fennec|' .
		'hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|' .
		'opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|' .
		'symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|' .
		'xda|xiino/i', (string)$_SERVER['HTTP_USER_AGENT']) ||
		preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|' .
		'ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|' .
		'ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|' .
		'bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|' .
		'cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|' .
		'dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|' .
		'er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|' .
		'gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|' .
		'hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|' .
		'hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|' .
		'im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|' .
		'klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|' .
		'\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|' .
		'm\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|' .
		't(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|' .
		'n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|' .
		'nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|' .
		'pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|' .
		'psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|' .
		'raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|' .
		'sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|' .
		'sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|' .
		'sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|' .
		'tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|' .
		'v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|' .
		'53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|' .
		'wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i',
		substr((string)$_SERVER['HTTP_USER_AGENT'],0,4)))
	{
		define('MOBILE_BROWSER', true);
	} else {
		define('MOBILE_BROWSER', false);
	}
}

if (!defined('IE_BROWSER')) {
	if(preg_match('/msie\s(\d+)/i', (string)$_SERVER['HTTP_USER_AGENT'], $array))
		define('IE_BROWSER', $array[1]);
	else
		define('IE_BROWSER', false);
}

if (!defined('FF_BROWSER')) {
	if(preg_match('/firefox\/(\d+)/i', (string)$_SERVER['HTTP_USER_AGENT'], $array))
		define('FF_BROWSER', $array[1]);
	else
		define('FF_BROWSER', false);
}

if (!defined('O_BROWSER')) {
	if(preg_match('/opera(\s|\/)(\d+)/i', (string)$_SERVER['HTTP_USER_AGENT'], $array))
		define('O_BROWSER', $array[2]);
	else
		define('O_BROWSER', false);
}

if (!defined('CH_BROWSER')) {
	if(preg_match('/chrome\/(\d+)/i', (string)$_SERVER['HTTP_USER_AGENT'], $array))
		define('CH_BROWSER', $array[1]);
	else
		define('CH_BROWSER', false);
}

if (!defined('SF_BROWSER')) {
	if(preg_match('/safari\/(\d+)/i', (string)$_SERVER['HTTP_USER_AGENT'], $array))
		define('SF_BROWSER', $array[1]);
	else
		define('SF_BROWSER', false);
}

include_once('lib/api.class.php');
include_once('lib/sql.class.php');

class _settings {
	var $adminPath = 'admin/site/settings';
	var $sqlTable = 'settings';
	var $textsDomain = 'messages';

	function __construct($table = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'settings::settings', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'settings::settings', $this, $handled);

			return $handled;
		}

		if ($table)
			$this->sqlTable = $table;

		$this->textsDomain = languages::$selectedTextsDomain;

		api::callHooks(API_HOOK_AFTER,
			'settings::settings', $this);
	}

	// ************************************************   Admin Part
	function verifyAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'settings::verifyAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'settings::verifyAdmin', $this, $handled);

			return $handled;
		}

		$update = null;
		$settings = null;

		if (isset($_POST['submit']))
			$update = (string)$_POST['submit'];

		if (isset($_POST['settings']))
			$settings = (array)$_POST['settings'];

		if ($update) {
			foreach($settings as $sid => $svalue) {
				$sid = strip_tags((string)$sid);
				$typeid = $this->getType($sid);

				if ($typeid == SETTINGS_TYPE_HIDDEN)
					continue;

				if ($typeid == SETTINGS_TYPE_TEXTAREA)
					$svalue = $svalue;
				elseif ($typeid == SETTINGS_TYPE_CHECKBOX)
					$svalue = (bool)$svalue;
				elseif ($typeid == SETTINGS_TYPE_NUMBER)
					$svalue = (int)$svalue;
				else
					$svalue = form::parseString($svalue);

				if (($sid == 'jQuery_Load_Plugins' &&
					 $svalue != JQUERY_LOAD_PLUGINS) ||
					($sid == 'jQuery_Load_Admin_Plugins' &&
					 $svalue != JQUERY_LOAD_ADMIN_PLUGINS))
				{
					if (!jQuery::update())
						tooltip::display(
							__("Settings have been successfully updated but " .
								"the template.js file couldn't be updated.")." " .
							sprintf(__("Please make sure \"%s\" is writable " .
								"by me or contact webmaster."),
								"template/template.js"),
							TOOLTIP_NOTIFICATION);
				}

				$this->edit($sid, $svalue);
			}

			tooltip::display(
				__("Settings have been successfully updated.")." " .
				"<a href='".url::uri('ALL').'?'.url::arg('path')."'>" .
					__("Refresh") .
				"</a>",
				TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'settings::verifyAdmin', $this, $update);

			return true;
		}

		api::callHooks(API_HOOK_AFTER,
			'settings::verifyAdmin', $this);

		return false;
	}

	function displayAdminItemTitle($settingstitle, $sectiontitle = null) {
		if (!$settingstitle)
			return;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'settings::displayAdminItemTitle', $this, $settingstitle, $sectiontitle);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'settings::displayAdminItemTitle', $this, $settingstitle, $sectiontitle, $handled);

			return $handled;
		}

		$exptitles = explode('_', $sectiontitle);

		foreach($exptitles as $exptitle) {
			if (preg_match('/^'.$sectiontitle.'/i', $settingstitle)) {
				$settingstitle = preg_replace('/^'.$sectiontitle.'/i', '', $settingstitle);
				break;
			}

			$sectiontitle = preg_replace('/_[^_]*?$/i', '', $sectiontitle);
		}

		echo __(trim(str_replace('_', ' ', $settingstitle)), $this->textsDomain);

		api::callHooks(API_HOOK_AFTER,
			'settings::displayAdminItemTitle', $this, $settingstitle, $sectiontitle);
	}

	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'settings::displayAdminTitle', $this, $ownertitle);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'settings::displayAdminTitle', $this, $ownertitle, $handled);

			return $handled;
		}

		admin::displayTitle(
			__('Settings Administration'),
			$ownertitle);

		api::callHooks(API_HOOK_AFTER,
			'settings::displayAdminTitle', $this, $ownertitle);
	}

	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'settings::displayAdminDescription', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'settings::displayAdminDescription', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'settings::displayAdminDescription', $this);
	}

	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'settings::displayAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'settings::displayAdmin', $this, $handled);

			return $handled;
		}

		$this->displayAdminTitle();
		$this->displayAdminDescription();

		echo
			"<div class='admin-content'>";

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$this->verifyAdmin();

		$rows = sql::run(
			" SELECT * FROM `{".$this->sqlTable."}`" .
			" WHERE `TypeID` = 0" .
			" ORDER BY `OrderID`, `ID`");

		if (JCORE_VERSION >= '0.6')
			echo
				"<div class='form rounded-corners'>";

		echo
			"<form action='".url::uri()."' method='post'>" .
				"<div class='form-title rounded-corners-top'>" .
					__("Change Settings") .
				"</div>" .
				"<div class='" .
					(JCORE_VERSION >= '0.6'?
						"form-content":
						"form") .
					" rounded-corners-bottom'>";

		$firstrow = null;

		while($row = sql::fetch($rows)) {
			if ($row['OrderID'] == 1) {
				echo
					"<b>";

				$this->displayAdminItemTitle($row['ID']);

				echo
					"</b>";

			}else {
				echo
					"<div tabindex='0' class='fc" .
						form::fcState('fcstgs'.$row['OrderID']) .
						"'>" .
						"<a class='fc-title' ".
							"name='fcstgs".$row['OrderID']."'>";

				$this->displayAdminItemTitle($row['ID'], $firstrow['ID']);

				echo
						"</a>" .
						"<div class='fc-content'>";
			}

			echo
				"<table width='100%'>";

			$settings = sql::run(
				" SELECT * FROM `{".$this->sqlTable."}`" .
				" WHERE `OrderID` = '".$row['OrderID']."'" .
				" AND `TypeID` > 0" .
				" AND `TypeID` != '".SETTINGS_TYPE_HIDDEN."'");

			while ($setting = sql::fetch($settings)) {
				$inputlength = strlen($setting['Value']);
				if ($inputlength > 50) $inputlength = 50;

				echo
					"<tr>" .
						"<td style='width: 1px; text-align: right; vertical-align: middle; padding: 0px 10px 5px 0px;'>" .
						"<label for='settings".$setting['ID']."' style='white-space: nowrap;' " .
							"title='".__(str_replace('_',' ', $setting['ID']))."'>";

				$this->displayAdminItemTitle($setting['ID'], $row['ID']);

				echo
						":</label>" .
						"</td>" .
						"<td class='auto-width' style='padding: 0px 10px 5px 0px;'>";

					if ($setting['TypeID'] == SETTINGS_TYPE_TEXTAREA) {
						echo
							"<textarea " .
								"name='settings[".$setting['ID']."]' style='width: " .
								(JCORE_VERSION >= '0.7'?
									'90%':
									'350px') .
									"; height: 200px;' " .
								(~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE?
									"readonly='readonly' ":
									null) .
								" id='settings".$setting['ID']."'>".
								htmlchars($setting['Value'], ENT_QUOTES) .
							"</textarea>";

					} elseif ($setting['TypeID'] == SETTINGS_TYPE_CHECKBOX) {
						echo
							"<input type='hidden' name='settings[".$setting['ID']."]' " .
								"value='".(int)$setting['Value']."' " .
								"id='hsettings".$setting['ID']."' />" .
							"<input type='checkbox' ".
								((int)$setting['Value']?
									"checked='checked' ":
									null).
							" onchange='if(this.checked) " .
								"document.getElementById(\"hsettings".$setting['ID']."\").value=\"1\"; " .
								"else " .
								"document.getElementById(\"hsettings".$setting['ID']."\").value=\"0\";' " .
							(~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE?
								"disabled='disabled' ":
								null) .
							"id='settings".$setting['ID']."' />";

					} elseif ($setting['TypeID'] == SETTINGS_TYPE_NUMBER) {
						echo
							"<input type='text' name='settings[".$setting['ID']."]' " .
								"value='".htmlchars($setting['Value'], ENT_QUOTES)."' " .
								"style='width: 50px;' " .
								(~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE?
									"readonly='readonly' ":
									null) .
								"id='settings".$setting['ID']."' />";

					} else {
						echo
							"<input type='" .
								($setting['TypeID'] == SETTINGS_TYPE_PASSWORD?
									"password":
									"text") .
								"' name='settings[".$setting['ID']."]' " .
								($setting['TypeID'] == SETTINGS_TYPE_COLOR?
									"class='color-input' ":
									null) .
								($setting['TypeID'] == SETTINGS_TYPE_DATE?
									"class='calendar-input' ":
									null) .
								($setting['TypeID'] == SETTINGS_TYPE_TIMESTAMP?
									"class='calendar-input timestamp' ":
									null) .
								"value='".htmlchars($setting['Value'], ENT_QUOTES)."' " .
								"size='".$inputlength."' " .
								(~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE?
									"readonly='readonly' ":
									null) .
								"id='settings".$setting['ID']."' />";
					}

					echo
						"</td>" .
					"</tr>";
			}

			echo
				"</table>";


			if ($row['OrderID'] != 1)
				echo
						"</div>" . //fc-content
					"</div>"; //fc
			else
				$firstrow = $row;
		}

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			echo
				"<input type='submit' name='submit' value='" .
					htmlchars(__("Submit"), ENT_QUOTES)."' class='button submit' /> " .
				"<input type='reset' name='reset' value='" .
					htmlchars(__("Reset"), ENT_QUOTES)."' class='button' />";

		echo
			"</div>" .
			"</form>";

		if (JCORE_VERSION >= '0.6')
			echo
				"</div>";

		echo
			"</div>";	//admin-content

		api::callHooks(API_HOOK_AFTER,
			'settings::displayAdmin', $this);
	}

	function add($id, $value, $type = SETTINGS_TYPE_TEXT) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'settings::add', $this, $id, $value, $type);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'settings::add', $this, $id, $value, $type, $handled);

			return $handled;
		}

		sql::run(
			" INSERT INTO `{".$this->sqlTable."}`" .
			" SET `ID` = '".sql::escape($id)."', " .
			" `Value` = '".sql::escape($value)."'," .
			" `TypeID` = '".(int)$type."'");

		$result = (sql::affected() != -1);

		api::callHooks(API_HOOK_AFTER,
			'settings::add', $this, $id, $value, $type, $result);

		return $result;
	}

	function edit($id, $value, $type = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'settings::edit', $this, $id, $value, $type);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'settings::edit', $this, $id, $value, $type, $handled);

			return $handled;
		}

		sql::run(
			" UPDATE `{".$this->sqlTable."}`" .
			" SET `Value` = '".sql::escape($value)."'" .
			($type?
				", `TypeID` = '".(int)$type."'":
				null) .
			" WHERE `ID` = '".sql::escape($id)."'");

		$result = (sql::affected() != -1);

		api::callHooks(API_HOOK_AFTER,
			'settings::edit', $this, $id, $value, $type, $result);

		return $result;
	}

	function delete($id) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'settings::delete', $this, $id);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'settings::delete', $this, $id, $handled);

			return $handled;
		}

		sql::run(
			" DELETE FROM `{".$this->sqlTable."}`" .
			" WHERE `ID` = '".sql::escape($id)."'");

		api::callHooks(API_HOOK_AFTER,
			'settings::delete', $this, $id);
	}

	// ************************************************   Client Part
	static function set($table, $id, $value = null) {
		if (!isset($value)) {
			$value = $id;
			$id = $table;
			$table = null;
		}

		if (!$table)
			$table = 'settings';

		sql::run(
			" UPDATE `{".$table."}`" .
			" SET `Value` = '".sql::escape($value)."'" .
			" WHERE `ID` = '".sql::escape($id)."'");

		return (sql::affected() != -1);
	}

	static function get($table, $id = null) {
		if (!isset($id)) {
			$id = $table;
			$table = null;
		}

		if (!$table)
			$table = 'settings';

		$row = sql::fetch(sql::run(
			" SELECT `Value` " .
			" FROM `{".$table."}`" .
			" WHERE `ID` = '".sql::escape($id)."'"));

		if ($row)
			return $row['Value'];

		return null;
	}

	static function getType($table, $id = null) {
		if (!isset($id)) {
			$id = $table;
			$table = null;
		}

		if (!$table)
			$table = 'settings';

		$row = sql::fetch(sql::run(
			" SELECT `TypeID` " .
			" FROM `{".$table."}`" .
			" WHERE `ID` = '".sql::escape($id)."'"));

		if ($row)
			return $row['TypeID'];

		return null;
	}

	static function iniGet($var, $parse = false) {
		if (!$var)
			return null;

		$value = ini_get($var);

		if ($parse && !is_numeric($value)) {
    		if (strpos($value, 'M') !== false)
        		$value = intval($value)*1024*1024;
    		elseif (strpos($value, 'K') !== false)
        		$value = intval($value)*1024;
    		elseif (strpos($value, 'G') !== false)
        		$value = intval($value)*1024*1024*1024;
		}

		return $value;
	}

	static function defineSettings($table = null) {
		if (!$table)
			$table = 'settings';

		$handled = api::callHooks(API_HOOK_BEFORE,
			'settings::defineSettings', $_ENV, $table);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'settings::defineSettings', $_ENV, $table, $handled);

			return $handled;
		}

		$rows = sql::run(
			" SELECT * FROM `{".$table."}`" .
			" WHERE `TypeID` > 0");

		while ($row = sql::fetch($rows))
			if (!defined(strtoupper($row['ID'])))
				define(strtoupper($row['ID']),
					(trim($row['Value'])?
						$row['Value']:
						''));

		// Definitions needed for compatiblity between jcore versions
		if (JCORE_VERSION <= '0.1' && !defined('AJAX_PAGING'))
			define('AJAX_PAGING', false);

		if (defined('MANUAL_GETTEXT') && MANUAL_GETTEXT && $table == 'settings')
			include_once('lib/gettext/gettext.inc');

		api::callHooks(API_HOOK_AFTER,
			'settings::defineSettings', $_ENV, $table);
	}

	static function displayMaintenanceNotification() {
		if (isset($GLOBALS['ADMIN']) && (bool)$GLOBALS['ADMIN'])
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'settings::displayMaintenanceNotification', $_ENV);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'settings::displayMaintenanceNotification', $_ENV, $handled);

			return $handled;
		}

		if (((defined('MAINTENANCE_SUSPEND_WEBSITE') && MAINTENANCE_SUSPEND_WEBSITE) ||
			(defined('MAINTENANCE_WEBSITE_SUSPENDED') && MAINTENANCE_WEBSITE_SUSPENDED)))
		{
			tooltip::display(
				strip_tags(preg_replace('/<title>.*?<\/title>/i', '',
					MAINTENANCE_SUSPEND_TEXT),
					'<a><b><i><p><h1><h2><h3><strong>'),
				TOOLTIP_NOTIFICATION);
		}

		if (defined('MAINTENANCE_NOTIFICATION_TEXT') &&
			MAINTENANCE_NOTIFICATION_TEXT)
		{
			tooltip::display(
				MAINTENANCE_NOTIFICATION_TEXT,
				TOOLTIP_NOTIFICATION);
		}

		api::callHooks(API_HOOK_AFTER,
			'settings::displayMaintenanceNotification', $_ENV);
	}
}

?>