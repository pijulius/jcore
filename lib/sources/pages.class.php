<?php

/***************************************************************************
 *            pages.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
include_once('lib/languages.class.php');
include_once('lib/modules.class.php');

define('PAGE_EVERYONE', 0);
define('PAGE_GUESTS_ONLY', 1);
define('PAGE_USERS_ONLY', 2);
define('PAGE_ADMINS_ONLY', 3);

class _pages {
	var $arguments = null;
	var $selectedID;
	var $adminPath = array(
		'admin/content/menuitems',
		'admin/content/pages');
	
	static $selected = null;
	
	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::pages', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::pages', $this, $handled);
			
			return $handled;
		}
		
		if (isset($_GET['pageid']))
			$this->selectedID = (int)$_GET['pageid'];
		
		api::callHooks(API_HOOK_AFTER,
			'pages::pages', $this);
	}
	
	static function populate() {
		if (JCORE_VERSION < '0.9')
			menus::getOrder();
		
		if (!isset($_GET['pageid']))
			$_GET['pageid'] = 0;
		
		if (isset($GLOBALS['ADMIN']) && (bool)$GLOBALS['ADMIN'])
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::populate', $_ENV);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::populate', $_ENV, $handled);
			
			return $handled;
		}
		
		$path = null;
		$selected = null;
		
		if ((int)$_GET['pageid']) {
			$page = sql::fetch(sql::run(
				" SELECT `Path` FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `ID` = '".(int)$_GET['pageid']."'"));
			
			if ($page)
				$path = $page['Path'];
			
		} else {
			$path = url::path();
		}
		
		if ($path)
			$selected = sql::fetch(sql::run(
				" SELECT * FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `Deactivated` = 0" .
				" AND `LanguageID` = '".(int)$_GET['languageid']."'" .
				" AND '".sql::escape($path)."/' LIKE CONCAT(`Path`,'/%')" .
				" ORDER BY `Path` DESC," .
					(JCORE_VERSION < '0.9'?
						(menus::$order?
							" FIELD(`MenuID`, ".menus::$order."),":
							" `MenuID`,"):
						null) .
					" `OrderID`" .
				" LIMIT 1"));
		
		if (!$selected && 
			((SEO_FRIENDLY_LINKS && !url::path()) || 
			(!SEO_FRIENDLY_LINKS && !(int)$_GET['pageid'])))
		{
			$selected = pages::getHome();
		}
		
		if (SEO_FRIENDLY_LINKS && $selected)
			url::setPath(preg_replace(
				'/'.preg_quote($selected['Path'], '/').'(\/|$)/i', '', 
				url::path(), 1));
		
		if ($selected) {
			pages::$selected = $selected;
			
			if (JCORE_VERSION >= '0.6' && $selected['SEOTitle']) {
				url::setPageTitle($selected['SEOTitle']);
			} else {
				$pageroute = array();
				
				foreach(pages::getBackTraceTree($selected['ID']) as $page)
					$pageroute[] = $page['Title'];
					
				url::addPageTitle(implode(' / ', $pageroute));
			}
			
			if (JCORE_VERSION >= '0.6' && $selected['SEODescription'])
				url::setPageDescription($selected['SEODescription']);
			
			if (JCORE_VERSION >= '0.6' && $selected['SEOKeywords'])
				url::setPageKeywords($selected['SEOKeywords']);
			
			if (JCORE_VERSION < '0.8')
				$_GET['menuid'] = $selected['ID'];
			
			$_GET['pageid'] = $selected['ID'];
			
			api::callHooks(API_HOOK_AFTER,
				'pages::populate', $_ENV, $selected['ID']);
			
			return;
		}
		
		if (JCORE_VERSION < '0.8')
			$_GET['menuid'] = 0;
		
		url::addPageTitle(__('Address Not Found'));
		$_GET['pageid'] = 0;
		
		api::callHooks(API_HOOK_AFTER,
			'pages::populate', $_ENV);
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::countAdminItems', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::countAdminItems', $this, $handled);
			
			return $handled;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}`" .
			" LIMIT 1"));
		
		api::callHooks(API_HOOK_AFTER,
			'pages::countAdminItems', $this, $row['Rows']);
		
		return $row['Rows'];
	}
	
	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::setupAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::setupAdmin', $this, $handled);
			
			return $handled;
		}
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Page'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Moving Posts'), 
			'?path=admin/content/postshandling');
		favoriteLinks::add(
			__('Content Files'), 
			'?path=admin/content/contentfiles');
		
		api::callHooks(API_HOOK_AFTER,
			'pages::setupAdmin', $this);
	}
	
	function setupAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::setupAdminForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::setupAdminForm', $this, $form, $handled);
			
			return $handled;
		}
		
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 250px;');
		
		$form->add(
			__('Sub Page of'),
			(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID'),
			FORM_INPUT_TYPE_SELECT);
		$form->setValueType(FORM_VALUE_TYPE_INT);
			
		$form->addValue('', '');
		
		if (JCORE_VERSION < '0.9') {
			$form->add(
				__('Menu Block'),
				'MenuID',
				FORM_INPUT_TYPE_SELECT);
			$form->setValueType(FORM_VALUE_TYPE_INT);
			
			$rows = sql::run(
				" SELECT * FROM `{menus}`" .
				" ORDER BY" .
				(JCORE_VERSION >= '0.7'?
					" `OrderID`,":
					null) .
				" `ID`");
			while($row = sql::fetch($rows))
				$form->addValue($row['ID'], $row['Title']);
		}
		
		if ($languages = languages::get()) {
			$form->add(
				__('Language'),
				'LanguageID',
				FORM_INPUT_TYPE_SELECT);
			$form->setValueType(FORM_VALUE_TYPE_INT);
				
			$form->addValue('', '');
			
			while($language = sql::fetch($languages))
				$form->addValue($language['ID'], 
					$language['Title']);
		}
		
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
		
		if (modules::count()) {
			$form->add(
				__('Modules'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
		
			$modules = modules::get();
			while($module = sql::fetch($modules)) {
				$modulename = preg_replace('/[^a-zA-Z0-9\_\-]/', '', 
					$module['Name']);
				
				if (!$modulename || !modules::load($modulename))
					continue;
				
				$form->add(
					modules::getTitle($module['Name']),
					'Modules['.$module['ID'].']',
					FORM_INPUT_TYPE_CHECKBOX,
					false,
					$module['ID']);
				$form->setValueType(FORM_VALUE_TYPE_ARRAY);
			
				$form->addAdditionalText(
					"<span class='comment'>" .
					modules::getDescription($module['Name']).
					"</span>");
				
				if (JCORE_VERSION >= '0.3') {
					if (method_exists($modulename, 'getTree') ||
						method_exists($modulename, 'getOptions')) 
					{
						$mtree = array();
						$options = array();
						$$modulename = new $modulename();
						
						if (method_exists($modulename, 'getTree'))
							$mtree = $$modulename->getTree();
						
						if (method_exists($modulename, 'getOptions'))
							$options = $$modulename->getOptions();
						
						unset($$modulename);
						
						if ($options && is_array($options) && count($options))
							$tree = array_merge($options, $mtree);
						else
							$tree = $mtree;
						
						if ($tree && is_array($tree) && count($tree)) {
							$form->add(
								'',
								'ModulesItem['.$module['ID'].']',
								FORM_INPUT_TYPE_SELECT);
							$form->setValueType(FORM_VALUE_TYPE_ARRAY);
							
							$form->addValue('', '');
							
							foreach($tree as $row) {
								$form->addValue(
									$row['ID'], 
									(isset($row['SubItemOfID']) && 
									 $row['SubItemOfID']?
										str_replace(' ', '&nbsp;', 
											str_pad('', $row['PathDeepnes']*4, ' ')).
										"|- ":
										null) .
									$row['Title']);
							}
						}
					}
				}
			}
			
			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER);
		}
		
		if (JCORE_VERSION >= '0.8') {
			$dforms = dynamicForms::getForm(null, false);
			
			if (sql::rows($dforms)) {
				$form->add(
					__('Dynamic Forms'),
					null,
					FORM_OPEN_FRAME_CONTAINER);
			
				$form->add(
					__('Display Form(s)'),
					'DynamicForms',
					FORM_INPUT_TYPE_CHECKBOX,
					false);
				$form->setValueType(FORM_VALUE_TYPE_ARRAY);
				
				while($dform = sql::fetch($dforms)) {
					$form->addValue(
						$dform['ID'],
						__($dform['Title'])." <span class='comment'>(" .
							$dform['FormID'].")</span><br />");
				}
				
				$form->add(
					null,
					null,
					FORM_CLOSE_FRAME_CONTAINER);
			}
		}
		
		$form->add(
			__('Display Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Path'),
			'Path',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 200px;');
		
		if (JCORE_VERSION < '0.9') {
			$form->add(
				__('Link to URL'),
				'Link',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 300px;');
			$form->setValueType(FORM_VALUE_TYPE_URL);
			$form->setTooltipText(__("e.g. http://domain.com"));
		}
		
		if (JCORE_VERSION >= '0.8') {
			$form->add(
				__('Post Tags'),
				'PostKeywords',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 250px;');
			$form->setTooltipText(__("e.g. oranges, lemons, limes"));
			$form->addAdditionalText(
				"<br />" .
				"<a href='".url::uri('request, keywords') .
					"&amp;request=".url::path() .
					"&amp;keywords=1' " .
					"class='posts-add-keyword add-link ajax-content-link' " .
					"title='".htmlspecialchars(__("Add Tag"), ENT_QUOTES)."'>" .
					__("Add Tag") .
				"</a>" .
				" <span class='comment'>" .
				__("(automatically show posts with these tags)") .
				"</span>");
		}
		
		if (JCORE_VERSION >= '0.9') {
			$form->add(
				__('Accessible by'),
				'AccessibleBy',
				FORM_INPUT_TYPE_SELECT);
			$form->setValueType(FORM_VALUE_TYPE_INT);
				
			$form->addValue(
				PAGE_EVERYONE, $this->access2Text(PAGE_EVERYONE));
			$form->addValue(
				PAGE_USERS_ONLY, $this->access2Text(PAGE_USERS_ONLY));
			$form->addValue(
				PAGE_ADMINS_ONLY, $this->access2Text(PAGE_ADMINS_ONLY));
			
			$ugroups = userGroups::get();
			
			while($ugroup = sql::fetch($ugroups))
				$form->addValue(
					$ugroup['ID']+10, $ugroup['GroupName']);
			
		} else {
			$form->add(
				__('Viewable by'),
				'ViewableBy',
				FORM_INPUT_TYPE_SELECT);
			$form->setValueType(FORM_VALUE_TYPE_INT);
				
			$form->addValue(
				PAGE_EVERYONE, $this->access2Text(PAGE_EVERYONE));
			$form->addValue(
				PAGE_GUESTS_ONLY, $this->access2Text(PAGE_GUESTS_ONLY));
			$form->addValue(
				PAGE_USERS_ONLY, $this->access2Text(PAGE_USERS_ONLY));
			$form->addValue(
				PAGE_ADMINS_ONLY, $this->access2Text(PAGE_ADMINS_ONLY));
		}
		
		if (JCORE_VERSION < '0.9') {
			$form->add(
				__('Hidden'),
				'Hidden',
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
				__('SEO Options'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
			
			$form->add(
				__('Page Title'),
				'SEOTitle',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 250px;');
			
			$form->add(
				__('Description'),
				'SEODescription',
				FORM_INPUT_TYPE_TEXTAREA);
			$form->setStyle('width: ' .
				(JCORE_VERSION >= '0.7'?
					'90%':
					'300px') .
				'; height: 100px;');
			
			$form->add(
				__('Keywords'),
				'SEOKeywords',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 350px;');
			
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
			'pages::setupAdminForm', $this, $form);
	}
	
	function verifyAdmin(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::verifyAdmin', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::verifyAdmin', $this, $form, $handled);
			
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
					'pages::verifyAdmin', $this, $form);
				return false;
			}
			
			foreach((array)$orders as $oid => $ovalue) {
				if (JCORE_VERSION >= '0.9') {
					$page = sql::fetch(sql::run(
						" SELECT `OrderID` FROM `{pages}`" .
						" WHERE `ID` = '".(int)$oid."'" .
						($this->userPermissionIDs?
							" AND `ID` IN (".$this->userPermissionIDs.")":
							null)));
					
					if ($page)
						sql::run(
							" UPDATE `{menuitems}` " .
							" SET `OrderID` = '".(int)$ovalue."'" .
							" WHERE `PageID` = '".(int)$oid."'" .
							" AND `OrderID` = '".$page['OrderID']."'");
				}
				
				sql::run(
					" UPDATE `{" .
						(JCORE_VERSION >= '0.8'?
							'pages':
							'menuitems') .
						"}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'" .
					($this->userPermissionIDs?
						" AND `ID` IN (".$this->userPermissionIDs.")":
						null));
			}
			
			tooltip::display(
				__("Pages have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'pages::verifyAdmin', $this, $form, $reorder);
			
			return true;
		}
		
		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'pages::verifyAdmin', $this, $form);
				return false;
			}
			
			$result = $this->delete($id);
			
			if ($result)
				tooltip::display(
					__("Page and all its subpages have been successfully deleted."),
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'pages::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'pages::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if (!$form->get('Path')) {
			$path = '';
			
			if ($form->get((JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID'))) {
				$subpageof = sql::fetch(sql::run(
					" SELECT `Path` FROM `{" .
						(JCORE_VERSION >= '0.8'?
							'pages':
							'menuitems') .
						"}`" .
					" WHERE `ID` = ".(int)$form->get((JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID'))));
				
				$path .= $subpageof['Path'].'/';
			} 
			
			$path .= url::genPathFromString($form->get('Title'));
			
			$form->set('Path', $path);
		}
				
		if ($edit && $form->get((JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID'))) {
			foreach(pages::getBackTraceTree($form->get((JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID'))) as $item) {
				if ($item['ID'] == $id) {
					tooltip::display(
						__("Page cannot be subpage of itself!"),
						TOOLTIP_ERROR);
					
					api::callHooks(API_HOOK_AFTER,
						'pages::verifyAdmin', $this, $form);
					
					return false;
				}
			}
		}
			
		if (JCORE_VERSION >= '0.9' && $duplicatepage = sql::fetch(sql::run(
			" SELECT * FROM `{pages}`" .
			" WHERE `Path` = '".sql::escape($form->get('Path'))."'" .
			" AND `LanguageID` = '".(int)$form->get('LanguageID')."'" .
			($edit?" AND `ID` != '".(int)$id."'":null)))) 
		{
			tooltip::display(
				sprintf(__("A page with the path \"%s\" already exists!"),
					$form->get('Path'))." " .
				"<a href='".$this->generateLink($duplicatepage)."' target='_blank'>" .
					__("View Page") .
				"</a>" .
				" - " .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$duplicatepage['ID']."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'pages::verifyAdmin', $this, $form);
			
			return false;
		}
			
		if ($edit) {
			$result = $this->edit($id, $form->getPostArray());
			
			if ($result) {
				$page = sql::fetch(sql::run(
					" SELECT * FROM `{" .
						(JCORE_VERSION >= '0.8'?
							'pages':
							'menuitems') .
						"}`" .
					" WHERE `ID` = '".(int)$id."'"));
				
				tooltip::display(
					__("Page has been successfully updated.")." " .
					"<a href='".$this->generateLink($page)."' target='_blank'>" .
						__("View Page") .
					"</a>" .
					" - " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);
			}
			
			api::callHooks(API_HOOK_AFTER,
				'pages::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if ($this->userPermissionIDs) {
			api::callHooks(API_HOOK_AFTER,
				'pages::verifyAdmin', $this, $form);
			
			return false;
		}
		
		$newid = $this->add($form->getPostArray());
		
		if ($newid) {
			$page = sql::fetch(sql::run(
				" SELECT * FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `ID` = '".(int)$newid."'"));
			
			tooltip::display(
				__("Page has been successfully created.")." " .
					"<a href='".$this->generateLink($page)."' target='_blank'>" .
						__("View Page") .
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
			'pages::verifyAdmin', $this, $form, $newid);
		
		return $newid;
	}
	
	function displayAdminListHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayAdminListHeader', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayAdminListHeader', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Path / Link")."</span></th>";
		
		if (JCORE_VERSION >= '0.9')
			echo
				"<th style='text-align: right;'><span class='nowrap'>".
					__("Accessible by")."</span></th>";
		else
			echo
				"<th style='text-align: right;'><span class='nowrap'>".
					__("Hidden")."</span></th>" .
				"<th style='text-align: right;'><span class='nowrap'>".
					__("Viewable by")."</span></th>";
		
		echo
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Limit")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayAdminListHeader', $this);
	}
	
	function displayAdminListHeaderOptions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayAdminListHeaderOptions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayAdminListHeaderOptions', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<th><span class='nowrap'>".
				__("Posts")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayAdminListHeaderFunctions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayAdminListHeaderFunctions', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayAdminListItem', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayAdminListItem', $this, $row, $handled);
			
			return $handled;
		}
		
		$tooltiptxt = null;
			
		if (JCORE_VERSION >= '0.9' && $row['LayoutID'] &&
			$layout = layouts::get($row['LayoutID']))
		{
			$tooltiptxt .= 
				"<b>".__("Layout")."</b>" .
				"<ul>" .
					"<li>".$layout['Title']."</li>" .
				"</ul>";
		}
		
		if (JCORE_VERSION >= '0.8' && $row['PostKeywords'])
			$tooltiptxt .= 
				"<b>".__("Post Tags")."</b>" .
				"<ul>" .
					"<li>".$row['PostKeywords']."</li>" .
				"</ul>";
		
		if (JCORE_VERSION >= '0.6' && ($row['SEOTitle'] ||
			$row['SEODescription'] || $row['SEOKeywords']))
			$tooltiptxt .= 
				"<b>".__("SEO Options")."</b>" .
				"<ul>" .
				($row['SEOTitle']?
					"<li>".$row['SEOTitle']."</li>":
					null) .
				($row['SEODescription']?
					"<li>".$row['SEODescription']."</li>":
					null) .
				($row['SEOKeywords']?
					"<li>".$row['SEOKeywords']."</li>":
					null) .
				"</ul>";
		
		$moduleids = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(`ModuleID` SEPARATOR ',') AS `ModuleIDs` " .
			" FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pagemodules':
					'menuitemmodules') .
				"}` " .
			" WHERE `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
				$row['ID']."'" .
			" GROUP BY `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."`" .
			" LIMIT 1"));
		
		if ($moduleids) {
			$modules = sql::run(
				" SELECT * FROM `{modules}`" .
				" WHERE `ID` IN (".$moduleids['ModuleIDs'].")" .
				" ORDER BY `Name`");
			
			$tooltiptxt .=
				"<b>".__("Modules")."</b>" .
				"<ul>";
		
			while($module = sql::fetch($modules))
				$tooltiptxt .= 
					"<li><b>" .
						modules::getTitle($module['Name']) .
						"</b><br />" .
						modules::getDescription($module['Name']) .
					"</li>";
			
			$tooltiptxt .= 
				"</ul>";
		}
		
		if (JCORE_VERSION >= '0.8') {
			$dformids = sql::fetch(sql::run(
				" SELECT GROUP_CONCAT(`FormID` SEPARATOR ',') AS `FormIDs` " .
				" FROM `{pageforms}` " .
				" WHERE `PageID` = '".$row['ID']."'" .
				" GROUP BY `PageID`" .
				" LIMIT 1"));
			
			if ($dformids) {
				$dforms = sql::run(
					" SELECT * FROM `{dynamicforms}`" .
					" WHERE `ID` IN (".$dformids['FormIDs'].")" .
					" ORDER BY `FormID`, `ID`");
				
				$tooltiptxt .=
					"<b>".__("Dynamic Forms")."</b>" .
					"<ul>";
			
				while($dform = sql::fetch($dforms))
					$tooltiptxt .= 
						"<li><b>" .
							$dform['Title'] .
							"</b> (".$dform['FormID'].")" .
						"</li>";
				
				$tooltiptxt .= 
					"</ul>";
			}
		}
		
		echo 
			"<td>" .
				"<input type='text' name='orders[".$row['ID']."]' " .
					"value='".$row['OrderID']."' " .
					"class='order-id-entry' tabindex='1' />" .
			"</td>" .
			"<td class='auto-width" .
				($tooltiptxt && JCORE_VERSION < '0.6'?
					" qtip":
					null) .
				"' " .
				($tooltiptxt?
					"title='".
						htmlspecialchars(
							"<div style='text-align: left;'>" .
								$tooltiptxt .
							"</div>", ENT_QUOTES) .
					"' ":
					null) .
				($row['Deactivated']?
					"style='text-decoration: line-through;' ":
					null).
				">" .
				"<div " .
					(!$row[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]?
						"class='bold' ":
						null).
					">" .
				$row['Title'] .
				"</div>" .
				"<div class='comment' style='padding-left: 10px;'>" .
				(JCORE_VERSION < '0.9' && $row['Link']?
					"<a href='".url::generateLink($row['Link']) .
						"' target='_blank'>":
					null) .
				$row['Path'] .
				(JCORE_VERSION < '0.9' && $row['Link']?
					"</a>":
					null) .
				"</div>" .
			"</td>";
		
		if (JCORE_VERSION >= '0.9')
			echo
				"<td style='text-align: right;'>" .
					"<span class='nowrap'>" .
					($row['AccessibleBy']?
						$this->access2Text($row['AccessibleBy']):
						null) .
					"</span>" .
				"</td>";
		else
			echo
				"<td style='text-align: right;'>" .
					($row['Hidden']?__('Yes'):'&nbsp;').
				"</td>" .
				"<td style='text-align: right;'>" .
					"<span class='nowrap'>" .
					($row['ViewableBy']?
						$this->access2Text($row['ViewableBy']):
						null) .
					"</span>" .
				"</td>";
		
		echo
			"<td style='text-align: right;'>" .
				($row['Limit']?
					$row['Limit']:
					null) .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListItemOptions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayAdminListItemOptions', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayAdminListItemOptions', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			"<td align='center'>" .
				"<a class='admin-link posts' " .
					"title='".htmlspecialchars(__("Posts"), ENT_QUOTES) .
					(JCORE_VERSION >= '0.5'?
						" (".$row['Posts'].")":
						null) .
						"' " .
					"href='".url::uri('id, edit, delete, path') .
					"&amp;path=".admin::path()."/".$row['ID']."/posts'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $row['Posts'])
			counter::display($row['Posts']);
		
		echo
				"</a>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayAdminListItemFunctions', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayAdminListItemFunctions', $this, $row, $handled);
			
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
			'pages::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminListFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayAdminListFunctions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayAdminListFunctions', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<input type='submit' name='reordersubmit' value='".
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayAdminListFunctions', $this);
	}
	
	function displayAdminListLanguages($pageid, $language) {
		ob_start();
		$this->displayAdminListItems($pageid, 0, false, $language);
		$pages = ob_get_contents();
		ob_end_clean();
		
		if (!$pages)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayAdminListLanguages', $this, $pageid, $language);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayAdminListLanguages', $this, $pageid, $language, $handled);
			
			return $handled;
		}
		
		echo 
		"<div tabindex='0' class='fc" . 
			form::fcState('fcl'.($pageid?$pageid:null).$language['ID'], true) . 
			"'>" .
			"<a class='fc-title' name='fcl".($pageid?$pageid:null).$language['ID']."'>" .
				stripcslashes($language['Title']) .
				(isset($language['Path']) && $language['Path']?
					" (".$language['Path'].")":
					null) .
			"</a>" .
			"<div class='fc-content'>" .
				$pages .
			"</div>" .
		"</div>";
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayAdminListLanguages', $this, $pageid, $language);
		
		return true;
	}
	
	function displayAdminListItems($pageid = 0, $subpageof = 0, $rowpair = false, $language = null) {
		if ($this->userPermissionIDs && $subpageof)
			return false;
		
		if (!$subpageof)
			$handled = api::callHooks(API_HOOK_BEFORE,
				'pages::displayAdminListItems', $this, $pageid);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
					'pages::displayAdminListItems', $this, $pageid, $handled);
			
			return $handled;
		}
		
		$rows = sql::run(
			" SELECT * FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}`" .
			" WHERE 1" .
			(JCORE_VERSION < '0.9'?
				" AND `MenuID` = '".(int)$pageid."'":
				null) .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				((int)$subpageof?
					" AND `".(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')."` = '".(int)$subpageof."'":
					" AND `".(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')."` = 0")) .
			($language?
				" AND `LanguageID` = '".$language['ID']."'":
				null) .
			" ORDER BY `OrderID`");
		
		if (!sql::rows($rows)) {
			if (!$subpageof)
				api::callHooks(API_HOOK_AFTER,
					'pages::displayAdminListItems', $this, $pageid);
			
			return false;
		}
			
		if ($subpageof) {
			echo 
				"<tr".($rowpair?" class='pair'":NULL).">" .
					"<td></td>" .
					"<td colspan='7' class='auto-width nopadding'>";
		}
				
		echo "<table class='list' cellpadding='0' cellspacing='0'>";
		
		if (!$subpageof) {
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
				"<tr".($i%2?" class='pair'":NULL).">";
				
			$this->displayAdminListItem($row);
			$this->displayAdminListItemOptions($row);
			
			if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
				$this->displayAdminListItemFunctions($row);
			
			echo
				"</tr>";
			
			$this->displayAdminListItems($pageid, $row['ID'], $i%2);
			
			$i++;
		}
		
		if ($subpageof) {
			echo 
				"</table>" .
				"</td>" .
				"</tr>";
		} else {
			echo 
				"</tbody>" .
				"</table>";
		}
		
		if (!$subpageof)
			api::callHooks(API_HOOK_AFTER,
				'pages::displayAdminListItems', $this, $pageid);
		
		return true;
	}
	
	function displayAdminList(&$rows, &$languages = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayAdminList', $this, $rows, $languages);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayAdminList', $this, $rows, $languages, $handled);
			
			return $handled;
		}
		
		echo
			"<form action='".url::uri('edit, delete')."' method='post'>" .
				"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";
		
		$pagesfound = false;
		
		if (JCORE_VERSION >= '0.9') {
			if (sql::rows($rows)) {
				$language['ID'] = 0;
				$language['Title'] = __('No Language Defined');
				$pagesfound = $this->displayAdminListLanguages(0, $language);
				
			} else {
				$pagesfound = $this->displayAdminListItems(0);
			}
		}
				
		while($row = sql::fetch($rows)) {
			if (JCORE_VERSION >= '0.9') {
				if ($this->displayAdminListLanguages(0, $row))
					$pagesfound = true;
				
				continue;
			}
			
			ob_start();
			if ($languages) {
				if (sql::count(
					" SELECT COUNT(`ID`) AS `Rows` " .
					" FROM `{" .
						(JCORE_VERSION >= '0.8'?
							'pages':
							'menuitems') .
						"}` " .
					" WHERE 1" .
					(JCORE_VERSION < '0.9'?
						" AND `MenuID` = '".$row['ID']."' ":
						null) .
					($this->userPermissionIDs?
						" AND `ID` IN (".$this->userPermissionIDs.")":
						null) .
					" AND `LanguageID` = 0"))
				{
					$language['ID'] = 0;
					$language['Title'] = __('No Language Defined');
					$this->displayAdminListLanguages($row['ID'], $language);
				}
				
				if (sql::rows($languages))
					sql::seek($languages, 0);
				
				while($language = sql::fetch($languages))
					$this->displayAdminListLanguages($row['ID'], $language);
				
			} else {	
				$this->displayAdminListItems($row['ID']);
			}
			
			$pages = ob_get_contents();
			ob_end_clean();
			
			if (!$pages)
				continue;
			
			echo 
				"<div tabindex='0' class='fc" .
					form::fcState('fcm'.$row['ID'], true) .
					"'>" .
					"<a class='fc-title' name='fcm".$row['ID']."'>".
						$row['Title'] .
					"</a>" .
					"<div class='fc-content'>" .
						$pages .
					"</div>" .
				"</div>";
			
			$pagesfound = true;
		}
		
		if (!$pagesfound)
			tooltip::display(
				__("No pages found."),
				TOOLTIP_NOTIFICATION);
		else
			echo "<br />";
		
		if ($pagesfound && $this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			$this->displayAdminListFunctions();
			
			echo
				"<div class='clear-both'></div>" .
				"<br />";
		}
				
		echo
			"</form>";
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayAdminList', $this, $rows, $languages);
	}
	
	function displayAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayAdminForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayAdminForm', $this, $form, $handled);
			
			return $handled;
		}
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayAdminTitle', $this, $ownertitle);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayAdminTitle', $this, $ownertitle, $handled);
			
			return $handled;
		}
		
		admin::displayTitle(
			__('Pages Administration'),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayAdminDescription', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayAdminDescription', $this, $handled);
			
			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'pages::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayAdmin', $this, $handled);
			
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
				" SELECT `Title` FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `ID` = '".$id."'" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null)));
			
			url::displayConfirmation(
				'<b>'.__('Delete').'?!</b> "'.$selected['Title'].'"');
		}
		
		$form = new form(
				($edit?
					__("Edit Page"):
					__("New Page")),
				'neweditpage');
					
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
		
		foreach(pages::getTree() as $row)
			$form->addValue((JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID'),
				$row['ID'], 
				($row[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]?
					str_replace(' ', '&nbsp;', 
						str_pad('', $row['PathDeepnes']*4, ' ')).
					"|- ":
					null) .
				$row['Title']);
		
		$form->groupValues((JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID'), array('0'));
		
		if (JCORE_VERSION >= '0.9') {
			$languages = languages::get();
			$this->displayAdminList($languages);
			
		} else {
			$rows = sql::run(
				" SELECT * FROM `{menus}`" .
				" ORDER BY" .
				(JCORE_VERSION >= '0.7'?
					" `OrderID`,":
					null) .
				" `ID`");
			
			$languages = languages::get();
			
			if (sql::rows($rows))
				$this->displayAdminList($rows, $languages); 
			else
				tooltip::display(
						__("No menu blocks found.")." " .
						__("Please go to Admin -> Site Layout and Functionality -> Menu Blocks " .
							"and create at least one menu block to put pages in."),
						TOOLTIP_NOTIFICATION);
		}
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || 
			($edit && in_array($id, explode(',', $this->userPermissionIDs)))) &&
			(JCORE_VERSION >= '0.9' || sql::rows($rows)))
		{
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{" .
						(JCORE_VERSION >= '0.8'?
							'pages':
							'menuitems') .
						"}`" .
					" WHERE `ID` = '".$id."'"));
				
				$form->setValues($selected);
				
				$modules = sql::run(
					" SELECT * FROM `{" .
						(JCORE_VERSION >= '0.8'?
							'pagemodules':
							'menuitemmodules') .
						"}`" .
					" WHERE `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
						$selected['ID']."'");
				
				while($module = sql::fetch($modules)) {
					$form->setValue(
							'Modules['.$module['ModuleID'].']', 
							$module['ModuleID']);
					
					if (isset($module['ModuleItemID']))
						$form->setValue(
								'ModulesItem['.$module['ModuleID'].']', 
								$module['ModuleItemID']);
				}
				
				if (JCORE_VERSION >= '0.8') {
					$dform = sql::fetch(sql::run(
						" SELECT GROUP_CONCAT(`FormID` SEPARATOR ',') AS `FormIDs`" .
						" FROM `{pageforms}`" .
						" WHERE `PageID` = '".$selected['ID']."'"));
					
					if ($dform['FormIDs'])
						$form->setValue(
							'DynamicForms', 
							explode(',', $dform['FormIDs']));
				}
			}
			
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form);
		
		echo 
			"</div>";	//admin-content
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayAdmin', $this);
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::add', $this, $values);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::add', $this, $values, $handled);
			
			return $handled;
		}
		
		if (!isset($values['LanguageID']))
			$values['LanguageID'] = null;
			
		if ($values['OrderID'] == '') {
			$row = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}` " .
				" WHERE `".(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')."` = '" .
					(int)$values[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]."'" .
				" ORDER BY `OrderID` DESC"));
			
			$values['OrderID'] = (int)$row['OrderID']+1;
			
		} else {
			sql::run(
				" UPDATE `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}` SET " .
				" `OrderID` = `OrderID` + 1" .
				" WHERE `".(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')."` = '" .
					(int)$values[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]."'" .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		if ((int)$values[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]) {
			$parentpage = sql::fetch(sql::run(
				" SELECT * FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `ID` = '" .
					(int)$values[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]."'"));
			
			if ($parentpage['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;
			
			if (JCORE_VERSION >= '0.9') {
				if ($parentpage['AccessibleBy'] && !$values['AccessibleBy'])
					$values['AccessibleBy'] = (int)$parentpage['AccessibleBy'];
			} else {
				if ($parentpage['ViewableBy'] && !$values['ViewableBy'])
					$values['ViewableBy'] = (int)$parentpage['ViewableBy'];
				
				if ($parentpage['Hidden'] && !$values['Hidden'])
					$values['Hidden'] = true;
			}
			
			$values['LanguageID'] = $parentpage['LanguageID'];
			
			if (JCORE_VERSION < '0.9')
				$values['MenuID'] = $parentpage['MenuID'];
		}
		
		$newid = sql::run(
			" INSERT INTO `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}` SET " .
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			(JCORE_VERSION < '0.9'?
				" `Link` = '".
					sql::escape($values['Link'])."'," .
				" `MenuID` = '".
					(int)$values['MenuID']."',":
				null) .
			" `LanguageID` = '".
				(int)$values['LanguageID']."'," .
			(JCORE_VERSION >= '0.9' && isset($values['LayoutID'])?
				" `LayoutID` = '".
					(int)$values['LayoutID']."',":
				null) .
			(JCORE_VERSION >= '0.6'?
				" `SEOTitle` = '".
					sql::escape($values['SEOTitle'])."'," .
				" `SEODescription` = '".
					sql::escape($values['SEODescription'])."'," .
				" `SEOKeywords` = '".
					sql::escape($values['SEOKeywords'])."',":
				null) .
			(JCORE_VERSION >= '0.8'?
				" `PostKeywords` = '".
					sql::escape($values['PostKeywords'])."',":
				null) .
			(JCORE_VERSION >= '0.9'?
				" `AccessibleBy` = '".
					(int)$values['AccessibleBy']."',":
				" `ViewableBy` = '".
					(int)$values['ViewableBy']."',") .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			(JCORE_VERSION < '0.9'?
				" `Hidden` = '".
					($values['Hidden']?
						'1':
						'0').
					"',":
				null) .
			" `".(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')."` = '".
				(int)$values[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]."'," .
			" `Limit` = '".
				(int)$values['Limit']."'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
		
		if (!$newid) {
			tooltip::display(
				sprintf(__("Page couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'pages::add', $this, $values);
			
			return false;
		}
		
		if (JCORE_VERSION >= '0.9') {
			$menus = sql::run(
				" SELECT * FROM `{menus}`" .
				" WHERE `IncludeNewPages` = 1");
			
			$menuitems = new menuItems();
			
			while($menu = sql::fetch($menus)) {
				if (!$values['SubPageOfID']) {
					$menuitems->add(array(
						'Title' => $values['Title'],
						'Path' => $values['Path'],
						'LanguageID' => $values['LanguageID'],
						'Deactivated' => $values['Deactivated'],
						'OrderID' => $values['OrderID'],
						'MenuID' => $menu['ID'],
						'PageID' => $newid,
						'SubMenuItemOfID' => 0,
						'ViewableBy' => MENU_EVERYONE,
						'Link' => ''));
					
					continue;
				}
				
				$items = sql::run(
					" SELECT * FROM `{menuitems}`" .
					" WHERE `MenuID` = '".$menu['ID']."'" .
					" AND `PageID` = '".(int)$values['SubPageOfID']."'");
				
				while($item = sql::fetch($items)) {
					$menuitems->add(array(
						'Title' => $values['Title'],
						'Path' => $values['Path'],
						'LanguageID' => $values['LanguageID'],
						'Deactivated' => $values['Deactivated'],
						'OrderID' => $values['OrderID'],
						'MenuID' => $menu['ID'],
						'PageID' => $newid,
						'SubMenuItemOfID' => $item['ID'],
						'ViewableBy' => MENU_EVERYONE,
						'Link' => ''));
				}
			}
			
			unset($menuitems);
		}
		
		if (isset($values['Modules']) && is_array($values['Modules'])) {
			foreach($values['Modules'] as $moduleid) {
				sql::run(
					" INSERT INTO `{" .
						(JCORE_VERSION >= '0.8'?
							'pagemodules':
							'menuitemmodules') .
						"}` SET " .
					" `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
						$newid."'," .
					" `ModuleID` = '".(int)$moduleid."'" .
					(JCORE_VERSION >= '0.3'?
						", `ModuleItemID` = '".
							(isset($values['ModulesItem'][$moduleid])?
								(int)$values['ModulesItem'][$moduleid]:
								0) .
							"'":
						null));
			}
		}
		
		if (isset($values['DynamicForms']) && is_array($values['DynamicForms'])) {
			foreach($values['DynamicForms'] as $dformid) {
				sql::run(
					" INSERT INTO `{pageforms}` SET " .
					" `PageID` = '".$newid."'," .
					" `FormID` = '".(int)$dformid."'");
			}
		}
		
		if (!$values['Deactivated'] && 
			(JCORE_VERSION >= '0.9' || $values['ViewableBy'] < 2)) 
		{
			$newpage = sql::fetch(sql::run(
				" SELECT * FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `ID` = '".$newid."'"));
			
			$url = str_replace('&amp;', '&', $this->generateLink($newpage));
			
			$sitemap = new siteMap();
			$sitemap->load();
			$sitemap->add(array(
				'Link' => $url));
			
			if (!$sitemap->save())
				tooltip::display(
					__("Page successfully created but xml sitemap file couldn't be updated.")." " .
					sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
						"sitemap.xml"),
					TOOLTIP_NOTIFICATION);
			
			unset($sitemap);
		}
		
		api::callHooks(API_HOOK_AFTER,
			'pages::add', $this, $values, $newid);
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
			
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::edit', $this, $id, $values);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::edit', $this, $id, $values, $handled);
			
			return $handled;
		}
		
		if (!isset($values['LanguageID']))
			$values['LanguageID'] = null;
			
		$sitemap = new siteMap();
		$sitemap->load();
		
		$page = sql::fetch(sql::run(
			" SELECT * FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}`" .
			" WHERE `ID` = '".(int)$id."'"));
			
		$pageurl = str_replace('&amp;', '&', $this->generateLink($page));
		
		if ((int)$values[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]) {
			$parentpage = sql::fetch(sql::run(
				" SELECT * FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `ID` = '" .
					(int)$values[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]."'"));
			
			if ($parentpage['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;
			
			if (JCORE_VERSION >= '0.9') {
				if ($parentpage['AccessibleBy'] && !$values['AccessibleBy'])
					$values['AccessibleBy'] = (int)$parentpage['AccessibleBy'];
				
			} else {
				if ($parentpage['ViewableBy'] && !$values['ViewableBy'])
					$values['ViewableBy'] = (int)$parentpage['ViewableBy'];
				
				if ($parentpage['Hidden'] && !$values['Hidden'])
					$values['Hidden'] = true;
			}
			
			$values['LanguageID'] = $parentpage['LanguageID'];
			
			if (JCORE_VERSION < '0.9')
				$values['MenuID'] = $parentpage['MenuID'];
		}
		
		sql::run(
			" UPDATE `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			(JCORE_VERSION < '0.9'?
				" `Link` = '".
					sql::escape($values['Link'])."'," .
				" `MenuID` = '".
					(int)$values['MenuID']."',":
				null) .
			" `LanguageID` = '".
				(int)$values['LanguageID']."'," .
			(JCORE_VERSION >= '0.9' && isset($values['LayoutID'])?
				" `LayoutID` = '".
					(int)$values['LayoutID']."',":
				null) .
			(JCORE_VERSION >= '0.6'?
				" `SEOTitle` = '".
					sql::escape($values['SEOTitle'])."'," .
				" `SEODescription` = '".
					sql::escape($values['SEODescription'])."'," .
				" `SEOKeywords` = '".
					sql::escape($values['SEOKeywords'])."',":
				null) .
			(JCORE_VERSION >= '0.8'?
				" `PostKeywords` = '".
					sql::escape($values['PostKeywords'])."',":
				null) .
			(JCORE_VERSION >= '0.9'?
				" `AccessibleBy` = '".
					(int)$values['AccessibleBy']."',":
				" `ViewableBy` = '".
					(int)$values['ViewableBy']."',") .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			(JCORE_VERSION < '0.9'?
				" `Hidden` = '".
					($values['Hidden']?
						'1':
						'0').
					"',":
				null) .
			" `".(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')."` = '".
				(int)$values[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]."'," .
			" `Limit` = '".
				(int)$values['Limit']."'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");
		
		$result = (sql::affected() != -1);
		
		if (!$result) {
			tooltip::display(
				sprintf(__("Page couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'pages::edit', $this, $id, $values);
			
			return false;
		}
		
		if (JCORE_VERSION >= '0.9') {
			if ($page['LanguageID'] != $values['LanguageID'])
				sql::run(
					" UPDATE `{posts}` SET" .
					" `LanguageID` = '".$values['LanguageID']."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `PageID` = '".(int)$id."'");
			
			$items = sql::run(
				" SELECT * FROM `{menuitems}`" .
				" WHERE `PageID` = '".(int)$id."'");
			
			$menuitems = new menuItems();
			
			while($item = sql::fetch($items)) {
				if ($page['SubPageOfID'] != $values['SubPageOfID']) {
					if ($values['SubPageOfID']) {
						$parentitem = sql::fetch(sql::run(
							" SELECT `ID` FROM `{menuitems}`" .
							" WHERE `PageID` = '".(int)$values['SubPageOfID']."'" .
							" AND `MenuID` = '".$item['MenuID']."'" .
							" ORDER BY `OrderID`, `ID`" .
							" LIMIT 1"));
						
						if ($parentitem)
							$item['SubMenuItemOfID'] = $parentitem['ID'];
						else
							$item['SubMenuItemOfID'] = 0;
						
					} else {
						$item['SubMenuItemOfID'] = 0;
					}
				}
				
				$menuitems->edit(
					$item['ID'],
					array(
						'Title' =>
							($item['Title'] == $page['Title']? 
								$values['Title']:
								$item['Title']),
						'OrderID' =>
							($item['OrderID'] == $page['OrderID']? 
								$values['OrderID']:
								$item['OrderID']),
						'Path' => $values['Path'],
						'LanguageID' => $values['LanguageID'],
						'Deactivated' => $values['Deactivated'],
						'MenuID' => $item['MenuID'],
						'PageID' => $item['PageID'],
						'SubMenuItemOfID' => $item['SubMenuItemOfID'],
						'ViewableBy' => $item['ViewableBy'],
						'Link' => $item['Link']));
			}
			
			unset($menuitems);
		}
			
		sql::run(
			" DELETE FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pagemodules':
					'menuitemmodules') .
				"}` " .
			" WHERE `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
				$id."'");
		
		if (isset($values['Modules']) && is_array($values['Modules'])) {
			foreach($values['Modules'] as $moduleid) {
				sql::run(
					" INSERT INTO `{" .
						(JCORE_VERSION >= '0.8'?
							'pagemodules':
							'menuitemmodules') .
						"}` SET " .
					" `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
						$id."'," .
					" `ModuleID` = '".(int)$moduleid."'" .
					(JCORE_VERSION >= '0.3'?
						", `ModuleItemID` = '".
							(isset($values['ModulesItem'][$moduleid])?
								(int)$values['ModulesItem'][$moduleid]:
								0) .
							"'":
						null));
			}
		}
		
		if (JCORE_VERSION >= '0.8')
			sql::run(
				" DELETE FROM `{pageforms}` " .
				" WHERE `PageID` = '".$id."'");
		
		if (isset($values['DynamicForms']) && is_array($values['DynamicForms'])) {
			foreach($values['DynamicForms'] as $dformid) {
				sql::run(
					" INSERT INTO `{pageforms}` SET " .
					" `PageID` = '".$id."'," .
					" `FormID` = '".(int)$dformid."'");
			}
		}
		
		$posts = new posts();
		$posts->updateRSS($page['ID']);
		
		if ((!$page['Deactivated'] && $values['Deactivated']) || (JCORE_VERSION < '0.9' &&
			$page['ViewableBy'] < $values['ViewableBy'] && $values['ViewableBy'] > 1))
			$sitemap->delete($pageurl);
		
		if (($page['Deactivated'] && !$values['Deactivated']) || (JCORE_VERSION < '0.9' &&
			$page['ViewableBy'] > $values['ViewableBy'] && $values['ViewableBy'] < 2))
			$sitemap->add(array('Link' => $pageurl));
			
		foreach(pages::getTree((int)$id) as $row) {
			if (!$row['ID'])
				continue;
			
			$updatesql = null;
			$url = str_replace('&amp;', '&', $this->generateLink($row));
			
			if (($page['Deactivated'] && !$values['Deactivated']) ||
				(!$page['Deactivated'] && $values['Deactivated'])) 
			{
				if (!$row['Deactivated'] && $values['Deactivated'])
					$updatesql[] = " `Deactivated` = 1";
				if ($row['Deactivated'] && !$values['Deactivated'])
					$updatesql[] = " `Deactivated` = 0";
			}
			
			if ($page['LanguageID'] != $values['LanguageID'] &&
				$row['LanguageID'] != $values['LanguageID'])
				$updatesql[] = " `LanguageID` = ".(int)$values['LanguageID'];
			
			if (JCORE_VERSION < '0.9') {
				if ($page['MenuID'] != $values['MenuID'] &&
					$row['MenuID'] != $values['MenuID'])
					$updatesql[] = " `MenuID` = ".(int)$values['MenuID'];
				
				if (($page['Hidden'] && !$values['Hidden']) ||
					(!$page['Hidden'] && $values['Hidden'])) 
				{
					if (!$row['Hidden'] && $values['Hidden'])
						$updatesql[] = " `Hidden` = 1";
					if ($row['Hidden'] && !$values['Hidden'])
						$updatesql[] = " `Hidden` = 0";
				}
				
				if ($page['ViewableBy'] != $values['ViewableBy'] &&
					$row['ViewableBy'] != $values['ViewableBy'])
					$updatesql[] = " `ViewableBy` = '".(int)$values['ViewableBy']."'";
			}
			
			if (((($page['Deactivated'] && !$values['Deactivated']) ||
				(!$page['Deactivated'] && $values['Deactivated'])) &&
				 !$row['Deactivated'] && $values['Deactivated']) ||
				(JCORE_VERSION < '0.9' && $page['ViewableBy'] != $values['ViewableBy'] &&
				 $row['ViewableBy'] < $values['ViewableBy'] && $values['ViewableBy'] > 1))
				$sitemap->delete($url);
			
			if (((($page['Deactivated'] && !$values['Deactivated']) ||
				(!$page['Deactivated'] && $values['Deactivated'])) &&
				 $row['Deactivated'] && !$values['Deactivated']) || 
				(JCORE_VERSION < '0.9' && $page['ViewableBy'] != $values['ViewableBy'] &&
				 $row['ViewableBy'] > $values['ViewableBy'] && $values['ViewableBy'] < 2))
				$sitemap->add(array('Link' => $url));
			
			if ($updatesql) {
				sql::run(
					" UPDATE `{" .
						(JCORE_VERSION >= '0.8'?
							'pages':
							'menuitems') .
						"}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
				
				$posts->updateRSS($row['ID']);
			}
		}
		
		foreach(pages::getBackTraceTree((int)$id) as $row) {
			$updatesql = null;
			$url = str_replace('&amp;', '&', $this->generateLink($row));
			
			if ($row['Deactivated'] && !$values['Deactivated'])
				$updatesql[] = " `Deactivated` = 0";
			
			if (JCORE_VERSION < '0.9') {
				if ($row['Hidden'] && !$values['Hidden'])
					$updatesql[] = " `Hidden` = 0";
				
				if ($row['ViewableBy'] > $values['ViewableBy'])
					$updatesql[] = " `ViewableBy` = '".(int)$values['ViewableBy']."'";
			}
			
			if (($row['Deactivated'] && !$values['Deactivated']) || (JCORE_VERSION < '0.9' &&
				$row['ViewableBy'] > $values['ViewableBy'] && $values['ViewableBy'] < 2))
				$sitemap->add(array('Link' => $url));
			
			if ($updatesql) {
				sql::run(
					" UPDATE `{" .
						(JCORE_VERSION >= '0.8'?
							'pages':
							'menuitems') .
						"}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
				
				$posts->updateRSS($row['ID']);
			}
		}

		if (!$sitemap->save())
			tooltip::display(
				__("Page successfully updated but xml sitemap file couldn't be updated.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"sitemap.xml"),
				TOOLTIP_NOTIFICATION);
		
		$posts->updateRSS();
		
		unset($posts);
		unset($sitemap);
		
		api::callHooks(API_HOOK_AFTER,
			'pages::edit', $this, $id, $values, $result);
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::delete', $this, $id);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::delete', $this, $id, $handled);
			
			return $handled;
		}
		
		$posts = new posts();
		$pageids = array($id);
		
		$sitemap = new siteMap();
		$sitemap->load();
		
		foreach(pages::getTree((int)$id) as $row) {
			if (!$row['ID'])
				continue;
			
			$pageids[] = $row['ID'];
		}
		
		foreach($pageids as $pageid) {
			$page = sql::fetch(sql::run(
				" SELECT * FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `ID` = '".$pageid."'"));
			
			$rows = sql::run(
				" SELECT * FROM `{posts}` " .
				" WHERE `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
					$pageid."'");
			
			while($row = sql::fetch($rows))
				$posts->delete($row['ID']);
			
			sql::run(
				" DELETE FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pagemodules':
						'menuitemmodules') .
					"}` " .
				" WHERE `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
					$pageid."'");
			
			if (JCORE_VERSION >= '0.8')
				sql::run(
					" DELETE FROM `{pageforms}` " .
					" WHERE `PageID` = '".$pageid."'");
			
			sql::run(
				" DELETE FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}` " .
				" WHERE `ID` = '".$pageid."'");
			
			if (JCORE_VERSION >= '0.9') {
				$menuitems = new menuItems();
				
				$items = sql::run(
					" SELECT * FROM `{menuitems}`" .
					" WHERE `PageID` = '".$pageid."'");
				
				while($item = sql::fetch($items))
					$menuitems->delete($item['ID']);
				
				unset($menuitems);
			}
			
			$url = str_replace('&amp;', '&', $this->generateLink($page));
			$sitemap->delete($url);
			
			$posts->updateRSS($pageid);
		}
		
		if (!$sitemap->save())
			tooltip::display(
				__("Page successfully deleted but xml sitemap file couldn't be updated.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"sitemap.xml"),
				TOOLTIP_NOTIFICATION);
		
		$posts->updateRSS();
		
		unset($sitemap);
		unset($posts);
		
		api::callHooks(API_HOOK_AFTER,
			'pages::delete', $this, $id);
		
		return true;
	}
	
	function updateSitemap() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::updateSitemap', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::updateSitemap', $this, $handled);
			
			return $handled;
		}
		
		$sitemap = new sitemap();
		
		$rows = sql::run(
			" SELECT *, GROUP_CONCAT(DISTINCT `ID` SEPARATOR ',') AS `IDs` FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}`" .
			" WHERE `Deactivated` = 0" .
			(JCORE_VERSION < '0.9'?
				" AND `ViewableBy` < 2":
				null) .
			" GROUP BY `Path`" .
			" ORDER BY `OrderID`, `ID`");
		
		while($row = sql::fetch($rows)) {
			$lastpost = sql::fetch(sql::run(
				" SELECT `TimeStamp` " .
				" FROM `{posts}` " .
				" WHERE `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` IN (" .
					$row['IDs'].")" .
				" AND `Deactivated` = 0" .
				" ORDER BY `TimeStamp` DESC" .
				" LIMIT 1"));
				
			$lastmodified = date("Y-m-d H:i:s");
			
			if ($lastpost['TimeStamp'])
				$lastmodified = $lastpost['TimeStamp'];
			
			$url = str_replace('&amp;', '&', $this->generateLink($row));
			$sitemap->add(array(
				'Link' => $url,
				'LastModified' => $lastmodified));
		}
		
		$result = $sitemap->save();
		unset($sitemap);
		
		api::callHooks(API_HOOK_AFTER,
			'pages::updateSitemap', $this, $result);
		
		return $result;
	}
	
	// ************************************************   Client Part
	static function get($pageid) {
		if (!(int)$pageid)
			return false;
		
		if (pages::$selected && $pageid == pages::$selected['ID'])
			return pages::$selected;
		
		return sql::fetch(sql::run(
			" SELECT * FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}`" .
			" WHERE `ID` = '".(int)$pageid."'"));
	}
	
	static function access2Text($typeid) {
		if ($typeid > 10) {
			$ugroup = userGroups::get($typeid-10);
			
			if (!$ugroup)
				return false;
			
			return $ugroup['GroupName'];
		}
		
		switch($typeid) {
			case PAGE_ADMINS_ONLY:
				return __('Admins');
			case PAGE_GUESTS_ONLY:
				return __('Guests');
			case PAGE_USERS_ONLY:
				return __('Members');
			default:
				return __('Everyone');
		}
	}
	
	static function checkAccess($row) {
		if ($row && !is_array($row))
			$row = sql::fetch(sql::run(
				" SELECT `".(JCORE_VERSION >= '0.9'?'AccessibleBy':'ViewableBy')."`" .
				" FROM `{pages}`" .
				" WHERE `ID` = '".(int)$row."'"));
		
		if (!$row)
			return true;
		
		if (JCORE_VERSION >= '0.9') {
			if (($row['AccessibleBy'] > PAGE_GUESTS_ONLY && !$GLOBALS['USER']->loginok) ||
				($row['AccessibleBy'] == PAGE_ADMINS_ONLY && !$GLOBALS['USER']->data['Admin']) ||
				($row['AccessibleBy'] > 10 && !$GLOBALS['USER']->data['Admin'] &&
				 $GLOBALS['USER']->data['GroupID'] != $row['AccessibleBy']-10)) 
			{
				return false;
			}
			
		} else {
			if ($row['ViewableBy'] > PAGE_GUESTS_ONLY && !$GLOBALS['USER']->loginok) {
				return false;
			}
		}
		
		return $row;
	}
	
	static function isHome($id, $languageid = 0) {
		if (!$id)
			return true;
		
		$homeids = array();
		
		if (!$languageid)
			$homeids = pages::getHomeIDs();
		else
			$homeids[] = pages::getHomeID($languageid);
		
		if (in_array($id, $homeids))
			return true;
		
		return false;
	}
	
	static function getTree($pageid = 0, $firstcall = true, 
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0)) 
	{
		$rows = sql::run(
			" SELECT * FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}` " .
			($pageid?
				" WHERE `".(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')."` = '" .
					$pageid."'":
				" WHERE `".(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')."` = 0") .
			" ORDER BY " .
				(JCORE_VERSION < '0.9'?
					(menus::$order?
						" FIELD(`MenuID`, ".menus::$order."),":
						" `MenuID`,"):
					null) .
				" `LanguageID`, `OrderID`");
		
		$arelanguages = false;
		
		while($row = sql::fetch($rows)) {
			$last = end($tree['Tree']);
			
			if (JCORE_VERSION >= '0.9') {
				if (!$last || $last['LanguageID'] != $row['LanguageID']) {
					$language = null;
					
					if ($row['LanguageID'])
						$language = languages::get($row['LanguageID']);
					
					if ($language)
						$tree['Tree'][] = array(
							'ID' => 0,
							'Title' => $language['Title'],
							'SubPageOfID' => 0,
							'LanguageID' => $language['ID'],
							'PathDeepnes' => 0);
					
					if (!$last['LanguageID'] && $row['LanguageID'])
						$arelanguages = true;
				}
				
			} else {
				if ($row['MenuID'] && (!$last || $last['MenuID'] != $row['MenuID'])) {
					$menu = sql::fetch(sql::run(
						" SELECT `Title` FROM `{menus}`" .
						" WHERE `ID` = '".$row['MenuID']."'"));
					
					$tree['Tree'][] = array(
						'ID' => 0,
						'Title' => $menu['Title'],
						(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID') => 0,
						'MenuID' => $row['MenuID'],
						'PathDeepnes' => 0);
				}
			}
			
			$row['PathDeepnes'] = $tree['PathDeepnes'];
			$tree['Tree'][] = $row;
			
			$tree['PathDeepnes']++;
			pages::getTree($row['ID'], false, $tree);
			$tree['PathDeepnes']--;
		}
		
		if ($arelanguages)
			array_unshift($tree['Tree'], array(
				'ID' => 0,
				'Title' => __('No Language Defined'),
				'SubPageOfID' => 0,
				'LanguageID' => 0,
				'PathDeepnes' => 0));
		
		if ($firstcall)
			return $tree['Tree'];
	}
	
	static function getBackTraceTree($id, $firstcall = true, $fields = '*',
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0)) 
	{
		if (!(int)$id)
			return array();
		
		$row = sql::fetch(sql::run(
			" SELECT ".$fields." FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}` " .
			" WHERE `ID` = '".(int)$id."'"));
		
		if (!$row)
			return array();
		
		if (isset($row[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]) && 
			$row[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')])
		{	
			pages::getBackTraceTree(
				$row[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')], 
				false, $fields, $tree);
		}
		
		$row['PathDeepnes'] = $tree['PathDeepnes'];
		$tree['Tree'][] = $row;
		$tree['PathDeepnes']++;
		
		if ($firstcall)
			return $tree['Tree'];
	}
	
	static function getHome($languageid = null, $fields = '*') {
		if (!isset($languageid) && languages::$selected)
			$languageid = languages::$selected['ID'];
		
		return sql::fetch(sql::run(
			" SELECT ".$fields." FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}` " .
			" WHERE `".(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')."` = 0" .
			" AND `LanguageID` = '".(int)$languageid."'" .
			" AND `Deactivated` = 0" .
			" ORDER BY " .
				(JCORE_VERSION < '0.9'?
					(menus::$order?
						" FIELD(`MenuID`, ".menus::$order."),":
						" `MenuID`,"):
					null) .
				" `OrderID`" .
			" LIMIT 1"));
	}
	
	static function getHomeID($languageid = null) {
		if (!isset($languageid) && languages::$selected)
			$languageid = languages::$selected['ID'];
		
		if ($page = pages::getHome($languageid, '`ID`'))
			return $page['ID'];
		
		return 0;
	}
	
	static function getHomeIDs() {
		$languageids = languages::getIDs();
		
		if (!$languageids) {
			if ($page = pages::getHome())
				return array($page['ID']);
			
			return false;
		}
		
		$pageids = null;
		
		if (!languages::getDefault())
			array_unshift($languageids, 0);
		else
			$languageids[] = 0;
		
		foreach($languageids as $languageid) {
			if ($page = pages::getHome($languageid))
				$pageids[] = $page['ID'];
		}
		
		return $pageids;
	}
	
	static function getSelected() {
		return pages::$selected;
	}
	
	static function getSelectedID () {
		if (pages::$selected)
			return pages::$selected['ID'];
		
		return 0;
	}
	
	// DEPRECATED! Since 0.9 there are no more alias pages allowed!
	static function getAliasIDs($pageid = null) {
		if (!$pageid)
			return false;
		
		$page = pages::get($pageid);
		
		if (!$page)
			return false;
		
		$pages = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(`ID` SEPARATOR ',') AS `IDs`" .
			" FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}`" .
			" WHERE `Path` = '".sql::escape($page['Path'])."'" .
			" AND `LanguageID` = '".(int)$page['LanguageID']."'" .
			" AND `ID` != '".(int)$pageid."'" .
			" GROUP BY `LanguageID`" .
			" LIMIT 1"));
		
		if (!$pages)
			return false;
		
		return explode(',', $pages['IDs']);
	}
	
	static function generateLink(&$row) {
		$language = languages::$selected;
		
		if ($row['LanguageID'] && (!$language || $language['ID'] != $row['LanguageID']))
			$language = sql::fetch(sql::run(
				" SELECT `ID`, `Path` FROM `{languages}`" .
				" WHERE `ID` = '".$row['LanguageID']."'"));
		
		if (SEO_FRIENDLY_LINKS)
			return url::site().
				($language?
					$language['Path'].'/':
					null) .
				$row['Path'];
		
		return url::site().'index.php?' .
			($language?
				'&amp;languageid='.$language['ID']:
				null) .
			'&amp;pageid='.$row['ID'];
	}
	
	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::ajaxRequest', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::ajaxRequest', $this, $handled);
			
			return $handled;
		}
		
		$keywords = null;
		
		if (isset($_GET['keywords']))
			$keywords = (int)$_GET['keywords'];
		
		if ($keywords) {
			$posts = new posts();
			$posts->displayAdminAvailableKeywords('#neweditpageform #entryPostKeywords');
			unset($posts);
			
			api::callHooks(API_HOOK_AFTER,
				'pages::ajaxRequest', $this, $keywords);
			
			return true;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'pages::ajaxRequest', $this);
		
		return true;
	}
	
	static function displayModules($pageid) {
		if (!$pageid)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayModules', $_ENV, $pageid);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayModules', $_ENV, $pageid, $handled);
			
			return $handled;
		}
		
		$modules = new modules();
		$modules->sqlTable = (JCORE_VERSION >= '0.8'?'pagemodules':'menuitemmodules');
		$modules->sqlRow = (JCORE_VERSION >= '0.8'?'PageID':'MenuItemID');
		$modules->sqlOwnerTable = (JCORE_VERSION >= '0.8'?'pages':'menuitems');
		$modules->selectedOwner = 'Page';
		$modules->selectedOwnerID = $pageid;
		$display = $modules->display();
		unset($modules);
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayModules', $_ENV, $pageid, $display);
		
		return $display;
	}
	
	static function displayForms($pageid) {
		if (!$pageid)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayForms', $_ENV, $pageid);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayForms', $_ENV, $pageid, $handled);
			
			return $handled;
		}
		
		$dformids = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(`FormID` SEPARATOR ',') AS `FormIDs`" .
			" FROM `{pageforms}`" .
			" WHERE `PageID` = '".(int)$pageid."'" .
			" GROUP BY `PageID`" .
			" LIMIT 1"));
		
		if ($dformids) {
			$dforms = sql::run(
				" SELECT `FormID` FROM `{dynamicforms}`" .
				" WHERE `ID` IN (".$dformids['FormIDs'].")" .
				" ORDER BY `FormID`, `ID`");
			
			while($dform = sql::fetch($dforms)) {
				$form = new dynamicForms($dform['FormID']);
				$form->load();
				$form->verify();
				$form->display();
				unset($form);
			}
		}
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayForms', $_ENV, $pageid, $dformids);
		
		return $dformids;
	}
	
	function displayLogin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayLogin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayLogin', $this, $handled);
			
			return $handled;
		}
		
		tooltip::display(
			__("This area is limited to members only. " .
				"Please login below."),
			TOOLTIP_NOTIFICATION);
		
		$GLOBALS['USER']->displayLogin();
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayLogin', $this);
	}
	
	function displayTitle(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayTitle', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayTitle', $this, $row, $handled);
			
			return $handled;
		}
		
		echo $row['Title'];
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayTitle', $this, $row);
	}
	
	function displayContent(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayContent', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayContent', $this, $row, $handled);
			
			return $handled;
		}
		
		echo "<p></p>";
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayContent', $this, $row);
	}
	
	function displaySelected(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displaySelected', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displaySelected', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			"<div class='post page'>" .
				"<h1 class='post-title page'>";
		
		$this->displayTitle($row);
		
		echo
				"</h1>" .
				"<div class='post-content page'>";
		
		$this->displayContent($row);
		
		echo
				"</div>" .
			"</div>";
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displaySelected', $this, $row);
	}
	
	function displayPath($level = 0, $page = null) {
		if (!$page)
			$page = pages::$selected;
			
		if (!$page)
			return;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayPath', $this, $level, $page);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayPath', $this, $level, $page, $handled);
			
			return $handled;
		}
		
		$i = 0;
		$backtrace = $this->getBackTraceTree($page['ID']);
		
		foreach($backtrace as $key => $page) {
			if ($key < $level)
				continue;
			
			if ($i > 0)
				echo "<span class='path-separator'> / </span>";
			
			echo
				"<a class='url-path' href='". $this->generateLink($page) .
					"'>".__($page['Title'])."</a>";
			
			$i++;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayPath', $this, $level, $page);
	}
	
	function displayArguments() {
		if (!isset($this->arguments))
			return false;
		
		if (!$this->arguments)
			return true;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::displayArguments', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayArguments', $this, $handled);
			
			return $handled;
		}
		
		$page = null;
		$argtype = null;
		
		if (preg_match('/(^|\/)(url|path|[A-Z][A-Za-z0-9_\-]+?)($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)(url|path|[A-Z][A-Za-z0-9_\-]+?)($|\/)/', '\3', $this->arguments);
			$argtype = $matches[2];
		}
		
		if (!$this->arguments) {
			$page = pages::$selected;
			
		} elseif (preg_match('/(^|\/)selected($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)selected($|\/)/', '\2', $this->arguments);
			$page = pages::$selected;
			
		} else {
			$page = sql::fetch(sql::run(
				" SELECT * FROM `{pages}` " .
				" WHERE '".sql::escape($this->arguments)."/' LIKE CONCAT(`Path`,'/%')" .
				(languages::$selected?
					" AND `LanguageID` = '".languages::$selected['ID']."'":
					null) .
				" AND `Deactivated` = 0" .
				" ORDER BY `Path` DESC, `OrderID`" .
				" LIMIT 1"));
		}
		
		if (!$page) {
			api::callHooks(API_HOOK_AFTER,
				'pages::displayArguments', $this);
			
			return true;
		}
		
		if ($argtype) {
			switch($argtype) {
				case 'url':
					echo $this->generateLink($page);
					break;
					
				case 'path':
					$this->displayPath(0, $page);
					break;
					
				default: 
					echo $page[$argtype];
			}
			
			api::callHooks(API_HOOK_AFTER,
				'pages::displayArguments', $this, $argtype);
			
			return true;
		}
		
		$result = false;
		if ($page['ID'] == $this->selectedID)
			$result = true;
		else
			$this->selectedID = $page['ID'];
		
		api::callHooks(API_HOOK_AFTER,
			'pages::displayArguments', $this, $result);
		
		return $result;
	}
	
	function display() {
		if ($this->displayArguments())
			return true;
		
		if (!$this->selectedID) {
			url::displayError();
			return false;
		}
		
		$page = pages::get($this->selectedID);
		
		if ($page['Deactivated']) {
			tooltip::display(
				__("This page has been deactivated."),
				TOOLTIP_NOTIFICATION);
			return false;
		}
		
		if ($page['LanguageID'] && languages::$selected &&
			$page['LanguageID'] != languages::$selected['ID'])
			return false;
		
		if (!$this->checkAccess($page)) {
			if (JCORE_VERSION >= '0.7')
				$this->displaySelected($page);
			
			if (!$GLOBALS['USER']->loginok)
				$this->displayLogin();
			else
				tooltip::display(
					__("You do not have permission to access this page!"),
					TOOLTIP_NOTIFICATION);
			
			return true;
		}
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pages::display', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pages::display', $this, $handled);
			
			return $handled;
		}
		
		$posts = new posts();
		$posts->selectedPageID = $this->selectedID;
		
		if (JCORE_VERSION < '0.9')
			$posts->aliasPageIDs = pages::getAliasIDs($this->selectedID);
		
		$items = $posts->display();
		
		if (JCORE_VERSION >= '0.7' && !$items && !$posts->search)
			$this->displaySelected($page);
		
		if (!$posts->search) {
			$this->displayModules($this->selectedID);
			
			if (JCORE_VERSION >= '0.8')
				$this->displayForms($this->selectedID);
		}
		
		unset($posts);
		
		api::callHooks(API_HOOK_AFTER,
			'pages::display', $this);
		
		return true;
	}
}

?>