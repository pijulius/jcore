<?php

/***************************************************************************
 *            massemail.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/email.class.php');

class _massEmail {
	var $adminPath = 'admin/members/massemail';
	var $ajaxRequest = null;

	// ************************************************   Admin Part
	static function countAdminItems() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::countAdminItems', $_ENV);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::countAdminItems', $_ENV, $handled);

			return $handled;
		}

		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{massemails}`" .
			" LIMIT 1"));

		api::callHooks(API_HOOK_AFTER,
			'massEmail::countAdminItems', $_ENV, $row['Rows']);

		return $row['Rows'];
	}

	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::setupAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::setupAdmin', $this, $handled);

			return $handled;
		}

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Email'),
				'?path='.admin::path().'#adminform');

		favoriteLinks::add(
			__('Users'),
			'?path=admin/members/users');

		api::callHooks(API_HOOK_AFTER,
			'massEmail::setupAdmin', $this);
	}

	function setupAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::setupAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::setupAdminForm', $this, $form, $handled);

			return $handled;
		}

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
		$form->setStyle('width: ' .
			(JCORE_VERSION >= '0.7'?
				'70%':
				'300px') .
			'; height: ' .
			(JCORE_VERSION >= '0.7'?
				'70px':
				'30px') .
			';');
		$form->setValueType(FORM_VALUE_TYPE_HTML);

		$tolinks =
			"<a href='javascript://' " .
				"class='mass-email-active-users' " .
				"onclick=\"$('#newemailform #entryTo').val('<ACTIVE-USERS>');\">" .
				__("Active Users") .
			"</a>" .
			"<a href='javascript://' " .
				"class='mass-email-all-users' " .
				"onclick=\"$('#newemailform #entryTo').val('<ALL-USERS>');\">" .
				__("All Users") .
			"</a>" .
			"<a href='".url::uri('request, users') .
				"&amp;request=".url::path() .
				"&amp;users=1' " .
				"class='mass-email-select-users ajax-content-link' " .
				"title='".htmlchars(__("Add User(s)"), ENT_QUOTES)."'>" .
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
			(defined('HTML_EMAILS') && HTML_EMAILS?
				FORM_INPUT_TYPE_EDITOR:
				FORM_INPUT_TYPE_TEXTAREA),
			true);
		$form->setStyle('width: ' .
			(JCORE_VERSION >= '0.7'?
				'90%':
				'350px') .
			'; height: 200px;');
		$form->setValueType(FORM_VALUE_TYPE_HTML);

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

		api::callHooks(API_HOOK_AFTER,
			'massEmail::setupAdminForm', $this, $form);
	}

	function verifyAdmin(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::verifyAdmin', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::verifyAdmin', $this, $form, $handled);

			return $handled;
		}

		$continue = null;
		$delete = null;
		$id = null;
		$newemailid = null;

		if (isset($_POST['continue']))
			$continue = (string)$_POST['continue'];

		if (isset($_POST['delete']))
			$delete = (int)$_POST['delete'];

		if (isset($_GET['id']))
			$id = (int)$_GET['id'];

		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'massEmail::verifyAdmin', $this, $form);
				return false;
			}

			$result = $this->delete($id);

			if ($result)
				tooltip::display(
					__("Email has been successfully deleted."),
					TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'massEmail::verifyAdmin', $this, $form, $result);

			return $result;
		}

		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::verifyAdmin', $this, $form);

			return false;
		}

		if (!email::verify($form->get('From'), true)) {
			tooltip::display(
				__("From email address is not a valid email address!"),
				TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'massEmail::verifyAdmin', $this, $form);

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
					htmlchars(implode(
						', ', $invalidtoemails))),
				TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'massEmail::verifyAdmin', $this, $form);

			return false;
		}

		if (!$form->get('SendSubmit')) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::verifyAdmin', $this, $form);

			return false;
		}

		if (JCORE_VERSION >= '0.2') {
			$newemailid = $this->add($form->getPostArray());

			if (!$newemailid) {
				api::callHooks(API_HOOK_AFTER,
					'massEmail::verifyAdmin', $this, $form);

				return false;
			}
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
					" WHERE `Suspended` = 0" .
					" AND `DisableNotificationEmails` = 0" .
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
					" WHERE `Suspended` = 0" .
					" AND `DisableNotificationEmails` = 0" .
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

			api::callHooks(API_HOOK_AFTER,
				'massEmail::verifyAdmin', $this, $form);

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

			api::callHooks(API_HOOK_AFTER,
				'massEmail::verifyAdmin', $this, $form);

			return false;
		}

		tooltip::display(
			sprintf(
				__("Emails have been successfully sent to: %s. " .
					"Emails sent: %s"),
				htmlchars(implode(
					', ', $toemails)),
				$emailssentout),
			TOOLTIP_SUCCESS);

		api::callHooks(API_HOOK_AFTER,
			'massEmail::verifyAdmin', $this, $form, $emailssentout);

		return true;
	}

	function displayAdminListHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::displayAdminListHeader', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::displayAdminListHeader', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Subject / Sent out")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Emails Sent")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'massEmail::displayAdminListHeader', $this);
	}

	function displayAdminListHeaderOptions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::displayAdminListHeaderOptions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::displayAdminListHeaderOptions', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'massEmail::displayAdminListHeaderOptions', $this);
	}

	function displayAdminListHeaderFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::displayAdminListHeaderFunctions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::displayAdminListHeaderFunctions', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Resend")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'massEmail::displayAdminListHeaderFunctions', $this);
	}

	function displayAdminListItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::displayAdminListItem', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::displayAdminListItem', $this, $row, $handled);

			return $handled;
		}

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

		api::callHooks(API_HOOK_AFTER,
			'massEmail::displayAdminListItem', $this, $row);
	}

	function displayAdminListItemOptions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::displayAdminListItemOptions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::displayAdminListItemOptions', $this, $row, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'massEmail::displayAdminListItemOptions', $this, $row);
	}

	function displayAdminListItemFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::displayAdminListItemFunctions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::displayAdminListItemFunctions', $this, $row, $handled);

			return $handled;
		}

		echo
			"<td align='center'>" .
				"<a class='admin-link email-new' " .
					"title='".htmlchars(__("Resend"), ENT_QUOTES)."' " .
					"href='".url::uri('id, resend, delete') .
					"&amp;id=".$row['ID']."&amp;resend=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, resend, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";

		api::callHooks(API_HOOK_AFTER,
			'massEmail::displayAdminListItemFunctions', $this, $row);
	}

	function displayAdminListItemSelected(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::displayAdminListItemSelected', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::displayAdminListItemSelected', $this, $row, $handled);

			return $handled;
		}

		$user = $GLOBALS['USER']->get($row['UserID']);

		admin::displayItemData(
			__("Sent out by"),
			$GLOBALS['USER']->constructUserName($user));

		admin::displayItemData(
			__("From"),
			htmlchars($row['From']));

		admin::displayItemData(
			__("To"),
			htmlchars($row['To']));

		admin::displayItemData(
			__("Subject"),
			htmlchars($row['Subject']));

		admin::displayItemData(
			"<hr />");

		if (defined('HTML_EMAILS') && HTML_EMAILS) {
			if (!preg_match('/<[a-zA-Z]>/', $row['Message']))
				$row['Message'] = form::text2HTML($row['Message']);

			admin::displayItemData($row['Message']);

		} else {
			admin::displayItemData(
				nl2br(url::parseLinks(htmlchars($row['Message']))));
		}

		api::callHooks(API_HOOK_AFTER,
			'massEmail::displayAdminListItemSelected', $this, $row);
	}

	function displayAdminList(&$rows) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::displayAdminList', $this, $rows);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::displayAdminList', $this, $rows, $handled);

			return $handled;
		}

		$id = null;

		if (isset($_GET['id']))
			$id = (int)$_GET['id'];

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

		api::callHooks(API_HOOK_AFTER,
			'massEmail::displayAdminList', $this, $rows);
	}

	function displayAdminPreview(&$form, &$emailssent) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::displayAdminPreview', $this, $form, $emailssent);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::displayAdminPreview', $this, $form, $emailssent, $handled);

			return $handled;
		}

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
						htmlchars($form->get('From')).
					"</div>" .
				"</div>" .
				"<div class='form-entry form-entry-To preview'>" .
					"<div class='form-entry-title bold'>" .
						__("To").":" .
					"</div>" .
					"<div class='form-entry-content'>".
						htmlchars($form->get('To')) .
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

		$message = $form->get('Message');

		if (defined('HTML_EMAILS') && HTML_EMAILS) {
			if (!preg_match('/<[a-zA-Z]>/', $message))
				$message = form::text2HTML($message);

		} else {
			$message = nl2br(url::parseLinks(htmlchars($message)));
		}

		echo
				"<div class='form-entry form-entry-Message preview'>" .
					"<div class='admin-content-preview'>" .
						$message .
					"</div>" .
					"<div class='clear-both'></div>" .
				"</div>" .
			"</div>";

		if (JCORE_VERSION >= '0.6')
			echo
				"</div>";

		echo "<br />";

		api::callHooks(API_HOOK_AFTER,
			'massEmail::displayAdminPreview', $this, $form, $emailssent);
	}

	function displayAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::displayAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::displayAdminForm', $this, $form, $handled);

			return $handled;
		}

		$form->display();

		api::callHooks(API_HOOK_AFTER,
			'massEmail::displayAdminForm', $this, $form);
	}

	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::displayAdminTitle', $this, $ownertitle);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::displayAdminTitle', $this, $ownertitle, $handled);

			return $handled;
		}

		admin::displayTitle(
			__('Mass Email Administration'),
			$ownertitle);

		api::callHooks(API_HOOK_AFTER,
			'massEmail::displayAdminTitle', $this, $ownertitle);
	}

	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::displayAdminDescription', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::displayAdminDescription', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'massEmail::displayAdminDescription', $this);
	}

	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::displayAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::displayAdmin', $this, $handled);

			return $handled;
		}

		$delete = null;
		$resend = null;
		$id = null;

		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];

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

		$selected = null;
		$emailssent = false;

		if ($id) {
			$selected = sql::fetch(sql::run(
				" SELECT `ID`, `Subject` FROM `{massemails}`" .
				" WHERE `ID` = '".$id."'" .
				($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
					" AND `UserID` = '".(int)$GLOBALS['USER']->data['ID']."'":
					null)));

			if ($delete && empty($_POST['delete']))
				url::displayConfirmation(
					'<b>'.__('Delete').'?!</b> "'.$selected['Subject'].'"');
		}

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			((!$resend && !$delete) || $selected))
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
							htmlchars($form->get('From'), ENT_QUOTES)."' />" .
						"<input type='hidden' name='To' value='" .
							htmlchars($form->get('To'), ENT_QUOTES)."' />" .
						"<input type='hidden' name='Subject' value='" .
							htmlchars($form->get('Subject'), ENT_QUOTES)."' />" .
						"<input type='hidden' name='Message' value='" .
							htmlchars($form->get('Message'), ENT_QUOTES)."' />" .
						"<input type='hidden' name='LimitEmails' value='" .
							$form->get('LimitEmails')."' />" .
						"<input type='hidden' name='LimitFrom' value='" .
							(int)($form->get('LimitFrom')+$form->get('LimitEmails'))."' />" .
						"<input type='hidden' name='Preview' value='1' />" .
						"<input type='submit' name='SendSubmit' value='" .
							htmlchars(sprintf(__('Send next %s Emails'),
								$form->get('LimitEmails')), ENT_QUOTES)."' " .
							"class='button submit' /> ";
				}

				echo
					"<input type='button' " .
						"value='".htmlchars(__('View Emails'), ENT_QUOTES)."' " .
						"class='button' " .
						"onclick=\"window.location='".
							str_replace('&amp;', '&', url::uri())."';\" /> " .
					"<input type='button' " .
						"value='".htmlchars(__('Admin Home'), ENT_QUOTES)."' " .
						"class='button' " .
						"onclick=\"window.location='".
							str_replace('&amp;', '&', url::uri('ALL'))."';\" />" .
					"</form>";

				echo
					"</div>";	//admin-content

				api::callHooks(API_HOOK_AFTER,
					'massEmail::displayAdmin', $this);

				return;
			}
		}

		if (JCORE_VERSION <= '0.1') {
			$this->displayAdminForm($form);
			unset($form);

			echo
				"</div>";	//admin-content

			api::callHooks(API_HOOK_AFTER,
				'massEmail::displayAdmin', $this);

			return;
		}

		$paging = new paging(10);
		$paging->ignoreArgs = 'id, resend, delete';

		$rows = sql::run(
				" SELECT * FROM `{massemails}`" .
				($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
					" WHERE `UserID` = '".(int)$GLOBALS['USER']->data['ID']."'":
					null) .
				" ORDER BY `TimeStamp` DESC, `ID` DESC" .
				" LIMIT ".$paging->limit);

		$paging->setTotalItems(sql::count());

		if ($paging->items)
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No emails sent out yet."),
				TOOLTIP_NOTIFICATION);

		$paging->display();

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($resend && $selected && ($emailssent || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{massemails}`" .
					" WHERE `ID` = '".$id."'"));

				if (defined('HTML_EMAILS') && HTML_EMAILS &&
					!preg_match('/<[a-zA-Z]>/', $selected['Message']))
					$selected['Message'] = form::text2HTML($selected['Message']);

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
			'massEmail::displayAdmin', $this);
	}

	function add($values) {
		if (!is_array($values))
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::add', $this, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::add', $this, $values, $handled);

			return $handled;
		}

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

			api::callHooks(API_HOOK_AFTER,
				'massEmail::add', $this, $values, $exists['ID']);

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

		if (!$newid)
			tooltip::display(
				sprintf(__("Mass Email couldn't be created! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'massEmail::add', $this, $values, $newid);

		return $newid;
	}

	function incEmailsSentOut($id) {
		if (!$id)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::incEmailsSentOut', $this, $id);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::incEmailsSentOut', $this, $id, $handled);

			return $handled;
		}

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

		api::callHooks(API_HOOK_AFTER,
			'massEmail::incEmailsSentOut', $this, $id);

		return true;
	}

	function delete($id, $ifnoemailssent = false) {
		if (!$id)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::delete', $this, $id, $ifnoemailssent);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::delete', $this, $id, $ifnoemailssent, $handled);

			return $handled;
		}

		sql::run(
			" DELETE FROM `{massemails}`" .
			" WHERE `ID` = '".(int)$id."'" .
			($ifnoemailssent?
				" AND `EmailsSentOut` = 0":
				null));

		api::callHooks(API_HOOK_AFTER,
			'massEmail::delete', $this, $id, $ifnoemailssent);

		return true;
	}

	// ************************************************   Client Part
	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'massEmail::ajaxRequest', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'massEmail::ajaxRequest', $this, $handled);

			return $handled;
		}

		if (!$GLOBALS['USER']->loginok ||
			!$GLOBALS['USER']->data['Admin'])
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'massEmail::ajaxRequest', $this);

			return true;
		}

		$users = null;

		if (isset($_GET['users']))
			$users = (int)$_GET['users'];

		if ($users) {
			include_once('lib/userpermissions.class.php');

			$permission = userPermissions::check(
				(int)$GLOBALS['USER']->data['ID'],
				$this->adminPath);

			if (~$permission['PermissionType'] & USER_PERMISSION_TYPE_WRITE) {
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);

				api::callHooks(API_HOOK_AFTER,
					'massEmail::ajaxRequest', $this);

				return true;
			}

			$GLOBALS['USER']->displayQuickList(
				'#newemailform #entryTo', true, '%UserName% <%Email%>');

			api::callHooks(API_HOOK_AFTER,
				'massEmail::ajaxRequest', $this, $users);

			return true;
		}

		api::callHooks(API_HOOK_AFTER,
			'massEmail::ajaxRequest', $this);

		return false;
	}
}

?>