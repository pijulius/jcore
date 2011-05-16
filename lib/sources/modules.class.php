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
		return
			" SELECT * FROM `{".$this->sqlTable."}`, `{modules}` " .
			" WHERE `".$this->sqlRow."` = '".$this->selectedOwnerID."'" .
			" AND `ModuleID` = `ID`" .
			" AND `Installed`" .
			(JCORE_VERSION >= '0.9'?
				" AND !`Deactivated`":
				null) .
			" ORDER BY `Name`";
	}
	
	function install() {
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
			
			return false;
		}
		
		$module = ucfirst(get_class($this));
		
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
		
		if (!$csssuccess || !$jssuccess)
			return false;
		
		$exists = modules::get($module);
		
		if ($exists)
			sql::run(
				" UPDATE `{modules}` SET " .
				(JCORE_VERSION >= '0.5' && $this->searchable?
					" `Searchable` = 1,":
					null) .
				(JCORE_VERSION >= '0.9'?
					" `Deactivated` = 0,":
					null) .
				" `Installed` = 1" .
				" WHERE `ID` = '".$exists['ID']."'");
		else
			sql::run(
				" INSERT INTO `{modules}` SET " .
				" `Name` = '".sql::escape($module)."'," .
				(JCORE_VERSION >= '0.5' && $this->searchable?
					" `Searchable` = 1,":
					null) .
				(JCORE_VERSION >= '0.9'?
					" `Deactivated` = 0,":
					null) .
				" `Installed` = 1");
		
		if (sql::error())
			return false;
		
		return true;
	}
	
	function uninstall() {
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
		
		$module = ucfirst(get_class($this));
		
		css::update();
		jQuery::update();
		
		$exists = modules::get($module);
		
		if (sql::error())
			return false;
		
		if (!$exists)
			return true;
		
		sql::run(
			" DELETE FROM `{modules}` " .
			" WHERE `ID` = '".$exists['ID']."'");
		
		if (sql::error())
			return false;
		
		sql::run(
			" DELETE FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pagemodules':
					'menuitemmodules') .
				"}` " .
			" WHERE `ModuleID` = '".$exists['ID']."'");
		
		if (sql::error())
			return false;
		
		return true;
	}
	
	function installSQL() {
		echo "<p>".__("No SQL queries to run.")."</p>";
			
		return true;
	}
	
	function installFiles() {
		echo "<p>".__("No files to install.")."</p>";
		
		return true;
	}
	
	function installCustom() {
		return true;
	}
	
	function uninstallSQL() {
		echo "<p>".__("No SQL queries to run.")."</p>";
		
		return true;
	}
	
	function uninstallFiles() {
		echo "<p>".__("No files to uninstall.")."</p>";
		
		return true;
	}
	
	function uninstallCustom() {
		return true;
	}
	
	// ************************************************   Admin Part
	function displayInstallResults($title, $results, $success = false) {
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
	}
	
	function displayInstallNotification() {
		tooltip::display(
			__("This module needs to be installed before it can be used."),
			TOOLTIP_NOTIFICATION);
	}
	
	function displayInstallFunctions() {
		echo
			"<input type='submit' name='submit' value='" .
				htmlspecialchars(__("Install Module"), ENT_QUOTES) .
				"' class='button submit' />";
	}
	
	function displayInstallTitle($ownertitle = null) {
		admin::displayTitle(
			__('Module Installation'), 
			$ownertitle);
	}
	
	function displayInstallDescription() {
		echo 
			"<p>" .
				modules::getDescription(ucfirst(get_class($this))) .
			"</p>";
	}
	
	function displayInstall() {
		$install = null;
		
		if (isset($_POST['install']))
			$install = $_POST['install'];
		
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
	}
	
	function displayAdmin() {
		if ($this->installed(get_class($this)))
			return false;
			
		$this->displayInstall();
		return true;
	}
	
	static function loadAdmin() {
		modules::loadModules((JCORE_VERSION < '0.3'?true:false));
		ksort(modules::$available);
		
		foreach(modules::$available as $id => $details) {
			if (JCORE_VERSION >= '0.9' && 
				(!isset(modules::$loaded[$id]) || !modules::$loaded[$id]))
				continue;
			
			admin::add('Modules', $id, 
				"<a href='".url::uri('ALL')."?path=admin/modules/".strtolower($id)."' " .
					"title='".htmlspecialchars($details['Description'], ENT_QUOTES)."'>" .
					"<span>".$details['Title']."</span>" .
				"</a>");
		}
	}
	
	// ************************************************   Client Part
	static function loadModules($skipinstalledcheck = false) {
		$rows = sql::run(
			" SELECT * FROM `{modules}`" .
			" WHERE 1" .
			(!$skipinstalledcheck?
				" AND `Installed`":
				null) .
			(JCORE_VERSION >= '0.9'?
				" AND !`Deactivated`":
				null) .
			" ORDER BY `Name`");
			
		while($row = sql::fetch($rows)) {
			modules::load(strtolower($row['Name']), false, $skipinstalledcheck);
		}
		
		if (!$skipinstalledcheck)
			return true;
		
		if (!is_dir(SITE_PATH.'lib/modules'))
			return false;
		
		if (!$dh = opendir(SITE_PATH.'lib/modules'))
			return false;
		
		while (($file = readdir($dh)) !== false) {
			if (strpos($file, '.') === 0)
				continue;
			
			if (is_file(SITE_PATH.'lib/modules/'.$file)) {
				preg_match('/(.*)\.class\.php$/', $file, $matches);
				
				if (isset($matches[1]) && $matches[1])
					modules::load($matches[1], false, $skipinstalledcheck);
				
				continue;
			}
			
			if (is_dir(SITE_PATH.'lib/modules/'.$file) &&
				is_file(SITE_PATH.'lib/modules/'.$file.'/'.$file.'.class.php'))
			{
				modules::load($file, false, $skipinstalledcheck);
				continue;
			}
		}
		
		closedir($dh);
		return true;
	}
	
	static function load($module, $quiet = false, $skipinstalledcheck = false) {
		if (!$module)
			return false;
		
		$module = strtolower(preg_replace(
					'/[^a-zA-Z0-9\@\.\_\-]/', '', $module));
		
		if (isset(modules::$loaded[$module]) && (!$skipinstalledcheck || modules::$loaded[$module]))
			return modules::$loaded[$module];
		
		modules::$loaded[$module] = false;
		
		if (@is_dir(SITE_PATH.'lib/modules/'.$module))
			include_once('lib/modules/'.$module.'/'.$module.'.class.php');
		else
			include_once('lib/modules/'.$module.'.class.php');
		
		if (!class_exists($module))
			return false;
			
		if (!$skipinstalledcheck) {
			$exists = modules::get($module);
			
			if (!$exists || !$exists['Installed'] || 
				(JCORE_VERSION >= '0.9' && $exists['Deactivated']))
				return false;
		}
		
		modules::$loaded[$module] = true;
		
		if ($quiet)
			return true;
		
		if (JCORE_VERSION <= '0.2')
			if (@is_file(SITE_PATH.'template/modules/css/'.$module.'.css'))
				echo 
					"<link rel='stylesheet' href='".
					url::site()."template/modules/css/".$module.".css?revision=".
					JCORE_VERSION .
					"' type='text/css' />\n";
		
		if (JCORE_VERSION <= '0.4')
			if (@is_file(SITE_PATH.'template/modules/js/'.$module.'.js'))
				echo 
					"<script src='".
						url::site()."template/modules/js/".$module.".js?revision=".
						JCORE_VERSION .
						"' type='text/javascript' language='Javascript'></script>\n";
		
		return true;
	}
	
	static function register($id, $title, $description = null) {
		if (!$id)
			return;
		
		if (!$description) {
			$description = $title;
			$title = $id;
		}
		
		$modulename = strtolower($id);
		
		if (isset(modules::$available[$modulename]))
			exit($id." module couldn't be registered as it's " .
				"id is already used by another module!");
		
		if (class_exists($modulename))
			$$modulename = new $modulename();
		
		modules::$available[strtolower($id)] = array(
			'Title' => _($title),
			'Description' => _($description));
		
		if (class_exists($modulename))
			unset($$modulename);
	}
	
	static function get($id = null) {
		if ($id)
			return sql::fetch(sql::run(
				" SELECT * FROM `{modules}`" .
				" WHERE `Name` LIKE '".sql::escape($id)."'" .
				" LIMIT 1"));
		
		return sql::run(
			" SELECT * FROM `{modules}`" .
			" WHERE `Installed`" .
			(JCORE_VERSION >= '0.9'?
				" AND !`Deactivated`":
				null) .
			" ORDER BY `Name`");
	}
	
	static function installed($id = null) {
		if (!$id)
			return false;
			
		if (is_object($id))
			$id = strtolower(get_class($id));
		
		$installed = sql::fetch(sql::run(
			" SELECT `ID` FROM `{modules}`" .
			" WHERE `Name` LIKE '".sql::escape($id)."'" .
			" AND `Installed`"));
		
		if ($installed)
			return true;
			
		return false;
	}
	
	static function getTitle($id = null) {
		if (!$id || !isset(modules::$available[strtolower($id)]))
			return false;
		
		return modules::$available[strtolower($id)]['Title'];
	}
	
	static function getDescription($id = null) {
		if (!$id || !isset(modules::$available[strtolower($id)]))
			return false;
		
		return modules::$available[strtolower($id)]['Description'];
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
				" AND (`ModuleItemID` = '".(int)$moduleitemid."' OR !`ModuleItemID`)" .
					" ORDER BY `ModuleItemID` DESC," .
					" `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."`":
				" ORDER BY `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."`") .
			" LIMIT 1"));
			
		if (!$modulepages['PageIDs'])
			return false;
			
		$page = sql::fetch(sql::run(
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
		
		if (!$page)
			return false;
			
		return $page;
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
			" WHERE `Installed`" .
			(JCORE_VERSION >= '0.9'?
				" AND !`Deactivated`":
				null));
	}
	
	static function displayCSSLinks() {
		if (JCORE_VERSION <= '0.2')
			return false;
			
		$modules = sql::run(
			" SELECT `Name` FROM `{modules}`" .
			" WHERE `Installed`" .
			(JCORE_VERSION >= '0.9'?
				" AND !`Deactivated`":
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
			return;
		
		$rows = sql::run(
			$this->SQL());
		
		if (!sql::rows($rows))
			return;
		
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
				
				$$modulename->display();
				unset($$modulename);
			}
		}
	}
}

?>