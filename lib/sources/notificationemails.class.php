<?php

/***************************************************************************
 *            notificationemails.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
class _notificationEmails {
	var $adminPath = 'admin/site/notificationemails';
	
	// ************************************************   Admin Part
	function countAdminItems() {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::countAdminItems', $this);
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{notificationemails}`" .
			" LIMIT 1"));
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::countAdminItems', $this, $row['Rows']);
		
		return $row['Rows'];
	}
	
	function setupAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::setupAdminForm', $this, $form);
		
		$form->add(
			__('Email ID'),
			'EmailID',
			FORM_INPUT_TYPE_REVIEW,
			true);
		$form->setStyle('width: 200px;');
		$form->setValueType(FORM_VALUE_TYPE_LIMITED_STRING);
		$form->setTooltipText(__("e.g. WebmasterWarning (without spaces)"));
		
		$form->add(
			__('Subject'),
			'Subject',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 300px;');
		
		$form->add(
			__('Body'),
			'Body',
			(defined('HTML_EMAILS') && HTML_EMAILS?
				FORM_INPUT_TYPE_EDITOR:
				FORM_INPUT_TYPE_TEXTAREA));
		$form->setStyle('width: ' .
			(JCORE_VERSION >= '0.7'?
				'90%':
				'350px') .
			'; height: 200px;');
		$form->setValueType(FORM_VALUE_TYPE_HTML);
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::setupAdminForm', $this, $form);
	}
	
	function verifyAdmin(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::verifyAdmin', $this, $form);
		
		$reset = null;
		$resetall = null;
		$edit = null;
		$id = null;
		$ids = null;
		
		if (isset($_POST['resetsubmit']))
			$reset = (string)$_POST['resetsubmit'];
		
		if (isset($_POST['resetallsubmit']))
			$resetall = (string)$_POST['resetallsubmit'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
		if ($reset && $ids && count($ids)) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'notificationEmails::verifyAdmin', $this, $form);
				return false;
			}
			
			foreach($ids as $id)
				$this->reset((int)$id);
			
			tooltip::display(
				__("Emails have been successfully reset."),
				TOOLTIP_SUCCESS);
				
			api::callHooks(API_HOOK_AFTER,
				'notificationEmails::verifyAdmin', $this, $form, $reset);
			
			return true;
		}
		
		if ($resetall) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'notificationEmails::verifyAdmin', $this, $form);
				return false;
			}
			
			$result = $this->reset();
			
			if ($result)
				tooltip::display(
					__("Emails have been successfully reset."),
					TOOLTIP_SUCCESS);
				
			api::callHooks(API_HOOK_AFTER,
				'notificationEmails::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'notificationEmails::verifyAdmin', $this, $form);
			
			return false;
		}
			
		if ($edit) {
			$result = $this->edit($id, $form->getPostArray());
			
			if ($result)
				tooltip::display(
					__("Email has been successfully updated.")." " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'notificationEmails::verifyAdmin', $this, $form, $reset);
			
			return $result;
		}
		
		$form->reset();
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::verifyAdmin', $this, $form);
		
		return true;
	}
	
	function displayAdminListHeader() {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::displayAdminListHeader', $this);
		
		echo
			"<th>" .
				"<input type='checkbox' class='checkbox-all' " .
				(~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE?
					"disabled='disabled' ":
					null) .
				"/>" .
			"</th>" .
			"<th><span class='nowrap'>".
				__("Email ID / Subject")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::displayAdminListHeader', $this);
	}
	
	function displayAdminListHeaderOptions() {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::displayAdminListHeaderOptions', $this);
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::displayAdminListHeaderFunctions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::displayAdminListItem', $this, $row);
		
		$ids = null;
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
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
			"<td class='auto-width bold'>" .
				"<a href='".
					url::uri('id, edit') .
					"&amp;id=".$row['ID']."'>" .
				preg_replace('/([A-Z])/', ' \1', $row['EmailID']) .
				"</a>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					$row['Subject'] .
				"</div>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListItemOptions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::displayAdminListItemOptions', $this, $row);
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::displayAdminListItemFunctions', $this, $row);
		
		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminListItemSelected(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::displayAdminListItemSelected', $this, $row);
		
		admin::displayItemData(
			__("Subject"),
			$row['Subject']);
		
		admin::displayItemData(
			"<hr />");
		
		if (defined('HTML_EMAILS') && HTML_EMAILS) {
			if (!preg_match('/<[a-zA-Z]>/', $row['Body']))
				$row['Body'] = form::text2HTML($row['Body']);
			
			admin::displayItemData($row['Body']);
			
		} else {
			admin::displayItemData(
				nl2br(url::parseLinks(htmlspecialchars($row['Body']))));
		}
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::displayAdminListItemSelected', $this, $row);
	}
	
	function displayAdminListFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::displayAdminListFunctions', $this);
		
		echo
			"<input type='submit' name='resetsubmit' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES) .
				"' class='button confirm-link' /> " .
			"<input type='submit' name='resetallsubmit' value='" .
				htmlspecialchars(__("Reset All"), ENT_QUOTES) .
				"' class='button confirm-link' /> ";
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::displayAdminListFunctions', $this);
	}
	
	function displayAdminList(&$rows) {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::displayAdminList', $this, $rows);
		
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		echo
			"<form action='".
				url::uri('id, edit')."' method='post'>" .
				"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";
		
		echo
			"<table cellpadding='0' cellspacing='0' class='list'>" .
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
			
			if ($row['ID'] == $id) {
				echo
					"<tr".($i%2?" class='pair'":null).">" .
						"<td colspan='10' class='auto-width'>" .
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
			'notificationEmails::displayAdminList', $this, $rows);
	}
	
	function displayAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::displayAdminForm', $this, $form);
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::displayAdminTitle', $this, $ownertitle);
		
		admin::displayTitle(
			__('Notification Emails Administration'),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::displayAdminDescription', $this);
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::displayAdmin', $this);
		
		$edit = null;
		$id = null;
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		$this->refresh();
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
				
		$form = new form(
				($edit?
					__("Edit Email"):
					__("New Email")),
				'neweditemail');
		
		if (!$edit)
			$form->action = url::uri('id, limit');
					
		$this->setupAdminForm($form);
		$form->addSubmitButtons();
		
		if ($edit) {
			$form->add(
				__('Cancel'),
				'cancel',
				 FORM_INPUT_TYPE_BUTTON);
			$form->addAttributes("onclick=\"window.location='".
				str_replace('&amp;', '&', url::uri('id, edit'))."'\"");
		}
		
		$verifyok = false;
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$verifyok = $this->verifyAdmin($form);
		
		$paging = new paging(20);
		$paging->ignoreArgs = 'id, edit, delete';
		
		$rows = sql::run(
			" SELECT * FROM `{notificationemails}`" .
			" ORDER BY `EmailID`, `ID`" .
			" LIMIT ".$paging->limit);
				
		$paging->setTotalItems(sql::count());
		
		if ($paging->items)
			$this->displayAdminList($rows);
		else
			tooltip::display(
					__("No emails found."),
					TOOLTIP_NOTIFICATION);
		
		$paging->display();
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{notificationemails}`" .
					" WHERE `ID` = '".$id."'"));
				
				if (defined('HTML_EMAILS') && HTML_EMAILS &&
					!preg_match('/<[a-zA-Z]>/', $selected['Body']))
					$selected['Body'] = form::text2HTML($selected['Body']);
				
				$form->setValues($selected);
			}
			
			if ($edit) {
				echo
					"<a name='adminform'></a>";
				
				$this->displayAdminForm($form);
			}
		}
		
		unset($form);
		
		echo 
			"</div>";	//admin-content
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::displayAdmin', $this);
	}
	
	function add($values, $quiet = false) {
		if (!is_array($values))
			return false;
		
		if ($this->get($values['EmailID'])) {
			if (!$quiet)
				tooltip::display(
					__("An email with this id already exists!"),
					TOOLTIP_ERROR);
			
			return false;
		}
		
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::add', $this, $values);
		
		$newid = sql::run(
			" INSERT INTO `{notificationemails}` SET ".
			" `EmailID` = '".
				sql::escape($values['EmailID'])."'," .
			" `Subject` = '".
				sql::escape($values['Subject'])."'," .
			" `Body` = '".
				sql::escape($values['Body'])."'");
			
		if (!$newid)
			tooltip::display(
				sprintf(__("Email couldn't be added! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::add', $this, $values, $newid);
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::edit', $this, $id, $values);
		
		sql::run(
			" UPDATE `{notificationemails}` SET ".
			(isset($values['EmailID']) && $values['EmailID']?
				" `EmailID` = '".
					sql::escape($values['EmailID'])."',":
				null) .
			" `Subject` = '".
				sql::escape($values['Subject'])."'," .
			" `Body` = '".
				sql::escape($values['Body'])."'" .
			" WHERE `ID` = '".(int)$id."'");
			
		$result = (sql::affected() != -1);
		
		if (!$result)
			tooltip::display(
				sprintf(__("Email couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::edit', $this, $id, $values, $result);
		
		return $result;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::delete', $this, $id);
		
		sql::run(
			" DELETE FROM `{notificationemails}` " .
			" WHERE `ID` = '".$id."'");
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::delete', $this, $id);
		
		return true;
	}
	
	function reset($id = null) {
		if ((int)$id) {
			$row = sql::fetch(sql::run(
				" SELECT * FROM `{notificationemails}`" .
				" WHERE `ID` = '".$id."'"));
			
			if (!$row) {
				tooltip::display(
					__("The specified email cannot be found!"),
					TOOLTIP_ERROR);
				return false;
			}
			
			api::callHooks(API_HOOK_BEFORE,
				'notificationEmails::reset', $this, $id);
			
			if (!isset(email::$templates[$row['EmailID']]))
				$result = $this->delete($id);
			else
				$result = $this->edit($id, array(
					'Subject' => email::$templates[$row['EmailID']]['Subject'],
					'Body' => email::$templates[$row['EmailID']]['Body']));
			
			api::callHooks(API_HOOK_AFTER,
				'notificationEmails::reset', $this, $id, $result);
			
			return $result;
		}
		
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::reset', $this, $id);
		
		$rows = sql::run(
			" SELECT * FROM `{notificationemails}`");
		
		$result = true;
		while($row = sql::fetch($rows)) {
			if (!isset(email::$templates[$row['EmailID']])) {
				if (!$this->delete($row['ID'])) {
					$result = false;
					break;
				}
				
				continue;
			}
			
			if (!$this->edit($row['ID'], array(
					'Subject' => email::$templates[$row['EmailID']]['Subject'],
					'Body' => email::$templates[$row['EmailID']]['Body'])))
			{
				$result = false;
				break;
			}
		}
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::reset', $this, $id, $result);
		
		return $result;
	}
	
	function refresh() {
		api::callHooks(API_HOOK_BEFORE,
			'notificationEmails::refresh', $this);
		
		foreach(email::$templates as $emailid => $email)
			if (isset($email['Save']) && $email['Save'])
				$this->add(array(
					'EmailID' => $emailid,
					'Subject' => $email['Subject'],
					'Body' => $email['Body']),
					true);
		
		api::callHooks(API_HOOK_AFTER,
			'notificationEmails::refresh', $this);
	}
	
	// ************************************************   Client Part
	static function get($id) {
		if (!$id)
			return null;
		
		return sql::fetch(sql::run(
			" SELECT * FROM `{notificationemails}`" .
			" WHERE `EmailID` = '".sql::escape($id)."'"));
	}
}

?>