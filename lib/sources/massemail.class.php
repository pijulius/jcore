<?php

/***************************************************************************
 *            massemail.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
include_once('lib/email.class.php');

class _massEmail {
	var $adminPath = 'admin/members/massemail';
	var $ajaxRequest = null;
	
	// ************************************************   Admin Part
	static function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{massemails}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Email'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Users'), 
			'?path=admin/members/users');
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			__('Preview'),
			'Preview',
			FORM_INPUT_TYPE_HIDDEN,
			true,
			1);
		
		$form->add(
			__('From'),
			'From',
			FORM_INPUT_TYPE_TEXT,
			true,
			PAGE_TITLE." <".WEBMASTER_EMAIL.">");
		$form->setStyle("width: 300px;");
		$form->setValueType(FORM_VALUE_TYPE_HTML);
		
		$form->add(
			__('To'),
			'To',
			FORM_INPUT_TYPE_TEXTAREA,
			true);
		$form->setStyle("width: 300px; height: 30px;");
		$form->setValueType(FORM_VALUE_TYPE_HTML);
		
		$tolinks = 
			"<a href='javascript://' " .
				"class='mass-email-active-users' " .
				"onclick=\"jQuery('#newemailform #entryTo').val('<ACTIVE-USERS>');\">" .
				__("Active Users") .
			"</a>" .
			"<a href='javascript://' " .
				"class='mass-email-all-users' " .
				"onclick=\"jQuery('#newemailform #entryTo').val('<ALL-USERS>');\">" .
				__("All Users") .
			"</a>" .
			"<a href='".url::uri('request, users') .
				"&amp;request=".$this->adminPath .
				"&amp;users=1' " .
				"class='mass-email-select-users ajax-content-link' " .
				"title='".htmlspecialchars(__("Add User(s)"), ENT_QUOTES)."'>" .
				__("Add User(s)") .
			"</a>";
		
		if (JCORE_VERSION >= '0.7')
			$form->addAdditionalText(
				"<br />".$tolinks);
		else
			$form->addAdditionalPreText(
				$tolinks .
				"<div style='height: 20px;'></div>");
			
		$form->add(
			'<div class="spacer"></div>',
			'',
			FORM_STATIC_TEXT);
		
		$form->add(
			__('Subject'),
			'Subject',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle("width: 350px;");
		
		$form->add(
			__('Message'),
			'Message',
			FORM_INPUT_TYPE_TEXTAREA,
			true);
		$form->setStyle("width: 350px; height: 200px;");
		
		$form->add(
			__('Partial sending (split your emails into chunks)'),
			'',
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Number of Emails'),
			'LimitEmails',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle("width: 50px;");
		$form->setValueType(FORM_VALUE_TYPE_INT);
		$form->setTooltipText(__("e.g. 500"));
		
		$form->add(
			__('Starting from'),
			'LimitFrom',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle("width: 50px;");
		$form->setValueType(FORM_VALUE_TYPE_INT);
		$form->setTooltipText(__("e.g. 100"));
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
	}
	
	function verifyAdmin(&$form) {
		$continue = null;
		$delete = null;
		$id = null;
		$newemailid = null;
		
		if (isset($_POST['continue']))
			$continue = $_POST['continue'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
			
			tooltip::display(
				__("Email has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		$fromemail = $form->get('From');
		preg_match('/<(.*)>/', $fromemail, $matches);
		
		if (isset($matches[1]))
			$fromemail = $matches[1];
		
		if (!email::verify($fromemail)) {
			tooltip::display(
				__("From email address is not a valid email address!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$emails = preg_split('/(,|;)/', $form->get('To'));
		$toemails = array();
		$invalidtoemails = array();
		$customtoemails = array();
		
		foreach($emails as $email) {
			preg_match('/<(.*)>/', $email, $matches);
			
			if (isset($matches[1]) && 
				($matches[1] == 'ALL-USERS' || 
				$matches[1] == 'ACTIVE-USERS'))
			{
				$toemails[] = $email;
				continue;
			}
			
			if (isset($matches[1]) && $matches[1])
				$emailaddress = $matches[1];
			else
				$emailaddress = $email;
			
			if (!email::verify(trim($emailaddress)))
				$invalidtoemails[] = $email;
			
			if (!$continue) {
				$customtoemails[] = $email;
				$toemails[] = $email;
			}
		}
		
		if (count($invalidtoemails)) {
			tooltip::display(
				sprintf(__("Invalid email addresses defined. " .
					"The following addresses are not valid email " .
					"addresses: %s."), 
					htmlspecialchars(implode(
						', ', $invalidtoemails))),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!$form->get('SendSubmit'))
			return false;
		
		if (JCORE_VERSION >= '0.2') {
			$newemailid = $this->add($form->getPostArray());
		
			if (!$newemailid)
				return false;
		}
		
		if (count($toemails) == count($customtoemails))
			$form->setValue('LimitEmails', 0);
		
		$limitemails = $form->get('LimitEmails');
		$limitfrom = $form->get('LimitFrom');
		
		if (!$limitemails)
			$limitemails = 100000;
				
		$email = new email();
		
		$email->from = $form->get('From');
		$email->subject = $form->get('Subject');
		$email->message = $form->get('Message');
		
		$emailstosend = 0;
		$emailssentout = 0;
		
		tooltip::display(
			($form->get('LimitEmails') && $form->get('LimitFrom')?
				sprintf(__("Sending %s emails starting from %s, please wait..."),
					$form->get('LimitEmails')+count($customtoemails),
					$form->get('LimitFrom')):
			($form->get('LimitEmails')?
				sprintf(__("Sending %s emails, please wait..."),
					$form->get('LimitEmails')+count($customtoemails)):
			($form->get('LimitFrom')?
				sprintf(__("Sending emails starting from %s, please wait..."),
					$form->get('LimitFrom')):
				__("Sending emails, please wait...")))) .
			"<div id='massemailsendstatus'></div>",
			TOOLTIP_NOTIFICATION);
		
		foreach($toemails as $toemail) {
			preg_match('/<(.*)>/', $toemail, $matches);
			
			if (isset($matches[1]) && $matches[1] == 'ALL-USERS') {
				$users = sql::run(
					" SELECT * FROM `{users}`" .
					" WHERE !`Suspended`" .
					" AND !`DisableNotificationEmails`" .
					" ORDER BY `ID`" .
					" LIMIT ".(int)$limitfrom.", ".(int)$limitemails);
				
				while($user = sql::fetch($users)) {
					$email->variables = $user;
					$email->to = $user['UserName']." <".
						$user['Email'].">";
					
					if ($email->send()) {
						$this->incEmailsSentOut($newemailid);
						$emailssentout++;
					}
					
					$emailstosend++;
				}
				
			} elseif (isset($matches[1]) && $matches[1] == 'ACTIVE-USERS') {
				$users = sql::run(
					" SELECT * FROM `{users}`" .
					" WHERE !`Suspended`" .
					" AND !`DisableNotificationEmails`" .
					" AND `LastVisitTimeStamp` > DATE_SUB(NOW(), INTERVAL 1 MONTH)" .
					" ORDER BY `ID`" .
					" LIMIT ".(int)$limitfrom.", ".(int)$limitemails);
				
				while($user = sql::fetch($users)) {
					$email->variables = $user;
					$email->to = $user['UserName']." <".
						$user['Email'].">";
					
					if ($email->send()) {
						$this->incEmailsSentOut($newemailid);
						$emailssentout++;
					}
					
					$emailstosend++;
				}
				
			} elseif (!$continue) {
				preg_match('/<(.*)>/', trim($toemail), $matches);
				
				if (isset($matches[1]))
					$onlyemail = $matches[1];
				else
					$onlyemail = trim($toemail);
		
				$user = sql::fetch(sql::run(
					" SELECT * FROM `{users}`" .
					" WHERE `Email` LIKE '".sql::escape($onlyemail)."'"));
				
				if ($user) {
					$email->variables = $user;
					
				} else {
					preg_match('/(.*?)(<|@)/', trim($toemail), $matches);
					
					if (isset($matches[1]))
						$email->variables = array(
							'UserName' => $matches[1]);
					else
						$email->variables = array();
				}
				
				$email->to = trim($toemail);
				
				if ($email->send()) {
					$this->incEmailsSentOut($newemailid);
					$emailssentout++;
				}
				
				$emailstosend++;
			}
		}
		
		unset($email);
		
		if (!$emailstosend && $form->get('LimitFrom')) {
			$this->delete($newemailid, true);
			$form->setValue('LimitEmails', 0);
			
			tooltip::display(
				__("All emails have been successfully sent. " .
					"No more emails left to be sent."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$emailssentout) {
			$this->delete($newemailid, true);
			
			tooltip::display(
				__("No emails have been sent out!")." " .
				(!$emailstosend?
					__("No users found to send email to."):
					__("Please see detailed error " .
						"messages above and try again.")),
				TOOLTIP_ERROR);
	
			return false;
		}
		
		tooltip::display(
			sprintf(
				__("Emails have been successfully sent to: %s. " .
					"Emails sent: %s"),
				htmlspecialchars(implode(
					', ', $toemails)),
				$emailssentout),
			TOOLTIP_SUCCESS);
		
		return true;
	}
	
	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				__("Subject / Sent out")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Emails Sent")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
	}
	
	function displayAdminListHeaderFunctions() {
		echo
			"<th><span class='nowrap'>".
				__("Resend")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
		echo
			"<td class='auto-width'>" .
				"<a href='".url::uri('id, resend, delete') .
					"&amp;id=".$row['ID']."' " .
					"class='bold'>".
					$row['Subject'] .
				"</a>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					calendar::dateTime($row['TimeStamp']) .
				"</div>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				$row['EmailsSentOut'] .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
	}
	
	function displayAdminListItemFunctions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='admin-link email-new' " .
					"title='".htmlspecialchars(__("Resend"), ENT_QUOTES)."' " .
					"href='".url::uri('id, resend, delete') .
					"&amp;id=".$row['ID']."&amp;resend=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, resend, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayAdminListItemSelected(&$row) {
		$user = $GLOBALS['USER']->get($row['UserID']);
		
		admin::displayItemData(
			__("Sent out by"),
			$GLOBALS['USER']->constructUserName($user));
		
		admin::displayItemData(
			__("From"),
			htmlspecialchars($row['From']));
		
		admin::displayItemData(
			__("To"),
			htmlspecialchars($row['To']));
		
		admin::displayItemData(
			__("Subject"),
			htmlspecialchars($row['Subject']));
		
		admin::displayItemData(
			"<hr />");
		admin::displayItemData(
			nl2br($row['Message']));
	}
	
	function displayAdminList(&$rows) {
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
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
	}
	
	function displayAdminPreview(&$form, &$emailssent) {
		if (JCORE_VERSION >= '0.6')
			echo
				"<div class='form rounded-corners'>";
		
		echo 
			"<div class='form-title rounded-corners-top'>" .
				($emailssent?
					__("Email Sent"):
					__("Preview Email")) .
			"</div>" .
			"<div class='" .
				(JCORE_VERSION >= '0.6'?
					"form-content":
					"form") .
				" rounded-corners-bottom'>" .
				"<div class='form-entry form-entry-From preview'>" .
					"<div class='form-entry-title bold'>" .
						__("From").":" .
					"</div>" .
					"<div class='form-entry-content'>".
						htmlspecialchars($form->get('From')).
					"</div>" .
				"</div>" .
				"<div class='form-entry form-entry-To preview'>" .
					"<div class='form-entry-title bold'>" .
						__("To").":" .
					"</div>" .
					"<div class='form-entry-content'>".
						htmlspecialchars($form->get('To')) .
					"</div>" .
				"</div>" .
				"<div class='form-entry form-entry-Subject preview'>" .
					"<div class='form-entry-title bold'>".
						__("Subject").":" .
					"</div>" .
					"<div class='form-entry-content'>".
						$form->get('Subject').
					"</div>" .
				"</div>";
		
		
		if ($form->get('LimitEmails') || $form->get('LimitFrom')) {
			echo
				"<div class='form-entry form-entry-Partialsending preview'>" .
					"<div class='form-entry-title bold'>".
						__("Partial sending").":" .
					"</div>" .
					"<div class='form-entry-content'>";
			
			if ($form->get('LimitEmails') && $form->get('LimitFrom'))
				echo
					sprintf(__("%s emails starting from %s"),
						$form->get('LimitEmails'),
						$form->get('LimitFrom'));
			elseif ($form->get('LimitEmails'))
				echo
					sprintf(__("%s emails"),
						$form->get('LimitEmails'));
			elseif ($form->get('LimitFrom'))
				echo
					sprintf(__("starting from %s"),
						$form->get('LimitFrom'));
			
			echo
					"</div>" .
				"</div>";
		}
		
		echo
				"<div class='form-entry form-entry-Message preview'>" .
					"<div class='admin-content-preview'>" .
						nl2br($form->get('Message')) .
					"</div>" .
				"</div>" .
			"</div>";
		
		if (JCORE_VERSION >= '0.6')
			echo
				"</div>";
		
		echo "<br />";
	}

	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Mass Email Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$resend = null;
		$id = null;
		
		if (isset($_GET['resend']))
			$resend = (int)$_GET['resend'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
				
		$form = new form(
			__("New Email"),
			'newemail');
		
		$form->action = url::uri('id, resend, delete');
		$this->setupAdminForm($form);
		
		if ($form->get('Preview'))
			$form->add(
				__('Send Email'),
				'SendSubmit',
				FORM_INPUT_TYPE_SUBMIT);
					
		$form->add(
			__('Preview'),
			'PreviewSubmit',
			FORM_INPUT_TYPE_SUBMIT);
					
		$form->add(
			__('Reset'),
			'Reset',
			FORM_INPUT_TYPE_RESET);
		
		if ($form->get('Preview')) {
			$form->add(
				__('Cancel'),
				'cancel',
				 FORM_INPUT_TYPE_BUTTON);
			$form->addAttributes("onclick=\"window.location='".
				str_replace('&amp;', '&', url::uri('id, resend, delete'))."'\"");
		}
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			$emailssent = $this->verifyAdmin($form);
	
		if ($form->get('PreviewSubmit') || $form->get('SendSubmit')) {
			$this->displayAdminPreview($form, $emailssent);
			
			if ($emailssent) {
				echo
					"<form action='".url::uri()."' method='post'>";
				
				if ($form->get('LimitEmails')) {
					echo
						"<input type='hidden' name='continue' value='1' />" .
						"<input type='hidden' name='From' value='" .
							htmlspecialchars($form->get('From'), ENT_QUOTES)."' />" .
						"<input type='hidden' name='To' value='" .
							htmlspecialchars($form->get('To'), ENT_QUOTES)."' />" .
						"<input type='hidden' name='Subject' value='" .
							htmlspecialchars($form->get('Subject'), ENT_QUOTES)."' />" .
						"<input type='hidden' name='Message' value='" .
							htmlspecialchars($form->get('Message'), ENT_QUOTES)."' />" .
						"<input type='hidden' name='LimitEmails' value='" .
							$form->get('LimitEmails')."' />" .
						"<input type='hidden' name='LimitFrom' value='" .
							(int)($form->get('LimitFrom')+$form->get('LimitEmails'))."' />" .
						"<input type='hidden' name='Preview' value='1' />" .
						"<input type='submit' name='SendSubmit' value='" .
							htmlspecialchars(sprintf(__('Send next %s Emails'), 
								$form->get('LimitEmails')), ENT_QUOTES)."' " .
							"class='button submit' /> ";
				}
				
				echo
					"<input type='button' " .
						"value='".htmlspecialchars(__('View Emails'), ENT_QUOTES)."' " .
						"class='button' " .
						"onclick=\"window.location='".
							str_replace('&amp;', '&', url::uri())."';\" /> " .
					"<input type='button' " .
						"value='".htmlspecialchars(__('Admin Home'), ENT_QUOTES)."' " .
						"class='button' " .
						"onclick=\"window.location='".
							str_replace('&amp;', '&', url::uri('ALL'))."';\" />" .
					"</form>";
			
				echo 
					"</div>";	//admin-content
				return;
			}
		}
		
		if (JCORE_VERSION <= '0.1') {
			$this->displayAdminForm($form);
			unset($form);
				
			echo 
				"</div>";	//admin-content
			
			return;
		}
		
		$paging = new paging(10);
		$paging->ignoreArgs = 'id, resend, delete';
		
		$rows = sql::run(
				" SELECT * FROM `{massemails}`" .
				($this->userPermissionIDs?
					" WHERE `ID` IN (".$this->userPermissionIDs.")":
					null) .
				" ORDER BY `ID` DESC" .
				" LIMIT ".$paging->limit);
				
		$paging->setTotalItems(sql::count());
		
		if ($paging->items)
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No emails sent out yet."),
				TOOLTIP_NOTIFICATION);
		
		$paging->display();
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($resend && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($resend && $id && !$form->submitted()) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{massemails}`" .
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
		if (!is_array($values))
			return false;
		
		$exists = sql::fetch(sql::run(
			" SELECT `ID` FROM `{massemails}`" .
			" WHERE `UserID` = '".
				(int)$GLOBALS['USER']->data['ID']."'".
			" AND `From` = '".
				sql::escape($values['From'])."'" .
			" AND `To` = '".
				sql::escape($values['To'])."'" .
			" AND `Subject` = '".
				sql::escape($values['Subject'])."'" .
			" ORDER BY `ID` DESC" .
			" LIMIT 1"));
		
		if ($exists) {
			sql::run(
				" UPDATE `{massemails}` SET " .
				" `Message` = '".
					sql::escape($values['Message'])."'," .
				" `TimeStamp` = NOW()" .
				" WHERE `ID` = '".$exists['ID']."'");
			
			return $exists['ID'];
		}
			
		$newid = sql::run(
			" INSERT INTO `{massemails}` SET " .
			" `UserID` = '".
				(int)$GLOBALS['USER']->data['ID']."',".
			" `From` = '".
				sql::escape($values['From'])."'," .
			" `To` = '".
				sql::escape($values['To'])."'," .
			" `Subject` = '".
				sql::escape($values['Subject'])."'," .
			" `Message` = '".
				sql::escape($values['Message'])."'," .
			" `TimeStamp` = NOW()");
				
		if (!$newid) {
			tooltip::display(
				sprintf(__("Mass Email couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return $newid;
	}
	
	function incEmailsSentOut($id) {
		if (!$id)
			return false;
		
		sql::run(
			" UPDATE `{massemails}` SET" .
			" `EmailsSentOut` = `EmailsSentOut` + 1," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".(int)$id."'");
		
		echo 
			"<script>" .
				"document.getElementById('massemailsendstatus').innerHTML += '. ';" .
			"</script>";
		
		url::flushDisplay();
		return true;
	}
	
	function delete($id, $ifnoemailssent = false) {
		if (!$id)
			return false;
		
		sql::run(
			" DELETE FROM `{massemails}`" .
			" WHERE `ID` = '".(int)$id."'" .
			($ifnoemailssent?
				" AND !`EmailsSentOut`":
				null));
		
		return true;
	}
	
	// ************************************************   Client Part
	function ajaxRequest() {
		if (!$GLOBALS['USER']->loginok || 
			!$GLOBALS['USER']->data['Admin']) 
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);
			return true;
		}
		
		$users = null;
		
		if (isset($_GET['users']))
			$users = $_GET['users'];
		
		if ($users) {
			include_once('lib/userpermissions.class.php');
			
			$permission = userPermissions::check(
				$GLOBALS['USER']->data['ID'],
				$this->adminPath);
			
			if ($permission['PermissionType'] != USER_PERMISSION_TYPE_WRITE ||
				$permission['PermissionIDs'])
			{
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);
				return true;
			}
			
			$GLOBALS['USER']->displayQuickList(
				'#newemailform #entryTo', true, '%UserName% <%Email%>');
			return true;
		}
		
		return false;
	}
}

?>