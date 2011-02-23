<?php

/***************************************************************************
 *            dynamicforms.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
include_once('lib/form.class.php');
include_once('lib/email.class.php');
include_once('lib/dynamicformdata.class.php');
 
email::add('DynamicForm',
		"%FORMTITLE% at %PAGE_TITLE%",
		"Dear Webmaster,\n\n" .
		"\"%FORMTITLE%\" form has been completed on your website with " .
			"the following content:\n\n" .
		"%FORMELEMENTS%\n" .
		"Sincerely,\n" .
		"%PAGE_TITLE%");

class _dynamicForms extends form {
	var $formID = null;
	var $sendNotificationEmail = true;
	var $sendNotificationEmailTo = WEBMASTER_EMAIL;
	var $successMessage = null;
	var $storageSQLTable;
	var $textsDomain = 'messages';
	var $adminPath = 'admin/content/dynamicforms';
	
	function __construct($title = null, $id = null, $method = 'post') {
		parent::__construct($title, $id, $method);
		$this->formID = $this->id;
		$this->textsDomain = languages::$selectedTextsDomain;
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{dynamicforms}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Form'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=admin/content/pages');
		favoriteLinks::add(
			__('View Website'), 
			SITE_URL);
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 250px;');
		
		$form->add(
			__('Form ID'),
			'FormID',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 150px;');
		
		$form->add(
			__('Notification Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
			
		$form->add(
			__('Send Email'),
			'SendNotificationEmail',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			1);
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
		
		if (JCORE_VERSION >= '0.7') {
			$form->add(
				__('Send To'),
				'SendNotificationEmailTo',
				FORM_INPUT_TYPE_EMAIL);
			$form->setStyle('width: 200px;');
		}
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Storage Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
			
		$form->add(
			__('SQL Table'),
			'SQLTable',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 150px;');
		$form->setValueType(FORM_VALUE_TYPE_LIMITED_STRING);
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Method'),
			'Method',
			FORM_INPUT_TYPE_SELECT);
			
		$form->addValue('post', 'POST');
		$form->addValue('get', 'GET');
		
		if (JCORE_VERSION >= '0.7') {
			$form->add(
				__('Success Message'),
				'SuccessMessage',
				FORM_INPUT_TYPE_TEXTAREA);
			$form->setStyle('width: ' .
				(JCORE_VERSION >= '0.7'?
					'90%':
					'300px') .
					'; height: 100px;');
		}
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
	}
	
	function verifyAdmin(&$form) {
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($id && $delete) {
			$row = sql::fetch(sql::run(
				" SELECT * FROM `{dynamicforms}` " .
				" WHERE `ID` = '".$id."'"));
				
			if ($row['Protected']) {
				tooltip::display(
					__("You are NOT allowed to delete a protected form!"),
					TOOLTIP_ERROR);
				
				return false;
			}
		}
		
		if ($delete) {
			if (!$this->deleteForm($id))
				return false;
				
			tooltip::display(
				__("Form has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if ($edit) {
			if (!$this->editForm($id, $form->getPostArray()))
				return false;
				
			tooltip::display(
				__("Form has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$newid = $this->addForm($form->getPostArray()))
			return false;
		
		tooltip::display(
			__("Form has been successfully created.")." " .
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
				__("Title / Form ID")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Email")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
		echo
			"<th><span class='nowrap'>".
				__("Data")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Fields")."</span></th>";
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
			"<td class='auto-width'>" .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."' " .
					"class='bold'>".
					__($row['Title']) .
				"</a>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					$row['FormID'] .
				"</div>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				($row['SendNotificationEmail']?
					__('Yes'):
					'') .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
		if (JCORE_VERSION >= '0.5') {
			if ($row['SQLTable'])
				$dbitems = sql::fetch(sql::run(
					" SELECT COUNT(*) AS `Rows`" .
					" FROM `{".$row['SQLTable']."}`" .
					" LIMIT 1"));
			
			$fields = sql::fetch(sql::run(
				" SELECT COUNT(*) AS `Rows`" .
				" FROM `{dynamicformfields}`" .
				" WHERE `FormID` = '".$row['ID']."'" .
				" LIMIT 1"));
		}
			
		echo
			"<td align='center'>" .
				($row['SQLTable'] && (JCORE_VERSION >= '0.7' || !$row['Protected'])?
					"<a class='admin-link db' " .
						"title='".htmlspecialchars(__("Browse Data"), ENT_QUOTES) .
						(JCORE_VERSION >= '0.5'?
							" (".$dbitems['Rows'].")":
							null) .
							"' " .
						"href='".url::uri('ALL') .
							(JCORE_VERSION >= '0.7' && $row['BrowseDataURL']?
								$row['BrowseDataURL']:
								"?path=".admin::path()."/".$row['ID']."/dynamicformdata") .
							"'>" .
						(ADMIN_ITEMS_COUNTER_ENABLED && $dbitems['Rows']?
							"<span class='counter'>" .
								"<span>" .
									"<span>" .
									$dbitems['Rows']."" .
									"</span>" .
								"</span>" .
							"</span>":
							null) .
					"</a>":
					null) .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link fields' " .
					"title='".htmlspecialchars(__("Fields"), ENT_QUOTES) .
					(JCORE_VERSION >= '0.5'?
						" (".$fields['Rows'].")":
						null) .
						"' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/dynamicformfields'>" .
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
				"<td class='comment' " .
					"title='".htmlspecialchars(__("Protected Form"), ENT_QUOTES)."'>" .
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
		if (JCORE_VERSION >= '0.7' &&
			$row['SendNotificationEmail'] && $row['SendNotificationEmailTo'])
			admin::displayItemData(
				__("Send Email To"),
				$row['SendNotificationEmailTo']);
				
		if ($row['SQLTable'])
			admin::displayItemData(
				__("SQL Table"),
				$row['SQLTable']);
			
		admin::displayItemData(
			__("Method"),
			$row['Method']);
		
		if (JCORE_VERSION >= '0.7' && $row['SuccessMessage'])
			admin::displayItemData(
				tooltip::construct($row['SuccessMessage'], TOOLTIP_SUCCESS));
		
		dynamicForms::displayPreview($row['FormID']);
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
	
	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Dynamic Forms Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
		echo "<p>".
			__("To implement a form in your post just add the following code " .
				"<code>{forms}formid{/forms}</code> " .
				"to your content where <i>formid</i> is your form's id, for e.g. contact.") .
			"</p>";
	}
	
	function displayAdmin() {
		$edit = null;
		$id = null;
		
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
					__("Edit Form"):
					__("New Form")),
				'neweditform');
					
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
		
		$rows = sql::run(
			" SELECT * FROM `{dynamicforms}`" .
			" WHERE 1" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			" ORDER BY `Title`");
			
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				__("No dynamic forms found."),
				TOOLTIP_NOTIFICATION);
		
		if (!$edit && !$form->submitted()) {
			$form->setValue('SendNotificationEmail', 1);
		}
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{dynamicforms}`" .
					" WHERE `ID` = '".$id."'"));
			
				if ($row['Protected']) {
					$form->edit('FormID', null, 'FormID', FORM_INPUT_TYPE_HIDDEN);
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
	
	function addForm($values) {
		if (!is_array($values))
			return false;
		
		$exists = sql::fetch(sql::run(
			" SELECT ID FROM `{dynamicforms}` " .
			" WHERE `FormID` = '".
				sql::escape($values['FormID'])."'"));
			
		if ($exists) {
			tooltip::display(
				__("A form with this ID already exists! Please choose a different " .
					"id for your form."),
				TOOLTIP_ERROR);
	
			return false;
		}
		
		if ($values['SQLTable']) {
			$exists = sql::fetch(sql::run(
				" SHOW TABLES LIKE '".
					(SQL_PREFIX?
						SQL_PREFIX.'_':
						null) .
					sql::escape($values['SQLTable'])."'"));
			
			if ($exists) {
				tooltip::display(
					sprintf(__("SQL Table \"%s\" already exists! Please choose a different " .
							"table for your form."), $values['SQLTable']),
					TOOLTIP_ERROR);
				return false;
			}
			
			sql::run(
				" CREATE TABLE `{".
					sql::escape($values['SQLTable'])."}` (" .
				" `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY" .
				" ) ENGINE = MYISAM ;");
			
			if (sql::display(true))
				return false;
		}
			
		$newid = sql::run(
			" INSERT INTO `{dynamicforms}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `FormID` = '".
				sql::escape($values['FormID'])."'," .
			" `Method` = '".
				sql::escape($values['Method'])."'," .
			" `SQLTable` = '".
				sql::escape($values['SQLTable'])."'," .
			(JCORE_VERSION >= '0.7'?
				" `SuccessMessage` = '".
					sql::escape($values['SuccessMessage'])."'," .
				" `SendNotificationEmailTo` = '" .
					sql::escape($values['SendNotificationEmailTo'])."',":
				null) .
			" `SendNotificationEmail` = '".
				($values['SendNotificationEmail']?
					'1':
					'0').
				"'");
			
		if (!$newid) {
			tooltip::display(
				sprintf(__("Form couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return $newid;
	}
	
	function editForm($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		$exists = sql::fetch(sql::run(
			" SELECT ID FROM `{dynamicforms}` " .
			" WHERE `FormID` = '".
				sql::escape($values['FormID'])."'" .
			" AND `ID` != '".$id."'"));
			
		if ($exists) {
			tooltip::display(
				__("A form with this ID already exists! Please choose a different " .
					"id for your form."),
				TOOLTIP_ERROR);
	
			return false;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicforms}`" .
			" WHERE `ID` = '".$id."'"));
			
		if (!$row['SQLTable'] && $values['SQLTable']) {
			$exists = sql::fetch(sql::run(
				" SHOW TABLES LIKE '".
					(SQL_PREFIX?
						SQL_PREFIX.'_':
						null) .
					sql::escape($values['SQLTable'])."'"));
			
			if ($exists) {
				tooltip::display(
					sprintf(__("SQL Table \"%s\" already exists! Please choose a different " .
							"table for your form."), $values['SQLTable']),
					TOOLTIP_ERROR);
				return false;
			}
			
			sql::run(
				" CREATE TABLE `{".
					sql::escape($values['SQLTable'])."}` (" .
				" `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY" .
				" ) ENGINE = MYISAM ;");
			
			if (sql::display(true))
				return false;
			
			$formfields = new dynamicFormFields();
			$formfields->storageSQLTable = $values['SQLTable'];
			
			$fields = sql::run(
				" SELECT * FROM `{dynamicformfields}`" .
				" WHERE `FormID` = '".$id."'" .
				" AND `Name` != ''" .
				" AND `ValueType`" .
				" ORDER BY `OrderID`, `ID`");
			
			while($field = sql::fetch($fields)) {
				if (!$formfields->addDBField($field)) {
					tooltip::display(
						__("Field for the storage table couldn't be added. Please see the SQL " .
							"error above and report it to webmaster."),
						TOOLTIP_ERROR);
					
					return false;
				}
			}
			
			unset($formfields);
		}
			
		if ($row['SQLTable'] && $values['SQLTable'] && 
			$values['SQLTable'] != $row['SQLTable']) 
		{
			if ((JCORE_VERSION < '0.7' && $row['Protected']) || 
				(JCORE_VERSION >= '0.7' && $row['ProtectedSQLTable'])) 
			{
				tooltip::display(
					__("Protected SQL Tables cannot be renamed / deleted!"),
					TOOLTIP_ERROR);
				return false;
			}
			
			$exists = sql::fetch(sql::run(
				" SHOW TABLES LIKE '".
					(SQL_PREFIX?
						SQL_PREFIX.'_':
						null) .
					sql::escape($values['SQLTable'])."'"));
			
			if ($exists) {
				tooltip::display(
					sprintf(__("SQL Table \"%s\" already exists! Please choose a different " .
							"table for your form."), $values['SQLTable']),
					TOOLTIP_ERROR);
				return false;
			}
			
			sql::run(
				" RENAME TABLE `{".$row['SQLTable']. "}`" .
				" TO `{".$values['SQLTable']."}` ;");
			
			if (sql::display(true))
				return false;
		}
			
		if ($row['SQLTable'] && !$values['SQLTable']) {
			if ((JCORE_VERSION < '0.7' && $row['Protected']) || 
				(JCORE_VERSION >= '0.7' && $row['ProtectedSQLTable'])) 
			{
				tooltip::display(
					__("Protected SQL Tables cannot be renamed / deleted!"),
					TOOLTIP_ERROR);
				return false;
			}
			
			sql::run(
				" DROP TABLE `{".$row['SQLTable']. "}`;");
			
			if (sql::display(true))
				return false;
		}
			
		sql::run(
			" UPDATE `{dynamicforms}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			(!$row['Protected']?
				" `FormID` = '".
					sql::escape($values['FormID'])."',":
				null) .
			" `Method` = '".
				sql::escape($values['Method'])."'," .
			" `SQLTable` = '".
				sql::escape($values['SQLTable'])."'," .
			(JCORE_VERSION >= '0.7'?
				" `SuccessMessage` = '".
					sql::escape($values['SuccessMessage'])."'," .
				" `SendNotificationEmailTo` = '" .
					sql::escape($values['SendNotificationEmailTo'])."',":
				null) .
			" `SendNotificationEmail` = '".
				($values['SendNotificationEmail']?
					'1':
					'0').
				"'" .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Form couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	function deleteForm($id) {
		if (!$id)
			return false;
			
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicforms}` " .
			" WHERE `ID` = '".$id."'"));
		
		if ($row['SQLTable']) {
			$usedbyothers = sql::fetch(sql::run(
				" SELECT * FROM `{dynamicforms}` " .
				" WHERE `ID` != '".$id."'" .
				" AND `SQLTable` = '".$row['SQLTable']."'"));
			
			if (!$usedbyothers) {
				sql::run(
					" DROP TABLE `{".$row['SQLTable']."}`;");
				
				if (sql::display(true))
					return false;
			}
		}
				
		$dynamicformfields = new dynamicFormFields();
		
		$rows = sql::run(
			" SELECT * FROM `{dynamicformfields}`" .
			" WHERE `FormID` = '".$id."'");
			
		while($row = sql::fetch($rows))
			$dynamicformfields->delete($row['ID']);
		
		sql::run(
			" DELETE FROM `{dynamicforms}` " .
			" WHERE `ID` = '".$id."'");
		
		unset($dynamicformfields);
		return true;
	}
	
	// ************************************************   Client Part
	function addData($data) {
		if (!$data || !is_array($data))
			return false;
		
		$this->load(false);
		
		if (!$this->storageSQLTable)
			return false;
		
		$formdata = new dynamicFormData();
		$formdata->storageSQLTable = $this->storageSQLTable;
		$newid = $formdata->add($data);
		unset($formdata);
		
		return $newid;
	}
	
	function editData($id, $data) {
		if (!$id || !$data || !is_array($data))
			return false;
		
		$this->load(false);
		
		if (!$this->storageSQLTable)
			return false;
		
		$formdata = new dynamicFormData();
		$formdata->storageSQLTable = $this->storageSQLTable;
		$newid = $formdata->edit($id, $data);
		unset($formdata);
		
		return $newid;
	}
	
	function uploadFile($file) {
		$formdata = new dynamicFormData();
		
		$filename = $formdata->upload($file);
		$filename = $formdata->storageSubFolder.'/'.$filename;
		
		unset($formdata);
		return $filename;
	}
	
	function sendEmail($to = null, $from = null) {
		$email = new email();
		
		$email->load('DynamicForm');
		
		if ($to)
			$email->to = $to;
		else
			$email->to = $this->sendNotificationEmailTo;
		
		$email->variables = array(
			'FormTitle' => $this->title,
			'FormElements' => ''); 
		
		foreach($this->elements as $elementid => $element) {
			if (in_array($element['Type'], array(
					FORM_INPUT_TYPE_TEXT,
					FORM_INPUT_TYPE_EMAIL,
					FORM_INPUT_TYPE_CHECKBOX,
					FORM_INPUT_TYPE_RADIO,
					FORM_INPUT_TYPE_SELECT,
					FORM_INPUT_TYPE_TEXTAREA,
					FORM_INPUT_TYPE_HIDDEN,
					FORM_INPUT_TYPE_FILE,
					FORM_INPUT_TYPE_MULTISELECT,
					FORM_INPUT_TYPE_TIMESTAMP,
					FORM_INPUT_TYPE_DATE,
					FORM_INPUT_TYPE_EDITOR,
					FORM_INPUT_TYPE_RECIPIENT_SELECT
				)))
			{
				if ($element['ValueType'] == FORM_VALUE_TYPE_ARRAY)
					$value = implode('; ', (array)$this->get($element['Name']));
				elseif ($element['ValueType'] == FORM_VALUE_TYPE_BOOL)
					$value = ($this->get($element['Name'])?__("Yes"):__("No"));
				else
					$value = $this->get($element['Name']);
				
				if ($element['Type'] == FORM_INPUT_TYPE_TEXTAREA)
					$email->variables['FormElements'] .= 
						"\n".__($element['Title']).":\n".
							$value."\n\n";
				else
					$email->variables['FormElements'] .= 
						__($element['Title']).": ".
							$value."\n";
			
				if ($element['Type'] == FORM_INPUT_TYPE_EMAIL)
					$email->from = $value;
			}
		}
		
		$emailsent = $email->send();
		unset($email);
		
		return $emailsent;
	}
	
	function verify($customdatahandling = false) {
		if (!parent::verify())
			return false;
		
		if ($customdatahandling)
			return true;
		
		if ($this->fileElements && is_array($this->fileElements) && 
			count($this->fileElements)) 
		{
			foreach($this->fileElements as $fieldid) {
				if (!$file = $this->getFile($fieldid))
					continue;
				
				if (!$filename = $this->uploadFile($file))
					return false;
				
				$this->set($this->elements[$fieldid]['Name'], $filename);
			}
		}
		
		if ($this->sendNotificationEmail && !$this->sendEmail())
			return false;
		
		if ($this->recipientElements && is_array($this->recipientElements) && 
			count($this->recipientElements)) 
		{
			foreach($this->recipientElements as $fieldid) {
				if (!$recipientemail = $this->get($fieldid))
					continue;
				
				if (!$this->sendEmail($recipientemail))
					return false;
			}
		}
		
		if ($this->storageSQLTable) {
			if (!$this->addData($this->getPostArray()))
				return false;
		}
		
		return true;
	}
	
	function load($addformbuttons = true) {
		$this->clear();
		
		$form = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicforms}`" .
			" WHERE `FormID` = '".sql::escape($this->formID)."'"));
			
		$this->title = __($form['Title'], $this->textsDomain);
		$this->method = $form['Method'];
		$this->storageSQLTable = $form['SQLTable'];
		$this->sendNotificationEmail = $form['SendNotificationEmail'];
		
		if (JCORE_VERSION >= '0.7' && $form['SendNotificationEmailTo'])
			$this->sendNotificationEmailTo = $form['SendNotificationEmailTo'];
		
		if (JCORE_VERSION >= '0.7' && $form['SuccessMessage'])
			$this->successMessage = $form['SuccessMessage'];
		
		$rows = sql::run(
			" SELECT * FROM `{dynamicformfields}`" .
			" WHERE `FormID` = '".$form['ID']."'" .
			(JCORE_VERSION >= '0.7' &&
			 (!$GLOBALS['USER']->loginok || !$GLOBALS['USER']->data['Admin'])?
					" AND (!`ViewableBy` OR " .
					($GLOBALS['USER']->loginok?
						" `ViewableBy` = 2":
						" `ViewableBy` = 1") .
					" )":
				null) .
			" ORDER BY `OrderID`, `ID`");
		
		$presetvalues = array();
		$formhassubmitbutton = false;
		
		while ($row = sql::fetch($rows)) {
			if ($row['TypeID'] == FORM_INPUT_TYPE_SUBMIT)
				$formhassubmitbutton = true;
			
			if (JCORE_VERSION < '0.7') {
				$values = sql::fetch(sql::run(
					" SELECT GROUP_CONCAT(IF(`ValueTitle` IS NOT NULL AND `ValueTitle` != '', " .
						" CONCAT(`ValueTitle`, '=', `Value`), `Value`)" .
						" ORDER BY `Selected` DESC, `OrderID`, `ID` SEPARATOR '\n') AS `Values`" .
					" FROM `{dynamicformfieldvalues}` " .
					" WHERE `FieldID` = '".$row['ID']."'" .
					" GROUP BY `FieldID`"));
				
				$row['Values'] = null;
				
				if (isset($values['Values']))
					$row['Values'] = $values['Values'];
			}
			
			$values = array();
			$presetvalue = false;
			$defaultvalue = null;
			
			if (in_array($row['TypeID'], array(
				FORM_INPUT_TYPE_SELECT, FORM_INPUT_TYPE_MULTISELECT,
				FORM_INPUT_TYPE_CHECKBOX, FORM_INPUT_TYPE_RADIO,
				FORM_INPUT_TYPE_RECIPIENT_SELECT)))
			{
				$values = explode("\n", str_replace("\r", "", $row['Values']));
				
				if (count($values) == 1 && 
					in_array($row['TypeID'], array(
						FORM_INPUT_TYPE_CHECKBOX, FORM_INPUT_TYPE_RADIO))) 
				{
					$value = current($values);
					
					if (strpos($value, '*') === 0) {
						$value = ltrim($value, '* ');
						$presetvalue = true;
					}
					
					if ($value) {
						if (strstr($value, '='))
							list($valuetitle, $value) = array_map('trim', explode('=', current($values)));
						
						$defaultvalue = $value;
					} else {
						$defaultvalue = 'Yes';
					}
					
					if ($presetvalue && !isset($GLOBALS['_'.strtoupper($this->method)][$row['Name']]))
						$presetvalues[$row['Name']] = $defaultvalue;
				}
				
			} else {
				$defaultvalue = (string)$row['Values'];
			}
			
			$this->add(
				__($row['Title'], $this->textsDomain),
				$row['Name'],
				$row['TypeID'],
				$row['Required'],
				($defaultvalue?
					$defaultvalue:
					null));
			
			if ($row['ValueType'])
				$this->setValueType($row['ValueType']);
			
			if (isset($row['PlaceholderText']) && $row['PlaceholderText'])
				$this->setPlaceholderText(
					__($row['PlaceholderText'], $this->textsDomain));
				
			if (isset($row['TooltipText']) && $row['TooltipText'])
				$this->setTooltipText(
					__($row['TooltipText'], $this->textsDomain));
				
			if (isset($row['AdditionalText']) && $row['AdditionalText'])
				$this->addAdditionalText(
					__($row['AdditionalText'], $this->textsDomain));
				
			if ($row['Attributes'])
				$this->addAttributes($row['Attributes']);
				
			if ($row['Style'])
				$this->setStyle($row['Style']);
			
			if (isset($defaultvalue))
				continue;
			
			if (!$row['Values'])
				continue;
				
			$selectedvalues = array();
			
			foreach($values as $value) {
				$valuetitle = null;
				$presetvalue = false;
				
				if (strpos($value, '*') === 0) {
					$value = ltrim($value, '* ');
					$presetvalue = true;
				}
				
				if (strstr($value, '='))
					list($valuetitle, $value) = array_map('trim', explode('=', $value));
				
				if ($valuetitle)
					$valuetitle = __($valuetitle, $this->textsDomain);
				
				$this->addValue($value, $valuetitle);
				
				if (in_array($row['TypeID'], array(
					FORM_INPUT_TYPE_SELECT, FORM_INPUT_TYPE_RADIO, 
					FORM_INPUT_TYPE_RECIPIENT_SELECT))) 
				{
					if ($presetvalue && !isset($GLOBALS['_'.strtoupper($this->method)][$row['Name']]))
						$presetvalues[$row['Name']] = $value;
					
					continue;
				}
					
				if (in_array($row['TypeID'], array(
					FORM_INPUT_TYPE_MULTISELECT, FORM_INPUT_TYPE_CHECKBOX))) 
				{
					if ($presetvalue && !isset($GLOBALS['_'.strtoupper($this->method)][$row['Name']]))
						$presetvalues[$row['Name']][] = $value;
					
					continue;
				}
			}
		}
		
		if (!$formhassubmitbutton && $addformbuttons)
			$this->addSubmitButtons();
		
		if (count($presetvalues) && !$this->submitted())
			$this->setValues($presetvalues);
	}
	
	static function displayPreview($formid) {
		$form = new dynamicForms($formid);
		$form->textsDomain = 'messages';
		$form->preview = true;
		$form->ignorePageBreaks = true;
		$form->load();
		$form->display();
		unset($form);
	}
	
	static function searchableFields($formid) {
		if (!$formid)
			return null;
		
		$form = sql::fetch(sql::run(
			" SELECT `ID` FROM `{dynamicforms}`" .
			" WHERE `FormID` = '".sql::escape($formid)."'"));
		
		if (!$form)
			return null;
		
		$fields = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(DISTINCT `Name` SEPARATOR '|') AS `Fields`" .
			" FROM `{dynamicformfields}`" .
			" WHERE `FormID` = '".$form['ID']."'" .
			" AND `Searchable`" .
			" LIMIT 1"));
		
		if (!$fields)
			return null;
		
		return explode('|', $fields['Fields']);
	}
}

?>