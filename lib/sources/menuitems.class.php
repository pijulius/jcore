<?php

/***************************************************************************
 *            menuitems.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/languages.class.php');
include_once('lib/pages.class.php');

define('MENU_EVERYONE', 0);
define('MENU_GUESTS_ONLY', 1);
define('MENU_USERS_ONLY', 2);
define('MENU_ADMINS_ONLY', 3);

class _menuItems {
	var $selectedIDs = null;
	var $selectedMenuID = 0;
	var $adminPath = array(
		'admin/content/menuitems',
		'admin/site/menus/menuitems');

	function SQL() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::SQL', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::SQL', $this, $handled);

			return $handled;
		}

		if (JCORE_VERSION >= '0.9')
			$sql =
				" SELECT * FROM `{menuitems}`" .
				" WHERE `Deactivated` = 0" .
				" AND `MenuID` = '".(int)$this->selectedMenuID."'" .
				" AND `LanguageID` = '".
					(languages::$selected?
						(int)languages::$selected['ID']:
						0) .
					"'" .
				" AND `SubMenuItemOfID` = 0" .
				" AND (`ViewableBy` = 0 OR " .
					($GLOBALS['USER']->loginok?
						($GLOBALS['USER']->data['Admin']?
							" `ViewableBy` IN (2, 3)":
							" `ViewableBy` = 2") .
						(JCORE_VERSION >= '0.9' && $GLOBALS['USER']->data['GroupID']?
							" OR `ViewableBy` = '".(int)($GLOBALS['USER']->data['GroupID']+10)."'":
							null):
						" `ViewableBy` = 1") .
				" )" .
				" ORDER BY `OrderID`, `ID`";
		else
			$sql =
				" SELECT * FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `Deactivated` = 0" .
				" AND `Hidden` = 0" .
				" AND `MenuID` = '".(int)$this->selectedMenuID."'" .
				" AND `LanguageID` = '".
					(languages::$selected?
						(int)languages::$selected['ID']:
						0) .
					"'" .
				" AND `".(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')."` = 0" .
				" AND (`ViewableBy` = 0 OR " .
					($GLOBALS['USER']->loginok?
						($GLOBALS['USER']->data['Admin']?
							" `ViewableBy` IN (2, 3)":
							" `ViewableBy` = 2"):
						" `ViewableBy` = 1") .
				" )" .
				" ORDER BY `OrderID`, `ID`";

		api::callHooks(API_HOOK_AFTER,
			'menuItems::SQL', $this, $sql);

		return $sql;
	}

	// ************************************************   Admin Part
	function countAdminItems() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::countAdminItems', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::countAdminItems', $this, $handled);

			return $handled;
		}

		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{" .
				(JCORE_VERSION == '0.8'?
					'pages':
					'menuitems') .
				"}`" .
			" LIMIT 1"));

		api::callHooks(API_HOOK_AFTER,
			'menuItems::countAdminItems', $this, $row['Rows']);

		return $row['Rows'];
	}

	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::setupAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::setupAdmin', $this, $handled);

			return $handled;
		}

		if (JCORE_VERSION < '0.9') {
			$pages = new pages();
			$pages->userPermissionType = $this->userPermissionType;
			$pages->userPermissionIDs = $this->userPermissionIDs;
			$pages->setupAdmin();
			unset($pages);

			api::callHooks(API_HOOK_AFTER,
				'menuItems::setupAdmin', $this);

			return;
		}

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Menu Item'),
				'?path='.admin::path().'#adminform');

		favoriteLinks::add(
			__('Pages / Posts'),
			'?path=admin/content/pages');

		api::callHooks(API_HOOK_AFTER,
			'menuItems::setupAdmin', $this);
	}

	function setupAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::setupAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::setupAdminForm', $this, $form, $handled);

			return $handled;
		}

		$edit = null;

		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];

		if (!$edit) {
			$form->add(
				__('Select Page(s) to Add'),
				'PageIDs',
				FORM_INPUT_TYPE_MULTISELECT);
			$form->setValueType(FORM_VALUE_TYPE_ARRAY);
			$form->setStyle('width: 90%; height: 250px;');

			foreach(pages::getTree() as $page)
				$form->addValue($page['ID'],
					($page['SubPageOfID']?
						str_replace(' ', '&nbsp;',
							str_pad('', $page['PathDeepnes']*4, ' ')).
						"|- ":
						null) .
					$page['Title']);

			$form->groupValues(array('0'));

			$form->add(
				__('Custom Menu Item Options'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
		}

		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			($edit?true:false));
		$form->setStyle('width: 250px;');

		$form->add(
			__('Sub Menu Item of'),
			'SubMenuItemOfID',
			FORM_INPUT_TYPE_SELECT);
		$form->setValueType(FORM_VALUE_TYPE_INT);

		$form->addValue('', '');

		if ($languages = languages::get()) {
			$form->add(
				__('Language'),
				'LanguageID',
				FORM_INPUT_TYPE_SELECT);
			$form->setValueType(FORM_VALUE_TYPE_INT);

			$form->addValue('', '');

			while($language = sql::fetch($languages))
				$form->addValue($language['ID'],
					$language['Title']);
		}

		if (!$edit)
			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER);

		$form->add(
			__('Display Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);

		$form->add(
			__('Path'),
			'Path',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 200px;');

		$form->add(
			__('Link to URL'),
			'Link',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 300px;');
		$form->setValueType(FORM_VALUE_TYPE_URL);
		$form->setTooltipText(__("e.g. http://domain.com"));

		$form->add(
			__('Viewable by'),
			'ViewableBy',
			FORM_INPUT_TYPE_SELECT);
		$form->setValueType(FORM_VALUE_TYPE_INT);

		$form->addValue(
			MENU_EVERYONE, $this->access2Text(MENU_EVERYONE));
		$form->addValue(
			MENU_GUESTS_ONLY, $this->access2Text(MENU_GUESTS_ONLY));
		$form->addValue(
			MENU_USERS_ONLY, $this->access2Text(MENU_USERS_ONLY));
		$form->addValue(
			MENU_ADMINS_ONLY, $this->access2Text(MENU_ADMINS_ONLY));

		if (JCORE_VERSION >= '0.9') {
			$ugroups = userGroups::get();

			while($ugroup = sql::fetch($ugroups))
				$form->addValue(
					$ugroup['ID']+10, $ugroup['GroupName']);
		}

		$form->add(
			'MenuID',
			'MenuID',
			FORM_INPUT_TYPE_HIDDEN,
			true,
			$this->selectedMenuID);
		$form->setValueType(FORM_VALUE_TYPE_INT);

		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);

		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);

		$form->add(
			__('Deactivated'),
			'Deactivated',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);

		$form->addAdditionalText(
			"<span class='comment' style='text-decoration: line-through;'>" .
			__("(marked with strike through)").
			"</span>");

		$form->add(
			__('Order'),
			'OrderID',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);

		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);

		api::callHooks(API_HOOK_AFTER,
			'menuItems::setupAdminForm', $this, $form);
	}

	function verifyAdmin(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::verifyAdmin', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::verifyAdmin', $this, $form, $handled);

			return $handled;
		}

		$reorder = null;
		$orders = null;
		$delete = null;
		$edit = null;
		$id = null;

		if (isset($_POST['reordersubmit']))
			$reorder = (string)$_POST['reordersubmit'];

		if (isset($_POST['orders']))
			$orders = (array)$_POST['orders'];

		if (isset($_POST['delete']))
			$delete = (int)$_POST['delete'];

		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];

		if (isset($_GET['id']))
			$id = (int)$_GET['id'];

		if ($reorder) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'menuItems::verifyAdmin', $this, $form);
				return false;
			}

			foreach((array)$orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{menuitems}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'");
			}

			tooltip::display(
				__("Menu items have been successfully re-ordered."),
				TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'menuItems::verifyAdmin', $this, $form, $reorder);

			return true;
		}

		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'menuItems::verifyAdmin', $this, $form);
				return false;
			}

			$result = $this->delete($id);

			if ($result)
				tooltip::display(
					__("Menu item and all its subitems have been successfully deleted."),
					TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'menuItems::verifyAdmin', $this, $form, $result);

			return $result;
		}

		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::verifyAdmin', $this, $form);

			return false;
		}

		if (!$edit && !$form->get('Title') && !count($form->get('PageIDs'))) {
			tooltip::display(
				__("No page selected to be added or no custom menu item title defined!"),
				TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'menuItems::verifyAdmin', $this, $form);

			return false;
		}

		if (!$edit && count($form->get('PageIDs'))) {
			$newids = array();

			foreach($form->get('PageIDs') as $pageid) {
				$page = sql::fetch(sql::run(
					" SELECT * FROM `{pages}`" .
					" WHERE `ID` = '".(int)$pageid."'"));

				if (!$page)
					continue;

				$page['ViewableBy'] = $form->get('ViewableBy');
				$page['Link'] = '';
				$page['MenuID'] = $this->selectedMenuID;
				$page['PageID'] = $page['ID'];

				$page['SubMenuItemOfID'] =
					($page['SubPageOfID'] && isset($newids[$page['SubPageOfID']]) &&
					 $newids[$page['SubPageOfID']]?
						$newids[$page['SubPageOfID']]:
						$form->get('SubMenuItemOfID'));

				$newids[$page['ID']] = $this->add($page);
			}

			$result = count($newids);

			if ($result) {
				tooltip::display(
					__("Pages have been successfully added.")." ",
					TOOLTIP_SUCCESS);

				$form->reset();
			}

			api::callHooks(API_HOOK_AFTER,
				'menuItems::verifyAdmin', $this, $form, $result);

			return $result;
		}

		if (!$form->get('Path')) {
			$path = '';

			if ($form->get('SubMenuItemOfID')) {
				$subitemof = sql::fetch(sql::run(
					" SELECT `Path` FROM `{menuitems}`" .
					" WHERE `ID` = ".(int)$form->get('SubMenuItemOfID')));

				$path .= $subitemof['Path'].'/';
			}

			$path .= url::genPathFromString($form->get('Title'));

			$form->set('Path', $path);
		}

		if ($edit && $form->get('SubMenuItemOfID')) {
			foreach(menuItems::getBackTraceTree($form->get('SubMenuItemOfID')) as $item) {
				if ($item['ID'] == $id) {
					tooltip::display(
						__("Menu item cannot be subitem of itself!"),
						TOOLTIP_ERROR);

					api::callHooks(API_HOOK_AFTER,
						'menuItems::verifyAdmin', $this, $form);

					return false;
				}
			}
		}

		if ($edit) {
			$result = $this->edit($id, $form->getPostArray());

			if ($result)
				tooltip::display(
					__("Menu item has been successfully updated.")." " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'menuItems::verifyAdmin', $this, $form, $result);

			return $result;
		}

		$newid = $this->add($form->getPostArray());

		if ($newid) {
			tooltip::display(
				__("Menu item has been successfully created.")." " .
					"<a href='".url::uri('id, edit, delete') .
						"&amp;id=".$newid."&amp;edit=1#adminform'>" .
						__("Edit") .
					"</a>",
				TOOLTIP_SUCCESS);

			$form->reset();
		}

		api::callHooks(API_HOOK_AFTER,
			'menuItems::verifyAdmin', $this, $form, $newid);

		return $newid;
	}

	function displayAdminListHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayAdminListHeader', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdminListHeader', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Path / Link")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Viewable by")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayAdminListHeader', $this);
	}

	function displayAdminListHeaderOptions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayAdminListHeaderOptions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdminListHeaderOptions', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayAdminListHeaderOptions', $this);
	}

	function displayAdminListHeaderFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayAdminListHeaderFunctions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdminListHeaderFunctions', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayAdminListHeaderFunctions', $this);
	}

	function displayAdminListItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayAdminListItem', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdminListItem', $this, $row, $handled);

			return $handled;
		}

		echo
			"<td>" .
				"<input type='text' name='orders[".$row['ID']."]' " .
					"value='".$row['OrderID']."' " .
					"class='order-id-entry' tabindex='1' />" .
			"</td>" .
			"<td class='auto-width' " .
				($row['Deactivated']?
					"style='text-decoration: line-through;' ":
					null).
				">" .
				"<div " .
					(!$row['SubMenuItemOfID']?
						"class='bold' ":
						null).
					">" .
				$row['Title'] .
				"</div>" .
				"<div class='comment' style='padding-left: 10px;'>" .
				($row['Link']?
					"<a href='".url::generateLink($row['Link']) .
						"' target='_blank'>" .
						$row['Link'] .
						"</a>":
					$row['Path']) .
				"</div>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				"<span class='nowrap'>" .
				($row['ViewableBy']?
					$this->access2Text($row['ViewableBy']):
					null) .
				"</span>" .
			"</td>";

		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayAdminListItem', $this, $row);
	}

	function displayAdminListItemOptions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayAdminListItemOptions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdminListItemOptions', $this, $row, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayAdminListItemOptions', $this, $row);
	}

	function displayAdminListItemFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayAdminListItemFunctions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdminListItemFunctions', $this, $row, $handled);

			return $handled;
		}

		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";

		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayAdminListItemFunctions', $this, $row);
	}

	function displayAdminListFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayAdminListFunctions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdminListFunctions', $this, $handled);

			return $handled;
		}

		echo
			"<input type='submit' name='reordersubmit' value='".
				htmlchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlchars(__("Reset"), ENT_QUOTES)."' class='button' />";

		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayAdminListFunctions', $this);
	}

	function displayAdminListLanguages($language) {
		ob_start();
		$this->displayAdminListItems(0, false, $language);
		$items = ob_get_contents();
		ob_end_clean();

		if (!$items)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayAdminListLanguages', $this, $language);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdminListLanguages', $this, $language, $handled);

			return $handled;
		}

		echo
		"<div tabindex='0' class='fc" .
			form::fcState('fcl'.$language['ID'], true) .
			"'>" .
			"<a class='fc-title' name='fcl".$language['ID']."'>" .
				stripcslashes($language['Title']) .
				(isset($language['Path']) && $language['Path']?
					" (".$language['Path'].")":
					null) .
			"</a>" .
			"<div class='fc-content'>" .
				$items .
			"</div>" .
		"</div>";

		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayAdminListLanguages', $this, $language);

		return true;
	}

	function displayAdminListItems($menuid = 0, $rowpair = false, $language = null) {
		$rows = sql::run(
			" SELECT * FROM `{menuitems}`" .
			" WHERE `MenuID` = '".$this->selectedMenuID."'" .
			((int)$menuid?
				" AND `SubMenuItemOfID` = '".(int)$menuid."'":
				" AND `SubMenuItemOfID` = 0") .
			($language?
				" AND `LanguageID` = '".$language['ID']."'":
				null) .
			" ORDER BY `OrderID`, `ID`");

		if (!sql::rows($rows))
			return false;

		if (!$menuid)
			$handled = api::callHooks(API_HOOK_BEFORE,
				'menuItems::displayAdminListItems', $this, $menuid, $rowpair, $language);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
					'menuItems::displayAdminListItems', $this, $menuid, $rowpair, $language, $handled);

			return $handled;
		}

		if ($menuid) {
			echo
				"<tr".($rowpair?" class='pair'":NULL).">" .
					"<td></td>" .
					"<td colspan='7' class='auto-width nopadding'>";
		}

		echo "<table class='list' cellpadding='0' cellspacing='0'>";

		if (!$menuid) {
			echo
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
		}

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

			$this->displayAdminListItems($row['ID'], $i%2);

			$i++;
		}

		if ($menuid) {
			echo
				"</table>" .
				"</td>" .
				"</tr>";
		} else {
			echo
				"</tbody>" .
				"</table>";
		}

		if (!$menuid)
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdminListItems', $this, $menuid, $rowpair, $language);

		return true;
	}

	function displayAdminList(&$rows) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayAdminList', $this, $rows);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdminList', $this, $rows, $handled);

			return $handled;
		}

		echo
			"<form action='".url::uri('edit, delete')."' method='post'>" .
				"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";

		$itemsfound = false;

		if (sql::rows($rows)) {
			$language['ID'] = 0;
			$language['Title'] = __('No Language Defined');
			$itemsfound = $this->displayAdminListLanguages($language);

		} else {
			$itemsfound = $this->displayAdminListItems(0);
		}

		while($row = sql::fetch($rows)) {
			if ($this->displayAdminListLanguages($row))
				$itemsfound = true;
		}

		if (!$itemsfound)
			tooltip::display(
				__("No menu items found."),
				TOOLTIP_NOTIFICATION);
		else
			echo "<br />";

		if ($itemsfound && $this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			$this->displayAdminListFunctions();

			echo
				"<div class='clear-both'></div>" .
				"<br />";
		}

		echo
			"</form>";

		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayAdminList', $this, $rows);
	}

	function displayAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdminForm', $this, $form, $handled);

			return $handled;
		}

		$form->display();

		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayAdminForm', $this, $form);
	}

	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayAdminTitle', $this, $ownertitle);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdminTitle', $this, $ownertitle, $handled);

			return $handled;
		}

		admin::displayTitle(
			__('Menu Items'),
			$ownertitle);

		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayAdminTitle', $this, $ownertitle);
	}

	function displayAdminDescription($includesnewpages = false) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayAdminDescription', $this, $includesnewpages);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdminDescription', $this, $includesnewpages, $handled);

			return $handled;
		}

		if ($includesnewpages)
			echo
				"<p>" .
					__("New pages will be automatically added to this menu.") .
				"</p>";

		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayAdminDescription', $this, $includesnewpages);
	}

	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdmin', $this, $handled);

			return $handled;
		}

		if (JCORE_VERSION < '0.9') {
			$pages = new pages();
			$pages->userPermissionType = $this->userPermissionType;
			$pages->userPermissionIDs = $this->userPermissionIDs;
			$pages->displayAdmin();
			unset($pages);

			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayAdmin', $this);

			return;
		}

		$delete = null;
		$edit = null;
		$id = null;

		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];

		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];

		if (isset($_GET['id']))
			$id = (int)$_GET['id'];

		$this->selectedMenuID = admin::getPathID();

		$selectedowner = sql::fetch(sql::run(
			" SELECT `Title`, `IncludeNewPages` " .
			" FROM `{menus}` " .
			" WHERE `ID` = '".$this->selectedMenuID."'"));

		$this->displayAdminTitle($selectedowner['Title']);
		$this->displayAdminDescription($selectedowner['IncludeNewPages']);

		echo
			"<div class='admin-content'>";

		if ($delete && $id && empty($_POST['delete'])) {
			$selected = sql::fetch(sql::run(
				" SELECT `Title` FROM `{menuitems}`" .
				" WHERE `ID` = '".$id."'"));

			url::displayConfirmation(
				'<b>'.__('Delete').'?!</b> "'.$selected['Title'].'"');
		}

		$form = new form(
				($edit?
					__("Edit Menu Item"):
					__("New Menu Item")),
				'neweditmenuitem');

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

		foreach(menuItems::getTree($this->selectedMenuID) as $row)
			$form->addValue('SubMenuItemOfID',
				$row['ID'],
				($row['SubMenuItemOfID']?
					str_replace(' ', '&nbsp;',
						str_pad('', $row['PathDeepnes']*4, ' ')).
					"|- ":
					null) .
				$row['Title']);

		$form->groupValues('SubMenuItemOfID', array('0'));

		$languages = languages::get();
		$this->displayAdminList($languages);

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{menuitems}`" .
					" WHERE `ID` = '".$id."'"));

				if ($selected['PageID']) {
					$form->edit(
						'LanguageID',
						null,
						null,
						FORM_INPUT_TYPE_HIDDEN);

					$form->edit(
						'Path',
						null,
						null,
						FORM_INPUT_TYPE_HIDDEN);
				}

				$form->setValues($selected);
			}

			echo
				"<a name='adminform'></a>";

			$this->displayAdminForm($form);
		}

		unset($form);

		echo
			"</div>";	//admin-content

		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayAdmin', $this);
	}

	function add($values) {
		if (!is_array($values))
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::add', $this, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::add', $this, $values, $handled);

			return $handled;
		}

		if (!isset($values['LanguageID']))
			$values['LanguageID'] = null;

		if ($values['OrderID'] == '') {
			$row = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{menuitems}` " .
				" WHERE `MenuID` = '".(int)$values['MenuID']."'" .
				" AND `SubMenuItemOfID` = '" .
					(int)$values['SubMenuItemOfID']."'" .
				" ORDER BY `OrderID` DESC"));

			$values['OrderID'] = (int)$row['OrderID']+1;

		} else {
			sql::run(
				" UPDATE `{menuitems}` SET " .
				" `OrderID` = `OrderID` + 1" .
				" WHERE `MenuID` = '".(int)$values['MenuID']."'" .
				" AND `SubMenuItemOfID` = '" .
					(int)$values['SubMenuItemOfID']."'" .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}

		if ((int)$values['SubMenuItemOfID']) {
			$parentitem = sql::fetch(sql::run(
				" SELECT * FROM `{menuitems}`" .
				" WHERE `ID` = '" .
					(int)$values['SubMenuItemOfID']."'"));

			if ($parentitem['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;

			if ($parentitem['ViewableBy'] && !$values['ViewableBy'])
				$values['ViewableBy'] = (int)$parentitem['ViewableBy'];

			$values['LanguageID'] = $parentitem['LanguageID'];
			$values['MenuID'] = $parentitem['MenuID'];
		}

		$newid = sql::run(
			" INSERT INTO `{menuitems}` SET " .
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			(isset($values['Path'])?
				" `Path` = '".
					sql::escape($values['Path'])."',":
				null) .
			(isset($values['PageID'])?
				" `PageID` = '".
					(int)$values['PageID']."',":
				null) .
			" `Link` = '".
				sql::escape($values['Link'])."'," .
			" `MenuID` = '".
				(int)$values['MenuID']."'," .
			" `LanguageID` = '".
				(int)$values['LanguageID']."'," .
			" `ViewableBy` = '".
				(int)$values['ViewableBy']."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `SubMenuItemOfID` = '".
				(int)$values['SubMenuItemOfID']."'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'");

		if (!$newid)
			tooltip::display(
				sprintf(__("Menu item couldn't be created! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'menuItems::add', $this, $values, $newid);

		return $newid;
	}

	function edit($id, $values) {
		if (!$id)
			return false;

		if (!is_array($values))
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::edit', $this, $id, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::edit', $this, $id, $values, $handled);

			return $handled;
		}

		if (!isset($values['LanguageID']))
			$values['LanguageID'] = null;

		if ((int)$values['SubMenuItemOfID']) {
			$parentitem = sql::fetch(sql::run(
				" SELECT * FROM `{menuitems}`" .
				" WHERE `ID` = '" .
					(int)$values['SubMenuItemOfID']."'"));

			if ($parentitem['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;

			if ($parentitem['ViewableBy'] && !$values['ViewableBy'])
				$values['ViewableBy'] = (int)$parentitem['ViewableBy'];

			$values['LanguageID'] = $parentitem['LanguageID'];
			$values['MenuID'] = $parentitem['MenuID'];
		}

		$item = sql::fetch(sql::run(
			" SELECT * FROM `{menuitems}`" .
			" WHERE `ID` = '".(int)$id."'"));

		sql::run(
			" UPDATE `{menuitems}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			(isset($values['Path'])?
				" `Path` = '".
					sql::escape($values['Path'])."',":
				null) .
			(isset($values['PageID'])?
				" `PageID` = '".
					(int)$values['PageID']."',":
				null) .
			" `Link` = '".
				sql::escape($values['Link'])."'," .
			" `MenuID` = '".
				(int)$values['MenuID']."'," .
			" `LanguageID` = '".
				(int)$values['LanguageID']."'," .
			" `ViewableBy` = '".
				(int)$values['ViewableBy']."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `SubMenuItemOfID` = '".
				(int)$values['SubMenuItemOfID']."'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");

		$result = (sql::affected() != -1);

		if (!$result) {
			tooltip::display(
				sprintf(__("Menu item couldn't be updated! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'menuItems::edit', $this, $id, $values);

			return false;
		}

		foreach(menuItems::getTree($item['MenuID'], (int)$id) as $row) {
			if (!$row['ID'])
				continue;

			$updatesql = null;

			if (($item['Deactivated'] && !$values['Deactivated']) ||
				(!$item['Deactivated'] && $values['Deactivated']))
			{
				if (!$row['Deactivated'] && $values['Deactivated'])
					$updatesql[] = " `Deactivated` = 1";
				if ($row['Deactivated'] && !$values['Deactivated'])
					$updatesql[] = " `Deactivated` = 0";
			}

			if ($item['LanguageID'] != $values['LanguageID'] &&
				$row['LanguageID'] != $values['LanguageID'])
				$updatesql[] = " `LanguageID` = ".(int)$values['LanguageID'];

			if ($item['MenuID'] != $values['MenuID'] &&
				$row['MenuID'] != $values['MenuID'])
				$updatesql[] = " `MenuID` = ".(int)$values['MenuID'];

			if ($item['ViewableBy'] != $values['ViewableBy'] &&
				$row['ViewableBy'] != $values['ViewableBy'])
				$updatesql[] = " `ViewableBy` = '".(int)$values['ViewableBy']."'";

			if ($updatesql)
				sql::run(
					" UPDATE `{menuitems}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
		}

		foreach(menuItems::getBackTraceTree($item['MenuID'], (int)$id) as $row) {
			$updatesql = null;

			if ($row['Deactivated'] && !$values['Deactivated'])
				$updatesql[] = " `Deactivated` = 0";

			if ($row['ViewableBy'] > $values['ViewableBy'])
				$updatesql[] = " `ViewableBy` = '".(int)$values['ViewableBy']."'";

			if ($updatesql)
				sql::run(
					" UPDATE `{menuitems}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
		}

		api::callHooks(API_HOOK_AFTER,
			'menuItems::edit', $this, $id, $values, $result);

		return $result;
	}

	function delete($id) {
		if (!$id)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::delete', $this, $id);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::delete', $this, $id, $handled);

			return $handled;
		}

		$itemids = array($id);

		$item = sql::fetch(sql::run(
			" SELECT * FROM `{menuitems}`" .
			" WHERE `ID` = '".(int)$id."'"));

		foreach(menuItems::getTree($item['MenuID'], (int)$id) as $row) {
			if (!$row['ID'])
				continue;

			$itemids[] = $row['ID'];
		}

		foreach($itemids as $itemid)
			sql::run(
				" DELETE FROM `{menuitems}` " .
				" WHERE `ID` = '".$itemid."'");

		api::callHooks(API_HOOK_AFTER,
			'menuItems::delete', $this, $id);

		return true;
	}

	// ************************************************   Client Part
	static function access2Text($typeid) {
		if ($typeid > 10) {
			$ugroup = userGroups::get($typeid-10);

			if (!$ugroup)
				return false;

			return $ugroup['GroupName'];
		}

		switch($typeid) {
			case MENU_ADMINS_ONLY:
				return __('Admins');
			case MENU_GUESTS_ONLY:
				return __('Guests');
			case MENU_USERS_ONLY:
				return __('Members');
			default:
				return __('Everyone');
		}
	}

	static function isMainMenu($id, $languageid = 0) {
		return pages::isHome($id, $languageid);
	}

	static function getMainMenu($languageid = null) {
		return pages::getHome($languageid);
	}

	static function getMainMenuIDs() {
		return pages::getHomeIDs();
	}

	static function getTree($menuid = 0, $submenuof = 0, $firstcall = true,
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0))
	{
		$rows = sql::run(
			" SELECT * FROM `{menuitems}` " .
			" WHERE `MenuID` = '".$menuid."'" .
			($submenuof?
				" AND `SubMenuItemOfID` = '" .
					$submenuof."'":
				" AND `SubMenuItemOfID` = 0") .
			" ORDER BY `LanguageID`, `OrderID`");

		$arelanguages = false;

		while($row = sql::fetch($rows)) {
			$last = end($tree['Tree']);

			if (!$last || $last['LanguageID'] != $row['LanguageID']) {
				$language = null;

				if ($row['LanguageID'])
					$language = languages::get($row['LanguageID']);

				if ($language)
					$tree['Tree'][] = array(
						'ID' => 0,
						'Title' => $language['Title'],
						'SubMenuItemOfID' => 0,
						'LanguageID' => $language['ID'],
						'PathDeepnes' => 0);

				if (!$last['LanguageID'] && $row['LanguageID'])
					$arelanguages = true;
			}

			$row['PathDeepnes'] = $tree['PathDeepnes'];
			$tree['Tree'][] = $row;

			$tree['PathDeepnes']++;
			menuItems::getTree($menuid, $row['ID'], false, $tree);
			$tree['PathDeepnes']--;
		}

		if ($arelanguages)
			array_unshift($tree['Tree'], array(
				'ID' => 0,
				'Title' => __('No Language Defined'),
				'SubMenuItemOfID' => 0,
				'LanguageID' => 0,
				'PathDeepnes' => 0));

		if ($firstcall)
			return $tree['Tree'];
	}

	static function getBackTraceTree($id, $firstcall = true, $fields = '*',
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0))
	{
		if (!(int)$id)
			return array();

		$row = sql::fetch(sql::run(
			" SELECT ".$fields." FROM `{menuitems}` " .
			" WHERE `ID` = '".(int)$id."'"));

		if (!$row)
			return array();

		if (isset($row['SubMenuItemOfID']) && $row['SubMenuItemOfID']) {
			menuItems::getBackTraceTree(
				$row['SubMenuItemOfID'],
				false, $fields, $tree);
		}

		$row['PathDeepnes'] = $tree['PathDeepnes'];
		$tree['Tree'][] = $row;
		$tree['PathDeepnes']++;

		if ($firstcall)
			return $tree['Tree'];
	}

	function getSelectedMenuIDs($menuid = null) {
		return $this->getSelectedIDs($menuid);
	}

	function getSelectedIDs($menuid = null) {
		if (JCORE_VERSION >= '0.9') {
			if (!pages::$selected)
				return false;

			$items = sql::run(
				" SELECT `ID`, `SubMenuItemOfID`, `Path`, `LanguageID` FROM `{menuitems}`" .
				" WHERE" .
					($menuid?
						" `ID` = '".(int)$menuid."'":
						" '".sql::escape(pages::$selected['Path'])."/' LIKE CONCAT(`Path`,'/%')") .
				" AND `LanguageID` = '".pages::$selected['LanguageID']."'" .
				" AND !`Deactivated`" .
				" ORDER BY `Path` DESC");

			if (!sql::rows($items))
				return false;

			while($item = sql::fetch($items)) {
				$this->selectedIDs[] = $item['ID'];

				if ($item['SubMenuItemOfID'])
					$this->getSelectedIDs($item['SubMenuItemOfID']);
			}

			return $this->selectedIDs;
		}

		if (!$menuid && pages::$selected)
			$menuid = pages::$selected['ID'];

		$row = sql::fetch(sql::run(
			" SELECT `ID`, `".(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')."`" .
				($menuid && pages::$selected && $menuid == pages::$selected['ID']?
					", `Path`," .
						(JCORE_VERSION < '0.9'?
							" `MenuID`,":
							null) .
						" `LanguageID`":
					null) .
			" FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}`" .
			" WHERE `ID` = '".(int)$menuid."'" .
			" LIMIT 1"));

		if (!$row)
			return false;

		$this->selectedIDs[] = $row['ID'];

		if ($row[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')])
			$this->getSelectedIDs($row[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]);

		if ($menuid && pages::$selected && $menuid == pages::$selected['ID']) {
			$aliaspages = sql::fetch(sql::run(
				" SELECT `ID`, `".(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')."`" .
				" FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE '".sql::escape($row['Path'])."/' LIKE CONCAT(`Path`,'/%')" .
				(JCORE_VERSION < '0.9'?
					" AND `MenuID` != '".$row['MenuID']."'":
					null) .
				" AND `LanguageID` = '".$row['LanguageID']."'" .
				" AND `Deactivated` = 0" .
				" ORDER BY `Path` DESC"));

			if ($aliaspages) {
				$this->selectedIDs[] = $aliaspages['ID'];

				if ($aliaspages[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')])
					$this->getSelectedIDs($aliaspages[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]);
			}
		}

		return $this->selectedIDs;
	}

	function generateLink(&$row) {
		$language = languages::$selected;

		if ($row['LanguageID'] && (!$language || $language['ID'] != $row['LanguageID']))
			$language = sql::fetch(sql::run(
				" SELECT `ID`, `Path` FROM `{languages}`" .
				" WHERE `ID` = '".$row['LanguageID']."'"));

		if (SEO_FRIENDLY_LINKS)
			return url::site().
				($language?
					$language['Path'].'/':
					null) .
				$row['Path'];

		return url::site().'index.php?' .
			($language?
				'&amp;languageid='.$language['ID']:
				null) .
			'&amp;pageid='.$row[(JCORE_VERSION >= '0.9'?'PageID':'ID')];
	}

	// For compatibility reasons only, DON'T USE IT!
	static function displayModules($menuid) {
		pages::displayModules($menuid);
	}

	function displayTitle(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayTitle', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayTitle', $this, $row, $handled);

			return $handled;
		}

		echo
			"<a href='".$row['_Link']."'" .
				($row['Link'] && strpos($row['Link'], '://') !== false &&
				 strpos($row['Link'], substr(SITE_URL, strpos(SITE_URL, '://'))) === false?
					" target='_blank'":
					null) .
				">" .
				"<span>" .
				$row['Title'] .
				"</span>" .
			"</a>";

		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayTitle', $this, $row);
	}

	function displaySubmenus(&$row, $container = true) {
		if (JCORE_VERSION >= '0.9') {
			$rows = sql::run(
				" SELECT * FROM `{menuitems}`" .
				" WHERE `Deactivated` = 0" .
				" AND `MenuID` = '".(int)$this->selectedMenuID."'" .
				" AND `LanguageID` = '".
					(languages::$selected?
						(int)languages::$selected['ID']:
						0) .
					"'" .
				" AND `SubMenuItemOfID` = '" .
					(int)$row['ID']."'" .
				" AND (`ViewableBy` = 0 OR " .
					($GLOBALS['USER']->loginok?
						($GLOBALS['USER']->data['Admin']?
							" `ViewableBy` IN (2, 3)":
							" `ViewableBy` = 2") .
						($GLOBALS['USER']->data['GroupID']?
							" OR `ViewableBy` = '".(int)($GLOBALS['USER']->data['GroupID']+10)."'":
							null):
						" `ViewableBy` = 1") .
				" )" .
				" ORDER BY `OrderID`, `ID`");

		} else {
			$rows = sql::run(
				" SELECT * FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `Deactivated` = 0" .
				" AND `Hidden` = 0" .
				" AND `MenuID` = '".(int)$this->selectedMenuID."'" .
				" AND `LanguageID` = '".
					(languages::$selected?
						(int)languages::$selected['ID']:
						0) .
					"'" .
				" AND `".(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')."` = '" .
					(int)$row['ID']."'" .
				" AND (`ViewableBy` = 0 OR " .
					($GLOBALS['USER']->loginok?
						($GLOBALS['USER']->data['Admin']?
							" `ViewableBy` IN (2, 3)":
							" `ViewableBy` = 2"):
						" `ViewableBy` = 1") .
				" )" .
				" ORDER BY `OrderID`, `ID`");
		}

		$i = 1;
		$items = sql::rows($rows);

		if (!$items)
			return;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displaySubmenus', $this, $row, $container);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displaySubmenus', $this, $row, $container, $handled);

			return $handled;
		}

		if ($container)
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

		if ($container)
			echo
			"</" .
			(JCORE_VERSION >= '0.5'?
				'ul':
				'div') .
			">"; //submenu

		api::callHooks(API_HOOK_AFTER,
			'menuItems::displaySubmenus', $this, $row, $container);
	}

	function displayOne(&$row = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::displayOne', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::displayOne', $this, $row, $handled);

			return $handled;
		}

		if ($row['Link'])
			$row['_Link'] = url::generateLink($row['Link']);
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
					(is_array($this->selectedIDs) && in_array($row['ID'], $this->selectedIDs)?
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

		api::callHooks(API_HOOK_AFTER,
			'menuItems::displayOne', $this, $row);
	}

	function display() {
		$this->getSelectedIDs();

		$rows = sql::run(
			$this->SQL());

		$i = 1;
		$items = sql::rows($rows);

		if (!$items)
			return;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'menuItems::display', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menuItems::display', $this, $handled);

			return $handled;
		}

		while ($row = sql::fetch($rows)) {
			$row['_CSSClass'] = null;

			if ($i == 1)
				$row['_CSSClass'] .= ' first';
			if ($i == $items)
				$row['_CSSClass'] .= ' last';

			$this->displayOne($row);

			$i++;
		}

		api::callHooks(API_HOOK_AFTER,
			'menuItems::display', $this);
	}
}

?>