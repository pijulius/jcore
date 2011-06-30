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
		$this->rootPath = SITE_PATH.'lib/modules/';
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		return modules::count();
	}
	
	function setupAdmin() {
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
	}
	
	function setupAdminForm(&$form) {
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
					"onclick=\"jQuery.jCore.form.appendEntryTo(" .
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
	}
	
	function verifyAdmin(&$form) {
		$activate = null;
		$deactivate = null;
		$delete = null;
		$id = null;
		
		if (isset($_GET['activate']))
			$activate = $_GET['activate'];
		
		if (isset($_GET['deactivate']))
			$deactivate = $_GET['deactivate'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['id']))
			$id = strtolower(preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '',
				strip_tags($_GET['id'])));
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
			
			tooltip::display(
				__("Module has been successfully deleted."),
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if ($activate) {
			if (!$this->activate($id))
				return false;
			
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
				"jQuery('link[rel=\"stylesheet\"]').each(function () {" .
					"this.href = this.href+'&reload';});" .
				"</script>";
				
			return true;
		}
		
		if ($deactivate) {
			if (!$this->deactivate($id))
				return false;
			
			tooltip::display(
				__("Module has been successfully deactivated.")." " .
				"<a href='".url::uri('id, deactivate')."'>" .
					__("Refresh") .
				"</a>",
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if (!$form->get('Files')) {
			tooltip::display(
				__("No module selected to be uploaded! " .
					"Please select at least one module to upload."),
				TOOLTIP_ERROR);
			
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
			
			if (!$successfiles || !count($successfiles))
				return false;
		}
		
		tooltip::display(
			sprintf(__("Modules have been successfully uploaded. " .
				"The following modules have been uploaded: %s."),
				implode(', ', $successfiles)),
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				__("Module")."</span></th>" .
			"<th></th>";
	}
	
	function displayAdminListHeaderOptions() {
	}
	
	function displayAdminListHeaderFunctions() {
		echo
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
		echo
			"<td align='center' style='width: 100px;'>" .
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
						($row['_Name']?
							$row['_Name']:
							($row['Title']?
								$row['Title']:
								ucfirst($row['ID']))) .
						($row['_Version']?
							" (".$row['_Version'].")":
							null) .
					"</h2>" .
					($row['_Author']?
						"<div class='template-details'>" .
							sprintf(__("by %s"), $row['_Author']) .
							($row['_URI']?
								" (".url::parseLinks($row['_URI']).")":
								null) .
						"</div>":
						null) .
					"<div class='template-description'>" .
						"<p>" .
							($row['_Description']?
								url::parseLinks($row['_Description']):
								url::parseLinks($row['Description'])) .
						"</p>" .
					"</div>" .
					($row['_Tags']?
						"<div class='template-tags'><b>" .
							__("Tags").":</b> " .
							$row['_Tags'] .
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
	}
	
	function displayAdminListItemActivation(&$row) {
		$url = url::uri('id, delete, activate, deactivate').
			"&amp;id=".urlencode($row['ID']);
		
		if ($row['_Activated']) {
			echo
				"<div class='button' style='float: none; margin: 10px 0 0 0;'>" .
					"<a href='".$url."&amp;deactivate=1'>" .
						__("Deactivate") .
					"</a>" .
				"</div>";
			
		} else {
			echo
				"<div class='button' style='float: none; margin: 10px 0 0 0;'>" .
					"<a href='".$url."&amp;activate=1'>" .
						__("Activate") .
					"</a>" .
				"</div>";
		}
	}
	
	function displayAdminListItemOptions(&$row) {
	}
	
	function displayAdminListItemFunctions(&$row) {
		if ($row['_Global'] && !$row['_Activated'] && !$row['_Installed']) {
			echo
				"<td></td>";
			return;
		}
		
		echo
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, delete, activate, deactivate') .
					"&amp;id=".urlencode($row['ID'])."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayAdminList(&$modules) {
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
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Modules Administration'), 
			$ownertitle);
	}
	
	function displayAdminDescription() {
		echo
			"<p>" .
				__("Below are the available modules found in the \"<b>modules/</b>\" folder. " .
					"To install a new module just extract it to the " .
					"\"<b>modules/</b>\" folder, or using the form below select the " .
					"module package file (e.g. module.tar.gz).") .
			"</p>";
	}
	
	function displayAdmin() {
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
		
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
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		$newid = sql::run(
			" INSERT INTO `{modules}` SET" .
			" `Name` = '".
				sql::escape($values['Name'])."'");
		
		if (!$newid) {
			tooltip::display(
				sprintf(__("Module couldn't be added! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return $newid;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		if (modules::load($id, true, true)) {
			$exists = modules::get($id);
			
			$module = new $id;
			$module->moduleID = $exists['ID'];
			$success = $module->uninstall();
			unset($module);
			
			if (!$success)
				return false;
			
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
			return false;
		}
		
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
		
		$exists = modules::get($id);
		
		if ($exists && $exists['Installed']) {
			if (JCORE_VERSION < '0.9')
				return true;
			
			sql::run(
				" UPDATE `{modules}` SET " .
				" `Deactivated` = 0" .
				" WHERE `ID` = '".$exists['ID']."'");
			
			if (sql::error())
				return false;
			
			css::update();
			jQuery::update();
			
			return true;
		}
		
		if ($exists)
			$newid = $exists['ID'];
		else
			$newid = $this->add(array(
				'Name' => $id));
		
		if (!$newid)
			return false;
		
		$module = new $id;
		$module->moduleID = $newid;
		$success = $module->install();
		unset($module);
		
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
		
		if (JCORE_VERSION >= '0.9') {
			sql::run(
				" UPDATE `{modules}` SET " .
				" `Deactivated` = 1" .
				" WHERE `ID` = '".$exists['ID']."'");
			
			if (sql::error())
				return false;
			
		} else {
			sql::run(
				" DELETE FROM `{modules}`" .
				" WHERE `ID` = '".$exists['ID']."'");
			
			if (sql::error())
				return false;
			
			sql::run(
				" DELETE FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pagemodules':
						'menuitemmodules') .
					"}`" .
				" WHERE `ModuleID` = '".$exists['ID']."'");
			
			if (sql::error())
				return false;
		}
		
		css::update();
		jQuery::update();
		
		return true;
	}
	
	function upload($file) {
		if (!$filename = files::upload($file, $this->rootPath, FILE_TYPE_UPLOAD))
			return false;
		
		if (security::checkOutOfMemory(@filesize($this->rootPath.$filename), 3)) {
			tooltip::display(
				__("Couldn't extract module as it is to big to be processed " .
					"with the current memory limit set. " .
					"Please try to extract it manually or increment the PHP " .
					"memory limit."),
				TOOLTIP_ERROR);
			
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
				
				return false;
			}
		}
		
		files::delete($this->rootPath.$filename);
		unset($tar);
		
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
			preg_match('/\/\*.*?'.$variable.': (.*?)(\r|\n).*?\*\//si', $data, $matches);
			
			if (isset($matches[1]))
				$values['_'.$variable] = trim($matches[1]);
		}
		
		return $values;
	}
}

?>