<?php

/***************************************************************************
 * 
 *  Name: Members Module
 *  URI: http://jcore.net
 *  Description: Allows members to register/update their accounts.
 *  Author: Istvan Petres
 *  Version: 0.8
 *  Tags: members module, gpl
 * 
 ****************************************************************************/

modules::register(
	'members',
	_('Site Members'), 
	_('Forms for My Account, Register and Login pages'));

class memberAccountForm extends dynamicForms {
	function __construct() {
		languages::load('members');
		
		parent::__construct(
			_('Member Account'), 'memberaccount');
	}
	
	function __destruct() {
		languages::unload('members');
	}
	
	function load($addformbuttons = true) {
		parent::load($addformbuttons);
		
		$this->setTooltipText('Password', 
			sprintf(__("minimum %s characters"), MINIMUM_PASSWORD_LENGTH));
		
		if (defined('USERNAME_CHANGES_DISABLED') && USERNAME_CHANGES_DISABLED)
			$this->edit('UserName', null, null, FORM_INPUT_TYPE_REVIEW);
	}
	
	function verify($customdatahandling = false) {
		if (!parent::verify(true))
			return false;
		
		$postarray = $this->getPostArray();
		
		if (defined('USERNAME_CHANGES_DISABLED') && USERNAME_CHANGES_DISABLED)
			$postarray['UserName'] = $GLOBALS['USER']->data['UserName'];
		
		if (!$GLOBALS['USER']->edit($GLOBALS['USER']->data['ID'], $postarray))
			return false;
			 
		tooltip::display(
			_("Your account has been successfully updated."),
			TOOLTIP_SUCCESS);
		
		return true;
	}
}

class memberRegistrationForm extends dynamicForms {
	function __construct() {
		languages::load('members');
		
		parent::__construct(
			_('Member Registration'), 'memberregistration');
	}
	
	function __destruct() {
		languages::unload('members');
	}
	
	function load($addformbuttons = true) {
		parent::load($addformbuttons);
		
		$this->setTooltipText('Password', 
			sprintf(__("minimum %s characters"), MINIMUM_PASSWORD_LENGTH));
	}
	
	function verify($customdatahandling = false) {
		if (!parent::verify(true))
			return false;
			
		if (!$GLOBALS['USER']->add($this->getPostArray()))
			return false;
		
		if (JCORE_VERSION >= '0.6' && (!$GLOBALS['USER']->loginok ||
			!$GLOBALS['USER']->data['Admin']))
			tooltip::display(
				_("<b>Thank you for your registration.</b><br />" .
					" Your account has been created. However we require" .
					" account activation, an activation key has been sent" .
					" to the e-mail address you provided. Please check" .
					" your e-mail for further information."),
				TOOLTIP_SUCCESS);
		else 
			tooltip::display(
				_("<b>Thank you for your registration.</b><br />" .
					" An email has been sent to you with the necessary" .
					" information to login to your account."),
				TOOLTIP_SUCCESS);
		
		tooltip::display(
			__("<b>IMPORTANT:</b> Some email providers put messages" .
				" received from addresses, which are not in your contact list, in" .
				" the \"Bulk / Junk E-Mail\" folders. Please check those folders" .
				" too."),
			TOOLTIP_NOTIFICATION);
		
		$this->reset();
		return true;
	}
}

class members extends modules {
	var $selectedID;
	var $adminPath = 'admin/modules/members';
	
	function __construct() {
		languages::load('members');
	}
	
	function __destruct() {
		languages::unload('members');
	}
	
	function installSQL() {
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicforms}` " .
			" WHERE `FormID` = 'memberregistration';"));
		
		if (sql::display())
			return false;
		
		$formid = $exists['ID'];
			
		if (!$exists) {
			$formid = sql::run(
				" INSERT INTO `{dynamicforms}` " .
				" (`Title`, `FormID`, `Method`, `SendNotificationEmail`, `SQLTable`, `Protected`, `ProtectedSQLTable`, `BrowseDataURL`) VALUES" .
				" ('Member Registration', 'memberregistration', 'post', 0, 'users', 1, 1, '?path=admin/members/users');");
			
			if (sql::display())
				return false;
		}
		
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicformfields}` " .
			" WHERE `FormID` = '".$formid."'" .
			" AND `Protected`;"));
		
		if (sql::display())
			return false;
		
		if (!$exists) {
			sql::run(
				" INSERT INTO `{dynamicformfields}` " .
				" (`FormID`, `Title`, `Name`, `TypeID`, `ValueType`, `Required`, `PlaceholderText`, `TooltipText`, `AdditionalText`, `Attributes`, `Style`, `OrderID`, `Protected`) VALUES" .
				" (".$formid.", 'Username', 'UserName', 1, 1, 1, '', '', '', '', 'width: 200px;', 1, 1)," .
				" (".$formid.", 'Email address', 'Email', 2, 1, 1, '', '', '', '', 'width: 300px;', 2, 1)," .
				" (".$formid.", 'Password', 'Password', 20, 1, 1, '', '', '', '', 'width: 150px;', 3, 1)," .
				" (".$formid.", 'Retype password', 'RePassword', 21, 1, 1, '', '', '', '', 'width: 150px;', 4, 1)," .
				" (".$formid.", 'Verification code', '', 11, 1, 1, '', '', '', '', '', 5, 0)," .
				" (".$formid.", 'Please note that you will need to enter a valid e-mail address before your account is activated. You will receive an e-mail at the address you provided that contains an account activation link.', '', 18, 0, 0, '', '', '', '', '', 6, 0);");
			
			if (sql::display())
				return false;
		}
			
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicforms}` " .
			" WHERE `FormID` = 'memberaccount';"));
		
		if (sql::display())
			return false;
			
		$formid = $exists['ID'];
			
		if (!$exists) {
			$formid = sql::run(
				" INSERT INTO `{dynamicforms}` " .
				" (`Title`, `FormID`, `Method`, `SendNotificationEmail`, `SQLTable`, `Protected`, `ProtectedSQLTable`, `BrowseDataURL`) VALUES" .
				" ('Member Account', 'memberaccount', 'post', 0, 'users', 1, 1, '?path=admin/members/users');");
		
			if (sql::display())
				return false;
		}
			
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicformfields}` " .
			" WHERE `FormID` = '".$formid."'" .
			" AND `Protected`;"));
		
		if (sql::display())
			return false;
		
		if (!$exists) {
			sql::run(
				" INSERT INTO `{dynamicformfields}` " .
				" (`FormID`, `Title`, `Name`, `TypeID`, `ValueType`, `Required`, `PlaceholderText`, `TooltipText`, `AdditionalText`, `Attributes`, `Style`, `OrderID`, `Protected`) VALUES" .
				" (".$formid.", 'Username', 'UserName', 1, 1, 1, '', '', '', '', 'width: 200px;', 1, 1)," .
				" (".$formid.", 'Email address', 'Email', 2, 1, 1, '', '', '', '', 'width: 300px;', 2, 1)," .
				" (".$formid.", 'Website', 'Website', 1, 1, 0, '', '', '', '', 'width: 350px;', 3, 1)," .
				" (".$formid.", 'Show Avatar', 'ShowAvatar', 3, 10, 1, '', '', '(<a href=''http://gravatar.com'' target=''_blank''>Change Avatar</a>)', '', '', 4, 1)," .
				" (".$formid.", 'Stay Logged In', 'StayLoggedIn', 3, 10, 0, '', '', '', '', '', 5, 1)," .
				" (".$formid.", 'Disable Notification Emails', 'DisableNotificationEmails', 3, 10, 0, '', '', '', '', '', 6, 1)," .
				" (".$formid.", 'Change password', '', 13, 0, 0, '', '', '', '', '', 7, 0)," .
				" (".$formid.", 'New password', 'Password', 20, 1, 0, '', '', '', '', 'width: 200px;', 8, 1)," .
				" (".$formid.", 'Retype password', 'RePassword', 21, 1, 0, '', '', '', '', 'width: 200px;', 9, 1)," .
				" (".$formid.", ' ', '', 14, 0, 0, '', '', '', '', '', 10, 0);");
		
			if (sql::display())
				return false;
		}
		
		return true;
	}
	
	function installFiles() {
		$css = 
			".as-modules-members a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/user_auth.png\");\n" .
			"}\n";
		
		if (!files::save(SITE_PATH.'template/modules/css/members.css', $css, true)) {
			tooltip::display(
				__("Could NOT write css file.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"template/modules/css/"),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	// ************************************************   Admin Part
	function setupAdmin() {
		favoriteLinks::add(
			_('Form Settings'), 
			'?path=admin/content/dynamicforms');
		favoriteLinks::add(
			__('Settings'), 
			'?path=admin/site/settings');
	}
	
	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				__("Title / Form ID")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
		echo
			"<th><span class='nowrap'>".
				__("Fields")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
		echo
			"<td class='auto-width'>" .
				"<div class='bold'>" .
					_($row['Title']) .
				"</div>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					$row['FormID'] .
				"</div>" .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
		$fields = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{dynamicformfields}`" .
			" WHERE `FormID` = '".$row['ID']."'" .
			" LIMIT 1"));
		
		echo
			"<td align='center'>" .
				"<a class='admin-link fields' " .
					"title='".htmlspecialchars(__("Fields"), ENT_QUOTES) .
					" (".$fields['Rows'].")' " .
					"href='".url::uri('ALL') .
					"?path=admin/content/dynamicforms/".$row['ID']."/dynamicformfields'>" .
					(ADMIN_ITEMS_COUNTER_ENABLED && $fields['Rows']?
						"<span class='counter'>" .
							"<span>" .
								"<span>" .
								$fields['Rows']."" .
								"</span>" .
							"</span>" .
						"</span>":
						null) .
				"</a>" .
			"</td>";
	}
	
	function displayAdminList(&$rows) {
		echo 
			"<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>";
		
		$this->displayAdminListHeader();
		$this->displayAdminListHeaderOptions();
		
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
			
			echo
				"</tr>";
			
			$i++;
		}
		
		echo 
				"</tbody>" .
			"</table>" .
			"<br />";
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			_('Member Forms Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		if (modules::displayAdmin())
			return;
		
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
				
		$rows = sql::run(
			" SELECT * FROM `{dynamicforms}`" .
			" WHERE `FormID` IN ('memberregistration', 'memberaccount')" .
			" ORDER BY `Title`");
		
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				_("No member forms found."),
				TOOLTIP_NOTIFICATION);
		
		echo
			"</div>"; //admin-content
	}
	
	// ************************************************   Client Part
	static function getTree() {
		return 
			array(
				array(
					'ID' => 1,
					'SubItemOfID' => 0,
					'PathDeepnes' => 0,
					'Title' => _("Member Registration Form")),
				array(
					'ID' => 2,
					'SubItemOfID' => 0,
					'PathDeepnes' => 0,
					'Title' => _("Member Login / Account Form")));
	}
	
	function displayRegistration() {
		if (defined('REGISTRATIONS_SUSPENDED') && REGISTRATIONS_SUSPENDED) {
			tooltip::display(
				_("New account registration has been temporarily " .
					"suspended. Please try again later."),
				TOOLTIP_NOTIFICATION);
		
			return false;
		}
		
		$form = new memberRegistrationForm();
		$form->load();
		$form->verify();
		$form->display();
		unset($form);
		return;
	}
	
	function displayAccount() {
		if (!$GLOBALS['USER']->loginok) {
			$GLOBALS['USER']->displayLogin();
			return;
		}
		
		$form = new memberAccountForm();
		$form->load();
		$form->verify();
		
		foreach($form->elements as $element) {
			if ($element['Type'] != FORM_INPUT_TYPE_PASSWORD && 
				isset($GLOBALS['USER']->data[$element['Name']]))
				$form->setValue($element['Name'], 
					$GLOBALS['USER']->data[$element['Name']]);
		}
		
		$form->display();
		unset($form);
	}
	
	function displayLogin() {
		$GLOBALS['USER']->displayLogin();
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		preg_match('/(.*?)(\/|$)(.*)/', $this->arguments, $matches);
		
		$argument = null;
		$parameters = null;
		
		if (isset($matches[1]))
			$argument = $matches[1];
			
		if (isset($matches[3]))
			$parameters = $matches[3];
			
		switch(strtolower($argument)) {
			case 'registration':
				$this->displayRegistration();
				return true;
		
			case 'account':
				$this->displayAccount();
				return true;
			
			case 'login':
				$this->displayLogin();
				return true;
			
			case 'user':
				if ($parameters == 'Password')
					return true;
				
				if ($parameters == 'username') {
					$GLOBALS['USER']->displayUserName($GLOBALS['USER']->data);
					return true;
				}
				
				if (isset($GLOBALS['USER']->data[$parameters]))
					echo $GLOBALS['USER']->data[$parameters];
				
				return true;
			
			default:
				return true;
		}
	}
	
	function display() {
		if ($this->displayArguments())
			return;
			
		if ($this->selectedID) {
			switch($this->selectedID) {
				case 2:
					$this->displayAccount();
					break;
				default:
					$this->displayRegistration();
			}
			
			return;
		}
		
		if ($this->owner['ViewableBy'] == PAGE_GUESTS_ONLY) {
			$this->displayRegistration();
			return;
		}
		
		$this->displayAccount();
	}
}
 
?>