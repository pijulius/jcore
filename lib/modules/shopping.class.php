<?php

/***************************************************************************
 *            shopping.class.php
 * 			  Ver 0.7.1 - Jan 5, 2010
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
include_once('lib/modules/shoppingcart.class.php');

modules::register(
	'shopping', 
	_('Shopping Store'),
	_('Sell products in a directory like structure'));

class shoppingItemsForm extends dynamicForms {
	function __construct() {
		languages::load('shopping');
		
		parent::__construct(
			_('Shopping Items'), 'shoppingitems');
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
	
	function verify($customdatahandling = true) {
		if (!parent::verify(true))
			return false;
		
		return true;
	}
	
	function load($addformbuttons = true) {
		parent::load($addformbuttons);
		
		if (defined('SHOPPING_CART_WEIGHT_UNIT') && SHOPPING_CART_WEIGHT_UNIT) 
			$this->addAdditionalText(
				'Weight',
				"<span class='shopping-weight-unit'> " .
					SHOPPING_CART_WEIGHT_UNIT .
				"</span>");
	}
}

class shoppingItemRating extends starRating {
	var $sqlTable = 'shoppingitemratings';
	var $sqlRow = 'ShoppingItemID';
	var $sqlOwnerTable = 'shoppingitems';
	var $adminPath = 'admin/modules/shopping/shoppingitems/shoppingitemrating';
	
	function __construct() {
		languages::load('shopping');
		
		parent::__construct();
		
		$this->selectedOwner = _('Item');
		$this->uriRequest = "modules/shopping/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
}

class shoppingItemDigitalGoods extends attachments {
	var $selectedOwnerOrderID = null;
	var $sqlTable = 'shoppingitemdigitalgoods';
	var $sqlRow = 'ShoppingItemID';
	var $sqlOwnerTable = 'shoppingitems';
	var $sqlOwnerCountField = 'DigitalGoods';
	var $adminPath = 'admin/modules/shopping/shoppingitems/shoppingitemdigitalgoods';
	
	function __construct() {
		languages::load('shopping');
		
		parent::__construct();
		
		if (isset($_GET['shoppingorderid']))
			$this->selectedOwnerOrderID = (int)$_GET['shoppingorderid'];
		
		$this->rootPath = $this->rootPath.'shopping/digitalgoods/';
		$this->rootURL = $this->rootURL.'shopping/digitalgoods/';
		
		$this->selectedOwner = _('Item');
		$this->uriRequest = "modules/shopping/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
	
	function download($id) {
		if (!(int)$id) {
			tooltip::display(
				_("No file selected to download!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{" .$this->sqlTable . "}`" .
			" WHERE `ID` = '".(int)$id."'" .
			" LIMIT 1"));
		
		if (!$row) {
			tooltip::display(
				_("The selected file cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (!$GLOBALS['USER']->loginok) {
			tooltip::display(
				_("You need to be logged in to download this file. " .
					"Please login or register."),
				TOOLTIP_ERROR);
			return false;
		}
		
		$orderitem = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorderitems}`" .
			" WHERE `ShoppingItemID` = '".$row['ShoppingItemID']."'" .
			" AND `ShoppingOrderID` = '".$this->selectedOwnerOrderID."'"));
		
		if (!$orderitem) {
			tooltip::display(
				_("An order with this file cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `ID` = '".$orderitem['ShoppingOrderID']."'"));
		
		if (!$order) {
			tooltip::display(
				_("The order you selected cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		if ($order['UserID'] != $GLOBALS['USER']->data['ID'] &&
			!$GLOBALS['USER']->data['Admin']) 
		{
			tooltip::display(
				_("You are not the owner of this order!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		if ($order['PaymentStatus'] != SHOPPING_ORDER_PAYMENT_STATUS_PAID) {
			tooltip::display(
				_("Your payment has not yet been confirmed!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (in_array($order['OrderStatus'], array(
				SHOPPING_ORDER_STATUS_CANCELLED,
				SHOPPING_ORDER_STATUS_REJECTED))) 
		{
			tooltip::display(
				_("Your order has been cancelled!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$item = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingitems}`" .
			" WHERE `ID` = '".$row['ShoppingItemID']."'"));
		
		$downloadable = sql::fetch(sql::run(
			" SELECT `DigitalGoodsExpiration` FROM `{shoppings}`" .
			" WHERE `ID` = '".$item['ShoppingID']."'" .
			" AND (!`DigitalGoodsExpiration`" .
				" OR DATEDIFF(NOW(), '".$order['TimeStamp']."')" .
					" <= `DigitalGoodsExpiration`)"));
		
		if (!$downloadable) {
			tooltip::display(
				_("Your download has expired!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$newid = sql::run(
			" INSERT INTO `{shoppingorderdownloads}` SET" .
			" `ShoppingOrderID` = '".$this->selectedOwnerOrderID."'," .
			" `ShoppingItemID` = '".$item['ID']."'," .
			" `ShoppingItemDigitalGoodID` = '".$id."'," .
			" `IP` = '".ip2long($_SERVER['REMOTE_ADDR'])."'," .
			" `StartTimeStamp` = NOW()," .
			" `FinishTimeStamp` = NULL");
		
		$downloaded = attachments::download($id);
		
		if ($downloaded && $newid)
			sql::run(
				" UPDATE `{shoppingorderdownloads}` SET" .
				" `StartTimeStamp` = `StartTimeStamp`," .
				" `FinishTimeStamp` = NOW()" .
				" WHERE `ID` = '".$newid."'");
		
		return $downloaded;
	}
}

class shoppingItemAttachments extends attachments {
	var $sqlTable = 'shoppingitemattachments';
	var $sqlRow = 'ShoppingItemID';
	var $sqlOwnerTable = 'shoppingitems';
	var $adminPath = 'admin/modules/shopping/shoppingitems/shoppingitemattachments';
	
	function __construct() {
		languages::load('shopping');
		
		parent::__construct();
		
		if (JCORE_VERSION >= '0.5') {
			$this->rootPath = $this->rootPath.'shopping/';
			$this->rootURL = $this->rootURL.'shopping/';
		}
		
		$this->selectedOwner = _('Item');
		$this->uriRequest = "modules/shopping/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
}

class shoppingItemComments extends comments {
	var $sqlTable = 'shoppingitemcomments';
	var $sqlRow = 'ShoppingItemID';
	var $sqlOwnerTable = 'shoppingitems';
	var $adminPath = 'admin/modules/shopping/shoppingitems/shoppingitemcomments';
	
	function __construct() {
		languages::load('shopping');
		
		parent::__construct();
		
		$this->selectedOwner = _('Item');
		$this->uriRequest = "modules/shopping/".$this->uriRequest;
		
		if ($GLOBALS['ADMIN'])
			$this->commentURL = shopping::getURL().
				"&shoppingid=".admin::getPathID(2) .
				"&shoppingitemid=".admin::getPathID();
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
}

class shoppingItemPictures extends pictures {
	var $sqlTable = 'shoppingitempictures';
	var $sqlRow = 'ShoppingItemID';
	var $sqlOwnerTable = 'shoppingitems';
	var $adminPath = 'admin/modules/shopping/shoppingitems/shoppingitempictures';
	
	function __construct() {
		languages::load('shopping');
		
		parent::__construct();
		
		if (JCORE_VERSION >= '0.5') {
			$this->rootPath = $this->rootPath.'shopping/';
			$this->rootURL = $this->rootURL.'shopping/';
		}
		
		$this->selectedOwner = _('Item');
		$this->uriRequest = "modules/shopping/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
}

class shoppingItems {
	static $uriVariables = 'ajax, request, shoppingid, shoppingitemid, shoppingitemslimit, shoppingitemattachmentslimit, shoppingitempictureslimit, shoppingitemrating, rate';
	var $searchable = true;
	var $arguments;
	var $selectedID;
	var $selectedShoppingID;
	var $selectedShoppingIDs;
	var $shoppingCartURL;
	var $shoppingURL;
	var $limit = 10;
	var $keywordsCloudLimit = 21;
	var $ignorePaging = false;
	var $showPaging = true;
	var $search = null;
	var $randomize = false;
	var $active = false;
	var $popular = false;
	var $discussed = false;
	var $rated = false;
	var $top = false;
	var $similar = false;
	var $fullItems = false;
	var $subgroupItems = true;
	var $ajaxPaging = AJAX_PAGING;
	var $ajaxRequest = null;
	var $adminPath = 'admin/modules/shopping/shoppingitems';
	
	function __construct() {
		languages::load('shopping');
		
		if (isset($_GET['shoppingitemid']))
			$this->selectedID = (int)$_GET['shoppingitemid'];
		
		if (isset($_GET['shoppingid']))
			$this->selectedShoppingID = (int)$_GET['shoppingid'];
		
		if (isset($_GET['shoppingitems'])) {
			$this->active = strstr($_GET['shoppingitems'], '1');
			$this->popular = strstr($_GET['shoppingitems'], '2');
			$this->discussed = strstr($_GET['shoppingitems'], '3');
			$this->similar = strstr($_GET['shoppingitems'], '4');
		}
		
		if (isset($_GET['searchin']) && isset($_GET['search']) && 
			($_GET['searchin'] == 'modules/shopping/shoppingitems' ||
			$_GET['searchin'] == 'modules/shopping'))
			$this->search = trim(strip_tags($_GET['search']));
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
	
	function SQL() {
		$categories = null;
		$ignorecategories = null;
			
		if ($this->search) {
			if (JCORE_VERSION >= '0.5' && !$GLOBALS['USER']->loginok) {
				$row = sql::fetch(sql::run(
					" SELECT GROUP_CONCAT(`ID` SEPARATOR ',') AS `CategoryIDs`" .
					" FROM `{shoppings}`" .
					" WHERE !`Deactivated`" .
					" AND `MembersOnly` " .
					" AND !`ShowToGuests`" .
					" LIMIT 1"));
				
				if ($row['CategoryIDs'])
					$ignorecategories = explode(',', $row['CategoryIDs']);
			}
			
			$row = sql::fetch(sql::run(
				" SELECT GROUP_CONCAT(`ID` SEPARATOR ',') AS `CategoryIDs`" .
				" FROM `{shoppings}`" .
				" WHERE !`Deactivated`" .
				($ignorecategories?
					" AND `ID` NOT IN (".implode(',', $ignorecategories).")":
					null) .
				sql::search(
					$this->search,
					array('Title', 'Description')) .
				" LIMIT 1"));
			
			if ($row['CategoryIDs']) {
				foreach(explode(',', $row['CategoryIDs']) as $id) {
					$categories[] = $id;
					foreach(shopping::getTree($id) as $category)
						$categories[] = $category['ID'];
				}
			}
		}
		
		return
			" SELECT * FROM `{shoppingitems}`" .
			" WHERE !`Deactivated`" .
			(!$this->selectedID && $this->selectedShoppingIDs?
				" AND `ShoppingID` IN (".implode(',',$this->selectedShoppingIDs).")":
				null) .
			($this->selectedID?
				" AND `ID` = '".$this->selectedID."'":
				null) .
			($this->search && !$this->selectedID?
				" AND (1" .
				sql::search(
					$this->search,
					(JCORE_VERSION >= '0.7'? 
						dynamicForms::searchableFields('shoppingitems'):
						array('Title', 'Description', 'Keywords')),
					($this->similar?'OR':'AND')) .
				($categories?
					" OR (`ShoppingID` IN (".implode(',', $categories)."))":
					null) .
				" )" .
				($ignorecategories?
					" AND `ShoppingID` NOT IN (".implode(',', $ignorecategories).")":
					null):
				null) .
			($this->active?
				" AND `Views`":
				null) .
			($this->popular?
				" AND `NumberOfOrders`":
				null) .
			($this->discussed?
				" AND `Comments`":
				null) .
			($this->rated?
				" AND `Rating`":
				null) .
			" ORDER BY" .
			($this->randomize?
				" RAND()":
				($this->search && !$this->selectedID?
					" `Views` DESC,":
					null) .
				($this->active?
					" `Views` DESC,":
					null) .
				($this->popular?
					" `NumberOfOrders` DESC,":
					null) .
				($this->discussed?
					" `Comments` DESC,":
					null) .
				($this->rated?
					" `Rating` DESC,":
					null) .
				" `OrderID`, `TimeStamp` DESC");
	}
	
	// ************************************************   Admin Part
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				_('New Item'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			_('Orders'), 
			'?path=admin/modules/shoppingorders');
		favoriteLinks::add(
			_('Cart Settings'), 
			'?path=admin/modules/shoppingcart');
	}
	
	function setupAdminForm(&$form) {
		$edit = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		$form->add(
			'ShoppingID',
			'ShoppingID',
			FORM_INPUT_TYPE_HIDDEN,
			true,
			admin::getPathID());
		$form->setValueType(FORM_VALUE_TYPE_INT);
					
		if (JCORE_VERSION >= '0.7') {
			$itemsform = new shoppingItemsForm();
			$itemsform->load(false);
			
			$firstcontainer = true;
			foreach($itemsform->elements as $element) {
				if ($element['Type'] == FORM_OPEN_FRAME_CONTAINER && 
					$firstcontainer) 
				{
					$element['Required'] = true;
					$firstcontainer = false;
				}
				
				$form->elements[] = $element;
			}
			
			if ($edit) {
				$form->insert(
					'Deactivated',
					__('Insert as New'),
					'InsertAsNew',
					FORM_INPUT_TYPE_CHECKBOX,
					false,
					'1');
				$form->setValueType(
					'InsertAsNew',
					FORM_VALUE_TYPE_BOOL);
					
				$form->addAdditionalText(
					'InsertAsNew',
					"<span class='comment'>" .
					_("(will create a new item)").
					"</span>");
			}
			
			if (defined('SHOPPING_CART_CURRENCY')) {
				if (defined('SHOPPING_CART_CURRENCY_POSITION') &&
					stristr(SHOPPING_CART_CURRENCY_POSITION, 'right'))
				{
					$form->addAdditionalText(
						'Price',
						"<span class='shopping-currency'>" .
							SHOPPING_CART_CURRENCY .
						"</span>");
					
					$form->addAdditionalText(
						'SpecialPrice',
						"<span class='shopping-currency'>" .
							SHOPPING_CART_CURRENCY .
						"</span>");
					
				} else {
					$form->addAdditionalPreText(
						'Price',
						"<span class='shopping-currency'>" .
							SHOPPING_CART_CURRENCY .
						"</span>");
					
					$form->addAdditionalPreText(
						'SpecialPrice',
						"<span class='shopping-currency'>" .
							SHOPPING_CART_CURRENCY .
						"</span>");
				}
			}
			
			unset($itemsform);
			return;
		}
		
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 350px;');
		
		$form->add(
			__('Description'),
			'Description',
			FORM_INPUT_TYPE_EDITOR);
		$form->setStyle('height: 400px;');
		$form->setValueType(FORM_VALUE_TYPE_HTML);
					
		$form->add(
			_('Item Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER,
			true);
		
		$form->add(
			_('Price'),
			'Price',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 80px;');
		$form->setValueType(FORM_VALUE_TYPE_FLOAT);
		
		if (defined('SHOPPING_CART_CURRENCY')) {
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
		}
		
		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(_("e.g. 170"));
		
		$form->add(
			_('Ref. Number'),
			'RefNumber',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 120px;');
		$form->setTooltipText(_("e.g. ITEM-0711"));
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			_('Quantity Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			_('Available Quantity'),
			'AvailableQuantity',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(_("e.g. 1000 (leave it empty for unlimited)"));
		else
			$form->addAdditionalText(_("e.g. 1000 (leave it empty for unlimited)"));
		
		$form->add(
			_('Max Order Quantity at Once'),
			'MaxQuantityAtOnce',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(_("e.g. 15 (default 30)"));
		else
			$form->addAdditionalText(_("e.g. 15 (default 30)"));
		
		$form->add(
			_('Show Quantity Picker'),
			'ShowQuantityPicker',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Blogging Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Created on'),
			'TimeStamp',
			FORM_INPUT_TYPE_TIMESTAMP);
		$form->setStyle('width: 170px;');
		$form->setValueType(FORM_VALUE_TYPE_TIMESTAMP);
		
		$form->add(
			__('Path'),
			'Path',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 300px;');
		
		$form->add(
			__('Keywords'),
			'Keywords',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 250px;');
		
		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(__("e.g. oranges, lemons, limes"));
		else
			$form->addAdditionalText(" ("._("e.g. oranges, lemons, limes").")");
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Rating Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Enable Rating'),
			'EnableRating',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
		$form->add(
			__('Enable Guest Rating'),
			'EnableGuestRating',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Comments Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Enable Comments'),
			'EnableComments',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
		$form->add(
			__('Enable Guest Comments'),
			'EnableGuestComments',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Deactivated'),
			'Deactivated',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
		$form->addAdditionalText(
			"<span class='comment' style='text-decoration: line-through;'>" .
			__("(marked with strike through)").
			"</span>");	
			
		if ($edit) {
			$form->add(
				__('Insert as New'),
				'InsertAsNew',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
				
			$form->addAdditionalText(
				"<span class='comment'>" .
				_("(will create a new item)").
				"</span>");
		}	
			
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
	
	function setupAdminFormOptions(&$form, $row = null) {
		$customoptions = $form->get('CustomOptions');
		$customoptionindex = 0;
		$customoptionpriceindexes = null;
		$customoptionshtml = null;
		$customoptionpriceshtml = null;
		
		$optiontypes = array(
			FORM_INPUT_TYPE_TEXT,
			FORM_INPUT_TYPE_TEXTAREA,
			FORM_INPUT_TYPE_CHECKBOX,
			FORM_INPUT_TYPE_RADIO,
			FORM_INPUT_TYPE_SELECT,
			FORM_INPUT_TYPE_MULTISELECT,
			FORM_INPUT_TYPE_DATE,
			FORM_INPUT_TYPE_TIME,
			FORM_INPUT_TYPE_TIMESTAMP,
			FORM_INPUT_TYPE_COLOR,
			FORM_INPUT_TYPE_TEL,
			FORM_INPUT_TYPE_RANGE,
			FORM_INPUT_TYPE_NUMBER);
		
		$multioptiontypes = array(
			FORM_INPUT_TYPE_CHECKBOX,
			FORM_INPUT_TYPE_RADIO,
			FORM_INPUT_TYPE_SELECT,
			FORM_INPUT_TYPE_MULTISELECT);
		
		if ($row) {
			$customoptions = array();
			
			$options = sql::run(
				" SELECT * FROM `{shoppingitemoptions}`" .
				" WHERE `ShoppingItemID` = '".$row['ID']."'" .
				" ORDER BY `OrderID`, `ID`");
			
			while($option = sql::fetch($options)) {
				$customoptions[$option['ID']]['OrderID'] = 
					$option['OrderID'];
				$customoptions[$option['ID']]['Title'] = 
					$option['Title'];
				$customoptions[$option['ID']]['TypeID'] = 
					$option['TypeID'];
				$customoptions[$option['ID']]['Required'] = 
					$option['Required'];
				
				$prices = sql::run(
					" SELECT * FROM `{shoppingitemoptionprices}`" .
					" WHERE `OptionID` = '".$option['ID']."'" .
					" ORDER BY `OrderID`, `ID`");
				
				while($price = sql::fetch($prices)) {
					if (!in_array($option['TypeID'], $multioptiontypes)) {
						$customoptions[$option['ID']]['Price'][$price['ID']]['MaxCharacters'] =
							$price['MaxCharacters'];
						$customoptions[$option['ID']]['Price'][$price['ID']]['Price'] = 
							$price['Price'];
						$customoptions[$option['ID']]['Price'][$price['ID']]['PriceType'] = 
							$price['PriceType'];
						continue;
					}
					
					$customoptions[$option['ID']]['Prices'][$price['ID']]['OrderID'] = 
						$price['OrderID'];
					$customoptions[$option['ID']]['Prices'][$price['ID']]['Title'] = 
						$price['Title'];
					$customoptions[$option['ID']]['Prices'][$price['ID']]['Price'] = 
						$price['Price'];
					$customoptions[$option['ID']]['Prices'][$price['ID']]['PriceType'] = 
						$price['PriceType'];
				}
			}
		}
		
		if (count($customoptions)) {
			foreach($customoptions as $optionid => $option) {
				$customoptionpriceindex = 0;
				
				if ($optionid > $customoptionindex)
					$customoptionindex = $optionid;
				
				$customoptionshtml .=
					"<table cellpadding='0' cellspacing='0' class='list' id='shoppingitemoption".$optionid."'>" .
					"<thead>" .
					"<tr>" .
						"<th>" .
							"<span class='nowrap'>".
							__("Order").
							"</span>" .
						"</th>" .
						"<th>" .
							"<span class='nowrap" .
								(!$option['Title']?
									" red":
									null) .
								"'>".
							_("Title* (e.g. Color, Size)").
							"</span>" .
						"</th>" .
						"<th>" .
							"<span class='nowrap'>".
							__("Type").
							"</span>" .
						"</th>" .
						"<th>" .
							"<span class='nowrap'>".
							_("Required").
							"</span>" .
						"</th>" .
						"<th>" .
							"<span class='nowrap'>".
							__("Remove").
							"</span>" .
						"</th>" .
					"</tr>" .
					"</thead>" .
					"<tbody>" .
					"<tr>" .
						"<td>" .
							"<input type='text' name='CustomOptions[".$optionid."][OrderID]' " .
								"value='".$option['OrderID']."' " .
								"class='order-id-entry' tabindex='1' />" .
						"</td>" .
						"<td class='auto-width'>" .
							"<input type='text' name='CustomOptions[".$optionid."][Title]' " .
								"class='text-entry' " .
								"value='".htmlspecialchars($option['Title'], ENT_QUOTES)."' style='width: 170px;' />" .
						"</td>" .
						"<td>" .
							"<select name='CustomOptions[".$optionid."][TypeID]' " .
								"class='shopping-item-option-type select-entry'>";
				
				foreach($optiontypes as $optiontype)
					$customoptionshtml .= 
								"<option value='".$optiontype."'" .
									($option['TypeID'] == $optiontype?
										" selected='selected'":
										null) .
									">" .
									_(form::type2Text($optiontype)) .
								"</option>";
				
				$customoptionshtml .=
							"</select>" .
						"</td>" .
						"<td align='center'>" .
							"<input type='checkbox' name='CustomOptions[".$optionid."][Required]' " .
								"class='checkbox-entry' " .
								"value='1'" .
								(isset($option['Required']) && $option['Required']?
									" checked='checked'":
									null) .
								" />" .
						"</td>" .
						"<td align='center'>" .
							"<a class='shopping-item-remove-option remove-link' href='javascript://'></a>" .
						"</td>" .
					"</tr>" .
					"<tr>" .
						"<td valign='top'></td>" .
						"<td colspan='4'>" .
							"<div class='shopping-item-option-prices'" .
								(in_array($option['TypeID'], $multioptiontypes)?
									" style='display: none;'":
									null) .
								">" .
							"<table cellpadding='0' cellspacing='0' class='list'>" .
								"<thead>" .
								"<tr>" .
									"<th>" .
										"<span class='nowrap'>".
										_("Price Difference").
										"</span>" .
									"</th>" .
									"<th>" .
										"<span class='nowrap'>".
										_("Price Type").
										"</span>" .
									"</th>" .
									"<th>" .
										"<span class='nowrap'>".
										_("Max Characters").
										"</span>" .
									"</th>" .
								"</tr>" .
								"</thead>" .
								"<tbody>";
				
				$price = null;
				$priceid = 0;
				
				if (!in_array($option['TypeID'], $multioptiontypes) && isset($option['Price']) && 
					is_array($option['Price']) && count($option['Price'])) 
				{
					$price = current($option['Price']);
					$priceid = key($option['Price']);
				}
				
				$customoptionshtml .=
								"<tr>" .
									"<td class='auto-width'>" .
										"<span class='nowrap'>" .
										(defined('SHOPPING_CART_CURRENCY') &&
										 (!defined('SHOPPING_CART_CURRENCY_POSITION') ||
										 !stristr(SHOPPING_CART_CURRENCY_POSITION, 'right'))?
											"<span class='shopping-currency custom-option-currency'" .
												(isset($price['PriceType']) && $price['PriceType'] != 1?
													" style='display: none;'":
													null) .
												">" .
											SHOPPING_CART_CURRENCY .
											"</span>":
											null) .
										"<input type='text' name='CustomOptions[".$optionid."][Price][".$priceid."][Price]' " .
											"class='text-entry' " .
											"value='".
												(isset($price['Price'])?
													$price['Price']:
													null) .
												"' style='width: 70px;' />" .
										"</span>" .
										(defined('SHOPPING_CART_CURRENCY_POSITION') &&
										 stristr(SHOPPING_CART_CURRENCY_POSITION, 'right')?
											"<span class='shopping-currency custom-option-currency'" .
												(isset($price['PriceType']) && $price['PriceType'] != 1?
													" style='display: none;'":
													null) .
												">" .
											SHOPPING_CART_CURRENCY .
											"</span>":
											null) .
										"<span class='custom-option-percent'" .
											(isset($price['PriceType']) && $price['PriceType'] != 2?
												" style='display: none;'":
												null) .
											">%</span>" .
									"</td>" .
									"<td>" .
										"<select name='CustomOptions[".$optionid."][Price][".$priceid."][PriceType]' " .
											"class='select-entry'>" .
											"<option value='1'" .
												(isset($price['PriceType']) && $price['PriceType'] == 1?
													" selected='selected'":
													null) .
												">" .
												_("Fixed") .
											"</option>" .
											"<option value='2'" .
												(isset($price['PriceType']) && $price['PriceType'] == 2?
													" selected='selected'":
													null) .
												">" .
												_("Percent") .
											"</option>" .
										"</select>" .
									"</td>" .
									"<td align='center'>" .
										"<input type='text' name='CustomOptions[".$optionid."][Price][".$priceid."][MaxCharacters]' " .
											"value='" .
												(isset($price['MaxCharacters']) && $price['MaxCharacters']?
													$price['MaxCharacters']:
													null) .
											"' style='width: 30px;' />" .
									"</td>" .
								"</tr>" .
								"</tbody>" .
							"</table>" .
							"</div>";
				
				$customoptionpriceserror = false;
				$customoptionpricetitleerror = false;			
				$customoptionmultipriceshtml = null;
				
				if (in_array($option['TypeID'], $multioptiontypes) && 
					(!isset($option['Prices']) || !is_array($option['Prices']) || !count($option['Prices'])))
					$customoptionpriceserror = true;
							
				if (in_array($option['TypeID'], $multioptiontypes) && isset($option['Prices']) && 
					is_array($option['Prices']) && count($option['Prices'])) 
				{
					foreach($option['Prices'] as $priceid => $price) {
						if ($priceid > $customoptionpriceindex)
							$customoptionpriceindex = $priceid;
						
						if (!$price['Title'])
							$customoptionpricetitleerror = true;
						
						$customoptionmultipriceshtml .=
									"<tr>" .
										"<td>" .
											"<input type='text' name='CustomOptions[".$optionid."][Prices][".$priceid."][OrderID]' " .
												"value='".$price['OrderID']."' " .
												"class='order-id-entry' tabindex='1' />" .
										"</td>" .
										"<td class='auto-width'>" .
											"<input type='text' name='CustomOptions[".$optionid."][Prices][".$priceid."][Title]' class='text-entry' " .
												"value='".htmlspecialchars($price['Title'], ENT_QUOTES)."' style='width: 100px;' />" .
										"</td>" .
										"<td>" .
											"<span class='nowrap'>" .
											(defined('SHOPPING_CART_CURRENCY') &&
											 (!defined('SHOPPING_CART_CURRENCY_POSITION') ||
											 !stristr(SHOPPING_CART_CURRENCY_POSITION, 'right'))?
												"<span class='shopping-currency custom-option-currency'" .
													(isset($price['PriceType']) && $price['PriceType'] != 1?
														" style='display: none;'":
														null) .
													">" .
													SHOPPING_CART_CURRENCY .
												"</span>":
												null) .
											"<input type='text' name='CustomOptions[".$optionid."][Prices][".$priceid."][Price]' class='text-entry' " .
												"value='".$price['Price']."' style='width: 70px;' />" .
											(defined('SHOPPING_CART_CURRENCY_POSITION') &&
											 stristr(SHOPPING_CART_CURRENCY_POSITION, 'right')?
												"<span class='shopping-currency custom-option-currency'" .
													(isset($price['PriceType']) && $price['PriceType'] != 1?
														" style='display: none;'":
														null) .
													">" .
													SHOPPING_CART_CURRENCY .
												"</span>":
												null) .
											"<span class='custom-option-percent'" .
												(isset($price['PriceType']) && $price['PriceType'] != 2?
													" style='display: none;'":
													null) .
												">%</span>" .
											"</span>" .
										"</td>" .
										"<td>" .
											"<select name='CustomOptions[".$optionid."][Prices][".$priceid."][PriceType]' class='select-entry'>" .
											"<option value='1'" .
												(isset($price['PriceType']) && $price['PriceType'] == 1?
													" selected='selected'":
													null) .
												">" .
												_("Fixed") .
											"</option>" .
											"<option value='2'" .
												(isset($price['PriceType']) && $price['PriceType'] == 2?
													" selected='selected'":
													null) .
												">" .
												_("Percent") .
											"</option>" .
											"</select>" .
										"</td>" .
										"<td align='center'>" .
											"<a class='shopping-item-remove-option-price remove-link' href='javascript://'></a>" .
										"</td>" .
									"</tr>";
					}
				}
				
				$customoptionshtml .=
							"<div class='shopping-item-option-multi-prices'" .
								(!in_array($option['TypeID'], $multioptiontypes)?
									" style='display: none;'":
									null) .
								">" .
							"<table cellpadding='0' cellspacing='0' class='list'>" .
								"<thead>" .
								"<tr>" .
									"<th>" .
										"<span class='nowrap" .
											($customoptionpriceserror?
												" red":
												null) .
											"'>".
										__("Order").
										"</span>" .
									"</th>" .
									"<th>" .
										"<span class='nowrap" .
											($customoptionpricetitleerror || $customoptionpriceserror?
												" red":
												null) .
											"'>".
										_("Title* (e.g. Red, XXL)").
										"</span>" .
									"</th>" .
									"<th>" .
										"<span class='nowrap" .
											($customoptionpriceserror?
												" red":
												null) .
											"'>".
										_("Price Difference").
										"</span>" .
									"</th>" .
									"<th>" .
										"<span class='nowrap" .
											($customoptionpriceserror?
												" red":
												null) .
											"'>".
										_("Price Type").
										"</span>" .
									"</th>" .
									"<th>" .
										"<span class='nowrap" .
											($customoptionpriceserror?
												" red":
												null) .
											"'>".
										__("Remove").
										"</span>" .
									"</th>" .
								"</tr>" .
								"</thead>" .
								"<tbody class='shopping-item-option-prices-container'>" .
								$customoptionmultipriceshtml;
				
				$customoptionpriceshtml .=
					"shoppingItemOptionPricesIndex[".$optionid."] = ".$customoptionpriceindex.";";
				
				$customoptionshtml .=
								"</tbody>" .
							"</table>" .
							"<a href='javascript://' class='shopping-item-add-option-price add-link'>" .
								_("Add new row") .
							"</a>" .
							"</div>" .
						"</td>" .
					"</tr>" .
					"</tbody>" .
					"</table>";
			}
		}
		
		$form->edit(
				'CustomOptions',
				"<script type='text/javascript'>" .
					"var shoppingItemOptionsIndex = ".$customoptionindex.";" .
					"var shoppingItemOptionPricesIndex = new Array();" .
					$customoptionpriceshtml .
				"</script>" .
				"<div class='shopping-item-options-container'>" .
					$customoptionshtml .
				"</div>" .
				"<a href='javascript://' class='shopping-item-add-option add-link'>" .
					_("Add new option") .
				"</a>" .
				"<script type='text/javascript'>" .
				"jQuery(document).ready(function() {" .
					"jQuery.jCore.modules.shopping = {" .
						"admin: {" .
							"itemWatchOptionType: function(typeselect) {" .
								"if (typeselect.value == 3 || typeselect.value == 4 || typeselect.value == 5 || typeselect.value == 15) {" .
									"jQuery(typeselect).parent().parent().parent().find('.shopping-item-option-multi-prices').show();" .
									"jQuery(typeselect).parent().parent().parent().find('.shopping-item-option-prices').hide();" .
								"} else {" .
									"jQuery(typeselect).parent().parent().parent().find('.shopping-item-option-multi-prices').hide();" .
									"jQuery(typeselect).parent().parent().parent().find('.shopping-item-option-prices').show();" .
								"}" .
							"}," .
							"itemAddOptionPrice: function(clickedlink) {" .
								"jlink = jQuery(clickedlink);" .
								"optionIndex = jlink.parent().parent().parent().parent().parent().attr('id').replace(/[^0-9]*/, '');" .
								"shoppingItemOptionPricesIndex[optionIndex]++;" .
								"priceIndex = shoppingItemOptionPricesIndex[optionIndex];" .
								"jlink.parent().find('.shopping-item-option-prices-container').append('" .
									"<tr>" .
										"<td>" .
											"<input type=\'text\' name=\'CustomOptions['+optionIndex+'][Prices]['+priceIndex+'][OrderID]\' " .
												"value=\'\' " .
												"class=\'order-id-entry\' tabindex=\'1\' />" .
										"</td>" .
										"<td class=\'auto-width\'>" .
											"<input type=\'text\' name=\'CustomOptions['+optionIndex+'][Prices]['+priceIndex+'][Title]\' class=\'text-entry\' " .
												"value=\'\' style=\'width: 100px;\' />" .
										"</td>" .
										"<td>" .
											"<span class=\'nowrap\'>" .
											(defined('SHOPPING_CART_CURRENCY') &&
											 (!defined('SHOPPING_CART_CURRENCY_POSITION') ||
											 !stristr(SHOPPING_CART_CURRENCY_POSITION, 'right'))?
												"<span class=\'shopping-currency\'>" .
													SHOPPING_CART_CURRENCY .
												"</span>":
												null) .
											"<input type=\'text\' name=\'CustomOptions['+optionIndex+'][Prices]['+priceIndex+'][Price]\' class=\'text-entry\' " .
												"value=\'\' style=\'width: 70px;\' />" .
											(defined('SHOPPING_CART_CURRENCY_POSITION') &&
											 stristr(SHOPPING_CART_CURRENCY_POSITION, 'right')?
												"<span class=\'shopping-currency\'>" .
													SHOPPING_CART_CURRENCY .
												"</span>":
												null) .
											"</span>" .
										"</td>" .
										"<td>" .
											"<select name=\'CustomOptions['+optionIndex+'][Prices]['+priceIndex+'][PriceType]\' class=\'select-entry\'>" .
												"<option value=\'1\'>"._("Fixed")."</option>" .
												"<option value=\'2\'>"._("Percent")."</option>" .
											"</select>" .
										"</td>" .
										"<td align=\'center\'>" .
											"<a class=\'shopping-item-remove-option-price remove-link\' href=\'javascript://\'></a>" .
										"</td>" .
									"</tr>" .
								"')." .
								"find('.shopping-item-remove-option-price').click(function() {" .
									"jQuery.jCore.modules.shopping.admin.itemRemoveOptionPrice(this);" .
								"});" .
							"}," .
							"itemRemoveOptionPrice: function(clickedlink) {" .
								"jQuery(clickedlink).parent().parent().remove();" .
							"}," .
							"itemRemoveOption: function(clickedlink) {" .
								"jQuery(clickedlink).parent().parent().parent().parent().remove();" .
							"}" .
						"}" .
					"};" .
					"" .
					"jQuery('.shopping-item-option-type').change(function() {" .
						"jQuery.jCore.modules.shopping.admin.itemWatchOptionType(this);" .
					"});" .
					"jQuery('.shopping-item-remove-option-price').click(function() {" .
						"jQuery.jCore.modules.shopping.admin.itemRemoveOptionPrice(this);" .
					"});" .
					"jQuery('.shopping-item-add-option-price').click(function() {" .
						"jQuery.jCore.modules.shopping.admin.itemAddOptionPrice(this);" .
					"});" .
					"jQuery('.shopping-item-remove-option').click(function() {" .
						"jQuery.jCore.modules.shopping.admin.itemRemoveOption(this);" .
					"});" .
					"jQuery('.shopping-item-add-option').click(function() {" .
						"shoppingItemOptionsIndex++;" .
						"shoppingItemOptionPricesIndex[shoppingItemOptionsIndex]=0;" .
						"joption = jQuery('" .
							"<table cellpadding=\'0\' cellspacing=\'0\' class=\'list\' id=\'shoppingitemoption'+shoppingItemOptionsIndex+'\'>" .
							"<thead>" .
							"<tr>" .
								"<th>" .
									"<span class=\'nowrap\'>".
									__("Order").
									"</span>" .
								"</th>" .
								"<th>" .
									"<span class=\'nowrap\'>".
									_("Title* (e.g. Color, Size)").
									"</span>" .
								"</th>" .
								"<th>" .
									"<span class=\'nowrap\'>".
									__("Type").
									"</span>" .
								"</th>" .
								"<th>" .
									"<span class=\'nowrap\'>".
									_("Required").
									"</span>" .
								"</th>" .
								"<th>" .
									"<span class=\'nowrap\'>".
									__("Remove").
									"</span>" .
								"</th>" .
							"</tr>" .
							"</thead>" .
							"<tbody>" .
							"<tr>" .
								"<td>" .
									"<input type=\'text\' name=\'CustomOptions['+shoppingItemOptionsIndex+'][OrderID]\' " .
										"value=\'\' " .
										"class=\'order-id-entry\' tabindex=\'1\' />" .
								"</td>" .
								"<td class=\'auto-width\'>" .
									"<input type=\'text\' name=\'CustomOptions['+shoppingItemOptionsIndex+'][Title]\' class=\'text-entry\' " .
										"value=\'\' style=\'width: 170px;\' />" .
								"</td>" .
								"<td>" .
									"<select name=\'CustomOptions['+shoppingItemOptionsIndex+'][TypeID]\' class=\'shopping-item-option-type select-entry\'>" .
										"<option value=\'1\'>".form::type2Text(1)."</option>" .
										"<option value=\'6\'>".form::type2Text(6)."</option>" .
										"<option value=\'3\'>".form::type2Text(3)."</option>" .
										"<option value=\'4\'>".form::type2Text(4)."</option>" .
										"<option value=\'5\' selected=\'selected\'>".form::type2Text(5)."</option>" .
										"<option value=\'15\'>".form::type2Text(15)."</option>" .
										"<option value=\'17\'>".form::type2Text(17)."</option>" .
										"<option value=\'30\'>".form::type2Text(30)."</option>" .
										"<option value=\'16\'>".form::type2Text(16)."</option>" .
										"<option value=\'24\'>".form::type2Text(24)."</option>" .
										"<option value=\'26\'>".form::type2Text(26)."</option>" .
										"<option value=\'28\'>".form::type2Text(28)."</option>" .
										"<option value=\'29\'>".form::type2Text(29)."</option>" .
									"</select>" .
								"</td>" .
								"<td align=\'center\'>" .
									"<input type=\'checkbox\' name=\'CustomOptions['+shoppingItemOptionsIndex+'][Required]\' class=\'checkbox-entry\' " .
										"value=\'1\' />" .
								"</td>" .
								"<td align=\'center\'>" .
									"<a class=\'shopping-item-remove-option remove-link\' href=\'javascript://\'></a>" .
								"</td>" .
							"</tr>" .
							"<tr>" .
								"<td valign=\'top\'></td>" .
								"<td colspan=\'4\'>" .
									"<div class=\'shopping-item-option-prices\' style=\'display: none;\'>" .
									"<table cellpadding=\'0\' cellspacing=\'0\' class=\'list\'>" .
										"<thead>" .
										"<tr>" .
											"<th>" .
												"<span class=\'nowrap\'>".
												_("Price Difference").
												"</span>" .
											"</th>" .
											"<th>" .
												"<span class=\'nowrap\'>".
												_("Price Type").
												"</span>" .
											"</th>" .
											"<th>" .
												"<span class=\'nowrap\'>".
												_("Max Characters").
												"</span>" .
											"</th>" .
										"</tr>" .
										"</thead>" .
										"<tbody>" .
										"<tr>" .
											"<td class=\'auto-width\'>" .
												"<span class=\'nowrap\'>" .
												(defined('SHOPPING_CART_CURRENCY') &&
												 (!defined('SHOPPING_CART_CURRENCY_POSITION') ||
												 !stristr(SHOPPING_CART_CURRENCY_POSITION, 'right'))?
													"<span class=\'shopping-currency\'>" .
														SHOPPING_CART_CURRENCY .
													"</span>":
													null) .
												"<input type=\'text\' name=\'CustomOptions['+shoppingItemOptionsIndex+'][Price][0][Price]\' class=\'text-entry\' " .
													"value=\'\' style=\'width: 70px;\' />" .
												(defined('SHOPPING_CART_CURRENCY_POSITION') &&
												 stristr(SHOPPING_CART_CURRENCY_POSITION, 'right')?
													"<span class=\'shopping-currency\'>" .
														SHOPPING_CART_CURRENCY .
													"</span>":
													null) .
												"</span>" .
											"</td>" .
											"<td>" .
												"<select name=\'CustomOptions['+shoppingItemOptionsIndex+'][Price][0][PriceType]\' class=\'select-entry\'>" .
													"<option value=\'1\'>"._("Fixed")."</option>" .
													"<option value=\'2\'>"._("Percent")."</option>" .
												"</select>" .
											"</td>" .
											"<td align=\'center\'>" .
												"<input type=\'text\' name=\'CustomOptions['+shoppingItemOptionsIndex+'][Price][0][MaxCharacters]\' " .
													"value=\'\' style=\'width: 30px;\' />" .
											"</td>" .
										"</tr>" .
										"</tbody>" .
									"</table>" .
									"</div>" .
									"<div class=\'shopping-item-option-multi-prices\'>" .
									"<table cellpadding=\'0\' cellspacing=\'0\' class=\'list\'>" .
										"<thead>" .
										"<tr>" .
											"<th>" .
												"<span class=\'nowrap\'>".
												__("Order").
												"</span>" .
											"</th>" .
											"<th>" .
												"<span class=\'nowrap\'>".
												_("Title* (e.g. Red, XXL)").
												"</span>" .
											"</th>" .
											"<th>" .
												"<span class=\'nowrap\'>".
												_("Price Difference").
												"</span>" .
											"</th>" .
											"<th>" .
												"<span class=\'nowrap\'>".
												_("Price Type").
												"</span>" .
											"</th>" .
											"<th>" .
												"<span class=\'nowrap\'>".
												__("Remove").
												"</span>" .
											"</th>" .
										"</tr>" .
										"</thead>" .
										"<tbody class=\'shopping-item-option-prices-container\'>" .
										"</tbody>" .
									"</table>" .
									"<a href=\'javascript://\' class=\'shopping-item-add-option-price add-link\'>" .
										_("Add new row") .
									"</a>" .
									"</div>" .
								"</td>" .
							"</tr>" .
							"</tbody>" .
							"</table>');" .
							"joption.find('.shopping-item-remove-option').click(function() {" .
								"jQuery.jCore.modules.shopping.admin.itemRemoveOption(this);" .
							"});" .
							"joption.find('.shopping-item-option-type').change(function() {" .
								"jQuery.jCore.modules.shopping.admin.itemWatchOptionType(this);" .
							"});" .
							"joption.find('.shopping-item-add-option-price').click(function() {" .
								"jQuery.jCore.modules.shopping.admin.itemAddOptionPrice(this);" .
							"});" .
							"jQuery(this).parent().find('.shopping-item-options-container').append(joption);" .
						"});" .
				"});" .
				"</script>",
			'CustomOptions',
			FORM_STATIC_TEXT);
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
		
		if ($reorder) {
			if (!$orders)
				return false;
			
			foreach($orders as $oid => $ovalue) {
				sql::run("UPDATE `{shoppingitems}` " .
					" SET `OrderID` = '".(int)$ovalue."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".(int)$oid."'");
			}
			
			tooltip::display(
				_("Items have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				_("Item has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if (!$form->get('Path'))
			$form->set('Path', url::genPathFromString($form->get('Title')));
			
		if (!$form->get('RefNumber'))
			$form->set('RefNumber', strtoupper(substr(str_replace(
				'-', '', $form->get('Path')), 0, 7) .
				'-'.security::randomChars()));
			
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray()))
				return false;
				
			tooltip::display(
				_("Item has been successfully updated.")." " .
				(modules::getOwnerURL('shopping')?
					"<a href='".shopping::getURL().
						"&amp;shoppingid=".admin::getPathID()."&amp;shoppingitemid=".$id."' target='_blank'>" .
						_("View Item") .
					"</a>" .
					" - ":
					null) .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
					
		if (!$newid = $this->add($form->getPostArray()))
			return false;
			
		tooltip::display(
			_("Item has been successfully created.")." " .
			(modules::getOwnerURL('shopping')?
				"<a href='".shopping::getURL().
					"&amp;shoppingid=".admin::getPathID()."&amp;shoppingitemid=".$newid."' target='_blank'>" .
					_("View Item") .
				"</a>" .
				" - ":
				null) .
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
				__("Title / Created on")."</span></th>";
	}
	
	function displayAdminListHeaderOptions($digitalgoods = false) {
		echo
			"<th><span class='nowrap'>".
				__("Comments")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Attachments")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Pictures")."</span></th>";
					
		if ($digitalgoods)
			echo
				"<th><span class='nowrap'>".
					_("Goods")."</span></th>";
	}
	
	function displayAdminListHeaderFunctions() {
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
		$user = $GLOBALS['USER']->get($row['UserID']);
		
		echo
			"<td>" .
				"<input type='text' name='orders[".$row['ID']."]' " .
					"value='".$row['OrderID']."' " .
					"class='order-id-entry' tabindex='1' />" .
			"</td>" .
			"<td class='auto-width' " .
				($row['Deactivated']?
					"style='text-decoration: line-through;' ":
					null).
				">" .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."' " .
				" class='bold'>" .
					$row['Title'] .
				"</a>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					calendar::dateTime($row['TimeStamp'])." " .
					($user?
						$GLOBALS['USER']->constructUserName($user, __('by %s')):
						null) .
					", ".sprintf(__("%s views"), $row['Views']) .
				"</div>" .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row, $digitalgoods = false) {
		echo
			"<td align='center'>" .
				"<a class='admin-link comments' " .
					"title='".htmlspecialchars(__("Comments"), ENT_QUOTES).
						" (".$row['Comments'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/shoppingitemcomments'>" .
					(ADMIN_ITEMS_COUNTER_ENABLED && $row['Comments']?
						"<span class='counter'>" .
							"<span>" .
								"<span>" .
								$row['Comments']."" .
								"</span>" .
							"</span>" .
						"</span>":
						null) .
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link attachments' " .
					"title='".htmlspecialchars(__("Attachments"), ENT_QUOTES) .
						" (".$row['Attachments'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/shoppingitemattachments'>" .
					(ADMIN_ITEMS_COUNTER_ENABLED && $row['Attachments']?
						"<span class='counter'>" .
							"<span>" .
								"<span>" .
								$row['Attachments']."" .
								"</span>" .
							"</span>" .
						"</span>":
						null) .
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link pictures' " .
					"title='".htmlspecialchars(__("Pictures"), ENT_QUOTES) .
						" (".$row['Pictures'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/shoppingitempictures'>" .
					(ADMIN_ITEMS_COUNTER_ENABLED && $row['Pictures']?
						"<span class='counter'>" .
							"<span>" .
								"<span>" .
								$row['Pictures']."" .
								"</span>" .
							"</span>" .
						"</span>":
						null) .
				"</a>" .
			"</td>";
			
		if ($digitalgoods)
			echo
				"<td align='center'>" .
					"<a class='admin-link digital-goods' " .
						"title='".htmlspecialchars(_("Digital Goods"), ENT_QUOTES) .
							" (".$row['DigitalGoods'].")' " .
						"href='".url::uri('ALL') .
						"?path=".admin::path()."/".$row['ID']."/shoppingitemdigitalgoods'>" .
						(ADMIN_ITEMS_COUNTER_ENABLED && $row['DigitalGoods']?
							"<span class='counter'>" .
								"<span>" .
									"<span>" .
									$row['DigitalGoods']."" .
									"</span>" .
								"</span>" .
							"</span>":
							null) .
					"</a>" .
				"</td>";
	}
	
	function displayAdminListItemFunctions(&$row) {
		echo
			"<td>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayAdminListItemSelected(&$row) {
		admin::displayItemData(
			_("Price"),
			shopping::constructPrice($row['Price']));
		
		if (JCORE_VERSION >= '0.7') {
			if ($row['SpecialPrice'] != '')
				admin::displayItemData(
					_("Special Price"),
					shopping::constructPrice($row['SpecialPrice']));
			
			if ($row['SpecialPriceStartDate'])
				admin::displayItemData(
					_("Special Price Starts"),
					calendar::date($row['SpecialPriceStartDate']));
				
			if ($row['SpecialPriceEndDate'])
				admin::displayItemData(
					_("Special Price Ends"),
					calendar::date($row['SpecialPriceEndDate']));
		}
		
		admin::displayItemData(
			_("Ref. Number"), 
			$row['RefNumber']);
		
		if (JCORE_VERSION >= '0.7' && $row['Weight'])
			admin::displayItemData(
				_("Weight"),
				$row['Weight'].
				(defined('SHOPPING_CART_WEIGHT_UNIT') && SHOPPING_CART_WEIGHT_UNIT?
					"<span class='shopping-weight-unit'> ".
						SHOPPING_CART_WEIGHT_UNIT .
					"</span>":
					null));
		
		if (JCORE_VERSION >= '0.7' && $row['Taxable']) 
			admin::displayItemData(
				_("Taxable"),
				__("Yes"));
		
		if (JCORE_VERSION >= '0.7' && $row['Options']) {
			$options = sql::run(
				" SELECT * FROM `{shoppingitemoptions}`" .
				" WHERE `ShoppingItemID` = '".$row['ID']."'" .
				" ORDER BY `OrderID`, `ID`");
			
			$customoptions = null;
			while($option = sql::fetch($options)) {
				$customoptions .=
					$option['Title'] .
					($option['Required']?
						"*":
						null).
					":<ul>";
				
				$prices = sql::run(
					" SELECT * FROM `{shoppingitemoptionprices}`" .
					" WHERE `OptionID` = '".$option['ID']."'" .
					" ORDER BY `OrderID`, `ID`");
				
				while($price = sql::fetch($prices)) {
					$customoptions .=
						"<li>" .
							$price['Title']." = " .
							($price['PriceType'] == 2?
								$price['Price'] ."%":
								shopping::constructPrice($price['Price']));
						"</li>";
				}
				
				$customoptions .=
					"</ul>";
			}
			
			if ($customoptions)
				admin::displayItemData(
					_("Custom Options"),
					"<div style='float: left;'>" .
						$customoptions.
					"</div>");
		}
		
		if ($row['AvailableQuantity'] != null) 
			admin::displayItemData(
				"<span" .
					(!$row['AvailableQuantity']?
						" class='red'":
						null) .
					">"._("Available Quantity")."</span>",
				$row['AvailableQuantity']);
		
		if ($row['MaxQuantityAtOnce']) 
			admin::displayItemData(
				_("Max Order Quantity at Once"),
				$row['MaxQuantityAtOnce']);
		
		if ($row['ShowQuantityPicker']) 
			admin::displayItemData(
				_("Show Quantity Picker"),
				__("Yes"));
		
		if ($row['Keywords'])
			admin::displayItemData(
				__("Keywords"), 
				$row['Keywords']);
		 
		if ($row['EnableRating'])
			admin::displayItemData(
				__("Enable Rating"),
				__("Yes") .
				($row['EnableGuestRating']?
					" ".__("(Guests can rate too!)"):
					null));
		
		if ($row['EnableComments'])
			admin::displayItemData(
				__("Enable Comments"),
				__("Yes") .
				($row['EnableGuestComments']?
					" ".__("(Guests can comment too!)"):
					null));
		
		if (isset($row['DisplayRelatedItems']) && $row['DisplayRelatedItems'])
			admin::displayItemData(
				_("Display Related Items"),
				__("Yes"));
		
		admin::displayItemData(
			__("Path"),
			$row['Path']);
		
		admin::displayItemData(
			"<hr />");
		admin::displayItemData(
			str_replace(
				'<div style="page-break-after: always',
				'<div class="page-break" style="page-break-after: always',
			$row['Description']));
		
		if (JCORE_VERSION >= '0.7')
			$this->displayAdminCustomFields($row);
	}
	
	function displayAdminListFunctions() {
		echo
			"<input type='submit' name='reordersubmit' value='" .
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminListSearch() {
		$search = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		echo 
			"<input type='hidden' name='path' value='".admin::path()."' />" .
			"<input type='search' name='search' value='".
				htmlspecialchars($search, ENT_QUOTES).
				"' results='5' placeholder='".htmlspecialchars(__("search..."), ENT_QUOTES)."' /> " .
			"<input type='submit' value='" .
				htmlspecialchars(__("Search"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminList($rows, $digitalgoods = false) {
		$id = null;
		$outofstockitems = false;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		echo
			"<form action='".
				url::uri('edit, delete').
				"' method='post'>";
			
		echo "<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>";
		
		$this->displayAdminListHeader();
		$this->displayAdminListHeaderOptions($digitalgoods);
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			$this->displayAdminListHeaderFunctions();
		
		echo
				"</tr>" .
				"</thead>" .
				"<tbody>";
				
		$i = 0;		
		while($row = sql::fetch($rows)) {
			if ($row['AvailableQuantity'] != null && 
				!$row['AvailableQuantity'])
				$outofstockitems = true;
			
			echo 
				"<tr".($i%2?" class='pair'":NULL).">";
			
			$this->displayAdminListItem($row);
			$this->displayAdminListItemOptions($row, $digitalgoods);
			
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
			"</table>";
		
		echo "<br />";
		
		if (!$outofstockitems && $this->userPermissionType == USER_PERMISSION_TYPE_WRITE) {
			$this->displayAdminListFunctions();
			
			echo
				"<div class='clear-both'></div>" .
				"<br />";
		}
		
		echo
			"</form>";
	}
	
	function displayAdminCustomFields(&$row) {
		$itemsform = new shoppingItemsForm();
		$itemsform->load(false);
		
		$itemsform->displayData($row, array(
			'Title', 'Description', 'Price', 'SpecialPrice',
			'SpecialPriceStartDate', 'SpecialPriceEndDate',
			'CustomOptions', 'RefNumber', 'Weight', 'Taxable',
			'Quantity', 'MaxQuantityAtOnce', 'ShowQuantityPicker',
			'TimeStamp', 'Path', 'Keywords', 'EnableRating', 
			'EnableGuestRating', 'EnableComments', 'EnableGuestComments', 
			'DisplayRelatedItems', 'Deactivated', 'OrderID'));
		
		unset($itemsform);
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}

	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			_('Shopping Items'), 
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$search = null;
		$edit = null;
		$id = null;
		$digitalgoods = false;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($edit && isset($_POST['InsertAsNew']) && $_POST['InsertAsNew']) {
			$_GET['edit'] = null;
			$_GET['id'] = null;
			
			$edit = false;
			$id = null;
		}
		
		$selectedowner = sql::fetch(sql::run(
			" SELECT * FROM `{shoppings}` " .
			" WHERE `ID` = '".admin::getPathID()."'"));
			
		if (isset($selectedowner['DigitalGoods']))
			$digitalgoods = $selectedowner['DigitalGoods'];
		
		echo
			"<div style='float: right;'>" .
				"<form action='".url::uri('ALL')."' method='get'>";
		
		$this->displayAdminListSearch();
		
		echo
				"</form>" .
			"</div>";
		
		$this->displayAdminTitle($selectedowner['Title']);
		$this->displayAdminDescription();
			
		echo
			"<div class='admin-content'>";
				
		$form = new form(
				($edit?
					_("Edit Item"):
					_("New Item")),
				'newedititem');
		
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
	
		$paging = new paging(10);
		$paging->ignoreArgs = 'id, edit, delete';
		
		$outofstockrows = sql::run(
				" SELECT * FROM `{shoppingitems}`" .
				" WHERE `ShoppingID` = '".admin::getPathID()."'" .
				" AND `AvailableQuantity` IS NOT NULL " .
				" AND !`AvailableQuantity`" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				($search?
					" AND (`Title` LIKE '%".sql::escape($search)."%' " .
					" 	OR `Keywords` LIKE '%".sql::escape($search)."%') ":
					null) .
				" ORDER BY `OrderID`, `ID` DESC");
				
		if (sql::rows($outofstockrows) && !$paging->getStart()) {
			echo 
				"<p>" .
					"<b class='red'>".
						_("Out of Stock Items").
					"</b>" .
				"</p>";
			
			$this->displayAdminList($outofstockrows, $digitalgoods);
			
			echo 
				"<div class='separator'></div>" .
				"<p>" .
					"<b>".
						_("Items in Stock").
					"</b>" .
				"</p>";
		}
		
		$rows = sql::run(
				" SELECT * FROM `{shoppingitems}`" .
				" WHERE `ShoppingID` = '".admin::getPathID()."'" .
				" AND (`AvailableQuantity` IS NULL " .
					" OR `AvailableQuantity`)" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				($search?
					" AND (`Title` LIKE '%".sql::escape($search)."%' " .
					" 	OR `Keywords` LIKE '%".sql::escape($search)."%') ":
					null) .
				" ORDER BY `OrderID`, `ID` DESC" .
				" LIMIT ".$paging->limit);
		
		$paging->setTotalItems(sql::count());
		
		if ($paging->items)
			$this->displayAdminList($rows, $digitalgoods);
		else
			tooltip::display(
				_("No items found for this category."),
				TOOLTIP_NOTIFICATION);
		
		$paging->display();
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{shoppingitems}` " .
					" WHERE `ID` = '".$id."'"));
				
				if (JCORE_VERSION >= '0.7')
					$this->setupAdminFormOptions($form, $row);
					
				$form->setValues($row);
				
			} elseif (JCORE_VERSION >= '0.7') {
				$this->setupAdminFormOptions($form);	
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
		
		$multioptiontypes = array(
			FORM_INPUT_TYPE_CHECKBOX,
			FORM_INPUT_TYPE_RADIO,
			FORM_INPUT_TYPE_SELECT,
			FORM_INPUT_TYPE_MULTISELECT);
		
		if (JCORE_VERSION >= '0.7' && isset($values['CustomOptions']) &&
			is_array($values['CustomOptions']) && count($values['CustomOptions'])) 
		{
			foreach($values['CustomOptions'] as $option) {
				if (!$option['Title']) {
					tooltip::display(
						_("Custom option title is required!"),
						TOOLTIP_ERROR);
					return false;
				}
				
				if (!in_array($option['TypeID'], $multioptiontypes) &&
					(!isset($option['Price']) || !$option['Price'] || !count($option['Price']))) 
				{
					tooltip::display(
						sprintf(_("No custom option price defined for \"%s\"!"),
							$option['Title']),
						TOOLTIP_ERROR);
					return false;
				}
				
				if (!in_array($option['TypeID'], $multioptiontypes))
					continue;
				
				if (!isset($option['Prices']) || !$option['Prices'] || !count($option['Prices'])) {
					tooltip::display(
						sprintf(_("No custom option price defined for \"%s\"!"),
							$option['Title']),
						TOOLTIP_ERROR);
					return false;
				}
				
				foreach($option['Prices'] as $priceid => $price) {
					if (!$price['Title']) {
						tooltip::display(
							sprintf(_("Custom option price title for \"%s\" is required!"),
								$option['Title']),
							TOOLTIP_ERROR);
						return false;
					}
				}
			}
		}
		
		if ($values['OrderID'] == '') {
			sql::run(
				" UPDATE `{shoppingitems}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ShoppingID` = '".(int)$values['ShoppingID']."'");
			
			$values['OrderID'] = 1;
			
		} else {
			sql::run(
				" UPDATE `{shoppingitems}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ShoppingID` = '".(int)$values['ShoppingID']."'" .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		if (JCORE_VERSION >= '0.7') {
			if (!isset($values['UserID']))
				$values['UserID'] = (int)$GLOBALS['USER']->data['ID'];
			
			$itemsform = new shoppingItemsForm();
			$newid = $itemsform->addData($values);
			unset($itemsform);
		
		} else {
			$newid = sql::run(
				" INSERT INTO `{shoppingitems}` SET ".
				" `ShoppingID` = '".
					(int)$values['ShoppingID']."'," .
				" `Title` = '".
					sql::escape($values['Title'])."'," .
				" `Description` = '".
					sql::escape($values['Description'])."'," .
				" `Path` = '".
					sql::escape($values['Path'])."'," .
				" `RefNumber` = '".
					sql::escape($values['RefNumber'])."'," .
				" `Price` = '".
					sql::escape($values['Price'])."'," .
				" `AvailableQuantity` = ".
					(!is_null($values['AvailableQuantity'])?
						"'".(int)$values['AvailableQuantity']."'":
						"NULL") .
					"," .
				" `MaxQuantityAtOnce` = ".
					($values['MaxQuantityAtOnce']?
						"'".(int)$values['MaxQuantityAtOnce']."'":
						30).
					"," .
				" `ShowQuantityPicker` = '".
					(int)$values['ShowQuantityPicker']."'," .
				" `Keywords` = '".
					sql::escape($values['Keywords'])."'," .
				" `TimeStamp` = " .
					($values['TimeStamp']?
						"'".sql::escape($values['TimeStamp'])."'":
						"NOW()").
					"," .
				" `Deactivated` = '".
					(int)$values['Deactivated']."'," .
				" `EnableRating` = '".
					(int)$values['EnableRating']."'," .
				" `EnableGuestRating` = '".
					(int)$values['EnableGuestRating']."'," .
				" `EnableComments` = '".
					(int)$values['EnableComments']."'," .
				" `EnableGuestComments` = '".
					(int)$values['EnableGuestComments']."'," .
				" `UserID` = '".
					(int)$GLOBALS['USER']->data['ID']."'," .
				" `OrderID` = '".
					(int)$values['OrderID']."'");
		}
		
		if (!$newid) {
			tooltip::display(
				sprintf(_("Item couldn't be added! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (JCORE_VERSION >= '0.7' && isset($values['CustomOptions']) &&
			is_array($values['CustomOptions']) && count($values['CustomOptions'])) 
		{
			foreach ($values['CustomOptions'] as $option) {
				if (!isset($option['OrderID']))
					$option['OrderID'] = 0;
				
				$newoptionid = sql::run(
					" INSERT INTO `{shoppingitemoptions}` SET" .
					" `ShoppingItemID` = '".(int)$newid."'," .
					" `Title` = '".sql::escape($option['Title'])."'," .
					" `TypeID` = '".(int)$option['TypeID']."'," .
					" `Required` = '".
						(isset($option['Required'])?
							(int)$option['Required']:
							0) .
						"'," .
					" `OrderID` = '".(int)$option['OrderID']."'");
				
				if (!$newoptionid)
					tooltip::display(
						sprintf(_("Custom option couldn't be created! Error: %s"), 
							sql::error()),
						TOOLTIP_ERROR);
				
				if (!in_array($option['TypeID'], $multioptiontypes)) {
					$newpriceid = sql::run(
						" INSERT INTO `{shoppingitemoptionprices}` SET" .
						" `OptionID` = '".(int)$newoptionid."'," .
						" `MaxCharacters` = '".
							(isset($option['Price'][0]['MaxCharacters'])?
								(int)$option['Price'][0]['MaxCharacters']:
								0)."'," .
						" `Price` = '".sql::escape($option['Price'][0]['Price'])."'," .
						" `PriceType` = '".(int)$option['Price'][0]['PriceType']."'");
					
					if (!$newpriceid)
						tooltip::display(
							sprintf(_("Custom option price couldn't be created! Error: %s"), 
								sql::error()),
							TOOLTIP_ERROR);
				
					continue;
				}
			
				foreach($option['Prices'] as $price) {
					if (!isset($price['OrderID']))
						$price['OrderID'] = 0;
					
					$newpriceid = sql::run(
						" INSERT INTO `{shoppingitemoptionprices}` SET" .
						" `OptionID` = '".(int)$newoptionid."'," .
						" `Title` = '".sql::escape($price['Title'])."'," .
						" `Price` = '".sql::escape($price['Price'])."'," .
						" `PriceType` = '".(int)$price['PriceType']."'," .
						" `OrderID` = '".(int)$price['OrderID']."'");
					
					if (!$newpriceid)
						tooltip::display(
							sprintf(_("Custom option price couldn't be created! Error: %s"), 
								sql::error()),
							TOOLTIP_ERROR);
				}
			}
		
			$options = sql::fetch(sql::run(
				" SELECT COUNT(*) AS `Rows` FROM `{shoppingitemoptions}`" .
				" WHERE `ShoppingItemID` = '".(int)$newid."'" .
				" LIMIT 1"));
			
			sql::run(
				" UPDATE `{shoppingitems}` SET " .
				" `Options` = '".(int)$options['Rows']."'," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".$newid."'");
		}
		
		sql::run(
			" UPDATE `{shoppings}` SET " .
			" `Items` = `Items` + 1," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".(int)$values['ShoppingID']."'");
		
		if (JCORE_VERSION >= '0.5')
			$this->updateKeywordsCloud($values['Keywords']);
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		$multioptiontypes = array(
			FORM_INPUT_TYPE_CHECKBOX,
			FORM_INPUT_TYPE_RADIO,
			FORM_INPUT_TYPE_SELECT,
			FORM_INPUT_TYPE_MULTISELECT);
		
		$values['Options'] = 0;
				
		if (JCORE_VERSION >= '0.7' && isset($values['CustomOptions']) &&
			is_array($values['CustomOptions']) && count($values['CustomOptions'])) 
		{
			foreach($values['CustomOptions'] as $option) {
				if (!$option['Title']) {
					tooltip::display(
						_("Custom option title is required!"),
						TOOLTIP_ERROR);
					return false;
				}
				
				if (!in_array($option['TypeID'], $multioptiontypes) &&
					(!isset($option['Price']) || !$option['Price'] || !count($option['Price']))) 
				{
					tooltip::display(
						sprintf(_("No custom option price defined for \"%s\"!"),
							$option['Title']),
						TOOLTIP_ERROR);
					return false;
				}
				
				if (!in_array($option['TypeID'], $multioptiontypes))
					continue;
				
				if (!isset($option['Prices']) || !$option['Prices'] || !count($option['Prices'])) {
					tooltip::display(
						sprintf(_("No custom option price defined for \"%s\"!"),
							$option['Title']),
						TOOLTIP_ERROR);
					return false;
				}
				
				foreach($option['Prices'] as $priceid => $price) {
					if (!$price['Title']) {
						tooltip::display(
							sprintf(_("Custom option price title for \"%s\" is required!"),
								$option['Title']),
							TOOLTIP_ERROR);
						return false;
					}
				}
			}
			
			$options = sql::run(
				" SELECT * FROM `{shoppingitemoptions}`" .
				" WHERE `ShoppingItemID` = '".(int)$id."'");
			
			while($option = sql::fetch($options)) {
				if (!isset($values['CustomOptions'][$option['ID']])) {
					sql::run(
						" DELETE FROM `{shoppingitemoptionprices}`" .
						" WHERE `OptionID` = '".$option['ID']."'");
					sql::run(
						" DELETE FROM `{shoppingitemoptions}`" .
						" WHERE `ID` = '".$option['ID']."'");
					continue;
				}
				
				if (!isset($values['CustomOptions'][$option['ID']]['OrderID']))
					$values['CustomOptions'][$option['ID']]['OrderID'] = 0;
				
				sql::run(
					" UPDATE `{shoppingitemoptions}` SET" .
					" `Title` = '".sql::escape($values['CustomOptions'][$option['ID']]['Title'])."'," .
					" `TypeID` = '".(int)$values['CustomOptions'][$option['ID']]['TypeID']."'," .
					" `Required` = '".
						(isset($values['CustomOptions'][$option['ID']]['Required'])?
							(int)$values['CustomOptions'][$option['ID']]['Required']:
							0) .
						"'," .
					" `OrderID` = '".(int)$values['CustomOptions'][$option['ID']]['OrderID']."'" .
					" WHERE `ID` = '".$option['ID']."'");
				
				if (sql::affected() == -1) {
					tooltip::display(
						sprintf(_("Custom option couldn't be updated! Error: %s"), 
							sql::error()),
						TOOLTIP_ERROR);
					return false;
				}
				
				if ((!in_array($option['TypeID'], $multioptiontypes) &&
					in_array($values['CustomOptions'][$option['ID']]['TypeID'], $multioptiontypes)) ||
					(in_array($option['TypeID'], $multioptiontypes) &&
					!in_array($values['CustomOptions'][$option['ID']]['TypeID'], $multioptiontypes))) 
				{
					sql::run(
						" DELETE FROM `{shoppingitemoptionprices}`" .
						" WHERE `OptionID` = '".$option['ID']."'");
				}
				
				if (!in_array($values['CustomOptions'][$option['ID']]['TypeID'], $multioptiontypes)) {
					$price = sql::fetch(sql::run(
						" SELECT * FROM `{shoppingitemoptionprices}`" .
						" WHERE `OptionID` = '".$option['ID']."'" .
						" ORDER BY `OrderID`, `ID`"));
					
					if (!isset($values['CustomOptions'][$option['ID']]['Price'][$price['ID']])) {
						sql::run(
							" DELETE FROM `{shoppingitemoptionprices}`" .
							" WHERE `OptionID` = '".$option['ID']."'");
						
						$priceid = key($values['CustomOptions'][$option['ID']]['Price']);
						$newpriceid = sql::run(
							" INSERT INTO `{shoppingitemoptionprices}` SET" .
							" `OptionID` = '".(int)$option['ID']."'," .
							" `MaxCharacters` = '".
								(isset($values['CustomOptions'][$option['ID']]['Price'][$priceid]['MaxCharacters'])?
									(int)$values['CustomOptions'][$option['ID']]['Price'][$priceid]['MaxCharacters']:
									0)."'," .
							" `Price` = '".sql::escape($values['CustomOptions'][$option['ID']]['Price'][$priceid]['Price'])."'," .
							" `PriceType` = '".(int)$values['CustomOptions'][$option['ID']]['Price'][$priceid]['PriceType']."'");
						
						if (!$newpriceid) {
							tooltip::display(
								sprintf(_("Custom option price couldn't be created! Error: %s"), 
									sql::error()),
								TOOLTIP_ERROR);
							return false;
						}
						
					} else {
						sql::run(
							" UPDATE `{shoppingitemoptionprices}` SET" .
							" `MaxCharacters` = '".
								(isset($values['CustomOptions'][$option['ID']]['Price'][$price['ID']]['MaxCharacters'])?
									(int)$values['CustomOptions'][$option['ID']]['Price'][$price['ID']]['MaxCharacters']:
									0)."'," .
							" `Price` = '".sql::escape($values['CustomOptions'][$option['ID']]['Price'][$price['ID']]['Price'])."'," .
							" `PriceType` = '".(int)$values['CustomOptions'][$option['ID']]['Price'][$price['ID']]['PriceType']."'" .
							" WHERE `ID` = '".$price['ID']."'");
							
						if (sql::affected() == -1) {
							tooltip::display(
								sprintf(_("Custom option price couldn't be updated! Error: %s"), 
									sql::error()),
								TOOLTIP_ERROR);
							return false;
						}
					}
					
					unset($values['CustomOptions'][$option['ID']]);
					continue;
				}
				
				$prices = sql::run(
					" SELECT * FROM `{shoppingitemoptionprices}`" .
					" WHERE `OptionID` = '".$option['ID']."'" .
					" ORDER BY `OrderID`, `ID`");
				
				while($price = sql::fetch($prices)) {
					if (!isset($values['CustomOptions'][$option['ID']]['Prices'][$price['ID']])) {
						sql::run(
							" DELETE FROM `{shoppingitemoptionprices}`" .
							" WHERE `ID` = '".$price['ID']."'");
						continue;
					}
					
					if (!isset($values['CustomOptions'][$option['ID']]['Prices'][$price['ID']]['OrderID']))
						$values['CustomOptions'][$option['ID']]['Prices'][$price['ID']]['OrderID'] = 0;
					
					sql::run(
						" UPDATE `{shoppingitemoptionprices}` SET" .
						" `Title` = '".sql::escape($values['CustomOptions'][$option['ID']]['Prices'][$price['ID']]['Title'])."'," .
						" `Price` = '".sql::escape($values['CustomOptions'][$option['ID']]['Prices'][$price['ID']]['Price'])."'," .
						" `PriceType` = '".(int)$values['CustomOptions'][$option['ID']]['Prices'][$price['ID']]['PriceType']."'," .
						" `OrderID` = '".(int)$values['CustomOptions'][$option['ID']]['Prices'][$price['ID']]['OrderID']."'" .
						" WHERE `ID` = '".$price['ID']."'");
					
					if (sql::affected() == -1) {
						tooltip::display(
							sprintf(_("Custom option price couldn't be updated! Error: %s"), 
								sql::error()),
							TOOLTIP_ERROR);
						return false;
					}
	
					unset($values['CustomOptions'][$option['ID']]['Prices'][$price['ID']]);
				}
				
				if (count($values['CustomOptions'][$option['ID']]['Prices'])) {
					foreach ($values['CustomOptions'][$option['ID']]['Prices'] as $price) {
						if (!isset($price['OrderID']))
							$price['OrderID'] = 0;
						
						$newpriceid = sql::run(
							" INSERT INTO `{shoppingitemoptionprices}` SET" .
							" `OptionID` = '".(int)$option['ID']."'," .
							" `Title` = '".sql::escape($price['Title'])."'," .
							" `Price` = '".sql::escape($price['Price'])."'," .
							" `PriceType` = '".(int)$price['PriceType']."'," .
							" `OrderID` = '".(int)$price['OrderID']."'");
						
						if (!$newpriceid) {
							tooltip::display(
								sprintf(_("Custom option price couldn't be created! Error: %s"), 
									sql::error()),
								TOOLTIP_ERROR);
							return false;
						}
					}
				}
				
				unset($values['CustomOptions'][$option['ID']]);
			}
			
			if (isset($values['CustomOptions']) && count($values['CustomOptions'])) {
				foreach ($values['CustomOptions'] as $option) {
					if (!isset($option['OrderID']))
						$option['OrderID'] = 0;
					
					$newid = sql::run(
						" INSERT INTO `{shoppingitemoptions}` SET" .
						" `ShoppingItemID` = '".(int)$id."'," .
						" `Title` = '".sql::escape($option['Title'])."'," .
						" `TypeID` = '".(int)$option['TypeID']."'," .
						" `Required` = '".
							(isset($option['Required'])?
								(int)$option['Required']:
								0) .
							"'," .
						" `OrderID` = '".(int)$option['OrderID']."'");
					
					if (!$newid) {
						tooltip::display(
							sprintf(_("Custom option couldn't be created! Error: %s"), 
								sql::error()),
							TOOLTIP_ERROR);
						return false;
					}
					
					if (!in_array($option['TypeID'], $multioptiontypes)) {
						$priceid = key($option['Price']);
						
						$newpriceid = sql::run(
							" INSERT INTO `{shoppingitemoptionprices}` SET" .
							" `OptionID` = '".(int)$newid."'," .
							" `MaxCharacters` = '".
								(isset($option['Price'][$priceid]['MaxCharacters'])?
									(int)$option['Price'][$priceid]['MaxCharacters']:
									0)."'," .
							" `Price` = '".sql::escape($option['Price'][$priceid]['Price'])."'," .
							" `PriceType` = '".(int)$option['Price'][$priceid]['PriceType']."'");
						
						if (!$newpriceid) {
							tooltip::display(
								sprintf(_("Custom option price couldn't be created! Error: %s"), 
									sql::error()),
								TOOLTIP_ERROR);
							return false;
						}
					
						continue;
					}
				
					foreach($option['Prices'] as $price) {
						if (!isset($price['OrderID']))
							$price['OrderID'] = 0;
						
						$newpriceid = sql::run(
							" INSERT INTO `{shoppingitemoptionprices}` SET" .
							" `OptionID` = '".(int)$newid."'," .
							" `Title` = '".sql::escape($price['Title'])."'," .
							" `Price` = '".sql::escape($price['Price'])."'," .
							" `PriceType` = '".(int)$price['PriceType']."'," .
							" `OrderID` = '".(int)$price['OrderID']."'");
						
						if (!$newpriceid) {
							tooltip::display(
								sprintf(_("Custom option price couldn't be created! Error: %s"), 
									sql::error()),
								TOOLTIP_ERROR);
							return false;
						}
					}
				}
			}
			
			$options = sql::fetch(sql::run(
				" SELECT COUNT(*) AS `Rows` FROM `{shoppingitemoptions}`" .
				" WHERE `ShoppingItemID` = '".(int)$id."'" .
				" LIMIT 1"));
			
			$values['Options'] = (int)$options['Rows'];
			
		} elseif (JCORE_VERSION >= '0.7') {
			$options = sql::run(
				" SELECT `ID` FROM `{shoppingitemoptions}`" .
				" WHERE `ShoppingItemID` = '".(int)$id."'");
			
			while($option = sql::fetch($options))
				sql::run(
					" DELETE FROM `{shoppingitemoptionprices}`" .
					" WHERE `OptionID` = '".$option['ID']."'");
			
			sql::run(
				" DELETE FROM `{shoppingitemoptions}`" .
				" WHERE `ShoppingItemID` = '".(int)$id."'");
		}
		
		$item = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingitems}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		if (JCORE_VERSION >= '0.7') {
			$itemsform = new shoppingItemsForm();
			$itemsform->editData($id, $values);
			unset($itemsform);
		
		} else {
			sql::run(
				" UPDATE `{shoppingitems}` SET ".
				" `Title` = '".
					sql::escape($values['Title'])."'," .
				" `Description` = '".
					sql::escape($values['Description'])."'," .
				" `Path` = '".
					sql::escape($values['Path'])."'," .
				" `RefNumber` = '".
					sql::escape($values['RefNumber'])."'," .
				" `Price` = '".
					sql::escape($values['Price'])."'," .
				" `AvailableQuantity` = ".
					(!is_null($values['AvailableQuantity'])?
						"'".(int)$values['AvailableQuantity']."'":
						"NULL") .
					"," .
				" `MaxQuantityAtOnce` = ".
					($values['MaxQuantityAtOnce']?
						"'".(int)$values['MaxQuantityAtOnce']."'":
						30).
					"," .
				" `ShowQuantityPicker` = '".
					(int)$values['ShowQuantityPicker']."'," .
				" `Keywords` = '".
					sql::escape($values['Keywords'])."'," .
				" `TimeStamp` = " .
					($values['TimeStamp']?
						"'".sql::escape($values['TimeStamp'])."'":
						"NOW()").
					"," .
				" `Deactivated` = '".
					(int)$values['Deactivated']."'," .
				" `EnableRating` = '".
					(int)$values['EnableRating']."'," .
				" `EnableGuestRating` = '".
					(int)$values['EnableGuestRating']."'," .
				" `EnableComments` = '".
					(int)$values['EnableComments']."'," .
				" `EnableGuestComments` = '".
					(int)$values['EnableGuestComments']."'," .
				" `OrderID` = '".
					(int)$values['OrderID']."'" .
				" WHERE `ID` = '".(int)$id."'");
		}
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(_("Item couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (JCORE_VERSION >= '0.5')
			$this->updateKeywordsCloud($values['Keywords'],
				$item['Keywords']);
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
			
		$item = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingitems}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		$comments = new shoppingItemComments();
		
		$rows = sql::run(
			" SELECT * FROM `{shoppingitemcomments}`" .
			" WHERE `ShoppingItemID` = '".$id."'");
		
		while($row = sql::fetch($rows))
			$comments->delete($row['ID']);
			
		unset($comments);
		
		$pictures = new shoppingItemPictures();
		
		$rows = sql::run(
			" SELECT * FROM `{shoppingitempictures}`" .
			" WHERE `ShoppingItemID` = '".$id."'");
		
		while($row = sql::fetch($rows))
			$pictures->delete($row['ID']);
		
		unset($pictures);
		
		$attachments = new shoppingItemAttachments();
		
		$rows = sql::run(
			" SELECT * FROM `{shoppingitemattachments}`" .
			" WHERE `ShoppingItemID` = '".$id."'");
		
		while($row = sql::fetch($rows))
			$attachments->delete($row['ID']);
		
		unset($attachments);
		
		$goods = new shoppingItemDigitalGoods();
		
		$rows = sql::run(
			" SELECT * FROM `{shoppingitemdigitalgoods}`" .
			" WHERE `ShoppingItemID` = '".$id."'");
		
		while($row = sql::fetch($rows))
			$goods->delete($row['ID']);
		
		unset($goods);
		
		if (JCORE_VERSION >= '0.7') {
			$options = sql::run(
				" SELECT `ID` FROM `{shoppingitemoptions}`" .
				" WHERE `ShoppingItemID` = '".(int)$id."'");
			
			while($option = sql::fetch($options))
				sql::run(
					" DELETE FROM `{shoppingitemoptionprices}`" .
					" WHERE `OptionID` = '".$option['ID']."'");
			
			sql::run(
				" DELETE FROM `{shoppingitemoptions}`" .
				" WHERE `ShoppingItemID` = '".(int)$id."'");
		}
		
		sql::run(
			" DELETE FROM `{shoppingitemratings}` " .
			" WHERE `ShoppingItemID` = '".$id."'");
			
		sql::run(
			" DELETE FROM `{shoppingitems}` " .
			" WHERE `ID` = '".$id."'");
			
		$row = sql::fetch(sql::run(
			" SELECT COUNT(`ID`) AS `Rows` FROM `{shoppingitems}`" .
			" WHERE `ShoppingID` = '".$item['ShoppingID']."'"));
		
		sql::run(
			" UPDATE `{shoppings}`" .
			" SET `Items` = '".(int)$row['Rows']."'," .
			" `TimeStamp` = `TimeStamp` " .
			" WHERE `ID` = '".$item['ShoppingID']."'");
		
		if (JCORE_VERSION >= '0.5')
			$this->updateKeywordsCloud(null, $item['Keywords']);
		
		return true;
	}
	
	function updateKeywordsCloud($newkeywords = null, $oldkeywords = null) {
		if (trim($oldkeywords)) {
			$oldkeywords = array_map('trim', explode(',', $oldkeywords));
			
			foreach($oldkeywords as $oldkeyword)
				sql::run(
					" UPDATE `{shoppingkeywords}` SET " .
					" `Counter` = `Counter` - 1" .
					" WHERE `Keyword` = '".sql::escape($oldkeyword)."'");
		}
			
		if (trim($newkeywords)) {
			$newkeywords = array_map('trim', explode(',', $newkeywords));
			
			foreach($newkeywords as $newkeyword) {
				sql::run(
					" UPDATE `{shoppingkeywords}` SET " .
					" `Counter` = `Counter` + 1" .
					" WHERE `Keyword` = '".sql::escape($newkeyword)."'");
				
				if (!sql::affected())
					sql::run(
						" INSERT INTO `{shoppingkeywords}` SET" .
						" `Keyword` = '".sql::escape($newkeyword)."'," .
						" `Counter` = 1");
			}
		}
		
		sql::run(
			" DELETE FROM `{shoppingkeywords}`" .
			" WHERE !`Counter`");
		
		return true;
	}
	
	// ************************************************   Client Part
	function incViews(&$row) {
		sql::run(
			" UPDATE `{shoppingitems}` SET " .
			" `Views` = `Views` + 1," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".$row['ID']."'");
	}
	
	function ajaxRequest() {
		$options = null;
		
		if (isset($_GET['shoppingitemoptions']))
			$options = (int)$_GET['shoppingitemoptions'];
		
		if ($options) {
			$row = sql::fetch(sql::run(
				" SELECT * FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$options."'" .
				" AND !`Deactivated`"));
			
			if ($row)
				$this->displayBuyFormOptions($row);
			
			return true;
		}
		
		$this->ajaxPaging = true;
		$this->display();
		return true;
	}
	
	function generateLink(&$row) {
		return
			$this->generateCategoryLink($row) .
			'&amp;shoppingitemid='.$row['ID'];
	}
	
	function generateCategoryLink(&$row) {
		return 
			$this->shoppingURL .
			($this->active || $this->popular || 
			 $this->discussed || $this->rated?
				'&amp;shoppingitems=' .
				($this->active?'1':null) .
				($this->popular?'2':null) .
				($this->discussed?'3':null) .
				($this->rated?'4':null):
				null) .
			($this->selectedShoppingID?
				'&amp;shoppingid='.$this->selectedShoppingID:
				null) .
			(url::arg('shoppingitemslimit')?
				'&amp;'.url::arg('shoppingitemslimit'):
				null);
	}
	
	function displayTitle(&$row) {
		echo
			"<a href='".$row['_Link']."'>" .
				$row['Title'] .
			"</a>";
	}
	
	function displaySelectedTitle(&$row) {
		echo $row['Title'];
	}
	
	function displayDetails(&$row) {
		$user = $GLOBALS['USER']->get($row['UserID']);
		
		echo
			calendar::datetime($row['TimeStamp'])." ";
				
		$GLOBALS['USER']->displayUserName($user, __('by %s'));
		
		if ($row['Views'])
			echo
				"<span class='shopping-item-details-separator separator-1'>" .
					", " .
				"</span>" .
				"<span class='shopping-item-views-number'>" .
					sprintf(__("%s views"), $row['Views']) .
				"</span>";
		
		echo
			"<span class='shopping-item-details-separator separator-2'>" .
				", " .
			"</span>";

		if (!isset($row['AvailableQuantity']) || $row['AvailableQuantity'])
			echo 
				"<span class='shopping-item-stock shopping-item-stock-available'>" .
					_("Stock: available") .
				"</span>";
		else
			echo 
				"<span class='shopping-item-stock shopping-item-stock-unavailable'>" .
					_("Stock: unavailable") .
				"</span>";
	}
	
	function displayPictures(&$row) {
		$pictures = new shoppingItemPictures();
		$pictures->selectedOwnerID = $row['ID'];
		$pictures->display();
		unset($pictures);
	}
	
	function displayLatestPicture(&$row) {
		$pictures = new shoppingItemPictures();
		$pictures->selectedOwnerID = $row['ID'];
		$pictures->limit = 1;
		$pictures->showPaging = false;
		$pictures->customLink = $row['_Link'];
		$pictures->display();
		unset($pictures);
	}
	
	function displayDescription(&$row) {
		$codes = new contentCodes();
		$codes->display($row['Description']);
		unset($codes);
	}
	
	function displayBody(&$row) {
		if ($row['Pictures'])
			$this->displayPictures($row);
		
		if ($row['Description']) {
			echo
				"<div class='shopping-item-content'>";
			
			$this->displayDescription($row);
		
			echo
				"</div>";
		}
		
		if (JCORE_VERSION >= '0.7') {
			echo
				"<div class='shopping-item-custom-fields'>";
			
			$this->displayCustomFields($row);
			
			echo
				"</div>";
		}
	}
	
	function displayTeaserBody(&$row) {
		if ($row['Pictures'])
			$this->displayLatestPicture($row);
		
		if ($row['Description']) {	
			echo
				"<div class='shopping-item-content'>";
			
			$row['Description'] = posts::generateTeaser($row['Description']);
			$this->displayDescription($row);
			
			echo
				"</div>";
		}
	}
	
	function displayAttachments(&$row) {
		$attachments = new shoppingItemAttachments();
		$attachments->selectedOwnerID = $row['ID'];
		$attachments->display();
		unset($attachments);
	}
	
	function displayKeywordsCloudLink(&$row) {
		echo  
			"<a href='".$this->shoppingURL."&amp;search=".
				urlencode('"'.trim($row['Keyword']).'"') .
				"&amp;searchin=modules/shopping/shoppingitems' " .
				"style='font-size: ".$row['_FontPercent']."%;'>" .
				ucfirst(trim($row['Keyword'])) .
			"</a> ";
	}
	
	function displayKeywordsCloud() {
		sql::run(
			" CREATE TEMPORARY TABLE `{TMPKeywordsCloud}` " .
			" (`Keyword` varchar(100) NOT NULL default ''," .
			"  `Counter` mediumint(8) unsigned NOT NULL default '0'," .
			"  `ID` tinyint(2) unsigned NOT NULL auto_increment," .
			" PRIMARY KEY  (`ID`)" .
			")");
			
		sql::run(
			" INSERT INTO `{TMPKeywordsCloud}` " .
			" SELECT *, NULL FROM `{shoppingkeywords}`" .
			" ORDER BY `Counter` DESC" .
			" LIMIT ".$this->keywordsCloudLimit);
			
		$rows = sql::run(
			" SELECT * FROM `{TMPKeywordsCloud}`" .
			" ORDER BY `Keyword`");
			
		echo "<div class='shopping-keywords-cloud'>";
		
		while($row = sql::fetch($rows)) {
			$row['_FontPercent'] = round((22-$row['ID'])*100/21);
			
			if ($row['_FontPercent'] < 30)
				$row['_FontPercent'] = 30;
			
			$this->displayKeywordsCloudLink($row);
		}
		
		sql::run(" DROP TEMPORARY TABLE `{TMPKeywordsCloud}` ");
		
		echo "</div>";
	}
	
	function displayKeywordLinks(&$row) {
		$words = explode(',', $row['Keywords']);
		foreach($words as $key => $word) {
			if ($key)
				echo ", ";
			
			echo  
				"<a href='".$row['_CategoryLink']."&amp;search=".
					($this->search?
						urlencode($this->search.","):
						null) .
					urlencode('"'.trim($word).'"') .
					"&amp;searchin=modules/shopping/shoppingitems" .
					"'>" .
					ucfirst(trim($word)) .
				"</a>";
		}
	}
	
	function displayKeywords(&$row) {
		echo
			__("Keywords").": ";
		
		$this->displayKeywordLinks($row);
	}
	
	function displayCustomFields(&$row) {
		$itemsform = new shoppingItemsForm();
		$itemsform->load(false);
		
		$itemsform->displayData($row, array(
			'Title', 'Description', 'Price', 'SpecialPrice',
			'SpecialPriceStartDate', 'SpecialPriceEndDate',
			'CustomOptions', 'RefNumber',
			'Quantity', 'MaxQuantityAtOnce', 'ShowQuantityPicker',
			'TimeStamp', 'Path', 'Keywords', 'EnableRating', 
			'EnableGuestRating', 'EnableComments', 'EnableGuestComments', 
			'DisplayRelatedItems', 'Deactivated', 'OrderID'));
		
		unset($itemsform);
	}
	
	function displayRating(&$row) {
		$rating = new shoppingItemRating();
		$rating->guestRating = $row['EnableGuestRating'];
		$rating->selectedOwnerID = $row['ID'];
		$rating->display();
		unset($rating);	
	}
	
	function displayItemFunctions(&$row) {
		if ($this->selectedID == $row['ID']) {
			echo
				"<a href='".$row['_CategoryLink']."' class='back comment'>".
					"<span>" .
					__("Back").
					"</span>" .
				"</a>";
		
		} else {
			if (!$this->fullItems)
				echo
					"<a href='".$row['_Link']."' class='more-details comment'>".
						"<span>" .
						_("More Details").
						"</span>" .
					"</a>";
			
			if ($row['EnableComments'])
				echo
					"<a href='".$row['_Link']."#comments' class='comments comment'>".
						"<span>" .
						__("Comments") .
						"</span> " .
						"<span>" .
						"(".$row['Comments'].")" .
						"</span>" .
					"</a>";
		}
		
		if (JCORE_VERSION >= '0.7.1' &&
			$GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'])
			echo
				"<a href='".SITE_URL."admin/?path=admin/modules/shopping/" .
					$row['ShoppingID']."/shoppingitems&amp;id=".$row['ID'] .
					"&amp;edit=1#adminform' " .
					"class='edit comment' target='_blank'>" .
					"<span>".
					__("Edit").
					"</span>" .
				"</a>";
	}
	
	function displayBuyFormOutOfStock(&$row) {
		echo
			"<span class='red'>" .
				_("Out of Stock") .
			"</span>";
	}
	
	function displayBuyFormSpecialPrice(&$row) {
		echo
			"<span class='hilight'>";
		
		shopping::displayPrice($row['SpecialPrice']);
		
		echo
			"</span> " .
			"<span class='shopping-item-old-price' " .
				"style='text-decoration: line-through;'>(";
		
		shopping::displayPrice($row['Price']);
		
		echo
			")</span>";
	}
	
	function displayBuyFormPrice(&$row) {
		if (JCORE_VERSION >= '0.7' && $row['SpecialPrice'] != '') {
			if ((!$row['SpecialPriceStartDate'] || 
					$row['SpecialPriceStartDate'] <= date('Y-m-d')) &&
				(!$row['SpecialPriceEndDate'] || 
					$row['SpecialPriceEndDate'] >= date('Y-m-d')))
			{
				echo
					"<div class='shopping-item-special-price'>";
				
				$this->displayBuyFormSpecialPrice($row);
				
				echo
					"</div>";
				return;
			}
		}
		
		shopping::displayPrice($row['Price']);
	}
	
	function displayBuyFormOptions(&$row) {
		$options = sql::run(
			" SELECT * FROM `{shoppingitemoptions}`" .
			" WHERE `ShoppingItemID` = '".$row['ID']."'" .
			" ORDER BY `OrderID`, `ID`");
		
		if (!sql::rows($options))
			return;
		
		$form = new form(
			_('Item Options'),
			'shoppingitemoptions'.$row['ID']);
		
		$form->action = shoppingCart::getURL() .
			(isset($_GET['shoppingcartreferrer'])?
				"&amp;shoppingcartreferrer=" .
					urlencode($_GET['shoppingcartreferrer']):
				null);
		$form->footer = '';
		
		$form->add(
			"Item",
			"shoppingitemid",
			FORM_INPUT_TYPE_HIDDEN,
			true,
			$row['ID']);
		
		while ($option = sql::fetch($options)) {
			$prices = sql::run(
				" SELECT * FROM `{shoppingitemoptionprices}` " .
				" WHERE `OptionID` = '".$option['ID']."'" .
				" ORDER BY `OrderID`, `ID`");
			
			$price = null;
			if (!in_array($option['TypeID'], array(
				FORM_INPUT_TYPE_CHECKBOX, FORM_INPUT_TYPE_RADIO,
				FORM_INPUT_TYPE_SELECT, FORM_INPUT_TYPE_MULTISELECT)))
				$price = sql::fetch($prices);
			
			$form->add(
				_($option['Title']),
				"shoppingitemoptions[".$option['ID']."]",
				$option['TypeID'],
				$option['Required']);
			
			if (isset($option['ValueType']) && $option['ValueType'])
				$form->setValueType($option['ValueType']);
			
			if ($price)
				continue;
				
			while($price = sql::fetch($prices)) {
				if ($price['PriceType'] == 2)
					$pricediff = round($price['Price']*$row['Price']/100, 2);
				else
					$pricediff = $price['Price'];
				
				$form->addValue(
					$price['ID'], 
					_($price['Title'])." (" .
					($pricediff > 0?
						"+".shopping::constructPrice($pricediff):
						shopping::constructPrice($pricediff)) .
					")");
			}
		}
		
		if ($row['ShowQuantityPicker']) {
			if (!$row['MaxQuantityAtOnce'])
				$row['MaxQuantityAtOnce'] = 30;
				
			if ($row['AvailableQuantity'] && 
				$row['AvailableQuantity'] < $row['MaxQuantityAtOnce'])
				$row['MaxQuantityAtOnce'] = $row['AvailableQuantity'];
			
			if ($row['MaxQuantityAtOnce'] > 1) {
				$form->add(
					_("Quantity"),
					"shoppingitemquantity",
					FORM_INPUT_TYPE_SELECT);
				
				for($i = 1; $i <= $row['MaxQuantityAtOnce']; $i++)	
					$form->addValue($i);
			}
		}
		
		$form->add(
			_("Add to My Cart"),
			"shoppingcartaddsubmit",
			FORM_INPUT_TYPE_SUBMIT);
		
		$form->display();
		unset($form);
	}
	
	function displayQuantityPicker(&$row) {
		echo
			"<select name='shoppingitemquantity'>";
	
		for($i = 1; $i <= $row['MaxQuantityAtOnce']; $i++) {	
			echo
				"<option>".$i."</option>";
		}
	
		echo
			"</select>";
	}
	
	function displayBuyFormQuantity(&$row) {
		echo
			"<span class='shopping-item-quantity-picker-title'>" .
				_("Quantity").":" .
			"</span> ";
		
		$this->displayQuantityPicker($row);
	}
	
	function displayBuyFormButton(&$row) {
		if (isset($row['Options']) && $row['Options']) {
			echo
				"<div class='shopping-item-add-to-cart-button button'>" .
					"<a href='".url::uri('ALL') .
						"?request=modules/shopping/shoppingitems" .
						"&amp;shoppingitemoptions=".$row['ID'] .
						"&amp;shoppingcartreferrer=" .
						($this->similar?
							urlencode(url::getarg('shoppingcartreferrer')):
							urlencode(url::uri('shoppingcartreferrer'))) .
						"' " .
						"class='ajax-content-link' " .
						"title='Select Options'>" .
						"<span>"._("Add to My Cart")."</span>" .
					"</a>" .
				"</div>";
			return;
		}
		
		echo
			"<div class='shopping-item-add-to-cart-button button'>" .
				"<a href='javascript://' " .
					"onclick=\"jQuery(this).parents('form').submit();\">" .
					"<span>"._("Add to My Cart")."</span>" .
				"</a>" .
			"</div>";
	}
	
	function displayBuyForm(&$row) {
		if (isset($row['AvailableQuantity']) && !$row['AvailableQuantity']) {
			echo
				"<div class='shopping-item-add-to-cart'>";
			
			$this->displayBuyFormOutOfStock($row);
			
			echo
				"</div>";
			
			return;
		}
		
		echo
			"<div class='shopping-item-add-to-cart'>" .
				"<form action='".$this->shoppingCartURL.
						"&amp;shoppingcartreferrer=" .
						($this->similar?
							urlencode(url::getarg('shoppingcartreferrer')):
							urlencode(url::uri('shoppingcartreferrer'))) .
						"' " .
					"id='shoppingitemaddtocartform".$row['ID']."' method='post'>" .
					"<input type='hidden' name='shoppingcartaddsubmit' value='1' />" .
					"<input type='hidden' name='shoppingitemid' value='".$row['ID']."' />" .
					"<div class='shopping-item-price'>";
		
		$this->displayBuyFormPrice($row);
					
		echo
					"</div>";
			
		if ($row['ShowQuantityPicker'] && 
			(!isset($row['Options']) || !$row['Options'])) 
		{
			if (!$row['MaxQuantityAtOnce'])
				$row['MaxQuantityAtOnce'] = 30;
				
			if ($row['AvailableQuantity'] && 
				$row['AvailableQuantity'] < $row['MaxQuantityAtOnce'])
				$row['MaxQuantityAtOnce'] = $row['AvailableQuantity'];
			
			if ($row['MaxQuantityAtOnce'] > 1) {
				echo
					"<div class='shopping-item-quantity-picker'>";
				
				$this->displayBuyFormQuantity($row);
				
				echo
					"</div>";
			}
		}
		
		$this->displayBuyFormButton($row);
		
		echo
					"<div class='clear-both'></div>" .
				"</form>" .
			"</div>";
	}
	
	function displayComments(&$row) {
		$comments = new shoppingItemComments();
		$comments->guestComments = $row['EnableGuestComments'];
		$comments->selectedOwnerID = $row['ID'];
		$comments->display();
		unset($comments);
	}
	
	function displayRelatedItemDate(&$row) {
		echo
			calendar::date($row['TimeStamp']);
	}
	
	function displayRelatedItems(&$row) {
		if ($row['Keywords'])
			$searches = explode(',', $row['Keywords']);
		else
			$searches = explode(' ', $row['Title']);
			
		if (!count($searches))
			return;
			
		$items = sql::run(
			" SELECT * " .
			" FROM `{shoppingitems}`" .
			" WHERE !`Deactivated`" .
			" AND ID != '".$row['ID']."'" .
			" AND (`Title` REGEXP '".sql::escape(implode('|', $searches))."'" .
			" OR `Keywords` REGEXP '".sql::escape(implode('|', $searches))."')" .
			" ORDER BY `ID` DESC" .
			" LIMIT 10");
		
		if (!sql::rows($items))
			return;
		
		echo 
			"<div class='shopping-related-items'>" .
				"<h3>"._("Related Items")."</h3>" .
				"<ul class='shopping-related-items-list'>";
		
		while($item = sql::fetch($items)) {
			$category = sql::fetch(sql::run(
				" SELECT `ID` FROM `{shoppings}`" .
				" WHERE `ID` = '".$item['ShoppingID']."'" .
				" AND !`Deactivated`" .
				(!$GLOBALS['USER']->loginok?
					" AND (!`MembersOnly` " .
					"	OR `ShowToGuests`)":
					null)));
			
			if (!$category)
				continue;
			
			$item['_Link'] = $this->generateLink($item);
			
			echo
					"<li class='shopping-related-item'>";
			
			$this->displayTitle($item);
			
			echo
						" " .
						"<span class='shopping-related-item-date comment'>";
			
			$this->displayRelatedItemDate($item);
			
			echo
						"</span>" .
					"</li>";
		}
		
		echo
				"</ul>" .
			"</div>";
	}
	
	function displayOne(&$row) {
		echo
			"<div class='shopping-item one".
				" shopping-item".$row['ID']."" .
				" shopping-item-num".$row['_ItemNumber'] .
				(isset($row['_CSSClass'])?
					" ".$row['_CSSClass']:
					null) .
				"'>";
			
		echo
				"<h2 class='shopping-item-title'>";
		
		$this->displayTitle($row);
		
		echo
				"</h2>" .
				"<div class='shopping-item-details comment'>";
		
		$this->displayDetails($row);
		
		echo
				"</div>";
		
		$this->displayTeaserBody($row);
		
		$this->displayBuyForm($row);
		
		if ($row['EnableRating']) {
			echo
				"<div class='shopping-item-rating'>";
			
			$this->displayRating($row);
		
			echo
				"</div>";
		}
		
		echo
				"<div class='shopping-item-links'>";
		
		$this->displayItemFunctions($row);
		
		echo
				"<div class='clear-both'></div>" .
				"</div>" .
				"<div class='spacer bottom'></div>" .
				"<div class='separator bottom'></div>";
			
		echo
			"</div>";
	}
	
	function displaySelected(&$row) {
		$this->incViews($row);
		
		echo
			"<div class='shopping-item" .
				($this->selectedID == $row['ID']?
					" selected":
					null) .
				" shopping-item".$row['ID']."" .
				" shopping-item-num".$row['_ItemNumber'] .
				(isset($row['_CSSClass'])?
					" ".$row['_CSSClass']:
					null) .
				"'>";
			
		echo
				"<h2 class='shopping-item-title'>";
		
		$this->displaySelectedTitle($row);
		
		echo
				"</h2>";
				
		if ($row['EnableRating']) {
			echo
				"<div class='shopping-item-rating'>";
			
			$this->displayRating($row);
		
			echo
				"</div>";
		}
		
		echo
				"<div class='shopping-item-details comment'>";
		
		$this->displayDetails($row);
		
		echo
				"</div>";
		
		$this->displayBuyForm($row);
		
		$this->displayBody($row);
		
		if ($row['Attachments'])
			$this->displayAttachments($row);
		
		if (trim($row['Keywords'])) {
			echo
				"<div class='keywords'>";
			
			$this->displayKeywords($row);
			
			echo
				"</div>";
		}
		
		ob_start();
		$this->displayItemFunctions($row);
		$links = ob_get_contents();
		ob_end_clean();
		
		if ($links)
			echo
				"<div class='shopping-item-links'>" .
				$links .
				"<div class='clear-both'></div>" .
				"</div>";
		
		if (isset($row['DisplayRelatedItems']) && $row['DisplayRelatedItems'])
			$this->displayRelatedItems($row);
				
		echo
				"<div class='spacer bottom'></div>" .
				"<div class='separator bottom'></div>";
				
		echo
			"</div>";
			
		if ($this->selectedID == $row['ID'] && $row['EnableComments'])
			$this->displayComments($row);
	}
	
	function display() {
		$this->shoppingCartURL = shoppingCart::getURL();
		
		if (!$this->shoppingURL)
			$this->shoppingURL = url::uri(shoppingItems::$uriVariables);
		
		if ($this->selectedShoppingID) {
			$selectedcategory = sql::fetch(sql::run(
				" SELECT * FROM `{shoppings}` " .
				" WHERE !`Deactivated`" .
				" AND `ID` = '".$this->selectedShoppingID."'"));
			
			if (JCORE_VERSION >= '0.6' && !$this->top && 
				$selectedcategory['FullItems'])
				$this->fullItems = true;
			
			$this->selectedShoppingIDs[] = $this->selectedShoppingID;
				
			if (!$selectedcategory['Items'] && $this->subgroupItems)
				foreach(shopping::getTree($this->selectedShoppingID) as $category)
					$this->selectedShoppingIDs[] = $category['ID'];
		}
		
		if (preg_match('/(\?|&)search=/i', url::referer(true)) && $this->selectedID) {
			tooltip::display(
				"<a href='".url::referer(true)."'>".__("Back")."</a> " .
				__("to search results."),
				TOOLTIP_NOTIFICATION);
		}
		
		$paging = new paging($this->limit);
		
		if ($this->ajaxPaging) {
			$paging->ajax = true;
			$paging->otherArgs = "&amp;request=modules/shopping/shoppingitems" .
				($this->active || $this->popular || 
				 $this->discussed || $this->rated?
					"&amp;shoppingitems=" .
					($this->active?'1':null) .
					($this->popular?'2':null) .
					($this->discussed?'3':null) .
					($this->rated?'4':null):
					null);
		}
		
		$paging->track(strtolower(get_class($this)).'limit');
		
		if (!$this->selectedID && $this->ignorePaging)
			$paging->reset();
		
		$rows = sql::run(
			$this->SQL() .
			(!$this->selectedID?
				($this->ignorePaging?
					($this->limit?
						" LIMIT ".$this->limit:
						null):
					" LIMIT ".$paging->limit):
				null));
		
		$paging->setTotalItems(sql::count());
		
		if ($this->search && !$this->selectedID && 
			!$this->ajaxRequest && !$this->similar)
			url::displaySearch($this->search, $paging->items);
		
		if (!$this->ajaxRequest)
			echo
				"<div class='shopping-items'>";
		
		$i = 1;
		$total = sql::rows($rows);
		
		while ($row = sql::fetch($rows)) {
			$row['_ItemNumber'] = $i;
			$row['_Link'] = $this->generateLink($row);
			$row['_CategoryLink'] = $this->generateCategoryLink($row);
			
			$row['_CSSClass'] = null;
			
			if ($i == 1)
				$row['_CSSClass'] .= ' first';
			if ($i == $total)
				$row['_CSSClass'] .= ' last';
			 
			if ($row['ID'] == $this->selectedID || $this->fullItems)
				$this->displaySelected($row);
			else
				$this->displayOne($row);
			
			$i++;
		}
		
		if (!$this->selectedID && !$this->randomize && $this->showPaging)
			$paging->display();
		
		echo
				"<div class='clear-both'></div>";
				
		if (!$this->ajaxRequest)
			echo
				"</div>"; //shopping-items
		
		return $paging->items;
	}
}

class shoppingIcons extends pictures {
	var $previewPicture = false;
	var $sqlTable = 'shoppingicons';
	var $sqlRow = 'ShoppingID';
	var $sqlOwnerTable = 'shoppings';
	var $selectedOwner = 'Category';
	var $sqlOwnerCountField = 'Icons';
	var $adminPath = 'admin/modules/shopping/shoppingicons';
	
	function __construct() {
		languages::load('shopping');
		
		parent::__construct();
		
		$this->rootPath = $this->rootPath.'icons/';
		$this->rootURL = $this->rootURL.'icons/';
		
		$this->uriRequest = "modules/shopping/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
}

class shopping extends modules {
	var $searchable = true;
	var $limit = 0;
	var $selectedID = 0;
	var $selectedItemID = 0;
	var $ignorePaging = false;
	var $showPaging = true;
	var $ajaxPaging = AJAX_PAGING;
	var $randomizeItems = false;
	var $latestItems = false;
	var $activeItems = false;
	var $popularItems = false;
	var $discussedItems = false;
	var $ratedItems = false;
	var $topItems = false;
	var $search = null;
	var $attachmentsPath;
	var $picturesPath;
	var $thumbnailsPath;
	var $digitalGoodsPath;
	var $shoppingURL;
	var $adminPath = 'admin/modules/shopping';
	
	function __construct() {
		languages::load('shopping');
		
		if (JCORE_VERSION < '0.6')
			$this->latestItems = true;
		
		if (isset($_GET['shoppingid']))
			$this->selectedID = (int)$_GET['shoppingid'];
		
		if (isset($_GET['shoppingitemid']))
			$this->selectedItemID = (int)$_GET['shoppingitemid'];
		
		if (isset($_GET['searchin']) && isset($_GET['search']) && 
			$_GET['searchin'] == 'modules/shopping')
			$this->search = trim(strip_tags($_GET['search']));
		
		$this->attachmentsPath = SITE_PATH.'sitefiles/file/shopping/';
		$this->digitalGoodsPath = $this->attachmentsPath.'digitalgoods/';
		
		$this->picturesPath = SITE_PATH.'sitefiles/image/shopping/';
		$this->thumbnailsPath = $this->picturesPath.'thumbnail/';
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
	
	function SQL() {
		return
			" SELECT * FROM `{shoppings}`" .
			" WHERE !`Deactivated`" .
			(JCORE_VERSION >= '0.5' && !$GLOBALS['USER']->loginok?
				" AND (!`MembersOnly` " .
				"	OR `ShowToGuests`)":
				null) .
			($this->search?
				sql::search(
					$this->search,
					array('Title', 'Description')):
				((int)$this->selectedID?
					" AND `SubCategoryOfID` = '".(int)$this->selectedID."'":
					" AND !`SubCategoryOfID`")) .
			" ORDER BY `OrderID`, `ID`";		
	}
	
	function installSQL() {
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicforms}` " .
			" WHERE `FormID` = 'shoppingitems';"));
		
		if (sql::display())
			return false;
			
		$formid = $exists['ID'];
			
		if (!$exists) {
			$formid = sql::run(
				" INSERT INTO `{dynamicforms}` " .
				" (`Title`, `FormID`, `Method`, `SendNotificationEmail`, `SQLTable`, `Protected`, `ProtectedSQLTable`, `BrowseDataURL`) VALUES" .
				" ('Shopping Items', 'shoppingitems', 'post', 0, 'shoppingitems', 1, 1, '?path=admin/modules/shopping');");
			
			if (sql::display())
				return false;
		}
		
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicformfields}` " .
			" WHERE `FormID` = '".$formid."';"));
		
		if (sql::display())
			return false;
		
		if (!$exists) {
			sql::run(
				" INSERT INTO `{dynamicformfields}` " .
				" (`FormID`, `Title`, `Name`, `TypeID`, `ValueType`, `Required`, `Searchable`, `PlaceholderText`, `TooltipText`, `AdditionalText`, `Attributes`, `Style`, `OrderID`, `Protected`) VALUES" .
				" (".$formid.", 'Title', 'Title', 1, 1, 1, 1, '', '', '', '', 'width: 350px;', 1, 1)," .
				" (".$formid.", 'Description', 'Description', 19, 6, 0, 1, '', '', '', '', 'height: 400px;', 2, 1)," .
				" (".$formid.", 'Item Options', '', 13, 0, 0, 0, '', '', '', '', '', 3, 0)," .
				" (".$formid.", 'Price', 'Price', 1, 12, 1, 0, '', 'e.g. 170', '', '', 'width: 80px;', 4, 1)," .
				" (".$formid.", 'Special Price', 'SpecialPrice', 1, 12, 0, 0, '', 'e.g. 150', '', '', 'width: 80px;', 5, 1)," .
				" (".$formid.", 'Special Price Starts', 'SpecialPriceStartDate', 17, 5, 0, 0, '', '', '', '', 'width: 100px;', 6, 1)," .
				" (".$formid.", 'Special Price Ends', 'SpecialPriceEndDate', 17, 5, 0, 0, '', '', '', '', 'width: 100px;', 7, 1)," .
				" (".$formid.", 'Ref. Number', 'RefNumber', 1, 1, 0, 0, '', 'e.g. ITEM-0711', '', '', 'width: 120px;', 8, 1)," .
				" (".$formid.", 'Weight', 'Weight', 1, 12, 0, 0, '', '', '', '', 'width: 50px;', 9, 1)," .
				" (".$formid.", 'Taxable', 'Taxable', 3, 10, 0, 0, '', '', '', '', '', 10, 1)," .
				" (".$formid.", ' ', '', 14, 0, 0, 0, '', '', '', '', '', 11, 0)," .
				" (".$formid.", 'Custom Options', 'sico', 13, 0, 0, 0, '', '', '', '', '', 12, 0)," .
				" (".$formid.", 'Custom Options Placeholder', 'CustomOptions', 18, 3, 0, 0, '', '', '', '', '', 13, 1)," .
				" (".$formid.", ' ', '', 14, 0, 0, 0, '', '', '', '', '', 14, 0)," .
				" (".$formid.", 'Quantity Options', '', 13, 0, 0, 0, '', '', '', '', '', 15, 0)," .
				" (".$formid.", 'Available Quantity', 'AvailableQuantity', 1, 2, 0, 0, '', 'e.g. 1000 (leave it empty for unlimited)', '', '', 'width: 50px;', 16, 1)," .
				" (".$formid.", 'Max Order Quantity at Once', 'MaxQuantityAtOnce', 1, 2, 0, 0, '', 'e.g. 15 (default 30)', '', '', 'width: 50px;', 17, 1)," .
				" (".$formid.", 'Show Quantity Picker', 'ShowQuantityPicker', 3, 10, 0, 0, '', '', '', '', '', 18, 1)," .
				" (".$formid.", ' ', '', 14, 0, 0, 0, '', '', '', '', '', 19, 0)," .
				" (".$formid.", 'Blogging Options', '', 13, 0, 0, 0, '', '', '', '', '', 20, 0)," .
				" (".$formid.", 'Created on', 'TimeStamp', 16, 4, 0, 0, '', '', '', '', 'width: 170px;', 21, 1)," .
				" (".$formid.", 'Path', 'Path', 1, 1, 0, 0, '', '', '', '', 'width: 300px;', 22, 1)," .
				" (".$formid.", 'Keywords', 'Keywords', 1, 1, 0, 1, '', 'e.g. oranges, lemons, limes', '', '', 'width: 250px;', 23, 1)," .
				" (".$formid.", ' ', '', 14, 0, 0, 0, '', '', '', '', '', 24, 0)," .
				" (".$formid.", 'Rating Options', '', 13, 0, 0, 0, '', '', '', '', '', 25, 0)," .
				" (".$formid.", 'Enable Rating', 'EnableRating', 3, 10, 0, 0, '', '', '', '', '', 26, 1)," .
				" (".$formid.", 'Enable Guest Rating', 'EnableGuestRating', 3, 10, 0, 0, '', '', '', '', '', 27, 1)," .
				" (".$formid.", ' ', '', 14, 0, 0, 0, '', '', '', '', '', 28, 0)," .
				" (".$formid.", 'Comments Options', '', 13, 0, 0, 0, '', '', '', '', '', 29, 0)," .
				" (".$formid.", 'Enable Comments', 'EnableComments', 3, 10, 0, 0, '', '', '', '', '', 30, 1)," .
				" (".$formid.", 'Enable Guest Comments', 'EnableGuestComments', 3, 10, 0, 0, '', '', '', '', '', 31, 1)," .
				" (".$formid.", ' ', '', 14, 0, 0, 0, '', '', '', '', '', 32, 0)," .
				" (".$formid.", 'Additional Options', '', 13, 0, 0, 0, '', '', '', '', '', 33, 0)," .
				" (".$formid.", 'Display Related Items', 'DisplayRelatedItems', 3, 10, 0, 0, '', '', '', '', '', 34, 1)," .
				" (".$formid.", 'Deactivated', 'Deactivated', 3, 10, 0, 0, '', '', '<span class=''comment'' style=''text-decoration: line-through;''>(marked with strike through)</span>', '', '', 35, 1)," .
				" (".$formid.", 'Order', 'OrderID', 1, 2, 0, 0, '', '', '', '', 'width: 50px;', 36, 1)," .
				" (".$formid.", ' ', '', 14, 0, 0, 0, '', '', '', '', '', 37, 0);");
			
			if (sql::display())
				return false;
		}
		
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppings}` (" .
			" `ID` smallint(5) unsigned NOT NULL auto_increment," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `Description` mediumtext NULL," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Path` varchar(255) NOT NULL default ''," .
			" `URL` varchar(255) NOT NULL default ''," .
			" `FullItems` tinyint(1) unsigned NOT NULL default '0'," .
			" `HideSubgroupItems` TINYINT(1) UNSIGNED NOT NULL DEFAULT  '0'," .
			" `SubCategoryOfID` smallint(5) unsigned NOT NULL default '0'," .
			" `Items` smallint(5) unsigned NOT NULL default '0'," .
			" `Icons` SMALLINT UNSIGNED NOT NULL DEFAULT '0'," .
			" `Deactivated` tinyint(1) unsigned NOT NULL default '0'," .
			" `MembersOnly` tinyint(1) unsigned NOT NULL default '0'," .
			" `ShowToGuests` tinyint(1) unsigned NOT NULL default '0'," .
			" `DisplayIcons` tinyint(1) unsigned NOT NULL default '0'," .
			" `DigitalGoods` tinyint(1) unsigned NOT NULL default '0'," .
			" `DigitalGoodsExpiration` tinyint(3) unsigned NOT NULL default '0'," .
			" `Limit` tinyint(3) unsigned NOT NULL default '0'," .
			" `UserID` mediumint(8) unsigned NOT NULL default '1'," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `Path` (`Path`, `UserID`, `TimeStamp`,`SubCategoryOfID`,`Deactivated`,`OrderID`)," .
			" KEY `MembersOnly` (`MembersOnly`, `ShowToGuests`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingicons}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" `Location` varchar(255) NOT NULL default ''," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `URL` varchar(255) NOT NULL default ''," .
			" `ShoppingID` smallint(5) unsigned NOT NULL default '1'," .
			" `Views` int(10) unsigned NOT NULL default '0'," .
			" `Thumbnail` tinyint(1) unsigned NOT NULL default '0'," .
			" KEY `ID` (`ID`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `ShoppingID` (`ShoppingID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingkeywords}` (" .
			" `Keyword` VARCHAR( 100 ) NOT NULL default '' ," .
			" `Counter` SMALLINT UNSIGNED NOT NULL DEFAULT  '0'," .
			" INDEX (  `Counter` )" .
			" ) ENGINE = MYISAM ;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingitems}` (" .
			" `ID` mediumint(8) unsigned NOT NULL auto_increment," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `Description` mediumtext NULL," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Path` varchar(255) NOT NULL default ''," .
			" `RefNumber` VARCHAR( 100 ) NOT NULL default ''," .
			" `Price` DECIMAL( 12, 2 ) NOT NULL default '0.00'," .
			" `SpecialPrice` DECIMAL( 12, 2 ) NULL DEFAULT NULL," .
			" `SpecialPriceStartDate` DATE NULL DEFAULT NULL," .
			" `SpecialPriceEndDate` DATE NULL DEFAULT NULL," .
			" `Weight` DECIMAL( 10, 2 ) UNSIGNED NULL DEFAULT  NULL," .
			" `Taxable` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0'," .
			" `AvailableQuantity` SMALLINT NULL DEFAULT NULL," .
			" `MaxQuantityAtOnce` TINYINT UNSIGNED NOT NULL DEFAULT '0'," .
			" `ShowQuantityPicker` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0'," .
			" `Keywords` varchar(255) NOT NULL default ''," .
			" `Views` INT UNSIGNED NOT NULL DEFAULT '0'," .
			" `DisplayRelatedItems` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0'," .
			" `NumberOfOrders` INT UNSIGNED NOT NULL DEFAULT '0'," .
			" `Deactivated` tinyint(1) unsigned NOT NULL default '0'," .
			" `Options` SMALLINT UNSIGNED NOT NULL DEFAULT  '0'," .
			" `Pictures` smallint(5) unsigned NOT NULL default '0'," .
			" `Attachments` smallint(5) unsigned NOT NULL default '0'," .
			" `DigitalGoods` smallint(5) unsigned NOT NULL default '0'," .
			" `Comments` smallint(5) unsigned NOT NULL default '0'," .
			" `EnableComments` tinyint(1) unsigned NOT NULL default '0'," .
			" `EnableGuestComments` tinyint(1) unsigned NOT NULL default '0'," .
			" `Rating` tinyint(1) unsigned NOT NULL default '0'," .
			" `EnableRating` tinyint(1) unsigned NOT NULL default '0'," .
			" `EnableGuestRating` tinyint(1) unsigned NOT NULL default '0'," .
			" `ShoppingID` smallint(5) unsigned NOT NULL default '0'," .
			" `UserID` mediumint(8) unsigned NOT NULL default '1'," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `Path` (`Path`,`UserID`,`TimeStamp`,`Deactivated`,`OrderID`)," .
			" KEY `Keywords` (`Keywords`)," .
			" KEY `ShoppingID` (`ShoppingID`)," .
			" KEY `AvailableQuantity` (`AvailableQuantity`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingitemoptions}` (" .
			" `ID` int(10) unsigned NOT NULL AUTO_INCREMENT," .
			" `ShoppingItemID` mediumint(8) unsigned NOT NULL DEFAULT '0'," .
			" `Title` varchar(255) NOT NULL DEFAULT ''," .
			" `TypeID` tinyint(3) unsigned NOT NULL DEFAULT '0'," .
			" `Required` tinyint(1) unsigned NOT NULL DEFAULT '0'," .
			" `OrderID` mediumint(9) NOT NULL DEFAULT '0'," .
			" PRIMARY KEY (`ID`)," .
			" KEY `ShoppingItemID` (`ShoppingItemID`,`OrderID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingitemoptionprices}` (" .
			" `ID` int(10) unsigned NOT NULL AUTO_INCREMENT," .
			" `OptionID` int(10) unsigned NOT NULL DEFAULT '0'," .
			" `Title` varchar(255) NOT NULL DEFAULT ''," .
			" `MaxCharacters` tinyint(4) unsigned NOT NULL DEFAULT '0'," .
			" `Price` decimal(12,2) NOT NULL DEFAULT '0.00'," .
			" `PriceType` tinyint(1) unsigned NOT NULL DEFAULT '1'," .
			" `OrderID` mediumint(9) NOT NULL DEFAULT '0'," .
			" PRIMARY KEY (`ID`)," .
			" KEY `OptionID` (`OptionID`)," .
			" KEY `OrderID` (`OrderID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingitemcomments}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `ShoppingItemID` mediumint(8) unsigned NOT NULL default '0'," .
			" `UserName` varchar(100) NOT NULL default ''," .
			" `Email` varchar(100) NOT NULL default ''," .
			" `UserID` mediumint(8) unsigned NOT NULL default '0'," .
			" `Comment` text NULL," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP," .
			" `IP` bigint(20) NOT NULL default '0'," .
			" `SubCommentOfID` int(10) unsigned NOT NULL default '0'," .
			" `Rating` tinyint(1) unsigned NOT NULL default '0'," .
			" `Pending` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `ShoppingItemID` (`ShoppingItemID`,`UserID`,`UserName`)," .
			" KEY `Pending` (`Pending`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingitemcommentsratings}` (" .
			" `CommentID` int(10) unsigned NOT NULL default '0'," .
			" `UserID` mediumint(8) unsigned NOT NULL default '0'," .
			" `IP` bigint(20) NOT NULL default '0'," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Rating` tinyint(1) NOT NULL default '0'," .
			" KEY `CommentID` (`CommentID`,`UserID`,`IP`,`TimeStamp`)," .
			" KEY `Rating` (`Rating`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingitemattachments}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" `Location` varchar(255) NOT NULL default ''," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `HumanMimeType` varchar(255) default NULL," .
			" `FileSize` int(10) unsigned NOT NULL default '0'," .
			" `ShoppingItemID` mediumint(8) unsigned NOT NULL default '1'," .
			" `Downloads` int(10) unsigned NOT NULL default '0'," .
			" KEY `ID` (`ID`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `ShoppingItemID` (`ShoppingItemID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingitempictures}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" `Location` varchar(255) NOT NULL default ''," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `URL` varchar(255) NOT NULL default ''," .
			" `ShoppingItemID` mediumint(8) unsigned NOT NULL default '1'," .
			" `Views` int(10) unsigned NOT NULL default '0'," .
			" `Thumbnail` tinyint(1) unsigned NOT NULL default '0'," .
			" KEY `ID` (`ID`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `ShoppingItemID` (`ShoppingItemID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingitemdigitalgoods}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" `Location` varchar(255) NOT NULL default ''," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `HumanMimeType` varchar(255) NOT NULL default ''," .
			" `FileSize` int(10) unsigned NOT NULL default '0'," .
			" `ShoppingItemID` mediumint(8) unsigned NOT NULL default '1'," .
			" `Downloads` int(10) unsigned NOT NULL default '0'," .
			" KEY `ID` (`ID`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `ShoppingItemID` (`ShoppingItemID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingitemratings}` (" .
			" `ShoppingItemID` mediumint(8) unsigned NOT NULL default '0'," .
			" `UserID` mediumint(8) unsigned NOT NULL default '0'," .
			" `IP` bigint(20) NOT NULL default '0'," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Rating` tinyint(1) NOT NULL default '0'," .
			" KEY `Rating` (`Rating`)," .
			" KEY `ShoppingItemID` (`ShoppingItemID`,`UserID`,`IP`,`TimeStamp`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		return true;
	}
	
	function installFiles() {
		$iconspath = SITE_URL."lib/icons/";
		
		if (defined('JCORE_URL') && JCORE_URL)
			$iconspath = JCORE_URL."lib/icons/";
		
		$css = 
			".shopping-category-selected {\n" .
			"	margin-bottom: 15px;\n" .
			"}\n" .
			"\n" .
			".shopping-category-title {\n" .
			"	margin: 0;\n" .
			"}\n" .
			"\n" .
			".shopping-category-details {\n" .
			"	margin: 3px 0 7px 0;\n" .
			"}\n" .
			"\n" .
			".shopping-categories {\n" .
			"	margin-bottom: 20px;\n" .
			"}\n" .
			"\n" .
			".shopping-category {\n" .
			"	padding: 5px 10px 5px 5px;\n" .
			"	margin: 1px 0px 5px 0px;\n" .
			"}\n" .
			"\n" .
			".shopping-category .shopping-category-title,\n" .
			".shopping-category .shopping-category-details,\n" .
			".shopping-category .shopping-category-description,\n" .
			".shopping-category .shopping-category-links\n" .
			"{\n" .
			"	margin-left: 60px;\n" .
			"}\n" .
			"\n" .
			".shopping-category .picture-title,\n" .
			".shopping-category .picture-details\n" .
			"{\n" .
			"	display: none;\n" .
			"}\n" .
			"\n" .
			".shopping-category .picture {\n" .
			"	width: auto;\n" .
			"	height: auto;\n" .
			"	margin: 0;\n" .
			"}\n" .
			"\n" .
			".shopping-category .picture img {\n" .
			"	width: 48px;\n" .
			"	height: auto;\n" .
			"}\n" .
			"\n" .
			".shopping-category-icon {\n" .
			"	display: block;\n" .
			"	float: left;\n" .
			"	width: 48px;\n" .
			"	height: 48px;\n" .
			"	background: url(\"".$iconspath."48/folder-shopping-categories.png\");\n" .
			"}\n" .
			"\n" .
			".shopping-category-icon.subcategories {\n" .
			"	background-image: url(\"".$iconspath."48/folder-shopping-subcategories.png\");\n" .
			"}\n" .
			"\n" .
			".shopping-category-icon.icon,\n" .
			".shopping-category-icon.preview\n" .
			"{\n" .
			"	background-image: none;\n" .
			"}\n" .
			"\n" .
			".shopping-category-selected .shopping-category-icon {\n" .
			"	width: auto;\n" .
			"	height: auto;\n" .
			"	margin-right: 15px;\n" .
			"}\n" .
			"\n" .
			".shopping-category-links a {\n" .
			"	display: inline-block;\n" .
			"	text-decoration: none;\n" .
			"	padding: 5px 0px 5px 20px;\n" .
			"	background: url(\"".$iconspath."16/link.png\") 0px 50% no-repeat;\n" .
			"	margin-right: 10px;\n" .
			"}\n" .
			"\n" .
			".shopping-category-links .back {\n" .
			"	background-image: url(\"".$iconspath."16/doc_page_previous.png\");\n" .
			"}\n" .
			"\n" .
			".shopping-category-links .items {\n" .
			"	background-image: url(\"".$iconspath."16/paper_bag.png\");\n" .
			"}\n" .
			"\n" .
			".shopping-sub-categories {\n" .
			"	margin-bottom: 20px;\n" .
			"}\n" .
			"\n" .
			".shopping-item {\n" .
			"	margin-bottom: 20px;\n" .
			"}\n" .
			"\n" .
			".shopping-item-pictures {\n" .
			"	margin: 0 0 0px 15px;\n" .
			"	float: right;\n" .
			"}\n" .
			"\n" .
			".shopping-item.one .shopping-item-pictures {\n" .
			"	float: left;\n" .
			"	margin: 10px 10px 0px 0px;\n" .
			"}\n" .
			"\n" .
			".shopping-item-title {\n" .
			"	margin: 0;\n" .
			"}\n" .
			"\n" .
			".shopping-item.one .shopping-item-title {\n" .
			"	font-size: 120%;\n" .
			"}\n" .
			"\n" .
			".shopping-item.last .separator.bottom {\n" .
			"	display: none;\n" .
			"}\n" .
			"\n" .
			".shopping-item-add-to-cart {\n" .
			"	margin-top: 15px;\n" .
			"}\n" .
			"\n" .
			".shopping-item.one .shopping-item-add-to-cart {\n" .
			"	margin-bottom: 10px;\n" .
			"}\n" .
			"\n" .
			".shopping-item-add-option,\n" .
			".shopping-item-add-option-price\n" .
			"{\n" .
			"	display: block;\n" .
			"	float: none;\n" .
			"	margin-top: 10px;\n" .
			"}\n" .
			"\n" .
			".shopping-item-price {\n" .
			"	float: right;\n" .
			"	font-weight: bold;\n" .
			"	font-size: 150%;\n" .
			"}\n" .
			"\n" .
			".shopping-item-old-price {\n" .
			"	font-weight: normal;\n" .
			"	font-size: 80%;\n" .
			"}\n" .
			"\n" .
			".shopping-item-quantity-picker {\n" .
			"	margin-right: 10px;\n" .
			"	float: left;\n" .
			"}\n" .
			"\n" .
			".shopping-item-add-to-cart-button span {\n" .
			"	background: url(\"".$iconspath."16/cart.png\") no-repeat;\n" .
			"	padding-left: 20px;\n" .
			"	display: block;\n" .
			"	height: 16px;\n" .
			"}\n" .
			"\n" .
			".shopping-item-rating {\n" .
			"	float: right;\n" .
			"}\n" .
			"\n" .
			".shopping-item-links a {\n" .
			"	display: inline-block;\n" .
			"	text-decoration: none;\n" .
			"	padding: 5px 0px 5px 20px;\n" .
			"	background: url(\"".$iconspath."16/link.png\") 0px 50% no-repeat;\n" .
			"	margin-right: 10px;\n" .
			"}\n" .
			"\n" .
			".shopping-item-links .back {\n" .
			"	background-image: url(\"".$iconspath."16/doc_page_previous.png\");\n" .
			"}\n" .
			"\n" .
			".shopping-item-links .more-details {\n" .
			"	background-image: url(\"".$iconspath."16/doc_text_image.png\");\n" .
			"}\n" .
			"\n" .
			".shopping-item-links .comments {\n" .
			"	background-image: url(\"".$iconspath."16/comment.png\");\n" .
			"}\n" .
			"\n" .
			".shopping-item-links .edit {\n" .
			"	background-image: url(\"".$iconspath."16/page_white_edit.png\");\n" .
			"}\n" .
			"\n" .
			".shopping-keywords-cloud {\n" .
			"	clear: both;\n" .
			"	font-size: 27px;\n" .
			"	margin-top: 1px;\n" .
			"}\n" .
			"\n" .
			".admin-link.items {\n" .
			"	background-image: url(\"".$iconspath."32/box-items.png\");\n" .
			"}\n" .
			"\n" .
			".admin-link.digital-goods {\n" .
			"	background-image: url(\"".$iconspath."32/document-save.png\");\n" .
			"}\n" .
			"\n" .
			".as-modules-shopping a {\n" .
			"	background-image: url(\"".$iconspath."48/folder-shopping-categories.png\");\n" .
			"}\n";
		
		if (!files::save(SITE_PATH.'template/modules/css/shopping.css', $css, true)) {
			tooltip::display(
				__("Could NOT write css file.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"template/modules/css/"),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		if (!parent::installed($this))
			return 0;
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{shoppings}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				_('New Category'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			_('Orders'), 
			'?path=admin/modules/shoppingorders');
		favoriteLinks::add(
			_('Cart Settings'), 
			'?path=admin/modules/shoppingcart');
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 250px;');
		
		$form->add(
			_('Sub Category of'),
			'SubCategoryOfID',
			FORM_INPUT_TYPE_SELECT);
		$form->setValueType(FORM_VALUE_TYPE_INT);
			
		$form->addValue('', '');
		
		$form->add(
			__('Content Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Description'),
			'Description',
			FORM_INPUT_TYPE_TEXTAREA);
		$form->setStyle('width: 350px; height: 100px;');
		$form->setValueType(FORM_VALUE_TYPE_HTML);
		
		$form->add(
			__('Limit'),
			'Limit',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		if (JCORE_VERSION >= '0.7') {
			$form->add(
				_('Show Icons'),
				'DisplayIcons',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				1);
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			$form->addAdditionalText(
				_("(display icons when category selected)"));
		}
		
		if (JCORE_VERSION >= '0.6') {
			$form->add(
				_('Full Items'),
				'FullItems',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
		}
			
		if (JCORE_VERSION >= '0.7') {
			$form->add(
				_('Hide Subgroup Items'),
				'HideSubgroupItems',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
		}
			
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		if (JCORE_VERSION >= '0.5') {
			$form->add(
				_('Digital / Downloadable Goods'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
			
			$form->add(
				_('Digital Goods'),
				'DigitalGoods',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
			$form->add(
				_('Downloads Expire after'),
				'DigitalGoodsExpiration',
				FORM_INPUT_TYPE_TEXT,
				false,
				'0');
			$form->setValueType(FORM_VALUE_TYPE_INT);
			$form->setStyle('width: 30px;');
			
			$form->addAdditionalText(_("days (when ordered)"));
			
			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER);
		}
		
		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Created on'),
			'TimeStamp',
			FORM_INPUT_TYPE_TIMESTAMP);
		$form->setStyle('width: 170px;');
		$form->setValueType(FORM_VALUE_TYPE_TIMESTAMP);
		
		$form->add(
			__('Path'),
			'Path',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 300px;');
		
		if (JCORE_VERSION >= '0.6') {
			$form->add(
				__('Link to URL'),
				'URL',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 300px;');
			$form->setValueType(FORM_VALUE_TYPE_URL);
			$form->setTooltipText(__("e.g. http://domain.com"));
		}
		
		if (JCORE_VERSION >= '0.5') {
			$form->add(
				_('Members Only'),
				'MembersOnly',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
			$form->add(
				_('Show to Guests'),
				'ShowToGuests',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
		}
		
		$form->add(
			__('Deactivated'),
			'Deactivated',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
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
	}
	
	function verifyAdmin(&$form = null) {
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
		
		if ($reorder) {
			if (!$orders)
				return false;
			
			foreach($orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{shoppings}` " .
					" SET `OrderID` = '".(int)$ovalue."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".(int)$oid."'");
			}
			
			tooltip::display(
				_("Categories have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				_("Category has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if ($edit && $form->get('SubCategoryOfID')) {
			foreach(shopping::getBackTraceTree($form->get('SubCategoryOfID')) as $category) {
				if ($category['ID'] == $id) {
					tooltip::display(
						_("Category cannot be subcategory of itself!"),
						TOOLTIP_ERROR);
					
					return false;
				}
			}
		}
		
		if (!$form->get('Path')) {
			$path = '';
			
			if ($form->get('SubCategoryOfID')) {
				$subcategoryof = sql::fetch(sql::run(
					" SELECT `Path` FROM `{shoppings}`" .
					" WHERE `ID` = ".(int)$form->get('SubCategoryOfID')));
				
				$path .= $subcategoryof['Path'].'/'; 
			}
			
			$path .= url::genPathFromString($form->get('Title'));
			
			$form->set('Path', $path);
		}
				
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray()))
				return false;
				
			tooltip::display(
				_("Category has been successfully updated.")." " .
				(modules::getOwnerURL('shopping')?
					"<a href='".shopping::getURL().
						"&amp;shoppingid=".$id."' target='_blank'>" .
						_("View Category") .
					"</a>" .
					" - ":
					null) .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$newid = $this->add($form->getPostArray()))
			return false;
				
		tooltip::display(
			_("Category has been successfully created.")." " .
			(modules::getOwnerURL('shopping')?
				"<a href='".shopping::getURL().
					"&amp;shoppingid=".$newid."' target='_blank'>" .
					_("View Category") .
				"</a>" .
				" - ":
				null) .
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
				__("Title / Path")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Limit")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
		echo
			"<th><span class='nowrap'>".
				_("Items")."</span></th>";
		
		if (JCORE_VERSION >= '0.6')
			echo
				"<th><span class='nowrap'>".
					_("Icon")."</span></th>";
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
			"<td class='auto-width' " .
				($row['Deactivated']?
					"style='text-decoration: line-through;' ":
					null).
				">" .
				"<a href='".
				url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."' " .
					(!$row['SubCategoryOfID']?
						"class='bold' ":
						null).
					">" .
				$row['Title'] .
				"</a> " .
				"<div class='comment' style='padding-left: 10px;'>" .
					$row['Path'] .
				"</div>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				($row['Limit']?
					$row['Limit']:
					null) .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='admin-link items' " .
					"title='".htmlspecialchars(_("Items"), ENT_QUOTES) .
						" (".$row['Items'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/shoppingitems'>" .
					(ADMIN_ITEMS_COUNTER_ENABLED && $row['Items']?
						"<span class='counter'>" .
							"<span>" .
								"<span>" .
								$row['Items']."" .
								"</span>" .
							"</span>" .
						"</span>":
						null) .
				"</a>" .
			"</td>";
		
		if (JCORE_VERSION >= '0.6')
			echo
				"<td align='center'>" .
					"<a class='admin-link icons' " .
						"title='".htmlspecialchars(_("Icons"), ENT_QUOTES) .
							" (".$row['Icons'].")' " .
						"href='".url::uri('ALL') .
						"?path=".admin::path()."/".$row['ID']."/shoppingicons'>" .
						(ADMIN_ITEMS_COUNTER_ENABLED && $row['Icons']?
							"<span class='counter'>" .
								"<span>" .
									"<span>" .
									$row['Icons']."" .
									"</span>" .
								"</span>" .
							"</span>":
							null) .
					"</a>" .
				"</td>";
	}
	
	function displayAdminListItemFunctions(&$row) {
		echo
			"<td>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayAdminListItemSelected(&$row) {
		$user = $GLOBALS['USER']->get($row['UserID']);
		
		admin::displayItemData(
			__("Created on"), 
			calendar::dateTime($row['TimeStamp'])." " .
			$GLOBALS['USER']->constructUserName($user, __('by %s')));
		
		if (JCORE_VERSION >= '0.6' && $row['URL'])
			admin::displayItemData(
				__("Link to URL"),
				"<a href='".$row['URL']."' target='_blank'>" . 
					$row['URL'] . 
				"</a>");
		
		if (JCORE_VERSION >= '0.7' && $row['DisplayIcons'])
			admin::displayItemData(
				_("Show Icons"),
				__("Yes"));
		
		if (JCORE_VERSION >= '0.6' && $row['FullItems'])
			admin::displayItemData(
				_("Full Items"),
				__("Yes"));
		
		if (JCORE_VERSION >= '0.7' && $row['HideSubgroupItems'])
			admin::displayItemData(
				_("Hide Subgroup Items"),
				__("Yes"));
		
		if (JCORE_VERSION >= '0.5' && $row['MembersOnly'])
			admin::displayItemData(
				_("Members Only"),
				__("Yes"));
		
		if (JCORE_VERSION >= '0.5' && $row['ShowToGuests'])
			admin::displayItemData(
				_("Show to Guests"),
				__("Yes"));
		
		if (JCORE_VERSION >= '0.5' && $row['DigitalGoods'])
			admin::displayItemData(
				_("Digital Goods"),
				__("Yes") .
				($row['DigitalGoodsExpiration']?
					" ".sprintf(_("(downloads expire after %s days)"), 
						$row['DigitalGoodsExpiration']):
					null));
		
		admin::displayItemData(
			"<hr />");
		admin::displayItemData(
			nl2br($row['Description']));
	}
	
	function displayAdminListFunctions() {
		echo
			"<input type='submit' name='reordersubmit' value='".
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminList($rows, $rowpair = null) {
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if (isset($rowpair)) {
			echo 
				"<tr".($rowpair?" class='pair'":NULL).">" .
					"<td></td>" .
					"<td colspan='7' class='auto-width nopadding'>";
		} else {
			echo
				"<form action='".url::uri('edit, delete')."' method='post'>";
		}
				
		echo "<table cellpadding='0' cellspacing='0' class='list'>";
		
		if (!isset($rowpair)) {
			echo
				"<thead>" .
				"<tr class='lheader'>";
			
			$this->displayAdminListHeader();
			$this->displayAdminListHeaderOptions();
					
			if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
				$this->displayAdminListHeaderFunctions();
					
			echo
				"</tr>" .
				"</thead>" .
				"<tbody>";
		}
		
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
			
			$subrows = sql::run(
				" SELECT * FROM `{shoppings}`" .
				" WHERE `SubCategoryOfID` = '".$row['ID']."'" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				" ORDER BY `OrderID`, `ID`");
			
			if (sql::rows($subrows))
				$this->displayAdminList($subrows, $i%2);
			
			$i++;
		}
		
		if (isset($rowpair)) {
			echo 
				"</table>" .
				"</td>" .
				"</tr>";
		} else {
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
			
		return true;
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			_('Shopping Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		if (modules::displayAdmin())
			return;
		
		$this->displayAdminTitle();
		$this->displayAdminDescription();
			
		$edit = null;
		$id = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		echo
			"<div class='admin-content'>";
				
		$form = new form(
				($edit?
					_("Edit Category"):
					_("New Category")),
				'neweditcategory');
		
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
		
		foreach(shopping::getTree() as $row) {
			$form->addValue('SubCategoryOfID',
				$row['ID'], 
				($row['SubItemOfID']?
					str_replace(' ', '&nbsp;', 
						str_pad('', $row['PathDeepnes']*4, ' ')).
					"|- ":
					null) .
				$row['Title']);
		}
		
		$rows = sql::run(
			" SELECT * FROM `{shoppings}`" .
			" WHERE !`SubCategoryOfID`" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			" ORDER BY `OrderID`, `ID`");
		
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
					_("No categories found."),
					TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{shoppings}` " .
					" WHERE `ID` = '".$id."'" .
					($this->userPermissionIDs?
						" AND `ID` IN (".$this->userPermissionIDs.")":
						null)));
				
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
		
		if ($values['OrderID'] == '') {
			$row = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{shoppings}` " .
				" WHERE `SubCategoryOfID` = '".(int)$values['SubCategoryOfID']."'" .
				" ORDER BY `OrderID` DESC"));
			
			$values['OrderID'] = (int)$row['OrderID']+1;
			
		} else {
			sql::run(
				" UPDATE `{shoppings}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `SubCategoryOfID` = '".(int)$values['SubCategoryOfID']."'" .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		if ((int)$values['SubCategoryOfID']) {
			$parentcategory = sql::fetch(sql::run(
				" SELECT * FROM `{shoppings}`" .
				" WHERE `ID` = '".(int)$values['SubCategoryOfID']."'"));
			
			if ($parentcategory['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;
			
			if (JCORE_VERSION >= '0.5') {
				if ($parentcategory['MembersOnly'] && !$values['MembersOnly'])
					$values['MembersOnly'] = true;
				
				if ($parentcategory['ShowToGuests'] && !$values['ShowToGuests'])
					$values['ShowToGuests'] = true;
			}
		}
		
		$newid = sql::run(
			" INSERT INTO `{shoppings}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Description` = '".
				sql::escape($values['Description'])."'," .
			" `TimeStamp` = " .
				($values['TimeStamp']?
					"'".sql::escape($values['TimeStamp'])."'":
					"NOW()").
				"," .
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			(JCORE_VERSION >= '0.6'?
				" `URL` = '".
					sql::escape($values['URL'])."'," .
				" `FullItems` = '".
					(int)$values['FullItems']."',":
				null) .
			(JCORE_VERSION >= '0.7'?
				" `HideSubgroupItems` = '".
					(int)$values['HideSubgroupItems']."',":
				null) .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `SubCategoryOfID` = '".
				(int)$values['SubCategoryOfID']."'," .
			(JCORE_VERSION >= '0.5'?
				" `MembersOnly` = '".
					(int)$values['MembersOnly']."'," .
				" `ShowToGuests` = '".
					(int)$values['ShowToGuests']."'," .
				" `DigitalGoods` = '".
					(int)$values['DigitalGoods']."'," .
				" `DigitalGoodsExpiration` = '".
					(int)$values['DigitalGoodsExpiration']."',":
				null) .
			(JCORE_VERSION >= '0.7'?
				" `DisplayIcons` = '".
					(int)$values['DisplayIcons']."',":
				null) .
			" `Limit` = '".
				(int)$values['Limit']."'," .
			" `UserID` = '".
				(int)$GLOBALS['USER']->data['ID']."'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
		
		if (!$newid) {
			tooltip::display(
				sprintf(_("Shopping Category couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (JCORE_VERSION >= '0.5')
			$this->protectFiles();
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		$category = sql::fetch(sql::run(
			" SELECT * FROM `{shoppings}`" .
			" WHERE `ID` = '".$id."'"));
			
		if ((int)$values['SubCategoryOfID'] &&
			(int)$values['SubCategoryOfID'] != $category['SubCategoryOfID']) 
		{
			$parentcategory = sql::fetch(sql::run(
				" SELECT * FROM `{shoppings}`" .
				" WHERE `ID` = '".(int)$values['SubCategoryOfID']."'"));
			
			if ($parentcategory['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;
			
			if (JCORE_VERSION >= '0.5') {
				if ($parentcategory['MembersOnly'] && !$values['MembersOnly'])
					$values['MembersOnly'] = true;
				
				if ($parentcategory['ShowToGuests'] && !$values['ShowToGuests'])
					$values['ShowToGuests'] = true;
			}
		}
		
		sql::run(
			" UPDATE `{shoppings}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Description` = '".
				sql::escape($values['Description'])."'," .
			" `TimeStamp` = " .
				($values['TimeStamp']?
					"'".sql::escape($values['TimeStamp'])."'":
					"NOW()").
				"," .
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			(JCORE_VERSION >= '0.6'?
				" `URL` = '".
					sql::escape($values['URL'])."'," .
				" `FullItems` = '".
					(int)$values['FullItems']."',":
				null) .
			(JCORE_VERSION >= '0.7'?
				" `HideSubgroupItems` = '".
					(int)$values['HideSubgroupItems']."',":
				null) .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `SubCategoryOfID` = '".
				(int)$values['SubCategoryOfID']."'," .
			(JCORE_VERSION >= '0.5'?
				" `MembersOnly` = '".
					(int)$values['MembersOnly']."'," .
				" `ShowToGuests` = '".
					(int)$values['ShowToGuests']."'," .
				" `DigitalGoods` = '".
					(int)$values['DigitalGoods']."'," .
				" `DigitalGoodsExpiration` = '".
					(int)$values['DigitalGoodsExpiration']."',":
				null) .
			(JCORE_VERSION >= '0.7'?
				" `DisplayIcons` = '".
					(int)$values['DisplayIcons']."',":
				null) .
			" `Limit` = '".
				(int)$values['Limit']."'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(_("Shopping Category couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		foreach(shopping::getTree((int)$id) as $row) {
			$updatesql = null;
			
			if (($category['Deactivated'] && !$values['Deactivated']) ||
				(!$category['Deactivated'] && $values['Deactivated'])) 
			{
				if (!$row['Deactivated'] && $values['Deactivated'])
					$updatesql[] = " `Deactivated` = 1";
				if ($row['Deactivated'] && !$values['Deactivated'])
					$updatesql[] = " `Deactivated` = 0";
			}
			
			if (JCORE_VERSION >= '0.5') {
				if (($category['MembersOnly'] && !$values['MembersOnly']) ||
					(!$category['MembersOnly'] && $values['MembersOnly'])) 
				{
					if (!$row['MembersOnly'] && $values['MembersOnly'])
						$updatesql[] = " `MembersOnly` = 1";
					if ($row['MembersOnly'] && !$values['MembersOnly'])
						$updatesql[] = " `MembersOnly` = 0";
				}
				
				if (($category['ShowToGuests'] && !$values['ShowToGuests']) ||
					(!$category['ShowToGuests'] && $values['ShowToGuests'])) 
				{
					if (!$row['ShowToGuests'] && $values['ShowToGuests'])
						$updatesql[] = " `ShowToGuests` = 1";
					if ($row['ShowToGuests'] && !$values['ShowToGuests'])
						$updatesql[] = " `ShowToGuests` = 0";
				}
			}
			
			if ($updatesql)
				sql::run(
					" UPDATE `{shoppings}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
		}
		
		foreach(shopping::getBackTraceTree((int)$id) as $row) {
			$updatesql = null;
			
			if ($row['Deactivated'] && !$values['Deactivated'])
				$updatesql[] = " `Deactivated` = 0";
			
			if (JCORE_VERSION >= '0.5') {
				if ($row['MembersOnly'] && !$values['MembersOnly'])
					$updatesql[] = " `MembersOnly` = 0";
			}
			
			if ($updatesql)
				sql::run(
					" UPDATE `{shoppings}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
		}

		if (JCORE_VERSION >= '0.5')
			$this->protectFiles();
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		$shoppingitems = new shoppingItems();
		$categoryids = array($id);
		
		foreach(shopping::getTree((int)$id) as $row)
			$categoryids[] = $row['ID'];
		
		
		foreach($categoryids as $categoryid) {
			$rows = sql::run(
				" SELECT * FROM `{shoppingitems}` " .
				" WHERE `ShoppingID` = '".$categoryid."'");
			
			while($row = sql::fetch($rows))
				$shoppingitems->delete($row['ID']);
			
			sql::run(
				" DELETE FROM `{shoppings}` " .
				" WHERE `ID` = '".(int)$id."'");
		}
		
		unset($shoppingitems);
		
		if (JCORE_VERSION >= '0.6') {
			$icons = new shoppingIcons();
			
			$rows = sql::run(
				" SELECT * FROM `{shoppingicons}`" .
				" WHERE `ShoppingID` = '".$id."'");
			
			while($row = sql::fetch($rows))
				$icons->delete($row['ID']);
			
			unset($icons);
		}
		
		if (JCORE_VERSION >= '0.5')
			$this->protectFiles();
		
		return true;
	}
	
	function protectFiles() {
		if (!$this->attachmentsPath && !$this->picturesPath && 
			!$this->digitalGoodsPath)
			return false;
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows` FROM `{shoppings}` " .
			" WHERE `MembersOnly` = 1" .
			" LIMIT 1"));
			
		if (!files::exists($this->digitalGoodsPath.'.htaccess') &&
			!files::create($this->digitalGoodsPath.'.htaccess',
				'deny from all'))
		{
			tooltip::display(
				_("Directory couldn't be protected!")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					$this->digitalGoodsPath),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if ($row['Rows']) {
			if (!files::exists($this->attachmentsPath.'.htaccess') &&
				!files::create($this->attachmentsPath.'.htaccess',
					'deny from all'))
			{
				tooltip::display(
					_("Directory couldn't be protected!")." " .
					sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
						$this->attachmentsPath),
					TOOLTIP_ERROR);
				
				return false;
			}
			
			if (!files::exists($this->picturesPath.'.htaccess') &&
				!files::create($this->picturesPath.'.htaccess',
					'deny from all'))
			{
				tooltip::display(
					_("Directory couldn't be protected!")." " .
					sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
						$this->picturesPath),
					TOOLTIP_ERROR);
				
				return false;
			}
			
			if (!files::exists($this->thumbnailsPath.'.htaccess') &&
				!files::create($this->thumbnailsPath.'.htaccess',
					'allow from all'))
			{
				tooltip::display(
					_("Directory couldn't be protected!")." " .
					sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
						$this->thumbnailsPath),
					TOOLTIP_ERROR);
				
				return false;
			}
			
			return true;
		}
		
		if (files::exists($this->attachmentsPath.'.htaccess'))
			files::delete($this->attachmentsPath.'.htaccess');
		
		if (files::exists($this->picturesPath.'.htaccess'))
			files::delete($this->picturesPath.'.htaccess');
		
		if (files::exists($this->thumbnailsPath.'.htaccess'))
			files::delete($this->thumbnailsPath.'.htaccess');
			
		return true;
	}
	
	static function getTree($categoryid = 0, $firstcall = true,
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0)) 
	{
		$rows = sql::run(
			" SELECT *, `SubCategoryOfID` AS `SubItemOfID` " .
			" FROM `{shoppings}` " .
			($categoryid?
				" WHERE `SubCategoryOfID` = '".$categoryid."'":
				" WHERE !`SubCategoryOfID`") .
			" ORDER BY `OrderID`, `ID`");
		
		while($row = sql::fetch($rows)) {
			$row['PathDeepnes'] = $tree['PathDeepnes'];
			$tree['Tree'][] = $row;
			
			$tree['PathDeepnes']++;
			shopping::getTree($row['ID'], false, $tree);
			$tree['PathDeepnes']--;
		}
		
		if ($firstcall)
			return $tree['Tree'];
	}
	
	static function getBackTraceTree($id, $firstcall = true,
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0)) 
	{
		if (!(int)$id)
			return array();
		
		$row = sql::fetch(sql::run(
			" SELECT *, `SubCategoryOfID` AS `SubItemOfID` " .
			" FROM `{shoppings}` " .
			" WHERE `ID` = '".(int)$id."'"));
		
		if (!$row)
			return array();
		
		if ($row['SubItemOfID'])	
			shopping::getBackTraceTree($row['SubItemOfID'], false, $tree);
		
		$row['PathDeepnes'] = $tree['PathDeepnes'];
		$tree['Tree'][] = $row;
		$tree['PathDeepnes']++;
		
		if ($firstcall)
			return $tree['Tree'];
	}
	
	// ************************************************   Client Part
	static function getURL($id = 0) {
		$url = modules::getOwnerURL('shopping', $id);
		
		if (!$url)
			return url::site().'?';
		
		return $url;	
	}
	
	static function verifyPermission($row) {
		if (!$row)
			return true;
			
		if ($GLOBALS['USER']->loginok)
			return true;
		
		if ($row['MembersOnly'] && !$row['ShowToGuests'])
			return false;
		
		return true;
	}
	
	static function getOptions() {
		return
			array(
				array(
					'ID' => -1,
					'Title' => '* '._('Latest Items'),
					'Path' => '',
					'SubItemOfID' => 0,
					'PathDeepnes' => 0),
				array(
					'ID' => -2,
					'Title' => '* '._('Active Items'),
					'Path' => '',
					'SubItemOfID' => 0,
					'PathDeepnes' => 0),
				array(
					'ID' => -3,
					'Title' => '* '._('Popular Items'),
					'Path' => '',
					'SubItemOfID' => 0,
					'PathDeepnes' => 0),
				array(
					'ID' => -4,
					'Title' => '* '._('Discussed Items'),
					'Path' => '',
					'SubItemOfID' => 0,
					'PathDeepnes' => 0),
				array(
					'ID' => -5,
					'Title' => '* '._('Rated Items'),
					'Path' => '',
					'SubItemOfID' => 0,
					'PathDeepnes' => 0));
	}
	
	function setOption($optionid) {
		switch ($optionid) {
			case -1:
				$this->latestItems = true;
				break;
			case -2:
				$this->activeItems = true;
				break;
			case -3:
				$this->popularItems = true;
				break;
			case -4:
				$this->discussedItems = true;
				break;
			case -5:
				$this->ratedItems = true;
				break;
		}
	}
	
	static function constructPrice($price) {
		if (!defined('SHOPPING_CART_CURRENCY') || !SHOPPING_CART_CURRENCY)
			return number_format($price, 2);
		
		if (defined('SHOPPING_CART_CURRENCY_POSITION') && SHOPPING_CART_CURRENCY_POSITION &&
			stristr(SHOPPING_CART_CURRENCY_POSITION, 'right'))
			return 
				number_format($price, 2) .
				"<span class='shopping-currency'>" .
					SHOPPING_CART_CURRENCY .
				"</span>";
		
		return 
				"<span class='shopping-currency'>" .
					SHOPPING_CART_CURRENCY .
				"</span>" .
				number_format($price, 2);
	} 
	
	static function displayPrice($price) {
		echo shopping::constructPrice($price);
	}
	
	function displayLogin() {
		tooltip::display(
			_("This area is limited to members only. " .
				"Please login below."),
			TOOLTIP_NOTIFICATION);
		
		$GLOBALS['USER']->displayLogin();
	}
	
	function displayIcon(&$row) {
		if (JCORE_VERSION >= '0.6' && $row['Icons']) {
			echo
				"<div class='shopping-category-icon icon'>";
		
			$icons = new shoppingIcons();
			$icons->selectedOwnerID = $row['ID'];
			$icons->limit = 1;
			$icons->showPaging = false;
			
			if ($row['URL'])
				$icons->customLink = url::generateLink($row['URL']);
			elseif (isset($row['_Link']))
				$icons->customLink = $row['_Link'];
			
			$icons->display();
			unset($icons);
			
			echo
				"</div>";
		
			return;
		}
		
		echo
			"<a href='" .
				(JCORE_VERSION >= '0.6' && $row['URL']?
					url::generateLink($row['URL']):
					$row['_Link']) .
				"' " .
				"title='".htmlspecialchars($row['Title'], ENT_QUOTES)."' " .
				"class='shopping-category-icon" .
				($row['_SubCategories']?
					" subcategories":
					null) .
				"'>".
			"</a>";
	}
	
	function displayTitle(&$row) {
		echo
			"<a href='" .
				(JCORE_VERSION >= '0.6' && $row['URL']?
					url::generateLink($row['URL']):
					$row['_Link']) .
				"' " .
				"title='".htmlspecialchars($row['Title'], ENT_QUOTES)."'>" .
				$row['Title'] .
			"</a>";
	}
	
	function displaySelectedTitle(&$row) {
		echo
			"<a href='".url::uri(shoppingItems::$uriVariables)."'>" .
				_("Shopping").
			"</a>";
		
		foreach(shopping::getBackTraceTree($row['ID']) as $category) {
			$href = url::uri(shoppingItems::$uriVariables)."&amp;shoppingid=".$category['ID'];
			
			echo 
				"<span class='path-separator'> / </span>" .
				"<a href='".$href."'>".
					$category['Title'] .
				"</a>";
		}
	}
	
	function displayDetails(&$row) {
		$user = $GLOBALS['USER']->get($row['UserID']);
		
		echo
			calendar::datetime($row['TimeStamp'])." ";
					
		$GLOBALS['USER']->displayUserName($user, __('by %s'));
	}
	
	function displayDescription($row) {
		echo
			"<p>";
		
		$codes = new contentCodes();
		$codes->display(nl2br($row['Description']));
		unset($codes);
		
		echo
			"</p>";
	}
	
	function displaySubCategories(&$row) {
		$categories = sql::run(
			$this->SQL());
			
		if (sql::rows($categories)) {
			echo
				"<div class='shopping-categories'>";
			
			while ($category = sql::fetch($categories))
				$this->displayOne($category);
			
			echo
				"</div>";
		}
	}
	
	function displayItems(&$row) {
		$shoppingitems = new shoppingItems();
		
		if (isset($row['HideSubgroupItems']) && $row['HideSubgroupItems'])
			$shoppingitems->subgroupItems = false;
		
		$shoppingitems->selectedShoppingID = $row['ID'];
		$shoppingitems->limit = $row['Limit'];
		
		if ($this->limit)
			$shoppingitems->limit = $this->limit;
	
		if ($this->topItems)
			$shoppingitems->shoppingURL = shopping::getURL($row['ID']);
		
		$shoppingitems->ignorePaging = $this->ignorePaging;
		$shoppingitems->showPaging = $this->showPaging;
		$shoppingitems->ajaxPaging = $this->ajaxPaging;
		$shoppingitems->randomize = $this->randomizeItems;
		$shoppingitems->active = $this->activeItems;
		$shoppingitems->popular = $this->popularItems;
		$shoppingitems->discussed = $this->discussedItems;
		$shoppingitems->rated = $this->ratedItems;
		$shoppingitems->top = $this->topItems;
		
		if ($this->topItems) {
			if (!$shoppingitems->limit)
				$shoppingitems->limit = 10;
			
			$shoppingitems->showPaging = false;
			$shoppingitems->selectedID = null;
			$shoppingitems->search = null;
		}
	
		$shoppingitems->display();
		unset($shoppingitems);
	}
	
	function displayFunctions(&$row) {
		echo
			"<a href='" .
				(JCORE_VERSION >= '0.6' && $row['URL']?
					url::generateLink($row['URL']):
					$row['_Link']) .
				"' class='items comment'>" .
				"<span>".
				($row['_SubCategories']?
					_("Items / Categories"):
					_("Items")).
				"</span> " .
				"<span>" .
				"(".($row['Items']+$row['_SubCategories']).")" .
				"</span>" .
			"</a>";
	}
	
	function displayOne(&$row) {
		$row['_Link'] = $this->shoppingURL."&amp;shoppingid=".$row['ID'];
		
		$row['_SubCategories'] = sql::count(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{shoppings}`" .
			" WHERE !`Deactivated`" .
			" AND `SubCategoryOfID` = '".(int)$row['ID']."'");
		
		echo 
			"<div" .
				(JCORE_VERSION < '0.6'?
					" id='shopping".$row['ID']."'":
					null) .
				" class='shopping-category" .
				($row['SubCategoryOfID']?
					" shopping-sub-category":
					null) .
				($row['_SubCategories']?
					" shopping-has-sub-categories":
					null) .
				" shopping".$row['ID'] .
				" rounded-corners'>";
		
		$this->displayIcon($row);
		
		echo
				"<h3 class='shopping-category-title'>";
		
		$this->displayTitle($row);
		
		echo
				"</h3>" .
				"<div class='shopping-category-details comment'>";
		
		$this->displayDetails($row);
			
		echo
				"</div>";
		
		if ($row['Description']) {
			echo
				"<div class='shopping-category-description'>";
			
			$this->displayDescription($row);
			
			echo
				"</div>";
		}
		
		echo
				"<div class='shopping-category-links'>";
		
		$this->displayFunctions($row);
					
		echo
				"</div>" .
				"<div class='clear-both'></div>" .
			"</div>";
	}
	
	function displaySelected(&$row) {
		if (JCORE_VERSION >= '0.5' && !shopping::verifyPermission($row)) {
			$this->displayLogin();
			return false;
		}
		
		echo 
			"<div class='shopping shopping".$row['ID']."'>";
		
		if (!$this->topItems) {
			echo
				"<div class='shopping-category-selected'>" .
					"<h3 class='shopping-category-title'>";
		
			$this->displaySelectedTitle($row);
	
			echo
					"</h3>" .
					"<div class='shopping-category-details comment'>";
		
			$this->displayDetails($row);
			
			echo
					"</div>";
		
			if (JCORE_VERSION >= '0.7' && $row['DisplayIcons'] && $row['Icons'])
				$this->displayIcon($row);
			
			if ($row['Description']) {
				echo
					"<div class='shopping-category-description'>";
			
				$this->displayDescription($row);
			
				echo
					"</div>";
			}
		
			echo
				"</div>" .
				"<div class='clear-both'></div>";
		
			if (!$this->selectedItemID)
				$this->displaySubCategories($row);
		}
		
		$this->displayItems($row);
			
		echo 
			"<div class='clear-both'></div>" .
			"</div>"; //shopping
		
		return true;
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		if (preg_match('/(^|\/)rand($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)rand($|\/)/', '\2', $this->arguments);
			$this->randomizeItems = true;
		}
		
		if (preg_match('/(^|\/)latest($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)latest($|\/)/', '\2', $this->arguments);
			$this->ignorePaging = true;
			$this->showPaging = false;
			$this->limit = 1;
		}
		
		if (preg_match('/(^|\/)([0-9]+?)\/ajax($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/\/ajax/', '', $this->arguments);
			$this->ignorePaging = true;
			$this->ajaxPaging = true;
		}
		
		if (preg_match('/(^|\/)([0-9]+?)($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)[0-9]+?($|\/)/', '\2', $this->arguments);
			$this->limit = (int)$matches[2];
		}
		
		if (preg_match('/(^|\/)keywords($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)keywords($|\/)/', '\2', $this->arguments);
			
			$items = new shoppingItems();
			$items->shoppingURL = shopping::getURL();
			
			if (isset($matches[2]))
				$items->keywordsCloudLimit = (int)$matches[2];
			
			$items->displayKeywordsCloud();
			unset($items);
			
			return true;
		}
		
		if (preg_match('/(^|\/)active($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)active($|\/)/', '\2', $this->arguments);
			$this->activeItems = true;
		}
		
		if (preg_match('/(^|\/)popular($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)popular($|\/)/', '\2', $this->arguments);
			$this->popularItems = true;
		}
		
		if (preg_match('/(^|\/)discussed($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)discussed($|\/)/', '\2', $this->arguments);
			$this->discussedItems = true;
		}
		
		if (preg_match('/(^|\/)rated($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)rated($|\/)/', '\2', $this->arguments);
			$this->ratedItems = true;
		}
		
		if (preg_match('/(^|\/)top($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)top($|\/)/', '\2', $this->arguments);
			$this->topItems = true;
			$this->shoppingURL = shopping::getURL();
		}
		
		if (!$this->arguments && !(int)$this->selectedID)
			return false;
		
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{shoppings}` " .
			" WHERE !`Deactivated`" .
			((int)$this->selectedID?
				" AND `ID` = '".(int)$this->selectedID."'":
				" AND `Path` LIKE '".sql::escape($this->arguments)."'") .
			" ORDER BY `OrderID`, `ID`" .
			" LIMIT 1"));
		
		if (!$row)
			return true;
		
		$this->selectedID = $row['ID'];	
		$this->displaySelected($row);
		return true;
	}
	
	function displaySearch() {
		$shoppingitems = new shoppingItems();
		
		$shoppingitems->limit = $this->limit;
		$shoppingitems->search = $this->search;
		$shoppingitems->shoppingURL = shopping::getURL();
		
		ob_start();
		$itemsfound = $shoppingitems->display();
		$content = ob_get_contents();
		ob_end_clean();
		
		unset($shoppingitems);
	
		echo
			"<div class='shopping'>" .
			$content .
			"</div>";
		
		return $itemsfound;
	}
	
	function display() {
		if (!$this->shoppingURL)
			$this->shoppingURL = url::uri(shoppingItems::$uriVariables);
		
		if ($this->displayArguments())
			return true;
		
		if ((int)$this->selectedID) {
			$row = sql::fetch(sql::run(
				" SELECT * FROM `{shoppings}`" .
				" WHERE !`Deactivated`" .
				" AND `ID` = '".(int)$this->selectedID."'" .
				" LIMIT 1"));
				
			return $this->displaySelected($row);
		}
		
		if (!$this->limit && $this->owner['Limit'])
			$this->limit = $this->owner['Limit'];
			
		if ($this->search)
			return $this->displaySearch();
		
		$rows = sql::run(
			$this->SQL());
			
		$items = sql::rows($rows);
		if (!$items)
			return false;
			
		echo 
			"<div class='shopping'>";
		
		if (!$this->selectedItemID && !$this->topItems) {
			echo
				"<div class='shopping-categories'>";
			
			
			while($row = sql::fetch($rows))
				$this->displayOne($row);
			
			echo
				"</div>";
		}
		
		if (!$this->selectedID && $this->selectedItemID)
			$this->latestItems = true;
		
		if (JCORE_VERSION < '0.6' || $this->latestItems ||
			$this->activeItems || $this->popularItems ||
			$this->discussedItems || $this-> ratedItems ||
			$this->topItems)
		{	
			$shoppingitems = new shoppingItems();
			$shoppingitems->limit = $this->limit;
		
			if ($this->topItems)
				$shoppingitems->shoppingURL = shopping::getURL();
			
			$shoppingitems->randomize = $this->randomizeItems;
			$shoppingitems->active = $this->activeItems;
			$shoppingitems->popular = $this->popularItems;
			$shoppingitems->discussed = $this->discussedItems;
			$shoppingitems->rated = $this->ratedItems;
			
			if ($this->topItems) {
				if (!$shoppingitems->limit)
					$shoppingitems->limit = 10;
				
				$shoppingitems->showPaging = false;
				$shoppingitems->selectedID = null;
				$shoppingitems->search = null;
			}
		
			$shoppingitems->display();
			unset($shoppingitems);
		}
		
		echo 
			"</div>";
			
		return $items;
	}
}

?>