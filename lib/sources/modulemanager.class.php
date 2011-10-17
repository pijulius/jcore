<?php

/***************************************************************************
 *            moduleManager.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
class _moduleManager {
	var $rootPath = null;
	var $adminPath = 'admin/site/modules';
	
	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::moduleManager', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::moduleManager', $this, $handled);
			
			return $handled;
		}
		
		$this->rootPath = SITE_PATH.'lib/modules/';
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::moduleManager', $this);
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::countAdminItems', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::countAdminItems', $this, $handled);
			
			return $handled;
		}
		
		$result = modules::count();
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::countAdminItems', $this, $result);
		
		return $result;
	}
	
	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::setupAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::setupAdmin', $this, $handled);
			
			return $handled;
		}
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('Upload Module'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('View Modules'), 
			'?path=admin/modules');
		
		favoriteLinks::add(
			__('Get Modules'), 
			'http://jcore.net/modules');
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::setupAdmin', $this);
	}
	
	function setupAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::setupAdminForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::setupAdminForm', $this, $form, $handled);
			
			return $handled;
		}
		
		$form->add(
			__('Module File'),
			'Files[]',
			FORM_INPUT_TYPE_FILE);
		$form->setValueType(FORM_VALUE_TYPE_FILE);
		$form->setAttributes("multiple='multiple'");
		
		$form->setTooltipText(__("e.g. module.tar.gz"));
		
		$form->add(
			"<div class='form-entry-upload-multi-modules-container'></div>" .
			"<div class='form-entry-title'></div>" .
			"<div class='form-entry-content'>" .
				"<a href='javascript://' class='add-link' " .
					"onclick=\"$.jCore.form.appendEntryTo(" .
						"'.form-entry-upload-multi-modules-container', " .
						"'', " .
						"'Files[]', " .
						FORM_INPUT_TYPE_FILE."," .
						"false, ''," .
						"'multiple');\">" .
					__("Upload another module") .
				"</a>" .
			"</div>",
			null,
			FORM_STATIC_TEXT);
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::setupAdminForm', $this, $form);
	}
	
	function verifyAdmin(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::verifyAdmin', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::verifyAdmin', $this, $form, $handled);
			
			return $handled;
		}
		
		$activate = null;
		$deactivate = null;
		$delete = null;
		$id = null;
		
		if (isset($_POST['activate']))
			$activate = (string)$_POST['activate'];
		
		if (isset($_POST['deactivate']))
			$deactivate = (string)$_POST['deactivate'];
		
		if (isset($_POST['delete']))
			$delete = (int)$_POST['delete'];
		
		if (isset($_GET['id']))
			$id = strtolower(preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '',
				strip_tags((string)$_GET['id'])));
		
		if (isset($_POST['id']))
			$id = strtolower(preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '',
				strip_tags((string)$_POST['id'])));
		
		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'moduleManager::verifyAdmin', $this, $form);
				return false;
			}
			
			$result = $this->delete($id);
			
			if ($result)
				tooltip::display(
					__("Module has been successfully deleted."),
					TOOLTIP_SUCCESS);
				
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if ($activate) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'moduleManager::verifyAdmin', $this, $form);
				return false;
			}
			
			$result = $this->activate($id);
			
			if ($result) {
				tooltip::display(
					__("Module has been successfully activated.")." " .
					"<a href='?path=admin/modules/".$id."'>" .
						__("View Module") .
					"</a>" .
					" - " .
					"<a href='".url::uri('id, activate')."'>" .
						__("Refresh") .
					"</a>",
					TOOLTIP_SUCCESS);
				
				echo
					"<script type='text/javascript'>" .
					"$('link[rel=\"stylesheet\"]').each(function () {" .
						"this.href = this.href+'&reload';});" .
					"</script>";
			}
			
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if ($deactivate) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'moduleManager::verifyAdmin', $this, $form);
				return false;
			}
			
			$result = $this->deactivate($id);
			
			if ($result)
				tooltip::display(
					__("Module has been successfully deactivated.")." " .
					"<a href='".url::uri('id, deactivate')."'>" .
						__("Refresh") .
					"</a>",
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if (!$form->get('Files')) {
			tooltip::display(
				__("No module selected to be uploaded! " .
					"Please select at least one module to upload."),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::verifyAdmin', $this, $form);
			
			return false;
		}
		
		$files = $form->getFile('Files');
		$successfiles = null;
		$failedfiles = null;
		
		foreach($form->get('Files') as $key => $filename) {
			if (!$this->upload(@$files[$key])) {
				$failedfiles[] = $filename;
				continue;
			}
			
			$successfiles[] = $filename;
		}
		
		if ($failedfiles && count($failedfiles)) {
			tooltip::display(
				sprintf(__("There were problems uploading some of the modules you selected. " .
					"The following modules couldn't be uploaded: %s."),
					implode(', ', $failedfiles)),
				TOOLTIP_ERROR);
			
			if (!$successfiles || !count($successfiles)) {
				api::callHooks(API_HOOK_AFTER,
					'moduleManager::verifyAdmin', $this, $form);
				
				return false;
			}
		}
		
		tooltip::display(
			sprintf(__("Modules have been successfully uploaded. " .
				"The following modules have been uploaded: %s."),
				implode(', ', $successfiles)),
			TOOLTIP_SUCCESS);
		
		$form->reset();
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::verifyAdmin', $this, $form, $successfiles);
		
		return true;
	}
	
	function displayAdminListHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::displayAdminListHeader', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::displayAdminListHeader', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<th><span class='nowrap'>".
				__("Module")."</span></th>" .
			"<th></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::displayAdminListHeader', $this);
	}
	
	function displayAdminListHeaderOptions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::displayAdminListHeaderOptions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::displayAdminListHeaderOptions', $this, $handled);
			
			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::displayAdminListHeaderFunctions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::displayAdminListHeaderFunctions', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::displayAdminListItem', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::displayAdminListItem', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			"<td align='center' style='width: 140px;'>" .
				"<div class='admin-content-preview'>" .
					"<div class='admin-section-item as-modules-".$row['ID'] .
						(!$row['_Activated']?
							"-deactivated":
							null) .
						"' style='float: none; height: 48px;'>" .
						"<a" .
							($row['_Activated']?
								" href='?path=admin/modules/".$row['ID']."'":
								null) .
							(isset($row['Icon']) && $row['Icon']?
								" style=\"background-image: url('".$row['Icon']."');\"":
								null) .
							"></a>" .
					"</div>";
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$this->displayAdminListItemActivation($row);
		
		echo
				"</div>" .
			"</td>" .
			"<td class='auto-width'>" .
				"<div class='admin-content-preview' style='padding-left: 0;'>" .
					"<h2 class='module-name' style='margin: 0;'>" .
						htmlspecialchars(
						($row['_Name']?
							$row['_Name']:
							($row['Title']?
								$row['Title']:
								ucfirst($row['ID']))) .
						($row['_Version']?
							" (".$row['_Version'].")":
							null)) .
					"</h2>" .
					($row['_Author']?
						"<div class='template-details'>" .
							sprintf(__("by %s"), 
								htmlspecialchars($row['_Author'])) .
							($row['_URI']?
								" (".url::parseLinks(strip_tags($row['_URI'])).")":
								null) .
						"</div>":
						null) .
					"<div class='template-description'>" .
						"<p>" .
							($row['_Description']?
								url::parseLinks(nl2br(htmlspecialchars($row['_Description']))):
								url::parseLinks(nl2br(htmlspecialchars($row['Description'])))) .
						"</p>" .
					"</div>" .
					($row['_Tags']?
						"<div class='template-tags'><b>" .
							__("Tags").":</b> " .
							htmlspecialchars($row['_Tags']) .
						"</div>":
						null) .
					"<div class='template-location'><b>" .
						__("Type").":</b> " .
						($row['_Global']?
							__('global'):
							__('local')) .
					"</div>" .
				"</div>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListItemActivation(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::displayAdminListItemActivation', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::displayAdminListItemActivation', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			"<form action='".
				url::uri('id, delete, activate, deactivate')."' method='post'>" .
				"<input type='hidden' name='id' value='".htmlspecialchars($row['ID'], ENT_QUOTES)."' />" .
				"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";
		
		if ($row['_Activated']) {
			echo
				"<input type='submit' class='button'" .
					" style='float: none; width: 100%; margin: 10px 0 0 0;'" .
					" name='deactivate' value='".__("Deactivate")."' />";
			
		} else {
			echo
				"<input type='submit' class='button'" .
					" style='float: none; width: 100%; margin: 10px 0 0 0;'" .
					" name='activate' value='".__("Activate")."' />";
		}
		
		echo
			"</form>";
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::displayAdminListItemActivation', $this, $row);
	}
	
	function displayAdminListItemOptions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::displayAdminListItemOptions', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::displayAdminListItemOptions', $this, $row, $handled);
			
			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::displayAdminListItemFunctions', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::displayAdminListItemFunctions', $this, $row, $handled);
			
			return $handled;
		}
		
		if ($row['_Global'] && !$row['_Activated'] && !$row['_Installed']) {
			echo
				"<td></td>";
		} else {
			echo
				"<td align='center'>" .
					"<a class='admin-link delete confirm-link' " .
						"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
						"href='".url::uri('id, delete, activate, deactivate') .
						"&amp;id=".urlencode($row['ID'])."&amp;delete=1'>" .
					"</a>" .
				"</td>";
		}
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminList(&$modules) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::displayAdminList', $this, $modules);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::displayAdminList', $this, $modules, $handled);
			
			return $handled;
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
		foreach($modules as $key => $module) {
			$row = array();
			$row['ID'] = $key;
			$row['Title'] = modules::getTitle($row['ID']);
			$row['Description'] = modules::getDescription($row['ID']);
			$row['Icon'] = modules::getIcon($row['ID']);
			
			$row['_Installed'] = false;
			$row['_Activated'] = false;
			$row['_Global'] = false;
			
			$exists = modules::get($row['ID']);
			
			if ($exists && $exists['Installed'])
				$row['_Installed'] = true;
			
			if ($exists && $exists['Installed'] &&
				(JCORE_VERSION < '0.9' || !$exists['Deactivated']))
				$row['_Activated'] = true;
			
			if (defined('JCORE_PATH') && JCORE_PATH && 
				strpos($module, JCORE_PATH.'lib/modules/') !== false)
				$row['_Global'] = true;
			
			$row += moduleManager::parseData(
				files::get($module));
			
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
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::displayAdminList', $this, $modules);
	}
	
	function displayAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::displayAdminForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::displayAdminForm', $this, $form, $handled);
			
			return $handled;
		}
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::displayAdminTitle', $this, $ownertitle);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::displayAdminTitle', $this, $ownertitle, $handled);
			
			return $handled;
		}
		
		admin::displayTitle(
			__('Modules Administration'), 
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::displayAdminDescription', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::displayAdminDescription', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<p>" .
				__("Below are the available modules found in the \"<b>modules/</b>\" folder. " .
					"To install a new module just extract it to the " .
					"\"<b>modules/</b>\" folder, or using the form below select the " .
					"module package file (e.g. module.tar.gz).") .
			"</p>";
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::displayAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::displayAdmin', $this, $handled);
			
			return $handled;
		}
		
		$delete = null;
		$id = null;
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['id']))
			$id = strtolower(preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '',
				strip_tags((string)$_GET['id'])));
		
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
		
		if ($delete && $id && empty($_POST['delete']))
			url::displayConfirmation(
				'<b>'.__('Delete').'?!</b> "'.ucfirst($id).'"');
		
		$form = new form(
			__("Upload New Module"),
			'uploadnewmodule');
		
		$form->action = url::uri('id, delete, activate, deactivate');
		
		$this->setupAdminForm($form);
		$form->addSubmitButtons();
		
		$verifyok = false;
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$verifyok = $this->verifyAdmin($form);
		
		$modules = array();
		$activemodules = array();
		
		$rows = modules::get();
		while ($row = sql::fetch($rows))
			$activemodules[strtolower($row['Name'])] = true;
		
		$d = dir(SITE_PATH.'lib/modules');
		
		while (false !== ($entry = $d->read())) {
			if (strpos($entry, '.') === 0)
				continue;
			
			if (is_file(SITE_PATH.'lib/modules/'.$entry) &&
				preg_match('/(.*)\.class\.php$/', $entry, $matches))
			{
				$modules[strtolower(preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '', $matches[1]))] = 
					SITE_PATH.'lib/modules/'.$entry;
				continue;
			}
			
			if (is_dir(SITE_PATH.'lib/modules/'.$entry) &&
				is_file(SITE_PATH.'lib/modules/'.$entry.'/'.$entry.'.class.php'))
			{
				$modules[strtolower(preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '', $entry))] = 
					SITE_PATH.'lib/modules/'.$entry.'/'.$entry.'.class.php';
				continue;
			}
		}
		
		$d->close();
		
		if (defined('JCORE_PATH') && JCORE_PATH) {
			$d = dir(JCORE_PATH.'lib/modules');
			while (false !== ($entry = $d->read())) {
				if (strpos($entry, '.') === 0)
					continue;
				
				if (is_file(JCORE_PATH.'lib/modules/'.$entry) &&
					preg_match('/(.*)\.class\.php$/', $entry, $matches))
				{
					$moduleid = strtolower(preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '', $matches[1]));
					if (isset($modules[$moduleid]))
						continue;
					
					$modules[$moduleid] = 
						JCORE_PATH.'lib/modules/'.$entry;
					continue;
				}
				
				if (is_dir(JCORE_PATH.'lib/modules/'.$entry) &&
					is_file(JCORE_PATH.'lib/modules/'.$entry.'/'.$entry.'.class.php'))
				{
					$moduleid = strtolower(preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '', $entry));
					if (isset($modules[$moduleid]))
						continue;
					
					$modules[$moduleid] = 
						JCORE_PATH.'lib/modules/'.$entry.'/'.$entry.'.class.php';
					continue;
				}
			}
			
			$d->close();
		}
		
		ksort($modules);
		$modules = array_merge($activemodules, $modules);
		
		if (count($modules))
			$this->displayAdminList($modules);
		else
			tooltip::display(
				__("No modules found."),
				TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form);
		
		echo
			"</div>"; //admin-content
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::displayAdmin', $this);
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::add', $this, $values);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::add', $this, $values, $handled);
			
			return $handled;
		}
		
		$newid = sql::run(
			" INSERT INTO `{modules}` SET" .
			" `Name` = '".
				sql::escape($values['Name'])."'");
		
		if (!$newid)
			tooltip::display(
				sprintf(__("Module couldn't be added! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::add', $this, $values, $newid);
		
		return $newid;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::delete', $this, $id);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::delete', $this, $id, $handled);
			
			return $handled;
		}
		
		if (modules::load($id, true, true)) {
			$exists = modules::get($id);
			
			$module = new $id;
			$module->moduleID = $exists['ID'];
			$success = $module->uninstall();
			unset($module);
			
			if (!$success) {
				api::callHooks(API_HOOK_AFTER,
					'moduleManager::delete', $this, $id);
				
				return false;
			}
			
			sql::run(
				" DELETE FROM `{modules}` " .
				" WHERE `ID` = '".$exists['ID']."'");
			
			sql::run(
				" DELETE FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pagemodules':
						'menuitemmodules') .
					"}` " .
				" WHERE `ModuleID` = '".$exists['ID']."'");
		}
		
		$isdir = @is_dir(SITE_PATH.'lib/modules/'.$id);
		
		if (($isdir && !files::delete(SITE_PATH.'lib/modules/'.$id)) ||
			(!$isdir && !files::delete(SITE_PATH.'lib/modules/'.$id.'.class.php'))) 
		{
			tooltip::display(
				sprintf(__("Module couldn't be deleted but it is now safe " .
					"to be deleted manually by just simply removing " .
					"the \"%s\" file or folder."), 'lib/modules/'.$id .
					($isdir?
						'/':
						'.class.php')),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::delete', $this, $id);
			
			return false;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::delete', $this, $id);
		
		return true;
	}
	
	function activate($id) {
		if (!$id)
			return false;
		
		if (!modules::load($id, true, true)) {
			tooltip::display(
				__("Module couldn't be loaded! Please make sure it is a valid " .
					"module and try again."),
				TOOLTIP_ERROR);
			return false;
		}
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::activate', $this, $id);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::activate', $this, $id, $handled);
			
			return $handled;
		}
		
		$exists = modules::get($id);
		
		if ($exists && $exists['Installed']) {
			if (JCORE_VERSION < '0.9') {
				api::callHooks(API_HOOK_AFTER,
					'moduleManager::activate', $this, $id);
				
				return true;
			}
			
			sql::run(
				" UPDATE `{modules}` SET " .
				" `Deactivated` = 0" .
				" WHERE `ID` = '".$exists['ID']."'");
			
			if (sql::error()) {
				api::callHooks(API_HOOK_AFTER,
					'moduleManager::activate', $this, $id);
				
				return false;
			}
			
			css::update();
			jQuery::update();
			
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::activate', $this, $id, $exists);
			
			return true;
		}
		
		if ($exists)
			$newid = $exists['ID'];
		else
			$newid = $this->add(array(
				'Name' => $id));
		
		if (!$newid) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::activate', $this, $id);
			
			return false;
		}
		
		$module = new $id;
		$module->moduleID = $newid;
		$success = $module->install();
		unset($module);
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::activate', $this, $id, $success);
		
		return $success;
	}
	
	function deactivate($id) {
		if (!$id)
			return false;
		
		if (!modules::load($id, true, true)) {
			tooltip::display(
				__("Module couldn't be loaded! Please make sure it is a valid " .
					"module and try again."),
				TOOLTIP_ERROR);
			return false;
		}
		
		$exists = modules::get($id);
		if (!$exists)
			return true;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::deactivate', $this, $id);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::deactivate', $this, $id, $handled);
			
			return $handled;
		}
		
		if (JCORE_VERSION >= '0.9') {
			sql::run(
				" UPDATE `{modules}` SET " .
				" `Deactivated` = 1" .
				" WHERE `ID` = '".$exists['ID']."'");
			
			if (sql::error()) {
				api::callHooks(API_HOOK_AFTER,
					'moduleManager::deactivate', $this, $id);
				
				return false;
			}
			
		} else {
			sql::run(
				" DELETE FROM `{modules}`" .
				" WHERE `ID` = '".$exists['ID']."'");
			
			if (sql::error()) {
				api::callHooks(API_HOOK_AFTER,
					'moduleManager::deactivate', $this, $id);
				
				return false;
			}
			
			sql::run(
				" DELETE FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pagemodules':
						'menuitemmodules') .
					"}`" .
				" WHERE `ModuleID` = '".$exists['ID']."'");
			
			if (sql::error()) {
				api::callHooks(API_HOOK_AFTER,
					'moduleManager::deactivate', $this, $id);
				
				return false;
			}
		}
		
		css::update();
		jQuery::update();
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::deactivate', $this, $id, $exists);
		
		return true;
	}
	
	function upload($file) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'moduleManager::upload', $this, $file);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::upload', $this, $file, $handled);
			
			return $handled;
		}
		
		if (!$filename = files::upload($file, $this->rootPath, FILE_TYPE_UPLOAD)) {
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::upload', $this, $file);
			
			return false;
		}
		
		if (security::checkOutOfMemory(@filesize($this->rootPath.$filename), 3)) {
			tooltip::display(
				__("Couldn't extract module as it is to big to be processed " .
					"with the current memory limit set. " .
					"Please try to extract it manually or increment the PHP " .
					"memory limit."),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::upload', $this, $file);
			
			return false;
		}
		
		$tar = new tar();
		$tar->openTar($this->rootPath.$filename);
		
		if (!isset($tar->directories) && !isset($tar->files)) {
			tooltip::display(
				__("Module couldn't be extracted! " .
					"Error: Invalid module! Please make sure to " .
					"upload a valid tar.gz module file."),
				TOOLTIP_ERROR);
			
			files::delete($this->rootPath.$filename);
			unset($tar);
			
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::upload', $this, $file);
			
			return false;
		}
		
		if (!isset($tar->directories))
			$tar->directories = array();
		
		if (!isset($tar->files))
			$tar->files = array();
		
		if (!count($tar->directories) && !count($tar->files)) {
			tooltip::display(
				__("Module couldn't be extracted! " .
					"Error: Empty module! The module you " .
					"selected seems to be an empty tar.gz file."),
				TOOLTIP_ERROR);
			
			files::delete($this->rootPath.$filename);
			unset($tar);
			
			api::callHooks(API_HOOK_AFTER,
				'moduleManager::upload', $this, $file);
			
			return false;
		}
		
		foreach($tar->directories as $directory) {
			if (@is_dir($this->rootPath.$directory['name']) && 
				!@is_writable($this->rootPath.$directory['name']))
			{
				tooltip::display(
					sprintf(__("Module couldn't be extracted! " .
						"Error: \"%s\" directory couldn't be created."), $directory['name']),
					TOOLTIP_ERROR);
				
				files::delete($this->rootPath.$filename);
				unset($tar);
				
				api::callHooks(API_HOOK_AFTER,
					'moduleManager::upload', $this, $file);
				
				return false;
		
			}
			
			@mkdir($this->rootPath.$directory['name']);
			@chmod($this->rootPath.$directory['name'], 0755);
		}
		
		foreach($tar->files as $tarfile) {
			if ((@is_file($this->rootPath.$tarfile['name']) && 
				!@is_writable($this->rootPath.$tarfile['name'])) ||
				!files::create($this->rootPath.$tarfile['name'], $tarfile['file']))
			{
				tooltip::display(
					sprintf(__("Module couldn't be extracted! " .
						"Error: \"%s\" file couldn't be created."), $tarfile['name']),
					TOOLTIP_ERROR);
				
				files::delete($this->rootPath.$filename);
				unset($tar);
				
				api::callHooks(API_HOOK_AFTER,
					'moduleManager::upload', $this, $file);
				
				return false;
			}
		}
		
		files::delete($this->rootPath.$filename);
		unset($tar);
		
		api::callHooks(API_HOOK_AFTER,
			'moduleManager::upload', $this, $file, $filename);
		
		return true;
	}
	
	// ************************************************   Client Part
	static function parseData($data) {
		$variables = array(
			'Name', 'URI', 'Description', 
			'Author', 'Version', 'Tags');
		
		$values['_Name'] = '';
		$values['_URI'] = '';
		$values['_Description'] = '';
		$values['_Author'] = '';
		$values['_Version'] = '';
		$values['_Tags'] = '';
		
		foreach($variables as $variable) {
			preg_match('/\/\*.*?'.$variable.': (.*?)((\r|\n) ?\*|\*\/).*?\*\//si', $data, $matches);
			
			if (isset($matches[1]))
				$values['_'.$variable] = trim($matches[1], " \r\n");
		}
		
		return $values;
	}
}

?>