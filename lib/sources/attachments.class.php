<?php

/***************************************************************************
 *            attachments.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/

include_once('lib/files.class.php');
include_once('lib/calendar.class.php');

class _attachments {
	var $limit = 0;
	var $ignorePaging = false;
	var $showPaging = true;
	var $sqlTable;
	var $sqlRow;
	var $sqlOwnerTable;
	var $sqlOwnerField = 'Title';
	var $sqlOwnerCountField = 'Attachments';
	var $selectedOwner;
	var $selectedOwnerID;
	var $customLink;
	var $uriRequest;
	var $subFolder;
	var $rootPath;
	var $rootURL;
	var $resumableDownloads = true;
	var $ajaxPaging = AJAX_PAGING;
	var $ajaxRequest = null;
	
	function __construct() {
		$this->uriRequest = strtolower(get_class($this));
		$this->subFolder = date('Ym');
		$this->rootPath = SITE_PATH.'sitefiles/file/';
		$this->rootURL = url::site().'sitefiles/file/';
		
		if ($this->sqlRow && isset($_GET[strtolower($this->sqlRow)]))
			$this->selectedOwnerID = (int)$_GET[strtolower($this->sqlRow)];
	}
	
	function SQL() {
		return
			" SELECT * FROM `{" .$this->sqlTable . "}`" .
			" WHERE 1" .
			($this->sqlRow?
				" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
				null) .
			" ORDER BY `OrderID`, `ID` DESC";
	}
	
	// ************************************************   Admin Part
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Attachment'),
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Content Files'), 
			'?path=admin/content/contentfiles');
	}
	
	function setupAdminForm(&$form) {
		$edit = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (!$edit) {
			$form->add(
				"<b>".__("Upload a new attachment")."</b>",
				'',
				FORM_STATIC_TEXT);
					
			$form->add(
				__('Attachment'),
				'Files[]',
				FORM_INPUT_TYPE_FILE);
			$form->setValueType(FORM_VALUE_TYPE_FILE);
			$form->setAttributes("multiple='multiple'");
			
			if (JCORE_VERSION >= '0.6')
				$form->setTooltipText(__("e.g. document.doc, document.pdf"));
			else
				$form->addAdditionalText(" (".__("e.g. document.doc, document.pdf").")");
			
			$form->add(
				"<div class='form-entry-upload-multi-attachments-container'></div>" .
				"<div class='form-entry-title'></div>" .
				"<div class='form-entry-content'>" .
					"<a href='javascript://' class='add-link' " .
						"onclick=\"jQuery.jCore.form.appendEntryTo(" .
							"'.form-entry-upload-multi-attachments-container', " .
							"'', " .
							"'Files[]', " .
							FORM_INPUT_TYPE_FILE."," .
							"false, ''," .
							"'multiple');\">" .
						__("Upload another file") .
					"</a>" .
				"</div>",
				null,
				FORM_STATIC_TEXT);
		}
			
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 300px;');
		
		if ($edit) {
			$form->add(
				__('Attachment'),
				'File',
				FORM_INPUT_TYPE_FILE);
			$form->setValueType(FORM_VALUE_TYPE_FILE);
			
			if (JCORE_VERSION >= '0.6')
				$form->setTooltipText(__("e.g. document.doc, document.pdf"));
			else
				$form->addAdditionalText(" (".__("e.g. document.doc, document.pdf").")");
		}
		
		if (!$edit) {
			$form->add(
				__('Already uploaded attachment'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
		
			$form->add(
				__('Attachment'),
				'AttachmentID',
				FORM_INPUT_TYPE_SELECT);
			$form->setValueType(FORM_VALUE_TYPE_INT);
			$form->setStyle('width: 300px;');
			
			$form->addValue(
				'',
				'');
				
			$form->add(
				__('Existing File (URL)'),
				'Location',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 350px;');
			$form->setTooltipText(__("e.g. http://domain.com/document.pdf"));
			
			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER);
		}
		
		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Uploaded on'),
			'TimeStamp',
			FORM_INPUT_TYPE_TIMESTAMP);
		$form->setStyle('width: 170px;');
		$form->setValueType(FORM_VALUE_TYPE_TIMESTAMP);
		
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
	}
	
	function verifyAdmin(&$form) {
		$reorder = null;
		$orders = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_POST['reordersubmit']))
			$reorder = $_POST['reordersubmit'];
		
		if (isset($_POST['orders']))
			$orders = (array)$_POST['orders'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($reorder) {
			if (!$orders)
				return false;
			
			foreach($orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{".$this->sqlTable ."}`" .
					" SET `OrderID` = '".(int)$ovalue."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".(int)$oid."'");
			}
			
			tooltip::display(
				__("Attachments have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
			
			tooltip::display(
				__("Attachment has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if (!$edit) {
			if (!$form->get('Files') && !$form->get('AttachmentID') && 
				!$form->get('Location')) 
			{
				tooltip::display(
					__("No file selected to be uploaded as a new attachment! " .
						"Please select a file to upload or define an already " .
						"uploaded attachment."),
					TOOLTIP_ERROR);
				
				return false;
			}
			
			if (!$form->get('Files') && $form->get('Location'))
				$form->set('Files', array($form->get('Location')));
			
			$form->add(
				'Attachment',
				'File',
				FORM_INPUT_TYPE_HIDDEN);
		}
		
		$form->add(
			'FileSize',
			'FileSize',
			FORM_INPUT_TYPE_HIDDEN);
		
		$form->add(
			'HumanMimeType',
			'HumanMimeType',
			FORM_INPUT_TYPE_HIDDEN);
		
		if ($edit) {
			if ($form->get('File')) {
				if (!$filename = $this->upload(
						$form->getFile('File'), 
						$this->rootPath.'/'))
					return false;
					
				if (!$form->get('Title'))
					$form->set('Title', preg_replace('/(.*(\/|\\\)|^)(.*)\..*/', '\3', 
						$form->get('File')));
				
				$form->set('FileSize', 
					@filesize($this->rootPath.$this->subFolder.'/'.$filename));
				$form->set('HumanMimeType', 
					@files::humanMimeType($this->rootPath.$this->subFolder.'/'.$filename));
				
				$form->set('File', $filename);
			}
		
			if (!$this->edit($id, $form->getPostArray()))
				return false;
				
			tooltip::display(
				__("Attachment has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->get('Files') && $form->get('AttachmentID')) {
			$attachment = sql::fetch(sql::run(
				" SELECT * FROM `{".$this->sqlTable . "}`" .
				" WHERE `ID` = '".$form->get('AttachmentID')."'"));
			
			$form->set('Title', $attachment['Title']);
			$form->set('File', $attachment['Location']);
			$form->set('FileSize', $attachment['FileSize']);
			$form->set('HumanMimeType', $attachment['HumanMimeType']);
			
			if (!$newid = $this->add($form->getPostArray()))
				return false;
			
			tooltip::display(
				__("Attachment has been successfully added.")." " .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$newid."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			$form->reset();
			return true;
		}
		
		$files = $form->getFile('Files');
		$filenames = $form->get('Files');
		$customtitle = $form->get('Title');
		$successfiles = null;
		$failedfiles = null;
		$noorderid = false;
		
		if (!$files || !count($files))
			$files = $filenames;
		
		if (!$form->get('OrderID'))
			$noorderid = true;
		
		$i = 1;
		foreach($files as $key => $file) {
			if (!$filename = $this->upload($file, $this->rootPath.'/')) {
				$failedfiles[] = $filenames[$key];
				continue;
			}
			
			$form->set('File', $filename);
			$form->set('Title',
				($customtitle?
					$customtitle .
					(count($filenames) > 1?
						' ('.$i.')':
						null):
					preg_replace('/(.*)\..*/', '\1', $filenames[$key])));
			
			$form->set('FileSize', 
				@filesize($this->rootPath.$this->subFolder.'/'.$filename));
			$form->set('HumanMimeType', 
				@files::humanMimeType($this->rootPath.$this->subFolder.'/'.$filename));
			
			if ($noorderid)
				$form->set('OrderID', $i);
			
			if (!$newid = $this->add($form->getPostArray())) {
				$failedfiles[] = $filenames[$key];
				continue;
			}
			
			$successfiles[] = $filenames[$key];
			$i++;
		}
		
		if ($failedfiles && count($failedfiles)) {
			tooltip::display(
				sprintf(__("There were problems uploading some of the files you selected. " .
					"The following files couldn't be uploaded: %s."),
					implode(', ', $failedfiles)),
				TOOLTIP_ERROR);
			
			if (!$successfiles || !count($successfiles))
				return false;
		}
		
		tooltip::display(
			sprintf(__("Attachment(s) have been successfully uploaded. " .
				"The following files have been uploaded: %s."),
				implode(', ', $successfiles)),
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Icon")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Uploaded on / File")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Downloads")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Filesize")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
	}
	
	function displayAdminListHeaderFunctions() {
		echo
			"<th>".__("Edit")."</th>" .
			"<th>".__("Delete")."</th>";
	}
	
	function displayAdminListItem(&$row) {
		echo
			"<td>" .
				"<input type='text' name='orders[".$row['ID']."]' " .
					"value='".$row['OrderID']."' " .
					"class='order-id-entry' tabindex='1' />" .
			"</td>" .
			"<td>";
		
		$this->displayIcon($row);
		
		echo
			"</td>" .
			"<td class='auto-width'>" .
				"<div class='bold'>".
					$row['Title'] .
				"</div>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					$row['HumanMimeType'].", " .
					sprintf(__("uploaded on %s"),
						calendar::datetime($row['TimeStamp'])) .
					"<br />" .
					"<a href='".$row['_Link']."' " .
						"title='".htmlspecialchars($row['Title'], ENT_QUOTES)."'>" .
						$row['Location'] .
					"</a>" .
				"</div>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				$row['Downloads'] .
			"</td>" .
			"<td style='text-align: right;'>" .
				"<span class='nowrap'>" .
				files::humanSize($row['FileSize']) .
				"</span>" .
			"</td>";
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
	
	function displayAdminListFunctions() {
		echo 
			"<input type='submit' name='reordersubmit' value='" .
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminList(&$rows) {
		echo
			"<form action='".
				url::uri('edit, delete')."' method='post'>";
			
		echo 
			"<table class='list' cellpadding='0' cellspacing='0'>" .
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
			$row['_Link'] = url::uri().
				"&amp;request=".$this->uriRequest .
				"&amp;download=".$row['ID']."&amp;ajax=1";
				
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
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE) {
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
			__(trim(ucfirst(preg_replace('/([A-Z])/', ' \1', 
				$this->sqlOwnerCountField)))), 
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		if (!$this->sqlTable) {
			tooltip::display(
				__("Storage table not defined."),
				TOOLTIP_NOTIFICATION);
			
			return;
		}
		
		$edit = null;
		$id = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($this->sqlOwnerTable) {
			$this->selectedOwnerID = admin::getPathID();
			
			$selectedowner = sql::fetch(sql::run(
				" SELECT `".$this->sqlOwnerField."` FROM `{" .$this->sqlOwnerTable . "}`" .
				" WHERE `ID` = '".$this->selectedOwnerID."'"));
			
			$this->displayAdminTitle($selectedowner[$this->sqlOwnerField]);
			
		} else {
			$this->displayAdminTitle($this->selectedOwner);
		}
		
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
				
		$form = new form(
				($edit?
					__("Edit Attachment"):
					__("New Attachment")),
				'neweditattachment');
		
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
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			$verifyok = $this->verifyAdmin($form);
		}
		
		$paging = new paging(10);
		$paging->ignoreArgs = 'id, edit, delete';
		
		$rows = sql::run(
				" SELECT * FROM `{".$this->sqlTable . "}`" .
				" WHERE 1" .
				($this->sqlRow?
					" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
					null) .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				" ORDER BY `OrderID`, `ID` DESC" .
				" LIMIT ".$paging->limit);
				
		$paging->setTotalItems(sql::count());
		
		if ($paging->items)
			$this->displayAdminList($rows);
		else
			tooltip::display(
					sprintf(__("No %s found for this %s."),
						strtolower(__(trim(preg_replace('/([A-Z])/', ' \1', 
							$this->sqlOwnerCountField)))), 
						$this->selectedOwner),
					TOOLTIP_NOTIFICATION);
	
		$paging->display();
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
						" SELECT * FROM `{".$this->sqlTable . "}`" .
						" WHERE `ID` = '".$id."'" .
						($this->sqlRow?
							" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
							null)));
				
				$form->setValues($row);
				$form->setValue('File', $row['Location']);
			}
			
			if (!$edit) {
				$attachments = sql::run(
					" SELECT * FROM `{".$this->sqlTable . "}`" .
					" GROUP BY `Location`" .
					" ORDER BY `ID` DESC" .
					" LIMIT 100");
				
				while($attachment = sql::fetch($attachments))
					$form->addValue(
						'AttachmentID',
						$attachment['ID'],
						$attachment['Title']);
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
			
		if ($values['OrderID'] == '') {
			sql::run(
				" UPDATE `{".$this->sqlTable."}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`".
				($this->sqlRow?
					" WHERE `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
					null));
			
			$values['OrderID'] = 1;
			
		} else {
			sql::run(
				" UPDATE `{".$this->sqlTable."}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE 1" .
				($this->sqlRow?
					" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
					null) .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		$newid = sql::run(
			" INSERT INTO `{".$this->sqlTable."}` SET ".
			($this->sqlRow?
				" `".$this->sqlRow."` = '".$this->selectedOwnerID."',":
				null) .
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `TimeStamp` = " .
				($values['TimeStamp']?
					"'".sql::escape($values['TimeStamp'])."'":
					"NOW()").
				"," .
			($values['File']?
				" `FileSize` = '".
					(int)$values['FileSize']."'," .
				" `HumanMimeType` = '".
					sql::escape($values['HumanMimeType'])."'," .
				" `Location` = '".
					(strpos($values['File'], '/') === false?
						$this->subFolder.'/':
						null) .
					sql::escape($values['File']).
					"',":
				null) .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
		
		if (!$newid) {
			tooltip::display(
				sprintf(__("Attachment couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if ($this->sqlOwnerTable) {
			sql::run(
				" UPDATE `{".$this->sqlOwnerTable."}` SET " .
				" `".$this->sqlOwnerCountField."` = `".
					$this->sqlOwnerCountField."` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".$this->selectedOwnerID."'");
		}
				
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
			
		if ($values['File'])
			sql::run(
				" UPDATE `{".$this->sqlTable . "}` SET " .
				" `FileSize` = '".
					(int)$values['FileSize']."'," .
				" `HumanMimeType` = '".
					sql::escape($values['HumanMimeType'])."'," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `Location` = '" .
					(strpos($values['File'], '/') === false?
						$this->subFolder.'/':
						null) .
					sql::escape($values['File']).
					"'"); 
		
		sql::run(
			" UPDATE `{".$this->sqlTable."}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `TimeStamp` = " .
				($values['TimeStamp']?
					"'".sql::escape($values['TimeStamp'])."'":
					"NOW()").
				"," .
			($values['File']?
				" `FileSize` = '".
					(int)$values['FileSize']."'," .
				" `HumanMimeType` = '".
					sql::escape($values['HumanMimeType'])."'," .
				" `Location` = '".
					(strpos($values['File'], '/') === false?
						$this->subFolder.'/':
						null) .
					sql::escape($values['File']).
					"',":
				null) .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Attachment couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		$row = sql::fetch(sql::run(
			" SELECT `Location` FROM `{".$this->sqlTable . "}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		if ($row && 
			!sql::count("SELECT COUNT(`ID`) AS `Rows` FROM `{".$this->sqlTable . "}`" .
				" WHERE `Location` = '".$row['Location']."'" .
				" AND `ID` != '".(int)$id."'")) 
		{
			files::delete($this->rootPath.$row['Location']);
		}
			
		sql::run(
			" DELETE FROM `{".$this->sqlTable . "}`" .
			" WHERE `ID` = '".(int)$id."'");
		
		if ($this->sqlOwnerTable) {
			$row = sql::fetch(sql::run(
				" SELECT COUNT(`ID`) AS `Rows` FROM `{".$this->sqlTable . "}`" .
				" WHERE `".$this->sqlRow."` = '".$this->selectedOwnerID."'"));
			
			sql::run("UPDATE `{".$this->sqlOwnerTable . "}`" .
				" SET `".$this->sqlOwnerCountField."` = '".(int)$row['Rows']."'," .
				" `TimeStamp` = `TimeStamp` " .
				" WHERE `ID` = '".$this->selectedOwnerID."'");
		}
					
		return true;
	}
	
	function upload($file, $to) {
		return files::upload($file, $to.$this->subFolder.'/');
	}
	
	// ************************************************   Client Part
	function download($id) {
		if (!(int)$id) {
			tooltip::display(
				__("No attachment selected to download!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{" .$this->sqlTable . "}`" .
			" WHERE `ID` = '".(int)$id."'" .
			" LIMIT 1"));
		
		if (!$row) {
			tooltip::display(
				__("The selected attachment cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$file = $this->rootPath.$row['Location'];
		
		if (!is_file($file)) {
			tooltip::display(
				sprintf(__("File \"%s\" cannot be found!"),
					$row['Location']),
				TOOLTIP_ERROR);
				
			return false;
		}

		session_write_close();
		files::display($file, true);
		
		if (!security::isBot())
			sql::run(
				" UPDATE `{" .$this->sqlTable."}` SET " .
				" `TimeStamp` = `TimeStamp`," .
				" `Downloads` = `Downloads`+1" .
				" WHERE `ID` = '".(int)$id."'");
		
		return true;
	}
	
	function ajaxRequest() {
		$download = null;
		
		if (isset($_GET['download']))
			$download = (int)$_GET['download'];
		
		if ($download)
			return $this->download($download);
		
		$this->ajaxPaging = true;
		$this->display();
		return true;
	}
	
	function displayIcon(&$row) {
		echo
			"<a href='".$row['_Link']."' " .
				"title='".
					htmlspecialchars(sprintf(__("Download %s"), 
						$row['Title']), ENT_QUOTES)."' " .
				"class='attachment-icon " .
				files::ext2MimeClass($row['Location']) .
				"'>".
			"</a>";
	}
	
	function displayTitle(&$row) {
		echo
			"<a href='".$row['_Link']."' " .
				"title='".htmlspecialchars(sprintf(__("Download %s"), 
					$row['Title']), ENT_QUOTES)."'>" .
				$row['Title'] . 
			"</a>";
	}
	
	function displaySize(&$row) {
		echo 
			"(".files::humanSize($row['FileSize']).")";
	}
	
	function displayDetails(&$row) {
		echo
			$row['HumanMimeType'] .
			"<span class='attachment-details-separator separator-1'>" .
				", " .
			"</span>";
		
		if ($row['Downloads'])
			echo
				"<span class='attachment-downloads'>".
					sprintf(__("%s downloads"), 
						$row['Downloads']) .
				"</span>" .
				"<span class='attachment-details-separator separator-2'>" .
					", " .
				"</span>";
		
		echo
			"<span class='attachment-uploaded-on'>".
				sprintf(__("uploaded on %s"),
					calendar::date($row['TimeStamp'])) .
			"</span>";
	}
	
	function displayOne(&$row) {
		if ($this->customLink) {
			if (is_array($this->customLink))
				$row['_Link'] = $this->customLink[$row['ID']];
			else
				$row['_Link'] = $this->customLink;
			
		} elseif (isset($row['URL']) && $row['URL']) {
			$row['_Link'] = url::generateLink($row['URL']);
			
		} else {
			$row['_Link'] = url::uri().
				"&amp;request=".$this->uriRequest .
				"&amp;download=".$row['ID']."&amp;ajax=1";
		}
				
		echo
			"<div " .
				(JCORE_VERSION < '0.6'?
					"id='attachment".$row['ID']."' ":
					null) .
				"class='attachment attachment".$row['ID']."'>";
			
		$this->displayIcon($row);
		$this->displayTitle($row);
		
		echo
				" <span class='attachment-size'>";
		
		$this->displaySize($row);
		
		echo
				"</span>" .
				"<div class='attachment-details comment'>";
		
		$this->displayDetails($row);
		
		echo
				"</div>" .
			"</div>";
	}
	
	function display() {
		if (!$this->sqlTable) {
			tooltip::display(
				__("Storage table not defined."),
				TOOLTIP_NOTIFICATION);
			
			return false;
		}
		
		$paging = new paging($this->limit);
		
		if ($this->ajaxPaging) {
			$paging->ajax = true;
			$paging->otherArgs = 
				"&amp;request=".$this->uriRequest .
				($this->sqlRow?
					"&amp;".strtolower($this->sqlRow)."=".$this->selectedOwnerID:
					null);
		}
		
		$paging->track(strtolower(get_class($this)).'limit');
		
		if ($this->ignorePaging)
			$paging->reset();
		
		$rows = sql::run(
			$this->SQL() .
			($this->ignorePaging?
				($this->limit?
					" LIMIT ".$this->limit:
					null):
				" LIMIT ".$paging->limit));
		
		$paging->setTotalItems(sql::count());
		
		if (!$paging->items)
			return false;
		
		if (!$this->ajaxRequest)
			echo
				"<div class='" .
					strtolower(preg_replace('/([A-Z])/', '-\\1', get_class($this))).
					" attachments rounded-corners'>";
				
		echo
				"<div class='attachments-title comment'>" .
					__(trim(ucfirst(preg_replace('/([A-Z])/', ' \1', 
						$this->sqlOwnerCountField)))) .
					" (".__("click to download").")" .
				"</div>";
		
		while ($row = sql::fetch($rows))
			$this->displayOne($row);
		
		echo
			"<div class='clear-both'></div>";
		
		if ($this->showPaging)
			$paging->display();
		
		if (!$this->ajaxRequest)
			echo
				"</div>"; //attachments
		
		return $paging->items;
	}
}

?>