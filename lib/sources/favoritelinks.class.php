<?php

/***************************************************************************
 *            favoritelinks.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

class _favoriteLinks {
	var $adminPath = 'admin/site/favoritelinks';
	static $links = array();

	function SQL() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::SQL', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::SQL', $this, $handled);

			return $handled;
		}

		$sql =
			" SELECT * FROM `{favoritelinks}` " .
			" WHERE `Deactivated` = 0" .
			" ORDER BY `OrderID`, `ID`";

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::SQL', $this, $sql);

		return $sql;
	}

	// ************************************************   Admin Part
	function countAdminItems() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::countAdminItems', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::countAdminItems', $this, $handled);

			return $handled;
		}

		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{favoritelinks}`" .
			" LIMIT 1"));

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::countAdminItems', $this, $row['Rows']);

		return $row['Rows'];
	}

	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::setupAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::setupAdmin', $this, $handled);

			return $handled;
		}

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Link'),
				'?path='.admin::path().'#adminform');

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::setupAdmin', $this);
	}

	function setupAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::setupAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::setupAdminForm', $this, $form, $handled);

			return $handled;
		}

		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 200px;');
		$form->setTooltipText(__("e.g. English"));

		$form->add(
			__('Link'),
			'Link',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 300px;');

		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);

		$form->add(
			__('Deactivated'),
			'Deactivated',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			1);
		$form->setValueType(FORM_VALUE_TYPE_BOOL);

		$form->addAdditionalText(
			"<span class='comment' style='text-decoration: line-through;'>" .
			__("(marked with strike through)").
			"</span>");

		$form->add(
			__('Order'),
			'OrderID',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);

		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::setupAdminForm', $this, $form);
	}

	function verifyAdmin(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::verifyAdmin', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::verifyAdmin', $this, $form, $handled);

			return $handled;
		}

		$reorder = null;
		$orders = null;
		$delete = null;
		$edit = null;
		$id = null;

		if (isset($_POST['reordersubmit']))
			$reorder = (string)$_POST['reordersubmit'];

		if (isset($_POST['orders']))
			$orders = (array)$_POST['orders'];

		if (isset($_POST['delete']))
			$delete = (int)$_POST['delete'];

		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];

		if (isset($_GET['id']))
			$id = (int)$_GET['id'];

		if ($reorder) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'favoriteLinks::verifyAdmin', $this, $form);
				return false;
			}

			foreach((array)$orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{favoritelinks}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'");
			}

			tooltip::display(
				__("Links have been successfully re-ordered."),
				TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::verifyAdmin', $this, $form, $reorder);

			return true;
		}

		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'favoriteLinks::verifyAdmin', $this, $form);
				return false;
			}

			$result = $this->dbDelete($id);

			if ($result)
				tooltip::display(
					__("Link has been successfully deleted."),
					TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::verifyAdmin', $this, $form, $result);

			return $result;
		}

		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::verifyAdmin', $this, $form);

			return false;
		}

		if ($edit) {
			$result = $this->dbEdit($id, $form->getPostArray());

			if ($result)
				tooltip::display(
					__("Link has been successfully updated.")." " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::verifyAdmin', $this, $form, $result);

			return $result;
		}

		$newid = $this->dbAdd($form->getPostArray());

		if ($newid) {
			tooltip::display(
				__("Link has been successfully created.")." " .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$newid."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);

			$form->reset();
		}

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::verifyAdmin', $this, $result, $newid);

		return $result;
	}

	function displayAdminListHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminListHeader', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::displayAdminListHeader', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Link")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminListHeader', $this);
	}

	function displayAdminListHeaderOptions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminListHeaderOptions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::displayAdminListHeaderOptions', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminListHeaderOptions', $this);
	}

	function displayAdminListHeaderFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminListHeaderFunctions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::displayAdminListHeaderFunctions', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminListHeaderFunctions', $this);
	}

	function displayAdminListItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminListItem', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::displayAdminListItem', $this, $row, $handled);

			return $handled;
		}

		echo
			"<td>" .
				"<input type='text' name='orders[".$row['ID']."]' " .
					"value='".$row['OrderID']."' " .
					"class='order-id-entry' tabindex='1' />" .
			"</td>" .
			"<td " .
				($row['Deactivated']?
					"style='text-decoration: line-through;' ":
					null).
				"class='auto-width bold'>" .
				$row['Title'] .
				"<div class='comment' style='padding-left: 10px;'>" .
					$row['Link'] .
				"</div>" .
			"</td>";

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminListItem', $this, $row);
	}

	function displayAdminListItemOptions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminListItemOptions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::displayAdminListItemOptions', $this, $row, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminListItemOptions', $this, $row);
	}

	function displayAdminListItemFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminListItemFunctions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::displayAdminListItemFunctions', $this, $row, $handled);

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
			'favoriteLinks::displayAdminListItemFunctions', $this, $row);
	}

	function displayAdminListFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminListFunctions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::displayAdminListFunctions', $this, $handled);

			return $handled;
		}

		echo
			"<input type='submit' name='reordersubmit' value='".
				htmlchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlchars(__("Reset"), ENT_QUOTES)."' class='button' />";

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminListFunctions', $this);
	}

	function displayAdminList(&$rows) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminList', $this, $rows);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::displayAdminList', $this, $rows, $handled);

			return $handled;
		}

		echo
			"<form action='".url::uri('edit, delete')."' method='post'>" .
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
			'favoriteLinks::displayAdminList', $this, $rows);
	}

	function displayAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::displayAdminForm', $this, $form, $handled);

			return $handled;
		}

		$form->display();

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminForm', $this, $form);
	}

	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminTitle', $this, $ownertitle);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::displayAdminTitle', $this, $ownertitle, $handled);

			return $handled;
		}

		admin::displayTitle(
			__('Favorite Links Administration'),
			$ownertitle);

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminTitle', $this, $ownertitle);
	}

	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminDescription', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::displayAdminDescription', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminDescription', $this);
	}

	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::displayAdmin', $this, $handled);

			return $handled;
		}

		$delete = null;
		$edit = null;
		$id = null;

		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];

		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];

		if (isset($_GET['id']))
			$id = (int)$_GET['id'];

		$this->displayAdminTitle();
		$this->displayAdminDescription();

		echo
			"<div class='admin-content'>";

		if ($delete && $id && empty($_POST['delete'])) {
			$selected = sql::fetch(sql::run(
				" SELECT `Title` FROM `{favoritelinks}`" .
				" WHERE `ID` = '".$id."'"));

			url::displayConfirmation(
				'<b>'.__('Delete').'?!</b> "'.$selected['Title'].'"');
		}

		$form = new form(
				($edit?
					__("Edit Link"):
					__("New Link")),
				'neweditlink');

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
			" SELECT * FROM `{favoritelinks}`" .
			" ORDER BY `OrderID`, `ID`");

		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
					__("No links found."),
					TOOLTIP_NOTIFICATION);

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{favoritelinks}`" .
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
			'favoriteLinks::displayAdmin', $this);
	}

	function dbAdd($values) {
		if (!is_array($values))
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::dbAdd', $this, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::dbAdd', $this, $values, $handled);

			return $handled;
		}

		if ($values['OrderID'] == '') {
			$row = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{favoritelinks}` " .
				" ORDER BY `OrderID` DESC" .
				" LIMIT 1"));

			$values['OrderID'] = (int)$row['OrderID']+1;

		} else {
			sql::run(
				" UPDATE `{favoritelinks}` SET " .
				" `OrderID` = `OrderID` + 1" .
				" WHERE `OrderID` >= '".(int)$values['OrderID']."'");
		}

		$newid = sql::run(
			" INSERT INTO `{favoritelinks}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Link` = '".
				sql::escape($values['Link'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'");

		if (!$newid)
			tooltip::display(
				sprintf(__("Link couldn't be added! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::dbAdd', $this, $values, $newid);

		return $newid;
	}

	function dbEdit($id, $values) {
		if (!$id)
			return false;

		if (!is_array($values))
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::dbEdit', $this, $id, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::dbEdit', $this, $id, $values, $handled);

			return $handled;
		}

		sql::run(
			" UPDATE `{favoritelinks}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Link` = '".
				sql::escape($values['Link'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");

		$result = (sql::affected() != -1);

		if (!$result)
			tooltip::display(
				sprintf(__("Link couldn't be updated! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::dbEdit', $this, $id, $values, $result);

		return $result;
	}

	function dbDelete($id) {
		if (!$id)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::dbDelete', $this, $id);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::dbDelete', $this, $id, $handled);

			return $handled;
		}

		sql::run(
			" DELETE FROM `{favoritelinks}` " .
			" WHERE `ID` = '".$id."'");

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::dbDelete', $this, $id);

		return true;
	}

	// ************************************************   Client Part
	static function add($title, $link) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::add', $_ENV, $title, $link);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::add', $_ENV, $title, $link, $handled);

			return $handled;
		}

		if (isset(favoriteLinks::$links[$link])) {
			$result = false;

		} else {
			preg_match('/(\?|&)path=(.*?)(#|&|\'|"|$)/i', $link, $matches);

			$userpermission = userPermissions::check((int)$GLOBALS['USER']->data['ID'],
				(isset($matches[2])?
					$matches[2]:
					null));

			if ($userpermission['PermissionType'])
				favoriteLinks::$links[$link] = $title;

			$result = $link;
		}

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::add', $_ENV, $title, $link, $result);

		return $result;
	}

	static function remove($link) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::remove', $_ENV, $link);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::remove', $_ENV, $link, $handled);

			return $handled;
		}

		unset(favoriteLinks::$links[$link]);

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::remove', $_ENV, $link);
	}

	static function clear() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::clear', $_ENV);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::clear', $_ENV, $handled);

			return $handled;
		}

		favoriteLinks::$links = array();

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::clear', $_ENV);
	}

	function display() {
		if (count(favoriteLinks::$links))
			favoriteLinks::add('<SPACER>', '');

		$rows = sql::run(
			$this->SQL());

		while($row = sql::fetch($rows))
			favoriteLinks::add(
				__($row['Title']), $row['Link']);

		if (!favoriteLinks::$links || !is_array(favoriteLinks::$links) ||
			!count(favoriteLinks::$links))
		{
			return;
		}

		$handled = api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::display', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::display', $this, $handled);

			return $handled;
		}

		if (count(favoriteLinks::$links) == 1) {
			echo
				"<div class='admin-favorite-links'>" .
					"<div tabindex='0' class='button fc'>" .
						"<a href='".key(favoriteLinks::$links)."'" .
							(preg_match('/https?:\/\//i', key(favoriteLinks::$links))?
								" target='_blank'":
								null) .
							">" .
							"<span>".favoriteLinks::$links[key(favoriteLinks::$links)]."</span>" .
						"</a>" .
					"</div>" .
				"</div>";

			api::callHooks(API_HOOK_AFTER,
				'favoriteLinks::display', $this);

			return;
		}

		$i = 1;
		foreach(favoriteLinks::$links as $link => $title) {
			$target = null;
			if (preg_match('/https?:\/\//i', $link))
				$target = " target='_blank'";

			if ($i == 1) {
				echo
					"<div class='admin-favorite-links'>" .
						"<div tabindex='0' class='button fc'>" .
							"<a class='fc-title'>&nbsp;</a>" .
							"<a href='".$link."'".$target.">" .
								"<span>".$title."</span>" .
							"</a>" .
							"<div class='fc-content'>" .
							"<ul>";
			} else {
				if ($title == '<SPACER>')
					echo
						"<li>" .
							"<div class='spacer'></div>" .
						"</li>";
				else
					echo
						"<li>" .
							"<a href='".$link."'".$target.">" .
								"<span>".$title."</span>" .
							"</a>" .
						"</li>";
			}

			$i++;
		}

		echo
							"</ul>" .
							"</div>" .
						"</div>" .
					"</div>";

		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::display', $this);
	}
}

?>