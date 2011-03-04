<?php

/***************************************************************************
 *            blocks.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/

include_once('lib/menus.class.php');
include_once('lib/posts.class.php'); 
include_once('lib/contentcodes.class.php');
include_once('lib/ads.class.php');

define('BLOCK_TYPE_MAIN_CONTENT', 1);
define('BLOCK_TYPE_CONTENT', 2);
define('BLOCK_TYPE_MENU', 3);
define('BLOCK_TYPE_AD', 4);
 
define('BLOCK_EVERYONE', 0);
define('BLOCK_GUESTS_ONLY', 1);
define('BLOCK_USERS_ONLY', 2);
define('BLOCK_ADMINS_ONLY', 3);

class _blocks {
	var $cachingInProgress = false;
	var $arguments = '';
	var $selectedLanguageID;
	var $selectedPageID;
	var $ignoreCache4BlockIDs = array();
	var $adminPath = 'admin/site/blocks';
	
	function __construct() {
		if (isset($_GET['languageid']))
			$this->selectedLanguageID = (int)$_GET['languageid'];
		
		if (isset($_GET['pageid']))
			$this->selectedPageID = (int)$_GET['pageid'];
	}
	
	function SQL() {
		return
			(JCORE_VERSION >= '0.4'?
				" SELECT *," .
				" IF(DATE_SUB(NOW(), INTERVAL `CacheRefreshTime` MINUTE) > `CacheTimeStamp`, 1, 0) AS `CacheExpired`" .
				" FROM `{blocks}`": 
				" SELECT * FROM `{blocks}`") .
			" WHERE 1" .
			(JCORE_VERSION >= '0.7'?
				" AND `TemplateID` = '".
					(template::$selected && (WEBSITE_TEMPLATE_SETFORADMIN ||
					 !isset($GLOBALS['ADMIN']) || !$GLOBALS['ADMIN'])?
						(int)template::$selected['ID']:
						0) .
					"'":
				null) .
			" AND !`Deactivated`" .
			" AND !`SubBlockOfID`" .
			" AND (!`ViewableBy` OR " .
				($GLOBALS['USER']->loginok?
					($GLOBALS['USER']->data['Admin']?
						" `ViewableBy` IN (2, 3)":
						" `ViewableBy` = 2"):
					" `ViewableBy` = 1") .
			" )" .
			" ORDER BY `OrderID`";		
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{blocks}`" .
			(JCORE_VERSION >= '0.7'?
				" WHERE `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0)."'":
				null) .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Block'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('CSS Editor'), 
			'?path=admin/site/template/templatecsseditor');
		favoriteLinks::add(
			__('View Website'), 
			SITE_URL);
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 250px;');
		
		$form->add(
			__('Type'),
			'TypeID',
			FORM_INPUT_TYPE_SELECT);
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		$form->addValue(
			'', '');
		$form->addValue(
			BLOCK_TYPE_MENU, $this->type2Text(BLOCK_TYPE_MENU));
		$form->addValue(
			BLOCK_TYPE_AD, $this->type2Text(BLOCK_TYPE_AD));
		$form->addValue(
			BLOCK_TYPE_MAIN_CONTENT, $this->type2Text(BLOCK_TYPE_MAIN_CONTENT));
		
		if (JCORE_VERSION < '0.8')
			$form->addValue(
				BLOCK_TYPE_CONTENT, $this->type2Text(BLOCK_TYPE_CONTENT));
		
		$form->add(
			__('Sub Block of'),
			'SubBlockOfID',
			FORM_INPUT_TYPE_SELECT);
		$form->setValueType(FORM_VALUE_TYPE_INT);
			
		$form->addValue('', '');
		
		$form->add(
			__('Content Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Content'),
			'Content',
			FORM_INPUT_TYPE_TEXTAREA);
		$form->setStyle('width: ' .
			(JCORE_VERSION >= '0.7'?
				'90%':
				'350px') .
			'; height: 200px;');
		$form->setValueType(FORM_VALUE_TYPE_HTML);
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Display Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Block ID'),
			'BlockID',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 200px;');
		$form->setValueType(FORM_VALUE_TYPE_LIMITED_STRING);
		
		$form->add(
			__('CSS Class'),
			'Class',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 300px;');
		
		$languages = languages::get();
		
		if ($languages) {
			$form->add(
				__('In Language(s)'),
				'LanguageIDs',
				FORM_INPUT_TYPE_MULTISELECT);
			$form->setValueType(FORM_VALUE_TYPE_ARRAY);
			$form->setStyle('height: 70px;');
			
			while($language = sql::fetch($languages))
				$form->addValue($language['ID'], 
					$language['Title']);
					
			$form->add(
				'LanguageExcept',
				'LanguageExcept',
				FORM_INPUT_TYPE_HIDDEN,
				false,
				0);
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
		}
		
		$form->add(
			__('On Page(s)'),
			(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs'),
			FORM_INPUT_TYPE_MULTISELECT);
		$form->setValueType(FORM_VALUE_TYPE_ARRAY);
		$form->setStyle('height: 150px;');
		
		$form->addValue('A', '* ' .
			__('Administration Section'));
		
		foreach(pages::getTree() as $page)
			$form->addValue($page['ID'], 
				($page[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]?
					str_replace(' ', '&nbsp;', 
						str_pad('', $page['PathDeepnes']*4, ' ')).
					"|- ":
					null) .
				$page['Title']);
		
		$form->groupValues(array('0'));
		
		$form->add(
			'Page Except',
			(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept'),
			FORM_INPUT_TYPE_HIDDEN,
			false,
			0);
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
		
		$form->add(
			__('Viewable by'),
			'ViewableBy',
			FORM_INPUT_TYPE_SELECT);
		$form->setValueType(FORM_VALUE_TYPE_INT);
			
		$form->addValue(
			BLOCK_EVERYONE, $this->access2Text(BLOCK_EVERYONE));
		$form->addValue(
			BLOCK_GUESTS_ONLY, $this->access2Text(BLOCK_GUESTS_ONLY));
		$form->addValue(
			BLOCK_USERS_ONLY, $this->access2Text(BLOCK_USERS_ONLY));
		$form->addValue(
			BLOCK_ADMINS_ONLY, $this->access2Text(BLOCK_ADMINS_ONLY));
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		if (JCORE_VERSION >= '0.4') {
			$form->add(
				__('Caching Options'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
			
			$form->add(
				__('Enable Caching'),
				'Caching',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				1);
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
			$form->addAdditionalText(
				__("(cache the content of this block including all sub blocks)"));
			
			$form->add(
				__('Only for Guests'),
				'CacheOnlyForGuests',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				1);
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
			$form->addAdditionalText(
				__("(don't use caching for logged in users)"));
			
			$form->add(
				__('Refresh Time'),
				'CacheRefreshTime',
				FORM_INPUT_TYPE_TEXT,
				false,
				10);
			$form->setValueType(FORM_VALUE_TYPE_INT);
			$form->setStyle('width: 50px;');
			
			$form->addAdditionalText(
				__("(cache refresh time interval in minutes)"));
			
			$form->add(
				__('Refresh Now'),
				'CacheRefreshNow',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				1);
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
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
			__('Deactivated'),
			'Deactivated',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
		
		$form->addAdditionalText(
			"<span class='comment' style='text-decoration: line-through;'>" .
			__("(marked with strike through)").
			"</span>");	
			
		$form->add(
			__('Limit'),
			'Limit',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
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
					" UPDATE `{blocks}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'");
			}
			
			tooltip::display(
				__("Blocks have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				__("Block has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if ($edit && $form->get('SubBlockOfID')) {
			foreach(blocks::getBackTraceTree($form->get('SubBlockOfID')) as $block) {
				if ($block['ID'] == $id) {
					tooltip::display(
						__("Block cannot be subblock of itself!"),
						TOOLTIP_ERROR);
					
					return false;
				}
			}
		}
			
		if (!$edit && !$form->get('BlockID') && 
			!$form->get('Class'))
			$form->set('BlockID', url::genPathFromString($form->get('Title')));
				
		if (JCORE_VERSION >= '0.4' && !$form->get('CacheRefreshTime'))
			$form->set('CacheRefreshTime', 10);
		
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray()))
				return false;
				
			tooltip::display(
				__("Block has been successfully updated.")." " .
				"<a href='".SITE_URL."' target='_blank'>" .
					__("View Website") .
				"</a>" .
				" - " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$newid = $this->add($form->getPostArray()))
			return false;
				
		tooltip::display(
			__("Block has been successfully created.") .
			"<a href='".SITE_URL."' target='_blank'>" .
				__("View Website") .
			"</a>" .
			" - " .
			"<a href='".url::uri('id, edit, delete') .
				"&amp;id=".$newid."&amp;edit=1#adminform'>" .
				__("Edit") .
			"</a>",
			TOOLTIP_SUCCESS);
			
		$form->reset();
		return true;
	}
	
	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Block ID / CSS Class")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Type")."</span></th>";
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
				"<a href='".
				url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."' " .
					(!$row['SubBlockOfID']?
						"class='bold' ":
						null).
					">" .
				$row['Title'] .
				"</a> " .
				"<div class='comment' style='padding-left: 10px;'>" .
					($row['BlockID']?
						" #".$row['BlockID']."<br />":
						null) .
					($row['Class']?
						" .".str_replace(' ', ' .', 
							$row['Class']):
						null) .
				"</div>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				"<span class='nowrap'>" .
				($row['TypeID']?
					$this->type2Text($row['TypeID']):
					null) .
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
	
	function displayAdminListItemSelected(&$row) {
		$pageroute = null;
		if ($row[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')]) {
			foreach(explode('|', $row[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')]) as $blockpage) {
				if ($blockpage == 'A') {
					$pageroute .= 
						"<div>* " .
							__("Administration Section") .
						"</div>";
					continue;
				}
				
				foreach(pages::getBackTraceTree($blockpage) as $page) {
					$pageroute .=
						"<div ".
							($page['ID'] != $blockpage?
								"class='comment'":
								null) .
							">" . 
						($page[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]?
							str_replace(' ', '&nbsp;', 
								str_pad('', $page['PathDeepnes']*4, ' ')).
							"|- ":
							null). 
						$page['Title'] .
						"</div>";
				}
			}
		}
		
		$languageroute = null;
		if ($row['LanguageIDs']) {
			foreach(explode('|', $row['LanguageIDs']) as $languageid) {
				$language = languages::get($languageid);
				
				$languageroute .=
						"<div>" . 
							$language['Title'] .
						"</div>";
			}
		}
		
		if ($row[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')])
			admin::displayItemData(
				($row[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')]?
					__("Display Except on Page"):
					__("Display Only on Page")),
				$pageroute);
		
		if ($row['LanguageIDs'])
			admin::displayItemData(
				($row['LanguageExcept']?
					__("Display Except in Language"):
					__("Display Only in Language")),
				$languageroute);
		
		admin::displayItemData(
			__("Viewable by"),
			$this->access2Text($row['ViewableBy']));
		
		if ($row['Limit'])
			admin::displayItemData(
				__("Limit"),
				$row['Limit']);
		
		if (JCORE_VERSION >= '0.4' && $row['Caching']) {
			admin::displayItemData(
				__("Enable Caching"),
				__("Yes") .
				($row['CacheOnlyForGuests']?
					" (".__("Only for Guests").")":
					null));
			
			admin::displayItemData(
				__("Refresh Time"),
				printf(__("%s minutes"),
					$row['CacheRefreshTime']));
		}
		
		admin::displayItemData(
			"<hr />");
		admin::displayItemData(
			"<code>" .
				nl2br(htmlspecialchars($row['Content'])).
			"</code>");
	}
	
	function displayAdminListFunctions() {
		echo 
			"<input type='submit' name='reordersubmit' value='" .
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminList(&$rows, $rowpair = null) {
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if (isset($rowpair)) {
			echo 
				"<tr".($rowpair?" class='pair'":NULL).">" .
					"<td></td>" .
					"<td colspan='4' class='auto-width nopadding'>";
		} else {
			echo
				"<form action='".url::uri('edit, delete')."' method='post'>";
		}
				
		echo "<table class='list' cellpadding='0' cellspacing='0'>";
		
		if (!isset($rowpair)) {
			echo
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
		}
		
		$i = 0;		
		while($row = sql::fetch($rows)) {
			echo 
				"<tr".($i%2?" class='pair'":null).">";
				
			$this->displayAdminListItem($row);
			$this->displayAdminListItemOptions($row);
					
			if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
				$this->displayAdminListItemFunctions($row);
					
			echo
				"</tr>";
			
			if ($row['ID'] == $id) {
				echo
					"<tr".($i%2?" class='pair'":null).">" .
						"<td colspan='10' class='auto-width'>" .
							"<div class='admin-content-preview'>";
				
				$this->displayAdminListItemSelected($row);
				
				echo
							"</div>" .
						"</td>" .
					"</tr>";
			}
			
			$subrows = sql::run(
				" SELECT * FROM `{blocks}`" .
				" WHERE `SubBlockOfID` = '".$row['ID']."'" .
				(JCORE_VERSION >= '0.7'?
					" AND `TemplateID` = '".
						(template::$selected?
							(int)template::$selected['ID']:
							0)."'":
					null) .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				" ORDER BY `OrderID`");
			
			if (sql::rows($subrows))
				$this->displayAdminList($subrows, $i%2);
			
			$i++;
		}
		
		if (isset($rowpair)) {
			echo 
				"</table>" .
				"</td>" .
				"</tr>";
		} else {
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
		
		return true;
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(__('Blocks Administration'));
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$edit = null;
		$id = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
				
		$form = new form(
				($edit?
					__("Edit Block"):
					__("New Block")),
				'neweditblock');
		
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
		
		$rows = sql::run(
			" SELECT * FROM `{blocks}`" .
			" WHERE 1" .
			(JCORE_VERSION >= '0.7'?
				" AND `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0)."'":
				null) .
			" AND !`SubBlockOfID`" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			" ORDER BY `OrderID`");
		
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No blocks found."),
				TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{blocks}` " .
					" WHERE `ID` = '".$id."'"));
					
				$form->setValues($row);
			}
			
			foreach(blocks::getTree() as $row) {
				$form->addValue('SubBlockOfID',
					$row['ID'], 
					($row['SubBlockOfID']?
						str_replace(' ', '&nbsp;', 
							str_pad('', $row['PathDeepnes']*4, ' ')).
						"|- ":
						null) .
					$row['Title']);
			}
			
			if ($form->getElementID('LanguageIDs')) {
				$form->addAdditionalText(
					'LanguageIDs',
					" <label>" .
						"<input type='radio' name='LanguageExceptRadio' value='0' " .
							"onclick=\"this.form.LanguageExcept.value = 0;\" " .
							(!$form->get('LanguageExcept')?
								"checked='checked'":
								null) .
							" /> " .
						__("Only") .
					"</label> " .
					" <label>" .
						"<input type='radio' name='LanguageExceptRadio' value='1' " .
							"onclick=\"this.form.LanguageExcept.value = 1;\" " .
							($form->get('LanguageExcept')?
								"checked='checked'":
								null) .
							" /> " .
						__("Except") .
					"</label>");
			}
			
			$form->addAdditionalText(
				(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs'),
				" <label>" .
					"<input type='radio' name='PageExceptRadio' value='0' " .
						"onclick=\"this.form.PageExcept.value = 0;\" " .
						(!$form->get((JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept'))?
							"checked='checked'":
							null) .
						" /> " .
					__("Only") .
				"</label> " .
				" <label>" .
					"<input type='radio' name='PageExceptRadio' value='1' " .
						"onclick=\"this.form.PageExcept.value = 1;\" " .
						($form->get((JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept'))?
							"checked='checked'":
							null) .
						" /> " .
					__("Except") .
				"</label>");
			
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
			
		if (!isset($values['LanguageIDs']))
			$values['LanguageIDs'] = null;
		
		if (!isset($values['LanguageExcept']))
			$values['LanguageExcept'] = null;
		
		if ($values['OrderID'] == '') {
			$row = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{blocks}` " .
				" WHERE 1" .
				(JCORE_VERSION >= '0.7'?
					" AND `TemplateID` = '".
						(template::$selected?
							(int)template::$selected['ID']:
							0)."'":
					null) .
				" AND `SubBlockOfID` = '".(int)$values['SubBlockOfID']."'" .
				" ORDER BY `OrderID` DESC"));
			
			$values['OrderID'] = (int)$row['OrderID']+1;
			
		} else {
			sql::run(
				" UPDATE `{blocks}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `CacheTimeStamp` = `CacheTimeStamp`" .
				" WHERE 1" .
				(JCORE_VERSION >= '0.7'?
					" AND `TemplateID` = '".
						(template::$selected?
							(int)template::$selected['ID']:
							0)."'":
					null) .
				" AND `SubBlockOfID` = '".(int)$values['SubBlockOfID']."'" .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		$newid = sql::run(
			" INSERT INTO `{blocks}` SET ".
			(JCORE_VERSION >= '0.7'?
				" `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0)."',":
				null) .
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Content` = '".
				sql::escape($values['Content'])."'," .
			" `BlockID` = '".
				sql::escape($values['BlockID'])."'," .
			" `Class` = '".
				sql::escape($values['Class'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `SubBlockOfID` = '".
				(int)$values['SubBlockOfID']."'," .
			" `TypeID` = '".
				(int)$values['TypeID']."'," .
			" `".(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')."` = '".
				sql::escape(implode('|', (array)$values[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')]))."'," .
			" `".(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')."` = '".
				(int)$values[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')]."'," .
			" `LanguageIDs` = '".
				sql::escape(implode('|', (array)$values['LanguageIDs']))."'," .
			" `LanguageExcept` = '".
				(int)$values['LanguageExcept']."'," .
			" `ViewableBy` = '".
				(int)$values['ViewableBy']."'," .
			(JCORE_VERSION >= '0.4'?
				" `Caching` = '".
					($values['Caching']?
						'1':
						'0').
					"'," .
				" `CacheOnlyForGuests` = '".
					($values['CacheOnlyForGuests']?
						'1':
						'0').
					"'," .
				" `CacheRefreshTime` = '".
					(int)$values['CacheRefreshTime']."'," .
				" `CacheTimeStamp` = '0000-00-00 00:00:00',":
				null) .
			" `Limit` = '".
				(int)$values['Limit']."'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
		
		if (!$newid) {
			tooltip::display(
				sprintf(__("Block couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		if (!isset($values['LanguageIDs']))
			$values['LanguageIDs'] = null;
		
		if (!isset($values['LanguageExcept']))
			$values['LanguageExcept'] = null;
		
		$block = sql::fetch(sql::run(
			" SELECT * FROM `{blocks}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		if ($block['TypeID'] == BLOCK_TYPE_MAIN_CONTENT &&
			$values['TypeID'] != BLOCK_TYPE_MAIN_CONTENT)
		{
			$othermainblock = sql::fetch(sql::run(
				" SELECT * FROM `{blocks}`" .
				" WHERE 1" .
				(JCORE_VERSION >= '0.7'?
					" AND `TemplateID` = '".
						(template::$selected?
							(int)template::$selected['ID']:
							0)."'":
					null) .
				" AND `ID` != '".(int)$id."'" .
				" AND `TypeID` = '".BLOCK_TYPE_MAIN_CONTENT."'" .
				" LIMIT 1"));
			
			if (!$othermainblock) {
				tooltip::display(
					__("Block type cannot be changed as it is a " .
						"\"Main Content\" block! Please first create " .
						"another \"Main Content\" block and then try to " .
						"edit this block again."),
					TOOLTIP_ERROR);
				return false;
			}
		}
		
		if ((int)$values['SubBlockOfID'] && 
			(int)$values['SubBlockOfID'] != $block['SubBlockOfID']) 
		{
			$parentblock = sql::fetch(sql::run(
				" SELECT * FROM `{blocks}`" .
				" WHERE `ID` = '".(int)$values['SubBlockOfID']."'"));
			
			if ($parentblock['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;
			
			if ($parentblock['ViewableBy'] && !$values['ViewableBy'])
				$values['ViewableBy'] = (int)$parentblock['ViewableBy'];
		}
		
		sql::run(
			" UPDATE `{blocks}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Content` = '".
				sql::escape($values['Content'])."'," .
			" `BlockID` = '".
				sql::escape($values['BlockID'])."'," .
			" `Class` = '".
				sql::escape($values['Class'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `SubBlockOfID` = '".
				(int)$values['SubBlockOfID']."'," .
			" `TypeID` = '".
				(int)$values['TypeID']."'," .
			" `".(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')."` = '".
				sql::escape(implode('|', (array)$values[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')]))."'," .
			" `".(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')."` = '".
				(int)$values[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')]."'," .
			" `LanguageIDs` = '".
				sql::escape(implode('|', (array)$values['LanguageIDs']))."'," .
			" `LanguageExcept` = '".
				(int)$values['LanguageExcept']."'," .
			" `ViewableBy` = '".
				(int)$values['ViewableBy']."'," .
			(JCORE_VERSION >= '0.4'?
				" `Caching` = '".
					($values['Caching']?
						'1':
						'0').
					"'," .
				" `CacheOnlyForGuests` = '".
					($values['CacheOnlyForGuests']?
						'1':
						'0').
					"'," .
				" `CacheRefreshTime` = '".
					(int)$values['CacheRefreshTime']."'," .
				($values['CacheRefreshNow']?
					" `CacheTimeStamp` = '0000-00-00 00:00:00',":
					" `CacheTimeStamp` = `CacheTimeStamp`,"):
				null) .
			" `Limit` = '".
				(int)$values['Limit']."'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Block couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		foreach(blocks::getTree((int)$id) as $row) {
			$updatesql = null;
			
			if (($block['Deactivated'] && !$values['Deactivated']) ||
				(!$block['Deactivated'] && $values['Deactivated'])) 
			{
				if (!$row['Deactivated'] && $values['Deactivated'])
					$updatesql[] = " `Deactivated` = 1";
				if ($row['Deactivated'] && !$values['Deactivated'])
					$updatesql[] = " `Deactivated` = 0";
			}
			
			if ($block['ViewableBy'] != $values['ViewableBy'] &&
				$row['ViewableBy'] != $values['ViewableBy'])
				$updatesql[] = " `ViewableBy` = '".(int)$values['ViewableBy']."'";
			
			if ($updatesql)
				sql::run(
					" UPDATE `{blocks}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
		}
		
		foreach(blocks::getBackTraceTree((int)$id) as $row) {
			$updatesql = null;
			
			if ($row['Deactivated'] && !$values['Deactivated'])
				$updatesql[] = " `Deactivated` = 0";
			if ($row['ViewableBy'] > $values['ViewableBy'])
				$updatesql[] = " `ViewableBy` = '".(int)$values['ViewableBy']."'";
			
			if ($updatesql)
				sql::run(
					" UPDATE `{blocks}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
		}

		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		$subblocks = blocks::getTree($id);
		$maincontentblocks = null;
		
		$block = sql::fetch(sql::run(
			" SELECT * FROM `{blocks}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		if ($block['TypeID'] == BLOCK_TYPE_MAIN_CONTENT)
			$maincontentblocks[] = $block['ID'];
		
		foreach($subblocks as $subblock) {
			if ($subblock['TypeID'] != BLOCK_TYPE_MAIN_CONTENT)
				continue;
			
			$maincontentblocks[] = $subblock['ID'];
		}
		
		if ($maincontentblocks) {
			$othermainblock = sql::fetch(sql::run(
				" SELECT * FROM `{blocks}`" .
				" WHERE 1" .
				(JCORE_VERSION >= '0.7'?
					" AND `TemplateID` = '".
						(template::$selected?
							(int)template::$selected['ID']:
							0)."'":
					null) .
				" AND `ID` NOT IN ('" .
					implode("','", $maincontentblocks) .
					"')" .
				" AND `TypeID` = '".BLOCK_TYPE_MAIN_CONTENT."'" .
				" LIMIT 1"));
			
			if (!$othermainblock) {
				tooltip::display(
					__("Block cannot be deleted as it is or contains a " .
						"\"Main Content\" block! Please first create " .
						"another \"Main Content\" block that wont be " .
						"affected by this operation."),
					TOOLTIP_ERROR);
				return false;
			}
		}
		
		foreach($subblocks as $subblock)
			sql::run(
				" DELETE FROM `{blocks}` " .
				" WHERE `ID` = '".$subblock['ID']."'");
		
		sql::run(
			" DELETE FROM `{blocks}` " .
			" WHERE `ID` = '".(int)$id."'");
			
		return true;
	}
	
	static function getTree($blockid = 0, $firstcall = true,
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0)) 
	{
		$rows = sql::run(
			" SELECT * FROM `{blocks}` " .
			" WHERE 1" .
			(JCORE_VERSION >= '0.7'?
				" AND `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0)."'":
				null) .
			($blockid?
				" AND `SubBlockOfID` = '".$blockid."'":
				" AND !`SubBlockOfID`") .
			" ORDER BY `OrderID`");
		
		while($row = sql::fetch($rows)) {
			$row['PathDeepnes'] = $tree['PathDeepnes'];
			$tree['Tree'][] = $row;
			
			$tree['PathDeepnes']++;
			blocks::getTree($row['ID'], false, $tree);
			$tree['PathDeepnes']--;
		}
		
		if ($firstcall)
			return $tree['Tree'];
	}
	
	static function getBackTraceTree($id, $firstcall = true,
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0)) 
	{
		if (!(int)$id)
			return array();
		
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{blocks}` " .
			" WHERE `ID` = '".(int)$id."'"));
			
		if (!$row)
			return array();
		
		if ($row['SubBlockOfID'])	
			blocks::getBackTraceTree($row['SubBlockOfID'], false, $tree);
		
		$row['PathDeepnes'] = $tree['PathDeepnes'];
		$tree['Tree'][] = $row;
		$tree['PathDeepnes']++;
		
		if ($firstcall)
			return $tree['Tree'];
	}
	
	// ************************************************   Client Part
	function type2Text($type) {
		if (!$type)
			return;
		
		switch($type) {
			case BLOCK_TYPE_MAIN_CONTENT:
				return __('Main Content');
			case BLOCK_TYPE_CONTENT:
				return __('Content');
			case BLOCK_TYPE_MENU:
				return __('Menu');
			case BLOCK_TYPE_AD:
				return __('Advertisement');
			default:
				return __('Undefined!');
		}
	}
	
	function access2Text($typeid) {
		switch($typeid) {
			case BLOCK_ADMINS_ONLY:
				return __('Admins');
			case BLOCK_GUESTS_ONLY:
				return __('Guests');
			case BLOCK_USERS_ONLY:
				return __('Members');
			default:
				return __('Everyone');
		}
	}
	
	function displayContent($block) {
		if ($block['Content']) {
			$codes = new contentCodes();
			$codes->contentLimit = $block['Limit'];
			$codes->display($block['Content']);
			unset($codes);
		}
		
		switch($block['TypeID']) {
			case BLOCK_TYPE_MAIN_CONTENT:
				tooltip::display();
				requests::displayResult();
				settings::displayMaintenanceNotification();
				
				if ($GLOBALS['USER']->display())
					echo
						"<p class='spacer'></p>";
		
				if (isset($GLOBALS['ADMIN']) && $GLOBALS['ADMIN']) {
					$admin = new admin();
					$admin->display();
					unset($admin);
					break;
				}
				
				$posts = new posts();
				$posts->display();
				unset($posts);
				break;
				
			case BLOCK_TYPE_CONTENT:
				$posts = new posts();
				$posts->limit = $block['Limit'];
				$posts->selectedBlockID = $block['ID'];
				$posts->display();
				unset($posts);
				break;
		
			case BLOCK_TYPE_MENU:
				$menus = new menus();
				$menus->limit = $block['Limit'];
				$menus->selectedBlockID = $block['ID'];
				$menus->display();
				unset($menus);
				break;
			
			case BLOCK_TYPE_AD:
				$ads = new ads();
				$ads->limit = $block['Limit'];
				$ads->selectedBlockID = $block['ID'];
				$ads->display();
				unset($ads);
				break;
		}
		
		if (!$this->cachingInProgress)
			url::flushDisplay();
	}
	
	function displayOne($block) {
		if ($block['LanguageIDs']) {
			if (!$this->selectedLanguageID && !$block['LanguageExcept'])
				return;
			
			$languageids = explode('|', $block['LanguageIDs']);
			
			if ((!$block['LanguageExcept'] && 
					!in_array($this->selectedLanguageID, $languageids)) ||
				($block[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')] && 
					in_array($this->selectedLanguageID, $languageids)))
			{ 
					return;
			}
		}
		
		if ($block[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')]) {
			$limitedtoadmin = false;
			
			if (isset($GLOBALS['ADMIN']) && $GLOBALS['ADMIN'] && 
				preg_match('/A(\||$)/', $block[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')])) 
			{
				$limitedtoadmin = true;
				
				if ($block[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')])
					return;
			}
			
			if (!$limitedtoadmin && !(int)$this->selectedPageID && 
				!$block[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')])
				return;
			
			$pageparents = pages::getBackTraceTree(
				(int)$this->selectedPageID, true, 'ID');
			
			$pageids = explode('|', $block[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')]);
			
			foreach($pageparents as $pageparent) {
				if ((!$block[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')] && 
						!in_array($pageparent['ID'], $pageids)) ||
					($block[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')] && 
						in_array($pageparent['ID'], $pageids)))
				{ 
					return;
				}
			}
		}
		
		$cssclass = null;
		
		if (isset($block['_BrowserSelector']))
			$cssclass[] = $block['_BrowserSelector'];
		
		if (isset($block['_CurrentPath']))
			$cssclass[] = str_replace('/', ' ', $block['_CurrentPath']);
		
		if ($block['Class'])
			$cssclass[] = $block['Class'];
		
		if (JCORE_VERSION < '0.6' && $block['SubBlockOfID'])
			$cssclass[] = 'subblock';
			
		echo
			"<div" .
				($block['BlockID']?
					" id='".$block['BlockID']."'":
					null) .
				($cssclass?
					" class='".implode(' ', $cssclass)."'":
					null) .
				">";
		
		if (count($this->ignoreCache4BlockIDs) && 
			in_array($block['ID'], $this->ignoreCache4BlockIDs))
			$block['Caching'] = false;
				
		if (JCORE_VERSION >= '0.4' && $block['Caching'] && 
			(!$block['CacheOnlyForGuests'] || !$GLOBALS['USER']->loginok)) 
		{
			if ($block['CacheExpired']) { 
				ob_start();
				$this->cachingInProgress = true;
				
			} else {
				echo 
					$block['CacheContent'] .
					"</div>";
				return;
			}
		}
		
		$this->displayContent($block);
		
		$rows = sql::run(
			(JCORE_VERSION >= '0.4'?
				" SELECT *," .
				" IF(DATE_SUB(NOW(), INTERVAL `CacheRefreshTime` MINUTE) > `CacheTimeStamp`, 1, 0) AS `CacheExpired`" .
				" FROM `{blocks}`": 
				" SELECT * FROM `{blocks}`") .
			" WHERE 1" .
			(JCORE_VERSION >= '0.7'?
				" AND `TemplateID` = '".
					(template::$selected && (WEBSITE_TEMPLATE_SETFORADMIN ||
					 !isset($GLOBALS['ADMIN']) || !$GLOBALS['ADMIN'])?
						(int)template::$selected['ID']:
						0) .
					"'":
				null) .
			" AND !`Deactivated`" .
			" AND `SubBlockOfID` = '".(int)$block['ID']."'" .
			" AND (!`ViewableBy` OR " .
				($GLOBALS['USER']->loginok?
					($GLOBALS['USER']->data['Admin']?
						" `ViewableBy` IN (2, 3)":
						" `ViewableBy` = 2"):
					" `ViewableBy` = 1") .
			" )" .
			" ORDER BY `OrderID`");
		
		while ($row = sql::fetch($rows))
			$this->displayOne($row);
		
		if (JCORE_VERSION >= '0.4' && $block['Caching'] && 
			(!$block['CacheOnlyForGuests'] || !$GLOBALS['USER']->loginok) && 
			$block['CacheExpired']) 
		{
			$blockcontent = ob_get_contents();
			
			sql::run(
				" UPDATE `{blocks}` SET" .
				" `CacheContent` = '".sql::escape($blockcontent)."'," .
				" `CacheTimeStamp` = NOW()" .
				" WHERE `ID` = '".$block['ID']."'");
			
			ob_end_flush();
			$this->cachingInProgress = false;
		}
		
		echo
			"</div>";
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		$row = sql::fetch(sql::run(
			(JCORE_VERSION >= '0.4'?
				" SELECT *," .
				" IF(DATE_SUB(NOW(), INTERVAL `CacheRefreshTime` MINUTE) > `CacheTimeStamp`, 1, 0) AS `CacheExpired`" .
				" FROM `{blocks}`": 
				" SELECT * FROM `{blocks}`") .
			" WHERE 1" .
			(JCORE_VERSION >= '0.7'?
				" AND `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0)."'":
				null) .
			" AND !`Deactivated`" .
			" AND `BlockID` LIKE '".sql::escape($this->arguments)."'" .
			" AND (!`ViewableBy` OR " .
				($GLOBALS['USER']->loginok?
					($GLOBALS['USER']->data['Admin']?
						" `ViewableBy` IN (2, 3)":
						" `ViewableBy` = 2"):
					" `ViewableBy` = 1") .
			" )" .
			" ORDER BY `OrderID`" .
			" LIMIT 1"));
			
		if ($row)
			$this->displayOne($row);
		
		return true;
	}
	
	function display() {
		if ($this->displayArguments())
			return;
		
		// In admin caching is turned off for Main Content
		if (JCORE_VERSION >= '0.4' && isset($GLOBALS['ADMIN']) && 
			$GLOBALS['ADMIN']) 
		{
			$mblock = sql::fetch(sql::run(
				" SELECT `ID` FROM `{blocks}`" .
				" WHERE `TypeID` = '".BLOCK_TYPE_MAIN_CONTENT."'" .
				(JCORE_VERSION >= '0.7'?
					" AND `TemplateID` = '".
						(template::$selected?
							(int)template::$selected['ID']:
							0)."'":
					null)));
			
			foreach(blocks::getBackTraceTree($mblock['ID']) as $block)
				$this->ignoreCache4BlockIDs[] = $block['ID'];
		}
		
		$rows = sql::run(
			$this->SQL());
		
		if (isset($GLOBALS['ADMIN']) && $GLOBALS['ADMIN']) {
			$path = 'admin';
			
		} else {
			$path =
				(languages::$selected?
					 languages::$selected['Path']:
					 null) .
				(languages::$selected && pages::$selected?
					'/':
					null) .
				(pages::$selected?
					pages::$selected['Path']:
					null);
			
			if (url::path())
				$path .= ($path?'/':null).
					trim(preg_replace('/[^a-zA-Z0-9\@\.\_\-\/]/', '',
						strip_tags(url::path())));
		}
		
		$browserselector = css::browserSelector();
		
		while ($row = sql::fetch($rows)) {
			$row['_CurrentPath'] = $path;
			$row['_BrowserSelector'] = $browserselector;
			
			$this->displayOne($row);
		}
	}
}

?>