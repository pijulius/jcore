<?php

/***************************************************************************
 *            menuitems.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
include_once('lib/languages.class.php');
include_once('lib/modules.class.php');

define('MENU_EVERYONE', 0);
define('MENU_GUESTS_ONLY', 1);
define('MENU_USERS_ONLY', 2);
define('MENU_ADMINS_ONLY', 3);

class _menuItems {
	var $arguments = '';
	var $selectedID;
	var $selectedIDs = array();
	var $selectedMenuID;
	var $adminPath = 'admin/content/menuitems';
	
	static $selected = null;
	
	function __construct() {
		if (isset($_GET['menuid']))
			$this->selectedID = (int)$_GET['menuid'];
	}
	
	function SQL() {
		return
			" SELECT * FROM `{menuitems}`" .
			" WHERE !`Deactivated`" .
			" AND !`Hidden`" .
			" AND `MenuID` = '".(int)$this->selectedMenuID."'" .
			" AND `LanguageID` = '".
				(languages::$selected?
					(int)languages::$selected['ID']:
					0) .
				"'" .
			" AND !`SubMenuOfID`" .
			" AND (!`ViewableBy` OR " .
				($GLOBALS['USER']->loginok?
					($GLOBALS['USER']->data['Admin']?
						" `ViewableBy` IN (2, 3)":
						" `ViewableBy` = 2"):
					" `ViewableBy` = 1") .
			" )" .
			" ORDER BY `OrderID`";		
	}
	
	static function populate() {
		if (!isset($_GET['menuid']))
			$_GET['menuid'] = 0;
		
		if (isset($GLOBALS['ADMIN']) && $GLOBALS['ADMIN'])
			return false;
		
		$selected = sql::fetch(sql::run(
			" SELECT * FROM `{menuitems}`" .
			" WHERE !`Deactivated`" .
			" AND `LanguageID` = '".(int)$_GET['languageid']."'" .
			(SEO_FRIENDLY_LINKS && !(int)$_GET['menuid']?
				" AND '".sql::escape(url::path())."/' LIKE CONCAT(`Path`,'/%')":
				" AND `ID` = '".(int)$_GET['menuid']."'") .
			" ORDER BY `Path` DESC," .
				(menus::$order?
					" FIELD(`MenuID`, ".menus::$order."),":
					" `MenuID`,") .
				" `OrderID`" .
			" LIMIT 1"));
			
		if (!$selected && 
			((SEO_FRIENDLY_LINKS && !url::path()) || 
			(!SEO_FRIENDLY_LINKS && !(int)$_GET['menuid'])))
		{
			$selected = menuItems::getMainMenu();
		}
		
		if (SEO_FRIENDLY_LINKS && $selected)
			url::setPath(preg_replace(
				'/'.preg_quote($selected['Path'], '/').'(\/|$)/i', '', 
				url::path(), 1));
		
		if ($selected) {
			menuItems::$selected = $selected;
			
			if (JCORE_VERSION >= '0.6' && $selected['SEOTitle']) {
				url::setPageTitle($selected['SEOTitle']);
			} else {
				$menuroute = array();
				
				foreach(menuItems::getBackTraceTree($selected['ID']) as $menu)
					$menuroute[] = $menu['Title'];
					
				url::addPageTitle(implode(' / ', $menuroute));
			}
			
			if (JCORE_VERSION >= '0.6' && $selected['SEODescription'])
				url::setPageDescription($selected['SEODescription']);
			
			if (JCORE_VERSION >= '0.6' && $selected['SEOKeywords'])
				url::setPageKeywords($selected['SEOKeywords']);
			
			$_GET['menuid'] = $selected['ID'];
			return;
		}
		
		url::addPageTitle(__('Address Not Found'));
		$_GET['menuid'] = 0;
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{menuitems}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Page'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Moving Posts'), 
			'?path=admin/content/postshandling');
		favoriteLinks::add(
			__('Content Files'), 
			'?path=admin/content/contentfiles');
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 250px;');
		
		$form->add(
			__('Sub Menu of'),
			'SubMenuOfID',
			FORM_INPUT_TYPE_SELECT);
		$form->setValueType(FORM_VALUE_TYPE_INT);
			
		$form->addValue('', '');
		
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
				
				if (!method_exists($modulename, 'display'))
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
			__('Path'),
			'Path',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 250px;');
		
		$form->add(
			__('Link to URL'),
			'Link',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 300px;');
		$form->setValueType(FORM_VALUE_TYPE_URL);
		$form->setTooltipText(__("e.g. http://domain.com"));
		
		$form->add(
			__('Viewable by'),
			'ViewableBy',
			FORM_INPUT_TYPE_SELECT);
		$form->setValueType(FORM_VALUE_TYPE_INT);
			
		$form->addValue(
			MENU_EVERYONE, $this->access2Text(MENU_EVERYONE));
		$form->addValue(
			MENU_GUESTS_ONLY, $this->access2Text(MENU_GUESTS_ONLY));
		$form->addValue(
			MENU_USERS_ONLY, $this->access2Text(MENU_USERS_ONLY));
		$form->addValue(
			MENU_ADMINS_ONLY, $this->access2Text(MENU_ADMINS_ONLY));
		
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
			__('Hidden'),
			'Hidden',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
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
					" UPDATE `{menuitems}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'");
			}
			
			tooltip::display(
				__("Menu items have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
			
			tooltip::display(
				__("Menu item and all its submenus have been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if (!$form->get('Path')) {
			$path = '';
			
			if ($form->get('SubMenuOfID')) {
				$submenuof = sql::fetch(sql::run(
					" SELECT `Path` FROM `{menuitems}`" .
					" WHERE `ID` = ".(int)$form->get('SubMenuOfID')));
				
				$path .= $submenuof['Path'].'/';
			} 
			
			$path .= url::genPathFromString($form->get('Title'));
			
			$form->set('Path', $path);
		}
				
		if ($edit && $form->get('SubMenuOfID')) {
			foreach(menuItems::getBackTraceTree($form->get('SubMenuOfID')) as $item) {
				if ($item['ID'] == $id) {
					tooltip::display(
						__("Menu item cannot be submenu of itself!"),
						TOOLTIP_ERROR);
					
					return false;
				}
			}
		}
			
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray()))
				return false;
			
			$menu = sql::fetch(sql::run(
				" SELECT * FROM `{menuitems}`" .
				" WHERE `ID` = '".(int)$id."'"));
			
			tooltip::display(
				__("Menu item has been successfully updated.")." " .
				"<a href='".$this->generateLink($menu)."' target='_blank'>" .
					__("View Page") .
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
			
		$menu = sql::fetch(sql::run(
			" SELECT * FROM `{menuitems}`" .
			" WHERE `ID` = '".(int)$newid."'"));
		
		tooltip::display(
			__("Menu item has been successfully created.")." " .
				"<a href='".$this->generateLink($menu)."' target='_blank'>" .
					__("View Page") .
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
	
	function displayAdminListItems($menuid = 0, $submenuof = 0, $rowpair = false, $language = null) {
		$rows = sql::run(
			" SELECT * FROM `{menuitems}`" .
			" WHERE `MenuID` = '".(int)$menuid."'" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			((int)$submenuof?
				" AND `SubMenuOfID` = '".(int)$submenuof."'":
				" AND !`SubMenuOfID`") .
			($language?
				" AND `LanguageID` = '".$language['ID']."'":
				null) .
			" ORDER BY `OrderID`");
		
		if (!sql::rows($rows))
			return false;
			
		if ($submenuof) {
			echo 
				"<tr".($rowpair?" class='pair'":NULL).">" .
					"<td></td>" .
					"<td colspan='7' class='auto-width nopadding'>";
		}
				
		echo "<table class='list' cellpadding='0' cellspacing='0'>";
		
		if (!$submenuof) {
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
				"<tr".($i%2?" class='pair'":NULL).">";
				
			$this->displayAdminListItem($row);
			$this->displayAdminListItemOptions($row);
			
			if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
				$this->displayAdminListItemFunctions($row);
			
			echo
				"</tr>";
			
			$this->displayAdminListItems($menuid, $row['ID'], $i%2);
			
			$i++;
		}
		
		if ($submenuof) {
			echo 
				"</table>" .
				"</td>" .
				"</tr>";
		} else {
			echo 
				"</tbody>" .
				"</table>";
		}
		
		return true;
	}
	
	function displayAdminListLanguages($menuid, $language) {
		echo 
		"<div tabindex='0' class='fc" . 
			form::fcState('fcl'.$menuid.$language['ID'], true) . 
			"'>" .
			"<a class='fc-title' name='fcl".$menuid.$language['ID']."'>" .
				stripcslashes($language['Title']) .
				(isset($language['Path']) && $language['Path']?
					" (".$language['Path'].")":
					null) .
			"</a>" .
			"<div class='fc-content'>";
			
		if (!$this->displayAdminListItems($menuid, 0, false, $language))
			tooltip::display(
					__("No menu items found."),
					TOOLTIP_NOTIFICATION);
		
		echo
			"</div>" .
		"</div>";
	}
	
	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Path / Link")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Hidden")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Viewable by")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Limit")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
		echo
			"<th><span class='nowrap'>".
				__("Posts")."</span></th>";
	}
	
	function displayAdminListHeaderFunctions() {
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
		$tooltiptxt = null;
			
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
			" FROM `{menuitemmodules}` " .
			" WHERE `MenuItemID` = '".$row['ID']."'" .
			" GROUP BY `MenuItemID`" .
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
					(!$row['SubMenuOfID']?
						"class='bold' ":
						null).
					">" .
				$row['Title'] .
				"</div>" .
				"<div class='comment' style='padding-left: 10px;'>" .
				($row['Link']?
					"<a href='".url::generateLink($row['Link']) .
						"' target='_blank'>":
					null) .
				$row['Path'] .
				($row['Link']?
					"</a>":
					null) .
				"</div>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				($row['Hidden']?__('Yes'):'&nbsp;').
			"</td>" .
			"<td style='text-align: right;'>" .
				($row['ViewableBy']?
					$this->access2Text($row['ViewableBy']):
					null) .
			"</td>" .
			"<td style='text-align: right;'>" .
				($row['Limit']?
					$row['Limit']:
					null) .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='admin-link posts' " .
					"title='".htmlspecialchars(__("Posts"), ENT_QUOTES) .
					(JCORE_VERSION >= '0.5'?
						" (".$row['Posts'].")":
						null) .
						"' " .
					"href='".url::uri('id, edit, delete, path') .
					"&amp;path=".admin::path()."/".$row['ID']."/posts'>" .
					(ADMIN_ITEMS_COUNTER_ENABLED && $row['Posts']?
						"<span class='counter'>" .
							"<span>" .
								"<span>" .
								$row['Posts']."" .
								"</span>" .
							"</span>" .
						"</span>":
						null) .
				"</a>" .
			"</td>";
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
			"<input type='submit' name='reordersubmit' value='".
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminList(&$rows, &$languages) {
		echo
			"<form action='".url::uri('edit, delete')."' method='post'>";
		
		while($row = sql::fetch($rows)) {
			echo 
				"<div tabindex='0' class='fc" .
					form::fcState('fcm'.$row['ID'], true) .
					"'>" .
					"<a class='fc-title' name='fcm".$row['ID']."'>".
						$row['Title'] .
					"</a>" .
					"<div class='fc-content'>";
			
			if ($languages) {
				if (sql::count(
					" SELECT COUNT(`ID`) AS `Rows` " .
					" FROM `{menuitems}` " .
					" WHERE `MenuID` = '".$row['ID']."' " .
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
				if (!$this->displayAdminListItems($row['ID']))
					tooltip::display(
							__("No menu items found."),
							TOOLTIP_NOTIFICATION);
			}
			
			echo
					"</div>" .
				"</div>";
		}
		
		echo "<br />";
		
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
			__('Menu Items Administration (Pages)'),
			$ownertitle);
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
					__("Edit Menu Item (Page)"):
					__("New Menu Item (Page)")),
				'neweditmenuitem');
					
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
		
		foreach(menuItems::getTree() as $row)
			$form->addValue('SubMenuOfID',
				$row['ID'], 
				($row['SubMenuOfID']?
					str_replace(' ', '&nbsp;', 
						str_pad('', $row['PathDeepnes']*4, ' ')).
					"|- ":
					null) .
				$row['Title']);
		
		$form->groupValues('SubMenuOfID', array('0'));
		
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
						"and create at least one menu block to put menu items in."),
					TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))) &&
			sql::rows($rows))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{menuitems}` " .
					" WHERE `ID` = '".$id."'" .
					($this->userPermissionIDs?
						" AND `ID` IN (".$this->userPermissionIDs.")":
						null)));
				
				$form->setValues($row);
				
				$modules = sql::run(
					" SELECT * FROM `{menuitemmodules}`" .
					" WHERE `MenuItemID` = '".$row['ID']."'");
				
				while($module = sql::fetch($modules)) {
					$form->setValue(
							'Modules['.$module['ModuleID'].']', 
							$module['ModuleID']);
					
					if (isset($module['ModuleItemID']))
						$form->setValue(
								'ModulesItem['.$module['ModuleID'].']', 
								$module['ModuleItemID']);
				}
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
		
		if (!isset($values['LanguageID']))
			$values['LanguageID'] = null;
			
		if ($values['OrderID'] == '') {
			$row = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{menuitems}` " .
				" WHERE `SubMenuOfID` = '".(int)$values['SubMenuOfID']."'" .
				" ORDER BY `OrderID` DESC"));
			
			$values['OrderID'] = (int)$row['OrderID']+1;
			
		} else {
			sql::run(
				" UPDATE `{menuitems}` SET " .
				" `OrderID` = `OrderID` + 1" .
				" WHERE `SubMenuOfID` = '".(int)$values['SubMenuOfID']."'" .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		if ((int)$values['SubMenuOfID']) {
			$parentmenu = sql::fetch(sql::run(
				" SELECT * FROM `{menuitems}`" .
				" WHERE `ID` = '".(int)$values['SubMenuOfID']."'"));
			
			if ($parentmenu['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;
			
			if ($parentmenu['Hidden'] && !$values['Hidden'])
				$values['Hidden'] = true;
			
			if ($parentmenu['ViewableBy'] && !$values['ViewableBy'])
				$values['ViewableBy'] = (int)$parentmenu['ViewableBy'];
			
			$values['LanguageID'] = $parentmenu['LanguageID'];
			$values['MenuID'] = $parentmenu['MenuID'];
		}
		
		$newid = sql::run(
			" INSERT INTO `{menuitems}` SET " .
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			" `Link` = '".
				sql::escape($values['Link'])."'," .
			" `MenuID` = '".
				(int)$values['MenuID']."'," .
			" `LanguageID` = '".
				(int)$values['LanguageID']."'," .
			(JCORE_VERSION >= '0.6'?
				" `SEOTitle` = '".
					sql::escape($values['SEOTitle'])."'," .
				" `SEODescription` = '".
					sql::escape($values['SEODescription'])."'," .
				" `SEOKeywords` = '".
					sql::escape($values['SEOKeywords'])."',":
				null) .
			" `ViewableBy` = '".
				(int)$values['ViewableBy']."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `Hidden` = '".
				($values['Hidden']?
					'1':
					'0').
				"'," .
			" `SubMenuOfID` = '".
				(int)$values['SubMenuOfID']."'," .
			" `Limit` = '".
				(int)$values['Limit']."'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
		
		if (!$newid) {
			tooltip::display(
				sprintf(__("Menu Item couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (isset($values['Modules']) && is_array($values['Modules'])) {
			foreach($values['Modules'] as $moduleid) {
				sql::run(
					" INSERT INTO `{menuitemmodules}` SET " .
					" `MenuItemID` = '".$newid."'," .
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
		
		if (!$values['Deactivated'] && $values['ViewableBy'] < 2) {
			$newmenu = sql::fetch(sql::run(
				" SELECT * FROM `{menuitems}`" .
				" WHERE `ID` = '".$newid."'"));
			
			$url = str_replace('&amp;', '&', $this->generateLink($newmenu));
			
			$sitemap = new siteMap();
			$sitemap->load();
			$sitemap->add(array(
				'Link' => $url));
			
			if (!$sitemap->save())
				tooltip::display(
					__("Menu item successfully created but xml sitemap file couldn't be updated.")." " .
					sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
						"sitemap.xml"),
					TOOLTIP_NOTIFICATION);
			
			unset($sitemap);
		}
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
			
		if (!isset($values['LanguageID']))
			$values['LanguageID'] = null;
			
		$sitemap = new siteMap();
		$sitemap->load();
		
		$menuitem = sql::fetch(sql::run(
			" SELECT * FROM `{menuitems}`" .
			" WHERE `ID` = '".$id."'"));
			
		$menuitemurl = str_replace('&amp;', '&', $this->generateLink($menuitem));
		
		if ((int)$values['SubMenuOfID'] && 
			(int)$values['SubMenuOfID'] != $menuitem['SubMenuOfID']) 
		{
			$parentmenu = sql::fetch(sql::run(
				" SELECT * FROM `{menuitems}`" .
				" WHERE `ID` = '".(int)$values['SubMenuOfID']."'"));
			
			if ($parentmenu['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;
			
			if ($parentmenu['Hidden'] && !$values['Hidden'])
				$values['Hidden'] = true;
			
			if ($parentmenu['ViewableBy'] && !$values['ViewableBy'])
				$values['ViewableBy'] = (int)$parentmenu['ViewableBy'];
			
			$values['LanguageID'] = $parentmenu['LanguageID'];
			$values['MenuID'] = $parentmenu['MenuID'];
		}
		
		sql::run(
			" UPDATE `{menuitems}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			" `Link` = '".
				sql::escape($values['Link'])."'," .
			" `MenuID` = '".
				(int)$values['MenuID']."'," .
			" `LanguageID` = '".
				(int)$values['LanguageID']."'," .
			(JCORE_VERSION >= '0.6'?
				" `SEOTitle` = '".
					sql::escape($values['SEOTitle'])."'," .
				" `SEODescription` = '".
					sql::escape($values['SEODescription'])."'," .
				" `SEOKeywords` = '".
					sql::escape($values['SEOKeywords'])."',":
				null) .
			" `ViewableBy` = '".
				(int)$values['ViewableBy']."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `Hidden` = '".
				($values['Hidden']?
					'1':
					'0').
				"'," .
			" `SubMenuOfID` = '".
				(int)$values['SubMenuOfID']."'," .
			" `Limit` = '".
				(int)$values['Limit']."'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".$id."'");
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Menu item couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		sql::run(
			" DELETE FROM `{menuitemmodules}` " .
			" WHERE `MenuItemID` = '".$id."'");
		
		if (isset($values['Modules']) && is_array($values['Modules'])) {
			foreach($values['Modules'] as $moduleid) {
				sql::run(
					" INSERT INTO `{menuitemmodules}` SET " .
					" `MenuItemID` = '".$id."'," .
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
		
		if ((!$menuitem['Deactivated'] && $values['Deactivated']) ||
			($menuitem['ViewableBy'] < $values['ViewableBy'] && $values['ViewableBy'] > 1))
			$sitemap->delete($menuitemurl);
		
		if (($menuitem['Deactivated'] && !$values['Deactivated']) ||
			($menuitem['ViewableBy'] > $values['ViewableBy'] && $values['ViewableBy'] < 2))
			$sitemap->add(array('Link' => $menuitemurl));
			
		foreach(menuItems::getTree((int)$id) as $row) {
			$updatesql = null;
			$url = str_replace('&amp;', '&', $this->generateLink($row));
			
			if (($menuitem['Hidden'] && !$values['Hidden']) ||
				(!$menuitem['Hidden'] && $values['Hidden'])) 
			{
				if (!$row['Hidden'] && $values['Hidden'])
					$updatesql[] = " `Hidden` = 1";
				if ($row['Hidden'] && !$values['Hidden'])
					$updatesql[] = " `Hidden` = 0";
			}
			
			if (($menuitem['Deactivated'] && !$values['Deactivated']) ||
				(!$menuitem['Deactivated'] && $values['Deactivated'])) 
			{
				if (!$row['Deactivated'] && $values['Deactivated'])
					$updatesql[] = " `Deactivated` = 1";
				if ($row['Deactivated'] && !$values['Deactivated'])
					$updatesql[] = " `Deactivated` = 0";
			}
			
			if ($menuitem['LanguageID'] != $values['LanguageID'] &&
				$row['LanguageID'] != $values['LanguageID'])
				$updatesql[] = " `LanguageID` = ".(int)$values['LanguageID'];
			
			if ($menuitem['MenuID'] != $values['MenuID'] &&
				$row['MenuID'] != $values['MenuID'])
				$updatesql[] = " `MenuID` = ".(int)$values['MenuID'];
			
			if ($menuitem['ViewableBy'] != $values['ViewableBy'] &&
				$row['ViewableBy'] != $values['ViewableBy'])
				$updatesql[] = " `ViewableBy` = '".(int)$values['ViewableBy']."'";
			
			if (((($menuitem['Deactivated'] && !$values['Deactivated']) ||
				(!$menuitem['Deactivated'] && $values['Deactivated'])) &&
				 !$row['Deactivated'] && $values['Deactivated']) ||
				($menuitem['ViewableBy'] != $values['ViewableBy'] &&
				 $row['ViewableBy'] < $values['ViewableBy'] && $values['ViewableBy'] > 1))
				$sitemap->delete($url);
			
			if (((($menuitem['Deactivated'] && !$values['Deactivated']) ||
				(!$menuitem['Deactivated'] && $values['Deactivated'])) &&
				 $row['Deactivated'] && !$values['Deactivated']) || 
				($menuitem['ViewableBy'] != $values['ViewableBy'] &&
				 $row['ViewableBy'] > $values['ViewableBy'] && $values['ViewableBy'] < 2))
				$sitemap->add(array('Link' => $url));
			
			if ($updatesql)
				sql::run(
					" UPDATE `{menuitems}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
		}
		
		foreach(menuItems::getBackTraceTree((int)$id) as $row) {
			$updatesql = null;
			$url = str_replace('&amp;', '&', $this->generateLink($row));
			
			if ($row['Hidden'] && !$values['Hidden'])
				$updatesql[] = " `Hidden` = 0";
			if ($row['Deactivated'] && !$values['Deactivated'])
				$updatesql[] = " `Deactivated` = 0";
			if ($row['ViewableBy'] > $values['ViewableBy'])
				$updatesql[] = " `ViewableBy` = '".(int)$values['ViewableBy']."'";
			
			if (($row['Deactivated'] && !$values['Deactivated']) ||
				($row['ViewableBy'] > $values['ViewableBy'] && $values['ViewableBy'] < 2))
				$sitemap->add(array('Link' => $url));
			
			if ($updatesql)
				sql::run(
					" UPDATE `{menuitems}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
		}

		if (!$sitemap->save())
			tooltip::display(
				__("Menu item successfully updated but xml sitemap file couldn't be updated.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"sitemap.xml"),
				TOOLTIP_NOTIFICATION);
		
		unset($sitemap);
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		$posts = new posts();
		$menuids = array($id);
		
		$sitemap = new siteMap();
		$sitemap->load();
		
		foreach(menuItems::getTree((int)$id) as $row)
			$menuids[] = $row['ID'];
		
		foreach($menuids as $menuid) {
			$menu = sql::fetch(sql::run(
				" SELECT * FROM `{menuitems}`" .
				" WHERE `ID` = '".$menuid."'"));
			
			$rows = sql::run(
				" SELECT * FROM `{posts}` " .
				" WHERE `MenuItemID` = '".$menuid."'");
			
			while($row = sql::fetch($rows))
				$posts->delete($row['ID']);
			
			sql::run(
				" DELETE FROM `{menuitemmodules}` " .
				" WHERE `MenuItemID` = '".$menuid."'");
			
			sql::run(
				" DELETE FROM `{menuitems}` " .
				" WHERE `ID` = '".$menuid."'");
			
			$url = str_replace('&amp;', '&', $this->generateLink($menu));
			$sitemap->delete($url);
		}
		
		if (!$sitemap->save())
			tooltip::display(
				__("Menu item successfully deleted but xml sitemap file couldn't be updated.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"sitemap.xml"),
				TOOLTIP_NOTIFICATION);
		
		unset($sitemap);
		unset($posts);
		
		return true;
	}
	
	function updateSitemap($menuid = null) {
		$sitemap = new sitemap();
		
		if ($menuid) {
			$menu = sql::fetch(sql::run(
				" SELECT `Title`, `Name` " .
				" FROM `{menus}` " .
				" WHERE `ID` = '".(int)$menuid."'"));
			
			if (!$menu['Name'])
				return false;
			
			$sitemap->file = SITE_PATH.'sitemap-'.preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '',
					str_replace('/', '-', $menu['Name'])).'.xml';
		}
		
		$rows = sql::run(
			" SELECT * FROM `{menuitems}`" .
			" WHERE !`Deactivated`" .
			" AND `ViewableBy` < 2" .
			($menuid?
				" AND `MenuID` = '".(int)$menuid."'":
				null) .
			" ORDER BY `MenuID`, `OrderID`");
			
		while($row = sql::fetch($rows)) {
			$lastpost = sql::fetch(sql::run(
				" SELECT `TimeStamp` " .
				" FROM `{posts}` " .
				" WHERE `MenuItemID` = '".$row['ID']."'" .
				" AND !`Deactivated`" .
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
		
		if (!$sitemap->save()) {
			unset($sitemap);
			return false;
		}
		
		unset($sitemap);
		return true;
	}
	
	// ************************************************   Client Part
	static function access2Text($typeid) {
		switch($typeid) {
			case MENU_ADMINS_ONLY:
				return __('Admins');
			case MENU_GUESTS_ONLY:
				return __('Guests');
			case MENU_USERS_ONLY:
				return __('Members');
			default:
				return __('Everyone');
		}
	}
	
	static function isMainMenu($id, $languageid = 0) {
		$mainmenu = menuItems::getMainMenu($languageid);
		
		if ($mainmenu['ID'] == $id)
			return true;
			
		return false;
	}
	
	static function getTree($menuid = 0, $firstcall = true, 
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0)) 
	{
		$rows = sql::run(
			" SELECT * FROM `{menuitems}` " .
			($menuid?
				" WHERE `SubmenuOfID` = '".$menuid."'":
				" WHERE !`SubmenuOfID`") .
			" ORDER BY " .
				(menus::$order?
					" FIELD(`MenuID`, ".menus::$order."),":
					" `MenuID`,") .
				" `LanguageID`, `OrderID`");
		
		while($row = sql::fetch($rows)) {
			$last = end($tree['Tree']);
			
			if ($row['MenuID'] && (!$last || $last['MenuID'] != $row['MenuID'])) {
				$menu = sql::fetch(sql::run(
					" SELECT `Title` FROM `{menus}`" .
					" WHERE `ID` = '".$row['MenuID']."'"));
				
				$tree['Tree'][] = array(
					'ID' => 0,
					'Title' => $menu['Title'],
					'SubMenuOfID' => 0,
					'PathDeepnes' => 0);
			}
			
			$row['PathDeepnes'] = $tree['PathDeepnes'];
			$tree['Tree'][] = $row;
			
			$tree['PathDeepnes']++;
			menuItems::getTree($row['ID'], false, $tree);
			$tree['PathDeepnes']--;
		}
		
		if ($firstcall)
			return $tree['Tree'];
	}
	
	static function getBackTraceTree($id, $firstcall = true, $fields = '*',
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0)) 
	{
		if (!(int)$id)
			return array();
		
		$row = sql::fetch(sql::run(
			" SELECT ".$fields." FROM `{menuitems}` " .
			" WHERE `ID` = '".(int)$id."'"));
		
		if (!$row)
			return array();
		
		if (isset($row['SubMenuOfID']) && $row['SubMenuOfID'])	
			menuItems::getBackTraceTree($row['SubMenuOfID'], false, $fields, $tree);
		
		$row['PathDeepnes'] = $tree['PathDeepnes'];
		$tree['Tree'][] = $row;
		$tree['PathDeepnes']++;
		
		if ($firstcall)
			return $tree['Tree'];
	}
	
	static function getMainMenu($languageid = null) {
		if (!isset($languageid) && languages::$selected)
			$languageid = languages::$selected['ID'];
		
		return sql::fetch(sql::run(
			" SELECT * FROM `{menuitems}` " .
			" WHERE !`Deactivated`" .
			" AND !`SubMenuOfID`" .
			" AND `LanguageID` = '".(int)$languageid."'" .
			" ORDER BY " .
				(menus::$order?
					" FIELD(`MenuID`, ".menus::$order."),":
					" `MenuID`,") .
				" `OrderID`" .
			" LIMIT 1"));
	}
	
	static function getMainMenuIDs() {
		$languageids = languages::getIDs();
		
		if (!$languageids) {
			if ($menu = menuitems::getMainMenu())
				return array($menu['ID']);
			
			return false;
		}
		
		$menuids = null;
		
		if (!languages::getDefault())
			array_unshift($languageids, 0);
		else
			$languageids[] = 0;
		
		foreach($languageids as $languageid) {
			if ($menu = menuitems::getMainMenu($languageid))
				$menuids[] = $menu['ID'];
		}
		
		return $menuids;
	}
	
	function getSelectedMenuIDs($menuid = null) {
		if (!$menuid)
			$menuid = $this->selectedID;
		
		$row = sql::fetch(sql::run(
			" SELECT `ID`, `SubMenuOfID`" .
				($menuid == $this->selectedID?
					", `Path`, `MenuID`, `LanguageID`":
					null) .
			" FROM `{menuitems}`" .
			" WHERE `ID` = '".(int)$menuid."'" .
			" LIMIT 1"));
		
		if (!$row)
			return false;
		
		$this->selectedIDs[] = $row['ID'];
		
		if ($row['SubMenuOfID'])
			$this->getSelectedMenuIDs($row['SubMenuOfID']);
		
		if ($menuid == $this->selectedID) {
			$aliasmenu = sql::fetch(sql::run(
				" SELECT `ID`, `SubMenuOfID`" .
				" FROM `{menuitems}`" .
				" WHERE '".sql::escape($row['Path'])."/' LIKE CONCAT(`Path`,'/%')" .
				" AND `MenuID` != '".$row['MenuID']."'" .
				" AND `LanguageID` = '".$row['LanguageID']."'" .
				" AND !`Deactivated`" .
				" ORDER BY `Path` DESC"));
			
			if ($aliasmenu) {
				$this->selectedIDs[] = $aliasmenu['ID'];
				
				if ($aliasmenu['SubMenuOfID'])
					$this->getSelectedMenuIDs($aliasmenu['SubMenuOfID']);
			}
		}
		
		return true;
	}
	
	function generateLink(&$row) {
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
			'&amp;menuid='.$row['ID'];
	}
	
	static function displayModules($menuid) {
		if (!$menuid)
			return false;
		
		$modules = new modules();
		$modules->sqlTable = 'menuitemmodules';
		$modules->sqlRow = 'MenuItemID';
		$modules->sqlOwnerTable = 'menuitems';
		$modules->selectedOwner = 'Menu';
		$modules->selectedOwnerID = $menuid;
		$modules->display();
		unset($modules);
	}
	
	function displayTitle(&$row) {
		echo 
			"<a href='".$row['_Link']."'>" .
				"<span>" .
				$row['Title'] .
				"</span>" .
			"</a>";
	}
	
	function displaySubmenus(&$row) {
		$rows = sql::run(
			" SELECT * FROM `{menuitems}`" .
			" WHERE !`Deactivated`" .
			" AND !`Hidden`" .
			" AND `MenuID` = '".(int)$this->selectedMenuID."'" .
			" AND `LanguageID` = '".
				(languages::$selected?
					(int)languages::$selected['ID']:
					0) .
				"'" .
			" AND `SubMenuOfID` = '".(int)$row['ID']."'" .
			" AND (!`ViewableBy` OR " .
				($GLOBALS['USER']->loginok?
					($GLOBALS['USER']->data['Admin']?
						" `ViewableBy` IN (2, 3)":
						" `ViewableBy` = 2"):
					" `ViewableBy` = 1") .
			" )" .
			" ORDER BY `OrderID`");
		
		$i = 1;
		$items = sql::rows($rows);
		
		if (!$items)
			return;
		
		echo 
			"<" .
			(JCORE_VERSION >= '0.5'?
				'ul':
				'div') .
			" class='" .
				(JCORE_VERSION < '0.6'?
					"sub-menu-of-id-".$row['ID']." ":
					null) .
				"sub-menu'>";
		
		while ($row = sql::fetch($rows)) {
			$row['_CSSClass'] = null;
			
			if ($i == 1)
				$row['_CSSClass'] .= ' first';
			if ($i == $items)
				$row['_CSSClass'] .= ' last';
			 
			$this->displayOne($row);
			
			$i++;
		}
		
		echo 
			"</" .
			(JCORE_VERSION >= '0.5'?
				'ul':
				'div') .
			">"; //submenu
	}
	
	function displayOne(&$row = null) {
		if ($row['Link'])
			$row['_Link'] = url::generateLink(
				$row['Link']);
		else
			$row['_Link'] = $this->generateLink($row);
		
		echo 
			"<" .
			(JCORE_VERSION >= '0.5'?
				'li':
				'div') .
			(JCORE_VERSION < '0.6' || !isset($this->arguments)?
				" id='menu".(int)$row['ID']."'":
				null) .
				" " .
				"class='menu" .
					(in_array($row['ID'], $this->selectedIDs)?
						" selected":
						NULL) .
					(isset($row['_CSSClass'])?
						" ".$row['_CSSClass']:
						null) .
					" menu".(int)$row['ID'] .
					"'>";
			
		$this->displayTitle($row);
		$this->displaySubmenus($row);
		
		echo
			"</" .
			(JCORE_VERSION >= '0.5'?
				'li':
				'div') .
			">";
	}
	
	function display() {
		$this->getSelectedMenuIDs();
		
		$rows = sql::run(
			$this->SQL());
		
		$i = 1;
		$items = sql::rows($rows);
		
		while ($row = sql::fetch($rows)) {
			$row['_CSSClass'] = null;
			
			if ($i == 1)
				$row['_CSSClass'] .= ' first';
			if ($i == $items)
				$row['_CSSClass'] .= ' last';
			 
			$this->displayOne($row);
			
			$i++;
		}
	}
}

?>