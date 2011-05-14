<?php

/***************************************************************************
 * 
 *  Name: Contact Module
 *  URI: http://jcore.net
 *  Description: A simple contact form using the Dynamic Forms feature. Released under the GPL, LGPL, and MPL Licenses.
 *  Author: Istvan Petres
 *  Version: 0.8
 *  Tags: contact module, gpl, lgpl, mpl
 * 
 ****************************************************************************/

modules::register(
	'contact', 
	_('Contact Form'), 
	_('Let people contact you by completing a form'));

class contactForm extends dynamicForms {
	function __construct() {
		languages::load('contact');
		
		parent::__construct(
			_('Contact'), 'contact');
	}
	
	function __destruct() {
		languages::unload('contact');
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
				__("Title / Form ID")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
		echo
			"<th><span class='nowrap'>".
				__("Fields")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
		echo
			"<td class='auto-width'>" .
				"<div class='bold'>" .
					_($row['Title']) .
				"</div>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					$row['FormID'] .
				"</div>" .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
		$fields = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{dynamicformfields}`" .
			" WHERE `FormID` = '".$row['ID']."'" .
			" LIMIT 1"));
		
		echo
			"<td align='center'>" .
				"<a class='admin-link fields' " .
					"title='".htmlspecialchars(__("Fields"), ENT_QUOTES) .
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
			" WHERE `FormID` IN ('contact')" .
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
			$sessionid = $_POST['sessionid'];
		
		if ($sessionid)
			echo
				"<div style='display: none;'>" .
					"<iframe src='".url::uri()."&amp;request=security&amp;regeneratesessionid=1&amp;ajax=1'></iframe>" .
				"</div>";
		
		if ($form->successMessage) {
			tooltip::display(
				_($form->successMessage),
				TOOLTIP_SUCCESS);
			return true;
		}
		
		tooltip::display(
			_("<b>Thank you for contacting us.</b><br /> " .
				"Your message has been successfully sent."),
			TOOLTIP_SUCCESS);
		
		return true;
	}
	
	function display() {
		echo 
			"<div class='contact-module'>";
		
		$form = new contactForm();
		
		$form->load();
		
		if ($GLOBALS['USER']->loginok)
			$form->setValues($GLOBALS['USER']->data);
		
		$this->verify($form);
		$form->display();
		
		unset($form);
		
		echo
			"</div>";
	}
}
 
?>