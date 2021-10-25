<?php

/***************************************************************************
 *            languages.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

define('PHP_GETTEXT', extension_loaded('gettext'));

if (!defined('LC_MESSAGES'))
	define('LC_MESSAGES', LC_ALL);

if (!PHP_GETTEXT || (defined('MANUAL_GETTEXT') && MANUAL_GETTEXT))
	include_once('lib/gettext/gettext.inc');

function __($message, $domain = null) {
	if (!$message)
		return $message;

	if (!$domain)
		$domain = 'messages';

	if ((defined('MANUAL_GETTEXT') && MANUAL_GETTEXT) || !PHP_GETTEXT)
		return T_dgettext($domain, $message);

	return dgettext($domain, $message);
}

class _languages {
	var $arguments = null;
	var $selectedID;
	var $adminPath = 'admin/site/languages';

	static $selected = null;
	static $textsDomains = array();
	static $selectedTextsDomain = 'messages';
	static $selectedLocale = '';

	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::languages', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::languages', $this, $handled);

			return $handled;
		}

		if (isset($_GET['languageid']))
			$this->selectedID = (int)$_GET['languageid'];

		api::callHooks(API_HOOK_AFTER,
			'languages::languages', $this);
	}

	function SQL() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::SQL', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::SQL', $this, $handled);

			return $handled;
		}

		$sql =
			" SELECT * FROM `{languages}` " .
			" WHERE `Deactivated` = 0" .
			" ORDER BY" .
			(JCORE_VERSION >= '0.7'?
				" `OrderID`,":
				null) .
			" `ID`";

		api::callHooks(API_HOOK_AFTER,
			'languages::languages', $this, $sql);

		return $sql;
	}

	static function populate() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::populate', $_ENV);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::populate', $_ENV, $handled);

			return $handled;
		}

		if (!isset($_GET['languageid']))
			$_GET['languageid'] = 0;

		if (isset($GLOBALS['ADMIN']) && (bool)$GLOBALS['ADMIN']) {
			//We always set the default for admin so you can have it in different
			//language independent of the default language set for the website
			languages::setDefault();

			api::callHooks(API_HOOK_AFTER,
				'languages::populate', $_ENV);

			return false;
		}

		$selected = sql::fetch(sql::run(
			" SELECT * FROM `{languages}`" .
			(SEO_FRIENDLY_LINKS && !(int)$_GET['languageid']?
				" WHERE '".sql::escape(url::path())."/' LIKE CONCAT(`Path`,'/%')":
				" WHERE `ID` = '".(int)$_GET['languageid']."'") .
			" ORDER BY `Path` DESC" .
			" LIMIT 1"));

		if (SEO_FRIENDLY_LINKS && $selected)
			url::setPath(preg_replace(
				'/'.preg_quote($selected['Path'], '/').'(\/|$)/i', '',
				url::path(), 1));

		if (!$selected)
			$selected = sql::fetch(sql::run(
				" SELECT * FROM `{languages}`" .
				" WHERE `Default` = 1" .
				" LIMIT 1"));

		if ($selected) {
			languages::$selected = $selected;
			$_GET['languageid'] = $selected['ID'];
			languages::set($selected['Locale']);

		} else {
			//We set a default language so you can translate a site without languages too
			languages::setDefault();
			$_GET['languageid'] = 0;
		}

		api::callHooks(API_HOOK_AFTER,
			'languages::populate', $_ENV);
	}

	// ************************************************   Admin Part
	function countAdminItems() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::countAdminItems', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::countAdminItems', $this, $handled);

			return $handled;
		}

		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{languages}`" .
			" LIMIT 1"));

		api::callHooks(API_HOOK_AFTER,
			'languages::countAdminItems', $this, $row['Rows']);

		return $row['Rows'];
	}

	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::setupAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::setupAdmin', $this, $handled);

			return $handled;
		}

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Language'),
				'?path='.admin::path().'#adminform');

		favoriteLinks::add(
			__('Pages / Posts'),
			'?path=' .
			(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));
		favoriteLinks::add(
			__('Blocks'),
			'?path=admin/site/blocks');

		api::callHooks(API_HOOK_AFTER,
			'languages::setupAdmin', $this);
	}

	function setupAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::setupAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::setupAdminForm', $this, $form, $handled);

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

		$form->addAdditionalText(
			"<a href='".url::uri('request, locales').
				"&amp;request=".url::path() .
				"&amp;locales=1' " .
				"class='select-link ajax-content-link' " .
				"title='".htmlchars(__("Select Locale"), ENT_QUOTES)."'>" .
				__("Select Locale") .
			"</a>" .
			"<br /><span class='comment'>(" .
				__("e.g. en_UK") .
			")</span>");

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

		api::callHooks(API_HOOK_AFTER,
			'languages::setupAdminForm', $this, $form);
	}

	function verifyAdmin(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::verifyAdmin', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::verifyAdmin', $this, $form, $handled);

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

		if (JCORE_VERSION >= '0.7' && $reorder) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'languages::verifyAdmin', $this, $form);
				return false;
			}

			foreach((array)$orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{languages}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'");
			}

			tooltip::display(
				__("Languages have been successfully re-ordered."),
				TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'languages::verifyAdmin', $this, $form, $reorder);

			return true;
		}

		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'languages::verifyAdmin', $this, $form);
				return false;
			}

			$result = $this->delete($id);

			if ($result)
				tooltip::display(
					__("Language has been successfully deleted."),
					TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'languages::verifyAdmin', $this, $form, $result);

			return $result;
		}

		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'languages::verifyAdmin', $this, $form);

			return false;
		}

		if (!$form->get('Path'))
			$form->set('Path', url::genPathFromString($form->get('Title')));

		if ($edit) {
			$result = $this->edit($id, $form->getPostArray());

			if ($result)
				tooltip::display(
					__("Language has been successfully updated.")." " .
					"<a href='?path=".(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems')."'>" .
						__("View Pages") .
					"</a>" .
					" - " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'languages::verifyAdmin', $this, $form, $result);

			return $result;
		}

		$newid = $this->add($form->getPostArray());

		if ($newid) {
			tooltip::display(
				__("Language has been successfully created.")." " .
				"<a href='?path=".(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems')."'>" .
					__("View Pages") .
				"</a>" .
				" - " .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$newid."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);

			$form->reset();
		}

		api::callHooks(API_HOOK_AFTER,
			'languages::verifyAdmin', $this, $form, $result);

		return $newid;
	}

	function displayAdminAvailableLocales() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayAdminAvailableLocales', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayAdminAvailableLocales', $this, $handled);

			return $handled;
		}

		if (!isset($_GET['ajaxlimit']))
			echo
				"<div class='languages-available-locales'>";

		echo
				"<div class='form-title'>".__('Available Locales') .
					"&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</div>" .
				"<table cellpadding='0' cellspacing='0' class='form-content list'>" .
					"<thead>" .
					"<tr>" .
						"<th>" .
							"<span class='nowrap'>".
							__("Select") .
							"</span>" .
						"</th>" .
						"<th>" .
							"<span class='nowrap'>".
							__("Locale") .
							"</span>" .
						"</th>" .
						"<th>" .
							"<span class='nowrap'>".
							__("Language") .
							"</span>" .
						"</th>" .
					"</tr>" .
					"</thead>" .
					"<tbody>";

		$dir = SITE_PATH.'locale/';
		$dirs = array();

		if (is_dir($dir) && $dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if (!is_dir($dir.'/'.$file) || strpos($file, '.') === 0)
					continue;

				$language = __('unknown/file');

				if (preg_match('/X-Poedit-Language: ([a-zA-Z0-9-_\. \(\)]+)/i',
					@file_get_contents($dir.$file.'/LC_MESSAGES/messages.po', false, null, -1, 1024), $matches))
           			$language = $matches[1];

				$dirs[$file] = array(
					'Title' => $language,
					'Location' => $file);
			}

			closedir($dh);
		}

		if (defined('JCORE_PATH') && JCORE_PATH) {
			$dir = JCORE_PATH.'locale/';

			if (is_dir($dir) && $dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if (!is_dir($dir.'/'.$file) || strpos($file, '.') === 0)
						continue;

					$language = __('unknown/file');

					if (preg_match('/X-Poedit-Language: ([a-zA-Z0-9-_\. \(\)]+)/i',
						@file_get_contents($dir.$file.'/LC_MESSAGES/messages.po', false, null, -1, 1024), $matches))
            			$language = $matches[1];

					$dirs[$file] = array(
						'Title' => $language,
						'Location' => $file);
				}

				closedir($dh);
			}
		}

		$paging = new paging(10);

		$paging->track('ajaxlimit');
		$paging->ajax = true;
		$paging->setTotalItems(count($dirs));

		ksort($dirs);
		$dirs = array_slice($dirs, $paging->getStart(), 10);

		if (!is_array($dirs))
			$dirs = array();

		$i = 1;
		foreach($dirs as $language) {
			echo
				"<tr".($i%2?" class='pair'":NULL).">" .
					"<td align='center'>" .
						"<a href='javascript://' " .
							"onclick=\"" .
								"$('#neweditlanguageform #entryLocale').val('" .
									htmlchars($language['Location'], ENT_QUOTES)."');" .
								(JCORE_VERSION >= '0.7'?
									"$(this).closest('.tipsy').hide();":
									"$(this).closest('.qtip').qtip('hide');") .
								"\" " .
							"class='languages-select-locale select-link'>" .
						"</a>" .
					"</td>" .
					"<td class='auto-width'>" .
						"<b>".$language['Location']."</b> " .
					"</td>" .
					"<td>" .
						$language['Title'] .
					"</td>" .
				"</tr>";

			$i++;
		}

		echo
					"</tbody>" .
				"</table>";

		$paging->display();

		if (!isset($_GET['ajaxlimit']))
			echo
				"</div>";

		api::callHooks(API_HOOK_AFTER,
			'languages::displayAdminAvailableLocales', $this);
	}

	function displayAdminListHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayAdminListHeader', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayAdminListHeader', $this, $handled);

			return $handled;
		}

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

		api::callHooks(API_HOOK_AFTER,
			'languages::displayAdminListHeader', $this);
	}

	function displayAdminListHeaderOptions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayAdminListHeaderOptions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayAdminListHeaderOptions', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'languages::displayAdminListHeaderOptions', $this);
	}

	function displayAdminListHeaderFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayAdminListHeaderFunctions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayAdminListHeaderFunctions', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'languages::displayAdminListHeaderFunctions', $this);
	}

	function displayAdminListItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayAdminListItem', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayAdminListItem', $this, $row, $handled);

			return $handled;
		}

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

		api::callHooks(API_HOOK_AFTER,
			'languages::displayAdminListItem', $this, $row);
	}

	function displayAdminListItemOptions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayAdminListItemOptions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayAdminListItemOptions', $this, $row, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'languages::displayAdminListItemOptions', $this, $row);
	}

	function displayAdminListItemFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayAdminListItemFunctions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayAdminListItemFunctions', $this, $row, $handled);

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
			'languages::displayAdminListItemFunctions', $this, $row);
	}

	function displayAdminListFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayAdminListFunctions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayAdminListFunctions', $this, $handled);

			return $handled;
		}

		echo
			"<input type='submit' name='reordersubmit' value='".
				htmlchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlchars(__("Reset"), ENT_QUOTES)."' class='button' />";

		api::callHooks(API_HOOK_AFTER,
			'languages::displayAdminListFunctions', $this);
	}

	function displayAdminList(&$rows) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayAdminList', $this, $rows);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayAdminList', $this, $rows, $handled);

			return $handled;
		}

		if (JCORE_VERSION >= '0.7') {
			echo
				"<form action='".url::uri('edit, delete')."' method='post'>" .
					"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";
		}

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

		if (JCORE_VERSION >= '0.7') {
			if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
				$this->displayAdminListFunctions();

				echo
					"<div class='clear-both'></div>" .
					"<br />";
			}

			echo
				"</form>";
		}

		api::callHooks(API_HOOK_AFTER,
			'languages::displayAdminList', $this, $rows);
	}

	function displayAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayAdminForm', $this, $form, $handled);

			return $handled;
		}

		$form->display();

		api::callHooks(API_HOOK_AFTER,
			'languages::displayAdminForm', $this, $form);
	}

	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayAdminTitle', $this, $ownertitle);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayAdminTitle', $this, $ownertitle, $handled);

			return $handled;
		}

		admin::displayTitle(
			__('Languages Administration'),
			$ownertitle);

		api::callHooks(API_HOOK_AFTER,
			'languages::displayAdminTitle', $this, $ownertitle);
	}

	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayAdminDescription', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayAdminDescription', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'languages::displayAdminDescription', $this);
	}

	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayAdmin', $this, $handled);

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
				" SELECT `Title` FROM `{languages}`" .
				" WHERE `ID` = '".$id."'"));

			url::displayConfirmation(
				'<b>'.__('Delete').'?!</b> "'.$selected['Title'].'"');
		}

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

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$verifyok = $this->verifyAdmin($form);

		$rows = sql::run(
			" SELECT * FROM `{languages}`" .
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

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{languages}`" .
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
			'languages::displayAdmin', $this);
	}

	function add($values) {
		if (!is_array($values))
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::add', $this, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::add', $this, $values, $handled);

			return $handled;
		}

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

		if (!$newid)
			tooltip::display(
				sprintf(__("Language couldn't be created! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'languages::add', $this, $values, $newid);

		return $newid;
	}

	function edit($id, $values) {
		if (!$id)
			return false;

		if (!is_array($values))
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::edit', $this, $id, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::edit', $this, $id, $values, $handled);

			return $handled;
		}

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

		$result = (sql::affected() != -1);

		if (!$result)
			tooltip::display(
				sprintf(__("Language couldn't be updated! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'languages::edit', $this, $id, $values, $result);

		return $result;
	}

	function delete($id) {
		if (!$id)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::delete', $this, $id);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::delete', $this, $id, $handled);

			return $handled;
		}

		sql::run(
			" DELETE FROM `{languages}` " .
			" WHERE `ID` = '".$id."'");

		/*
		 * If a language deleted we set all pages to 0 so they are kept
		 * in the db for backup but are shown as No Language Selected.
		 */

		sql::run(
			" UPDATE `{" .
				(JCORE_VERSION >= '0.8'?
					'pages':
					'menuitems') .
				"}` " .
			" SET `LanguageID` = 0" .
			" WHERE `LanguageID` = '".$id."'");

		api::callHooks(API_HOOK_AFTER,
			'languages::delete', $this, $id);

		return true;
	}

	// ************************************************   Client Part
	static function get($id = 0) {
		if ($id && $id == languages::$selected['ID'])
			return languages::$selected;

		if ($id)
			return sql::fetch(sql::run(
				" SELECT * FROM `{languages}`" .
				" WHERE `ID` = '".(int)$id."'"));

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

		languages::$selectedLocale = $locale;
		@putenv('LC_ALL='.$locale);

		if ((defined('MANUAL_GETTEXT') && MANUAL_GETTEXT) || !PHP_GETTEXT) {
			T_setlocale(LC_ALL, $locale.'.'.PAGE_CHARSET, $locale);

		} else {
			# there is a problem with Turkish locales in PHP 5 but fixed in PHP 6
			if (substr($locale, 0, 2) == 'tr' && phpversion() < '6.0') {
				setlocale(LC_COLLATE, $locale.'.'.PAGE_CHARSET, $locale);
				setlocale(LC_MONETARY, $locale.'.'.PAGE_CHARSET, $locale);
				setlocale(LC_TIME, $locale.'.'.PAGE_CHARSET, $locale);
				setlocale(LC_MESSAGES, $locale.'.'.PAGE_CHARSET, $locale);

			} else {
				setlocale(LC_ALL, $locale.'.'.PAGE_CHARSET, $locale);
			}
		}

		return languages::loadMessages();
	}

	static function bind($file) {
		if (!$file)
			return false;

		$localedir = substr(languages::$selectedLocale.'.', 0,
			strpos(languages::$selectedLocale.'.', '.'));

		if (defined('JCORE_PATH') && JCORE_PATH &&
			!@is_dir(SITE_PATH.'locale/'.$localedir))
			$localedir = JCORE_PATH.'locale';
		else
			$localedir = SITE_PATH.'locale';

		if ((defined('MANUAL_GETTEXT') && MANUAL_GETTEXT) || !PHP_GETTEXT) {
			$result = T_bindtextdomain($file, $localedir);
			T_bind_textdomain_codeset($file, PAGE_CHARSET);

		} else {
			$result = bindtextdomain($file, $localedir);
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

		if ((defined('MANUAL_GETTEXT') && MANUAL_GETTEXT) || !PHP_GETTEXT)
			return T_textdomain($file);

		return textdomain($file);
	}

	static function setDefault() {
		if (defined('DEFAULT_LOCALE') && DEFAULT_LOCALE)
			return languages::set(DEFAULT_LOCALE);

		return languages::set('en_US');
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
			return explode('|', $languageids['IDs']);

		return null;
	}

	static function getDefault() {
		return sql::fetch(sql::run(
			" SELECT * FROM `{languages}`" .
			" WHERE `Default` = 1" .
			" LIMIT 1"));
	}

	static function getSelected() {
		return languages::$selected;
	}

	static function getSelectedID () {
		if (languages::$selected)
			return languages::$selected['ID'];

		return 0;
	}

	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::ajaxRequest', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::ajaxRequest', $this, $handled);

			return $handled;
		}

		if (!$GLOBALS['USER']->loginok ||
			!$GLOBALS['USER']->data['Admin'])
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'languages::ajaxRequest', $this);

			return true;
		}

		$locales = null;

		if (isset($_GET['locales']))
			$locales = (int)$_GET['locales'];

		if ($locales) {
			$permission = userPermissions::check(
				(int)$GLOBALS['USER']->data['ID'],
				$this->adminPath);

			if (~$permission['PermissionType'] & USER_PERMISSION_TYPE_WRITE) {
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);

				api::callHooks(API_HOOK_AFTER,
					'languages::ajaxRequest', $this);

				return true;
			}

			$result = true;
			$this->displayAdminAvailableLocales();

			api::callHooks(API_HOOK_AFTER,
				'languages::ajaxRequest', $this, $result);

			return true;
		}

		api::callHooks(API_HOOK_AFTER,
			'languages::ajaxRequest', $this);

		return false;
	}

	function displayTitle(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayTitle', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayTitle', $this, $row, $handled);

			return $handled;
		}

		echo
			"<a href='".$row['_Link']."'>" .
				"<span>" .
				$row['Title'] .
				"</span>" .
			"</a>";

		api::callHooks(API_HOOK_AFTER,
			'languages::displayTitle', $this, $row);
	}

	function displayOne(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayOne', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayOne', $this, $row, $handled);

			return $handled;
		}

		$translatedpage = null;

		if (pages::$selected)
			$translatedpage = sql::fetch(sql::run(
				" SELECT `ID`, `Path` FROM `{" .
					(JCORE_VERSION >= '0.8'?
						'pages':
						'menuitems') .
					"}`" .
				" WHERE `LanguageID` = '".$row['ID']."'" .
				" AND `Path` = '".pages::$selected['Path']."'"));

		if (SEO_FRIENDLY_LINKS) {
			$row['_Link'] = url::site().
				$row['Path'].
				($translatedpage?
					'/'.$translatedpage['Path']:
					null);

		} else {
			$row['_Link'] = url::site().'index.php' .
				'?languageid='.$row['ID'] .
				($translatedpage?
					'&amp;pageid='.$translatedpage['ID']:
					null);
		}

		echo
			"<div " .
				(JCORE_VERSION < '0.6'?
					"id='language".$row['ID']."' ":
					null) .
				"class='language ".$row['Path'] .
				($row['ID'] == $this->selectedID?
					" selected":
					NULL) .
				"'>";

		$this->displayTitle($row);

		echo
			"</div>";

		api::callHooks(API_HOOK_AFTER,
			'languages::displayOne', $this, $row);
	}

	function displayArguments() {
		if (!$this->arguments)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::displayArguments', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayArguments', $this, $handled);

			return $handled;
		}

		if (preg_match('/(^|\/)selected($|\/)/', $this->arguments)) {
			if (languages::$selected) {
				if (SEO_FRIENDLY_LINKS)
					echo languages::$selected['Path'];
				else
					echo languages::$selected['ID'];
			}

			api::callHooks(API_HOOK_AFTER,
				'languages::displayArguments', $this, languages::$selected);

			return true;
		}

		$row = sql::fetch(sql::run(
			" SELECT * FROM `{languages}` " .
			" WHERE `Path` LIKE '".sql::escape($this->arguments)."'" .
			" LIMIT 1"));

		if (!$row) {
			api::callHooks(API_HOOK_AFTER,
				'languages::displayArguments', $this);

			return true;
		}

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

		api::callHooks(API_HOOK_AFTER,
			'languages::displayArguments', $this, $row);

		return true;
	}

	function display() {
		if ($this->displayArguments())
			return;

		$rows = sql::run(
			$this->SQL());

		if (sql::rows($rows) < 2)
			return;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'languages::display', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'languages::display', $this, $handled);

			return $handled;
		}

		echo
			"<div class='languages'>";

		while($row = sql::fetch($rows))
			$this->displayOne($row);

		echo
			"</div>";

		api::callHooks(API_HOOK_AFTER,
			'languages::display', $this);
	}
}

?>