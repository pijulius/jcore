<?php

/***************************************************************************
 *            dynamicformdata.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
class _dynamicFormData {
	var $formID = 0;
	var $storageSQLTable;
	var $storagePath;
	var $storageSubFolder;
	var $adminPath = 'admin/content/dynamicforms/dynamicformdata';
	
	function __construct($formid = null) {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::dynamicFormData', $this, $formid);
		
		$this->storageSubFolder = date('Ym');
		$this->storagePath = SITE_PATH.'sitefiles/file/';
		
		if (!$formid && isset($GLOBALS['ADMIN']) && (bool)$GLOBALS['ADMIN'])
			$formid = admin::getPathID();
		
		if ($formid) {
			$form = dynamicForms::getForm($formid);
			$this->formID = $form['ID'];
			$this->storageSQLTable = $form['SQLTable'];
			
			if (JCORE_VERSION >= '1.0' && $form['StorageDirectory'])
				$this->storagePath .= $form['StorageDirectory'].'/';
		}
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::dynamicFormData', $this, $formid);
	}
	
	// ************************************************   Admin Part
	function setupAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::setupAdmin', $this);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Data'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Forms'), 
			'?path=admin/content/dynamicforms');
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=' .
			(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::setupAdmin', $this);
	}
	
	function setupAdminForm(&$form, $owner) {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::setupAdminForm', $this, $form, $owner);
		
		$dynamicform = new dynamicForms($owner['FormID']);
		$dynamicform->textsDomain = 'messages';
		$dynamicform->load();
		
		$form->ignorePageBreaks = true;
		$form->elements = $dynamicform->elements;
		$form->fileElements = $dynamicform->fileElements;
		$form->recipientElements = $dynamicform->recipientElements;
		$form->pageBreakElements = $dynamicform->pageBreakElements;
		
		unset($dynamicform);
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::setupAdminForm', $this, $form, $owner);
	}
	
	function verifyAdmin(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::verifyAdmin', $this, $form);
		
		$search = null;
		$deleteall = null;
		$exportall = null;
		$delete = null;
		$edit = null;
		$id = null;
		$ids = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags((string)$_GET['search']));
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_POST['deleteallsubmit']))
			$deleteall = (string)$_POST['deleteallsubmit'];
		
		if (isset($_POST['exportallsubmit']))
			$exportall = (string)$_POST['exportallsubmit'];
		
		if (isset($_POST['deletesubmit']))
			$delete = (string)$_POST['deletesubmit'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
		if ($deleteall) {
			$owner = dynamicForms::getForm($this->formID);
			
			sql::run(
				" DELETE FROM `{".$this->storageSQLTable."}`" .
				" WHERE 1" .
				($search?
					sql::search(
						$search,
						(JCORE_VERSION >= '0.7'? 
							dynamicForms::searchableFields($owner['FormID']):
							array('ID'))):
					null));
			
			tooltip::display(
				__("Data has been successfully deleted."),
				TOOLTIP_SUCCESS);
				
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormData::verifyAdmin', $this, $form, $deleteall);
			
			return true;
		}
		
		if ($exportall) {
			$owner = dynamicForms::getForm($this->formID);
			
			if (!$file = $this->export(
				($search?
					" WHERE 1" .
					sql::search(
						$search,
						(JCORE_VERSION >= '0.7'? 
							dynamicForms::searchableFields($owner['FormID']):
							array('ID'))):
					null)))
			{
				api::callHooks(API_HOOK_AFTER,
					'dynamicFormData::verifyAdmin', $this, $form);
				
				return false;
			}
			
			tooltip::display(
				__("Data has been successfully exported.")." " .
				"<a href='".url::uri('id, edit, delete, request, download') .
					"&amp;request=".url::path() .
					"&amp;download=".$file .
					"&amp;ajax=1'>" .
					__("Download") .
				"</a>" .
				"<script type='text/javascript'>" .
					"$(document).ready(function() {" .
						"window.location='".url::uri('id, edit, delete, request, download') .
							"&request=".url::path() .
							"&download=".$file .
							"&ajax=1';" .
					"});" .
				"</script>",
				TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormData::verifyAdmin', $this, $form, $file);
			
			return true;
		}
		
		if ($ids && count($ids) && $delete) {
			foreach($ids as $id)
				$this->delete((int)$id);
			
			tooltip::display(
				__("Data has been successfully deleted."),
				TOOLTIP_SUCCESS);
				
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormData::verifyAdmin', $this, $form, $delete);
			
			return true;
		}
			
		if ($delete) {
			$result = $this->delete($id);
			
			if ($result)
				tooltip::display(
					__("Data has been successfully deleted."),
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormData::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormData::verifyAdmin', $this, $form);
			
			return false;
		}
		
		$ignorefields = null;
		
		if ($form->fileElements && is_array($form->fileElements) && 
			count($form->fileElements)) 
		{
			foreach($form->fileElements as $fieldid) {
				if (!$file = $form->getFile($fieldid)) {
					$ignorefields[] = $form->elements[$fieldid]['Name'];
					continue;
				}
				
				if (!$filename = $this->upload($file)) {
					api::callHooks(API_HOOK_AFTER,
						'dynamicFormData::verifyAdmin', $this, $form);
					
					return false;
				}
				
				$form->set($form->elements[$fieldid]['Name'], 
					$this->storageSubFolder.'/'.$filename);
			}
		}
		
		if ($edit) {
			$result = $this->edit($id, $form->getPostArray(), $ignorefields);
			
			if ($result)
				tooltip::display(
					__("Data has been successfully updated.")." " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormData::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		$newid = $this->add($form->getPostArray());
		
		if ($newid) {
			tooltip::display(
				__("Data has been successfully added.")." " .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$newid."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			$form->reset();
		}
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::verifyAdmin', $this, $form, $newid);
		
		return $newid;
	}
	
	function displayAdminListHeader(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::displayAdminListHeader', $this, $form);
		
		echo
			"<th>" .
				"<input type='checkbox' class='checkbox-all' " .
				(~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE?
					"disabled='disabled' ":
					null) .
				"/>" .
			"</th>" .
			"<th><span class='nowrap'>".
				__("ID")."</span></th>";
		
		$fields = null;
		$maxcolumns = 2;
		
		if (JCORE_VERSION >= '0.7') {
			$previewfields = sql::run(
				" SELECT `Title`, `Name`" .
				" FROM `{dynamicformfields}`" .
				" WHERE `FormID` = '".$this->formID."'" .
				" AND `DataPreview` = 1" .
				" ORDER BY `OrderID`, `ID`");
			
			while($field = sql::fetch($previewfields)) {
				$exists = sql::fetch(sql::run(
					" SHOW COLUMNS FROM `{".$this->storageSQLTable . "}`" .
					" WHERE `Field` = '".sql::escape($field['Name'])."'"));
				
				if (!$exists)
					continue;
				
				$fields[] = $field;
				$maxcolumns = 0;
			}
		}
		
		if (!$fields)
			$fields = $form->elements;
		
		$column = 0;
		foreach($fields as $field) {
			$exists = sql::fetch(sql::run(
				" SHOW COLUMNS FROM `{".$this->storageSQLTable . "}`" .
				" WHERE `Field` = '".sql::escape($field['Name'])."'"));
			
			if (!$exists)
				continue;
			
			if ($maxcolumns && $column > $maxcolumns)
				break;
			
			echo
				"<th" .
					($column?
						" style='text-align: right;'":
						null) .
					"><span class='nowrap'>".
					$field['Title']."</span></th>";
			
			$column++;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::displayAdminListHeader', $this, $form);
	}
	
	function displayAdminListHeaderOptions() {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::displayAdminListHeaderOptions', $this);
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::displayAdminListHeaderFunctions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row, &$form) {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::displayAdminListItem', $this, $row, $form);
		
		$ids = null;
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
		echo
			"<td>" .
				"<input type='checkbox' name='ids[]' " .
					"value='".$row['ID']."' " .
					($ids && in_array($row['ID'], $ids)?
						"checked='checked' ":
						null).
					(~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE?
						"disabled='disabled' ":
						null) .
					" />" .
			"</td>" .
			"<td align='right'>" .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."' " .
				" class='bold'>" .
				"#".$row['ID'] .
				"</a>" .
			"</td>";
	
		$fields = null;
		$maxcolumns = 2;
		
		if (JCORE_VERSION >= '0.7') {
			$previewfields = sql::run(
				" SELECT `Title`, `Name`, `ValueType`, `TypeID` AS `Type`" .
				" FROM `{dynamicformfields}`" .
				" WHERE `FormID` = '".$this->formID."'" .
				" AND `DataPreview` = 1" .
				" ORDER BY `OrderID`, `ID`");
			
			while($field = sql::fetch($previewfields)) {
				$exists = sql::fetch(sql::run(
					" SHOW COLUMNS FROM `{".$this->storageSQLTable . "}`" .
					" WHERE `Field` = '".sql::escape($field['Name'])."'"));
				
				if (!$exists)
					continue;
				
				$fields[] = $field;
				$maxcolumns = 0;
			}
		}
		
		if (!$fields)
			$fields = $form->elements;
		
		$column = 0;
		foreach($fields as $key => $element) {
			$exists = sql::fetch(sql::run(
				" SHOW COLUMNS FROM `{".$this->storageSQLTable . "}`" .
				" WHERE `Field` = '".sql::escape($element['Name'])."'"));
		
			if (!$exists)
				continue;
		
			if ($maxcolumns && $column > $maxcolumns)
				break;
			
			echo
				"<td" .
					($column === 0?
						" class='auto-width'":
						" style='text-align: right;'") .
					">" .
					($column?
						"<span class='nowrap'>":
						null);
			
			if ($element['Type'] == FORM_INPUT_TYPE_FILE)
				echo
					"<a href='".url::uri('id, edit, delete, request, download') .
						"&amp;request=".url::path() .
						"&amp;download=".$row[$element['Name']] .
						"&amp;ajax=1'>";
			 
			switch($element['ValueType']) {
				case FORM_VALUE_TYPE_BOOL:
					echo 
						($row[$element['Name']]?
							__('Yes'):
							__('No'));
					break;
				case FORM_VALUE_TYPE_TIMESTAMP:
				case FORM_VALUE_TYPE_DATE:
					echo 
						$row[$element['Name']];
					break;
				case FORM_VALUE_TYPE_ARRAY:
					echo 
						implode('; ', explode('|', $row[$element['Name']]));
					break;
				case FORM_VALUE_TYPE_HTML:
				case FORM_VALUE_TYPE_TEXT:
					echo
						preg_replace('/<separator>.*/s', '...',
							wordwrap(strip_tags($row[$element['Name']]), 50, '<separator>'));
					break;
				case FORM_VALUE_TYPE_STRING:
				case FORM_VALUE_TYPE_INT:
				case FORM_VALUE_TYPE_URL:
				case FORM_VALUE_TYPE_LIMITED_STRING:
				case FORM_VALUE_TYPE_FILE:
				default:
					echo
						$row[$element['Name']];
			}
			
			if ($element['Type'] == FORM_INPUT_TYPE_FILE)
				echo
					"</a>";
			
			echo
				($column?
					"</span>":
					null) .
				"</td>";
			
			$column++;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::displayAdminListItem', $this, $row, $form);
	}
	
	function displayAdminListItemOptions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::displayAdminListItemOptions', $this, $row);
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::displayAdminListItemFunctions', $this, $row);
		
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
			'dynamicFormData::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminListItemSelected(&$row, &$form) {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::displayAdminListItemSelected', $this, $row, $form);
		
		$elements = $form->elements;
		
		if ($form->fileElements && is_array($form->fileElements) && 
			count($form->fileElements)) 
		{
			foreach($form->fileElements as $fieldid) {
				if (!isset($row[$elements[$fieldid]['Name']]) ||
					!$row[$elements[$fieldid]['Name']])
					continue;
				
				$elements[$fieldid]['AdditionalPreText'] =
					(isset($elements[$fieldid]['AdditionalPreText'])?
						$elements[$fieldid]['AdditionalPreText']:
						null) .
					"<a href='".url::uri('id, edit, delete, request, download') .
						"&amp;request=".url::path() .
						"&amp;download=".$row[$elements[$fieldid]['Name']] .
						"&amp;ajax=1'>";
				
				$elements[$fieldid]['AdditionalText'] =
					"</a>" .
					(isset($elements[$fieldid]['AdditionalText'])?
						$elements[$fieldid]['AdditionalText']:
						null);
			}
		}
		
		$dynamicform = new dynamicForms();
		$dynamicform->elements = $elements;
		$dynamicform->displayData($row);
		unset($dynamicform);
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::displayAdminListItemSelected', $this, $row, $form);
	}
	
	function displayAdminListSearch() {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::displayAdminListSearch', $this);
		
		$search = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags((string)$_GET['search']));
		
		echo
			"<input type='hidden' name='path' value='".admin::path()."' />" .
			"<input type='search' name='search' value='".
				htmlspecialchars($search, ENT_QUOTES).
				"' results='5' placeholder='".
				htmlspecialchars(__("search..."), ENT_QUOTES)."' /> " .
			"<input type='submit' value='" .
				htmlspecialchars(__("Search"), ENT_QUOTES)."' class='button' />";
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::displayAdminListSearch', $this);
	}
	
	function displayAdminListFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::displayAdminListFunctions', $this);
		
		echo
			"<input type='submit' name='deletesubmit' value='" .
				htmlspecialchars(__("Delete"), ENT_QUOTES) .
				"' class='button confirm-link' /> " .
			"<input type='submit' name='deleteallsubmit' value='" .
				htmlspecialchars(__("Delete All"), ENT_QUOTES) .
				"' class='button confirm-link' /> " .
			"<input type='submit' name='exportallsubmit' value='" .
				htmlspecialchars(__("Export All"), ENT_QUOTES) .
				"' class='button' /> ";
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::displayAdminListFunctions', $this);
	}
	
	function displayAdminList(&$rows, &$form) {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::displayAdminList', $this, $rows, $form);
		
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		echo
			"<form action='".
				url::uri('edit, delete')."' method='post'>";
		
		echo "<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>";
		
		$this->displayAdminListHeader($form);
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
			
			$this->displayAdminListItem($row, $form);
			$this->displayAdminListItemOptions($row);
			
			if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
				$this->displayAdminListItemFunctions($row);
			
			echo
				"</tr>";
			
			if ($row['ID'] == $id) {
				echo
					"<tr".($i%2?" class='pair'":NULL).">" .
						"<td class='auto-width' colspan='10'>" .
							"<div class='admin-content-preview'>";
				
				$this->displayAdminListItemSelected($row, $form);
				
				echo
							"</div>" .
						"</td>" .
					"</tr>";
			}
			
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
			'dynamicFormData::displayAdminList', $this, $rows, $form);
	}
	
	function displayAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::displayAdminForm', $this, $form);
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::displayAdminTitle', $this, $ownertitle);
		
		admin::displayTitle(
			__('Data'),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::displayAdminDescription', $this);
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::displayAdmin', $this);
		
		$search = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags((string)$_GET['search']));
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if (!$this->formID)
			$this->formID = admin::getPathID();
		
		$owner = dynamicForms::getForm($this->formID);
		$this->storageSQLTable = $owner['SQLTable'];
		
		echo
			"<div style='float: right;'>" .
				"<form action='".url::uri('ALL')."' method='get'>";
		
		$this->displayAdminListSearch();
		
		echo
				"</form>" .
			"</div>";
		
		$this->displayAdminTitle($owner['Title']);
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
			
		if (JCORE_VERSION < '0.7' && $owner['Protected']) {
			tooltip::display(
				__("You are NOT allowed to access form data!"),
				TOOLTIP_ERROR);
				
			echo "</div>";
			
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormData::displayAdmin', $this);
			
			return false;
		}
		
		if (JCORE_VERSION >= '0.7' && $owner['BrowseDataURL']) {
			tooltip::display(
				__("You should be automatically redirected to browse form data, if for some " .
					"reason you aren't please click the \"Browse Data\" button below."),
				TOOLTIP_NOTIFICATION);
				
			echo
				"<div class='button'>" .
					"<a href='".$owner['BrowseDataURL']."'>" .
						__("Browse Data") .
					"</a>" .
				"</div>" .
				"<script type='text/javascript'>" .
					"window.location='".$owner['BrowseDataURL']."';" .
				"</script>" .
				"<div class='clear-both'></div>";
				
			echo "</div>";
			
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormData::displayAdmin', $this);
			
			return false;
		}
		
		if (!$this->storageSQLTable) {
			tooltip::display(
				__("No SQL Table defined for this form."),
				TOOLTIP_NOTIFICATION);
				
			echo "</div>";
			
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormData::displayAdmin', $this);
			
			return false;
		}
		
		$form = new form(
				($edit?
					__("Edit Data"):
					__("New Data")),
				'neweditdata');
					
		if (!$edit)
			$form->action = url::uri('id, delete, limit');
		
		$this->setupAdminForm($form, $owner);
				
		if ($edit) {
			if ($form->fileElements && is_array($form->fileElements) && 
				count($form->fileElements)) 
				foreach($form->fileElements as $fieldid)
					$form->elements[$fieldid]['Required'] = false;
			
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
		
		$paging = new paging(20);
		$paging->ignoreArgs = 'id, edit, delete';
		
		$rows = sql::run(
			" SELECT * FROM `{".$this->storageSQLTable."}`" .
			" WHERE 1" .
			($search?
				sql::search(
					$search,
					(JCORE_VERSION >= '0.7'? 
						dynamicForms::searchableFields($owner['FormID']):
						array('ID'))):
				null) .
			" ORDER BY `ID` DESC" .
			" LIMIT ".$paging->limit);
		
		$paging->setTotalItems(sql::count());
		
		if ($paging->items)
			$this->displayAdminList($rows, $form);
		else
			tooltip::display(
				__("No data found."),
				TOOLTIP_NOTIFICATION);
		
		$paging->display();
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{".$this->storageSQLTable."}`" .
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
			'dynamicFormData::displayAdmin', $this);
	}
	
	function add($values, $ignorefields = null) {
		if (!is_array($values))
			return false;
		
		$newid = 0;
		$query = null;
	
		$rows = sql::run(
			" SHOW COLUMNS FROM `{".$this->storageSQLTable."}`");
	
		$fields = array();
		$nullfields = array();
		$nowfields = array();
		
		while($row = sql::fetch($rows)) {
			$fields[$row['Field']] = $row['Field'];
			$nullfields[$row['Field']] = (strtolower($row['Null']) == 'yes'?true:false);
			
			if (in_array(strtolower($row['Type']), array(
				'date', 'datetime', 'timestamp', 'time', 'year')) &&
				!$nullfields[$row['Field']])
				$nowfields[$row['Field']] = true;
			else
				$nowfields[$row['Field']] = false;
		}
	
		foreach($values as $field => $value) { 
			if ($ignorefields && is_array($ignorefields) &&
				count($ignorefields) && in_array($field, $ignorefields))
				continue;
			
			if (in_array($field, $fields))
				$query .= " `".$field."` = " .
					($nullfields[$field] && !$value?
						"NULL":
						($nowfields[$field] && !$value?
							"NOW()":
							"'".
							(is_array($value)?
								sql::escape(implode('|', $value)):
								sql::escape($value)).
							"'")) .
					",";
		}
		
		if (!$query) {
			tooltip::display(
				__("Nothing to store! Please define some fields for your form and make sure " .
					"their Value Types are set."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::add', $this, $values, $ignorefields);
		
		$newid = sql::run(
			" INSERT INTO `{" .$this->storageSQLTable. "}`" .
			" SET ".substr($query, 0, -1));
		
		if (!$newid)
			tooltip::display(
				sprintf(__("Could not save data to the DB! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::add', $this, $values, $ignorefields, $newid);
		
		return $newid;
	}
	
	function edit($id, $values, $ignorefields = null) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		$query = null;
	
		$rows = sql::run(
			" SHOW COLUMNS FROM `{".$this->storageSQLTable."}`");
	
		$fields = array();
		$nullfields = array();
		$nowfields = array();
		
		while($row = sql::fetch($rows)) {
			$fields[$row['Field']] = $row['Field'];
			$nullfields[$row['Field']] = (strtolower($row['Null']) == 'yes'?true:false);
			
			if (in_array(strtolower($row['Type']), array(
				'date', 'datetime', 'timestamp', 'time', 'year')) &&
				!$nullfields[$row['Field']])
				$nowfields[$row['Field']] = true;
			else
				$nowfields[$row['Field']] = false;
		}
		
		foreach($values as $field => $value) {
			if ($ignorefields && is_array($ignorefields) &&
				count($ignorefields) && in_array($field, $ignorefields))
				continue;
			
			if (in_array($field, $fields))
				$query .= " `".$field."` = " .
					($nullfields[$field] && !$value?
						"NULL":
						($nowfields[$field] && !$value?
							"NOW()":
							"'".
							(is_array($value)?
								sql::escape(implode('|', $value)):
								sql::escape($value)).
							"'")) .
					",";
		}
		
		if (!$query) {
			tooltip::display(
				__("Nothing to store! Please define some fields for your form and make sure " .
					"their Value Types are set."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::edit', $this, $id, $values, $ignorefields);
		
		sql::run(
			" UPDATE `{".$this->storageSQLTable."}` SET ".
			substr($query, 0, -1) .
			" WHERE `ID` = '".(int)$id."'");
			
		$result = (sql::affected() != -1);
		
		if (!$result)
			tooltip::display(
				sprintf(__("Could not update DB data! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::edit', $this, $id, $values, $ignorefields, $result);
		
		return $result;
	}
	
	function delete($id) {
		if (!$id)
			return false;
			
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::delete', $this, $id);
		
		sql::run(
			" DELETE FROM `{".$this->storageSQLTable. "}`" .
			" WHERE `ID` = '".$id."'");
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::delete', $this, $id);
		
		return true;
	}
	
	function export($searchquery = null) {
		if (!$this->storageSQLTable)
			return false;
		
		$filename = 'form-data-'.$this->storageSQLTable.'-'.date('Y-m-d').'.csv';
		$file = SITE_PATH.'sitefiles/var/forms/'.$filename;
		
		if (!files::create($file, '')) {
			tooltip::display(
				__("File couldn't be saved!")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					SITE_PATH.'sitefiles/var/forms/'),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!$fp = @fopen($file, 'w'))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::export', $this, $searchquery, $filename);
		
		$rows = sql::run(
			" SELECT * FROM `{".$this->storageSQLTable."}`" .
			($searchquery?
				$searchquery:
				null) .
			" ORDER BY `ID` DESC");
		
		$firstrow = true;
		while ($row = sql::fetch($rows)) {
			if ($firstrow) {
				foreach($row as $key => $data)
					fwrite($fp, '"'.str_replace('"', '""', $key).'",');
				
				fwrite($fp, "\r\n");
			}
			
			foreach($row as $key => $data)
				fwrite($fp, '"'.str_replace('"', '""', $data).'",');
			
			fwrite($fp, "\r\n");
			$firstrow = false;
		}
		
		fclose($fp);
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::export', $this, $searchquery, $filename);
		
		return $filename;
	}
	
	function upload($file, $to = null) {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::upload', $this, $file, $to);
		
		if (!$to)
			$to = $this->storagePath;
		
		$filename = files::upload($file, $to.$this->storageSubFolder.'/');
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::upload', $this, $file, $to, $filename);
		
		return $filename;
	}
	
	function download($file) {
		if (!is_file($file)) {
			tooltip::display(
				sprintf(__("File \"%s\" cannot be found!"),
					$file),
				TOOLTIP_ERROR);
			
			return false;
		}

		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::download', $this, $file);
		
		session_write_close();
		files::display($file, true);
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::download', $this, $file);
		
		return true;
	}
	
	function ajaxRequest() {
		api::callHooks(API_HOOK_BEFORE,
			'dynamicFormData::ajaxRequest', $this);
		
		$download = null;
		
		if (isset($_GET['download'])) {
			preg_match('/(([0-9]+\/)?[^(\/|\\\)]*)$/', strip_tags((string)$_GET['download']), $matches);
			
			if (isset($matches[1]) && $matches[1] != '.' && $matches[1] != '..')
				$download = $matches[1];
		}
		
		if (!$GLOBALS['USER']->loginok || 
			!$GLOBALS['USER']->data['Admin']) 
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormData::ajaxRequest', $this);
			
			return true;
		}
		
		$permission = userPermissions::check(
			(int)$GLOBALS['USER']->data['ID'],
			$this->adminPath);
		
		if (~$permission['PermissionType'] & USER_PERMISSION_TYPE_WRITE) {
			tooltip::display(
				__("You do not have permission to access this path!"),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormData::ajaxRequest', $this);
			
			return true;
		}
		
		if ($download) {
			if (strpos($download, '/') !== false)
				$file = $this->storagePath.$download;
			else
				$file = SITE_PATH.'sitefiles/var/forms/'.$download;
			
			$result = $this->download($file);
			
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormData::ajaxRequest', $this, $result);
			
			return $result;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormData::ajaxRequest', $this);
		
		return true;
	}
}

?>