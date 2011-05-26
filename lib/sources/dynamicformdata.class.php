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
	var $storageSQLTable;
	var $storagePath;
	var $storageURL;
	var $storageSubFolder;
	var $adminPath = 'admin/content/dynamicforms/dynamicformdata';
	
	function __construct() {
		$this->storageSubFolder = date('Ym');
		$this->storagePath = SITE_PATH.'sitefiles/file/';
		$this->storageURL = url::site().'sitefiles/file/';
	}
	
	// ************************************************   Admin Part
	function setupAdmin() {
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
	}
	
	function setupAdminForm(&$form, $owner) {
		$dynamicform = new dynamicForms($owner['FormID']);
		$dynamicform->textsDomain = 'messages';
		$dynamicform->load();
		
		$form->ignorePageBreaks = true;
		$form->elements = $dynamicform->elements;
		$form->fileElements = $dynamicform->fileElements;
		$form->recipientElements = $dynamicform->recipientElements;
		$form->pageBreakElements = $dynamicform->pageBreakElements;
		
		unset($dynamicform);
	}
	
	function verifyAdmin(&$form) {
		$search = null;
		$deleteall = null;
		$delete = null;
		$edit = null;
		$id = null;
		$ids = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_POST['deleteallsubmit']))
			$deleteall = $_POST['deleteallsubmit'];
		
		if (isset($_POST['deletesubmit']))
			$delete = $_POST['deletesubmit'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
		if ($deleteall) {
			$owner = sql::fetch(sql::run(
				" SELECT * FROM `{dynamicforms}`" .
				" WHERE `ID` = '".admin::getPathID()."'"));
			
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
				
			return true;
		}
		
		if ($ids && count($ids) && $delete) {
			foreach($ids as $id)
				$this->delete($id);
			
			tooltip::display(
				__("Data has been successfully deleted."),
				TOOLTIP_SUCCESS);
				
			return true;
		}
			
		if ($delete) {
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				__("Data has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		$ignorefields = null;
		
		if ($form->fileElements && is_array($form->fileElements) && 
			count($form->fileElements)) 
		{
			foreach($form->fileElements as $fieldid) {
				if (!$file = $form->getFile($fieldid)) {
					$ignorefields[] = $form->elements[$fieldid]['Name'];
					continue;
				}
				
				if (!$filename = $this->upload($file))
					return false;
				
				$form->set($form->elements[$fieldid]['Name'], 
					$this->storageSubFolder.'/'.$filename);
			}
		}
		
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray(), $ignorefields))
				return false;
				
			tooltip::display(
				__("Data has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$newid = $this->add($form->getPostArray()))
			return false;
			
		tooltip::display(
			__("Data has been successfully added.")." " .
			"<a href='".url::uri('id, edit, delete') .
				"&amp;id=".$newid."&amp;edit=1#adminform'>" .
				__("Edit") .
			"</a>",
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminListHeader(&$form) {
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
				" WHERE `FormID` = '".admin::getPathID()."'" .
				" AND `DataPreview`" .
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
	
	function displayAdminListItem(&$row, &$form) {
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
				" WHERE `FormID` = '".admin::getPathID()."'" .
				" AND `DataPreview`" .
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
					"<a href='".$this->storageURL.'/' .
						$row[$element['Name']]."'>";
			 
			switch($element['ValueType']) {
				case FORM_VALUE_TYPE_BOOL:
					echo 
						($element['Name']?
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
	}
	
	function displayAdminListItemOptions(&$row) {
	}
	
	function displayAdminListItemFunctions(&$row) {
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
	}
	
	function displayAdminListItemSelected(&$row, &$form) {
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
					"<a href='".$this->storageURL."/" .
						$row[$elements[$fieldid]['Name']]."'>";
				
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
	}
	
	function displayAdminListSearch() {
		$search = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		echo
			"<input type='hidden' name='path' value='".admin::path()."' />" .
			"<input type='search' name='search' value='".
				htmlspecialchars($search, ENT_QUOTES).
				"' results='5' placeholder='".
				htmlspecialchars(__("search..."), ENT_QUOTES)."' /> " .
			"<input type='submit' value='" .
				htmlspecialchars(__("Search"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminListFunctions() {
		echo
			"<input type='submit' name='deletesubmit' value='" .
				htmlspecialchars(__("Delete"), ENT_QUOTES) .
				"' class='button confirm-link' /> " .
			"<input type='submit' name='deleteallsubmit' value='" .
				htmlspecialchars(__("Delete All"), ENT_QUOTES) .
				"' class='button confirm-link' /> ";
	}
	
	function displayAdminList(&$rows, &$form) {
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
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Data'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$search = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		$owner = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicforms}`" .
			" WHERE `ID` = '".admin::getPathID()."'"));
			
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
			return false;
		}
		
		if (!$this->storageSQLTable) {
			tooltip::display(
				__("No SQL Table defined for this form."),
				TOOLTIP_NOTIFICATION);
				
			echo "</div>";
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
		
		$newid = sql::run(
			" INSERT INTO `{" .$this->storageSQLTable. "}`" .
			" SET ".substr($query, 0, -1));
		
		if (!$newid) {
			tooltip::display(
				sprintf(__("Could not save data to the DB! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
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
		
		sql::run(
			" UPDATE `{".$this->storageSQLTable."}` SET ".
			substr($query, 0, -1) .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Could not update DB data! Error: %s"), 
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
			" DELETE FROM `{".$this->storageSQLTable. "}`" .
			" WHERE `ID` = '".$id."'");
		
		return true;
	}
	
	function upload($file, $to = null) {
		if (!$to)
			$to = $this->storagePath;
		
		if (!$filename = files::upload($file, $to.$this->storageSubFolder.'/'))
			return false;
		
		return $filename;
	}
}

?>