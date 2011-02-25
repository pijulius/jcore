<?php

/***************************************************************************
 *            postsatglance.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
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
	
	function setupAdminForm(&$form, $isownerhomepage = false) {
		$pageid = null;
		
		if (isset($_GET['searchpageid']))
			$pageid = (int)$_GET['searchpageid'];
		
		parent::setupAdminForm($form, $isownerhomepage);
		
		$form->edit(
			'PageID',
			__('Post to Page'),
			'PageID',
			FORM_INPUT_TYPE_SELECT,
			false,
			$pageid);
		
		$form->addValue('PageID', '', '');
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
			if ($deactivate) {
				foreach($ids as $id)
					$this->deactivate($id);
				
				tooltip::display(
					__("Posts have been successfully deactivated and " .
						"are now NOT visible to the public."),
					TOOLTIP_SUCCESS);
					
				return true;
			}
			
			if ($activate) {
				foreach($ids as $id)
					$this->activate($id);
				
				tooltip::display(
					__("Posts have been successfully activated and " .
						"are now visible to the public."),
					TOOLTIP_SUCCESS);
					
				return true;
			}
			
			if ($delete) {
				foreach($ids as $id)
					$this->delete($id);
				
				tooltip::display(
					__("Posts have been successfully deleted."),
					TOOLTIP_SUCCESS);
					
				return true;
			}
		}
		
		return parent::verifyAdmin($form);
	}
	
	function displayAdminListHeader($isownerhomepage = false) {
		echo
			"<th>" .
				"<input type='checkbox' class='checkbox-all' alt='.list' " .
				($this->userPermissionType != USER_PERMISSION_TYPE_WRITE?
					"disabled='disabled' ":
					null) .
				"/>" .
			"</th>" .
			"<th><span class='nowrap'>".
				__("Title / Created on")."</span></th>";
	}
	
	function displayAdminListItemSelected(&$row, $isownerhomepage = false) {
		if ($row['PageID']) {
			$pageroute = null;
			
			foreach(pages::getBackTraceTree($row['PageID']) as $page) {
				$pageroute .=
					"<div".
						($page['ID'] == $row['PageID']?
							" class='bold'":
							null) .
						">" . 
					($page['SubPageOfID']?
						str_replace(' ', '&nbsp;', 
							str_pad('', $page['PathDeepnes']*4, ' ')).
						"|- ":
						null). 
					$page['Title'] .
					"</div>";
			}
			
			$isownerhomepage = pages::isHome(
				$row['PageID'], 
				$page['LanguageID']);
	
			admin::displayItemData(
				__("Page"),
				$pageroute);
		}
		
		parent::displayAdminListItemSelected($row, $isownerhomepage);
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
					($this->userPermissionType != USER_PERMISSION_TYPE_WRITE?
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
				($row['SubPageOfID']?
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
		$edit = null;
		$id = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['searchpageid']))
			$pageid = (int)$_GET['searchpageid'];
		
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
		
		$selectedowner = sql::fetch(sql::run(
			" SELECT `Title`, `LanguageID` " .
			" FROM `{pages}` " .
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
		
		$verifyok = false;
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			$verifyok = $this->verifyAdmin($form);
		}
	
		$paging = new paging(10);
		$paging->ignoreArgs = 'id, edit, delete';
		
		foreach(pages::getTree() as $row)
			$form->addValue(
				'PageID',
				$row['ID'], 
				($row['SubPageOfID']?
					str_replace(' ', '&nbsp;', 
						str_pad('', $row['PathDeepnes']*4, ' ')).
					"|- ":
					null) .
				$row['Title']);
		
		$form->groupValues('PageID', array('0'));
		
		$rows = sql::run(
			" SELECT * FROM `{posts}` " .
			" WHERE 1" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			($pageid?
				" AND `PageID` = '".$pageid."'":
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
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{posts}`" .
					" WHERE `ID` = '".$id."'"));
		
				$form->setValues($row);
				
				$user = $GLOBALS['USER']->get($row['UserID']);
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