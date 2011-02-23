<?php

/***************************************************************************
 *            languages.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/

if (!extension_loaded('gettext')) {
	define('MANUAL_GETTEXT', true);
	include_once('lib/gettext/gettext.inc');
} else {
	define('MANUAL_GETTEXT', false);
}

function __($message, $domain = null) {
	if (!$message)
		return $message;
	
	if (!$domain)
		$domain = 'messages';
	
	if (MANUAL_GETTEXT)
		return T_dgettext($domain, $message);
	
	return dgettext($domain, $message);
}

class _languages {
	var $arguments = '';
	var $selectedID;
	var $adminPath = 'admin/site/languages';
	
	static $selected = null;
	static $textsDomains = array();
	static $selectedTextsDomain = 'messages';
	
	function __construct() {
		if (isset($_GET['languageid']))
			$this->selectedID = (int)$_GET['languageid'];
	}
	
	function SQL() {
		return
			" SELECT * FROM `{languages}` " .
			" WHERE !`Deactivated`" .
			" ORDER BY" .
			(JCORE_VERSION >= '0.7'?
				" `OrderID`,":
				null) .
			" `ID`";		
	}
	
	static function populate() {
		if (!isset($_GET['languageid']))
			$_GET['languageid'] = 0;
		
		$selected = sql::fetch(sql::run(
			" SELECT * FROM `{languages}`" .
			" WHERE 1 " .
			(SEO_FRIENDLY_LINKS && !(int)$_GET['languageid']?
				" AND '".sql::escape(url::path())."/' LIKE CONCAT(`Path`,'/%')":
				" AND `ID` = '".(int)$_GET['languageid']."'") .
			" ORDER BY `Path` DESC" .
			" LIMIT 1"));
			
		if (SEO_FRIENDLY_LINKS && $selected)
			url::setPath(preg_replace(
				'/'.preg_quote($selected['Path'], '/').'(\/|$)/i', '', 
				url::path(), 1));
		
		if (!$selected)
			$selected = sql::fetch(sql::run(
				" SELECT * FROM `{languages}`" .
				" WHERE `Default`" .
				" LIMIT 1"));
		
		if ($selected) {
			languages::$selected = $selected;
			$_GET['languageid'] = $selected['ID'];
			languages::set($selected['Locale']);
			return;
		}
		
		//We set a default language so you can translate a site without languages too
		if (defined('DEFAULT_LOCALE') && DEFAULT_LOCALE)
			languages::set(DEFAULT_LOCALE);
		else 
			languages::set('en_US');
		
		$_GET['languageid'] = 0;
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{languages}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Language'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=admin/content/pages');
		favoriteLinks::add(
			__('Blocks'), 
			'?path=admin/site/blocks');
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 200px;');
		$form->setTooltipText(__("e.g. English"));
		
		$form->add(
			__('Path'),
			'Path',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 100px;');
		$form->setValueType(FORM_VALUE_TYPE_LIMITED_STRING);
		
		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(__("e.g. en"));
		else
			$form->addAdditionalText(" (".__("e.g. en").")");
		
		$form->add(
			__('Locale Directory'),
			'Locale',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 50px;');
		
		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(__("e.g. en_UK"));
		else
			$form->addAdditionalText(" (".__("e.g. en_UK").")");
		
		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Default'),
			'Default',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			1);
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
		
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
		
		if (JCORE_VERSION >= '0.7') {
			$form->add(
				__('Order'),
				'OrderID',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 50px;');
			$form->setValueType(FORM_VALUE_TYPE_INT);
		}
		
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
		
		if (JCORE_VERSION >= '0.7' && $reorder) {
			if (!$orders)
				return false;
			
			foreach($orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{languages}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'");
			}
			
			tooltip::display(
				__("Languages have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
			
			tooltip::display(
				__("Language has been successfully deleted."),
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
				__("Language has been successfully updated.")." " .
				"<a href='?path=admin/content/pages'>" .
					__("View Pages") .
				"</a>" .
				" - " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$newid = $this->add($form->getPostArray()))
			return false;
		
		tooltip::display(
			__("Language has been successfully created.")." " .
			"<a href='?path=admin/content/pages'>" .
				__("View Pages") .
			"</a>" .
			" - " .
			"<a href='".url::uri('id, edit, delete') .
				"&amp;id=".$newid."&amp;edit=1#adminform'>" .
				__("Edit") .
			"</a>",
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminListHeader() {
		if (JCORE_VERSION >= '0.7')
			echo
				"<th><span class='nowrap'>".
					__("Order")."</span></th>";
		
		echo
			"<th><span class='nowrap'>".
				__("Title / Path")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Default")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Locale")."</span></th>";
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
		if (JCORE_VERSION >= '0.7')
			echo
				"<td>" .
					"<input type='text' name='orders[".$row['ID']."]' " .
						"value='".$row['OrderID']."' " .
						"class='order-id-entry' tabindex='1' />" .
				"</td>";
		
		echo
			"<td " .
				($row['Deactivated']?
					"style='text-decoration: line-through;' ":
					null).
				"class='auto-width bold'>" .
				$row['Title'] .
				"<div class='comment' style='padding-left: 10px;'>" .
					$row['Path'] .
				"</div>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				($row['Default']?
					__('Yes'):
					null) .
			"</td>" .
			"<td style='text-align: right;'>" .
				"<span class='nowrap'>" .
				$row['Locale'] .
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
			"<input type='submit' name='reordersubmit' value='".
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminList(&$rows) {
		if (JCORE_VERSION >= '0.7') {
			echo
				"<form action='".url::uri('edit, delete')."' method='post'>";
		}
		
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
			
			$i++;
		}
		
		echo 
				"</tbody>" .
			"</table>" .
			"<br />";
		
		if (JCORE_VERSION >= '0.7') {
			if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE) {
				$this->displayAdminListFunctions();
				
				echo
					"<div class='clear-both'></div>" .
					"<br />";
			}
						
			echo
				"</form>";
		}
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Languages Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
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
					__("Edit Language"):
					__("New Language")),
				'neweditlanguage');
		
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
			" SELECT * FROM `{languages}`" .
			" WHERE 1" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			" ORDER BY" .
			(JCORE_VERSION >= '0.7'?
				" `OrderID`,":
				null) .
			" `ID`");
			
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
					__("No languages found. Default en_US will be used for translations " .
						"(can be changed in Global Settings)."),
					TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{languages}`" .
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
		
		if (JCORE_VERSION >= '0.7') {
			if ($values['OrderID'] == '') {
				$row = sql::fetch(sql::run(
					" SELECT `OrderID` FROM `{languages}` " .
					" ORDER BY `OrderID` DESC" .
					" LIMIT 1"));
				
				$values['OrderID'] = (int)$row['OrderID']+1;
				
			} else {
				sql::run(
					" UPDATE `{languages}` SET " .
					" `OrderID` = `OrderID` + 1" .
					" WHERE `OrderID` >= '".(int)$values['OrderID']."'");
			}
		}
		
		if ($values['Default']) {
			sql::run(
				" UPDATE `{languages}` SET" .
				" `Default` = 0");
		}
		
		$newid = sql::run(
			" INSERT INTO `{languages}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			" `Locale` = '".
				sql::escape($values['Locale'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			(JCORE_VERSION >= '0.7'?
				" `OrderID` = '".
					(int)$values['OrderID']."',":
				null) .
			" `Default` = '".
				($values['Default']?
					'1':
					'0').
				"'");
			
		if (!$newid) {
			tooltip::display(
				sprintf(__("Language couldn't be created! Error: %s"), 
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
		
		if ($values['Default']) {
			sql::run(
				" UPDATE `{languages}` SET" .
				" `Default` = 0");
		}
		
		sql::run(
			" UPDATE `{languages}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			" `Locale` = '".
				sql::escape($values['Locale'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			(JCORE_VERSION >= '0.7'?
				" `OrderID` = '".
					(int)$values['OrderID']."',":
				null) .
			" `Default` = '".
				($values['Default']?
					'1':
					'0').
				"'" .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Language couldn't be updated! Error: %s"), 
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
			" DELETE FROM `{languages}` " .
			" WHERE `ID` = '".$id."'");
		
		/*	
		 * If a language deleted we set all pages to 0 so they are kept
		 * in the db for backup but are shown as No Language Selected.
		 */
		
		sql::run(
			" UPDATE `{pages}` " .
			" SET `LanguageID` = 0" .
			" WHERE `LanguageID` = '".$id."'");
			
		return true;
	}
	
	// ************************************************   Client Part
	static function get($id = 0) {
		if ($id) {
			return sql::fetch(sql::run(
				" SELECT * FROM `{languages}`" .
				" WHERE `ID` = '".(int)$id."'"));
		}
		
		$rows = sql::run(
			" SELECT * FROM `{languages}` " .
			" ORDER BY" .
			(JCORE_VERSION >= '0.7'?
				" `OrderID`,":
				null) .
			" `ID` ");
		
		if (!sql::rows($rows))
			return false;
			
		return $rows;
	}
	
	static function set($locale) {
		if (!$locale)
			return false;
		
		$locale = $locale.'.'.PAGE_CHARSET;
		putenv('LC_ALL='.$locale);
		
		if (MANUAL_GETTEXT) {
			T_setlocale(LC_ALL, $locale);
			
		} else {
			# there is a problem with Turkish locales in PHP 5 but fixed in PHP 6
			if (substr($locale, 0, 2) == 'tr' && phpversion() < '6.0') {
				setlocale(LC_COLLATE, $locale);
				setlocale(LC_MONETARY, $locale);
				setlocale(LC_TIME, $locale);
				setlocale(LC_MESSAGES, $locale);
				
			} else {
				setlocale(LC_ALL, $locale);
			}
		}
		
		return languages::loadMessages();
	}
	
	static function bind($file) {
		if (!$file)
			return false;
		
		if (MANUAL_GETTEXT) {
			$result = T_bindtextdomain($file, SITE_PATH."locale");
			T_bind_textdomain_codeset($file, PAGE_CHARSET);
			
		} else {
			$result = bindtextdomain($file, SITE_PATH."locale");
			bind_textdomain_codeset($file, PAGE_CHARSET);
		}
		
		return $result;
	}
	
	static function loadMessages() {
		return languages::load('messages');
	}
	
	static function load($file) {
		if (!$file)
			return false;
		
		languages::$textsDomains[count(languages::$textsDomains)] = $file;
		languages::bind($file);
		
		return languages::setText($file);
	}
	
	static function unload($file = null) {
		$key = count(languages::$textsDomains)-1;
		
		if ($file && languages::$textsDomains[$key] != $file)
			return false;
		
		if (!languages::$textsDomains || !count(languages::$textsDomains))
			return languages::unsetText();
		
		unset(languages::$textsDomains[count(languages::$textsDomains)-1]);
		
		if (!isset(languages::$textsDomains[count(languages::$textsDomains)-1]))
			return languages::unsetText();
		
		$file = languages::$textsDomains[count(languages::$textsDomains)-1];
		return languages::setText($file);
	}
	
	static function setText($file) {
		if (!$file)
			return false;
		
		if (languages::$selectedTextsDomain == $file)
			return true;
		
		languages::$selectedTextsDomain = $file;
		
		if (MANUAL_GETTEXT)
			return T_textdomain($file);
		
		return textdomain($file);
	}
	
	static function unsetText() {
		return languages::setText('messages');
	}
	
	static function getIDs() {
		$languageids = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(DISTINCT `ID` SEPARATOR '|') AS `IDs`" .
			" FROM `{languages}`" .
			" ORDER BY" .
			(JCORE_VERSION >= '0.7'?
				" `OrderID`,":
				null) .
			" `ID`"));
		
		if ($languageids)
			$languageids = explode('|', $languageids['IDs']);
		
		return $languageids;
	}
	
	static function getDefault() {
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{languages}`" .
			" WHERE `Default`" .
			" LIMIT 1"));
		
		return $row;
	}
	
	function displayTitle(&$row) {
		echo 
			"<a href='".$row['_Link']."'>" .
				"<span>" .
				$row['Title'] .
				"</span>" .
			"</a>";
	} 
	
	function displayOne(&$row) {
		if (SEO_FRIENDLY_LINKS) {
			$row['_Link'] = url::site().
				$row['Path'];
		
		} else {
			$row['_Link'] = url::site().'index.php' .
				'?languageid='.$row['ID'];
		}
		
		echo
			"<div " .
				(JCORE_VERSION < '0.6'?
					"id='language".$row['ID']."' ":
					null) .
				"class='language ".$row['Path']."'>";
		
		$this->displayTitle($row);
		
		echo
			"</div>";
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		if (preg_match('/(^|\/)selected($|\/)/', $this->arguments)) {
			if (languages::$selected) {
				if (SEO_FRIENDLY_LINKS)
					echo languages::$selected['Path'];
				else
					echo languages::$selected['ID'];
			}
			
			return true;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{languages}` " .
			" WHERE `Path` LIKE '".sql::escape($this->arguments)."'" .
			" LIMIT 1"));
			
		if (!$row)
			return true;
		
		echo
			"<div class='languages'>" .
				"<div " .
					(JCORE_VERSION < '0.6'?
						"id='language".$row['ID']."' ":
						null) .
					"class='language ".$row['Path']."'>";
		
		$this->displayOne($row);
		
		echo
				"</div>" .
			"</div>";
		
		return true;
	}
	
	function display() {
		if ($this->displayArguments())
			return;
		
		$rows = sql::run(
			$this->SQL());
			
		if (sql::rows($rows) < 2)
			return;
		
		echo
			"<div class='languages'>";
		
		while($row = sql::fetch($rows))
			$this->displayOne($row);
		
		echo
			"</div>";
	}
}

?>