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
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::SQL', $this);
		
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
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::countAdminItems', $this);
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{favoritelinks}`" .
			" LIMIT 1"));
		
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::countAdminItems', $this, $row['Rows']);
		
		return $row['Rows'];
	}
	
	function setupAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::setupAdmin', $this);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Link'), 
				'?path='.admin::path().'#adminform');
		
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::setupAdmin', $this);
	}
	
	function setupAdminForm(&$form) {
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::setupAdminForm', $this, $form);
		
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
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::verifyAdmin', $this, $form);
		
		$reorder = null;
		$orders = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_POST['reordersubmit']))
			$reorder = (string)$_POST['reordersubmit'];
		
		if (isset($_POST['orders']))
			$orders = (array)$_POST['orders'];
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($reorder) {
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
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminListHeader', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Link")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminListHeader', $this);
	}
	
	function displayAdminListHeaderOptions() {
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminListHeaderOptions', $this);
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminListHeaderOptions', $this);
	}
	
	function displayAdminListHeaderFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminListHeaderFunctions', $this);
		
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminListHeaderFunctions', $this);
	}
	
	function displayAdminListItem(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminListItem', $this, $row);
		
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
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminListItemOptions', $this, $row);
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminListItemOptions', $this, $row);
	}
	
	function displayAdminListItemFunctions(&$row) {
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminListItemFunctions', $this, $row);
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminListItemFunctions', $this, $row);
	}
	
	function displayAdminListFunctions() {
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminListFunctions', $this);
		
		echo
			"<input type='submit' name='reordersubmit' value='".
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
		
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminListFunctions', $this);
	}
	
	function displayAdminList(&$rows) {
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminList', $this, $rows);
		
		echo
			"<form action='".url::uri('edit, delete')."' method='post'>" .
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
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminForm', $this, $form);
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminTitle', $this, $ownertitle);
		
		admin::displayTitle(
			__('Favorite Links Administration'),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdminDescription', $this);
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::displayAdmin', $this);
		
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
		
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::dbAdd', $this, $values);
		
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
		
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::dbEdit', $this, $id, $values);
		
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
		
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::dbDelete', $this, $id);
		
		sql::run(
			" DELETE FROM `{favoritelinks}` " .
			" WHERE `ID` = '".$id."'");
		
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::dbDelete', $this, $id);
		
		return true;
	}
	
	// ************************************************   Client Part
	static function add($title, $link) {
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::add', $_ENV, $title, $link);
		
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
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::remove', $_ENV, $link);
		
		unset(favoriteLinks::$links[$link]);
		
		api::callHooks(API_HOOK_AFTER,
			'favoriteLinks::remove', $_ENV, $link);
	}

	static function clear() {
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::clear', $_ENV);
		
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
		
		api::callHooks(API_HOOK_BEFORE,
			'favoriteLinks::display', $this);
		
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