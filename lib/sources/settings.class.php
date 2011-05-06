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

include_once('lib/sql.class.php');
 
class _settings {
	var $adminPath = 'admin/site/settings';
	var $sqlTable = 'settings';
	var $textsDomain = 'messages';
	
	function __construct($table = null) {
		if ($table)
			$this->sqlTable = $table;
		
		if (class_exists('languages'))
			$this->textsDomain = languages::$selectedTextsDomain;
	}
	
	// ************************************************   Admin Part
	function verifyAdmin() {
		$update = null;
		$settings = null;
		
		if (isset($_POST['submit']))
			$update = $_POST['submit'];
			
		if (isset($_POST['settings']))
			$settings = (array)$_POST['settings'];
		
		if ($update) {
			foreach($settings as $sid => $svalue) {
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
				__("Settings have been successfully updated."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		return false;
	}
	
	function displayAdminItemTitle($settingstitle, $sectiontitle = null) {
		if (!$settingstitle)
			return;
		
		$exptitles = explode('_', $sectiontitle);
		
		foreach($exptitles as $exptitle) {
			if (preg_match('/^'.$sectiontitle.'/i', $settingstitle)) {
				$settingstitle = preg_replace('/^'.$sectiontitle.'/i', '', $settingstitle);
				break;
			}
			
			$sectiontitle = preg_replace('/_[^_]*?$/i', '', $sectiontitle);
		}
		
		echo __(trim(str_replace('_', ' ', $settingstitle)), $this->textsDomain);
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Settings Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			$this->verifyAdmin();
		
		$rows = sql::run(
			" SELECT * FROM `{".$this->sqlTable."}`" .
			" WHERE !`TypeID`" .
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
				" AND `TypeID`" .
				" AND `TypeID` != '".SETTINGS_TYPE_HIDDEN."'");
			
			while ($setting = sql::fetch($settings)) {	
				$inputlength = strlen($setting['Value']);
				if ($inputlength > 50) $inputlength = 50;
				
				echo
					"<tr>" .
						"<td style='width: 1px; text-align: right; vertical-align: middle; padding: 0px 10px 5px 0px;'>" .
						"<label for='settings".$setting['ID']."' style='white-space: nowrap;'>";
				
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
								($this->userPermissionType != USER_PERMISSION_TYPE_WRITE?
									"readonly='readonly' ":
									null) .
								" id='settings".$setting['ID']."'>".
								htmlspecialchars($setting['Value'], ENT_QUOTES) .
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
							($this->userPermissionType != USER_PERMISSION_TYPE_WRITE?
								"disabled='disabled' ":
								null) .
							"id='settings".$setting['ID']."' />";
				
					} elseif ($setting['TypeID'] == SETTINGS_TYPE_NUMBER) {
						echo 
							"<input type='text' name='settings[".$setting['ID']."]' " .
								"value='".htmlspecialchars($setting['Value'], ENT_QUOTES)."' " .
								"style='width: 50px;' " .
								($this->userPermissionType != USER_PERMISSION_TYPE_WRITE?
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
								"value='".htmlspecialchars($setting['Value'], ENT_QUOTES)."' " .
								"size='".$inputlength."' " .
								($this->userPermissionType != USER_PERMISSION_TYPE_WRITE?
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
	
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			echo
				"<input type='submit' name='submit' value='" .
					htmlspecialchars(__("Submit"), ENT_QUOTES)."' class='button submit' /> " .
				"<input type='reset' name='reset' value='" .
					htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
				
		echo
			"</div>" .
			"</form>";
			
		if (JCORE_VERSION >= '0.6')
			echo
				"</div>";
		
		echo
			"</div>";	//admin-content
	}
	
	function add($id, $value, $type = SETTINGS_TYPE_TEXT) {
		sql::run(
			" INSERT INTO `{".$this->sqlTable."}`" .
			" SET `ID` = '".sql::escape($id)."', " .
			" `Value` = '".sql::escape($value)."'," .
			" `TypeID` = '".(int)$type."'");
	}
	
	function edit($id, $value, $type = null) {
		sql::run(
			" UPDATE `{".$this->sqlTable."}`" .
			" SET `Value` = '".sql::escape($value)."'" .
			($type?
				", `TypeID` = '".(int)$type."'":
				null) .
			" WHERE `ID` = '".sql::escape($id)."'");
	}
	
	function set($id, $value) {
		return $this->edit($id, $value);
	}
	
	function delete($id) {
		sql::run(
			" DELETE FROM `{".$this->sqlTable."}`" .
			" WHERE `ID` = '".sql::escape($id)."'");
	}
	
	// ************************************************   Client Part
	function get($id) {
		$row = sql::fetch(sql::run(
			" SELECT `Value` " .
			" FROM `{".$this->sqlTable."}`" .
			" WHERE `ID` = '".sql::escape($id)."'"));
		
		if (!$row)
			return null;
		
		return $row['Value'];
	}
	
	static function iniGet($var, $parse = false) {
		if (!$var)
			return null;
		
		$value = ini_get($var);
		
		if (!$parse)
			return $value;
		
		if (!is_numeric($value)) {
    		if (strpos($value, 'M') !== false)
        		$value = intval($value)*1024*1024;
    		elseif (strpos($value, 'K') !== false)
        		$value = intval($value)*1024;
    		elseif (strpos($value, 'G') !== false)
        		$value = intval($value)*1024*1024*1024;
		}
		
		return $value;
	}
	
	function defineSettings() {
		$rows = sql::run(
			" SELECT * FROM `{".$this->sqlTable."}`" .
			" WHERE `TypeID`");
			
		if (!$rows)
			return false;
		
		while ($row = sql::fetch($rows))
			if (!defined(strtoupper($row['ID'])))
				define(strtoupper($row['ID']), 
					(trim($row['Value'])?
						$row['Value']:
						''));
		
		// Definitions needed for compatiblity between jcore versions
		if (JCORE_VERSION <= '0.1' && !defined('AJAX_PAGING'))
			define('AJAX_PAGING', false);
		
		return true;
	}
	
	static function displayMaintenanceNotification() {
		if (isset($GLOBALS['ADMIN']) && $GLOBALS['ADMIN'])
			return false;
		
		if (((defined('MAINTENANCE_SUSPEND_WEBSITE') && MAINTENANCE_SUSPEND_WEBSITE) ||
			(defined('MAINTENANCE_WEBSITE_SUSPENDED') && MAINTENANCE_WEBSITE_SUSPENDED)))
		{
			tooltip::display(
				strip_tags(preg_replace('/<title>.*?<\/title>/i', '', 
					MAINTENANCE_SUSPEND_TEXT), 
					'<a><b><i><p><h1><h2><h3><strong>'),
				TOOLTIP_NOTIFICATION);
		}
		
		if (!defined('MAINTENANCE_NOTIFICATION_TEXT') || 
			!MAINTENANCE_NOTIFICATION_TEXT)
			return false;
		
		tooltip::display(
			MAINTENANCE_NOTIFICATION_TEXT,
			TOOLTIP_NOTIFICATION);
		return true;
	}
}

?>