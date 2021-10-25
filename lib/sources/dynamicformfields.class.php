<?php

/***************************************************************************
 *            dynamicformfields.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

define('DYNAMIC_FORM_FIELD_EVERYONE', 0);
define('DYNAMIC_FORM_FIELD_GUESTS_ONLY', 1);
define('DYNAMIC_FORM_FIELD_USERS_ONLY', 2);
define('DYNAMIC_FORM_FIELD_ADMINS_ONLY', 3);

class _dynamicFormFields {
	var $formID = null;
	var $storageSQLTable;
	var $adminPath = 'admin/content/dynamicforms/dynamicformfields';

	function __construct($formid = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::dynamicFormFields', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::dynamicFormFields', $this, $handled);

			return $handled;
		}

		if ($formid) {
			$form = dynamicForms::getForm($formid);
			$this->formID = $form['ID'];
			$this->storageSQLTable = $form['SQLTable'];
		}

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::dynamicFormFields', $this);
	}

	// ************************************************   Admin Part
	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::setupAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::setupAdmin', $this, $handled);

			return $handled;
		}

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Field'),
				'?path='.admin::path().'#adminform');

		favoriteLinks::add(
			__('Forms'),
			'?path=admin/content/dynamicforms');
		favoriteLinks::add(
			__('Pages / Posts'),
			'?path=' .
			(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::setupAdmin', $this);
	}

	function setupAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::setupAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::setupAdminForm', $this, $form, $handled);

			return $handled;
		}

		$form->add(
			'FormID',
			'FormID',
			FORM_INPUT_TYPE_HIDDEN,
			true,
			$this->formID);
		$form->setValueType(FORM_VALUE_TYPE_INT);

		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 250px;');

		$form->add(
			__('Type'),
			'TypeID',
			FORM_INPUT_TYPE_SELECT,
			true);
		$form->setValueType(FORM_VALUE_TYPE_INT);

		$form->addValue(
			FORM_INPUT_TYPE_TEXT, form::type2Text(FORM_INPUT_TYPE_TEXT));
		$form->addValue(
			FORM_INPUT_TYPE_TEXTAREA, form::type2Text(FORM_INPUT_TYPE_TEXTAREA));
		$form->addValue(
			FORM_INPUT_TYPE_EDITOR, form::type2Text(FORM_INPUT_TYPE_EDITOR));
		$form->addValue(
			FORM_INPUT_TYPE_EMAIL, form::type2Text(FORM_INPUT_TYPE_EMAIL));
		$form->addValue(
			FORM_INPUT_TYPE_DATE, form::type2Text(FORM_INPUT_TYPE_DATE));
		$form->addValue(
			FORM_INPUT_TYPE_TIME, form::type2Text(FORM_INPUT_TYPE_TIME));
		$form->addValue(
			FORM_INPUT_TYPE_TIMESTAMP, form::type2Text(FORM_INPUT_TYPE_TIMESTAMP));
		$form->addValue(
			FORM_INPUT_TYPE_COLOR, form::type2Text(FORM_INPUT_TYPE_COLOR));
		$form->addValue(
			FORM_INPUT_TYPE_SEARCH, form::type2Text(FORM_INPUT_TYPE_SEARCH));
		$form->addValue(
			FORM_INPUT_TYPE_TEL, form::type2Text(FORM_INPUT_TYPE_TEL));
		$form->addValue(
			FORM_INPUT_TYPE_URL, form::type2Text(FORM_INPUT_TYPE_URL));
		$form->addValue(
			FORM_INPUT_TYPE_NUMBER, form::type2Text(FORM_INPUT_TYPE_NUMBER));
		$form->addValue(
			FORM_INPUT_TYPE_RANGE, form::type2Text(FORM_INPUT_TYPE_RANGE));
		$form->addValue(
			FORM_INPUT_TYPE_PASSWORD, form::type2Text(FORM_INPUT_TYPE_PASSWORD));
		$form->addValue(
			FORM_INPUT_TYPE_FILE, form::type2Text(FORM_INPUT_TYPE_FILE));
		$form->addValue(
			FORM_INPUT_TYPE_CHECKBOX, form::type2Text(FORM_INPUT_TYPE_CHECKBOX));
		$form->addValue(
			FORM_INPUT_TYPE_RADIO, form::type2Text(FORM_INPUT_TYPE_RADIO));
		$form->addValue(
			FORM_INPUT_TYPE_SELECT, form::type2Text(FORM_INPUT_TYPE_SELECT));
		$form->addValue(
			FORM_INPUT_TYPE_MULTISELECT, form::type2Text(FORM_INPUT_TYPE_MULTISELECT));
		$form->addValue(
			FORM_INPUT_TYPE_RECIPIENT_SELECT, form::type2Text(FORM_INPUT_TYPE_RECIPIENT_SELECT));
		$form->addValue(
			FORM_INPUT_TYPE_HIDDEN, form::type2Text(FORM_INPUT_TYPE_HIDDEN));
		$form->addValue(
			FORM_INPUT_TYPE_CONFIRM, form::type2Text(FORM_INPUT_TYPE_CONFIRM));
		$form->addValue(
			FORM_INPUT_TYPE_VERIFICATION_CODE, form::type2Text(FORM_INPUT_TYPE_VERIFICATION_CODE));
		$form->addValue(
			FORM_STATIC_TEXT, form::type2Text(FORM_STATIC_TEXT));
		$form->addValue(
			FORM_PAGE_BREAK, form::type2Text(FORM_PAGE_BREAK));
		$form->addValue(
			FORM_INPUT_TYPE_SUBMIT, form::type2Text(FORM_INPUT_TYPE_SUBMIT));
		$form->addValue(
			FORM_INPUT_TYPE_RESET, form::type2Text(FORM_INPUT_TYPE_RESET));
		$form->addValue(
			FORM_INPUT_TYPE_BUTTON, form::type2Text(FORM_INPUT_TYPE_BUTTON));
		$form->addValue(
			FORM_OPEN_FRAME_CONTAINER, form::type2Text(FORM_OPEN_FRAME_CONTAINER));
		$form->addValue(
			FORM_CLOSE_FRAME_CONTAINER, form::type2Text(FORM_CLOSE_FRAME_CONTAINER));

		if (JCORE_VERSION < 0.7) {
			$form->add(
				__('Field Name'),
				'Name',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 150px;');
			$form->setValueType(FORM_VALUE_TYPE_LIMITED_STRING);
		}

		$form->add(
			__('Value Type'),
			'ValueType',
			FORM_INPUT_TYPE_SELECT);
		$form->setValueType(FORM_VALUE_TYPE_INT);

		$form->addValue(
			'', '');
		$form->addValue(
			FORM_VALUE_TYPE_STRING, form::valueType2Text(FORM_VALUE_TYPE_STRING));
		$form->addValue(
			FORM_VALUE_TYPE_INT, form::valueType2Text(FORM_VALUE_TYPE_INT));
		$form->addValue(
			FORM_VALUE_TYPE_FLOAT, form::valueType2Text(FORM_VALUE_TYPE_FLOAT));
		$form->addValue(
			FORM_VALUE_TYPE_ARRAY, form::valueType2Text(FORM_VALUE_TYPE_ARRAY));
		$form->addValue(
			FORM_VALUE_TYPE_TIMESTAMP, form::valueType2Text(FORM_VALUE_TYPE_TIMESTAMP));
		$form->addValue(
			FORM_VALUE_TYPE_DATE, form::valueType2Text(FORM_VALUE_TYPE_DATE));
		$form->addValue(
			FORM_VALUE_TYPE_HTML, form::valueType2Text(FORM_VALUE_TYPE_HTML));
		$form->addValue(
			FORM_VALUE_TYPE_URL, form::valueType2Text(FORM_VALUE_TYPE_URL));
		$form->addValue(
			FORM_VALUE_TYPE_LIMITED_STRING, form::valueType2Text(FORM_VALUE_TYPE_LIMITED_STRING));
		$form->addValue(
			FORM_VALUE_TYPE_TEXT, form::valueType2Text(FORM_VALUE_TYPE_TEXT));
		$form->addValue(
			FORM_VALUE_TYPE_BOOL, form::valueType2Text(FORM_VALUE_TYPE_BOOL));

		if (JCORE_VERSION >= '0.7') {
			$form->add(
				__('Value Options'),
				null,
				FORM_OPEN_FRAME_CONTAINER);

			$form->add(
				__('Field Name'),
				'Name',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 150px;');
			$form->setValueType(FORM_VALUE_TYPE_LIMITED_STRING);

			$form->add(
				__('Value(s)'),
				'Values',
				FORM_INPUT_TYPE_TEXTAREA);
			$form->setStyle('width: ' .
				(JCORE_VERSION >= '0.7'?
					'90%':
					'300px') .
				'; height: 150px;');

			$form->addAdditionalText(
				"<div class='comment'>" .
					__("Enter one value per line. Values can also have two arguments " .
						"if required (title and value), for e.g. " .
						"\"Tech Department = tech@domain.com\". To have a value preselected " .
						"as the default mark it with an asterisk (*), for e.g. " .
						"\"*Tech Deparment\"") .
				"</div>");

			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER);
		}

		$form->add(
			__('Display Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);

		$form->add(
			__('Style'),
			'Style',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 350px;');

		if (JCORE_VERSION >= '0.2') {
			$form->add(
				__('Additional Text'),
				'AdditionalText',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 350px;');
		}

		if (JCORE_VERSION >= '0.6') {
			$form->add(
				__('Placeholder Text'),
				'PlaceholderText',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 200px;');

			$form->add(
				__('Tooltip Text'),
				'TooltipText',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 200px;');
		}

		if (JCORE_VERSION >= '0.7') {
			$form->add(
				__('Viewable by'),
				'ViewableBy',
				FORM_INPUT_TYPE_SELECT);
			$form->setValueType(FORM_VALUE_TYPE_INT);

			$form->addValue(
				DYNAMIC_FORM_FIELD_EVERYONE, $this->access2Text(DYNAMIC_FORM_FIELD_EVERYONE));
			$form->addValue(
				DYNAMIC_FORM_FIELD_GUESTS_ONLY, $this->access2Text(DYNAMIC_FORM_FIELD_GUESTS_ONLY));
			$form->addValue(
				DYNAMIC_FORM_FIELD_USERS_ONLY, $this->access2Text(DYNAMIC_FORM_FIELD_USERS_ONLY));
			$form->addValue(
				DYNAMIC_FORM_FIELD_ADMINS_ONLY, $this->access2Text(DYNAMIC_FORM_FIELD_ADMINS_ONLY));
		}

		if (JCORE_VERSION >= '0.9') {
			$ugroups = userGroups::get();

			while($ugroup = sql::fetch($ugroups))
				$form->addValue(
					$ugroup['ID']+10, $ugroup['GroupName']);
		}

		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);

		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);

		$form->add(
			__('Required'),
			'Required',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);

		if (JCORE_VERSION >= '0.7') {
			$form->add(
				__('Searchable'),
				'Searchable',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);

			$form->add(
				__('Data Preview'),
				'DataPreview',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			$form->addAdditionalText(
				"<span class='comment'>" .
					__("(field will be used as preview when browsing saved data)") .
				"</span>");
		}

		$form->add(
			__('Attributes'),
			'Attributes',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 350px;');

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
			'dynamicFormFields::setupAdminForm', $this, $form);
	}

	function verifyAdmin(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::verifyAdmin', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::verifyAdmin', $this, $form, $handled);

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
					'dynamicFormFields::verifyAdmin', $this, $form);
				return false;
			}

			foreach((array)$orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{dynamicformfields}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'");
			}

			tooltip::display(
				__("Fields have been successfully re-ordered."),
				TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::verifyAdmin', $this, $form, $reorder);

			return true;
		}

		if ($delete) {
			$row = sql::fetch(sql::run(
				" SELECT * FROM `{dynamicformfields}` " .
				" WHERE `ID` = '".$id."'"));

			if ($row['Protected']) {
				tooltip::display(
					__("You are NOT allowed to delete a protected field!"),
					TOOLTIP_ERROR);

				api::callHooks(API_HOOK_AFTER,
					'dynamicFormFields::verifyAdmin', $this, $form);

				return false;
			}

			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'comments::verifyAdmin', $this, $form);
				return false;
			}

			$result = $this->delete($id);

			if ($result)
				tooltip::display(
					__("Field has been successfully deleted."),
					TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::verifyAdmin', $this, $form, $result);

			return $result;
		}

		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::verifyAdmin', $this, $form);

			return false;
		}

		if (!$form->get('Name') &&
			!in_array($form->get('TypeID'), array(
				FORM_INPUT_TYPE_VERIFICATION_CODE,
				FORM_OPEN_FRAME_CONTAINER,
				FORM_CLOSE_FRAME_CONTAINER,
				FORM_STATIC_TEXT,
				FORM_PAGE_BREAK)))
		{
			$form->set('Name', url::genPathFromString($form->get('Title'), false));
		}

		if ($edit) {
			$result = $this->edit($id, $form->getPostArray());

			if ($result)
				tooltip::display(
					__("Field has been successfully updated.")." " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::verifyAdmin', $this, $form, $result);

			return $result;
		}

		$newid = $this->add($form->getPostArray());

		if ($newid) {
			tooltip::display(
				__("Field has been successfully created.")." " .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$newid."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);

			$form->reset();
		}

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::verifyAdmin', $this, $form, $newid);

		return $newid;
	}

	function displayAdminListHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::displayAdminListHeader', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::displayAdminListHeader', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Type")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Value Type")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::displayAdminListHeader', $this);
	}

	function displayAdminListHeaderOptions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::displayAdminListHeaderOptions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::displayAdminListHeaderOptions', $this, $handled);

			return $handled;
		}

		if (JCORE_VERSION < '0.7')
			echo
				"<th><span class='nowrap'>".
					__("Values")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::displayAdminListHeaderOptions', $this);
	}

	function displayAdminListHeaderFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::displayAdminListHeaderFunctions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::displayAdminListHeaderFunctions', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::displayAdminListHeaderFunctions', $this);
	}

	function displayAdminListItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::displayAdminListItem', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::displayAdminListItem', $this, $row, $handled);

			return $handled;
		}

		echo
			"<td>" .
				"<input type='text' name='orders[".$row['ID']."]' " .
					"value='".$row['OrderID']."' " .
					"class='order-id-entry' tabindex='1' />" .
			"</td>" .
			"<td class='auto-width'>" .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."' " .
					"class='bold'>".
					$row['Title'] .
					($row['Required']?
						'*':
						'') .
				"</a>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					form::type2Text($row['TypeID']) .
				"</div>" .
			"</td>" .
			"<td style='text-align: right; white-space: nowrap;'>" .
				($row['ValueType']?
					form::valueType2Text($row['ValueType']):
					null) .
			"</td>";

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::displayAdminListItem', $this, $row);
	}

	function displayAdminListItemOptions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::displayAdminListItemOptions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::displayAdminListItemOptions', $this, $row, $handled);

			return $handled;
		}

		if (JCORE_VERSION < '0.7') {
			if (JCORE_VERSION >= '0.5')
				$values = sql::fetch(sql::run(
					" SELECT COUNT(*) AS `Rows`" .
					" FROM `{dynamicformfieldvalues}`" .
					" WHERE `FieldID` = '".$row['ID']."'" .
					" LIMIT 1"));

			echo
				"<td align='center'>" .
					"<a class='admin-link values' " .
						"title='".htmlchars(__("Values"), ENT_QUOTES) .
						(JCORE_VERSION >= '0.5'?
							" (".$values['Rows'].")":
							null) .
							"' " .
						"href='".url::uri('ALL') .
						"?path=".admin::path()."/".$row['ID']."/dynamicformfieldvalues'>";

			if (ADMIN_ITEMS_COUNTER_ENABLED && $values['Rows'])
				counter::display($values['Rows']);

			echo
					"</a>" .
				"</td>";
		}

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::displayAdminListItemOptions', $this, $row);
	}

	function displayAdminListItemFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::displayAdminListItemFunctions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::displayAdminListItemFunctions', $this, $row, $handled);

			return $handled;
		}

		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>";

		if ($row['Protected'])
			echo
				"<td class='comment' title='" .
				htmlchars(__("Protected Field"), ENT_QUOTES)."'>" .
				(JCORE_VERSION < '0.6'?
					__("Protected"):
					null) .
				"</td>";
		else
			echo
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::displayAdminListItemFunctions', $this, $row);
	}

	function displayAdminListItemSelected(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::displayAdminListItemSelected', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::displayAdminListItemSelected', $this, $row, $handled);

			return $handled;
		}

		if ($row['Name'])
			admin::displayItemData(
				__("Field Name"),
				$row['Name']);

		if (JCORE_VERSION >= '0.6' && $row['PlaceholderText'])
			admin::displayItemData(
				__("Placeholder Text"),
				$row['PlaceholderText']);

		if (JCORE_VERSION >= '0.6' && $row['TooltipText'])
			admin::displayItemData(
				__("Tooltip Text"),
				$row['TooltipText']);

		if (JCORE_VERSION >= '0.2' && $row['AdditionalText'])
			admin::displayItemData(
				__("Additional Text"),
				$row['AdditionalText']);

		if ($row['Style'])
			admin::displayItemData(
				__("Style"),
				$row['Style']);

		if (JCORE_VERSION >= '0.7' && $row['ViewableBy'])
			admin::displayItemData(
				__("Viewable by"),
				$this->access2Text($row['ViewableBy']));

		if ($row['Required'])
			admin::displayItemData(
				__("Required"),
				__("Yes"));

		if (JCORE_VERSION >= '0.7' && $row['Searchable'])
			admin::displayItemData(
				__("Searchable"),
				__("Yes"));

		if (JCORE_VERSION >= '0.7' && $row['DataPreview'])
			admin::displayItemData(
				__("Data Preview"),
				__("Yes"));

		if ($row['Attributes'])
			admin::displayItemData(
				__("Attributes"),
				$row['Attributes']);

		if (JCORE_VERSION >= '0.7' && $row['Values']) {
			admin::displayItemData(
				"<hr />");
			admin::displayItemData(
				nl2br($row['Values']));
		}

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::displayAdminListItemSelected', $this, $row);
	}

	function displayAdminListFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::displayAdminListFunctions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::displayAdminListFunctions', $this, $handled);

			return $handled;
		}

		echo
			"<input type='submit' name='reordersubmit' value='".
				htmlchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlchars(__("Reset"), ENT_QUOTES)."' class='button' />";

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::displayAdminListFunctions', $this);
	}

	function displayAdminList(&$rows) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::displayAdminList', $this, $rows);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::displayAdminList', $this, $rows, $handled);

			return $handled;
		}

		$id = null;

		if (isset($_GET['id']))
			$id = (int)$_GET['id'];

		echo
			"<form action='".url::uri('edit, delete')."' method='post'>" .
				"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";

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

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::displayAdminList', $this, $rows);
	}

	function displayAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::displayAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::displayAdminForm', $this, $form, $handled);

			return $handled;
		}

		$form->display();

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::displayAdminForm', $this, $form);
	}

	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::displayAdminTitle', $this, $ownertitle);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::displayAdminTitle', $this, $ownertitle, $handled);

			return $handled;
		}

		admin::displayTitle(__('Form Fields'),
			$ownertitle);

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::displayAdminTitle', $this, $ownertitle);
	}

	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::displayAdminDescription', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::displayAdminDescription', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::displayAdminDescription', $this);
	}

	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::displayAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::displayAdmin', $this, $handled);

			return $handled;
		}

		if (!$this->formID)
			$this->formID = admin::getPathID();

		$owner = dynamicForms::getForm($this->formID);
		$this->storageSQLTable = $owner['SQLTable'];

		$this->displayAdminTitle($owner['Title']);
		$this->displayAdminDescription();

		$delete = null;
		$edit = null;
		$id = null;

		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];

		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];

		if (isset($_GET['id']))
			$id = (int)$_GET['id'];

		echo
			"<div class='admin-content'>";

		if (!$owner) {
			tooltip::display(__("Form couldn't be found!"),
				TOOLTIP_ERROR);

			echo "</div>";

			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::displayAdmin', $this);

			return;
		}

		if ($delete && $id && empty($_POST['delete'])) {
			$selected = sql::fetch(sql::run(
				" SELECT `Title` FROM `{dynamicformfields}`" .
				" WHERE `ID` = '".$id."'"));

			url::displayConfirmation(
				'<b>'.__('Delete').'?!</b> "'.$selected['Title'].'"');
		}

		$form = new form(
				($edit?
					__("Edit Field"):
					__("New Field")),
				'neweditfield');

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

		echo
			"<div tabindex='0' class='fc" .
				form::fcState('fcdyfp') .
				"'>" .
				"<a class='fc-title' name='fcdyfp'>".
					__("Preview Dynamic Form").
				"</a>" .
				"<div class='fc-content'>";

		dynamicForms::displayPreview($owner['FormID']);

		echo
				"</div>" .
			"</div>" .
			"<br />";

		$rows = sql::run(
			" SELECT * FROM `{dynamicformfields}`" .
			" WHERE `FormID` = '".$this->formID."'" .
			" ORDER BY `OrderID`, `ID`");

		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No form fields found."),
				TOOLTIP_NOTIFICATION);

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{dynamicformfields}`" .
					" WHERE `ID` = '".$id."'"));

				if ($selected['Protected']) {
					$form->edit('ValueType', null, 'ValueType', FORM_INPUT_TYPE_HIDDEN);
					$form->edit('Name', null, 'Name', FORM_INPUT_TYPE_HIDDEN);
				}

				$form->setValues($selected);
			}

			echo
				"<a name='adminform'></a>";

			$this->displayAdminForm($form);
		}

		unset($form);

		echo
			"</div>"; //admin-content

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::displayAdmin', $this);
	}

	function add($values) {
		if (!is_array($values))
			return false;

		if ($this->storageSQLTable && $values['Name'] && $values['ValueType'] &&
			form::isInput(array('Type' => $values['TypeID'])))
		{
			if (!$this->addDBField($values)) {
				tooltip::display(
					__("Field for the storage table couldn't be added. Please see the SQL " .
						"error above and report it to webmaster."),
					TOOLTIP_ERROR);

				return false;
			}
		}

		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::add', $this, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::add', $this, $values, $handled);

			return $handled;
		}

		if ($values['OrderID'] == '') {
			$row = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{dynamicformfields}` " .
				" WHERE `FormID` = '".(int)$values['FormID']."'" .
				" ORDER BY `OrderID` DESC"));

			$values['OrderID'] = (int)$row['OrderID']+1;

		} else {
			sql::run(
				" UPDATE `{dynamicformfields}` SET " .
				" `OrderID` = `OrderID` + 1" .
				" WHERE `FormID` = '".(int)$values['FormID']."'" .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}

		$newid = sql::run(
			" INSERT INTO `{dynamicformfields}` SET ".
			" `FormID` = '".
				(int)$values['FormID']."'," .
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Name` = '".
				sql::escape($values['Name'])."'," .
			" `TypeID` = '".
				(int)$values['TypeID']."'," .
			" `ValueType` = '".
				(int)$values['ValueType']."'," .
			(JCORE_VERSION >= '0.7'?
				" `Values` = '".
					sql::escape($values['Values'])."',":
				null) .
			" `Required` = '".
				($values['Required']?
					'1':
					'0').
				"'," .
			(JCORE_VERSION >= '0.7'?
				" `Searchable` = '".
					($values['Searchable']?
						'1':
						'0').
					"'," .
				" `DataPreview` = '".
					($values['DataPreview']?
						'1':
						'0').
					"'," .
				" `ViewableBy` = '".
					(int)$values['ViewableBy']."',":
				null) .
			(JCORE_VERSION >= '0.6'?
				" `PlaceholderText` = '".
					sql::escape($values['PlaceholderText'])."'," .
				" `TooltipText` = '".
					sql::escape($values['TooltipText'])."',":
				null) .
			(JCORE_VERSION >= '0.2'?
				" `AdditionalText` = '".
					sql::escape($values['AdditionalText'])."',":
				null) .
			" `Attributes` = '".
				sql::escape($values['Attributes'])."'," .
			" `Style` = '".
				sql::escape($values['Style'])."'," .
			" `OrderID` = '".
				(int)$values['OrderID'] .
				"'");

		if (!$newid)
			tooltip::display(
				sprintf(__("Field couldn't be created! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::add', $this, $values, $newid);

		return $newid;
	}

	function edit($id, $values) {
		if (!$id)
			return false;

		if (!is_array($values))
			return false;

		$row = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicformfields}`" .
			" WHERE `ID` = '".(int)$id."'"));

		if (!$row['Protected'] && $this->storageSQLTable && $values['Name'] &&
			$values['ValueType'] && form::isInput(array('Type' => $values['TypeID'])))
		{
			if (!$this->editDBField($id, $values)) {
				tooltip::display(
					__("Field for the storage table couldn't be edited. Please see the SQL " .
						"error above and report it to webmaster."),
					TOOLTIP_ERROR);

				return false;
			}
		}

		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::edit', $this, $id, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::edit', $this, $id, $values, $handled);

			return $handled;
		}

		sql::run(
			" UPDATE `{dynamicformfields}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			(!$row['Protected']?
				" `Name` = '".
					sql::escape($values['Name'])."',":
				null) .
			" `TypeID` = '".
				(int)$values['TypeID']."'," .
			(!$row['Protected']?
				" `ValueType` = '".
					(int)$values['ValueType']."',":
				null) .
			(JCORE_VERSION >= '0.7'?
				" `Values` = '".
					sql::escape($values['Values'])."',":
				null) .
			" `Required` = '".
				($values['Required']?
					'1':
					'0').
				"'," .
			(JCORE_VERSION >= '0.7'?
				" `Searchable` = '".
					($values['Searchable']?
						'1':
						'0').
					"'," .
				" `DataPreview` = '".
					($values['DataPreview']?
						'1':
						'0').
					"'," .
				" `ViewableBy` = '".
					(int)$values['ViewableBy']."',":
				null) .
			(JCORE_VERSION >= '0.6'?
				" `PlaceholderText` = '".
					sql::escape($values['PlaceholderText'])."'," .
				" `TooltipText` = '".
					sql::escape($values['TooltipText'])."',":
				null) .
			(JCORE_VERSION >= '0.2'?
				" `AdditionalText` = '".
					sql::escape($values['AdditionalText'])."',":
				null) .
			" `Attributes` = '".
				sql::escape($values['Attributes'])."'," .
			" `Style` = '".
				sql::escape($values['Style'])."'," .
			" `OrderID` = '".
				(int)$values['OrderID'] .
				"'" .
			" WHERE `ID` = '".(int)$id."'");

		$result = (sql::affected() != -1);

		if (!$result)
			tooltip::display(
				sprintf(__("Field couldn't be updated! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::edit', $this, $id, $values, $result);

		return $result;
	}

	function addDBField($values) {
		if (!is_array($values))
			return false;

		if (!$values['ValueType'])
			return false;

		$exists = sql::fetch(sql::run(
			" SHOW COLUMNS FROM `{".$this->storageSQLTable . "}`" .
			" WHERE `Field` = '".$values['Name']."'"));

		if ($exists)
			return true;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::addDBField', $this, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::addDBField', $this, $values, $handled);

			return $handled;
		}

		switch($values['ValueType']) {
			case FORM_VALUE_TYPE_BOOL:
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" ADD `".$values['Name']."` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';");
				break;

			case FORM_VALUE_TYPE_ARRAY:
			case FORM_VALUE_TYPE_TEXT:
			case FORM_VALUE_TYPE_HTML:
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" ADD `".$values['Name']."` TEXT NULL DEFAULT NULL;");
				break;

			case FORM_VALUE_TYPE_DATE:
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" ADD `".$values['Name']."` DATE NULL DEFAULT NULL;");
				break;

			case FORM_VALUE_TYPE_TIMESTAMP:
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" ADD `".$values['Name']."` TIMESTAMP NULL DEFAULT NULL;");
				break;

			case FORM_VALUE_TYPE_INT:
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" ADD `".$values['Name']."` INT NOT NULL DEFAULT '0';");
				break;

			case FORM_VALUE_TYPE_FLOAT:
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" ADD `".$values['Name']."` FLOAT NOT NULL DEFAULT '0';");
				break;

			case FORM_VALUE_TYPE_URL:
			case FORM_VALUE_TYPE_LIMITED_STRING:
			case FORM_VALUE_TYPE_STRING:
			default:
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" ADD `".$values['Name']."` VARCHAR( 255 ) NOT NULL DEFAULT '';");
		}

		if (sql::error()) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::addDBField', $this, $values);

			return false;
		}

		if (JCORE_VERSION >= '0.7' && in_array($values['ValueType'], array(
			FORM_VALUE_TYPE_BOOL, FORM_VALUE_TYPE_DATE,
			FORM_VALUE_TYPE_INT, FORM_VALUE_TYPE_FLOAT,
			FORM_VALUE_TYPE_URL, FORM_VALUE_TYPE_LIMITED_STRING,
			FORM_VALUE_TYPE_STRING)))
		{
			$indexexists = sql::rows(sql::run(
				" SHOW INDEX FROM `{".$this->storageSQLTable."}`" .
				" WHERE `Column_name` = '".$values['Name']."'"));

			if (!$indexexists && $values['Searchable'])
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" ADD INDEX (`".$values['Name']."`);");
			if ($indexexists && !$values['Searchable'])
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" DROP INDEX `".$values['Name']."`;");
		}

		$newid = true;

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::addDBField', $this, $values, $newid);

		return $newid;
	}

	function editDBField($id, $values) {
		if (!$id)
			return false;

		if (!is_array($values))
			return false;

		if (!$values['Name'])
			return false;

		$row = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicformfields}`" .
			" WHERE `ID` = '".$id."'"));

		if (!$row) {
			tooltip::display(
				__("Field selected to be edited doesn't exist!"),
				TOOLTIP_ERROR);

			return false;
		}

		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::editDBField', $this, $id, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::editDBField', $this, $id, $values, $handled);

			return $handled;
		}

		$newexists = sql::fetch(sql::run(
			" SHOW COLUMNS FROM `{".$this->storageSQLTable . "}`" .
			" WHERE `Field` = '".$values['Name']."'"));

		if ($newexists)
			$row['Name'] = $values['Name'];

		$oldexists = sql::fetch(sql::run(
			" SHOW COLUMNS FROM `{".$this->storageSQLTable . "}`" .
			" WHERE `Field` = '".$row['Name']."'"));

		if (!$row['Name'] || (!$oldexists && !$newexists)) {
			$result = $this->addDBField($values);

			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::editDBField', $this, $id, $values);

			return $result;
		}

		switch($values['ValueType']) {
			case FORM_VALUE_TYPE_BOOL:
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" CHANGE `".$row['Name']."` `".$values['Name']."` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';");
				break;

			case FORM_VALUE_TYPE_ARRAY:
			case FORM_VALUE_TYPE_TEXT:
			case FORM_VALUE_TYPE_HTML:
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" CHANGE `".$row['Name']."` `".$values['Name']."` TEXT NULL DEFAULT NULL;");
				break;

			case FORM_VALUE_TYPE_DATE:
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" CHANGE `".$row['Name']."` `".$values['Name']."` DATE NULL DEFAULT NULL;");
				break;

			case FORM_VALUE_TYPE_TIMESTAMP:
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" CHANGE `".$row['Name']."` `".$values['Name']."` TIMESTAMP NULL DEFAULT NULL;");
				break;

			case FORM_VALUE_TYPE_INT:
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" CHANGE `".$row['Name']."` `".$values['Name']."` INT NOT NULL DEFAULT '0';");
				break;

			case FORM_VALUE_TYPE_FLOAT:
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" CHANGE `".$row['Name']."` `".$values['Name']."` FLOAT NOT NULL DEFAULT '0';");
				break;

			case FORM_VALUE_TYPE_URL:
			case FORM_VALUE_TYPE_LIMITED_STRING:
			case FORM_VALUE_TYPE_STRING:
			default:
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" CHANGE `".$row['Name']."` `".$values['Name']."` VARCHAR( 255 ) NOT NULL DEFAULT '';");
		}

		if (sql::error()) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::editDBField', $this, $id, $values);

			return false;
		}

		if (JCORE_VERSION >= '0.7' && in_array($values['ValueType'], array(
			FORM_VALUE_TYPE_BOOL, FORM_VALUE_TYPE_DATE,
			FORM_VALUE_TYPE_INT, FORM_VALUE_TYPE_FLOAT,
			FORM_VALUE_TYPE_URL, FORM_VALUE_TYPE_LIMITED_STRING,
			FORM_VALUE_TYPE_STRING)))
		{
			$indexexists = sql::rows(sql::run(
				" SHOW INDEX FROM `{".$this->storageSQLTable."}`" .
				" WHERE `Column_name` = '".$values['Name']."'"));

			if (!$indexexists && $values['Searchable'])
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" ADD INDEX (`".$values['Name']."`);");
			if ($indexexists && !$values['Searchable'])
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" DROP INDEX `".$values['Name']."`;");
		}

		$result = true;

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::editDBField', $this, $id, $values, $result);

		return $result;
	}

	function delete($id) {
		if (!$id)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'dynamicFormFields::delete', $this, $id);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'dynamicFormFields::delete', $this, $id, $handled);

			return $handled;
		}

		$row = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicformfields}` " .
			" WHERE `ID` = '".$id."'"));

		if ($row['Name'] && $this->storageSQLTable) {
			$usedbyothers = sql::fetch(sql::run(
				" SELECT * FROM `{dynamicformfields}` " .
				" WHERE `ID` != '".$id."'" .
				" AND `Name` = '".$row['Name']."'"));

			if (!$usedbyothers) {
				$exists = sql::fetch(sql::run(
					" SHOW COLUMNS FROM `{".$this->storageSQLTable . "}`" .
					" WHERE `Field` = '".$row['Name']."'"));

				if ($exists) {
					sql::run(
						" ALTER TABLE `{".$this->storageSQLTable."}`" .
						" DROP `".$row['Name']."`;");

					if (sql::error(true)) {
						api::callHooks(API_HOOK_AFTER,
							'dynamicFormFields::delete', $this, $id);

						return false;
					}
				}
			}
		}

		if (JCORE_VERSION < '0.7') {
			$dynamicformfieldvalues = new dynamicFormFieldValues();

			$rows = sql::run(
				" SELECT * FROM `{dynamicformfieldvalues}`" .
				" WHERE `FieldID` = '".$id."'");

			while($row = sql::fetch($rows))
				$dynamicformfieldvalues->delete($row['ID']);

			unset($dynamicformfieldvalues);
		}

		sql::run(
			" DELETE FROM `{dynamicformfields}` " .
			" WHERE `ID` = '".$id."'");

		api::callHooks(API_HOOK_AFTER,
			'dynamicFormFields::delete', $this, $id);

		return true;
	}

	// ************************************************   Client Part
	static function access2Text($typeid) {
		if ($typeid > 10) {
			$ugroup = userGroups::get($typeid-10);

			if (!$ugroup)
				return false;

			return $ugroup['GroupName'];
		}

		switch($typeid) {
			case DYNAMIC_FORM_FIELD_ADMINS_ONLY:
				return __('Admins Only');
			case DYNAMIC_FORM_FIELD_GUESTS_ONLY:
				return __('Guests');
			case DYNAMIC_FORM_FIELD_USERS_ONLY:
				return __('Members');
			default:
				return __('Everyone');
		}
	}
}

?>