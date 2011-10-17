<?php

/***************************************************************************
 *            comments.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

if (!defined('PAGINATED_COMMENTS'))
	define('PAGINATED_COMMENTS', false);

if (!defined('COMMENTS_PER_PAGE'))
	define('COMMENTS_PER_PAGE', 0);
 
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
	var $defaultRating = 7;
	var $uriRequest;
	var $commentURL;
	var $format = null;
	var $limit = 0;
	var $latests = false;
	var $showPaging = true;
	var $ajaxPaging = false;
	var $ajaxRequest = null;
	var $adminPath = null;
	var $userPermissionType = 0;
	
	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::comments', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::comments', $this, $handled);
			
			return $handled;
		}
		
		$this->commentURL = $this->getCommentURL();
		$this->uriRequest = strtolower(get_class($this));
		
		if ($this->sqlRow && isset($_GET[strtolower($this->sqlRow)]))
			$this->selectedOwnerID = (int)$_GET[strtolower($this->sqlRow)];
		
		if (JCORE_VERSION < '0.6')
			$this->defaultRating = 8;
		
		if (PAGINATED_COMMENTS) {
			$this->limit = COMMENTS_PER_PAGE;
			$this->ajaxPaging = (COMMENTS_PER_PAGE?true:false);
		}
		
		if ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin']) {
			if (!$this->adminPath) {
				$userpermission = array(
					'PermissionType' => USER_PERMISSION_TYPE_WRITE);
			} else {
				include_once('lib/userpermissions.class.php');
				
				$userpermission = userPermissions::check(
					(int)$GLOBALS['USER']->data['ID'], $this->adminPath);
			}
			
			$this->userPermissionType = $userpermission['PermissionType'];
		}
		
		api::callHooks(API_HOOK_AFTER,
			'comments::comments', $this);
	}
	
	function SQL($commentid = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::SQL', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::SQL', $this, $handled);
			
			return $handled;
		}
		
		$sql =
			" SELECT * FROM `{" . $this->sqlTable . "}`" .
			" WHERE 1" .
			($this->sqlRow && !$this->latests?
				" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
				null) .
			(!$this->latests?
				($commentid?
					" AND `SubCommentOfID` = '".(int)$commentid."'":
					" AND `SubCommentOfID` = 0"):
				null) .
			(defined('MODERATED_COMMENTS') && MODERATED_COMMENTS?
				 (defined('MODERATED_COMMENTS_PENDING_MINUTES') && 
				  MODERATED_COMMENTS_PENDING_MINUTES?
					" AND (`TimeStamp` <= DATE_SUB(NOW(), INTERVAL " .
						(int)MODERATED_COMMENTS_PENDING_MINUTES." MINUTE)" .
					" OR `IP` = '".security::ip2long((string)$_SERVER['REMOTE_ADDR'])."')":
					null) .
				 (defined('MODERATED_COMMENTS_BY_APPROVAL') && 
				  MODERATED_COMMENTS_BY_APPROVAL && (!$GLOBALS['USER']->loginok ||
				  !$GLOBALS['USER']->data['Admin'] || ~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE)?
					" AND (`Pending` = 0" .
					" OR `IP` = '".security::ip2long((string)$_SERVER['REMOTE_ADDR'])."')":
					null):
				null) .
			" ORDER BY " .
			($this->latests?
				" `TimeStamp` DESC,":
				" `TimeStamp`,") .
			" `ID`";
		
		api::callHooks(API_HOOK_AFTER,
			'comments::SQL', $this, $sql);
		
		return $sql;
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		if (!$this->sqlTable)
			return 0;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::countAdminItems', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::countAdminItems', $this, $handled);
			
			return $handled;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{".$this->sqlTable."}`" .
			" LIMIT 1"));
		
		api::callHooks(API_HOOK_AFTER,
			'comments::countAdminItems', $this, $row['Rows']);
		
		return $row['Rows'];
	}
	
	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::setupAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::setupAdmin', $this, $handled);
			
			return $handled;
		}
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Comment'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Settings'), 
			'?path=admin/site/settings');
		
		api::callHooks(API_HOOK_AFTER,
			'comments::setupAdmin', $this);
	}
	
	function setupAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::setupAdminForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::setupAdminForm', $this, $form, $handled);
			
			return $handled;
		}
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'comments::setupAdminForm', $this, $form);
	}
	
	function verifyAdmin(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::verifyAdmin', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::verifyAdmin', $this, $form, $handled);
			
			return $handled;
		}
		
		$search = null;
		$decline = null;
		$approve = null;
		$deleteall = null;
		$delete = null;
		$edit = null;
		$id = null;
		$ids = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags((string)$_GET['search']));
		
		if (isset($_POST['declinesubmit']))
			$decline = (string)$_POST['declinesubmit'];
		
		if (isset($_POST['approvesubmit']))
			$approve = (string)$_POST['approvesubmit'];
		
		if (isset($_POST['deleteallsubmit']))
			$deleteall = (string)$_POST['deleteallsubmit'];
		
		if (isset($_POST['deletesubmit']))
			$delete = (string)$_POST['deletesubmit'];
		
		if (isset($_POST['decline']))
			$decline = (int)$_POST['decline'];
		
		if (isset($_POST['approve']))
			$approve = (int)$_POST['approve'];
		
		if (isset($_POST['delete']))
			$delete = (int)$_POST['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
		if ($deleteall) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'comments::verifyAdmin', $this, $form);
				return false;
			}
			
			sql::run(
				" DELETE FROM `{".$this->sqlTable . "}`" .
				" WHERE 1" .
				($this->sqlRow?
					" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
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
				
			api::callHooks(API_HOOK_AFTER,
				'comments::verifyAdmin', $this, $form, $deleteall);
			
			return true;
		}
		
		if (!$id && !$ids && 
			($decline || $approve || $delete))
		{
			tooltip::display(
				__("No comment selected! Please select at " .
					"least one comment."),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'comments::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if ($ids && count($ids)) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'comments::verifyAdmin', $this, $form);
				return false;
			}
			
			if ($decline) {
				foreach($ids as $id)
					$this->decline((int)$id);
				
				tooltip::display(
					__("Comments have been successfully declined and " .
						"are now NOT visible to the public."),
					TOOLTIP_SUCCESS);
					
				api::callHooks(API_HOOK_AFTER,
					'comments::verifyAdmin', $this, $form, $decline);
				
				return true;
			}
			
			if ($approve) {
				foreach($ids as $id)
					$this->approve((int)$id);
				
				tooltip::display(
					__("Comments have been successfully approved and " .
						"are now visible to the public."),
					TOOLTIP_SUCCESS);
					
				api::callHooks(API_HOOK_AFTER,
					'comments::verifyAdmin', $this, $form, $approve);
				
				return true;
			}
			
			if ($delete) {
				foreach($ids as $id)
					$this->delete((int)$id);
				
				tooltip::display(
					__("Comments have been successfully deleted."),
					TOOLTIP_SUCCESS);
					
				api::callHooks(API_HOOK_AFTER,
					'comments::verifyAdmin', $this, $form, $delete);
				
				return true;
			}
		}
			
		if ($decline) {
			$result = $this->decline($id);
			
			if ($result)
				tooltip::display(
					__("Comment has been successfully declined and " .
						"is now NOT visible to the public."),
					TOOLTIP_SUCCESS);
				
			api::callHooks(API_HOOK_AFTER,
				'comments::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if ($approve) {
			$result = $this->approve($id);
			
			if ($result)
				tooltip::display(
					__("Comment has been successfully approved and " .
						"is now visible to the public."),
					TOOLTIP_SUCCESS);
				
			api::callHooks(API_HOOK_AFTER,
				'comments::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'comments::verifyAdmin', $this, $form);
				return false;
			}
			
			$result = $this->delete($id);
			
			if ($result)
				tooltip::display(
					__("Comment has been successfully deleted."),
					TOOLTIP_SUCCESS);
				
			api::callHooks(API_HOOK_AFTER,
				'comments::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'comments::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if ($edit) {
			$result = $this->edit($id, $form->getPostArray());
			
			if ($result)
				tooltip::display(
					__("Comment has been successfully updated.")." " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);
				
			api::callHooks(API_HOOK_AFTER,
				'comments::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		$newid = $this->add($form->getPostArray());
		
		if ($newid) {
			tooltip::display(
				__("Comment has been successfully created.")." " .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$newid."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
				
			$form->reset();
		}
		
		api::callHooks(API_HOOK_AFTER,
			'comments::verifyAdmin', $this, $form, $newid);
		
		return $newid;
	}
	
	function displayAdminListHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayAdminListHeader', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayAdminListHeader', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<th>" .
				"<input type='checkbox' class='checkbox-all' " .
				(~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE?
					"disabled='disabled' ":
					null) .
				"/>" .
			"#</th>" .
			"<th><span class='nowrap'>".
				__("Posted By")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Comment")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayAdminListHeader', $this);
	}
	
	function displayAdminListHeaderOptions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayAdminListHeaderOptions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayAdminListHeaderOptions', $this, $handled);
			
			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'comments::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayAdminListHeaderFunctions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayAdminListHeaderFunctions', $this, $handled);
			
			return $handled;
		}
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayAdminListItem', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayAdminListItem', $this, $row, $handled);
			
			return $handled;
		}
		
		$ids = null;
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
		echo
			"<td>" .
				"<span class='nowrap'>" .
				"<label>" .
				"<input type='checkbox' name='ids[]' " .
					"value='".$row['ID']."' " .
					($ids && in_array($row['ID'], $ids)?
						"checked='checked' ":
						null).
					(~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE?
						"disabled='disabled' ":
						null) .
					" />" .
				"#".(int)$row['ID']."<br />" .
				((int)$row['SubCommentOfID']?
					"<span class='comment'>&nbsp; &#8594; #" .
						(int)$row['SubCommentOfID'] .
					"</span>":
					null) .
				"</span>" .
				"</label>" .
			"</td>" .
			"<td align='center'>" .
				"<div class='admin-content-preview'>";
		
		if (JCORE_VERSION >= '0.7')
			$this->displayAvatar($row);
		
		$avatarsize = 64;
		if (defined('AVATARS_SIZE') && AVATARS_SIZE)
			$avatarsize = (int)AVATARS_SIZE;
		
		echo
					"<div style='width: ".$avatarsize."px; word-wrap: break-word;' class='bold'>" .
						$row['UserName'] .
					"</div>" .
					"<div style='width: ".$avatarsize."px; word-wrap: break-word;'>";
			
		if ($row['UserID']) {
			$user = $GLOBALS['USER']->get($row['UserID']);
			$GLOBALS['USER']->displayUserName($user, '(%s)');
			
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
					calendar::datetime($row['TimeStamp']) .
				" (".security::long2ip($row['IP']).") ";
		
		$this->displayIsPending($row);
		
		echo
				" (<a href='".url::uri('edit')."#adminform' " .
					"onclick=\"jQuery('#entrySubCommentOfID').val('".$row['ID']."');\" " .
					"class='comment'>" .
					__("Reply") .
				"</a>)" .
				"</div>" .
				"</div>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListItemOptions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayAdminListItemOptions', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayAdminListItemOptions', $this, $row, $handled);
			
			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'comments::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayAdminListItemFunctions', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayAdminListItemFunctions', $this, $row, $handled);
			
			return $handled;
		}
		
		if (defined('MODERATED_COMMENTS') && MODERATED_COMMENTS &&
			defined('MODERATED_COMMENTS_BY_APPROVAL') && 
			MODERATED_COMMENTS_BY_APPROVAL)
		{
			if ($row['Pending'])
				echo
					"<td align='center'>" .
						"<a class='admin-link lock confirm-link' " .
							"title='".htmlspecialchars(__("Approve"), ENT_QUOTES)."' " .
							"href='".url::uri('id, edit, delete, approve, decline') .
							"&amp;id=".$row['ID']."&amp;approve=1'>" .
						"</a>" .
					"</td>";
			else
				echo
					"<td align='center'>" .
						"<a class='admin-link apply confirm-link' " .
							"title='".htmlspecialchars(__("Decline"), ENT_QUOTES)."' " .
							"href='".url::uri('id, edit, delete, approve, decline') .
							"&amp;id=".$row['ID']."&amp;decline=1'>" .
						"</a>" .
					"</td>";
		}
				
		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete, approve, decline') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete, approve, decline') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminListSearch() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayAdminListSearch', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayAdminListSearch', $this, $handled);
			
			return $handled;
		}
		
		$search = null;
		$searchtype = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags((string)$_GET['search']));
		
		if (isset($_GET['searchtype']))
			$searchtype = (int)$_GET['searchtype'];
		
		echo
			"<input type='hidden' name='path' value='".admin::path()."' />" .
			"<input type='search' name='search' value='".
				htmlspecialchars($search, ENT_QUOTES).
				"' results='5' placeholder='" .
					htmlspecialchars(__("search..."), ENT_QUOTES)."' /> ";
		
		if (defined('MODERATED_COMMENTS') && MODERATED_COMMENTS)
			echo
				"<select name='searchtype' onchange='this.form.submit();'>" .
					"<option value=''>" .
						__("All")."</option>" .
					"<option value='1'".($searchtype == 1?" selected='selected'":null).">" .
						__("Pending")."</option>" .
					"<option value='2'".($searchtype == 2?" selected='selected'":null).">" .
						__("Approved")."</option>" .
				"</select> ";
		
		echo
			"<input type='submit' value='" .
				htmlspecialchars(__("Search"), ENT_QUOTES)."' class='button' />";
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayAdminListSearch', $this);
	}
	
	function displayAdminListFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayAdminListFunctions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayAdminListFunctions', $this, $handled);
			
			return $handled;
		}
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayAdminListFunctions', $this);
	}
	
	function displayAdminList(&$rows) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayAdminList', $this, $rows);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayAdminList', $this, $rows, $handled);
			
			return $handled;
		}
		
		echo
			"<form action='".
				url::uri('edit, delete, approve, decline')."' method='post'>" .
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
			'comments::displayAdminList', $this, $rows);
	}
	
	function displayAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayAdminForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayAdminForm', $this, $form, $handled);
			
			return $handled;
		}
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayAdminTitle', $this, $ownertitle);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayAdminTitle', $this, $ownertitle, $handled);
			
			return $handled;
		}
		
		admin::displayTitle(
			__(trim(ucfirst(preg_replace('/([A-Z])/', ' \1', 
				$this->sqlOwnerCountField)))), 
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayAdminDescription', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayAdminDescription', $this, $handled);
			
			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'comments::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		if (!$this->sqlTable) {
			echo
				"<br />";
			
			tooltip::display(
				__("Storage table not defined."),
				TOOLTIP_NOTIFICATION);
			
			return;
		}
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayAdmin', $this, $handled);
			
			return $handled;
		}
		
		$search = null;
		$searchtype = null;
		$decline = null;
		$approve = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags((string)$_GET['search']));
		
		if (isset($_GET['searchtype']))
			$searchtype = (int)$_GET['searchtype'];
		
		if (isset($_GET['decline']))
			$decline = (int)$_GET['decline'];
		
		if (isset($_GET['approve']))
			$approve = (int)$_GET['approve'];
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
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
		
		if ($id && (($delete && empty($_POST['delete'])) || 
			($decline && empty($_POST['decline'])) ||
			($approve && empty($_POST['approve']))))
		{
			$selected = sql::fetch(sql::run(
				" SELECT `Comment` FROM `{".$this->sqlTable."}`" .
				" WHERE `ID` = '".$id."'"));
			
			if ($delete)
				url::displayConfirmation(
					'<b>'.__('Delete').'?!</b> "'.comments::generateTeaser($selected['Comment']).'"');
			if ($decline)
				url::displayConfirmation(
					'<b>'.__('Decline').'?!</b> "'.comments::generateTeaser($selected['Comment']).'"',
					'decline');
			if ($approve)
				url::displayConfirmation(
					'<b>'.__('Approve').'?!</b> "'.comments::generateTeaser($selected['Comment']).'"',
					'approve');
		}
		
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$verifyok = $this->verifyAdmin($form);
		
		$paging = new paging(10);
		$paging->ignoreArgs = 'id, edit, delete, approve, decline';
		
		$rows = sql::run(
				" SELECT * FROM `{".$this->sqlTable."}`" .
				" WHERE 1" .
				($this->sqlRow?
					" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
					null) .
				($searchtype?
					" AND `Pending` = '".($searchtype == 1?1:0)."'":
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{".$this->sqlTable."}`" .
					" WHERE `ID` = '".$id."'"));
				
				if ($selected['SubCommentOfID'])
					$form->addValue('SubCommentOfID',
						$selected['SubCommentOfID'],
						"#".$selected['SubCommentOfID']);
				
				if (JCORE_VERSION >= '0.7' && !$selected['UserID'])
					$form->edit('Email', null, null, FORM_INPUT_TYPE_EMAIL);
					
				$form->setValues($selected);
			}
			
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form);
		
		echo "</div>";	//admin-content
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayAdmin', $this);
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		$values[$this->sqlRow] = $this->selectedOwnerID;
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
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::add', $this, $values);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::add', $this, $values, $handled);
			
			return $handled;
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
			" `IP` = '".security::ip2long((string)$_SERVER['REMOTE_ADDR'])."'," .
			" `TimeStamp` = NOW()");
			
		if (!$newid) {
			tooltip::display(
				sprintf(__("Comment couldn't be added! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'comments::add', $this, $values);
			
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
				"CommentURL" => str_replace('&amp;', '&', 
					$this->getCommentURL($values))."#comment".(int)$newid);
		
		$selectedowner = null;
		
		if ($this->sqlOwnerTable) {
			$selectedowner = sql::fetch(sql::run(
				" SELECT * FROM `{" .$this->sqlOwnerTable . "}`" .
				" WHERE `ID` = '".$this->selectedOwnerID."'"));
			
			$email->variables["CommentSectionTitle"] = 
					$selectedowner[$this->sqlOwnerField];
		}
			
		if (!$GLOBALS['USER']->data['Admin'])
			$email->send();
		
		if ($selectedowner) {
			sql::run(
				" UPDATE `{".$this->sqlOwnerTable."}` SET " .
				" `".$this->sqlOwnerCountField."` = `".$this->sqlOwnerCountField."` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".$this->selectedOwnerID."'");
		}
		
		if (defined('MODERATED_COMMENTS') && MODERATED_COMMENTS &&
			defined('MODERATED_COMMENTS_BY_APPROVAL') && MODERATED_COMMENTS_BY_APPROVAL &&
			(!$GLOBALS['USER']->loginok || !$GLOBALS['USER']->data['Admin']))
		{
			unset($email);
			
			api::callHooks(API_HOOK_AFTER,
				'comments::add', $this, $values, $newid);
			
			return $newid;
		}
		
		if ($selectedowner && isset($selectedowner['UserID']) && $selectedowner['UserID'] &&
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
				(!$selectedowner || !isset($selectedowner['UserID']) ||
				$replytocomment['UserID'] != $selectedowner['UserID']) && 
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
			
		unset($email);
		
		api::callHooks(API_HOOK_AFTER,
			'comments::add', $this, $values, $newid);
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::edit', $this, $id, $values);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::edit', $this, $id, $values, $handled);
			
			return $handled;
		}
		
		$comment = $this->get($id);
		
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
		
		$result = (sql::affected() != -1);
		if (!$result) {
			tooltip::display(
				sprintf(__("Comment couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			
		} elseif (!$GLOBALS['USER']->data['Admin']) {
			$email = new email();
			$email->load('CommentEdit');
			
			$email->to = WEBMASTER_EMAIL;
			$email->variables = array(
				"CommentSectionTitle" => '',
				"CommentSection" => $this->selectedOwner,
				"CommentBy" => $values['UserName'],
				"CommentBody" => $values['Comment'],
				"CommentURL" => str_replace('&amp;', '&', 
					$this->getCommentURL($comment))."#comment".(int)$id);
			
			if ($this->sqlOwnerTable) {
				$selectedowner = sql::fetch(sql::run(
					" SELECT `".$this->sqlOwnerField."` FROM `{" .$this->sqlOwnerTable . "}`" .
					" WHERE `ID` = '".$comment[$this->sqlRow]."'"));
				
				$email->variables["CommentSectionTitle"] = 
						$selectedowner[$this->sqlOwnerField];
			}
			
			$email->send();
			unset($email);
		}
		
		api::callHooks(API_HOOK_AFTER,
			'comments::edit', $this, $id, $values, $result);
		
		return $result;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::delete', $this, $id);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::delete', $this, $id, $handled);
			
			return $handled;
		}
		
		$comment = $this->get($id);
		
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
				" WHERE `".$this->sqlRow."` = '".$comment[$this->sqlRow]."'"));
			
			sql::run(
				" UPDATE `{".$this->sqlOwnerTable . "}`" .
				" SET `".$this->sqlOwnerCountField."` = '".(int)$row['Rows']."'," .
				" `TimeStamp` = `TimeStamp` " .
				" WHERE `ID` = '".$comment[$this->sqlRow]."'");
		}
					
		api::callHooks(API_HOOK_AFTER,
			'comments::delete', $this, $id);
		
		return true;
	}
	
	function decline($id) {
		if (!$id)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::decline', $this, $id);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::decline', $this, $id, $handled);
			
			return $handled;
		}
		
		sql::run(
			" UPDATE `{".$this->sqlTable."}` SET ".
			" `TimeStamp` = `TimeStamp`," .
			" `Pending` = 1" .
			" WHERE `ID` = '".(int)$id."'");
		
		$result = (sql::affected() != -1);
		if (!$result)
			tooltip::display(
				sprintf(__("Comment couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'comments::decline', $this, $id, $result);
		
		return $result;
	}
	
	function approve($id) {
		if (!$id)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::approve', $this, $id);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::approve', $this, $id, $handled);
			
			return $handled;
		}
		
		sql::run(
			" UPDATE `{".$this->sqlTable."}` SET ".
			" `TimeStamp` = `TimeStamp`," .
			" `Pending` = 0" .
			" WHERE `ID` = '".(int)$id."'");
		
		$result = (sql::affected() != -1);
		if (!$result) {
			tooltip::display(
				sprintf(__("Comment couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			
		} else {
			if (!defined('MODERATED_COMMENTS') || !MODERATED_COMMENTS ||
				!defined('MODERATED_COMMENTS_BY_APPROVAL') ||
				!MODERATED_COMMENTS_BY_APPROVAL)
			{
				api::callHooks(API_HOOK_AFTER,
					'comments::approve', $this, $id, $result);
				
				return $result;
			}
			
			$comment = $this->get($id);
			
			$email = new email();
			$email->variables = array(
				"CommentSectionTitle" => '', 
				"CommentSection" => $this->selectedOwner,
				"CommentBy" => $comment['UserName'],
				"CommentBody" => $comment['Comment'],
				"CommentURL" => str_replace('&amp;', '&', 
					$this->getCommentURL($comment))."#comment".(int)$id);
			
			$selectedowner = null;
			
			if ($this->sqlOwnerTable) {
				$selectedowner = sql::fetch(sql::run(
					" SELECT * FROM `{" .$this->sqlOwnerTable . "}`" .
					" WHERE `ID` = '".$comment[$this->sqlRow]."'"));
				
				$email->variables["CommentSectionTitle"] = 
					$selectedowner[$this->sqlOwnerField];
			}
			
			if ($selectedowner && isset($selectedowner['UserID']) && $selectedowner['UserID'] &&
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
			
			if (isset($comment['SubCommentOfID']) && (int)$comment['SubCommentOfID']) {
				$email->reset();
				$replytocomment = sql::fetch(sql::run(
					" SELECT * FROM `{".$this->sqlTable."}` " .
					" WHERE `ID` = '".(int)$comment['SubCommentOfID']."'"));
				
				if ($replytocomment['UserID'] && 
					(!$selectedowner || !isset($selectedowner['UserID']) ||
					$replytocomment['UserID'] != $selectedowner['UserID']) && 
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
				
			unset($email);
		}
		
		api::callHooks(API_HOOK_AFTER,
			'comments::approve', $this, $id, $result);
		
		return $result;
	}
	
	// ************************************************   Client Part
	function get($id) {
		if (!$id)
			return false;
		
		return sql::fetch(sql::run(
			" SELECT * FROM `{".$this->sqlTable."}` " .
			" WHERE `ID` = '".(int)$id."'"));
	}
	
	function getSubComments($commentid = 0, $firstcall = true,
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0)) 
	{
		$rows = sql::run(
			" SELECT * FROM `{".$this->sqlTable."}` " .
			($commentid?
				" WHERE `SubCommentOfID` = '".$commentid."'":
				" WHERE `SubCommentOfID` = 0") .
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
	
	static function getCommentURL($comment = null) {
		$url = url::get();
		
		if ($pos = strpos($url, '#'))
			$url = substr($url, 0, $pos);
		
		return $url;
	}
	
	static function generateTeaser($description, $length = 130) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::generateTeaser', $_ENV, $description, $length);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::generateTeaser', $_ENV, $description, $length, $handled);
			
			return $handled;
		}
		
		$teaser = strip_tags($description);
		
		if (strlen($teaser) > $length) {
			list($teaser) = explode('<sep>', wordwrap($teaser, $length, '<sep>'));
			$teaser .= " ...";
		}
		
		api::callHooks(API_HOOK_AFTER,
			'comments::generateTeaser', $_ENV, $description, $length, $teaser);
		
		return $teaser;
	}
	
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
				" AND `IP` = '".security::ip2long((string)$_SERVER['REMOTE_ADDR'])."'" .
				" AND `TimeStamp` > DATE_SUB(NOW(), INTERVAL 1 DAY)":
				" AND `UserID` = '".(int)$GLOBALS['USER']->data['ID']."'")));
			
		if ($row) {
			tooltip::display(
				__("You already rated this comment."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::rate', $this, $id, $rating);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::rate', $this, $id, $rating, $handled);
			
			return $handled;
		}
		
		sql::run(
			" INSERT INTO `{".$this->sqlTable."ratings}`" .
			" SET `CommentID` = '".$id."'," .
			" `IP` = '".security::ip2long((string)$_SERVER['REMOTE_ADDR'])."'," .
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
		
		if (JCORE_VERSION >= '0.8') {
			$rating = $positiveratings-$negativeratings;
			
		} else {
			$rating = $this->defaultRating+
				($positiveratings-$negativeratings);
			
			if ($rating < 1)
				$rating = 1;
				
			if ($rating > 10)
				$rating = 10;
		}
				
		sql::run(
			" UPDATE `{".$this->sqlTable . "}`" .
			" SET `TimeStamp` = `TimeStamp`," .
			" `Rating` = ".(float)$rating .
			" WHERE `ID` = '".$id."'");
		
		tooltip::display(
			__("Thank you for rating this comment."),
			TOOLTIP_SUCCESS);
		
		api::callHooks(API_HOOK_AFTER,
			'comments::rate', $this, $id, $rating, $row);
		
		return true;
	}
	
	function verify(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::verify', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::verify', $this, $form, $handled);
			
			return $handled;
		}
		
		$commentid = null;
		$delete = null;
		$approve = false;
		
		if (isset($_GET[strtolower(get_class($this))]))
			$commentid = (int)$_GET[strtolower(get_class($this))];
			
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
			
		if (isset($_POST['approve']))
			$approve = (int)$_POST['approve'];
		
		if ($delete) {
			if (!$GLOBALS['USER']->loginok ||
				!$GLOBALS['USER']->data['Admin'])
			{
				tooltip::display(
					__("Only administrators can delete comments."),
					TOOLTIP_ERROR);
				
				api::callHooks(API_HOOK_AFTER,
					'comments::verify', $this, $form);
				
				return false;
			}
			
			if (~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);
				
				api::callHooks(API_HOOK_AFTER,
					'comments::verify', $this, $form);
				
				return false;
			}
			
			$result = $this->delete($commentid);
			
			if ($result)
				tooltip::display(
					__("Comment has been successfully deleted."),
					TOOLTIP_SUCCESS);
				
			api::callHooks(API_HOOK_AFTER,
				'comments::verify', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form || (!$this->guestComments && !$GLOBALS['USER']->loginok)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::verify', $this, $form);
			
			return false;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'comments::verify', $this, $form);
			
			return false;
		}
		
		$commentid = $form->get('CommentID');
		if ($commentid) {
			$comment = sql::fetch(sql::run(
				" SELECT * FROM `{" . $this->sqlTable . "}`" .
				" WHERE `ID` = '".(int)$commentid."'"));
			
			if (!$comment) {
				tooltip::display(
					__("Selected comment cannot be found!"),
					TOOLTIP_ERROR);
				
				api::callHooks(API_HOOK_AFTER,
					'comments::verify', $this, $form);
				
				return false;
			}
			
			if (!$GLOBALS['USER']->loginok || 
				(!$GLOBALS['USER']->data['Admin'] &&
				 $GLOBALS['USER']->data['ID'] != $comment['UserID'])) 
			{
				tooltip::display(
					__("You can only edit your own comments."),
					TOOLTIP_ERROR);
				
				api::callHooks(API_HOOK_AFTER,
					'comments::verify', $this, $form);
				
				return false;
			}
			
			if ($GLOBALS['USER']->data['ID'] != $comment['UserID'] &&
				~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE) 
			{
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);
				
				api::callHooks(API_HOOK_AFTER,
					'comments::verify', $this, $form);
				
				return false;
			}
			
			if (defined('MODERATED_COMMENTS') && MODERATED_COMMENTS &&
				defined('MODERATED_COMMENTS_BY_APPROVAL') && 
				MODERATED_COMMENTS_BY_APPROVAL && $approve && 
				$comment['Pending'] && $GLOBALS['USER']->data['Admin'] &&
				$this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
				$this->approve($commentid);
			
			$result = $this->edit($commentid, $form->getPostArray());
			
			if ($result)
				tooltip::display(
					__("Your comment has been successfully updated.").
					"<script>window.location='#comment".$commentid."';</script>",
					TOOLTIP_SUCCESS);
		
			api::callHooks(API_HOOK_AFTER,
				'comments::verify', $this, $form, $result);
			
			return $result;
		}
		
		$newid = $this->add($form->getPostArray());
		
		if ($newid) {
			tooltip::display(
				__("Thank you for your comment.").
					"<script>window.location='#comment".$newid."';</script>",
				TOOLTIP_SUCCESS);
			
			$form->reset();
		}
		
		api::callHooks(API_HOOK_AFTER,
			'comments::verify', $this, $form, $newid);
		
		return $newid;
	}
	
	function isPending(&$row) {
		if (!defined('MODERATED_COMMENTS') || !MODERATED_COMMENTS)
			return false;
		
		if (defined('MODERATED_COMMENTS_BY_APPROVAL') &&
			MODERATED_COMMENTS_BY_APPROVAL && $row['Pending'])
			return true;
		
		if (defined('MODERATED_COMMENTS_PENDING_MINUTES') &&
			MODERATED_COMMENTS_PENDING_MINUTES)
		{ 
			$commenttime = strtotime($row['TimeStamp']);
			
			if ($commenttime > time()-(int)MODERATED_COMMENTS_PENDING_MINUTES*60)
				return $commenttime;
		}
		
		return false;
	}
	
	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::ajaxRequest', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::ajaxRequest', $this, $handled);
			
			return $handled;
		}
		
		$commentid = null;
		
		if (isset($_GET[strtolower(get_class($this))]))
			$commentid = (int)$_GET[strtolower(get_class($this))];
		
		$rateup = null;
		$ratedown = null;
		$reply = null;
		$edit = null;
			
		if (isset($_GET['rateup']))
			$rateup = (int)$_GET['rateup'];
	
		if (isset($_GET['ratedown']))
			$ratedown = (int)$_GET['ratedown'];
	
		if (isset($_GET['reply']))
			$reply = (int)$_GET['reply'];
			
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
			
		if ($rateup || $ratedown || $reply || $edit) {
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
				$result = $this->rate($commentid, 1);
				
			} elseif ($ratedown) {
				$result = $this->rate($commentid, -1);
				
			} elseif ($reply) {
				$result = $this->displayReplyForm($commentid);
				
			} elseif ($edit) {
				$result = $this->displayEditForm($commentid);
			}
			
			api::callHooks(API_HOOK_AFTER,
				'comments::ajaxRequest', $this, $result);
			
			return true;
		}
		
		$result = true;
		$this->ajaxPaging = true;
		$this->display();
		
		api::callHooks(API_HOOK_AFTER,
			'comments::ajaxRequest', $this, $result);
		
		return true;
	}
	
	function countItems($toplevels = false) {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{" . $this->sqlTable . "}`" .
			" WHERE 1" .
			($this->sqlRow && !$this->latests?
				" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
				null) .
			($toplevels?
				" AND `SubCommentOfID` = 0" .
				(defined('MODERATED_COMMENTS') && MODERATED_COMMENTS?
					 (defined('MODERATED_COMMENTS_PENDING_MINUTES') && 
					  MODERATED_COMMENTS_PENDING_MINUTES?
						" AND (`TimeStamp` <= DATE_SUB(NOW(), INTERVAL " .
							(int)MODERATED_COMMENTS_PENDING_MINUTES." MINUTE)" .
						" OR `IP` = '".security::ip2long((string)$_SERVER['REMOTE_ADDR'])."')":
						null) .
					 (defined('MODERATED_COMMENTS_BY_APPROVAL') && 
					  MODERATED_COMMENTS_BY_APPROVAL && (!$GLOBALS['USER']->loginok ||
					  !$GLOBALS['USER']->data['Admin'] || ~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE)?
						" AND (`Pending` = 0" .
						" OR `IP` = '".security::ip2long((string)$_SERVER['REMOTE_ADDR'])."')":
						null):
					null):
				null) .
			" LIMIT 1"));
		
		return (int)$row['Rows'];
	}
	
	function setupForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::setupForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::setupForm', $this, $form, $handled);
			
			return $handled;
		}
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'comments::setupForm', $this, $form);
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
					
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayReplyForm', $this, $tocommentid, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayReplyForm', $this, $tocommentid, $form, $handled);
			
			return $handled;
		}
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayReplyForm', $this, $tocommentid, $form);
		
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
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayEditForm', $this, $commentid, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayEditForm', $this, $commentid, $form, $handled);
			
			return $handled;
		}
		
		$form->action = url::referer(true);
		$form->footer = '';
		
		$form->add(
			__('Edit Comment'),
			'CommentID',
			FORM_INPUT_TYPE_HIDDEN,
			true,
			$commentid);
		
		$this->setupForm($form);
		
		if ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'] &&
			$this->userPermissionType & USER_PERMISSION_TYPE_WRITE && $this->isPending($comment) &&
			defined('MODERATED_COMMENTS_BY_APPROVAL') && MODERATED_COMMENTS_BY_APPROVAL)
		{
			$form->add(
				__('Approve'),
				'approve',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				1);
		}
		
		$form->setValue('CommentID', $commentid);
		$form->setValue('UserName', $comment['UserName']);
		$form->setValue('Comment', $comment['Comment']);
		
		$form->add(
			__('Edit Comment'),
			'newcommentsubmit',
			FORM_INPUT_TYPE_SUBMIT);
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayEditForm', $this, $commentid, $form);
		
		unset($form);
	}
	
	function displayFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayFunctions', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayFunctions', $this, $row, $handled);
			
			return $handled;
		}
		
		if ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'] &&
			$this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
		{
			echo
				"<a class='comment-delete comment confirm-link' " .
					"href='".url::uri(strtolower(get_class($this)).', ' .
							comments::$uriVariables).
						"&amp;".strtolower(get_class($this))."=".$row['ID'] .
						"&amp;delete=1#comments' " .
					"title=\"".
						htmlspecialchars(__("Delete comment and all it's subcomments"), ENT_QUOTES)."\">" .
					"<span>" .
						__("Delete") .
					"</span>" .
				"</a>";
		}
		
		if ($GLOBALS['USER']->loginok && ($row['UserID'] == $GLOBALS['USER']->data['ID'] ||
			($GLOBALS['USER']->data['Admin'] && $this->userPermissionType & USER_PERMISSION_TYPE_WRITE)))
		{
			echo
				"<a class='comment-edit ajax-content-link comment' " .
					"href='".url::uri(strtolower(get_class($this)).', ' .
							comments::$uriVariables).
						"&amp;request=".$this->uriRequest .
						"&amp;".strtolower(get_class($this))."=".$row['ID'] .
						"&amp;edit=1" .
						(JCORE_VERSION > '0.9'?
							"#newcomment":
							null) .
						"' " .
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
						"&amp;reply=1" .
						(JCORE_VERSION > '0.9'?
							"#newcomment":
							null) .
						"' " .
					"title='".htmlspecialchars(__("Reply to this comment"), ENT_QUOTES)."'>" .
					"<span>" .
						__("Reply") .
					"</span>" .
				"</a>";
		else			
			echo
				"<a class='comment-reply comment' " .
					"href='javascript:$.jCore.tooltip.display(\"" .
						"<div class=\\\"tooltip error\\\"><span>" .
						htmlspecialchars(__("Only registered users can comment."), ENT_QUOTES) .
						"</span></div>\", true)' " .
					"title='".htmlspecialchars(__("Reply to this comment"), ENT_QUOTES)."'>" .
					"<span>" .
						__("Reply") .
					"</span>" .
				"</a>";
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayFunctions', $this, $row);
	}
	
	function displayRating(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayRating', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayRating', $this, $row, $handled);
			
			return $handled;
		}
		
		$visiblerating = 0;
		
		if (JCORE_VERSION >= '0.8')
			$visiblerating = $row['Rating'];
		elseif ($row['Rating'])
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
		
		if ($visiblerating)
			echo
				"<span class='comment-rating comment'>" .
					$visiblerating .
				"</span>";
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayRating', $this, $row);
	}
	
	function displayIsPending(&$row) {
		if (!$pending = $this->isPending($row))
			return;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayIsPending', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayIsPending', $this, $row, $handled);
			
			return $handled;
		}
		
		if (defined('MODERATED_COMMENTS_BY_APPROVAL') &&
			MODERATED_COMMENTS_BY_APPROVAL && $pending)
		{
			echo
				" <span class='red' title='" .
					htmlspecialchars(__("Will be visible to the public once approved."), ENT_QUOTES) .
					"'>(" .
					__("Pending")."!" .
				")</span>";
			
		} elseif (defined('MODERATED_COMMENTS_PENDING_MINUTES') &&
			MODERATED_COMMENTS_PENDING_MINUTES && $pending)
		{
			echo
				" <span class='red' title='" .
					htmlspecialchars(sprintf(__("Will be visible to the public in %s minutes."),
						ceil(($pending - (time()-(int)MODERATED_COMMENTS_PENDING_MINUTES*60))/60)), ENT_QUOTES) .
					"'>(" .
					__("Pending")."!" .
				")</span>";
		}
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayIsPending', $this, $row);
	}
	
	function displayIP(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayIP', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayIP', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			" (".security::long2ip($row['IP']).")";
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayIP', $this, $row);
	}
	
	function displayDetails(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayDetails', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayDetails', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			"<span class='details-date'>" .
			calendar::dateTime($row['TimeStamp']) .
			" </span>" .
			"<span class='comment-id'>" .
				"(<i>#".$row['ID']."</i>)" .
			" </span>";
				
		$GLOBALS['USER']->displayUserName($row['_User'], __('by %s').' ');
		
		if ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin']) {
			echo
				"<span class='comment-ip'>";
			$this->displayIP($row);
			echo
				" </span>";
		}
		
		$this->displayIsPending($row);
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayDetails', $this, $row);
	}
	
	function displayComment(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayComment', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayComment', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			preg_replace('/\<a /i', "<a target='_blank' rel='nofollow' ",
				nl2br($row['Comment']));
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayComment', $this, $row);
	}
	
	function displaySubComments(&$row) {
		$subrows = sql::run(
			$this->SQL($row['ID']));
		
		if (!sql::rows($subrows))
			return;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displaySubComments', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displaySubComments', $this, $row, $handled);
			
			return $handled;
		}
		
		while($subrow = sql::fetch($subrows)) {
			if ($subrow['SubCommentOfID'])
				echo 
					"<div class='sub-comment'>";
			
			if ($this->format)
				$this->displayFormated($subrow);
			else
				$this->displayOne($subrow);
			
			if ($subrow['SubCommentOfID'])
				echo 
					"</div>";
		}
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displaySubComments', $this, $row);
	}
	
	function displayAvatar(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayAvatar', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayAvatar', $this, $row, $handled);
			
			return $handled;
		}
		
		if ($row['UserID'])
			$result = $GLOBALS['USER']->displayAvatar($row['UserID']);
		else
			$result = $GLOBALS['USER']->displayAvatar($row['Email']);
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayAvatar', $this, $row);
		
		return $result;
	}
	
	function displayFormated(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayFormated', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayFormated', $this, $row, $handled);
			
			return $handled;
		}
		
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
		
		if (JCORE_VERSION >= '0.8') {
			$rating = $this->defaultRating+$row['Rating'];
			
			if ($rating > 10)
				$rating = 10;
			
			if ($rating < 1)
				$rating = 1;
			
		} elseif ($row['Rating']) {
			$rating = $row['Rating'];
			
		} else {
			$rating = $this->defaultRating;
		}
		
		if (!$this->latests)
			echo 
				"<a name='comment".$row['ID']."'></a>";
		
		echo
			"<div class='comment-entry comment-rating-".$rating .
				(isset($row['_User']['Admin']) && $row['_User']['Admin']?
					" site-owner":
					null) .
				"'>";
		
		$parts = preg_split('/%([a-z0-9-_]+?)%/', $this->format, null, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach($parts as $part) {
			switch($part) {
				case 'title':
					if ($this->sqlRow) {
						echo
							"<h3 class='comment-title'>";
						
						$owner = sql::fetch(sql::run(
							" SELECT `".$this->sqlOwnerField."` FROM `{" .$this->sqlOwnerTable . "}`" .
							" WHERE `ID` = '".$row[$this->sqlRow]."'"));
						
						echo 
							$owner[$this->sqlOwnerField] .
							"</h3>";
					}
					
					break;
				
				case 'avatar':
					if (JCORE_VERSION >= '0.7') {
						echo
							"<div class='comment-avatar'>";
						
						$this->displayAvatar($row);
						
						echo
							"</div>";
					}
					
					break;
				
				case 'teaser':
					echo
						"<div class='comment-text'>" .
							comments::generateTeaser($row['Comment']) .
						"</div>";
					break;
					
				case 'comment':
					echo
						"<div class='comment-text'>";
					
					$this->displayComment($row);
					
					echo
						"</div>";
					break;
				
				case 'details':
					echo
						"<div class='comment-details comment'>";
					
					$this->displayDetails($row);
					
					echo
						"</div>";
					break;
				
				case 'links':
					echo
						"<div class='comment-functions'>";
					
					if (!$this->latests) {
						$this->displayFunctions($row);
						$this->displayRating($row);
					}
					
					echo
						"</div>";
					break;
				
				case 'link':
					echo $this->getCommentURL($row).'#comment'.$row['ID'];
					break;
				
				default:
					echo $part;
					break;
			}
		}
		
		echo
				"<div class='clear-both'></div>" .
			"</div>";
		
		if (!$this->latests)
			$this->displaySubComments($row);
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayFormated', $this, $row);
	}
	
	function displayOne(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayOne', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayOne', $this, $row, $handled);
			
			return $handled;
		}
		
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
		
		if (JCORE_VERSION >= '0.8') {
			$rating = $this->defaultRating+$row['Rating'];
			
			if ($rating > 10)
				$rating = 10;
			
			if ($rating < 1)
				$rating = 1;
			
		} elseif ($row['Rating']) {
			$rating = $row['Rating'];
			
		} else {
			$rating = $this->defaultRating;
		}
		
		if (!$this->latests)
			echo 
				"<a name='comment".$row['ID']."'></a>";
		
		echo
			"<div class='comment-entry comment-rating-".$rating .
				($this->isPending($row)?
					" pending":
					null) .
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
		
		if (!$this->latests) {
			$this->displayFunctions($row);
			$this->displayRating($row);
		}
		
		echo
				"</div>" .
				"<div class='comment-details comment'>";
		
		$this->displayDetails($row);
		
		echo
				"</div>" .
			"</div>";
		
		if (!$this->latests)
			$this->displaySubComments($row);
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayOne', $this, $row);
	}
	
	function displayTitle() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayTitle', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayTitle', $this, $handled);
			
			return $handled;
		}
		
		echo
			__("Comments") .
			" (".$this->countItems().")";
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayTitle', $this);
	}
	
	function displayForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::displayForm', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::displayForm', $this, $handled);
			
			return $handled;
		}
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'comments::displayForm', $this);
	}
	
	function display() {
		if (!$this->sqlTable) {
			tooltip::display(
				__("Storage table not defined."),
				TOOLTIP_NOTIFICATION);
			
			return;
		}
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'comments::display', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'comments::display', $this, $handled);
			
			return $handled;
		}
		
		$replyto = null;
		$edit = null;
		
		if (isset($_POST['SubCommentOfID']))
			$replyto = (int)$_POST['SubCommentOfID'];
		
		if (isset($_POST['CommentID']))
			$edit = (int)$_POST['CommentID'];
		
		if (!$replyto && isset($_GET['postcomments']) && $_GET['postcomments'] &&
			isset($_GET['reply']) && $_GET['reply'])
			$replyto = (int)$_GET['postcomments'];
		
		if (!$edit && isset($_GET['postcomments']) && $_GET['postcomments'] &&
			isset($_GET['edit']) && $_GET['edit'])
			$edit = (int)$_GET['postcomments'];
		
		if (!$this->ajaxRequest)
			echo 
				"<div class='comments'>";
		
		if (!$this->latests) {
			echo
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
			
			$verifyok = $this->verify($form);
			
			if ($edit) {
				$comment = sql::fetch(sql::run(
					" SELECT * FROM `{".$this->sqlTable."}` " .
					" WHERE `ID` = '".(int)$edit."'"));
				
				if ($verifyok || !$form->submitted())
					$form->setValues($comment);
				
				if ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'] &&
					$this->userPermissionType & USER_PERMISSION_TYPE_WRITE && $this->isPending($comment) &&
					defined('MODERATED_COMMENTS_BY_APPROVAL') && MODERATED_COMMENTS_BY_APPROVAL)
				{
					$form->insert(
						'newcommentsubmit',
						__('Approve'),
						'approve',
						FORM_INPUT_TYPE_CHECKBOX,
						false,
						1,
						FORM_INSERT_BEFORE);
				}
			}
			
			if ($edit && ($verifyok || !$form->submitted()))
				$form->setValues($comment);
			
			$paging = new paging($this->limit);
			
			if ($this->ajaxPaging) {
				$paging->ajax = true;
				$paging->otherArgs = "&amp;request=".$this->uriRequest .
					($this->sqlRow?
						"&amp;".strtolower($this->sqlRow)."=".$this->selectedOwnerID:
						null);
			}
			
			$paging->track(strtolower(get_class($this)).'limit');
		}
		
		$rows = sql::run(
			$this->SQL() .
			($this->latests?
				($this->limit?
					" LIMIT ".$this->limit:
					null):
				" LIMIT ".$paging->limit));
		
		if (!$this->latests) {
			echo 
				"<h3 class='comments-title'>";
			$this->displayTitle();
			echo
				"</h3>";
			
			$paging->setTotalItems($this->countItems(true));
		}
		
		if (!sql::rows($rows)) {
			tooltip::display(
				__("No comments yet."),
				TOOLTIP_NOTIFICATION);
			
		} else {
			echo
				"<div class='comment-entries'>";
			
			while($row = sql::fetch($rows)) {
				if ($this->format)
					$this->displayFormated($row);
				else
					$this->displayOne($row);
			}
			
			echo
				"</div>";
		}
		
		if ($this->showPaging && !$this->latests)
			$paging->display();
		
		if (!$this->latests) {
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
		}
		
		if (!$this->ajaxRequest)
			echo "</div>";
		
		api::callHooks(API_HOOK_AFTER,
			'comments::display', $this, $paging->items);
		
		return $paging->items;
	}
}

?>