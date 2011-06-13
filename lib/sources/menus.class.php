<?php

/***************************************************************************
 *            menus.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/menuitems.class.php'); 

class _menus {
	var $arguments = null;
	var $limit = 0;
	var $selectedLanguageID;
	var $selectedBlockID;
	var $adminPath = 'admin/site/menus';
	
	static $order = null;
	
	function __construct() {
		if (isset($_GET['languageid']))
			$this->selectedLanguageID = (int)$_GET['languageid'];
	}
	
	function SQL() {
		return
			" SELECT * FROM `{menus}` " .
			($this->selectedBlockID?
				" WHERE `BlockID` = '".$this->selectedBlockID."'":
				null) .
			" ORDER BY" .
			(JCORE_VERSION >= '0.7'?
				" `OrderID`,":
				null) .
			" `ID`";
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{menus}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Menu'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Layout Blocks'), 
			'?path=admin/site/blocks');
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=' .
			(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 250px;');
		
		$form->add(
			__('In Block'),
			'BlockID',
			FORM_INPUT_TYPE_SELECT);
		$form->setValueType(FORM_VALUE_TYPE_INT);
			
		$form->addValue('', '');
		
		$blockids = array();
		$layoutids = array();
		$disabledblocks = array();
		
		$menublocks = sql::run(
			" SELECT `ID`, `SubBlockOfID` FROM `{blocks}`" .
			" WHERE `TypeID` = '".BLOCK_TYPE_MENU."'" .
			(JCORE_VERSION >= '0.7'?
				" AND `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0)."'":
				null));
		
		if (sql::rows($menublocks)) {
			while($menublock = sql::fetch($menublocks)) {
				if (isset($blockids[$menublock['SubBlockOfID']])) {
					$blockids[$menublock['ID']] = true;
					
					if (JCORE_VERSION >= '0.9')
						$layoutids[$menublock['LayoutID']] = true;
					
					continue;
				}
				
				foreach(blocks::getBackTraceTree($menublock['ID']) as $block) {
					if (isset($blockids[$block['ID']]))
						continue;
					
					$blockids[$block['ID']] = true;
					
					if (JCORE_VERSION >= '0.9')
						$layoutids[$block['LayoutID']] = true;
				}
			}
			
			foreach(blocks::getTree() as $key => $block) {
				if ((JCORE_VERSION < '0.9' || !isset($layoutids[$block['LayoutID']]) || 
					$block['ID']) && !isset($blockids[$block['ID']]))
					continue;
				
				$form->addValue($block['ID'], 
					($block['SubBlockOfID']?
						str_replace(' ', '&nbsp;', 
							str_pad('', $block['PathDeepnes']*4, ' ')).
						"|- ":
						null) .
					$block['Title']);
					
				if ($block['ID'] && $block['TypeID'] != BLOCK_TYPE_MENU)
					$disabledblocks[] = $block['ID'];
			}
			
			$form->disableValues($disabledblocks);
			$form->groupValues(array('0'));
			
		} else {
			$form->edit(
				'BlockID',
				null,
				null,
				FORM_INPUT_TYPE_HIDDEN);
		}
		
		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		if (JCORE_VERSION >= '0.9') {
			$form->add(
				__('Include New Pages'),
				'IncludeNewPages',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				1);
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			$form->addAdditionalText(
				"<span class='comment'>" .
				__("(automatically add new pages)") .
				"</span>");
		}
		
		$form->add(
			__('Name'),
			'Name',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 200px;');
		$form->setValueType(FORM_VALUE_TYPE_LIMITED_STRING);
		
		if (JCORE_VERSION >= '0.7') {
			$form->add(
				__('Order'),
				'OrderID',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 50px;');
			$form->setValueType(FORM_VALUE_TYPE_INT);
		}
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
	}
	
	function verifyAdmin(&$form) {
		$reorder = null;
		$orders = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_POST['reordersubmit']))
			$reorder = $_POST['reordersubmit'];
		
		if (isset($_POST['orders']))
			$orders = (array)$_POST['orders'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if (JCORE_VERSION >= '0.7' && $reorder) {
			if (!$orders)
				return false;
			
			foreach($orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{menus}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'");
			}
			
			tooltip::display(
				__("Menus have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
			
			tooltip::display(
				__("Menu has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if (!$form->get('Name'))
			$form->set('Name', url::genPathFromString($form->get('Title')));
				
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray()))
				return false;
				
			tooltip::display(
				__("Menu has been successfully updated.")." " .
				"<a href='?path=" .
					(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems')."'>" .
					__("View Pages") .
				"</a>" .
				" - " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$newid = $this->add($form->getPostArray()))
			return false;
		
		tooltip::display(
			__("Menu has been successfully created.")." " .
			"<a href='?path=" .
				(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems')."'>" .
				__("View Pages") .
			"</a>" .
			" - " .
			"<a href='".url::uri('id, edit, delete') .
				"&amp;id=".$newid."&amp;edit=1#adminform'>" .
				__("Edit") .
			"</a>",
			TOOLTIP_SUCCESS);
			
		$form->reset();
		return true;
	}
	
	function displayAdminListHeader() {
		if (JCORE_VERSION >= '0.7')
			echo
				"<th><span class='nowrap'>".
					__("Order")."</span></th>";
		
		echo
			"<th><span class='nowrap'>".
				__("Title / Name")."</span></th>";
		
		if (JCORE_VERSION >= '0.9')
			echo
				"<th><span class='nowrap'>".
					__("New Pages")."</span></th>";
		
		echo
			"<th><span class='nowrap'>".
				__("In Block")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
		if (JCORE_VERSION >= '0.9')
			echo
				"<th><span class='nowrap'>".
					__("Menu Items")."</span></th>";
	}
	
	function displayAdminListHeaderFunctions() {
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
		$blockroute = null;
		
		foreach(blocks::getBackTraceTree($row['BlockID']) as $block)
			$blockroute .=
				"<div class='nowrap".
					($block['ID'] != $row['BlockID']?
						" comment":
						null) .
					"'>" . 
				($block['SubBlockOfID']?
					str_replace(' ', '&nbsp;', 
						str_pad('', $block['PathDeepnes']*4, ' ')).
					"|- ":
					null). 
				$block['Title'] .
				"</div>";
		
		if (JCORE_VERSION >= '0.7')
			echo
				"<td>" .
					"<input type='text' name='orders[".$row['ID']."]' " .
						"value='".$row['OrderID']."' " .
						"class='order-id-entry' tabindex='1' />" .
				"</td>";
		
		echo 
			"<td class='auto-width bold'>" .
				$row['Title'] .
				"<div class='comment' style='padding-left: 10px;'>" .
					$row['Name'] .
				"</div>" .
			"</td>";
		
		if (JCORE_VERSION >= '0.9')
			echo
				"<td style='text-align: right;'>" .
					($row['IncludeNewPages']?__('Yes'):'&nbsp;').
				"</td>";
		
		echo
			"<td>" .
				$blockroute .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
		if (JCORE_VERSION >= '0.9') {
			$items = sql::fetch(sql::run(
				" SELECT COUNT(*) AS `Rows`" .
				" FROM `{menuitems}`" .
				" WHERE `MenuID` = '".$row['ID']."'" .
				" LIMIT 1"));
			
			echo
				"<td align='center'>" .
					"<a class='admin-link menu-items' " .
						"title='".htmlspecialchars(__("Menu Items"), ENT_QUOTES) .
						(JCORE_VERSION >= '0.5'?
							" (".$items['Rows'].")":
							null) .
							"' " .
						"href='".url::uri('ALL') .
						"?path=".admin::path()."/".$row['ID']."/menuitems'>";
			
			if (ADMIN_ITEMS_COUNTER_ENABLED && $items['Rows'])
				counter::display($items['Rows']);
			
			echo
					"</a>" .
				"</td>";
		}
	}
	
	function displayAdminListItemFunctions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayAdminListFunctions() {
		echo
			"<input type='submit' name='reordersubmit' value='".
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminList(&$rows) {
		if (JCORE_VERSION >= '0.7') {
			echo
				"<form action='".url::uri('edit, delete')."' method='post'>";
		}
		
		echo "<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>";
		
		$this->displayAdminListHeader();
		$this->displayAdminListHeaderOptions();
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$this->displayAdminListHeaderFunctions();
		
		echo
				"</tr>" .
				"</thead>" .
				"<tbody>";
		
		$i = 0;		
		while($row = sql::fetch($rows)) {
			echo 
				"<tr".($i%2?" class='pair'":NULL).">";
			
			$this->displayAdminListItem($row);
			$this->displayAdminListItemOptions($row);
					
			if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
				$this->displayAdminListItemFunctions($row);
			
			echo
				"</tr>";
			
			$i++;
		}
		
		echo 
				"</tbody>" .
			"</table>" .
			"<br />";
		
		if (JCORE_VERSION >= '0.7') {
			if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
				$this->displayAdminListFunctions();
				
				echo
					"<div class='clear-both'></div>" .
					"<br />";
			}
						
			echo
				"</form>";
		}
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Menus Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
				
		$form = new form(
				($edit?
					__("Edit Menu"):
					__("New Menu")),
				'neweditmenu');
		
		if (!$edit)
			$form->action = url::uri('id, delete, limit');
					
		$this->setupAdminForm($form);
		$form->addSubmitButtons();
		
		if ($edit) {
			$form->add(
				__('Cancel'),
				'cancel',
				 FORM_INPUT_TYPE_BUTTON);
			$form->addAttributes("onclick=\"window.location='".
				str_replace('&amp;', '&', url::uri('id, edit, delete'))."'\"");
		}
		
		$verifyok = false;
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$verifyok = $this->verifyAdmin($form);
		
		$rows = sql::run(
			" SELECT * FROM `{menus}`" .
			" ORDER BY" .
			(JCORE_VERSION >= '0.7'?
				" `OrderID`,":
				null) .
			" `ID`");
			
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
					__("No menu blocks found."),
					TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{menus}`" .
					" WHERE `ID` = '".$id."'"));
				
				$form->setValues($selected);
			}
			
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form);
		
		echo 
			"</div>";	//admin-content
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		if (JCORE_VERSION >= '0.7') {
			if ($values['OrderID'] == '') {
				$row = sql::fetch(sql::run(
					" SELECT `OrderID` FROM `{menus}` " .
					" ORDER BY `OrderID` DESC" .
					" LIMIT 1"));
				
				$values['OrderID'] = (int)$row['OrderID']+1;
				
			} else {
				sql::run(
					" UPDATE `{menus}` SET " .
					" `OrderID` = `OrderID` + 1" .
					" WHERE `OrderID` >= '".(int)$values['OrderID']."'");
			}
		}
		
		$newid = sql::run(
			" INSERT INTO `{menus}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Name` = '".
				sql::escape($values['Name'])."'," .
			(JCORE_VERSION >= '0.7'?
				" `OrderID` = '".
					(int)$values['OrderID']."',":
				null) .
			(JCORE_VERSION >= '0.9'?
				" `IncludeNewPages` = '".
					(int)$values['IncludeNewPages']."',":
				null) .
			" `BlockID` = '".
				(int)$values['BlockID']."'");
			
		if (!$newid) {
			tooltip::display(
				sprintf(__("Menu couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		sql::run(
			" UPDATE `{menus}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Name` = '".
				sql::escape($values['Name'])."'," .
			(JCORE_VERSION >= '0.7'?
				" `OrderID` = '".
					(int)$values['OrderID']."',":
				null) .
			(JCORE_VERSION >= '0.9'?
				" `IncludeNewPages` = '".
					(int)$values['IncludeNewPages']."',":
				null) .
			" `BlockID` = '".
				(int)$values['BlockID']."'" .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Menu couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		sql::run(
			" DELETE FROM `{menus}` " .
			" WHERE `ID` = '".(int)$id."'");
		
		if (JCORE_VERSION >= '0.9')
			sql::run(
				" DELETE FROM `{menuitems}`" .
				" WHERE `MenuID` = '".(int)$id."'");
		
		return true;
	}
	
	// ************************************************   Client Part
	static function getOrder() {
		if (JCORE_VERSION >= '0.7') {
			$menuorder = sql::fetch(sql::run(
				" SELECT GROUP_CONCAT(`ID` ORDER BY `OrderID`, `ID` SEPARATOR ',') AS `MenuIDs`" .
				" FROM `{menus}`" .
				" LIMIT 1"));
			
			menus::$order = $menuorder['MenuIDs'];
			return menus::$order;
		}
		
		return false;
	}
	
	function displayItems(&$row, $language = null, $menuitem = null) {
		$menuitems = new menuItems();
		$menuitems->arguments = $this->arguments;
		
		if ($language)
			$menuitems->selectedLanguageID = $language['ID'];
		
		if ($menuitem) {
			$menuitems->selectedMenuID = $row['ID'];
			$menuitems->getSelectedIDs();
			$menuitems->displaySubmenus($menuitem, false);
			
		} else {
			$menuitems->selectedMenuID = $row['ID'];
			$menuitems->display();
		}
		
		unset($menuitems);
	}
	
	function displayOne(&$row, $language = null, $menuitem = null) {
		echo
			"<nav " .
				(JCORE_VERSION >= '0.5' && $this->arguments?
					"class":
					"id") .
				"='".$row['Name']."_outer'>" .
				"<" .
				(JCORE_VERSION >= '0.5'?
					'ul':
					'div') .
				" " .
				(JCORE_VERSION >= '0.5' && $this->arguments?
					"class":
					"id") .
				"='".$row['Name']."'>";
		
		$this->displayItems($row, $language, $menuitem);
		
		echo
				"</" .
				(JCORE_VERSION >= '0.5'?
					'ul':
					'div') .
				">" .
			"</nav>";
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		if (preg_match('/(^|\/)selected($|\/)/', $this->arguments)) {
			if (pages::$selected) {
				if (SEO_FRIENDLY_LINKS)
					echo pages::$selected['Path'];
				else
					echo pages::$selected['ID'];
			}
			
			return true;
		}
		
		preg_match('/(.*?)(\/|$)(.*)/', $this->arguments, $matches);
		
		$menu = null;
		$languageanditem = null;
		
		if (isset($matches[1]))
			$menu = $matches[1];
			
		if (isset($matches[3]))
			$languageanditem = $matches[3];
			
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{menus}` " .
			" WHERE `Name` LIKE '".sql::escape($menu)."'" .
			" ORDER BY `ID`" .
			" LIMIT 1"));
			
		if (!$row)
			return true;
		
		if ($languageanditem) {
			preg_match('/(.*?)(\/|$)(.*)/', $languageanditem, $matches);
			
			$lang = null;
			$item = null;
			
			if (isset($matches[1]))
				$lang = $matches[1];
				
			if (isset($matches[3]))
				$item = $matches[3];
			
			$language = null;
			if ((int)$this->selectedLanguageID)	
				$language = sql::fetch(sql::run(
					" SELECT * FROM `{languages}` " .
					" WHERE `Path` LIKE '".sql::escape($lang)."'" .
					" LIMIT 1"));
					
			if ($language && !$item) {
				$this->displayOne($row, $language);
				return true;
			}
				
			$menuitem = sql::fetch(sql::run(
				" SELECT * FROM `{" .
					(JCORE_VERSION == '0.8'?
						'pages':
						'menuitems') .
					"}` " .
				" WHERE `Deactivated` = 0" .
				" AND `MenuID` = '".$row['ID']."'" .
				(JCORE_VERSION < '0.9'?
					" AND `Hidden` = 0":
					null) .
				($language?
					" AND `LanguageID` = '".(int)$language['ID']."'" .
					" AND `Path` LIKE '".sql::escape($item)."'":
					" AND `Path` LIKE '".sql::escape(
						($item?
							$lang.'/'.$item:
							$lang)).
						"'") .
				" AND (`ViewableBy` = 0 OR " .
					($GLOBALS['USER']->loginok?
						($GLOBALS['USER']->data['Admin']?
							" `ViewableBy` IN (2, 3)":
							" `ViewableBy` = 2") .
						(JCORE_VERSION >= '0.9' && $GLOBALS['USER']->data['GroupID']?
							" OR `ViewableBy` = '".($GLOBALS['USER']->data['GroupID']+10)."'":
							null):
						" `ViewableBy` = 1") .
				" )" .
				" ORDER BY" .
				(JCORE_VERSION >= '0.9'?
					" `SubMenuItemOfID`,":
					null) .
				" `OrderID`, `ID`" .
				" LIMIT 1"));
			
			if (!$menuitem)
				return true;
			
			$this->displayOne($row, $language, $menuitem);
			return true;
		}
		
		$this->displayOne($row);
		return true;
	}
	
	function display() {
		if ($this->displayArguments())
			return;
		
		$rows = sql::run(
			$this->SQL() .
			($this->limit?
				" LIMIT ".$this->limit:
				null));
		
		while($row = sql::fetch($rows))
			$this->displayOne($row);
	}
}

?>