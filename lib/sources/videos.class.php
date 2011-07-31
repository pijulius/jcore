<?php

/***************************************************************************
 *            videos.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/files.class.php');
include_once('lib/calendar.class.php');

class _videos {
	var $limit = 0;
	var $latests = false;
	var $format = null;
	var $ignorePaging = false;
	var $showPaging = true;
	var $randomize = false;
	var $columns = 0;
	var $sqlTable;
	var $sqlRow;
	var $sqlOwnerTable;
	var $sqlOwnerField = 'Title';
	var $sqlOwnerCountField = 'Videos';
	var $selectedID = 0;
	var $selectedOwner;
	var $selectedOwnerID;
	var $customLink;
	var $uriRequest;
	var $subFolder;
	var $rootPath;
	var $rootURL;
	var $videoWidth = 640;
	var $videoHeight = 385;
	var $ajaxPaging = AJAX_PAGING;
	var $ajaxRequest = null;
	
	function __construct() {
		$this->uriRequest = strtolower(get_class($this));
		$this->subFolder = date('Ym');
		$this->rootPath = SITE_PATH.'sitefiles/media/';
		$this->rootURL = url::site().'sitefiles/media/';
		
		if ($this->sqlRow && isset($_GET[strtolower($this->sqlRow)]))
			$this->selectedOwnerID = (int)$_GET[strtolower($this->sqlRow)];
		
		if (isset($_GET['videoid']))
			$this->selectedID = (int)$_GET['videoid'];
	}
	
	function SQL() {
		return
			" SELECT * FROM `{" .$this->sqlTable."}`" .
			" WHERE 1" .
			($this->selectedID && !$this->latests?
				" AND `ID` = '".$this->selectedID."'":
				($this->sqlRow && !$this->latests?
					" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
					null)) .
			" ORDER BY" .
			($this->randomize?
				" RAND()":
				($this->latests?
					" `TimeStamp` DESC,":
					" `OrderID`,") .
				" `ID` DESC");
	}
	
	// ************************************************   Admin Part
	function setupAdmin() {
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Video'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Content Files'), 
			'?path=admin/content/contentfiles');
	}
	
	function setupAdminForm(&$form) {
		$edit = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if ($edit) {
			$form->add(
				__('Title'),
				'Title',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 300px;');
			
			$form->add(
				__('Video Cap'),
				'CapLocation',
				FORM_INPUT_TYPE_HIDDEN);
		}
		
		if (!$edit) {
			$form->add(
				"<b>".__("Add an already uploaded video (for e.g. youtube, vimeo url)")."</b>",
				'',
				FORM_STATIC_TEXT);
			
			$form->add(
				__('Video (URL)'),
				'Locations[]',
				FORM_INPUT_TYPE_TEXT);
			$form->setValueType(FORM_VALUE_TYPE_ARRAY);
			$form->setStyle('width: 350px;');
			$form->setTooltipText(__("e.g. http://www.youtube.com/watch?v=0_fPV13lKm4"));
			
			$form->add(
				"<div class='form-entry-upload-multi-video-urls-container'></div>" .
				"<div class='form-entry-title'></div>" .
				"<div class='form-entry-content'>" .
					"<a href='javascript://' class='add-link' " .
						"onclick=\"jQuery.jCore.form.appendEntryTo(" .
							"'.form-entry-upload-multi-video-urls-container', " .
							"'', " .
							"'Locations[]', " .
							FORM_INPUT_TYPE_TEXT."," .
							"false, '', 'style=\'width: 270px;\'');\">" .
						__("Add another url") .
					"</a>" .
				"</div>",
				null,
				FORM_STATIC_TEXT);
			
			$form->add(
				__('Local Video'),
				'VideoID',
				FORM_INPUT_TYPE_SELECT);
			$form->setValueType(FORM_VALUE_TYPE_INT);
			$form->setStyle('width: 300px;');
			
			$form->addValue(
				'',
				'');
			
			$form->add(
				__('Upload a new video'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
		
			$form->add(
				__('Video File'),
				'File',
				FORM_INPUT_TYPE_FILE);
			$form->setValueType(FORM_VALUE_TYPE_FILE);
			$form->setTooltipText(__("e.g. video.mp4, video.flv"));
						
			$form->add(
				__('Video Cap'),
				'CapFile',
				FORM_INPUT_TYPE_FILE);
			$form->setTooltipText(__("e.g. image.jpg, image.gif"));
			$form->setValueType(FORM_VALUE_TYPE_FILE);
						
			$form->add(
				__('Title'),
				'Title',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 300px;');
		
			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER);
			
		} else {
			$form->add(
				__('Video (URL)'),
				'Location',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 350px;');
			$form->setTooltipText(__("e.g. http://www.youtube.com/watch?v=0_fPV13lKm4"));
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
				__("Videos have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
			
			tooltip::display(
				__("Video has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if (!$edit && !$form->get('File') && 
			!$form->get('VideoID') && !count($form->get('Locations'))) 
		{
			tooltip::display(
				__("No file selected to be uploaded as a new video! " .
					"Please select a file / video to upload or define an already " .
					"uploaded video."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$filename = null;
		$capfilename = null;
		
		if ($form->get('File')) {
			if (!$filename = $this->upload(
					$form->getFile('File'), 
					$this->rootPath))
				return false;
			
			if (!$form->get('Title'))
				$form->set('Title', preg_replace('/(.*(\/|\\\)|^)(.*)\..*/', '\3', 
					$form->get('File')));
			
			$capfilename = $this->uploadCap(
					$form->getFile('CapFile'), 
					$this->rootPath);
		}
		
		$form->set('File', $filename);
		$form->set('CapFile', $capfilename);
		
		if ($edit) {
			$postarray = $form->getPostArray();
			$postarray['File'] = $form->get('Location');
			$postarray['CapFile'] = $form->get('CapLocation');
			
			if ($video = videos::getOnlineVideo($form->get('Location'))) {
				$postarray['File'] = $video['File'];
				$postarray['CapFile'] = $video['CapFile'];
			}
			
			if (isset($video['NoEmbed']) && $video['NoEmbed']) {
				tooltip::display(
					sprintf(
						__("Video \"%s\" is not embeddable, \"%s\"."),
						$form->get('Location'), $video['NoEmbed']),
					TOOLTIP_ERROR);
				return false;
			}
			
			if (!$this->edit($id, $postarray))
				return false;
				
			tooltip::display(
				__("Video has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($form->get('File')) {
			if (!$newid = $this->add($form->getPostArray()))
				return false;
			
			tooltip::display(
				__("Video has been successfully uploaded.")." ".
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$newid."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			$form->reset();
			return true;
		}
		
		if (!count($form->get('Locations')) && $form->get('VideoID')) {
			$video = sql::fetch(sql::run(
				" SELECT * FROM `{".$this->sqlTable . "}`" .
				" WHERE `ID` = '".$form->get('VideoID')."'"));
			
			$form->set('Title', $video['Title']);
			$form->set('File', $video['Location']);
			$form->set('CapFile', $video['CapLocation']);
			
			if (!$newid = $this->add($form->getPostArray()))
				return false;
			
			tooltip::display(
				__("Video has been successfully added.")." " .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$newid."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			$form->reset();
			return true;
		}
		
		$customtitle = $form->get('Title');
		$locations = $form->get('Locations');
		$successurls = null;
		$failedurls = null;
		$noorderid = false;
		
		if (!$form->get('OrderID'))
			$noorderid = true;
		
		$i = 1;
		foreach($locations as $location) {
			if (!$video = videos::getOnlineVideo($location)) {
				$failedurls[] = $location;
				continue;
			}
			
			if (isset($video['NoEmbed']) && $video['NoEmbed']) {
				$failedurls[] = $location;
				
				tooltip::display(
					sprintf(
						__("Video \"%s\" is not embeddable, \"%s\"."),
						$location, $video['NoEmbed']),
					TOOLTIP_ERROR);
				continue;
			}
			
			$form->set('File', $video['File']);
			$form->set('CapFile', $video['CapFile']);
			$form->set('Title', 
				($customtitle?
					$customtitle .
					(count($locations) > 1?
						' ('.$i.')':
						null):
					$video['Title']));
			
			if ($noorderid)
				$form->set('OrderID', $i);
			
			if (!$newid = $this->add($form->getPostArray())) {
				$failedurls[] = $location;
				continue;
			}
			
			$successurls[] = $location;
			$i++;
		}
		
		if ($failedurls && count($failedurls)) {
			tooltip::display(
				sprintf(__("There were problems adding some of the video urls you defined. " .
					"The following videos couldn't be added: %s."),
					implode(', ', $failedurls)),
				TOOLTIP_ERROR);
			
			if (!$successurls || !count($successurls))
				return false;
		}
		
		tooltip::display(
			sprintf(__("Video(s) have been successfully added. " .
				"The following videos have been added: %s."),
				implode(', ', $successurls)),
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Video")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Added on")."</span></th>";
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
		$href = url::uri().
			"&amp;request=".$this->uriRequest .
			"&amp;view=".$row['ID']."&amp;ajax=1";
	
		$pic = $this->rootURL.$row['Location'];
		$file_data = @getimagesize($this->rootPath.
			$row['Location']);
		
		$thumbnailwidth = PICTURE_THUMBNAIL_WIDTH;
		$thumbnailheight = PICTURE_THUMBNAIL_HEIGHT;
		
		if ($file_data[0] && $file_data[0] < $thumbnailwidth)
			$thumbnailwidth = $file_data[0];
		
		if ($file_data[1] && $file_data[1] < $thumbnailheight)
			$thumbnailheight = $file_data[1];
		
		echo
			"<td>" .
				"<input type='text' name='orders[".$row['ID']."]' " .
					"value='".$row['OrderID']."' " .
					"class='order-id-entry' tabindex='1' />" .
			"</td>" .
			"<td>" .
				"<div class='video-preview'>";
		
		$this->displayPreview($row);
		
		echo
				"</div>" .
			"</td>" .
			"<td class='auto-width'>" .
				"<div class='bold'>".
					$row['Title'] .
				"</div>" .
				"<div class='comment' style='padding-left: 10px;'>";
				
		$this->displayDetails($row);

		echo				
				"</div>" .
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
		
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($this->sqlOwnerTable) {
			$this->selectedOwnerID = admin::getPathID();
			
			$selectedowner = sql::fetch(sql::run(
				" SELECT `".$this->sqlOwnerField."`" .
				" FROM `{" .$this->sqlOwnerTable."}`" .
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
					__("Edit Video"):
					__("New Video")),
				'neweditvideo');
		
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
				$form->setValue('CapFile', $selected['CapLocation']);
			}
			
			if (!$edit) {
				$pics = sql::run(
					" SELECT * FROM `{".$this->sqlTable."}`" .
					" GROUP BY `Location`" .
					" ORDER BY `ID` DESC" .
					" LIMIT 100");
				
				while($pic = sql::fetch($pics))
					$form->addValue(
						'VideoID',
						$pic['ID'],
						$pic['Title']);
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
				" `Location` = '".
					(strpos($values['File'], '/') === false?
						$this->subFolder.'/':
						null) .
					sql::escape($values['File']).
					"',":
					null) .
			($values['CapFile']?
				" `CapLocation` = '".
					(strpos($values['CapFile'], '/') === false?
						$this->subFolder.'/':
						null) .
					sql::escape($values['CapFile']).
					"',":
					null) .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
		
		if (!$newid) {
			tooltip::display(
				sprintf(__("Video couldn't be created! Error: %s"), 
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
				" `Location` = '".
					(strpos($values['File'], '/') === false?
						$this->subFolder.'/':
						null) .
					sql::escape($values['File']).
					"',":
				null) .
			($values['CapFile']?
				" `CapLocation` = '".
					(strpos($values['CapFile'], '/') === false?
						$this->subFolder.'/':
						null) .
					sql::escape($values['CapFile']).
					"',":
					null) .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Video couldn't be updated! Error: %s"), 
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
			" SELECT `Location`, `CapLocation` FROM `{".$this->sqlTable."}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		if ($row && 
			!sql::count("SELECT COUNT(`ID`) AS `Rows` FROM `{".$this->sqlTable."}`" .
				" WHERE `Location` = '".$row['Location']."'" .
				" AND `ID` != '".(int)$id."'")) 
		{
			files::delete($this->rootPath.$row['Location']);
			files::delete($this->rootPath.$row['CapLocation']);
		}
			
		sql::run(
			" DELETE FROM `{".$this->sqlTable."}`" .
			" WHERE `ID` = '".(int)$id."'");
		
		if ($this->sqlOwnerTable) {
			$row = sql::fetch(sql::run(
				" SELECT COUNT(`ID`) AS `Rows` FROM `{".$this->sqlTable."}`" .
				" WHERE `".$this->sqlRow."` = '".$this->selectedOwnerID."'"));
			
			sql::run("UPDATE `{".$this->sqlOwnerTable."}`" .
				" SET `".$this->sqlOwnerCountField."` = '".(int)$row['Rows']."'," .
				" `TimeStamp` = `TimeStamp` " .
				" WHERE `ID` = '".$this->selectedOwnerID."'");
		}
					
		return true;
	}
	
	function upload($file, $to) {
		if (strpos($file, '://') !== false)
			return $file;
		
		$videopath = $to.$this->subFolder.'/';
		
		if (!$filename = files::upload($file, $videopath, FILE_TYPE_VIDEO))
			return false;
			
		return $filename;
	}
	
	function uploadCap($file, $to) {
		if (strpos($file, '://') !== false)
			return $file;
		
		$pictures = new pictures();
		$pictures->subFolder = $this->subFolder;
		$pictures->thumbnailsFolder = '';
		$filename = $pictures->upload($file, $to);
		unset($pictures);
			
		if (!$filename)
			return false;
		
		return $filename;
	}
	
	static function getDailyMotionVideo($url) {
		preg_match('/video\/(.*?)(_|$)/i', $url, $matches);
		
		if (!isset($matches[1]))
			return false;
		
		$data = files::get('http://www.dailymotion.com/rss/video/' .
			$matches[1]);
		
		if (!$data)
			return false;
		
		if (!preg_match('/<media:title.*?>(.*?)<\/media:title>/i', $data, $matches))
			return false;
		
		$video['Title'] = $matches[1];
		
		if (!preg_match('/<media:content.*?url=(\'|")(.*?)(\'|")/i', $data, $matches))
			return false;
		
		$video['File'] = $matches[2];
		
		if (!preg_match('/<media:thumbnail.*?url=(\'|")(.*?)(\'|")/i', $data, $matches))
			return false;
		
		$video['CapFile'] = $matches[2];
		
		return $video;
	}
	
	static function getYouTubeVideo($url) {
		preg_match('/v=(.*?)(&|$)/i', $url, $matches);
		
		if (!isset($matches[1]))
			return false;
		
		$data = files::get('http://gdata.youtube.com/feeds/api/videos/' .
			$matches[1]);
		
		if (!$data)
			return false;
		
		if (!preg_match('/<media:title.*?>(.*?)<\/media:title>/i', $data, $matches))
			return false;
		
		$video['Title'] = $matches[1];
		
		if (!preg_match('/<media:content.*?url=(\'|")(.*?)(\'|")/i', $data, $matches))
			return false;
		
		$video['File'] = $matches[2];
		
		if (!preg_match('/<media:thumbnail.*?url=.([^ \'"]+0\.jpg)/i', $data, $matches))
			return false;
		
		$video['CapFile'] = $matches[1];
		
		if (preg_match('/<yt:noembed.*?>/', $data))
			$video['NoEmbed'] = 'NoEmbed';
		
		if (preg_match('/<yt:private.*?>/', $data))
			$video['NoEmbed'] = 'Private';
		
		return $video;
	}
	
	static function getVimeoVideo($url) {
		preg_match('/(\d+)$/', $url, $matches);
		
		if (!isset($matches[1]))
			return false;

		$data = files::get('http://vimeo.com/api/clip/'.$matches[1].'/php');
		$array = @unserialize(trim($data));
		
		if (!isset($array[0]['url']))
			return false;
		
		$video['Title'] = $array[0]['title'];
		$video['File'] = $array[0]['url'];
		$video['CapFile'] = $array[0]['thumbnail_medium'];
		
		return $video;
	}
	
	static function getMetacafeVideo($url) {
		preg_match('/(\d+)($|\/)/', $url, $matches);
		
		if (!isset($matches[1]))
			return false;

		$data = files::get('http://www.metacafe.com/api/item/'.$matches[1].'/');
		
		if (!$data)
			return false;
		
		if (!preg_match('/<media:title.*?>(.*?)<\/media:title>/i', $data, $matches))
			return false;
		
		$video['Title'] = $matches[1];
		
		if (!preg_match('/<media:content.*?url=(\'|")(.*?)(\'|")/i', $data, $matches))
			return false;
		
		$video['File'] = $matches[2];
		
		if (!preg_match('/<media:thumbnail.*?url=(\'|")(.*?)(\'|")/i', $data, $matches))
			return false;
		
		$video['CapFile'] = $matches[2];
		
		return $video;
	}
	
	static function getOnlineVideo($url) {
		if (!$url)
			return false;
			
		if (preg_match('/dailymotion\.com\//i', $url))
			return videos::getDailyMotionVideo($url);
		
		if (preg_match('/youtube\.com\//i', $url))
			return videos::getYouTubeVideo($url);
		
		if (preg_match('/vimeo\.com\//i', $url))
			return videos::getVimeoVideo($url);
		
		if (preg_match('/metacafe\.com\//i', $url))
			return videos::getMetacafeVideo($url);
		
		return false;
	}
	
	// ************************************************   Client Part
	function download($id) {
		if (!(int)$id) {
			tooltip::display(
				__("No video selected!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{" .$this->sqlTable . "}`" .
			" WHERE `ID` = '".(int)$id."'" .
			" LIMIT 1"));
		
		if (!$row) {
			tooltip::display(
				__("The selected video cannot be found!"),
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
		files::display($file);
		
		return true;
	}
	
	function generateLink(&$row) {
		$link = url::uri('videoid').
			"&amp;request=".$this->uriRequest .
			"&amp;videoid=".$row['ID'];
		
		if ($this->customLink) {
			if (is_array($this->customLink))
				$link = $this->customLink[$row['ID']];
			else
				$link = $this->customLink;
		}
		
		return $link;
	}
		
	function incViews(&$row) {
		sql::run(
			" UPDATE `{" .$this->sqlTable. "}` SET " .
			" `Views` = `Views` + 1," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".$row['ID']."'");
	}
	
	function ajaxRequest() {
		$get = null;
		
		if (isset($_GET['get']))
			$get = (int)$_GET['get'];
		
		if ($get)
			return $this->download($get);
		
		$this->ajaxPaging = true;
		$this->display();
		return true;
	}
	
	function displayPlayButton(&$row) {
		echo
			"<span class='video-play-button rounded-corners'></span>";
	}
	
	function displayCap(&$row) {
		echo
			"<img src='".
				(strpos($row['CapLocation'], '://') !== false?
					$row['CapLocation']:
					$this->rootURL.$row['CapLocation']).
					"' " .
				"title='".htmlspecialchars($row['Title'], ENT_QUOTES)."' " .
				"alt='".htmlspecialchars($row['Title'], ENT_QUOTES)."' " .
				"border='0' />";
	}
	
	function displayPreview(&$row) {
		if (!isset($row['_Link']) || !$row['_Link'])
			$row['_Link'] = $this->generateLink($row);
		
		echo
			"<a href='".$row['_Link']."' " .
				(!$this->customLink?
					"rel='videolightbox[".strtolower(get_class($this))."".$this->selectedOwnerID."]' ":
					null) .
				"title='".htmlspecialchars($row['Title'], ENT_QUOTES)."'>";
		
		if (!$this->customLink)
			$this->displayPlayButton($row);
		
		$this->displayCap($row);
		
		echo
			"</a>";
	}
	
	function displayTitle(&$row) {
		echo $row['Title'];
	}
	
	function displayDetails(&$row) {
		echo
			"<span class='details-date'>" .
				calendar::date($row['TimeStamp']) .
			"</span>";
		
		if (JCORE_VERSION >= '0.5' && $row['Views'])
			echo
				"<span class='details-separator separator-1'>" .
					", " .
				"</span>" .
				"<span class='video-views-number'>" .
					sprintf(__("%s views"), $row['Views']) .
				"</span>";
	}
	
	function displayVideoPlayer(&$row) {
		$parameters = array(
			'FlashVars' => 'url=' .
				urlencode(
					(strpos($row['Location'], '://') !== false?
						$row['Location']:
						$this->rootURL.$row['Location'])) .
				'&autoplay=1');
		
		$row['Location'] = url::jCore() .
				'lib/flash/player.swf';
		
		$this->displayFlashVideo($row, $parameters);
	}
	
	function displayFlashVideo(&$row, $parameters = array()) {
		echo
			"<object width='".$this->videoWidth."' height='".$this->videoHeight."'>" .
				"<param name='movie' " .
					"value='".$row['Location']."'>" .
				"</param>" .
				"<param name='allowFullScreen' value='true'></param>" .
				"<param name='allowscriptaccess' value='always'></param>";
		
		foreach($parameters as $key => $value)
			echo 
				"<param name='".$key."' value='".$value."'></param>";
		
		echo
				"<embed src='".$row['Location']."' " .
					"type='application/x-shockwave-flash' " .
					"allowscriptaccess='always' " .
					"allowfullscreen='true' ";
		
		foreach($parameters as $key => $value)
			echo 
				$key."='".$value."' ";
		
		echo
					"width='".$this->videoWidth."' height='".$this->videoHeight."'>" .
				"</embed>" .
			"</object>";
	}
	
	function displayHTML5Video(&$row) {
		echo
			"<video " .
				"width='".$this->videoWidth."' " .
				"height='".$this->videoHeight."' " .
				"controls='true' " .
				"autoplay='true'>" .
				"<source src='" .
				(strpos($row['Location'], '://') !== false?
					$row['Location']:
					$this->rootURL.$row['Location']) .
				"' />";
		
		$this->displayVideoPlayer($row);
		
		echo
			"</video>";
	}
	
	function displayIframeVideo(&$row) {
		echo
			"<iframe " .
				"width='".$this->videoWidth."' height='".$this->videoHeight."' " .
				"src='".$row['Location']."' frameborder='0' allowfullscreen='true'>
			</iframe>";
	}
	
	function displayDailyMotionVideo(&$row) {
		$row['Location'] .= '&amp;autoplay=1';
		$this->displayFlashVideo($row);
	}
	
	function displayYouTubeVideo(&$row) {
		$row['Location'] = str_replace('/v/', '/embed/', $row['Location']) .
			'&amp;fs=1&amp;autoplay=1';
		
		$this->displayIframeVideo($row);
	}
	
	function displayVimeoVideo(&$row) {
		preg_match('/([0-9]*)$/', $row['Location'], $matches);
		$row['Location'] = 'http://player.vimeo.com/video/' .
			$matches[1].'?show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;fullscreen=1&amp;autoplay=1';
		
		$this->displayIframeVideo($row);
	}
	
	function displayMetacafeVideo(&$row) {
		$row['Location'] .= '?playerVars=autoPlay=yes';
		$this->displayFlashVideo($row);
	}
	
	function displayLocalVideo(&$row) {
		$file = $row['Location'];
		
		$row['Location'] = url::site().'index.php?'.
			"&request=".$this->uriRequest .
			"&get=".$row['ID']."&ajax=1";
		
		if (preg_match('/\.(mp4|mov|webm|ogv)$/i', $file)) {
			$this->displayHTML5Video($row);
			return true;
		}
		
		$this->displayVideoPlayer($row);
		return true;
	}
	
	function displayRemoteVideo(&$row) {
		if (preg_match('/dailymotion\.com\//i', $row['Location'])) {
			$this->displayYouTubeVideo($row);
			return true;
		}
		
		if (preg_match('/youtube\.com\//i', $row['Location'])) {
			$this->displayYouTubeVideo($row);
			return true;
		}
		
		if (preg_match('/vimeo\.com\//i', $row['Location'])) {
			$this->displayVimeoVideo($row);
			return true;
		}
		
		if (preg_match('/metacafe\.com\//i', $row['Location'])) {
			$this->displayMetacafeVideo($row);
			return true;
		}
		
		if (preg_match('/\.swf$/i', $row['Location'])) {
			$this->displayFlashVideo($row);
			return true;
		}
		
		if (preg_match('/\.(mp4|mov|webm|ogv)$/i', $row['Location'])) {
			$this->displayHTML5Video($row);
			return true;
		}
		
		$this->displayVideoPlayer($row);
		return true;
	}
	
	function displayVideo(&$row) {
		if (strpos($row['Location'], '://') !== false)
			return $this->displayRemoteVideo($row);
		
		return $this->displayLocalVideo($row);
	}
	
	function displayFormated(&$row) {
		if (!isset($row['_Link']) || $row['_Link'])
			$row['_Link'] = $this->generateLink($row);
		
		echo
			"<div class='video".$row['ID']." video-preview video-preview-num" .
				$row['_VideoNumber']."'>";
		
		$parts = preg_split('/%([a-z0-9-_]+?)%/', $this->format, null, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach($parts as $part) {
			switch($part) {
				case 'preview':
					$this->displayPreview($row);
					break;
				
				case 'title':
					echo
						"<div class='video-title'>";
					
					$this->displayTitle($row);
					
					echo
						"</div>";
					break;
				
				case 'details':
					echo
						"<div class='video-details comment'>";
					
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
	}
	
	function displaySelected(&$row) {
		if (!security::isBot())
			$this->incViews($row);
		
		if (!$row['Location'])
			return;
		
		echo
			"<div class='video" .
				(!$this->ajaxRequest?
					" selected":
					null) .
				" video".$row['ID'] .
				"'>";
		
		$this->displayVideo($row);
		
		echo
			"</div>";
	}
	
	function displayOne(&$row) {
		if (!$row['Location'])
			return;
		
		echo
			"<div class='video".$row['ID']." video-preview video-preview-num" .
				$row['_VideoNumber']."'>";
		
		$this->displayPreview($row);
		
		echo
				"<div class='video-title'>";
				
		$this->displayTitle($row);
		
		echo
				"</div>" .
				"<div class='video-details comment'>";
				
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
		
		if ($this->selectedID && !$this->latests) {
			$row = sql::fetch(sql::run(
				$this->SQL() .
				" LIMIT 1"));
		
			$this->displaySelected($row);
			
			if ($this->ajaxRequest)
				return true;
			
			$this->selectedID = 0;
			url::delargs('videoid');
		}
		
		if (!$this->latests) {
			$paging = new paging($this->limit);
			
			if ($this->ajaxPaging) {
				$paging->ajax = true;
				$paging->otherArgs = "&amp;request=".$this->uriRequest .
					($this->sqlRow?
						"&amp;".strtolower($this->sqlRow)."=".$this->selectedOwnerID:
						null);
			}
			
			$paging->track(strtolower(get_class($this)).'limit');
			
			if (!$this->selectedID && $this->ignorePaging)
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
		
		if (!$this->ajaxRequest)
			echo
				"<div class='".
					strtolower(preg_replace('/([A-Z])/', '-\\1', get_class($this))).
					" videos'>";
		
		$i = 1;
		while ($row = sql::fetch($rows)) {
			$row['_VideoNumber'] = $i;
			$row['_Link'] = $this->generateLink($row);
			
			if ($this->format)
				$this->displayFormated($row);
			else
				$this->displayOne($row);
			
			if ($this->columns == $i) {
				echo "<div class='clear-both'></div>";
				$i = 0;
			}
			
			$i++;
		}
		
		echo
			"<div class='clear-both'></div>";
		
		if ($this->showPaging && !$this->randomize && !$this->latests)
			$paging->display();
		
		if (!$this->ajaxRequest)
			echo
				"</div>"; //videos
		
		if ($this->latests)
			return true;
		
		return $paging->items;
	}
}

?>