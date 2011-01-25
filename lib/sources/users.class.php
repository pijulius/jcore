<?php

/***************************************************************************
 *            users.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/

define('REQUEST_TYPE_NEW_ACCOUNT', 1);
define('REQUEST_TYPE_NEW_PASSWORD', 2);
 
include_once('lib/email.class.php');
include_once('lib/security.class.php');
include_once('lib/bfprotection.class.php');
include_once('lib/ptprotection.class.php');

email::add('UserRegistration', 
		"Welcome to %PAGE_TITLE%",
		"Welcome %USERNAME%,\n\n" .
		"You have completed the registration of a new user account at \"" .
		"%PAGE_TITLE%\".\n\n" .
		"Your login information are:\n" .
		"Username: %USERNAME%\n" .
		"Password: %PASSWORD%\n\n" .
		"To login to your account please click here:\n" .
		"%SITE_URL%\n\n" .
		"Sincerely,\n" .
		"%PAGE_TITLE%");
			
email::add('RequestPassword',
		"Password Request for %PAGE_TITLE%",
		"Dear %USERNAME%,\n\n" .
		"To complete your request and have a new password sent to " .
			"you for \"%PAGE_TITLE%\" please click on the link below:\n" .
		"%REQUESTURL%\n\n" .
		"Sincerely,\n" .
		"%PAGE_TITLE%",
		false);
		
email::add('NewPassword',
		"Login information for %PAGE_TITLE%",
		"Dear %USERNAME%,\n\n" .
		"Please see below the new password you requested for \"".
			"%PAGE_TITLE%\".\n\n" .
		"Username: %USERNAME%\n" .
		"Password: %NEWPASSWORD%\n\n" .
		"To login to your account please click here:\n" .
		"%SITE_URL%\n\n" .
		"Sincerely,\n" .
		"%PAGE_TITLE%",
		false);
		
email::add('NewAccountActivation',
		"Welcome to %PAGE_TITLE%",
		"Dear %USERNAME%,\n\n" .
		"Please keep this e-mail for your records. Your account " .
		"information is as follows.\n\n" .
		"----------------------------\n" .
		"Username: %USERNAME%\n" .
		"Password: %PASSWORD%\n" .
		"----------------------------\n\n" .
		"Please visit the following link in order to activate your account:\n" .
		"%SITE_URL%index.php?request=users&requestid=%REQUESTID%\n\n" .
		"Your password has been securely stored in our database and " .
		"cannot be retrieved. In the event that it is forgotten, you will " .
		"be able to reset it using the email address associated with " .
		"your account.\n\n" .
		"Thank you for registering.\n" .
		"%PAGE_TITLE%");
		
email::add('NewPasswordActivation',
		"New password activation for %PAGE_TITLE%",
		"Dear %USERNAME%,\n\n" .
		"You are receiving this notification because you have " .
		"(or someone pretending to be you has) requested a new " .
		"password be sent for your account on \"%PAGE_TITLE%\". " .
		"If you did not request this notification then please ignore " .
		"it, if you keep receiving it please contact the site administrator.\n\n" .
		"To use the new password you need to activate it. To do " .
		"this click the link provided below.\n\n" .
		"%REQUESTURL%\n\n" .
		"If successful you will be able to login using the following " .
		"username / password:\n\n" .
		"Username: %USERNAME%\n" .
		"Password: %NEWPASSWORD%\n\n" .
		"You can of course change this password yourself via your " .
		"account page. If you have any difficulties please contact " .
		"the site administrator.\n\n" .
		"Sincerely,\n" .
		"%PAGE_TITLE%");
		
class _users {
	var $loginok = null;
	var $data = null;
	var $logedInNow = null;
	var $result = null;
	var $verifyError = 0;
	var $ajaxRequest = null;
	var $adminPath = 'admin/members/users';
	
	// ************************************************   Admin Part
	function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{users}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New User'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Mass Email'), 
			'?path=admin/members/massemail');
		favoriteLinks::add(
			__('Settings'), 
			'?path=admin/site/settings');
	}
	
	function setupAdminForm(&$form, $membersModuleAvailable = false) {
		$edit = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if ($membersModuleAvailable) {
			if ($edit) {
				$accountform = new memberAccountForm();
				$accountform->load(false);
				
				foreach($accountform->elements as $element)
					$form->elements[] = $element;
				
				unset($accountform);
				
			} else {
				$registrationform = new memberRegistrationForm();
				$registrationform->load(false);
				
				foreach($registrationform->elements as $element) {
					if ($element['Type'] != FORM_INPUT_TYPE_VERIFICATION_CODE)
						$form->elements[] = $element;
				}
				
				unset($registrationform);
			}
		
			$form->add(
				__('Additional Options'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
			
			$form->add(
				__('Suspended'),
				'Suspended',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
				
			$form->add(
				__('Administrator'),
				'Admin',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
				
			if (JCORE_VERSION >= '0.5') {	
				$form->add(
					__('Skip IP Check'),
					'SkipIPCheck',
					FORM_INPUT_TYPE_CHECKBOX,
					false,
					'1');
				$form->setValueType(FORM_VALUE_TYPE_BOOL);
				$form->addAdditionalText(
					__("(don't bind user to IP)"));
			}
				
			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER);
				
		} else {
			$form->add(
				__('Username'),
				'UserName',
				FORM_INPUT_TYPE_TEXT,
				true);
			$form->setStyle('width: 200px;');
			$form->setValueType(FORM_VALUE_TYPE_LIMITED_STRING);
			
			$form->add(
				__('Email'),
				'Email',
				FORM_INPUT_TYPE_EMAIL,
				true);
			$form->setStyle('width: 300px;');
						
			if ($edit)
				$form->add(
					__('Change Password'),
					null,
					FORM_OPEN_FRAME_CONTAINER);
			
			if ($edit || (!$edit && JCORE_VERSION >= '0.6')) {
				$form->add(
					__('Password'),
					'Password',
					FORM_INPUT_TYPE_PASSWORD,
					false);
				$form->setStyle('width: 150px;');
				
				$form->add(
					__('Confirm password'),
					'ConfirmPassword',
					FORM_INPUT_TYPE_CONFIRM,
					false);
				$form->setStyle('width: 150px;');
			}
			
			if (!$edit && JCORE_VERSION >= '0.6')
				$form->add(
					__("Please note that you will need to enter a " .
						"valid e-mail address before your account is " .
						"activated. You will receive an e-mail at the " .
						"address you provided that contains an account " .
						"activation link."),
					'',
					FORM_STATIC_TEXT);
			
			if ($edit)
				$form->add(
					null,
					null,
					FORM_CLOSE_FRAME_CONTAINER);
			
			$form->add(
				__('Additional Options'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
			
			$form->add(
				__('Website'),
				'Website',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 350px;');
			
			if (JCORE_VERSION >= '0.7') {	
				$form->add(
					__('Show Avatar'),
					'ShowAvatar',
					FORM_INPUT_TYPE_CHECKBOX,
					false,
					'1');
				$form->setValueType(FORM_VALUE_TYPE_BOOL);
				$form->addAdditionalText(
					"(<a href='http://gravatar.com' target='_blank'>" .
						__("Change Avatar")."</a>)");
			}
			
			$form->add(
				__('Stay Logged In'),
				'StayLoggedIn',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
				
			$form->add(
				__('Suspended'),
				'Suspended',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
				
			$form->add(
				__('Administrator'),
				'Admin',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
			if (JCORE_VERSION >= '0.5') {	
				$form->add(
					__('Skip IP Check'),
					'SkipIPCheck',
					FORM_INPUT_TYPE_CHECKBOX,
					false,
					'1');
				$form->setValueType(FORM_VALUE_TYPE_BOOL);
				$form->addAdditionalText(
					__("(don't bind user to IP)"));
			}
				
			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER);
		}
		
		$form->setTooltipText('Password', 
			sprintf(__("minimum %s characters"), MINIMUM_PASSWORD_LENGTH));
	}
	
	function verifyAdmin(&$form) {
		$activate = null;
		$suspend = null;
		$delete = null;
		$edit = null;
		$id = null;
		$ids = null;
		
		if (isset($_POST['activatesubmit']))
			$activate = $_POST['activatesubmit'];
		
		if (isset($_POST['suspendsubmit']))
			$suspend = $_POST['suspendsubmit'];
		
		if (isset($_POST['deletesubmit']))
			$delete = $_POST['deletesubmit'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
		if (!$id && !$ids && 
			($activate || $suspend || $delete))
		{
			tooltip::display(
				__("No user selected! Please select at " .
					"least one user."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if ($ids && count($ids)) {
			if ($activate) {
				foreach($ids as $id)
					$this->activate($id);
				
				tooltip::display(
					__("Users have been successfully activated and are now " .
						"able to login."),
					TOOLTIP_SUCCESS);
					
				return true;
			}
			
			if ($suspend) {
				foreach($ids as $id)
					$this->suspend($id);
				
				tooltip::display(
					__("Users have been successfully suspended."),
					TOOLTIP_SUCCESS);
					
				return true;
			}
			
			if ($delete) {
				foreach($ids as $id)
					$this->delete($id);
				
				tooltip::display(
					__("Users have been successfully deleted."),
					TOOLTIP_SUCCESS);
					
				return true;
			}
		}
			
		if ($delete) {
			if (!$this->delete($id))
				return false;
		
			tooltip::display(
				__("User has been successfully deleted."),
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if ($edit) {
			$form->setValue('RePassword', $form->get('Password'));
			
			if (!$this->edit($id, $form->getPostArray()))
				return false;
			
			tooltip::display(
				__("User has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
				
			return true;
		}
			
		if (!$newid = $this->add($form->getPostArray())) 
			return false;
		
		tooltip::display(
			__("User has been successfully created and a notification email " .
				"with the login information has been sent to the email " .
				"address specified.")." ".
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
			"</th>" .
			"<th><span class='nowrap'>".
				__("Username / Registered on")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Admin")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Email")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
		echo
			"<th><span class='nowrap'>".
				__("Permissions")."</span></th>";
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
			"<td class='auto-width'>" .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."' " .
					"class='bold'>".
					$row['UserName'] .
				"</a>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					calendar::dateTime($row['TimeStamp']) .
				"</span>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				($row['Admin']?
					__('Yes'):
					null).
			"</td>" .
			"<td style='text-align: right;'>" .
				"<a href='mailto:".$row['Email']."'>".
					$row['Email'] .
				"</a>" .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
		$permissions = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{userpermissions}`" .
			" WHERE `UserID` = '".$row['ID']."'" .
			" LIMIT 1"));
			
		echo
			"<td align='center'>" .
				"<a class='admin-link permissions' " .
					"title='".htmlspecialchars(__("Permissions"), ENT_QUOTES) .
					" (".$permissions['Rows'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/userpermissions'>" .
					(ADMIN_ITEMS_COUNTER_ENABLED && $permissions['Rows']?
						"<span class='counter'>" .
							"<span>" .
								"<span>" .
								$permissions['Rows']."" .
								"</span>" .
							"</span>" .
						"</span>":
						null) .
				"</a>" .
			"</td>";
	}
	
	function displayAdminListItemFunctions(&$row) {
		echo
			"<td>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='" .url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayAdminListItemSelected(&$row, $membersModuleAvailable = false) {
		if (JCORE_VERSION >= '0.7') {
			echo
				"<div class='align-right'>";
			
			$this->displayAvatar($row['ID']);
			
			echo
				"</div>";
		}
		
		if ($membersModuleAvailable) {
			$accountform = new memberAccountForm();
			$accountform->load(false);
			
			$accountform->displayData($row, array(
				'LastVisitTimeStamp', 'IP', 'Suspended', 'SkipIPCheck'));
			unset($accountform);
		
		} else {
			if ($row['Website'])
				admin::displayItemData(
					__("Website"),
					"<a href='".
						$row['Website']."' target='_blank'>".
						$row['Website'] .
					"</a>");
			
			if (JCORE_VERSION >= '0.7' && $row['ShowAvatar'])
				admin::displayItemData(
					__("Show Avatar"),
					__("Yes"));
			
			if ($row['StayLoggedIn'])
				admin::displayItemData(
					__("Stay Logged In"),
					__("Yes"));
		}
						
		if ($row['LastVisitTimeStamp'])
			admin::displayItemData(
				__("Last visit"),
				calendar::dateTime($row['LastVisitTimeStamp']));
		
		if ($row['IP'])
			admin::displayItemData(
				__("From IP"),
				long2ip($row['IP']));
		
		if ($row['Suspended'])
			admin::displayItemData(
				__("Suspended"),
				__("Yes"));
		
		if (isset($row['SkipIPCheck']) && $row['SkipIPCheck'])
			admin::displayItemData(
				__("Skip IP Check"),
				__("Yes"));
		
		if (!$row['Password'])
			admin::displayItemData(
				__("Account Pending"),
				__("Yes"));
	}
	
	function displayAdminListSearch() {
		$search = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		echo
			"<input type='hidden' name='path' value='".admin::path()."' />" .
			"<input type='search' name='search' value='".
				htmlspecialchars($search, ENT_QUOTES).
				"' results='5' placeholder='".htmlspecialchars(__("search..."), ENT_QUOTES)."' /> " .
			"<input type='submit' value='" .
				htmlspecialchars(__("Search"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminListFunctions() {
		echo
			"<input type='submit' name='activatesubmit' value='" .
				htmlspecialchars(__("Activate"), ENT_QUOTES) .
				"' class='button' /> " .
			"<input type='submit' name='suspendsubmit' value='" .
				htmlspecialchars(__("Suspend"), ENT_QUOTES) .
				"' class='button confirm-link' /> " .
			"<input type='submit' name='deletesubmit' value='" .
				htmlspecialchars(__("Delete"), ENT_QUOTES) .
				"' class='button confirm-link' /> ";
	}
	
	function displayAdminList(&$rows, $membersModuleAvailable = false) {
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
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
				
			if ($row['ID'] == $id) {
				echo 
					"<tr".($i%2?" class='pair'":NULL).">" .
						"<td class='auto-width' colspan='10'>" .
							"<div class='admin-content-preview'>";
				
				$this->displayAdminListItemSelected($row, $membersModuleAvailable);
				
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
			__('Users Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
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
		
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		$membersModuleAvailable = modules::installed('Members');
		
		echo
			"<div class='admin-content'>";
				
		$form = new form(
				($edit?
					__("Edit User"):
					__("New User")),
				'newedituser');
		
		if (!$edit)
			$form->action = url::uri('id, delete, limit');
		
		$this->setupAdminForm($form, $membersModuleAvailable);
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
		
		$paging = new paging(20);
		$paging->ignoreArgs = 'id, edit, delete';
		
		$rows = sql::run(
				" SELECT * FROM `{users}`" .
				" WHERE 1" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				($search?
					sql::search(
						$search,
						array('UserName', 'Email')):
					null) .
				" ORDER BY `Admin` DESC, `ID` DESC" .
				" LIMIT ".$paging->limit);
				
		$paging->setTotalItems(sql::count());
		
		if ($paging->items)
			$this->displayAdminList($rows, $membersModuleAvailable);
		else
			tooltip::display(
				__("No users found."),
				TOOLTIP_NOTIFICATION);
		
		$paging->display();
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{users}`" .
					" WHERE `ID` = '".$id."'"));
				
				$form->setValues($row);
			}
			
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form);
			
		echo 
			"</div>";	//admin-content
	}
	
	function add($values) {
		if ((!$GLOBALS['USER']->loginok || !$GLOBALS['USER']->data['Admin']) &&
			defined('REGISTRATIONS_SUSPENDED') && REGISTRATIONS_SUSPENDED) 
		{
			tooltip::display(
				__("New account registration has been temporarily " .
					"suspended. Please try again later."),
				TOOLTIP_ERROR);
		
			return false;
		}
		
		if (!is_array($values)) {
			tooltip::display(
				__("No username specified!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!$values['UserName'] || !$values['Email']) {
			tooltip::display(
				__("Username and Email are required!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!email::verify($values['Email'])) {
			tooltip::display(
				__("Invalid email address. Please make sure you enter " .
					"a valid email address."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!$this->checkUsername($values['UserName']))
			return false;
		
		if ((!$GLOBALS['USER']->loginok || !$GLOBALS['USER']->data['Admin']) &&
			!$this->checkEmail($values['Email']))
			return false;
		
		if (isset($values['Password']) && $values['Password'])
			$password = $values['Password'];
		else	
			$password = security::genPassword($values['Email']);
		
		$skipfields = array(
			'UserName', 
			'Password', 
			'Email');
		
		if (!$GLOBALS['USER']->loginok || !$GLOBALS['USER']->data['Admin'])
			$skipfields = array_merge($skipfields, 
				array(
					'Admin', 
					'Suspended'));
			
		$query = 
			" `UserName` = '".sql::escape($values['UserName'])."'," .
			(JCORE_VERSION < '0.6' || 
			 ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'])?
				" `Password` = '".sql::escape(security::text2Hash($password))."',":
				null) .
			" `Email` = '".sql::escape($values['Email'])."',";
		
		$rows = sql::run(
			" SHOW COLUMNS FROM `{users}`");
		
		$fields = array();
		while($row = sql::fetch($rows))
			if (!in_array($row['Field'], $skipfields))
				$fields[] = $row['Field'];
		
		foreach($values as $field => $value)
			if (in_array($field, $fields))
				$query .= " `".$field."` = '".
					sql::escape($value)."',";
		
		$newid = sql::run(
			" INSERT INTO `{users}` SET" .
			$query .
			" `TimeStamp` = NOW()");
			
		if (!$newid) {
			tooltip::display(
				sprintf(__("User couldn't be registered! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (JCORE_VERSION >= '0.6' && (!$GLOBALS['USER']->loginok ||
			!$GLOBALS['USER']->data['Admin']))
		{
			$email = new email();
			$email->load('NewAccountActivation');
			
			$requestid = $this->addRequest(array(
				'UserID' => $newid,
				'RequestTypeID' => REQUEST_TYPE_NEW_ACCOUNT,
				'Data' => security::text2Hash($password)));
			
			if (!$requestid)
				return $requestid;
				
			$email->toUserID = $newid;
			$email->variables['RequestID'] = $requestid;
			$email->variables['Password'] = $password;
			
			$email->send();
			unset($email);
			
		} else {
			$email = new email();
			$email->load('UserRegistration');
			$email->toUserID = $newid;
			$email->variables['Password'] = $password;
			$email->send();
			unset($email);
		}
			
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)			
			return false;
		
		if (!is_array($values)) {
			tooltip::display(
				__("No data defined to update user to!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!$values['UserName'] || !$values['Email']) {
			tooltip::display(
				__("Username and Email are required!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!email::verify($values['Email'])) {
			tooltip::display(
				__("Invalid email address. Please make sure you enter " .
					"a valid email address."),
				TOOLTIP_ERROR);
					
			return false;
		}
		
		if(preg_match('/[^a-zA-Z0-9\@\.\_\-]/', $values['UserName'])) {
			tooltip::display(
				__("Incorrect username. Usernames may consist of a-z, 0-9 and " .
					"underscores only."),
				TOOLTIP_ERROR);
			
			return false;
		}
			
		if (!$this->checkUsername($values['UserName'], $id))
			return false;
		
		if ((!$GLOBALS['USER']->loginok || !$GLOBALS['USER']->data['Admin']) &&
			!$this->checkEmail($values['Email'], $id))
			return false;
		
		$query = 
			" `UserName` = '".
				sql::escape($values['UserName'])."'," .
			($values['Password']?
				" `Password` = '".
					sql::escape(security::text2Hash($values['Password']))."',":
				null) .
			" `Email` = '".
				sql::escape($values['Email'])."',";
		
		$rows = sql::run(
			" SHOW COLUMNS FROM `{users}`");
		
		$fields = array();
		$skipfields = array(
			'UserName', 
			'Password', 
			'Email');
		
		if (!$GLOBALS['USER']->loginok || !$GLOBALS['USER']->data['Admin'])
			$skipfields = array_merge($skipfields, 
				array(
					'Admin', 
					'Suspended'));
			
		while($row = sql::fetch($rows))
			if (!in_array($row['Field'], $skipfields))
				$fields[] = $row['Field'];
		
		foreach($values as $field => $value)
			if (in_array($field, $fields))
				$query .= " `".$field."` = '".
					sql::escape($value)."',";
		
		sql::run(
			" UPDATE `{users}` SET" .
			$query .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("User couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		$this->refresh();
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
			
		sql::run(
			" DELETE FROM `{users}`" .
			" WHERE `ID` = '".(int)$id."'");
			
		sql::run(
			" DELETE FROM `{userlogins}`" .
			" WHERE `UserID` = '".(int)$id."'");
			
		return true;
	}
	
	// ************************************************   Client Part
	function total() {
		return sql::count(
			" SELECT COUNT(*) AS `Rows` " .
			" FROM `{users}`");
	}
	
	function get($id = null, $fields = "*") {
		if (!isset($id) || (int)$id == $this->data['ID'])
			return $this->data;
		
		return sql::fetch(sql::run(
			" SELECT ".$fields." FROM `{users}`" .
			" WHERE `ID` = '".(int)$id."'"));
	}
	
	function refresh() {
		if (!$this->data['ID'])
			return false;
			
		$this->data = sql::fetch(sql::run(
			" SELECT * FROM `{users}`" .
			" WHERE `ID` = '".$this->data['ID']."'"));
			
		return true;
	}
	
	function kickOut($id) {
		sql::run(
			" DELETE FROM `{userlogins}` " .
			" WHERE `UserID` = '".(int)$id."'");
	}
	
	function reset() {
		if (isset($_COOKIE['memberloginid']))
			sql::run(
				" DELETE FROM `{userlogins}`" .
				" WHERE BINARY `SessionID` = '".$_COOKIE['memberloginid']."'");
		
		$this->loginok = null;
		$this->data = null;

		$cookiedomain = url::rootDomain();
		if (!strstr($cookiedomain, '.'))
			$cookiedomain = false;
		
		do {
			setcookie("memberloginid", '', time() - 3600, '/', $cookiedomain);
			$cookiedomain = preg_replace('/^.*?\./', '', $cookiedomain);
		} while (strstr($cookiedomain, '.'));
		
		unset($_COOKIE['memberloginid']);
	}
	
	function check() {
		$logout = null;
		$rememberme = null;
		$member = null;
		$password = null;
		
		if (isset($_GET['memberlogout']))
			$logout = (int)$_GET['memberlogout'];
			
		if (isset($_GET['logout']))
			$logout = (int)$_GET['logout'];
			
		if (isset($_POST['rememberme']))
			$rememberme = (int)$_POST['rememberme'];
			
		if (isset($_POST['member']))
			$member = trim($_POST['member']);
			
		if (isset($_POST['password']))
			$password = $_POST['password'];
		
		$bfprotection = new BFProtection();
		$bfprotection->verify();
		
		$ptprotection = new PTProtection();
		
		// Logout a user
		if ($logout) {
			$this->reset();
			header('Location: '.str_replace('&amp;', '&', url::uri('logout, memberlogout, requestpassword')));
			exit();
		}
		
		// Login a new user
		if ($member && $password) {
			if ($bfprotection->failureAttempts >= $bfprotection->maximumFailureAttempts) {
				$bfprotection->add($member, $_SERVER['REMOTE_ADDR']);
				$this->verifyError = 3;
				return false;
			}
			
			// Delete userlogins older than 3 hours or 7 days for the "keepit"
			sql::run(
				" DELETE FROM `{userlogins}`" .
				" WHERE (`TimeStamp` < DATE_SUB(NOW(), INTERVAL 3 HOUR)" .
					" AND !`KeepIt`) OR" .
				" `TimeStamp` < DATE_SUB(NOW(), INTERVAL 7 DAY)");
			
			// Delete users which didn't visit our site within at least a month
			users::cleanUp();
					
			$record = sql::fetch(sql::run(
				" SELECT * FROM `{users}`" .
				" WHERE BINARY `UserName` = '".sql::escape($member)."'" .
				" OR BINARY `Email` = '".sql::escape($member)."'" .
				" LIMIT 1"));
				
			if ($record && !$record['Password']) {
				$this->loginok = false;
				$this->verifyError = 7;
				return false;
			}
			
			if (!$record || $record['Password'] != 
				security::text2Hash($password, substr($record['Password'], 0, 7)))
			{
				$bfprotection->add($member, $_SERVER['REMOTE_ADDR']);
				
				$this->loginok = false;
				$this->verifyError = 2;
				return false;
			}
		
			if ($record['Suspended']) {
				$this->verifyError = 1;
				return false;
			}
			
			if (!$record['Admin'] && defined('LOGINS_SUSPENDED') && 
				LOGINS_SUSPENDED) 
			{
				$this->verifyError = 6;
				return false;
			}
		
			$bfprotection->clear($_SERVER['REMOTE_ADDR']);
			
			// If user is banned because of to many logins from different ips
			if ($ptprotection->verify($record['ID'])) {
				$this->kickOut($record['ID']);
				$this->verifyError = 4;
				return false;
			}
			
			if ($rememberme)
				$record['StayLoggedIn'] = 1;
			
			sql::run(
				" INSERT INTO `{userlogins}` SET" .
				" `SessionID` = '".sql::escape(session_id())."', " .
				" `UserID` = '".$record['ID']."'," .
				" `FromIP` = '".ip2long($_SERVER['REMOTE_ADDR'])."'," .
				($record['StayLoggedIn']?
					" `KeepIt` = 1, ":
					NULL) .
				" `TimeStamp` = NOW()");
			
			if (sql::error()) {
				$this->verifyError = 5;
				return false;
			}
	  
			$this->loginok = true;
			$this->logedInNow = true;
			$this->data = $record;
		
			sql::run(
				" UPDATE `{users}` SET" .
				" `StayLoggedIn` = '" .
					($rememberme?
						1:
						0) .
					"'," .
				" `LastVisitTimeStamp` = NOW()," .
				" `IP` = '".ip2long($_SERVER['REMOTE_ADDR'])."'," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".$record['ID']."'");
				
			$cookiedomain = url::rootDomain();
			if (!strstr($cookiedomain, '.'))
				$cookiedomain = false;
					
			if ($record['StayLoggedIn']) 
				setcookie ("memberloginid", session_id(), 
					time()+7*24*60*60, '/', $cookiedomain);
			else 
				setcookie ("memberloginid", session_id(), 
					0, '/', $cookiedomain);
			
			header("Location: ".str_replace('&amp;', '&', url::uri('login, requestpassword')));
			exit();
		}
		
		// Check a logged in user
		if (isset($_COOKIE['memberloginid']) && $_COOKIE['memberloginid']) {
			$record = sql::fetch(sql::run(
				" SELECT *," .
				" IF(`TimeStamp` < DATE_SUB(NOW(), INTERVAL 7 HOUR) AND `KeepIt` = 1, 'True', NULL) AS `CookieNeedsToBeRefreshed`" .
				" FROM `{userlogins}`" .
				" WHERE BINARY `SessionID` = '".sql::escape($_COOKIE['memberloginid'])."'" .
				" LIMIT 1"));
						
			if (!$record) {
				$this->reset();
				return false;
			}
			
			$this->data = $this->get($record['UserID']);
			
			if ((!isset($this->data['SkipIPCheck']) || !$this->data['SkipIPCheck']) && 
				$record['FromIP'] != ip2long($_SERVER['REMOTE_ADDR'])) 
			{
				$this->reset();
				return false;
			}
			
			if ($this->data['Suspended']) {
				$this->reset();
				$this->verifyError = 1;
				return false;
			}
			
			if (!$this->data['Admin'] && defined('LOGINS_SUSPENDED') && 
				LOGINS_SUSPENDED) 
			{
				$this->reset();
				$this->verifyError = 6;
				return false;
			}
		
			sql::run(
				" UPDATE `{userlogins}`" .
				" SET `TimeStamp` = NOW()" .
				" WHERE BINARY `SessionID` = '".sql::escape($_COOKIE['memberloginid'])."'" .
				" AND `UserID` = '".$record['UserID']."'");
					
			$this->loginok = true;
			$this->logedInNow = false;
			
			if ($record['CookieNeedsToBeRefreshed']) {
				$cookiedomain = url::rootDomain();
				if (!strstr($cookiedomain, '.'))
					$cookiedomain = false;
					
				if ($this->data['StayLoggedIn']) 
					setcookie ("memberloginid", $_COOKIE['memberloginid'], 
						time()+7*24*60*60, '/', $cookiedomain);
				else 
					setcookie ("memberloginid", $_COOKIE['memberloginid'], 
						0, '/', $cookiedomain);
			}
			
			return true;
		}
		
		return false;
	}
	
	function checkUsername($username, $userid = null) {
		if (!$username)
			return false;
		
		if(preg_match('/[^a-zA-Z0-9\@\.\_\-]/', $username)) {
			tooltip::display(
				__("Incorrect username. Usernames may consist of a-z, 0-9 and " .
					"underscores only."),
				TOOLTIP_ERROR);
			
			return false;
		}
			
		$usernameexists = sql::fetch(sql::run(
			" SELECT `ID` FROM `{users}`" .
			" WHERE `UserName` = '".sql::escape($username)."'" .
			($userid?
				" AND `ID` != '".(int)$userid."'":
				null) .
			" LIMIT 1"));
			
		if ($usernameexists) {
			tooltip::display(
				__("Username is already taken. " .
					"Please choose a different username or login."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		return true;
	}
	
	function checkEmail($email, $userid = null) {
		if (!$email)
			return false;
		
		$emailexists = sql::fetch(sql::run(
			" SELECT `ID` FROM `{users}`" .
			" WHERE `Email` = '".sql::escape($email)."'" .
			($userid?
				" AND `ID` != '".(int)$userid."'":
				null) .
			" LIMIT 1"));
			
		if ($emailexists) {
			tooltip::display(
				__("Email address is already in use.")." " .
				"<a href='".url::uri('requestpassword')."&amp;requestpassword=1'>" .
					__("Forgot your password?") .
				"</a>.",
				TOOLTIP_ERROR);
			
			return false;
		}
		
		return true;
	}
	
	function addRequest($values) {
		if (!is_array($values)) {
			tooltip::display(
				__("No request values specified!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!isset($values['UserID']) || !$values['UserID']) {
			tooltip::display(
				__("No owner defined for the request!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$requests = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows` FROM `{userrequests}`" .
			" WHERE `UserID` = '".$values['UserID']."'" .
			" AND `FromIP` = '".ip2long($_SERVER['REMOTE_ADDR'])."'" .
			" LIMIT 1"));
		
		if ($requests['Rows'] >= BRUTE_FORCE_MAXIMUM_FAILURE_ATTEMPTS) {
			tooltip::display(
				sprintf(__("You have reached the maximum number of %s " .
					"requests! If you are still having problems completing " .
					"your request please contact us or wait a few hours " .
					"and try again."), 
					BRUTE_FORCE_MAXIMUM_FAILURE_ATTEMPTS),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$newrequestid = security::randomChars(21);
		
		sql::run(
			" INSERT INTO `{userrequests}` SET" .
			" `UserID` = '".$values['UserID']."'," .
			" `TimeStamp` = NOW()," .
			" `RequestTypeID` = '".(int)$values['RequestTypeID']."'," .
			(isset($values['Data'])?
				" `Data` = '" .
					sql::escape($values['Data']) .
					"',":
				null) .
			" `RequestID` = '".$newrequestid."'," .
			" `FromIP` = '".ip2long($_SERVER['REMOTE_ADDR'])."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Request couldn't be stored! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		return $newrequestid;
	}
	
	function getRequest($requestid) {
		if (!$requestid)
			return null;
		
		return sql::fetch(sql::run(
			" SELECT * FROM `{userrequests}` " .
			" WHERE BINARY `RequestID` = '".sql::escape($requestid)."'" .
			" AND `FromIP` = '".ip2long($_SERVER['REMOTE_ADDR'])."'" .
			" LIMIT 1"));
	}
	
	function cleanUpRequests() {
		// Delete requests older than 3 hours
		sql::run(
			" DELETE FROM `{userrequests}`" .
			" WHERE `TimeStamp` < DATE_SUB(NOW(), INTERVAL 3 HOUR)");
	}
	
	static function activate($id) {
		if (!$id)
			return false;
		
		$user = sql::fetch(sql::query(
			" SELECT * FROM `{users}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		if ($user['Password'])
			return true;
		
		$latestrequest = sql::fetch(sql::run(
			" SELECT `Data` FROM `{userrequests}`" .
			" WHERE `UserID` = '".$id."'" .
			" AND `RequestTypeID` IN (" .
				REQUEST_TYPE_NEW_ACCOUNT.", ".REQUEST_TYPE_NEW_PASSWORD.")" .
			" ORDER BY `TimeStamp` DESC" .
			" LIMIT 1"));
		
		$newpassword = null;
		
		if ($latestrequest['Data']) {
			$password = $latestrequest['Data'];
			
		} else {
			$newpassword = security::genPassword($user['Email']);
			$password = security::text2Hash($newpassword);
		}
		
		sql::run(
			" UPDATE `{users}` SET" .
			" `Password` = '".sql::escape($password)."'," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("User couldn't be activated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if ($newpassword) {
			$email = new email();
			$email->load('UserRegistration');
			$email->toUserID = (int)$id;
			$email->variables['Password'] = $newpassword;
			$email->send();
			unset($email);
		}
		
		return true;
	}
	
	static function suspend($id) {
		if (!$id)
			return false;
			
		sql::run(
			" UPDATE `{users}` SET" .
			" `Suspended` = 1," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("User couldn't be suspended! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	static function fastCheck($column = null) {
		if (!isset($_COOKIE['memberloginid']) || !$_COOKIE['memberloginid'])
			return false;
		
		$record = sql::fetch(sql::run(
			" SELECT * FROM `{userlogins}`" .
			" WHERE BINARY `SessionID` = '".sql::escape($_COOKIE['memberloginid'])."'" .
			" AND `FromIP` = '".ip2long($_SERVER['REMOTE_ADDR'])."'" .
			" LIMIT 1"));
					
		if (!$record)
			return false;
			
		$user = sql::fetch(sql::run(
			" SELECT * FROM `{users}`" .
			" WHERE `ID` = '".$record['UserID']."'" .
			" LIMIT 1"));
			
		if ($column)
			return $user[$column];
		
		return $user;
	}
	
	static function cleanUp() {
		sql::run(
			" DELETE FROM `{users}`" .
			" WHERE `TimeStamp` < DATE_SUB(NOW(), INTERVAL 1 MONTH)" .
			" AND (`LastVisitTimeStamp` = '00000000000000'" .
			" OR `LastVisitTimeStamp` = '0000-00-00 00:00:00')");
	}
	
	function requestPassword($values = null) {
		if (!is_array($values) || !isset($values['Email']) || !$values['Email']) {
			$this->result = 
				tooltip::construct(
					__("No email address specified! Please enter the email address " .
						"provided at registration."),
					TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!email::verify($values['Email'])) {
			$this->result = 
				tooltip::construct(
					__("Invalid email address. Please make sure you enter " .
						"a valid email address."),
					TOOLTIP_ERROR);
			
			return false;
		}
		
		$users = sql::run(
			" SELECT * FROM `{users}`" .
			" WHERE !`Suspended`" .
			" AND `Email` = '".sql::escape($values['Email'])."'");
					
		if (!sql::rows($users)) {
			$this->result = 
				tooltip::construct(
					__("Your email address is not in our" .
						" database. Please be sure to use the one" .
						" provided at registration."),
					TOOLTIP_ERROR);
			
			return false;
		}
		
		$email = new email();
		$email->force = true;
		
		if (JCORE_VERSION >= '0.6')
			$email->load('NewPasswordActivation');
		else
			$email->load('RequestPassword');
		
		while($user = sql::fetch($users)) {
			if (JCORE_VERSION >= '0.6') {
				$newpassword = security::genPassword($user['Email']);
				
				$newid = $this->addRequest(array(
					'UserID' => $user['ID'],
					'RequestTypeID' => REQUEST_TYPE_NEW_PASSWORD,
					'Data' => security::text2Hash($newpassword)));
				
			} else {
				$newid = $this->addRequest(array(
					'UserID' => $user['ID'],
					'RequestTypeID' => REQUEST_TYPE_NEW_PASSWORD));
			}
			
			if (!$newid)
				return false;
				
			$email->variables = $user;
			$email->variables['RequestID'] = $newid;
			$email->variables['RequestURL'] = 
				url::site().str_replace('&amp;', '&', url::uri('requestpassword')).
				"&request=users&requestid=".$newid;
			
			if (JCORE_VERSION >= '0.6')
				$email->variables['NewPassword'] = $newpassword;
			
			$email->to = $user['UserName']." <".
				$user['Email'].">";
			
			if (!$email->send())
				return false;
		}
		
		unset($email);
		
		if (JCORE_VERSION >= '0.6')
			$this->result = 
				tooltip::construct(
					__("<b>Request Successful</b><br />" .
						" A notification email has been sent" .
						" to your email address with the neccessary" .
						" information to login to your account."),
					TOOLTIP_SUCCESS);
		else
			$this->result = 
				tooltip::construct(
					__("<b>Request Successful</b><br />" .
						" A notification email has been sent" .
						" to your email address with the neccessary information" .
						" to request a new password."),
					TOOLTIP_SUCCESS);
		
		$this->result .= 
			tooltip::construct(
				__("<b>IMPORTANT:</b> Some email providers put messages" .
					" received from addresses, which are not in your contact list, in" .
					" the \"Bulk / Junk E-Mail\" folders. Please check those folders" .
					" too."),
				TOOLTIP_NOTIFICATION);
		
		return true;
	}
	
	function request() {
		$requestid = null;
		
		if (isset($_GET['requestid']))
			$requestid = $_GET['requestid'];
		
		if (!$requestid)
			return false;
		
		$this->cleanUpRequests();
		$request = $this->getRequest($requestid);
		
		if(!$request) {
			tooltip::display(
				__("Request not found! Please make sure the request was made " .
					"in the last 3 hours as older requests are automatically " .
					"deleted."),
				TOOLTIP_ERROR);
		
			return true;
		}
		
		$user = $this->get($request['UserID']);
		if (!$user) {
			tooltip::display(
				__("Request owner couldn't be found!"),
				TOOLTIP_ERROR);
		
			return true;
		}
		
		switch($request['RequestTypeID']) {
			case REQUEST_TYPE_NEW_ACCOUNT:
				sql::run(
					" UPDATE `{users}` SET" .
					" `Password` = '".sql::escape($request['Data'])."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".$user['ID']."'");
					
				if (sql::affected() == -1) {
					tooltip::display(
						sprintf(__("Your account couldn't be" .
							" activated! Error: %s"), 
							sql::error()),
						TOOLTIP_ERROR);
					return true;
				}
				
				tooltip::display(
					__("<b>Your Account has been Activated</b><br />" .
						" Thank you for registering."),
					TOOLTIP_SUCCESS);
				return true;
			
			case REQUEST_TYPE_NEW_PASSWORD:
				if (JCORE_VERSION >= '0.6') {
					sql::run(
						" UPDATE `{users}` SET" .
						" `Password` = '".sql::escape($request['Data'])."'," .
						" `TimeStamp` = `TimeStamp`" .
						" WHERE `ID` = '".$user['ID']."'");
						
					if (sql::affected() == -1) {
						tooltip::display(
							sprintf(__("Your new password couldn't be" .
								" activated! Error: %s"), 
								sql::error()),
							TOOLTIP_ERROR);
						return true;
					}
					
					tooltip::display(
						__("<b>Your New Password has been Activated</b><br />" .
							" Please copy and paste your new password" .
							" from the email you received to prevent" .
							" any misspelling issues."),
						TOOLTIP_SUCCESS);
					return true;
				}
				
				$password = security::genPassword($user['Email']);
				
				sql::run(
					" UPDATE `{users}` SET" .
					" `Password` = '".sql::escape(security::text2Hash($password))."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".$user['ID']."'");
				
				if (sql::affected() == -1) {
					tooltip::display(
						sprintf(__("Your new password couldn't be" .
							" activated! Error: %s"), 
							sql::error()),
						TOOLTIP_ERROR);
					return true;
				}
					
				$email = new email();
				$email->force = true;
				$email->load('NewPassword');
				
				$email->variables = array(
					'NewPassword' => $password);
				
				$email->toUserID = $user['ID'];
				$email->send();
				unset($email);
			
				tooltip::display(
					__("<b>Your New Password has been Sent</b><br />" .
						" A notification email has been sent" .
						" to your email address with the new login information."),
					TOOLTIP_SUCCESS);
					
				tooltip::display(
					__("<b>IMPORTANT:</b> Some email providers put messages" .
						" received from addresses, which are not in your contact list, in" .
						" the \"Bulk / Junk E-Mail\" folders. Please check those folders" .
						" too."),
					TOOLTIP_NOTIFICATION);
				return true;
		}
		
		return true;
	}
	
	function ajaxRequest() {
		$login = null;
		
		if (isset($_GET['quicklogin']))
			$login = $_GET['quicklogin'];
		
		if ($login) {
			$this->displayQuickLogin();
			return true;
		}
		
		return false;
	}
	
	function constructUserName($row, $format = null) {
		$username = $row['UserName'];
		
		if (!strstr($format, '%s'))
			$format .= " %s";
		
		if (isset($row['DisplayUserName']) && $row['DisplayUserName'])
			$username = $row['DisplayUserName'];
		
		if (isset($GLOBALS['ADMIN']) && $GLOBALS['ADMIN'])
			return
				"<span class='user-name'>" .
					sprintf($format,
						"<a class='user-name-link' href='".
						url::uri('ALL') .
						"?path=admin/members/users" .
						"&amp;search=".urlencode($row['Email']) .
						"&amp;id=".$row['ID'] ."' " .
						"target='_blank'>".
							$username .
						"</a>") .
				"</span>";
		
		if (!isset($row['Website']) || !$row['Website'])
			return
				"<span class='user-name'>" .
					sprintf($format, $username) .
				"</span>";
		
		return
			"<span class='user-name'>" .
				sprintf($format,
					"<a class='user-name-link' href='".
						htmlspecialchars($row['Website'], ENT_QUOTES).
						"' rel='nofollow' target='_blank'>".
						$username .
					"</a>") .
			"</span>";
	}
	
	function setupQuickAccountForm(&$form) {
		$referer = url::referer(true);
		
		if(strstr($referer, '?'))
			$logoutlink = $referer.'&amp;logout=1';
		else
			$logoutlink = $referer.'?logout=1';
		
		$form->add(
			sprintf(__("Welcome back %s"),
				$GLOBALS['USER']->data['UserName']),
			null,
			FORM_STATIC_TEXT);
		
		$form->add(
			"[ <a href='".$logoutlink."'>".__("Logout")."</a> ]",
			null,
			FORM_STATIC_TEXT);
	}
	
	function setupQuickLoginForm(&$form) {
		$form->add(
			__('Username'),
			'member',
			FORM_INPUT_TYPE_TEXT);
		
		$form->add(
			__('Password'),
			'password',
			FORM_INPUT_TYPE_PASSWORD);
	}
	
	
	function setupLoginForm(&$form) {
		$form->add(
			__('Username'),
			'member',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setAutoFocus(true);
		
		$form->add(
			__('Password'),
			'password',
			FORM_INPUT_TYPE_PASSWORD,
			true);
		
		$form->add(
			"<label>" .
			"<input type='checkbox' name='rememberme' value='1' /> " .
				__("Remember me (stay logged in)") .
			"</label>",
			'rememberme',
			FORM_STATIC_TEXT);
	}
	
	function setupRequestPasswordForm(&$form) {
		$form->add(
			__("Please enter the email address you have provided at registration / join."),
			null,
			FORM_STATIC_TEXT);
		
		$form->add(
			__('Email address'),
			'Email',
			FORM_INPUT_TYPE_EMAIL,
			true);
	}
	
	function displayAvatar($useridoremail = null, $size = null, $default = null) {
		if (defined('AVATARS_DISABLED') && AVATARS_DISABLED)
			return false;
		
		if (!isset($useridoremail) && $this->loginok)
			$useridoremail = $this->data['Email'];
		
		if (!$size && defined('AVATARS_SIZE') && AVATARS_SIZE)
			$size = (int)AVATARS_SIZE;
		
		if (!$size)
			$size = 64;
		
		if (!$default && defined('DEFAULT_AVATAR_URL') && DEFAULT_AVATAR_URL)
			$default = DEFAULT_AVATAR_URL;
		
		if (!$default)
			$default = 'mm';
		
		$hash = null;
		if (is_numeric($useridoremail)) {
			$user = $this->get($useridoremail);
			
			if ($user && $user['ShowAvatar'])
				$hash = md5(strtolower(trim($user['Email'])));
			
		} else {
			$hash = md5(strtolower(trim($useridoremail)));
		}
		
		echo
			"<div class='user-avatar'>" .
				"<img src='http://www.gravatar.com/avatar/".$hash."?s=".$size .
					"&amp;d=".urlencode($default)."' " .
					"border='0' alt='".htmlspecialchars(__("Avatar"), ENT_QUOTES)."' " .
					"class='gavatar' />" .
			"</div>";
		
		return true;
	}
	
	function displayQuickAccountForm(&$form) {
		$form->display();
	}
	
	function displayQuickAccount() {
		$form = new form(
			__('Quick Account'),
			'quickaccount');
		
		$form->action = url::referer(true);
		$form->footer = '';
		
		$this->setupQuickAccountForm($form);
		$this->displayQuickAccountForm($form);
		unset($form);
	}
	
	function displayQuickLoginForm(&$form) {
		$form->display();
	}
	
	function displayQuickLogin() {
		if ($GLOBALS['USER']->loginok) {
			$this->displayQuickAccount();
			return true;
		}
		
		$referer = url::referer(true);
		
		if(strstr($referer, '?'))
			$requestlink = $referer.'&amp;requestpassword=1';
		else
			$requestlink = $referer.'?requestpassword=1';
		
		$form = new form(
			__('Quick Login'),
			'quicklogin');
		
		$form->footer = '';
		$form->action = $referer.
			(isset($_GET['anchor'])?
				"#".$_GET['anchor']:
				null);
		
		$this->setupQuickLoginForm($form);
		
		$form->add(
			__('Login'),
			'login',
			FORM_INPUT_TYPE_SUBMIT);
		
		$form->add(
			"<br />[ <a href='".$requestlink."'>" .
				__("Forgot your password?")."</a> ]",
			null,
			FORM_STATIC_TEXT);
		
		$this->displayQuickLoginForm($form);
		unset($form);
		
		return true;
	}
	
	function displayLoginForm(&$form) {
		$form->display();
	}
	
	function displayLogin() {
		$form = new form(
			__('Member Login'),
			'memberlogin');
		
		$form->action = url::uri('requestpassword');
		$form->verifyPassword = false;
		
		$this->setupLoginForm($form);
		
		$form->add(
			__('Login'),
			'login',
			FORM_INPUT_TYPE_SUBMIT);
		
		$form->addAdditionalPreText(
			"<div class='align-right'>" .
				"[ <a href='".url::uri('requestpassword')."&amp;requestpassword=1'>" .
					__("Forgot your password?")."</a> ]" .
			"</div>");
		
		$form->add(
			__('Cancel'),
			'cancel',
			FORM_INPUT_TYPE_BUTTON);
		$form->addAttributes("onclick=\"window.location='".url::site()."';\"");
		
		$form->verify();
		$this->displayLoginForm($form);
		unset($form);
		
		return true;
	}
	
	function displayRequestPasswordForm(&$form) {
		$form->display();
	}
	
	function displayRequestPassword() {
		$form = new form(
			__('Request a New Password'),
			'requestanewpassword');
		
		$this->setupRequestPasswordForm($form);
		
		$form->add(
			__('Request a New Password'),
			'requestsubmit',
			FORM_INPUT_TYPE_SUBMIT);
		
		$form->add(
			__('Cancel'),
			'cancel',
			FORM_INPUT_TYPE_BUTTON);
		$form->addAttributes("onclick=\"window.location='".
			str_replace('&amp;', '&', url::uri('requestpassword'))."';\"");
		
		$request = $form->verify();
		$requested = false;
		
		if ($request) {
			$requested = $this->requestPassword($form->getPostArray());
			$this->displayResult();
		}
		
		if (!$requested)
			$this->displayRequestPasswordForm($form);
		
		unset($form);
		
		return true;
	}
	
	function displayResult() {
		if (!$this->verifyError && !$this->result)
			return;
		
		if ($this->result) {
			echo $this->result;
			unset($this->result);
			return;
		}
			
		$bfprotection = new BFProtection();
		$bfprotection->get($_SERVER['REMOTE_ADDR']);
		
		$ptprotection = new PTProtection();
		
		if ($this->verifyError == 1)
			tooltip::display(
				__("This account has been suspended!"),
				TOOLTIP_ERROR);
		
		if ($this->verifyError == 2)
			tooltip::display(
				__("You have entered an invalid username or password. " .
					"Please enter the correct details (use copy and paste) or use " .
					"your email address as your username and try again. " .
					"Don't forget that the password is case sensitive.") .
					" <a href='".url::uri('requestpassword')."&amp;requestpassword=1". 
						"'>".__("Forgot your password?")."</a>" .
				(BRUTE_FORCE_PROTECTION_ENABLED?
					"<br /><br />" .
					sprintf(__("You have used %s out of %s login attempts. After all " .
						"%s have been used, you will be unable to login for ".
						"%s minutes."),
							$bfprotection->failureAttempts,
							$bfprotection->maximumFailureAttempts,
							$bfprotection->maximumFailureAttempts,
							$bfprotection->protectionTimeMinutes):
					null),
				TOOLTIP_ERROR);
		
		if ($this->verifyError == 3)
			tooltip::display(
				sprintf(__("Wrong username or password. <b>You have used up your " .
					"failed login quota! Please wait %s minutes before " .
					"trying again.</b> Don't forget that the password is case sensitive."), 
					$bfprotection->protectionTimeMinutes) ." " .
				"<a href='".url::uri('requestpassword')."&amp;requestpassword=1" .
					"'>".__("Forgot your password?")."</a>",
				TOOLTIP_ERROR);
				
		if ($this->verifyError == 4)
			tooltip::display(
				sprintf(__("Account suspended! <b>This account has been suspended " .
					"for %s minutes because of to many logins from different IP " .
					"addresses.</b> Please ensure that you have a secure " .
					"password and/or your are logged out from your other PCs before trying " .
					"again."), 
						$ptprotection->protectionTimeMinutes),
				TOOLTIP_ERROR);
				
		if ($this->verifyError == 5)
			tooltip::display(
				__("Your session couldn't be stored! " .
					"Please contact the webmaster with this error and " .
					"your login information."),
				TOOLTIP_ERROR);
		
		if ($this->verifyError == 6)
			tooltip::display(
				__("Login has been temporarily suspended. " .
					"Please try again later."),
				TOOLTIP_ERROR);
				
		if ($this->verifyError == 7)
			tooltip::display(
				__("The specified username is currently inactive. If you " .
					"have problems activating your account, please contact " .
					"us or request a new password to activate your account."),
				TOOLTIP_ERROR);
				
		unset($this->verifyError);
		unset($bfprotection);
	}
	
	function displayUserName($row, $format = null) {
		echo $this->constructUserName($row, $format);
	}
	
	function display($login = false) {
		$this->displayResult();
		
		$requestpassword = null;
		
		if (isset($_GET['requestpassword']))
			$requestpassword = $_GET['requestpassword'];
		
		if (isset($_GET['login']) || isset($_GET['quicklogin']) || $login)
			$login = true;
		
		if ($requestpassword)
			return $this->displayRequestPassword();
		
		if ($login)
			return $this->displayLogin();
	}
}

?>