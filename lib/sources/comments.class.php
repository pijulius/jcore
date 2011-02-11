<?php

/***************************************************************************
 *            comments.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
include_once('lib/security.class.php');
include_once('lib/email.class.php');
include_once('lib/calendar.class.php');

email::add('CommentNew',
		"New Comment by %COMMENTBY% at %PAGE_TITLE%",
		"Dear Webmaster,\n\n" .
		"A new comment has been posted by \"%COMMENTBY%\" " .
		"to the following %COMMENTSECTION% \"%COMMENTSECTIONTITLE%\".\n\n" .
		"%COMMENTURL%\n\n" .
		"Comment posted:\n" .
		"--------------------------------\n\n" .
		"%COMMENTBODY%\n\n" .
		"Sincerely,\n" .
		"%PAGE_TITLE%");
		
email::add('CommentEdit',
		"Comment Edited by %COMMENTBY% at %PAGE_TITLE%",
		"Dear Webmaster,\n\n" .
		"A comment has been edited by \"%COMMENTBY%\" " .
		"at the following %COMMENTSECTION% \"%COMMENTSECTIONTITLE%\".\n\n" .
		"%COMMENTURL%\n\n" .
		"Updated comment:\n" .
		"--------------------------------\n\n" .
		"%COMMENTBODY%\n\n" .
		"Sincerely,\n" .
		"%PAGE_TITLE%");
		
email::add('CommentReply',
		"New Comment Reply by %COMMENTBY% at %PAGE_TITLE%",
		"Dear %USERNAME%,\n\n" .
		"A new comment has been posted by \"%COMMENTBY%\" " .
		"to the following %COMMENTSECTION% \"%COMMENTSECTIONTITLE%\".\n\n" .
		"%COMMENTURL%\n\n" .
		"Comment posted:\n" .
		"--------------------------------\n\n" .
		"%COMMENTBODY%\n\n" .
		"* You are receiving this email or because you are the owner of this " .
		"%COMMENTSECTION% or because the comment has been posted " .
		"as a direct reply to yours. To opt out of these and other " .
		"notification emails please visit your account page and check the " .
		"\"Disable Notification Emails\" option.\n\n" .
		"Sincerely,\n" .
		"%PAGE_TITLE%");
		
class _comments {
	static $uriVariables = 'delete, edit, reply, rateup, ratedown';
	var $selectedOwnerID;
	var $user;
	var $sqlTable;
	var $sqlRow;
	var $sqlOwnerTable;
	var $sqlOwnerField = 'Title';
	var $sqlOwnerCountField = 'Comments';
	var $selectedOwner;
	var $guestComments = false;
	var $defaultRating = 8;
	var $uriRequest;
	var $commentURL;
	var $ajaxRequest = null;
	
	function __construct() {
		$this->commentURL = "http://".$_SERVER['HTTP_HOST'].
			str_replace('&amp;', '&', url::uri());
		
		$this->uriRequest = strtolower(get_class($this));
		
		if ($this->sqlRow && isset($_GET[strtolower($this->sqlRow)]))
			$this->selectedOwnerID = (int)$_GET[strtolower($this->sqlRow)];
		
		if (JCORE_VERSION >= '0.6')
			$this->defaultRating = 7;
	}
	
	function SQL($commentid = null) {
		return
			" SELECT * FROM `{" . $this->sqlTable . "}`" .
			" WHERE 1" .
			($this->sqlRow?
				" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
				null) .
			($commentid?
				" AND `SubCommentOfID` = '".(int)$commentid."'":
				" AND !`SubCommentOfID`") .
			(defined('MODERATED_COMMENTS') && MODERATED_COMMENTS?
				 (defined('MODERATED_COMMENTS_PENDING_MINUTES') && 
				  MODERATED_COMMENTS_PENDING_MINUTES?
					" AND (`TimeStamp` <= DATE_SUB(NOW(), INTERVAL " .
						(int)MODERATED_COMMENTS_PENDING_MINUTES." MINUTE)" .
					" OR `IP` = '".ip2long($_SERVER['REMOTE_ADDR'])."')":
					null) .
				 (defined('MODERATED_COMMENTS_BY_APPROVAL') && 
				  MODERATED_COMMENTS_BY_APPROVAL?
					" AND (!`Pending`" .
					" OR `IP` = '".ip2long($_SERVER['REMOTE_ADDR'])."')":
					null):
				null) .
			" ORDER BY `ID`";
	}
	
	// ************************************************   Admin Part
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Comment'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Settings'), 
			'?path=admin/site/settings');
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			__('Nickname'),
			'UserName',
			FORM_INPUT_TYPE_TEXT,
			true,
			$GLOBALS['USER']->data['UserName']);
		$form->setStyle('width: 150px;');
		
		if (JCORE_VERSION >= '0.7') {
			$form->add(
				__('Email'),
				'Email',
				FORM_INPUT_TYPE_HIDDEN);
			$form->setStyle('width: 250px;');
		}
		
		$form->add(
			__('Comment'),
			'Comment',
			FORM_INPUT_TYPE_TEXTAREA,
			true);
		$form->setStyle('width: ' .
			(JCORE_VERSION >= '0.7'?
				'90%':
				'350px') .
			'; height: 150px;');
		
		$form->add(
			__('In Reply To'),
			'SubCommentOfID',
			FORM_INPUT_TYPE_SELECT);
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		$form->addValue('','');
	}
	
	function verifyAdmin(&$form) {
		$search = null;
		$decline = null;
		$approve = null;
		$deleteall = null;
		$delete = null;
		$edit = null;
		$id = null;
		$ids = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_POST['declinesubmit']))
			$decline = $_POST['declinesubmit'];
		
		if (isset($_POST['approvesubmit']))
			$approve = $_POST['approvesubmit'];
		
		if (isset($_POST['deleteallsubmit']))
			$deleteall = $_POST['deleteallsubmit'];
		
		if (isset($_POST['deletesubmit']))
			$delete = $_POST['deletesubmit'];
		
		if (isset($_GET['decline']))
			$decline = $_GET['decline'];
		
		if (isset($_GET['approve']))
			$approve = $_GET['approve'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
		if ($deleteall) {
			sql::run(
				" DELETE FROM `{".$this->sqlTable . "}`" .
				" WHERE 1" .
				($this->sqlRow?
					" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
					null) .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				($search?
					sql::search(
						$search,
						array('Comment')):
					null));
			
			if ($this->sqlOwnerTable) {
				$row = sql::fetch(sql::run(
					" SELECT COUNT(`ID`) AS `Rows` FROM `{".$this->sqlTable . "}`" .
					" WHERE `".$this->sqlRow."` = '".$this->selectedOwnerID."'"));
				
				sql::run(
					" UPDATE `{".$this->sqlOwnerTable . "}`" .
					" SET `".$this->sqlOwnerCountField."` = '".(int)$row['Rows']."'," .
					" `TimeStamp` = `TimeStamp` " .
					" WHERE `ID` = '".$this->selectedOwnerID."'");
			}
					
			tooltip::display(
				__("Comments have been successfully deleted."),
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if (!$id && !$ids && 
			($decline || $approve || $delete))
		{
			tooltip::display(
				__("No comment selected! Please select at " .
					"least one comment."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if ($ids && count($ids)) {
			if ($decline) {
				foreach($ids as $id)
					$this->decline($id);
				
				tooltip::display(
					__("Comments have been successfully declined and " .
						"are now NOT visible to the public."),
					TOOLTIP_SUCCESS);
					
				return true;
			}
			
			if ($approve) {
				foreach($ids as $id)
					$this->approve($id);
				
				tooltip::display(
					__("Comments have been successfully approved and " .
						"are now visible to the public."),
					TOOLTIP_SUCCESS);
					
				return true;
			}
			
			if ($delete) {
				foreach($ids as $id)
					$this->delete($id);
				
				tooltip::display(
					__("Comments have been successfully deleted."),
					TOOLTIP_SUCCESS);
					
				return true;
			}
		}
			
		if ($decline) {
			if (!$this->decline($id))
				return false;
			
			tooltip::display(
				__("Comment has been successfully declined and " .
					"is now NOT visible to the public."),
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if ($approve) {
			if (!$this->approve($id))
				return false;
			
			tooltip::display(
				__("Comment has been successfully approved and " .
					"is now visible to the public."),
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
			
			tooltip::display(
				__("Comment has been successfully deleted."),
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray()))
				return false;
			
			tooltip::display(
				__("Comment has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if (!$newid = $this->add($form->getPostArray()))
			return false;
		
		tooltip::display(
			__("Comment has been successfully created.")." " .
			"<a href='".url::uri('id, edit, delete') .
				"&amp;id=".$newid."&amp;edit=1#adminform'>" .
				__("Edit") .
			"</a>",
			TOOLTIP_SUCCESS);
			
		$form->reset();
		return true;
	}
	
	function displayAdminListHeader() {
		echo
			"<th>" .
				"<input type='checkbox' class='checkbox-all' " .
				($this->userPermissionType != USER_PERMISSION_TYPE_WRITE?
					"disabled='disabled' ":
					null) .
				"/>" .
			"#</th>" .
			"<th><span class='nowrap'>".
				__("Posted By")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Comment")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
	}
	
	function displayAdminListHeaderFunctions() {
		if (defined('MODERATED_COMMENTS') && MODERATED_COMMENTS &&
			defined('MODERATED_COMMENTS_BY_APPROVAL') && 
			MODERATED_COMMENTS_BY_APPROVAL)
			echo
				"<th><span class='nowrap'>".
					__("Approved")."</span></th>";
				
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
		$ids = null;
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
		echo
			"<td>" .
				"<span class='nowrap'>" .
				"<input type='checkbox' name='ids[]' " .
					"value='".$row['ID']."' " .
					($ids && in_array($row['ID'], $ids)?
						"checked='checked' ":
						null).
					($this->userPermissionType != USER_PERMISSION_TYPE_WRITE?
						"disabled='disabled' ":
						null) .
					" />" .
				"#".$row['ID']."<br />" .
				($row['SubCommentOfID']?
					"<span class='comment'>&nbsp; &#8594; #" .
						$row['SubCommentOfID'] .
					"</span>":
					null) .
				"</span>" .
			"</td>" .
			"<td align='center'>" .
				"<div class='admin-content-preview'>";
		
		if (JCORE_VERSION >= '0.7')
			$this->displayAvatar($row);
		
		echo
					"<div class='nowrap bold'>" .
						$row['UserName'] .
					"</div>" .
					"<div class='nowrap'>";
			
		if ($row['UserID']) {
			$user = $GLOBALS['USER']->get($row['UserID']);
			
			echo
					" (";
				
			$GLOBALS['USER']->displayUserName($user);
			
			echo
					")";
			
		} elseif (isset($row['Email']) && $row['Email']) {
			echo
					" (<a href='mailto:".$row['Email']."'>" .
						preg_replace('/@.*$/', '', $row['Email'])."</a>)";
		}
			
		echo
					"</div>" .
				"</div>" .
			"</td>" .
			"<td class='auto-width'>" .
				"<div class='admin-content-preview' style='padding-left: 0;'>" .
				nl2br($row['Comment']) .
				"<div class='spacer'></div>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					calendar::datetime($row['TimeStamp']);
		
		$this->displayIsPending($row);
		
		echo
				" (".long2ip($row['IP']).")" .
				"</div>" .
				"</div>" .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
	}
	
	function displayAdminListItemFunctions(&$row) {
		if (defined('MODERATED_COMMENTS') && MODERATED_COMMENTS &&
			defined('MODERATED_COMMENTS_BY_APPROVAL') && 
			MODERATED_COMMENTS_BY_APPROVAL)
		{
			if ($row['Pending'])
				echo
					"<td align='center'>" .
						"<a class='admin-link important' " .
							"title='".htmlspecialchars(__("Approve"), ENT_QUOTES)."' " .
							"href='".url::uri('id, edit, delete, approve, decline') .
							"&amp;id=".$row['ID']."&amp;approve=1'>" .
						"</a>" .
					"</td>";
			else
				echo
					"<td align='center'>" .
						"<a class='admin-link apply' " .
							"title='".htmlspecialchars(__("Decline"), ENT_QUOTES)."' " .
							"href='".url::uri('id, edit, delete, approve, decline') .
							"&amp;id=".$row['ID']."&amp;decline=1'>" .
						"</a>" .
					"</td>";
		}
				
		echo
			"<td>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete, approve, decline') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete, approve, decline') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayAdminListSearch() {
		$search = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		echo
			"<input type='hidden' name='path' value='".admin::path()."' />" .
			"<input type='search' name='search' value='".
				htmlspecialchars($search, ENT_QUOTES).
				"' results='5' placeholder='" .
					htmlspecialchars(__("search..."), ENT_QUOTES)."' /> " .
			"<input type='submit' value='" .
				htmlspecialchars(__("Search"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminListFunctions() {
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
				"' class='button confirm-link' /> " .
			"<input type='submit' name='deleteallsubmit' value='" .
				htmlspecialchars(__("Delete All"), ENT_QUOTES) .
				"' class='button confirm-link' /> ";
	}
	
	function displayAdminList(&$rows) {
		echo
			"<form action='".
				url::uri('edit, delete, approve, decline')."' method='post'>";
		
		echo "<table cellpadding='0' cellspacing='0' class='list'>" .
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
	
	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__(trim(ucfirst(preg_replace('/([A-Z])/', ' \1', 
				$this->sqlOwnerCountField)))), 
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		if (!$this->sqlTable) {
			tooltip::display(
				__("Storage table not defined."),
				TOOLTIP_NOTIFICATION);
			
			return;
		}
		
		$search = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		echo
			"<div style='float: right;'>" .
				"<form action='".url::uri('ALL')."' method='get'>";
		
		$this->displayAdminListSearch();
		
		echo
				"</form>" .
			"</div>";
		
		if ($this->sqlOwnerTable) {
			$this->selectedOwnerID = admin::getPathID();
			
			$selectedowner = sql::fetch(sql::run(
				" SELECT `".$this->sqlOwnerField."` FROM `{" .$this->sqlOwnerTable . "}`" .
				" WHERE `ID` = '".admin::getPathID()."'"));
			
			$this->displayAdminTitle($selectedowner[$this->sqlOwnerField]);
			
		} else {
			$this->displayAdminTitle($this->selectedOwner);
		}
		
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
		}
		
		$verifyok = false;
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			$verifyok = $this->verifyAdmin($form);
		}
	
		$paging = new paging(10);
		$paging->ignoreArgs = 'id, edit, delete, approve, decline';
		
		$rows = sql::run(
				" SELECT * FROM `{".$this->sqlTable . "}`" .
				" WHERE 1" .
				($this->sqlRow?
					" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
					null) .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				($search?
					sql::search(
						$search,
						array('Comment')):
					null) .
				" ORDER BY `ID` DESC" .
				" LIMIT ".$paging->limit);
				
		$paging->setTotalItems(sql::count());
		
		if ($paging->items)
			$this->displayAdminList($rows);
		else
			tooltip::display(
					sprintf(__("No %s found for this %s."),
						strtolower(__(trim(preg_replace('/([A-Z])/', ' \1',
							$this->sqlOwnerCountField)))), 
						$this->selectedOwner),
					TOOLTIP_NOTIFICATION);
		
		$paging->display();
		
		if (sql::rows($rows))
			sql::seek($rows, 0);
		
		while($row = sql::fetch($rows))
			$form->addValue('SubCommentOfID',
				$row['ID'],
				"#".$row['ID']);
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{".$this->sqlTable . "}`" .
					" WHERE `ID` = '".$id."'" .
					($this->sqlRow?
						" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
						null)));
				
				if ($row['SubCommentOfID'])
					$form->addValue('SubCommentOfID',
						$row['SubCommentOfID'],
						"#".$row['SubCommentOfID']);
				
				if (JCORE_VERSION >= '0.7' && !$row['UserID'])
					$form->edit('Email', null, null, FORM_INPUT_TYPE_EMAIL);
					
				$form->setValues($row);
			}
			
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form);
		
		echo "</div>";	//admin-content
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		$values['Comment'] = url::parseLinks(
			security::closeTags($values['Comment']));
		
		$duplicate = sql::fetch(sql::run(
			" SELECT `ID` FROM `{". $this->sqlTable . "}`" .
			" WHERE 1" .
			($this->sqlRow?
				" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
				null) .
			" AND `Comment` = '".
				sql::escape($values['Comment'])."'"));
				
		if ($duplicate) {
			tooltip::display(
				__("Comment already posted!"),
				TOOLTIP_ERROR);
					
			return false;
		}
		
		$newid = sql::run(
			" INSERT INTO `{".$this->sqlTable."}` SET" .
			($this->sqlRow?
				" `".$this->sqlRow."` = '".$this->selectedOwnerID."',":
				null) .
			" `UserName` = '".
				sql::escape($values['UserName'])."'," .
			(isset($values['Email'])?
				" `Email` = '".
					sql::escape($values['Email'])."',":
				null) .
			" `UserID` = '" .
				(int)$GLOBALS['USER']->data['ID']."'," .
			" `Comment` = '" .
				sql::escape($values['Comment'])."'," .
			(isset($values['SubCommentOfID'])?
				" `SubCommentOfID` = '".
					(int)$values['SubCommentOfID']."',":
				null) .
			(defined('MODERATED_COMMENTS') && MODERATED_COMMENTS &&
			 defined('MODERATED_COMMENTS_BY_APPROVAL') && 
			 MODERATED_COMMENTS_BY_APPROVAL && 
			 (!$GLOBALS['USER']->loginok || !$GLOBALS['USER']->data['Admin'])?
				" `Pending` = 1,":
				null) .
			" `IP` = '".ip2long($_SERVER['REMOTE_ADDR'])."'," .
			" `TimeStamp` = NOW()");
			
		if (!$newid) {
			tooltip::display(
				sprintf(__("Comment couldn't be added! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		$email = new email();
		$email->load('CommentNew');
		
		$email->to = WEBMASTER_EMAIL;
		$email->variables = array(
				"CommentSectionTitle" => '', 
				"CommentSection" => $this->selectedOwner,
				"CommentBy" => $values['UserName'],
				"CommentBody" => $values['Comment'],
				"CommentURL" => $this->commentURL."#comment".(int)$newid);
		
		if ($this->sqlOwnerTable) {
			$selectedowner = sql::fetch(sql::run(
				" SELECT * FROM `{" .$this->sqlOwnerTable . "}`" .
				" WHERE `ID` = '".$this->selectedOwnerID."'"));
			
			$email->variables["CommentSectionTitle"] = 
					$selectedowner[$this->sqlOwnerField];
		}
			
		if (!$GLOBALS['USER']->data['Admin'])
			$email->send();
		
		if ($this->sqlOwnerTable) {
			sql::run(
				" UPDATE `{".$this->sqlOwnerTable."}` SET " .
				" `".$this->sqlOwnerCountField."` = `".$this->sqlOwnerCountField."` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".$this->selectedOwnerID."'");
				
			if ($selectedowner['UserID'] &&
				$selectedowner['UserID'] != $GLOBALS['USER']->data['ID']) 
			{
				$email->reset();
				$user = $GLOBALS['USER']->get($selectedowner['UserID']);
				
				if ($user && $user['Email'] != WEBMASTER_EMAIL) {
					$email->load('CommentReply');
					$email->toUser = $user;
					$email->send();
				}
			}
				
			if (isset($values['SubCommentOfID']) && (int)$values['SubCommentOfID']) {
				$email->reset();
				$replytocomment = sql::fetch(sql::run(
					" SELECT * FROM `{".$this->sqlTable."}` " .
					" WHERE `ID` = '".(int)$values['SubCommentOfID']."'"));
				
				if ($replytocomment['UserID'] &&
					$replytocomment['UserID'] != $selectedowner['UserID'] && 
					$replytocomment['UserID'] != $GLOBALS['USER']->data['ID']) 
				{
					$email->load('CommentReply');
					$email->toUserID = $replytocomment['UserID'];
					$email->send();
					
				} elseif (isset($replytocomment['Email']) && $replytocomment['Email']) {
					$email->load('CommentReply');
					$email->variables['UserName'] = $replytocomment['UserName'];
					$email->to = $replytocomment['Email'];
					$email->send();
				}
			}
		}
			
		unset($email);
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		sql::run(
			" UPDATE `{".$this->sqlTable."}` SET ".
			" `TimeStamp` = `TimeStamp`, " .
			(isset($values['SubCommentOfID'])?
				" `SubCommentOfID` = '".
					(int)$values['SubCommentOfID']."',":
				null) .
			" `UserName` = '".
				sql::escape($values['UserName'])."'," .
			(isset($values['Email'])?
				" `Email` = '".
					sql::escape($values['Email'])."',":
				null) .
			" `Comment` = '".
				sql::escape(
					url::parseLinks(
						security::closeTags($values['Comment'])))."'" .
			" WHERE `ID` = '".(int)$id."'");
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Comment couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (!$GLOBALS['USER']->data['Admin']) {
			$email = new email();
			$email->load('CommentEdit');
			
			$email->to = WEBMASTER_EMAIL;
			$email->variables = array(
				"CommentSectionTitle" => '',
				"CommentSection" => $this->selectedOwner,
				"CommentBy" => $values['UserName'],
				"CommentBody" => $values['Comment'],
				"CommentURL" => $this->commentURL."#comment".(int)$id);
			
			if ($this->sqlOwnerTable) {
				$selectedowner = sql::fetch(sql::run(
					" SELECT `".$this->sqlOwnerField."` FROM `{" .$this->sqlOwnerTable . "}`" .
					" WHERE `ID` = '".$this->selectedOwnerID."'"));
				
				$email->variables["CommentSectionTitle"] = 
						$selectedowner[$this->sqlOwnerField];
			}
			
			$email->send();
			unset($email);
		}
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		foreach($this->getSubComments($id) as $subcomment)
			sql::run(
				" DELETE FROM `{".$this->sqlTable."}` " .
				" WHERE `ID` = '".$subcomment['ID']."'");
		
		sql::run(
			" DELETE FROM `{".$this->sqlTable."}`" .
			" WHERE `ID` = '".(int)$id."'");
		
		if ($this->sqlOwnerTable) {
			$row = sql::fetch(sql::run(
				" SELECT COUNT(`ID`) AS `Rows` FROM `{".$this->sqlTable . "}`" .
				" WHERE `".$this->sqlRow."` = '".$this->selectedOwnerID."'"));
			
			sql::run(
				" UPDATE `{".$this->sqlOwnerTable . "}`" .
				" SET `".$this->sqlOwnerCountField."` = '".(int)$row['Rows']."'," .
				" `TimeStamp` = `TimeStamp` " .
				" WHERE `ID` = '".$this->selectedOwnerID."'");
		}
					
		return true;
	}
	
	function getSubComments($commentid = 0, $firstcall = true,
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0)) 
	{
		$rows = sql::run(
			" SELECT * FROM `{".$this->sqlTable."}` " .
			($commentid?
				" WHERE `SubCommentOfID` = '".$commentid."'":
				" WHERE !`SubCommentOfID`") .
			" ORDER BY `ID`");
		
		while($row = sql::fetch($rows)) {
			$row['PathDeepnes'] = $tree['PathDeepnes'];
			$tree['Tree'][] = $row;
			
			$tree['PathDeepnes']++;
			$this->getSubComments($row['ID'], false, $tree);
			$tree['PathDeepnes']--;
		}
		
		if ($firstcall)
			return $tree['Tree'];
	}
	
	function decline($id) {
		if (!$id)
			return false;
		
		sql::run(
			" UPDATE `{".$this->sqlTable."}` SET ".
			" `TimeStamp` = `TimeStamp`," .
			" `Pending` = 1" .
			" WHERE `ID` = '".(int)$id."'");
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Comment couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	function approve($id) {
		if (!$id)
			return false;
		
		sql::run(
			" UPDATE `{".$this->sqlTable."}` SET ".
			" `TimeStamp` = `TimeStamp`," .
			" `Pending` = 0" .
			" WHERE `ID` = '".(int)$id."'");
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Comment couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	// ************************************************   Client Part
	function rate($id, $rating = 0) {
		if (!$this->guestComments && !$GLOBALS['USER']->loginok) {
			tooltip::display(
					__("Only registered users can rate."),
					TOOLTIP_ERROR);
			
			return false;
		}
		
		$comment = sql::fetch(sql::run(
			" SELECT `ID`, `UserID` FROM `{".$this->sqlTable . "}`" .
			" WHERE `ID` = '".$id."'"));
		
		if (!$comment) {
			tooltip::display(
				__("Comment does not exist!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if ($GLOBALS['USER']->loginok && 
			$comment['UserID'] == $GLOBALS['USER']->data['ID'])
		{
			tooltip::display(
				__("You cannot rate your own comments!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT `TimeStamp` FROM `{".$this->sqlTable."ratings}`" .
			" WHERE `CommentID` = '".$id."'" .
			($this->guestComments?
				" AND `IP` = '".ip2long($_SERVER['REMOTE_ADDR'])."'" .
				" AND `TimeStamp` > DATE_SUB(NOW(), INTERVAL 1 DAY)":
				" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'")));
			
		if ($row) {
			tooltip::display(
				__("You already rated this comment."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		sql::run(
			" INSERT INTO `{".$this->sqlTable."ratings}`" .
			" SET `CommentID` = '".$id."'," .
			" `IP` = '".ip2long($_SERVER['REMOTE_ADDR'])."'," .
			" `TimeStamp` = NOW()," .
			($GLOBALS['USER']->loginok?
				" `UserID` = '".(int)$GLOBALS['USER']->data['ID']."',":
				null) .
			" `Rating` = ".(int)$rating);
			
		$negativeratings = sql::fetch(sql::run(
			" SELECT COUNT(`CommentID`) AS `Rows` " .
			" FROM `{".$this->sqlTable."ratings}`" .
			" WHERE `CommentID` = '".$id."'" .
			" AND `Rating` < 0"));
		
		$positiveratings = sql::fetch(sql::run(
			" SELECT COUNT(`CommentID`) AS `Rows` " .
			" FROM `{".$this->sqlTable."ratings}`" .
			" WHERE `CommentID` = '".$id."'" .
			" AND `Rating` > 0"));
		
		$negativeratings = (int)$negativeratings['Rows'];
		$positiveratings = (int)$positiveratings['Rows'];
		
		$rating = $this->defaultRating+
			($positiveratings-$negativeratings);
		
		if ($rating < 1)
			$rating = 1;
			
		if ($rating > 10)
			$rating = 10;
			
		sql::run(
			" UPDATE `{".$this->sqlTable . "}`" .
			" SET `TimeStamp` = `TimeStamp`," .
			" `Rating` = ".(int)$rating .
			" WHERE `ID` = '".$id."'");
		
		tooltip::display(
			__("Thank you for rating this comment."),
			TOOLTIP_SUCCESS);
		
		return true;
	}
	
	function verify(&$form) {
		$commentid = null;
		$delete = null;
		
		if (isset($_GET[strtolower(get_class($this))]))
			$commentid = (int)$_GET[strtolower(get_class($this))];
			
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
			
		if ($delete) {
			if (!$GLOBALS['USER']->loginok ||
				!$GLOBALS['USER']->data['Admin'])
			{
				tooltip::display(
					__("Only administrators can delete comments."),
					TOOLTIP_ERROR);
				
				return false;
			}
			
			if (!$this->delete($commentid))
				return false;
			
			tooltip::display(
				__("Comment has been successfully deleted."),
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if (!$form)
			return false;
		
		if (!$this->guestComments && !$GLOBALS['USER']->loginok)
			return false;
		
		if (!$form->verify())
			return false;
		
		$commentid = $form->get('CommentID');
		if ($commentid) {
			$comment = sql::fetch(sql::run(
				" SELECT * FROM `{" . $this->sqlTable . "}`" .
				" WHERE `ID` = '".(int)$commentid."'"));
			
			if (!$comment) {
				tooltip::display(
					__("Selected comment cannot be found!"),
					TOOLTIP_ERROR);
				return false;
			}
			
			if (!$GLOBALS['USER']->loginok || 
				(!$GLOBALS['USER']->data['Admin'] &&
				 $GLOBALS['USER']->data['ID'] != $comment['UserID'])) 
			{
				tooltip::display(
					__("You can only edit your own comments."),
					TOOLTIP_ERROR);
				return false;
			}
			
			if (!$this->edit($commentid, $form->getPostArray()))
				return false;
			
			tooltip::display(
				__("Your comment has been successfully updated.").
				"<script>window.location='#comment".$commentid."';</script>",
				TOOLTIP_SUCCESS);
		
			return true;
		}
		
		$newid = $this->add($form->getPostArray());
		
		if (!$newid)
			return false;
		
		$form->reset();
		tooltip::display(
			__("Thank you for your comment.").
				"<script>window.location='#comment".$newid."';</script>",
			TOOLTIP_SUCCESS);
		
		return $newid;
	}
	
	function ajaxRequest() {
		$commentid = null;
		
		if (isset($_GET[strtolower(get_class($this))]))
			$commentid = (int)$_GET[strtolower(get_class($this))];
		
		if (!$commentid)
			return false;
			
		$rateup = null;
		$ratedown = null;
		$reply = null;
		$edit = null;
			
		if (isset($_GET['rateup']))
			$rateup = $_GET['rateup'];
	
		if (isset($_GET['ratedown']))
			$ratedown = $_GET['ratedown'];
	
		if (isset($_GET['reply']))
			$reply = $_GET['reply'];
			
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
			
		if (!$rateup && !$ratedown && !$reply && !$edit)
			return false;
		
		if ($this->sqlOwnerTable && !$this->guestComments) {
			if (sql::rows(sql::run(
				" SHOW COLUMNS FROM `{" .$this->sqlOwnerTable . "}`" .
				" LIKE 'EnableGuestComments'")))
			{
				$selectedowner = sql::fetch(sql::run(
					" SELECT `EnableGuestComments` FROM `{" .$this->sqlOwnerTable . "}`" .
					" WHERE `ID` = '".$this->selectedOwnerID."'"));
				
				if ($selectedowner['EnableGuestComments'])
					$this->guestComments = true;
			}
		}
		
		if ($rateup) {
			$this->rate($commentid, 1);
			return true;
		}
	
		if ($ratedown) {
			$this->rate($commentid, -1);
			return true;
		}
	
		if ($reply) {
			$this->displayReplyForm($commentid);
			return true;
		}
			
		if ($edit) {
			$this->displayEditForm($commentid);
			return true;
		}
			
		return false;
	}
	
	function countItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{" . $this->sqlTable . "}`" .
			($this->sqlRow?
				" WHERE `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
				null) .
			" LIMIT 1"));
		return (int)$row['Rows'];
	}
	
	function setupForm(&$form) {
		$form->action .= '#comments';
		
		$form->add(
			__('Nickname'),
			'UserName',
			FORM_INPUT_TYPE_TEXT,
			true,
			$GLOBALS['USER']->data['UserName']);
		$form->setStyle('width: 150px;');
		
		if (JCORE_VERSION >= '0.7' && !$GLOBALS['USER']->loginok) {
			$form->add(
				__('Email'),
				'Email',
				FORM_INPUT_TYPE_EMAIL);
			$form->setAdditionalText(
				"<span class='comment'><br />" .
					__("(optional, used only for receiving Notification on " .
						"replies and for the <a href='http://gravatar.com' " .
						"target='_blank' rel='nofollow'>" .
						"Avatar</a>)") .
				"</span>");
			$form->setStyle('width: 250px;');
		}
		
		$form->add(
			__('Comment'),
			'Comment',
			FORM_INPUT_TYPE_TEXTAREA,
			true);
		$form->setStyle('width: 350px; height: 150px;');
		
		if ($this->guestComments) {
			$form->add(
				__('Verification code'),
				null,
				FORM_INPUT_TYPE_VERIFICATION_CODE);
		}
	}
	
	function displayReplyForm($tocommentid = null) {
		if (!$this->guestComments && !$GLOBALS['USER']->loginok) {
			tooltip::display(
				__("Only registered users can comment."),
				TOOLTIP_NOTIFICATION);
			return;
		}
		
		$form = new form(
			__("Quick Reply"),
			'quickreply');
					
		$form->action = url::referer(true);
		$form->footer = '';
		
		$form->add(
			__('In Reply To'),
			'SubCommentOfID',
			FORM_INPUT_TYPE_HIDDEN,
			true,
			$tocommentid);
		
		$this->setupForm($form);
		
		$form->add(
			__('Submit Reply'),
			'newcommentsubmit',
			FORM_INPUT_TYPE_SUBMIT);
		
		if (!$GLOBALS['USER']->loginok)
			$form->addAdditionalText(
				'UserName',
				"(<a href='?request=users&amp;quicklogin=1&amp;anchor=comment".$tocommentid."' " .
					"class='ajax-content-link'>" .
					__("Login") .
				"</a>)");
		
		$form->display();
		
		unset($form);
	}
	
	function displayEditForm($commentid) {
		if (!(int)$commentid)
			return;
		
		$comment = sql::fetch(sql::run(
			" SELECT * FROM `{" . $this->sqlTable . "}`" .
			" WHERE `ID` = '".(int)$commentid."'"));
		
		if (!$comment) {
			tooltip::display(
				__("Selected comment cannot be found!"),
				TOOLTIP_ERROR);
			return;
		}
		
		if (!$GLOBALS['USER']->loginok || 
			(!$GLOBALS['USER']->data['Admin'] &&
			 $GLOBALS['USER']->data['ID'] != $comment['UserID'])) 
		{
			tooltip::display(
				__("You can only edit your own comments."),
				TOOLTIP_ERROR);
			return;
		}
		
		$form = new form(
			__("Edit Comment"),
			'editcomment');
		
		$form->action = url::referer(true);
		$form->footer = '';
		
		$form->add(
			__('Edit Comment'),
			'CommentID',
			FORM_INPUT_TYPE_HIDDEN,
			true,
			$commentid);
		
		$this->setupForm($form);
		
		$form->setValue('CommentID', $commentid);
		$form->setValue('UserName', $comment['UserName']);
		$form->setValue('Comment', $comment['Comment']);
		
		$form->add(
			__('Edit Comment'),
			'newcommentsubmit',
			FORM_INPUT_TYPE_SUBMIT);
		
		$form->display();
		
		unset($form);
	}
	
	function displayFunctions(&$row) {
		if ($GLOBALS['USER']->loginok && 
			$GLOBALS['USER']->data['Admin'])
		{
			echo	
				"<a class='comment-delete comment confirm-link' " .
					"href='".url::uri(strtolower(get_class($this)).', ' .
							comments::$uriVariables).
						"&amp;".strtolower(get_class($this))."=".$row['ID'] .
						"&amp;delete=1' " .
					"title=\"".
						htmlspecialchars(__("Delete comment and all it's subcomments"), ENT_QUOTES)."\">" .
					"<span>" .
						__("Delete") .
					"</span>" .
				"</a>";
		}
		
		if ($GLOBALS['USER']->loginok && 
			($row['UserID'] == $GLOBALS['USER']->data['ID'] ||
			$GLOBALS['USER']->data['Admin']))
		{
			echo	
				"<a class='comment-edit ajax-content-link comment' " .
					"href='".url::uri(strtolower(get_class($this)).', ' .
							comments::$uriVariables).
						"&amp;request=".$this->uriRequest .
						"&amp;".strtolower(get_class($this))."=".$row['ID'] .
						"&amp;edit=1' " .
					"title='".htmlspecialchars(__("Edit this comment"), ENT_QUOTES)."'>" .
					"<span>" .
						__("Edit") .
					"</span>" .
				"</a>";
		}
		
		if ($this->guestComments || $GLOBALS['USER']->loginok)
			echo
				"<a class='comment-reply ajax-content-link comment' " .
					"href='".url::uri(strtolower(get_class($this)).', ' .
							comments::$uriVariables).
						"&amp;request=".$this->uriRequest .
						"&amp;".strtolower(get_class($this))."=".$row['ID'] .
						"&amp;reply=1' " .
					"title='".htmlspecialchars(__("Reply to this comment"), ENT_QUOTES)."'>" .
					"<span>" .
						__("Reply") .
					"</span>" .
				"</a>";
		else			
			echo
				"<a class='comment-reply comment' " .
					"href='javascript:jQuery.jCore.tooltip.display(\"" .
						"<div class=\\\"tooltip error\\\"><span>" .
						htmlspecialchars(__("Only registered users can comment."), ENT_QUOTES) .
						"</span></div>\", true)' " .
					"title='".htmlspecialchars(__("Reply to this comment"), ENT_QUOTES)."'>" .
					"<span>" .
						__("Reply") .
					"</span>" .
				"</a>";
	}
	
	function displayRating(&$row) {
		$visiblerating = $row['Rating']-$this->defaultRating;
		if ($visiblerating > 0)
			$visiblerating = "+".$visiblerating;
		
		if (!$GLOBALS['USER']->loginok || 
			$row['UserID'] != $GLOBALS['USER']->data['ID'])
		{
			echo
				"<a class='comment-rating-up ajax-link comment' " .
					"href='".url::uri(strtolower(get_class($this)).', ' .
							comments::$uriVariables).
						"&amp;request=".$this->uriRequest .
						"&amp;".strtolower(get_class($this))."=".$row['ID'] .
						"&amp;rateup=1' " .
					"title='".htmlspecialchars(__("Vote Up"), ENT_QUOTES)."'>" .
				"</a>" .
				"<a class='comment-rating-down ajax-link comment' " .
					"href='".url::uri(strtolower(get_class($this)).', ' .
							comments::$uriVariables).
						"&amp;request=".$this->uriRequest .
						"&amp;".strtolower(get_class($this))."=".$row['ID'] .
						"&amp;ratedown=1' " .
					"title='".htmlspecialchars(__("Vote Down"), ENT_QUOTES)."'>" .
				"</a>";
		}
		
		if (!$visiblerating)
			return;
		
		echo
			"<span class='comment-rating comment'>" .
				$visiblerating .
			"</span>";
	}
	
	function displayIsPending(&$row) {
		if (!defined('MODERATED_COMMENTS') || !MODERATED_COMMENTS)
			return;
		
		if (defined('MODERATED_COMMENTS_BY_APPROVAL') &&
			MODERATED_COMMENTS_BY_APPROVAL && $row['Pending'])
		{
			echo
				" <span class='red' title='" .
					htmlspecialchars(__("Will be visible to the public once approved."), ENT_QUOTES) .
					"'>(" .
					__("Pending")."!" .
				")</span>";
			
		} elseif (defined('MODERATED_COMMENTS_PENDING_MINUTES') &&
			MODERATED_COMMENTS_PENDING_MINUTES)
		{
			$commenttime = strtotime($row['TimeStamp']);
			if ($commenttime <= time()-(int)MODERATED_COMMENTS_PENDING_MINUTES*60)
				return;
			
			echo
				" <span class='red' title='" .
					htmlspecialchars(sprintf(__("Will be visible to the public in %s minutes."),
						ceil(($commenttime - (time()-(int)MODERATED_COMMENTS_PENDING_MINUTES*60))/60)), ENT_QUOTES) .
					"'>(" .
					__("Pending")."!" .
				")</span>";
		}
	}
	
	function displayIP(&$row) {
		echo
			" (".long2ip($row['IP']).")";
	}
	
	function displayDetails(&$row) {
		echo
			calendar::dateTime($row['TimeStamp']) .
			" (<i>#".$row['ID']."</i>) ";
				
		$GLOBALS['USER']->displayUserName($row['_User'], __('by %s'));
		
		if ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'])
			$this->displayIP($row);
		
		$this->displayIsPending($row);
	}
	
	function displayComment(&$row) {
		echo
			preg_replace('/\<a /i', "<a target='_blank' rel='nofollow' ",
				nl2br($row['Comment']));
	}
	
	function displaySubComments(&$row) {
		$subrows = sql::run(
			$this->SQL($row['ID']));
		
		if (!sql::rows($subrows))
			return;
		
		while($subrow = sql::fetch($subrows)) {
			if ($subrow['SubCommentOfID'])
				echo 
					"<div class='sub-comment'>";
			
			$this->displayOne($subrow);
			
			if ($subrow['SubCommentOfID'])
				echo 
					"</div>";
		}
	}
	
	function displayAvatar(&$row) {
		if ($row['UserID'])
			return $GLOBALS['USER']->displayAvatar($row['UserID']);
		
		return $GLOBALS['USER']->displayAvatar($row['Email']);
	}
	
	function displayOne(&$row) {
		if ($row['UserID']) {
			$row['_User'] = $GLOBALS['USER']->get($row['UserID']);
			$row['_User']['DisplayUserName'] = $row['UserName'];
		} else {
			$row['_User']['UserName'] = $row['UserName'];
			$row['_User']['Email'] = '';
			$row['_User']['ID'] = 0;
			
			if (isset($row['Email']))
				$row['_User']['Email'] = $row['Email'];
		}
		
		if (!$row['Rating'])
			$row['Rating'] = $this->defaultRating;
		
		echo
			"<a name='comment".$row['ID']."'></a>" .
			"<div class='comment-entry comment-rating-".$row['Rating'] .
				(isset($row['_User']['Admin']) && $row['_User']['Admin']?
					" site-owner":
					null) .
				"'>";
		
		echo
				"<div class='comment-body'>";
		
		if (JCORE_VERSION >= '0.7') {
			echo
						"<div class='comment-avatar'>";
			
			$this->displayAvatar($row);
			
			echo
						"</div>";
		}
		
		echo
						"<div class='comment-text'>";
		
		$this->displayComment($row);
		
		echo
						"</div>" .
					"<div class='clear-both'></div>" .
				"</div>" .
				"<div class='comment-functions'>";
		
		$this->displayFunctions($row);
		$this->displayRating($row);
		
		echo
				"</div>" .
				"<div class='comment-details comment'>";
		
		$this->displayDetails($row);
		
		echo
				"</div>" .
			"</div>";
		
		$this->displaySubComments($row);
	}
	
	function displayForm(&$form) {
		$form->display();
	}
	
	function display() {
		if (!$this->sqlTable) {
			tooltip::display(
				__("Storage table not defined."),
				TOOLTIP_NOTIFICATION);
			
			return;
		}
		
		$replyto = null;
		$edit = null;
		
		if (isset($_POST['SubCommentOfID']))
			$replyto = (int)$_POST['SubCommentOfID'];
		
		if (isset($_POST['CommentID']))
			$edit = (int)$_POST['CommentID'];
		
		echo 
			"<div class='comments'>" .
				"<a name='comments'></a>";
		
		$form = new form(
			($replyto?
				sprintf(__("Reply To comment #%s"), $replyto):
				($edit?
					sprintf(__("Edit comment #%s"), $edit):
					__("New Comment"))),
			'newcomment');
		
		if ($replyto) {	
			$form->add(
				__('Reply To'),
				'SubCommentOfID',
				FORM_INPUT_TYPE_HIDDEN,
				false,
				$replyto);
		}
		
		if ($edit) {	
			$form->add(
				__('Edit Comment'),
				'CommentID',
				FORM_INPUT_TYPE_HIDDEN,
				false,
				$edit);
		}
		
		$form->add(
			__("You can use some HTML tags, such as &lt;b&gt;, &lt;i&gt;, " .
				"&lt;a&gt;, &lt;em&gt;, &lt;blockquote&gt;, &lt;code&gt;"),
			null,
			FORM_STATIC_TEXT);
	
		$this->setupForm($form);
		$form->addSubmitButtons();
		
		if ($replyto) {
			$form->add(
				__('Cancel Reply'),
				'cancel',
				 FORM_INPUT_TYPE_BUTTON);
			$form->addAttributes("onclick=\"window.location='".
				str_replace('&amp;', '&', url::uri())."'\"");
		}
		
		if ($edit) {
			$form->add(
				__('Cancel Edit'),
				'cancel',
				 FORM_INPUT_TYPE_BUTTON);
			$form->addAttributes("onclick=\"window.location='".
				str_replace('&amp;', '&', url::uri())."'\"");
		}
		
		if (!$GLOBALS['USER']->loginok)
			$form->addAdditionalText(
				'UserName',
				"(<a href='?request=users&amp;quicklogin=1&amp;anchor=newcomment' " .
					"class='ajax-content-link'>" .
					__("Login") .
				"</a>)");
		
		$this->verify($form);
						
		$rows = sql::run(
			$this->SQL());
		
		echo 
			"<h3 class='comments-title'>" .
				__("Comments") .
				" (".$this->countItems().")" .
			"</h3>";
		
		if (!sql::rows($rows)) {
			tooltip::display(
				__("No comments yet."),
				TOOLTIP_NOTIFICATION);
			
		} else {
			echo
				"<div class='comment-entries'>";
			
			while($row = sql::fetch($rows))
				$this->displayOne($row);
			
			echo
				"</div>";
		}
		
		if ($this->guestComments || $GLOBALS['USER']->loginok) {
			echo
				"<a name='newcomment'></a>";
			
			$this->displayForm($form);
			
		} else {
			tooltip::display(
				__("Only registered users can comment."),
				TOOLTIP_NOTIFICATION);
		}
		
		unset($form);
		
		echo "</div>";
	}
}

?>