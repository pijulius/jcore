<?php

/***************************************************************************
 *            users.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
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
		api::callHooks(API_HOOK_BEFORE,
			'users::countAdminItems', $this);
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{users}`" .
			" LIMIT 1"));
		
		api::callHooks(API_HOOK_AFTER,
			'users::countAdminItems', $this, $row['Rows']);
		
		return $row['Rows'];
	}
	
	function setupAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'users::setupAdmin', $this);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New User'), 
				'?path='.admin::path().'#adminform');
		
		if (JCORE_VERSION >= '0.8')
			favoriteLinks::add(
				__('User Groups'), 
				'?path=admin/members/usergroups');
		else
			favoriteLinks::add(
				__('Mass Email'), 
				'?path=admin/members/massemail');
		
		favoriteLinks::add(
			__('Settings'), 
			'?path=admin/site/settings');
		
		api::callHooks(API_HOOK_AFTER,
			'users::setupAdmin', $this);
	}
	
	function setupAdminForm(&$form, $membersModuleAvailable = false, $groupids = null) {
		api::callHooks(API_HOOK_BEFORE,
			'users::setupAdminForm', $this, $form, $membersModuleAvailable);
		
		$groupid = null;
		$edit = null;
		
		if (isset($_GET['searchgroupid']))
			$groupid = (int)$_GET['searchgroupid'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
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
				
			if (JCORE_VERSION >= '0.8' && !$form->getElementID('GroupID')) {
				$groups = userGroups::get();
				
				if (sql::rows($groups)) {
					$form->add(
						__('Group'),
						'GroupID',
						FORM_INPUT_TYPE_SELECT,
						false,
						$groupid);
					$form->setValueType(FORM_VALUE_TYPE_INT);
					
					if (!$groupids)
						$form->addValue('', '');
					
					while($group = sql::fetch($groups)) {
						if ($groupids && !in_array($group['ID'], (array)$groupids))
							continue;
						
						$form->addValue($group['ID'], $group['GroupName']);
					}
				}
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
			
			if (JCORE_VERSION >= '0.8' && !$form->getElementID('GroupID')) {
				$groups = userGroups::get();
				
				if (sql::rows($groups)) {
					$form->add(
						__('Group'),
						'GroupID',
						FORM_INPUT_TYPE_SELECT,
						false,
						$groupid);
					$form->setValueType(FORM_VALUE_TYPE_INT);
					
					if (!$groupids)
						$form->addValue('', '');
					
					while($group = sql::fetch($groups)) {
						if ($groupids && !in_array($group['ID'], (array)$groupids))
							continue;
						
						$form->addValue($group['ID'], $group['GroupName']);
					}
				}
			}
			
			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER);
		}
		
		$form->setTooltipText('Password', 
			sprintf(__("minimum %s characters"), MINIMUM_PASSWORD_LENGTH));
		
		api::callHooks(API_HOOK_AFTER,
			'users::setupAdminForm', $this, $form, $membersModuleAvailable);
	}
	
	function verifyAdmin(&$form, $groupids = null) {
		api::callHooks(API_HOOK_BEFORE,
			'users::verifyAdmin', $this, $form);
		
		$activate = null;
		$suspend = null;
		$delete = null;
		$edit = null;
		$id = null;
		$ids = null;
		
		if (isset($_POST['activatesubmit']))
			$activate = (string)$_POST['activatesubmit'];
		
		if (isset($_POST['suspendsubmit']))
			$suspend = (string)$_POST['suspendsubmit'];
		
		if (isset($_POST['deletesubmit']))
			$delete = (string)$_POST['deletesubmit'];
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
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
			
			api::callHooks(API_HOOK_AFTER,
				'users::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if ($ids && count($ids)) {
			$permissionids = null;
			if ($this->userPermissionIDs)
				$permissionids = explode(',', $this->userPermissionIDs);
			
			if ($activate) {
				foreach($ids as $id) {
					if ($permissionids && !in_array($id, $permissionids))
						continue;
					
					if ($this->userPermissionType & USER_PERMISSION_TYPE_OWN && 
						(int)$id != $GLOBALS['USER']->data['ID'])
						continue;
					
					$this->activate((int)$id, $groupids);
				}
				
				tooltip::display(
					__("Users have been successfully activated and are now " .
						"able to login."),
					TOOLTIP_SUCCESS);
					
				api::callHooks(API_HOOK_AFTER,
					'users::verifyAdmin', $this, $form, $activate);
				
				return true;
			}
			
			if ($suspend) {
				foreach($ids as $id) {
					if ($permissionids && !in_array($id, $permissionids))
						continue;
					
					if ($this->userPermissionType & USER_PERMISSION_TYPE_OWN && 
						(int)$id != $GLOBALS['USER']->data['ID'])
						continue;
					
					$this->suspend((int)$id, $groupids);
				}
				
				tooltip::display(
					__("Users have been successfully suspended."),
					TOOLTIP_SUCCESS);
					
				api::callHooks(API_HOOK_AFTER,
					'users::verifyAdmin', $this, $form, $suspend);
				
				return true;
			}
			
			if ($delete) {
				foreach($ids as $id) {
					if ($permissionids && !in_array($id, $permissionids))
						continue;
					
					if ($this->userPermissionType & USER_PERMISSION_TYPE_OWN && 
						(int)$id != $GLOBALS['USER']->data['ID'])
						continue;
					
					$this->delete((int)$id, $groupids);
				}
				
				tooltip::display(
					__("Users have been successfully deleted."),
					TOOLTIP_SUCCESS);
					
				api::callHooks(API_HOOK_AFTER,
					'users::verifyAdmin', $this, $form, $delete);
				
				return true;
			}
		}
			
		if ($delete) {
			$result = $this->delete($id, $groupids);
			
			if ($result)
				tooltip::display(
					__("User has been successfully deleted."),
					TOOLTIP_SUCCESS);
				
			api::callHooks(API_HOOK_AFTER,
				'users::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'users::verifyAdmin', $this, $form);
			
			return false;
		}
		
		if ($edit) {
			$form->setValue('RePassword', $form->get('Password'));
			
			$result = $this->edit($id, $form->getPostArray(), $groupids);
			
			if ($result)
				tooltip::display(
					__("User has been successfully updated.")." " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'users::verifyAdmin', $this, $form, $result);
			
			return $result;
		}
		
		if ($this->userPermissionIDs || $this->userPermissionType & USER_PERMISSION_TYPE_OWN) {
			api::callHooks(API_HOOK_AFTER,
				'users::verifyAdmin', $this, $form);
			
			return false;
		}
		
		$newid = $this->add($form->getPostArray(), $groupids); 
		
		if ($newid) {
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
		}
		
		api::callHooks(API_HOOK_AFTER,
			'users::verifyAdmin', $this, $form, $newid);
		
		return $newid;
	}
	
	function displayAdminListHeader() {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAdminListHeader', $this);
		
		echo
			"<th>" .
				"<input type='checkbox' class='checkbox-all' " .
				(~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE?
					"disabled='disabled' ":
					null) .
				"/>" .
			"</th>" .
			"<th><span class='nowrap'>".
				__("Username / Registered on")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Admin")."</span></th>";
		
		if (JCORE_VERSION >= '0.8')
			echo
				"<th style='text-align: right;'><span class='nowrap'>".
					__("Group")."</span></th>";
		
		echo
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Email")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayAdminListHeader', $this);
	}
	
	function displayAdminListHeaderOptions() {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAdminListHeaderOptions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Permissions")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAdminListHeaderFunctions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAdminListItem', $this, $row);
		
		$ids = null;
		$group = null;
		
		if (isset($_POST['ids']))
			$ids = (array)$_POST['ids'];
		
		if (JCORE_VERSION >= '0.8' && $row['GroupID'])
			$group = userGroups::get($row['GroupID']);
		
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
			"</td>";
		
		if (JCORE_VERSION >= '0.8')
			echo
				"<td style='text-align: right;'>" .
					"<span class='nowrap'>" .
					($group?
						$group['GroupName']:
						null).
					"</span>" .
				"</td>";
		
		echo
			"<td style='text-align: right;'>" .
				"<a href='mailto:".$row['Email']."'>".
					$row['Email'] .
				"</a>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayAdminListItem', $this, $row);
	}
	
	function displayAdminListItemOptions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAdminListItemOptions', $this, $row);
		
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
					"?path=".admin::path()."/".$row['ID']."/userpermissions'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $permissions['Rows'])
			counter::display($permissions['Rows']);
		
		echo
				"</a>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAdminListItemFunctions', $this, $row);
		
		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='" .url::uri('id, edit, delete') .
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
			'users::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminListItemSelected(&$row, $membersModuleAvailable = false) {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAdminListItemSelected', $this, $row, $membersModuleAvailable);
		
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
				security::long2ip($row['IP']));
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayAdminListItemSelected', $this, $row, $membersModuleAvailable);
	}
	
	function displayAdminListSearch($groupids = null) {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAdminListSearch', $this, $groupids);
		
		$search = null;
		$groupid = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags((string)$_GET['search']));
		
		if (isset($_GET['searchgroupid']))
			$groupid = (int)$_GET['searchgroupid'];
		
		echo
			"<input type='hidden' name='path' value='".admin::path()."' />" .
			"<input type='search' name='search' value='".
				htmlspecialchars($search, ENT_QUOTES).
				"' results='5' placeholder='".htmlspecialchars(__("search..."), ENT_QUOTES)."' /> ";
		
		if (JCORE_VERSION >= '0.8') {
			$groups = userGroups::get();
			
			if (sql::rows($groups)) {
				echo
					"<select name='searchgroupid' style='width: 100px;' " .
						"onchange='this.form.submit();'>" .
						"<option value=''>"._("All")."</option>";
				
				while($group = sql::fetch($groups)) {
					if ($groupids && !in_array($group['ID'], (array)$groupids))
						continue;
					
					echo
						"<option value='".$group['ID']."'" .
							($group['ID'] == $groupid?
								" selected='selected'":
								null) .
							">" .
							$group['GroupName'] .
						"</option>";
				}
				
				echo
					"</select> ";
			}
		}
		
		echo
			"<input type='submit' value='" .
				htmlspecialchars(__("Search"), ENT_QUOTES)."' class='button' />";
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayAdminListSearch', $this, $groupids);
	}
	
	function displayAdminListFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAdminListFunctions', $this);
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayAdminListFunctions', $this);
	}
	
	function displayAdminList(&$rows, $membersModuleAvailable = false) {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAdminList', $this, $rows, $membersModuleAvailable);
		
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			$this->displayAdminListFunctions();
			
			echo
				"<div class='clear-both'></div>" .
				"<br />";
		}
		
		echo
			"</form>";
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayAdminList', $this, $rows, $membersModuleAvailable);
	}
	
	function displayAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAdminForm', $this, $form);
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAdminTitle', $this, $ownertitle);
		
		admin::displayTitle(
			__('Users Administration'),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAdminDescription', $this);
		api::callHooks(API_HOOK_AFTER,
			'users::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAdmin', $this);
		
		$search = null;
		$groupid = null;
		$delete = null;
		$edit = null;
		$id = null;
		$groupids = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags((string)$_GET['search']));
		
		if (isset($_GET['searchgroupid']))
			$groupid = (int)$_GET['searchgroupid'];
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if (JCORE_VERSION >= '0.8' &&
			isset($GLOBALS['USER']->data['GroupID']) && 
			$GLOBALS['USER']->data['GroupID'])
		{
			$gpermission = userPermissions::check(
				(int)$GLOBALS['USER']->data['ID'],
				'admin/members/usergroups');
			
			if (~$gpermission['PermissionType'] & USER_PERMISSION_TYPE_WRITE)
				$groupids = userGroups::get($GLOBALS['USER']->data['GroupID'], true);
		}
		
		echo
			"<div style='float: right;'>" .
				"<form action='".url::uri('ALL')."' method='get'>";
		
		$this->displayAdminListSearch($groupids);
		
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
		
		$this->setupAdminForm($form, $membersModuleAvailable, $groupids);
		$form->addSubmitButtons();
		
		if ($edit) {
			$form->add(
				__('Cancel'),
				'cancel',
				 FORM_INPUT_TYPE_BUTTON);
			$form->addAttributes("onclick=\"window.location='".
				str_replace('&amp;', '&', url::uri('id, edit, delete'))."'\"");
		}
		
		$selected = null;
		$verifyok = false;
		
		if ($id)
			$selected = sql::fetch(sql::run(
				" SELECT `ID` FROM `{users}`" .
				" WHERE `ID` = '".$id."'" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
					" AND `ID` = '".(int)$GLOBALS['USER']->data['ID']."'":
					null) .
				($groupids?
					" AND `GroupID` IN (".implode(',', (array)$groupids).")":
					null)));
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			((!$edit && !$delete) || $selected))
			$verifyok = $this->verifyAdmin($form, $groupids);
		
		$paging = new paging(20);
		$paging->ignoreArgs = 'id, edit, delete';
		
		$rows = sql::run(
				" SELECT * FROM `{users}`" .
				" WHERE 1" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
					" AND `ID` = '".(int)$GLOBALS['USER']->data['ID']."'":
					null) .
				($groupids?
					" AND `GroupID` IN (".implode(',', (array)$groupids).")":
					null) .
				(JCORE_VERSION >= '0.8' && $groupid?
					" AND `GroupID` = '".(int)$groupid."'":
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			((!$this->userPermissionIDs && ~$this->userPermissionType & USER_PERMISSION_TYPE_OWN) || ($edit && $selected)))
		{
			if ($edit && $selected && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{users}`" .
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
			'users::displayAdmin', $this);
	}
	
	function add($values, $groupids = null) {
		if (!is_array($values))
			return false;
		
		if (JCORE_VERSION >= '0.8' && 
			$groupids && !in_array($values['GroupID'], (array)$groupids))
			return false;
		
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
		
		api::callHooks(API_HOOK_BEFORE,
			'users::add', $this, $values);
		
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
			 ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin']) ||
			 (defined('INSTANT_USER_REGISTRATION') && INSTANT_USER_REGISTRATION)?
				" `Password` = '".sql::escape(security::genHash($password))."',":
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
			
			api::callHooks(API_HOOK_AFTER,
				'users::add', $this, $values);
			
			return false;
		}
		
		if (JCORE_VERSION >= '0.6' && (!$GLOBALS['USER']->loginok ||
			!$GLOBALS['USER']->data['Admin']) &&
			(!defined('INSTANT_USER_REGISTRATION') || !INSTANT_USER_REGISTRATION))
		{
			$email = new email();
			$email->load('NewAccountActivation');
			
			$requestid = $this->addRequest(array(
				'UserID' => $newid,
				'RequestTypeID' => REQUEST_TYPE_NEW_ACCOUNT,
				'Data' => security::genHash($password)));
			
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
			
		api::callHooks(API_HOOK_AFTER,
			'users::add', $this, $values, $newid);
		
		return $newid;
	}
	
	function edit($id, $values, $groupids = null) {
		if (!$id)			
			return false;
		
		if (!is_array($values))
			return false;
		
		if (JCORE_VERSION >= '0.8' && 
			$groupids && !in_array($values['GroupID'], (array)$groupids))
			return false;
		
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
		
		api::callHooks(API_HOOK_BEFORE,
			'users::edit', $this, $id, $values);
		
		$query = 
			" `UserName` = '".
				sql::escape($values['UserName'])."'," .
			($values['Password']?
				" `Password` = '".
					sql::escape(security::genHash($values['Password']))."',":
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
			
		$result = (sql::affected() != -1);
		
		if (!$result)
			tooltip::display(
				sprintf(__("User couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		else
			$this->refresh();
		
		api::callHooks(API_HOOK_AFTER,
			'users::edit', $this, $id, $values, $result);
		
		return $result;
	}
	
	function delete($id, $groupids = null) {
		if (!$id)
			return false;
			
		$user = users::get((int)$id);
		if (JCORE_VERSION >= '0.8' && 
			$groupids && !in_array($user['GroupID'], (array)$groupids))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'users::delete', $this, $id);
		
		sql::run(
			" DELETE FROM `{users}`" .
			" WHERE `ID` = '".(int)$id."'");
			
		sql::run(
			" DELETE FROM `{userlogins}`" .
			" WHERE `UserID` = '".(int)$id."'");
			
		api::callHooks(API_HOOK_AFTER,
			'users::delete', $this, $id);
		
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
		api::callHooks(API_HOOK_BEFORE,
			'users::get', $this);
		
		$result = false;
		
		if (isset($this->data['ID']) && $this->data['ID']) {
			$result = true;
			
			$this->data = sql::fetch(sql::run(
				" SELECT * FROM `{users}`" .
				" WHERE `ID` = '".$this->data['ID']."'"));
		}
			
		api::callHooks(API_HOOK_AFTER,
			'users::get', $this, $result);
		
		return $result;
	}
	
	function kickOut($id) {
		api::callHooks(API_HOOK_BEFORE,
			'users::kickOut', $this, $id);
		
		sql::run(
			" DELETE FROM `{userlogins}` " .
			" WHERE `UserID` = '".(int)$id."'");
		
		api::callHooks(API_HOOK_AFTER,
			'users::kickOut', $this, $id);
	}
	
	function reset() {
		api::callHooks(API_HOOK_BEFORE,
			'users::reset', $this);
		
		if (isset($_COOKIE['memberloginid']))
			sql::run(
				" DELETE FROM `{userlogins}`" .
				" WHERE `SessionID` = BINARY '".$_COOKIE['memberloginid']."'");
		
		$this->loginok = null;
		$this->data = null;

		$cookiedomain = url::rootDomain();
		if (strpos($cookiedomain, '.') === false)
			$cookiedomain = false;
		
		do {
			setcookie("memberloginid", '', time() - 3600, '/', $cookiedomain, false, true);
			$cookiedomain = preg_replace('/^.*?\./', '', $cookiedomain);
		} while (strpos($cookiedomain, '.') !== false);
		
		unset($_COOKIE['memberloginid']);
		
		api::callHooks(API_HOOK_AFTER,
			'users::reset', $this);
	}
	
	function check() {
		api::callHooks(API_HOOK_BEFORE,
			'users::check', $this);
		
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
			$member = trim(strip_tags((string)$_POST['member']));
			
		if (isset($_POST['password']))
			$password = (string)$_POST['password'];
		
		$bfprotection = new BFProtection();
		$bfprotection->verify();
		
		$ptprotection = new PTProtection();
		
		// Logout a user
		if ($logout) {
			$this->reset();
			header('Location: '.str_replace('&amp;', '&', url::uri('logout, memberlogout, requestpassword')));
			
			api::callHooks(API_HOOK_AFTER,
				'users::check', $this);
			
			exit();
		}
		
		// Login a new user
		if ($member && $password) {
			if ($bfprotection->failureAttempts >= $bfprotection->maximumFailureAttempts) {
				$bfprotection->add($member, strip_tags((string)$_SERVER['REMOTE_ADDR']));
				$this->verifyError = 3;
				
				api::callHooks(API_HOOK_AFTER,
					'users::check', $this);
				
				return false;
			}
			
			// Delete userlogins older than 3 hours or 7 days for the "keepit"
			sql::run(
				" DELETE FROM `{userlogins}`" .
				" WHERE (`TimeStamp` < DATE_SUB(NOW(), INTERVAL 3 HOUR)" .
					" AND `KeepIt` = 0) OR" .
				" `TimeStamp` < DATE_SUB(NOW(), INTERVAL 7 DAY)");
			
			// Delete users which didn't visit our site within at least a month
			users::cleanUp();
					
			$record = sql::fetch(sql::run(
				" SELECT * FROM `{users}`" .
				" WHERE `UserName` = BINARY '".sql::escape($member)."'" .
				" OR `Email` = '".sql::escape($member)."'" .
				" LIMIT 1"));
				
			if ($record && !$record['Password']) {
				$this->loginok = false;
				$this->verifyError = 7;
				
				api::callHooks(API_HOOK_AFTER,
					'users::check', $this);
				
				return false;
			}
			
			if (!$record || !security::checkHash($password, $record['Password'])) {
				$bfprotection->add($member, strip_tags((string)$_SERVER['REMOTE_ADDR']));
				
				$this->loginok = false;
				$this->verifyError = 2;
				
				api::callHooks(API_HOOK_AFTER,
					'users::check', $this);
				
				return false;
			}
		
			if ($record['Suspended']) {
				$this->verifyError = 1;
				
				api::callHooks(API_HOOK_AFTER,
					'users::check', $this);
				
				return false;
			}
			
			if (!$record['Admin'] && defined('LOGINS_SUSPENDED') && 
				LOGINS_SUSPENDED) 
			{
				$this->verifyError = 6;
				
				api::callHooks(API_HOOK_AFTER,
					'users::check', $this);
				
				return false;
			}
		
			$bfprotection->clear(strip_tags((string)$_SERVER['REMOTE_ADDR']));
			
			// If user is banned because of to many logins from different ips
			if ($ptprotection->verify($record['ID'])) {
				$this->kickOut($record['ID']);
				$this->verifyError = 4;
				
				api::callHooks(API_HOOK_AFTER,
					'users::check', $this);
				
				return false;
			}
			
			if ($rememberme)
				$record['StayLoggedIn'] = 1;
			
			sql::run(
				" INSERT INTO `{userlogins}` SET" .
				" `SessionID` = '".sql::escape(sha1(session_id().time() .
					$record['ID'].$record['Email'] .
					$record['TimeStamp'].$record['LastVisitTimeStamp']))."', " .
				" `UserID` = '".$record['ID']."'," .
				" `FromIP` = '".security::ip2long((string)$_SERVER['REMOTE_ADDR'])."'," .
				($record['StayLoggedIn']?
					" `KeepIt` = 1, ":
					NULL) .
				" `TimeStamp` = NOW()");
			
			if (sql::error()) {
				$this->verifyError = 5;
				
				api::callHooks(API_HOOK_AFTER,
					'users::check', $this);
				
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
				" `IP` = '".security::ip2long((string)$_SERVER['REMOTE_ADDR'])."'," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".$record['ID']."'");
				
			$cookiedomain = url::rootDomain();
			if (strpos($cookiedomain, '.') === false)
				$cookiedomain = false;
					
			if ($record['StayLoggedIn']) 
				setcookie ("memberloginid", sha1(session_id().time() .
					$record['ID'].$record['Email'] .
					$record['TimeStamp'].$record['LastVisitTimeStamp']), 
					time()+7*24*60*60, '/', $cookiedomain, false, true);
			else 
				setcookie ("memberloginid", sha1(session_id().time() .
					$record['ID'].$record['Email'] .
					$record['TimeStamp'].$record['LastVisitTimeStamp']), 
					0, '/', $cookiedomain, false, true);
			
			header("Location: ".str_replace('&amp;', '&', url::uri('login, requestpassword')));
			
			api::callHooks(API_HOOK_AFTER,
				'users::check', $this);
			
			exit();
		}
		
		// Check a logged in user
		if (isset($_COOKIE['memberloginid']) && $_COOKIE['memberloginid']) {
			$record = sql::fetch(sql::run(
				" SELECT *," .
				" IF(`TimeStamp` < DATE_SUB(NOW(), INTERVAL 7 HOUR) AND `KeepIt` = 1, 'True', NULL) AS `CookieNeedsToBeRefreshed`" .
				" FROM `{userlogins}`" .
				" WHERE `SessionID` = BINARY '".sql::escape($_COOKIE['memberloginid'])."'" .
				" LIMIT 1"));
						
			if (!$record) {
				$this->reset();
				
				api::callHooks(API_HOOK_AFTER,
					'users::check', $this);
				
				return false;
			}
			
			$this->data = $this->get($record['UserID']);
			
			if ((!isset($this->data['SkipIPCheck']) || !$this->data['SkipIPCheck']) && 
				$record['FromIP'] != security::ip2long((string)$_SERVER['REMOTE_ADDR'])) 
			{
				$this->reset();
				
				api::callHooks(API_HOOK_AFTER,
					'users::check', $this);
				
				return false;
			}
			
			if ($this->data['Suspended']) {
				$this->reset();
				$this->verifyError = 1;
				
				api::callHooks(API_HOOK_AFTER,
					'users::check', $this);
				
				return false;
			}
			
			if (!$this->data['Admin'] && defined('LOGINS_SUSPENDED') && 
				LOGINS_SUSPENDED) 
			{
				$this->reset();
				$this->verifyError = 6;
				
				api::callHooks(API_HOOK_AFTER,
					'users::check', $this);
				
				return false;
			}
		
			sql::run(
				" UPDATE `{userlogins}`" .
				" SET `TimeStamp` = NOW()" .
				" WHERE `SessionID` = BINARY '".sql::escape($_COOKIE['memberloginid'])."'" .
				" AND `UserID` = '".$record['UserID']."'");
					
			$this->loginok = true;
			$this->logedInNow = false;
			
			if ($record['CookieNeedsToBeRefreshed']) {
				$cookiedomain = url::rootDomain();
				if (strpos($cookiedomain, '.') === false)
					$cookiedomain = false;
					
				if ($this->data['StayLoggedIn']) 
					setcookie ("memberloginid", $_COOKIE['memberloginid'], 
						time()+7*24*60*60, '/', $cookiedomain, false, true);
				else 
					setcookie ("memberloginid", $_COOKIE['memberloginid'], 
						0, '/', $cookiedomain, false, true);
			}
			
			api::callHooks(API_HOOK_AFTER,
				'users::check', $this, $record);
			
			return true;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'users::check', $this);
		
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
			" AND `FromIP` = '".security::ip2long((string)$_SERVER['REMOTE_ADDR'])."'" .
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
		
		api::callHooks(API_HOOK_BEFORE,
			'users::addRequest', $this, $values);
		
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
			" `FromIP` = '".security::ip2long((string)$_SERVER['REMOTE_ADDR'])."'");
			
		$result = (sql::affected() != -1);
		
		if (!$result)
			tooltip::display(
				sprintf(__("Request couldn't be stored! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		else
			$result = $newrequestid;
		
		api::callHooks(API_HOOK_AFTER,
			'users::addRequest', $this, $values, $result);
		
		return $result;
	}
	
	function getRequest($requestid) {
		if (!$requestid)
			return null;
		
		return sql::fetch(sql::run(
			" SELECT * FROM `{userrequests}` " .
			" WHERE `RequestID` = BINARY '".sql::escape($requestid)."'" .
			" AND `FromIP` = '".security::ip2long((string)$_SERVER['REMOTE_ADDR'])."'" .
			" LIMIT 1"));
	}
	
	function cleanUpRequests() {
		// Delete requests older than 3 hours
		sql::run(
			" DELETE FROM `{userrequests}`" .
			" WHERE `TimeStamp` < DATE_SUB(NOW(), INTERVAL 3 HOUR)");
	}
	
	static function activate($id, $groupids = null) {
		if (!$id)
			return false;
		
		$user = users::get((int)$id);
		
		if ($user['Password'])
			return true;
		
		if (JCORE_VERSION >= '0.8' && 
			$groupids && !in_array($user['GroupID'], (array)$groupids))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'users::activate', $_ENV, $id);
		
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
			$password = security::genHash($newpassword);
		}
		
		sql::run(
			" UPDATE `{users}` SET" .
			" `Password` = '".sql::escape($password)."'," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".(int)$id."'");
		
		$result = (sql::affected() != -1);
		
		if (!$result) {
			tooltip::display(
				sprintf(__("User couldn't be activated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			
		} else if ($newpassword) {
			$email = new email();
			$email->load('UserRegistration');
			$email->toUserID = (int)$id;
			$email->variables['Password'] = $newpassword;
			$email->send();
			unset($email);
		}
		
		api::callHooks(API_HOOK_AFTER,
			'users::activate', $_ENV, $id, $result);
		
		return $result;
	}
	
	static function suspend($id, $groupids = null) {
		if (!$id)
			return false;
			
		$user = users::get((int)$id);
		if (JCORE_VERSION >= '0.8' && 
			$groupids && !in_array($user['GroupID'], (array)$groupids))
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'users::suspend', $_ENV, $id);
		
		sql::run(
			" UPDATE `{users}` SET" .
			" `Suspended` = 1," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".(int)$id."'");
			
		$result = (sql::affected() != -1);
		
		if (!$result)
			tooltip::display(
				sprintf(__("User couldn't be suspended! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
		
		api::callHooks(API_HOOK_AFTER,
			'users::suspend', $_ENV, $id, $result);
		
		return $result;
	}
	
	static function fastCheck($column = null) {
		if (!isset($_COOKIE['memberloginid']) || !$_COOKIE['memberloginid'])
			return false;
		
		$record = sql::fetch(sql::run(
			" SELECT * FROM `{userlogins}`" .
			" WHERE `SessionID` = BINARY '".sql::escape($_COOKIE['memberloginid'])."'" .
			" AND `FromIP` = '".security::ip2long((string)$_SERVER['REMOTE_ADDR'])."'" .
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
			" WHERE `Suspended` = 0" .
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
		
		api::callHooks(API_HOOK_BEFORE,
			'users::requestPassword', $this, $values);
		
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
					'Data' => security::genHash($newpassword)));
				
			} else {
				$newid = $this->addRequest(array(
					'UserID' => $user['ID'],
					'RequestTypeID' => REQUEST_TYPE_NEW_PASSWORD));
			}
			
			if (!$newid) {
				api::callHooks(API_HOOK_AFTER,
					'users::requestPassword', $this, $values);
				
				return false;
			}
				
			$email->variables = $user;
			$email->variables['RequestID'] = $newid;
			$email->variables['RequestURL'] = 
				str_replace('&amp;', '&', url::get('request, requestid, requestpassword')).
				"&request=users&requestid=".$newid;
			
			if (JCORE_VERSION >= '0.6')
				$email->variables['NewPassword'] = $newpassword;
			
			$email->to = $user['UserName']." <".
				$user['Email'].">";
			
			if (!$email->send()) {
				api::callHooks(API_HOOK_AFTER,
					'users::requestPassword', $this, $values);
				
				return false;
			}
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
		
		api::callHooks(API_HOOK_AFTER,
			'users::requestPassword', $this, $values, $newid);
		
		return true;
	}
	
	function request() {
		$requestid = null;
		
		if (isset($_GET['requestid']))
			$requestid = strip_tags((string)$_GET['requestid']);
		
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
		
		api::callHooks(API_HOOK_BEFORE,
			'users::request', $this);
		
		switch($request['RequestTypeID']) {
			case REQUEST_TYPE_NEW_ACCOUNT:
				sql::run(
					" UPDATE `{users}` SET" .
					" `Password` = '".sql::escape($request['Data'])."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".$user['ID']."'");
					
				$result = (sql::affected() != -1);
				
				if (!$result)
					tooltip::display(
						sprintf(__("Your account couldn't be" .
							" activated! Error: %s"), 
							sql::error()),
						TOOLTIP_ERROR);
				else
					tooltip::display(
						__("<b>Your Account has been Activated</b><br />" .
							" Thank you for registering."),
						TOOLTIP_SUCCESS);
				
				api::callHooks(API_HOOK_AFTER,
					'users::request', $this, $result);
				
				return true;
			
			case REQUEST_TYPE_NEW_PASSWORD:
				if (JCORE_VERSION >= '0.6') {
					sql::run(
						" UPDATE `{users}` SET" .
						" `Password` = '".sql::escape($request['Data'])."'," .
						" `TimeStamp` = `TimeStamp`" .
						" WHERE `ID` = '".$user['ID']."'");
						
					$result = (sql::affected() != -1);
					
					if (!$result)
						tooltip::display(
							sprintf(__("Your new password couldn't be" .
								" activated! Error: %s"), 
								sql::error()),
							TOOLTIP_ERROR);
					else
						tooltip::display(
							__("<b>Your New Password has been Activated</b><br />" .
								" Please copy and paste your new password" .
								" from the email you received to prevent" .
								" any misspelling issues."),
							TOOLTIP_SUCCESS);
					
					api::callHooks(API_HOOK_AFTER,
						'users::request', $this, $result);
					
					return true;
				}
				
				$password = security::genPassword($user['Email']);
				
				sql::run(
					" UPDATE `{users}` SET" .
					" `Password` = '".sql::escape(security::genHash($password))."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".$user['ID']."'");
				
				$result = (sql::affected() != -1);
				
				if (!$result) {
					tooltip::display(
						sprintf(__("Your new password couldn't be" .
							" activated! Error: %s"), 
							sql::error()),
						TOOLTIP_ERROR);
					
				} else {
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
				}
				
				api::callHooks(API_HOOK_AFTER,
					'users::request', $this, $result);
				
				return true;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'users::request', $this);
		
		return true;
	}
	
	function ajaxRequest() {
		api::callHooks(API_HOOK_BEFORE,
			'users::ajaxRequest', $this);
		
		$login = null;
		
		if (isset($_GET['quicklogin']))
			$login = (int)$_GET['quicklogin'];
		
		$result = false;
		if ($login) {
			$this->displayQuickLogin();
			$result = true;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'users::ajaxRequest', $this, $result);
		
		return $result;
	}
	
	function constructUserName($row, $format = null) {
		api::callHooks(API_HOOK_BEFORE,
			'users::constructUserName', $this, $row, $format);
		
		$username = $row['UserName'];
		
		if (strpos($format, '%s') === false)
			$format .= " %s";
		
		if (isset($row['DisplayUserName']) && $row['DisplayUserName'])
			$username = $row['DisplayUserName'];
		
		if (isset($GLOBALS['ADMIN']) && (bool)$GLOBALS['ADMIN'])
			$result =
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
		
		else if (!isset($row['Website']) || !$row['Website'])
			$result =
				"<span class='user-name'>" .
					sprintf($format, $username) .
				"</span>";
		
		else
			$result =
				"<span class='user-name'>" .
					sprintf($format,
						"<a class='user-name-link' href='".
							htmlspecialchars($row['Website'], ENT_QUOTES).
							"' rel='nofollow' target='_blank'>".
							$username .
						"</a>") .
				"</span>";
		
		api::callHooks(API_HOOK_AFTER,
			'users::constructUserName', $this, $row, $format, $result);
		
		return $result;
	}
	
	function setupQuickAccountForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'users::setupQuickAccountForm', $this, $form);
		
		$referer = url::referer(true);
		
		if(strpos($referer, '?') !== false)
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
		
		api::callHooks(API_HOOK_AFTER,
			'users::setupQuickAccountForm', $this, $form);
	}
	
	function setupQuickLoginForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'users::setupQuickLoginForm', $this, $form);
		
		$form->add(
			__('Username'),
			'member',
			FORM_INPUT_TYPE_TEXT);
		
		$form->add(
			__('Password'),
			'password',
			FORM_INPUT_TYPE_PASSWORD);
		
		api::callHooks(API_HOOK_AFTER,
			'users::setupQuickLoginForm', $this, $form);
	}
	
	
	function setupLoginForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'users::setupLoginForm', $this, $form);
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'users::setupLoginForm', $this, $form);
	}
	
	function setupRequestPasswordForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'users::setupRequestPasswordForm', $this, $form);
		
		$form->add(
			__("Please enter the email address you have provided at registration / join."),
			null,
			FORM_STATIC_TEXT);
		
		$form->add(
			__('Email address'),
			'Email',
			FORM_INPUT_TYPE_EMAIL,
			true);
		
		api::callHooks(API_HOOK_AFTER,
			'users::setupRequestPasswordForm', $this, $form);
	}
	
	function displayAvatar($useridoremail = null, $size = null, $default = null) {
		if (defined('AVATARS_DISABLED') && AVATARS_DISABLED)
			return false;
		
		api::callHooks(API_HOOK_BEFORE,
			'users::displayAvatar', $this, $useridoremail, $size, $default);
		
		if (!isset($useridoremail) && $this->loginok)
			$useridoremail = $this->data['Email'];
		
		if (!$size && defined('AVATARS_SIZE') && AVATARS_SIZE)
			$size = (int)AVATARS_SIZE;
		
		if (!$size)
			$size = 64;
		
		if (!$default && defined('DEFAULT_AVATAR_URL') && DEFAULT_AVATAR_URL) {
			$default = DEFAULT_AVATAR_URL;
			
			if (strpos($default, '/') === 0)
				$default = url::site().$default;
			elseif (strpos($default, 'http://') !== 0 && strpos($default, 'https://') !== 0)
				$default = TEMPLATE_URL.$default;
		}
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayAvatar', $this, $useridoremail, $size, $default);
		
		return true;
	}
	
	function displayQuickList($target = null, $multiple = false, $format = '%UserName%', $separator = ',') {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayQuickList', $this, $target, $multiple, $format, $separator);
		
		$search = null;
		
		if (isset($_POST['ajaxsearch']))
			$search = trim(strip_tags((string)$_POST['ajaxsearch']));
		
		if (isset($_GET['ajaxsearch']))
			$search = trim(strip_tags((string)$_GET['ajaxsearch']));
		
		if (!isset($search) && !isset($_GET['ajaxlimit']))
			echo 
				"<div class='select-users-list'>";
			
		echo
				"<div class='select-users-list-search' " .
					"style='margin-right: 20px;'>" .
					"<form action='".url::uri('ajaxsearch, ajaxlimit, ajax')."' method='post' " .
						"class='ajax-form' " .
						"target='.select-users-list'>" .
					__("Search").": " .
					"<input type='search' " .
						"name='ajaxsearch' " .
						"value='".
							htmlspecialchars($search, ENT_QUOTES).
						"' results='5' placeholder='".htmlspecialchars(__("search..."), ENT_QUOTES)."' " .
						"autofocus='autofocus' />" .
					"</form>" .
				"</div>" .
				"<br />" .
				"<table cellpadding='0' cellspacing='0' class='list'>" .
					"<thead>" .
					"<tr>" .
						($target?
							"<th>" .
								"<span class='nowrap'>".
								($multiple?
									__("Add"):
									__("Select")).
								"</span>" .
							"</th>":
							null) .
						"<th>" .
							"<span class='nowrap'>".
							__("Username").
							"</span>" .
						"</th>" .
						"<th style='text-align: right;'>" .
							"<span class='nowrap'>".
							__("Admin").
							"</span>" .
						"</th>" .
						"<th style='text-align: right;'>" .
							"<span class='nowrap'>".
							__("Email").
							"</span>" .
						"</th>" .
						"<th style='text-align: right;'>" .
							"<span class='nowrap'>".
							__("Registered on").
							"</span>" .
						"</th>" .
					"</tr>" .
					"</thead>" .
					"<tbody>";
					
		$paging = new paging(10,
			'&amp;ajaxsearch='.urlencode($search));
		
		$paging->track('ajaxlimit');
		$paging->ajax = true;
		
		$rows = sql::run(
			" SELECT * FROM `{users}`" .
			" WHERE 1" .
			($search?
				" AND (`UserName` LIKE '%".sql::escape($search)."%' " .
				" 	OR `Email` LIKE '%".sql::escape($search)."%') ":
				null) .
			" ORDER BY `Admin` DESC, `ID` DESC" .
			" LIMIT ".$paging->limit);
		
		$paging->setTotalItems(sql::count());
		
		$i = 1;
		$total = sql::rows($rows);
		
		preg_match_all('/%([a-zA-Z0-9\_\-\.]+)%/', $format, $formatkeys);
		$formatkeys = $formatkeys[1];
		
		while ($row = sql::fetch($rows)) {
			$formatedvalue = $format;
			
			foreach($formatkeys as $formatkey)
				$formatedvalue = str_replace(
					"%".$formatkey."%", $row[$formatkey], $formatedvalue);
			
			echo
				"<tr".($i%2?" class='pair'":NULL).">" .
					($target?
						"<td align='center'>" .
							"<a href='javascript://' " .
								($multiple?
									"onclick=\"" .
										"$('".$target."').val(" .
											"$('".$target."').val()+" .
											"($('".$target."').val()?'" .
												htmlspecialchars($separator, ENT_QUOTES)." ':'')+" .
											"'".$formatedvalue."');" .
											"$(this).closest('.select-users-list').find('input[type=search]').first().focus();" .
										"\" class='add-link'>":
									"onclick='$(\"".$target."\")" .
										".val(\"".$formatedvalue."\");" .
										(JCORE_VERSION >= '0.7'?
											"$(this).closest(\".tipsy\").hide();":
											"$(this).closest(\".qtip\").qtip(\"hide\");") .
										"' class='select-link'>") .
								($multiple && JCORE_VERSION < '0.6'?
									"&nbsp;+&nbsp;":
									null) .
								(!$multiple && JCORE_VERSION < '0.7.1'?
									"&nbsp;o&nbsp;":
									null) .
							"</a>" .
						"</td>":
						null) .
					"<td class='auto-width'>" .
						"<b>" .
						$row['UserName'] .
						"</b>" .
					"</td>" .
					"<td style='text-align: right;'>" .
						($row['Admin']?
							_('Yes'):
							null).
					"</td>" .
					"<td style='text-align: right;'>" .
						"<a href='mailto:".$row['Email']."'>" .
							$row['Email'] .
						"</a>" .
					"</td>" .
					"<td style='text-align: right;'>" .
						"<span class='nowrap'>" .
						calendar::date($row['TimeStamp']) .
						"</span>" .
					"</td>" .
				"</tr>";
			
			$i++;
		}
		
		echo
					"</tbody>" .
				"</table>" .
				"<br />";
				
		$paging->display();
		
		if (!isset($search) && !isset($_GET['ajaxlimit']))
			echo
				"</div>";
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayQuickList', $this, $target, $multiple, $format, $separator);
	}
	
	function displayQuickAccountForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayQuickAccountForm', $this, $form);
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayQuickAccountForm', $this, $form);
	}
	
	function displayQuickAccount() {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayQuickAccount', $this);
		
		$form = new form(
			__('Quick Account'),
			'quickaccount');
		
		$form->action = url::referer(true);
		$form->footer = '';
		
		$this->setupQuickAccountForm($form);
		$this->displayQuickAccountForm($form);
		unset($form);
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayQuickAccount', $this);
	}
	
	function displayQuickLoginForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayQuickLoginForm', $this, $form);
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayQuickLoginForm', $this, $form);
	}
	
	function displayQuickLogin() {
		if ($GLOBALS['USER']->loginok) {
			$this->displayQuickAccount();
			return true;
		}
		
		api::callHooks(API_HOOK_BEFORE,
			'users::displayQuickLogin', $this);
		
		$referer = url::referer(true);
		
		if(strpos($referer, '?') !== false)
			$requestlink = $referer.'&amp;requestpassword=1';
		else
			$requestlink = $referer.'?requestpassword=1';
		
		$form = new form(
			__('Quick Login'),
			'quicklogin');
		
		$form->footer = '';
		$form->action = $referer.
			(isset($_GET['anchor'])?
				"#".strip_tags((string)$_GET['anchor']):
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
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayQuickLogin', $this);
		
		return true;
	}
	
	function displayLoginForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayLoginForm', $this, $form);
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayLoginForm', $this, $form);
	}
	
	function displayLogin() {
		if (requests::$ajax)
			return $this->displayQuickLogin();
		
		api::callHooks(API_HOOK_BEFORE,
			'users::displayLogin', $this);
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayLogin', $this);
		
		return true;
	}
	
	function displayRequestPasswordForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayRequestPasswordForm', $this, $form);
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayRequestPasswordForm', $this, $form);
	}
	
	function displayRequestPassword() {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayRequestPassword', $this);
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayRequestPassword', $this);
		
		return true;
	}
	
	function displayResult() {
		if (!$this->verifyError && !$this->result)
			return;
		
		api::callHooks(API_HOOK_BEFORE,
			'users::displayResult', $this);
		
		if ($this->result) {
			echo $this->result;
			unset($this->result);
			
			api::callHooks(API_HOOK_AFTER,
				'users::displayResult', $this);
			
			return;
		}
			
		$bfprotection = new BFProtection();
		$bfprotection->get(strip_tags((string)$_SERVER['REMOTE_ADDR']));
		
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
					"password and/or you are logged out from your other PCs before trying " .
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
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayResult', $this);
	}
	
	function displayUserName($row, $format = null) {
		api::callHooks(API_HOOK_BEFORE,
			'users::displayUserName', $this, $row, $format);
		
		echo $this->constructUserName($row, $format);
		
		api::callHooks(API_HOOK_AFTER,
			'users::displayUserName', $this, $row, $format);
	}
	
	function display($login = false) {
		api::callHooks(API_HOOK_BEFORE,
			'users::display', $this, $login);
		
		$this->displayResult();
		
		$requestpassword = null;
		
		if (isset($_GET['requestpassword']))
			$requestpassword = (int)$_GET['requestpassword'];
		
		if (isset($_GET['login']) || isset($_GET['quicklogin']) || $login)
			$login = true;
		
		if ($requestpassword)
			$this->displayRequestPassword();
			
		elseif ($login)
			$this->displayLogin();
		
		api::callHooks(API_HOOK_AFTER,
			'users::display', $this, $login);
	}
}

?>