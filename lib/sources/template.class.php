<?php

/***************************************************************************
 *            template.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
if (defined('WEBSITE_TEMPLATE') && WEBSITE_TEMPLATE)
	define('TEMPLATE_URL', url::site().'template/'.WEBSITE_TEMPLATE.'/');
else
	define('TEMPLATE_URL', url::site().'template/');

class _template {
	static $selected = null;
	
	static function populate() {
		if (!defined('WEBSITE_TEMPLATE') || !WEBSITE_TEMPLATE)
			return false;
		
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{templates}`" .
			" WHERE `Name` = '".sql::escape(WEBSITE_TEMPLATE)."'"));
		
		if (!$row)
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'template::populate', $_ENV);
		
		template::$selected = $row;
		
		if (@is_file('template/'.WEBSITE_TEMPLATE.'/template.php'))
			include_once('template/'.WEBSITE_TEMPLATE.'/template.php');
		
		api::callHooks(API_HOOK_AFTER,
			'template::populate', $_ENV, $row);
		
		return $row;
	}
	
	function install() {
		if (!isset($this->templateID) || !$this->templateID)
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'template::install', $this);
		
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
				__("Template couldn't be installed!")." " .
				__("Please see detailed error messages above and try again."),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'template::install', $this);
			
			return false;
		}
		
		if (JCORE_VERSION >= '0.9') {
			sql::run(
				" UPDATE `{templates}` SET " .
				" `Installed` = 1" .
				" WHERE `ID` = '".$this->templateID."'");
			
			if (sql::error()) {
				api::callHooks(API_HOOK_AFTER,
					'template::install', $this);
				
				return false;
			}
		}
		
		api::callHooks(API_HOOK_AFTER,
			'template::install', $this, $successfiles);
		
		return true;
	}
	
	function uninstall() {
		if (!isset($this->templateID) || !$this->templateID)
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'template::uninstall', $this);
		
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
		
		$result = true;
		
		if (JCORE_VERSION >= '0.9') {
			sql::run(
				" UPDATE `{templates}` SET " .
				" `Installed` = 0" .
				" WHERE `ID` = '".$this->templateID."'");
			
			if (sql::error())
				$result = false;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'template::uninstall', $this, $result);
		
		return $result;
	}
	
	// Class should be overwritten from template's own class
	function installSQL() {
		echo "<p>".__("No SQL queries to run.")."</p>";
			
		return true;
	}
	
	// Class should be overwritten from template's own class
	function installFiles() {
		echo "<p>".__("No files to install.")."</p>";
		
		return true;
	}
	
	// Class should be overwritten from template's own class
	function installCustom() {
		return true;
	}
	
	function installjQueryPlugins($plugins = null) {
		if (!isset($this->templateID) || !$this->templateID)
			return false;
		
		sql::run(
			" UPDATE `{templates}`" .
			" SET `jQueryPlugins` = '".sql::escape($plugins)."'" .
			" WHERE `ID` = '".$this->templateID."'");
		
		return (sql::affected() != -1);
	}
	
	// Class should be overwritten from template's own class
	function uninstallSQL() {
		echo "<p>".__("No SQL queries to run.")."</p>";
		
		return true;
	}
	
	// Class should be overwritten from template's own class
	function uninstallFiles() {
		echo "<p>".__("No files to uninstall.")."</p>";
		
		return true;
	}
	
	// Class should be overwritten from template's own class
	function uninstallCustom() {
		return true;
	}
	
	// ************************************************   Admin Part
	function displayInstallResults($title, $results, $success = false) {
		api::callHooks(API_HOOK_BEFORE,
			'template::displayInstallResults', $this, $title, $results, $success);
		
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
			'template::displayInstallResults', $this, $title, $results, $success);
	}
	
	// ************************************************   Client Part
	static function getSelected() {
		return template::$selected;
	}
	
	static function getSelectedID () {
		if (template::$selected)
			return template::$selected['ID'];
		
		return 0;
	}
}

?>