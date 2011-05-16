<?php

/***************************************************************************
 *            template.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
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
		
		template::$selected = $row;
		return true;
	}
	
	function install() {
		if (!$this->templateID)
			return false;
		
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
			
			return false;
		}
		
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
	
	// ************************************************   Client Part
	static function getSelected() {
		return template::$selected;
	}
	
	static function getSelectedID () {
		if (!template::$selected)
			return 0;
		
		return template::$selected['ID'];
	}
}

?>