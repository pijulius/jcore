<?php

/***************************************************************************
 *            postshandling.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

class _postsHandling {
	var $adminPath = 'admin/content/postshandling';
	
	// ************************************************   Admin Part
	function verifyAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::verifyAdmin', $this);
		
		$delete = null;
		$copy = null;
		$move = null;
		$id = null;
		$ids = null;
		$topageid = null;
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_POST['copysubmit']))
			$copy = (string)$_POST['copysubmit'];
		
		if (isset($_POST['movesubmit']))
			$move = (string)$_POST['movesubmit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
			
		if (isset($_POST['postids']))
			$ids = (array)$_POST['postids'];
			
		if (isset($_POST['topageid']))
			$topageid = (int)$_POST['topageid'];
		
		if ($delete) {
			$result = $this->delete($id);
			
			if ($result)
				tooltip::display(
					__("Post has been successfully deleted."),
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'postsHandling::verifyAdmin', $this, $result);
			
			return $result;
		}
		
		if (!$copy && !$move) {
			api::callHooks(API_HOOK_AFTER,
				'postsHandling::verifyAdmin', $this);
			
			return false;
		}
			
		if (!$ids) {
			tooltip::display(
				__("No posts selected! Please select at least one post that you " .
					"would like to copy and/or move."),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'postsHandling::verifyAdmin', $this);
			
			return false;
		}
		
		if (!$topageid) {
			tooltip::display(
				__("No page selected to copy and/or move posts to! Please select " .
					"a page to move the posts to at the bottom of the list."),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'postsHandling::verifyAdmin', $this);
			
			return false;
		}
		
		$topage = sql::fetch(sql::run(
			" SELECT * FROM `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}`" .
			" WHERE `ID` = '".(int)$topageid."'"));
		
		if (!$topage) {
			tooltip::display(
				__("The page you have selected to copy and/or move posts to does not " .
					"exists. This could be happening because the page has been " .
					"deleted meanwhile."),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'postsHandling::verifyAdmin', $this);
			
			return false;
		}
		
		if ($move) {
			foreach($ids as $postid)
				$this->move((int)$postid, $topage['ID']);
					
			$_POST = array();
			
			tooltip::display(
				sprintf(__("Posts have been successfully moved over to \"%s\"."),
					$topage['Title']),
				TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'postsHandling::verifyAdmin', $this, $move);
			
			return true;
		}
		
		foreach($ids as $postid)
			$this->copy((int)$postid, $topage['ID']);
		
		$_POST = array();
		
		tooltip::display(
			sprintf(__("Posts have been successfully copied over to \"%s\"."),
				$topage['Title']),
			TOOLTIP_SUCCESS);
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::verifyAdmin', $this, $copy);
		
		return true;
	}
	
	function setupAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::setupAdmin', $this);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('Pages / Posts'), 
				'?path=' .
				(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));
		
		favoriteLinks::add(
			__('Settings'), 
			'?path=admin/site/settings');
		favoriteLinks::add(
			__('View Website'), 
			SITE_URL);
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::setupAdmin', $this);
	}
	
	function displayAdminListHeader($pageroute = null) {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::displayAdminListHeader', $this, $pageroute);
		
		echo
			"<th>" .
				"<input type='checkbox' class='checkbox-all' alt='.list' " .
				(~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE?
					"disabled='disabled' ":
					null) .
				"/>" .
			"</th>" .
			"<th>" .
				"<div class='nowrap'>" .
				$pageroute .
				"</div>" .
			"</th>";
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::displayAdminListHeader', $this, $pageroute);
	}
	
	function displayAdminListHeaderOptions() {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::displayAdminListHeaderOptions', $this);
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::displayAdminListHeaderFunctions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::displayAdminListItem', $this, $row);
		
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
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListItemOptions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::displayAdminListItemOptions', $this, $row);
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::displayAdminListItemFunctions', $this, $row);
		
		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='?path=" .
					($row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]?
						(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems') .
							"/".$row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."/posts":
						"admin/content/postsatglance") .
					"&amp;search=".urlencode($row['Title']) .
					"&amp;id=".$row['ID'].
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
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminListItemSelected(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::displayAdminListItemSelected', $this, $row);
		
		$post = new posts();
		$post->displayAdminListItemSelected($row);
		unset($post);
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::displayAdminListItemSelected', $this, $row);
	}
	
	function displayAdminListSearch() {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::displayAdminListSearch', $this);
		
		$pageid = null;
		$search = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags((string)$_GET['search']));
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::displayAdminListSearch', $this);
	}
	
	function displayAdminListFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::displayAdminListFunctions', $this);
		
		$topageid = null;
		
		if (isset($_POST['topageid']))
			$topageid = (int)$_POST['topageid'];
		
		echo 
			__("Copy / Move selected posts to:")." " .
			"<select name='topageid'>" .
				"<option value=''></option>";
		
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
					($topageid == $row['ID']?
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
			"<input type='submit' name='copysubmit' value='" .
				htmlspecialchars(__("Copy"), ENT_QUOTES)."' class='button submit' /> " .
			"<input type='submit' name='movesubmit' value='" .
				htmlspecialchars(__("Move"), ENT_QUOTES)."' class='button' /> ";
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::displayAdminListFunctions', $this);
	}
	
	function displayAdminList(&$rows) {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::displayAdminList', $this, $rows);
		
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
			
		echo
			"<form action='".
				url::uri('delete')."' method='post'>";
		
		$i = 0;
		$pageid = null;
		$pageroute = null;
		
		while($row = sql::fetch($rows)) {
			if ($pageid !== $row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]) {
				$pageroute = null;
				
				if ($row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]) {
					foreach(pages::getBackTraceTree($row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]) as $page) {
						$pageroute .=
							"<div".
								($page['ID'] == $row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]?
									" class='bold' style='font-size: 120%;'":
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
					
				} else {
					$pageroute = __('Title / Created on');
				}
		
				if (isset($pageid))
					echo 
						"</tbody>" .
					"</table>" .
					"<br />";
				
				echo 
					"<table cellpadding='0' cellspacing='0' class='list'>" .
					"<thead>" .
					"<tr>";
					
				$this->displayAdminListHeader($pageroute);
				$this->displayAdminListHeaderOptions();
							
				if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
					$this->displayAdminListHeaderFunctions();
				
				echo
					"</tr>" .
					"</thead>" .
					"<tbody>";
					
				$pageid = $row[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')];
			}
			
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
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::displayAdminList', $this, $rows);
	}
	
	function displayAdminTitle($ownertitle = null) {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::displayAdminTitle', $this, $ownertitle);
		
		admin::displayTitle(
			__('Handling Posts'),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::displayAdminDescription', $this);
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::displayAdmin', $this);
		
		$pageid = null;
		$search = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags((string)$_GET['search']));
		
		if (isset($_GET['searchpageid']))
			$pageid = (int)$_GET['searchpageid'];
		
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$this->verifyAdmin();
		
		$paging = new paging(10);
		
		$rows = sql::run(
			" SELECT * FROM `{posts}` " .
			" WHERE 1" .
			($pageid?
				" AND `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
					$pageid."'":
				null) .
			($search?
				" AND (`Title` LIKE '%".sql::escape($search)."%' " .
				" 	OR `Keywords` LIKE '%".sql::escape($search)."%') ":
				null) .
			" ORDER BY `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."`," .
				" `OrderID`, `ID` DESC" .
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
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::displayAdmin', $this);
	}
	
	function copy($postid, $topageid) {
		if (!$postid || !$topageid)
			return false;
			
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::copy', $this, $postid, $topageid);
		
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
			
			api::callHooks(API_HOOK_AFTER,
				'postsHandling::copy', $this, $postid, $topageid);
			
			return false;
		}
		
		$page = sql::fetch(sql::run(
			" SELECT * FROM `{pages}`" .
			" WHERE `ID` = '".(int)$topageid."'"));
		
		if (JCORE_VERSION >= '0.5')
			sql::run(
				" UPDATE `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}` SET " .
				" `Posts` = `Posts` + 1" .
				" WHERE `ID` = '".(int)$topageid."'");
		
		sql::run(
			" UPDATE `{posts}` SET" .
			" `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
				$topageid."'," .
			(JCORE_VERSION >= '0.9'?
				" `LanguageID` = '".$page['LanguageID']."',":
				null) .
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
				$post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]);
		}
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::copy', $this, $postid, $topageid, $newpostid);
		
		return $newpostid;
	}
	
	function move($postid, $topageid) {
		if (!$postid || !$topageid)
			return false;
			
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::move', $this, $postid, $topageid);
		
		$post = sql::fetch(sql::run(
			" SELECT * FROM `{posts}`" .
			" WHERE `ID` = '".(int)$postid."'"));
		
		$page = sql::fetch(sql::run(
			" SELECT * FROM `{pages}`" .
			" WHERE `ID` = '".(int)$topageid."'"));
		
		sql::run(
			" UPDATE `{posts}` SET" .
			" `".(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')."` = '" .
				$topageid."'," .
			(JCORE_VERSION >= '0.9'?
				" `LanguageID` = '".$page['LanguageID']."',":
				null) .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".(int)$postid."'");
		
		$result = (sql::affected() != -1);
		
		if (!$result) {
			tooltip::display(
				sprintf(__("Post couldn't be moved! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'postsHandling::move', $this, $postid, $topageid);
			
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
				" WHERE `ID` = '".(int)$topageid."'");
			
			sql::run(
				" UPDATE `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}` SET " .
				" `Posts` = `Posts` - 1" .
				" WHERE `ID` = '" .
					(int)$post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]."'");
		}
		
		if (JCORE_VERSION >= '0.7')
			posts::updateKeywordsCloud(
				$post['Keywords'], $post['Keywords'],
				$topageid, $post[(JCORE_VERSION >= '0.8'?'PageID':'MenuItemID')]);
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::move', $this, $postid, $topageid, $result);
		
		return $result;
	}
	
	function delete($id) {
		if (!$id)
			return false;
			
		api::callHooks(API_HOOK_BEFORE,
			'postsHandling::delete', $this, $id);
		
		$posts = new posts();
		$deleted = $posts->delete($id);
		unset($posts);
		
		api::callHooks(API_HOOK_AFTER,
			'postsHandling::delete', $this, $id, $deleted);
		
		return $deleted;	
	}
}

?>