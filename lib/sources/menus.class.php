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
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::menus', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::menus', $this, $handled);

			return $handled;
		}

		if (isset($_GET['languageid']))
			$this->selectedLanguageID = (int)$_GET['languageid'];

		api::callHooks(API_HOOK_AFTER,
			'menus::menus', $this);
	}

	function SQL() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::SQL', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::SQL', $this, $handled);

			return $handled;
		}

		$sql =
			" SELECT * FROM `{menus}` " .
			($this->selectedBlockID?
				" WHERE " .
					(JCORE_VERSION >= '1.0'?
						"`BlockIDs` REGEXP '(^|\\\|)".$this->selectedBlockID."(\\\||$)'":
						"`BlockID` = '".$this->selectedBlockID."'"):
				null) .
			" ORDER BY" .
			(JCORE_VERSION >= '0.7'?
				" `OrderID`,":
				null) .
			" `ID`";

		api::callHooks(API_HOOK_AFTER,
			'menus::SQL', $this);

		return $sql;
	}

	// ************************************************   Admin Part
	function countAdminItems() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::countAdminItems', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::countAdminItems', $this, $handled);

			return $handled;
		}

		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{menus}`" .
			" LIMIT 1"));

		api::callHooks(API_HOOK_AFTER,
			'menus::countAdminItems', $this, $row['Rows']);

		return $row['Rows'];
	}

	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::setupAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::setupAdmin', $this, $handled);

			return $handled;
		}

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

		api::callHooks(API_HOOK_AFTER,
			'menus::setupAdmin', $this);
	}

	function setupAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::setupAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::setupAdminForm', $this, $form, $handled);

			return $handled;
		}

		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 250px;');

		if (JCORE_VERSION >= '1.0') {
			$form->add(
				__('Display In Block(s)'),
				'BlockIDs',
				FORM_INPUT_TYPE_MULTISELECT);

			$form->setValueType(FORM_VALUE_TYPE_ARRAY);
			$form->setStyle('min-width: 350px; height: 150px;');

		} else {
			$form->add(
				__('In Block'),
				'BlockID',
				FORM_INPUT_TYPE_SELECT);

			$form->setValueType(FORM_VALUE_TYPE_INT);
			$form->addValue('', '');
		}

		$blockids = array();
		$layoutids = array();
		$disabledblocks = array();

		$menublocks = sql::run(
			" SELECT `ID`, `SubBlockOfID`" .
			(JCORE_VERSION >= '0.9'?
				", `LayoutID`":
				null) .
			" FROM `{blocks}`" .
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
				(JCORE_VERSION >= '1.0'?'BlockIDs':'BlockID'),
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

		api::callHooks(API_HOOK_AFTER,
			'menus::setupAdminForm', $this, $form);
	}

	function verifyAdmin(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::verifyAdmin', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::verifyAdmin', $this, $form, $handled);

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

		if (JCORE_VERSION >= '0.7' && $reorder) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'menus::verifyAdmin', $this, $form);
				return false;
			}

			foreach((array)$orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{menus}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'");
			}

			tooltip::display(
				__("Menus have been successfully re-ordered."),
				TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'menus::verifyAdmin', $this, $form, $reorder);

			return true;
		}

		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'menus::verifyAdmin', $this, $form);
				return false;
			}

			$result = $this->delete($id);

			if ($result)
				tooltip::display(
					__("Menu has been successfully deleted."),
					TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'menus::verifyAdmin', $this, $form, $result);

			return $result;
		}

		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'menus::verifyAdmin', $this, $form);

			return false;
		}

		if (!$form->get('Name'))
			$form->set('Name', url::genPathFromString($form->get('Title')));

		if ($edit) {
			$result = $this->edit($id, $form->getPostArray());

			if ($result)
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

			api::callHooks(API_HOOK_AFTER,
				'menus::verifyAdmin', $this, $form, $result);

			return $result;
		}

		$newid = $this->add($form->getPostArray());

		if ($newid) {
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
		}

		api::callHooks(API_HOOK_AFTER,
			'menus::verifyAdmin', $this, $form, $newid);

		return $newid;
	}

	function displayAdminListHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayAdminListHeader', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayAdminListHeader', $this, $handled);

			return $handled;
		}

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

		api::callHooks(API_HOOK_AFTER,
			'menus::displayAdminListHeader', $this);
	}

	function displayAdminListHeaderOptions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayAdminListHeaderOptions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayAdminListHeaderOptions', $this, $handled);

			return $handled;
		}

		if (JCORE_VERSION >= '0.9')
			echo
				"<th><span class='nowrap'>".
					__("Menu Items")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'menus::displayAdminListHeaderOptions', $this);
	}

	function displayAdminListHeaderFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayAdminListHeaderFunctions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayAdminListHeaderFunctions', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'menus::displayAdminListHeaderFunctions', $this);
	}

	function displayAdminListItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayAdminListItem', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayAdminListItem', $this, $row, $handled);

			return $handled;
		}

		$blockroute = null;
		$blockids = explode('|', $row[(JCORE_VERSION >= '1.0'?'BlockIDs':'BlockID')]);

		foreach($blockids as $blockid) {
			foreach(blocks::getBackTraceTree($blockid) as $block)
				$blockroute .=
					"<div class='nowrap".
						($block['ID'] != $blockid?
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
		}

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

		api::callHooks(API_HOOK_AFTER,
			'menus::displayAdminListItem', $this, $row);
	}

	function displayAdminListItemOptions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayAdminListItemOptions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayAdminListItemOptions', $this, $row, $handled);

			return $handled;
		}

		if (JCORE_VERSION >= '0.9') {
			$items = sql::fetch(sql::run(
				" SELECT COUNT(*) AS `Rows`" .
				" FROM `{menuitems}`" .
				" WHERE `MenuID` = '".$row['ID']."'" .
				" LIMIT 1"));

			echo
				"<td align='center'>" .
					"<a class='admin-link menu-items' " .
						"title='".htmlchars(__("Menu Items"), ENT_QUOTES) .
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

		api::callHooks(API_HOOK_AFTER,
			'menus::displayAdminListItemOptions', $this, $row);
	}

	function displayAdminListItemFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayAdminListItemFunctions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayAdminListItemFunctions', $this, $row, $handled);

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
			'menus::displayAdminListItemFunctions', $this, $row);
	}

	function displayAdminListFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayAdminListFunctions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayAdminListFunctions', $this, $handled);

			return $handled;
		}

		echo
			"<input type='submit' name='reordersubmit' value='".
				htmlchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlchars(__("Reset"), ENT_QUOTES)."' class='button' />";

		api::callHooks(API_HOOK_AFTER,
			'menus::displayAdminListFunctions', $this);
	}

	function displayAdminList(&$rows) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayAdminList', $this, $rows);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayAdminList', $this, $rows, $handled);

			return $handled;
		}

		if (JCORE_VERSION >= '0.7') {
			echo
				"<form action='".url::uri('edit, delete')."' method='post'>" .
					"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";
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

		api::callHooks(API_HOOK_AFTER,
			'menus::displayAdminList', $this, $rows);
	}

	function displayAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayAdminForm', $this, $form, $handled);

			return $handled;
		}

		$form->display();

		api::callHooks(API_HOOK_AFTER,
			'menus::displayAdminForm', $this, $form);
	}

	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayAdminTitle', $this, $ownertitle);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayAdminTitle', $this, $ownertitle, $handled);

			return $handled;
		}

		admin::displayTitle(
			__('Menus Administration'),
			$ownertitle);

		api::callHooks(API_HOOK_AFTER,
			'menus::displayAdminTitle', $this, $ownertitle);
	}

	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayAdminDescription', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayAdminDescription', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'menus::displayAdminDescription', $this);
	}

	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayAdmin', $this, $handled);

			return $handled;
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

		$this->displayAdminTitle();
		$this->displayAdminDescription();

		echo
			"<div class='admin-content'>";

		if ($delete && $id && empty($_POST['delete'])) {
			$selected = sql::fetch(sql::run(
				" SELECT `Title` FROM `{menus}`" .
				" WHERE `ID` = '".$id."'"));

			url::displayConfirmation(
				'<b>'.__('Delete').'?!</b> "'.$selected['Title'].'"');
		}

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

		api::callHooks(API_HOOK_AFTER,
			'menus::displayAdmin', $this);
	}

	function add($values) {
		if (!is_array($values))
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::add', $this, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::add', $this, $values, $handled);

			return $handled;
		}

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
			(JCORE_VERSION >= '1.0'?
				" `BlockIDs` = '".
					sql::escape(implode('|', (array)$values['BlockIDs']))."'":
				" `BlockID` = '".
					(int)$values['BlockID']."'"));

		if (!$newid)
			tooltip::display(
				sprintf(__("Menu couldn't be created! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'menus::add', $this, $values, $newid);

		return $newid;
	}

	function edit($id, $values) {
		if (!$id)
			return false;

		if (!is_array($values))
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::edit', $this, $id, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::edit', $this, $id, $values, $handled);

			return $handled;
		}

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
			(JCORE_VERSION >= '1.0'?
				" `BlockIDs` = '".
					sql::escape(implode('|', (array)$values['BlockIDs']))."'":
				" `BlockID` = '".
					(int)$values['BlockID']."'") .
			" WHERE `ID` = '".(int)$id."'");

		$result = (sql::affected() != -1);

		if (!$result)
			tooltip::display(
				sprintf(__("Menu couldn't be updated! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'menus::edit', $this, $id, $values, $result);

		return $result;
	}

	function delete($id) {
		if (!$id)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::delete', $this, $id);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::delete', $this, $id, $handled);

			return $handled;
		}

		sql::run(
			" DELETE FROM `{menus}` " .
			" WHERE `ID` = '".(int)$id."'");

		if (JCORE_VERSION >= '0.9')
			sql::run(
				" DELETE FROM `{menuitems}`" .
				" WHERE `MenuID` = '".(int)$id."'");

		api::callHooks(API_HOOK_AFTER,
			'menus::delete', $this, $id);

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
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayItems', $this, $row, $language, $menuitem);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayItems', $this, $row, $language, $menuitem, $handled);

			return $handled;
		}

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

		api::callHooks(API_HOOK_AFTER,
			'menus::displayItems', $this, $row, $language, $menuitem);
	}

	function displayOne(&$row, $language = null, $menuitem = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayOne', $this, $row, $language, $menuitem);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayOne', $this, $row, $language, $menuitem, $handled);

			return $handled;
		}

		echo
			"<" .
			(IE_BROWSER < 9?
				"div":
				"nav") .
				(JCORE_VERSION >= '0.5' && $this->arguments?
					" class":
					" id") .
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
			"</" .
			(IE_BROWSER < 9?
				"div":
				"nav") .
			">";

		api::callHooks(API_HOOK_AFTER,
			'menus::displayOne', $this, $row, $language, $menuitem);
	}

	function displayArguments() {
		if (!$this->arguments)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::displayArguments', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayArguments', $this, $handled);

			return $handled;
		}

		if (preg_match('/(^|\/)selected($|\/)/', $this->arguments)) {
			if (pages::$selected) {
				if (SEO_FRIENDLY_LINKS)
					echo pages::$selected['Path'];
				else
					echo pages::$selected['ID'];
			}

			api::callHooks(API_HOOK_AFTER,
				'menus::displayArguments', $this, pages::$selected);

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

		if (!$row) {
			api::callHooks(API_HOOK_AFTER,
				'menus::displayArguments', $this);

			return true;
		}

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

				api::callHooks(API_HOOK_AFTER,
					'menus::displayArguments', $this, $row);

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
							" OR `ViewableBy` = '".(int)($GLOBALS['USER']->data['GroupID']+10)."'":
							null):
						" `ViewableBy` = 1") .
				" )" .
				" ORDER BY" .
				(JCORE_VERSION >= '0.9'?
					" `SubMenuItemOfID`,":
					null) .
				" `OrderID`, `ID`" .
				" LIMIT 1"));

			if ($menuitem)
				$this->displayOne($row, $language, $menuitem);

			api::callHooks(API_HOOK_AFTER,
				'menus::displayArguments', $this, $menuitem);

			return true;
		}

		$this->displayOne($row);

		api::callHooks(API_HOOK_AFTER,
			'menus::displayArguments', $this, $row);

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

		if (!sql::rows($rows))
			return;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'menus::display', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'menus::display', $this, $handled);

			return $handled;
		}

		while($row = sql::fetch($rows))
			$this->displayOne($row);

		api::callHooks(API_HOOK_AFTER,
			'menus::display', $this);
	}
}

?>