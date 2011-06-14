<?php

/***************************************************************************
 *            postsatglance.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

class _postsAtGlance extends posts {
	// ************************************************   Admin Part
	function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{posts}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdminForm(&$form) {
		$pageid = null;
		
		if (isset($_GET['searchpageid']))
			$pageid = (int)$_GET['searchpageid'];
		
		parent::setupAdminForm($form);
		
		$form->edit(
			(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID'),
			__('Post to Page'),
			(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID'),
			FORM_INPUT_TYPE_SELECT,
			false,
			$pageid);
		
		$form->addValue(
			(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID'), 
			'', '');
		
		foreach(pages::getTree() as $page)
			$form->addValue(
				(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID'),
				$page['ID'], 
				($page[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]?
					str_replace(' ', '&nbsp;', 
						str_pad('', $page['PathDeepnes']*4, ' ')).
					"|- ":
					null) .
				$page['Title']);
		
		$form->groupValues(
			(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID'), 
			array('0'));
		
		if (JCORE_VERSION >= '0.9' && $languages = languages::get()) {
			$form->edit(
				'LanguageID',
				__('Language'),
				'LanguageID',
				FORM_INPUT_TYPE_SELECT);
			
			$form->addValue(
				'LanguageID', 
				'', '');
			
			while($language = sql::fetch($languages))
				$form->addValue(
					'LanguageID',
					$language['ID'], 
					$language['Title']);
		}
	}
	
	function verifyAdmin(&$form) {
		$delete = null;
		$deactivate = null;
		$activate = null;
		$ids = null;
		
		if (isset($_POST['deletesubmit']))
			$delete = $_POST['deletesubmit'];
		
		if (isset($_POST['deactivatesubmit']))
			$deactivate = $_POST['deactivatesubmit'];
		
		if (isset($_POST['activatesubmit']))
			$activate = $_POST['activatesubmit'];
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
		if (!$ids && ($deactivate || $activate || $delete)) {
			tooltip::display(
				__("No posts selected! Please select at " .
					"least one post."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if ($ids && count($ids)) {
			$permissionids = null;
			if ($this->userPermissionIDs)
				$permissionids = explode(',', $this->userPermissionIDs);
			
			if ($deactivate) {
				foreach($ids as $id) {
					if ($permissionids && !in_array($id, $permissionids))
						continue;
					
					if ($this->userPermissionType & USER_PERMISSION_TYPE_OWN && 
						!sql::rows(sql::run(
							" SELECT `ID` FROM `{posts}`" .
							" WHERE `ID` = '".(int)$id."'" .
							" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'")))
						continue;
					
					$this->deactivate($id);
				}
				
				tooltip::display(
					__("Posts have been successfully deactivated and " .
						"are now NOT visible to the public."),
					TOOLTIP_SUCCESS);
					
				return true;
			}
			
			if ($activate) {
				foreach($ids as $id) {
					if ($permissionids && !in_array($id, $permissionids))
						continue;
					
					if ($this->userPermissionType & USER_PERMISSION_TYPE_OWN && 
						!sql::rows(sql::run(
							" SELECT `ID` FROM `{posts}`" .
							" WHERE `ID` = '".(int)$id."'" .
							" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'")))
						continue;
					
					$this->activate($id);
				}
				
				tooltip::display(
					__("Posts have been successfully activated and " .
						"are now visible to the public."),
					TOOLTIP_SUCCESS);
					
				return true;
			}
			
			if ($delete) {
				foreach($ids as $id) {
					if ($permissionids && !in_array($id, $permissionids))
						continue;
					
					if ($this->userPermissionType & USER_PERMISSION_TYPE_OWN && 
						!sql::rows(sql::run(
							" SELECT `ID` FROM `{posts}`" .
							" WHERE `ID` = '".(int)$id."'" .
							" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'")))
						continue;
					
					$this->delete($id);
				}
				
				tooltip::display(
					__("Posts have been successfully deleted."),
					TOOLTIP_SUCCESS);
					
				return true;
			}
		}
		
		return parent::verifyAdmin($form);
	}
	
	function displayAdminListHeader() {
		echo
			"<th>" .
				"<input type='checkbox' class='checkbox-all' alt='.list' " .
				(~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE?
					"disabled='disabled' ":
					null) .
				"/>" .
			"</th>" .
			"<th><span class='nowrap'>".
				__("Title / Created on")."</span></th>";
	}
	
	function displayAdminListItemSelected(&$row) {
		if ($row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]) {
			$pageroute = null;
			
			foreach(pages::getBackTraceTree($row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]) as $page) {
				$pageroute .=
					"<div ".
						($page['ID'] != $row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]?
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
			
			admin::displayItemData(
				__("Page"),
				$pageroute);
			
		} elseif (JCORE_VERSION >= '0.9' && $row['LanguageID']) {
			$language = languages::get($row['LanguageID']);
			
			admin::displayItemData(
				__("Language"),
				$language['Title']);
		}
		
		parent::displayAdminListItemSelected($row);
	}
	
	function displayAdminListItem(&$row) {
		$ids = null;
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
		$user = $GLOBALS['USER']->get($row['UserID']);
		
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
	}
	
	function displayAdminListFunctions() {
		echo
			"<input type='submit' name='deletesubmit' value='" .
				htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
				"class='button confirm-link' /> " .
			"<input type='submit' name='deactivatesubmit' value='" .
				htmlspecialchars(__("Deactivate"), ENT_QUOTES)."' class='button' />" .
			"<input type='submit' name='activatesubmit' value='" .
				htmlspecialchars(__("Activate"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminListSearch() {
		$pageid = null;
		$search = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['searchpageid']))
			$pageid = (int)$_GET['searchpageid'];
		
		echo 
			"<input type='hidden' name='path' value='".admin::path()."' />" .
			"<input type='search' name='search' value='".
				htmlspecialchars($search, ENT_QUOTES).
				"' results='5' placeholder='".htmlspecialchars(__("search..."), ENT_QUOTES)."' /> " .
			"<select style='width: 100px;' name='searchpageid' onchange='this.form.submit();'>" .
				"<option value=''>".__("All")."</option>";
		
		$optgroup = false;
		
		foreach(pages::getTree() as $row) {
			if ($row['ID'] === 0) {
				if ($optgroup)
					echo "</optgroup>";
				
				echo "<optgroup label='" .
					htmlspecialchars($row['Title'], ENT_QUOTES)."'>";
				
				$optgroup = true;
				continue;
			}
			
			echo
				"<option value='".$row['ID']."'" .
					($pageid == $row['ID']?
						" selected='selected'":
						null) .
					">" . 
				($row[(JCORE_VERSION >= '0.8'?'SubPageOfID':'SubMenuOfID')]?
					str_replace(' ', '&nbsp;', 
						str_pad('', $row['PathDeepnes']*4, ' ')).
					"|- ":
					null) .
				$row['Title'] .
				"</option>";
		}
	
		if ($optgroup)
			echo "</optgroup>";
		
		echo
			"</select> " .
			"<input type='submit' value='" .
				htmlspecialchars(__("Search"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Posts at Glance'), 
			$ownertitle);
	}
	
	function displayAdmin() {
		$pageid = null;
		$search = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['searchpageid']))
			$pageid = (int)$_GET['searchpageid'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
			
		if ($edit && isset($_POST['InsertAsNew']) && $_POST['InsertAsNew']) {
			$_GET['limit'] = null;
			$_GET['edit'] = null;
			$_GET['id'] = null;
			
			$edit = false;
			$id = null;
		}
		
		echo
			"<div style='float: right;'>" .
				"<form action='".url::uri('ALL')."' method='get'>";
		
		$this->displayAdminListSearch();
		
		echo
				"</form>" .
			"</div>";
		
		$this->displayAdminTitle();
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
		
		if ($id)
			$selected = sql::fetch(sql::run(
				" SELECT `ID`, `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` FROM `{posts}`" .
				" WHERE `ID` = '".$id."'" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
					" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'":
					null)));
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			((!$edit && !$delete) || $selected))
			$verifyok = $this->verifyAdmin($form);
		
		$paging = new paging(10);
		$paging->ignoreArgs = 'id, edit, delete';
		
		$rows = sql::run(
			" SELECT * FROM `{posts}` " .
			" WHERE 1" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
				" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'":
				null) .
			($pageid?
				" AND `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
					$pageid."'":
				null) .
			($search?
				sql::search(
					$search,
					array('Title', 'Content', 'Keywords')):
				null) .
			($search?
				" AND (`Title` LIKE '%".sql::escape($search)."%' " .
				" 	OR `Keywords` LIKE '%".sql::escape($search)."%') ":
				null) .
			" ORDER BY `TimeStamp` DESC, `OrderID`, `ID` DESC" .
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
			if (!$id || ($selected && 
				pages::isHome($selected[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')])))
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
	}
}

?>