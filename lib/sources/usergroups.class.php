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
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{usergroups}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
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
	}
	
	function setupAdminForm(&$form) {
		$edit = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		$form->add(
			__('Group Name'),
			'GroupName',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 200px;');
		$form->setTooltipText(__("e.g. Editor"));
	}
	
	function verifyAdmin(&$form) {
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
		
			tooltip::display(
				__("Group has been successfully deleted."),
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray()))
				return false;
			
			tooltip::display(
				__("Group has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if (!$newid = $this->add($form->getPostArray())) 
			return false;
		
		tooltip::display(
			__("Group has been successfully created.")." ".
			"<a href='".url::uri('id, edit, delete') .
				"&amp;id=".$newid."&amp;edit=1#adminform'>" .
				__("Edit") .
			"</a>",
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				__("Group")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
		echo
			"<th><span class='nowrap'>".
				__("Users")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Permissions")."</span></th>";
	}
	
	function displayAdminListHeaderFunctions() {
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
		echo
			"<td class='auto-width'>" .
				"<div class='bold'>".
					$row['GroupName'] .
				"</div>" .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
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
	}
	
	function displayAdminListItemFunctions(&$row) {
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
	}
	
	function displayAdminList(&$rows) {
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
		
		echo
			"</form>";
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('User Groups Administration'),
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
				" ORDER BY `GroupName`, `ID`");
		
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
			
		$newid = sql::run(
			" INSERT INTO `{usergroups}` SET " .
			" `GroupName` = '".
				sql::escape($values['GroupName'])."'");
		
		if (!$newid) {
			tooltip::display(
				sprintf(__("Group couldn't be created! Error: %s"), 
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
			
		sql::run(
			" UPDATE `{usergroups}` SET ".
			" `GroupName` = '".
				sql::escape($values['GroupName'])."'" .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Group couldn't be updated! Error: %s"), 
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
			" DELETE FROM `{usergroups}`" .
			" WHERE `ID` = '".(int)$id."'");
		
		return true;
	}
	
	// ************************************************   Client Part
	static function get($id = null) {
		if ((int)$id)
			return sql::fetch(sql::run(
				" SELECT * FROM `{usergroups}`" .
				" WHERE `ID` = '".(int)$id."'"));
		
		return sql::run(
			" SELECT * FROM `{usergroups}`" .
			" ORDER BY `GroupName`, `ID`");
	}
}

?>