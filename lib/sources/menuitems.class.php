<?php

/***************************************************************************
 *            menuitems.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
include_once('lib/languages.class.php');
include_once('lib/pages.class.php');

// DEPRECATED! Please use instead PAGE_*
// see pages.class.php
define('MENU_EVERYONE', 0);
define('MENU_GUESTS_ONLY', 1);
define('MENU_USERS_ONLY', 2);
define('MENU_ADMINS_ONLY', 3);

class _menuItems extends pages {
	var $adminPath = 'admin/content/menuitems';
	
	static function isMainMenu($id, $languageid = 0) {
		return pages::isHome($id, $languageid);
	}
	
	static function getMainMenu($languageid = null) {
		return pages::getHome($languageid);
	}
	
	static function getMainMenuIDs() {
		return pages::getHomeIDs();
	}
	
	function getSelectedMenuIDs($menuid = null) {
		return pages::getSelectedIDs($menuid);
	}
	
	function displayTitle(&$row) {
		echo 
			"<a href='".$row['_Link']."'>" .
				"<span>" .
				$row['Title'] .
				"</span>" .
			"</a>";
	}
	
	function displaySubmenus(&$row) {
		$rows = sql::run(
			" SELECT * FROM `{pages}`" .
			" WHERE !`Deactivated`" .
			" AND !`Hidden`" .
			" AND `MenuID` = '".(int)$this->selectedMenuID."'" .
			" AND `LanguageID` = '".
				(languages::$selected?
					(int)languages::$selected['ID']:
					0) .
				"'" .
			" AND `SubPageOfID` = '".(int)$row['ID']."'" .
			" AND (!`ViewableBy` OR " .
				($GLOBALS['USER']->loginok?
					($GLOBALS['USER']->data['Admin']?
						" `ViewableBy` IN (2, 3)":
						" `ViewableBy` = 2"):
					" `ViewableBy` = 1") .
			" )" .
			" ORDER BY `OrderID`");
		
		$i = 1;
		$items = sql::rows($rows);
		
		if (!$items)
			return;
		
		echo 
			"<" .
			(JCORE_VERSION >= '0.5'?
				'ul':
				'div') .
			" class='" .
				(JCORE_VERSION < '0.6'?
					"sub-menu-of-id-".$row['ID']." ":
					null) .
				"sub-menu'>";
		
		while ($row = sql::fetch($rows)) {
			$row['_CSSClass'] = null;
			
			if ($i == 1)
				$row['_CSSClass'] .= ' first';
			if ($i == $items)
				$row['_CSSClass'] .= ' last';
			 
			$this->displayOne($row);
			
			$i++;
		}
		
		echo 
			"</" .
			(JCORE_VERSION >= '0.5'?
				'ul':
				'div') .
			">"; //submenu
	}
	
	function displayOne(&$row = null) {
		if ($row['Link'])
			$row['_Link'] = url::generateLink(
				$row['Link']);
		else
			$row['_Link'] = $this->generateLink($row);
		
		echo 
			"<" .
			(JCORE_VERSION >= '0.5'?
				'li':
				'div') .
			(JCORE_VERSION < '0.6' || !isset($this->arguments)?
				" id='menu".(int)$row['ID']."'":
				null) .
				" " .
				"class='menu" .
					(in_array($row['ID'], $this->selectedIDs)?
						" selected":
						NULL) .
					(isset($row['_CSSClass'])?
						" ".$row['_CSSClass']:
						null) .
					" menu".(int)$row['ID'] .
					"'>";
			
		$this->displayTitle($row);
		$this->displaySubmenus($row);
		
		echo
			"</" .
			(JCORE_VERSION >= '0.5'?
				'li':
				'div') .
			">";
	}
	
	function display() {
		$this->getSelectedIDs();
		
		$rows = sql::run(
			$this->SQL());
		
		$i = 1;
		$items = sql::rows($rows);
		
		while ($row = sql::fetch($rows)) {
			$row['_CSSClass'] = null;
			
			if ($i == 1)
				$row['_CSSClass'] .= ' first';
			if ($i == $items)
				$row['_CSSClass'] .= ' last';
			 
			$this->displayOne($row);
			
			$i++;
		}
	}
}

?>