<?php

/***************************************************************************
 *            templatemanager.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
class _templateManager {
	var $rootPath = null;
	var $rootURL = null;
	var $adminPath = 'admin/site/template';
	
	function __construct() {
		$this->rootPath = SITE_PATH.'template/';
		$this->rootURL = SITE_URL.'template/';
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		if (template::$selected)
			return 1;
		
		return 0;
	}
	
	function setupAdmin() {
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('Upload Template'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Layout Blocks'), 
			'?path=admin/site/blocks');
		
		if (JCORE_VERSION >= '0.9')
			favoriteLinks::add(
				__('Layouts'), 
				'?path=admin/site/blocks/layouts');
		
		favoriteLinks::add(
			__('View Website'), 
			SITE_URL);
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			__('Template File'),
			'Files[]',
			FORM_INPUT_TYPE_FILE);
		$form->setValueType(FORM_VALUE_TYPE_FILE);
		$form->setAttributes("multiple='multiple'");
		
		$form->setTooltipText(__("e.g. template-name.tar.gz"));
		
		$form->add(
			"<div class='form-entry-upload-multi-templates-container'></div>" .
			"<div class='form-entry-title'></div>" .
			"<div class='form-entry-content'>" .
				"<a href='javascript://' class='add-link' " .
					"onclick=\"jQuery.jCore.form.appendEntryTo(" .
						"'.form-entry-upload-multi-templates-container', " .
						"'', " .
						"'Files[]', " .
						FORM_INPUT_TYPE_FILE."," .
						"false, ''," .
						"'multiple');\">" .
					__("Upload another template") .
				"</a>" .
			"</div>",
			null,
			FORM_STATIC_TEXT);
	}
	
	function verifyAdmin(&$form) {
		$activate = null;
		$deactivate = null;
		$setadmin = null;
		$unsetadmin = null;
		$delete = null;
		$id = null;
		
		if (isset($_GET['activate']))
			$activate = $_GET['activate'];
		
		if (isset($_GET['deactivate']))
			$deactivate = $_GET['deactivate'];
		
		if (isset($_GET['setadmin']))
			$setadmin = $_GET['setadmin'];
		
		if (isset($_GET['unsetadmin']))
			$unsetadmin = $_GET['unsetadmin'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['id']))
			$id = $_GET['id'];
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
			
			tooltip::display(
				__("Template has been successfully deleted."),
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if ($activate) {
			if (!$this->activate($id))
				return false;
			
			tooltip::display(
				__("Template has been successfully activated.")." " .
				"<a href='".SITE_URL."' target='_blank'>" .
					__("View Website") .
				"</a>",
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if ($deactivate) {
			if (!$this->deactivate($id))
				return false;
			
			tooltip::display(
				__("Default template has been successfully reset for your website.")." " .
				"<a href='".SITE_URL."' target='_blank'>" .
					__("View Website") .
				"</a>",
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if ($setadmin) {
			if (!$this->setAdmin($id))
				return false;
			
			tooltip::display(
				__("Template has been successfully set for Admin section.")." " .
				"<a href='".url::uri('ALL').'?'.url::arg('path')."'>" .
					__("Refresh") .
				"</a>",
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if ($unsetadmin) {
			if (!$this->unsetAdmin($id))
				return false;
			
			tooltip::display(
				__("Default template has been successfully reset for Admin section.")." " .
				"<a href='".url::uri('ALL').'?'.url::arg('path')."'>" .
					__("Refresh") .
				"</a>",
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if (!$form->get('Files')) {
			tooltip::display(
				__("No template selected to be uploaded! " .
					"Please select at least one template to upload."),
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
				sprintf(__("There were problems uploading some of the templates you selected. " .
					"The following templates couldn't be uploaded: %s."),
					implode(', ', $failedfiles)),
				TOOLTIP_ERROR);
			
			if (!$successfiles || !count($successfiles))
				return false;
		}
		
		tooltip::display(
			sprintf(__("Templates have been successfully uploaded. " .
				"The following templates have been uploaded: %s."),
				implode(', ', $successfiles)),
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				__("Template")."</span></th>" .
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
			"<td align='center' style='width: " .
				($row['_Activated']?
					"190":
					"140") .
				"px;'>" .
				"<div class='admin-content-preview'>" .
					"<div class='template-preview'>" .
						"<a href='".$row['_Preview']."' " .
							"title='".htmlspecialchars($row['_Name'], ENT_QUOTES)."' " .
							"rel='lightbox[templates]' " .
							"style='display: block; max-height: " .
								($row['_Activated']?
									"150":
									"100") .
								"px; overflow: hidden;'>" .
							"<img src='".$row['_Preview']."' " .
								"alt='".htmlspecialchars($row['_Name'], ENT_QUOTES)."' " .
								"width='" .
									($row['_Activated']?
										"150":
										"100") .
									"' />" .
						"</a>" .
					"</div>";
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$this->displayAdminListItemActivation($row);
		
		echo
				"</div>" .
			"</td>" .
			"<td class='auto-width'>" .
				"<div class='admin-content-preview' style='padding-left: 0;'>" .
					"<h2 class='template-name' style='margin: 0;'>" .
						htmlspecialchars(
						($row['_Name']?
							$row['_Name']:
							$row['_ID']) .
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
							url::parseLinks(nl2br(htmlspecialchars($row['_Description']))) .
						"</p>" .
					"</div>" .
					($row['_Tags']?
						"<div class='template-tags'><b>" .
							__("Tags").":</b> " .
							htmlspecialchars($row['_Tags']) .
						"</div>":
						null) .
					"<div class='template-location'><b>" .
						__("Location").":</b> " .
						'template/'.$row['_ID'].'/' .
					"</div>" .
				"</div>" .
			"</td>";
	}
	
	function displayAdminListItemActivation(&$row) {
		$url = url::uri('id, delete, activate, deactivate, setadmin, unsetadmin').
			"&amp;id=".urlencode($row['ID']);
		
		if ($row['_Activated']) {
			echo
				"<div class='button' style='float: none; margin: 10px 0 0 0;'>" .
					"<a href='".$url."&amp;deactivate=1' " .
						"title='".htmlspecialchars(__("Restore default template for your " .
							"website"), ENT_QUOTES)."'>" .
						__("Deactivate") .
					"</a>" .
				"</div>";
			
			$settings = new settings();
			
			if ($settings->get('Website_Template_SetForAdmin')) {
				echo
					"<div class='button' style='float: none; margin: 10px 0 0 0;'>" .
						"<a href='".$url."&amp;unsetadmin=1' " .
							"title='".htmlspecialchars(__("Restore default template for Admin " .
								"section"), ENT_QUOTES)."'>" .
							__("Unset Admin") .
						"</a>" .
					"</div>";
			} else {
				echo
					"<div class='button' style='float: none; margin: 10px 0 0 0;'>" .
						"<a href='".$url."&amp;setadmin=1' " .
							"title='".htmlspecialchars(__("Set template as default for Admin " .
								"section"), ENT_QUOTES)."'>" .
							__("Set Admin") .
						"</a>" .
					"</div>";
			}
			
			unset($settings);
			
		} else {
			echo
				"<div class='button' style='float: none; margin: 10px 0 0 0;'>" .
					"<a href='".$url."&amp;activate=1' " .
						"title='".htmlspecialchars(__("Activate and set it as the current " .
							"template for your website"), ENT_QUOTES)."'>" .
						__("Activate") .
					"</a>" .
				"</div>";
		}
	}
	
	function displayAdminListItemOptions(&$row) {
	}
	
	function displayAdminListItemFunctions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, delete, activate, deactivate, setadmin, unsetadmin') .
					"&amp;id=".urlencode($row['ID'])."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayAdminList(&$templates, $selectedtemplate = null) {
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
		foreach($templates as $template) {
			$row = array();
			$row['ID'] = $template;
			
			$row['_ID'] = $template;
			$row['_Preview'] = $this->rootURL.$template.'/template.jpg';
			$row['_Activated'] = false;
			
			$row += templateManager::parseData(
				files::get($this->rootPath.$template.'/template.php'));
			
			if ($selectedtemplate == $row['_ID'])
				$row['_Activated'] = true;
			
			echo 
				"<tr".($i%2?" class='pair'":NULL).">";
				
			$this->displayAdminListItem($row);
			$this->displayAdminListItemOptions($row);
			
			if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
				$this->displayAdminListItemFunctions($row);
					
			echo
				"</tr>";
			
			if ($row['_Activated'])
				echo
					"</tbody>" .
					"</table>" .
					"<table cellpadding='0' cellspacing='0' class='list'>" .
					"<tbody>";
			
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
		if (JCORE_VERSION >= '0.7') {
			admin::displayTitle(
				__('Template Administration'), 
				$ownertitle);
			return;
		}
		
		echo
			__('Template Administration');
	}
	
	function displayAdminDescription() {
		echo
			"<p>" .
				__("Below are the available templates found in the \"<b>template/</b>\" folder. " .
					"To install a new template just extract it to the " .
					"\"<b>template/</b>\" folder, or using the form below select the " .
					"template package file (e.g. template-name.tar.gz).") .
			"</p>";
	}
	
	function displayAdminSections() {
		echo
			"<div class='admin-section-item as-site-template-css-editor'>" .
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/templatecsseditor' " .
					"title='".htmlspecialchars(__("Edit the CSS template file"), ENT_QUOTES).
					"'>" .
					"<span>" .
					__("Edit CSS File")."" .
					"</span>" .
				"</a>" .
			"</div>" .
			"<div class='admin-section-item as-site-template-js-editor'>" .
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/templatejseditor' " .
					"title='".htmlspecialchars(__("Edit the JavaScript template file"), ENT_QUOTES).
					"'>" .
					"<span>" .
					__("Edit JS File")."" .
					"</span>" .
				"</a>" .
			"</div>" .
			"<div class='admin-section-item as-site-template-files'>" .
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/templateimages' " .
					"title='".htmlspecialchars(__("Browse template Images"), ENT_QUOTES).
					"'>" .
					"<span>" .
					__("Template Images")."" .
					"</span>" .
				"</a>" .
			"</div>" .
			"<div class='admin-section-item as-site-export-template'>" .
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/templateexporter' " .
					"title='".htmlspecialchars(__("Export current template as an installable template package"), ENT_QUOTES).
					"'>" .
					"<span>" .
					__("Export Template")."" .
					"</span>" .
				"</a>" .
			"</div>";
		
		if (JCORE_VERSION >= '0.7')
			echo
				"<div class='admin-section-item as-site-template-download'>" .
					"<a href='http://jcore.net/templates' target='_blank' " .
						"title='".htmlspecialchars(__("Browse and download more templates"), ENT_QUOTES).
						"'>" .
						"<span>" .
						__("Get Templates")."" .
						"</span>" .
					"</a>" .
				"</div>";
	}
	
	function displayAdmin() {
		if (JCORE_VERSION < '0.7')
			$this->displayAdminDescription();
		else
			$this->displayAdminTitle();
		
		echo
			"<div class='admin-content'>";
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			echo 
				"<div tabindex='0' class='fc" .
					form::fcState('fcts', true) .
					"'>" .
					"<a class='fc-title' name='fcts'>";
			
			if (JCORE_VERSION >= '0.7')
				echo
					__("Modify Activated Template");
			else
				$this->displayAdminTitle();
			
			echo
					"</a>" .
					"<div class='fc-content'>";
			
			$this->displayAdminSections();
			
			echo
						"<div class='clear-both'></div>" .
					"</div>" .
				"</div>";
		}
		
		if (JCORE_VERSION >= '0.7') {
			$this->displayAdminDescription();
			
			$form = new form(
				__("Upload New Template"),
				'uploadnewtemplate');
			
			$form->action = url::uri('id, delete, activate, deactivate, setadmin, unsetadmin');
			
			$this->setupAdminForm($form);
			$form->addSubmitButtons();
			
			$verifyok = false;
			
			if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
				$verifyok = $this->verifyAdmin($form);
			
			$templates = array();
			$selectedtemplate = null;
			
			$d = dir($this->rootPath);
			while (false !== ($entry = $d->read())) {
				$entry = preg_replace('/[^a-zA-Z0-9\@\.\_\- ]/', '', $entry);
				
				if (strpos($entry, '.') === 0 ||
					in_array($entry, array('images', 'modules')) ||
					!@is_dir($this->rootPath.$entry) ||
					!@is_file($this->rootPath.$entry.'/template.php'))
					continue;
				
				if (template::$selected && template::$selected['Name'] && 
					template::$selected['Name'] == $entry) 
				{
					$selectedtemplate = $entry;
					continue;
				}
				
				$templates[] = $entry;
			}
			
			$d->close();
			sort($templates);
			
			if ($selectedtemplate)
				$templates = array_pad(
					$templates, -(count($templates)+1), $selectedtemplate);
			
			if (count($templates))
				$this->displayAdminList($templates, $selectedtemplate);
			else
				tooltip::display(
					__("No templates found."),
					TOOLTIP_NOTIFICATION);
			
			if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
				echo
					"<a name='adminform'></a>";
				
				$this->displayAdminForm($form);
			}
			
			unset($form);
		}
		
		echo
			"</div>"; //admin-content
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		$newid = sql::run(
			" INSERT INTO `{templates}` SET" .
			" `Name` = '".
				sql::escape($values['Name'])."'");
		
		if (!$newid) {
			tooltip::display(
				sprintf(__("Template couldn't be added! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return $newid;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{templates}`" .
			" WHERE `Name` = '".sql::escape($id)."'"));
		
		if ($exists) {
			if (!$this->deactivate($id))
				return false;
			
			@include_once($this->rootPath.$exists['Name'].'/template.php');
			
			if (class_exists('templateInstaller') && 
				method_exists('templateInstaller', 'uninstall'))
			{
				$installer = new templateInstaller();
				$installer->templateID = $exists['ID'];
				$success = $installer->uninstall();
				unset($installer);
				
				if (!$success)
					return false;
			}
			
			sql::run(
				" DELETE FROM `{templates}`" .
				" WHERE `ID` = '".(int)$exists['ID']."'");
			
			sql::run(
				" DELETE FROM `{blocks}`" .
				" WHERE `TemplateID` = '".(int)$exists['ID']."'");
			
			if (JCORE_VERSION >= '0.9')
				sql::run(
					" DELETE FROM `{layouts}`" .
					" WHERE `TemplateID` = '".(int)$exists['ID']."'");
		}
		
		if (is_dir($this->rootPath.$id) && 
			!dirs::delete($this->rootPath.$id)) 
		{
			tooltip::display(
				sprintf(__("Template couldn't be deleted but it is now safe " .
					"to be deleted manually by just simply removing " .
					"the \"%s\" folder."), 'template/'.$id.'/'),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	function cleanUp($templateid) {
		if (!$templateid)
			return false;
		
		sql::run(
			" DELETE FROM `{blocks}`" .
			" WHERE `TemplateID` = '".(int)$templateid."'");
		
		if (JCORE_VERSION >= '0.9')
			sql::run(
				" DELETE FROM `{layouts}`" .
				" WHERE `TemplateID` = '".(int)$templateid."'");
		
		if (JCORE_VERSION < '0.9')
			sql::run(
				" DELETE FROM `{templates}`" .
				" WHERE `ID` = '".(int)$templateid."'");
		
		return true;
	}
	
	function activate($id) {
		if (!$id)
			return false;
		
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{templates}`" .
			" WHERE `Name` = '".sql::escape($id)."'"));
		
		if ($exists && (JCORE_VERSION < '0.9' || $exists['Installed'])) {
			$settings = new settings();
			$settings->set('Website_Template', $exists['Name']);
			$settings->set('Website_Template_SetForAdmin', '0');
			unset($settings);
			
			$this->autoSetup($exists['ID']);
			
			template::$selected = $exists;
			return true;
		}
		
		if ($exists)
			$newid = $exists['ID'];
		else
			$newid = $this->add(array(
				'Name' => $id));
		
		if (!$newid)
			return false;
		
		@include_once($this->rootPath.$id.'/template.php');
		
		if (!class_exists('templateInstaller') || 
			!method_exists('templateInstaller', 'install'))
		{
			tooltip::display(
				__("Invalid or template installer script cannot be found."),
				TOOLTIP_ERROR);
			return false;
		}
		
		$installer = new templateInstaller();
		$installer->templateID = $newid;
		$success = $installer->install();
		unset($installer);
		
		if (!$success) {
			$this->cleanUp($newid);
			
			tooltip::display(
				__("Template couldn't be activated!")." " .
				__("Please see detailed error messages above and try again."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$settings = new settings();
		$settings->set('Website_Template', $id);
		$settings->set('Website_Template_SetForAdmin', '0');
		unset($settings);
		
		$template = sql::fetch(sql::run(
			" SELECT * FROM `{templates}`" .
			" WHERE `ID` = '".(int)$newid."'"));
		
		$this->autoSetup($template['ID']);
		
		template::$selected = $template;
		return true;
	}
	
	function deactivate($id) {
		if (!$id)
			return false;
		
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{templates}`" .
			" WHERE `Name` = '".sql::escape($id)."'"));
		
		if (!$exists) {
			tooltip::display(
				__("The template you selected cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$settings = new settings();
		$settings->set('Website_Template', '');
		$settings->set('Website_Template_SetForAdmin', '0');
		unset($settings);
		
		$this->autoSetup();
		
		template::$selected = null;
		return true;
	}
	
	function setAdmin($id) {
		if (!$id)
			return false;
		
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{templates}`" .
			" WHERE `Name` = '".sql::escape($id)."'"));
		
		if (!$exists) {
			tooltip::display(
				__("The template you selected cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$settings = new settings();
		$settings->set('Website_Template_SetForAdmin', '1');
		unset($settings);
		
		return true;
	}
	
	function unsetAdmin($id) {
		if (!$id)
			return false;
		
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{templates}`" .
			" WHERE `Name` = '".sql::escape($id)."'"));
		
		if (!$exists) {
			tooltip::display(
				__("The template you selected cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$settings = new settings();
		$settings->set('Website_Template_SetForAdmin', '0');
		unset($settings);
		
		return true;
	}
	
	function upload($file) {
		if (!$filename = files::upload($file, $this->rootPath, FILE_TYPE_UPLOAD))
			return false;
		
		if (security::checkOutOfMemory(@filesize($this->rootPath.$filename), 3)) {
			tooltip::display(
				__("Couldn't extract template as it is to big to be processed " .
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
				__("Template couldn't be extracted! " .
					"Error: Invalid template! Please make sure to " .
					"upload a valid tar.gz template file."),
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
				__("Template couldn't be extracted! " .
					"Error: Empty template! The template you " .
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
					sprintf(__("Template couldn't be extracted! " .
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
					sprintf(__("Template couldn't be extracted! " .
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
			preg_match('/\/\*.*?'.$variable.': (.*?)((\r|\n) ?\*|\*\/).*?\*\//si', $data, $matches);
			
			if (isset($matches[1]))
				$values['_'.$variable] = trim($matches[1], " \r\n");
		}
		
		return $values;
	}
	
	function autoSetup($templateid = 0) {
		// Set menu blocks to their new places
		
		$rows = sql::run(
			" SELECT * FROM `{menus}`" .
			" ORDER BY `BlockID` = 0, `BlockID`, `OrderID`, `ID`");
		
		$ids = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(`ID` ORDER BY `ID` SEPARATOR '|') AS `IDs`" .
			" FROM `{blocks}`" .
			" WHERE `TemplateID` = '".(int)$templateid."'" .
			" AND `TypeID` = '".BLOCK_TYPE_MENU."'"));
		
		$blockids = array();
		if ($ids['IDs'])
			$blockids = explode('|', $ids['IDs']);
		
		if (count($blockids)) {
			$i = 0;
			$prev = null;
			
			while($row = sql::fetch($rows)) {
				if ($prev && $prev['BlockID'] != $row['BlockID'])
					$i++;
				
				$prev = $row;
				if (!isset($blockids[$i]))
					break;
				
				sql::run(
					" UPDATE `{menus}` SET" .
					" `BlockID` = '".(int)$blockids[$i]."'" .
					" WHERE `ID` = '".$row['ID']."'");
			}
		}
		
		// Set ads to their new places
		
		$rows = sql::run(
			" SELECT * FROM `{ads}`" .
			" ORDER BY `BlockID` = 0, `BlockID`, `OrderID`, `ID`");
		
		$ids = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(`ID` ORDER BY `ID` SEPARATOR '|') AS `IDs`" .
			" FROM `{blocks}`" .
			" WHERE `TemplateID` = '".(int)$templateid."'" .
			" AND `TypeID` = '".BLOCK_TYPE_AD."'"));
		
		$blockids = array();
		if ($ids['IDs'])
			$blockids = explode('|', $ids['IDs']);
		
		if (count($blockids)) {
			$i = 0;
			$prev = null;
			
			while($row = sql::fetch($rows)) {
				if ($prev && $prev['BlockID'] != $row['BlockID'])
					$i++;
				
				$prev = $row;
				if (!isset($blockids[$i]))
					break;
				
				sql::run(
					" UPDATE `{ads}` SET" .
					" `BlockID` = '".(int)$blockids[$i]."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".$row['ID']."'");
			}
		}
		
		// Set block posts to their new places
		
		$rows = sql::run(
			" SELECT * FROM `{posts}`" .
			" WHERE `BlockID` > 0" .
			" ORDER BY `BlockID`, `OrderID`, `ID`");
		
		$ids = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(`ID` ORDER BY `ID` SEPARATOR '|') AS `IDs`" .
			" FROM `{blocks}`" .
			" WHERE `TemplateID` = '".(int)$templateid."'" .
			" AND `TypeID` = '".BLOCK_TYPE_CONTENT."'"));
		
		$blockids = array();
		if ($ids['IDs'])
			$blockids = explode('|', $ids['IDs']);
		
		if (count($blockids)) {
			$i = 0;
			$prev = null;
			
			while($row = sql::fetch($rows)) {
				if ($prev && $prev['BlockID'] != $row['BlockID'])
					$i++;
				
				$prev = $row;
				if (!isset($blockids[$i]))
					break;
				
				sql::run(
					" UPDATE `{posts}` SET" .
					" `BlockID` = '".(int)$blockids[$i]."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".$row['ID']."'");
			}
		}
	}
}

?>