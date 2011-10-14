<?php

/***************************************************************************
 *            layouts.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

class _layouts {
	var $adminPath = 'admin/site/blocks/layouts';
	
	// ************************************************   Admin Part
	function countAdminItems() {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::countAdminItems', $this);
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{layouts}`" .
			" LIMIT 1"));
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::countAdminItems', $this, $row['Rows']);
		
		return $row['Rows'];
	}
	
	function setupAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::setupAdmin', $this);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Layout'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Layout Blocks'), 
			'?path=admin/site/blocks');
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=' .
			(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::setupAdmin', $this);
	}
	
	function setupAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::setupAdminForm', $this, $form);
		
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 250px;');
		
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
			'layouts::setupAdminForm', $this, $form);
	}
	
	function verifyAdmin(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::verifyAdmin', $this, $form);
		
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
					'layouts::verifyAdmin', $this, $form);
				return false;
			}
			
			foreach((array)$orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{layouts}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'");
			}
			
			tooltip::display(
				__("Layouts have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'layouts::verifyAdmin', $this, $form, $reorder);
			
			return true;
		}
		
		if ($delete && $id) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'layouts::verifyAdmin', $this, $form);
				return false;
			}
			
			$result = $this->delete($id);
			
			if ($result)
				tooltip::display(
					__("Layout has been successfully deleted."),
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'layouts::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'layouts::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if ($edit) {
			$result = $this->edit($id, $form->getPostArray());
			
			if ($result)
				tooltip::display(
					__("Layout has been successfully updated.")." " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'layouts::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		$newid = $this->add($form->getPostArray());
		
		if ($newid) {
			tooltip::display(
				__("Layout has been successfully created.")." " .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$newid."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
				
			$form->reset();
		}
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::verifyAdmin', $this, $form, $newid);
		
		return $newid;
	}
	
	function displayAdminListHeader() {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::displayAdminListHeader', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::displayAdminListHeader', $this);
	}
	
	function displayAdminListHeaderOptions() {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::displayAdminListHeaderOptions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Blocks")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::displayAdminListHeaderFunctions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::displayAdminListItem', $this, $row);
		
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
				"<div class='bold'>" .
					$row['Title'] .
				"</div>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListItemOptions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::displayAdminListItemOptions', $this, $row);
		
		$blocks = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{blocks}`" .
			" WHERE `LayoutID` = '".$row['ID']."'"));
		
		echo
			"<td align='center'>" .
				"<a class='admin-link blocks' " .
					"title='".htmlspecialchars(__("Blocks"), ENT_QUOTES) .
					" (".$blocks['Rows'].")' " .
					"href='?path=admin/site/blocks#fcbl".$row['ID']."'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $blocks['Rows'])
			counter::display($blocks['Rows']);
		
		echo
				"</a>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::displayAdminListItemFunctions', $this, $row);
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminListFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::displayAdminListFunctions', $this);
		
		echo 
			"<input type='submit' name='reordersubmit' value='".
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::displayAdminListFunctions', $this);
	}
	
	function displayAdminList(&$rows) {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::displayAdminList', $this, $rows);
		
		echo
			"<form action='".url::uri('edit, delete')."' method='post'>" .
				"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";
		
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			$this->displayAdminListFunctions();
			
			echo
				"<div class='clear-both'></div>" .
				"<br />";
		}
				
		echo
			"</form>";
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::displayAdminList', $this, $rows);
	}
	
	function displayAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::displayAdminForm', $this, $form);
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::displayAdminTitle', $this, $ownertitle);
		
		admin::displayTitle(
			__('Layouts Administration'),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::displayAdminDescription', $this);
		api::callHooks(API_HOOK_AFTER,
			'layouts::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'layouts::displayAdmin', $this);
		
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
				" SELECT `Title` FROM `{layouts}`" .
				" WHERE `ID` = '".$id."'"));
			
			security::displayConfirmation(
				'<b>'.__('Delete').'?!</b> "'.$selected['Title'].'"');
		}
		
		$form = new form(
				($edit?
					__("Edit Layout"):
					__("New Layout")),
				'neweditlayout');
		
		if (!$edit)
			$form->action = url::uri('id, delete');
					
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
			" SELECT * FROM `{layouts}`" .
			" WHERE `TemplateID` = '".
				(template::$selected?
					(int)template::$selected['ID']:
					0)."'" .
			" ORDER BY `OrderID`, `ID`");
			
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No layouts found."),
				TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{layouts}`" .
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
			'layouts::displayAdmin', $this);
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'layouts::add', $this, $values);
		
		if ($values['OrderID'] == '') {
			$row = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{layouts}` " .
				" ORDER BY `OrderID` DESC"));
			
			$values['OrderID'] = (int)$row['OrderID']+1;
			
		} else {
			sql::run(
				" UPDATE `{layouts}` SET " .
				" `OrderID` = `OrderID` + 1" .
				" WHERE `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		$newid = sql::run(
			" INSERT INTO `{layouts}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `TemplateID` = '".
				(template::$selected?
					(int)template::$selected['ID']:
					0)."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
		
		if (!$newid)
			tooltip::display(
				sprintf(__("Layout couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::add', $this, $values, $newid);
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'layouts::edit', $this, $id, $values);
		
		sql::run(
			" UPDATE `{layouts}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");
		
		$result = (sql::affected() != -1);
		
		if (!$result)
			tooltip::display(
				sprintf(__("Layout couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::edit', $this, $id, $values, $result);
		
		return $result;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'layouts::delete', $this, $id);
		
		sql::run(
			" DELETE FROM `{layouts}` " .
			" WHERE `ID` = '".(int)$id."'");
		
		sql::run(
			" DELETE FROM `{blocks}` " .
			" WHERE `LayoutID` = '".(int)$id."'");
		
		api::callHooks(API_HOOK_AFTER,
			'layouts::delete', $this, $id);
		
		return true;
	}
	
	// ************************************************   Client Part
	static function get($id = null) {
		if ($id)
			return sql::fetch(sql::run(
				" SELECT * FROM `{layouts}`" .
				" WHERE `ID` = '".(int)$id."'" .
				" AND `Deactivated` = 0" .
				" AND `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0)."'"));
		
		return sql::run(
			" SELECT * FROM `{layouts}`" .
			" WHERE `Deactivated` = 0" .
			" AND `TemplateID` = '".
				(template::$selected?
					(int)template::$selected['ID']:
					0)."'" .
			" ORDER BY `OrderID`, `ID`");
	}
	
	static function exists($id = null) {
		if (!$id)
			return true;
		
		return sql::rows(sql::run(
			" SELECT `ID` FROM `{layouts}`" .
			" WHERE `ID` = '".(int)$id."'" .
			" AND `Deactivated` = 0" .
			" AND `TemplateID` = '".
				(template::$selected?
					(int)template::$selected['ID']:
					0)."'"));
	}
}

?>