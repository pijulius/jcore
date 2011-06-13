<?php

/***************************************************************************
 * 
 *  Name: Newsletter Module
 *  URI: http://jcore.net
 *  Description: Create newsletter lists with subscribers and send newsletters. Released under the GPL, LGPL, and MPL Licenses.
 *  Author: Istvan Petres
 *  Version: 0.3
 *  Tags: newsletter module, gpl, lgpl, mpl
 * 
 ****************************************************************************/

email::add('NewsletterSubscribe',
		"Your confirmation is required to subscribe to %LIST% newsletters at %PAGE_TITLE%",
		"Dear Subscriber,\n\n" .
		"You are receiving this notification because you have " .
		"(or someone pretending to be you has) requested \"%EMAIL%\" " .
		"to be subscribed to \"%LIST%\" newsletters at %PAGE_TITLE%. " .
		"If you did not request this subscription then please ignore " .
		"it, if you keep receiving it please contact the site administrator.\n\n" .
		"To confirm your subscription and start receiving the " .
		"newsletters please click on the link below.\n\n" .
		"%CONFIRMURL%\n\n" .
		"If you do not wish to be subscribed, please simply " .
		"disregard this message.\n\n" .
		"Sincerely,\n" .
		"%PAGE_TITLE%");

email::add('NewsletterUnsubscribe',
		"Your confirmation is required to unsubscribe from %LIST% newsletters at %PAGE_TITLE%",
		"Dear Subscriber,\n\n" .
		"You are receiving this notification because you have " .
		"(or someone pretending to be you has) requested \"%EMAIL%\" " .
		"to be unsubscribed from \"%LIST%\" newsletters at %PAGE_TITLE%. " .
		"If you did not request this unsubscription then please ignore " .
		"it, if you keep receiving it please contact the site administrator.\n\n" .
		"To confirm your unsubscription and stop receiving the " .
		"newsletters please click on the link below.\n\n" .
		"%CANCELURL%\n\n" .
		"If you do not wish to be unsubscribed, please simply " .
		"disregard this message.\n\n" .
		"Sincerely,\n" .
		"%PAGE_TITLE%");

class newsletterLists {
	var $adminPath = 'admin/modules/newsletter/newsletterlists';
	
	function __construct() {
		languages::load('newsletter');
	}
	
	function __destruct() {
		languages::unload('newsletter');
	}
	
	// ************************************************   Admin Part
	static function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{newsletterlists}`" .
			" LIMIT 1"));
		
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				_('New List'), 
				'?path=admin/modules/newsletter/newsletterlists#adminform');
		
		favoriteLinks::add(
			_('Subscriptions'), 
			'?path=admin/modules/newsletter/newslettersubscriptions');
		favoriteLinks::add(
			_('Emails'), 
			'?path=admin/modules/newsletter/newsletteremails');
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 200px;');
		
		if (JCORE_VERSION >= '0.8') {
			$form->add(
				__('Description'),
				'Description',
				FORM_INPUT_TYPE_TEXTAREA);
			$form->setStyle('width: ' .
				(JCORE_VERSION >= '0.7'?
					'90%':
					'350px') .
				'; height: 50px;');
			$form->setValueType(FORM_VALUE_TYPE_HTML);
		}
		
		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
			
		$form->add(
			__('Deactivated'),
			'Deactivated',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
		
		$form->addAdditionalText(
			"<span class='comment' style='text-decoration: line-through;'>" .
			__("(marked with strike through)").
			"</span>");	
			
		$form->add(
			__('Path'),
			'Path',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 250px;');
		
		$form->add(
			__('Order'),
			'OrderID',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER,
			true);
	}
	
	function verifyAdmin(&$form) {
		$reorder = null;
		$orders = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_POST['reordersubmit']))
			$reorder = $_POST['reordersubmit'];
		
		if (isset($_POST['orders']))
			$orders = (array)$_POST['orders'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($reorder) {
			if (!$orders)
				return false;
			
			foreach($orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{newsletterlists}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'");
			}
			
			tooltip::display(
				_("Lists have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				_("List has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if (!$form->get('Path'))
			$form->set('Path', url::genPathFromString($form->get('Title')));
				
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray()))
				return false;
				
			tooltip::display(
				_("List has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$newid = $this->add($form->getPostArray()))
			return false;
			
		tooltip::display(
			_("List has been successfully created.")." " .
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
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Path")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
		echo
			"<th><span class='nowrap'>".
				_("Members")."</span></th>";
	}
	
	function displayAdminListHeaderFunctions() {
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
		echo
			"<td>" .
				"<input type='text' name='orders[".$row['ID']."]' " .
					"value='".$row['OrderID']."' " .
					"class='order-id-entry' tabindex='1' />" .
			"</td>" .
			"<td class='auto-width'" .
				($row['Deactivated']?
					" style='text-decoration: line-through;' ":
					null).
				">" .
				(JCORE_VERSION >= '0.8'?
					"<a href='".url::uri('id, edit, delete') .
						"&amp;id=".$row['ID']."' " .
						"class='bold'>":
					"<div class='bold'>") .
					$row['Title'] .
				(JCORE_VERSION >= '0.8'?
					"</a>":
					"</div>") .
				"<div class='comment' style='padding-left: 10px;'>" .
					$row['Path'] .
				"</div>" .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
		$members = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{newslettersubscriptions}`" .
			" WHERE `ListID` = '".$row['ID']."'"));
		
		echo
			"<td align='center'>" .
				"<a class='admin-link newsletter-members' " .
					"title='".htmlspecialchars(_("Members"), ENT_QUOTES) .
					" (".$members['Rows'].")' " .
					"href='?path=admin/modules/newsletter/newslettersubscriptions" .
						"&searchlistid=".$row['ID']."'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $members['Rows'])
			counter::display($members['Rows']);
		
		echo
				"</a>" .
			"</td>";
	}
	
	function displayAdminListItemFunctions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
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
	}
	
	function displayAdminListItemSelected(&$row) {
		if (JCORE_VERSION >= '0.8')
			admin::displayItemData(
				__("Description"),
				nl2br($row['Description']));
	}
	
	function displayAdminListFunctions() {
		echo
			"<input type='submit' name='reordersubmit' value='".
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminList(&$rows) {
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		echo
			"<form action='".url::uri('edit, delete')."' method='post'>";
				
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
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
			_('Newsletter Lists Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
				
		$form = new form(
				($edit?
					_("Edit List"):
					_("New List")),
				'neweditlist');
					
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
				str_replace('&amp;', '&', url::uri('id, edit, delete'))."'\"");
		}
		
		$verifyok = false;
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$verifyok = $this->verifyAdmin($form);
		
		$rows = sql::run(
			" SELECT * FROM `{newsletterlists}`" .
			" ORDER BY `OrderID`");
			
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				_("No lists found."),
				TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{newsletterlists}`" .
					" WHERE `ID` = '".$id."'"));
				
				$form->setValues($selected);
			}
			
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form);
		
		echo
			"</div>"; //admin-content
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
			
		if ($values['OrderID'] == '') {
			$row = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{newsletterlists}` " .
				" ORDER BY `OrderID` DESC" .
				" LIMIT 1"));
			
			$values['OrderID'] = (int)$row['OrderID']+1;
			
		} else {
			sql::run(
				" UPDATE `{newsletterlists}` SET " .
				" `OrderID` = `OrderID` + 1" .
				" WHERE `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		$newid = sql::run(
			" INSERT INTO `{newsletterlists}` SET ".
			" `Title` = '" .
				sql::escape($values['Title'])."'," .
			(JCORE_VERSION >= '0.8'?
				" `Description` = '".
					sql::escape($values['Description'])."',":
				null) .
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
			
		if (!$newid) {
			tooltip::display(
				sprintf(_("List couldn't be added! Error: %s"), 
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
			" UPDATE `{newsletterlists}` SET ".
			" `Title` = '" .
				sql::escape($values['Title'])."'," .
			(JCORE_VERSION >= '0.8'?
				" `Description` = '".
					sql::escape($values['Description'])."',":
				null) .
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(_("List couldn't be updated! Error: %s"), 
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
			" DELETE FROM `{newsletterlists}` " .
			" WHERE `ID` = '".$id."'");
		
		sql::run(
			" DELETE FROM `{newslettersubscriptions}` " .
			" WHERE `ListID` = '".$id."'");
		
		return true;
	}
	
	static function get($id = null, $deactivated = true) {
		if ($id)
			return sql::fetch(sql::run(
			" SELECT * FROM `{newsletterlists}`" .
			" WHERE ID = '".(int)$id."'" .
			(!$deactivated?
				" AND !`Deactivated`":
				null) .
			" ORDER BY `OrderID`"));
		
		$rows = sql::run(
			" SELECT * FROM `{newsletterlists}`" .
			(!$deactivated?
				" WHERE !`Deactivated`":
				null) .
			" ORDER BY `OrderID`");
		
		return $rows;
	}
}

class newsletterSubscriptions {
	var $adminPath = 'admin/modules/newsletter/newslettersubscriptions';
	
	function __construct() {
		languages::load('newsletter');
	}
	
	function __destruct() {
		languages::unload('newsletter');
	}
	
	// ************************************************   Admin Part
	static function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{newslettersubscriptions}`" .
			" LIMIT 1"));
		
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				_('New Subscription'), 
				'?path=admin/modules/newsletter/newslettersubscriptions#adminform');
		
		favoriteLinks::add(
			_('Emails'), 
			'?path=admin/modules/newsletter/newsletteremails');
		favoriteLinks::add(
			_('Lists'), 
			'?path=admin/modules/newsletter/newsletterlists');
	}
	
	function setupAdminForm(&$form) {
		$edit = null;
		$listid = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['searchlistid']))
			$listid = (float)$_GET['searchlistid'];
		
		if ($edit) {
			$form->add(
				__('Email'),
				'Email',
				FORM_INPUT_TYPE_EMAIL,
				true);
			$form->setStyle('width: 250px;');
			
			$form->add(
				_('Subscribed to'),
				'ListID',
				FORM_INPUT_TYPE_SELECT,
				true);
			$form->setValueType(FORM_VALUE_TYPE_INT);
				
			$lists = newsletterLists::get();
			while($list = sql::fetch($lists))
				$form->addValue($list['ID'], $list['Title']);
			
		} else {
			$form->add(
				_('Email(s)'),
				'Emails',
				FORM_INPUT_TYPE_TEXTAREA,
				true);
			$form->setStyle('width: 300px; height: 100px;');
			$form->addAdditionalTitle("<div class='comment'>" .
				_("one email per line")."</div>");
			
			$form->add(
				_('Subscribed to'),
				'ListIDs',
				FORM_INPUT_TYPE_MULTISELECT,
				true,
				array($listid));
			$form->setValueType(FORM_VALUE_TYPE_ARRAY);
			$form->addAdditionalTitle("<div class='comment'>" .
				_("hold CTRL down to select more")."</div>");
				
			$lists = newsletterLists::get();
			while($list = sql::fetch($lists))
				$form->addValue($list['ID'], $list['Title']);
		}
			
		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
			
		$form->add(
			_('Confirmed'),
			'Confirmed',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
		$form->addAdditionalText(_("(if not checked an email will be sent to the email address(es) for confirmation)"));
		
		$form->add(
			_('Subscribed on'),
			'TimeStamp',
			FORM_INPUT_TYPE_TIMESTAMP);
		$form->setStyle('width: 170px;');
		$form->setValueType(FORM_VALUE_TYPE_TIMESTAMP);
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER,
			true);
	}
	
	function verifyAdmin(&$form) {
		$deleteall = null;
		$confirmall = null;
		$confirm = null;
		$listid = null;
		$search = null;
		$delete = null;
		$edit = null;
		$id = null;
		$ids = null;
		
		if (isset($_POST['deleteallsubmit']))
			$deleteall = $_POST['deleteallsubmit'];
		
		if (isset($_POST['deletesubmit']))
			$delete = $_POST['deletesubmit'];
		
		if (isset($_POST['confirmallsubmit']))
			$confirmall = $_POST['confirmallsubmit'];
		
		if (isset($_POST['confirmsubmit']))
			$confirm = $_POST['confirmsubmit'];
		
		if (isset($_GET['searchlistid']))
			$listid = (int)$_GET['searchlistid'];
		
		if (isset($_GET['search']))
			$search = $_GET['search'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if (isset($_POST['subscriptionids']))
			$ids = (array)$_POST['subscriptionids'];
		
		if ($confirmall) {
			$rows = sql::run(
				" UPDATE `{newslettersubscriptions}` SET" .
				" `Confirmed` = 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE 1" .
				($listid == -1?
					" AND `Confirmed`":
					null) .
				($listid == -2?
					" AND !`Confirmed`":
					null) .
				($listid > 0?
					" AND `ListID` = '".(int)$listid."'":
					null) .
				($search?
					sql::search(
						$search,
						array('Email')):
					null));
			
			tooltip::display(
				_("Subscriptions have been successfully confirmed."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($deleteall) {
			$rows = sql::run(
				" DELETE FROM `{newslettersubscriptions}`" .
				" WHERE 1" .
				($listid == -1?
					" AND `Confirmed`":
					null) .
				($listid == -2?
					" AND !`Confirmed`":
					null) .
				($listid > 0?
					" AND `ListID` = '".(int)$listid."'":
					null) .
				($search?
					sql::search(
						$search,
						array('Email')):
					null));
			
			tooltip::display(
				__("Subscriptions have been successfully deleted."),
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if (!$id && !$ids && 
			($confirm || $delete))
		{
			tooltip::display(
				_("No subscriptions selected! Please select at " .
					"least one subscription."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if ($ids && count($ids)) {
			if ($confirm) {
				foreach($ids as $id)
					$this->confirm($id);
				
				tooltip::display(
					_("Subscriptions have been successfully confirmed."),
					TOOLTIP_SUCCESS);
				
				return true;
			}
			
			if ($delete) {
				foreach($ids as $id)
					$this->delete($id);
				
				tooltip::display(
					_("Subscriptions have been successfully deleted."),
					TOOLTIP_SUCCESS);
					
				return true;
			}
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				_("Subscription has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if ($edit) {
			$row = newsletterSubscriptions::get($id);
			
			if (!$this->edit($id, $form->getPostArray()))
				return false;
				
			if ($row['Confirmed'] && !$form->get('Confirmed')) {
				newsletterSubscriptions::sendConfirmationEmail($id);
				
				tooltip::display(
					_("Subscription has been successfully updated " .
						"and a notification email has been sent for " .
						"confirmation.")." " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);
				
				return true;
			}
				
			tooltip::display(
				_("Subscription has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		$postarray = $form->getPostArray();
		$emails = explode("\n", str_replace("\n\r", '', $postarray['Emails']));
		$listids = (array)$form->get('ListIDs');
		
		$emailsadded = null;
		$invalidemails = null;
		$validemails = null;
		
		foreach($emails as $email) {
			$email = trim($email);
			
			if (!$email)
				continue;
			
			if (!email::verify($email)) {
				$invalidemails[] = $email;
				continue;
			}
			
			$validemails[] = $email;
		}
			
		if ($invalidemails) {
			tooltip::display(
				sprintf(_("Invalid email addresses defined. " .
					"The following defined emails are not valid email " .
					"addresses: %s."), 
					htmlspecialchars(implode(', ', $invalidemails))),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!$validemails) {
			tooltip::display(
				_("No valid email addresses have been defined!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!$postarray['Confirmed'])
			tooltip::display(
				_("Sending confirmation requests, please wait...") .
				"<div id='newsletterconfirmationstatus'></div>",
				TOOLTIP_NOTIFICATION);
		
		foreach($validemails as $email) {
			$newids = array();
			
			foreach($listids as $listid) {
				$postarray['Email'] = $email;
				$postarray['ListID'] = $listid;
				
				if (!$newid = $this->add($postarray))
					continue;
				
				$newids[] = $newid;
			}
			
			if (!count($newids))
				continue;
			
			if (!$postarray['Confirmed'] && 
				!newsletterSubscriptions::sendConfirmationEmail($newids))
			{ 
				foreach($newids as $newid)
					$this->delete($newid);
				
				continue;
			}
			
			if (!$postarray['Confirmed']) { 
				echo 
					"<script>" .
						"document.getElementById('newsletterconfirmationstatus').innerHTML += '. ';" .
					"</script>";
				
				url::flushDisplay();
			}
			
			$emailsadded[$email] = $email;
		}
			
		if (!$emailsadded) {
			tooltip::display(
				_("No subscriptions have been created! Please see detailed " .
					"error messages above and try again."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		tooltip::display(
			sprintf(_("Subscriptions have been successfully created." .
				" Emails subscribed: %s"),
				implode(', ', $emailsadded)),
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminListHeader() {
		echo
			"<th>" .
				"<input type='checkbox' class='checkbox-all' " .
				(~$this->userPermissionType & USER_PERMISSION_TYPE_WRITE?
					"disabled='disabled' ":
					null) .
				"/>" .
			"</th>" .
			"<th><span class='nowrap'>".
				_("Email / Subscribed on")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				_("Confirmed")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				_("Newsletter List")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
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
		
		if (isset($_POST['subscriptionids']))
			$ids = (array)$_POST['subscriptionids'];
		
		$list = newsletterLists::get($row['ListID']);
		
		echo
			"<td style='white-space: nowrap;'>" .
				"<input type='checkbox' name='subscriptionids[]' " .
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
				"<div class='bold'>" .
					$row['Email'] .
				"</div>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					calendar::dateTime($row['TimeStamp']) .
				"</div>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				($row['Confirmed']?
					__("Yes"):
					null) .
			"</td>" .
			"<td style='text-align: right;" .
				($list['Deactivated']?
					" text-decoration: line-through;":
					null).
				"'>" .
				"<span class='nowrap'>" .
					$list['Title'] .
				"</span>" .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
	}
	
	function displayAdminListItemFunctions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
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
	}
	
	function displayAdminListFunctions() {
		echo
			"<input type='submit' name='confirmsubmit' value='".
				htmlspecialchars(_("Confirm"), ENT_QUOTES)."' class='button' /> " .
			"<input type='submit' name='confirmallsubmit' value='".
				htmlspecialchars(_("Confirm All"), ENT_QUOTES)."' class='button' /> " .
			"<input type='submit' name='deletesubmit' value='".
				htmlspecialchars(_("Delete"), ENT_QUOTES)."' class='button confirm-link' /> " .
			"<input type='submit' name='deleteallsubmit' value='".
				htmlspecialchars(_("Delete All"), ENT_QUOTES)."' class='button confirm-link' /> ";
	}
	
	function displayAdminListSearch() {
		$search = null;
		$listid = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['searchlistid']))
			$listid = (float)$_GET['searchlistid'];
		
		echo
			"<input type='hidden' name='path' value='".admin::path()."' />" .
			"<input type='search' name='search' value='".
				htmlspecialchars($search, ENT_QUOTES).
				"' results='5' placeholder='".htmlspecialchars(__("search..."), ENT_QUOTES)."' /> " .
			"<select name='searchlistid' style='width: 100px;' " .
				"onchange='this.form.submit();'>" .
				"<option value=''>"._("All")."</option>" .
				"<option value='-1'" .
					($listid == -1?" selected='selected'":null) .
					">"._("Confirmed")."</option>" .
				"<option value='-2'" .
					($listid == -2?" selected='selected'":null) .
					">"._("Unconfirmed")."</option>";
		
		$lists = newsletterLists::get();
		while($list = sql::fetch($lists))
			echo
				"<option value='".$list['ID']."'" .
					($list['ID'] == $listid?
						" selected='selected'":
						null) .
					">" .
					$list['Title'] .
				"</option>";
		
		echo
			"</select> " .
			"<input type='submit' value='" .
				htmlspecialchars(__("Search"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminList(&$rows) {
		echo
			"<form action='".url::uri('edit, delete')."' method='post'>";
				
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
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			_('Newsletter Subscriptions Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$search = null;
		$listid = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['searchlistid']))
			$listid = (float)$_GET['searchlistid'];
		
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
		
		echo
			"<div class='admin-content'>";
				
		$form = new form(
				($edit?
					_("Edit Subscription"):
					_("New Subscription")),
				'neweditsubscription');
					
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
				str_replace('&amp;', '&', url::uri('id, edit, delete'))."'\"");
		}
		
		$selected = null;
		$verifyok = false;
		
		if ($id)
			$selected = sql::fetch(sql::run(
				" SELECT `ID` FROM `{newslettersubscriptions}`" .
				" WHERE `ID` = '".$id."'"));
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$verifyok = $this->verifyAdmin($form);
		
		$paging = new paging(10);
		$paging->ignoreArgs = 'id, edit, delete';
		
		$rows = sql::run(
			" SELECT * FROM `{newslettersubscriptions}`" .
			" WHERE 1" .
			($listid == -1?
				" AND `Confirmed`":
				null) .
			($listid == -2?
				" AND !`Confirmed`":
				null) .
			($listid > 0?
				" AND `ListID` = '".(int)$listid."'":
				null) .
			($search?
				sql::search(
					$search,
					array('Email')):
				null) .
			" ORDER BY `ID` DESC" .
			" LIMIT ".$paging->limit);
			
		$paging->setTotalItems(sql::count());
		
		if ($paging->items)
			$this->displayAdminList($rows);
		else
			tooltip::display(
				_("No subscriptions found."),
				TOOLTIP_NOTIFICATION);
		
		$paging->display();
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && $selected && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{newslettersubscriptions}`" .
					" WHERE `ID` = '".$id."'"));
				
				$form->setValues($selected);
			}
			
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form);
		
		echo
			"</div>"; //admin-content
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
			
		if (newsletterSubscriptions::check($values['Email'], $values['ListID'])) {
			$list = newsletterLists::get($values['ListID']);
			
			tooltip::display(
				sprintf(_("Email address \"%s\" is already subscribed to \"%s\" newsletters!"),
					$values['Email'], $list['Title']),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!isset($values['ConfirmationCode']) || !$values['ConfirmationCode'])
			$values['ConfirmationCode'] = security::randomChars(21);
		
		$newid = sql::run(
			" INSERT INTO `{newslettersubscriptions}` SET ".
			" `Email` = '" .
				sql::escape($values['Email'])."'," .
			" `TimeStamp` = " .
				($values['TimeStamp']?
					"'".sql::escape($values['TimeStamp'])."'":
					"NOW()").
				"," .
			" `Confirmed` = '".
				($values['Confirmed']?
					'1':
					'0').
				"'," .
			" `ConfirmationCode` = '" .
				sql::escape($values['ConfirmationCode'])."'," .
			" `ListID` = '".
				(int)$values['ListID']."'");
			
		if (!$newid) {
			tooltip::display(
				sprintf(_("Subscription couldn't be added! Error: %s"), 
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
		
		if (newsletterSubscriptions::check($values['Email'], $values['ListID'], $id)) {
			$list = newsletterLists::get($values['ListID']);
			
			tooltip::display(
				sprintf(_("Email address \"%s\" is already subscribed to \"%s\" newsletters!"),
					$values['Email'], $list['Title']),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		sql::run(
			" UPDATE `{newslettersubscriptions}` SET ".
			" `Email` = '" .
				sql::escape($values['Email'])."'," .
			" `TimeStamp` = " .
				($values['TimeStamp']?
					"'".sql::escape($values['TimeStamp'])."'":
					"NOW()").
				"," .
			" `Confirmed` = '".
				($values['Confirmed']?
					'1':
					'0').
				"'," .
			(isset($values['ConfirmationCode']) && $values['ConfirmationCode']?
				" `ConfirmationCode` = '" .
					sql::escape($values['ConfirmationCode'])."',":
				null) .
			" `ListID` = '".
				(int)$values['ListID']."'" .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(_("Subscription couldn't be updated! Error: %s"), 
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
			" DELETE FROM `{newslettersubscriptions}` " .
			" WHERE `ID` = '".$id."'");
		
		return true;
	}
	
	function confirm($id) {
		if (!$id)
			return false;
			
		sql::run(
			" UPDATE `{newslettersubscriptions}` SET " .
			" `Confirmed` = 1," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".$id."'");
		
		return true;
	}
	
	static function get($id) {
		if (!$id)
			return false;
		
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{newslettersubscriptions}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		return $row;
	}
	
	static function check($email, $listid = null, $subscriptionid = null) {
		if (!$email)
			return false;
		
		$row = sql::fetch(sql::run(
			" SELECT `ID` FROM `{newslettersubscriptions}`" .
			" WHERE `Email` = '".sql::escape($email)."'" .
			($subscriptionid?
				" AND `ID` != '".(int)$subscriptionid."'":
				null) .
			($listid?
				" AND `ListID` = '".(int)$listid."'":
				null)));
		
		if (!$row)
			return false;
		
		return $row['ID'];
	}
	
	static function cleanUp() {
		return sql::run(
			" DELETE FROM `{newslettersubscriptions}`" .
			" WHERE !`Confirmed` AND `TimeStamp` < DATE_SUB(NOW(), INTERVAL 1 WEEK)");
	}
	
	static function sendConfirmationEmail($subscriptionid, $emailid = 'NewsletterSubscribe') {
		if (!$subscriptionid || 
			(is_array($subscriptionid) && !count($subscriptionid)))
			return false;
		
		$rows = sql::run(
			" SELECT * FROM `{newslettersubscriptions}`" .
			" WHERE `ID` IN ('" .
				(is_array($subscriptionid)?
					implode("','", $subscriptionid):
					(int)$subscriptionid) .
				"')");
		
		if (!sql::rows($rows)) {
			tooltip::display(
				_("Subscription couldn't be found for confirmation!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$subscriptions = array();
		
		while($row = sql::fetch($rows)) {
			$list = newsletterLists::get($row['ListID']);
			
			$subscriptions[$row['Email']]['Lists'][] = $list['Title'];
			$subscriptions[$row['Email']]['ConfirmationCodes'][] = 
				$row['ConfirmationCode'];
		}
		
		$email = new email();
		$email->load($emailid);
		
		foreach($subscriptions as $emailaddress => $details) {
			$email->to = $emailaddress;
			
			$email->variables = array(
				'Email' => $emailaddress,
				'List' => implode(", ", $details['Lists']),
				'ConfirmationCode' => implode("-", $details['ConfirmationCodes']),
				'ConfirmURL' => newsletter::getURL() .
					'&request=modules/newsletter&subscribe=' .
					implode("-", $details['ConfirmationCodes']),
				'CancelURL' => newsletter::getURL() .
					'&request=modules/newsletter&unsubscribe=' .
					implode("-", $details['ConfirmationCodes']));
			
			if (!$email->send())
				return false;
		}
		
		unset($email);
		return true;
	}
}

class newsletterEmails {
	var $adminPath = 'admin/modules/newsletter/newsletteremails';
	
	function __construct() {
		languages::load('newsletter');
	}
	
	function __destruct() {
		languages::unload('newsletter');
	}
	
	// ************************************************   Admin Part
	static function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{newsletters}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				_('New Email'), 
				'?path=admin/modules/newsletter/newsletteremails#adminform');
		
		favoriteLinks::add(
			_('Subscriptions'), 
			'?path=admin/modules/newsletter/newslettersubscriptions');
		favoriteLinks::add(
			_('Lists'), 
			'?path=admin/modules/newsletter/newsletterlists');
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
			FORM_INPUT_TYPE_MULTISELECT,
			true);
		$form->setValueType(FORM_VALUE_TYPE_ARRAY);
		$form->addAdditionalTitle("<div class='comment'>" .
			_("hold CTRL down to select more")."</div>");
			
		$form->addValue('all', _("All List")." "._("Members"));
		
		$lists = newsletterLists::get();
		while($list = sql::fetch($lists))
			$form->addValue($list['ID'], $list['Title']." ".
				_("Members"));
			
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
			true,
			"\n\n\n--\n" .
				_("If you do not wish to receive our newsletters in the future, " .
				"please click the link below to unsubscribe.") .
			"\n%UNSUBSCRIBE_LINK%");
		$form->setStyle('width: ' .
			(JCORE_VERSION >= '0.7'?
				'90%':
				'350px') .
			'; height: 200px;');
		
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
		$delete = null;
		$id = null;
		$newemailid = null;
		
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
		
		if (!email::verify($form->get('From'), true)) {
			tooltip::display(
				__("From email address is not a valid email address!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (!$form->get('SendSubmit'))
			return false;
		
		$newemailid = $this->add($form->getPostArray());
		if (!$newemailid)
			return false;
		
		$limitemails = $form->get('LimitEmails');
		$limitfrom = $form->get('LimitFrom');
		
		if (!$limitemails)
			$limitemails = 100000;
				
		$email = new email();
		
		$email->from = $form->get('From');
		$email->subject = $form->get('Subject');
		$email->message = $form->get('Message');
		
		$newsletterurl = newsletter::getURL();
		$subscriptions = 0;
		$emailssentout = 0;
		
		tooltip::display(
			($form->get('LimitEmails') && $form->get('LimitFrom')?
				sprintf(__("Sending %s emails starting from %s, please wait..."),
					$form->get('LimitEmails'),
					$form->get('LimitFrom')):
			($form->get('LimitEmails')?
				sprintf(__("Sending %s emails, please wait..."),
					$form->get('LimitEmails')):
			($form->get('LimitFrom')?
				sprintf(__("Sending emails starting from %s, please wait..."),
					$form->get('LimitFrom')):
				__("Sending emails, please wait...")))) .
			"<div id='newslettersendstatus'></div>",
			TOOLTIP_NOTIFICATION);
		
		if (in_array('all', $form->get('To')))
			$rows = sql::run(
				" SELECT `Email`, GROUP_CONCAT(DISTINCT `ConfirmationCode` SEPARATOR '-')" .
					" AS `ConfirmationCodes`" .
				" FROM `{newslettersubscriptions}`" .
				" WHERE `Confirmed`" .
				" GROUP BY `Email`" .
				" LIMIT ".(int)$limitfrom.", ".(int)$limitemails);
		else
			$rows = sql::run(
				" SELECT `Email`, GROUP_CONCAT(DISTINCT `ConfirmationCode` SEPARATOR '-')" .
					" AS `ConfirmationCodes`" .
				" FROM `{newslettersubscriptions}`" .
				" WHERE `Confirmed`" .
				" AND `ListID` IN ('".implode("','", $form->get('To'))."')" .
				" GROUP BY `Email`" .
				" LIMIT ".(int)$limitfrom.", ".(int)$limitemails);
			
		while($row = sql::fetch($rows)) {
			$email->variables = array(
				'Unsubscribe_Link' => $newsletterurl .
					'&request=modules/newsletter&unsubscribe=' .
					$row['ConfirmationCodes']);
			
			$email->to = $row['Email'];
			
			if ($email->send()) {
				$this->incEmailsSentOut($newemailid);
				$emailssentout++;
			}
			
			$subscriptions++;
		}
		
		unset($email);
		
		if (!$subscriptions && $form->get('LimitFrom')) {
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
				(!$subscriptions?
					_("No members found to send email to."):
					__("Please see detailed error " .
						"messages above and try again.")),
				TOOLTIP_ERROR);
	
			return false;
		}
		
		$tolists = array();
		foreach($form->get('To') as $to) {
			if ($to == 'all') {
				$tolists[] = _("All List");
				break;
			}
			
			$list = newsletterLists::get((int)$to);
			if ($list)
				$tolists[] = $list['Title'];
		}
		
		tooltip::display(
			sprintf(
				_("Emails have been successfully sent to: %s. " .
					"Emails sent: %s"),
				implode(', ', $tolists)." "._("Members"),
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
		$user = $GLOBALS['USER']->get($row['UserID']);
		
		echo
			"<td class='auto-width'>" .
				"<a href='".url::uri('id, resend, delete') .
					"&amp;id=".$row['ID']."' " .
					"class='bold'>".
					$row['Subject'].
				"</a>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					calendar::dateTime($row['TimeStamp']) .
					" ";
		
		$GLOBALS['USER']->displayUserName($user, __('by %s')); 
		
		echo
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
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, resend, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayAdminListItemSelected(&$row) {
		admin::displayItemData(
			__("From"),
			htmlspecialchars($row['From']));
		
		$tolists = array();
		foreach(explode('|', $row['To']) as $to) {
			if ($to == 'all') {
				$tolists[] = _("All List");
				break;
			}
			
			$list = newsletterLists::get((int)$to);
			if ($list)
				$tolists[] = $list['Title'];
		}
		
		admin::displayItemData(
			__("To"),
			implode(', ', $tolists)." "._("Members"));
		
		admin::displayItemData(
			__("Subject"),
			$row['Subject']);
		
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
					"<div class='form-entry-content'>";
		
		$tolists = array();
		
		if ($form->get('To')) {
			foreach($form->get('To') as $to) {
				if ($to == 'all') {
					$tolists[] = _("All List");
					break;
				}
				
				$list = newsletterLists::get((int)$to);
				if ($list)
					$tolists[] = $list['Title'];
				else
					$tolists[] = _("List deleted!");
			}
			
			echo
				implode(', ', $tolists)." "._("Members");
		}
		
		echo
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
						nl2br($form->get('Message')).
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
			_('Newsletter Emails Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$delete = null;
		$resend = null;
		$id = null;
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['resend']))
			$resend = (int)$_GET['resend'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
				
		$form = new form(
			_("New Email"),
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
		
		if ($id && $this->userPermissionType & USER_PERMISSION_TYPE_OWN)
			$selected = sql::fetch(sql::run(
				" SELECT `ID` FROM `{newsletters}`" .
				" WHERE `ID` = '".$id."'" .
				" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'"));
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			((!$resend && !$delete) || $selected))
			$emailssent = $this->verifyAdmin($form);
		
		if ($form->get('PreviewSubmit') || $form->get('SendSubmit')) {
			$this->displayAdminPreview($form, $emailssent);
			
			if ($emailssent) {
				echo
					"<form action='".url::uri()."' method='post'>";
				
				if ($form->get('LimitEmails')) {
					foreach($form->get('To') as $to)
						echo
							"<input type='hidden' name='To[]' value='".$to."' />";
					
					echo
						"<input type='hidden' name='From' value='" .
							htmlspecialchars($form->get('From'), ENT_QUOTES)."' />" .
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
							htmlspecialchars(sprintf(_('Send next %s Emails'), 
								$form->get('LimitEmails')), ENT_QUOTES)."' " .
							"class='button submit' /> ";
				}
				
				echo
					"<input type='button' " .
						"value='".htmlspecialchars(_('View Newsletters'), ENT_QUOTES)."' " .
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
				" SELECT * FROM `{newsletters}`" .
				($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
					" WHERE `UserID` = '".$GLOBALS['USER']->data['ID']."'":
					null) .
				" ORDER BY `TimeStamp` DESC" .
				" LIMIT ".$paging->limit);
				
		$paging->setTotalItems(sql::count());
		
		if ($paging->items)
			$this->displayAdminList($rows);
		else
			tooltip::display(
				_("No newsletters sent out yet."),
				TOOLTIP_NOTIFICATION);
		
		$paging->display();
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			(~$this->userPermissionType & USER_PERMISSION_TYPE_OWN || ($resend && $selected)))
		{
			if ($resend && ($emailssent || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{newsletters}`" .
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
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		$exists = sql::fetch(sql::run(
			" SELECT `ID` FROM `{newsletters}`" .
			" WHERE `UserID` = '".
				(int)$GLOBALS['USER']->data['ID']."'".
			" AND `From` = '".
				sql::escape($values['From'])."'" .
			" AND `To` = '".
				sql::escape(implode('|', $values['To']))."'" .
			" AND `Subject` = '".
				sql::escape($values['Subject'])."'" .
			" ORDER BY `TimeStamp` DESC" .
			" LIMIT 1"));
		
		if ($exists) {
			sql::run(
				" UPDATE `{newsletters}` SET " .
				" `Message` = '".
					sql::escape($values['Message'])."'," .
				" `TimeStamp` = NOW()" .
				" WHERE `ID` = '".$exists['ID']."'");
			
			return $exists['ID'];
		}
			
		$newid = sql::run(
			" INSERT INTO `{newsletters}` SET " .
			" `UserID` = '".
				(int)$GLOBALS['USER']->data['ID']."',".
			" `From` = '".
				sql::escape($values['From'])."'," .
			" `To` = '".
				sql::escape(implode('|', $values['To']))."'," .
			" `Subject` = '".
				sql::escape($values['Subject'])."'," .
			" `Message` = '".
				sql::escape($values['Message'])."'," .
			" `TimeStamp` = NOW()");
				
		if (!$newid) {
			tooltip::display(
				sprintf(_("Newsletter Email couldn't be created! Error: %s"), 
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
			" UPDATE `{newsletters}` SET" .
			" `EmailsSentOut` = `EmailsSentOut` + 1," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".(int)$id."'");
		
		echo 
			"<script>" .
				"document.getElementById('newslettersendstatus').innerHTML += '. ';" .
			"</script>";
		
		url::flushDisplay();
		return true;
	}
	
	function delete($id, $ifnoemailssent = false) {
		if (!$id)
			return false;
		
		sql::run(
			" DELETE FROM `{newsletters}`" .
			" WHERE `ID` = '".(int)$id."'" .
			($ifnoemailssent?
				" AND !`EmailsSentOut`":
				null));
		
		return true;
	}
}

class newsletter extends modules {
	var $adminPath = 'admin/modules/newsletter';
	var $selectedID = 0;
	
	function __construct() {
		languages::load('newsletter');
	}
	
	function __destruct() {
		languages::unload('newsletter');
	}
	
	function installSQL() {
		sql::run(
			"CREATE TABLE IF NOT EXISTS `{newsletters}` (" .
			" `ID` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT," .
			" `UserID` MEDIUMINT UNSIGNED NOT NULL default '0' ," .
			" `From` VARCHAR( 255 ) NOT NULL default '' ," .
			" `To` TEXT NULL," .
			" `Subject` VARCHAR( 255 ) NOT NULL default '' ," .
			" `Message` MEDIUMTEXT NULL," .
			" `TimeStamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ," .
			" `EmailsSentOut` MEDIUMINT UNSIGNED NOT NULL default '0' ," .
			" PRIMARY KEY (`ID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `UserID` (`UserID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
		
		sql::run(
			"CREATE TABLE IF NOT EXISTS `{newsletterlists}` (" .
			" `ID` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT ," .
			" `Title` VARCHAR( 255 ) NOT NULL default '' ," .
			(JCORE_VERSION >= '0.8'?
				" `Description` TEXT NULL,":
				null) .
			" `Path` VARCHAR( 255 ) NOT NULL default '' ," .
			" `Deactivated` tinyint(1) unsigned NOT NULL default '0'," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" PRIMARY KEY (`ID`)," .
			" KEY `Path` (`Path`)," .
			" KEY `Deactivated` (`Deactivated`)," .
			" KEY `OrderID` (`OrderID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
		
		$exists = sql::rows(sql::run(
			" SELECT * FROM `{newsletterlists}`"));
		
		if (sql::error())
			return false;
		
		if (!$exists) {
			sql::run(
				" INSERT INTO `{newsletterlists}` " .
				" (`Title`, `Path`, `Deactivated`, `OrderID`) VALUES" .
				" ('Default List', 'default-list', 0, 1);");
		
			if (sql::error())
				return false;
		}
		
		sql::run(
			"CREATE TABLE IF NOT EXISTS `{newslettersubscriptions}` (" .
			" `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT ," .
			" `Email` VARCHAR( 255 ) NOT NULL default '' ," .
			" `TimeStamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ," .
			" `ListID` SMALLINT UNSIGNED NOT NULL default '1'," .
			" `Confirmed` tinyint(1) unsigned NOT NULL default '0'," .
			" `ConfirmationCode` VARCHAR(100) NOT NULL default ''," .
			" PRIMARY KEY (`ID`)," .
			" KEY `ListID` (`ListID`)," .
			" KEY `Email` (`Email`)," .
			" KEY `Confirmed` (`Confirmed`)," .
			" KEY `ConfirmationCode` (`ConfirmationCode`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
		
		return true;
	}
	
	function installFiles() {
		$css = 
			".form-entry-listids .form-entry-content {\n" .
			"	margin-left: 130px;\n" .
			"}\n" .
			"\n" .
			".admin-link.newsletter-members {\n" .
			"	background-image: url(\"http://icons.jcore.net/32/stock_people.png\");\n" .
			"}\n" .
			"\n" .
			".admin-section-item.as-modules-newsletter-subscriptions {\n" .
			"	width: 80px;\n" .
			"}\n" .
			"\n" .
			".admin-section-item.as-modules-newsletter-emails,\n" .
			".admin-section-item.as-modules-newsletter-lists,\n" .
			".admin-section-item.as-modules-newsletter\n" .
			"{\n" .
			"	width: 70px;\n" .
			"}\n" .
			"\n" .
			".admin-section-item.as-modules-newsletter-emails a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/newslettersend.png\");\n" .
			"}\n" .
			"\n" .
			".admin-section-item.as-modules-newsletter-subscriptions a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/newslettersubscriptions.png\");\n" .
			"}\n" .
			"\n" .
			".admin-section-item.as-modules-newsletter-lists a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/newsletterlists.png\");\n" .
			"}\n" .
			"\n" .
			".admin-section-item.as-modules-newsletter a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/newsletter.png\");\n" .
			"}\n";
		
		return
			files::save(SITE_PATH.'template/modules/css/newsletter.css', $css);
	}
	
	function uninstallSQL() {
		sql::run(
			" DROP TABLE IF EXISTS `{newsletters}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{newsletterlists}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{newslettersubscriptions}`;");
		
		return true;
	}
	
	function uninstallFiles() {
		return 
			files::delete(SITE_PATH.'template/modules/css/newsletter.css');
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		if (!parent::installed($this))
			return 0;
		
		return newsletterEmails::countAdminItems();
	}
	
	function setupAdmin() {
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			favoriteLinks::add(
				_('New Email'), 
				'?path=admin/modules/newsletter/newsletteremails#adminform');
			favoriteLinks::add(
				_('New Subscription'), 
				'?path=admin/modules/newsletter/newslettersubscriptions#adminform');
			favoriteLinks::add(
				_('New List'), 
				'?path=admin/modules/newsletter/newsletterlists#adminform');
		}
	}
	
	function displayAdminTitle($ownertitle = null) {
		echo
			_('Newsletter Administration');
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdminSections() {
		$emails = 0;
		$subscriptions = 0;
		$lists = 0;
		
		if (ADMIN_ITEMS_COUNTER_ENABLED) {
			$emails = newsletterEmails::countAdminItems();
			$subscriptions = newsletterSubscriptions::countAdminItems();
			$lists = newsletterLists::countAdminItems();
		}
			
		echo
			"<div class='admin-section-item as-modules-newsletter-emails'>";
		
		if ($emails)
			counter::display((int)$emails);
		
		echo
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/newsletteremails' " .
					"title='".htmlspecialchars(_("Send and View sent " .
						"out Newsletters"), ENT_QUOTES).
					"'>" .
					"<span>" .
					_("Send Newsletters")."" .
					"</span>" .
				"</a>" .
			"</div>" .
			"<div class='admin-section-item as-modules-newsletter-subscriptions'>";
		
		if ($subscriptions)
			counter::display((int)$subscriptions);
		
		echo
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/newslettersubscriptions' " .
					"title='".htmlspecialchars(_("Import, Edit and Delete " .
						"newsletter Subscriptions"), ENT_QUOTES).
					"'>" .
					"<span>" .
					_("Newsletter Subscriptions")."" .
					"</span>" .
				"</a>" .
			"</div>" .
			"<div class='admin-section-item as-modules-newsletter-lists'>";
		
		if ($lists)
			counter::display((int)$lists);
		
		echo
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/newsletterlists' " .
					"title='".htmlspecialchars(_("Create, Modify and " .
						"Delete newsletter Lists"), ENT_QUOTES).
					"'>" .
					"<span>" .
					_("Newsletter Lists")."" .
					"</span>" .
				"</a>" .
			"</div>";
	}
	
	function displayAdmin() {
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
		
		echo 
			"<div tabindex='0' class='fc" .
				form::fcState('fcshcs', true) .
				"'>" .
				"<a class='fc-title' name='fcshcs'>";
		
		$this->displayAdminTitle();
		
		echo
				"</a>" .
				"<div class='fc-content'>";
		
		$this->displayAdminSections();
		
		echo
					"<div class='clear-both'></div>" .
				"</div>" .
			"</div>";
		
		echo
				"<div class='clear-both'></div>" .
			"</div>"; //admin-content
	}
	
	static function getTree() {
		$tree = array();
		$rows = newsletterLists::get();
		
		while($row = sql::fetch($rows)) {
			$row['PathDeepnes'] = 0;
			$tree['Tree'][] = $row;
		}
		
		return $tree['Tree'];
	}
	
	// ************************************************   Client Part
	static function getURL() {
		$url = modules::getOwnerURL('newsletter');
		
		if (!$url)
			return url::site().'?';
		
		return $url;	
	}
	
	function request() {
		$subscribe = true;
		$confirmationid = null;
		
		if (isset($_GET['subscribe']))
			$confirmationid = $_GET['subscribe'];
		
		if (isset($_GET['unsubscribe'])) {
			$confirmationid = $_GET['unsubscribe'];
			$subscribe = false;
		}
		
		if ($confirmationid) {
			$this->confirmSubscription($confirmationid, $subscribe);
			return true;
		}
		
		return false;
	}
	
	function confirmSubscription($confirmationid, $subscribe = true) {
		$rows = sql::run(
			" SELECT `ID`, `Email`, `ListID` FROM `{newslettersubscriptions}`" .
			" WHERE BINARY `ConfirmationCode` IN ('" .
				implode("', '", explode('-', sql::escape($confirmationid)))."')");
		
		if (!sql::rows($rows)) {
			tooltip::display(
				_("The defined subscription cannot be found! Your newsletter subscription" .
					" may have been already cancelled or deleted."),
				TOOLTIP_ERROR);
			return false;
		}
		
		$lists = array();
		$emails = array();
		
		if (!$subscribe) {
			while($row = sql::fetch($rows)) {
				sql::run(
					" DELETE FROM `{newslettersubscriptions}` " .
					" WHERE `ID` = '".$row['ID']."'");
			
				$list = newsletterLists::get($row['ListID']);
				$lists[] = $list['Title'];
				
				if (!in_array($row['Email'], $emails))
					$emails[] = $row['Email'];
			}
			
			tooltip::display(
				sprintf(_("<b>Your newsletter subscription has been cancelled.</b><br /> " .
					"You have successfully cancelled your subscription for \"%s\" " .
					"to \"%s\" newsletters."),
					implode(', ', $emails),
					implode(', ', $lists)),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		while($row = sql::fetch($rows)) {
			sql::run(
				" UPDATE `{newslettersubscriptions}` SET" .
				" `Confirmed` = 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".$row['ID']."'");
			
			$list = newsletterLists::get($row['ListID']);
			$lists[] = $list['Title'];
			
			if (!in_array($row['Email'], $emails))
				$emails[] = $row['Email'];
		}
		
		tooltip::display(
			sprintf(_("<b>Thank you for subscribing to our newsletters.</b><br /> " .
				"You have successfully confirmed your subscription for \"%s\" " .
				"to \"%s\" newsletters."),
				implode(', ', $emails),
				implode(', ', $lists)),
			TOOLTIP_SUCCESS);
		
		newsletterSubscriptions::cleanUp();
		return true;
	}
	
	function verifySubscription(&$form) {
		if (!$form->verify())
			return false;
		
		$unsubscribe = $form->get('Unsubscribe');
		$subscribe = $form->get('Subscribe');
		$listids = $form->get('ListIDs');
		$email = $form->get('Email');
		
		if (!$listids || !count($listids)) {
			tooltip::display(
				_("No newsletter list selected!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		if ($unsubscribe) {
			$subscriptionids = array();
			$list = null;
			
			foreach($listids as $listid) {
				if ((int)$listid)
					$list = newsletterLists::get((int)$listid);
				
				if (!$list) {
					tooltip::display(
						_("The selected newsletter list cannot be found! Please try " .
							"again by selecting a different list or contact site administrator."),
						TOOLTIP_ERROR);
					return false;
				}
				
				if (!$subscriptionid = newsletterSubscriptions::check($email, (int)$listid)) {
					tooltip::display(
						sprintf(_("Email address \"%s\" is not subscribed to \"%s\" newsletters!"),
							$email, $list['Title']),
						TOOLTIP_ERROR);
					return false;
				}
				
				$subscriptionids[] = $subscriptionid;
			}
			
			if (!newsletterSubscriptions::sendConfirmationEmail(
				$subscriptionids, 'NewsletterUnsubscribe')) 
				return false;
			
			tooltip::display(
				_("<b>Unsubscription request has been successfully submitted.</b><br />" .
					" An email has been sent to you which contains the confirmation url" .
					" to unsubscribe. Once confirmed your email will be removed from the list" .
					" and you will stop receiving our newsletters."),
				TOOLTIP_SUCCESS);
			
			tooltip::display(
				__("<b>IMPORTANT:</b> Some email providers put messages" .
					" received from addresses, which are not in your contact list, in" .
					" the \"Bulk / Junk E-Mail\" folders. Please check those folders" .
					" too."),
				'notification');
			
			return true;
		}
		
		$subscriptions = new newsletterSubscriptions();
		$subscriptionids = array();
		$list = null;
		
		foreach($listids as $listid) {
			if ((int)$listid)
				$list = newsletterLists::get((int)$listid, false);
			
			if (!$list) {
				tooltip::display(
					_("The selected newsletter list cannot be found! Please try " .
						"again by selecting a different list or contact site administrator."),
					TOOLTIP_ERROR);
				return false;
			}
			
			$postarray['Email'] = $email;
			$postarray['ListID'] = $listid;
			$postarray['Confirmed'] = null;
			$postarray['TimeStamp'] = null;
			
			$subscriptionid = newsletterSubscriptions::check($email, (int)$listid);
			$row = newsletterSubscriptions::get($subscriptionid);
			
			if ($row['Confirmed']) {
				tooltip::display(
					sprintf(_("Email address \"%s\" is already subscribed to \"%s\" newsletters!"),
						$email, $list['Title']),
					TOOLTIP_ERROR);
				return false;
			}
			
			if (!$subscriptionid)
				$subscriptionid = $subscriptions->add($postarray);
			
			if (!$subscriptionid)
				return false;
			
			$subscriptionids[] = $subscriptionid;
		}
			
		unset($subscriptions);
		
		if (!newsletterSubscriptions::sendConfirmationEmail($subscriptionids)) 
			return false;
		
		tooltip::display(
			_("<b>Your subscription request has been successfully submitted.</b><br />" .
				" An email has been sent to you which contains the confirmation url" .
				" for the lists you subscribed to. Once confirmed you will start receiving" .
				" our newsletters."),
			TOOLTIP_SUCCESS);
		
		tooltip::display(
			__("<b>IMPORTANT:</b> Some email providers put messages" .
				" received from addresses, which are not in your contact list, in" .
				" the \"Bulk / Junk E-Mail\" folders. Please check those folders" .
				" too."),
			'notification');
		
		return true;
	}
	
	function setupSubscriptionForm(&$form) {
		$form->add(
			_('Email address'),
			'Email',
			FORM_INPUT_TYPE_EMAIL,
			true);
		
		$selectedlist = null;
		
		if ($this->selectedID)
			$selectedlist = newsletterLists::get($this->selectedID, false);
		
		if ($selectedlist) {
			$form->add(
				_('Select List(s)'),
				'ListIDs',
				FORM_INPUT_TYPE_HIDDEN,
				true,
				array($this->selectedID));
			
		} else {
			$lists = newsletterLists::get(null, false);
			
			if (sql::rows($lists) == 1 || !sql::rows($lists)) {
				$list = sql::fetch($lists);
				
				$form->add(
					_('Select List(s)'),
					'ListIDs',
					FORM_INPUT_TYPE_HIDDEN,
					true,
					($list?
						array($list['ID']):
						array(0)));
				
			} else {
				$form->add(
					_('Select List(s)'),
					'ListIDs',
					FORM_INPUT_TYPE_CHECKBOX,
					true);
				
				while($list = sql::fetch($lists))
					$form->addValue($list['ID'], 
						"<span class='newsletter-list-title'>" .
							$list['Title'] .
						"<br /></span>".
						(JCORE_VERSION >= '0.8' && trim($list['Description'])?
							"<span class='newsletter-list-description comment'>" .
								nl2br($list['Description']) .
							"<br /><span>":
							null));
			}
		}
		
		$form->setValueType(FORM_VALUE_TYPE_ARRAY);
	}
	
	function displaySubscriptionForm() {
		$selectedlist = null;
		
		if ($this->selectedID)
			$selectedlist = newsletterLists::get($this->selectedID, false);
		
		$form = new form(
			($selectedlist?
				sprintf(_('Subscribe to %s Newsletters or Unsubscribe'), 
					$selectedlist['Title']):
				_('Subscribe to our Newsletters or Unsubscribe')),
			'newslettersubscribe');
		
		$form->footer = '';
		$this->setupSubscriptionForm($form);
		
		$form->add(
			_('Subscribe'),
			'Subscribe',
			FORM_INPUT_TYPE_SUBMIT);
		
		$form->add(
			_('Unsubscribe'),
			'Unsubscribe',
			FORM_INPUT_TYPE_SUBMIT);
		
		$this->verifySubscription($form);
		$form->display();
		unset($form);
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		$list = sql::fetch(sql::run(
			" SELECT `ID` FROM `{newsletterlists}`" .
			" WHERE `Path` LIKE '".sql::escape($this->arguments)."'"));
		
		if ($list)
			$this->selectedID = $list['ID'];
		
		return false;
	}
	
	function display() {
		if ($this->displayArguments())
			return;
		
		$this->displaySubscriptionForm();
	}
}

modules::register(
	'Newsletter', 
	_('Newsletters'),
	_('Create lists, send out newsletters, let people subscribe and unsubscribe'));

?>