<?php

/***************************************************************************
 *            notes.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/paging.class.php');
include_once('lib/notecomments.class.php');
include_once('lib/noteattachments.class.php');

define('NOTE_STATUS_OPEN', 1);
define('NOTE_STATUS_CLOSED', 2);

class _notes {
	var $adminPath = 'admin/site/notes';

	// ************************************************   Admin Part
	function countAdminItems() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::countAdminItems', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::countAdminItems', $this, $handled);

			return $handled;
		}

		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{notes}`" .
			" LIMIT 1"));

		api::callHooks(API_HOOK_AFTER,
			'notes::countAdminItems', $this, $row['Rows']);

		return $row['Rows'];
	}

	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::setupAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::setupAdmin', $this, $handled);

			return $handled;
		}

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Note'),
				'?path='.admin::path().'#adminform');

		favoriteLinks::add(
			__('Pages / Posts'),
			'?path=' .
			(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));
		favoriteLinks::add(
			__('View Website'),
			SITE_URL);

		api::callHooks(API_HOOK_AFTER,
			'notes::setupAdmin', $this);
	}

	function setupAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::setupAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::setupAdminForm', $this, $form, $handled);

			return $handled;
		}

		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 350px;');

		$form->add(
			__('Content'),
			'Content',
			FORM_INPUT_TYPE_EDITOR);
		$form->setStyle('height: 400px;');
		$form->setValueType(FORM_VALUE_TYPE_HTML);

		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);

		$form->add(
			__('Status'),
			'StatusID',
			FORM_INPUT_TYPE_SELECT,
			true);
		$form->setValueType(FORM_VALUE_TYPE_INT);

		$form->addAdditionalText(
			"<span class='comment' style='text-decoration: line-through;'>" .
			__("(closed ones are marked with strike through)").
			"</span>");

		$form->addValue(
			NOTE_STATUS_OPEN,
			$this->status2Text(NOTE_STATUS_OPEN));

		$form->addValue(
			NOTE_STATUS_CLOSED,
			$this->status2Text(NOTE_STATUS_CLOSED));

		$form->add(
			__('Created on'),
			'TimeStamp',
			FORM_INPUT_TYPE_TIMESTAMP);
		$form->setStyle('width: 170px;');
		$form->setValueType(FORM_VALUE_TYPE_TIMESTAMP);

		$form->add(
			__('Due Date'),
			'DueDate',
			FORM_INPUT_TYPE_DATE);
		$form->setStyle('width: 100px;');
		$form->setValueType(FORM_VALUE_TYPE_DATE);

		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);

		api::callHooks(API_HOOK_AFTER,
			'notes::setupAdminForm', $this, $form);
	}

	function verifyAdmin(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::verifyAdmin', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::verifyAdmin', $this, $form, $handled);

			return $handled;
		}

		$delete = null;
		$edit = null;
		$id = null;

		if (isset($_POST['delete']))
			$delete = (int)$_POST['delete'];

		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];

		if (isset($_GET['id']))
			$id = (int)$_GET['id'];

		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'notes::verifyAdmin', $this, $form);
				return false;
			}

			$result = $this->delete($id);

			if ($result)
				tooltip::display(
					__("Note has been successfully deleted."),
					TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'notes::verifyAdmin', $this, $form, $result);

			return $result;
		}

		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'notes::verifyAdmin', $this, $form);

			return false;
		}

		if ($edit) {
			$result = $this->edit($id, $form->getPostArray());

			if ($result)
				tooltip::display(
					__("Note has been successfully updated.")." ".
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'notes::verifyAdmin', $this, $form, $result);

			return $result;
		}

		$newid = $this->add($form->getPostArray());

		if ($newid) {
			tooltip::display(
				__("Note has been successfully created.")." " .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$newid."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);

			$form->reset();
		}

		api::callHooks(API_HOOK_AFTER,
			'notes::verifyAdmin', $this, $form, $newid);

		return $newid;
	}

	function displayAdminListHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::displayAdminListHeader', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::displayAdminListHeader', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Title / Created on")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'notes::displayAdminListHeader', $this);
	}

	function displayAdminListHeaderOptions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::displayAdminListHeaderOptions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::displayAdminListHeaderOptions', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Comments")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Attachments")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'notes::displayAdminListHeaderOptions', $this);
	}

	function displayAdminListHeaderFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::displayAdminListHeaderFunctions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::displayAdminListHeaderFunctions', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'notes::displayAdminListHeaderFunctions', $this);
	}

	function displayAdminListItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::displayAdminListItem', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::displayAdminListItem', $this, $row, $handled);

			return $handled;
		}

		$user = $GLOBALS['USER']->get($row['UserID']);

		echo
			"<td class='auto-width' " .
				($row['StatusID'] == NOTE_STATUS_CLOSED?
					"style='text-decoration: line-through;' ":
					null).
				">" .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."' " .
					" class='bold'>" .
					$row['Title'] .
				"</a>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					calendar::dateTime($row['TimeStamp'])." " .
					$GLOBALS['USER']->constructUserName($user, __('by %s')) .
				"</div>" .
			"</td>";

		api::callHooks(API_HOOK_AFTER,
			'notes::displayAdminListItem', $this, $row);
	}

	function displayAdminListItemOptions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::displayAdminListItemOptions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::displayAdminListItemOptions', $this, $row, $handled);

			return $handled;
		}

		echo
			"<td align='center'>" .
				"<a class='admin-link comments' " .
					"title='".htmlchars(__("Comments"), ENT_QUOTES).
						" (".$row['Comments'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/notecomments'>";

		if (ADMIN_ITEMS_COUNTER_ENABLED && $row['Comments'])
			counter::display($row['Comments']);

		echo
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link attachments' " .
					"title='".htmlchars(__("Attachments"), ENT_QUOTES) .
						" (".$row['Attachments'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/noteattachments'>";

		if (ADMIN_ITEMS_COUNTER_ENABLED && $row['Attachments'])
			counter::display($row['Attachments']);

		echo
				"</a>" .
			"</td>";

		api::callHooks(API_HOOK_AFTER,
			'notes::displayAdminListItemOptions', $this, $row);
	}

	function displayAdminListItemFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::displayAdminListItemFunctions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::displayAdminListItemFunctions', $this, $row, $handled);

			return $handled;
		}

		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";

		api::callHooks(API_HOOK_AFTER,
			'notes::displayAdminListItemFunctions', $this, $row);
	}

	function displayAdminListItemSelected(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::displayAdminListItemSelected', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::displayAdminListItemSelected', $this, $row, $handled);

			return $handled;
		}

		$user = $GLOBALS['USER']->get($row['UserID']);

		admin::displayItemData(
			__("Owner"),
			$GLOBALS['USER']->constructUserName($user));

		admin::displayItemData(
			__("Status"),
			$this->status2Text($row['StatusID']));

		if ($row['DueDate'])
			admin::displayItemData(
				__("Due Date"),
				$row['DueDate']);

		admin::displayItemData(
			"<hr />");
		admin::displayItemData(
			$row['Content']);

		api::callHooks(API_HOOK_AFTER,
			'notes::displayAdminListItemSelected', $this, $row);
	}

	function displayAdminListSearch() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::displayAdminListSearch', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::displayAdminListSearch', $this, $handled);

			return $handled;
		}

		$search = null;

		if (isset($_GET['search']))
			$search = trim(strip_tags((string)$_GET['search']));

		echo
			"<input type='hidden' name='path' value='".admin::path()."' />" .
			"<input type='search' name='search' value='".
				htmlchars($search, ENT_QUOTES).
				"' results='5' placeholder='".htmlchars(__("search..."), ENT_QUOTES)."' /> " .
			"<input type='submit' value='" .
				htmlchars(__("Search"), ENT_QUOTES)."' class='button' />";

		api::callHooks(API_HOOK_AFTER,
			'notes::displayAdminListSearch', $this);
	}

	function displayAdminList(&$rows) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::displayAdminList', $this, $rows);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::displayAdminList', $this, $rows, $handled);

			return $handled;
		}

		$id = null;

		if (isset($_GET['id']))
			$id = (int)$_GET['id'];

		echo
			"<form action='".
				url::uri('edit, delete').
				"' method='post'>";

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
			"</table>";

		echo "<br />";

		echo
			"</form>";

		api::callHooks(API_HOOK_AFTER,
			'notes::displayAdminList', $this, $rows);
	}

	function displayAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::displayAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::displayAdminForm', $this, $form, $handled);

			return $handled;
		}

		$form->display();

		api::callHooks(API_HOOK_AFTER,
			'notes::displayAdminForm', $this, $form);
	}

	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::displayAdminTitle', $this, $ownertitle);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::displayAdminTitle', $this, $ownertitle, $handled);

			return $handled;
		}

		admin::displayTitle(
			__('Notes Administration'),
			$ownertitle);

		api::callHooks(API_HOOK_AFTER,
			'notes::displayAdminTitle', $this, $ownertitle);
	}

	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::displayAdminDescription', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::displayAdminDescription', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'notes::displayAdminDescription', $this);
	}

	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::displayAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::displayAdmin', $this, $handled);

			return $handled;
		}

		$search = null;
		$delete = null;
		$edit = null;
		$id = null;

		if (isset($_GET['search']))
			$search = trim(strip_tags((string)$_GET['search']));

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

		$this->displayAdminTitle();
		$this->displayAdminDescription();

		echo
			"<div class='admin-content'>";

		$form = new form(
				($edit?
					__("Edit Note"):
					__("New Note")),
				'neweditnote');

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

		if ($id) {
			$selected = sql::fetch(sql::run(
				" SELECT `ID`, `Title` FROM `{notes}`" .
				" WHERE `ID` = '".$id."'" .
				($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
					" AND `UserID` = '".(int)$GLOBALS['USER']->data['ID']."'":
					null)));

			if ($delete && empty($_POST['delete']))
				url::displayConfirmation(
					'<b>'.__('Delete').'?!</b> "'.$selected['Title'].'"');
		}

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			((!$edit && !$delete) || $selected))
			$verifyok = $this->verifyAdmin($form);

		$paging = new paging(10);
		$paging->ignoreArgs = 'id, edit, delete';

		$rows = sql::run(
				" SELECT * FROM `{notes}`" .
				" WHERE 1" .
				($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
					" AND `UserID` = '".(int)$GLOBALS['USER']->data['ID']."'":
					null) .
				($search?
					sql::search(
						$search,
						array('Title', 'Content')):
					null) .
				" ORDER BY `ID` DESC" .
				" LIMIT ".$paging->limit);

		$paging->setTotalItems(sql::count());

		if ($paging->items)
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No notes found."),
				TOOLTIP_NOTIFICATION);

		$paging->display();

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && $selected && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{notes}`" .
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
			'notes::displayAdmin', $this);
	}

	function add($values) {
		if (!is_array($values))
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::add', $this, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::add', $this, $values, $handled);

			return $handled;
		}

		$newid = sql::run(
			" INSERT INTO `{notes}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Content` = '".
				sql::escape($values['Content'])."'," .
			" `TimeStamp` = " .
				($values['TimeStamp']?
					"'".sql::escape($values['TimeStamp'])."'":
					"NOW()").
				"," .
			" `DueDate` = " .
				($values['DueDate']?
					"'".sql::escape($values['DueDate'])."'":
					"NULL").
				"," .
			" `StatusID` = '".
				(int)$values['StatusID']."'," .
			" `UserID` = '".
				(isset($values['UserID']) && (int)$values['UserID']?
					(int)$values['UserID']:
					(int)$GLOBALS['USER']->data['ID']) .
				"'");

		if (!$newid)
			tooltip::display(
				sprintf(__("Note couldn't be created! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'notes::add', $this, $values, $newid);

		return $newid;
	}

	function edit($id, $values) {
		if (!$id)
			return false;

		if (!is_array($values))
			return false;

		$note = sql::fetch(sql::run(
			" SELECT * FROM `{notes}`" .
			" WHERE `ID` = '".(int)$id."'"));

		if ($note['UserID'] != $GLOBALS['USER']->data['ID']) {
			tooltip::display(
				__("You are not allowed to edit someone else's note! " .
					"If you would like to add something to the note " .
					"please do so in the comments."),
				TOOLTIP_ERROR);

			return false;
		}

		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::edit', $this, $id, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::edit', $this, $id, $values, $handled);

			return $handled;
		}

		sql::run(
			" UPDATE `{notes}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Content` = '".
				sql::escape($values['Content'])."'," .
			" `TimeStamp` = " .
				($values['TimeStamp']?
					"'".sql::escape($values['TimeStamp'])."'":
					"NOW()").
				"," .
			" `DueDate` = " .
				($values['DueDate']?
					"'".sql::escape($values['DueDate'])."'":
					"NULL").
				"," .
			(isset($values['UserID']) && (int)$values['UserID']?
				" `UserID` = '".(int)$values['UserID']."',":
				null) .
			" `StatusID` = '".
				(int)$values['StatusID']."'" .
			" WHERE `ID` = '".(int)$id."'");

		$result = (sql::affected() != -1);

		if (!$result)
			tooltip::display(
				sprintf(__("Note couldn't be updated! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'notes::edit', $this, $id, $values, $result);

		return $result;
	}

	function delete($id) {
		if (!$id)
			return false;

		$note = sql::fetch(sql::run(
			" SELECT * FROM `{notes}`" .
			" WHERE `ID` = '".(int)$id."'"));

		if ($note['UserID'] != $GLOBALS['USER']->data['ID']) {
			tooltip::display(
				__("You are not allowed to delete someone else's note! " .
					"If you would like to add something to the note " .
					"please do so in the comments."),
				TOOLTIP_ERROR);

			return false;
		}

		$handled = api::callHooks(API_HOOK_BEFORE,
			'notes::delete', $this, $id);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'notes::delete', $this, $id, $handled);

			return $handled;
		}

		$comments = new noteComments();

		$rows = sql::run(
			" SELECT * FROM `{notecomments}`" .
			" WHERE `NoteID` = '".$id."'");

		while($row = sql::fetch($rows))
			$comments->delete($row['ID']);

		unset($comments);

		$attachments = new noteAttachments();

		$rows = sql::run(
			" SELECT * FROM `{noteattachments}`" .
			" WHERE `NoteID` = '".$id."'");

		while($row = sql::fetch($rows))
			$attachments->delete($row['ID']);

		unset($attachments);

		sql::run(
			" DELETE FROM `{notes}` " .
			" WHERE `ID` = '".$id."'");

		api::callHooks(API_HOOK_AFTER,
			'notes::delete', $this, $id);

		return true;
	}

	// ************************************************   Client Part
	function status2Text($status) {
		if (!$status)
			return;

		switch($status) {
			case NOTE_STATUS_OPEN:
				return __('Open');
			case NOTE_STATUS_CLOSED:
				return __('Closed');
			default:
				return __('Undefined!');
		}
	}
}

?>