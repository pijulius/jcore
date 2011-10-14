<?php

/***************************************************************************
 *            commentsatglance.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

class _commentsAtGlance extends comments {
	var $commentClasses = null;
	var $adminPath = 'admin/content/commentsatglance';
	
	function __construct() {
		api::callHooks(API_HOOK_BEFORE,
			'commentsAtGlance::comments', $this);
		
		$classes = get_declared_classes();
		foreach($classes as $class) {
			if (strpos($class, '_') === 0 ||
				$class == 'commentsAtGlance' || 
				$class == 'noteComments' || 
				!is_subclass_of($class, 'comments'))
				continue;
			
			$this->commentClasses[$class] = new $class();
		}
		
		api::callHooks(API_HOOK_AFTER,
			'commentsAtGlance::comments', $this);
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		api::callHooks(API_HOOK_BEFORE,
			'commentsAtGlance::countAdminItems', $this);
		
		$items = 0;
		foreach((array)$this->commentClasses as $class)
			$items += $class->countAdminItems();
		
		api::callHooks(API_HOOK_AFTER,
			'commentsAtGlance::countAdminItems', $this, $items);
		
		return $items;
	}
	
	function verifyAdmin(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'commentsAtGlance::verifyAdmin', $this, $form);
		
		$decline = null;
		$approve = null;
		$delete = null;
		$edit = null;
		$id = null;
		$ids = null;
		
		if (isset($_POST['declinesubmit']))
			$decline = (string)$_POST['declinesubmit'];
		
		if (isset($_POST['approvesubmit']))
			$approve = (string)$_POST['approvesubmit'];
		
		if (isset($_POST['deletesubmit']))
			$delete = (string)$_POST['deletesubmit'];
		
		if (isset($_GET['decline']))
			$decline = (int)$_GET['decline'];
		
		if (isset($_GET['approve']))
			$approve = (int)$_GET['approve'];
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = strip_tags((string)$_GET['id']);
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
		$result = false;
		
		if (!$id && !$ids && 
			($decline || $approve || $delete))
		{
			tooltip::display(
				__("No comment selected! Please select at " .
					"least one comment."),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'commentsAtGlance::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if ($decline || $approve || $delete || $edit) {
			if ($id) {
				list($id, $class) = explode('_', $id);
				
				if (isset($this->commentClasses[$class]))
					$result = $this->commentClasses[$class]->verifyAdmin($form);
				
			} else if ($ids) {
				if (!security::checkToken()) {
					api::callHooks(API_HOOK_AFTER,
						'commentsAtGlance::verifyAdmin', $this, $form);
					return false;
				}
				
				$result = true;
				$idsbyclass = null;
				
				foreach($ids as $id) {
					list($id, $class) = explode('_', $id);
					
					if (!isset($this->commentClasses[$class]))
						continue;
					
					$idsbyclass[$class][] = $id;
				}
				
				tooltip::caching(true);
				
				foreach((array)$idsbyclass as $class => $ids) {
					$_POST['ids'] = $ids;
					$this->commentClasses[$class]->verifyAdmin($form);
				}
				
				tooltip::$cache = null;
				tooltip::caching(false);
				
				if ($decline)
					tooltip::display(
						__("Comments have been successfully declined and " .
							"are now NOT visible to the public."),
						TOOLTIP_SUCCESS);
				
				else if ($approve)
					tooltip::display(
						__("Comments have been successfully approved and " .
							"are now visible to the public."),
						TOOLTIP_SUCCESS);
					
				else if ($delete)
					tooltip::display(
						__("Comments have been successfully deleted."),
						TOOLTIP_SUCCESS);
			}
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'commentsAtGlance::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if (!$edit) {
			list($id, $class) = explode('_', $form->get('SubCommentOfID'));
			
			if (isset($this->commentClasses[$class])) {
				$comment = sql::fetch(sql::run(
					" SELECT `".$this->commentClasses[$class]->sqlRow."`" .
					" FROM `{".$this->commentClasses[$class]->sqlTable."}`" .
					" WHERE `ID` = '".(int)$id."'"));
				
				if ($comment) {
					$this->commentClasses[$class]->selectedOwnerID = 
						$comment[$this->commentClasses[$class]->sqlRow];
					$result = $this->commentClasses[$class]->verifyAdmin($form);
				}
			}
		}
		
		api::callHooks(API_HOOK_AFTER,
			'commentsAtGlance::verifyAdmin', $this, $form, $result);
		
		return $result;
	}
	
	function displayAdminListItem(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'commentsAtGlance::displayAdminListItem', $this, $row);
		
		list($id, $class) = explode('_', $row['ID']);
		
		$selectedowner = sql::fetch(sql::run(
			" SELECT `".$this->commentClasses[$class]->sqlOwnerField."`" .
			" FROM `{" .$this->commentClasses[$class]->sqlOwnerTable . "}`" .
			" WHERE `ID` = '".$row['OwnerID']."'"));
		
		$row[$this->commentClasses[$class]->sqlRow] = $row['OwnerID'];
		$url = $this->commentClasses[$class]->getCommentURL($row);
		
		$row['Comment'] = 
			"<b>".$this->commentClasses[$class]->selectedOwner."</b>: " .
			"<a href='".$url."#comments' target='_blank'>" .
				$selectedowner[$this->commentClasses[$class]->sqlOwnerField] .
			"</a>" .
			"<br /><br />" .
			$row['Comment'];
		
		parent::displayAdminListItem($row);
		
		api::callHooks(API_HOOK_AFTER,
			'commentsAtGlance::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'commentsAtGlance::displayAdminListFunctions', $this);
		
		if (defined('MODERATED_COMMENTS') && MODERATED_COMMENTS &&
			defined('MODERATED_COMMENTS_BY_APPROVAL') && 
			MODERATED_COMMENTS_BY_APPROVAL)
			echo 
				"<input type='submit' name='approvesubmit' value='" .
					htmlspecialchars(__("Approve"), ENT_QUOTES)."' class='button' /> " .
				"<input type='submit' name='declinesubmit' value='" .
					htmlspecialchars(__("Decline"), ENT_QUOTES)."' class='button' /> ";
		
		echo
			"<input type='submit' name='deletesubmit' value='" .
				htmlspecialchars(__("Delete"), ENT_QUOTES) .
				"' class='button confirm-link' /> ";
		
		api::callHooks(API_HOOK_AFTER,
			'commentsAtGlance::displayAdminListFunctions', $this);
	}
	
	function displayAdminTitle($ownertitle = null) {
		api::callHooks(API_HOOK_BEFORE,
			'commentsAtGlance::displayAdminTitle', $this, $ownertitle);
		
		admin::displayTitle(
			__('Comments at Glance'), 
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'commentsAtGlance::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdmin() {			
		api::callHooks(API_HOOK_BEFORE,
			'commentsAtGlance::displayAdmin', $this);
		
		$search = null;
		$searchtype = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags((string)$_GET['search']));
		
		if (isset($_GET['searchtype']))
			$searchtype = (int)$_GET['searchtype'];
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = strip_tags((string)$_GET['id']);
		
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
					__("Edit Comment"):
					__("New Comment")),
				'neweditcomment');
		
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
				str_replace('&amp;', '&', url::uri('id, edit, delete, approve, decline'))."'\"");
			
		} else {
			$form->edit(
				'SubCommentOfID',
				null, null, null, true);
			$form->setValueType(
				'SubCommentOfID',
				FORM_VALUE_TYPE_STRING);
		}
		
		$verifyok = false;
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$verifyok = $this->verifyAdmin($form);
		
		$items = 0;
		$paging = new paging(10);
		$paging->ignoreArgs = 'id, edit, delete, approve, decline';
		
		sql::run(
			" CREATE TEMPORARY TABLE `{TMPcomments}` (" .
			" `ID` varchar(100) NOT NULL default ''," .
			" `OwnerID` int(10) unsigned NOT NULL default '0'," .
			" `UserName` varchar(100) NOT NULL default ''," .
			" `Email` varchar(100) NOT NULL default ''," .
			" `UserID` mediumint(8) unsigned NOT NULL default '0'," .
			" `Comment` text NULL," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP," .
			" `IP` DECIMAL(39, 0) NOT NULL default '0'," .
			" `SubCommentOfID` varchar(100) NOT NULL default ''," .
			" `Rating` smallint(6) NOT NULL default '0'," .
			" `Pending` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0'," .
			" KEY `ID` (`ID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `OwnerID` (`OwnerID`)," .
			" KEY `SubCommentOfID` (`SubCommentOfID`)," .
			" KEY `UserName` (`UserName`)," .
			" KEY `UserID` (`UserID`)," .
			" KEY `Pending` (`Pending`)" .
			" );");
		
		foreach((array)$this->commentClasses as $classname => $class) {
			if (!isset($class->adminPath))
				continue;
			
			$query =
				" SELECT" .
				" CONCAT(`ID`, '_$classname') AS `ID`," .
				" `".$class->sqlRow."` AS `OwnerID`," .
				" `UserName`, `Email`, `UserID`, `Comment`, `TimeStamp`, `IP`," .
				" CONCAT(`SubCommentOfID`, ' $classname') AS `SubCommentOfID`," .
				" `Rating`, `Pending`" .
				" FROM `{".$class->sqlTable."}`" .
				" WHERE 1" .
				($searchtype?
					" AND `Pending` = '".($searchtype == 1?1:0)."'":
					null) .
				($search?
					sql::search(
						$search,
						array('Comment')):
					null) .
				" ORDER BY `ID` DESC" .
				" LIMIT ".$paging->getEnd();
			
			sql::$lastQuery = $query;
			$items += sql::count();
			
			sql::run(
				" INSERT INTO `{TMPcomments}`" .
				$query);
		}
		
		$rows = sql::run(
			" SELECT * FROM `{TMPcomments}`" .
			" ORDER BY `TimeStamp` DESC" .
			" LIMIT ".$paging->limit);
		
		$paging->setTotalItems($items);
		
		if ($paging->items)
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No comments found."),
				TOOLTIP_NOTIFICATION);
		
		$paging->display();
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			$selected = null;
			
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{TMPcomments}`" .
					" WHERE `ID` = '".$id."'"));
				
				if ((int)$selected['SubCommentOfID'])
					$form->addValue('SubCommentOfID',
						$selected['SubCommentOfID'],
						"#".ucwords(str_replace('_', ' ', str_ireplace('Comments', '', $selected['SubCommentOfID']))));
				
				if (JCORE_VERSION >= '0.7' && !$selected['UserID'])
					$form->edit('Email', null, null, FORM_INPUT_TYPE_EMAIL);
					
				$form->setValues($selected);
			}
			
			if (sql::rows($rows)) {
				sql::seek($rows, 0);
				
				if ($selected)
					$selected = substr($selected['ID'], strpos($selected['ID'], '_'));
				
				while($row = sql::fetch($rows)) {
					if ($selected && strpos($row['ID'], $selected) === false)
						continue;
					
					$form->addValue('SubCommentOfID',
						$row['ID'],
						"#".ucwords(str_replace('_', ' ', str_ireplace('Comments', '', $row['ID']))));
				}
			}
			
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form);
		
		sql::run("DROP TABLE `{TMPcomments}`");
		
		echo "</div>";	//admin-content
		
		api::callHooks(API_HOOK_AFTER,
			'commentsAtGlance::displayAdmin', $this);
	}
}

?>