<?php

/***************************************************************************
 * 
 *  Name: Shopping Cart Module
 *  URI: http://jcore.net
 *  Description: Allow people to put together their shopping carts and checkout/place order. Released under the GPL, LGPL, and MPL Licenses.
 *  Author: Istvan Petres
 *  Version: 0.8
 *  Tags: shopping cart module, gpl, lgpl, mpl
 * 
 ****************************************************************************/

modules::register(
	'shoppingCart', 
	_('Shopping Cart'),
	_('Set Fees, Discounts, Taxes, checkout Limits and let people place orders'));

class shoppingCartSettings extends settings {
	var $sqlTable = 'shoppingcartsettings';
	var $adminPath = 'admin/modules/shoppingcart/shoppingcartsettings';
	
	function __construct($table = null) {
		languages::load('shopping');
		
		parent::__construct($table);
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
}

if (modules::installed('shoppingCart')) {
	$shoppingcartsettings = new shoppingCartSettings();
	$shoppingcartsettings->defineSettings();
	unset($shoppingcartsettings);
}

include_once('lib/modules/shopping.class.php');
include_once('lib/modules/shoppingorders.class.php');

class shoppingCartDiscounts {
	var $ajaxRequest = null;
	var $adminPath = 'admin/modules/shoppingcart/shoppingcartdiscounts';
	
	function __construct() {
		languages::load('shopping');
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
	
	// ************************************************   Admin Part
	static function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{shoppingcartdiscounts}`" .
			" LIMIT 1"));
		
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				_('New Discount'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Settings'), 
			'?path=admin/modules/shoppingcart/shoppingcartsettings');
		favoriteLinks::add(
			__('Users'), 
			'?path=admin/members/users');
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			_('Discount Percentage'),
			'DiscountPercent',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		if (JCORE_VERSION >= '0.6') {
			$form->addAdditionalText('%');
			$form->setTooltipText(_("e.g. 25"));
		} else {
			$form->addAdditionalText("% ("._("e.g. 25").")");
		}
		
		$form->add(
			_('Price Above or Equal'),
			'Above',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 100px;');
		$form->setValueType(FORM_VALUE_TYPE_FLOAT);
		
		if (defined('SHOPPING_CART_CURRENCY_POSITION') && 
			stristr(SHOPPING_CART_CURRENCY_POSITION, 'right'))
			$form->addAdditionalText(
				"<span class='shopping-currency'>" .
					SHOPPING_CART_CURRENCY .
				"</span>");
		else
			$form->addAdditionalPreText(
				"<span class='shopping-currency'>" .
					SHOPPING_CART_CURRENCY .
				"</span>");
		
		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(_("e.g. 10"));
		else
			$form->addAdditionalText(" ("._("e.g. 10").")");
			
		$form->add(
			_('Price Below'),
			'Below',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 100px;');
		$form->setValueType(FORM_VALUE_TYPE_FLOAT);
		
		if (defined('SHOPPING_CART_CURRENCY_POSITION') && 
			stristr(SHOPPING_CART_CURRENCY_POSITION, 'right'))
			$form->addAdditionalText(
				"<span class='shopping-currency'>" .
					SHOPPING_CART_CURRENCY .
				"</span>");
		else
			$form->addAdditionalPreText(
				"<span class='shopping-currency'>" .
					SHOPPING_CART_CURRENCY .
				"</span>");
		
		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(_("e.g. 70"));
		else
			$form->addAdditionalText(" ("._("e.g. 70").")");
			
		if (JCORE_VERSION >= '0.5') {
			$form->add(
				_('For Defined User'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
				
			$form->add(
				__('Username'),
				'UserName',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 150px;');
			
			$form->addAdditionalText(
				"<a href='".url::uri('request, users').
					"&amp;request=".url::path() .
					"&amp;users=1' " .
					"class='shopping-cart-discounts-select-user ajax-content-link' " .
					"title='".htmlspecialchars(_("Define the owner of the discount."), ENT_QUOTES)."'>" .
					_("Select User") .
				"</a>");
					
			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER,
				true);
		}
	}
	
	function verifyAdmin(&$form) {
		$setpriority = null;
		$priorities = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_POST['setprioritysubmit']))
			$setpriority = $_POST['setprioritysubmit'];
		
		if (isset($_POST['priorities']))
			$priorities = (array)$_POST['priorities'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($setpriority) {
			if (!$priorities)
				return false;
			
			foreach($priorities as $pid => $pvalue) {
				sql::run(
					" UPDATE `{shoppingcartdiscounts}`" .
					" SET `Priority` = '".(int)$pvalue."'" .
					" WHERE `ID` = '".(int)$pid."'");
			}
			
			tooltip::display(
				_("Priorities have been successfully set."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				_("Discount has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		$values = $form->getPostArray();
		$values['UserID'] = 0;
		
		if (JCORE_VERSION >= '0.5' && $form->get('UserName')) {
			$user = sql::fetch(sql::run(
				" SELECT * FROM `{users}` " .
				" WHERE `UserName` = '".sql::escape($form->get('UserName'))."'"));
			
			if (!$user) {
				tooltip::display(
					sprintf(__("User \"%s\" couldn't be found!"), 
						$form->get('UserName'))." " .
					__("Please make sure you have entered / selected the right " .
						"username or if it's a new user please first create " .
						"the user at Member Management -> Users."),
					TOOLTIP_ERROR);
				
				$form->setError('UserName', FORM_ERROR_REQUIRED);
				return false;
			}
		
			$values['UserID'] = $user['ID'];
		}
		
		if ($edit) {
			if (!$this->edit($id, $values))
				return false;
				
			tooltip::display(
				_("Discount has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$newid = $this->add($values))
			return false;
			
		tooltip::display(
			_("Discount has been successfully created.")." " .
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
					_("Priority")."</span></th>";
		
		echo
			"<th><span class='nowrap'>".
				_("Discount")."</span></th>" .
			"<th><span class='nowrap'>".
				htmlspecialchars(_("Price >="))."</span></th>" .
			"<th><span class='nowrap'>".
				htmlspecialchars(_("Price <"))."</span></th>";
		
		if (JCORE_VERSION >= '0.5')
			echo
				"<th><span class='nowrap'>".
					_("User")."</span></th>";
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
					"<input type='text' name='priorities[".$row['ID']."]' " .
						"value='".$row['Priority']."' " .
						"class='order-id-entry' tabindex='1' />" .
				"</td>";
		
		echo
			"<td class='bold'>" .
				"<span class='nowrap'>" .
				$row['DiscountPercent']."%" .
				"</span>" .
			"</td>" .
			"<td>" .
				"<span class='nowrap'>";
		
		shoppingCart::displayPrice($row['Above']);
		
		echo
				"</span>" .
			"</td>" .
			"<td" .
				(JCORE_VERSION < '0.5'?
					" class='auto-width'":
					null) .
				">" .
				"<span class='nowrap'>";
		
		if ($row['Below'])
			shoppingCart::displayPrice($row['Below']);
		else
			echo "&#8734;";
		
		echo
				"</span>" .
			"</td>";
	
		if (JCORE_VERSION >= '0.5') {
			$username = null;
			
			if ($row['UserID']) {
				$user = $GLOBALS['USER']->get($row['UserID']);
				
				if ($user)
					$username = $user['UserName'];
				else
					$username = "<span class='red'>" .
						_("User deleted!") .
						"</span>";
			}
			
			echo
				"<td class='auto-width'>" .
					$username .
				"</td>";
		}
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
			"<input type='submit' name='setprioritysubmit' value='" .
				htmlspecialchars(_("Set Priority"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminList(&$rows) {
		echo
			"<form action='".
				url::uri('edit, delete')."' method='post'>";
			
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
		
		if (JCORE_VERSION >= '0.7' && 
			$this->userPermissionType == USER_PERMISSION_TYPE_WRITE) 
		{
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
		admin::displayTitle(
			_('Shopping Cart Discounts Administration'),
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
					_("Edit Discount"):
					_("New Discount")),
				'neweditdiscount');
					
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
			" SELECT * FROM `{shoppingcartdiscounts}`" .
			" WHERE 1" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			" ORDER BY " .
			(JCORE_VERSION >= '0.7'?
				" `Priority`, ":
				null) .
			(JCORE_VERSION >= '0.5'?
				" `UserID`, ":
				null) .
			" `DiscountPercent`");
			
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				_("No discounts found."),
				TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{shoppingcartdiscounts}`" .
					" WHERE `ID` = '".$id."'"));
			
				$form->setValues($row);
				
				if (JCORE_VERSION >= '0.5') {
					$user = $GLOBALS['USER']->get($row['UserID']);
					$form->setValue('UserName', $user['UserName']);
				}
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
			
		$newid = sql::run(
			" INSERT INTO `{shoppingcartdiscounts}` SET ".
			(JCORE_VERSION >= '0.5'?
				" `UserID` = '".
					(int)$values['UserID']."',":
				null) .
			" `Above` = " .
				($values['Above']?
					"'".sql::escape($values['Above'])."'":
					"0") .
				"," .
			" `Below` = " .
				($values['Below']?
					"'".sql::escape($values['Below'])."'":
					"NULL") .
				"," .
			" `DiscountPercent` = '".
				(int)$values['DiscountPercent']."'");
			
		if (!$newid) {
			tooltip::display(
				sprintf(_("Discount couldn't be added! Error: %s"), 
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
		
		sql::run(
			" UPDATE `{shoppingcartdiscounts}` SET ".
			(JCORE_VERSION >= '0.5'?
				" `UserID` = '".
					(int)$values['UserID']."',":
				null) .
			" `Above` = " .
				($values['Above']?
					"'".sql::escape($values['Above'])."'":
					"0") .
				"," .
			" `Below` = " .
				($values['Below']?
					"'".sql::escape($values['Below'])."'":
					"NULL") .
				"," .
			" `DiscountPercent` = '".
				(int)$values['DiscountPercent']."'" .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(_("Discount couldn't be updated! Error: %s"), 
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
			" DELETE FROM `{shoppingcartdiscounts}` " .
			" WHERE `ID` = '".$id."'");
		
		return true;
	}
	
	static function get($amount, $userid = null) {
		if (!isset($userid) && $GLOBALS['USER']->loginok)
			$userid = $GLOBALS['USER']->data['ID'];
		
		$row = sql::fetch(sql::run(
			" SELECT `DiscountPercent` FROM `{shoppingcartdiscounts}`" .
			" WHERE '".sql::escape($amount)."' >= `Above` " .
			" AND ('".sql::escape($amount)."' < `Below`" .
				" OR `Below` IS NULL)" .
			(JCORE_VERSION >= '0.5'?
				" AND (!`UserID` " .
				($userid?
					" OR `UserID` = '".(int)$userid."'":
					null) .
				")":
				null) .
			" ORDER BY " .
			(JCORE_VERSION >= '0.7'?
				" `Priority` DESC, ":
				null) .
			(JCORE_VERSION >= '0.5'?
				" `UserID` DESC, ":
				null) .
			" `DiscountPercent` DESC" .
			" LIMIT 1"));
			
		if (!$row)
			return 0;
			
		return number_format($amount*$row['DiscountPercent']/100, 2, '.', '');
	}
	
	static function getNext($amount, $userid = null) {
		if (!isset($userid) && $GLOBALS['USER']->loginok)
			$userid = $GLOBALS['USER']->data['ID'];
		
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingcartdiscounts}`" .
			" WHERE ('".sql::escape($amount)."' <= `Above`) " .
			(JCORE_VERSION >= '0.5'?
				" AND (!`UserID` " .
				($userid?
					" OR `UserID` = '".(int)$userid."'":
					null) .
				")":
				null) .
			" ORDER BY" .
			(JCORE_VERSION >= '0.7'?
				" `Priority` DESC, ":
				null) .
			(JCORE_VERSION >= '0.5'?
				" `UserID` DESC,":
				null) .
			" `DiscountPercent`" .
			" LIMIT 1"));
			
		return $row;
	}
	
	function ajaxRequest() {
		if (!$GLOBALS['USER']->loginok || 
			!$GLOBALS['USER']->data['Admin']) 
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);
			return true;
		}
		
		$users = null;
		
		if (isset($_GET['users']))
			$users = $_GET['users'];
		
		if ($users) {
			include_once('lib/userpermissions.class.php');
			
			$permission = userPermissions::check(
				$GLOBALS['USER']->data['ID'],
				$this->adminPath);
			
			if ($permission['PermissionType'] != USER_PERMISSION_TYPE_WRITE ||
				$permission['PermissionIDs'])
			{
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);
				return true;
			}
			
			$GLOBALS['USER']->displayQuickList('#neweditdiscountform #entryUserName');
			return true;
		}
		
		return false;
	}
}

class shoppingCartFees {
	var $ajaxRequest = null;
	var $adminPath = 'admin/modules/shoppingcart/shoppingcartfees';
	
	function __construct() {
		languages::load('shopping');
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
	
	// ************************************************   Admin Part
	static function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{shoppingcartfees}`" .
			" LIMIT 1"));
		
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				_('New Fee'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Settings'), 
			'?path=admin/modules/shoppingcart/shoppingcartsettings');
		favoriteLinks::add(
			_('Order Form'), 
			'?path=admin/content/dynamicforms');
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			_('Fee Amount'),
			'Fee',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 100px;');
		$form->setValueType(FORM_VALUE_TYPE_FLOAT);
		
		if (defined('SHOPPING_CART_CURRENCY_POSITION') &&
			stristr(SHOPPING_CART_CURRENCY_POSITION, 'right'))
			$form->addAdditionalText(
				"<span class='shopping-currency'>" .
					SHOPPING_CART_CURRENCY .
				"</span>");
		else
			$form->addAdditionalPreText(
				"<span class='shopping-currency'>" .
					SHOPPING_CART_CURRENCY .
				"</span>");
		
		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(_("e.g. 5"));
		else
			$form->addAdditionalText(" ("._("e.g. 5").")");
		
		$form->add(
			_('Price Above or Equal'),
			'Above',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 100px;');
		$form->setValueType(FORM_VALUE_TYPE_FLOAT);
		
		if (defined('SHOPPING_CART_CURRENCY_POSITION') &&
			stristr(SHOPPING_CART_CURRENCY_POSITION, 'right'))
			$form->addAdditionalText(
				"<span class='shopping-currency'>" .
					SHOPPING_CART_CURRENCY .
				"</span>");
		else
			$form->addAdditionalPreText(
				"<span class='shopping-currency'>" .
					SHOPPING_CART_CURRENCY .
				"</span>");
		
		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(_("e.g. 10"));
		else
			$form->addAdditionalText(" ("._("e.g. 10").")");
			
		$form->add(
			_('Price Below'),
			'Below',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 100px;');
		$form->setValueType(FORM_VALUE_TYPE_FLOAT);
		
		if (defined('SHOPPING_CART_CURRENCY_POSITION') &&
			stristr(SHOPPING_CART_CURRENCY_POSITION, 'right'))
			$form->addAdditionalText(
				"<span class='shopping-currency'>" .
					SHOPPING_CART_CURRENCY .
				"</span>");
		else
			$form->addAdditionalPreText(
				"<span class='shopping-currency'>" .
					SHOPPING_CART_CURRENCY .
				"</span>");
		
		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(_("e.g. 70"));
		else
			$form->addAdditionalText(" ("._("e.g. 70").")");
		
		if (JCORE_VERSION >= '0.7') {
			$form->add(
				_('Using Weight'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
				
			$form->add(
				_('Weight Above or Equal'),
				'WeightAbove',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 50px;');
			$form->setValueType(FORM_VALUE_TYPE_FLOAT);
			
			if (defined('SHOPPING_CART_WEIGHT_UNIT') &&
				SHOPPING_CART_WEIGHT_UNIT)
				$form->addAdditionalText(
					"<span class='shopping-weight-unit'>" .
						SHOPPING_CART_WEIGHT_UNIT .
					"</span>");
			
			$form->setTooltipText(_("e.g. 0.5"));
			
			$form->add(
				_('Weight Below'),
				'WeightBelow',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 50px;');
			$form->setValueType(FORM_VALUE_TYPE_FLOAT);
			
			if (defined('SHOPPING_CART_WEIGHT_UNIT') &&
				SHOPPING_CART_WEIGHT_UNIT)
				$form->addAdditionalText(
					"<span class='shopping-weight-unit'>" .
						SHOPPING_CART_WEIGHT_UNIT .
					"</span>");
			
			$form->setTooltipText(_("e.g. 3"));
			
			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER,
				true);
		}
		
		if (JCORE_VERSION >= '0.5') {
			$form->add(
				_('Using Order Form Field Values'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
				
			$form->add(
				_('Field name'),
				'FieldName',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 150px;');
			
			$form->addAdditionalText(
				"<a href='".url::uri('request, orderformfields').
					"&amp;request=".url::path() .
					"&amp;orderformfields=1' " .
					"class='shopping-cart-fees-select-order-form-field ajax-content-link' " .
					"title='".htmlspecialchars(_("Define the field you would like to compare."), ENT_QUOTES)."'>" .
					_("Select Field") .
				"</a>");
					
			$form->add(
				_('Is or Contains'),
				'FieldValue',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 250px;');
				
			if (JCORE_VERSION >= '0.6')
				$form->setTooltipText(_("e.g. field name \"ShippingState\" is \"CA\" or contains \"CA, WA, NY\""));
			else
				$form->addAdditionalText(" ("._("e.g. field name \"ShippingState\" is \"CA\" or contains \"CA, WA, NY\"").")");
			
			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER,
				true);
		}
	}
	
	function verifyAdmin(&$form) {
		$setpriority = null;
		$priorities = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_POST['setprioritysubmit']))
			$setpriority = $_POST['setprioritysubmit'];
		
		if (isset($_POST['priorities']))
			$priorities = (array)$_POST['priorities'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($setpriority) {
			if (!$priorities)
				return false;
			
			foreach($priorities as $pid => $pvalue) {
				sql::run(
					" UPDATE `{shoppingcartfees}`" .
					" SET `Priority` = '".(int)$pvalue."'" .
					" WHERE `ID` = '".(int)$pid."'");
			}
			
			tooltip::display(
				_("Priorities have been successfully set."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				_("Fee has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		$values = $form->getPostArray();
		$values['FieldID'] = 0;
		
		if (JCORE_VERSION >= '0.5' && $form->get('FieldName')) {	
			$orderform = sql::fetch(sql::run(
				" SELECT `ID` FROM `{dynamicforms}`" .
				" WHERE `FormID` = 'shoppingorders'" .
				" LIMIT 1"));
			
			$orderformfield = sql::fetch(sql::run(
				" SELECT `ID` FROM `{dynamicformfields}`" .
				" WHERE `FormID` = '".$orderform['ID']."'" .
				" AND `Name` = '".sql::escape($form->get('FieldName'))."'"));
				
			if (!$orderformfield) {
				tooltip::display(
					_("Order form field couldn't be found!")." " .
					_("Please make sure you have " .
						"entered / selected the right field or if you wish to " .
						"add this fee to a new field please first create " .
						"the field at Content Management -> Dynamic Forms."),
					TOOLTIP_ERROR);
				
				$form->setError('FieldName', FORM_ERROR_REQUIRED);
				return false;
			}
			
			$values['FieldID'] = $orderformfield['ID'];
		}
		
		if ($edit) {
			if (!$this->edit($id, $values))
				return false;
				
			tooltip::display(
				_("Fee has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$newid = $this->add($values))
			return false;
			
		tooltip::display(
			_("Fee has been successfully created.")." " .
			"<a href='".url::uri('id, edit, delete') .
				"&amp;id=".$newid."&amp;edit=1#adminform'>" .
				__("Edit") .
			"</a>",
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminOrderFormFields() {
		echo 
			"<div class='shopping-cart-fees-order-form-fields'>" .
				"<div class='form-title'>"._('Order Form Fields')."</div>" .
				"<table cellpadding='0' cellspacing='0' class='form-content list'>" .
					"<thead>" .
					"<tr>" .
						"<th>" .
							"<span class='nowrap'>".
							_("Select").
							"</span>" .
						"</th>" .
						"<th>" .
							"<span class='nowrap'>".
							_("Field").
							"</span>" .
						"</th>" .
						"<th style='text-align: right;'>" .
							"<span class='nowrap'>".
							_("Name").
							" &nbsp; &nbsp;</span>" .
						"</th>" .
					"</tr>" .
					"</thead>" .
					"<tbody>";
					
		$form = sql::fetch(sql::run(
			" SELECT `ID` FROM `{dynamicforms}`" .
			" WHERE `FormID` = 'shoppingorders'" .
			" LIMIT 1"));
		
		$rows = sql::run(
			" SELECT * FROM `{dynamicformfields}`" .
			" WHERE `FormID` = '".$form['ID']."'" .
			" AND `ValueType`" .
			" AND `Name` != ''" .
			" ORDER BY `OrderID`, `Title`");
		
		$i = 1;
		while ($row = sql::fetch($rows)) {
			echo
				"<tr".
					($i%2?" class='pair'":NULL).">" .
					"<td align='center'>" .
						"<a href='javascript://' " .
							"onclick='jQuery(\"#neweditfeeform #entryFieldName\")" .
								".val(\"".$row['Name']."\");" .
								(JCORE_VERSION >= '0.7'?
									"jQuery(this).closest(\".tipsy\").hide();":
									"jQuery(this).closest(\".qtip\").qtip(\"hide\");") .
								"' " .
							"class='shopping-cart-fees-select-order-form-field'>" .
						"</a>" .
					"</td>" .
					"<td class='auto-width'>" .
						"<b>" .
						$row['Title'] .
						"</b>" .
					"</td>" .
					"<td style='text-align: right;'>" .
						"<span class='nowrap'>" .
							$row['Name'] .
						"</span>" .
					"</td>" .
				"</tr>";
			
			$i++;
		}
		
		echo
					"</tbody>" .
				"</table>";
				
		echo
			"</div>";
	}
	
	function displayAdminListHeader() {
		if (JCORE_VERSION >= '0.7')
			echo
				"<th><span class='nowrap'>".
					_("Priority")."</span></th>";
		
		echo
			"<th><span class='nowrap'>".
				_("Fee")."</span></th>" .
			"<th><span class='nowrap'>".
				htmlspecialchars(_("Price >="))."</span></th>" .
			"<th><span class='nowrap'>".
				htmlspecialchars(_("Price <"))."</span></th>";
					
		if (JCORE_VERSION >= '0.7')
			echo
				"<th><span class='nowrap'>".
					htmlspecialchars(_("Weight >="))."</span></th>" .
				"<th><span class='nowrap'>".
					htmlspecialchars(_("Weight <"))."</span></th>";
		
		if (JCORE_VERSION >= '0.5')
			echo
				"<th><span class='nowrap'>".
					_("Field")."</span></th>" .
				"<th><span class='nowrap'>".
					_("Is or Contains")."</span></th>";
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
					"<input type='text' name='priorities[".$row['ID']."]' " .
						"value='".$row['Priority']."' " .
						"class='order-id-entry' tabindex='1' />" .
				"</td>";
		
		echo
			"<td" .
				(JCORE_VERSION < '0.5'?
					" class='auto-width'":
					null) .
				">" .
				"<span class='nowrap bold'>";
		
		shoppingCart::displayPrice($row['Fee']);
		
		echo
				"</span>" .
			"</td>" .
			"<td>" .
				"<span class='nowrap'>";
		
		shoppingCart::displayPrice($row['Above']);
		
		echo
				"</span>" .
			"</td>" .
			"<td>" .
				"<span class='nowrap'>";
		
		if ($row['Below'])
			shoppingCart::displayPrice($row['Below']);
		else
			echo "&#8734;";
		
		echo
				"</span>" .
			"</td>";
	
		if (JCORE_VERSION >= '0.7') {
			echo
				"<td>" .
					"<span class='nowrap'>";
			
			echo
				$row['WeightAbove'];
			
			if (defined('SHOPPING_CART_WEIGHT_UNIT') &&
				SHOPPING_CART_WEIGHT_UNIT)
				echo " ".SHOPPING_CART_WEIGHT_UNIT;
			
			echo
					"</span>" .
				"</td>" .
				"<td>" .
					"<span class='nowrap'>";
			
			if ($row['WeightBelow']) {
				echo
					$row['WeightBelow'];
				
				if (defined('SHOPPING_CART_WEIGHT_UNIT') &&
					SHOPPING_CART_WEIGHT_UNIT)
					echo " ".SHOPPING_CART_WEIGHT_UNIT;
			
			} else {
				echo "&#8734;";
			}
			
			echo
					"</span>" .
				"</td>";
		}
		
		if (JCORE_VERSION >= '0.5') {
			$fieldname = null;
		
			if ($row['FieldID']) {
				$field = sql::fetch(sql::run(
					" SELECT `Name` FROM `{dynamicformfields}`" .
					" WHERE `ID` = '".$row['FieldID']."'"));
				
				if ($field)
					$fieldname = $field['Name'];
				else
					$fieldname = "<span class='red'>" .
						_("Field deleted!") .
						"</span>";
			}
			
			echo
				"<td>" .
					"<span class='nowrap'>" .
					$fieldname .
					"</span>" .
				"</td>" .
				"<td class='auto-width'>" .
					sql::regexp2txt($row['FieldValue']) .
				"</td>";
		}
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
			"<input type='submit' name='setprioritysubmit' value='" .
				htmlspecialchars(_("Set Priority"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminList(&$rows) {
		echo
			"<form action='".
				url::uri('edit, delete')."' method='post'>";
			
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
		
		if (JCORE_VERSION >= '0.7' && 
			$this->userPermissionType == USER_PERMISSION_TYPE_WRITE) 
		{
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
		admin::displayTitle(
			_('Shopping Cart Fees Administration'),
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
					_("Edit Fee"):
					_("New Fee")),
				'neweditfee');
					
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
			" SELECT * FROM `{shoppingcartfees}`" .
			" WHERE 1" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			" ORDER BY " .
			(JCORE_VERSION >= '0.7'?
				" `Priority`,":
				null) .
			(JCORE_VERSION >= '0.5'?
				" `FieldID`,":
				null).
			"`Fee`");
			
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				_("No fees found."),
				TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{shoppingcartfees}`" .
					" WHERE `ID` = '".$id."'"));
			
				$form->setValues($row);
				
				if (JCORE_VERSION >= '0.5') {
					$field = sql::fetch(sql::run(
						" SELECT `Name` FROM `{dynamicformfields}`" .
						" WHERE `ID` = '".$row['FieldID']."'"));
					
					$form->setValue('FieldName', $field['Name']);
					$form->setValue('FieldValue', 
						sql::regexp2txt($row['FieldValue']));
				}
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
		
		$newid = sql::run(
			" INSERT INTO `{shoppingcartfees}` SET ".
			(JCORE_VERSION >= '0.5'?
				" `FieldID` = '" .
					(int)$values['FieldID']."'" .
				"," .
				" `FieldValue` = '" .
					sql::escape(sql::txt2regexp($values['FieldValue']))."'" .
				",":
				null) .
			" `Above` = " .
				($values['Above']?
					"'".sql::escape($values['Above'])."'":
					"0") .
				"," .
			" `Below` = " .
				($values['Below']?
					"'".sql::escape($values['Below'])."'":
					"NULL") .
				"," .
			(JCORE_VERSION >= '0.7'?
				" `WeightAbove` = " .
					($values['WeightAbove']?
						"'".sql::escape($values['WeightAbove'])."'":
						"0") .
					"," .
				" `WeightBelow` = " .
					($values['WeightBelow']?
						"'".sql::escape($values['WeightBelow'])."'":
						"NULL") .
					",":
				null) .
			" `Fee` = '".
				sql::escape($values['Fee'])."'");
			
		if (!$newid) {
			tooltip::display(
				sprintf(_("Fee couldn't be added! Error: %s"), 
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
		
		sql::run(
			" UPDATE `{shoppingcartfees}` SET ".
			(JCORE_VERSION >= '0.5'?
				" `FieldID` = '" .
					(int)$values['FieldID']."'" .
				"," .
				" `FieldValue` = '" .
					sql::escape(sql::txt2regexp($values['FieldValue']))."'" .
				",":
				null) .
			" `Above` = " .
				($values['Above']?
					"'".sql::escape($values['Above'])."'":
					"0") .
				"," .
			" `Below` = " .
				($values['Below']?
					"'".sql::escape($values['Below'])."'":
					"NULL") .
				"," .
			(JCORE_VERSION >= '0.7'?
				" `WeightAbove` = " .
					($values['WeightAbove']?
						"'".sql::escape($values['WeightAbove'])."'":
						"0") .
					"," .
				" `WeightBelow` = " .
					($values['WeightBelow']?
						"'".sql::escape($values['WeightBelow'])."'":
						"NULL") .
					",":
				null) .
			" `Fee` = '".
				sql::escape($values['Fee'])."'" .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(_("Fee couldn't be updated! Error: %s"), 
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
			" DELETE FROM `{shoppingcartfees}` " .
			" WHERE `ID` = '".$id."'");
		
		return true;
	}
	
	static function get($amount, $weight = 0, $values = null) {
		if (!isset($values))
			$values = $_POST;
		
		if (JCORE_VERSION >= '0.5') {
			$feefields = sql::fetch(sql::run(
				" SELECT GROUP_CONCAT(DISTINCT `FieldID` SEPARATOR ',') AS `FieldIDs`" .
				" FROM `{shoppingcartfees}`" .
				" LIMIT 1"));
				
			$fieldquery = null;
			
			if ($feefields['FieldIDs']) {
				$orderformfields = sql::run(
					" SELECT `ID`, `Name` FROM `{dynamicformfields}`" .
					" WHERE `ID` IN (".$feefields['FieldIDs'].")" .
					" ORDER BY `OrderID`");
				
				while($orderformfield = sql::fetch($orderformfields)) {
					$value = null;
					
					if (isset($values[$orderformfield['Name']]))
						$value = $values[$orderformfield['Name']];
					
					$fieldquery .= 
						" OR (`FieldID` = '".$orderformfield['ID']."'" .
						" AND '".sql::escape($value)."' REGEXP `FieldValue`)";
				}
			}
		}
		
		$row = sql::fetch(sql::run(
			" SELECT `Fee` FROM `{shoppingcartfees}`" .
			" WHERE '".sql::escape($amount)."' >= `Above` " .
			" AND ('".sql::escape($amount)."' < `Below`" .
				" OR `Below` IS NULL)" .
			(JCORE_VERSION >= '0.7'?
				" AND ('".sql::escape($weight)."' >= `WeightAbove`) " .
				" AND ('".sql::escape($weight)."' < `WeightBelow`" .
					" OR `WeightBelow` IS NULL)":
				null) .
			(JCORE_VERSION >= '0.5'?
				" AND (!`FieldID` " .
				($fieldquery?
					$fieldquery:
					null) .
				")":
				null) .
			" ORDER BY " .
			(JCORE_VERSION >= '0.7'?
				" `Priority` DESC, ":
				null) .
			(JCORE_VERSION >= '0.5'?
				" `FieldID` DESC, ":
				null) .
			" `Fee` DESC" .
			" LIMIT 1"));
		
		if (!$row)
			return 0;
			
		return $row['Fee'];
	}
	
	function ajaxRequest() {
		if (!$GLOBALS['USER']->loginok || 
			!$GLOBALS['USER']->data['Admin']) 
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$orderformfields = null;
		
		if (isset($_GET['orderformfields']))
			$orderformfields = $_GET['orderformfields'];
		
		if ($orderformfields) {
			include_once('lib/userpermissions.class.php');
			
			$permission = userPermissions::check(
				$GLOBALS['USER']->data['ID'],
				$this->adminPath);
			
			if ($permission['PermissionType'] != USER_PERMISSION_TYPE_WRITE ||
				$permission['PermissionIDs'])
			{
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);
				return true;
			}
			
			$this->displayAdminOrderFormFields();
			return true;
		}
		
		return false;
	}
}

class shoppingCartTaxes {
	var $ajaxRequest = null;
	var $adminPath = 'admin/modules/shoppingcart/shoppingcarttaxes';
	
	function __construct() {
		languages::load('shopping');
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
	
	// ************************************************   Admin Part
	static function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{shoppingcarttaxes}`" .
			" LIMIT 1"));
		
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				_('New Tax'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Settings'), 
			'?path=admin/modules/shoppingcart/shoppingcartsettings');
		favoriteLinks::add(
			_('Order Form'), 
			'?path=admin/content/dynamicforms');
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			_('Tax Percentage'),
			'Tax',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_FLOAT);
		$form->addAdditionalText("%");
		
		$form->add(
			_('Field name'),
			'FieldName',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 150px;');
		
		$form->addAdditionalText(
			"<a href='".url::uri('request, orderformfields').
				"&amp;request=".url::path() .
				"&amp;orderformfields=1' " .
				"class='shopping-cart-taxes-select-order-form-field ajax-content-link' " .
				"title='".htmlspecialchars(_("Define the field you would like to compare."), ENT_QUOTES)."'>" .
				_("Select Field") .
			"</a>");
				
		$form->add(
			_('Is or Contains'),
			'FieldValue',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 250px;');
			
		$form->setTooltipText(_("e.g. field name \"ShippingState\" is \"CA\" or contains \"CA, WA, NY\""));
	}
	
	function verifyAdmin(&$form) {
		$setpriority = null;
		$priorities = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_POST['setprioritysubmit']))
			$setpriority = $_POST['setprioritysubmit'];
		
		if (isset($_POST['priorities']))
			$priorities = (array)$_POST['priorities'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($setpriority) {
			if (!$priorities)
				return false;
			
			foreach($priorities as $pid => $pvalue) {
				sql::run(
					" UPDATE `{shoppingcarttaxes}`" .
					" SET `Priority` = '".(int)$pvalue."'" .
					" WHERE `ID` = '".(int)$pid."'");
			}
			
			tooltip::display(
				_("Priorities have been successfully set."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				_("Tax has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		$values = $form->getPostArray();
		$values['FieldID'] = 0;
		
		if ($form->get('FieldName')) {	
			$orderform = sql::fetch(sql::run(
				" SELECT `ID` FROM `{dynamicforms}`" .
				" WHERE `FormID` = 'shoppingorders'" .
				" LIMIT 1"));
			
			$orderformfield = sql::fetch(sql::run(
				" SELECT `ID` FROM `{dynamicformfields}`" .
				" WHERE `FormID` = '".$orderform['ID']."'" .
				" AND `Name` = '".sql::escape($form->get('FieldName'))."'"));
				
			if (!$orderformfield) {
				tooltip::display(
					_("Order form field couldn't be found!")." " .
					_("Please make sure you have " .
						"entered / selected the right field or if you wish to " .
						"add this tax to a new field please first create " .
						"the field at Content Management -> Dynamic Forms."),
					TOOLTIP_ERROR);
				
				$form->setError('FieldName', FORM_ERROR_REQUIRED);
				return false;
			}
			
			$values['FieldID'] = $orderformfield['ID'];
		}
		
		if ($edit) {
			if (!$this->edit($id, $values))
				return false;
				
			tooltip::display(
				_("Tax has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$newid = $this->add($values))
			return false;
			
		tooltip::display(
			_("Tax has been successfully created.")." " .
			"<a href='".url::uri('id, edit, delete') .
				"&amp;id=".$newid."&amp;edit=1#adminform'>" .
				__("Edit") .
			"</a>",
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminOrderFormFields() {
		echo 
			"<div class='shopping-cart-taxes-order-form-fields'>" .
				"<div class='form-title'>"._('Order Form Fields')."</div>" .
				"<table cellpadding='0' cellspacing='0' class='form-content list'>" .
					"<thead>" .
					"<tr>" .
						"<th>" .
							"<span class='nowrap'>".
							_("Select").
							"</span>" .
						"</th>" .
						"<th>" .
							"<span class='nowrap'>".
							_("Field").
							"</span>" .
						"</th>" .
						"<th style='text-align: right;'>" .
							"<span class='nowrap'>".
							_("Name").
							" &nbsp; &nbsp;</span>" .
						"</th>" .
					"</tr>" .
					"</thead>" .
					"<tbody>";
					
		$form = sql::fetch(sql::run(
			" SELECT `ID` FROM `{dynamicforms}`" .
			" WHERE `FormID` = 'shoppingorders'" .
			" LIMIT 1"));
		
		$rows = sql::run(
			" SELECT * FROM `{dynamicformfields}`" .
			" WHERE `FormID` = '".$form['ID']."'" .
			" AND `ValueType`" .
			" AND `Name` != ''" .
			" ORDER BY `OrderID`, `Title`");
		
		$i = 1;
		while ($row = sql::fetch($rows)) {
			echo
				"<tr".
					($i%2?" class='pair'":NULL).">" .
					"<td align='center'>" .
						"<a href='javascript://' " .
							"onclick='jQuery(\"#newedittaxform #entryFieldName\")" .
								".val(\"".$row['Name']."\");" .
								(JCORE_VERSION >= '0.7'?
									"jQuery(this).closest(\".tipsy\").hide();":
									"jQuery(this).closest(\".qtip\").qtip(\"hide\");") .
								"' " .
							"class='shopping-cart-taxes-select-order-form-field'>" .
						"</a>" .
					"</td>" .
					"<td class='auto-width'>" .
						"<b>" .
						$row['Title'] .
						"</b>" .
					"</td>" .
					"<td style='text-align: right;'>" .
						"<span class='nowrap'>" .
							$row['Name'] .
						"</span>" .
					"</td>" .
				"</tr>";
			
			$i++;
		}
		
		echo
					"</tbody>" .
				"</table>";
				
		echo
			"</div>";
	}
	
	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				_("Priority")."</span></th>" .
			"<th><span class='nowrap'>".
				_("Tax")."</span></th>" .
			"<th><span class='nowrap'>".
				_("Field")."</span></th>" .
			"<th><span class='nowrap'>".
				_("Is or Contains")."</span></th>";
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
		echo
			"<td>" .
				"<input type='text' name='priorities[".$row['ID']."]' " .
					"value='".$row['Priority']."' " .
					"class='order-id-entry' tabindex='1' />" .
			"</td>" .
			"<td>" .
				"<span class='nowrap bold'>" .
					$row['Tax']."%" .
				"</span>" .
			"</td>";
	
		$fieldname = null;
	
		if ($row['FieldID']) {
			$field = sql::fetch(sql::run(
				" SELECT `Name` FROM `{dynamicformfields}`" .
				" WHERE `ID` = '".$row['FieldID']."'"));
			
			if ($field)
				$fieldname = $field['Name'];
			else
				$fieldname = "<span class='red'>" .
					_("Field deleted!") .
					"</span>";
		}
		
		echo
			"<td>" .
				"<span class='nowrap'>" .
				$fieldname .
				"</span>" .
			"</td>" .
			"<td class='auto-width'>" .
				sql::regexp2txt($row['FieldValue']) .
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
			"<input type='submit' name='setprioritysubmit' value='" .
				htmlspecialchars(_("Set Priority"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminList($rows) {
		echo
			"<form action='".
				url::uri('edit, delete')."' method='post'>";
			
		echo 
			"<table cellpadding='0' cellspacing='0' class='list'>" .
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
		admin::displayTitle(
			_('Shopping Cart Taxes Administration'),
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
					_("Edit Tax"):
					_("New Tax")),
				'newedittax');
					
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
			" SELECT * FROM `{shoppingcarttaxes}`" .
			($this->userPermissionIDs?
				" WHERE `ID` IN (".$this->userPermissionIDs.")":
				null) .
			" ORDER BY " .
			" `Priority`, " .
			" `FieldID`, " .
			" `Tax`");
			
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				_("No taxes found."),
				TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{shoppingcarttaxes}`" .
					" WHERE `ID` = '".$id."'"));
				
				$form->setValues($row);
				
				$field = sql::fetch(sql::run(
					" SELECT `Name` FROM `{dynamicformfields}`" .
					" WHERE `ID` = '".$row['FieldID']."'"));
				
				$form->setValue('FieldName', $field['Name']);
				$form->setValue('FieldValue', 
					sql::regexp2txt($row['FieldValue']));
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
		
		$newid = sql::run(
			" INSERT INTO `{shoppingcarttaxes}` SET ".
			" `FieldID` = '" .
				(int)$values['FieldID']."'," .
			" `FieldValue` = '" .
				sql::escape(sql::txt2regexp($values['FieldValue']))."'," .
			" `Tax` = '".
				sql::escape($values['Tax'])."'");
			
		if (!$newid) {
			tooltip::display(
				sprintf(_("Tax couldn't be added! Error: %s"), 
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
		
		sql::run(
			" UPDATE `{shoppingcarttaxes}` SET ".
			" `FieldID` = '" .
				(int)$values['FieldID']."'," .
			" `FieldValue` = '" .
				sql::escape(sql::txt2regexp($values['FieldValue']))."'," .
			" `Tax` = '".
				sql::escape($values['Tax'])."'" .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(_("Tax couldn't be updated! Error: %s"), 
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
			" DELETE FROM `{shoppingcarttaxes}` " .
			" WHERE `ID` = '".$id."'");
		
		return true;
	}
	
	static function get($values = null) {
		if (!isset($values))
			$values = $_POST;
		
		$taxfields = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(DISTINCT `FieldID` SEPARATOR ',') AS `FieldIDs`" .
			" FROM `{shoppingcarttaxes}`" .
			" LIMIT 1"));
		
		$fieldquery = null;
		
		if ($taxfields['FieldIDs']) {
			$orderformfields = sql::run(
				" SELECT `ID`, `Name` FROM `{dynamicformfields}`" .
				" WHERE `ID` IN (".$taxfields['FieldIDs'].")" .
				" ORDER BY `OrderID`");
			
			while($orderformfield = sql::fetch($orderformfields)) {
				$value = null;
				$valueindex = $orderformfield['Name'];
				
				if ((!isset($values[$valueindex]) || !trim($values[$valueindex])) &&
					strpos($valueindex, 'Shipping') !== false)
				{
					$valueindex = str_replace('Shipping', '', $valueindex);
				}
				
				if (isset($values[$valueindex]) && trim($values[$valueindex]))
					$value = trim($values[$valueindex]);
				
				if (!$value)
					continue;
				
				$fieldquery .= 
					" OR (`FieldID` = '".$orderformfield['ID']."'" .
					" AND '".sql::escape($value)."' REGEXP `FieldValue`)";
			}
		}
		
		$row = sql::fetch(sql::run(
			" SELECT `Tax` FROM `{shoppingcarttaxes}`" .
			" WHERE (!`FieldID` " .
				($fieldquery?
					$fieldquery:
					null) .
				")" .
			" ORDER BY " .
			" `Priority` DESC, " .
			" `FieldID` DESC, " .
			" `Tax` DESC" .
			" LIMIT 1"));
		
		if (!$row)
			return 0;
			
		return $row['Tax'];
	}
	
	function ajaxRequest() {
		if (!$GLOBALS['USER']->loginok || 
			!$GLOBALS['USER']->data['Admin']) 
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$orderformfields = null;
		
		if (isset($_GET['orderformfields']))
			$orderformfields = $_GET['orderformfields'];
		
		if ($orderformfields) {
			include_once('lib/userpermissions.class.php');
			
			$permission = userPermissions::check(
				$GLOBALS['USER']->data['ID'],
				$this->adminPath);
			
			if ($permission['PermissionType'] != USER_PERMISSION_TYPE_WRITE ||
				$permission['PermissionIDs'])
			{
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);
				return true;
			}
			
			$this->displayAdminOrderFormFields();
			return true;
		}
		
		return false;
	}
}

class shoppingCartCheckoutForm extends dynamicForms {
	function __construct() {
		languages::load('shopping');
		
		parent::__construct(
			_('Checkout Form'), 
			'checkout');
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
	
	function process() {
		$ordermethodclass = 'shoppingOrderMethod'.
			$this->get('ordermethod');
		
		if (!class_exists($ordermethodclass) || 
			!method_exists($ordermethodclass,'process')) 
		{
			tooltip::display(
				_("We are sorry for the inconvenience but it seems this order " .
					"method doesn't have order processing capabilities. " .
					"Please choose a different order method or contact webmaster."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		// Check Stock and also calculate totals
		$cartitems = shoppingCart::getItems();
		
		$subtotal = 0;
		$discount = 0;
		$fee = 0;
		$tax = 0;
		$weight = 0;
		$taxpercentage = 0;
		
		if (JCORE_VERSION >= '0.7')
			$taxpercentage = shoppingCart::getTax();
		
		while($row = sql::fetch($cartitems)) {
			$item = sql::fetch(sql::run(
				" SELECT * FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$row['ShoppingItemID']."'"));
			
			if (isset($item['AvailableQuantity']) && 
				$item['AvailableQuantity'] < $row['Quantity']) 
			{
				tooltip::display(
					sprintf(_("We are sorry but it seems the stock for \"%s\" " .
						"has changed meanwhile and there aren't enough items in " .
						"stock now. Please go back and update your cart. Total " .
						"available items in stock for this item are: %s."),
						$item['Title'], $item['AvailableQuantity']),
					TOOLTIP_ERROR);
				return false;
			}
			
			if (JCORE_VERSION >= '0.7') {
				$weight += $row['Quantity']*$item['Weight'];
				
				if ($taxpercentage > 0 && $item['Taxable'] &&
					$row['Quantity']*$row['Price'] > 0)
					$tax += round(($row['Quantity']*$row['Price'])*$taxpercentage/100, 2);
			}
			
			$subtotal += $row['Quantity']*$row['Price'];
		}
		
		$discount = shoppingCart::getDiscount($subtotal);
		$fee = shoppingCart::getFee($subtotal, $weight);
		
		$userid = $GLOBALS['USER']->data['ID'];
		
		if (!$GLOBALS['USER']->loginok) {
			$user = $this->getPostArray();
			$user['LastVisitTimeStamp'] = date('Y-m-d H:i:s');
			
			$userid = $GLOBALS['USER']->add($user);
		}
			
		if (!$userid)
			return false;
		
		$ordermethod = new $ordermethodclass;
		$ordermethod->checkoutForm = $this;
		
		$paymentstatus = $ordermethod->process();
		$paymentresult = $ordermethod->processResult;
		
		if (!$paymentstatus)
			return false;
		
		$ordernumber = shoppingOrders::genOrderID();
		
		$orderform = new shoppingOrderForm();
		$orderform->load();
		$ordervalues = $orderform->getPostArray();
		unset($orderform);
		
		$ordervalues['OrderID'] = $ordernumber;
		$ordervalues['UserID'] = $userid;
		$ordervalues['PaymentStatus'] = $paymentstatus;
		$ordervalues['OrderMethod'] = $this->get('ordermethod');
		$ordervalues['Discount'] = $discount;
		$ordervalues['Fee'] = $fee;
		$ordervalues['Tax'] = $tax;
		$ordervalues['Subtotal'] = $subtotal;
		
		$ordervalues['OrderMethodDetails'] = 
			" - ".date('Y-m-d H:i:s')." - \n" .
			$paymentresult .
			(isset($_SESSION['HTTP_REFERER']) && $_SESSION['HTTP_REFERER']?
				"\n\n"._("Order originated from").":\n" .
				$_SESSION['HTTP_REFERER']:
				null);
		
		$orders = new shoppingOrders();
		$orderid = $orders->add($ordervalues);
		unset($orders);
		
		if (!$orderid)
			return false;
		
		$orderitems = new shoppingOrderItems();
		
		if (sql::rows($cartitems))
			sql::seek($cartitems, 0);
		
		while($row = sql::fetch($cartitems)) {
			$itemvalues['ShoppingOrderID'] = $orderid;
			$itemvalues['ShoppingItemID'] = $row['ShoppingItemID'];
			$itemvalues['ShoppingItemOptions'] = null;
			
			if (isset($row['ShoppingItemOptions']))
				$itemvalues['ShoppingItemOptions'] = $row['ShoppingItemOptions'];
			
			$itemvalues['Price'] = $row['Price'];
			$itemvalues['Quantity'] = $row['Quantity'];
			
			$newid = $orderitems->add($itemvalues);
			
			if ($newid) {
				// Update items AvailableQuantity value
				sql::run(
					" UPDATE `{shoppingitems}` SET " .
					" `AvailableQuantity` = `AvailableQuantity` - ".(int)$row['Quantity'].", " .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".$row['ShoppingItemID']."'");
			
			} else {
				tooltip::display(
					_("There were some errors while processing your order (some " .
						"items couldn't be added to your order). Please contact " .
						"us with this error and your order number as soon as possible."),
					TOOLTIP_ERROR);
			}
		}
		
		unset($orderitems);
		
		shoppingCart::clear();
		
		$postprocess = $ordermethod->postProcess($orderid);
		
		if ($postprocess)
			tooltip::display(
				sprintf(_("<b>Your order %s has been successfully saved.</b><br /> " .
					"You can now complete the payment or leave it for a later date."),
					$ordernumber) .
				" <a href='".shoppingOrders::getURL()."'>" .
					_("View Your Orders") .
				"</a>");
		else
			tooltip::display(
				sprintf(_("<b>Thank you for your order!</b><br /><br /> " .
					"Your confirmation / tracking number is <b>%s</b>, " .
					"please keep it for your record, also a receipt has been emailed to you " .
					"with all the information including your confirmation / tracking " .
					"number."), $ordernumber) .
				"<br /><br /> " .
				"<a href='".shoppingOrders::getURL()."'>" .
					_("View Your Orders") .
				"</a>",
				TOOLTIP_SUCCESS);
		
		if (!$postprocess)
			shoppingOrders::sendNotificationEmails($orderid);
		
		unset($ordermethod);
		
		return true;
	}
	
	function verify($customdatahandling = true) {
		if (!parent::verify($customdatahandling))
			return false;
		
		if (!$GLOBALS['USER']->loginok && 
			(!$GLOBALS['USER']->checkUsername($this->get('UserName')) || 
			($this->get('Email') && !$GLOBALS['USER']->checkEmail($this->get('Email')))))
			return false;
		
		if ($this->get('checkoutstep') >= 4) {
			$ordermethodclass = 'shoppingOrderMethod'.
				$this->get('ordermethod');
		
			if (!class_exists($ordermethodclass) || 
				!method_exists($ordermethodclass,'verify')) 
			{
				tooltip::display(
					_("Invalid order method selected. Please choose a " .
						"different order method below."),
					TOOLTIP_ERROR);
				
				return false;
			}
			
			$ordermethod = new $ordermethodclass;
			$ordermethod->checkoutForm = $this;
			$ordermethod->setUp();
			
			if (!$ordermethod->verify()) {
				unset($ordermethod);
				return false;
			}
		
			unset($ordermethod);
		}
		
		if ($this->get('checkoutstep') < 5) {
			$this->setValue('checkoutstep', $this->get('checkoutstep')+1);
			$this->setUp();
			return false;
		}
			
		return true;
	}
	
	function setUp() {
		$backstep = 1;
		$this->clear();
		
		$this->add(
			_('Checkout Step'),
			'checkoutstep',
			FORM_INPUT_TYPE_HIDDEN,
			true,
			1);
		
		$this->add(
			'Submitted',
			'checkoutformsubmitted',
			FORM_INPUT_TYPE_HIDDEN,
			true, 1);
		
		switch($this->get('checkoutstep')) {
			case 5:
				$this->title = _('Review Your Order');
				
				if (!$GLOBALS['USER']->loginok) {
					$this->add(
						"<b class='form-section-title'>".
							_("Account information").
						"</b>",
						'',
						FORM_STATIC_TEXT);
					
					$this->add(
						__('Username'),
						'UserName',
						FORM_INPUT_TYPE_REVIEW,
						true);
					$this->setValueType(FORM_VALUE_TYPE_LIMITED_STRING);
					
					if (JCORE_VERSION >= '0.6')
						$this->add(
							__('Password'),
							'Password',
							FORM_INPUT_TYPE_HIDDEN,
							true);
					
					$this->add(
						__('Email'),
						'Email',
						FORM_INPUT_TYPE_REVIEW,
						true);
					
					if (JCORE_VERSION >= '0.6')
						$this->add(
							__("Please note that you will need to enter a " .
								"valid e-mail address before your account is " .
								"activated. You will receive an e-mail at the " .
								"address you provided that contains an account " .
								"activation link."),
							'',
							FORM_STATIC_TEXT);
					else
						$this->add(
							_("Your password will be emailed to the email address " .
								"specified here.<br /> You will be able to change your " .
								"password by using your account page after login."),
							'',
							FORM_STATIC_TEXT);
				
					$this->add(
						__('Verification code'),
						'scimagecode',
						FORM_INPUT_TYPE_HIDDEN,
						true);
				
					$this->add(
						"<div class='separator'></div>",
						'',
						FORM_STATIC_TEXT);
				}

				$orderform = new shoppingOrderForm();
				$orderform->id = 'checkout';
				$orderform->load(false);
				
				foreach($orderform->elements as $element) {
					if (form::isInput($element)) {
						$this->add(
							$element['Title'],
							$element['Name'],
							FORM_INPUT_TYPE_REVIEW,
							$element['Required'],
							$element['Value']);
							
						$this->setValueType($element['ValueType']);
					
					} elseif ($element['Type'] == FORM_STATIC_TEXT) {
						$this->add(
							_($element['Title']),
							$element['Name'],
							FORM_STATIC_TEXT);
					
					} elseif ($element['Type'] == FORM_OPEN_FRAME_CONTAINER) {
						$this->add(
							"<div class='separator'></div>",
							'',
							FORM_STATIC_TEXT);
						
						$this->add(
							"<b class='form-section-title'>".
								$element['Title'].
							"</b>",
							$element['Name'],
							FORM_STATIC_TEXT);
					}
				}
				
				if (!count($orderform->elements)) 
					$backstep += 1;
				
				if (count($orderform->elements))
					$this->add(
						"<div class='separator'></div>",
						'',
						FORM_STATIC_TEXT);
					
				unset($orderform);
				
				$this->add(
					"<b class='form-section-title'>".
						_("Order Method").
					"</b>",
					'',
					FORM_STATIC_TEXT);
				
				$this->add(
					_('Order Method'),
					'ordermethod',
					FORM_INPUT_TYPE_HIDDEN,
					true);
				
				$ordermethod = shoppingOrderMethods::get($this->get('ordermethod'));
				
				$this->add(
					"<span class='bold'>".
						_($ordermethod['Title']).
					"</span><br />".
					_($ordermethod['Description']),
					'',
					FORM_STATIC_TEXT);
				
				$ordermethodclass = 'shoppingOrderMethod'.
					$this->get('ordermethod');
				
				$ordermethod = new $ordermethodclass;
				$ordermethod->checkoutForm = $this;
				$ordermethod->setUp();
				
				foreach($ordermethod->elements as $element) {
					if (form::isInput($element)) {
						$this->add(
							$element['Title'],
							$element['Name'],
							FORM_INPUT_TYPE_REVIEW,
							$element['Required'],
							$element['Value']);
					
						$this->setValueType($element['ValueType']);
						
					} elseif ($element['Type'] == FORM_STATIC_TEXT) {
						$this->add(
							_($element['Title']),
							$element['Name'],
							FORM_STATIC_TEXT);
					
					} elseif ($element['Type'] == 	FORM_OPEN_FRAME_CONTAINER) {
						$this->add(
							"<div class='separator'></div>",
							'',
							FORM_STATIC_TEXT);
						
						$this->add(
							"<b class='form-section-title'>".
								$element['Title'].
							"</b>",
							$element['Name'],
							FORM_STATIC_TEXT);
					}
				}
				
				if (count(shoppingOrderMethods::get()) == 1)
					$backstep += 1;
			
				if (!count($ordermethod->elements))
					$backstep += 1;
				
				unset($ordermethod);

				break;
			
			case 4:
				$this->title = _('Order Method').': ';
				
				if (!$GLOBALS['USER']->loginok) {
					$this->add(
						__('Username'),
						'UserName',
						FORM_INPUT_TYPE_HIDDEN,
						true);
					$this->setValueType(FORM_VALUE_TYPE_LIMITED_STRING);
					
					if (JCORE_VERSION >= '0.6')
						$this->add(
							__('Password'),
							'Password',
							FORM_INPUT_TYPE_HIDDEN,
							true);
					
					$this->add(
						_('Email address'),
						'Email',
						FORM_INPUT_TYPE_HIDDEN,
						true);
					
					$this->add(
						__('Verification code'),
						'scimagecode',
						FORM_INPUT_TYPE_HIDDEN,
						true);
				}
		
				$orderform = new shoppingOrderForm();
				$orderform->id = 'checkout';
				$orderform->load(false);
				
				if (!count($orderform->elements)) 
					$backstep += 1;
				
				foreach($orderform->elements as $element) {
					if (form::isInput($element)) {
						$this->add(
							$element['Title'],
							$element['Name'],
							FORM_INPUT_TYPE_HIDDEN,
							$element['Required'],
							$element['Value']);
				
						$this->setValueType($element['ValueType']);
					}
				}
						
				unset($orderform);
				
				$this->add(
					_('Order Method'),
					'ordermethod',
					FORM_INPUT_TYPE_HIDDEN,
					true);
				
				if (!shoppingOrderMethods::get($this->get('ordermethod'))) {
					tooltip::display(
						_("Invalid order method selected! Please choose a " .
							"different order method below."),
						TOOLTIP_ERROR);
					
					$this->setValue('checkoutstep', $this->get('checkoutstep')-1);
					$this->setUp();
					
					unset($ordermethod);
					return;
				}
				
				$this->title .= $GLOBALS['SHOPPING_ORDER_METHODS'][$this->get('ordermethod')]['Title'];
				
				$ordermethodclass = 'shoppingOrderMethod'.
					$this->get('ordermethod');
				
				$ordermethod = new $ordermethodclass;
				$ordermethod->checkoutForm = $this;
				$ordermethod->setUp();
				
				if (!count($ordermethod->elements)) {
					$this->setValue('checkoutstep', $this->get('checkoutstep')+1);
					$this->setUp();
					
					unset($ordermethod);
					return;
				}
				
				foreach($ordermethod->elements as $element)
					$this->elements[] = $element;
				
				unset($ordermethod);
				
				if (count(shoppingOrderMethods::get()) == 1)
					$backstep += 1;
				
				break;
			
			case 3:
				$this->title = _('Order Method');
				
				if (!$GLOBALS['USER']->loginok) {
					$this->add(
						__('Username'),
						'UserName',
						FORM_INPUT_TYPE_HIDDEN,
						true);
					$this->setValueType(FORM_VALUE_TYPE_LIMITED_STRING);
					
					if (JCORE_VERSION >= '0.6')
						$this->add(
							__('Password'),
							'Password',
							FORM_INPUT_TYPE_HIDDEN,
							true);
					
					$this->add(
						_('Email address'),
						'Email',
						FORM_INPUT_TYPE_HIDDEN,
						true);
					
					$this->add(
						__('Verification code'),
						'scimagecode',
						FORM_INPUT_TYPE_HIDDEN,
						true);
				}
		
				$orderform = new shoppingOrderForm();
				$orderform->id = 'checkout';
				$orderform->load(false);
				
				foreach($orderform->elements as $element) {
					if (form::isInput($element)) {
						$this->add(
							$element['Title'],
							$element['Name'],
							FORM_INPUT_TYPE_HIDDEN,
							$element['Required'],
							$element['Value']);
						
						$this->setValueType($element['ValueType']);
					}
				}
				
				if (!count($orderform->elements)) 
					$backstep += 1;
				
				unset($orderform);
		
				$this->add(
					_("Please select the order method you would " .
						"like to proceed with"),
					'ordermethod',
					FORM_INPUT_TYPE_RADIO,
					true);
					
				$this->addAdditionalPreText("<div class='clear-both'></div>");
		
				$methods = shoppingOrderMethods::get();
				
				if (count($methods) == 1) {
					$this->setValue('ordermethod', key($methods));
					$this->setValue('checkoutstep', $this->get('checkoutstep')+1);
					$this->setUp();
					
					return;
				}
				
				foreach($methods as $methodid => $method) {
					$this->addValue(
						$methodid,
						"<span class='shopping-order-method shopping-order-method-".
							htmlspecialchars($methodid, ENT_QUOTES).
							"'>" .
							"<b>".
								_($method['Title']).
							"</b>" .
							"<br />" .
							"<span class='comment'>" .
								_($method['Description']).
							"</span>" .
							"<br />" .
						"</span>");
				}
				
				break;
			
			case 2:
				$this->title = _('Order Form');
				
				if (!$GLOBALS['USER']->loginok) {
					$this->add(
						__('Username'),
						'UserName',
						FORM_INPUT_TYPE_HIDDEN,
						true);
					$this->setValueType(FORM_VALUE_TYPE_LIMITED_STRING);
					
					if (JCORE_VERSION >= '0.6')
						$this->add(
							__('Password'),
							'Password',
							FORM_INPUT_TYPE_HIDDEN,
							true);
					
					$this->add(
						_('Email address'),
						'Email',
						FORM_INPUT_TYPE_HIDDEN,
						true);
					
					$this->add(
						__('Verification code'),
						'scimagecode',
						FORM_INPUT_TYPE_HIDDEN,
						true);
				}
		
				$orderform = new shoppingOrderForm();
				$orderform->id = 'checkout';
				$orderform->load(false);
				
				if (!count($orderform->elements)) {
					$this->setValue('checkoutstep', $this->get('checkoutstep')+1);
					$this->setUp();
					return;
				}
				
				foreach($orderform->elements as $element)
					$this->elements[] = $element;
				
				unset($orderform);
				
				// Fill order form with users last order's values
				if ($GLOBALS['USER']->loginok && !$this->get('checkoutformsubmitted')) {
					$lastorder = sql::fetch(sql::run(
						" SELECT * FROM `{shoppingorders}`" .
						" WHERE `UserID` = '".$GLOBALS['USER']->data['ID']."'" .
						" ORDER BY `ID` DESC" .
						" LIMIT 1"));
						
					if (!$lastorder)
						$lastorder = $GLOBALS['USER']->data;
					
					foreach($lastorder as $key => $value) {
						if ($this->get($key) === null) {
							$element = $this->elements[$this->getElementID($key)];
							
							if ($element['ValueType'] == FORM_VALUE_TYPE_ARRAY)
								$this->setValue($key, explode('|', $value));
							else
								$this->setValue($key, $value);
						}
					}
				}
				
				break;
			
			case 1:
			default:
				if ($GLOBALS['USER']->loginok) {
					$this->setValue('checkoutstep', $this->get('checkoutstep')+1);
					$this->setUp();
					return;
				}
				
				$this->title = _('New User?');
				
				$this->add(
					(JCORE_VERSION >= '0.6'?
						_("Please enter your desired username / password to " .
							"continue as a new user."):
						_("Please enter your desired username to continue " .
							"as a new user.")),
					'',
					FORM_STATIC_TEXT);
		
				$this->add(
					__('Username'),
					'UserName',
					FORM_INPUT_TYPE_TEXT,
					true);
				$this->setStyle('width: 200px;');
				$this->setValueType(FORM_VALUE_TYPE_LIMITED_STRING);
				
				$orderform = new shoppingOrderForm();
				$orderform->id = 'checkout';
				$orderform->load(false);
				
				if (!count($orderform->elements)) {
					$this->add(
						_('Email address'),
						'Email',
						FORM_INPUT_TYPE_EMAIL,
						true);
					$this->setStyle('width: 250px;');
				}
				
				unset($orderform);
				
				if (JCORE_VERSION >= '0.6') {
					$this->add(
						__('Password'),
						'Password',
						FORM_INPUT_TYPE_PASSWORD,
						true);
					$this->setStyle('width: 150px;');
					$this->setTooltipText('Password', 
						sprintf(__("minimum %s characters"), MINIMUM_PASSWORD_LENGTH));
					
					$this->add(
						__('Confirm password'),
						'ConfirmPassword',
						FORM_INPUT_TYPE_CONFIRM,
						true);
					$this->setStyle('width: 150px;');
				}
				
				$this->add(
					__('Verification code'),
					'',
					FORM_INPUT_TYPE_VERIFICATION_CODE,
					true);
		
				break;
		}
		
		if ($this->get('checkoutstep') <= 1) {	
			$this->add(
				_('Continue as a New User'),
				'checkoutsubmit',
				FORM_INPUT_TYPE_SUBMIT);
			
			$this->add(
				__('Cancel'),
				'cancel',
				FORM_INPUT_TYPE_BUTTON);
			$this->addAttributes("onclick=\"window.location='".
				url::uri('shoppingcartcheckout')."';\"");
			
		} else {
			if ($this->get('checkoutstep') == 5)
				$this->add(
					_('Submit / Process Order'),
					'checkoutsubmit',
					FORM_INPUT_TYPE_SUBMIT);
			else
				$this->add(
					_('Submit / Review'),
					'checkoutsubmit',
					FORM_INPUT_TYPE_SUBMIT);
			
			if (!$GLOBALS['USER']->loginok || $this->get('checkoutstep') > 2) {
				$this->add(
					__('Back'),
					'checkoutback',
					FORM_INPUT_TYPE_BUTTON);
			
				$this->addAttributes(
					"onclick=\"this.form.checkoutstep.value=".
						(int)($this->get('checkoutstep')-$backstep).
						"; this.form.submit();\"");
			}
			
			$this->add(
				__('Cancel'),
				'cancel',
				FORM_INPUT_TYPE_BUTTON);
				
			$this->addAttributes(
				"onclick=\"window.location='".url::uri('shoppingcartcheckout')."'\"");
		}
	}
	
	function display($formdesign = true) {
		parent::display($formdesign);
		
		echo
			"<script type='text/javascript'>" .
			"jQuery('#checkoutform form').submit(function () {" .
				"jQuery('#checkoutform #buttoncheckoutsubmit').click(function() {" .
					"alert('".htmlspecialchars(_("Form is being processed. Please wait."), ENT_QUOTES)."');" .
					"return false;" .
				"});" .
				"return true;" .
			"});" .
			"</script>";
	}
}

class shoppingCart extends modules {
	static $uriVariables = 'shoppingcartid, shoppingcartremove, shoppingcartcheckout';
	var $shoppingURL;
	var $checkout = false;
	var $referrer = null;
	var $checkoutForm;
	var $similarItemsSearch = null;
	var $adminPath = 'admin/modules/shoppingcart';
	
	function __construct() {
		languages::load('shopping');
		
		if (isset($_GET['shoppingcartcheckout']))
			$this->checkout = (bool)$_GET['shoppingcartcheckout'];
		
		if (isset($_GET['shoppingcartreferrer']))
			$this->referrer = $_GET['shoppingcartreferrer'];
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
	
	function installSQL() {
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingcarts}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `SessionID` varchar(100) NOT NULL default ''," .
			" `ShoppingItemID` mediumint(8) unsigned NOT NULL default '0'," .
			" `ShoppingItemOptions` TEXT NULL," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Price` decimal(12,2) NOT NULL default '0.00'," .
			" `Quantity` tinyint(3) unsigned NOT NULL default '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `SessionID` (`SessionID`,`TimeStamp`)" .
			" ) ENGINE=MyISAM ;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingcartsettings}` (" .
			" `ID` varchar(100) NOT NULL default ''," .
			" `Value` mediumtext NULL," .
			" `TypeID` tinyint(1) unsigned NOT NULL default '1'," .
			" `OrderID` smallint(5) unsigned NOT NULL default '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `OrderID` (`OrderID`)" .
			") ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
		
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingcartsettings}`"));
		
		if (sql::display())
			return false;
		
		if (!$exists) {
			sql::run(
				" INSERT INTO `{shoppingcartsettings}` (`ID`, `Value`, `TypeID`, `OrderID`) VALUES" .
				" ('Shopping_Cart', '', 0, 1)," .
				" ('Shopping_Cart_Currency', '$', 1, 1)," .
				" ('Shopping_Cart_Currency_Position', 'Left', 1, 1)," .
				" ('Shopping_Cart_Weight_Unit', 'kg', 1, 1)," .
				" ('Shopping_Cart_Automatic_Continue_Shopping', '0', 3, 1)," .
				" ('Shopping_Cart_Similar_Items', '', 0, 2)," .
				" ('Shopping_Cart_Display_Similar_Items', '1', 3, 2)," .
				" ('Shopping_Cart_Similar_Items_Limit', '5', 1, 2)," .
				" ('Shopping_Cart_Email_Notification', '', 0, 3)," .
				" ('Shopping_Cart_Send_Notification_Email_On_New_Order', '1', 3, 3)," .
				" ('Shopping_Cart_Send_Notification_Email_To', '', 1, 3)," .
				" ('Shopping_Cart_Limits', '', 0, 4)," .
				" ('Shopping_Cart_Minimum_Item_Order', '1', 1, 4)," .
				" ('Shopping_Cart_Minimum_Order_Price', '1', 1, 4);");
		}
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingcartdiscounts}` (" .
			" `ID` smallint(5) unsigned NOT NULL auto_increment," .
			" `Above` decimal(12,2) default NULL," .
			" `Below` decimal(12,2) default NULL," .
			" `DiscountPercent` tinyint(3) unsigned NOT NULL default '0'," .
			" `UserID` MEDIUMINT UNSIGNED NOT NULL DEFAULT  '0'," .
			" `Priority` SMALLINT NOT NULL DEFAULT  '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `Above` (`Above`,`Below`)," .
			" KEY `UserID` (`UserID`)," .
			" KEY `Priority` (`Priority`)" .
			") ENGINE=MyISAM ;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingcartfees}` (" .
			" `ID` smallint(5) unsigned NOT NULL auto_increment," .
			" `Above` decimal(12,2) NOT NULL default '0.00'," .
			" `Below` decimal(12,2) NULL default NULL," .
			" `WeightAbove` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0.00'," .
			" `WeightBelow` DECIMAL( 10, 2 ) NULL DEFAULT NULL," .
			" `Fee` decimal(12,2) NOT NULL default '0.00'," .
			" `FieldID` int(10) UNSIGNED NOT NULL DEFAULT 0," .
			" `FieldValue` VARCHAR( 255 ) NOT NULL DEFAULT ''," .
			" `Priority` SMALLINT NOT NULL DEFAULT  '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `Above` (`Above`,`Below`)," .
			" KEY `WeightAbove` (`WeightAbove`,`WeightBelow`)," .
			" KEY `FieldID` (`FieldID`)," .
			" KEY `Priority` (`Priority`)" .
			") ENGINE=MyISAM ;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingcarttaxes}` (" .
			" `ID` smallint(5) unsigned NOT NULL auto_increment," .
			" `Tax` decimal(5,2) NOT NULL default '0.00'," .
			" `FieldID` int(10) UNSIGNED NOT NULL DEFAULT 0," .
			" `FieldValue` VARCHAR( 255 ) NOT NULL DEFAULT ''," .
			" `Priority` SMALLINT NOT NULL DEFAULT  '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `FieldID` (`FieldID`)," .
			" KEY `Priority` (`Priority`)" .
			") ENGINE=MyISAM ;");
		
		if (sql::display())
			return false;
			
		return true;
	}
	
	function installFiles() {
		$css = 
			".shopping-cart-item .pictures {\n" .
			"	float: left;\n" .
			"	margin: 0 5px 0 0;\n" .
			"	padding: 0;\n" .
			"	width: auto;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-item .picture {\n" .
			"	width: auto;\n" .
			"	margin: 0;\n" .
			"	padding: 0;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-item .picture img {\n" .
			"	width: 50px;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-item .picture-title,\n" .
			".shopping-cart-item .picture-details\n" .
			"{\n" .
			"	display: none;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-remove a {\n" .
			"	width: 32px;\n" .
			"	height: 32px;\n" .
			"	overflow: hidden;\n" .
			"	display: block;\n" .
			"	margin: 0;\n" .
			"	padding: 0;\n" .
			"	border: 0;\n" .
			"	background: transparent;\n" .
			"	background-image: url(\"http://icons.jcore.net/32/window-close.png\");\n" .
			"}\n" .
			"\n" .
			".shopping-cart-totals {\n" .
			"	width: 250px;\n" .
			"	float: right;\n" .
			"	margin: 10px 0 10px 10px;\n" .
			"	text-align: right;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-total-title {\n" .
			"	display: block;\n" .
			"	width: 130px;\n" .
			"	float: left;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-subtotal,\n" .
			".shopping-cart-discount,\n" .
			".shopping-cart-fee,\n" .
			".shopping-cart-tax,\n" .
			".shopping-cart-grand-total\n" .
			"{\n" .
			"	padding: 2px 5px 2px 5px;\n" .
			"	white-space: nowrap;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-grand-total {\n" .
			"	font-size: 120%;\n" .
			"	border-top: 1px solid;\n" .
			"	margin-top: 5px;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-buttons {\n" .
			"	clear: both;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-grand-total td {\n" .
			"	border-top-style: solid;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-grand-total td {\n" .
			"	font-size: 120%;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-shopping-button {\n" .
			"	margin-right: 5px;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-checkout-button.button {\n" .
			"	float: right;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-discounts-select-user {\n" .
			"	padding: 0 0 5px 20px;\n" .
			"	background: url(\"http://icons.jcore.net/16/user.png\") no-repeat;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-discounts-users .shopping-cart-discounts-select-user {\n" .
			"	display: block;\n" .
			"	padding: 0;\n" .
			"	width: 16px;\n" .
			"	height: 16px;\n" .
			"	background: url(\"http://icons.jcore.net/16/target.png\") no-repeat;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-fees-select-order-form-field,\n" .
			".shopping-cart-taxes-select-order-form-field\n" .
			"{\n" .
			"	padding: 0 0 5px 20px;\n" .
			"	background: url(\"http://icons.jcore.net/16/target.png\") no-repeat;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-fees-order-form-fields .shopping-cart-fees-select-order-form-field,\n" .
			".shopping-cart-taxes-order-form-fields .shopping-cart-taxes-select-order-form-field\n" .
			"{\n" .
			"	display: block;\n" .
			"	padding: 0;\n" .
			"	width: 16px;\n" .
			"	height: 16px;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-similar-items {\n" .
			"	padding-top: 30px;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-checkout .shopping-order-method {\n" .
			"	display: inline;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-checkout .form-entry-ordermethod .form-entry-title {\n" .
			"	width: auto;\n" .
			"}\n" .
			"\n" .
			".shopping-cart-checkout .form-entry-ordermethod .form-entry-content {\n" .
			"	margin-left: 0;\n" .
			"}\n" .
			"\n" .
			".as-modules-shoppingcart a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/shopping-cart.png\");\n" .
			"}\n" .
			"\n" .
			".as-shopping-cart-settings a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/shopping-cart-settings.png\");\n" .
			"}\n" .
			"\n" .
			".as-shopping-cart-discounts a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/shopping-cart-discounts.png\");\n" .
			"}\n" .
			"\n" .
			".as-shopping-cart-fees a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/shopping-cart-fees.png\");\n" .
			"}\n" .
			"\n" .
			".as-shopping-cart-taxes a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/shopping-cart-taxes.png\");\n" .
			"}\n";
		
		return
			files::save(SITE_PATH.'template/modules/css/shoppingcart.css', $css, true);
	}
	
	// ************************************************   Admin Part
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE) {
			favoriteLinks::add(
				_('New Discount'), 
				'?path=admin/modules/shoppingcart/shoppingcartdiscounts#adminform');
			favoriteLinks::add(
				_('New Fee'), 
				'?path=admin/modules/shoppingcart/shoppingcartfees#adminform');
		}
		
		favoriteLinks::add(
			_('Orders'), 
			'?path=admin/modules/shoppingorders');
	}
	
	function displayAdminTitle($ownertitle = null) {
		echo
			_('Shopping Cart Administration');
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdminSections() {
		$discounts = 0;
		$fees = 0;
		$taxes = 0;
		
		if (ADMIN_ITEMS_COUNTER_ENABLED) {
			$discounts = shoppingCartDiscounts::countAdminItems();
			$fees = shoppingCartFees::countAdminItems();
			
			if (JCORE_VERSION >= '0.7')
				$taxes = shoppingCartTaxes::countAdminItems();
		}
			
		echo
			"<div class='admin-section-item as-shopping-cart-settings'>" .
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/shoppingcartsettings' " .
					"title='".htmlspecialchars(_("Set checkout methods, currency and cart limits"), ENT_QUOTES).
					"'>" .
					"<span>" .
					_("Cart Settings")."" .
					"</span>" .
				"</a>" .
			"</div>" .
			"<div class='admin-section-item as-shopping-cart-discounts'>";
		
		if ($discounts)
			counter::display((int)$discounts);
		
		echo
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/shoppingcartdiscounts' " .
					"title='".htmlspecialchars(_("Set global or user discounts"), ENT_QUOTES).
					"'>" .
					"<span>" .
					_("Discount Settings")."" .
					"</span>" .
				"</a>" .
			"</div>" .
			"<div class='admin-section-item as-shopping-cart-fees'>";
		
		if ($fees)
			counter::display((int)$fees);
		
		echo
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/shoppingcartfees' " .
					"title='".htmlspecialchars(_("Set global or local fees"), ENT_QUOTES).
					"'>" .
					"<span>" .
					_("Fees Settings")."" .
					"</span>" .
				"</a>" .
			"</div>";
		
		if (JCORE_VERSION >= '0.7') {
			echo
				"<div class='admin-section-item as-shopping-cart-taxes'>";
			
			if ($taxes)
				counter::display((int)$taxes);
			
			echo
					"<a href='".url::uri('ALL') .
						"?path=".admin::path()."/shoppingcarttaxes' " .
						"title='".htmlspecialchars(_("Set and manage taxes"), ENT_QUOTES).
						"'>" .
						"<span>" .
						_("Tax Settings")."" .
						"</span>" .
					"</a>" .
				"</div>";
		}
	}
	
	function displayAdmin() {
		//$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
		
		echo 
			"<div tabindex='0' class='fc" .
				form::fcState('fcshcs', true) .
				"'>" .
				"<a class='fc-title' name='fcshcs'>";
		
		$this->displayAdminTitle();
		
		echo
				"</a>" .
				"<div class='fc-content'>";
		
		$this->displayAdminSections();
		
		echo
					"<div class='clear-both'></div>" .
				"</div>" .
			"</div>";
		
		echo
				"<div class='clear-both'></div>" .
			"</div>"; //admin-content
	}
	
	// ************************************************   Client Part
	static function getURL() {
		$url = modules::getOwnerURL('ShoppingCart');
		
		if (!$url)
			return url::uri(shoppingCart::$uriVariables);
		
		return $url;	
	}
	
	static function getDiscount($amount, $userid = null) {
		return shoppingCartDiscounts::get($amount);
	}
	
	static function getFee($amount, $weight = 0) {
		return shoppingCartFees::get($amount, $weight);
	}
	
	static function getTax() {
		return shoppingCartTaxes::get();
	}
	
	static function getSubTotal() {
		$row = sql::fetch(sql::run(
			" SELECT SUM(`Price`*`Quantity`) AS `SubTotal`" .
			" FROM `{shoppingcarts}` " .
			" WHERE `SessionID` = '".sql::escape(session_id())."'" .
			" LIMIT 1"));
		
		return (float)$row['SubTotal'];		
	}
	
	static function getGrandTotal() {
		$subtotal = shoppingCart::getSubTotal();
		
		$tax = 0;
		$weight = 0;
		
		if (JCORE_VERSION >= '0.7') {
			$taxpercentage = shoppingCart::getTax();
			
			$rows = sql::run(
				" SELECT `ShoppingItemID`, `Quantity`, `Price`" .
				" FROM `{shoppingcarts}` " .
				" WHERE `SessionID` = '".sql::escape(session_id())."'");
			
			while($row = sql::fetch($rows)) {
				$item = sql::fetch(sql::run(
					" SELECT `Weight`, `Taxable` FROM `{shoppingitems}`" .
					" WHERE `ID` = '".$row['ShoppingItemID']."'"));
				
				if (!$item)
					continue;
				
				if ($taxpercentage > 0 && $item['Taxable'] &&
					$row['Quantity']*$row['Price'] > 0)
					$tax += round(($row['Quantity']*$row['Price'])*$taxpercentage/100, 2);
				
				$weight += $row['Quantity']*$item['Weight'];
			}
		}
		
		$discount = shoppingCart::getDiscount($subtotal+$tax);
		$fee = shoppingCart::getFee($subtotal+$tax, $weight);
		
		return $subtotal+$tax-$discount+$fee;
	}
	
	static function getItems() {
		return sql::run(
			" SELECT * FROM `{shoppingcarts}` " .
			" WHERE `SessionID` = '".sql::escape(session_id())."'" .
			" ORDER BY `ID`");		
	}
	
	static function clear() {
		return sql::run(
			" DELETE FROM `{shoppingcarts}` " .
			" WHERE `SessionID` = '".sql::escape(session_id())."'");		
	}
	
	static function cleanUp() {
		return sql::run(
			" DELETE FROM `{shoppingcarts}` " .
			" WHERE `TimeStamp` < DATE_SUB(NOW(), INTERVAL 1 DAY)");
	}
	
	function verify() {
		$remove = null;
		$cartid = null;
		$add = null;
		$itemid = null;
		$itemquantity = null;
		$itemquantities = null;
		$options = null;
		$referrer = null;
		
		if (isset($_GET['shoppingcartremove'])) {
			$remove = $_GET['shoppingcartremove'];
			unset($_GET['shoppingcartremove']);
		}
		
		if (isset($_GET['shoppingcartid'])) {
			$cartid = (int)$_GET['shoppingcartid'];
			unset($_GET['shoppingcartid']);
		}
		
		if (isset($_POST['shoppingcartaddsubmit'])) {
			$add = $_POST['shoppingcartaddsubmit'];
			unset($_POST['shoppingcartaddsubmit']);
		}
		
		if (isset($_POST['shoppingitemid']))
			$itemid = (int)$_POST['shoppingitemid'];
		
		if (isset($_POST['shoppingitemquantity'])) {
			$itemquantity = (int)$_POST['shoppingitemquantity'];
			unset($_POST['shoppingitemquantity']);
		}
			
		if (isset($_POST['shoppingitemquantities'])) {
			$itemquantities = (array)$_POST['shoppingitemquantities'];
			unset($_POST['shoppingitemquantities']);
		}
			
		if (isset($_POST['shoppingitemoptions'])) {
			$options = (array)$_POST['shoppingitemoptions'];
			unset($_POST['shoppingitemoptions']);
		}
			
		if (isset($_GET['shoppingcartreferrer']))
			$referrer = $_GET['shoppingcartreferrer'];
		
		if ($remove) {
			if (!$cartid) {
				tooltip::display(
					_("No item defined to remove from your cart."),
					TOOLTIP_ERROR);
				return false;
			}
			
			sql::run(
				" DELETE FROM `{shoppingcarts}`" .
				" WHERE `ID` = '".$cartid."'");
			
			tooltip::display(
				_("Item has been successfully removed from your cart.") .
				" <a href='".
					($this->referrer?
						urldecode($this->referrer):
						$this->shoppingURL) .
					"'>".
					_("Continue Shopping").
				"</a>",
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if ($add) {
			if (!$itemid) {
				tooltip::display(
					_("No item defined to add to your cart."),
					TOOLTIP_ERROR);
				return false;
			}
			
			$item = sql::fetch(sql::run(
				" SELECT * FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$itemid."'" .
				" AND (`AvailableQuantity` " .
					" OR `AvailableQuantity` IS NULL)" .
				" LIMIT 1"));
			
			if (!$item) {
				tooltip::display(
					_("The item you wanted to add to your cart cannot be found " .
						"or is unfortunately temporarily out of stock. " .
						"To check the availability of this item please contact us."),
					TOOLTIP_ERROR);
				return false;
			}
			
			$category = sql::fetch(sql::run(
				" SELECT * FROM `{shoppings}`" .
				" WHERE !`Deactivated`" .
				" AND `ID` = '".$item['ShoppingID']."'"));
			
			if (!$category) {
				tooltip::display(
					_("The item you wanted to add to your cart is temporary " .
						"not available. Please contact us for more information " .
						"on this item."),
					TOOLTIP_ERROR);
				return false;
			}
			
			if (JCORE_VERSION >= '0.5' && !shopping::verifyPermission($category)) {
				tooltip::display(
					_("The item you wanted to add to your cart can only " .
						"be accessed by members. Please login first and " .
						"try again."),
					TOOLTIP_ERROR);
				return false;
			}
			
			if (JCORE_VERSION >= '0.7' && $item['SpecialPrice'] != '') {
				if ((!$item['SpecialPriceStartDate'] || 
						$item['SpecialPriceStartDate'] <= date('Y-m-d')) &&
					(!$item['SpecialPriceEndDate'] || 
						$item['SpecialPriceEndDate'] >= date('Y-m-d')))
				{
					$item['Price'] = $item['SpecialPrice'];
				}
			}
			
			if (!$itemquantity)
				$itemquantity = 1;
			
			$itemoptions = null;
			
			if (JCORE_VERSION >= '0.7' && $options) {
				foreach($options as $optionid => $priceid) {
					if (!$optionid)
						continue;
					
					$option = sql::fetch(sql::run(
						" SELECT * FROM `{shoppingitemoptions}`" .
						" WHERE `ShoppingItemID` = '".$item['ID']."'" .
						" AND `ID` = '".(int)$optionid."'"));
					
					if (!$option) {
						tooltip::display(
							_("Item option cannot be found!"),
							TOOLTIP_ERROR);
						return false;
					}
					
					if (!$option['Required'] && !$priceid)
						continue;
					
					if ($option['Required'] && !$priceid) {
						tooltip::display(
							__("Field(s) marked with an asterisk (*) is/are required.")." " .
							sprintf(_("Item option \"%s\" is required for \"%s\"!"),
								$option['Title'], $item['Title']). " " .
							"<a href='" .
								($this->referrer?
									urldecode($this->referrer):
									$this->shoppingURL) .
									"'>" .
								__("Go Back") .
							"</a>",
							TOOLTIP_ERROR);
						return false;
					}
					
					if (!in_array($option['TypeID'], array(
						FORM_INPUT_TYPE_CHECKBOX, FORM_INPUT_TYPE_RADIO,
						FORM_INPUT_TYPE_SELECT, FORM_INPUT_TYPE_MULTISELECT)))
					{
						$price = sql::fetch(sql::run(
							" SELECT * FROM `{shoppingitemoptionprices}`" .
							" WHERE `OptionID` = '".(int)$optionid."'" .
							" LIMIT 1"));
						
						if (!$price)
							continue;
						
						$item['Price'] +=
							($price['PriceType'] == 2?
								 round($price['Price']*$item['Price']/100, 2):
								$price['Price']);
						
						$itemoptions[] = $option['Title'].": " .
							$priceid;
						
						continue;
					}
					
					if (is_array($priceid)) {
						$itemoptionprices = null;
						
						foreach($priceid as $pricesid) {
							$price = sql::fetch(sql::run(
								" SELECT * FROM `{shoppingitemoptionprices}`" .
								" WHERE `OptionID` = '".(int)$optionid."'" .
								" AND `ID` = '".(int)$pricesid."'"));
							
							if (!$price)
								continue;
								
							$item['Price'] += 
								($price['PriceType'] == 2?
									 round($price['Price']*$item['Price']/100, 2):
									$price['Price']);
							
							$itemoptionprices[] = $price['Title'];
						}
						
						if ($itemoptionprices)
							$itemoptions[] = $option['Title'].": " .
								implode(', ', $itemoptionprices);
						
						continue;
					}
					
					$price = sql::fetch(sql::run(
						" SELECT * FROM `{shoppingitemoptionprices}`" .
						" WHERE `OptionID` = '".(int)$optionid."'" .
						" AND `ID` = '".(int)$priceid."'"));
					
					if (!$price)
						continue;
						
					$item['Price'] += 
						($price['PriceType'] == 2?
							 round($price['Price']*$item['Price']/100, 2):
							$price['Price']);
					
					$itemoptions[] = $option['Title'].": ".$price['Title'];
				}
			}
			
			sql::run(
				" INSERT INTO `{shoppingcarts}` SET" .
				" `SessionID` = '".sql::escape(session_id())."'," .
				" `ShoppingItemID` = '".$item['ID']."'," .
				($itemoptions?
					" `ShoppingItemOptions` = '" .
						sql::escape(implode(', ', $itemoptions))."',":
					null) .
				" `TimeStamp` = NOW()," .
				" `Price` = '".$item['Price']."'," .
				" `Quantity` = '".$itemquantity."'");
				
			tooltip::display(
				_("Item has been successfully added to your cart.") .
				" <a href='".
					($this->referrer?
						urldecode($this->referrer):
						$this->shoppingURL) .
					"'>".
					_("Continue Shopping").
				"</a>" .
				" - " .
				" <a href='".url::uri()."'>".
					_("Refresh Cart").
				"</a>",
				TOOLTIP_SUCCESS);
				
			if (SHOPPING_CART_AUTOMATIC_CONTINUE_SHOPPING && $referrer)
				echo "<script type='text/javascript'>" .
						"window.location='".
							urldecode($referrer)."';" .
					"</script>";
			
			shoppingCart::cleanUp();
			return true;
		}
		
		if ($itemquantities) {
			foreach($itemquantities as $cartid => $quantity)
				sql::run(
					" UPDATE `{shoppingcarts}` SET" .
					" `Quantity` = '".(int)$quantity."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".(int)$cartid."'");
			
			tooltip::display(
				_("Cart has been successfully updated."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		return false;
	}
	
	static function constructPrice($price) {
		if (method_exists('shopping', 'constructPrice'))
			return shopping::constructPrice($price);
		
		if (!defined('SHOPPING_CART_CURRENCY') || !SHOPPING_CART_CURRENCY)
			return number_format($price, 2);
		
		return
				"<span class='shopping-currency'>" .
					SHOPPING_CART_CURRENCY .
				"</span>" .
				number_format($price, 2);
	}
	
	static function displayPrice($price) {
		echo shoppingCart::constructPrice($price);
	}
	
	function displayCheckout() {
		$this->checkoutForm = new shoppingCartCheckoutForm();
		$this->checkoutForm->setUp();
		
		if (!$GLOBALS['USER']->loginok && 
			$this->checkoutForm->get('checkoutstep') < 1) 
		{
			$loginform = new form(
				_('Already Registered?'), 
				'memberlogin');
	
			$loginform->add(
				_("Please enter your login information " .
					"below to checkout with your account."),
				'',
				FORM_STATIC_TEXT);
			
			$GLOBALS['USER']->setupLoginForm($loginform);
			
			$loginform->add(
				__('Login'),
				'login',
				FORM_INPUT_TYPE_SUBMIT);
			
			$loginform->addAdditionalPreText(
				"<div class='align-right'>" .
					"[ <a href='".url::uri('requestpassword')."&amp;requestpassword=1'>" .
						__("Forgot your password?")."</a> ]" .
				"</div>");
			
			$loginform->add(
				__('Cancel'),
				'cancel',
				FORM_INPUT_TYPE_BUTTON);
			$loginform->addAttributes("onclick=\"window.location='".
				url::uri('shoppingcartcheckout')."';\"");
				
			$loginform->display();	
			unset($loginform);
			
			if (defined('REGISTRATIONS_SUSPENDED') && REGISTRATIONS_SUSPENDED)
				return false;
		}
		
		if (!$this->checkoutForm->verify()) {
			$this->checkoutForm->display();
			return;
		}
		
		if (!$this->checkoutForm->process()) {
			$this->checkoutForm->display();
			return;
		}
	}
	
	function displaySummary() {
		if (!$this->shoppingURL)
			$this->shoppingURL = shopping::getURL();
		
		echo
			"<div class='shopping-cart shopping-cart-summary'>";
		
		tooltip::caching(true);
		$this->verify();
		tooltip::caching(false);
		
		$rows = $this->getItems();
		$cartitems = sql::rows($rows);
			
		if (!$cartitems) {
			tooltip::display(
				_("There are no items in your cart."),
				TOOLTIP_NOTIFICATION);
			
		} else {
			echo 
				"<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>" .
					"<th class='shopping-cart-item'>" .
						"<span class='nowrap'>".
						_("Item").
						"</span>" .
					"</th>" .
					"<th class='shopping-cart-quantity'>" .
						"<span class='nowrap'>".
						_("Quantity").
						"</span>" .
					"</th>" .
					"<th class='shopping-cart-total'>" .
						"<span class='nowrap'>".
						_("Price").
						"</span>" .
					"</th>" .
				"</tr>" .
				"</thead>" .
				"<tbody>";
		}
				
		$subtotal = 0;
		$discount = 0;
		$fee = 0;
		$tax = 0;
		$weight = 0;
		$taxpercentage = 0;
		
		if (JCORE_VERSION >= '0.7')
			$taxpercentage = shoppingCart::getTax();
		
		$i = 0;		
		while ($row = sql::fetch($rows)) {
			$item = sql::fetch(sql::run(
				" SELECT * FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$row['ShoppingItemID']."'" .
				" LIMIT 1"));
			
			echo 
				"<tr".($i%2?" class='pair'":NULL).">" .
					"<td class='shopping-cart-item auto-width'>" .
						"<a href='".$this->shoppingURL.
							"&amp;shoppingid=".$item['ShoppingID'].
							"&amp;shoppingitemid=".$item['ID']."'>" .
							"<b>" .
							$item['Title'] .
							"</b>" .
						"</a>" .
					"</td>" .
					"<td class='shopping-cart-quantity' align='center'>" .
						"<span class='nowrap'>" .
						$row['Quantity'] .
						"</span>" .
					"</td>" .
					"<td class='shopping-cart-total' align='right'>" .
						"<span class='nowrap'>";
			
			shoppingCart::displayPrice($row['Quantity']*$row['Price']);
			
			echo
						"</span>" .
					"</td>" .
				"</tr>";
			
			if (JCORE_VERSION >= '0.7') {
				$weight += $row['Quantity']*$item['Weight'];
				
				if ($taxpercentage > 0 && $item['Taxable'] &&
					$row['Quantity']*$row['Price'] > 0)
					$tax += round(($row['Quantity']*$row['Price'])*$taxpercentage/100, 2);
			}
			
			$subtotal += $row['Quantity']*$row['Price'];
			$i++;
		}
		
		if ($cartitems) {
			$discount = $this->getDiscount($subtotal+$tax);
			$fee = $this->getFee($subtotal+$tax, $weight);
			
			echo 
				"</tbody>" .
			"</table>";
		
			echo
			"<div class='shopping-cart-totals'>" .
				"<div class='shopping-cart-subtotal'>" .
					"<span class='shopping-cart-total-title'>".
						_("Subtotal").":" .
					"</span>" .
					"<span class='bold nowrap'>";
			
			shoppingCart::displayPrice($subtotal);
			
			echo
					"</span>" .
				"</div>";
		
			if ($tax) {
				echo				
				"<div class='shopping-cart-tax'>" .
					"<span class='shopping-cart-total-title'>".
						_("Tax").":" .
					"</span>" .
					"<span class='bold nowrap'>";
				
				shoppingCart::displayPrice($tax);
				
				echo
					"</span>" .
				"</div>";
			}
		
			if ($discount) {
				echo				
				"<div class='shopping-cart-discount'>" .
					"<span class='shopping-cart-total-title'>".
						_("Discount").":" .
					"</span>" .
					"<span class='bold nowrap'>";
				
				shoppingCart::displayPrice($discount);
				
				echo
					"</span>" .
				"</div>";
			}
		
			if ($fee) {
				echo
				"<div class='shopping-cart-fee'>" .
					"<span class='shopping-cart-total-title'>".
						htmlspecialchars(_("Shipping & Handling")).":" .
					"</span>" .
					"<span class='bold nowrap'>";
				
				shoppingCart::displayPrice($fee);
				
				echo
					"</span>" .
				"</div>";
			}
		
			echo
				"<div class='shopping-cart-grand-total bold'>" .
					"<span class='shopping-cart-total-title'>".
						_("Grand Total").":" .
					"</span>" .
					"<span class='bold nowrap'>";
			
			shoppingCart::displayPrice($subtotal+$tax-$discount+$fee);
			
			echo
					"</span>" .
				"</div>" .
			"</div>";
		}
		
		echo
			"<div class='shopping-cart-buttons'>" .
				"<div class='shopping-cart-view-cart-button button'>" .
					"<a href='".shoppingCart::getURL()."'>".
						_("View My Cart").
					"</a>" .
				"</div>" .
				"<div class='clear-both'></div>" .
			"</div>";
			
		echo 
			"<div class='clear-both'></div>" .
			"</form>" .
			"</div>";
	}
	
	function displayInfo() {
		echo
			"<div class='shopping-cart-info'>";
		
		tooltip::caching(true);
		$this->verify();
		tooltip::caching(false);
		
		$grandtotal = 0;
		$rows = $this->getItems();
		$cartitems = sql::rows($rows);
		
		if ($cartitems)
			$grandtotal = $this->getGrandTotal();
		
		echo
			"<a href='".shoppingCart::getURL()."'>" .
				"<span class='shopping-cart-info-text-my-cart'>" .
				sprintf(_("My Cart: %s items"),
					"</span>" .
					"<span class='shopping-cart-info-items'>" .
						$cartitems .
					"</span>" .
					"<span class='shopping-cart-info-text-items'>") .
				" </span>" .
				"<span class='shopping-cart-info-grand-total'>";
		
		shoppingCart::displayPrice($grandtotal);
		
		echo
				"</span>" .
			"</a>" .
		"</div>";
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		switch(strtolower($this->arguments)) {
			case 'summary':
				$this->displaySummary();
				return true;
		
			case 'info':
				$this->displayInfo();
				return true;
		
			default:
				return true;
		}
	}
	
	function displayListHeader() {
		echo
			"<th class='shopping-cart-ref-number'>" .
				"<span class='nowrap'>".
				_("Ref. Number").
				"</span>" .
			"</th>" .
			"<th class='shopping-cart-item'>" .
				"<span class='nowrap'>".
				(JCORE_VERSION >= '0.7'?
					_("Item / Options"):
					_("Item / Added to Cart")) .
				"</span>" .
			"</th>" .
			"<th style='text-align: right;' class='shopping-cart-quantity'>" .
				"<span class='nowrap'>".
				_("Quantity").
				"</span>" .
			"</th>" .
			"<th style='text-align: right;' class='shopping-cart-price'>" .
				"<span class='nowrap'>".
				_("Unit Price").
				"</span>" .
			"</th>" .
			"<th style='text-align: right;' class='shopping-cart-total'>" .
				"<span class='nowrap'>".
				_("Total Price").
				"</span>" .
			"</th>";
	}
	
	function displayListHeaderOptions() {
	}
	
	function displayListHeaderFunctions() {
		echo
			"<th class='shopping-cart-remove'>" .
				"<span class='nowrap'>".
				_("Remove").
				"</span>" .
			"</th>";
	}
	
	function displayListItem(&$row, $item = null) {
		if (!$this->shoppingURL)
			$this->shoppingURL = shopping::getURL();
		
		if (!$item)
			$item = sql::fetch(sql::run(
				" SELECT * FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$row['ShoppingItemID']."'" .
				" LIMIT 1"));
		
		$itemlink = $this->shoppingURL.
			"&amp;shoppingid=".$item['ShoppingID'].
			"&amp;shoppingitemid=".$item['ID'];
		
		if ($item['Keywords'])
			$this->similarItemsSearch .= 
				($this->similarItemsSearch?', ':null).
				$item['Keywords'];
		else
			$this->similarItemsSearch .= 
				($this->similarItemsSearch?', ':null).
				str_replace(' ', ',', $item['Title']);
			
		echo
			"<td class='shopping-cart-ref-number'>" .
				"<span class='nowrap'>" .
				$item['RefNumber'] .
				"</span>" .
			"</td>" .
			"<td class='shopping-cart-item auto-width'>";
	
		if (JCORE_VERSION >= '0.5') {		
			$pictures = new shoppingItemPictures();
			$pictures->selectedOwnerID = $item['ID'];
			$pictures->limit = 1;
			$pictures->showPaging = false;
			$pictures->display();
			unset($pictures);
		}
		
		echo
				"<a href='".$itemlink."'>" .
					"<b>" .
					$item['Title'] .
					"</b>" .
				"</a>" .
				(JCORE_VERSION >= '0.7'?
					"<div class='shopping-cart-item-options comment'>" .
						$row['ShoppingItemOptions'] .
					"</div>":
					"<div class='shopping-cart-item-details comment'>" .
						calendar::datetime($row['TimeStamp']) .
					"</div>") .
			"</td>" .
			"<td style='text-align: right;' class='shopping-cart-quantity'>";
			
		if ($item['ShowQuantityPicker'] && isset($row['SessionID']) &&
			$row['SessionID']) 
		{
			echo
				"<select name='shoppingitemquantities[".$row['ID']."]'>";
		
			if (!$item['MaxQuantityAtOnce'])
				$item['MaxQuantityAtOnce'] = 30;
			
			if ($item['AvailableQuantity'] && 
				$item['AvailableQuantity'] < $item['MaxQuantityAtOnce'])
				$item['MaxQuantityAtOnce'] = $item['AvailableQuantity'];
			
			for($i = 1; $i <= $item['MaxQuantityAtOnce']; $i++) {	
				echo
						"<option ".
							($i == $row['Quantity']?
								"selected='selected'":
								null) .
							">".$i."</option>";
			}
					
			echo
				"</select>";
		
		} else {
			echo
				"<span class='nowrap'>" .
				$row['Quantity'] .
				"</span>";
		}
		
		echo
				"</td>" .
				"<td style='text-align: right;' class='shopping-cart-price'>" .
					"<span class='nowrap'>";
		
		shoppingCart::displayPrice($row['Price']);
		
		echo
					"</span>" .
				"</td>" .
				"<td style='text-align: right;' class='shopping-cart-total'>" .
					"<span class='nowrap'>";
		
		shoppingCart::displayPrice($row['Quantity']*$row['Price']);
		
		echo
					"</span>" .
				"</td>";
	}
	
	function displayListItemOptions(&$row) {
	}
	
	function displayListItemFunctions(&$row) {
		echo
			"<td class='shopping-cart-remove' align='center'>" .
				"<a class='confirm-link' " .
					"title='".htmlspecialchars(_("Remove Item"), ENT_QUOTES)."' " .
					"href='".url::uri(shoppingCart::$uriVariables) .
					"&amp;shoppingcartid=".$row['ID']."&amp;shoppingcartremove=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayListSubTotalTitle(&$totals) {
		echo
			_("Subtotal").":";
	}
	
	function displayListSubTotalValue(&$totals) {
		shoppingCart::displayPrice($totals['SubTotal']);
	}
	
	function displayListSubTotal(&$totals) {
		echo
			"<span class='shopping-cart-total-title'>";
		
		$this->displayListSubTotalTitle($totals);
		
		echo
			"</span>" .
			"<span class='bold'>";
		
		$this->displayListSubTotalValue($totals);
		
		echo
			"</span>";
	}
	
	function displayListTaxTotalTitle(&$totals) {
		echo
			_("Tax").":";
	}
	
	function displayListTaxTotalValue(&$totals) {
		shoppingCart::displayPrice($totals['Tax']);
	}
	
	function displayListTaxTotal(&$totals) {
		echo
			"<span class='shopping-cart-total-title'>";
		
		$this->displayListTaxTotalTitle($totals);
		
		echo
			"</span>" .
			"<span class='bold'>";
		
		$this->displayListTaxTotalValue($totals);
		
		echo
			"</span>";
	}
	
	function displayListDiscountTotalTitle(&$totals) {
		echo
			_("Discount").":";
	}
	
	function displayListDiscountTotalValue(&$totals) {
		shoppingCart::displayPrice($totals['Discount']);
	}
	
	function displayListDiscountTotal(&$totals) {
		echo
			"<span class='shopping-cart-total-title'>";
		
		$this->displayListDiscountTotalTitle($totals);
		
		echo
			"</span>" .
			"<span class='bold'>";
		
		$this->displayListDiscountTotalValue($totals);
		
		echo
			"</span>";
	}
	
	function displayListFeeTotalTitle(&$totals) {
		echo
			htmlspecialchars(_("Shipping & Handling")).":";
	}
	
	function displayListFeeTotalValue(&$totals) {
		shoppingCart::displayPrice($totals['Fee']);
	}
	
	function displayListFeeTotal(&$totals) {
		echo
			"<span class='shopping-cart-total-title'>";
		
		$this->displayListFeeTotalTitle($totals);
		
		echo
			"</span>" .
			"<span class='bold'>";
		
		$this->displayListFeeTotalValue($totals);
		
		echo
			"</span>";
	}
	
	function displayListGrandTotalTitle(&$totals) {
		echo
			_("Grand Total").":";
	}
	
	function displayListGrandTotalValue(&$totals) {
		shoppingCart::displayPrice($totals['GrandTotal']);
	}
	
	function displayListGrandTotal(&$totals) {
		echo
			"<span class='shopping-cart-total-title'>";
		
		$this->displayListGrandTotalTitle($totals);
		
		echo
			"</span>" .
			"<span class='bold'>";
		
		$this->displayListGrandTotalValue($totals);
		
		echo
			"</span>";
	}
	
	function displayListTotals(&$totals) {
		echo
			"<div class='shopping-cart-totals'>" .
				"<div class='shopping-cart-subtotal'>";
		
		$this->displayListSubTotal($totals);
		
		echo
				"</div>";
		
		if ($totals['Tax'] > 0) {
			echo				
				"<div class='shopping-cart-tax'>";
			
			$this->displayListTaxTotal($totals);
			
			echo
				"</div>";
		}
		
		if ($totals['Discount'] > 0) {
			echo				
				"<div class='shopping-cart-discount'>";
			
			$this->displayListDiscountTotal($totals);
			
			echo
				"</div>";
		}
		
		if ($totals['Fee'] > 0) {
			echo
				"<div class='shopping-cart-fee'>";
			
			$this->displayListFeeTotal($totals);
			
			echo
				"</div>";
		}
		
		echo
				"<div class='shopping-cart-grand-total bold'>";
		
		$this->displayListGrandTotal($totals);
		
		echo
				"</div>" .
			"</div>";
	}
	
	function displayListTips(&$totals) {
		$nextdiscount = shoppingCartDiscounts::getNext($totals['SubTotal']);
		
		if (!$nextdiscount)
			return;
		
		echo
			"<p class='shopping-cart-discount'>" .
				sprintf(_("<b>TIP:</b> Orders over %s get %s discount!"),
					shoppingCart::constructPrice($nextdiscount['Above']),
					$nextdiscount['DiscountPercent'].'%') .
			"</p>";
	}
	
	function displayListFooter(&$totals) {
		$this->displayListTips($totals);
		
		echo 
			"<p class='shopping-cart-hint comment'>" .
				_("* For more details on each item click on the item's title. " .
					"To change the quantity of an item pick a new quantity and " .
					"press the Update Cart button.") .
			"</p>";
	}
	
	function displayListFunctions() {
		echo
			"<input type='button' name='checkout' " .
				"value='".htmlspecialchars(_("Checkout"), ENT_QUOTES)."' " .
				"class='button shopping-cart-checkout-button' " .
				"onclick=\"window.location='" .
					url::uri(shoppingCart::$uriVariables) .
					"&amp;shoppingcartcheckout=1" .
					"';\" />" .
			"<input type='button' name='continueshopping' " .
				"value='".htmlspecialchars(_("Continue Shopping"), ENT_QUOTES)."' " .
				"class='button shopping-cart-shopping-button' " .
				"onclick=\"window.location='" .
					($this->referrer?
						urldecode($this->referrer):
						$this->shoppingURL) .
					"';\" />" .
			"<input type='submit' name='updatecart' " .
				"value='".htmlspecialchars(_("Update Cart"), ENT_QUOTES)."' " .
				"class='button submit shopping-cart-update-cart-button' />";
	}
	
	function displayList(&$rows) {
		if (sql::rows($rows)) {
			echo 
				"<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>";
			
			$this->displayListHeader();
			$this->displayListHeaderOptions();
			$this->displayListHeaderFunctions();
			
			echo
				"</tr>" .
				"</thead>" .
				"<tbody>";
		}
		
		$totals = array(
			'SubTotal' => 0,
			'Discount' => 0,
			'Fee' => 0,
			'Tax' => 0,
			'Weight' => 0,
			'GrandTotal' => 0);
		
		$i = 0;
		$taxpercentage = 0;
		
		if (JCORE_VERSION >= '0.7')
			$taxpercentage = shoppingCart::getTax();
		
		while ($row = sql::fetch($rows)) {
			$item = sql::fetch(sql::run(
				" SELECT * FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$row['ShoppingItemID']."'" .
				" LIMIT 1"));
			
			echo 
				"<tr".($i%2?" class='pair'":NULL).">";
			
			$this->displayListItem($row, $item);
			$this->displayListItemOptions($row);
			$this->displayListItemFunctions($row);
			
			echo
				"</tr>";
			
			if (JCORE_VERSION >= '0.7') {
				$totals['Weight'] += $row['Quantity']*$item['Weight'];
				
				if ($taxpercentage > 0 && $item['Taxable'] &&
					$row['Quantity']*$row['Price'] > 0)
					$totals['Tax'] += round(($row['Quantity']*$row['Price'])*$taxpercentage/100, 2);
			}
			
			$totals['SubTotal'] += $row['Quantity']*$row['Price'];
			$i++;
		}
		
		if (sql::rows($rows)) {
			$totals['Discount'] = $this->getDiscount($totals['SubTotal']+$totals['Tax']);
			$totals['Fee'] = $this->getFee($totals['SubTotal']+$totals['Tax'], $totals['Weight']);
			
			$totals['GrandTotal'] = $totals['SubTotal']+$totals['Tax']-
				$totals['Discount']+$totals['Fee'];
			
			echo 
				"</tbody>" .
			"</table>";
		}
		
		$this->displayListTotals($totals);
		$this->displayListFooter($totals);
	}
	
	function displaySimilarItemsTitle() {
		if ($this->similarItemsSearch)
			echo _("Similar Items");
		else
			echo _("Latest Items");
	}
	
	function displaySimilarItemsItems() {
		$this->load('Shopping');
		$shoppingitems = new shoppingItems();
		$shoppingitems->similar = true;
		$shoppingitems->limit = SHOPPING_CART_SIMILAR_ITEMS_LIMIT;
		$shoppingitems->search = $this->similarItemsSearch;
		
		if ($this->similarItemsSearch)
			$shoppingitems->randomize = true;
		
		$shoppingitems->showPaging = false;
		$shoppingitems->display();
		unset($shoppingitems);
	}
	
	function displaySimilarItems() {
		echo
			"<div class='shopping-cart-similar-items'>" .
				"<div class='separator'></div>" .
				"<h3>";
		
		$this->displaySimilarItemsTitle();
		
		echo
				"</h3>";
		
		$this->displaySimilarItemsItems();
		
		echo
				"<div class='clear-both'></div>" .
			"</div>";
	}
	
	function display() {
		if ($this->displayArguments())
			return;
		
		if (!$this->shoppingURL)
			$this->shoppingURL = shopping::getURL();
		
		echo
			"<div class='shopping-cart'>" .
			"<form action='".url::uri(shoppingCart::$uriVariables)."' " .
				"method='post'>";
		
		tooltip::display();
		
		$this->verify();
		$rows = $this->getItems();
			
		if (!sql::rows($rows)) {
			tooltip::display(
				_("There are no items in your cart."),
				($this->checkout?
					TOOLTIP_ERROR:
					TOOLTIP_NOTIFICATION));
			
			$this->checkout = null;
			
		} else if (defined('SHOPPING_CART_MINIMUM_ITEM_ORDER') && 
			sql::rows($rows) < SHOPPING_CART_MINIMUM_ITEM_ORDER)
		{
			tooltip::display(
				sprintf(_("<b>Checkout Limit:</b> You must purchase at least " .
					"%s item(s) to complete your checkout."),
					SHOPPING_CART_MINIMUM_ITEM_ORDER),
				($this->checkout?
					TOOLTIP_ERROR:
					TOOLTIP_NOTIFICATION));
			
			$this->checkout = null;
			
		} else if (defined('SHOPPING_CART_MINIMUM_ORDER_PRICE') &&
			$this->getGrandTotal() < SHOPPING_CART_MINIMUM_ORDER_PRICE)
		{
			tooltip::display(
				sprintf(_("<b>Checkout Limit:</b> You must purchase item(s) " .
					"for at least %s to complete your checkout."),
					shoppingCart::constructPrice(SHOPPING_CART_MINIMUM_ORDER_PRICE)),
				($this->checkout?
					TOOLTIP_ERROR:
					TOOLTIP_NOTIFICATION));
			
			$this->checkout = null;
		}
		
		$this->displayList($rows);
			
		if (!$this->checkout) {
			echo
				"<div class='shopping-cart-buttons'>";
			
			$this->displayListFunctions();
			
			echo
					"<div class='clear-both'></div>" .
				"</div>";
		}
			
		echo 
			"<div class='clear-both'></div>" .
			"</form>" .
			"</div>";
			
		if ($this->checkout) {
			echo 
				"<div class='shopping-cart-checkout'>";
			
			$this->displayCheckout();
			
			echo 
				"</div>";
			
			return;
		}
			
		if (SHOPPING_CART_DISPLAY_SIMILAR_ITEMS)
			$this->displaySimilarItems();
	}
}

?>