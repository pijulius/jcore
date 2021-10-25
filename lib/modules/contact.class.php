<?php

/***************************************************************************
 *
 *  Name: Contact Module
 *  URI: http://jcore.net
 *  Description: A simple contact form using the Dynamic Forms feature. Released under the GPL, LGPL, and MPL Licenses.
 *  Author: Istvan Petres
 *  Version: 1.0
 *  Tags: contact module, gpl, lgpl, mpl
 *
 ****************************************************************************/

class contactForm extends dynamicForms {
	function __construct() {
		languages::load('contact');

		parent::__construct(
			_('Contact'), 'contact');
	}

	function __destruct() {
		languages::unload('contact');
	}

	function verify($customdatahandling = false) {
		if (!$this->successMessage)
			$this->successMessage =
				_("<b>Thank you for contacting us.</b><br /> " .
					"Your message has been successfully sent.");

		return parent::verify($customdatahandling);
	}
}

class contact extends modules {
	var $adminPath = 'admin/modules/contact';

	function __construct() {
		languages::load('contact');
	}

	function __destruct() {
		languages::unload('contact');
	}

	function installSQL() {
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicforms}` " .
			" WHERE `FormID` = 'contact';"));

		if (sql::error())
			return false;

		if ($exists)
			return true;

		$newformid = sql::run(
			" INSERT INTO `{dynamicforms}` " .
			" (`Title`, `FormID`, `Method`, `SendNotificationEmail`, `SQLTable`, `Protected`) VALUES" .
			" ('Contact', 'contact', 'post', 1, '', 1);");

		if (sql::error())
			return false;

		sql::run(
			" INSERT INTO `{dynamicformfields}` " .
			" (`FormID`, `Title`, `Name`, `TypeID`, `ValueType`, `Required`, `PlaceholderText`, `TooltipText`, `AdditionalText`, `Attributes`, `Style`, `OrderID`, `Protected`) VALUES" .
			" (".$newformid.", 'Your name', 'FullName', 1, 1, 1, '', '', '', '', 'width: 200px;', 1, 0)," .
			" (".$newformid.", 'Email address', 'Email', 2, 1, 1, '', '', '', '', 'width: 250px;', 2, 0)," .
			" (".$newformid.", 'Phone number', 'PhoneNumber', 1, 1, 1, '', '', '', '', 'width: 180px;', 3, 0)," .
			" (".$newformid.", 'Questions / Comments', 'Message', 6, 9, 1, '', '', '', '', 'width: 290px; height: 100px;', 4, 0)," .
			" (".$newformid.", 'Verification code', '', 11, 1, 1, '', '', '', '', '', 5, 0);");

		if (sql::error())
			return false;

		return true;
	}

	function installFiles() {
		$css =
			".as-modules-contact a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/contact-form.png\");\n" .
			"}\n";

		return
			files::save(SITE_PATH.'template/modules/css/contact.css', $css);
	}

	function uninstallSQL() {
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicforms}` " .
			" WHERE `FormID` = 'contact';"));

		if ($exists) {
			$form = new dynamicForms();
			$form->deleteForm($exists['ID']);
			unset($form);
		}

		return true;
	}

	function uninstallFiles() {
		return
			files::delete(SITE_PATH.'template/modules/css/contact.css');
	}

	// ************************************************   Admin Part
	function setupAdmin() {
		favoriteLinks::add(
			_('Form Settings'),
			'?path=admin/content/dynamicforms');
		favoriteLinks::add(
			__('Settings'),
			'?path=admin/site/settings');
	}

	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				__("Title / Form ID")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Email")."</span></th>";
	}

	function displayAdminListHeaderOptions() {
		echo
			"<th><span class='nowrap'>".
				__("Data")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Fields")."</span></th>";
	}

	function displayAdminListItem(&$row) {
		echo
			"<td class='auto-width'>" .
				"<a class='bold' href='".url::uri('ALL') .
					"?path=admin/content/dynamicforms&amp;id=".$row['ID']."'>" .
					_($row['Title']) .
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
		$dbitems = null;

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

		echo
			"<td align='center'>";

		if ($row['SQLTable'] && JCORE_VERSION >= '0.7') {
			echo
				"<a class='admin-link db' " .
					"title='".htmlchars(__("Browse Data"), ENT_QUOTES) .
					" (".$dbitems['Rows'].")' " .
					"href='".url::uri('ALL') .
					"?path=admin/content/dynamicforms/".$row['ID']."/dynamicformdata'>";
		if (ADMIN_ITEMS_COUNTER_ENABLED && $dbitems['Rows'])
			counter::display($dbitems['Rows']);

			echo
				"</a>";
		}

		echo
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link fields' " .
					"title='".htmlchars(__("Fields"), ENT_QUOTES) .
					" (".$fields['Rows'].")' " .
					"href='".url::uri('ALL') .
					"?path=admin/content/dynamicforms/".$row['ID']."/dynamicformfields'>";
		if (ADMIN_ITEMS_COUNTER_ENABLED && $fields['Rows'])
			counter::display($fields['Rows']);

		echo
				"</a>" .
			"</td>";
	}

	function displayAdminList(&$rows) {
		echo
			"<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>";

		$this->displayAdminListHeader();
		$this->displayAdminListHeaderOptions();

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

			echo
				"</tr>";

			$i++;
		}

		echo
				"</tbody>" .
			"</table>";
	}

	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			_('Contact Form Administration'),
			$ownertitle);
	}

	function displayAdminDescription() {
	}

	function displayAdmin() {
		$this->displayAdminTitle();
		$this->displayAdminDescription();

		echo
			"<div class='admin-content'>";

		$rows = sql::run(
			" SELECT * FROM `{dynamicforms}`" .
			" WHERE `FormID` = 'contact'" .
			" ORDER BY `Title`");

		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				_("No contact form found."),
				TOOLTIP_NOTIFICATION);

		echo
			"</div>"; //admin-content
	}

	// ************************************************   Client Part
	function verify(&$form) {
		if (!$form->verify())
			return false;

		$form->reset();

		$sessionid = null;

		if (isset($_POST['sessionid']))
			$sessionid = strip_tags((string)$_POST['sessionid']);

		if ($sessionid)
			echo
				"<div style='display: none;'>" .
					"<iframe src='".url::uri()."&amp;request=security&amp;regeneratesessionid=1&amp;ajax=1'></iframe>" .
				"</div>";

		return true;
	}

	function display() {
		echo
			"<div class='contact-module'>";

		$form = new contactForm();

		$form->load();
		$this->verify($form);

		if ($GLOBALS['USER']->loginok)
			$form->setValues($GLOBALS['USER']->data);

		$form->display();

		unset($form);

		echo
			"</div>";
	}
}

modules::register(
	'contact',
	_('Contact Form'),
	_('Let people contact you by completing a form'));

?>