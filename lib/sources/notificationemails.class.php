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
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{notificationemails}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdminForm(&$form) {
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
			FORM_INPUT_TYPE_TEXTAREA);
		$form->setStyle('width: ' .
			(JCORE_VERSION >= '0.7'?
				'90%':
				'350px') .
			'; height: 200px;');
		$form->setValueType(FORM_VALUE_TYPE_HTML);
	}
	
	function verifyAdmin(&$form) {
		$reset = null;
		$resetall = null;
		$edit = null;
		$id = null;
		$ids = null;
		
		if (isset($_POST['resetsubmit']))
			$reset = $_POST['resetsubmit'];
		
		if (isset($_POST['resetallsubmit']))
			$resetall = $_POST['resetallsubmit'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
		if ($reset && $ids && count($ids)) {
			foreach($ids as $id)
				$this->reset($id);
			
			tooltip::display(
				__("Emails have been successfully reset."),
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if ($resetall) {
			if (!$this->reset())
				return false;
			
			tooltip::display(
				__("Emails have been successfully reset."),
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if (!$form->verify())
			return false;
			
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray()))
				return false;
				
			tooltip::display(
				__("Email has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
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
			"</th>" .
			"<th><span class='nowrap'>".
				__("Email ID / Subject")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
	}
	
	function displayAdminListHeaderFunctions() {
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
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
					($this->userPermissionType != USER_PERMISSION_TYPE_WRITE?
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
	}
	
	function displayAdminListItemOptions(&$row) {
	}
	
	function displayAdminListItemFunctions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>";
	}
	
	function displayAdminListItemSelected(&$row) {
		admin::displayItemData(
			__("Subject"),
			$row['Subject']);
		
		admin::displayItemData(
			"<hr />");
		admin::displayItemData(
			nl2br(htmlspecialchars($row['Body'])));
	}
	
	function displayAdminListFunctions() {
		echo
			"<input type='submit' name='resetsubmit' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES) .
				"' class='button confirm-link' /> " .
			"<input type='submit' name='resetallsubmit' value='" .
				htmlspecialchars(__("Reset All"), ENT_QUOTES) .
				"' class='button confirm-link' /> ";
	}
	
	function displayAdminList(&$rows) {
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		echo
			"<form action='".
				url::uri('id, edit')."' method='post'>";
		
		echo
			"<table cellpadding='0' cellspacing='0' class='list'>" .
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
			__('Notification Emails Administration'),
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
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			$verifyok = $this->verifyAdmin($form);
		}
		
		$paging = new paging(20);
		$paging->ignoreArgs = 'id, edit, delete';
		
		$rows = sql::run(
			" SELECT * FROM `{notificationemails}`" .
			" WHERE 1" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
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
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{notificationemails}`" .
					" WHERE `ID` = '".$id."'"));
			
				$form->setValues($row);
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
		
		$newid = sql::run(
			" INSERT INTO `{notificationemails}` SET ".
			" `EmailID` = '".
				sql::escape($values['EmailID'])."'," .
			" `Subject` = '".
				sql::escape($values['Subject'])."'," .
			" `Body` = '".
				sql::escape($values['Body'])."'");
			
		if (!$newid) {
			tooltip::display(
				sprintf(__("Email couldn't be added! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
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
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Email couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		sql::run(
			" DELETE FROM `{notificationemails}` " .
			" WHERE `ID` = '".$id."'");
		
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
			
			if (!isset(email::$templates[$row['EmailID']]))
				return $this->delete($id);
			
			return $this->edit($id, array(
					'Subject' => email::$templates[$row['EmailID']]['Subject'],
					'Body' => email::$templates[$row['EmailID']]['Body']));
		}
		
		$rows = sql::run(
			" SELECT * FROM `{notificationemails}`");
		
		while($row = sql::fetch($rows)) {
			if (!isset(email::$templates[$row['EmailID']]))
				return $this->delete($row['ID']);
			
			if (!$this->edit($row['ID'], array(
					'Subject' => email::$templates[$row['EmailID']]['Subject'],
					'Body' => email::$templates[$row['EmailID']]['Body'])))
				return false;
		}
		
		return true;
	}
	
	function refresh() {
		foreach(email::$templates as $emailid => $email)
			if (isset($email['Save']) && $email['Save'])
				$this->add(array(
					'EmailID' => $emailid,
					'Subject' => $email['Subject'],
					'Body' => $email['Body']),
					true);
	}
	
	// ************************************************   Client Part
	static function get($id) {
		if (!$id)
			return null;
		
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{notificationemails}`" .
			" WHERE `EmailID` = '".sql::escape($id)."'"));
		
		if (!$row)
			return null;
		
		return $row;
	}
}

?>