<?php

/***************************************************************************
 *            userpermissions.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
define('USER_PERMISSION_TYPE_READ', 1);
define('USER_PERMISSION_TYPE_WRITE', 2);
 
class _userPermissions {
	var $ajaxRequest = null;
	var $adminPath = 'admin/members/users/userpermissions';
	
	// ************************************************   Admin Part
	function setupAdminForm(&$form) {
		$edit = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		$form->add(
			'UserID',
			'UserID',
			FORM_INPUT_TYPE_HIDDEN,
			true,
			admin::getPathID());
		$form->setValueType(FORM_VALUE_TYPE_INT);
					
		if (!$edit) {
			$form->add(
				__('Path'),
				'Path',
				FORM_INPUT_TYPE_HIDDEN);
			
			$form->add(
				__('Path'),
				'Paths[]',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 300px;');
			$form->setValueType(FORM_VALUE_TYPE_ARRAY);
			
			if (JCORE_VERSION >= '0.6')
				$form->setTooltipText(__("e.g. admin/content/menuitems/1/posts"));
			else
				$form->addAdditionalText(
					" (".__("e.g. admin/content/menuitems/1/posts").")");
			
			$form->add(
				"<div class='form-entry-user-multi-permission-paths-container'></div>" .
				"<div class='form-entry-title'></div>" .
				"<div class='form-entry-content'>" .
					"<a href='".url::uri('request, sections') .
						"&amp;request=".$this->adminPath .
						"&amp;sections=1' " .
						"class='select-link ajax-content-link'>" .
						__("Select path") .
					"</a> &nbsp; " .
					"<a href='javascript://' class='add-link' " .
						"onclick=\"jQuery.jCore.form.appendEntryTo(" .
							"'.form-entry-user-multi-permission-paths-container', " .
							"'', " .
							"'Paths[]', " .
							FORM_INPUT_TYPE_TEXT."," .
							"false, '', 'style=\'width: 300px;\'');\">" .
						__("Add another path") .
					"</a>" .
				"</div>",
				null,
				FORM_STATIC_TEXT);
			
		} else {
			$form->add(
				__('Path'),
				'Path',
				FORM_INPUT_TYPE_TEXT,
				true);
			$form->setStyle('width: 300px;');
			
			$form->addAdditionalText(
					"<a href='".url::uri('request, sections') .
						"&amp;request=".$this->adminPath .
						"&amp;sections=1' " .
						"class='select-link ajax-content-link'>" .
						__("Select path") .
					"</a>",
				null,
				FORM_STATIC_TEXT);
			
			if (JCORE_VERSION >= '0.6')
				$form->setTooltipText(__("e.g. admin/content/menuitems/1/posts"));
			else
				$form->addAdditionalText(
					" (".__("e.g. admin/content/menuitems/1/posts").")");
		}
			
		$form->add(
			__('Permission'),
			'PermissionTypeID',
			FORM_INPUT_TYPE_SELECT,
			true);
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		$form->addValue(
			2, $this->type2Text(2));
		$form->addValue(
			1, $this->type2Text(1));
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Permission'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Users'), 
			'?path=admin/members/users');
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
				__("Permission has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if (!$edit && !count($form->get('Paths'))) {
			tooltip::display(
				__("No paths have been defined! " .
					"Please define at least one path to set permission to."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if ($edit) {
			$form->set('Path', 
				str_replace(' ', '', trim($form->get('Path'), ' /')));
			
			if (!$this->edit($id, $form->getPostArray()))
				return false;
				
			tooltip::display(
				__("Permission has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		
		$paths = $form->get('Paths');
		$successpaths = null;
		$failedpaths = null;
		
		foreach($paths as $path) {
			$form->set('Path', $path);
			
			if (!$this->add($form->getPostArray())) {
				$failedpaths[] = $path;
				continue;
			}
			
			$successpaths[] = $path;
		}
		
		if ($failedpaths && count($failedpaths)) {
			tooltip::display(
				sprintf(__("There were problems adding some of the permissions you defined. " .
					"The following permissions couldn't be added: %s."),
					implode(', ', $failedpaths)),
				TOOLTIP_ERROR);
			
			if (!$failedpaths || !count($successpaths))
				return false;
		}
		
		tooltip::display(
			sprintf(__("Permission(s) have been successfully added. " .
				"The following permissions have been added: %s."),
				implode(', ', $successpaths)),
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminSections() {
		echo
			"<div class='user-permissions-admin-sections'>" .
				"<div class='form-title'>".__('Available Sections')."</div>" .
				"<table class='form-content'>" .
				"<tr>";
		
		$admin = new admin();
		$admin->load();
		unset($admin);
		
		$column = 1;
		foreach(array('Content', 'Members', 'Modules', 'Site') as $sectionid) {
			$section = admin::$sections[$sectionid];
			
			if (!count($section['Items']))
				continue;
			
			if ($column == 1 || count($section['Items']) > 10) {
				if ($column > 1)
					echo
						"</td>";
				
				echo
					"<td valign='top'>";
				
			} else {
				echo
					"<br />";
			}
			
			echo 
				"<table cellpadding='0' cellspacing='0' class='list'>" .
					"<thead>" .
					"<tr>" .
						"<th colspan='2'>" .
							"<span class='nowrap'>".
								$section['Title'] .
							"</span>" .
						"</th>" .
					"</tr>" .
					"</thead>" .
					"<tbody>";
			
			$i = 1;	
			foreach($section['Items'] as $itemid => $item) {
				preg_match('/href=.*?path=(.*?)(\'|"|&|$)/', $item, $matches);
				
				if (!isset($matches[1]))
					continue;
				
				echo
					"<tr".($i%2?" class='pair'":NULL).">" .
						"<td align='center'>" .
							"<a href='javascript://' " .
								"onclick=\"" .
									"jQuery('#neweditpermissionform .text-entry[name^=Path]:last').val('" .
										htmlspecialchars($matches[1], ENT_QUOTES)."');" .
									(JCORE_VERSION >= '0.7'?
										"jQuery(this).closest('.tipsy').hide();":
										"jQuery(this).closest('.qtip').qtip('hide');") .
									"\" " .
								"class='user-permissions-select-path select-link'>" .
							"</a>" .
						"</td>" .
						"<td class='auto-width'>" .
							strip_tags($item, '<span>') .
							"<div class='comment' style='padding-left: 10px;'>" .
								$matches[1] .
							"</div>" .
						"</td>" .
					"</tr>";
				
				$i++;
			}
			
			echo
					"</tbody>" .
				"</table>";
			
			$column++;
		}		
		
		echo
				"</td>" .
				"</tr>" .
				"</table>";
		
		echo
			"</div>";
	}
	
	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				__("Section / Path")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Permission")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
	}
	
	function displayAdminListHeaderFunctions() {
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
		$pathtitle = null;
		
		foreach(admin::$sections as $sectionid => $section) {
			if (!count($section['Items']))
				continue;
			
			foreach($section['Items'] as $itemid => $item) {
				preg_match('/href=.*?path=(.*?)(\'|"|&|$)/', $item, $matches);
				
				if (!isset($matches[1]))
					continue;
				
				if ($row['Path'] == $matches[1] ||
					(strlen($row['Path']) > strlen($matches[1]) && 
					preg_match('/^'.preg_quote($matches[1], '/').'\//i', $row['Path'])))
				{
					$pathtitle = strip_tags($item);
					break;
				}
				
				if (strlen($row['Path']) < strlen($matches[1]) && 
					preg_match('/^'.preg_quote($row['Path'], '/').'\//i', $matches[1]))
				{
					$pathtitle = strip_tags($section['Title']);
					break;
				} 
			}
			
			if ($pathtitle)
				break;
		}
		
		echo
			"<td class='auto-width'>" .
				"<b>".
					($pathtitle?
						$pathtitle:
						__('Unknown Section')) .
				"</b>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					$row['Path'] .
				"</div>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				$this->type2Text($row['PermissionTypeID']) .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
	}
	
	function displayAdminListItemFunctions(&$row) {
		echo
			"<td>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td>" .
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
					
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
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
					
			if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
				$this->displayAdminListItemFunctions($row);
			
			echo
				"</tr>";
			
			$i++;
		}
		
		echo 
				"</tbody>" .
			"</table>" .
			"<br />";
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('User Permissions'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
		echo "<p>".
			__("User will have full access to all paths (if marked as Admin) " .
				"unless you define some paths below.") .
			"</p>";
	}
	
	function displayAdmin() {
		$userpermission = userPermissions::check($GLOBALS['USER']->data['ID'], 
			'admin/members/users');
		
		if ($GLOBALS['USER']->data['ID'] == admin::getPathID()) {
			tooltip::display(
				__("You are not allowed to modify your own permissions!"),
				TOOLTIP_NOTIFICATION);
			return;
		}
		
		$edit = null;
		$id = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		$owner = sql::fetch(sql::run(
			" SELECT * FROM `{users}`" .
			" WHERE `ID` = '".admin::getPathID()."'"));
			
		$this->displayAdminTitle($owner['UserName']);
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
		
		$form = new form(
				($edit?
					__("Edit Permission"):
					__("New Permission")),
				'neweditpermission');
					
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
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($edit, explode(',', $this->userPermissionIDs)))))
		{
			$verifyok = $this->verifyAdmin($form);
		}
		
		$rows = sql::run(
			" SELECT * FROM `{userpermissions}`" .
			" WHERE `UserID` = '".admin::getPathID()."'" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			" ORDER BY `Path`");
			
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No user permissions found."),
				TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{userpermissions}`" .
					" WHERE `UserID` = '".admin::getPathID()."'" .
					" AND `ID` = '".$id."'"));
			
				$form->setValues($row);
			}
			
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form);
		
		echo
			"</div>"; //admin-content
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
			
		$exists = sql::fetch(sql::run(
			" SELECT `ID` FROM `{userpermissions}` " .
			" WHERE `Path` = '".
				sql::escape($values['Path'])."'" .
			" AND `UserID` = '".
				(int)$values['UserID']."'" .
			" LIMIT 1"));
			
		if ($exists) {
			tooltip::display(
				sprintf(__("Permission to path \"%s\" already exists."),
					$values['Path']),
				TOOLTIP_ERROR);
			return false;
		}
			
		$newid = sql::run(
			" INSERT INTO `{userpermissions}` SET " .
			" `UserID` = " .
				"'".(int)$values['UserID']."',".
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			" `PermissionTypeID` = '".
				(int)$values['PermissionTypeID']."'");
			
		if (!$newid) {
			tooltip::display(
				sprintf(__("Permission couldn't be set! Error: %s"), 
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
			" SELECT `ID` FROM `{userpermissions}` " .
			" WHERE `Path` = '".
				sql::escape($values['Path'])."'" .
			" AND `UserID` = '".
				(int)$values['UserID']."'" .
			" AND `ID` != '".(int)$id."'" .
			" LIMIT 1"));
			
		if ($exists) {
			tooltip::display(
				sprintf(__("Permission to path \"%s\" already exists."),
					$values['Path']),
				TOOLTIP_ERROR);
			return false;
		}
			
		sql::run(
			" UPDATE `{userpermissions}` SET ".
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			" `PermissionTypeID` = '".
				(int)$values['PermissionTypeID']."'" .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Permission couldn't be updated! Error: %s"), 
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
			" DELETE FROM `{userpermissions}` " .
			" WHERE `ID` = '".$id."'");
		
		return true;
	}
	
	// ************************************************   Client Part
	static function type2Text($type) {
		if (!$type)
			return;
		
		switch($type) {
			case USER_PERMISSION_TYPE_READ:
				return __('Read');
			case USER_PERMISSION_TYPE_WRITE:
				return __('Write');
			default:
				return __('Undefined!');
		}
	}
	
	static function check($userid, $path = null) {
		if (!(int)$userid)
			return false;
			
		if (!$path)
			$path = url::path();
		
		$haspermissions = sql::fetch(sql::run(
			" SELECT `ID` FROM `{userpermissions}`" .
			" WHERE `UserID` = '".(int)$userid."'" .
			" LIMIT 1"));
		
		if (!$haspermissions)
			return array(
				'PermissionType' => USER_PERMISSION_TYPE_WRITE,
				'PermissionIDs' => null);
			
		$permissions = sql::run(
			" SELECT `PermissionTypeID`, `Path` " .
			" FROM `{userpermissions}`" .
			" WHERE `UserID` = '".(int)$userid."'" .
			" AND ('".sql::escape($path)."/' LIKE CONCAT(`Path`, '/%')" .
			" 	OR CONCAT(`Path`, '/') LIKE '".sql::escape($path)."/%')" .
			" ORDER BY `Path`");
		
		$permissiontype = 0;
		$permissionids = null;
		
		while($permission = sql::fetch($permissions)) {
			preg_match('/'.str_replace('/', '\/', $path).'\/([0-9]*?)(\/|$)/i',
				$permission['Path'], $matches);
			
			if (!$permissiontype)
				$permissiontype = $permission['PermissionTypeID'];
			
			if (isset($matches[1]) && (int)$matches[1])
				$permissionids[] = (int)$matches[1];
		}
		
		if ($permissionids)
			$permissionids = implode(',', $permissionids);
		
		return array(
			'PermissionType' => (int)$permissiontype,
			'PermissionIDs' => $permissionids);
	}
	
	function ajaxRequest() {
		if (!$GLOBALS['USER']->loginok || 
			!$GLOBALS['USER']->data['Admin']) 
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);
			return true;
		}
		
		$sections = null;
		
		if (isset($_GET['sections']))
			$sections = $_GET['sections'];
		
		if ($sections) {
			$permission = userPermissions::check(
				$GLOBALS['USER']->data['ID'],
				$this->adminPath);
			
			if ($permission['PermissionType'] != USER_PERMISSION_TYPE_WRITE ||
				$permission['PermissionIDs'])
			{
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);
				return true;
			}
			
			$this->displayAdminSections();
			return true;
		}
		
		return false;
	}
}

?>