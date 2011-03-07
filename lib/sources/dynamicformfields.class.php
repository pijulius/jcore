<?php

/***************************************************************************
 *            dynamicformfields.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/

define('DYNAMIC_FORM_FIELD_EVERYONE', 0);
define('DYNAMIC_FORM_FIELD_GUESTS_ONLY', 1);
define('DYNAMIC_FORM_FIELD_USERS_ONLY', 2);
define('DYNAMIC_FORM_FIELD_ADMINS_ONLY', 3);

class _dynamicFormFields {
	var $storageSQLTable;
	var $adminPath = 'admin/content/dynamicforms/dynamicformfields';
	
	// ************************************************   Admin Part
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
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
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			'FormID',
			'FormID',
			FORM_INPUT_TYPE_HIDDEN,
			true,
			admin::getPathID());
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
		
		if (JCORE_VERSION >= '0.2')
			$form->add(
				__('Additional Text'),
				'AdditionalText',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 350px;');
		
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
		
		if ($id && $delete) {
			$row = sql::fetch(sql::run(
				" SELECT * FROM `{dynamicformfields}` " .
				" WHERE `ID` = '".$id."'"));
				
			if ($row['Protected']) {
				tooltip::display(
					__("You are NOT allowed to delete a protected field!"),
					TOOLTIP_ERROR);
				
				return false;
			}
		}
		
		if ($reorder) {
			if (!$orders)
				return false;
			
			foreach($orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{dynamicformfields}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'");
			}
			
			tooltip::display(
				__("Fields have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
			
			tooltip::display(
				__("Field has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
			
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
			if (!$this->edit($id, $form->getPostArray()))
				return false;
				
			tooltip::display(
				__("Field has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$newid = $this->add($form->getPostArray()))
			return false;
			
		tooltip::display(
			__("Field has been successfully created.")." " .
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
				__("Title / Type")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Value Type")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
		if (JCORE_VERSION < '0.7')
			echo
				"<th><span class='nowrap'>".
					__("Values")."</span></th>";
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
	}
	
	function displayAdminListItemOptions(&$row) {
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
						"title='".htmlspecialchars(__("Values"), ENT_QUOTES) .
						(JCORE_VERSION >= '0.5'?
							" (".$values['Rows'].")":
							null) .
							"' " .
						"href='".url::uri('ALL') .
						"?path=".admin::path()."/".$row['ID']."/dynamicformfieldvalues'>" .
						(ADMIN_ITEMS_COUNTER_ENABLED && $values['Rows']?
							"<span class='counter'>" .
								"<span>" .
									"<span>" .
									$values['Rows']."" .
									"</span>" .
								"</span>" .
							"</span>":
							null) .
					"</a>" .
				"</td>";
		}
	}
	
	function displayAdminListItemFunctions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>";
		
		if ($row['Protected'])
			echo
				"<td class='comment' title='" .
				htmlspecialchars(__("Protected Field"), ENT_QUOTES)."'>" .
				(JCORE_VERSION < '0.6'?
					__("Protected"):
					null) .
				"</td>";
		else	
			echo
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayAdminListItemSelected(&$row) {
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
		admin::displayTitle(__('Form Fields'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$owner = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicforms}`" .
			" WHERE `ID` = '".admin::getPathID()."'"));
		
		$this->storageSQLTable = $owner['SQLTable'];
		
		$this->displayAdminTitle($owner['Title']);
		$this->displayAdminDescription();
			
		$edit = null;
		$id = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		echo
			"<div class='admin-content'>";
		
		if (!$owner) {		
			tooltip::display(__("Form couldn't be found!"),
				TOOLTIP_ERROR);
				
			echo "</div>";
			return;
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
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			$verifyok = $this->verifyAdmin($form);
		}
		
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
			" WHERE `FormID` = '".admin::getPathID()."'" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			" ORDER BY `OrderID`, `ID`");
			
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No form fields found."),
				TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{dynamicformfields}`" .
					" WHERE `FormID` = '".admin::getPathID()."'" .
					" AND `ID` = '".$id."'"));
				
				if ($row['Protected']) {
					$form->edit('ValueType', null, 'ValueType', FORM_INPUT_TYPE_HIDDEN);
					$form->edit('Name', null, 'Name', FORM_INPUT_TYPE_HIDDEN);
				}
				
				$form->setValues($row);
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
			
		if (!$newid) {
			tooltip::display(
				sprintf(__("Field couldn't be created! Error: %s"), 
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
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Field couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
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
		
		if (sql::display(true))
			return false;
			
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
			elseif ($indexexists)
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" DROP INDEX `".$values['Name']."`;");
		}
		
		return true;
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
			
		$newexists = sql::fetch(sql::run(
			" SHOW COLUMNS FROM `{".$this->storageSQLTable . "}`" .
			" WHERE `Field` = '".$values['Name']."'"));
		
		if ($newexists)
			$row['Name'] = $values['Name'];
			
		$oldexists = sql::fetch(sql::run(
			" SHOW COLUMNS FROM `{".$this->storageSQLTable . "}`" .
			" WHERE `Field` = '".$row['Name']."'"));
			
		if (!$row['Name'] || (!$oldexists && !$newexists))
			return $this->addDBField($values);
		
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
		
		if (sql::display(true))
			return false;
			
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
			elseif ($indexexists)
				sql::run(
					" ALTER TABLE `{".$this->storageSQLTable."}`" .
					" DROP INDEX `".$values['Name']."`;");
		}
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
			
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
					
					if (sql::display(true))
						return false;
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
		
		return true;
	}
	
	// ************************************************   Client Part
	static function access2Text($typeid) {
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