<?php

/***************************************************************************
 *            userpermissions.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
define('USER_PERMISSION_TYPE_READ', 1);
define('USER_PERMISSION_TYPE_WRITE', 2);
define('USER_PERMISSION_TYPE_OWN', 4);
 
class _userPermissions {
	var $sqlTable = 'userpermissions';
	var $sqlRow = 'UserID';
	var $sqlOwnerTable = 'users';
	var $sqlOwnerField = 'UserName';
	var $ajaxRequest = null;
	var $adminPath = 'admin/members/users/userpermissions';
	
	// ************************************************   Admin Part
	function setupAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::setupAdminForm', $this, $form);
		
		$edit = null;
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		$form->add(
			$this->sqlRow,
			$this->sqlRow,
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
				$form->setTooltipText(__("e.g. admin/content/pages/1/posts"));
			else
				$form->addAdditionalText(
					" (".__("e.g. admin/content/pages/1/posts").")");
			
			$form->add(
				"<div class='form-entry-user-multi-permission-paths-container'></div>" .
				"<div class='form-entry-title'></div>" .
				"<div class='form-entry-content'>" .
					"<a href='".url::uri('request, sections') .
						"&amp;request=".url::path() .
						"&amp;sections=1' " .
						"class='select-link ajax-content-link'>" .
						__("Select path") .
					"</a> &nbsp; " .
					"<a href='javascript://' class='add-link' " .
						"onclick=\"$.jCore.form.appendEntryTo(" .
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
						"&amp;request=".url::path() .
						"&amp;sections=1' " .
						"class='select-link ajax-content-link'>" .
						__("Select path") .
					"</a>",
				null,
				FORM_STATIC_TEXT);
			
			if (JCORE_VERSION >= '0.6')
				$form->setTooltipText(__("e.g. admin/content/pages/1/posts"));
			else
				$form->addAdditionalText(
					" (".__("e.g. admin/content/pages/1/posts").")");
		}
			
		$form->add(
			__('Permission'),
			'PermissionTypeID',
			FORM_INPUT_TYPE_SELECT,
			true);
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		$form->addValue(
			USER_PERMISSION_TYPE_WRITE, $this->type2Text(USER_PERMISSION_TYPE_WRITE));
		$form->addValue(
			USER_PERMISSION_TYPE_READ, $this->type2Text(USER_PERMISSION_TYPE_READ));
		$form->addValue(
			USER_PERMISSION_TYPE_WRITE | USER_PERMISSION_TYPE_OWN, $this->type2Text(USER_PERMISSION_TYPE_WRITE | USER_PERMISSION_TYPE_OWN));
		$form->addValue(
			USER_PERMISSION_TYPE_READ | USER_PERMISSION_TYPE_OWN, $this->type2Text(USER_PERMISSION_TYPE_READ | USER_PERMISSION_TYPE_OWN));
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::setupAdminForm', $this, $form);
	}
	
	function setupAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::setupAdmin', $this);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Permission'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Users'), 
			'?path=admin/members/users');
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::setupAdmin', $this);
	}
	
	function verifyAdmin(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::verifyAdmin', $this, $form);
		
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_POST['delete']))
			$delete = (int)$_POST['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'userPermissions::verifyAdmin', $this, $form);
				return false;
			}
			
			$result = $this->delete($id);
			
			if ($result)
				tooltip::display(
					__("Permission has been successfully deleted."),
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'userPermissions::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'userPermissions::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if (!$edit && !count($form->get('Paths'))) {
			tooltip::display(
				__("No paths have been defined! " .
					"Please define at least one path to set permission to."),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'userPermissions::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if ($edit) {
			$form->set('Path', 
				str_replace(' ', '', trim($form->get('Path'), ' /')));
			
			$result = $this->edit($id, $form->getPostArray());
			
			if ($result)
				tooltip::display(
					__("Permission has been successfully updated.")." " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'userPermissions::verifyAdmin', $this, $form, $result);
			
			return $result;
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
			
			if (!$failedpaths || !count($successpaths)) {
				api::callHooks(API_HOOK_AFTER,
					'userPermissions::verifyAdmin', $this, $form);
				
				return false;
			}
		}
		
		tooltip::display(
			sprintf(__("Permission(s) have been successfully added. " .
				"The following permissions have been added: %s."),
				implode(', ', $successpaths)),
			TOOLTIP_SUCCESS);
		
		$form->reset();
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::verifyAdmin', $this, $form, $successpaths);
		
		return true;
	}
	
	function displayAdminSections() {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::displayAdminSections', $this);
		
		echo
			"<div class='user-permissions-admin-sections'>" .
				"<div class='form-title'>".__('Available Sections')."</div>" .
				"<table class='form-content'>" .
				"<tr>";
		
		$admin = new admin();
		$admin->setup();
		unset($admin);
		
		$column = 0;
		$columnitems = 0;
		$sections = array('Content', 'Members', 'Modules', 'Site');
		
		$totalitems = 0;
		foreach(admin::$sections as $sectionid => $section) {
			$totalitems += count($section['Items']);
			
			if (!in_array($sectionid, $sections))
				$sections[] = $sectionid;
		}
		
		foreach($sections as $sectionid) {
			$section = admin::$sections[$sectionid];
			
			if (!count($section['Items']))
				continue;
			
			if ($column == 0 || ($column < 3 && 
				($columnitems >= ceil($totalitems/3) || 
				$columnitems+count($section['Items']) >= ceil($totalitems/3)+ceil(($totalitems/3)/2)))) 
			{
				if ($column)
					echo
						"</td>";
				
				echo
					"<td valign='top'>";
				
				$column++;
				$columnitems = 0;
				
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
									"$('#neweditpermissionform .text-entry[name^=Path]:last').val('" .
										htmlspecialchars($matches[1], ENT_QUOTES)."');" .
									(JCORE_VERSION >= '0.7'?
										"$(this).closest('.tipsy').hide();":
										"$(this).closest('.qtip').qtip('hide');") .
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
			
			$columnitems += count($section['Items']);
		}		
		
		echo
				"</td>" .
				"</tr>" .
				"</table>";
		
		echo
			"</div>";
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::displayAdminSections', $this);
	}
	
	function displayAdminListHeader() {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::displayAdminListHeader', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Section / Path")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Permission")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::displayAdminListHeader', $this);
	}
	
	function displayAdminListHeaderOptions() {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::displayAdminListHeaderOptions', $this);
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::displayAdminListHeaderFunctions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::displayAdminListItem', $this, $row);
		
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
				"<span class='nowrap'>" .
				$this->type2Text($row['PermissionTypeID']) .
				"</span>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListItemOptions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::displayAdminListItemOptions', $this, $row);
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::displayAdminListItemFunctions', $this, $row);
		
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
			'userPermissions::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminList(&$rows) {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::displayAdminList', $this, $rows);
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::displayAdminList', $this, $rows);
	}
	
	function displayAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::displayAdminForm', $this, $form);
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::displayAdminTitle', $this, $ownertitle);
		
		admin::displayTitle(
			__('User Permissions'),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::displayAdminDescription', $this);
		
		echo "<p>".
			__("User will have full access to all paths (if marked as Admin) " .
				"unless you define some paths below.") .
			"</p>";
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		if ($this->sqlTable == 'userpermissions' && 
			$GLOBALS['USER']->data['ID'] == admin::getPathID()) 
		{
			echo "<br />";
			
			tooltip::display(
				__("You are not allowed to modify your own permissions!"),
				TOOLTIP_NOTIFICATION);
			return;
		}
		
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::displayAdmin', $this);
		
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		$owner = sql::fetch(sql::run(
			" SELECT * FROM `{".$this->sqlOwnerTable."}`" .
			" WHERE `ID` = '".admin::getPathID()."'"));
			
		$this->displayAdminTitle($owner[$this->sqlOwnerField]);
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
		
		if ($delete && $id && empty($_POST['delete'])) {
			$selected = sql::fetch(sql::run(
				" SELECT `Path` FROM `{".$this->sqlTable."}`" .
				" WHERE `ID` = '".$id."'"));
			
			url::displayConfirmation(
				'<b>'.__('Delete').'?!</b> "'.$selected['Path'].'"');
		}
		
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$verifyok = $this->verifyAdmin($form);
		
		$rows = sql::run(
			" SELECT * FROM `{".$this->sqlTable."}`" .
			" WHERE `".$this->sqlRow."` = '".admin::getPathID()."'" .
			" ORDER BY `Path`");
			
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No permissions found."),
				TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{".$this->sqlTable."}`" .
					" WHERE `ID` = '".$id."'"));
				
				$form->setValues($selected);
			}
			
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form);
		
		echo
			"</div>"; //admin-content
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::displayAdmin', $this);
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
			
		$exists = sql::fetch(sql::run(
			" SELECT `ID` FROM `{".$this->sqlTable."}` " .
			" WHERE `Path` = '".
				sql::escape($values['Path'])."'" .
			" AND `".$this->sqlRow."` = '".
				(int)$values[$this->sqlRow]."'" .
			" LIMIT 1"));
			
		if ($exists) {
			tooltip::display(
				sprintf(__("Permission to path \"%s\" already exists."),
					$values['Path']),
				TOOLTIP_ERROR);
			return false;
		}
			
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::add', $this, $values);
		
		$newid = sql::run(
			" INSERT INTO `{".$this->sqlTable."}` SET " .
			" `".$this->sqlRow."` = " .
				"'".(int)$values[$this->sqlRow]."',".
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			" `PermissionTypeID` = '".
				(int)$values['PermissionTypeID']."'");
			
		if (!$newid)
			tooltip::display(
				sprintf(__("Permission couldn't be set! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::add', $this, $values, $newid);
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		$exists = sql::fetch(sql::run(
			" SELECT `ID` FROM `{".$this->sqlTable."}` " .
			" WHERE `Path` = '".
				sql::escape($values['Path'])."'" .
			" AND `".$this->sqlRow."` = '".
				(int)$values[$this->sqlRow]."'" .
			" AND `ID` != '".(int)$id."'" .
			" LIMIT 1"));
			
		if ($exists) {
			tooltip::display(
				sprintf(__("Permission to path \"%s\" already exists."),
					$values['Path']),
				TOOLTIP_ERROR);
			return false;
		}
			
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::edit', $this, $id, $values);
		
		sql::run(
			" UPDATE `{".$this->sqlTable."}` SET ".
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			" `PermissionTypeID` = '".
				(int)$values['PermissionTypeID']."'" .
			" WHERE `ID` = '".(int)$id."'");
			
		$result = (sql::affected() != -1);
		
		if (!$result)
			tooltip::display(
				sprintf(__("Permission couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::edit', $this, $id, $values, $result);
		
		return $result;
	}
	
	function delete($id) {
		if (!$id)
			return false;
			
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::delete', $this, $id);
		
		sql::run(
			" DELETE FROM `{".$this->sqlTable."}` " .
			" WHERE `ID` = '".$id."'");
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::delete', $this, $id);
		
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
			case USER_PERMISSION_TYPE_READ | USER_PERMISSION_TYPE_OWN:
				return __('Read Own');
			case USER_PERMISSION_TYPE_WRITE | USER_PERMISSION_TYPE_OWN:
				return __('Write Own');
			default:
				return __('Undefined!');
		}
	}
	
	static function check($userid, $path = null) {
		if (!(int)$userid)
			return false;
		
		$user = null;
		
		if (!$path)
			$path = url::path();
		
		$haspermissions = sql::fetch(sql::run(
			" SELECT `ID` FROM `{userpermissions}`" .
			" WHERE `UserID` = '".(int)$userid."'" .
			" LIMIT 1"));
		
		if (JCORE_VERSION >= '0.8' && !$haspermissions) {
			$user = $GLOBALS['USER']->get($userid);
			
			if ($user['GroupID'])
				$haspermissions = sql::fetch(sql::run(
					" SELECT `ID` FROM `{usergrouppermissions}`" .
					" WHERE `GroupID` = '".(int)$user['GroupID']."'" .
					" LIMIT 1"));
		}
		
		if (!$haspermissions)
			return array(
				'PermissionType' => USER_PERMISSION_TYPE_WRITE,
				'PermissionIDs' => null,
				'PermissionIDTypes' => null);
		
		$pathregexp = null;
		$pathqueries = null;
		
		if (is_array($path)) {
			foreach($path as $pth) {
				$pathregexp[] = preg_quote($pth, '/');
				
				$pathqueries[] = 
					" '".sql::escape($pth)."/' LIKE CONCAT(`Path`, '/%')" .
					" OR CONCAT(`Path`, '/') LIKE '".sql::escape($pth)."/%'";
			}
		}
		
		$permissions = sql::run(
			" SELECT `PermissionTypeID`, `Path` " .
			" FROM `{userpermissions}`" .
			" WHERE `UserID` = '".(int)$userid."'" .
			" AND (" .
			($pathqueries?
				implode(' OR ', $pathqueries):
				" '".sql::escape($path)."/' LIKE CONCAT(`Path`, '/%')" .
				" OR CONCAT(`Path`, '/') LIKE '".sql::escape($path)."/%'") .
			" )" .
			" ORDER BY `Path`");
		
		if (JCORE_VERSION >= '0.8' && !sql::rows($permissions)) {
			if (!$user)
				$user = $GLOBALS['USER']->get($userid);
			
			if ($user['GroupID'])
				$permissions = sql::run(
					" SELECT `PermissionTypeID`, `Path` " .
					" FROM `{usergrouppermissions}`" .
					" WHERE `GroupID` = '".(int)$user['GroupID']."'" .
					" AND (" .
					($pathqueries?
						implode(' OR ', $pathqueries):
						" '".sql::escape($path)."/' LIKE CONCAT(`Path`, '/%')" .
						" OR CONCAT(`Path`, '/') LIKE '".sql::escape($path)."/%'") .
					" )" .
					" ORDER BY `Path`");
		}
		
		$permissiontype = 0;
		$permissionids = null;
		$permissionidtypes = null;
		
		while($permission = sql::fetch($permissions)) {
			preg_match('/' .
				($pathregexp?
					implode('|', $pathregexp):
					preg_quote($path, '/')) .
				'\/([0-9]*?)((\/.*$)|$)/i',
				$permission['Path'], $matches);
			
			if (!$permissiontype || $permissiontype >= $permission['PermissionTypeID']) {
				if (isset($matches[3]) && $matches[3])
					$permissiontype = USER_PERMISSION_TYPE_READ;
				else
					$permissiontype = $permission['PermissionTypeID'];
			}
			
			if (isset($matches[1]) && (int)$matches[1]) {
				if (!isset($permissionids) || count($permissionids))
					$permissionids[] = (int)$matches[1];
				
				$permissionidtypes[(int)$matches[1]] = $permission['PermissionTypeID'];
			} else {
				$permissionids = array();
			}
		}
		
		if ($permissionids)
			$permissionids = implode(',', $permissionids);
		
		return array(
			'PermissionType' => (int)$permissiontype,
			'PermissionIDs' => $permissionids,
			'PermissionIDTypes' => $permissionidtypes);
	}
	
	function ajaxRequest() {
		api::callHooks(API_HOOK_BEFORE,
			'userPermissions::ajaxRequest', $this);
		
		if (!$GLOBALS['USER']->loginok || 
			!$GLOBALS['USER']->data['Admin']) 
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'userPermissions::ajaxRequest', $this);
			
			return true;
		}
		
		$sections = null;
		
		if (isset($_GET['sections']))
			$sections = (int)$_GET['sections'];
		
		if ($sections) {
			$permission = userPermissions::check(
				(int)$GLOBALS['USER']->data['ID'],
				$this->adminPath);
			
			if (~$permission['PermissionType'] & USER_PERMISSION_TYPE_WRITE) {
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);
				
				api::callHooks(API_HOOK_AFTER,
					'userPermissions::ajaxRequest', $this);
				
				return true;
			}
			
			$this->displayAdminSections();
			
			api::callHooks(API_HOOK_AFTER,
				'userPermissions::ajaxRequest', $this, $sections);
			
			return true;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'userPermissions::ajaxRequest', $this);
		
		return false;
	}
}

?>