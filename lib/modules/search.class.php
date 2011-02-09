<?php

/***************************************************************************
 * 
 *  Name: Search Module
 *  URI: http://jcore.net
 *  Description: Implement your own search engine for posts and searchable modules.
 *  Author: Istvan Petres
 *  Version: 0.8
 *  Tags: search module, gpl
 * 
 ****************************************************************************/

modules::register(
	'search', 
	_('Search Engine'),
	_('Have your own search engine'));

class search extends modules {
	static $uriVariables = 'search, searchin, searchlimit';
	var $selectedID;
	var $searchURL = '';
	var $limit = 10;
	var $keywordsLimit = 21;
	var $search = '';
	var $searchIn = null;
	var $adminPath = 'admin/modules/search';
	var $searchInList = array();
	var $ajaxRequest = null;
	
	function __construct() {
		languages::load('search');
		
		if (isset($_GET['search']))
			$this->search = trim(strip_tags($_GET['search']));
		
		if (isset($_POST['search']))
			$this->search = trim(strip_tags($_POST['search']));
		
		if (isset($_GET['searchin']))
			$this->searchIn = trim(strip_tags($_GET['searchin']));
		
		if (isset($_POST['searchin']))
			$this->searchIn = trim(strip_tags($_POST['searchin']));
	}
	
	function __destruct() {
		languages::unload('search');
	}
	
	function installSQL() {
		sql::run(
			"CREATE TABLE IF NOT EXISTS `{searches}` (" .
			" `Keyword` varchar(100) NOT NULL default ''," .
			" `Counter` mediumint(8) unsigned NOT NULL default '0'," .
			" KEY `Counter` (`Counter`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{searchmodules}` (" .
			" `ModuleID` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0'," .
			" `Limit` SMALLINT UNSIGNED NOT NULL DEFAULT '10'," .
			" INDEX ( `ModuleID` )" .
			" ) ENGINE = MYISAM ;");
		
		if (sql::display())
			return false;
			
		return true;
	}
	
	function installFiles() {
		$css = 
			"#searchform {\n" .
			"	margin-bottom: 10px;\n" .
			"}\n" .
			"\n" .
			"#searchform .text-entry {\n" .
			"	width: 200px;\n" .
			"	margin-right: 10px;\n" .
			"}\n" .
			"\n" .
			"#searchform .form-entry {\n" .
			"	display: inline;\n" .
			"	white-space: nowrap;\n" .
			"	clear: none;\n" .
			"}\n" .
			"\n" .
			"#searchform .form-entry-title {\n" .
			"	padding-top: 3px;\n" .
			"	display: inline;\n" .
			"	width: auto;\n" .
			"	float: none;\n" .
			"}\n" .
			"\n" .
			"#searchform .form-entry-content {\n" .
			"	display: inline;\n" .
			"	width: auto;\n" .
			"	margin-left: 0;\n" .
			"}\n" .
			"\n" .
			"#searchform .button-searching {\n" .
			"	margin-left: 10px;\n" .
			"}\n" .
			"\n" .
			"#searchform .clear-both {\n" .
			"	clear: none;\n" .
			"	display: inline;\n" .
			"}\n" .
			"\n" .
			".searches {\n" .
			"	margin-top: 10px;\n" .
			"}\n" .
			"\n" .
			".search-keywords-cloud {\n" .
			"	clear: both;\n" .
			"	font-size: 27px;\n" .
			"	margin-top: 1px;\n" .
			"}\n" .
			"\n" .
			".as-modules-search a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/system-search.png\");\n" .
			"}\n";
		
		if (!files::save(SITE_PATH.'template/modules/css/search.css', $css, true)) {
			tooltip::display(
				__("Could NOT write css file.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"template/modules/css/"),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	// ************************************************   Admin Part
	function verifyAdmin(&$form = null) {
		$submit = null;
		$moduleids = null;
		$modulelimits = null;
		
		if (isset($_POST['submit']))
			$submit = $_POST['submit'];
		
		if (isset($_POST['moduleids']))
			$moduleids = (array)$_POST['moduleids'];
		
		if (isset($_POST['modulelimits']))
			$modulelimits = (array)$_POST['modulelimits'];
		
		if ($submit) {
			sql::run(
				" TRUNCATE TABLE `{searchmodules}`");
			
			if (count($moduleids)) {
				foreach($moduleids as $moduleid => $checked)
					sql::run(
						" INSERT INTO `{searchmodules}` SET" .
						" `ModuleID` = '".(int)$moduleid."'," .
						" `Limit` = '".(int)$modulelimits[$moduleid]."'");
			}
			
			tooltip::display(
				_("Items have been successfully updated."),
				TOOLTIP_SUCCESS);
				
			$_POST = array();
			return true;
		}
		
		return false;
	}
	
	function setupAdmin() {
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=admin/content/menuitems');
		favoriteLinks::add(
			__('Layout Blocks'), 
			'?path=admin/site/blocks');
		favoriteLinks::add(
			__('View Website'), 
			SITE_URL);
	}
	
	function displayAdminListHeader() {
		echo
			"<th>" .
				"<input type='checkbox' class='checkbox-all' " .
				($this->userPermissionType != USER_PERMISSION_TYPE_WRITE?
					"disabled='disabled' ":
					null) .
				"/>" .
			"</th>" .
			"<th><span class='nowrap'>".
				_("Title / Description")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Limit")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
		if ($row['ID'] == 65536) {
			$moduletitle = __('Posts');
			$moduledescription = _('Blog like content for menu items.');
		} else {
			$moduletitle = modules::getTitle($row['Name']);
			$moduledescription = modules::getDescription($row['Name']);
		}
		
		$moduleset = sql::fetch(sql::run(
			" SELECT * FROM `{searchmodules}`" .
			" WHERE `ModuleID` = '".$row['ID']."'" .
			" LIMIT 1"));
		
		echo
			"<td>" .
				"<input type='checkbox' name='moduleids[".$row['ID']."]' " .
					"value='".$row['ID']."' " .
					($moduleset?
						"checked='checked' ":
						null).
					($this->userPermissionType != USER_PERMISSION_TYPE_WRITE?
						"disabled='disabled' ":
						null) .
					" />" .
			"</td>" .
			"<td class='auto-width'>" .
				"<div class='bold'>" .
					$moduletitle .
				"</div>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					$moduledescription .
				"</div>" .
			"</td>" .
			"<td>" .
				"<input type='text' name='modulelimits[".$row['ID']."]' " .
					"value='".$moduleset['Limit']."' " .
					($this->userPermissionType != USER_PERMISSION_TYPE_WRITE?
						"disabled='disabled' ":
						null) .
					"style='width: 30px;' onchange=\"if(parseInt(this.value) > 0) " .
						"jQuery(this).closest('tr').find('input:first').attr('checked', true);\"/>" .
			"</td>";
	}
	
	function displayAdminListFunctions() {
		echo
			"<input type='submit' name='submit' value='".
				htmlspecialchars(_("Update"), ENT_QUOTES)."' class='button submit'> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button'>";
	}
	
	function displayAdminList(&$rows) {
		echo
			"<form action='".url::uri()."' id='searchmodulesform' method='post'>" .
			"<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>";
		
		$this->displayAdminListHeader();
			
		echo
				"</tr>" .
				"</thead>" .
				"<tbody>";
		
		$i = 0;
		foreach($rows as $row) {
			echo 
				"<tr".($i%2?" class='pair'":NULL).">";
			
			$this->displayAdminListItem($row);
			
			echo
				"</tr>";
			
			$i++;
		}
		
		echo 
				"</tbody>" .
			"</table>" .
			"<br />";
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE) {
			$this->displayAdminListFunctions();
			
			echo
				"<div class='clear-both'></div>" .
				"<br />";
		}
				
		echo
			"</form>";
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			_('Search Engine Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
		echo 
			"<p>" .
				_("Check modules that should be searchable by the Local " .
					"Search Engine and you can also define custom limits " .
					"for them. By default if none checked all modules will " .
					"be searchable and if no limit defined (0) the one " .
					"set for the menu under which the search module is " .
					"will be used.") .
			"</p>";
	}
	
	function displayAdmin() {
		if (modules::displayAdmin())
			return;
		
		$this->displayAdminTitle();
		
		if (JCORE_VERSION <= '0.4') {
			tooltip::display(
				"There is no settings for the search module anymore " .
					"unless you upgrade to jCore version 0.5+",
				TOOLTIP_NOTIFICATION);
			return;
		}
		
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			$this->verifyAdmin();
		
		$rows = search::getTree();
			
		if (count($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				_("No modules found."),
				TOOLTIP_NOTIFICATION);
		
		echo
			"</div>"; //admin-content
	}
	
	// ************************************************   Client Part
	function watch() {
		if (!$this->search)
			return null;
			
		sql::run(
			" UPDATE `{searches}` " .
			" SET `Counter` = `Counter`+1" .
			" WHERE `Keyword` LIKE '".sql::escape($this->search)."'");
			
		if (sql::affected()) {
			return true;
		}
		
		sql::run(
			" INSERT INTO `{searches}` SET " .
			" `Keyword` = '".sql::escape($this->search)."'," .
			" `Counter` = 1");
	}
	
	static function getTree($onlyenabled = false) {
		$onlyin = null;
		
		if (JCORE_VERSION < '0.5')
			return array();
		
		if ($onlyenabled) {
			$row = sql::fetch(sql::run(
				" SELECT GROUP_CONCAT(`ModuleID` SEPARATOR ',') AS `ModuleIDs`" .
				" FROM `{searchmodules}`" .
				" LIMIT 1"));
			
			if ($row['ModuleIDs'])
				$onlyin = explode(',', $row['ModuleIDs']);
		}
		
		$rows = sql::run(
			" SELECT " .
			" `ID`," .
			" `Name`," .
			" `Name` AS `Title`, " .
			" 0 AS `SubItemOfID`," .
			" 0 AS `PathDeepnes`" .
			" FROM `{modules}` " .
			" WHERE `Installed`" .
			" AND `Searchable`" .
			" ORDER BY `Name`");
			
		$tree = array();
		
		if (!$onlyin || in_array(65536, $onlyin))
			$tree[] = array(
				'ID' => 65536,
				'Title' => 'Posts',
				'Name' => 'posts',
				'Path' => '',
				'SubItemOfID' => 0,
				'PathDeepnes' => 0);
		
		while($row = sql::fetch($rows)) {
			if ($onlyin && !in_array($row['ID'], $onlyin))
				continue;
			
			$row['Path'] = 'modules/'.
				strtolower($row['Title']);
			
			modules::load($row['Title'], true);
			$row['Title'] = modules::getTitle($row['Title']);
			$tree[] = $row;
		}
		
		return $tree;
	}
	
	static function getURL() {
		$url = modules::getOwnerURL('Search');
		
		if (!$url)
			return url::uri(search::$uriVariables).
				"&amp;searchin=posts";
		
		return $url;	
	}
	
	function setOption($optionid) {
		$this->selectedID = $optionid;
	}
	
	function displayKeywordsCloudLink(&$row) {
		echo 
			"<a href='".$this->searchURL."&amp;search=".
				htmlspecialchars($row['Keyword'], ENT_QUOTES)."' " .
				"style='font-size: ".$row['_FontPercent']."%;'>" .
				$row['Keyword'] .
			"</a> ";
	}
	
	function displayKeywordsCloud($arguments = null) {
		$byranks = false;
		
		if (preg_match('/(^|\/)byranks($|\/)/', $arguments)) {
			$arguments = preg_replace('/(^|\/)byranks($|\/)/', '\2', $arguments);
			$byranks = true;
		}
		
		if (!$this->searchURL)
			$this->searchURL = search::getURL();
		
		sql::run(
			" CREATE TEMPORARY TABLE `{TMPSearches}` " .
			" (`Keyword` varchar(100) NOT NULL default ''," .
			"  `Counter` mediumint(8) unsigned NOT NULL default '0'," .
			"  `ID` tinyint(2) unsigned NOT NULL auto_increment," .
			" PRIMARY KEY  (`ID`)" .
			")");
			
		sql::run(
			" INSERT INTO `{TMPSearches}` " .
			" SELECT *, NULL FROM `{searches}`" .
			" ORDER BY `Counter` DESC" .
			" LIMIT ".$this->keywordsLimit);
			
		$rows = sql::run(
			" SELECT * FROM `{TMPSearches}`" .
			" ORDER BY " .
			($byranks?
				" `Counter` DESC, ID,":
				null) .
			" `Keyword`");
		
		$hrow = sql::fetch(sql::run(
			" SELECT `Counter` FROM `{TMPSearches}`" .
			" ORDER BY `Counter` DESC" .
			" LIMIT 1"));
		
		echo "<div class='search-keywords-cloud'>";
		
		while($row = sql::fetch($rows)) {
			$row['_FontPercent'] = round(
				($row['Counter']*70/$hrow['Counter']))+30;
			
			$this->displayKeywordsCloudLink($row);
		}
		
		sql::run(" DROP TEMPORARY TABLE `{TMPSearches}` ");
		
		echo "</div>";
	}
	
	function setupSearchForm(&$form) {
		if (!$this->searchInList)
			$this->searchInList = search::getTree(true);
		
		$form->add(
			_('Search for'),
			'search',
			FORM_INPUT_TYPE_SEARCH,
			false,
			$this->search);
		
		$form->setPlaceholderText(
			_("search..."));
		$form->setAttributes(
			"results='5'");
			
		if ($this->arguments && $this->searchIn) {
			$form->add(
				_('in'),
				'searchin',
				FORM_INPUT_TYPE_HIDDEN,
				false,
				$this->searchIn);
			
			$form->setValue($this->searchIn);
		}
		
		if (!$this->selectedID && !$this->arguments) {
			$form->add(
				_('in'),
				'searchin',
				(count($this->searchInList) > 1?
					FORM_INPUT_TYPE_SELECT:
					FORM_INPUT_TYPE_HIDDEN),
				false,
				$this->searchIn);
					
			foreach($this->searchInList as $in) {
				if ($in['ID'] == 65536)
					$form->addValue(
						'', 
						__('Posts'));
				else
					$form->addValue(
						$in['Path'], 
						ucfirst($in['Title']));
			}
		}
		
		if (JCORE_VERSION >= '0.5')
			$form->add(
				__('Search'),
				'searching',
				FORM_INPUT_TYPE_SUBMIT);
		elseif (count($this->searchInList) > 1)
			$form->addAdditionalText(
				"<input type='submit' " .
					"value='".htmlspecialchars(__("Search"), ENT_QUOTES)."' " .
					"class='button submit' />");
		else
			$form->addAdditionalText(
				'search',
				"<input type='submit' " .
					"value='".htmlspecialchars(__("Search"), ENT_QUOTES)."' " .
					"class='button submit' />");
		
		$getvars = explode('&amp;', 
			preg_replace('/.*\?/', '', $form->action));
		
		foreach($getvars as $getvar) {
			preg_match('/(.*?)=(.*)/', $getvar, $matches);
			
			if (isset($matches[1]) && isset($matches[2]) && 
				$form->getElementID($matches[1]) === null)
			{
				$form->add(
					'',
					strip_tags($matches[1]),
					FORM_INPUT_TYPE_HIDDEN,
					false,
					strip_tags($matches[2]));
				
				$form->setValue(strip_tags($matches[2]));
			}
		}
	}
	
	function displaySearchForm() {
		if (!$this->searchURL)
			$this->searchURL = search::getURL();
		
		$form = new form(
			__('Search'), 'search', 'get');
		
		$form->action = $this->searchURL;
		$form->footer = '';
		
		$this->setupSearchForm($form);
		
		$form->display(false);
		unset($form);
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		$argument = null;
		
		if (preg_match('/(^|\/)([0-9]*?)($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)[0-9]*?($|\/)/', '\2', $this->arguments);
			$this->limit = (int)$matches[2];
		}
		
		if (preg_match('/(^|\/)keywords($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)keywords($|\/)/', '\2', $this->arguments);
			
			if (isset($matches[2]))
				$this->keywordsLimit = (int)$matches[2];
			
			$argument = 'keywords';
		}
		
		if (preg_match('/(^|\/)form($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)form($|\/)/', '\2', $this->arguments);
			$argument = 'form';
		}
		
		$this->searchIn = trim($this->arguments, '/');
		
		if ($this->searchIn == 'posts')
			$this->searchIn = null;
		
		switch(strtolower($argument)) {
			case 'keywords':
				$this->displayKeywordsCloud();
				return true;
		
			case 'form':
				$this->displaySearchForm();
				return true;
			
			default:
				return true;
		}
	}
	
	function display() {
		if ($this->displayArguments())
			return;
		
		if ($this->owner['Limit'])
			$this->limit = $this->owner['Limit'];
			
		if (!$this->searchURL)
			$this->searchURL = search::getURL();
			
		if (!$this->searchInList)
			$this->searchInList = search::getTree(true);
		
		$inid = 0;
		foreach($this->searchInList as $in) {
			if (strcasecmp($this->searchIn, $in['Path']))
				continue;
			
			$inid = $in['ID'];
			break;
		}
		
		if (!$inid) {
			$inid = $this->searchInList[0]['ID'];
			$this->searchIn = null;
		}
		
		if (JCORE_VERSION >= '0.5') {
			$customlimit = sql::fetch(sql::run(
				" SELECT * FROM `{searchmodules}`" .
				" WHERE `ModuleID` = '".$inid."'"));
			
			if ($customlimit['Limit'])
				$this->limit = $customlimit['Limit'];
		}
		
		if (!$this->searchIn)
			$this->searchIn = $this->searchInList[0]['Path'];
		
		if ($this->selectedID && $this->selectedID != 65536) {
			$module = sql::fetch(sql::run(
				" SELECT * FROM `{modules}`" .
				" WHERE `ID` = '".(int)$this->selectedID."'" .
				" AND `Installed`" .
				" AND `Searchable`" .
				" ORDER BY `Name`"));
			
			$this->searchIn = 
				'modules/'.strtolower($module['Name']);
		}
			
		$this->watch();
		
		if (!$this->ajaxRequest)
			$this->displaySearchForm();
		
		if (!$this->search) {
			$this->displayKeywordsCloud();
			return;
		}
		
		$classname = 'posts';
		$expsearchin = explode('/', trim($this->searchIn, '/'));
			
		if (!strcasecmp('modules', $expsearchin[0])) {
			$classname = $expsearchin[count($expsearchin)-1];
			modules::load($classname);
		}
		
		if (!class_exists($classname) || !method_exists($classname, 'display')) {
			tooltip::display(
				_("Invalid target selected for your search! Please " .
					"select a new target (search in) or don't select " .
					"any if you want to search for posts."),
				TOOLTIP_ERROR);
			
			return;
		}
		
		if (!$this->ajaxRequest)
			echo "<div class='searches'>";
		
		$keywords = array();
		
		if ($this->search) {
			if (!$this->searchIn)
				$this->searchIn = 'posts';
			else
				url::setURI(url::uri('searchin').
					"&searchin=".$this->searchIn);
			
			$class = new $classname();
			$class->search = $this->search;
			$class->limit = $this->limit;
			
			if (!$class->display())
				$this->displayKeywordsCloud();
			
			unset($class);
		}
		
		if (!$this->ajaxRequest)
			echo "</div>"; //searches
	}
}

?>