<?php

/***************************************************************************
 *            posts.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/paging.class.php');
include_once('lib/calendar.class.php');
include_once('lib/rss.class.php');
include_once('lib/contentcodes.class.php');
include_once('lib/postattachments.class.php');
include_once('lib/postcomments.class.php');
include_once('lib/postpictures.class.php');
include_once('lib/postrating.class.php');
include_once('lib/postscalendar.class.php');
include_once('lib/postsform.class.php');

class _posts {
	var $arguments = null;
	var $selectedID;
	var $selectedPageID;
	var $selectedLanguageID;
	var $selectedBlockID;
	var $selectedPage;
	var $selectedLanguage;
	var $limit = 0;
	var $keywordsCloudLimit = 21;
	var $randomize = false;
	var $latests = false;
	var $active = false;
	var $discussed = false;
	var $rated = false;
	var $ignorePaging = false;
	var $showPaging = true;
	var $format = null;
	var $search = null;
	var $searchKeywords = null;
	var $ajaxPaging = AJAX_PAGING;
	var $ajaxRequest = null;
	var $adminPath = array(
		'admin/content/menuitems/posts',
		'admin/content/pages/posts',
		'admin/content/postsatglance');
	
	// DEPRECATED! Since 0.9 there are no more alias pages allowed!
	var $aliasPageIDs = null;
	
	static $selected = null;
	
	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::posts', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::posts', $this, $handled);
			
			return $handled;
		}
		
		if (isset($_GET['postid']))
			$this->selectedID = (int)$_GET['postid'];
		
		if (isset($_GET['languageid']))
			$this->selectedLanguageID = (int)$_GET['languageid'];
		
		if (isset($_GET['pageid']))
			$this->selectedPageID = (int)$_GET['pageid'];
		
		if (isset($_GET['searchin']) && isset($_GET['search']) && 
			$_GET['searchin'] == 'posts')
			$this->search = trim(strip_tags((string)$_GET['search']));
			
		if (isset($_GET['arguments']) && isset($_GET['ajax']))
			$this->arguments = strip_tags(urldecode((string)$_GET['arguments']));
		
		api::callHooks(API_HOOK_AFTER,
			'posts::posts', $this);
	}
	
	function SQL() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::SQL', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::SQL', $this, $handled);
			
			return $handled;
		}
		
		$homepage = pages::getHome($this->selectedLanguageID);
		$page = pages::get($this->selectedPageID);
		
		$searchignorepageids = null;
		if ($this->search || $this->searchKeywords || !$page) {
			$pageids = sql::fetch(sql::run(
				" SELECT GROUP_CONCAT(`ID` SEPARATOR ',') AS PageIDs" .
				" FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `Deactivated` = 1" .
				(!$GLOBALS['USER']->loginok?
					" OR `".(JCORE_VERSION >= '0.9'?'AccessibleBy':'ViewableBy')."` > 1":
					null)));
			
			if (isset($pageids['PageIDs']) && $pageids['PageIDs'])
				$searchignorepageids = $pageids['PageIDs'];
		}
		
		$sql =
			" SELECT * " .
			" FROM `{posts}`" .
			" WHERE 1" .
			($this->selectedID?
				" AND `ID` = '".$this->selectedID."'":
				null) .
			(!$this->search && !$this->searchKeywords && !$this->selectedID?
				($page?
					" AND (`".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
						$page['ID']."'" .
					(JCORE_VERSION < '0.9' && is_array($this->aliasPageIDs) && count($this->aliasPageIDs)?
						" OR `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` IN (" .
							implode(',', $this->aliasPageIDs) .
							")":
						null) .
					(JCORE_VERSION >= '0.8' && trim($page['PostKeywords'])?
						" OR (1" .
							sql::search($page['PostKeywords'].',', array('Keywords'), 'OR') .
						")":
						null) .
					($homepage['ID'] == $page['ID']?
						" OR (`OnMainPage` = 1" .
						(JCORE_VERSION >= '0.9'?
							" AND `LanguageID` = '".$page['LanguageID']."'":
							null) .
						") OR (`".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = 0" .
						(JCORE_VERSION >= '0.9'?
							" AND (`LanguageID` = '".$page['LanguageID']."' OR `LanguageID` = 0)":
							null) .
						")":
						" OR ((`".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '".$homepage['ID']."'" .
							" OR (`".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = 0" .
							(JCORE_VERSION >= '0.9'?
								" AND (`LanguageID` = '".$page['LanguageID']."' OR `LanguageID` = 0)":
								null) .
							")) AND `OnMainPage` = 1) ") .
					")":
					null):
				null) .
			($searchignorepageids?
				" AND `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` NOT IN (" .
					$searchignorepageids.")":
				null) .
			(JCORE_VERSION >= '0.9'?
				" AND (`EndDate` IS NULL OR `EndDate` >= CURDATE() OR (`EndDate` < CURDATE() && `HideExpired` = 0))":
				null) .
			(!$this->selectedID?
				" AND `BlockID` = 0":
				null) .
			" AND `Deactivated` = 0" .
			(($this->search || $this->searchKeywords) && !$this->selectedID?
				(JCORE_VERSION >= '0.9'?
					" AND `NotSearchable` = 0":
					null) .
				sql::search(
					($this->searchKeywords?
						$this->searchKeywords.',':
						$this->search),
					($this->searchKeywords?
						array('Keywords'):
						(JCORE_VERSION >= '0.7'? 
							dynamicForms::searchableFields('posts'):
							array('Title', 'Content', 'Keywords'))),
					($this->searchKeywords?
						'OR':
						'AND'), 
					array(
						'date' => 'TimeStamp',
						'tag' => 'Keywords')):
				null) .
			($this->active?
				" AND `Views` > 0":
				null) .
			($this->discussed?
				" AND `Comments` > 0":
				null) .
			($this->rated?
				" AND `Rating` > 0":
				null) .
			" ORDER BY" .
			($this->randomize?
				" RAND()":
				($this->active || ($this->search && !$this->selectedID)?
					" `Views` DESC,":
					null) .
				($this->latests || (JCORE_VERSION >= '0.8' && $page && trim($page['PostKeywords']))?
					" `TimeStamp` DESC,":
					null) .
				($this->discussed?
					" `Comments` DESC,":
					null) .
				($this->rated?
					" `Rating` DESC,":
					null) .
				($homepage['ID'] == $page['ID']?
					" `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = 0,":
					" `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '".$page['ID']."',") .
				" `OnMainPage` DESC, `OrderID`, `StartDate`, `ID` DESC");
		
		api::callHooks(API_HOOK_AFTER,
			'posts::SQL', $this, $sql);
		
		return $sql;
	}
	
	static function populate() {
		if (!isset($_GET['postid']))
			$_GET['postid'] = 0;
		
		if (isset($GLOBALS['ADMIN']) && (bool)$GLOBALS['ADMIN'])
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::populate', $_ENV);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::populate', $_ENV, $handled);
			
			return $handled;
		}
		
		$selected = sql::fetch(sql::run(
			" SELECT `ID`, `Title`, `Path`, `Keywords`" .
			" FROM `{posts}`" .
			" WHERE `Deactivated` = 0" .
			" AND (`".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = 0" .
				" OR `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
					(int)$_GET['pageid']."')" .
			(SEO_FRIENDLY_LINKS && !(int)$_GET['postid']?
				" AND '".sql::escape(url::path())."/' LIKE CONCAT(`Path`,'/%')":
				" AND `ID` = '".(int)$_GET['postid']."'") .
			" ORDER BY `OrderID`, `StartDate`, `ID` DESC" .
			" LIMIT 1"));
		
		if (SEO_FRIENDLY_LINKS && $selected)
			url::setPath(preg_replace(
				'/'.preg_quote($selected['Path'], '/').'(\/|$)/i', '', 
				url::path(), 1));
		
		if ($selected) {
			posts::$selected = $selected;
			url::addPageTitle($selected['Title']);			
			url::addPageDescription($selected['Title'].'.');
			url::addPageKeywords($selected['Keywords']);
				
			$_GET['postid'] = $selected['ID'];
			
			api::callHooks(API_HOOK_AFTER,
				'posts::populate', $_ENV, $selected['ID']);
			
			return;
		}
		
		$_GET['postid'] = 0;
		
		api::callHooks(API_HOOK_AFTER,
			'posts::populate', $_ENV);
	}
	
	// ************************************************   Admin Part
	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::setupAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::setupAdmin', $this, $handled);
			
			return $handled;
		}
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Post'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Content Files'), 
			'?path=admin/content/contentfiles');
		favoriteLinks::add(
			__('View Website'), 
			SITE_URL);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::setupAdmin', $this);
	}
	
	function setupAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::setupAdmin', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::setupAdmin', $this, $form, $handled);
			
			return $handled;
		}
		
		$edit = null;
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (JCORE_VERSION >= '0.7') {
			$postsform = new postsForm();
			$postsform->id = 'neweditpost';
			$postsform->load(false);
			
			foreach($postsform->elements as $element)
				$form->elements[] = $element;
			
			if ($form->getElementID('BlockID')) {
				$form->addValue('BlockID', '','');
				
				$blockids = array();
				$layoutids = array();
				$disabledblocks = array();
				
				$postblocks = sql::run(
					" SELECT `ID`, `SubBlockOfID`" .
					(JCORE_VERSION >= '0.9'?
						", `LayoutID`":
						null) .
					" FROM `{blocks}`" .
					" WHERE `TypeID` = '".BLOCK_TYPE_CONTENT."'" .
					(JCORE_VERSION >= '0.7'?
						" AND `TemplateID` = '".
							(template::$selected?
								(int)template::$selected['ID']:
								0)."'":
						null));
				
				if (sql::rows($postblocks)) {
					while($postblock = sql::fetch($postblocks)) {
						if (isset($blockids[$postblock['SubBlockOfID']])) {
							$blockids[$postblock['ID']] = true;
							
							if (JCORE_VERSION >= '0.9')
								$layoutids[$postblock['LayoutID']] = true;
							
							continue;
						}
						
						foreach(blocks::getBackTraceTree($postblock['ID']) as $block) {
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
						
						$form->addValue(
							'BlockID',
							$block['ID'], 
							($block['SubBlockOfID']?
								str_replace(' ', '&nbsp;', 
									str_pad('', $block['PathDeepnes']*4, ' ')).
								"|- ":
								null) .
							$block['Title']);
						
						if ($block['ID'] && $block['TypeID'] != BLOCK_TYPE_CONTENT)
							$disabledblocks[] = $block['ID'];
					}
					
					$form->disableValues('BlockID', $disabledblocks);
					$form->groupValues('BlockID', array('0'));
					
				} else {
					$form->edit(
						'BlockID',
						null,
						null,
						FORM_INPUT_TYPE_HIDDEN);
				}
			}
			
			if ($edit) {
				$form->insert(
					'Deactivated',
					__('Insert as New'),
					'InsertAsNew',
					FORM_INPUT_TYPE_CHECKBOX,
					false,
					'1');
				$form->setValueType(
					'InsertAsNew',
					FORM_VALUE_TYPE_BOOL);
					
				$form->addAdditionalText(
					'InsertAsNew',
					"<span class='comment'>" .
					__("(will create a new post)").
					"</span>");
			}
			
			$form->insert(
				'OrderID',
				__('Owner'),
				'Owner',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('Owner', 'width: 110px;');
			
			$form->addAdditionalText(
				'Owner',
				"<a style='zoom: 1;' href='".url::uri('request, users') .
					"&amp;request=".url::path() .
					"&amp;users=1' " .
					"class='select-owner-link ajax-content-link'>" .
					_("Select User") .
				"</a>");
			
			$form->insert(
				'BlockID',
				(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID'),
				(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID'),
				FORM_INPUT_TYPE_HIDDEN,
				true,
				admin::getPathID(),
				FORM_INSERT_BEFORE);
			$form->setValueType(FORM_VALUE_TYPE_INT);
			
			if (JCORE_VERSION >= '0.9') {
				$form->insert(
					'PageID',
					'LanguageID',
					'LanguageID',
					FORM_INPUT_TYPE_HIDDEN);
				$form->setValueType(FORM_VALUE_TYPE_INT);
			}
			
			$form->addAdditionalText(
				'Keywords',
				"<br />" .
				"<a href='".url::uri('request, keywords') .
					"&amp;request=".url::path() .
					"&amp;keywords=1' " .
					"class='posts-add-keyword add-link ajax-content-link' " .
					"title='".htmlspecialchars(__("Add Tag"), ENT_QUOTES)."'>" .
					__("Add Tag") .
				"</a>");
			
			unset($postsform);
			
			api::callHooks(API_HOOK_AFTER,
				'posts::setupAdmin', $this, $form);
			
			return;
		}
		
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 350px;');
		
		$form->add(
			__('Content'),
			'Content',
			FORM_INPUT_TYPE_EDITOR);
		$form->setStyle('height: 400px;');
		$form->setValueType(FORM_VALUE_TYPE_HTML);
					
		$form->add(
			__('Blogging Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Created on'),
			'TimeStamp',
			FORM_INPUT_TYPE_TIMESTAMP);
		$form->setStyle('width: 170px;');
		$form->setValueType(FORM_VALUE_TYPE_TIMESTAMP);
		
		$form->add(
			__('Path'),
			'Path',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 300px;');
		
		if (JCORE_VERSION >= '0.6') {
			$form->add(
				__('Link to URL'),
				'URL',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 300px;');
			$form->setValueType(FORM_VALUE_TYPE_URL);
			$form->setTooltipText(__("e.g. http://domain.com"));
		}
		
		$form->add(
			__('Tags'),
			'Keywords',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 250px;');
		
		if (JCORE_VERSION >= '0.6') {
			$form->setTooltipText(__("e.g. oranges, lemons, limes"));
			
			$form->addAdditionalText(
				"<br />" .
				"<a href='".url::uri('request, keywords') .
					"&amp;request=".url::path() .
					"&amp;keywords=1' " .
					"class='posts-add-keyword add-link ajax-content-link' " .
					"title='".htmlspecialchars(__("Add Tag"), ENT_QUOTES)."'>" .
					__("Add Tag") .
				"</a>");
			
		} else {
			$form->addAdditionalText(" (".__("e.g. oranges, lemons, limes").")");
		}
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Announcement Options'),
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
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Display Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID'),
			(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID'),
			FORM_INPUT_TYPE_HIDDEN,
			true,
			admin::getPathID());
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		$form->add(
			__('In Block'),
			'BlockID',
			FORM_INPUT_TYPE_SELECT,
			false);
		$form->setValueType(FORM_VALUE_TYPE_INT);
		$form->addValue('','');
		
		$blockids = array();
		$disabledblocks = array();
		
		$postblocks = sql::run(
			" SELECT `ID`, `SubBlockOfID` FROM `{blocks}`" .
			" WHERE `TypeID` = '".BLOCK_TYPE_CONTENT."'" .
			(JCORE_VERSION >= '0.7'?
				" AND `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0)."'":
				null));
		
		if (sql::rows($postblocks)) {
			while($postblock = sql::fetch($postblocks)) {
				if (isset($blockids[$postblock['SubBlockOfID']])) {
					$blockids[$postblock['ID']] = true;
					continue;
				}
				
				foreach(blocks::getBackTraceTree($postblock['ID']) as $block) {
					if (isset($blockids[$block['ID']]))
						continue;
					
					$blockids[$block['ID']] = $block['Title'];
				}
			}
			
			foreach(blocks::getTree() as $block) {
				if (!isset($blockids[$block['ID']]))
					continue;
				
				$form->addValue($block['ID'], 
					($block['SubBlockOfID']?
						str_replace(' ', '&nbsp;', 
							str_pad('', $block['PathDeepnes']*4, ' ')).
						"|- ":
						null) .
					$block['Title']);
				
				if ($block['TypeID'] != BLOCK_TYPE_CONTENT)
					$disabledblocks[] = $block['ID'];
			}
			
			$form->disableValues($disabledblocks);
			
		} else {
			$form->edit(
				'BlockID',
				null,
				null,
				FORM_INPUT_TYPE_HIDDEN);
		}
		
		$form->add(
			__('Display on Main page'),
			'OnMainPage',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
		$form->add(
			__('Partial Content'),
			'PartialContent',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
		
		if (JCORE_VERSION >= '0.3') {
			$form->add(
				__('Display Related Posts'),
				'DisplayRelatedPosts',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
		}
			
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
			
		if (JCORE_VERSION >= '0.6') {
			$form->add(
				__('Rating Options'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
			
			$form->add(
				__('Enable Rating'),
				'EnableRating',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
				
			$form->add(
				__('Enable Guest Rating'),
				'EnableGuestRating',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
				
			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER);
		}
		
		$form->add(
			__('Comments Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Enable Comments'),
			'EnableComments',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
		$form->add(
			__('Enable Guest Comments'),
			'EnableGuestComments',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
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
			
		$form->addAdditionalText(
			"<span class='comment' style='text-decoration: line-through;'>" .
			__("(marked with strike through)").
			"</span>");	
			
		if ($edit) {
			$form->add(
				__('Insert as New'),
				'InsertAsNew',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
				
			$form->addAdditionalText(
				"<span class='comment'>" .
				__("(will create a new post)").
				"</span>");
		}	
			
		$form->add(
			__('Order'),
			'OrderID',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		$form->add(
			__('Owner'),
			'Owner',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 110px;');
		
		$form->addAdditionalText(
			"<a style='zoom: 1;' href='".url::uri('request, users') .
				"&amp;request=".url::path() .
				"&amp;users=1' " .
				"class='select-owner-link ajax-content-link'>" .
				_("Select User") .
			"</a>");
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::setupAdmin', $this, $form);
	}
	
	function verifyAdmin(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::verifyAdmin', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::verifyAdmin', $this, $form, $handled);
			
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
					'posts::verifyAdmin', $this, $form);
				return false;
			}
			
			foreach((array)$orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{posts}` " .
					" SET `OrderID` = '".(int)$ovalue."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".(int)$oid."'" .
					($this->userPermissionIDs?
						" AND `ID` IN (".$this->userPermissionIDs.")":
						null) .
					($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
						" AND `UserID` = '".(int)$GLOBALS['USER']->data['ID']."'":
						null));
			}
			
			tooltip::display(
				__("Posts have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'posts::verifyAdmin', $this, $form, $reorder);
			
			return true;
		}
		
		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'posts::verifyAdmin', $this, $form);
				return false;
			}
			
			$result = $this->delete($id);
			
			if ($result)
				tooltip::display(
					__("Post has been successfully deleted."),
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'posts::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'posts::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if ($form->get('Owner')) {
			$user = sql::fetch(sql::run(
				" SELECT * FROM `{users}` " .
				" WHERE `UserName` = '".sql::escape($form->get('Owner'))."'"));
			
			if (!$user) {
				tooltip::display(
					sprintf(__("User \"%s\" couldn't be found!"), 
						$form->get('Owner'))." " .
					__("Please make sure you have entered / selected the right " .
						"username or if it's a new user please first create " .
						"the user at Member Management -> Users."),
					TOOLTIP_ERROR);
				
				$form->setError('Owner', FORM_ERROR_REQUIRED);
				
				api::callHooks(API_HOOK_AFTER,
					'posts::verifyAdmin', $this, $form);
				
				return false;
			}
			
			$form->add(
				'UserID',
				'UserID',
				FORM_INPUT_TYPE_HIDDEN);
			$form->setValue('UserID', $user['ID']);
		}
		
		if (!$form->get('Path'))
			$form->set('Path', url::genPathFromString($form->get('Title')));
			
		if ($edit) {
			$result = $this->edit($id, $form->getPostArray());
			
			if ($result) {
				$post = sql::fetch(sql::run(
					" SELECT * FROM `{posts}`" .
					" WHERE `ID` = '".(int)$id."'"));
				
				tooltip::display(
					__("Post has been successfully updated.")." ".
					"<a href='".$this->generateLink($post)."' target='_blank'>" .
						__("View Post") .
					"</a>" .
					" - " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);
			}
			
			api::callHooks(API_HOOK_AFTER,
				'posts::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if ($this->userPermissionIDs) {
			api::callHooks(API_HOOK_AFTER,
				'posts::verifyAdmin', $this, $form);
			
			return false;
		}
		
		$newid = $this->add($form->getPostArray());
		
		if ($newid) {
			$post = sql::fetch(sql::run(
				" SELECT * FROM `{posts}`" .
				" WHERE `ID` = '".(int)$newid."'"));
			
			tooltip::display(
				__("Post has been successfully created.")." " .
					"<a href='".$this->generateLink($post)."' target='_blank'>" .
						__("View Post") .
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
			'posts::verifyAdmin', $this, $form, $result);
		
		return $result;
	}
	
	function displayAdminAvailableKeywords($targetfield = '#neweditpostform #entryKeywords') {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdminAvailableKeywords', $this, $targetfield);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdminAvailableKeywords', $this, $targetfield, $handled);
			
			return $handled;
		}
		
		$search = null;
		
		if (isset($_POST['ajaxsearch']))
			$search = trim(strip_tags((string)$_POST['ajaxsearch']));
		
		if (isset($_GET['ajaxsearch']))
			$search = trim(strip_tags((string)$_GET['ajaxsearch']));
		
		if (!isset($search) && !isset($_GET['ajaxlimit']))
			echo 
				"<div class='posts-add-keywords-list'>";
			
		echo
				"<div class='posts-add-keywords-list-search' " .
					"style='margin-right: 20px;'>" .
					"<form action='".url::uri('ajaxsearch, ajaxlimit, ajax')."' method='post' " .
						"class='ajax-form' " .
						"target='.posts-add-keywords-list'>" .
					__("Search").": " .
					"<input type='search' " .
						"name='ajaxsearch' " .
						"value='".
							htmlspecialchars($search, ENT_QUOTES).
						"' results='5' placeholder='".htmlspecialchars(__("search..."), ENT_QUOTES)."' " .
						"autofocus='autofocus' />" .
					"</form>" .
				"</div>" .
				"<br />" .
				"<table cellpadding='0' cellspacing='0' class='list'>" .
					"<thead>" .
					"<tr>" .
						"<th>" .
							"<span class='nowrap'>".
							__("Add") .
							"</span>" .
						"</th>" .
						"<th>" .
							"<span class='nowrap'>".
							__("Tag").
							"</span>" .
						"</th>" .
						"<th style='text-align: right;'>" .
							"<span class='nowrap'>".
							__("Popularity").
							"</span>" .
						"</th>" .
					"</tr>" .
					"</thead>" .
					"<tbody>";
					
		$paging = new paging(10,
			'&amp;ajaxsearch='.urlencode($search));
		
		$paging->track('ajaxlimit');
		$paging->ajax = true;
		
		$rows = sql::run(
			" SELECT * FROM `{postkeywords}`" .
			" WHERE 1" .
			($search?
				" AND (`Keyword` LIKE '%".sql::escape($search)."%') ":
				null) .
			" ORDER BY `Counter` DESC, `Keyword`" .
			" LIMIT ".$paging->limit);
		
		$paging->setTotalItems(sql::count('Keyword'));
		
		$i = 1;
		$total = sql::rows($rows);
		
		while ($row = sql::fetch($rows)) {
			echo
				"<tr".($i%2?" class='pair'":NULL).">" .
					"<td align='center'>" .
						"<a href='javascript://' " .
							"onclick=\"" .
								"$('".$targetfield."').val(" .
									"$('".$targetfield."').val()+" .
									"($('".$targetfield."').val()?', ':'')+" .
									"'".htmlspecialchars($row['Keyword'], ENT_QUOTES)."');" .
									"$(this).closest('.posts-add-keywords-list').find('input[type=search]').first().focus();" .
								"\" class='add-link'>" .
							(JCORE_VERSION < '0.6'?
								"&nbsp;+&nbsp;":
								null) .
						"</a>" .
					"</td>" .
					"<td class='auto-width'>" .
						"<b>" .
						$row['Keyword'] .
						"</b>" .
					"</td>" .
					"<td style='text-align: right;'>" .
						$row['Counter'] .
					"</td>" .
				"</tr>";
			
			$i++;
		}
		
		echo
					"</tbody>" .
				"</table>" .
				"<br />";
				
		$paging->display();
		
		if (!isset($search) && !isset($_GET['ajaxlimit']))
			echo
				"</div>";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAdminAvailableKeywords', $this, $targetfield);
	}
	
	function displayAdminListItemSelected(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdminListItemSelected', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdminListItemSelected', $this, $row, $handled);
			
			return $handled;
		}
		
		$blockroute = null;
		
		admin::displayItemData(
			__("Path"),
			$row['Path']);
		
		if ($row['Keywords'])
			admin::displayItemData(
				__("Tags"),
				$row['Keywords']);
		
		if (JCORE_VERSION >= '0.6' && $row['URL'])
			admin::displayItemData(
				__("Link to URL"),
				"<a href='".$row['URL']."' target='_blank'>" . 
					$row['URL'] . 
				"</a>");
		
		if ($row['StartDate'])
			admin::displayItemData(
				__("Start Date"),
				$row['StartDate']);
		
		if ($row['EndDate'])
			admin::displayItemData(
				__("End Date"),
				$row['EndDate']);
		
		if ($row['HideExpired'])
			admin::displayItemData(
				__("Hide when Expired"),
				__("Yes"));
		
		if ($row['BlockID'])
			foreach(blocks::getBackTraceTree($row['BlockID']) as $block)
				$blockroute .=
					"<div ".
						($block['ID'] != $row['BlockID']?
							"class='comment'":
							null) .
						">" . 
					($block['SubBlockOfID']?
						str_replace(' ', '&nbsp;', 
							str_pad('', $block['PathDeepnes']*4, ' ')).
						"|- ":
						null). 
					$block['Title'].
					"</div>";
	
		if ($row['BlockID'])
			admin::displayItemData(
				__("Display in Block"),
				$blockroute);
		
		if ($row['PartialContent'])
			admin::displayItemData(
				__("Partial Content"),
				__("Yes"));
		
		if ($row['NotSearchable'])
			admin::displayItemData(
				__("Not Searchable"),
				__("Yes"));
		
		if ($row['OnMainPage'])
			admin::displayItemData(
				(pages::isHome($row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')])?
					__("Display on All pages"):
					__("Display on Main page")),
				__("Yes"));
		
		if (isset($row['DisplayRelatedPosts']) && $row['DisplayRelatedPosts'])
			admin::displayItemData(
				__("Display Related Posts"),
				__("Yes"));
		
		if (isset($row['EnableRating']) && $row['EnableRating'])
			admin::displayItemData(
				__("Enable Rating"),
				__("Yes") .
				($row['EnableGuestRating']?
					" ".__("(Guests can rate too!)"):
					null));
		
		if (isset($row['EnableComments']) && $row['EnableComments'])
			admin::displayItemData(
				__("Enable Comments"),
				__("Yes") .
				($row['EnableGuestComments']?
					" ".__("(Guests can comment too!)"):
					null));
		
		admin::displayItemData(
			"<hr />");
		admin::displayItemData(
			str_replace(
				'<div style="page-break-after: always',
				'<div class="page-break" style="page-break-after: always',
				$row['Content']));
		
		if (JCORE_VERSION >= '0.7')
			$this->displayCustomFields($row);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAdminListItemSelected', $this, $row);
	}
	
	function displayAdminListHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdminListHeader', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdminListHeader', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Created on")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAdminListHeader', $this);
	}
	
	function displayAdminListHeaderOptions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdminListHeaderOptions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdminListHeaderOptions', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<th><span class='nowrap'>".
				__("Comments")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Attachments")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Pictures")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdminListHeaderFunctions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdminListHeaderFunctions', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdminListItem', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdminListItem', $this, $row, $handled);
			
			return $handled;
		}
		
		$user = $GLOBALS['USER']->get($row['UserID']);
		
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
					calendar::dateTime($row['TimeStamp'])." " .
					$GLOBALS['USER']->constructUserName($user, __('by %s')) .
					", ".sprintf(__("%s views"), $row['Views']) .
				"</div>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListItemOptions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdminListItemOptions', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdminListItemOptions', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			"<td align='center'>" .
				"<a class='admin-link comments' " .
					"title='".htmlspecialchars(__("Comments"), ENT_QUOTES).
						" (".$row['Comments'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/postcomments'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $row['Comments'])
			counter::display($row['Comments']);
		
		echo
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link attachments' " .
					"title='".htmlspecialchars(__("Attachments"), ENT_QUOTES) .
						" (".$row['Attachments'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/postattachments'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $row['Attachments'])
			counter::display($row['Attachments']);
		
		echo
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link pictures' " .
					"title='".htmlspecialchars(__("Pictures"), ENT_QUOTES) .
						" (".$row['Pictures'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/postpictures'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $row['Pictures'])
			counter::display($row['Pictures']);
		
		echo
				"</a>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdminListItemFunctions', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdminListItemFunctions', $this, $row, $handled);
			
			return $handled;
		}
		
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
			'posts::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminListFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdminListFunctions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdminListFunctions', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<input type='submit' name='reordersubmit' value='" .
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAdminListFunctions', $this);
	}
	
	function displayAdminListSearch() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdminListSearch', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdminListSearch', $this, $handled);
			
			return $handled;
		}
		
		$search = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags((string)$_GET['search']));
		
		echo 
			"<input type='hidden' name='path' value='".admin::path()."' />" .
			"<input type='search' name='search' value='".
				htmlspecialchars($search, ENT_QUOTES).
				"' results='5' placeholder='".htmlspecialchars(__("search..."), ENT_QUOTES)."' /> " .
			"<input type='submit' value='" .
				htmlspecialchars(__("Search"), ENT_QUOTES)."' class='button' />";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAdminListSearch', $this);
	}
	
	function displayAdminList(&$rows) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdminList', $this, $rows);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdminList', $this, $rows, $handled);
			
			return $handled;
		}
		
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		echo
			"<form action='".
				url::uri('edit, delete').
				"' method='post'>" .
				"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";
			
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
				
			if ($row['ID'] == $id) {
				echo
					"<tr".($i%2?" class='pair'":NULL).">" .
						"<td class='auto-width' colspan='10'>" .
							"<div class='admin-content-preview'>";
				
				$this->displayAdminListItemSelected($row);
				
				echo
							"</div>" .
						"</td>" .
					"</tr>";
			}
			
			$i++;
		}
		
		echo 
				"</tbody>" .
			"</table>";
		
		echo "<br />";
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			$this->displayAdminListFunctions();
			
			echo
				"<div class='clear-both'></div>" .
				"<br />";
		}
		
		echo
			"</form>";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAdminList', $this, $rows);
	}
	
	function displayAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdminList', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdminList', $this, $form, $handled);
			
			return $handled;
		}
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAdminList', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdminTitle', $this, $ownertitle);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdminTitle', $this, $ownertitle, $handled);
			
			return $handled;
		}
		
		admin::displayTitle(
			__('Posts'), 
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdminDescription', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdminDescription', $this, $handled);
			
			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAdmin', $this, $handled);
			
			return $handled;
		}
		
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
			
		if ($edit && isset($_POST['InsertAsNew']) && (int)$_POST['InsertAsNew']) {
			$_GET['limit'] = null;
			$_GET['edit'] = null;
			$_GET['id'] = null;
			
			$edit = false;
			$id = null;
		}
		
		$selectedowner = sql::fetch(sql::run(
			" SELECT `Title`, `LanguageID` " .
			" FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}` " .
			" WHERE `ID` = '".admin::getPathID()."'"));
			
		echo
			"<div style='float: right;'>" .
				"<form action='".url::uri('ALL')."' method='get'>";
		
		$this->displayAdminListSearch();
		
		echo
				"</form>" .
			"</div>";
		
		$this->displayAdminTitle($selectedowner['Title']);
		$this->displayAdminDescription();
			
		echo
			"<div class='admin-content'>";
				
		$form = new form(
				($edit?
					__("Edit Post"):
					__("New Post")),
				'neweditpost');
		
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
		
		$selected = null;
		$verifyok = false;
		
		if ($id) {
			$selected = sql::fetch(sql::run(
				" SELECT `ID`, `Title` FROM `{posts}`" .
				" WHERE `ID` = '".$id."'" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
					" AND `UserID` = '".(int)$GLOBALS['USER']->data['ID']."'":
					null)));
			
			if ($delete && empty($_POST['delete']))
				url::displayConfirmation(
					'<b>'.__('Delete').'?!</b> "'.$selected['Title'].'"');
		}
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			((!$edit && !$delete) || $selected))
			$verifyok = $this->verifyAdmin($form);
		
		$paging = new paging(10);
		$paging->ignoreArgs = 'id, edit, delete';
		
		$rows = sql::run(
				" SELECT * FROM `{posts}`" .
				" WHERE `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
					admin::getPathID()."'" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
					" AND `UserID` = '".(int)$GLOBALS['USER']->data['ID']."'":
					null) .
				($search?
					sql::search(
						$search,
						array('Title', 'Content', 'Keywords')):
					null) .
				" ORDER BY `OrderID`, `ID` DESC" .
				" LIMIT ".$paging->limit);
		
		$paging->setTotalItems(sql::count());
				
		if ($paging->items)
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No posts found."),
				TOOLTIP_NOTIFICATION);
		
		$paging->display();
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && $selected)))
		{
			if (pages::isHome(admin::getPathID()))
				$form->edit(
					'OnMainPage',
					__('Display on All pages'));
			
			if ($edit && $selected && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{posts}`" .
					" WHERE `ID` = '".$id."'"));
				
				$form->setValues($selected);
				
				$user = $GLOBALS['USER']->get($selected['UserID']);
				$form->setValue('Owner', $user['UserName']);
			}
			
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form);
		
		echo 
			"</div>";	//admin-content
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAdmin', $this);
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::add', $this, $values);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::add', $this, $values, $handled);
			
			return $handled;
		}
		
		if ($values['OrderID'] == '') {
			if (JCORE_VERSION < '0.9')
				$pageids = pages::getAliasIDs((int)$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]);
			
			$pageids[] = (int)$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')];
			
			sql::run(
				" UPDATE `{posts}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` IN (" .
					implode(',', $pageids).")");
			
			$values['OrderID'] = 1;
			
		} else {
			sql::run(
				" UPDATE `{posts}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
					(int)$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'" .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		if ((int)$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')])
			$page = sql::fetch(sql::run(
				" SELECT * FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `ID` = '".(int)$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'"));
		else
			$page = pages::getHome();
		
		if (stripos($values['Content'], '<div style="page-break-after: always') !== false)
			$values['PartialContent'] = true;
		
		if (JCORE_VERSION >= '0.7') {
			if (!isset($values['UserID']))
				$values['UserID'] = (int)$GLOBALS['USER']->data['ID'];
			
			if (JCORE_VERSION >= '0.9' && (int)$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')])
				$values['LanguageID'] = $page['LanguageID'];
			
			$postsform = new postsForm();
			$newid = $postsform->addData($values);
			unset($postsform);
		
		} else {
			$newid = sql::run(
				" INSERT INTO `{posts}` SET ".
				" `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '".
					(int)$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'," .
				" `Title` = '".
					sql::escape($values['Title'])."'," .
				" `Content` = '".
					sql::escape($values['Content'])."'," .
				" `Path` = '".
					sql::escape($values['Path'])."'," .
				(JCORE_VERSION >= '0.6'?
					" `URL` = '".
						sql::escape($values['URL'])."',":
					null) .
				" `Keywords` = '".
					sql::escape($values['Keywords'])."'," .
				" `TimeStamp` = " .
					($values['TimeStamp']?
						"'".sql::escape($values['TimeStamp'])."'":
						"NOW()").
					"," .
				" `StartDate` = " .
					($values['StartDate']?
						"'".sql::escape($values['StartDate'])."'":
						"NULL").
					"," .
				" `EndDate` = " .
					($values['EndDate']?
						"'".sql::escape($values['EndDate'])."'":
						"NULL").
					"," .
				" `Deactivated` = '".
					(int)$values['Deactivated']."'," .
				" `PartialContent` = '".
					(int)$values['PartialContent']."'," .
				" `OnMainPage` = '".
					(int)$values['OnMainPage']."'," .
				(JCORE_VERSION >= '0.3'?
					" `DisplayRelatedPosts` = '".
						(int)$values['DisplayRelatedPosts']."',":
					null) .
				(JCORE_VERSION >= '0.6'?
					" `EnableRating` = '".
						(int)$values['EnableRating']."'," .
					" `EnableGuestRating` = '".
						(int)$values['EnableGuestRating']."',":
					null) .
				" `EnableComments` = '".
					(int)$values['EnableComments']."'," .
				" `EnableGuestComments` = '".
					(int)$values['EnableGuestComments']."'," .
				" `UserID` = '".
					(isset($values['UserID']) && (int)$values['UserID']?
						(int)$values['UserID']:
						(int)$GLOBALS['USER']->data['ID']) .
					"'," .
				" `BlockID` = '".
					(int)$values['BlockID']."'," .
				" `OrderID` = '".
					(int)$values['OrderID']."'");
		}
		
		if (!$newid) {
			tooltip::display(
				sprintf(__("Post couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'posts::add', $this, $values);
			
			return false;
		}
		
		if (JCORE_VERSION >= '0.5') {
			sql::run(
				" UPDATE `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}` SET " .
				" `Posts` = `Posts` + 1" .
				" WHERE `ID` = '".(int)$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'");
			
			$this->updateKeywordsCloud(
				$values['Keywords'], null,
				(JCORE_VERSION >= '0.7'?$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]:null));
		}
				
		$sitemap = new siteMap();
		$sitemap->load();
		
		if (SEO_FRIENDLY_LINKS)
			$pageurl = SITE_URL.
				$page['Path'];
		else
			$pageurl = SITE_URL.'index.php' .
				'?pageid='.$page['ID'];
		
		$sitemap->edit($pageurl, array(
			'LastModified' => 
				($values['TimeStamp']?
					$values['TimeStamp']:
					date('Y-m-d H:i:s'))));
		
		if (!$sitemap->save())
			tooltip::display(
				__("Post successfully created but xml sitemap file couldn't be updated.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"sitemap.xml"),
				TOOLTIP_NOTIFICATION);
		
		unset($sitemap);
		
		if ($page[(JCORE_VERSION >= '0.9'?'AccessibleBy':'ViewableBy')] <= PAGE_GUESTS_ONLY && 
			(!$this->updateRSS() || !$this->updateRSS($page['ID'])))
			tooltip::display(
				__("Post successfully created but rss feed couldn't be updated.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"rss/"),
				TOOLTIP_NOTIFICATION);
		
		if (defined('BLOG_PING_ON_NEW_POSTS') && BLOG_PING_ON_NEW_POSTS &&
			$page[(JCORE_VERSION >= '0.9'?'AccessibleBy':'ViewableBy')] <= PAGE_GUESTS_ONLY && 
			!$this->blogPing($page['ID']))
			tooltip::display(
				__("Post successfully created but couldn't ping blog servers. " .
					"Please define at least one blog ping server or multiple " .
					"servers separated by commas in Global Settings."),
				TOOLTIP_NOTIFICATION);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::add', $this, $values, $newid);
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::edit', $this, $id, $values);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::edit', $this, $id, $values, $handled);
			
			return $handled;
		}
		
		$post = sql::fetch(sql::run(
			" SELECT * FROM `{posts}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		if ((int)$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')])
			$page = sql::fetch(sql::run(
				" SELECT * FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `ID` = '".(int)$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'"));
		else
			$page = pages::getHome();
		
		if (stripos($values['Content'], '<div style="page-break-after: always') !== false &&
			stripos($post['Content'], '<div style="page-break-after: always') === false)
			$values['PartialContent'] = true;
		
		if (stripos($values['Content'], '<div style="page-break-after: always') === false &&
			stripos($post['Content'], '<div style="page-break-after: always') !== false)
			$values['PartialContent'] = false;
		
		if (JCORE_VERSION >= '0.7') {
			if (JCORE_VERSION >= '0.9' && (int)$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')])
				$values['LanguageID'] = $page['LanguageID'];
			
			$postsform = new postsForm();
			$postsform->editData($id, $values);
			unset($postsform);
		
		} else {
			sql::run(
				" UPDATE `{posts}` SET ".
				" `Title` = '".
					sql::escape($values['Title'])."'," .
				" `Content` = '".
					sql::escape($values['Content'])."'," .
				" `Path` = '".
					sql::escape($values['Path'])."'," .
				(JCORE_VERSION >= '0.6'?
					" `URL` = '".
						sql::escape($values['URL'])."',":
					null) .
				" `Keywords` = '".
					sql::escape($values['Keywords'])."'," .
				" `TimeStamp` = " .
					($values['TimeStamp']?
						"'".sql::escape($values['TimeStamp'])."'":
						"NOW()").
					"," .
				" `StartDate` = " .
					($values['StartDate']?
						"'".sql::escape($values['StartDate'])."'":
						"NULL").
					"," .
				" `EndDate` = " .
					($values['EndDate']?
						"'".sql::escape($values['EndDate'])."'":
						"NULL").
					"," .
				" `Deactivated` = '".
					(int)$values['Deactivated']."'," .
				" `PartialContent` = '".
					(int)$values['PartialContent']."'," .
				" `OnMainPage` = '".
					(int)$values['OnMainPage']."'," .
				(JCORE_VERSION >= '0.3'?
					" `DisplayRelatedPosts` = '".
						(int)$values['DisplayRelatedPosts']."',":
					null) .
				(JCORE_VERSION >= '0.6'?
					" `EnableRating` = '".
						(int)$values['EnableRating']."'," .
					" `EnableGuestRating` = '".
						(int)$values['EnableGuestRating']."',":
					null) .
				" `EnableComments` = '".
					(int)$values['EnableComments']."'," .
				" `EnableGuestComments` = '".
					(int)$values['EnableGuestComments']."'," .
				" `BlockID` = '".
					(int)$values['BlockID']."'," .
				(isset($values['UserID']) && (int)$values['UserID']?
					" `UserID` = '".(int)$values['UserID']."',":
					null) .
				" `OrderID` = '".
					(int)$values['OrderID']."'" .
				" WHERE `ID` = '".(int)$id."'");
		}
			
		$result = (sql::affected() != -1);
		
		if (!$result) {
			tooltip::display(
				sprintf(__("Post couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'posts::edit', $this, $id, $values, $result);
			
			return false;
		}
		
		if (JCORE_VERSION >= '0.5') {
			if ($post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')] != 
				$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]) 
			{
				$posts = sql::fetch(sql::run(
					" SELECT COUNT(`ID`) AS `Rows` FROM `{posts}`" .
					" WHERE `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
						(int)$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'"));
				
				sql::run(
					"UPDATE `{" .
						(JCORE_VERSION >= '0.8'?
							'pages':
							'menuitems') .
						"}`" .
					" SET `Posts` = '".(int)$posts['Rows']."'" .
					" WHERE `ID` = '".(int)$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'");
				
				$posts = sql::fetch(sql::run(
					" SELECT COUNT(`ID`) AS `Rows` FROM `{posts}`" .
					" WHERE `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
						(int)$post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'"));
				
				sql::run(
					"UPDATE `{" .
						(JCORE_VERSION >= '0.8'?
							'pages':
							'menuitems') .
						"}`" .
					" SET `Posts` = '".(int)$posts['Rows']."'" .
					" WHERE `ID` = '".(int)$post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'");
			}
			
			$this->updateKeywordsCloud(
				$values['Keywords'], $post['Keywords'],
				(JCORE_VERSION >= '0.7'?$values[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]:null),
				(JCORE_VERSION >= '0.7'?$post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]:null));
		}
		
		$sitemap = new siteMap();
		$sitemap->load();
		
		if (SEO_FRIENDLY_LINKS)
			$pageurl = SITE_URL.
				$page['Path'];
		else
			$pageurl = SITE_URL.'index.php' .
				'?pageid='.$page['ID'];
		
		$sitemap->edit($pageurl, array(
			'LastModified' => 
				($values['TimeStamp']?
					$values['TimeStamp']:
					date('Y-m-d H:i:s'))));
		
		if (!$sitemap->save())
			tooltip::display(
				__("Post successfully updated but xml sitemap file couldn't be updated.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"sitemap.xml"),
				TOOLTIP_NOTIFICATION);
		
		unset($sitemap);
		
		if ($page[(JCORE_VERSION >= '0.9'?'AccessibleBy':'ViewableBy')] <= PAGE_GUESTS_ONLY && 
			(!$this->updateRSS() || !$this->updateRSS($page['ID'])))
			tooltip::display(
				__("Post successfully updated but rss feed couldn't be updated.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"rss/"),
				TOOLTIP_NOTIFICATION);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::edit', $this, $id, $values, $result);
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
			
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::delete', $this, $id);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::delete', $this, $id, $handled);
			
			return $handled;
		}
		
		$post = sql::fetch(sql::run(
			" SELECT * FROM `{posts}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		$comments = new postComments();
		
		$rows = sql::run(
			" SELECT * FROM `{postcomments}`" .
			" WHERE `PostID` = '".$id."'");
		
		while($row = sql::fetch($rows))
			$comments->delete($row['ID']);
			
		unset($comments);
		
		$pictures = new postPictures();
		
		$rows = sql::run(
			" SELECT * FROM `{postpictures}`" .
			" WHERE `PostID` = '".$id."'");
		
		while($row = sql::fetch($rows))
			$pictures->delete($row['ID']);
		
		unset($pictures);
		
		$attachments = new postAttachments();
		
		$rows = sql::run(
			" SELECT * FROM `{postattachments}`" .
			" WHERE `PostID` = '".$id."'");
		
		while($row = sql::fetch($rows))
			$attachments->delete($row['ID']);
		
		unset($attachments);
		
		if (JCORE_VERSION >= '0.6')
			sql::run(
				" DELETE FROM `{postratings}` " .
				" WHERE `PostID` = '".$id."'");
		
		sql::run(
			" DELETE FROM `{posts}` " .
			" WHERE `ID` = '".$id."'");
		
		if (JCORE_VERSION >= '0.5') {
			$row = sql::fetch(sql::run(
				" SELECT COUNT(`ID`) AS `Rows` FROM `{posts}`" .
				" WHERE `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
					$post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'"));
			
			sql::run(
				"UPDATE `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" SET `Posts` = '".(int)$row['Rows']."'" .
				" WHERE `ID` = '".$post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'");
			
			$this->updateKeywordsCloud(
				null, $post['Keywords'],
				null, (JCORE_VERSION >= '0.7'?$post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]:null));
		}
		
		if ($post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')])	
			$page = sql::fetch(sql::run(
				" SELECT * FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `ID` = '".$post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'"));
		else
			$page = pages::getHome();
		
		if ($page[(JCORE_VERSION >= '0.9'?'AccessibleBy':'ViewableBy')] <= PAGE_GUESTS_ONLY && 
			(!$this->updateRSS() || !$this->updateRSS($page['ID'])))
			tooltip::display(
				__("Post successfully deleted but rss feed couldn't be updated.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"rss/"),
				TOOLTIP_NOTIFICATION);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::delete', $this, $id);
		
		return true;
	}
	
	function activate($id) {
		if (!$id)
			return false;
		
		$post = sql::fetch(sql::run(
			" SELECT * FROM `{posts}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		if (!$post)
			return false;
		
		if (!$post['Deactivated'])
			return true;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::activate', $this, $id);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::activate', $this, $id, $handled);
			
			return $handled;
		}
		
		$post['Deactivated'] = false;
		$result = $this->edit($id, $post);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::activate', $this, $id, $result);
		
		return $result;
	}
	
	function deactivate($id) {
		if (!$id)
			return false;
		
		$post = sql::fetch(sql::run(
			" SELECT * FROM `{posts}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		if (!$post)
			return false;
		
		if ($post['Deactivated'])
			return true;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::deactivate', $this, $id);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::deactivate', $this, $id, $handled);
			
			return $handled;
		}
		
		$post['Deactivated'] = true;
		$result = $this->edit($id, $post);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::deactivate', $this, $id, $result);
		
		return $result;
	}
	
	static function updateRSS($pageid = null) {
		$rss = new rss();
		$rss->file = SITE_PATH.'rss/posts.xml';
		
		$ignorepageids = null;
		$pageids = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(`ID` SEPARATOR ',') AS PageIDs" .
			" FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}`" .
			" WHERE `Deactivated` = 1" .
			" OR `".(JCORE_VERSION >= '0.9'?'AccessibleBy':'ViewableBy')."` > 1"));
		
		if (isset($pageids['PageIDs']) && $pageids['PageIDs'])
			$ignorepageids = $pageids['PageIDs'];
		
		if ($pageid) {
			$page = sql::fetch(sql::run(
				" SELECT `Title`, `Path` " .
				" FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}` " .
				" WHERE `ID` = '".(int)$pageid."'"));
			
			if (!$page['Path']) {
				unset($rss);
				return false;
			}
			
			$rss->channel['Title'] = $page['Title']." - ".
				$rss->channel['Title'];
			
			$rss->file = SITE_PATH.'rss/posts-'.preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '',
					str_replace('/', '-', $page['Path'])).'.xml';
		}
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::updateRSS', $_ENV, $pageid, $rss);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::updateRSS', $_ENV, $pageid, $rss, $handled);
			
			return $handled;
		}
		
		$rows = sql::run(
			" SELECT * FROM `{posts}`" .
			" WHERE 1" .
			($pageid?
				" AND `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
					(int)$pageid."'":
				null) .
			($ignorepageids?
				" AND `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` NOT IN (" .
					$ignorepageids.")":
				null) .
			" AND `Deactivated` = 0" .
			" AND `BlockID` = 0" .
			" ORDER BY `OrderID`, `StartDate`, `ID` DESC" .
			" LIMIT 10");
			
		while($row = sql::fetch($rows)) {
			$page = sql::fetch(sql::run(
				" SELECT `ID`, `Path` " .
				" FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}` " .
				" WHERE `ID` = '".$row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'"));
			
			$user = $GLOBALS['USER']->get($row['UserID']);
		
			if (SEO_FRIENDLY_LINKS) {
				$postlink = SITE_URL.
					$page['Path'].'/'.$row['Path'];
			
			} else {
				$postlink = SITE_URL.'index.php' .
					'?pageid='.$page['ID'].
					'&amp;postid='.$row['ID'];
			}
			
			$rss->add(array(
				'Title' => $row['Title'],
				'Link' => $postlink,
				'Description' => posts::generateTeaser($row['Content']),
				'TimeStamp' => $row['TimeStamp'],
				'Comments' => $row['Comments'],
				'Author' => 
					($user?
						$user['Email']." (".$user['UserName'].")":
						null)));
		}
		
		$result = false;
		
		if (!count($rss->items)) {
			files::delete($rss->file);
			$result = true;
			
		} elseif ($rss->save()) {
			$result = true;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'posts::updateRSS', $_ENV, $pageid, $rss, $result);
		
		unset($rss);
		return $result;
	}
	
	static function updateKeywordsCloud($newkeywords = null, $oldkeywords = null, 
		$newpageid = null, $oldpageid = null) 
	{
		$oldkeywords = array_map('trim', explode(',', $oldkeywords));
		$newkeywords = array_map('trim', explode(',', $newkeywords));
		
		if (count($oldkeywords)) {
			foreach($oldkeywords as $oldkeyword) {
				if (!$oldkeyword || (in_array($oldkeyword, $newkeywords) && 
					$oldpageid == $newpageid))
					continue;
				
				$pageids = null;
				if ($oldpageid) {
					$pageids = sql::fetch(sql::run(
						" SELECT `".(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')."` FROM `{postkeywords}`" .
						" WHERE `Keyword` = '".sql::escape($oldkeyword)."'"));
					
					if ($pageids) {
						$pageids = explode('|', $pageids[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')]);
						
						$exists = sql::fetch(sql::run(
							" SELECT `ID` FROM `{posts}`" .
							" WHERE `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
								(int)$oldpageid."'" .
							" AND CONCAT(',', `Keywords`, ',') LIKE '%," .
								sql::escape($oldkeyword).",%'" .
							" LIMIT 1"));
						
						if (!$exists) {
							$pageids = array_flip($pageids);
							unset($pageids[(int)$oldpageid]);
							$pageids = array_flip($pageids);
						}
					}
				}
				
				sql::run(
					" UPDATE `{postkeywords}` SET " .
					(is_array($pageids)?
						"`".(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')."` = '" .
							implode('|', $pageids)."',":
						null) .
					" `Counter` = `Counter` - 1" .
					" WHERE `Keyword` = '".sql::escape($oldkeyword)."'");
			}
		}
			
		if (count($newkeywords)) {
			foreach($newkeywords as $newkeyword) {
				if (!$newkeyword || (in_array($newkeyword, $oldkeywords) &&
					$newpageid == $oldpageid))
					continue;
				
				$pageids = null;
				if ($newpageid) {
					$pageids = sql::fetch(sql::run(
						" SELECT `".(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')."` FROM `{postkeywords}`" .
						" WHERE `Keyword` = '".sql::escape($newkeyword)."'"));
					
					if ($pageids) {
						if ($pageids[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')])
							$pageids = explode('|', $pageids[(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')]);
						else
							$pageids = array();
						
						if (!in_array((int)$newpageid, $pageids))
							$pageids[] = (int)$newpageid;
						
					} else {
						$pageids[] = (int)$newpageid;
					}
				}
				
				sql::run(
					" UPDATE `{postkeywords}` SET " .
					($pageids?
						"`".(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')."` = '" .
							implode('|', $pageids)."',":
						null) .
					" `Counter` = `Counter` + 1" .
					" WHERE `Keyword` = '".sql::escape($newkeyword)."'");
				
				if (!sql::affected())
					sql::run(
						" INSERT INTO `{postkeywords}` SET" .
						($pageids?
							"`".(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')."` = '" .
								implode('|', $pageids)."',":
							null) .
						" `Keyword` = '".sql::escape($newkeyword)."'," .
						" `Counter` = 1");
			}
		}
		
		sql::run(
			" DELETE FROM `{postkeywords}`" .
			" WHERE `Counter` = 0");
		
		return true;
	}
	
	static function blogPing($pageid = null) {
		if (!defined('BLOG_PING_SERVERS') || !BLOG_PING_SERVERS)
			return false;
		
		$rssfile = SITE_URL.'rss/posts.xml';
		
		if ($pageid) {
			$page = sql::fetch(sql::run(
				" SELECT `Title`, `Path` " .
				" FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}` " .
				" WHERE `ID` = '".(int)$pageid."'"));
			
			if (!$page['Path'])
				return false;
			
			$rssfile = SITE_URL.'rss/posts-'.preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '',
					str_replace('/', '-', $page['Path'])).'.xml';
		}
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::blogPing', $_ENV, $pageid);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::blogPing', $_ENV, $pageid, $handled);
			
			return $handled;
		}
		
		include_once('lib/ixr.class.php');
		
		$servers = explode(",", BLOG_PING_SERVERS);
		foreach ($servers as $server) {
			if (!trim($server))
				continue;
				
			// using a timeout of 3 seconds should be enough to cover slow servers
			$client = new IXR_Client($server);
			$client->timeout = 3;
			$client->useragent .= ' -- jCore/'.JCORE_VERSION;
		
			// when set to true, this outputs debug messages by itself
			$client->debug = false;
			
			if (!$client->query('weblogUpdates.extendedPing', PAGE_TITLE, 
				SITE_URL, $rssfile)) 
			{
				// then try a normal ping
				$client->query('weblogUpdates.ping', PAGE_TITLE, 
					SITE_URL);
			}
			
			unset($client);
		}
		
		api::callHooks(API_HOOK_AFTER,
			'posts::blogPing', $_ENV, $pageid);
		
		return true;
	}
	
	// ************************************************   Client Part
	static function checkAccess($row) {
		if ($row && !is_array($row))
			$row = sql::fetch(sql::run(
				" SELECT `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."`" .
				" FROM `{posts}`" .
				" WHERE `ID` = '".(int)$row."'"));
		
		if (!$row)
			return true;
		
		return pages::checkAccess($row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]);
	}
	
	static function generateTeaser($description) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::generateTeaser', $_ENV, $description);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::generateTeaser', $_ENV, $description, $handled);
			
			return $handled;
		}
		
		if (stripos($description, '<div style="page-break-after: always') !== false)
			preg_match('/(.*?)(<div style="page-break-after: always)/is', $description, $matches);
		elseif (stripos($description, '<hr') !== false)
			preg_match('/(.*?)(<hr.*?>)/is', $description, $matches);
		else
			preg_match('/(.*?)((<\/p>))/is', $description, $matches);
		
		if (isset($matches[1])) {
			$teaser = $matches[1];
			
			if (isset($matches[3]))
				$teaser .= $matches[3];
			
		} else {
			$teaser = $description;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'posts::generateTeaser', $_ENV, $description, $teaser);
		
		return $teaser;
	}
	
	static function getSelected() {
		return posts::$selected;
	}
	
	static function getSelectedID () {
		if (posts::$selected)
			return posts::$selected['ID'];
		
		return 0;
	}
	
	static function getPostURL($post = null) {
		if (!$post)
			return url::get();
		
		if (!is_array($post))
			$post = sql::fetch(sql::run(
				" SELECT `ID`, `Path`, `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."`" .
				" FROM `{posts}`" .
				" WHERE `ID` = '".(int)$post."'"));
		
		$page = pages::$selected;
		$language = languages::$selected;
		
		if (!$post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')])
			$page = pages::getHome();
		elseif ($page['ID'] != $post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')])
			$page = sql::fetch(sql::run(
				" SELECT `ID`, `Path`, `LanguageID` FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `ID` = '".$post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'"));
		
		if ($language['ID'] != $page['LanguageID'])
			$language = sql::fetch(sql::run(
				" SELECT `ID`, `Path` FROM `{languages}`" .
				" WHERE `ID` = '".$page['LanguageID']."'"));
		
		if (SEO_FRIENDLY_LINKS)
			return
				url::site() .
				($language?
					$language['Path'].'/':
					null) .
				$page['Path'].'/' .
				$post['Path'];
		
		return 
			url::site().'index.php?' .
			($language?
				'&amp;languageid='.$language['ID']:
				null) .
			'&amp;pageid='.$page['ID'].
			'&amp;postid='.$post['ID'];
	}
	
	function generateLink(&$row) {
		if (isset($row['_PageLink']) && $row['_PageLink']) {
			if (SEO_FRIENDLY_LINKS)
				return
					$row['_PageLink'].'/' .
					$row['Path'];
			
			return
				$row['_PageLink'] . 
				'&amp;postid='.$row['ID'];
		}
		
		$language = $this->selectedLanguage;
		$page = $this->selectedPage;
		
		if (!$row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')])
			$page = pages::getHome();
		elseif (!$page)
			$page = sql::fetch(sql::run(
				" SELECT `ID`, `Path`, `LanguageID` FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `ID` = '".$row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'"));
		
		if (!$language && $page['LanguageID'])
			$language = sql::fetch(sql::run(
				" SELECT `ID`, `Path` FROM `{languages}`" .
				" WHERE `ID` = '".$page['LanguageID']."'"));
		
		if (SEO_FRIENDLY_LINKS)
			return
				url::site() .
				($language?
					$language['Path'].'/':
					null) .
				$page['Path'].'/' .
				$row['Path'];
		
		return 
			url::site().'index.php?' .
			($language?
				'&amp;languageid='.$language['ID']:
				null) .
			'&amp;pageid='.$page['ID'].
			'&amp;postid='.$row['ID'];
	}
	
	function generatePageLink(&$row) {
		$language = $this->selectedLanguage;
		$page = $this->selectedPage;
		
		if (!$row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')])
			$page = pages::getHome();
		elseif (!$page)
			$page = sql::fetch(sql::run(
				" SELECT `ID`, `Path`, `LanguageID` FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `ID` = '".$row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'"));
		
		if (!$language && $page['LanguageID'])
			$language = sql::fetch(sql::run(
				" SELECT `ID`, `Path` FROM `{languages}`" .
				" WHERE `ID` = '".$page['LanguageID']."'"));
		
		if (SEO_FRIENDLY_LINKS)
			return
				url::site() .
				($language?
					$language['Path'].'/':
					null) .
				$page['Path'];
		
		return 
			url::site().'index.php?' .
			($language?
				'&amp;languageid='.$language['ID']:
				null) .
			'&amp;pageid='.$page['ID'];
	}
	
	function generateCSSClass(&$row) {
		$class = null;
		$paths = explode('/', $this->selectedPage['Path']);
		
		foreach($paths as $path)
			$class .= " menu-" .
				preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '', $path);
		
		return $class;
	}
	
	function incViews(&$row) {
		sql::run(
			" UPDATE `{posts}` SET " .
			" `Views` = `Views` + 1," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".$row['ID']."'");
	}
	
	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::ajaxRequest', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::ajaxRequest', $this, $handled);
			
			return $handled;
		}
		
		$users = null;
		$keywords = null;
		
		if (isset($_GET['users']))
			$users = (int)$_GET['users'];
		
		if (isset($_GET['keywords']))
			$keywords = (int)$_GET['keywords'];
		
		if ($users) {
			if (!$GLOBALS['USER']->loginok || 
				!$GLOBALS['USER']->data['Admin']) 
			{
				tooltip::display(
					__("Request can only be accessed by administrators!"),
					TOOLTIP_ERROR);
				
				api::callHooks(API_HOOK_AFTER,
					'posts::ajaxRequest', $this);
				
				return true;
			}
			
			include_once('lib/userpermissions.class.php');
			
			$permission = userPermissions::check(
				(int)$GLOBALS['USER']->data['ID'],
				$this->adminPath);
			
			if (~$permission['PermissionType'] & USER_PERMISSION_TYPE_WRITE) {
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);
				
				api::callHooks(API_HOOK_AFTER,
					'posts::ajaxRequest', $this);
				
				return true;
			}
			
			$GLOBALS['USER']->displayQuickList('#neweditpostform #entryOwner');
			
			api::callHooks(API_HOOK_AFTER,
				'posts::ajaxRequest', $this, $users);
			
			return true;
		}
		
		if ($keywords) {
			$this->displayAdminAvailableKeywords();
			
			api::callHooks(API_HOOK_AFTER,
				'posts::ajaxRequest', $this, $keywords);
			
			return true;
		}
		
		if (preg_match('/[0-9]/', $this->uriRequest))
			$this->selectedPageID = url::getPathID(0, $this->uriRequest);
		
		if (!pages::checkAccess($this->selectedPageID)) {
			$page = new pages();
			$page->displayLogin();
			unset($page);
			$result = true;
			
		} else {
			$this->ajaxPaging = true;
			$this->display();
			$Result = true;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'posts::ajaxRequest', $this, $result);
		
		return true;
	}
	
	function displayTitle(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayTitle', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayTitle', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			"<a href='" .
				(JCORE_VERSION >= '0.6' && $row['URL']?
					url::generateLink($row['URL']):
					$row['_Link']) .
				"'>" .
				$row['Title'] .
			"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayTitle', $this, $row);
	}
	
	function displaySelectedTitle(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displaySelectedTitle', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displaySelectedTitle', $this, $row, $handled);
			
			return $handled;
		}
		
		echo $row['Title'];
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displaySelectedTitle', $this, $row);
	}
	
	function displayDetails(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayDetails', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayDetails', $this, $row, $handled);
			
			return $handled;
		}
		
		$user = $GLOBALS['USER']->get($row['UserID']);
		
		echo
			"<span class='details-date'>" .
			calendar::datetime($row['TimeStamp']) .
			" </span>";
		
		$GLOBALS['USER']->displayUserName($user, __('by %s'));
		
		if ((JCORE_VERSION < '0.6' || !$row['URL']) && $row['Views'])
			echo
				"<span class='details-separator separator-1'>" .
				", " .
				"</span>" .
				"<span class='post-views-number'>" .
					sprintf(__("%s views"), $row['Views']) .
				"</span>";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayDetails', $this, $row);
	}
	
	function displayAnnouncementInfo(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAnnouncementInfo', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAnnouncementInfo', $this, $row, $handled);
			
			return $handled;
		}
		
		if ($row['StartDate'] && $row['EndDate']) {
			echo
				sprintf(__("Starting <b>%s</b> until <b>%s</b>"),
					calendar::date($row['StartDate']),
					calendar::date($row['EndDate']));
			
		} else if ($row['StartDate']) {
			echo
				sprintf(__("Starting <b>%s</b>"),
					calendar::date($row['StartDate']));
			
		} else if ($row['EndDate']) {
			echo
				sprintf(__("Until <b>%s</b>"),
					calendar::date($row['EndDate']));
		}
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAnnouncementInfo', $this, $row);
	}
	
	function displayPictures(&$row = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayPictures', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayPictures', $this, $row, $handled);
			
			return $handled;
		}
		
		$pictures = new postPictures();
		
		if ($row) {
			$pictures->selectedOwnerID = $row['ID'];
		} else {
			$pictures->latests = true;
			$pictures->limit = $this->limit;
			$pictures->format = $this->format;
			$pictures->ignorePaging = $this->ignorePaging;
			$pictures->showPaging = $this->showPaging;
			$pictures->ajaxPaging = $this->ajaxPaging;
		}
		
		$pictures->display();
		unset($pictures);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayPictures', $this, $row);
	}
	
	function displayLatestPicture(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayLatestPicture', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayLatestPicture', $this, $row, $handled);
			
			return $handled;
		}
		
		$pictures = new postPictures();
		$pictures->selectedOwnerID = $row['ID'];
		$pictures->limit = 1;
		$pictures->showPaging = false;
		$pictures->customLink = $row['_Link'];
		$pictures->display();
		unset($pictures);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayLatestPicture', $this, $row);
	}
	
	function displayContent(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayContent', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayContent', $this, $row, $handled);
			
			return $handled;
		}
		
		$codes = new contentCodes();
		$codes->fixParagraph = true;
		
		if ($this->search)
			$codes->ignoreCodes = array(
				'languages', 'menus', 'pages', 'posts', 'blocks', 'modules', 'forms');
		
		$codes->display($row['Content']);
		unset($codes);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayContent', $this, $row);
	}
	
	function displayBody(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayBody', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayBody', $this, $row, $handled);
			
			return $handled;
		}
		
		if ($row['Pictures'])
			$this->displayPictures($row);
		
		if ($row['Content']) {
			echo
				"<div class='post-content'>";
			
			$this->displayContent($row);
		
			echo
				"</div>";
		}
		
		if (JCORE_VERSION >= '0.7') {
			echo
				"<div class='post-custom-fields'>";
			
			$this->displayCustomFields($row);
			
			echo
				"</div>";
		}
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayBody', $this, $row);
	}
	
	function displayTeaserBody(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayTeaserBody', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayTeaserBody', $this, $row, $handled);
			
			return $handled;
		}
		
		if ($row['Pictures'])
			$this->displayLatestPicture($row);
		
		if ($row['Content']) {	
			echo
				"<div class='post-content'>";
			
			$row['Content'] = posts::generateTeaser($row['Content']);
			$this->displayContent($row);
			
			echo
				"</div>";
		}
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayTeaserBody', $this, $row);
	}
	
	function displayCustomFields(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayCustomFields', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayCustomFields', $this, $row, $handled);
			
			return $handled;
		}
		
		$postsform = new postsForm();
		$postsform->load(false);
		
		$postsform->displayData($row, array(
			'Title', 'Content', 'TimeStamp', 'Path',
			'Keywords', 'URL', 'StartDate', 'EndDate',
			'OnMainPage', 'BlockID', 'PartialContent', 
			'DisplayRelatedPosts', 'EnableRating', 
			'EnableGuestRating', 'EnableComments', 
			'EnableGuestComments', 'Deactivated', 'OrderID',
			'NotSearchable', 'HideExpired'));
		
		unset($postsform);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayCustomFields', $this, $row);
	}
	
	function displayRating(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayRating', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayRating', $this, $row, $handled);
			
			return $handled;
		}
		
		$rating = new postRating();
		$rating->guestRating = $row['EnableGuestRating'];
		$rating->selectedOwnerID = $row['ID'];
		$rating->display();
		unset($rating);
			
		api::callHooks(API_HOOK_AFTER,
			'posts::displayRating', $this, $row);
	}
	
	function displayAttachments(&$row = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayAttachments', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayAttachments', $this, $row, $handled);
			
			return $handled;
		}
		
		$attachments = new postAttachments();
		
		if ($row) {
			$attachments->selectedOwnerID = $row['ID'];
		} else {
			$attachments->latests = true;
			$attachments->limit = $this->limit;
			$attachments->format = $this->format;
			$attachments->ignorePaging = $this->ignorePaging;
			$attachments->showPaging = $this->showPaging;
			$attachments->ajaxPaging = $this->ajaxPaging;
		}
		
		$attachments->display();
		unset($attachments);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayAttachments', $this, $row);
	}
	
	function displayComments(&$row = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayComments', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayComments', $this, $row, $handled);
			
			return $handled;
		}
		
		$comments = new postComments();
		
		if ($row) {
			$comments->guestComments = $row['EnableGuestComments'];
			$comments->selectedOwnerID = $row['ID'];
		} else {
			$comments->latests = true;
			$comments->limit = $this->limit;
			$comments->format = $this->format;
		}
		
		$comments->display();
		unset($comments);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayComments', $this, $row);
	}
	
	function displayKeywordsCloudLink(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayKeywordsCloudLink', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayKeywordsCloudLink', $this, $row, $handled);
			
			return $handled;
		}
		
		echo  
			"<a href='".$row['_SearchURL'] .
				(strpos($row['_SearchURL'], '?') === false?
					'?':
					'&amp;') .
				"search=tag:".urlencode('"'.trim($row['Keyword']).'"') .
				"&amp;searchin=posts' " .
				"style='font-size: ".$row['_FontPercent']."%;'>" .
				ucfirst(trim($row['Keyword'])) .
			"</a> ";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayKeywordsCloudLink', $this, $row);
	}
	
	function displayKeywordsCloud($arguments = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayKeywordsCloud', $this, $arguments);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayKeywordsCloud', $this, $arguments, $handled);
			
			return $handled;
		}
		
		$page = null;
		$byranks = false;
		
		if (preg_match('/(^|\/)byranks($|\/)/', $arguments)) {
			$arguments = preg_replace('/(^|\/)byranks($|\/)/', '\2', $arguments);
			$byranks = true;
		}
		
		if ($arguments)
			$page = sql::fetch(sql::run(
				" SELECT `ID`, `LanguageID`, `Path` FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}` " .
				" WHERE '".sql::escape($arguments)."/' LIKE CONCAT(`Path`,'/%')" .
				" AND `Deactivated` = 0" .
				($this->selectedLanguageID?
					" AND `LanguageID` = '".$this->selectedLanguageID."'":
					null) .
				" ORDER BY `Path` DESC," .
					(JCORE_VERSION < '0.9'?
						(menus::$order?
							" FIELD(`MenuID`, ".menus::$order."),":
							" `MenuID`,"):
						null) .
					" `OrderID`" .
				" LIMIT 1"));
		
		sql::run(
			" CREATE TEMPORARY TABLE `{TMPKeywordsCloud}` " .
			" (`Keyword` varchar(100) NOT NULL default ''," .
			"  `Counter` mediumint(8) unsigned NOT NULL default '0'," .
			"  `ID` tinyint(2) unsigned NOT NULL auto_increment," .
			" PRIMARY KEY  (`ID`)" .
			")");
			
		sql::run(
			" INSERT INTO `{TMPKeywordsCloud}` " .
			" SELECT `Keyword`, `Counter`, NULL FROM `{postkeywords}`" .
			($page?
				" WHERE CONCAT('|', `".(JCORE_VERSION >= '0.8'?'PageIDs':'MenuItemIDs')."`, '|')" .
					" LIKE '%|".$page['ID']."|%'":
				null) .
			" ORDER BY `Counter` DESC" .
			" LIMIT ".$this->keywordsCloudLimit);
		
		$rows = sql::run(
			" SELECT * FROM `{TMPKeywordsCloud}`" .
			" ORDER BY " .
			($byranks?
				" `Counter` DESC, ID,":
				null) .
			" `Keyword`");
			
		$hrow = sql::fetch(sql::run(
			" SELECT `Counter` FROM `{TMPKeywordsCloud}`" .
			" ORDER BY `Counter` DESC" .
			" LIMIT 1"));
		
		echo "<div class='post-keywords-cloud'>";
		
		if ($page)
			$searchurl = pages::generateLink($page);
		else
			$searchurl = modules::getOwnerURL('Search');
		
		if (!$searchurl)
			$searchurl = url::site()."index.php?";
		
		while($row = sql::fetch($rows)) {
			$row['_SearchURL'] = $searchurl;
			$row['_FontPercent'] = round(
				($row['Counter']*70/$hrow['Counter']))+30;
			
			$this->displayKeywordsCloudLink($row);
		}
		
		sql::run(" DROP TEMPORARY TABLE `{TMPKeywordsCloud}` ");
		
		echo "</div>";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayKeywordsCloud', $this, $arguments);
	}
	
	function displayCalendar($pagepath = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayCalendar', $this, $pagepath);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayCalendar', $this, $pagepath, $handled);
			
			return $handled;
		}
		
		$page = null;
		
		if ($pagepath)
			$page = sql::fetch(sql::run(
				" SELECT `ID` FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}` " .
				" WHERE '".sql::escape(trim($pagepath, '/'))."/' LIKE CONCAT(`Path`,'/%')" .
				" AND `Deactivated` = 0" .
				($this->selectedLanguageID?
					" AND `LanguageID` = '".$this->selectedLanguageID."'":
					null) .
				" ORDER BY `Path` DESC," .
					(JCORE_VERSION < '0.9'?
						(menus::$order?
							" FIELD(`MenuID`, ".menus::$order."),":
							" `MenuID`,"):
						null) .
					" `OrderID`" .
				" LIMIT 1"));
		
		$calendar = new postsCalendar(($page?$page['ID']:null));
		$calendar->display();
		unset($calendar);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayCalendar', $this, $pagepath);
	}
	
	function displayKeywordLinks(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayKeywordLinks', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayKeywordLinks', $this, $row, $handled);
			
			return $handled;
		}
		
		$words = explode(',', $row['Keywords']);
		foreach($words as $key => $word) {
			if ($key)
				echo ", ";
			
			echo  
				"<a href='".$row['_PageLink'] .
					(strpos($row['_PageLink'], '?') === false?
						'?':
						'&amp;') .
					"search=tag:".urlencode('"'.trim($word).'"') .
					"&amp;searchin=posts" .
					"' class='keyword'>" .
					ucfirst(trim($word)) .
				"</a>";
		}
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayKeywordLinks', $this, $row);
	}
	
	function displayKeywords(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayKeywords', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayKeywords', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			"<span class='keywords-title'>" .
				__("Tags").": " .
			"</span> ";
		
		$this->displayKeywordLinks($row);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayKeywords', $this, $row);
	}
	
	function displayFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayFunctions', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayFunctions', $this, $row, $handled);
			
			return $handled;
		}
		
		if ($this->selectedID == $row['ID']) {
			echo
				"<a href='".$row['_BackLink']."' class='back comment'>" .
					"<span>".
					__("Back").
					"</span>" .
				"</a>";
		
			if (JCORE_VERSION >= '0.6' && $row['URL'])
				echo
					"<a href='" .
						url::generateLink($row['URL']) .
						"' class='read-more comment'>".
						"<span>" .
						__("Read more").
						"</span>" .
					"</a>";
			
		} else {
			if ($row['PartialContent'] || 
				($this->search && !$this->selectedBlockID) ||
				(JCORE_VERSION >= '0.6' && $row['URL']))
				echo
					"<a href='" .
						(JCORE_VERSION >= '0.6' && $row['URL']?
							url::generateLink($row['URL']):
							$row['_Link']) .
						"' class='read-more comment'>".
						"<span>" .
						__("Read more").
						"</span>" .
					"</a>";
			
			if ($row['EnableComments'])
				echo
					"<a href='".$row['_Link']."#comments' class='comments comment'>".
						"<span>" .
						__("Comments") .
						"</span> " .
						"<span>" .
						"(".$row['Comments'].")" .
						"</span>" .
					"</a>";
		}
		
		if (JCORE_VERSION >= '0.7.1' &&
			$GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'])
			echo
				"<a href='".SITE_URL."admin/?path=" .
					(JCORE_VERSION >= '0.3'?
						"admin/content/postsatglance":
						(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems')."/" .
						$row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."/posts") .
						"&amp;id=".$row['ID'] .
						"&amp;search=".urlencode($row['Title']) .
					"&amp;edit=1#adminform' " .
					"class='edit comment' target='_blank'>" .
					"<span>".
					__("Edit").
					"</span>" .
				"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayFunctions', $this, $row);
	}
	
	function displayRelatedPostDate(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayRelatedPostDate', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayRelatedPostDate', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			calendar::date($row['TimeStamp']);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayRelatedPostDate', $this, $row);
	}
	
	function displayRelatedPosts(&$row) {
		if ($row['Keywords'])
			$searches = explode(',', $row['Keywords']);
		else
			$searches = explode(' ', $row['Title']);
			
		if (!count($searches))
			return;
			
		$posts = sql::run(
			" SELECT * " .
			" FROM `{posts}`" .
			" WHERE `Deactivated` = 0" .
			" AND ID != '".$row['ID']."'" .
			" AND (`Title` REGEXP '".sql::escape(implode('|', $searches))."'" .
			" OR `Keywords` REGEXP '".sql::escape(implode('|', $searches))."')" .
			" ORDER BY `ID` DESC" .
			" LIMIT 10");
			
		if (!sql::rows($posts))
			return;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayRelatedPosts', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayRelatedPosts', $this, $row, $handled);
			
			return $handled;
		}
		
		echo 
			"<div class='related-posts'>" .
				"<h3>".__("Related Posts")."</h3>" .
				"<ul class='related-posts-list'>";
		
		while($post = sql::fetch($posts)) {
			$language = null;
			
			$page = sql::fetch(sql::run(
				" SELECT `ID`, `Path`, `LanguageID` FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `ID` = '".$post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'" .
				" AND `Deactivated` = 0" .
				" AND (" .
				(JCORE_VERSION >= '0.9'?
					"`AccessibleBy` = 0 OR " .
						($GLOBALS['USER']->loginok?
							($GLOBALS['USER']->data['Admin']?
								" `AccessibleBy` IN (2, 3)":
								" `AccessibleBy` = 2"):
							" `AccessibleBy` = 1"):
					"`ViewableBy` = 0 OR " .
						($GLOBALS['USER']->loginok?
							($GLOBALS['USER']->data['Admin']?
								" `ViewableBy` IN (2, 3)":
								" `ViewableBy` = 2"):
							" `ViewableBy` = 1")) .
				" )"));
			
			if (!$page)
				continue;
			
			if ($page['LanguageID']) {
				$language = sql::fetch(sql::run(
					" SELECT `ID`, `Path` FROM `{languages}`" .
					" WHERE `ID` = '".$page['LanguageID']."'" .
					" AND `Deactivated` = 0"));
					
				if (!$language)
					continue;
			}
			
			if (SEO_FRIENDLY_LINKS) {
				$post['_Link'] = url::site().
					($language?
						$language['Path'].'/':
						null) .
					$page['Path'].'/' .
					$post['Path'];
			
			} else {
				$post['_Link'] = url::site().'index.php?' .
					($language?
						'&amp;languageid='.$language['ID']:
						null) .
					'&amp;pageid='.$page['ID'].
					'&amp;postid='.$post['ID'];
			}
		
			echo
					"<li class='related-post'>";
			
			$this->displayTitle($post);
			
			echo
						" " .
						"<span class='related-post-date comment'>";
			
			$this->displayRelatedPostDate($post);
			
			echo
						"</span>" .
					"</li>";
		}
		
		echo
				"</ul>" .
			"</div>";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayRelatedPosts', $this, $row);
	}
	
	function displayFormated(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayFormated', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayFormated', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			"<" .
			(IE_BROWSER < 9?
				"div":
				"article") .
				(!isset($this->arguments)?
					" id='post".$row['ID']."'":
					null) .
				" class='post" .
				($this->selectedID == $row['ID']?
					" selected":
					" one") .
				" post".$row['ID'] .
				" post-num".$row['_PostNumber'] .
				(isset($row['_CSSClass'])?
					" ".$row['_CSSClass']:
					null) .
				"'>";
		
		$parts = preg_split('/%([a-z0-9-_]+?)%/', $this->format, null, PREG_SPLIT_DELIM_CAPTURE);
		
		if (strpos($this->format, '%description%') === false &&
			strpos($this->format, '%body%') === false)
			$row['PartialContent'] = 1;
		
		ob_start();
		$this->displayFunctions($row);
		$links = ob_get_contents();
		ob_end_clean();
		
		foreach($parts as $part) {
			switch($part) {
				case 'title':
					echo
							"<h" .
							(JCORE_VERSION >= '0.6'?
								'1':
								'2') .
							" class='post-title'>";
							
					$this->displayTitle($row);
					
					echo
							"</h" .
							(JCORE_VERSION >= '0.6'?
								'1':
								'2') .
							">";
					break;
				
				case 'details':
					echo
						"<div class='post-details comment'>";
					
					$this->displayDetails($row);
					
					echo
						"</div>";
					break;
				
				case 'announcement':
					if ($row['StartDate'] || $row['EndDate']) {
						echo
							"<h3 class='post-announcement-dates'>";
								
						$this->displayAnnouncementInfo($row);
						
						echo
							"</h3>";
					}
					break;
				
				case 'preview':
					if ($row['Pictures'])
						$this->displayLatestPicture($row);
					break;
					
				case 'pictures':
					if ($row['Pictures'])
						$this->displayPictures($row);
					break;
				
				case 'teaser':
					if ($row['Content']) {
						echo
							"<div class='post-content'>";
						
						$row['Content'] = posts::generateTeaser($row['Content']);
						$this->displayContent($row);
						
						echo
							"</div>";
					}
					break;
					
				case 'description':
					if ($row['Content']) {
						echo
							"<div class='post-content'>";
						
						if ($row['PartialContent'] && $row['ID'] != $this->selectedID) {
							$row['Content'] = posts::generateTeaser($row['Content']);
							$this->displayContent($row);
						} else {
							$this->displayContent($row);
						}
						
						echo
							"</div>";
					}
					break;
					
				case 'customfields':
					if (JCORE_VERSION >= '0.7') {
						echo
							"<div class='post-custom-fields'>";
						
						$this->displayCustomFields($row);
						
						echo
							"</div>";
					}
					break;
					
				case 'body':
					if ($row['PartialContent'] && $row['ID'] != $this->selectedID)
						$this->displayTeaserBody($row);
					else
						$this->displayBody($row);
					break;
				
				case 'links':
					if ($links)
						echo
							"<div class='post-links'>" .
							$links .
							"<div class='clear-both'></div>" .
							"</div>";
					break;
				
				case 'link':
					echo
						(JCORE_VERSION >= '0.6' && $row['URL']?
							url::generateLink($row['URL']):
							$row['_Link']);
					break;
				
				case 'rating':
					if (isset($row['EnableRating']) && $row['EnableRating']) {
						echo
							"<div class='post-rating'>";
						
						$this->displayRating($row);
					
						echo
							"</div>";
					}
					break;
				
				case 'attachments':
					if ($row['Attachments'])
						$this->displayAttachments($row);
					break;
				
				case 'keywords':
				case 'tags':
					if (trim($row['Keywords'])) {
						echo
							"<div class='keywords post-keywords'>";
						
						$this->displayKeywords($row);
						
						echo
							"</div>";
					}
					break;
				
				case 'relatedposts':
					if (isset($row['DisplayRelatedPosts']) && $row['DisplayRelatedPosts'])
						$this->displayRelatedPosts($row);
					break;
				
				default:
					echo $part;
					break;
			}
		}
		
		echo
				"<div class='spacer bottom'></div>" .
				"<div class='separator bottom'></div>";
			
		echo
			"</" .
			(IE_BROWSER < 9?
				"div":
				"article") .
			">";
		
		if ($this->selectedID == $row['ID'] && $row['EnableComments'])
			$this->displayComments($row);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayFormated', $this, $row);
	}
	
	// This is kept only for compatibility reasons, don't use it!
	function displaySelected(&$row) {
		return $this->displayOne($row);
	}
	
	function displayPartial(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayPartial', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayPartial', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			"<" .
			(IE_BROWSER < 9?
				"div":
				"article") .
				(!isset($this->arguments)?
					" id='post".$row['ID']."'":
					null) .
				" class='post one".
				" post".$row['ID'] .
				" post-num".$row['_PostNumber'] .
				(isset($row['_CSSClass'])?
					" ".$row['_CSSClass']:
					null) .
				"'>" .
				"<h" .
				(JCORE_VERSION >= '0.6'?
					'1':
					'2') .
				" class='post-title'>";
				
		$this->displayTitle($row);
		
		echo
				"</h" .
				(JCORE_VERSION >= '0.6'?
					'1':
					'2') .
				">";
				
		echo
			"<div class='post-details comment'>";
		
		$this->displayDetails($row);
		
		echo
			"</div>";
		
		if ($row['StartDate'] || $row['EndDate']) {
			echo
				"<h3 class='post-announcement-dates'>";
					
			$this->displayAnnouncementInfo($row);
			
			echo
				"</h3>";
		}
		
		$this->displayTeaserBody($row);
		
		ob_start();
		$this->displayFunctions($row);
		$links = ob_get_contents();
		ob_end_clean();
		
		if (isset($row['EnableRating']) && $row['EnableRating']) {
			echo
				"<div class='post-rating'>";
			
			$this->displayRating($row);
		
			echo
				"</div>";
		}
		
		if ($links)
			echo
				"<div class='post-links'>" .
				$links .
				"<div class='clear-both'></div>" .
				"</div>";
		
		echo
				"<div class='spacer bottom'></div>" .
				"<div class='separator bottom'></div>";
			
		echo
			"</" .
			(IE_BROWSER < 9?
				"div":
				"article") .
			">";
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayPartial', $this, $row);
	}
	
	function displayOne(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayOne', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayOne', $this, $row, $handled);
			
			return $handled;
		}
		
		$this->incViews($row);
		
		echo
			"<" .
			(IE_BROWSER < 9?
				"div":
				"article") .
				(!isset($this->arguments)?
					" id='post".$row['ID']."'":
					null) .
				" class='post" .
				($this->selectedID == $row['ID']?
					" selected":
					null) .
				" post".$row['ID'] .
				" post-num".$row['_PostNumber'] .
				(isset($row['_CSSClass'])?
					" ".$row['_CSSClass']:
					null) .
				"'>";
			
		echo
				"<h" .
				(JCORE_VERSION >= '0.6'?
					'1':
					'2') .
				" class='post-title'>";
		
		$this->displaySelectedTitle($row);
		
		echo
				"</h" .
				(JCORE_VERSION >= '0.6'?
					'1':
					'2') .
				">";
				
		if (isset($row['EnableRating']) && $row['EnableRating']) {
			echo
				"<div class='post-rating'>";
			
			$this->displayRating($row);
		
			echo
				"</div>";
		}
		
		echo
			"<div class='post-details comment'>";
		
		$this->displayDetails($row);
		
		echo
			"</div>";
			
		if ($row['StartDate'] || $row['EndDate']) {
			echo
				"<h3 class='post-announcement-dates'>";
					
			$this->displayAnnouncementInfo($row);
			
			echo
				"</h3>";
		}
		
		$this->displayBody($row);
		
		if ($row['Attachments'])
			$this->displayAttachments($row);
		
		if (trim($row['Keywords'])) {
			echo
				"<div class='keywords post-keywords'>";
			
			$this->displayKeywords($row);
			
			echo
				"</div>";
		}
		
		ob_start();
		$this->displayFunctions($row);
		$links = ob_get_contents();
		ob_end_clean();
		
		if ($links)
			echo
				"<div class='post-links'>" .
				$links .
				"<div class='clear-both'></div>" .
				"</div>";
		
		if (isset($row['DisplayRelatedPosts']) && $row['DisplayRelatedPosts'])
			$this->displayRelatedPosts($row);
				
		echo
			"<div class='spacer bottom'></div>" .
			"<div class='separator bottom'></div>";
			
		echo
			"</" .
			(IE_BROWSER < 9?
				"div":
				"article") .
			">";
			
		if ($this->selectedID == $row['ID'] && $row['EnableComments'])
			$this->displayComments($row);
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayOne', $this, $row);
	}
	
	function displayBlockPosts($blockid) {
		if (!(int)$blockid)
			return false;
		
		$homepage = pages::getHome($this->selectedLanguageID);
		$page = pages::get($this->selectedPageID);
		
		$rows = sql::run(
			" SELECT * " .
			" FROM `{posts}`" .
			" WHERE `BlockID` = '".(int)$blockid."'" .
			" AND (`".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
				$this->selectedPageID."'" .
			(JCORE_VERSION >= '0.9'?
				" AND (`EndDate` IS NULL OR `EndDate` >= CURDATE() OR (`EndDate` < CURDATE() && `HideExpired` = 0))":
				null) .
			($homepage['ID'] == $page['ID']?
				" OR (`OnMainPage` = 1" .
				(JCORE_VERSION >= '0.9'?
					" AND `LanguageID` = '".$page['LanguageID']."'":
					null) .
				") OR (`".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = 0" .
				(JCORE_VERSION >= '0.9'?
					" AND (`LanguageID` = '".$page['LanguageID']."' OR `LanguageID` = 0)":
					null) .
				")":
				" OR ((`".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '".$homepage['ID']."'" .
					" OR (`".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = 0" .
					(JCORE_VERSION >= '0.9'?
						" AND (`LanguageID` = '".$page['LanguageID']."' OR `LanguageID` = 0)":
						null) .
					")) AND `OnMainPage` = 1) ") .
			" ) " .
			" AND `Deactivated` = 0" .
			" ORDER BY" .
			($homepage['ID'] == $page['ID']?
				" `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = 0,":
				" `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '".$page['ID']."',") .
			" `OnMainPage` DESC, `OrderID`, `StartDate`, `ID` DESC" .
			($this->limit?
				" LIMIT ".$this->limit:
				null));
		
		$this->selectedID = null;
		
		$i = 1;
		$total = sql::rows($rows);
		$pageid = 0;
			
		$cssclass = null;
		$pagelink = null;
		
		if (!$total)
			return false;
			
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayBlockPosts', $this, $blockid);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayBlockPosts', $this, $blockid, $handled);
			
			return $handled;
		}
		
		while ($row = sql::fetch($rows)) {
			if ($row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')] != $pageid) {
				$this->selectedPage = sql::fetch(sql::run(
					" SELECT * FROM `{" .
						(JCORE_VERSION >= '0.8'?
							'pages':
							'menuitems') .
						"}` " .
					" WHERE `ID` = '".$row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'"));
			
				if ($this->selectedPage['LanguageID'] && 
					$this->selectedPage['LanguageID'] != $this->selectedLanguageID)
					continue;
		
				if ($this->selectedPage['LanguageID'])	
					$this->selectedLanguage = sql::fetch(sql::run(
						" SELECT * FROM `{languages}` " .
						" WHERE `ID` = '".$this->selectedPage['LanguageID']."'"));
				
				$pageid = $row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')];
				$pagelink = $this->generatePageLink($row);
				$cssclass = $this->generateCSSClass($row);
			}
			
			$row['_PostNumber'] = $i;
			$row['_Link'] = $this->generateLink($row);
			$row['_PageLink'] = $pagelink;
			$row['_CSSClass'] = $cssclass;
			$row['_BackLink'] = $pagelink;
			
			if ($this->selectedPageID == $pageid && url::arg('postslimit')) {
				if ($this->selectedID == $row['ID'])
					$row['_BackLink'] .= 
					 	(strpos($row['_BackLink'], '?') === false?
					 		'?':
							'&amp;') .
						url::arg('postslimit');
				
				$row['_Link'] .= 
				 	(strpos($row['_Link'], '?') === false?
				 		'?':
						'&amp;') .
					url::arg('postslimit');
			}
			
			if ($i == 1)
				$row['_CSSClass'] .= ' first';
			if ($i == $total)
				$row['_CSSClass'] .= ' last';
			 
			if ($row['PartialContent'])
				$this->displayOne($row);
			else
				$this->displaySelected($row);
		
			$i++;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayBlockPosts', $this, $blockid);
		
		return $total;
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::displayArguments', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayArguments', $this, $handled);
			
			return $handled;
		}
		
		if (preg_match('/(^|\/)rand($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)rand($|\/)/', '\2', $this->arguments);
			$this->randomize = true;
		}
		
		if (preg_match('/(^|\/)latest($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)latest($|\/)/', '\2', $this->arguments);
			$this->latests = true;
			$this->ignorePaging = true;
			$this->showPaging = false;
			$this->limit = 1;
		}
		
		if (preg_match('/(^|\/)active($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)active($|\/)/', '\2', $this->arguments);
			$this->active = true;
			$this->ignorePaging = true;
			$this->showPaging = false;
			$this->limit = 1;
		}
		
		if (preg_match('/(^|\/)discussed($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)discussed($|\/)/', '\2', $this->arguments);
			$this->discussed = true;
			$this->ignorePaging = true;
			$this->showPaging = false;
			$this->limit = 1;
		}
		
		if (preg_match('/(^|\/)rated($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)rated($|\/)/', '\2', $this->arguments);
			$this->rated = true;
			$this->ignorePaging = true;
			$this->showPaging = false;
			$this->limit = 1;
		}
		
		if (preg_match('/(^|\/)([0-9]+?)\/ajax($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/\/ajax/', '', $this->arguments);
			$this->ignorePaging = true;
			$this->ajaxPaging = true;
		}
		
		if (preg_match('/(^|\/)([0-9]+?)($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)[0-9]+?($|\/)/', '\2', $this->arguments);
			$this->limit = (int)$matches[2];
		}
		
		if (preg_match('/(^|\/)format\/(.*?)($|[^<]\/[^>])/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)format\/.*?($|[^<]\/[^>])/', '\2', $this->arguments);
			$this->format = trim($matches[2]);
		}
		
		if (preg_match('/(^|\/)(keyword|tag)\/(.*?)($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)(keyword|tag)\/.*?($|\/)/', '\3', $this->arguments);
			$this->searchKeywords = trim($matches[3]);
		}
		
		if (preg_match('/(^|\/)search\/(.*?)($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)search\/.*?($|\/)/', '\2', $this->arguments);
			$this->search = trim($matches[2]);
		}
		
		if (preg_match('/(^|\/)(keywords|tags)($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)(keywords|tags)($|\/)/', '\3', $this->arguments);
			
			if ($this->limit)
				$this->keywordsCloudLimit = $this->limit;
			
			$this->displayKeywordsCloud($this->arguments);
			
			$result = true;
			api::callHooks(API_HOOK_AFTER,
				'posts::displayArguments', $this, $result);
			
			return true;
		}
		
		if (preg_match('/(^|\/)calendar($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)calendar($|\/)/', '\2', $this->arguments);
			
			$this->displayCalendar($this->arguments);
			
			$result = true;
			api::callHooks(API_HOOK_AFTER,
				'posts::displayArguments', $this, $result);
			
			return true;
		}
		
		if (preg_match('/(^|\/)pictures($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)pictures($|\/)/', '\2', $this->arguments);
			$this->ignorePaging = true;
			$this->showPaging = false;
			
			$this->displayPictures();
			
			api::callHooks(API_HOOK_AFTER,
				'posts::displayArguments', $this, $this->ignorePaging);
			
			return true;
		}
		
		if (preg_match('/(^|\/)attachments($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)attachments($|\/)/', '\2', $this->arguments);
			$this->ignorePaging = true;
			$this->showPaging = false;
			
			$this->displayAttachments();
			
			api::callHooks(API_HOOK_AFTER,
				'posts::displayArguments', $this, $this->ignorePaging);
			
			return true;
		}
		
		if (preg_match('/(^|\/)comments($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)comments($|\/)/', '\2', $this->arguments);
			$this->ignorePaging = true;
			$this->showPaging = false;
			
			$this->displayComments();
			
			api::callHooks(API_HOOK_AFTER,
				'posts::displayArguments', $this, $this->ignorePaging);
			
			return true;
		}
		
		$this->selectedPageID = null;
		$this->selectedID = null;
		
		if (!$this->arguments) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayArguments', $this);
			
			return false;
		}
		
		$page = sql::fetch(sql::run(
			" SELECT `ID`, `Path` FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}` " .
			" WHERE '".sql::escape($this->arguments)."/' LIKE CONCAT(`Path`,'/%')" .
			($this->selectedLanguageID?
				" AND `LanguageID` = '".$this->selectedLanguageID."'":
				null) .
			(JCORE_VERSION >= '0.9'?
				" AND (`AccessibleBy` = 0 OR " .
					($GLOBALS['USER']->loginok?
						($GLOBALS['USER']->data['Admin']?
							" `AccessibleBy` IN (2, 3)":
							" `AccessibleBy` = 2") .
						($GLOBALS['USER']->data['GroupID']?
							" OR `AccessibleBy` = '".(int)($GLOBALS['USER']->data['GroupID']+10)."'":
							null):
						" `AccessibleBy` = 1") .
				" )":
				" AND (`ViewableBy` = 0 OR " .
					($GLOBALS['USER']->loginok?
						($GLOBALS['USER']->data['Admin']?
							" `ViewableBy` IN (2, 3)":
							" `ViewableBy` = 2"):
						" `ViewableBy` = 1") .
				" )") .
			" AND `Deactivated` = 0" .
			" ORDER BY `Path` DESC," .
				(JCORE_VERSION < '0.9'?
					(menus::$order?
						" FIELD(`MenuID`, ".menus::$order."),":
						" `MenuID`,"):
					null) .
				" `OrderID`" .
			" LIMIT 1"));
		
		if (!$page) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayArguments', $this);
			
			return true;
		}
		
		$this->selectedPageID = $page['ID'];
		$this->arguments = preg_replace(
			'/'.preg_quote($page['Path'], '/').'(\/|$)/i', '', 
			$this->arguments, 1);
		
		if (!$this->arguments) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayArguments', $this);
			
			return false;
		}
		
		$post = sql::fetch(sql::run(
			" SELECT `ID`, `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` FROM `{posts}` " .
			" WHERE `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '".$page['ID']."'" .
			" AND '".sql::escape($this->arguments)."/' LIKE CONCAT(`Path`,'/%')" .
			" AND `Deactivated` = 0" .
			" ORDER BY `Path` DESC, `OrderID`" .
			" LIMIT 1"));
			
		if (!$post) {
			api::callHooks(API_HOOK_AFTER,
				'posts::displayArguments', $this);
			
			return true;
		}
			
		$this->selectedID = $post['ID'];
		$this->selectedPageID = $post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')];
		
		api::callHooks(API_HOOK_AFTER,
			'posts::displayArguments', $this);
		
		return false;
	}
	
	function display() {
		if ($this->displayArguments())
			return true;
		
		if ($this->selectedBlockID)
			return $this->displayBlockPosts($this->selectedBlockID);
		
		if (!$this->selectedPageID && !isset($this->arguments))
			return false;
		
		if ($this->selectedPageID) {
			$this->selectedPage = pages::get($this->selectedPageID);
			
			if ($this->selectedPage['LanguageID'])	
				$this->selectedLanguage = languages::get(
					$this->selectedPage['LanguageID']);
			
			if (!$this->limit)
				$this->limit = $this->selectedPage['Limit'];
		}
		
		if (!$this->latests) {
			if (preg_match('/(\?|&)search=/i', url::referer(true)) && $this->selectedID) {
				tooltip::display(
					"<a href='".url::referer(true)."'>".__("Back")."</a> " .
					__("to search results."),
					TOOLTIP_NOTIFICATION);
			}
			
			$paging = new paging($this->limit);
			
			if ($this->ajaxPaging) {
				$paging->ajax = true;
				$paging->otherArgs = "&amp;request=posts" .
					($this->selectedPageID?
						"/".$this->selectedPageID:
						null) .
					(isset($this->arguments)?
						"&amp;arguments=".urlencode($this->arguments) .
						($this->arguments && $this->limit?
							"/":
							null) .
						$this->limit:
						null);
			}
			
			$paging->track(strtolower(get_class($this)) .
				($this->search?
					'search':
					null) .
				'limit');
			
			if (!$this->selectedID && $this->ignorePaging)
				$paging->reset();
		}
		
		$rows = sql::run(
			$this->SQL() .
			(!$this->selectedID?
				($this->ignorePaging || $this->latests?
					($this->limit?
						" LIMIT ".$this->limit:
						null):
					" LIMIT ".$paging->limit):
				null));
		
		if (!$this->latests)
			$paging->setTotalItems(sql::count());
		
		if ($this->search && !$this->selectedID && !$this->ajaxRequest && !isset($this->arguments))
			url::displaySearch($this->search, $paging->items);
		
		$i = 1;
		$total = sql::rows($rows);
		$pageid = $this->selectedPageID;
		
		if (!$total)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'posts::display', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'posts::display', $this, $handled);
			
			return $handled;
		}
		
		if (!$this->ajaxRequest)
			echo
				"<div class='posts'>";
		
		$cssclass = null;
		$pagelink = null;
		
		while ($row = sql::fetch($rows)) {
			if ($row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')] != $pageid || !$pagelink) {
				if ($row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')] != $pageid) {
					$this->selectedPage = pages::get(
						$row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]);
					
					if ($this->selectedPage['LanguageID'])
						$this->selectedLanguage = languages::get(
							$this->selectedPage['LanguageID']);
					else
						$this->selectedLanguage = null;
					
					$pageid = $row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')];
				}
				
				$pagelink = $this->generatePageLink($row);
				$cssclass = $this->generateCSSClass($row);
			}
			
			$row['_PostNumber'] = $i;
			$row['_PageLink'] = $pagelink;
			$row['_BackLink'] = $pagelink;
			$row['_Link'] = $this->generateLink($row);
			$row['_CSSClass'] = $cssclass;
			
			if ($this->selectedPageID == $pageid && url::arg('postslimit')) {
				if ($this->selectedID == $row['ID'])
					$row['_BackLink'] .= 
					 	(strpos($row['_BackLink'], '?') === false?
					 		'?':
							'&amp;') .
						url::arg('postslimit');
				
				$row['_Link'] .= 
				 	(strpos($row['_Link'], '?') === false?
				 		'?':
						'&amp;') .
					url::arg('postslimit');
			}
			
			if ($i == 1)
				$row['_CSSClass'] .= ' first';
			if ($i == $total)
				$row['_CSSClass'] .= ' last';
			
			if ($this->format)
				$this->displayFormated($row);
			elseif ($this->search || ($row['PartialContent'] && 
				$row['ID'] != $this->selectedID))
				$this->displayPartial($row);
			else
				$this->displayOne($row);
			
			$i++;
		}
		
		if (!$this->selectedID && !$this->randomize && $this->showPaging && !$this->latests)
			$paging->display();
		
		if (!$this->ajaxRequest)
			echo
				"</div>"; //posts
		
		api::callHooks(API_HOOK_AFTER,
			'posts::display', $this);
		
		if ($this->latests)
			return true;
		
		return $paging->items;
	}
}

?>