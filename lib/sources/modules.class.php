<?php

/***************************************************************************
 *            modules.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/security.class.php');
include_once('lib/email.class.php');
include_once('lib/patch.class.php');
include_once('lib/dynamicforms.class.php');
include_once('lib/pictures.class.php');
include_once('lib/comments.class.php');
include_once('lib/starrating.class.php');
include_once('lib/files.class.php');
include_once('lib/dirs.class.php');

class _modules {
	static $available = array();
	static $loaded = array();
	
	var $owner;
	var $arguments = null;
	var $sqlTable;
	var $sqlRow;
	var $sqlOwnerTable;
	var $selectedID;
	var $selectedOwner;
	var $selectedOwnerID;
	var $searchable = false;
	
	function SQL() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'modules::SQL', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'modules::SQL', $this, $handled);
			
			return $handled;
		}
		
		$sql =
			" SELECT * FROM `{".$this->sqlTable."}`, `{modules}` " .
			" WHERE `".$this->sqlRow."` = '".$this->selectedOwnerID."'" .
			" AND `ModuleID` = `ID`" .
			" AND `Installed` = 1" .
			(JCORE_VERSION >= '0.9'?
				" AND `Deactivated` = 0":
				null) .
			" ORDER BY `Name`";
		
		api::callHooks(API_HOOK_AFTER,
			'modules::SQL', $this, $sql);
		
		return $sql;
	}
	
	function install() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'modules::install', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'modules::install', $this, $handled);
			
			return $handled;
		}
		
		if (!isset($this->moduleID) || !$this->moduleID) {
			$module = ucfirst(get_class($this));
			$exists = modules::get($module);
			
			if ($exists)
				$this->moduleID = $exists['ID'];
			else
				$this->moduleID = sql::run(
					" INSERT INTO `{modules}` SET " .
					" `Name` = '".sql::escape($module)."'," .
					(JCORE_VERSION >= '0.5' && $this->searchable?
						" `Searchable` = 1,":
						null) .
					(JCORE_VERSION >= '0.9'?
						" `Deactivated` = 0,":
						null) .
					" `Installed` = 0");
			
			if (sql::error()) {
				api::callHooks(API_HOOK_AFTER,
					'modules::install', $this);
				
				return false;
			}
		}
		
		files::$debug = true;
		sql::$debug = true;
		
		ob_start();
		
		$obcontent = null;
		$successfiles = $this->installFiles();
		$obcontent = ob_get_contents();
		
		ob_end_clean();
		
		$this->displayInstallResults(
			__("Writing files"),
			$obcontent,
			$successfiles);
		
		ob_start();
		
		$obcontent = null;
		$successsql = $this->installSQL();
		$successcustom = $this->installCustom();
		$obcontent = ob_get_contents();
		
		ob_end_clean();
		
		$this->displayInstallResults(
			__("Running SQL Queries"),
			$obcontent,
			$successsql);
		
		files::$debug = false;
		sql::$debug = false;
		
		if (!$successfiles || !$successsql || !$successcustom) {
			tooltip::display(
				__("Module couldn't be installed!")." " .
				__("Please see detailed error messages above and try again."),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'modules::install', $this);
			
			return false;
		}
		
		if (!$csssuccess = css::update())
			tooltip::display(
				__("Couldn't update template.css file.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"template/template.css"),
				TOOLTIP_ERROR);
		
		if (!$jssuccess = jQuery::update())
			tooltip::display(
				__("Couldn't update template.js file.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"template/template.js"),
				TOOLTIP_ERROR);
		
		if (!$csssuccess || !$jssuccess) {
			api::callHooks(API_HOOK_AFTER,
				'modules::install', $this);
			
			return false;
		}
		
		sql::run(
			" UPDATE `{modules}` SET " .
			(JCORE_VERSION >= '0.5' && $this->searchable?
				" `Searchable` = 1,":
				null) .
			(JCORE_VERSION >= '0.9'?
				" `Deactivated` = 0,":
				null) .
			" `Installed` = 1" .
			" WHERE `ID` = '".$this->moduleID."'");
		
		$result = !sql::error();
		
		api::callHooks(API_HOOK_AFTER,
			'modules::install', $this, $result);
		
		return $result;
	}
	
	function uninstall() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'modules::uninstall', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'modules::uninstall', $this, $handled);
			
			return $handled;
		}
		
		if (!isset($this->moduleID) || !$this->moduleID) {
			$module = ucfirst(get_class($this));
			
			if ($exists = modules::get($module))
				$this->moduleID = $exists['ID'];
		}
		
		files::$debug = true;
		sql::$debug = true;
		
		ob_start();
		
		$obcontent = null;
		$this->uninstallFiles();
		$obcontent = ob_get_contents();
		
		ob_end_clean();
		
		$this->displayInstallResults(
			__("Deleting files"),
			$obcontent,
			null);
		
		ob_start();
		
		$obcontent = null;
		$this->uninstallSQL();
		$this->uninstallCustom();
		$obcontent = ob_get_contents();
		
		ob_end_clean();
		
		$this->displayInstallResults(
			__("Running SQL Queries"),
			$obcontent,
			null);
		
		files::$debug = false;
		sql::$debug = false;
		
		css::update();
		jQuery::update();
		
		sql::run(
			" UPDATE `{modules}` SET " .
			" `Installed` = 0" .
			" WHERE `ID` = '".$this->moduleID."'");
		
		$result = !sql::error();
		
		api::callHooks(API_HOOK_AFTER,
			'modules::uninstall', $this, $result);
		
		return $result;
	}
	
	// Class should be overwritten from module's own class
	function installSQL() {
		echo "<p>".__("No SQL queries to run.")."</p>";
			
		return true;
	}
	
	// Class should be overwritten from module's own class
	function installFiles() {
		echo "<p>".__("No files to install.")."</p>";
		
		return true;
	}
	
	// Class should be overwritten from module's own class
	function installCustom() {
		return true;
	}
	
	function installjQueryPlugins($plugins = null) {
		if (!isset($this->moduleID) || !$this->moduleID) {
			$module = ucfirst(get_class($this));
			
			if ($exists = modules::get($module))
				$this->moduleID = $exists['ID'];
		}
		
		sql::run(
			" UPDATE `{modules}`" .
			" SET `jQueryPlugins` = '".sql::escape($plugins)."'" .
			" WHERE `ID` = '".$this->moduleID."'");
		
		return true;
	}
	
	// Class should be overwritten from module's own class
	function uninstallSQL() {
		echo "<p>".__("No SQL queries to run.")."</p>";
		
		return true;
	}
	
	// Class should be overwritten from module's own class
	function uninstallFiles() {
		echo "<p>".__("No files to uninstall.")."</p>";
		
		return true;
	}
	
	// Class should be overwritten from module's own class
	function uninstallCustom() {
		return true;
	}
	
	// ************************************************   Admin Part
	function displayInstallResults($title, $results, $success = false) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'modules::displayInstallResults', $this, $title, $results, $success);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'modules::displayInstallResults', $this, $title, $results, $success, $handled);
			
			return $handled;
		}
		
		echo
			"<div tabindex='0' class='fc" .
				(isset($success) && !$success?
					" expanded":
					null) .
				"'>" .
				"<a class='fc-title'>" .
				(isset($success)?
					($success?
						" <span class='align-right'>[".strtoupper(__("Success"))."]</span>":
						" <span class='align-right'>[".strtoupper(__("Error"))."]</span>"):
					null) .
				$title .
				"</a>" .
				"<div class='fc-content'>" .
					$results .
				"</div>" .
			"</div>";
		
		api::callHooks(API_HOOK_AFTER,
			'modules::displayInstallResults', $this, $title, $results, $success);
	}
	
	function displayInstallNotification() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'modules::displayInstallNotification', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'modules::displayInstallNotification', $this, $handled);
			
			return $handled;
		}
		
		tooltip::display(
			__("This module needs to be installed before it can be used."),
			TOOLTIP_NOTIFICATION);
		
		api::callHooks(API_HOOK_AFTER,
			'modules::displayInstallNotification', $this);
	}
	
	function displayInstallFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'modules::displayInstallFunctions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'modules::displayInstallFunctions', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<input type='submit' name='submit' value='" .
				htmlspecialchars(__("Install Module"), ENT_QUOTES) .
				"' class='button submit' />";
		
		api::callHooks(API_HOOK_AFTER,
			'modules::displayInstallFunctions', $this);
	}
	
	function displayInstallTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'modules::displayInstallTitle', $this, $ownertitle);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'modules::displayInstallTitle', $this, $ownertitle, $handled);
			
			return $handled;
		}
		
		admin::displayTitle(
			__('Module Installation'), 
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'modules::displayInstallTitle', $this, $ownertitle);
	}
	
	function displayInstallDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'modules::displayInstallDescription', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'modules::displayInstallDescription', $this, $handled);
			
			return $handled;
		}
		
		echo 
			"<p>" .
				modules::getDescription(ucfirst(get_class($this))) .
			"</p>";
		
		api::callHooks(API_HOOK_AFTER,
			'modules::displayInstallDescription', $this);
	}
	
	function displayInstall() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'modules::displayInstall', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'modules::displayInstall', $this, $handled);
			
			return $handled;
		}
		
		$install = null;
		
		if (isset($_POST['install']))
			$install = (int)$_POST['install'];
		
		$this->displayInstallTitle(modules::getTitle(ucfirst(get_class($this))));
		$this->displayInstallDescription();
		
		echo
			"<div class='admin-content'>";
			
		if ($install && $this->install()) {
			tooltip::display(
				__("Module has been successfully installed.")." " .
				"<a href='".url::uri()."'>" .
					__("View Module") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			echo "</div>"; //admin-content
			
			api::callHooks(API_HOOK_AFTER,
				'modules::displayInstall', $this);
			
			return true;
		}
		
		if (!$install)
			$this->displayInstallNotification();
		
		echo
			"<form action='".url::uri()."' id='moduleinstallform' method='post'>" .
			"<input type='hidden' name='install' value='1' />";
		
		$this->displayInstallFunctions();
		
		echo
			"</form>" .
			"<div class='clear-both'></div>";
			
		echo 
			"</div>"; //admin-content
		
		api::callHooks(API_HOOK_AFTER,
			'modules::displayInstall', $this);
	}
	
	function displayAdmin() {
		if ($this->installed(get_class($this)))
			return false;
			
		$handled = api::callHooks(API_HOOK_BEFORE,
			'modules::displayAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'modules::displayAdmin', $this, $handled);
			
			return $handled;
		}
		
		$this->displayInstall();
		
		api::callHooks(API_HOOK_AFTER,
			'modules::displayAdmin', $this);
		
		return true;
	}
	
	static function loadAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'modules::loadAdmin', $_ENV);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'modules::loadAdmin', $_ENV, $handled);
			
			return $handled;
		}
		
		foreach(modules::$loaded as $id => $details) {
			admin::add('Modules', $id, 
				"<a href='".url::uri('ALL')."?path=admin/modules/".strtolower($id)."' " .
					"title='".htmlspecialchars($details['Description'], ENT_QUOTES)."'" .
					(isset($details['Icon']) && $details['Icon']?
						" style=\"background-image: url('".$details['Icon']."');\"":
						null) .
					">" .
					"<span>".$details['Title']."</span>" .
				"</a>");
		}
		
		api::callHooks(API_HOOK_AFTER,
			'modules::loadAdmin', $_ENV);
	}
	
	// ************************************************   Client Part
	static function loadModules() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'modules::loadModules', $_ENV);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'modules::loadModules', $_ENV, $handled);
			
			return $handled;
		}
		
		$rows = sql::run(
			" SELECT `Name` FROM `{modules}`" .
			" WHERE 1" .
			(JCORE_VERSION >= '0.3'?
				" AND `Installed` = 1":
				null) .
			(JCORE_VERSION >= '0.9'?
				" AND `Deactivated` = 0":
				null) .
			" ORDER BY `Name`");
		
		while($row = sql::fetch($rows))
			modules::load($row['Name']);
		
		api::callHooks(API_HOOK_AFTER,
			'modules::loadModules', $_ENV);
		
		return true;
	}
	
	static function load($module) {
		if (!$module)
			return false;
		
		$module = strtolower($module);
		
		if (isset(modules::$loaded[$module]))
			return modules::$loaded[$module];
		
		modules::$loaded[$module] = false;
		
		if (!isset(modules::$available[$module]))
			modules::$available[$module] = array(
				'Title' =>
					ucwords(preg_replace('/-|_/', ' ', $module)),
				'Description' => '',
				'Icon' => '');
		
		$moduleclass = preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '', $module);
		
		if (@is_dir(SITE_PATH.'lib/modules/'.$moduleclass) || (defined('JCORE_PATH') &&
			JCORE_PATH && @is_dir(JCORE_PATH.'lib/modules/'.$moduleclass)))
			include_once('lib/modules/'.$moduleclass.'/'.$moduleclass.'.class.php');
		else
			include_once('lib/modules/'.$moduleclass.'.class.php');
		
		if (!class_exists($module))
			return false;
			
		modules::$loaded[$module] = modules::$available[$module];
		return true;
	}
	
	static function register($id, $title, $description = null, $icon = null) {
		if (!$id)
			return;
		
		if (!$description) {
			$description = $title;
			$title = $id;
		}
		
		$modulename = strtolower($id);
		
		if (class_exists($modulename))
			$$modulename = new $modulename();
		
		if ($icon && stripos($icon, 'http://') === false) {
			if (stripos($icon, '/') === false)
				$icon = url::jCore().'lib/icons/48/'.$icon;
			else
				$icon = url::site().$icon;
		}
		
		modules::$available[$modulename] = array(
			'Title' => _($title),
			'Description' => _($description),
			'Icon' => $icon);
		
		if (class_exists($modulename))
			unset($$modulename);
	}
	
	static function get($name = null) {
		if ($name)
			return sql::fetch(sql::run(
				" SELECT * FROM `{modules}`" .
				" WHERE `Name` LIKE '".sql::escape($name)."'" .
				" LIMIT 1"));
		
		return sql::run(
			" SELECT * FROM `{modules}`" .
			" WHERE `Installed` = 1" .
			(JCORE_VERSION >= '0.9'?
				" AND `Deactivated` = 0":
				null) .
			" ORDER BY `Name`");
	}
	
	static function installed($id = null) {
		if (!$id)
			return false;
			
		if (is_object($id))
			$id = strtolower(get_class($id));
		
		return sql::fetch(sql::run(
			" SELECT `ID` FROM `{modules}`" .
			" WHERE `Name` LIKE '".sql::escape($id)."'" .
			" AND `Installed` = 1" .
			(JCORE_VERSION >= '0.9'?
				" AND `Deactivated` = 0":
				null)));
	}
	
	static function getTitle($id = null) {
		$id = strtolower($id);
		
		if (!$id || !isset(modules::$available[$id]))
			return false;
		
		return modules::$available[$id]['Title'];
	}
	
	static function getDescription($id = null) {
		$id = strtolower($id);
		
		if (!$id || !isset(modules::$available[$id]))
			return false;
		
		return modules::$available[$id]['Description'];
	}
	
	static function getIcon($id = null) {
		$id = strtolower($id);
		
		if (!$id || !isset(modules::$available[$id]))
			return false;
		
		return modules::$available[$id]['Icon'];
	}
	
	static function getOwnerMenu($name, $languageid = 0, $moduleitemid = 0) {
		return modules::getOwnerPage($name, $languageid, $moduleitemid);
	}
	
	static function getOwnerPage($name, $languageid = 0, $moduleitemid = 0) {
		if (!$name)
			return false;
		
		$module = modules::get($name);
		
		if (!$module)
			return false;
		
		$modulepages = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(`".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID') . 
				"` SEPARATOR ',') AS `PageIDs` " .
			" FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pagemodules':
					'menuitemmodules') .
				"}`" .
			" WHERE `ModuleID` = '".$module['ID']."'" .
			($moduleitemid && JCORE_VERSION >= '0.3'?
				" AND (`ModuleItemID` = '".(int)$moduleitemid."' OR `ModuleItemID` = 0)" .
					" ORDER BY `ModuleItemID` DESC," .
					" `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."`":
				" ORDER BY `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."`") .
			" LIMIT 1"));
			
		if (!$modulepages['PageIDs'])
			return false;
			
		return sql::fetch(sql::run(
			" SELECT * FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}`" .
			" WHERE `ID` IN (".$modulepages['PageIDs'].")" .
			" AND `LanguageID` = ".(int)$languageid .
			" ORDER BY " .
				(JCORE_VERSION < '0.9'?
					" `MenuID`,":
					null) .
				" `OrderID`" .
			" LIMIT 1"));
	}
	
	static function getOwnerURL($name, $moduleitemid = 0, $languageid = 0) {
		if (!$languageid)
			$languageid = (int)$_GET['languageid'];
		
		$page = modules::getOwnerPage($name, $languageid, $moduleitemid);
		
		if (!$page)
			return false;
			
		if ($page['LanguageID'])	
			$selectedlanguage = sql::fetch(sql::run(
				" SELECT * FROM `{languages}` " .
				" WHERE `ID` = '".$page['LanguageID']."'"));
			
		if (SEO_FRIENDLY_LINKS)
			return url::site().
				(isset($selectedlanguage)?
					$selectedlanguage['Path'].'/':
					null) .
				$page['Path'].'?';
		
		return url::site().'index.php?' .
			(isset($selectedlanguage)?
				'&amp;languageid='.$selectedlanguage['ID']:
				null) .
			'&amp;pageid='.$page['ID'];
	}
	
	static function count() {
		return sql::count(
			" SELECT COUNT(*) AS `Rows` " .
			" FROM `{modules}`" .
			" WHERE `Installed` = 1" .
			(JCORE_VERSION >= '0.9'?
				" AND `Deactivated` = 0":
				null));
	}
	
	static function displayCSSLinks() {
		if (JCORE_VERSION <= '0.2')
			return false;
			
		$modules = sql::run(
			" SELECT `Name` FROM `{modules}`" .
			" WHERE `Installed` = 1" .
			(JCORE_VERSION >= '0.9'?
				" AND `Deactivated` = 0":
				null));
			
		while($module = sql::fetch($modules)) {
			if (@is_file(SITE_PATH.'template/modules/css/'.
				strtolower($module['Name']).'.css'))
				echo 
					"<link rel='stylesheet' href='".
						url::site()."template/modules/css/".
						strtolower($module['Name']).".css?revision=".
						JCORE_VERSION .
						"' type='text/css' />\n";
		}
		
		return true;
	}
	
	function display() {
		if (!$this->sqlTable)
			return false;
		
		$rows = sql::run(
			$this->SQL());
		
		if (!sql::rows($rows))
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'modules::display', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'modules::display', $this, $handled);
			
			return $handled;
		}
		
		$display = false;
		$owner = sql::fetch(sql::run(
			" SELECT * FROM `{".$this->sqlOwnerTable. "}`" .
			" WHERE `ID` = '".$this->selectedOwnerID."'"));
		
		while($row = sql::fetch($rows)) {
			$modulename = preg_replace('/[^a-zA-Z0-9\_\-]/', '', 
				$row['Name']);
			
			if ($modulename && $this->load($modulename)) {
				$$modulename = new $modulename();
				$$modulename->owner = $owner;
				
				if (isset($row['ModuleItemID'])) {
					if ($row['ModuleItemID'] < 0) {
						$$modulename->setOption($row['ModuleItemID']);
						
					} elseif (!$$modulename->selectedID) {
						$$modulename->selectedID = $row['ModuleItemID'];
					}
				}
				
				if ($$modulename->display())
					$display = true;
				
				unset($$modulename);
			}
		}
		
		api::callHooks(API_HOOK_AFTER,
			'modules::display', $this, $display);
		
		return $display;
	}
}

?>