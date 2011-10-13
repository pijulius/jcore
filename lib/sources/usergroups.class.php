<?php

/***************************************************************************
 *            usergroups.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

class _userGroups {
	var $adminPath = 'admin/members/usergroups';
	
	// ************************************************   Admin Part
	function countAdminItems() {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::countAdminItems', $this);
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{usergroups}`" .
			" LIMIT 1"));
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::countAdminItems', $this, $row['Rows']);
		
		return $row['Rows'];
	}
	
	function setupAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::setupAdmin', $this);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Group'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Users'), 
			'?path=admin/members/users');
		favoriteLinks::add(
			__('Settings'), 
			'?path=admin/site/settings');
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::setupAdmin', $this);
	}
	
	function setupAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::setupAdminForm', $this, $form);
		
		$edit = null;
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		$form->add(
			__('Group Name'),
			'GroupName',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 200px;');
		$form->setTooltipText(__("e.g. Editor"));
		
		$form->add(
			__('Priority'),
			'Priority',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::setupAdminForm', $this, $form);
	}
	
	function verifyAdmin(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::verifyAdmin', $this, $form);
		
		$setprioroties = null;
		$priorities = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_POST['prioritysubmit']))
			$setprioroties = (string)$_POST['prioritysubmit'];
		
		if (isset($_POST['priorities']))
			$priorities = (array)$_POST['priorities'];
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($setprioroties) {
			foreach((array)$priorities as $pid => $pvalue) {
				sql::run(
					" UPDATE `{usergroups}` " .
					" SET `Priority` = '".(int)$pvalue."'" .
					" WHERE `ID` = '".(int)$pid."'");
			}
			
			tooltip::display(
				__("Groups have been successfully updated."),
				TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'userGroups::verifyAdmin', $this, $form, $setprioroties);
			
			return true;
		}
		
		if ($delete) {
			$result = $this->delete($id);
			
			if ($result)
				tooltip::display(
					__("Group has been successfully deleted."),
					TOOLTIP_SUCCESS);
				
			api::callHooks(API_HOOK_AFTER,
				'userGroups::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'userGroups::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if ($edit) {
			$result = $this->edit($id, $form->getPostArray());
			
			if ($result)
				tooltip::display(
					__("Group has been successfully updated.")." " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'userGroups::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		$newid = $this->add($form->getPostArray()); 
		
		if ($newid) {
			tooltip::display(
				__("Group has been successfully created.")." ".
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$newid."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			$form->reset();
		}
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::verifyAdmin', $this, $form, $newid);
		
		return $newid;
	}
	
	function displayAdminListHeader() {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::displayAdminListHeader', $this);
		
		if (JCORE_VERSION >= '1.0')
			echo
				"<th><span class='nowrap'>".
					__("Priority")."</span></th>";
		
		echo
			"<th><span class='nowrap'>".
				__("Group")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::displayAdminListHeader', $this);
	}
	
	function displayAdminListHeaderOptions() {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::displayAdminListHeaderOptions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Users")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Permissions")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::displayAdminListHeaderFunctions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::displayAdminListItem', $this, $row);
		
		if (JCORE_VERSION >= '1.0')
			echo
				"<td>" .
					"<input type='text' name='priorities[".$row['ID']."]' " .
						"value='".$row['Priority']."' " .
						"class='order-id-entry' tabindex='1' />" .
				"</td>";
		
		echo
			"<td class='auto-width'>" .
				"<div class='bold'>".
					$row['GroupName'] .
				"</div>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListItemOptions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::displayAdminListItemOptions', $this, $row);
		
		$users = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{users}`" .
			" WHERE `GroupID` = '".$row['ID']."'" .
			" LIMIT 1"));
		
		$permissions = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{usergrouppermissions}`" .
			" WHERE `GroupID` = '".$row['ID']."'" .
			" LIMIT 1"));
		
		echo
			"<td align='center'>" .
				"<a class='admin-link users' " .
					"title='".htmlspecialchars(__("Users"), ENT_QUOTES) .
					" (".$users['Rows'].")' " .
					"href='".url::uri('ALL') .
					"?path=admin/members/users" .
					"&amp;searchgroupid=".$row['ID']."'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $users['Rows'])
			counter::display($users['Rows']);
		
		echo
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link permissions' " .
					"title='".htmlspecialchars(__("Permissions"), ENT_QUOTES) .
					" (".$permissions['Rows'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/usergrouppermissions'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $permissions['Rows'])
			counter::display($permissions['Rows']);
		
		echo
				"</a>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::displayAdminListItemFunctions', $this, $row);
		
		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='" .url::uri('id, edit, delete') .
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
			'userGroups::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminListFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::displayAdminListFunctions', $this);
		
		echo
			"<input type='submit' name='prioritysubmit' value='".
				htmlspecialchars(__("Set Priorities"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::displayAdminListFunctions', $this);
	}
	
	function displayAdminList(&$rows) {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::displayAdminList', $this, $rows);
		
		echo 
			"<form action='".url::uri('edit, delete')."' method='post'>" .
			"<table cellpadding='0' cellspacing='0' class='list'>" .
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
		
		if (JCORE_VERSION >= '1.0' &&
			$this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			$this->displayAdminListFunctions();
			
			echo
				"<div class='clear-both'></div>" .
				"<br />";
		}
				
		echo
			"</form>";
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::displayAdminList', $this, $rows);
	}
	
	function displayAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::displayAdminForm', $this, $form);
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::displayAdminTitle', $this, $ownertitle);
		
		admin::displayTitle(
			__('User Groups Administration'),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::displayAdminDescription', $this);
		api::callHooks(API_HOOK_AFTER,
			'userGroups::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::displayAdmin', $this);
		
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
				
		$form = new form(
				($edit?
					__("Edit Group"):
					__("New Group")),
				'neweditusergroup');
		
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
				" SELECT * FROM `{usergroups}`" .
				" ORDER BY" .
				(JCORE_VERSION >= '1.0'?
					" `Priority`,":
					null) .
				" `GroupName`, `ID`");
		
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No groups found."),
				TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{usergroups}`" .
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
			'userGroups::displayAdmin', $this);
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		$exists = sql::fetch(sql::run(
			" SELECT `ID` FROM `{usergroups}` " .
			" WHERE `GroupName` = '".
				sql::escape($values['GroupName'])."'" .
			" LIMIT 1"));
			
		if ($exists) {
			tooltip::display(
				sprintf(__("Group \"%s\" already exists."),
					$values['GroupName']),
				TOOLTIP_ERROR);
			return false;
		}
			
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::add', $this, $values);
		
		$newid = sql::run(
			" INSERT INTO `{usergroups}` SET " .
			(JCORE_VERSION >= '1.0'?
				" `Priority` = '".(int)$values['Priority']."',":
				null) .
			" `GroupName` = '".
				sql::escape($values['GroupName'])."'");
		
		if (!$newid)
			tooltip::display(
				sprintf(__("Group couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::add', $this, $values, $newid);
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		$exists = sql::fetch(sql::run(
			" SELECT `ID` FROM `{usergroups}` " .
			" WHERE `GroupName` = '".
				sql::escape($values['GroupName'])."'" .
			" AND `ID` != '".(int)$id."'" .
			" LIMIT 1"));
			
		if ($exists) {
			tooltip::display(
				sprintf(__("Group \"%s\" already exists."),
					$values['Path']),
				TOOLTIP_ERROR);
			return false;
		}
			
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::edit', $this, $id, $values);
		
		sql::run(
			" UPDATE `{usergroups}` SET ".
			(JCORE_VERSION >= '1.0'?
				" `Priority` = '".(int)$values['Priority']."',":
				null) .
			" `GroupName` = '".
				sql::escape($values['GroupName'])."'" .
			" WHERE `ID` = '".(int)$id."'");
			
		$result = (sql::affected() != -1);
		
		if (!$result)
			tooltip::display(
				sprintf(__("Group couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::edit', $this, $id, $values, $result);
		
		return $result;
	}
	
	function delete($id) {
		if (!$id)
			return false;
			
		api::callHooks(API_HOOK_BEFORE,
			'userGroups::delete', $this, $id);
		
		sql::run(
			" DELETE FROM `{usergroups}`" .
			" WHERE `ID` = '".(int)$id."'");
		
		api::callHooks(API_HOOK_AFTER,
			'userGroups::delete', $this, $id);
		
		return true;
	}
	
	// ************************************************   Client Part
	static function get($id = null, $accesstogroupids = false) {
		if ((int)$id) {
			if ($accesstogroupids) {
				if (JCORE_VERSION < '1.0')
					return array($id);
				 
				$group = sql::fetch(sql::run(
					" SELECT `Priority` FROM `{usergroups}`" .
					" WHERE `ID` = '".(int)$id."'"));
				
				if (!$group)
					return false;
				
				$group = sql::fetch(sql::run(
					" SELECT GROUP_CONCAT(`ID` SEPARATOR ',') AS `GroupIDs` FROM `{usergroups}`" .
					" WHERE `Priority` >= '".$group['Priority']."'"));
				
				return explode(',', $group['GroupIDs']);
			}
			
			return sql::fetch(sql::run(
				" SELECT * FROM `{usergroups}`" .
				" WHERE `ID` = '".(int)$id."'"));
		}
		
		return sql::run(
			" SELECT * FROM `{usergroups}`" .
			" ORDER BY `GroupName`, `ID`");
	}
}

?>