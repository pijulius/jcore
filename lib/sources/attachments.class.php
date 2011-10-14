<?php

/***************************************************************************
 *            attachments.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/files.class.php');
include_once('lib/calendar.class.php');

class _attachments {
	var $limit = 0;
	var $latests = false;
	var $format = null;
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
		api::callHooks(API_HOOK_BEFORE,
			'attachments::attachments', $this);
		
		$this->uriRequest = strtolower(get_class($this));
		$this->subFolder = date('Ym');
		$this->rootPath = SITE_PATH.'sitefiles/file/';
		$this->rootURL = url::site().'sitefiles/file/';
		
		if ($this->sqlRow && isset($_GET[strtolower($this->sqlRow)]))
			$this->selectedOwnerID = (int)$_GET[strtolower($this->sqlRow)];
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::attachments', $this);
	}
	
	function SQL() {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::SQL', $this);
		
		$sql = 
			" SELECT * FROM `{" .$this->sqlTable . "}`" .
			" WHERE 1" .
			($this->sqlRow && !$this->latests?
				" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
				null) .
			" ORDER BY " .
			($this->latests?
				" `TimeStamp` DESC,":
				" `OrderID`,") .
			" `ID` DESC";
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::SQL', $this, $sql);
		
		return $sql;
	}
	
	// ************************************************   Admin Part
	function setupAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::setupAdmin', $this);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Attachment'),
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Content Files'), 
			'?path=admin/content/contentfiles');
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::setupAdmin', $this);
	}
	
	function setupAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::setupAdminForm', $this, $form);
		
		$edit = null;
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
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
						"onclick=\"$.jCore.form.appendEntryTo(" .
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
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::setupAdminForm', $this, $form);
	}
	
	function verifyAdmin(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::verifyAdmin', $this, $form);
		
		$reorder = null;
		$orders = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_POST['reordersubmit']))
			$reorder = (string)$_POST['reordersubmit'];
		
		if (isset($_POST['orders']))
			$orders = (array)$_POST['orders'];
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($reorder) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'attachments::verifyAdmin', $this, $form);
				return false;
			}
			
			foreach((array)$orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{".$this->sqlTable ."}`" .
					" SET `OrderID` = '".(int)$ovalue."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".(int)$oid."'");
			}
			
			tooltip::display(
				__("Attachments have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'attachments::verifyAdmin', $this, $form, $reorder);
			
			return true;
		}
		
		if ($delete) {
			$result = $this->delete($id);
			
			if ($result)
				tooltip::display(
					__("Attachment has been successfully deleted."),
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'attachments::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'attachments::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if (!$edit) {
			if (!$form->get('Files') && !$form->get('AttachmentID') && 
				!$form->get('Location')) 
			{
				tooltip::display(
					__("No file selected to be uploaded as a new attachment! " .
						"Please select a file to upload or define an already " .
						"uploaded attachment."),
					TOOLTIP_ERROR);
				
				api::callHooks(API_HOOK_AFTER,
					'attachments::verifyAdmin', $this, $form);
				
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
				{
					api::callHooks(API_HOOK_AFTER,
						'attachments::verifyAdmin', $this, $form);
					
					return false;
				}
					
				if (!$form->get('Title'))
					$form->set('Title', preg_replace('/(.*(\/|\\\)|^)(.*)\..*/', '\3', 
						$form->get('File')));
				
				$form->set('FileSize', 
					@filesize($this->rootPath.$this->subFolder.'/'.$filename));
				$form->set('HumanMimeType', 
					@files::humanMimeType($this->rootPath.$this->subFolder.'/'.$filename));
				
				$form->set('File', $filename);
			}
		
			$result = $this->edit($id, $form->getPostArray());
			
			if ($result)
				tooltip::display(
					__("Attachment has been successfully updated.")." " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'attachments::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->get('Files') && $form->get('AttachmentID')) {
			$attachment = sql::fetch(sql::run(
				" SELECT * FROM `{".$this->sqlTable . "}`" .
				" WHERE `ID` = '".$form->get('AttachmentID')."'"));
			
			$form->set('Title', $attachment['Title']);
			$form->set('File', $attachment['Location']);
			$form->set('FileSize', $attachment['FileSize']);
			$form->set('HumanMimeType', $attachment['HumanMimeType']);
			
			$newid = $this->add($form->getPostArray());
			
			if ($newid) {
				tooltip::display(
					__("Attachment has been successfully added.")." " .
					"<a href='".url::uri('id, edit, delete') .
						"&amp;id=".$newid."&amp;edit=1#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);
				
				$form->reset();
			}
			
			api::callHooks(API_HOOK_AFTER,
				'attachments::verifyAdmin', $this, $form, $newid);
			
			return $newid;
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
		foreach($filenames as $key => $filename) {
			if (!$newfilename = $this->upload(@$files[$key], $this->rootPath.'/')) {
				$failedfiles[] = $filename;
				continue;
			}
			
			$form->set('File', $newfilename);
			$form->set('Title',
				($customtitle?
					$customtitle .
					(count($filenames) > 1?
						' ('.$i.')':
						null):
					preg_replace('/(.*)\..*/', '\1', $filename)));
			
			$form->set('FileSize', 
				@filesize($this->rootPath.$this->subFolder.'/'.$newfilename));
			$form->set('HumanMimeType', 
				@files::humanMimeType($this->rootPath.$this->subFolder.'/'.$newfilename));
			
			if ($noorderid)
				$form->set('OrderID', $i);
			
			if (!$newid = $this->add($form->getPostArray())) {
				$failedfiles[] = $filename;
				continue;
			}
			
			$successfiles[] = $filename;
			$i++;
		}
		
		if ($failedfiles && count($failedfiles)) {
			tooltip::display(
				sprintf(__("There were problems uploading some of the files you selected. " .
					"The following files couldn't be uploaded: %s."),
					implode(', ', $failedfiles)),
				TOOLTIP_ERROR);
			
			if (!$successfiles || !count($successfiles)) {
				api::callHooks(API_HOOK_AFTER,
					'attachments::verifyAdmin', $this, $form);
				
				return false;
			}
		}
		
		tooltip::display(
			sprintf(__("Attachment(s) have been successfully uploaded. " .
				"The following files have been uploaded: %s."),
				implode(', ', $successfiles)),
			TOOLTIP_SUCCESS);
		
		$form->reset();
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::verifyAdmin', $this, $form, $successfiles);
		
		return true;
	}
	
	function displayAdminListHeader() {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayAdminListHeader', $this);
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayAdminListHeader', $this);
	}
	
	function displayAdminListHeaderOptions() {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayAdminListHeaderOptions', $this);
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayAdminListHeaderFunctions', $this);
		
		echo
			"<th>".__("Edit")."</th>" .
			"<th>".__("Delete")."</th>";
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayAdminListItem', $this, $row);
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListItemOptions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayAdminListItemOptions', $this, $row);
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayAdminListItemFunctions', $this, $row);
		
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
			'attachments::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminListFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayAdminListFunctions', $this);
		
		echo 
			"<input type='submit' name='reordersubmit' value='" .
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayAdminListFunctions', $this);
	}
	
	function displayAdminList(&$rows) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayAdminList', $this, $rows);
		
		echo
			"<form action='".
				url::uri('edit, delete')."' method='post'>" .
				"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";
			
		echo 
			"<table class='list' cellpadding='0' cellspacing='0'>" .
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
			$row['_Link'] = url::uri().
				"&amp;request=".$this->uriRequest .
				"&amp;download=".$row['ID']."&amp;ajax=1";
				
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			$this->displayAdminListFunctions();
			
			echo 
				"<div class='clear-both'></div>" .
				"<br />";
		}
					
		echo
			"</form>";
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayAdminList', $this, $rows);
	}
	
	function displayAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayAdminForm', $this, $form);
		
		$form->display();
				
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayAdminTitle', $this, $ownertitle);
		
		admin::displayTitle(
			__(trim(ucfirst(preg_replace('/([A-Z])/', ' \1', 
				$this->sqlOwnerCountField)))), 
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayAdminDescription', $this);
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		if (!$this->sqlTable) {
			tooltip::display(
				__("Storage table not defined."),
				TOOLTIP_NOTIFICATION);
			
			return;
		}
		
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayAdmin', $this);
		
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$verifyok = $this->verifyAdmin($form);
		
		$paging = new paging(10);
		$paging->ignoreArgs = 'id, edit, delete';
		
		$rows = sql::run(
				" SELECT * FROM `{".$this->sqlTable."}`" .
				" WHERE 1" .
				($this->sqlRow?
					" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{".$this->sqlTable."}`" .
					" WHERE `ID` = '".$id."'"));
				
				$form->setValues($selected);
				$form->setValue('File', $selected['Location']);
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
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayAdmin', $this);
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
			
		api::callHooks(API_HOOK_BEFORE,
			'attachments::add', $this, $values);
		
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
			
		} else if ($this->sqlOwnerTable) {
			sql::run(
				" UPDATE `{".$this->sqlOwnerTable."}` SET " .
				" `".$this->sqlOwnerCountField."` = `".
					$this->sqlOwnerCountField."` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".$this->selectedOwnerID."'");
		}
				
		api::callHooks(API_HOOK_AFTER,
			'attachments::add', $this, $values, $newid);
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
			
		api::callHooks(API_HOOK_BEFORE,
			'attachments::edit', $this, $id, $values);
		
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
		
		$result = (sql::affected() != -1);
		
		if (!$result)
			tooltip::display(
				sprintf(__("Attachment couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::edit', $this, $id, $values, $result);
		
		return $result;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'attachments::delete', $this, $id);
		
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
					
		api::callHooks(API_HOOK_AFTER,
			'attachments::delete', $this, $id);
		
		return true;
	}
	
	function upload($file, $to) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::upload', $this, $file, $to);
		
		$result = files::upload($file, $to.$this->subFolder.'/');
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::upload', $this, $file, $to, $result);
		
		return $result;
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

		api::callHooks(API_HOOK_BEFORE,
			'attachments::download', $this, $id);
		
		session_write_close();
		files::display($file, true);
		
		if (!security::isBot())
			sql::run(
				" UPDATE `{" .$this->sqlTable."}` SET " .
				" `TimeStamp` = `TimeStamp`," .
				" `Downloads` = `Downloads`+1" .
				" WHERE `ID` = '".(int)$id."'");
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::download', $this, $id, $row);
		
		return true;
	}
	
	function generateLink(&$row) {
		if ($this->customLink) {
			if (is_array($this->customLink))
				$link = $this->customLink[$row['ID']];
			else
				$link = $this->customLink;
			
		} elseif (isset($row['URL']) && $row['URL']) {
			$link = url::generateLink($row['URL']);
			
		} else {
			$link = url::uri().
				"&amp;request=".$this->uriRequest .
				"&amp;download=".$row['ID']."&amp;ajax=1";
		}
		
		return $link;
	}
	
	function ajaxRequest() {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::ajaxRequest', $this);
		
		$result = true;
		$download = null;
		
		if (isset($_GET['download']))
			$download = (int)$_GET['download'];
		
		if ($download) {
			$result = $this->download($download);
			
		} else {
			$this->ajaxPaging = true;
			$this->display();
		}
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::ajaxRequest', $this, $result);
		
		return $result;
	}
	
	function displayIcon(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayIcon', $this, $row);
		
		echo
			"<a href='".$row['_Link']."' " .
				"title='".
					htmlspecialchars(sprintf(__("Download %s"), 
						$row['Title']), ENT_QUOTES)."' " .
				"class='attachment-icon " .
				files::ext2MimeClass($row['Location']) .
				"'>".
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayIcon', $this, $row);
	}
	
	function displayTitle(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayTitle', $this, $row);
		
		echo
			"<a href='".$row['_Link']."' " .
				"class='attachment-title' " .
				"title='".htmlspecialchars(sprintf(__("Download %s"), 
					$row['Title']), ENT_QUOTES)."'>" .
				$row['Title'] . 
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayTitle', $this, $row);
	}
	
	function displaySize(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displaySize', $this, $row);
		
		echo 
			"(".files::humanSize($row['FileSize']).")";
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::displaySize', $this, $row);
	}
	
	function displayDetails(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayDetails', $this, $row);
		
		echo
			"<span class='attachment-type'>" .
				$row['HumanMimeType'] .
			"</span>" .
			"<span class='details-separator separator-1'>" .
				", " .
			"</span>";
		
		if ($row['Downloads'])
			echo
				"<span class='attachment-downloads'>".
					sprintf(__("%s downloads"), 
						$row['Downloads']) .
				"</span>" .
				"<span class='details-separator separator-2'>" .
					", " .
				"</span>";
		
		echo
			"<span class='attachment-uploaded-on'>".
				sprintf(__("uploaded on %s"),
					calendar::date($row['TimeStamp'])) .
			"</span>";
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayDetails', $this, $row);
	}
	
	function displayFormated(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayFormated', $this, $row);
		
		if (!isset($row['_Link']) || !$row['_Link'])
			$row['_Link'] = $this->generateLink($row);
		
		echo
			"<div " .
				(JCORE_VERSION < '0.6'?
					"id='attachment".$row['ID']."' ":
					null) .
				"class='attachment attachment".$row['ID']."'>";
		
		$parts = preg_split('/%([a-z0-9-_]+?)%/', $this->format, null, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach($parts as $part) {
			switch($part) {
				case 'icon':
					$this->displayIcon($row);
					break;
				
				case 'title':
					$this->displayTitle($row);
					break;
				
				case 'size':
					echo
						"<span class='attachment-size'> ";
					
					$this->displaySize($row);
					
					echo
						"</span>";
					break;
					
				case 'details':
					echo
						"<div class='attachment-details comment'>";
					
					$this->displayDetails($row);
					
					echo
						"</div>";
					break;
				
				case 'link':
					echo $row['_Link'];
					break;
				
				default:
					echo $part;
					break;
			}
		}
		
		echo
			"</div>";
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayFormated', $this, $row);
	}
	
	function displayOne(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'attachments::displayOne', $this, $row);
		
		if (!isset($row['_Link']) || !$row['_Link'])
			$row['_Link'] = $this->generateLink($row);
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::displayOne', $this, $row);
	}
	
	function display() {
		if (!$this->sqlTable) {
			tooltip::display(
				__("Storage table not defined."),
				TOOLTIP_NOTIFICATION);
			
			return false;
		}
		
		if (!$this->latests) {
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
		}
		
		$rows = sql::run(
			$this->SQL() .
			($this->ignorePaging || $this->latests?
				($this->limit?
					" LIMIT ".$this->limit:
					null):
				" LIMIT ".$paging->limit));
		
		if (!$this->latests)
			$paging->setTotalItems(sql::count());
		
		if (!sql::rows($rows))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'attachments::display', $this);
		
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
		
		while ($row = sql::fetch($rows)) {
			$row['_Link'] = $this->generateLink($row);
			
			if ($this->format)
				$this->displayFormated($row);
			else
				$this->displayOne($row);
		}
		
		echo
			"<div class='clear-both'></div>";
		
		if ($this->showPaging && !$this->latests)
			$paging->display();
		
		if (!$this->ajaxRequest)
			echo
				"</div>"; //attachments
		
		api::callHooks(API_HOOK_AFTER,
			'attachments::display', $this);
		
		if ($this->latests)
			return true;
		
		return $paging->items;
	}
}

?>