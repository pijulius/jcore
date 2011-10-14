<?php

/***************************************************************************
 *            rss.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/files.class.php');
include_once('lib/calendar.class.php');

class _rss {
	var $file;
	var $channel = array();
	var $items = array();
	var $adminPath = 'admin/site/rss';
	var $ajaxRequest = null;
	
	function __construct() {
		api::callHooks(API_HOOK_BEFORE,
			'rss::rss', $this);
		
		$this->file = SITE_PATH.'rss/rss.xml';
		
		$this->channel['Title'] = PAGE_TITLE;
		$this->channel['Link'] = SITE_URL;
		$this->channel['Description'] = META_DESCRIPTION;
		$this->channel['ManagingEditor'] = WEBMASTER_EMAIL;
		$this->channel['WebMaster'] = WEBMASTER_EMAIL;
		$this->channel['TTL'] = 60;
		$this->channel['Logo'] = SITE_URL.'template/images/favicon.png';
		
		api::callHooks(API_HOOK_AFTER,
			'rss::rss', $this);
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		api::callHooks(API_HOOK_BEFORE,
			'rss::countAdminItems', $this);
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{rssfeeds}`" .
			" LIMIT 1"));
		
		api::callHooks(API_HOOK_AFTER,
			'rss::countAdminItems', $this, $row['Rows']);
		
		return $row['Rows'];
	}
	
	function setupAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'rss::setupAdmin', $this);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Feed'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=' .
			(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));
		
		api::callHooks(API_HOOK_AFTER,
			'rss::setupAdmin', $this);
	}
	
	function setupAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'rss::setupAdminForm', $this, $form);
		
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 250px;');
		
		$form->add(
			__('Feed URL'),
			'FeedURL',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 300px;');
		
		$form->addAdditionalText(
			"<a href='".url::uri('request, feeds').
				"&amp;request=".url::path() .
				"&amp;feeds=1' " .
				"class='select-link ajax-content-link' " .
				"title='".htmlspecialchars(__("Select Feed"), ENT_QUOTES)."'>" .
				__("Select Feed") .
			"</a>" .
			"<br /><span class='comment'>(" .
				__("e.g. http://jcore.net/rss/posts.xml") .
			")</span>");
		
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
			'rss::setupAdminForm', $this, $form);
	}
	
	function verifyAdmin(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'rss::verifyAdmin', $this, $form);
		
		$reorder = null;
		$orders = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_POST['reordersubmit']))
			$reorder = (string)$_POST['reordersubmit'];
		
		if (isset($_POST['orders']))
			$orders = (array)$_POST['orders'];
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($reorder) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'rss::verifyAdmin', $this, $form);
				return false;
			}
			
			foreach((array)$orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{rssfeeds}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'");
			}
			
			tooltip::display(
				__("RSS feeds have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'rss::verifyAdmin', $this, $form, $reorder);
			
			return true;
		}
		
		if ($delete && $id) {
			$result = $this->deleteFeed($id);
			
			if ($result)
				tooltip::display(
					__("RSS feed has been successfully deleted."),
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'rss::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'rss::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if ($edit) {
			$result = $this->editFeed($id, $form->getPostArray());
			
			if ($result)
				tooltip::display(
					__("RSS feed has been successfully updated.")." " .
					"<a href='".$form->get('FeedURL')."' target='_blank'>" .
						__("View RSS") .
					"</a>" .
					" - " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'rss::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		$newid = $this->addFeed($form->getPostArray());
		
		if ($newid) {
			tooltip::display(
				__("RSS feed has been successfully created.")." " .
				"<a href='".$form->get('FeedURL')."' target='_blank'>" .
					__("View RSS") .
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
			'rss::verifyAdmin', $this, $form, $newid);
		
		return $newid;
	}
	
	function displayAdminAvailableFeeds() {
		api::callHooks(API_HOOK_BEFORE,
			'rss::displayAdminAvailableFeeds', $this);
		
		if (!isset($_GET['ajaxlimit']))
			echo
				"<div class='rss-feeds-available-feeds'>";
		
		echo
				"<div class='form-title'>".__('Available Feeds')."</div>" .
				"<table cellpadding='0' cellspacing='0' class='form-content list'>" .
					"<thead>" .
					"<tr>" .
						"<th>" .
							"<span class='nowrap'>".
							__("Select") .
							"</span>" .
						"</th>" .
						"<th>" .
							"<span class='nowrap'>".
							__("RSS Feeds") .
							"</span>" .
						"</th>" .
					"</tr>" .
					"</thead>" .
					"<tbody>";
		
		$dir = SITE_PATH.'rss/';
		$files = array();
		
		if (is_dir($dir) && $dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if (!is_file($dir.'/'.$file) || $file == 'index.html')
					continue;
				
				$files[$file] = ucwords(str_replace('-', ' / ', 
					preg_replace('/\..*?$/', '', $file)));
			}
			
			closedir($dh);
		}
		
		$paging = new paging(10);
		
		$paging->track('ajaxlimit');
		$paging->ajax = true;
		$paging->setTotalItems(count($files));
		
		asort($files);
		$files = array_slice($files, $paging->getStart(), 10);
		
		if (!is_array($files))
			$files = array();
		
		$i = 1;	
		foreach($files as $file => $title) {
			echo
				"<tr".($i%2?" class='pair'":NULL).">" .
					"<td align='center'>" .
						"<a href='javascript://' " .
							"onclick=\"" .
								"$('#neweditrssfeedform #entryFeedURL').val('" .
									htmlspecialchars(SITE_URL.'rss/'.$file, ENT_QUOTES)."');" .
								(JCORE_VERSION >= '0.7'?
									"$(this).closest('.tipsy').hide();":
									"$(this).closest('.qtip').qtip('hide');") .
								"\" " .
							"class='rss-feeds-select-feed select-link'>" .
						"</a>" .
					"</td>" .
					"<td class='auto-width'>" .
						"<b>".$title."</b> " .
						"(".files::humanSize(filesize(SITE_PATH.'rss/'.$file)).")<br />" .
						"<div class='comment' style='padding-left: 10px;'>" .
							calendar::datetime(filemtime(SITE_PATH.'rss/'.$file)) .
						"</div>" .
					"</td>" .
				"</tr>";
			
			$i++;
		}
		
		echo
					"</tbody>" .
				"</table>";
		
		$paging->display();
		
		if (!isset($_GET['ajaxlimit']))
			echo
				"</div>";
		
		api::callHooks(API_HOOK_AFTER,
			'rss::displayAdminAvailableFeeds', $this);
	}
	
	function displayAdminListHeader() {
		api::callHooks(API_HOOK_BEFORE,
			'rss::displayAdminListHeader', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Feed URL")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'rss::displayAdminListHeader', $this);
	}
	
	function displayAdminListHeaderOptions() {
		api::callHooks(API_HOOK_BEFORE,
			'rss::displayAdminListHeaderOptions', $this);
		api::callHooks(API_HOOK_AFTER,
			'rss::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'rss::displayAdminListHeaderFunctions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'rss::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'rss::displayAdminListItem', $this, $row);
		
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
				"<div class='bold'>" .
					$row['Title'] .
				"</div>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					"<a href='".$row['FeedURL']."'>" .
						$row['FeedURL'] .
					"</a>" .
				"</div>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'rss::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListItemOptions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'rss::displayAdminListItemOptions', $this, $row);
		api::callHooks(API_HOOK_AFTER,
			'rss::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'rss::displayAdminListItemFunctions', $this, $row);
		
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
			'rss::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminListFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'rss::displayAdminListFunctions', $this);
		
		echo 
			"<input type='submit' name='reordersubmit' value='".
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
		
		api::callHooks(API_HOOK_AFTER,
			'rss::displayAdminListFunctions', $this);
	}
	
	function displayAdminList(&$rows) {
		api::callHooks(API_HOOK_BEFORE,
			'rss::displayAdminList', $this, $rows);
		
		echo
			"<form action='".url::uri('edit, delete')."' method='post'>" .
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
			'rss::displayAdminList', $this, $rows);
	}
	
	function displayAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'rss::displayAdminForm', $this, $form);
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'rss::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		api::callHooks(API_HOOK_BEFORE,
			'rss::displayAdminTitle', $this, $ownertitle);
		
		admin::displayTitle(
			__('RSS Feeds Administration'),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'rss::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'rss::displayAdminDescription', $this);
		api::callHooks(API_HOOK_AFTER,
			'rss::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'rss::displayAdmin', $this);
		
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
				
		$form = new form(
				($edit?
					__("Edit RSS Feed"):
					__("New RSS Feed")),
				'neweditrssfeed');
		
		if (!$edit)
			$form->action = url::uri('id, delete');
					
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$verifyok = $this->verifyAdmin($form);
		
		$rows = sql::run(
			" SELECT * FROM `{rssfeeds}`" .
			" ORDER BY `OrderID`, `ID`");
			
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No RSS feeds found."),
				TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{rssfeeds}`" .
					" WHERE `ID` = '".$id."'"));
				
				$form->setValues($selected);
			}
			
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form);
		
		echo 
			"</div>";	//admin-content
		
		api::callHooks(API_HOOK_AFTER,
			'rss::displayAdmin', $this);
	}
	
	function addFeed($values) {
		if (!is_array($values))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'rss::addFeed', $this, $values);
		
		if ($values['OrderID'] == '') {
			$row = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{rssfeeds}` " .
				" ORDER BY `OrderID` DESC"));
			
			$values['OrderID'] = (int)$row['OrderID']+1;
			
		} else {
			sql::run(
				" UPDATE `{rssfeeds}` SET " .
				" `OrderID` = `OrderID` + 1" .
				" WHERE `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		$newid = sql::run(
			" INSERT INTO `{rssfeeds}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `FeedURL` = '".
				sql::escape($values['FeedURL'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
		
		if (!$newid)
			tooltip::display(
				sprintf(__("Feed couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'rss::addFeed', $this, $values, $newid);
		
		return $newid;
	}
	
	function editFeed($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'rss::editFeed', $this, $id, $values);
		
		sql::run(
			" UPDATE `{rssfeeds}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `FeedURL` = '".
				sql::escape($values['FeedURL'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");
		
		$result = (sql::affected() != -1);
		
		if (!$result)
			tooltip::display(
				sprintf(__("Feed couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'rss::editFeed', $this, $id, $values, $result);
		
		return $result;
	}
	
	function deleteFeed($id) {
		if (!$id)
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'rss::deleteFeed', $this, $id);
		
		sql::run(
			" DELETE FROM `{rssfeeds}` " .
			" WHERE `ID` = '".(int)$id."'");
			
		api::callHooks(API_HOOK_AFTER,
			'rss::deleteFeed', $this, $id);
		
		return true;
	}
	
	// ************************************************   Client Part
	function getItemID($link = null) {
		if (!$link)
			return count($this->items)-1;
			
		$itemid = null;
		
		foreach($this->items as $itemnum => $item) {
			if (!isset($item['Link']))
				continue;
			
			if ($item['Link'] == $link) {
				$itemid = $itemnum;
				break;
			}
		}
		
		return $itemid;
	}
	
	function add($item) {
		if (!isset($item) || !is_array($item))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'rss::add', $this, $item);
		
		if (!isset($item['Title']))
			$item['Title'] = PAGE_TITLE;
		
		if (!isset($item['Link']))
			$item['Link'] = SITE_URL;
		
		if (!isset($item['Description']))
			$item['Description'] = null;
			
		if (!isset($item['TimeStamp']))
			$item['TimeStamp'] = date('Y-m-d H:i:s');
			
		if (!isset($item['Author']))
			$item['Author'] = WEBMASTER_EMAIL;
		
		if (!isset($item['Comments']))
			$item['Comments'] = 0;	
		
		$this->items[] = $item;
		$newid = $this->getItemID();
		
		api::callHooks(API_HOOK_AFTER,
			'rss::add', $this, $item, $newid);
		
		return $newid;
	}
	
	function edit($link, $item) {
		if (!isset($link))
			return false;
		
		if (!isset($item) || !is_array($item))
			return false;
		
		$itemid = $this->getItemID($link);
		if (!isset($itemid))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'rss::edit', $this, $item, $itemid);
		
		if (isset($item['Title']))
			$this->items[$itemid]['Title'] = $item['Title'];
		
		if (isset($item['Link']))
			$this->items[$itemid]['Link'] = $item['Link'];
		
		if (isset($item['Description']))
			$this->items[$itemid]['Description'] = $item['Description'];
		
		if (isset($item['TimeStamp']))
			$this->items[$itemid]['TimeStamp'] = $item['TimeStamp'];
		
		if (isset($item['Author']))
			$this->items[$itemid]['Author'] = $item['Author'];
		
		if (isset($item['Comments']))
			$this->items[$itemid]['Comments'] = $item['Comments'];

		api::callHooks(API_HOOK_AFTER,
			'rss::edit', $this, $item, $itemid);
		
		return true;
	}
	
	function delete($link) {
		if (!isset($link))
			return false;
		
		$itemid = $this->getItemID($link);
		if (!isset($itemid))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'rss::delete', $this, $link);
		
		array_splice($this->items, $itemid, 1);
		
		api::callHooks(API_HOOK_AFTER,
			'rss::delete', $this, $link);
		
		return true;
	}
	
	function load($file = null) {
		if (!$file)
			$file = $this->file;
		
		$this->clear();
		
		preg_match_all('/<item\b[^>]*>(.*?)<\/item>/is', 
			@file_get_contents($file), $items);
		
		if (!isset($items[1]))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'rss::load', $this, $file);
		
		foreach($items[1] as $item) {
			preg_match_all('/<(title|link|description|pubdate|author|comments)\b[^>]*>(.*?)<\/\1>/is', 
				$item, $matches);
			
			if (!isset($matches[1]))
				continue;
			
			$loaditem = array();
			foreach($matches[1] as $key => $value) {
				
				switch(strtolower($value)) {
					case 'title':
						$loaditem['Title'] = $matches[2][$key];
						break;
					case 'link':
						$loaditem['Link'] = $matches[2][$key];
						break;
					case 'description':
						$loaditem['Description'] = preg_replace(
							'/(<!\[CDATA\[|\]\]>)/is', '', 
							$matches[2][$key]);
						break;
					case 'pubdate':
						$loaditem['TimeStamp'] = date('Y-m-d H:i:s', 
							strtotime($matches[2][$key]));
						break;
					case 'author':
						$loaditem['Author'] = $matches[2][$key];
						break;
					case 'comments':
						$loaditem['Comments'] = $matches[2][$key];
						break;
				}
			}
			
			$this->add($loaditem);
		}
		
		api::callHooks(API_HOOK_AFTER,
			'rss::load', $this, $file);
	}
	
	function save($file = null) {
		if (!$file)
			$file = $this->file;
		
		if (!count($this->items))
			return files::delete($file);
		
		api::callHooks(API_HOOK_BEFORE,
			'rss::save', $this, $file);
		
		$rss =  
			"<?xml version=\"1.0\" encoding=\"".PAGE_CHARSET."\" ?>\n" .
			"<rss version=\"2.0\">\n" .
			"	<channel>\n" .
			"		<title>".$this->channel['Title']."</title>\n" .
			"		<link>".$this->channel['Link']."</link>\n" .
			"		<description>".$this->channel['Description']."</description>\n" .
			"		<copyright>Copyright ".date('Y').", ".PAGE_TITLE."</copyright>\n" .
			"		<lastBuildDate>".calendar::datetime()."</lastBuildDate>\n" .
			"		<managingEditor>".$this->channel['ManagingEditor']."</managingEditor>\n" .
			"		<webMaster>".$this->channel['WebMaster']."</webMaster>\n" .
			"		<generator>jCore CMS ver.".JCORE_VERSION."</generator>\n" .
			"		<ttl>".$this->channel['TTL']."</ttl>\n";
		
		if (isset($this->channel['Language']) && $this->channel['Language'])
			$rss .=
				"		<language>".$this->channel['Language']."</language>\n";
					
		if ($this->channel['Logo'])
			$rss .=
				"		<image>\n" .
				"			<link>".$this->channel['Link']."</link>\n" .
				"			<url>".$this->channel['Logo']."</url>\n" .
				"			<title>".$this->channel['Title']."</title>\n" .
				"		</image>\n";
		
		foreach($this->items as $item) {
			$rss .=
				"		<item>\n" .
				"			<title>".$item['Title']."</title>\n" .
				"			<link>".$item['Link']."</link>\n" .
				"			<description><![CDATA[".$item['Description']."]]></description>\n" .
				"			<pubDate>".calendar::datetime($item['TimeStamp'])."</pubDate>\n" .
				"			<guid>".$item['Link']."</guid>\n" .
				"			<author>".$item['Author']."</author>\n" .
				($item['Comments']?
					"			<comments>".$item['Link']."</comments>\n":
					null) .
				"		</item>\n";
		}
				    
		$rss .=
			"	</channel>\n" .
  			"</rss>\n";
  		
  		$result = files::save($file, $rss);
  		
		api::callHooks(API_HOOK_AFTER,
			'rss::save', $this, $file, $result);
		
  		return $result;
	}
	
	function clear() {
		$this->items = array();
	}
	
	function ajaxRequest() {
		api::callHooks(API_HOOK_BEFORE,
			'rss::ajaxRequest', $this);
		
		if (!$GLOBALS['USER']->loginok || 
			!$GLOBALS['USER']->data['Admin']) 
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'rss::ajaxRequest', $this);
			
			return true;
		}
		
		$feeds = null;
		
		if (isset($_GET['feeds']))
			$feeds = (int)$_GET['feeds'];
		
		if ($feeds) {
			$permission = userPermissions::check(
				(int)$GLOBALS['USER']->data['ID'],
				$this->adminPath);
			
			if (~$permission['PermissionType'] & USER_PERMISSION_TYPE_WRITE) {
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);
				
				api::callHooks(API_HOOK_AFTER,
					'rss::ajaxRequest', $this);
				
				return true;
			}
			
			$this->displayAdminAvailableFeeds();
			
			$result = true;
			api::callHooks(API_HOOK_AFTER,
				'rss::ajaxRequest', $this, $result);
			
			return true;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'rss::ajaxRequest', $this);
		
		return false;
	}
	
	static function displayFeeds() {
		$rows = sql::run(
			" SELECT * FROM `{rssfeeds}`" .
			" WHERE `Deactivated` = 0" .
			" ORDER BY `OrderID`, `ID`");
		
		if (!sql::rows($rows))
			return;
		
		api::callHooks(API_HOOK_BEFORE,
			'rss::displayFeeds', $this);
		
		while ($row = sql::fetch($rows)) {
			echo
				"<link rel='alternate' type='application/rss+xml' " .
					"title='".htmlspecialchars($row['Title'], ENT_QUOTES)."' " .
					"href='".$row['FeedURL']."' />\n";
		}
		
		api::callHooks(API_HOOK_AFTER,
			'rss::displayFeeds', $this);
	}
}

?>