<?php

/***************************************************************************
 *            ads.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/files.class.php');

class _ads {
	var $limit = 0;
	var $selectedBlockID;
	var $subFolder;
	var $rootPath;
	var $rootURL;
	var $adminPath = 'admin/content/ads';
	var $ajaxRequest = null;
	
	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::ads', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::ads', $this, $handled);
			
			return $handled;
		}
		
		$this->subFolder = date('Ym');
		$this->rootPath = SITE_PATH.'sitefiles/banner/';
		$this->rootURL = url::site().'sitefiles/banner/';
		
		api::callHooks(API_HOOK_AFTER,
			'ads::ads', $this);
	}
	
	function SQL() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::SQL', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::SQL', $this, $handled);
			
			return $handled;
		}
		
		$sql = 
			" SELECT * FROM `{ads}`" .
			" WHERE 1" .
			($this->selectedBlockID?
				" AND `BlockID` = '".$this->selectedBlockID."'":
				null) .
			" AND `Deactivated` = 0" .
			" AND (`StartDate` IS NULL OR `StartDate` <= CURDATE())" .
			" AND (`EndDate` IS NULL OR `EndDate` >= CURDATE())" .
			" AND (`ShowOn` IS NULL OR `ShowOn` LIKE '%".date('w')."%')" .
			" ORDER BY" .
			($this->limit?
				" RAND()":
				" `OrderID`, `ID`");
		
		api::callHooks(API_HOOK_AFTER,
			'ads::SQL', $this, $sql);
		
		return $sql;
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::countAdminItems', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::countAdminItems', $this, $handled);
			
			return $handled;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{ads}`" .
			" LIMIT 1"));
		
		api::callHooks(API_HOOK_AFTER,
			'ads::countAdminItems', $this, $row['Rows']);
		
		return $row['Rows'];
	}
	
	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::setupAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::setupAdmin', $this, $handled);
			
			return $handled;
		}
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Ad'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Layout Blocks'), 
			'?path=admin/site/blocks');
		favoriteLinks::add(
			__('View Website'), 
			SITE_URL);
		
		api::callHooks(API_HOOK_AFTER,
			'ads::setupAdmin', $this);
	}
	
	function setupAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::setupAdminForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::setupAdminForm', $this, $form, $handled);
			
			return $handled;
		}
		
		$form->add(
			__('Promo text'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 300px;');
		
		$form->add(
			__('In Block'),
			'BlockID',
			FORM_INPUT_TYPE_SELECT);
		$form->setValueType(FORM_VALUE_TYPE_INT);
		$form->addValue('', '');
		
		$blockids = array();
		$layoutids = array();
		$disabledblocks = array();
		
		$adblocks = sql::run(
			" SELECT `ID`, `SubBlockOfID`" .
			(JCORE_VERSION >= '0.9'?
				", `LayoutID`":
				null) .
			" FROM `{blocks}`" .
			" WHERE `TypeID` = '".BLOCK_TYPE_AD."'" .
			(JCORE_VERSION >= '0.7'?
				" AND `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0)."'":
				null));
		
		if (sql::rows($adblocks)) {
			while($adblock = sql::fetch($adblocks)) {
				if (isset($blockids[$adblock['SubBlockOfID']])) {
					$blockids[$adblock['ID']] = true;
					
					if (JCORE_VERSION >= '0.9')
						$layoutids[$adblock['LayoutID']] = true;
					
					continue;
				}
				
				foreach(blocks::getBackTraceTree($adblock['ID']) as $block) {
					if (isset($blockids[$block['ID']]))
						continue;
					
					$blockids[$block['ID']] = true;
					
					if (JCORE_VERSION >= '0.9')
						$layoutids[$block['LayoutID']] = true;
				}
			}
			
			foreach(blocks::getTree() as $block) {
				if ((JCORE_VERSION < '0.9' || !isset($layoutids[$block['LayoutID']]) || 
					$block['ID']) && !isset($blockids[$block['ID']]))
					continue;
				
				$form->addValue($block['ID'], 
					($block['SubBlockOfID']?
						str_replace(' ', '&nbsp;', 
							str_pad('', $block['PathDeepnes']*4, ' ')).
						"|- ":
						null) .
					$block['Title']);
				
				if ($block['ID'] && $block['TypeID'] != BLOCK_TYPE_AD)
					$disabledblocks[] = $block['ID'];
			}
				
			$form->disableValues($disabledblocks);
			$form->groupValues(array('0'));
			
		} else {
			$form->edit(
				'BlockID',
				null,
				null,
				FORM_INPUT_TYPE_HIDDEN);
		}
		
		$form->add(
			__('Upload a banner'),
			null,
			FORM_OPEN_FRAME_CONTAINER,
			true);
		
		$form->add(
			__('Banner'),
			'File',
			FORM_INPUT_TYPE_FILE);
		$form->setValueType(FORM_VALUE_TYPE_FILE);
		
		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(__("e.g. image.jpg, flash.swf"));
		else
			$form->addAdditionalText(" (".__("e.g. image.jpg, flash.swf").")");
		
		$form->add(
			__('Link to URL'),
			'URL',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 250px;');
		$form->setValueType(FORM_VALUE_TYPE_URL);
		$form->setTooltipText(__("e.g. http://domain.com"));
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Use Google or other Ad Code'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Ad Code (leave it empty to automatically generate code for flash ads)'),
			'AdCode',
			FORM_INPUT_TYPE_TEXTAREA);
		$form->setStyle('width: ' .
			(JCORE_VERSION >= '0.7'?
				'90%':
				'300px') .
			'; height: 200px;');
		$form->setValueType(FORM_VALUE_TYPE_HTML);
		
		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(__("e.g. Google Ad Code"));
		else
			$form->addAdditionalText(" (".__("e.g. Google Ad Code").")");
			
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Show Ad only within a defined time interval'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Start Date'),
			'StartDate',
			FORM_INPUT_TYPE_DATE);
		$form->setStyle('width: 100px;');
		$form->setValueType(FORM_VALUE_TYPE_DATE);
		
		$form->add(
			__('End Date'),
			'EndDate',
			FORM_INPUT_TYPE_DATE);
		$form->setStyle('width: 100px;');
		$form->setValueType(FORM_VALUE_TYPE_DATE);
		
		$form->add(
			__('Show on'),
			'ShowOn',
			FORM_INPUT_TYPE_CHECKBOX);
		$form->setValueType(FORM_VALUE_TYPE_ARRAY);
			
		for ($i = 0; $i < 7; $i++)
			$form->addValue($i, calendar::int2Day($i));
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Deactivated'),
			'Deactivated',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
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
			'ads::setupAdminForm', $this, $form);
	}
	
	function verifyAdmin(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::verifyAdmin', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::verifyAdmin', $this, $form, $handled);
			
			return $handled;
		}
		
		$reorder = null;
		$orders = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_POST['reordersubmit']))
			$reorder = (string)$_POST['reordersubmit'];
		
		if (isset($_POST['orders']))
			$orders = (array)$_POST['orders'];
		
		if (isset($_POST['delete']))
			$delete = (int)$_POST['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($reorder) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'ads::verifyAdmin', $this, $form);
				return false;
			}
			
			foreach((array)$orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{ads}`" .
					" SET `OrderID` = '".(int)$ovalue."', " .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".(int)$oid."'" .
					($this->userPermissionIDs?
						" AND `ID` IN (".$this->userPermissionIDs.")":
						null));
			}
			
			tooltip::display(
				__("Ads have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'ads::verifyAdmin', $this, $form, $reorder);
			
			return true;
		}
		
		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'ads::verifyAdmin', $this, $form);
				return false;
			}
			
			$result = $this->delete($id);
			
			if ($result)
				tooltip::display(
					__("Ad has been successfully deleted."),
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'ads::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'ads::verifyAdmin', $this, $form);
			
			return false;
		}
		
		$filename = null;
		
		if ($form->get('File')) {
			if (!$filename = $this->upload(
					$form->getFile('File'), 
					$this->rootPath.$this->subFolder.'/'))
			{
				api::callHooks(API_HOOK_AFTER,
					'ads::verifyAdmin', $this, $form);
				
				return false;
			}
		}
		
		$form->set('File', $filename);
		
		if ($edit) {
			$result = $this->edit($id, $form->getPostArray());
			
			if ($result)
				tooltip::display(
					__("Ad has been successfully updated.")." " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);
				
			api::callHooks(API_HOOK_AFTER,
				'ads::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if ($this->userPermissionIDs) {
			api::callHooks(API_HOOK_AFTER,
				'ads::verifyAdmin', $this, $form);
			
			return false;
		}
		
		$newid = $this->add($form->getPostArray());
		
		if ($newid) {
			tooltip::display(
				__("Ad has been successfully created.")." " .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$newid."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			$form->reset();
		}
				
		api::callHooks(API_HOOK_AFTER,
			'ads::verifyAdmin', $this, $form, $newid);
		
		return $newid;
	}
	
	function displayAdminListHeader(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayAdminListHeader', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayAdminListHeader', $this, $row, $handled);
			
			return $handled;
		}
		
		$blockroute = null;
		
		foreach(blocks::getBackTraceTree($row['BlockID']) as $block)
			$blockroute .=
				"<div".
					($block['ID'] == $row['BlockID']?
						" class='bold' style='display: inline; font-size: 120%;'":
						null) .
					">" . 
				($block['SubBlockOfID']?
					str_replace(' ', '&nbsp;', 
						str_pad('', $block['PathDeepnes']*4, ' ')).
					"|- ":
					null). 
				$block['Title'] .
				"</div>";
			
		$totalads = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows` FROM `{ads}` " .
			" WHERE `BlockID` = '".$row['BlockID']."'"));
			
		$pendingads = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows` FROM `{ads}` " .
			" WHERE `BlockID` = '".$row['BlockID']."'" .
			" AND `StartDate` IS NOT NULL" .
			" AND `StartDate` > NOW()"));
			
		$expiredads = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows` FROM `{ads}` " .
			" WHERE `BlockID` = '".$row['BlockID']."'" .
			" AND `EndDate` IS NOT NULL" .
			" AND `EndDate` < CURDATE()"));
			
		$deactivatedads = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows` FROM `{ads}` " .
			" WHERE `BlockID` = '".$row['BlockID']."'" .
			" AND `Deactivated` = 1"));
			
		echo 
			"<th colspan='2'>" .
				"<div class='nowrap'>" .
				($row['BlockID']?
					$blockroute:
					"<span class='red bold'>" .
					__("No Place Defined") .
					"</span>") .
				" (" .
				__("Total").": <b>" .
				$totalads['Rows'] .
				"</b> " .
				($deactivatedads['Rows']?
					"<span class=''>":
					NULL) .
				__("Deactivated").": <b>" .
				$deactivatedads['Rows'] .
				"</b> " .
				($deactivatedads['Rows']?
					"</span>":
					NULL) .
				($pendingads['Rows']?
					"<span class='blue'>":
					NULL) .
				__("Pending").": <b>" .
				$pendingads['Rows'] .
				"</b> " .
				($pendingads['Rows']?
					"</span>":
					NULL) .
				($expiredads['Rows']?
					"<span class='red'>":
					NULL) .
				__("Expired").": <b>" .
				$expiredads['Rows'] .
				"</b>" .
				($expiredads['Rows']?
					"</span>":
					NULL) .
				")" .
				"</div>" .
			"</th>";
		
		api::callHooks(API_HOOK_AFTER,
			'ads::displayAdminListHeader', $this, $row);
	}
	
	function displayAdminListHeaderOptions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayAdminListHeaderOptions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayAdminListHeaderOptions', $this, $handled);
			
			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'ads::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayAdminListHeaderFunctions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayAdminListHeaderFunctions', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'ads::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItemSelected(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayAdminListItemSelected', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayAdminListItemSelected', $this, $row, $handled);
			
			return $handled;
		}
		
		admin::displayItemData(
			__("Created on"),
			calendar::dateTime($row['TimeStamp']));
		
		if ($row['URL'])
			admin::displayItemData(
				__("Link to URL"),
				"<a href='".$row['URL']."' target='_blank'>".
					$row['URL'] .
				"</a>");
		
		if ($row['StartDate'])
			admin::displayItemData(
				__("Start Date"),
				calendar::date($row['StartDate']).
				($row['Pending']?
					" <span class='hilight'>".strtoupper(__("Pending"))."!</span>":
					NULL));
		
		if ($row['EndDate'])
			admin::displayItemData(
				__("End Date"),
				calendar::date($row['EndDate']).
				($row['Expired']?
					" <span class='red'>".strtoupper(__("Expired"))."!</span>":
					NULL));
		
		if (isset($row['ShowOn'])) {
			$days = explode(',', $row['ShowOn']);
			foreach($days as $key=>$day)
				$days[$key] = calendar::int2Day($day);
			
			$showon = implode(', ', $days);
		} else {
			$showon = __("Every day");
		}
		
		admin::displayItemData(
			__("Show on"),
			$showon);
		
		admin::displayItemData(
			__("Stats"),
			__("Displays").": " .
			(int)$row['Shown']." " .
			__("Clicks").": " .
			(int)$row['Clicked']." " .
			__("Ratio").": " .
			" <span class='hilight'>" .
				@number_format($row['Clicked']*100/$row['Shown'], 2) .
			"%</span>");
		
		admin::displayItemData(
			"<hr />");
		admin::displayItemData(
			($row['Location'] && !$this->isFlash($row)?
				"<img src='".url::site()."sitefiles/banner/".
					$row['Location']."' border='0' />":
				null) .
			($row['AdCode']?
				$row['AdCode']:
				null));
		
		api::callHooks(API_HOOK_AFTER,
			'ads::displayAdminListItemSelected', $this, $row);
	}
	
	function displayAdminListItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayAdminListItem', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayAdminListItem', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			"<td>" .
				"<input type='text' name='orders[".$row['ID']."]' " .
					"value='".$row['OrderID']."' " .
					"class='order-id-entry' tabindex='1' />" .
			"</td>" .
			"<td class='auto-width' " .
				($row['Deactivated']?
					"style='text-decoration: line-through;' ":
					null).
				">" .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."' " .
					" class='bold'>" .
					$row['Title'] .
				"</a>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					__("Displays").":" .
					" <b>".(int)$row['Shown']."</b> " .
					__("Clicks").":" .
					" <b>".(int)$row['Clicked']."</b> " .
					__("Ratio").":" .
					" <b class='hilight'>" .
						@number_format($row['Clicked']*100/$row['Shown'], 2) .
						"%</b>" .
				"</div>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'ads::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListItemOptions(&$row){
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayAdminListItemOptions', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayAdminListItemOptions', $this, $row, $handled);
			
			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'ads::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayAdminListItemFunctions', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayAdminListItemFunctions', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete').
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete').
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'ads::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminListFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayAdminListFunctions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayAdminListFunctions', $this, $handled);
			
			return $handled;
		}
		
		echo 
			"<input type='submit' name='reordersubmit' value='" .
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
		
		api::callHooks(API_HOOK_AFTER,
			'ads::displayAdminListFunctions', $this);
	}
	
	function displayAdminList(&$rows) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayAdminList', $this, $rows);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayAdminList', $this, $rows, $handled);
			
			return $handled;
		}
		
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		echo
			"<form action='".
				url::uri('edit, delete')."' method='post'>" .
				"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";
		
		while($row = sql::fetch($rows)) {
			echo 
				"<table class='list' cellpadding='0' cellspacing='0'>" .
				"<thead>" .
				"<tr>";
			
			$this->displayAdminListHeader($row);
			$this->displayAdminListHeaderOptions();
			
			if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
				$this->displayAdminListHeaderFunctions();
			
			echo
				"</tr>" .
				"</thead>" .
				"<tbody>";
				
			$ads = sql::run(
				" SELECT `{ads}`.*," .
				" IF (`EndDate` IS NOT NULL AND `EndDate` < CURDATE(), 'true', NULL) AS `Expired`," .
				" IF (`StartDate` IS NOT NULL AND `StartDate` > CURDATE(), 'true', NULL) AS `Pending`," .
				" (`Shown`/`Clicked`) AS `AdRatio`" .
				" FROM `{ads}`" .
				" WHERE `BlockID` = '".$row['BlockID']."'" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				" ORDER BY `OrderID`, `ID`");
			
			$i = 0;	
			while($ad = sql::fetch($ads)) {
				echo
					"<tr".($i%2?" class='pair'":NULL).">";
				
				$this->displayAdminListItem($ad);
				$this->displayAdminListItemOptions($ad);
				
				if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
					$this->displayAdminListItemFunctions($ad);
				
				echo
					"</tr>";
			
				if ($ad['ID'] == $id) {
					echo
						"<tr".($i%2?" class='pair'":NULL).">" .
							"<td class='auto-width' colspan='10'>" .
								"<div class='admin-content-preview'>";
					
					$this->displayAdminListItemSelected($ad);
					
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
		}
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			$this->displayAdminListFunctions();
			
			echo
				"<div class='clear-both'></div>" .
				"<br />";
		}
		
		echo
			"</form>";
		
		api::callHooks(API_HOOK_AFTER,
			'ads::displayAdminList', $this, $rows);
	}
	
	function displayAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayAdminForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayAdminForm', $this, $form, $handled);
			
			return $handled;
		}
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'ads::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayAdminTitle', $this, $ownertitle);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayAdminTitle', $this, $ownertitle, $handled);
			
			return $handled;
		}
		
		admin::displayTitle(
			__('Ads Administration'), 
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'ads::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayAdminDescription', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayAdminDescription', $this, $handled);
			
			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'ads::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayAdmin', $this, $handled);
			
			return $handled;
		}
		
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
				
		if ($delete && $id && empty($_POST['delete'])) {
			$selected = sql::fetch(sql::run(
				" SELECT `Title` FROM `{ads}`" .
				" WHERE `ID` = '".$id."'" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null)));
			
			url::displayConfirmation(
				'<b>'.__('Delete').'?!</b> "'.$selected['Title'].'"');
		}
		
		$form = new form(
				($edit?
					__("Edit Ad / Banner"):
					__("New Ad / Banner")),
				'neweditbanner');
		
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			((!$edit && !$delete) || !$this->userPermissionIDs ||
			in_array($id, explode(',', $this->userPermissionIDs))))
			$verifyok = $this->verifyAdmin($form);
		
		$rows = sql::run(
			" SELECT DISTINCT `BlockID` FROM `{ads}` " .
			" WHERE 1" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			" ORDER BY `BlockID`");
		
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
					__("No ads found."),
					TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || 
			($edit && in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{ads}`" .
					" WHERE `ID` = '".$id."'"));
				
				$form->setValues($selected);
				
				$form->setValue('ShowOn', 
					(isset($selected['ShowOn'])?
						explode(',', $selected['ShowOn']):
						null));
				
				$form->setValue('File', $selected['Location']);
			}
			
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form); 
		
		echo 
			"</div>";	//admin-content
		
		api::callHooks(API_HOOK_AFTER,
			'ads::displayAdmin', $this);
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::add', $this, $values);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::add', $this, $values, $handled);
			
			return $handled;
		}
		
		if ($values['OrderID'] == '') {
			$row = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{ads}` " .
				" ORDER BY `OrderID` DESC"));
			
			$values['OrderID'] = (int)$row['OrderID']+1;
			
		} else {
			sql::run(
				" UPDATE `{ads}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		if ($values['File'] && !$values['AdCode'] && 
			$this->isFlash($values)) 
		{
			list($width, $height) = getimagesize($this->rootPath.
				$this->subFolder.'/'.$values['File']);
			
			$values['AdCode'] = 
				"<object width='".$width."' height='".$height."'>\n" .
					"<param name='movie' value='".
						$this->rootURL.$this->subFolder.'/'.$values['File'].
						"'>\n" .
					"<embed src='".
						$this->rootURL.$this->subFolder.'/'.$values['File'].
						"' width='".$width."' height='".$height."'>\n" .
					"</embed>\n" .
				"</object>\n";
		}
		
		$newid = sql::run(
			" INSERT INTO `{ads}` SET" .
			" `TimeStamp` = NOW()," .
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `URL` = '".
				sql::escape($values['URL'])."'," .
			($values['File']?
				" `Location` = '".
					sql::escape($this->subFolder.'/'.$values['File'])."',":
				NULL) .
			" `AdCode` = '".
				sql::escape($values['AdCode'])."'," .
			" `BlockID` = '".
				(int)$values['BlockID']."', " .
			" `Deactivated` = '".
				(int)$values['Deactivated']."', " .
			($values['StartDate']?
				" `StartDate` = '".
					sql::escape($values['StartDate'])."', ":
				" `StartDate` = NULL, ") .
			($values['EndDate']?
				" `EndDate` = '".
					sql::escape($values['EndDate'])."', ":
				" `EndDate` = NULL, ") .
			" `ShowOn` = ".
				($values['ShowOn']?
					"'".implode(',', $values['ShowOn'])."'":
					'NULL').
				"," .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
				
		if (!$newid)
			tooltip::display(
				sprintf(__("Ad couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'ads::add', $this, $values, $newid);
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::edit', $this, $id, $values);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::edit', $this, $id, $values, $handled);
			
			return $handled;
		}
		
		$ad = sql::fetch(sql::run(
			" SELECT * FROM `{ads}`" .
			" WHERE `ID` = '".$id."'"));
			
		if (!$values['AdCode'] && 
			($this->isFlash($values) || $this->isFlash($ad))) 
		{
			$file = $ad['Location'];
			
			if ($values['File'])
				$file = $this->subFolder.'/'.$values['File'];
			
			list($width, $height) = getimagesize($this->rootPath.
				$file);
			
			$values['AdCode'] = 
				"<object width='".$width."' height='".$height."'>\n" .
					"<param name='movie' value='".
						$this->rootURL.$file.
						"'>\n" .
					"<embed src='".
						$this->rootURL.$file.
						"' width='".$width."' height='".$height."'>\n" .
					"</embed>\n" .
				"</object>\n";
		}
			
		sql::run(
			" UPDATE `{ads}` SET" .
			" `TimeStamp` = `TimeStamp`," .
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `URL` = '".
				sql::escape($values['URL'])."'," .
			($values['File']?
				" `Location` = '".
					sql::escape($this->subFolder.'/'.$values['File'])."',":
				NULL) .
			" `AdCode` = '".
				sql::escape($values['AdCode'])."'," .
			" `BlockID` = '".
				(int)$values['BlockID']."', " .
			" `Deactivated` = '".
				(int)$values['Deactivated']."', " .
			($values['StartDate']?
				" `StartDate` = '".
					sql::escape($values['StartDate'])."', ":
				" `StartDate` = NULL, ") .
			($values['EndDate']?
				" `EndDate` = '".
					sql::escape($values['EndDate'])."', ":
				" `EndDate` = NULL, ") .
			" `ShowOn` = ".
				($values['ShowOn']?
					"'".implode(',', $values['ShowOn'])."'":
					'NULL') .
				"," .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");
		
		$result = (sql::affected() != -1);
		
		if (!$result)
			tooltip::display(
				sprintf(__("Ad couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'ads::edit', $this, $id, $values, $result);
		
		return $result;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::delete', $this, $id);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::delete', $this, $id, $handled);
			
			return $handled;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT `Location` FROM `{ads}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		sql::run(
			" DELETE FROM `{ads}`" .
			" WHERE `ID` = '".(int)$id."'");
			
		if ($row['Location'] && !sql::count(
			" SELECT COUNT(`ID`) AS `Rows` FROM `{ads}`" .
			" WHERE `Location` = '".$row['Location']."'")) 
		{
			@unlink($this->rootPath.$row['Location']);
		}
		
		api::callHooks(API_HOOK_AFTER,
			'ads::delete', $this, $id);
		
		return true;
	}
	
	function upload($file, $to) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::upload', $this, $file, $to);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::upload', $this, $file, $to, $handled);
			
			return $handled;
		}
		
		$result = files::upload($file, $to, FILE_TYPE_BANNER);
		
		api::callHooks(API_HOOK_AFTER,
			'ads::upload', $this, $file, $to, $result);
		
		return $result;
	}
	
	// ************************************************   Client Part
	function redirect($adid = 0) {
		if (!(int)$adid)
			return false;
			
		$row = sql::fetch(sql::run(
			" SELECT `ID`, `URL` FROM `{ads}` " .
			" WHERE `ID` = '".(int)$adid."'"));
			
		if (!$row['ID']) {
			echo __("Ad not found!");
			return false;
		}
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::redirect', $this, $adid);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::redirect', $this, $adid, $handled);
			
			return $handled;
		}
		
		sql::run(
			" UPDATE `{ads}` SET" .
			" `TimeStamp` = `TimeStamp`," .
			" `Clicked` = `Clicked` + 1" .
			" WHERE `ID` = '".$row['ID']."'");
		
		if (preg_match('/:\/\//i', $row['URL']))
			Header("Location: ".$row['URL']);
		else
			Header("Location: ".url::site().$row['URL']);
		
		api::callHooks(API_HOOK_AFTER,
			'ads::redirect', $this, $adid, $row);
		
		return true;
	}
	
	function isFlash($row) {
		$filename = null;
		
		if (isset($row['File']))
			$filename = $row['File'];
		
		if(isset($row['Location']))
			$filename = $row['Location'];
			
		if (!$filename)
			return false;
		
		return preg_match('/\.swf$/i', $filename);
	}
	
	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::ajaxRequest', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::ajaxRequest', $this, $handled);
			
			return $handled;
		}
		
		$result = false;
		if (isset($_GET['redirect']) && (int)$_GET['redirect'])
			$result = $this->redirect((int)$_GET['redirect']);
		
		api::callHooks(API_HOOK_AFTER,
			'ads::ajaxRequest', $this, $result);
		
		return $result;
	}
	
	function displayImage(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayImage', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayImage', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			"<img src='".$this->rootURL.$row['Location'] .
				"' border='0' " .
				"alt='".htmlspecialchars($row['Title'], ENT_QUOTES)."' " .
				"title='".htmlspecialchars($row['Title'], ENT_QUOTES)."' />";
		
		api::callHooks(API_HOOK_AFTER,
			'ads::displayImage', $this, $row);
	}
	
	function displayCode(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'ads::displayCode', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'ads::displayCode', $this, $row, $handled);
			
			return $handled;
		}
		
		echo $row['AdCode'];
		
		api::callHooks(API_HOOK_AFTER,
			'ads::displayCode', $this, $row);
	}
	
	function displayOne(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE, 
			'ads::displayOne', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER, 
				'ads::displayOne', $this, $row, $handled);
			
			return $handled;
		}
		
		sql::run(
			" UPDATE `{ads}` SET" .
			" `TimeStamp` = `TimeStamp`," .
			" `Shown` = `Shown` + 1" .
			" WHERE `ID` = '".(int)$row['ID']."'");
			
		echo 
			"<div class='ad".
				(isset($row['_CSSClass'])?
					" ".$row['_CSSClass']:
					null) .
				"'>";
		
		if (JCORE_VERSION < '0.7' || $row['URL'])
			echo
				"<a href='".url::uri().
					"&amp;request=ads" .
					"&amp;redirect=".$row['ID'].
					"&amp;ajax=1" .
					"'" .
					(preg_match('/:\/\//i', $row['URL'])?
						" target='_blank'":
						null) .
					">";
			
		if ($row['Location'] && !$this->isFlash($row))
			$this->displayImage($row);
		
		echo
				"<span class='adcode'>";
		
		$this->displayCode($row);
		
		echo
				"</span>";
		
		if (JCORE_VERSION < '0.7' || $row['URL'])
			echo
				"</a>";
		
		echo
			"</div>";
		
		api::callHooks(API_HOOK_AFTER, 
			'ads::displayOne', $this, $row);
	}
			
	function display() {
		$rows = sql::run(
			$this->SQL() .
			($this->limit?
				" LIMIT ".$this->limit:
				null));
		
		$i = 1;
		$total = sql::rows($rows);
		
		if (!$total)
			return;
		
		$handled = api::callHooks(API_HOOK_BEFORE, 
			'ads::display', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER, 
				'ads::display', $this, $handled);
			
			return $handled;
		}
		
		echo "<div class='ads'>";
		
		while($row = sql::fetch($rows)) {
			$row['_CSSClass'] = null;
			
			if ($i == 1)
				$row['_CSSClass'] .= ' first';
			if ($i == $total)
				$row['_CSSClass'] .= ' last';
			
			$this->displayOne($row);
			
			$i++;
		}
		
		echo 
				"<div class='clear-both'></div>" .
			"</div>";
		
		api::callHooks(API_HOOK_AFTER, 
			'ads::display', $this);
	}
}

?>