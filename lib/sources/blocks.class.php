<?php

/***************************************************************************
 *            blocks.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/menus.class.php');
include_once('lib/posts.class.php'); 
include_once('lib/contentcodes.class.php');
include_once('lib/ads.class.php');
include_once('lib/layouts.class.php');

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
	var $arguments = null;
	var $selectedLanguageID;
	var $selectedPageID;
	var $ignoreCache4BlockIDs = array();
	var $adminPath = 'admin/site/blocks';
	
	function __construct() {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::blocks', $this);
		
		if (isset($_GET['languageid']))
			$this->selectedLanguageID = (int)$_GET['languageid'];
		
		if (isset($_GET['pageid']))
			$this->selectedPageID = (int)$_GET['pageid'];
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::blocks', $this);
	}
	
	function SQL() {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::SQL', $this);
		
		$sql = 
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
			(JCORE_VERSION >= '0.9'?
				(pages::$selected && pages::$selected['LayoutID'] && 
				 layouts::exists(pages::$selected['LayoutID'])?
					" AND `LayoutID` = '".pages::$selected['LayoutID']."'":
					" AND `LayoutID` = 0"):
				null) .
			" AND `Deactivated` = 0" .
			" AND `SubBlockOfID` = 0" .
			" AND (`ViewableBy` = 0 OR " .
				($GLOBALS['USER']->loginok?
					($GLOBALS['USER']->data['Admin']?
						" `ViewableBy` IN (2, 3)":
						" `ViewableBy` = 2") .
					(JCORE_VERSION >= '0.9' && $GLOBALS['USER']->data['GroupID']?
						" OR `ViewableBy` = '".(int)($GLOBALS['USER']->data['GroupID']+10)."'":
						null):
					" `ViewableBy` = 1") .
			" )" .
			" ORDER BY `OrderID`";
				
		api::callHooks(API_HOOK_AFTER,
			'blocks::SQL', $this, $sql);
		
		return $sql;
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::countAdminItems', $this);
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::countAdminItems', $this, $row['Rows']);
		
		return $row['Rows'];
	}
	
	function setupAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::setupAdmin', $this);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Block'), 
				'?path='.admin::path().'#adminform');
		
		if (JCORE_VERSION >= '0.9')
			favoriteLinks::add(
				__('Layouts'), 
				'?path=admin/site/blocks/layouts');
		
		favoriteLinks::add(
			__('CSS Editor'), 
			'?path=admin/site/template/templatecsseditor');
		favoriteLinks::add(
			__('View Website'), 
			SITE_URL);
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::setupAdmin', $this);
	}
	
	function setupAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::setupAdminForm', $this, $form);
		
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
		$form->addValue(
			BLOCK_TYPE_CONTENT, $this->type2Text(BLOCK_TYPE_CONTENT));
		
		$form->add(
			__('Sub Block of'),
			'SubBlockOfID',
			FORM_INPUT_TYPE_SELECT);
		$form->setValueType(FORM_VALUE_TYPE_INT);
			
		$form->addValue('', '');
		
		if (JCORE_VERSION >= '0.9') {
			$layouts = layouts::get();
			
			if (sql::rows($layouts)) {
				$form->add(
					__('Layout'),
					'LayoutID',
					FORM_INPUT_TYPE_SELECT);
				$form->setValueType(FORM_VALUE_TYPE_INT);
				$form->addValue('0', __('Default'));
				
				while($layout = sql::fetch($layouts))
					$form->addValue($layout['ID'], $layout['Title']);
			}
		}
		
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
		$form->addValue('M', '* ' .
			__('Mobile Browsers'));
		
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
		
		if (JCORE_VERSION >= '0.9') {
			$ugroups = userGroups::get();
			
			while($ugroup = sql::fetch($ugroups))
				$form->addValue(
					$ugroup['ID']+10, $ugroup['GroupName']);
		}
		
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
				"<span class='comment'>" .
				__("(cache the content of this block including all sub blocks)") .
				"</span>");
			
			$form->add(
				__('Only for Guests'),
				'CacheOnlyForGuests',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				1);
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
			$form->addAdditionalText(
				"<span class='comment'>" .
				__("(don't use caching for logged in users)") .
				"</span>");
			
			$form->add(
				__('Refresh Time'),
				'CacheRefreshTime',
				FORM_INPUT_TYPE_TEXT,
				false,
				10);
			$form->setValueType(FORM_VALUE_TYPE_INT);
			$form->setStyle('width: 50px;');
			
			$form->addAdditionalText(
				"<span class='comment'>" .
				__("(cache refresh time interval in minutes)") .
				"</span>");
			
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
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::setupAdminForm', $this, $form);
	}
	
	function verifyAdmin(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::verifyAdmin', $this, $form);
		
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
					'blocks::verifyAdmin', $this, $form);
				return false;
			}
			
			foreach((array)$orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{blocks}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'" .
					($this->userPermissionIDs?
						" AND `ID` IN (".$this->userPermissionIDs.")":
						null));
			}
			
			tooltip::display(
				__("Blocks have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'blocks::verifyAdmin', $this, $form, $reorder);
			
			return true;
		}
		
		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'blocks::verifyAdmin', $this, $form);
				return false;
			}
			
			$result = $this->delete($id);
			
			if ($result)
				tooltip::display(
					__("Block has been successfully deleted."),
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'blocks::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'blocks::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if ($edit && $form->get('SubBlockOfID')) {
			foreach(blocks::getBackTraceTree($form->get('SubBlockOfID')) as $block) {
				if ($block['ID'] == $id) {
					tooltip::display(
						__("Block cannot be subblock of itself!"),
						TOOLTIP_ERROR);
					
					api::callHooks(API_HOOK_AFTER,
						'blocks::verifyAdmin', $this, $form);
					
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
			$result = $this->edit($id, $form->getPostArray());
			
			if ($result)
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
			
			api::callHooks(API_HOOK_AFTER,
				'blocks::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if ($this->userPermissionIDs) {
			api::callHooks(API_HOOK_AFTER,
				'blocks::verifyAdmin', $this, $form);
			
			return false;
		}
		
		$newid = $this->add($form->getPostArray());
		
		if ($newid) {
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
		}
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::verifyAdmin', $this, $form, $newid);
		
		return $newid;
	}
	
	function displayAdminListHeader() {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdminListHeader', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Block ID / CSS Class")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Type")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayAdminListHeader', $this);
	}
	
	function displayAdminListHeaderOptions() {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdminListHeaderOptions', $this);
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdminListHeaderFunctions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdminListItem', $this, $row);
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListItemOptions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdminListItemOptions', $this, $row);
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdminListItemFunctions', $this, $row);
		
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
			'blocks::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminListItemSelected(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdminListItemSelected', $this, $row);
		
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
				
				if ($blockpage == 'M') {
					$pageroute .= 
						"<div>* " .
							__("Mobile Browsers") .
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
		
		if ($row['ViewableBy'])
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
		
		if ($row['Content']) {
			admin::displayItemData(
				"<hr />");
			admin::displayItemData(
				"<code style='max-height: none;'>" .
					nl2br(htmlspecialchars($row['Content'])) .
				"</code>");
		}
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayAdminListItemSelected', $this, $row);
	}
	
	function displayAdminListFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdminListFunctions', $this);
		
		echo 
			"<input type='submit' name='reordersubmit' value='" .
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayAdminListFunctions', $this);
	}
	
	function displayAdminListLayouts($layout) {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdminListLayouts', $this, $layout);
		
		ob_start();
		$this->displayAdminListItems(0, false, $layout);
		$items = ob_get_contents();
		ob_end_clean();
		
		if ($items)
			echo 
			"<div tabindex='0' class='fc" . 
				form::fcState('fcbl'.$layout['ID'], true) . 
				"'>" .
				"<a class='fc-title' name='fcbl".$layout['ID']."'>" .
					stripcslashes($layout['Title']) .
				"</a>" .
				"<div class='fc-content'>" .
					$items .
				"</div>" .
			"</div>";
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayAdminListLayouts', $this, $layout);
		
		return $items;
	}
	
	function displayAdminListItems($blockid, $rowpair = null, $layout = null) {
		if ($this->userPermissionIDs && $blockid)
			return false;
		
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		$rows = sql::run(
			" SELECT * FROM `{blocks}`" .
			" WHERE 1" .
			(JCORE_VERSION >= '0.7'?
				" AND `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0)."'":
				null) .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				((int)$blockid?
					" AND `SubBlockOfID` = '".(int)$blockid."'":
					" AND `SubBlockOfID` = 0")) .
			($layout?
				" AND `LayoutID` = '".$layout['ID']."'":
				null) .
			" ORDER BY `OrderID`");
		
		if (!sql::rows($rows))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdminListItems', $this, $blockid, $rowpair, $layout);
		
		if ($blockid) {
			echo 
				"<tr".($rowpair?" class='pair'":NULL).">" .
					"<td></td>" .
					"<td colspan='4' class='auto-width nopadding'>";
		}
				
		echo "<table class='list' cellpadding='0' cellspacing='0'>";
		
		if (!$blockid) {
			echo
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
		}
		
		$i = 0;		
		while($row = sql::fetch($rows)) {
			echo 
				"<tr".($i%2?" class='pair'":null).">";
				
			$this->displayAdminListItem($row);
			$this->displayAdminListItemOptions($row);
					
			if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
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
			
			$this->displayAdminListItems($row['ID'], $i%2);
			
			$i++;
		}
		
		if ($blockid) {
			echo 
				"</table>" .
				"</td>" .
				"</tr>";
		} else {
			echo 
				"</tbody>" .
				"</table>";
		}
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayAdminListItems', $this, $blockid, $rowpair, $layout);
		
		return true;
	}
	
	function displayAdminList(&$rows) {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdminList', $this, $rows);
		
		echo
			"<form action='".url::uri('edit, delete')."' method='post'>" .
				"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";
				
		$itemsfound = false;
		
		if ($rows && sql::rows($rows)) {
			$layout['ID'] = 0;
			$layout['Title'] = __('Default');
			$itemsfound = $this->displayAdminListLayouts($layout);
			
		} else {
			$itemsfound = $this->displayAdminListItems(0);
		}
		
		if ($rows) {
			while($row = sql::fetch($rows)) {
				if ($this->displayAdminListLayouts($row))
					$itemsfound = true;
			}
		}
		
		if (!$itemsfound)
			tooltip::display(
				__("No blocks found."),
				TOOLTIP_NOTIFICATION);
		else
			echo "<br />";
		
		if ($itemsfound && $this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			$this->displayAdminListFunctions();
			
			echo
				"<div class='clear-both'></div>" .
				"<br />";
		}
			
		echo
			"</form>";
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayAdminList', $this, $rows);
	}
	
	function displayAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdminForm', $this, $form);
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdminTitle', $this, $ownertitle);
		
		admin::displayTitle(__('Blocks Administration'));
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdminDescription', $this);
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayAdmin', $this);
		
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
				" SELECT `Title` FROM `{blocks}`" .
				" WHERE `ID` = '".$id."'" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null)));
			
			url::displayConfirmation(
				'<b>'.__('Delete').'?!</b> "'.$selected['Title'].'"');
		}
		
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			((!$edit && !$delete) || !$this->userPermissionIDs ||
			in_array($id, explode(',', $this->userPermissionIDs))))
			$verifyok = $this->verifyAdmin($form);
		
		$layouts = null;
		
		if (JCORE_VERSION >= '0.9')
			$layouts = layouts::get();
			
		$this->displayAdminList($layouts);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || 
			($edit && in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{blocks}`" .
					" WHERE `ID` = '".$id."'"));
				
				$form->setValues($selected);
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
			
			$form->groupValues('SubBlockOfID', array('0'));
			
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
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayAdmin', $this);
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
			
		api::callHooks(API_HOOK_BEFORE,
			'blocks::add', $this, $values);
		
		if (!isset($values['LanguageIDs']))
			$values['LanguageIDs'] = null;
		
		if (!isset($values['LanguageExcept']))
			$values['LanguageExcept'] = null;
		
		if ($values['OrderID'] == '') {
			$row = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{blocks}` " .
				" WHERE `SubBlockOfID` = '".(int)$values['SubBlockOfID']."'" .
				(JCORE_VERSION >= '0.7'?
					" AND `TemplateID` = '".
						(template::$selected?
							(int)template::$selected['ID']:
							0)."'":
					null) .
				" ORDER BY `OrderID` DESC"));
			
			$values['OrderID'] = (int)$row['OrderID']+1;
			
		} else {
			sql::run(
				" UPDATE `{blocks}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `CacheTimeStamp` = `CacheTimeStamp`" .
				" WHERE `SubBlockOfID` = '".(int)$values['SubBlockOfID']."'" .
				(JCORE_VERSION >= '0.7'?
					" AND `TemplateID` = '".
						(template::$selected?
							(int)template::$selected['ID']:
							0)."'":
					null) .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		if ((int)$values['SubBlockOfID']) {
			$parentblock = sql::fetch(sql::run(
				" SELECT * FROM `{blocks}`" .
				" WHERE `ID` = '" .
					(int)$values['SubBlockOfID']."'"));
			
			if ($parentblock['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;
			
			if ($parentblock['ViewableBy'] && !$values['ViewableBy'])
				$values['ViewableBy'] = (int)$parentblock['ViewableBy'];
			
			if (JCORE_VERSION >= '0.9')
				$values['LayoutID'] = $parentblock['LayoutID'];
		}
		
		$newid = sql::run(
			" INSERT INTO `{blocks}` SET ".
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
				sql::escape(strip_tags(implode('|', (array)$values[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')])))."'," .
			" `".(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')."` = '".
				(int)$values[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')]."'," .
			" `LanguageIDs` = '".
				sql::escape(strip_tags(implode('|', (array)$values['LanguageIDs'])))."'," .
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
			(JCORE_VERSION >= '0.7'?
				" `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0)."',":
				null) .
			(JCORE_VERSION >= '0.9' && isset($values['LayoutID'])?
				" `LayoutID` = '".(int)$values['LayoutID']."',":
				null) .
			" `Limit` = '".
				(int)$values['Limit']."'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
		
		if (!$newid)
			tooltip::display(
				sprintf(__("Block couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::add', $this, $values, $newid);
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'blocks::edit', $this, $id, $values);
		
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
				" WHERE `TypeID` = '".BLOCK_TYPE_MAIN_CONTENT."'" .
				" AND `ID` != '".(int)$id."'" .
				(JCORE_VERSION >= '0.7'?
					" AND `TemplateID` = '".
						(template::$selected?
							(int)template::$selected['ID']:
							0)."'":
					null) .
				" LIMIT 1"));
			
			if (!$othermainblock) {
				tooltip::display(
					__("Block type cannot be changed as it is a " .
						"\"Main Content\" block! Please first create " .
						"another \"Main Content\" block and then try to " .
						"edit this block again."),
					TOOLTIP_ERROR);
				
				api::callHooks(API_HOOK_AFTER,
					'blocks::edit', $this, $id, $values);
				
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
			
			if (JCORE_VERSION >= '0.9')
				$values['LayoutID'] = $parentblock['LayoutID'];
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
				sql::escape(strip_tags(implode('|', (array)$values[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')])))."'," .
			" `".(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')."` = '".
				(int)$values[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')]."'," .
			" `LanguageIDs` = '".
				sql::escape(strip_tags(implode('|', (array)$values['LanguageIDs'])))."'," .
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
			(JCORE_VERSION >= '0.9' && isset($values['LayoutID'])?
				" `LayoutID` = '".(int)$values['LayoutID']."',":
				null) .
			" `Limit` = '".
				(int)$values['Limit']."'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");
		
		$result = (sql::affected() != -1);
		if (!$result) {
			tooltip::display(
				sprintf(__("Block couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			
		} else {
			foreach(blocks::getTree((int)$id) as $row) {
				if (!$row['ID'])
					continue;
				
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
				
				if (JCORE_VERSION >= '0.9' && isset($values['LayoutID']) &&
					$block['LayoutID'] != $values['LayoutID'] &&
					$row['LayoutID'] != $values['LayoutID'])
					$updatesql[] = " `LayoutID` = ".(int)$values['LayoutID'];
				
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
		}

		api::callHooks(API_HOOK_AFTER,
			'blocks::edit', $this, $id, $values, $result);
		
		return $result;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'blocks::delete', $this, $id);
		
		$subblocks = blocks::getTree($id);
		$maincontentblocks = null;
		
		$block = sql::fetch(sql::run(
			" SELECT * FROM `{blocks}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		if ($block['TypeID'] == BLOCK_TYPE_MAIN_CONTENT)
			$maincontentblocks[] = $block['ID'];
		
		foreach($subblocks as $subblock) {
			if (!$subblock['ID'])
				continue;
			
			if ($subblock['TypeID'] != BLOCK_TYPE_MAIN_CONTENT)
				continue;
			
			$maincontentblocks[] = $subblock['ID'];
		}
		
		if ($maincontentblocks) {
			$othermainblock = sql::fetch(sql::run(
				" SELECT * FROM `{blocks}`" .
				" WHERE `TypeID` = '".BLOCK_TYPE_MAIN_CONTENT."'" .
				" AND `ID` NOT IN ('" .
					implode("','", $maincontentblocks) .
					"')" .
				(JCORE_VERSION >= '0.7'?
					" AND `TemplateID` = '".
						(template::$selected?
							(int)template::$selected['ID']:
							0)."'":
					null) .
				" LIMIT 1"));
			
			if (!$othermainblock) {
				tooltip::display(
					__("Block cannot be deleted as it is or contains a " .
						"\"Main Content\" block! Please first create " .
						"another \"Main Content\" block that wont be " .
						"affected by this operation."),
					TOOLTIP_ERROR);
				
				api::callHooks(API_HOOK_AFTER,
					'blocks::delete', $this, $id);
				
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
			
		api::callHooks(API_HOOK_AFTER,
			'blocks::delete', $this, $id);
		
		return true;
	}
	
	static function getTree($blockid = 0, $firstcall = true,
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0)) 
	{
		$rows = sql::run(
			" SELECT * FROM `{blocks}` " .
			($blockid?
				" WHERE `SubBlockOfID` = '".$blockid."'":
				" WHERE `SubBlockOfID` = 0") .
			(JCORE_VERSION >= '0.7'?
				" AND `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0)."'":
				null) .
			" ORDER BY" .
			(JCORE_VERSION >= '0.9'?
				" `LayoutID`,":
				null) .
			" `OrderID`");
		
		$arelayouts = false;
		
		while($row = sql::fetch($rows)) {
			$last = end($tree['Tree']);
			
			if (JCORE_VERSION >= '0.9' && 
				(!$last || $last['LayoutID'] != $row['LayoutID'])) 
			{
				$layout = null;
				
				if ($row['LayoutID'])
					$layout = layouts::get($row['LayoutID']);
				
				if ($layout)
					$tree['Tree'][] = array(
						'ID' => 0,
						'Title' => $layout['Title'],
						'SubBlockOfID' => 0,
						'LayoutID' => $layout['ID'],
						'PathDeepnes' => 0);
				
				if (!$last['LayoutID'] && $row['LayoutID'])
					$arelayouts = true;
			}
			
			$row['PathDeepnes'] = $tree['PathDeepnes'];
			$tree['Tree'][] = $row;
			
			$tree['PathDeepnes']++;
			blocks::getTree($row['ID'], false, $tree);
			$tree['PathDeepnes']--;
		}
		
		if (JCORE_VERSION >= '0.9' && $arelayouts)
			array_unshift($tree['Tree'], array(
				'ID' => 0,
				'Title' => __('Default'),
				'SubBlockOfID' => 0,
				'LayoutID' => 0,
				'PathDeepnes' => 0));
		
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
		if ($typeid > 10) {
			$ugroup = userGroups::get($typeid-10);
			
			if (!$ugroup)
				return false;
			
			return $ugroup['GroupName'];
		}
		
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
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayContent', $this, $block);
		
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
		
				if (isset($GLOBALS['ADMIN']) && (bool)$GLOBALS['ADMIN']) {
					$admin = new admin();
					$admin->display();
					unset($admin);
					break;
				}
				
				$pages = new pages();
				$pages->display();
				unset($pages);
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
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayContent', $this, $block);
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
			$admin = false;
			$mobile = false;
			
			if (strpos($block[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')], 'A') !== false) {
				$admin = isset($GLOBALS['ADMIN']) && (bool)$GLOBALS['ADMIN'];
				
				if (($admin && $block[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')]) ||
					(!$admin && !$block[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')]))
				{
					return;
				}
			}
			
			if (strpos($block[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')], 'M') !== false) {
				$mobile = MOBILE_BROWSER;
				
				if (($mobile && $block[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')]) ||
					(!$mobile && !$block[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')]))
				{
					return;
				}
			}
			
			if (!$admin && !$mobile && !(int)$this->selectedPageID && 
				!$block[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')])
			{
				return;
			}
			
			$pageids = array_flip(explode('|', 
				$block[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')]));
			
			unset($pageids['A']);
			unset($pageids['M']);
			
			if (count($pageids) && (int)$this->selectedPageID) {
				$pageparents = pages::getBackTraceTree(
					(int)$this->selectedPageID, true, 'ID');
				
				foreach($pageparents as $pageparent) {
					if ((!$block[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')] && 
							!isset($pageids[$pageparent['ID']])) ||
						($block[(JCORE_VERSION >= '0.8'?'PageExcept':'MenuItemExcept')] && 
							isset($pageids[$pageparent['ID']])))
					{ 
						return;
					}
				}
			}
		}
		
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayOne', $this, $block);
		
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
				
				api::callHooks(API_HOOK_AFTER,
					'blocks::displayOne', $this, $block);
				
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
			" WHERE `SubBlockOfID` = '".(int)$block['ID']."'" .
			(JCORE_VERSION >= '0.7'?
				" AND `TemplateID` = '".
					(template::$selected && (WEBSITE_TEMPLATE_SETFORADMIN ||
					 !isset($GLOBALS['ADMIN']) || !(bool)$GLOBALS['ADMIN'])?
						(int)template::$selected['ID']:
						0) .
					"'":
				null) .
			" AND `Deactivated` = 0" .
			" AND (`ViewableBy` = 0 OR " .
				($GLOBALS['USER']->loginok?
					($GLOBALS['USER']->data['Admin']?
						" `ViewableBy` IN (2, 3)":
						" `ViewableBy` = 2") .
					(JCORE_VERSION >= '0.9' && $GLOBALS['USER']->data['GroupID']?
						" OR `ViewableBy` = '".(int)($GLOBALS['USER']->data['GroupID']+10)."'":
						null):
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
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayOne', $this, $block);
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'blocks::displayArguments', $this);
		
		$row = sql::fetch(sql::run(
			(JCORE_VERSION >= '0.4'?
				" SELECT *," .
				" IF(DATE_SUB(NOW(), INTERVAL `CacheRefreshTime` MINUTE) > `CacheTimeStamp`, 1, 0) AS `CacheExpired`" .
				" FROM `{blocks}`": 
				" SELECT * FROM `{blocks}`") .
			" WHERE `BlockID` LIKE '".sql::escape($this->arguments)."'" .
			(JCORE_VERSION >= '0.7'?
				" AND `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0)."'":
				null) .
			" AND `Deactivated` = 0" .
			" AND (`ViewableBy` = 0 OR " .
				($GLOBALS['USER']->loginok?
					($GLOBALS['USER']->data['Admin']?
						" `ViewableBy` IN (2, 3)":
						" `ViewableBy` = 2") .
					(JCORE_VERSION >= '0.9' && $GLOBALS['USER']->data['GroupID']?
						" OR `ViewableBy` = '".(int)($GLOBALS['USER']->data['GroupID']+10)."'":
						null):
					" `ViewableBy` = 1") .
			" )" .
			" ORDER BY `OrderID`" .
			" LIMIT 1"));
			
		if ($row)
			$this->displayOne($row);
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::displayArguments', $this);
		
		return true;
	}
	
	function display() {
		if ($this->displayArguments())
			return;
		
		api::callHooks(API_HOOK_BEFORE,
			'blocks::display', $this);
		
		// In admin caching is turned off for Main Content
		if (JCORE_VERSION >= '0.4' && isset($GLOBALS['ADMIN']) && 
			(bool)$GLOBALS['ADMIN']) 
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
		
		if (isset($GLOBALS['ADMIN']) && (bool)$GLOBALS['ADMIN']) {
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
		
		api::callHooks(API_HOOK_AFTER,
			'blocks::display', $this);
	}
}

?>