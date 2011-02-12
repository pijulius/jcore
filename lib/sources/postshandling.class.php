<?php

/***************************************************************************
 *            postshandling.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/

class _postsHandling {
	var $adminPath = 'admin/content/postshandling';
	
	// ************************************************   Admin Part
	function verifyAdmin() {
		$delete = null;
		$copy = null;
		$move = null;
		$id = null;
		$ids = null;
		$tomenuid = null;
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_POST['copysubmit']))
			$copy = $_POST['copysubmit'];
		
		if (isset($_POST['movesubmit']))
			$move = $_POST['movesubmit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
			
		if (isset($_POST['postids']))
			$ids = (array)$_POST['postids'];
			
		if (isset($_POST['tomenuid']))
			$tomenuid = (int)$_POST['tomenuid'];
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				__("Post has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$copy && !$move)
			return false;
			
		if (!$ids) {
			tooltip::display(
				__("No posts selected! Please select at least one post that you " .
					"would like to copy and/or move."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!$tomenuid) {
			tooltip::display(
				__("No menu selected to copy and/or move posts to! Please select " .
					"a menu to move the posts to at the bottom of the list."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$tomenu = sql::fetch(sql::run(
			" SELECT * FROM `{menuitems}`" .
			" WHERE `ID` = '".$tomenuid."'"));
		
		if (!$tomenu) {
			tooltip::display(
				__("The menu you have selected to copy and/or move posts to does not " .
					"exists. This could be happening because the menu has been " .
					"deleted meanwhile."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if ($move) {
			foreach($ids as $postid)
				$this->move($postid, $tomenu['ID']);
					
			$_POST = array();
			
			tooltip::display(
				sprintf(__("Posts have been successfully moved over to \"%s\"."),
					$tomenu['Title']),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		foreach($ids as $postid)
			$this->copy($postid, $tomenu['ID']);
		
		$_POST = array();
		
		tooltip::display(
			sprintf(__("Posts have been successfully copied over to \"%s\"."),
				$tomenu['Title']),
			TOOLTIP_SUCCESS);
		
		return true;
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('Pages / Posts'), 
				'?path=admin/content/menuitems');
		
		favoriteLinks::add(
			__('Settings'), 
			'?path=admin/site/settings');
		favoriteLinks::add(
			__('View Website'), 
			SITE_URL);
	}
	
	function displayAdminListHeader($isownermainmenu = false, $menuroute = null) {
		echo
			"<th>" .
				"<input type='checkbox' class='checkbox-all' alt='.list' " .
				($this->userPermissionType != USER_PERMISSION_TYPE_WRITE?
					"disabled='disabled' ":
					null) .
				"/>" .
			"</th>" .
			"<th>" .
				"<div class='nowrap'>" .
				$menuroute .
				"</div>" .
			"</th>" .
			($isownermainmenu?
				"<th style='text-align: right;' " .
					"title='".htmlspecialchars(__("Display On All Pages"), ENT_QUOTES)."'>" .
					"<span class='nowrap'>".__("On All Pages")."</span></th>":
				"<th style='text-align: right;' " .
					"title='".htmlspecialchars(__("Display On Main Page"), ENT_QUOTES)."'>" .
					"<span class='nowrap'>".__("On Main Page")."</span></th>");
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
		$ids = null;
		
		if (isset($_POST['postids']))
			$ids = (array)$_POST['postids'];
		
		$user = $GLOBALS['USER']->get($row['UserID']);
		
		echo
			"<td>" .
				"<input type='checkbox' name='postids[]' " .
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
				"<a href='".url::uri('id, delete') .
					"&amp;id=".$row['ID']."' " .
					" class='bold'>" .
					$row['Title'] .
				"</a>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					calendar::dateTime($row['TimeStamp']) .
					($user?
						" ".sprintf(__("by %s"),
							$GLOBALS['USER']->constructUserName($user)):
						null) .
					", ".sprintf(__("%s views"), $row['Views']) .
				"</div>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				($row['OnMainPage']?
					__("Yes"):
					null) .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
	}
	
	function displayAdminListItemFunctions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='?path=admin/content/menuitems/".
					$row['MenuItemID']."/posts&amp;id=".$row['ID'].
					"&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayAdminListItemSelected(&$row) {
		$post = new posts();
		$post->displayAdminListItemSelected($row);
		unset($post);
	}
	
	function displayAdminListSearch() {
		$menuid = null;
		$search = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['searchmenuid']))
			$menuid = (int)$_GET['searchmenuid'];
		
		echo 
			"<input type='hidden' name='path' value='".admin::path()."' />" .
			"<input type='search' name='search' value='".
				htmlspecialchars($search, ENT_QUOTES).
				"' results='5' placeholder='".htmlspecialchars(__("search..."), ENT_QUOTES)."' /> " .
			"<select style='width: 100px;' name='searchmenuid' onchange='this.form.submit();'>" .
				"<option value=''>".__("All")."</option>";
		
		$optgroup = false;
		
		foreach(menuItems::getTree() as $row) {
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
					($menuid == $row['ID']?
						" selected='selected'":
						null) .
					">" . 
				($row['SubMenuOfID']?
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
	
	function displayAdminListFunctions() {
		$tomenuid = null;
		
		if (isset($_POST['tomenuid']))
			$tomenuid = (int)$_POST['tomenuid'];
		
		echo 
			__("Copy / Move selected posts to:")." " .
			"<select name='tomenuid'>" .
				"<option value=''></option>";
		
		$optgroup = false;
		
		foreach(menuItems::getTree() as $row) {
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
					($tomenuid == $row['ID']?
						" selected='selected'":
						null) .
					">" . 
				($row['SubMenuOfID']?
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
			"<input type='submit' name='copysubmit' value='" .
				htmlspecialchars(__("Copy"), ENT_QUOTES)."' class='button submit' /> " .
			"<input type='submit' name='movesubmit' value='" .
				htmlspecialchars(__("Move"), ENT_QUOTES)."' class='button submit' /> ";
	}
	
	function displayAdminList(&$rows) {
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
			
		echo
			"<form action='".
				url::uri('delete')."' method='post'>";
		
		$i = 0;
		$menuitemid = 0;
		$menuroute = null;
		$isownermainmenu = false;
		
		while($row = sql::fetch($rows)) {
			if ($menuitemid != $row['MenuItemID']) {
				$menuroute = null;
				
				foreach(menuItems::getBackTraceTree($row['MenuItemID']) as $menuitem) {
					$menuroute .=
						"<div".
							($menuitem['ID'] == $row['MenuItemID']?
								" class='bold' style='font-size: 120%;'":
								null) .
							">" . 
						($menuitem['SubMenuOfID']?
							str_replace(' ', '&nbsp;', 
								str_pad('', $menuitem['PathDeepnes']*4, ' ')).
							"|- ":
							null). 
						$menuitem['Title'] .
						"</div>";
				}
				
				$isownermainmenu = menuItems::isMainMenu(
					$row['MenuItemID'], $menuitem['LanguageID']);
		
				if ($menuitemid)
					echo 
						"</tbody>" .
					"</table>" .
					"<br />";
				
				echo 
					"<table cellpadding='0' cellspacing='0' class='list'>" .
					"<thead>" .
					"<tr>";
					
				$this->displayAdminListHeader($isownermainmenu, $menuroute);
				$this->displayAdminListHeaderOptions();
							
				if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
					$this->displayAdminListHeaderFunctions();
				
				echo
					"</tr>" .
					"</thead>" .
					"<tbody>";
					
				$menuitemid = $row['MenuItemID'];
			}
			
			echo
				"<tr".($i%2?" class='pair'":NULL).">";
				
			$this->displayAdminListItem($row);
			$this->displayAdminListItemOptions($row);
					
			if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
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
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Handling Posts'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$menuid = null;
		$search = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['searchmenuid']))
			$menuid = (int)$_GET['searchmenuid'];
		
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
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			$this->verifyAdmin();
		
		$paging = new paging(10);
		
		$rows = sql::run(
			" SELECT * FROM `{posts}` " .
			" WHERE 1" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			($menuid?
				" AND `MenuItemID` = '".$menuid."'":
				null) .
			($search?
				" AND (`Title` LIKE '%".sql::escape($search)."%' " .
				" 	OR `Keywords` LIKE '%".sql::escape($search)."%') ":
				null) .
			" ORDER BY `MenuItemID`, `OrderID`, `ID` DESC" .
			" LIMIT ".$paging->limit);
		
		$paging->setTotalItems(sql::count());
		
		if ($paging->items)
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No posts found."),
				TOOLTIP_NOTIFICATION);
		
		$paging->display();
		
		echo 
			"</div>";	//admin-content
	}
	
	function copy($postid, $tomenuid) {
		if (!$postid || !$tomenuid)
			return false;
			
		$columns = sql::run(
			" SHOW COLUMNS FROM `{posts}`" .
			" WHERE `Field` != 'ID'");
		
		$postcolumns = array();
		while($column = sql::fetch($columns))
			$postcolumns[] = $column['Field'];
		
		$newpostid = sql::run(
			" INSERT INTO `{posts}`" .
			" SELECT NULL, `".implode('`, `', $postcolumns)."`" .
			" FROM `{posts}`" .
			" WHERE `ID` = '".(int)$postid."'");
			
		if (!$newpostid) {
			tooltip::display(
				sprintf(__("Post couldn't be copied! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (JCORE_VERSION >= '0.5')
			sql::run(
				" UPDATE `{menuitems}` SET " .
				" `Posts` = `Posts` + 1" .
				" WHERE `ID` = '".(int)$tomenuid."'");
		
		sql::run(
			" UPDATE `{posts}` SET" .
			" `MenuItemID` = '".$tomenuid."'," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".$newpostid."'");
		
		$columns = sql::run(
			" SHOW COLUMNS FROM `{postpictures}`" .
			" WHERE `Field` != 'ID'");
		
		$picturecolumns = array();	
		while($column = sql::fetch($columns))
			$picturecolumns[] = $column['Field'];
		
		$pictures = sql::run(
			" SELECT `ID` FROM `{postpictures}`" .
			" WHERE `PostID` = '".(int)$postid."'");
		
		while($picture = sql::fetch($pictures)) {
			$newpictureid = sql::run(
				" INSERT INTO `{postpictures}`" .
				" SELECT NULL, `".implode('`, `', $picturecolumns)."`" .
				" FROM `{postpictures}`" .
				" WHERE `ID` = '".$picture['ID']."'");
				
			sql::run(
				" UPDATE `{postpictures}` SET" .
				" `PostID` = '".$newpostid."'," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".$newpictureid."'");
		}
		
		$columns = sql::run(
			" SHOW COLUMNS FROM `{postattachments}`" .
			" WHERE `Field` != 'ID'");
		
		$attachmentcolumns = array();	
		while($column = sql::fetch($columns))
			$attachmentcolumns[] = $column['Field'];
		
		$attachments = sql::run(
			" SELECT `ID` FROM `{postattachments}`" .
			" WHERE `PostID` = '".(int)$postid."'");
		
		while($attachment = sql::fetch($attachments)) {
			$newattachmentid = sql::run(
				" INSERT INTO `{postattachments}`" .
				" SELECT NULL, `".implode('`, `', $attachmentcolumns)."`" .
				" FROM `{postattachments}`" .
				" WHERE `ID` = '".$attachment['ID']."'");
				
			sql::run(
				" UPDATE `{postattachments}` SET" .
				" `PostID` = '".$newpostid."'," .
				" `TimeStamp` = TimeStamp" .
				" WHERE `ID` = '".$newattachmentid."'");
		}
		
		if (JCORE_VERSION >= '0.7') {
			$post = sql::fetch(sql::run(
				" SELECT * FROM `{posts}`" .
				" WHERE `ID` = '".$newpostid."'"));
			
			posts::updateKeywordsCloud(
				$post['Keywords'], null,
				$post['MenuItemID']);
		}
		
		return $newpostid;
	}
	
	function move($postid, $tomenuid) {
		if (!$postid || !$tomenuid)
			return false;
			
		$post = sql::fetch(sql::run(
			" SELECT * FROM `{posts}`" .
			" WHERE `ID` = '".(int)$postid."'"));
		
		sql::run(
			" UPDATE `{posts}` SET" .
			" `MenuItemID` = '".$tomenuid."'," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".(int)$postid."'");
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Post couldn't be moved! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (JCORE_VERSION >= '0.5') {
			sql::run(
				" UPDATE `{menuitems}` SET " .
				" `Posts` = `Posts` + 1" .
				" WHERE `ID` = '".(int)$tomenuid."'");
			
			sql::run(
				" UPDATE `{menuitems}` SET " .
				" `Posts` = `Posts` - 1" .
				" WHERE `ID` = '".(int)$post['MenuItemID']."'");
		}
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Post couldn't be moved! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (JCORE_VERSION >= '0.7')
			posts::updateKeywordsCloud(
				$post['Keywords'], $post['Keywords'],
				$tomenuid, $post['MenuItemID']);
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
			
		$posts = new posts();
		$deleted = $posts->delete($id);
		unset($posts);
		
		return $deleted;	
	}
}

?>