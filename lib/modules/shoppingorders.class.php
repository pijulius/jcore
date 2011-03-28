<?php

/***************************************************************************
 * 
 *  Name: Shopping Orders Module
 *  URI: http://jcore.net
 *  Description: Hande new/processed orders and display order status to the clients. Released under the GPL, LGPL, and MPL Licenses.
 *  Author: Istvan Petres
 *  Version: 0.8
 *  Tags: shopping orders module, gpl, lgpl, mpl
 * 
 ****************************************************************************/
 
include_once('lib/json.class.php');
include_once('lib/modules/shoppingcart.class.php');
include_once('lib/modules/shopping.class.php');

define('SHOPPING_ORDER_STATUS_NEW', 1);
define('SHOPPING_ORDER_STATUS_PROCESSING', 2);
define('SHOPPING_ORDER_STATUS_ACCEPTED', 3);
define('SHOPPING_ORDER_STATUS_CANCELLED', 4);
define('SHOPPING_ORDER_STATUS_REJECTED', 5);
define('SHOPPING_ORDER_STATUS_DELIVERED', 6);

define('SHOPPING_ORDER_PAYMENT_STATUS_PENDING', 1);
define('SHOPPING_ORDER_PAYMENT_STATUS_PAID', 2);
define('SHOPPING_ORDER_PAYMENT_STATUS_CANCELLED', 3);
define('SHOPPING_ORDER_PAYMENT_STATUS_FAILED', 4);
define('SHOPPING_ORDER_PAYMENT_STATUS_EXPIRED', 5);
define('SHOPPING_ORDER_PAYMENT_STATUS_PROCESSING', 6);

modules::register(
	'shoppingOrders', 
	_('Shopping Orders'),
	_('Handle New / Processed orders and display members their orders history'));

email::add('ShoppingOrder',
		"Your Order at %PAGE_TITLE%",
		"Dear %USERNAME%,\n\n" .
		"Thank you for your order! Your order information are listed below, " .
		"please keep it for your record.\n\n" .
		"Your Order/Tracking number is: %ORDERNUMBER%\n" .
		"Payment Status: %PAYMENTSTATUS%\n\n" .
		"%LINKTODIGITALGOODS%\n" .
		"%ORDERITEMS%\n\n\n" .
		"%ORDERFORM%\n\n\n" .
		"You can view your orders statuses and/or add further comments to " .
		"your order at:\n" .
		"%LINKTOORDERS%\n\n" .
		"Sincerely,\n" .
		"%PAGE_TITLE%");

email::add('ShoppingOrderToWebmaster',
		"New Order at %PAGE_TITLE%",
		"Dear Order Processor,\n\n" .
		"A new order has been placed on \"%PAGE_TITLE%\"!\n\n" .
		"Order/Tracking number: %ORDERNUMBER%\n" .
		"Payment Status: %PAYMENTSTATUS%\n" .
		"%PAYMENTSTATUSNOTE%\n\n\n" .
		"%ORDERITEMS%\n\n\n" .
		"%ORDERFORM%\n\n\n" .
		"You can view/process this order at:\n" .
		"%SITE_URL%admin/?path=admin/modules/shoppingorders&id=%ORDERID%\n\n" .
		"Sincerely,\n" .
		"%PAGE_TITLE%");

$GLOBALS['SHOPPING_ORDER_METHODS'] = array();

shoppingOrderMethods::add(
	'invoiceCustomer',
	_('Invoice Customer'),
	_('You will be contacted personally to finalize the payments'));
 
shoppingOrderMethods::add(
	'check',
	_('Check Payment'),
	_('Please complete the check to be sent to %s'));
 
shoppingOrderMethods::add(
	'payPal',
	_('Credit Card Payment (PayPal)'),
	_('An extra step will be required trough Paypal.com'));
 
shoppingOrderMethods::add(
	'ccBill',
	_('Credit Card Payment (CCBill)'),
	_('An extra step will be required trough CCBill.com'));
 
shoppingOrderMethods::add(
	'alertPay',
	_('Credit Card Payment (AlertPay)'),
	_('An extra step will be required trough AlertPay.com'));
 
shoppingOrderMethods::add(
	'authorizeDotNet',
	_('Credit Card Payment (Authorize.net)'),
	_('An extra step will be required trough Authorize.net'));
 
shoppingOrderMethods::add(
	'2CheckOut',
	_('Credit Card Payment (2CheckOut)'),
	_('An extra step will be required trough 2CheckOut.com'));
 
shoppingOrderMethods::add(
	'MoneyBookers',
	_('Credit Card Payment (MoneyBookers)'),
	_('An extra step will be required trough MoneyBookers.com'));
 
shoppingOrderMethods::add(
	'Ogone',
	_('Credit Card Payment (Ogone)'),
	_('An extra step will be required trough Ogone.com'));
 
class shoppingOrderMethodInvoiceCustomer extends form {
	var $postProcessText = null;
	var $processResult = null;
	
	function __construct() {
		parent::__construct(
			_('Invoice Customer'), 'invoicecustomer');
	}
	
	function process() {
		$this->processResult = 
			_("Please contact client to finalize the payments.");
		
		return SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
	}
	
	function postProcess($orderid) {
		return false;
	}
	
	function verify() {
		return true;
	}
	
	function setUp() {
	}
}

class shoppingOrderMethodCheck extends form {
	var $postProcessText = null;
	var $processResult = null;
	 
	function __construct() {
		parent::__construct(
			_('Check Payment'), 'checkpayment');
	}
	
	function process() {
		$this->processResult = 
			_("Name on Check").": ".
				$this->checkoutForm->get('NameOnCheck').
			"\n" .
			_("Check Number").": ".
				$this->checkoutForm->get('CheckNumber').
			"\n" .
			_("Check Amount").": ".
				$this->checkoutForm->get('CheckAmount');
		
		return SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
	}
	
	function postProcess($orderid) {
		return false;
	}
	
	function verify() {
		return true;
	}
	
	function setUp() {
		$this->add(
			_('Name on Check'),
			'NameOnCheck',
			FORM_INPUT_TYPE_TEXT,
			true);
		$this->setStyle("width: 200px;");
		
		$this->add(
			_('Check Number'),
			'CheckNumber',
			FORM_INPUT_TYPE_TEXT,
			true);
		$this->setStyle("width: 200px;");
		
		$this->add(
			_('Check Amount'),
			'CheckAmount',
			FORM_INPUT_TYPE_TEXT,
			true);
		$this->setStyle("width: 100px;");
	}
}

class shoppingOrderMethodPayPal extends form {
	var $postProcessText = null;
	var $processResult = null;
	var $ajaxRequest = null;
	
	function __construct() {
		parent::__construct(
			_('PayPal'), 'paypal');
		
		$this->postProcessText = 			
			_("Your order has been successfully created but your payment has not been processed yet. " .
				"To finalize your payments please click on the button below.");
	}
	
	function process() {
		$this->processResult = 
			_("User redirected to paypal, payment is being processed.");
		
		return SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
	}
	
	function postProcess($orderid) {
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `ID` = '".$orderid."'"));
		
		if (!$order)
			return false;
		
		$orderurl = shoppingOrders::getURL().
				"&shoppingorderid=".$order['ID'];
		
		$grandtotal = number_format($order['Subtotal']+
			(isset($order['Tax'])?$order['Tax']:0)-
			$order['Discount']+$order['Fee'], 2, '.', '');
		
		$items = array();
		$orderitems = sql::run(
			" SELECT * FROM `{shoppingorderitems}`" .
			" WHERE `ShoppingOrderID` = '".$order['ID']."'");
		
		while($orderitem = sql::fetch($orderitems)) {
			$item = sql::fetch(sql::run(
				" SELECT `RefNumber` FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$orderitem['ShoppingItemID']."'"));
			
			$items[] = $item['RefNumber'];
		}
		
		tooltip::display(
			//"<form action='https://www.sandbox.paypal.com/cgi-bin/webscr' method='post' id='shoppingordermethodpaypalform'>" .
			"<form action='https://www.paypal.com/cgi-bin/webscr' method='post' id='shoppingordermethodpaypalform'>" .
			"<input type='hidden' name='business' value='".SHOPPING_CART_ORDER_METHOD_PAYPAL_ID."' />" .
			"<input type='hidden' name='cmd' value='_xclick' />" .
			"<input type='hidden' name='item_name' value='Checkout for ".htmlspecialchars(PAGE_TITLE, ENT_QUOTES)."' />" . 
			"<input type='hidden' name='item_number' value='".$order['OrderID']."' />" .
			"<input type='hidden' name='on0' value='Items' />" . 
			"<input type='hidden' name='os0' value='".htmlspecialchars(implode('; ', $items), ENT_QUOTES)."' />" . 
			"<input type='hidden' name='invoice' value='".$order['OrderID']."' />" .
			"<input type='hidden' name='currency_code' value='".SHOPPING_CART_ORDER_METHOD_PAYPAL_CURRENCY."' />" . 
			"<input type='hidden' name='amount' value='".$grandtotal."' />" .
			"<input type='hidden' name='return' value='".$orderurl."' />" .
			"<input type='hidden' name='cancel_return' value='".$orderurl."' />" . 
			"<input type='hidden' name='notify_url' value='".SITE_URL."index.php?request=modules/shoppingorders/shoppingordermethodpaypal&ajax=1' />" .
			$this->postProcessText .
			"<br /><br />" .
			"<input type='submit' name='submitorder' value='".
				htmlspecialchars(_("Click to Finalize Payments"), ENT_QUOTES)."' " .
				"class='button submit' />" .
			"</form>", 
			TOOLTIP_NOTIFICATION);
		
		return true;
	}
	
	function ipnProcess() {
		if (!isset($_POST['invoice']) || !isset($_POST['txn_id']) ||
			!$_POST['invoice'] || !$_POST['txn_id'])
			exit("Invalid IPN request!");
		
		$grandtotal = $_POST['mc_gross'];
		$ordernumber = $_POST['invoice'];
		$ordertransactionid = $_POST['txn_id'];
		$orderemail = $_POST['payer_email'];
		$paymentstatus = $_POST['payment_status'];
		$paymenttype = $_POST['payment_type'];
		
		$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
		
		if (in_array($paymentstatus, array(
			'Completed',
			'Canceled_Reversal'))) 
		{
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PAID;
			
		} elseif (in_array($paymentstatus, array(
			'Refunded',
			'Reversed',
			'Voided')))
		{
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_CANCELLED;
			
		} elseif (in_array($paymentstatus, array(
			'Denied',
			'Failed')))
		{
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_FAILED;
			
		} elseif ($paymentstatus == 'Expired') {
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_EXPIRED;
			
		} elseif (in_array($paymentstatus, array(
			'Created', 
			'Pending',
			'Processed')))
		{
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PROCESSING;
		}
		
		// These are used for debugging
		$postgetarguments = "GET arguments:\n";
		foreach($_GET as $key => $value) {
			$postgetarguments .= $key."=".$value."\n";
   		}
		
		$postgetarguments .= "\nPOST arguments:\n";
		foreach($_POST as $key => $value) {
			$postgetarguments .= $key."=".$value."\n";
   		}
   		
		$email = new email();
		$email->load('WebmasterWarning');
	
		$email->to = WEBMASTER_EMAIL;
	
		$email->variables = array(
			'Warning' => $postgetarguments.
				"\nProcessing Order Payment\n"); 
		
		if (!$grandtotal) {
			$email->variables['Warning'] .= "FAILED: No Grand Total returned!\n";	
			$email->send();
	
			exit("FAILED: No Grand Total returned!");
		}
	
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `OrderID` = '".sql::escape($ordernumber)."'"));
		
		if (!$order) {
			$email->variables['Warning'] .= "FAILED: Order Number not found!\n";	
			$email->send();
			
			exit("FAILED: Order Number not found!");
		}
		
		$ordertotal = number_format($order['Subtotal']+
				(isset($order['Tax'])?$order['Tax']:0)-
				$order['Discount']+$order['Fee'], 2, '.', '');
		
		if ($grandtotal != $ordertotal) {
			$email->variables['Warning'] .= "FAILED: Grand Total returned (".
				$grandtotal.") doesn't mach Order's total (".$ordertotal.")!\n";	
			$email->send();
			
			exit("FAILED: Grand Total returned (".$grandtotal.") doesn't mach " .
				" Order's total (".$ordertotal.")!");
		}
		
		include_once('lib/phpbrowser.class.php');
		$browser = new phpBrowser();
		
		$_POST['cmd'] = '_notify-validate';
		$browser->submit(
			'https://www.paypal.com/cgi-bin/webscr',
			$_POST);
	
		if (!stristr($browser->results, 'VERIFIED')) {
			$email->variables['Warning'] .= "FAILED: " .
				"Order couldn't be verified by PayPal! ".
				$browser->results."\n";	
			$email->send();
		
			exit("FAILED: Order couldn't be verified by PayPal!");
		}
		
		unset($browser);
		
		$orderdetails = $order['OrderMethodDetails'];
		
		$orderdetails = 
			" - ".date('Y-m-d H:i:s')." - \n" .
			(!stristr($orderdetails, 'Transaction ID')?
				"Transaction ID: ".$ordertransactionid."\n" .
				"Payer Email: ".$orderemail."\n":
				null) .
			"Payment Status: ".$paymentstatus."\n" .
			"Payment Type: ".$paymenttype .
			($orderdetails?"\n\n".$orderdetails:null);
		
		sql::run(
			" UPDATE `{shoppingorders}` SET " .
			" `PaymentStatus` = '".
				$orderstatus."', " .
			" `OrderMethodDetails` = '".
				sql::escape($orderdetails)."', " .
			($orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PAID?
				" `TimeStamp` = NOW()":
				" `TimeStamp` = `TimeStamp`") .
			" WHERE `ID` = '".$order['ID']."'");
		
		shoppingOrders::sendNotificationEmails($order['ID']);
		
		if ($orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PAID ||
			$orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PROCESSING)
			users::activate($order['UserID']);
		
		exit("OK: Order successfully updated!");
	}
	
	function verify() {
		return true;
	}
	
	function setUp() {
	}
	
	function ajaxRequest() {
		$this->ipnProcess();
		return true;
	}
}

class shoppingOrderMethodCCBill extends form {
	var $postProcessText = null;
	var $processResult = null;
	var $ajaxRequest = null;
	 
	function __construct() {
		parent::__construct(
			_('CCBill'), 'ccbill');
		
		$this->postProcessText = 			
			_("Your order has been successfully created but your payment has not been processed yet. " .
				"To finalize your payments please click on the button below.");
	}
	
	function process() {
		$this->processResult = 
			_("User redirected to ccbill, payment is being processed."); 
		
		return SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
	}
	
	function postProcess($orderid) {
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `ID` = '".$orderid."'"));
		
		if (!$order)
			return false;
			
		$orderurl = shoppingOrders::getURL().
				"&shoppingorderid=".$order['ID'];
		
		$grandtotal = number_format($order['Subtotal']+
			(isset($order['Tax'])?$order['Tax']:0)-
			$order['Discount']+$order['Fee'], 2, '.', '');
		
		$items = array();
		$orderitems = sql::run(
			" SELECT * FROM `{shoppingorderitems}`" .
			" WHERE `ShoppingOrderID` = '".$order['ID']."'");
		
		while($orderitem = sql::fetch($orderitems)) {
			$item = sql::fetch(sql::run(
				" SELECT `RefNumber` FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$orderitem['ShoppingItemID']."'"));
			
			$items[] = $item['RefNumber'];
		}
		
		$formdiggest = md5(
			$grandtotal.'10'.
			SHOPPING_CART_ORDER_METHOD_CCBILL_CURRENCY_CODE.
			SHOPPING_CART_ORDER_METHOD_CCBILL_ENCRYPTION_KEY);
			
		tooltip::display(
			"<form action='https://bill.ccbill.com/jpost/signup.cgi' method='post' id='shoppingordermethodccbillform'>" .
			"<input type='hidden' name='clientAccnum' value='".SHOPPING_CART_ORDER_METHOD_CCBILL_ACCOUNT_NUMBER."' />" .
			"<input type='hidden' name='clientSubacc' value='".SHOPPING_CART_ORDER_METHOD_CCBILL_SUBACCOUNT_NUMBER."' />" .
			"<input type='hidden' name='formName' value='".SHOPPING_CART_ORDER_METHOD_CCBILL_FORM_ID."' />" .
			"<input type='hidden' name='orderid' value='".$order['OrderID']."' />" .
			"<input type='hidden' name='ordertitle' value='Checkout for ".htmlspecialchars(PAGE_TITLE, ENT_QUOTES)."' />" .
			"<input type='hidden' name='orderdetails' value='".htmlspecialchars(implode('; ', $items), ENT_QUOTES)."' />" .
			"<input type='hidden' name='formPrice' value='".$grandtotal."' />" .
			"<input type='hidden' name='formPeriod' value='10' />" .
			"<input type='hidden' name='formDigest' value='".$formdiggest."' />" .
			"<input type='hidden' name='currencyCode' value='".SHOPPING_CART_ORDER_METHOD_CCBILL_CURRENCY_CODE."' />" .
			$this->postProcessText .
			"<br /><br />" .
			"<input type='submit' name='submitorder' value='".
				htmlspecialchars(_("Click to Finalize Payments"), ENT_QUOTES)."' " .
				"class='button submit' />" .
			"</form>",
			TOOLTIP_NOTIFICATION);
			
		return true;
	}
	
	function ipnProcess() {
		if (!isset($_POST['orderid']) || !isset($_POST['subscription_id']) ||
			!isset($_POST['responseDigest']) || !$_POST['responseDigest'] ||
			!$_POST['orderid'] || !$_POST['subscription_id']) 
			exit("Invalid IPN request!");
		
		$grandtotal = $_POST['initialPrice'];
		$ordernumber = $_POST['orderid'];
		$ordertransactionid = $_POST['subscription_id'];
		$orderemail = $_POST['email'];
		$paymentdeclined = @$_POST['reasonForDeclineCode'];
		$paymentdeclinedmsg = @$_POST['reasonForDecline'];
		
		if ($paymentdeclined)
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_FAILED;
		else
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PAID;
		
		// These are used for debugging
		$postgetarguments = "GET arguments:\n";
		foreach($_GET as $key => $value) {
			$postgetarguments .= $key."=".$value."\n";
    	}
		
		$postgetarguments .= "\nPOST arguments:\n";
		foreach($_POST as $key => $value) {
			$postgetarguments .= $key."=".$value."\n";
    	}
    	
		$email = new email();
		$email->load('WebmasterWarning');
		
		$email->to = WEBMASTER_EMAIL;
		$email->variables = array(
			'Warning' => $postgetarguments.
				"\nProcessing Order Payment\n"); 
		
		if (!$grandtotal) {
			$email->variables['Warning'] .= "FAILED: No Grand Total returned!\n";	
			$email->send();
			
			exit("FAILED: No Grand Total returned!");
		}
		
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `OrderID` = '".sql::escape($ordernumber)."'"));
		
		if (!$order) {
			$email->variables['Warning'] .= "FAILED: Order Number not found!\n";	
			$email->send();
			
			exit("FAILED: Order Number not found!");
		}
		
		$ordertotal = number_format($order['Subtotal']+
				(isset($order['Tax'])?$order['Tax']:0)-
				$order['Discount']+$order['Fee'], 2, '.', '');
		
		if ($grandtotal != $ordertotal) {
			$email->variables['Warning'] .= "FAILED: Grand Total returned (".
				$grandtotal.") doesn't mach Order's total (".$ordertotal.")!\n";	
			$email->send();
		
			exit("FAILED: Grand Total returned (".$grandtotal.") doesn't mach " .
				" Order's total (".$ordertotal.")!");
		}
		
		$diggest = md5(
			$ordertotal.'10'.
			SHOPPING_CART_ORDER_METHOD_CCBILL_CURRENCY_CODE.
			SHOPPING_CART_ORDER_METHOD_CCBILL_ENCRYPTION_KEY);
		
		if ($diggest != $_POST['responseDigest']) {
			$email->variables['Warning'] .= "FAILED: Not a CCBill request!\n";	
			$email->send();
			
			exit("Not a CCBill request!");
		}
			
		$orderdetails = $order['OrderMethodDetails'];
		
		$orderdetails = 
			" - ".date('Y-m-d H:i:s')." - \n" .
			(!stristr($orderdetails, 'Transaction ID')?
				"Transaction ID: ".$ordertransactionid."\n" .
				"Payer Email: ".$orderemail."\n":
				null) .
			"Payment Status: ".
				($paymentdeclined?
					"Declined (".$paymentdeclinedmsg.")":
					"Approved") .
			($orderdetails?"\n\n".$orderdetails:null);
		
		sql::run(
			" UPDATE `{shoppingorders}` SET " .
			" `PaymentStatus` = '".
				$orderstatus."', " .
			" `OrderMethodDetails` = '".
				sql::escape($orderdetails)."', " .
			" `TimeStamp` = `TimeStamp` " .
			" WHERE `ID` = '".$order['ID']."'");
		
		shoppingOrders::sendNotificationEmails($order['ID']);
		
		users::activate($order['UserID']);
		exit("OK: Order successfully updated!");
	}
	
	function verify() {
		return true;
	}
	
	function setUp() {
	}
	
	function ajaxRequest() {
		$this->ipnProcess();
		return true;
	}
}

class shoppingOrderMethodAlertPay extends form {
	var $postProcessText = null;
	var $processResult = null;
	var $ajaxRequest = null;
	 
	function __construct() {
		parent::__construct(
			_('AlertPay'), 'alertpay');
		
		$this->postProcessText = 			
			_("Your order has been successfully created but your payment has not been processed yet. " .
				"To finalize your payments please click on the button below.");
	}
	
	function process() {
		$this->processResult = 
			_("User redirected to alertpay, payment is being processed."); 
		
		return SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
	}
	
	function postProcess($orderid) {
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `ID` = '".$orderid."'"));
		
		if (!$order)
			return false;
			
		$orderurl = shoppingOrders::getURL().
				"&shoppingorderid=".$order['ID'];
		
		$grandtotal = number_format($order['Subtotal']+
			(isset($order['Tax'])?$order['Tax']:0)-
			$order['Discount']+$order['Fee'], 2, '.', '');
		
		$items = array();
		$orderitems = sql::run(
			" SELECT * FROM `{shoppingorderitems}`" .
			" WHERE `ShoppingOrderID` = '".$order['ID']."'");
		
		while($orderitem = sql::fetch($orderitems)) {
			$item = sql::fetch(sql::run(
				" SELECT `RefNumber` FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$orderitem['ShoppingItemID']."'"));
			
			$items[] = $item['RefNumber'];
		}
		
		tooltip::display(
			"<form action='https://www.alertpay.com/PayProcess.aspx' method='post' id='shoppingordermethodalertpayform'>" .
			"<input type='hidden' name='ap_purchasetype' value='Item' />" .
			"<input type='hidden' name='ap_merchant' value='".SHOPPING_CART_ORDER_METHOD_ALERTPAY_ID."' />" .
			"<input type='hidden' name='ap_itemname' value='Checkout for ".htmlspecialchars(PAGE_TITLE, ENT_QUOTES)."' />" .
			"<input type='hidden' name='ap_currency' value='".SHOPPING_CART_ORDER_METHOD_ALERTPAY_CURRENCY."' />" .
			"<input type='hidden' name='ap_itemcode' value='".$order['OrderID']."' />" .
			"<input type='hidden' name='ap_quantity' value='1' />" .
			"<input type='hidden' name='ap_description' value='".htmlspecialchars(implode('; ', $items), ENT_QUOTES)."' />" .
			"<input type='hidden' name='ap_amount' value='".$grandtotal."' />" .
			"<input type='hidden' name='ap_returnurl' value='".$orderurl."' />" .
			"<input type='hidden' name='ap_cancelurl' value='".$orderurl."' />" .
			"<input type='hidden' name='apc_1' value='0' />" .
			"<input type='hidden' name='apc_2' value='0' />" .
			"<input type='hidden' name='apc_3' value='0' />" .
			$this->postProcessText .
			"<br /><br />" .
			"<input type='submit' name='submitorder' value='".
				htmlspecialchars(_("Click to Finalize Payments"), ENT_QUOTES)."' " .
				"class='button submit' />" .
			"</form>",
			TOOLTIP_NOTIFICATION);
		
		return true;
	}
	
	function ipnProcess() {
		if (!isset($_POST['ap_securitycode']) || !isset($_POST['ap_itemcode']) ||
			!$_POST['ap_securitycode'] || !$_POST['ap_itemcode'])
			exit("Invalid IPN request!");
		
		$securitycode = $_POST['ap_securitycode'];
		$grandtotal = $_POST['ap_totalamount'];
		$ordernumber = $_POST['ap_itemcode'];
		$ordertransactionid = $_POST['ap_referencenumber'];
		$orderemail = $_POST['ap_custemailaddress'];
		$paymentstatus = $_POST['ap_status'];
		
		$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
		
		if (stristr($paymentstatus, 'Success'))
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PAID;
		elseif (stristr($paymentstatus, 'Canceled'))
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_CANCELLED;
		elseif (stristr($paymentstatus, 'Failed'))
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_FAILED;
		elseif (stristr($paymentstatus, 'Expired'))
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_EXPIRED;
		elseif (stristr($paymentstatus, 'Rescheduled'))
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PROCESSING;
		
		// These are used for debugging
		$postgetarguments = "GET arguments:\n";
		foreach($_GET as $key => $value) {
			$postgetarguments .= $key."=".$value."\n";
   		}
		
		$postgetarguments .= "\nPOST arguments:\n";
		foreach($_POST as $key => $value) {
			$postgetarguments .= $key."=".$value."\n";
   		}
   		
		$email = new email();
		$email->load('WebmasterWarning');
	
		$email->to = WEBMASTER_EMAIL;
	
		$email->variables = array(
			'Warning' => $postgetarguments.
				"\nProcessing Order Payment\n"); 
		
		if ($securitycode != SHOPPING_CART_ORDER_METHOD_ALERTPAY_SECURITY_CODE) {
			$email->variables['Warning'] .= "FAILED: Invalid security code defined!\n";	
			$email->send();
	
			exit("FAILED: Invalid security code defined!");
		}
	
		if (!$grandtotal) {
			$email->variables['Warning'] .= "FAILED: No Grand Total returned!\n";	
			$email->send();
	
			exit("FAILED: No Grand Total returned!");
		}
	
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `OrderID` = '".sql::escape($ordernumber)."'"));
		
		if (!$order) {
			$email->variables['Warning'] .= "FAILED: Order Number not found!\n";	
			$email->send();
			
			exit("FAILED: Order Number not found!");
		}
		
		$ordertotal = number_format($order['Subtotal']+
				(isset($order['Tax'])?$order['Tax']:0)-
				$order['Discount']+$order['Fee'], 2, '.', '');
		
		if ($grandtotal != $ordertotal) {
			$email->variables['Warning'] .= "FAILED: Grand Total returned (".
				$grandtotal.") doesn't mach Order's total (".$ordertotal.")!\n";	
			$email->send();
			
			exit("FAILED: Grand Total returned (".$grandtotal.") doesn't mach " .
				" Order's total (".$ordertotal.")!");
		}
		
		$orderdetails = $order['OrderMethodDetails'];
		
		$orderdetails = 
			" - ".date('Y-m-d H:i:s')." - \n" .
			(!stristr($orderdetails, 'Transaction ID')?
				"Transaction ID: ".$ordertransactionid."\n" .
				"Payer Email: ".$orderemail."\n":
				null) .
			"Payment Status: ".$paymentstatus .
			($orderdetails?"\n\n".$orderdetails:null);
		
		sql::run(
			" UPDATE `{shoppingorders}` SET " .
			" `PaymentStatus` = '".
				$orderstatus."', " .
			" `OrderMethodDetails` = '".
				sql::escape($orderdetails)."', " .
			($orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PAID?
				" `TimeStamp` = NOW()":
				" `TimeStamp` = `TimeStamp`") .
			" WHERE `ID` = '".$order['ID']."'");
		
		shoppingOrders::sendNotificationEmails($order['ID']);
		
		if ($orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PAID ||
			$orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PROCESSING)
			users::activate($order['UserID']);
		
		exit("OK: Order successfully updated!");
	}
	
	function verify() {
		return true;
	}
	
	function setUp() {
	}
	
	function ajaxRequest() {
		$this->ipnProcess();
		return true;
	}
}

class shoppingOrderMethodAuthorizeDotNet extends form {
	var $postProcessText = null;
	var $processResult = null;
	var $ajaxRequest = null;
	
	function __construct() {
		parent::__construct(
			_('Authorize.net'), 'authorizenet');
		
		$this->postProcessText = 			
			_("Your order has been successfully created but your payment has not been processed yet. " .
				"To finalize your payments please click on the button below.");
	}
	
	function process() {
		$this->processResult = 
			_("User redirected to authorize.net, payment is being processed.");
		
		return SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
	}
	
	function postProcess($orderid) {
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `ID` = '".$orderid."'"));
		
		if (!$order)
			return false;
		
		$orderurl = shoppingOrders::getURL().
				"&shoppingorderid=".$order['ID'];
		
		$grandtotal = number_format($order['Subtotal']+
			(isset($order['Tax'])?$order['Tax']:0)-
			$order['Discount']+$order['Fee'], 2, '.', '');
		
		$items = array();
		$orderitems = sql::run(
			" SELECT * FROM `{shoppingorderitems}`" .
			" WHERE `ShoppingOrderID` = '".$order['ID']."'");
		
		while($orderitem = sql::fetch($orderitems)) {
			$item = sql::fetch(sql::run(
				" SELECT `RefNumber` FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$orderitem['ShoppingItemID']."'"));
			
			$items[] = $item['RefNumber'];
		}
		
		$fptime = time();
		$fpsequence = $order['OrderID'].$fptime;
		
		if (function_exists('hash_hmac'))
            $hash = hash_hmac("md5", SHOPPING_CART_ORDER_METHOD_AUTHORIZEDOTNET_API_LOGIN_ID . 
				"^".$fpsequence."^".$fptime."^".$grandtotal."^", 
				SHOPPING_CART_ORDER_METHOD_AUTHORIZEDOTNET_TRANSACTION_KEY);
		else
	        $hash = bin2hex(mhash(MHASH_MD5, SHOPPING_CART_ORDER_METHOD_AUTHORIZEDOTNET_API_LOGIN_ID . 
				"^".$fpsequence."^".$fptime."^".$grandtotal."^", 
				SHOPPING_CART_ORDER_METHOD_AUTHORIZEDOTNET_TRANSACTION_KEY));
		
		tooltip::display(
			"<form action='https://secure.authorize.net/gateway/transact.dll' method='post' id='shoppingordermethodauthorizedotnetform'>" .
			"<input type='hidden' name='x_login' value='".SHOPPING_CART_ORDER_METHOD_AUTHORIZEDOTNET_API_LOGIN_ID."' />" .
			"<input type='hidden' name='x_fp_hash' value='".$hash."' />" .
			"<input type='hidden' name='x_amount' value='".$grandtotal."' />" .
			"<input type='hidden' name='x_fp_timestamp' value='".$fptime."' />" .
			"<input type='hidden' name='x_description' value='Checkout for ".htmlspecialchars(PAGE_TITLE, ENT_QUOTES)."' />" . 
			"<input type='hidden' name='x_invoice_num' value='".$order['OrderID']."' />" .
			"<input type='hidden' name='x_fp_sequence' value='".$fpsequence."' />" .
			"<input type='hidden' name='x_version' value='3.1' />" .
			"<input type='hidden' name='x_show_form' value='payment_form' />" .
			"<input type='hidden' name='x_test_request' value='false' />" .
			"<input type='hidden' name='x_method' value='cc' />" .
			"<input type='hidden' name='x_relay_response' value='false' />" .
			"<input type='hidden' name='x_receipt_link_url' value='".$orderurl."' />" .
			"<input type='hidden' name='x_receipt_link_text' value='Back to ".htmlspecialchars(PAGE_TITLE, ENT_QUOTES)."' />" .
			"<input type='hidden' name='x_receipt_link_method' value='link' />" .
			$this->postProcessText .
			"<br /><br />" .
			"<input type='submit' name='submitorder' value='".
				htmlspecialchars(_("Click to Finalize Payments"), ENT_QUOTES)."' " .
				"class='button submit' />" .
			"</form>", 
			TOOLTIP_NOTIFICATION);
		
		return true;
	}
	
	function ipnProcess() {
		if (!isset($_POST['x_invoice_num']) || !isset($_POST['x_trans_id']) ||
			!isset($_POST['x_MD5_Hash']) || !$_POST['x_MD5_Hash'] ||
			!$_POST['x_invoice_num'] || !$_POST['x_trans_id'])
			exit("Invalid IPN request!");
		
		$grandtotal = $_POST['x_amount'];
		$ordernumber = $_POST['x_invoice_num'];
		$ordertransactionid = $_POST['x_trans_id'];
		$orderemail = $_POST['x_email'];
		$paymentstatus = $_POST['x_response_code'];
		$paymentstatusmsg = $_POST['x_response_reason_text'];
		
		$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
		
		if ($paymentstatus == 1)
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PAID;
		elseif ($paymentstatus == 2 || $paymentstatus == 3)
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_FAILED;
		elseif ($paymentstatus == 4)
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PROCESSING;
		
		// These are used for debugging
		$postgetarguments = "GET arguments:\n";
		foreach($_GET as $key => $value) {
			$postgetarguments .= $key."=".$value."\n";
   		}
		
		$postgetarguments .= "\nPOST arguments:\n";
		foreach($_POST as $key => $value) {
			$postgetarguments .= $key."=".$value."\n";
   		}
   		
		$email = new email();
		$email->load('WebmasterWarning');
	
		$email->to = WEBMASTER_EMAIL;
	
		$email->variables = array(
			'Warning' => $postgetarguments.
				"\nProcessing Order Payment\n"); 
		
		if (!$grandtotal) {
			$email->variables['Warning'] .= "FAILED: No Grand Total returned!\n";	
			$email->send();
	
			exit("FAILED: No Grand Total returned!");
		}
	
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `OrderID` = '".sql::escape($ordernumber)."'"));
		
		if (!$order) {
			$email->variables['Warning'] .= "FAILED: Order Number not found!\n";	
			$email->send();
			
			exit("FAILED: Order Number not found!");
		}
		
		$ordertotal = number_format($order['Subtotal']+
				(isset($order['Tax'])?$order['Tax']:0)-
				$order['Discount']+$order['Fee'], 2, '.', '');
		
		if ($grandtotal != $ordertotal) {
			$email->variables['Warning'] .= "FAILED: Grand Total returned (".
				$grandtotal.") doesn't mach Order's total (".$ordertotal.")!\n";	
			$email->send();
			
			exit("FAILED: Grand Total returned (".$grandtotal.") doesn't mach " .
				" Order's total (".$ordertotal.")!");
		}
		
		$fptime = time();
		$fpsequence = $order['OrderID'].$fptime;
		$md5 = strtoupper(md5(SHOPPING_CART_ORDER_METHOD_AUTHORIZEDOTNET_API_LOGIN_ID .
			SHOPPING_CART_ORDER_METHOD_AUTHORIZEDOTNET_API_LOGIN_ID .
			$ordertransactionid.$ordertotal));
		
		if ($md5 != $_POST['x_MD5_Hash']) {
			$email->variables['Warning'] .= "FAILED: Not an Authorize.net request!\n";	
			$email->send();
			
			exit("Not an Authorize.net request!");
		}
		
		$orderdetails = $order['OrderMethodDetails'];
		
		$orderdetails = 
			" - ".date('Y-m-d H:i:s')." - \n" .
			(!stristr($orderdetails, 'Transaction ID')?
				"Transaction ID: ".$ordertransactionid."\n" .
				"Payer Email: ".$orderemail."\n":
				null) .
			"Payment Status: ".$paymentstatusmsg." (".$paymentstatus.")" .
			($orderdetails?"\n\n".$orderdetails:null);
		
		sql::run(
			" UPDATE `{shoppingorders}` SET " .
			" `PaymentStatus` = '".
				$orderstatus."', " .
			" `OrderMethodDetails` = '".
				sql::escape($orderdetails)."', " .
			($orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PAID?
				" `TimeStamp` = NOW()":
				" `TimeStamp` = `TimeStamp`") .
			" WHERE `ID` = '".$order['ID']."'");
		
		shoppingOrders::sendNotificationEmails($order['ID']);
		
		if ($orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PAID ||
			$orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PROCESSING)
			users::activate($order['UserID']);
		
		exit("OK: Order successfully updated!");
	}
	
	function verify() {
		return true;
	}
	
	function setUp() {
	}
	
	function ajaxRequest() {
		$this->ipnProcess();
		return true;
	}
}

class shoppingOrderMethod2CheckOut extends form {
	var $postProcessText = null;
	var $processResult = null;
	var $ajaxRequest = null;
	
	function __construct() {
		parent::__construct(
			_('2CheckOut'), '2checkout');
		
		$this->postProcessText = 			
			_("Your order has been successfully created but your payment has not been processed yet. " .
				"To finalize your payments please click on the button below.");
	}
	
	function process() {
		$this->processResult = 
			_("User redirected to 2checkout, payment is being processed.");
		
		return SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
	}
	
	function postProcess($orderid) {
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `ID` = '".$orderid."'"));
		
		if (!$order)
			return false;
		
		$orderurl = shoppingOrders::getURL().
				"&shoppingorderid=".$order['ID'];
		
		$grandtotal = number_format($order['Subtotal']+
			(isset($order['Tax'])?$order['Tax']:0)-
			$order['Discount']+$order['Fee'], 2, '.', '');
		
		$items = array();
		$orderitems = sql::run(
			" SELECT * FROM `{shoppingorderitems}`" .
			" WHERE `ShoppingOrderID` = '".$order['ID']."'");
		
		while($orderitem = sql::fetch($orderitems)) {
			$item = sql::fetch(sql::run(
				" SELECT `RefNumber` FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$orderitem['ShoppingItemID']."'"));
			
			$items[] = $item['RefNumber'];
		}
		
		tooltip::display(
			"<form action='https://www.2checkout.com/checkout/purchase' method='post' id='shoppingordermethod2checkoutform'>" .
			"<input type='hidden' name='sid' value='".SHOPPING_CART_ORDER_METHOD_2CHECKOUT_VENDOR_ID."' />" .
			"<input type='hidden' name='tco_currency' value='".SHOPPING_CART_ORDER_METHOD_2CHECKOUT_CURRENCY."' />" .
			"<input type='hidden' name='total' value='".$grandtotal."' />" .
			"<input type='hidden' name='vendor_order_id' value='".$order['OrderID']."' />" .
			"<input type='hidden' name='id_type' value='1' />" .
			"<input type='hidden' name='c_prod' value='".$order['OrderID']."' />" . 
			"<input type='hidden' name='c_name' value='Checkout for ".htmlspecialchars(PAGE_TITLE, ENT_QUOTES)."' />" . 
			"<input type='hidden' name='c_description' value='".htmlspecialchars(implode('; ', $items), ENT_QUOTES)."' />" . 
			"<input type='hidden' name='c_price' value='".$grandtotal."' />" . 
			//"<input type='hidden' name='x_receipt_link_url' value='".SITE_URL."index.php?request=modules/shoppingorders/shoppingordermethod2checkout&ajax=1' />" .
			"<input type='hidden' name='return_url' value='".$orderurl."' />" .
			$this->postProcessText .
			"<br /><br />" .
			"<input type='submit' name='submitorder' value='".
				htmlspecialchars(_("Click to Finalize Payments"), ENT_QUOTES)."' " .
				"class='button submit' />" .
			"</form>", 
			TOOLTIP_NOTIFICATION);
		
		return true;
	}
	
	function ipnProcess() {
		if (!isset($_POST['sale_id']) || !isset($_POST['vendor_order_id']) ||
			!isset($_POST['md5_hash']) || !$_POST['md5_hash'] ||
			!$_POST['sale_id'] || !$_POST['vendor_order_id'])
			exit("Invalid IPN request!");
		
		$grandtotal = $_POST['invoice_list_amount'];
		$ordernumber = $_POST['vendor_order_id'];
		$ordertransactionid = $_POST['sale_id'];
		$orderemail = $_POST['customer_email'];
		$fraudstatus = @$_POST['fraud_status'];
		$paymentstatus = @$_POST['invoice_status'];
		$paymentstatustype = $_POST['message_type'];
		$paymentstatusmsg = $_POST['message_description'];
		
		$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
		
		if (in_array($paymentstatustype, array(
			'ORDER_CREATED', 
			'FRAUD_STATUS_CHANGED',
			'SHIP_STATUS_CHANGED',
			'INVOICE_STATUS_CHANGED')))
		{
			if (in_array($paymentstatus, array(
				'approved',
				'pending',
				'deposited')))
				$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PAID;
			elseif ($paymentstatus == 'declined')
				$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_FAILED;
			
			if ($fraudstatus && !SHOPPING_CART_ORDER_METHOD_2CHECKOUT_SKIP_FRAUD_CHECK) {
				if ($fraudstatus == 'pass')
					$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PAID;
				elseif ($fraudstatus == 'wait')
					$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PROCESSING;
				elseif ($fraudstatus == 'fail')
					$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_FAILED;
			}
			
		} elseif ($paymentstatustype == 'REFUND_ISSUED') {
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_CANCELLED;
			
		} elseif ($paymentstatustype == 'RECURRING_INSTALLMENT_SUCCESS') {
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PAID;
			
		} elseif ($paymentstatustype == 'RECURRING_INSTALLMENT_FAILED') {
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_FAILED;
			
		} elseif ($paymentstatustype == 'RECURRING_STOPPED' || 
			$paymentstatustype == 'RECURRING_COMPLETE') {
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_EXPIRED;
		
		} elseif ($paymentstatustype == 'RECURRING_RESTARTED') {
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PAID;
		}
		
		// These are used for debugging
		$postgetarguments = "GET arguments:\n";
		foreach($_GET as $key => $value) {
			$postgetarguments .= $key."=".$value."\n";
   		}
		
		$postgetarguments .= "\nPOST arguments:\n";
		foreach($_POST as $key => $value) {
			$postgetarguments .= $key."=".$value."\n";
   		}
   		
		$email = new email();
		$email->load('WebmasterWarning');
	
		$email->to = WEBMASTER_EMAIL;
	
		$email->variables = array(
			'Warning' => $postgetarguments.
				"\nProcessing Order Payment\n"); 
		
		if (!$grandtotal) {
			$email->variables['Warning'] .= "FAILED: No Grand Total returned!\n";	
			$email->send();
	
			exit("FAILED: No Grand Total returned!");
		}
	
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `OrderID` = '".sql::escape($ordernumber)."'"));
		
		if (!$order) {
			$email->variables['Warning'] .= "FAILED: Order Number not found!\n";	
			$email->send();
			
			exit("FAILED: Order Number not found!");
		}
		
		$ordertotal = number_format($order['Subtotal']+
				(isset($order['Tax'])?$order['Tax']:0)-
				$order['Discount']+$order['Fee'], 2, '.', '');
		
		if ($grandtotal != $ordertotal) {
			$email->variables['Warning'] .= "FAILED: Grand Total returned (".
				$grandtotal.") doesn't mach Order's total (".$ordertotal.")!\n";	
			$email->send();
			
			exit("FAILED: Grand Total returned (".$grandtotal.") doesn't mach " .
				" Order's total (".$ordertotal.")!");
		}
		
		$md5 = strtoupper(md5($ordertransactionid .
			SHOPPING_CART_ORDER_METHOD_2CHECKOUT_VENDOR_ID .
			$_POST['invoice_id'] .
			SHOPPING_CART_ORDER_METHOD_2CHECKOUT_SECRET_WORD));
		
		if ($md5 != $_POST['md5_hash']) {
			$email->variables['Warning'] .= "FAILED: Not a 2CheckOut request!\n";	
			$email->send();
			
			exit("Not a 2CheckOut request!");
		}
		
		$orderdetails = $order['OrderMethodDetails'];
		
		$orderdetails = 
			" - ".date('Y-m-d H:i:s')." - \n" .
			(!stristr($orderdetails, 'Transaction ID')?
				"Transaction ID: ".$ordertransactionid."\n" .
				"Payer Email: ".$orderemail."\n":
				null) .
			($paymentstatus?
				"Payment Status: ".$paymentstatus."\n":
				null) .
			($fraudstatus?
				"Fraud Status: ".$fraudstatus."\n":
				null) .
			$paymentstatusmsg .
			($orderdetails?"\n\n".$orderdetails:null);
		
		sql::run(
			" UPDATE `{shoppingorders}` SET " .
			" `PaymentStatus` = '".
				$orderstatus."', " .
			" `OrderMethodDetails` = '".
				sql::escape($orderdetails)."', " .
			($orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PAID?
				" `TimeStamp` = NOW()":
				" `TimeStamp` = `TimeStamp`") .
			" WHERE `ID` = '".$order['ID']."'");
		
		shoppingOrders::sendNotificationEmails($order['ID']);
		
		if ($orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PAID)
			users::activate($order['UserID']);
		
		exit("OK: Order successfully updated!");
	}
	
	function verify() {
		return true;
	}
	
	function setUp() {
	}
	
	function ajaxRequest() {
		$this->ipnProcess();
		return true;
	}
}

class shoppingOrderMethodMoneyBookers extends form {
	var $postProcessText = null;
	var $processResult = null;
	var $ajaxRequest = null;
	
	function __construct() {
		parent::__construct(
			_('MoneyBookers'), 'moneybookers');
		
		$this->postProcessText = 			
			_("Your order has been successfully created but your payment has not been processed yet. " .
				"To finalize your payments please click on the button below.");
	}
	
	function process() {
		$this->processResult = 
			_("User redirected to moneybookers, payment is being processed.");
		
		return SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
	}
	
	function postProcess($orderid) {
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `ID` = '".$orderid."'"));
		
		if (!$order)
			return false;
		
		$orderurl = shoppingOrders::getURL().
				"&shoppingorderid=".$order['ID'];
		
		$grandtotal = number_format($order['Subtotal']+
			(isset($order['Tax'])?$order['Tax']:0)-
			$order['Discount']+$order['Fee'], 2, '.', '');
		
		$items = array();
		$orderitems = sql::run(
			" SELECT * FROM `{shoppingorderitems}`" .
			" WHERE `ShoppingOrderID` = '".$order['ID']."'");
		
		while($orderitem = sql::fetch($orderitems)) {
			$item = sql::fetch(sql::run(
				" SELECT `RefNumber` FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$orderitem['ShoppingItemID']."'"));
			
			$items[] = $item['RefNumber'];
		}
		
		tooltip::display(
			"<form action='https://www.moneybookers.com/app/payment.pl' method='post' id='shoppingordermethodmoneybookersform'>" .
			"<input type='hidden' name='pay_to_email' value='".SHOPPING_CART_ORDER_METHOD_MONEYBOOKERS_ID."' />" .
			"<input type='hidden' name='recipient_description' value='Checkout for ".htmlspecialchars(PAGE_TITLE, ENT_QUOTES)."' />" . 
			"<input type='hidden' name='transaction_id' value='".$order['OrderID']."' />" .
			"<input type='hidden' name='detail1_description' value='Items' />" . 
			"<input type='hidden' name='detail1_text' value='".htmlspecialchars(implode('; ', $items), ENT_QUOTES)."' />" . 
			"<input type='hidden' name='currency' value='".SHOPPING_CART_ORDER_METHOD_MONEYBOOKERS_CURRENCY."' />" . 
			"<input type='hidden' name='amount' value='".$grandtotal."' />" .
			"<input type='hidden' name='cancel_url' value='".$orderurl."' />" .
			"<input type='hidden' name='return_url' value='".$orderurl."' />" . 
			"<input type='hidden' name='status_url' value='".SITE_URL."index.php?request=modules/shoppingorders/shoppingordermethodpaypal&ajax=1' />" .
			$this->postProcessText .
			"<br /><br />" .
			"<input type='submit' name='submit' value='".
				htmlspecialchars(_("Click to Finalize Payments"), ENT_QUOTES)."' " .
				"class='button submit' />" .
			"</form>", 
			TOOLTIP_NOTIFICATION);
		
		return true;
	}
	
	function ipnProcess() {
		if (!isset($_POST['transaction_id']) || !isset($_POST['mb_transaction_id']) ||
			!$_POST['transaction_id'] || !$_POST['mb_transaction_id'])
			exit("Invalid IPN request!");
		
		$grandtotal = $_POST['mb_amount'];
		$ordernumber = $_POST['transaction_id'];
		$ordertransactionid = $_POST['mb_transaction_id'];
		$orderemail = $_POST['pay_from_email'];
		$paymentstatus = $_POST['status'];
		$paymenttype = $_POST['payment_type'];
		
		$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
		
		if ($paymentstatus == -2)
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_FAILED;
		elseif ($paymentstatus == 2)
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PAID;
		elseif ($paymentstatus == -1)
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_CANCELLED;
		
		// These are used for debugging
		$postgetarguments = "GET arguments:\n";
		foreach($_GET as $key => $value) {
			$postgetarguments .= $key."=".$value."\n";
   		}
		
		$postgetarguments .= "\nPOST arguments:\n";
		foreach($_POST as $key => $value) {
			$postgetarguments .= $key."=".$value."\n";
   		}
   		
		$email = new email();
		$email->load('WebmasterWarning');
	
		$email->to = WEBMASTER_EMAIL;
	
		$email->variables = array(
			'Warning' => $postgetarguments.
				"\nProcessing Order Payment\n"); 
		
		if (!$grandtotal) {
			$email->variables['Warning'] .= "FAILED: No Grand Total returned!\n";	
			$email->send();
	
			exit("FAILED: No Grand Total returned!");
		}
		
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `OrderID` = '".sql::escape($ordernumber)."'"));
		
		if (!$order) {
			$email->variables['Warning'] .= "FAILED: Order Number not found!\n";	
			$email->send();
			
			exit("FAILED: Order Number not found!");
		}
		
		$ordertotal = number_format($order['Subtotal']+
				(isset($order['Tax'])?$order['Tax']:0)-
				$order['Discount']+$order['Fee'], 2, '.', '');
		
		if ($grandtotal != $ordertotal) {
			$email->variables['Warning'] .= "FAILED: Grand Total returned (".
				$grandtotal.") doesn't mach Order's total (".$ordertotal.")!\n";	
			$email->send();
			
			exit("FAILED: Grand Total returned (".$grandtotal.") doesn't mach " .
				" Order's total (".$ordertotal.")!");
		}
		
		$md5 = md5($_POST['merchant_id'].$order['OrderID'] .
			strtoupper(md5(SHOPPING_CART_ORDER_METHOD_MONEYBOOKERS_SECRET_WORD)) .
			$ordertotal.$_POST['mb_currency'].$paymentstatus);
		
		if ($md5 != $_POST['md5sig']) {
			$email->variables['Warning'] .= "FAILED: Not a MoneyBookers request!\n";	
			$email->send();
			
			exit("Not a MoneyBookers request!");
		}
		
		$orderdetails = $order['OrderMethodDetails'];
		
		$orderdetails = 
			" - ".date('Y-m-d H:i:s')." - \n" .
			(!stristr($orderdetails, 'Transaction ID')?
				"Transaction ID: ".$ordertransactionid."\n" .
				"Payer Email: ".$orderemail."\n":
				null) .
			"Payment Status: " .
				shoppingOrders::paymentStatus2Text($orderstatus) .
				" (".$paymentstatus.")\n" .
			"Payment Type: ".$paymenttype .
			($orderdetails?"\n\n".$orderdetails:null);
		
		sql::run(
			" UPDATE `{shoppingorders}` SET " .
			" `PaymentStatus` = '".
				$orderstatus."', " .
			" `OrderMethodDetails` = '".
				sql::escape($orderdetails)."', " .
			($orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PAID?
				" `TimeStamp` = NOW()":
				" `TimeStamp` = `TimeStamp`") .
			" WHERE `ID` = '".$order['ID']."'");
		
		shoppingOrders::sendNotificationEmails($order['ID']);
		
		if ($orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PAID)
			users::activate($order['UserID']);
		
		exit("OK: Order successfully updated!");
	}
	
	function verify() {
		return true;
	}
	
	function setUp() {
	}
	
	function ajaxRequest() {
		$this->ipnProcess();
		return true;
	}
}

class shoppingOrderMethodOgone extends form {
	var $postProcessText = null;
	var $processResult = null;
	var $ajaxRequest = null;
	
	function __construct() {
		parent::__construct(
			_('Ogone'), 'ogone');
		
		$this->postProcessText = 			
			_("Your order has been successfully created but your payment has not been processed yet. " .
				"To finalize your payments please click on the button below.");
	}
	
	function process() {
		$this->processResult = 
			_("User redirected to ogone, payment is being processed.");
		
		return SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
	}
	
	function postProcess($orderid) {
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `ID` = '".$orderid."'"));
		
		if (!$order)
			return false;
		
		$orderurl = shoppingOrders::getURL().
				"&shoppingorderid=".$order['ID'];
		
		$grandtotal = number_format($order['Subtotal']+
			(isset($order['Tax'])?$order['Tax']:0)-
			$order['Discount']+$order['Fee'], 2, '.', '');
		
		$items = array();
		$orderitems = sql::run(
			" SELECT * FROM `{shoppingorderitems}`" .
			" WHERE `ShoppingOrderID` = '".$order['ID']."'");
		
		while($orderitem = sql::fetch($orderitems)) {
			$item = sql::fetch(sql::run(
				" SELECT `RefNumber` FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$orderitem['ShoppingItemID']."'"));
			
			$items[] = $item['RefNumber'];
		}
		
		$arguments =
			array(
				'AMOUNT' => $grandtotal*100,
				'BACKURL' => $orderurl,
				'BGCOLOR' => SHOPPING_CART_ORDER_METHOD_OGONE_PAGE_BG_COLOR,
				'BUTTONBGCOLOR' => SHOPPING_CART_ORDER_METHOD_OGONE_PAGE_BUTTON_BG_COLOR,
				'BUTTONTXTCOLOR' => SHOPPING_CART_ORDER_METHOD_OGONE_PAGE_BUTTON_TEXT_COLOR,
				'CATALOGURL' => $orderurl,
				'COM' => implode('; ', $items),
				'CURRENCY' => SHOPPING_CART_ORDER_METHOD_OGONE_CURRENCY,
				'FONTTYPE' => SHOPPING_CART_ORDER_METHOD_OGONE_PAGE_FONT_TYPE,
				'HOMEURL' => $orderurl,
				'LANGUAGE' => '',
				'LOGO' => SHOPPING_CART_ORDER_METHOD_OGONE_PAGE_LOGO,
				'ORDERID' => $order['OrderID'],
				'PSPID' => SHOPPING_CART_ORDER_METHOD_OGONE_PSPID,
				'TBLBGCOLOR' => SHOPPING_CART_ORDER_METHOD_OGONE_PAGE_TABLE_BG_COLOR,
				'TBLTXTCOLOR' => SHOPPING_CART_ORDER_METHOD_OGONE_PAGE_TABLE_TEXT_COLOR,
				'TITLE' => SHOPPING_CART_ORDER_METHOD_OGONE_PAGE_TITLE,
				'TP' => SHOPPING_CART_ORDER_METHOD_OGONE_DYNAMIC_TEMPLATE_URL,
				'TXTCOLOR' => SHOPPING_CART_ORDER_METHOD_OGONE_PAGE_TEXT_COLOR);
		
		contentCodes::replaceDefinitions($arguments['TITLE']);
		
		if (languages::$selected)
			$arguments['LANGUAGE'] = languages::$selected['Locale'];
		
		$sha1_signature = '';
		$htmlfields = '';
			
		foreach ($arguments as $key => $value) {
			if (strlen($value) == 0)
				continue;
			
			$sha1_signature .= $key.'='.$value.SHOPPING_CART_ORDER_METHOD_OGONE_SHA_IN_PASS_PHRASE;
			$htmlfields .= "<input type='hidden' name='".$key."' " .
				"value='".htmlspecialchars($value, ENT_QUOTES)."' />";
		}
		
		tooltip::display(
			//"<form action='https://secure.ogone.com/ncol/test/orderstandard.asp' method='post' id='shoppingordermethodogoneform'>" .
			"<form action='https://secure.ogone.com/ncol/prod/orderstandard.asp' method='post' id='shoppingordermethodogoneform'>" .
			$htmlfields .
			"<input type='hidden' name='SHASign' value='".sha1($sha1_signature)."' />" .
			$this->postProcessText .
			"<br /><br />" .
			"<input type='submit' name='submitorder' value='".
				htmlspecialchars(_("Click to Finalize Payments"), ENT_QUOTES)."' " .
				"class='button submit' />" .
			"</form>", 
			TOOLTIP_NOTIFICATION);
		
		return true;
	}
	
	function ipnProcess() {
		if (!isset($_POST['PAYID']) || !isset($_POST['SHASIGN']) ||
			!$_POST['PAYID'] || !$_POST['SHASIGN'])
			exit("Invalid IPN request!");
		
		$arguments = 
			array(
				'AAVADDRESS' => (isset($_POST['AAVADDRESS'])?$_POST['AAVADDRESS']:null),
				'AAVCHECK' => (isset($_POST['AAVCHECK'])?$_POST['AAVCHECK']:null),
				'AAVZIP' => (isset($_POST['AAVZIP'])?$_POST['AAVZIP']:null),
				'ACCEPTANCE' => (isset($_POST['ACCEPTANCE'])?$_POST['ACCEPTANCE']:null),
				'AMOUNT' => (isset($_POST['amount'])?$_POST['amount']:null),
				'BIN' => (isset($_POST['BIN'])?$_POST['BIN']:null),
				'BRAND' => (isset($_POST['BRAND'])?$_POST['BRAND']:null),
				'CARDNO' => (isset($_POST['CARDNO'])?$_POST['CARDNO']:null),
				'CCCTY'=> (isset($_POST['CCCTY'])?$_POST['CCCTY']:null),
				'CN' => (isset($_POST['CN'])?$_POST['CN']:null),
				'COMPLUS' => (isset($_POST['COMPLUS'])?$_POST['COMPLUS']:null),
				'CURRENCY' => (isset($_POST['currency'])?$_POST['currency']:null),
				'CVCCHECK' => (isset($_POST['CVCCheck'])?$_POST['CVCCheck']:null),
				'ECI' => (isset($_POST['ECI'])?$_POST['ECI']:null),
				'ED' => (isset($_POST['ED'])?$_POST['ED']:null),
				'IP' => (isset($_POST['IP'])?$_POST['IP']:null),
				'IPCTY' => (isset($_POST['IPCTY'])?$_POST['IPCTY']:null),
				'NCERROR' => (isset($_POST['NCERROR'])?$_POST['NCERROR']:null),
				'ORDERID' => (isset($_POST['orderID'])?$_POST['orderID']:null),
				'PAYID' => (isset($_POST['PAYID'])?$_POST['PAYID']:null),
				'PM' => (isset($_POST['PM'])?$_POST['PM']:null),
				'STATUS' => (isset($_POST['STATUS'])?$_POST['STATUS']:null),
				'TRXDATE' => (isset($_POST['TRXDATE'])?$_POST['TRXDATE']:null),
				'VC' => (isset($_POST['VC'])?$_POST['VC']:null));
		
		$sha1key = '';
		foreach ($arguments as $key => $value) {
			if (strlen($value) == 0)
				continue;
			
			$sha1key .= $key.'='.$value.SHOPPING_CART_ORDER_METHOD_OGONE_SHA_OUT_PASS_PHRASE;
		}
		
		$sha1key = sha1($sha1key);
		$grandtotal = $_POST['amount'];
		$ordernumber = $_POST['orderID'];
		$ordertransactionid = $_POST['PAYID'];
		$paymentmethod = $_POST['PM'];
		$paymentstatus = $_POST['STATUS'];
		
		switch($_POST['STATUS']) {
			case 0:
				$paymentstatusmsg = 'Incomplete or invalid';
				break;
			case 1:
				$paymentstatusmsg = 'Cancelled by client';
				break;
			case 2:
				$paymentstatusmsg = 'Authorization refused';
				break;
			case 4:
				$paymentstatusmsg = 'Order stored';
				break;
			case 41:
				$paymentstatusmsg = 'Waiting client payment';
				break;
			case 5:
				$paymentstatusmsg = 'Authorized';
				break;
			case 51:
				$paymentstatusmsg = 'Authorization waiting';
				break;
			case 52:
				$paymentstatusmsg = 'Authorization not known';
				break;
			case 59:
				$paymentstatusmsg = 'Author. to get manually';
				break;
			case 6:
				$paymentstatusmsg = 'Authorized and canceled';
				break;
			case 61:
				$paymentstatusmsg = 'Author. deletion waiting';
				break;
			case 62:
				$paymentstatusmsg = 'Author. deletion uncertain';
				break;
			case 63:
				$paymentstatusmsg = 'Author. deletion refused';
				break;
			case 7:
				$paymentstatusmsg = 'Payment deleted';
				break;
			case 71:
				$paymentstatusmsg = 'Payment deletion pending';
				break;
			case 72:
				$paymentstatusmsg = 'Payment deletion uncertain';
				break;
			case 73:
				$paymentstatusmsg = 'Payment deletion refused';
				break;
			case 74:
				$paymentstatusmsg = 'Payment deleted (not accepted)';
				break;
			case 75:
				$paymentstatusmsg = 'Deletion processed by merchant';
				break;
			case 8:
				$paymentstatusmsg = 'Refund';
				break;
			case 81:
				$paymentstatusmsg = 'Refund pending';
				break;
			case 82:
				$paymentstatusmsg = 'Refund uncertain';
				break;
			case 83:
				$paymentstatusmsg = 'Refund refused';
				break;
			case 84:
				$paymentstatusmsg = 'Payment declined by the acquirer';
				break;
			case 85:
				$paymentstatusmsg = 'Refund processed by merchant';
				break;
			case 9:
				$paymentstatusmsg = 'Payment requested';
				break;
			case 91:
				$paymentstatusmsg = 'Payment processing';
				break;
			case 92:
				$paymentstatusmsg = 'Payment uncertain';
				break;
			case 93:
				$paymentstatusmsg = 'Payment refused';
				break;
			case 94:
				$paymentstatusmsg = 'Refund declined by the acquirer';
				break;
			case 95:
				$paymentstatusmsg = 'Payment processed by merchant';
				break;
			case 97:
			case 98:
			case 99:
				$paymentstatusmsg = 'Being processed';
				break;
			default:
				$paymentstatusmsg = 'Unknown';
				break;
		}
		
		$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PENDING;
		
		if ($paymentstatus == 5) {
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PAID;
			
		} elseif (in_array($paymentstatus, array(1, 6, 7, 8))) {
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_CANCELLED;
			
		} elseif (in_array($paymentstatus, array(2))) {
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_FAILED;
			
		} elseif (in_array($paymentstatus, array(4, 41, 51, 52, 59, 61, 62, 63, 71, 72, 73, 74, 75, 81, 82, 83, 84, 85, 9, 91, 92, 93, 94, 95, 97, 98, 99))) {
			$orderstatus = SHOPPING_ORDER_PAYMENT_STATUS_PROCESSING;
		}
		
		// These are used for debugging
		$postgetarguments = "GET arguments:\n";
		foreach($_GET as $key => $value) {
			$postgetarguments .= $key."=".$value."\n";
   		}
		
		$postgetarguments .= "\nPOST arguments:\n";
		foreach($_POST as $key => $value) {
			$postgetarguments .= $key."=".$value."\n";
   		}
   		
		$email = new email();
		$email->load('WebmasterWarning');
	
		$email->to = WEBMASTER_EMAIL;
	
		$email->variables = array(
			'Warning' => $postgetarguments.
				"\nProcessing Order Payment\n"); 
		
		if (!$grandtotal) {
			$email->variables['Warning'] .= "FAILED: No Grand Total returned!\n";	
			$email->send();
	
			exit("FAILED: No Grand Total returned!");
		}
	
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `OrderID` = '".sql::escape($ordernumber)."'"));
		
		if (!$order) {
			$email->variables['Warning'] .= "FAILED: Order Number not found!\n";	
			$email->send();
			
			exit("FAILED: Order Number not found!");
		}
		
		$ordertotal = number_format($order['Subtotal']+
				(isset($order['Tax'])?$order['Tax']:0)-
				$order['Discount']+$order['Fee'], 2, '.', '');
		
		if ($grandtotal != $ordertotal) {
			$email->variables['Warning'] .= "FAILED: Grand Total returned (".
				$grandtotal.") doesn't mach Order's total (".$ordertotal.")!\n";	
			$email->send();
			
			exit("FAILED: Grand Total returned (".$grandtotal.") doesn't mach " .
				" Order's total (".$ordertotal.")!");
		}
		
		if(strtoupper($sha1key) != strtoupper($_POST['SHASIGN'])) {
			$email->variables['Warning'] .= "FAILED: Not an Ogone request!\n";	
			$email->send();
			
			exit("Not an Ogone request!");
		}
		
		$orderdetails = $order['OrderMethodDetails'];
		
		$orderdetails = 
			" - ".date('Y-m-d H:i:s')." - \n" .
			(!stristr($orderdetails, 'Transaction ID')?
				"Transaction ID: ".$ordertransactionid."\n":
				null) .
			"Payment Status: ".$paymentstatusmsg." (".$paymentstatus.")\n" .
			"Payment Method: ".$paymentmethod .
			($orderdetails?"\n\n".$orderdetails:null);
		
		sql::run(
			" UPDATE `{shoppingorders}` SET " .
			" `PaymentStatus` = '".
				$orderstatus."', " .
			" `OrderMethodDetails` = '".
				sql::escape($orderdetails)."', " .
			($orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PAID?
				" `TimeStamp` = NOW()":
				" `TimeStamp` = `TimeStamp`") .
			" WHERE `ID` = '".$order['ID']."'");
		
		shoppingOrders::sendNotificationEmails($order['ID']);
		
		if ($orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PAID ||
			$orderstatus == SHOPPING_ORDER_PAYMENT_STATUS_PROCESSING)
			users::activate($order['UserID']);
		
		exit("OK: Order successfully updated!");
	}
	
	function verify() {
		return true;
	}
	
	function setUp() {
	}
	
	function ajaxRequest() {
		$this->ipnProcess();
		return true;
	}
}

class shoppingOrderMethods {
	static function add($id, $title, $description = null) {
		if (!$id)
			return false;
		
		if (!$description) {
			$description = $title;
			$title = $id;
		}
		
		if (isset($GLOBALS['SHOPPING_ORDER_METHODS'][strtolower($id)]))
			exit($id." order method couldn't be added as it's " .
				"id is already used by another order method!");
		
		if ((defined('SHOPPING_CART_ENABLE_ORDER_METHOD_'.strtoupper($id)) && 
			!constant('SHOPPING_CART_ENABLE_ORDER_METHOD_'.strtoupper($id))) ||
			(defined('SHOPPING_CART_ORDER_METHOD_'.strtoupper($id).'_ENABLED') && 
			!constant('SHOPPING_CART_ORDER_METHOD_'.strtoupper($id).'_ENABLED')) ||
			(!defined('SHOPPING_CART_ENABLE_ORDER_METHOD_'.strtoupper($id)) && 
			!defined('SHOPPING_CART_ORDER_METHOD_'.strtoupper($id).'_ENABLED') &&
			in_array(strtolower($id), 
				array(
					'invoicecustomer', 
					'check', 
					'paypal', 
					'ccbill',
					'alertpay',
					'authorizedotnet',
					'2checkout',
					'moneybookers',
					'ogone'))))
			return false;
		
		languages::load('shopping');
		
		$GLOBALS['SHOPPING_ORDER_METHODS'][strtolower($id)] = array(
			'Title' => _($title),
			'Description' => sprintf(_($description),
				(defined('SHOPPING_CART_ORDER_METHOD_CHECK_TO_NAME')?
					SHOPPING_CART_ORDER_METHOD_CHECK_TO_NAME:
					PAGE_TITLE)));
		
		languages::unload('shopping');
		
		return true;
	}
	
	static function get($methodid = null) {
		if (!$methodid)
			return $GLOBALS['SHOPPING_ORDER_METHODS'];
			
		if (isset($GLOBALS['SHOPPING_ORDER_METHODS'][$methodid]))
			return $GLOBALS['SHOPPING_ORDER_METHODS'][$methodid];
		
		return null;
	}
}
 
class shoppingOrderForm extends dynamicForms {
	function __construct() {
		languages::load('shopping');
		
		parent::__construct(
			_('Shopping Orders'), 'shoppingorders');
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
	
	function verify($customdatahandling = true) {
		if (!parent::verify(true))
			return false;
		
		return true;
	}
}

class shoppingOrderComments extends comments {
	var $sqlTable = 'shoppingordercomments';
	var $sqlRow = 'ShoppingOrderID';
	var $sqlOwnerTable = 'shoppingorders';
	var $sqlOwnerField = 'OrderID';
	var $adminPath = array(
		'admin/modules/shoppingorders/shoppingneworders/shoppingordercomments',
		'admin/modules/shoppingorders/shoppingprocessedorders/shoppingordercomments');
	
	function __construct() {
		languages::load('shopping');
		
		parent::__construct();
		
		$this->selectedOwner = _('Order');
		$this->uriRequest = "modules/shoppingorders/".$this->uriRequest;
		
		if ($GLOBALS['ADMIN'])
			$this->commentURL = shoppingOrders::getURL().
				"&shoppingorderid=".admin::getPathID();
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
}

class shoppingOrderItems {
	function __construct() {
		languages::load('shopping');
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		$newid = sql::run(
			" INSERT INTO `{shoppingorderitems}` SET " .
			" `ShoppingOrderID` = '".(int)$values['ShoppingOrderID']."', " .
			" `ShoppingItemID` = '".(int)$values['ShoppingItemID']."', " .
			(isset($values['ShoppingItemOptions']) && 
			 $values['ShoppingItemOptions']?
				" `ShoppingItemOptions` = '" .
					sql::escape($values['ShoppingItemOptions'])."', ":
				null) .
			" `Price` = '".sql::escape($values['Price'])."', " .
			" `Quantity` = '".sql::escape($values['Quantity'])."'");
		
		if (!$newid) {
			tooltip::display(
				_("Order item couldn't be added to the db! Please contact us " .
					"with this error as soon as possible."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if (JCORE_VERSION >= '0.5') {
			sql::run(
				" UPDATE `{shoppingitems}` SET" .
				" `NumberOfOrders` = `NumberOfOrders` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".(int)$values['ShoppingItemID']."'");
		}
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		sql::run(
			" UPDATE `{shoppingorderitems}` SET " .
			(isset($values['ShoppingItemOptions'])?
				" `ShoppingItemOptions` = " .
					($values['ShoppingItemOptions']?
						"'".sql::escape($values['ShoppingItemOptions'])."'":
						"NULL") .
					", ":
				null) .
			" `Price` = '".sql::escape($values['Price'])."', " .
			" `Quantity` = '".sql::escape($values['Quantity'])."'" .
			" WHERE `ID` = '".(int)$id."'");
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(_("Order item couldn't be updated! Error: %s"), 
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
			" DELETE FROM `{shoppingorderitems}` " .
			" WHERE `ID` = '".$id."'");
			
		return true;
	}
}

class shoppingNewOrders extends shoppingOrders {
	var $adminPath = 'admin/modules/shoppingorders/shoppingneworders';
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			_('Shopping Orders'),
			_('New and Processing Orders'));
	}
	
	function displayAdminListSearch($ordertypes = null) {
		parent::displayAdminListSearch(array(
			SHOPPING_ORDER_STATUS_NEW,
			SHOPPING_ORDER_STATUS_PROCESSING));
	}
	
	function displayAdmin() {
		parent::displayAdminOrders(array(
			SHOPPING_ORDER_STATUS_NEW,
			SHOPPING_ORDER_STATUS_PROCESSING));
	}
}

class shoppingProcessedOrders extends shoppingOrders {
	var $adminPath = 'admin/modules/shoppingorders/shoppingprocessedorders';
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			_('Shopping Orders'),
			_('Processed Orders'));
	}
	
	function displayAdminListSearch($ordertypes = null) {
		parent::displayAdminListSearch(array(
			SHOPPING_ORDER_STATUS_ACCEPTED,
			SHOPPING_ORDER_STATUS_CANCELLED,
			SHOPPING_ORDER_STATUS_DELIVERED,
			SHOPPING_ORDER_STATUS_REJECTED));
	}
	
	function displayAdmin() {
		parent::displayAdminOrders(array(
			SHOPPING_ORDER_STATUS_ACCEPTED,
			SHOPPING_ORDER_STATUS_CANCELLED,
			SHOPPING_ORDER_STATUS_DELIVERED,
			SHOPPING_ORDER_STATUS_REJECTED));
	}
}

class shoppingOrders extends modules {
	var $selectedID;
	var $limit = 20;
	var $shoppingURL;
	var $shoppingOrdersURL;
	var $ignorePaging = false;
	var $showPaging = true;
	var $ajaxPaging = AJAX_PAGING;
	var $ajaxRequest = null;
	var $adminPath = 'admin/modules/shoppingorders';
	
	function __construct() {
		languages::load('shopping');
		
		if (isset($_GET['shoppingorderid']))
			$this->selectedID = (int)$_GET['shoppingorderid'];
	}
	
	function __destruct() {
		languages::unload('shopping');
	}
	
	function SQL() {
		$permission = null;
		
		if ($GLOBALS['USER']->data['Admin']) {
			include_once('lib/userpermissions.class.php');
			
			$permission = userPermissions::check($GLOBALS['USER']->data['ID'], 
				$this->adminPath);
		}
		
		return
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE 1" .
			($this->selectedID?
				" AND `ID` = '".$this->selectedID."'":
				null) . 
			(!$GLOBALS['USER']->data['Admin'] || !$permission['PermissionType']?
				" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'":
				null) .
			($permission && $permission['PermissionIDs']?
				" AND (`ID` IN (".$permission['PermissionIDs'].")" .
				" OR `UserID` = '".$GLOBALS['USER']->data['ID']."')":
				null) .
			" ORDER BY `ID` DESC";
	}
	
	function installSQL() {
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{dynamicforms}` " .
			" WHERE `FormID` = 'shoppingorders';"));
		
		if (sql::display())
			return false;
			
		$formid = $exists['ID'];
			
		if (!$exists) {
			$formid = sql::run(
				" INSERT INTO `{dynamicforms}` " .
				" (`Title`, `FormID`, `Method`, `SendNotificationEmail`, `SQLTable`, `Protected`, `ProtectedSQLTable`, `BrowseDataURL`) VALUES" .
				" ('Shopping Orders', 'shoppingorders', 'post', 0, 'shoppingorders', 1, 1, '?path=admin/modules/shoppingorders');");
			
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
				" (`FormID`, `Title`, `Name`, `TypeID`, `ValueType`, `Required`, `PlaceholderText`, `TooltipText`, `AdditionalText`, `Attributes`, `Style`, `OrderID`, `Protected`) VALUES" .
				" (".$formid.", '<b>Billing information</b><br />* This information must match your credit card to prevent any delays', '', 18, 0, 0, '', '', '', '', '', 1, 0)," .
				" (".$formid.", 'Your name', 'FullName', 1, 1, 1, '', '', '', '', 'width: 200px;', 2, 0)," .
				" (".$formid.", 'Company / Organization', 'Company', 1, 1, 0, '', '', '', '', 'width: 250px;', 3, 0)," .
				" (".$formid.", 'Address', 'Address', 1, 1, 1, '', '', '', '', 'width: 350px;', 4, 0)," .
				" (".$formid.", 'City', 'City', 1, 1, 1, '', '', '', '', 'width: 110px;', 5, 0)," .
				" (".$formid.", 'State', 'State', 1, 1, 0, '', '', '', '', 'width: 50px;', 6, 0)," .
				" (".$formid.", 'Zip/Postal code', 'ZipCode', 1, 1, 1, '', '', '', '', 'width: 70px;', 7, 0)," .
				" (".$formid.", 'Country', 'Country', 1, 1, 1, '', '', '', '', 'width: 150px;', 8, 0)," .
				" (".$formid.", 'Phone number', 'PhoneNumber', 1, 1, 1, '', '', '', '', 'width: 150px;', 9, 0)," .
				" (".$formid.", 'Email address', 'Email', 2, 1, 1, '', '', '', '', 'width: 250px;', 10, 0)," .
				" (".$formid.", '<hr />', '', 18, 0, 0, '', '', '', '', '', 11, 0)," .
				" (".$formid.", '<b>Shipping address</b><br />* Leave blank If same as Billing Information', '', 18, 0, 1, '', '', '', '', '', 12, 0)," .
				" (".$formid.", 'Name', 'ShippingName', 1, 1, 0, '', '', '', '', 'width: 200px;', 13, 0)," .
				" (".$formid.", 'Company / Organization', 'ShippingCompany', 1, 1, 0, '', '', '', '', 'width: 250px;', 14, 0)," .
				" (".$formid.", 'Address', 'ShippingAddress', 1, 1, 0, '', '', '', '', 'width: 350px;', 15, 0)," .
				" (".$formid.", 'City', 'ShippingCity', 1, 1, 0, '', '', '', '', 'width: 110px;', 16, 0)," .
				" (".$formid.", 'State', 'ShippingState', 1, 1, 0, '', '', '', '', 'width: 50px;', 17, 0)," .
				" (".$formid.", 'Zip/Postal code', 'ShippingZipCode', 1, 1, 0, '', '', '', '', 'width: 70px;', 18, 0)," .
				" (".$formid.", 'Country', 'ShippingCountry', 1, 1, 0, '', '', '', '', 'width: 150px;', 19, 0)," .
				" (".$formid.", 'Comments', 'shgofc', 13, 0, 0, '', '', '', '', '', 20, 0)," .
				" (".$formid.", 'Indicate here your preferences or other comments', 'OrderComment', 6, 9, 0, '', '', '', '', 'width: 300px;', 21, 0)," .
				" (".$formid.", ' ', '', 14, 0, 0, '', '', '', '', '', 22, 0)," .
				" (".$formid.", 'Gift order', 'shgofg', 13, 0, 0, '', '', '', '', '', 23, 0)," .
				" (".$formid.", 'Is this a gift order?', 'GiftOrder', 3, 10, 0, '', '', '', '', '', 24, 0)," .
				" (".$formid.", 'Please enter your gift message here', 'GiftMessage', 6, 9, 0, '', '', '', '', 'width: 300px;', 25, 0)," .
				" (".$formid.", ' ', '', 14, 0, 0, '', '', '', '', '', 26, 0);");
			
			if (sql::display())
				return false;
		}
		
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingorders}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `OrderID` varchar(100) NOT NULL default ''," .
			" `UserID` mediumint(8) unsigned NOT NULL default '0'," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Discount` decimal(15,2) NOT NULL default '0.00'," .
			" `Fee` decimal(15,2) NOT NULL default '0.00'," .
			" `Tax` decimal(15,2) NOT NULL default '0.00'," .
			" `Subtotal` decimal(15,2) NOT NULL default '0.00'," .
			" `Comments` smallint(5) unsigned NOT NULL default '0'," .
			" `OrderStatus` tinyint(1) unsigned NOT NULL default '0'," .
			" `PaymentStatus` tinyint(1) unsigned NOT NULL default '0'," .
			" `OrderMethod` varchar(100) NOT NULL default ''," .
			" `OrderMethodDetails` mediumtext NULL," .
			" `FullName` varchar(100) NOT NULL default ''," .
			" `Company` varchar(150) NOT NULL default ''," .
			" `Address` varchar(255) NOT NULL default ''," .
			" `City` varchar(100) NOT NULL default ''," .
			" `State` varchar(15) NOT NULL default ''," .
			" `ZipCode` varchar(10) NOT NULL default ''," .
			" `Country` varchar(50) NOT NULL default ''," .
			" `PhoneNumber` varchar(50) NOT NULL default ''," .
			" `Email` varchar(100) NOT NULL default ''," .
			" `ShippingName` varchar(100) NOT NULL default ''," .
			" `ShippingCompany` varchar(150) NOT NULL default ''," .
			" `ShippingAddress` varchar(255) NOT NULL default ''," .
			" `ShippingCity` varchar(100) NOT NULL default ''," .
			" `ShippingState` varchar(15) NOT NULL default ''," .
			" `ShippingZipCode` varchar(10) NOT NULL default ''," .
			" `ShippingCountry` varchar(50) NOT NULL default ''," .
			" `OrderComment` mediumtext NULL," .
			" `GiftOrder` tinyint(1) unsigned NOT NULL default '0'," .
			" `GiftMessage` text NULL," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `OrderID` (`OrderID`,`UserID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `OrderStatus` (`OrderStatus`,`PaymentStatus`)" .
			") ENGINE=MyISAM ;");
		
		if (sql::display())
			return false;
		
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingorderitems}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `ShoppingOrderID` int(10) unsigned NOT NULL default '0'," .
			" `ShoppingItemID` mediumint(8) unsigned NOT NULL default '0'," .
			" `ShoppingItemOptions` TEXT NULL," .
			" `Price` decimal(12,2) NOT NULL default '0.00'," .
			" `Quantity` tinyint(3) unsigned NOT NULL default '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `ShoppingOrderID` (`ShoppingOrderID`)" .
			") ENGINE=MyISAM ;");
		
		if (sql::display())
			return false;
		
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingordercomments}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `ShoppingOrderID` int(10) unsigned NOT NULL default '0'," .
			" `UserName` varchar(100) NOT NULL default ''," .
			" `Email` varchar(100) NOT NULL default ''," .
			" `UserID` mediumint(8) unsigned NOT NULL default '0'," .
			" `Comment` text NULL," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP," .
			" `IP` bigint(20) NOT NULL default '0'," .
			" `SubCommentOfID` int(10) unsigned NOT NULL default '0'," .
			" `Rating` smallint(6) NOT NULL default '0'," .
			" `Pending` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `ShoppingItemID` (`ShoppingOrderID`,`UserID`,`UserName`)," .
			" KEY `Pending` (`Pending`)" .
			") ENGINE=MyISAM ;");
		
		if (sql::display())
			return false;
		
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingordercommentsratings}` (" .
			" `CommentID` int(10) unsigned NOT NULL default '0'," .
			" `UserID` mediumint(8) unsigned NOT NULL default '0'," .
			" `IP` bigint(20) NOT NULL default '0'," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Rating` tinyint(1) NOT NULL default '0'," .
			" KEY `CommentID` (`CommentID`,`UserID`,`IP`,`TimeStamp`)," .
			" KEY `Rating` (`Rating`)" .
			") ENGINE=MyISAM ;");
		
		if (sql::display())
			return false;
		
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{shoppingorderdownloads}` (" .
			" `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT," .
			" `ShoppingOrderID` INT UNSIGNED NOT NULL default '0'," .
			" `ShoppingItemID` MEDIUMINT UNSIGNED NOT NULL default '0'," .
			" `ShoppingItemDigitalGoodID` INT UNSIGNED NOT NULL default '0'," .
			" `IP` BIGINT NOT NULL default '0'," .
			" `StartTimeStamp` TIMESTAMP NOT NULL default CURRENT_TIMESTAMP," .
			" `FinishTimeStamp` TIMESTAMP NULL DEFAULT NULL," .
			" PRIMARY KEY  (`ID`)," .
			" INDEX (  `ShoppingOrderID` ,  `ShoppingItemID` ,  `ShoppingItemDigitalGoodID` )" .
			") ENGINE = MYISAM ;");
		
		if (sql::display())
			return false;
		
		$exists = sql::fetch(sql::run(
			" SHOW TABLES LIKE '" .
				(SQL_PREFIX?
					SQL_PREFIX.'_':
					null) .
				"shoppingcartsettings'"));
		
		if (sql::display())
			return false;
		
		if (!$exists) {
			tooltip::display(
				_("Shopping cart settings table cannot be found! Please " .
					"first install the Shopping Cart module and then try again."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingcartsettings}`" .
			" WHERE `ID` LIKE 'Shopping_Cart_Order_Method%'"));
		
		if (sql::display())
			return false;
		
		if (!$exists) {
			$lastorderid = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{shoppingcartsettings}`" .
				" ORDER BY `OrderID` DESC" .
				" LIMIT 1"));
		
			if (sql::display())
				return false;
				
			$nextorderid = (int)($lastorderid['OrderID']+1);
		
			sql::run(
				" INSERT INTO `{shoppingcartsettings}` (`ID`, `Value`, `TypeID`, `OrderID`) VALUES" .
				" ('Shopping_Cart_Order_Method_InvoiceCustomer', '', 0, ".$nextorderid.")," .
				" ('Shopping_Cart_Order_Method_InvoiceCustomer_Enabled', '1', 3, ".$nextorderid.")," .
				" ('Shopping_Cart_Order_Method_Check', '', 0, ".($nextorderid+1).")," .
				" ('Shopping_Cart_Order_Method_Check_Enabled', '1', 3, ".($nextorderid+1).")," .
				" ('Shopping_Cart_Order_Method_Check_To_Name', 'Website''s name', 1, ".($nextorderid+1).")," .
				" ('Shopping_Cart_Order_Method_PayPal', '', 0, ".($nextorderid+2).")," .
				" ('Shopping_Cart_Order_Method_PayPal_Enabled', '1', 3, ".($nextorderid+2).")," .
				" ('Shopping_Cart_Order_Method_PayPal_ID', 'me@pijulius.com', 1, ".($nextorderid+2).")," .
				" ('Shopping_Cart_Order_Method_PayPal_Currency', 'USD', 1, ".($nextorderid+2).")," .
				" ('Shopping_Cart_Order_Method_CCBill', '', 0, ".($nextorderid+3).")," .
				" ('Shopping_Cart_Order_Method_CCBill_Enabled', '1', 3, ".($nextorderid+3).")," .
				" ('Shopping_Cart_Order_Method_CCBill_Account_Number', '900100', 1, ".($nextorderid+3).")," .
				" ('Shopping_Cart_Order_Method_CCBill_SubAccount_Number', '0000', 1, ".($nextorderid+3).")," .
				" ('Shopping_Cart_Order_Method_CCBill_Form_ID', '144cc', 1, ".($nextorderid+3).")," .
				" ('Shopping_Cart_Order_Method_CCBill_Encryption_Key', 'ENCR67KEY907', 1, ".($nextorderid+3).")," .
				" ('Shopping_Cart_Order_Method_CCBill_Currency_Code', '840', 1, ".($nextorderid+3).")," .
				" ('Shopping_Cart_Order_Method_AlertPay', '', 0, ".($nextorderid+4).")," .
				" ('Shopping_Cart_Order_Method_AlertPay_Enabled', '1', 3, ".($nextorderid+4).")," .
				" ('Shopping_Cart_Order_Method_AlertPay_ID', 'me@pijulius.com', 1, ".($nextorderid+4).")," .
				" ('Shopping_Cart_Order_Method_AlertPay_Currency', 'USD', 1, ".($nextorderid+4).")," .
				" ('Shopping_Cart_Order_Method_AlertPay_Security_Code', 'XoQVtB9fi7eeqQ', 1, ".($nextorderid+4).")," .
				" ('Shopping_Cart_Order_Method_AuthorizeDotNet', '', 0, ".($nextorderid+5).")," .
				" ('Shopping_Cart_Order_Method_AuthorizeDotNet_Enabled', '1', 3, ".($nextorderid+5).")," .
				" ('Shopping_Cart_Order_Method_AuthorizeDotNet_API_Login_ID', '124dud21U7Kg', 1, ".($nextorderid+5).")," .
				" ('Shopping_Cart_Order_Method_AuthorizeDotNet_Transaction_Key', '72a233K5y45OpXrG', 1, ".($nextorderid+5).")," .
				" ('Shopping_Cart_Order_Method_2CheckOut', '', 0, ".($nextorderid+6).")," .
				" ('Shopping_Cart_Order_Method_2CheckOut_Enabled', '1', 3, ".($nextorderid+6).")," .
				" ('Shopping_Cart_Order_Method_2CheckOut_Vendor_ID', '1234567890', 1, ".($nextorderid+6).")," .
				" ('Shopping_Cart_Order_Method_2CheckOut_Secret_Word', 'secret', 1, ".($nextorderid+6).")," .
				" ('Shopping_Cart_Order_Method_2CheckOut_Currency', 'USD', 1, ".($nextorderid+6).")," .
				" ('Shopping_Cart_Order_Method_2CheckOut_Skip_Fraud_Check', '0', 3, ".($nextorderid+6).")," .
				" ('Shopping_Cart_Order_Method_MoneyBookers', '', 0, ".($nextorderid+7).")," .
				" ('Shopping_Cart_Order_Method_MoneyBookers_Enabled', '1', 3, ".($nextorderid+7).")," .
				" ('Shopping_Cart_Order_Method_MoneyBookers_ID', 'me@pijulius.com', 1, ".($nextorderid+7).")," .
				" ('Shopping_Cart_Order_Method_MoneyBookers_Secret_Word', 'secret', 1, ".($nextorderid+7).")," .
				" ('Shopping_Cart_Order_Method_MoneyBookers_Currency', 'USD', 1, ".($nextorderid+7).")," .
				" ('Shopping_Cart_Order_Method_Ogone', '', 0, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_Enabled', '1', 3, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_PSPID', 'pijulius', 1, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_Currency', 'USD', 1, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_SHA_IN_Pass_Phrase', '1234567890qwertyuiop', 1, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_SHA_OUT_Pass_Phrase', '0987654321poiuytrewq', 1, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_Page_Title', 'Checkout for \"%PAGE_TITLE%\"', 1, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_Page_BG_Color', '#4e84c4', 10, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_Page_Text_Color', '#FFFFFF', 10, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_Page_Table_BG_Color', '#FFFFFF', 10, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_Page_Table_Text_Color', '#000000', 10, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_Page_Button_BG_Color', '#00467F', 10, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_Page_Button_Text_Color', '#FFFFFF', 10, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_Page_Font_Type', 'Verdana', 1, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_Page_Logo', '', 1, ".($nextorderid+8).")," .
				" ('Shopping_Cart_Order_Method_Ogone_Dynamic_Template_URL', '', 1, ".($nextorderid+8).");");
			
			if (sql::display())
				return false;
		}
		
		$exists = sql::fetch(sql::run(
			" SELECT * FROM `{settings}`" .
			" WHERE `ID` = 'jQuery_Load_Plugins'"));
		
		if (sql::display())
			return false;
		
		if ($exists && !preg_match('/numberformat/i', $exists['Value'])) {
			sql::run(
				" UPDATE `{settings}` SET" .
				" `Value` = CONCAT(`Value`, ', numberformat')" .
				" WHERE `ID` = 'jQuery_Load_Plugins';");
			
			if (sql::display())
				return false;
		}
		
		return true;
	}
	
	function installFiles() {
		$css = 
			".shopping-order-comments-link {\n" .
			"	width: 32px;\n" .
			"	height: 32px;\n" .
			"	overflow: hidden;\n" .
			"	display: block;\n" .
			"	margin: 0;\n" .
			"	padding: 0;\n" .
			"	border: 0;\n" .
			"	background: transparent;\n" .
			"	background-image: url(\"http://icons.jcore.net/32/internet-group-chat.png\");\n" .
			"	text-decoration: none;\n" .
			"}\n" .
			"\n" .
			".shopping-order-number {\n" .
			"	margin: 0;\n" .
			"}\n" .
			"\n" .
			".shopping-order-content {\n" .
			"	padding: 0 0 15px 0;\n" .
			"}\n" .
			"\n" .
			".shopping-order.selected .separator.bottom {\n" .
			"	display: none;\n" .
			"}\n" .
			"\n" .
			".shopping-order-links a {\n" .
			"	display: block;\n" .
			"	padding: 5px 0px 5px 20px;\n" .
			"	background: url(\"http://icons.jcore.net/16/link.png\") 0px 50% no-repeat;\n" .
			"	float: left;\n" .
			"	margin-right: 10px;\n" .
			"}\n" .
			"\n" .
			".shopping-order-links .back {\n" .
			"	background-image: url(\"http://icons.jcore.net/16/doc_page_previous.png\");\n" .
			"}\n" .
			"\n" .
			".shopping-orders-cart-add-item,\n" .
			".shopping-orders-cart-refresh\n" .
			"{\n" .
			"	display: block;\n" .
			"	float: left;\n" .
			"	margin-top: 10px;\n" .
			"	padding: 0 0 5px 20px;\n" .
			"	background: url(\"http://icons.jcore.net/16/add.png\") no-repeat;\n" .
			"}\n" .
			"\n" .
			".shopping-orders-cart-refresh {\n" .
			"	margin-left: 10px;\n" .
			"	background: url(\"http://icons.jcore.net/16/arrow_refresh.png\") no-repeat;\n" .
			"}\n" .
			"\n" .
			".shopping-order-new-order-items .list .auto-width {\n" .
			"	width: 300px;\n" .
			"}\n" .
			"\n" .
			".shopping-order-new-order-add-user {\n" .
			"	padding: 0 0 5px 20px;\n" .
			"	background: url(\"http://icons.jcore.net/16/user.png\") no-repeat;\n" .
			"}\n" .
			"\n" .
			".shopping-order-new-order-select-user {\n" .
			"	display: block;\n" .
			"	width: 16px;\n" .
			"	height: 16px;\n" .
			"	background: url(\"http://icons.jcore.net/16/target.png\") no-repeat;\n" .
			"}\n" .
			"\n" .
			".shopping-order-new-order-add-item {\n" .
			"	display: block;\n" .
			"	width: 16px;\n" .
			"	height: 16px;\n" .
			"	background: url(\"http://icons.jcore.net/16/add.png\") no-repeat;\n" .
			"}\n" .
			"\n" .
			".shopping-order-new-order-remove-item {\n" .
			"	display: block;\n" .
			"	width: 16px;\n" .
			"	height: 16px;\n" .
			"	background: url(\"http://icons.jcore.net/16/cross.png\") no-repeat;\n" .
			"}\n" .
			"\n" .
			".shopping-order-new-order-items-search span {\n" .
			"	display: block;\n" .
			"	white-space: nowrap;\n" .
			"	float: left;\n" .
			"	margin-right: 10px;\n" .
			"}\n" .
			"\n" .
			".as-modules-shoppingorders a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/shopping-orders.png\");\n" .
			"}\n" .
			"\n" .
			".as-shopping-new-orders a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/shopping-orders-new.png\");\n" .
			"}\n";
		
		if (!files::save(SITE_PATH.'template/modules/css/shoppingorders.css', $css, true)) {
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
	function countAdminItems($ordertypes = null) {
		if (!parent::installed($this))
			return 0;
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{shoppingorders}`" .
			($ordertypes && is_array($ordertypes)?
				" WHERE `OrderStatus` IN (".implode(',', $ordertypes).")":
				null) .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				_('New Order'), 
				'?path=admin/modules/shoppingorders/shoppingneworders#adminform');
		
		favoriteLinks::add(
			_('Items'), 
			'?path=admin/modules/shopping');
		favoriteLinks::add(
			_('Cart Settings'), 
			'?path=admin/modules/shoppingcart');
		favoriteLinks::add(
			__('Users'), 
			'?path=admin/members/users');
	}
	
	function setupAdminForm(&$form) {
		$edit = null;
		$id = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($edit) {
			$form->add(
				_('Order Status'),
				'OrderStatus',
				FORM_INPUT_TYPE_SELECT,
				true);
			$form->setValueType(FORM_VALUE_TYPE_INT);
			
			$form->addValue(
				SHOPPING_ORDER_STATUS_NEW,
				$this->status2Text(SHOPPING_ORDER_STATUS_NEW));
			
			$form->addValue(
				SHOPPING_ORDER_STATUS_PROCESSING,
				$this->status2Text(SHOPPING_ORDER_STATUS_PROCESSING));
			
			$form->addValue(
				SHOPPING_ORDER_STATUS_ACCEPTED,
				$this->status2Text(SHOPPING_ORDER_STATUS_ACCEPTED));
			
			$form->addValue(
				SHOPPING_ORDER_STATUS_DELIVERED,
				$this->status2Text(SHOPPING_ORDER_STATUS_DELIVERED));
				
			$form->addValue(
				SHOPPING_ORDER_STATUS_CANCELLED,
				$this->status2Text(SHOPPING_ORDER_STATUS_CANCELLED));
			
			$form->addValue(
				SHOPPING_ORDER_STATUS_REJECTED,
				$this->status2Text(SHOPPING_ORDER_STATUS_REJECTED));
				
			$form->add(
				"<div class='separator'></div>",
				null,
				FORM_STATIC_TEXT);
				
		} else {
			$form->add(
				"<b style='zoom: 1;'>"._("Order Owner")."</b>",
				null,
				FORM_STATIC_TEXT);
				
			$form->add(
				__("Username"),
				"UserName",
				FORM_INPUT_TYPE_TEXT,
				true);
			
			$form->addAdditionalText(
				"<a style='zoom: 1;' href='".url::uri('request, users').
					"&amp;request=".url::path() .
					"&amp;users=1' " .
					"class='shopping-order-new-order-add-user ajax-content-link' " .
					"title='".htmlspecialchars(_("Define the owner of this order"), ENT_QUOTES)."'>" .
					_("Select User") .
				"</a>");
				
			$form->add(
				"<br /><b style='zoom: 1;'>"._("Shopping Items")."</b><br />" .
				"<span style='zoom: 1;'>" .
					_("Click on the Add Items link below to add items to this order.").
				"</span>",
				null,
				FORM_STATIC_TEXT);
				
			$form->add(
				null,
				'ShoppingCart',
				FORM_STATIC_TEXT);
		}
		
		$orderform = new shoppingOrderForm();
		$orderform->id = 'neworder';
		$orderform->load(false);
		
		foreach($orderform->elements as $element)
			$form->elements[] = $element;
		
		unset($orderform);
		
		$form->add(
			_('Order Method'),
			null,
			FORM_OPEN_FRAME_CONTAINER,
			true);
			
		if ($edit) {
			$order = sql::fetch(sql::run(
				" SELECT `OrderMethod` FROM `{shoppingorders}` " .
				" WHERE `ID` = '".$id."'"));
			
			$ordermethod = null;
			
			if ($order['OrderMethod'])
				$ordermethod = shoppingOrderMethods::get($order['OrderMethod']);
			
			if ($ordermethod)
				$form->add(
					"<b>".$ordermethod['Title']."</b><br />" .
						$ordermethod['Description'],
					null,
					FORM_STATIC_TEXT);
			else
				$form->add(
					"<b class='red'>".
						_("No Order Method Specified") .
					"</b>",
					null,
					FORM_STATIC_TEXT);
			
			$form->add(
				_('Status'),
				'PaymentStatus',
				FORM_INPUT_TYPE_SELECT,
				true);
			$form->setValueType(FORM_VALUE_TYPE_INT);
			
			$form->addValue(
				SHOPPING_ORDER_PAYMENT_STATUS_PENDING,
				$this->paymentStatus2Text(SHOPPING_ORDER_PAYMENT_STATUS_PENDING));
			
			$form->addValue(
				SHOPPING_ORDER_PAYMENT_STATUS_PROCESSING,
				$this->paymentStatus2Text(SHOPPING_ORDER_PAYMENT_STATUS_PROCESSING));
			
			$form->addValue(
				SHOPPING_ORDER_PAYMENT_STATUS_PAID,
				$this->paymentStatus2Text(SHOPPING_ORDER_PAYMENT_STATUS_PAID));
			
			$form->addValue(
				SHOPPING_ORDER_PAYMENT_STATUS_CANCELLED,
				$this->paymentStatus2Text(SHOPPING_ORDER_PAYMENT_STATUS_CANCELLED));
			
			$form->addValue(
				SHOPPING_ORDER_PAYMENT_STATUS_FAILED,
				$this->paymentStatus2Text(SHOPPING_ORDER_PAYMENT_STATUS_FAILED));
			
			$form->addValue(
				SHOPPING_ORDER_PAYMENT_STATUS_EXPIRED,
				$this->paymentStatus2Text(SHOPPING_ORDER_PAYMENT_STATUS_EXPIRED));
			
			$form->add(
				_('Details'),
				'OrderMethodDetails',
				FORM_INPUT_TYPE_TEXTAREA);
			
			$form->setStyle('width: ' .
				(JCORE_VERSION >= '0.7'?
					'90%':
					'300px') .
				'; height: 200px;');
			
		} else {
			$form->add(
				_("Please select the order method you would " .
					"like to proceed with"),
				null,
				FORM_STATIC_TEXT);
					
			$ordermethods = new shoppingOrderMethods();
			$methods = $ordermethods->get();
			
			foreach($methods as $methodid => $method) {
				$form->add(
					$method['Title'],
					'ordermethod',
					FORM_INPUT_TYPE_RADIO,
					true,
					$methodid);
				
				$form->setElementKey(
					'EntryID', 
					'ordermethod'.$methodid);
					
				$form->addAdditionalText(
					"<span class='comment'>" .
						$method['Description'].
					"</span>");
					
				$ordermethodclass = 'shoppingOrderMethod'.
					$methodid;
				
				$ordermethod = new $ordermethodclass;
				$ordermethod->checkoutForm = $form;
				$ordermethod->setUp();
				
				foreach($ordermethod->elements as $element) {
					if ($form->get('ordermethod') != $methodid)
						$element['Required'] = false;
					
					$form->elements[] = $element;
				}
				
				unset($ordermethod);
			}
			
			unset($ordermethods);
		}
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
	}
	
	function setupAdminFormCart(&$form) {
		$submittedcart = null;
		$currencyleft = '';
		$currencyright = '';
		
		if (defined('SHOPPING_CART_CURRENCY')) {
			if (defined('SHOPPING_CART_CURRENCY_POSITION') && SHOPPING_CART_CURRENCY_POSITION &&
				stristr(SHOPPING_CART_CURRENCY_POSITION, 'right'))
				$currencyright = SHOPPING_CART_CURRENCY;
			else
				$currencyleft = SHOPPING_CART_CURRENCY;
		}
		
		if (JCORE_VERSION >= '0.7') {
			$items = null;
			$quantities = null;
			$customoptions = null;
			$prices = null;
			
			if (isset($_POST['ShoppingItemID']))
				$items = $_POST['ShoppingItemID'];
			
			if (isset($_POST['ShoppingItemQuantity']))
				$quantities = $_POST['ShoppingItemQuantity'];
			
			if (isset($_POST['ShoppingItemCustomOption']))
				$customoptions = $_POST['ShoppingItemCustomOption'];
			
			if (isset($_POST['ShoppingItemPrice']))
				$prices = $_POST['ShoppingItemPrice'];
		
			if ($items && is_array($items)) {			
				foreach($items as $key => $itemid) {
					$item = sql::fetch(sql::run(
						" SELECT * FROM `{shoppingitems}`" .
						" WHERE `ID` = '".(int)$itemid."'"));
					
					$submittedcart .= 
						"<tr>" .
							"<td>" .
								$item['RefNumber'] .
								"<input type='hidden' name='ShoppingItemID[]' " .
									"value='".$itemid."' />" .
							"</td>" .
							"<td class='auto-width'>" .
								$item['Title']."<br />" .
								"<a href='javascript://' class='shopping-order-new-order-custom-option comment'>" .
									_("Custom Options") .
								"</a><br />" .
								"<textarea name='ShoppingItemCustomOption[]' " .
									"style='" .
									(!$customoptions[$key]?
										"display: none;":
										null) .
									" width: 90%;'>" .
									$customoptions[$key] .
								"</textarea>" .
							"</td>" .
							"<td style='text-align: right;'>" . 
								"<input type='text' name='ShoppingItemQuantity[]' " .
									"value='".$quantities[$key]."' " .
									"style='width: 30px;' />" .
							"</td>" .
							"<td style='text-align: right;'>" .
								"<span class='nowrap'>" .
								$currencyleft."<input type='text' " .
									"name='ShoppingItemPrice[]' " .
									"value='".$prices[$key]."' " .
									"style='width: 50px;' />" .
								$currencyright .
								"</span>" .
							"</td>" .
							"<td style='text-align: right;'>" .
								"<span class='shopping-order-new-order-item-total-price nowrap'>" .
								$currencyleft . 
								number_format($quantities[$key]*$prices[$key], 2) .
								$currencyright .
								"</span>" .
							"</td>" .
							"<td align='center'>" .
								"<a class='shopping-order-new-order-remove-item' " .
									"href='javascript://'></a>" . 
							"</td>" .
						"</tr>";
				}
			}
		}
	
		$form->edit(
			'ShoppingCart',
			"<div class='shopping-cart shopping-order-cart'>" .
				"<table cellpadding='0' cellspacing='0' class='list'>" .
					"<thead>" .
					"<tr>" .
						"<th class='shopping-cart-ref-number'>" .
							"<span class='nowrap'>".
							_("Ref. Number").
							"</span>" .
						"</th>" .
						"<th class='shopping-cart-item'>" .
							"<span class='nowrap'>".
							_("Item").
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
						"</th>" .
						"<th class='shopping-cart-remove'>" .
							"<span class='nowrap'>".
							__("Remove").
							"</span>" .
						"</th>" .
					"</tr>" .
					"</thead>" .
					"<tbody>" .
					(JCORE_VERSION >= '0.7'?
						$submittedcart:
						null) .
					"</tbody>" .
				"</table>" .
				"<a href='".url::uri('request, shoppingitems').
					"&amp;request=".url::path() .
					"&amp;shoppingitems=1' " .
					"class='shopping-orders-cart-add-item ajax-content-link' " .
					"title='".htmlspecialchars(_("Add items to this order"), ENT_QUOTES)."'>" .
					_("Add Items") .
				"</a>" .
				(JCORE_VERSION >= '0.7'?
					"<a href='javascript://' " .
						"class='shopping-orders-cart-refresh' " .
						"title='".htmlspecialchars(_("Refresh order totals"), ENT_QUOTES)."'>" .
						_("Refresh") .
					"</a>":
					null) .
				"<div class='shopping-cart-totals shopping-order-cart-totals'>" .
					"<div class='shopping-cart-subtotal shopping-order-cart-subtotal'>" .
						"<span class='shopping-cart-total-title'>".
							_("Subtotal").":" .
						"</span>" .
						"<span class='bold'>" .
							shoppingOrders::constructPrice(0) .
						"</span>" .
					"</div>" .
					(JCORE_VERSION >= '0.7'?
						"<div class='shopping-cart-tax shopping-order-cart-tax'>" .
							"<span class='shopping-cart-total-title'>".
								_("Tax").":" .
							"</span>" .
							"<span class='bold'>" .
								shoppingOrders::constructPrice(0) .
							"</span>" .
						"</div>":
						null) .
					"<div class='shopping-cart-discount shopping-order-cart-discount'>" .
						"<span class='shopping-cart-total-title'>".
							_("Discount").":" .
						"</span>" .
						"<span class='bold'>" .
							shoppingOrders::constructPrice(0) .
						"</span>" .
					"</div>" .
					"<div class='shopping-cart-fee shopping-order-cart-fee'>" .
						"<span class='shopping-cart-total-title'>".
							htmlspecialchars(_("Shipping & Handling")).":" .
						"</span>" .
						"<span class='bold'>" .
							shoppingOrders::constructPrice(0) .
						"</span>" .
					"</div>" .
					"<div class='shopping-cart-grand-total shopping-order-cart-grand-total bold'>" .
						"<span class='shopping-cart-total-title'>".
							_("Grand Total").":" .
						"</span>" .
						"<span class='bold'>" .
							shoppingOrders::constructPrice(0) .
						"</span>" .
					"</div>" .
				"</div>" .
				"<div class='clear-both'></div>" .
			"</div>" .
			(JCORE_VERSION >= '0.7'?
			"<script type='text/javascript'>" .
			"jQuery(document).ready(function() {" .
				"jQuery.jCore.modules.shoppingOrders = {" .
					"admin: {" .
						"newOrder: {" .
							"cart: {" .
								"add: function(itemid, refnumber, title, quantity, price) {" .
									"cart = jQuery('#neworderform .shopping-order-cart tbody');" .
									
									"if (!refnumber || !title || !quantity || !price) {" .
										"itemtds = jQuery('#shoppingorderneworderitemlistrow'+itemid+' td');" .
										"if (!refnumber)" .
											"refnumber = jQuery(itemtds.get(0)).html();" .
										"if (!title)" .
											"title = jQuery(itemtds.get(1)).html();" .
										"if (!price)" .
											"price = jQuery(itemtds.get(3)).find('input').val();" .
										"if (!quantity) {" .
											"if (jQuery(itemtds.get(2)).find('select').val())" .
												"quantity = jQuery(itemtds.get(2)).find('select').val();" .
											"else" .
												"quantity = jQuery(itemtds.get(2)).find('input').val();" .
										"}" .
									"}" .
									
									"newitem = jQuery(" .
										"\"<tr>" .
											"<td>" .
												"\"+refnumber + \"" .
												"<input type='hidden' name='ShoppingItemID[]' " .
													"value='\"+itemid+\"' />" .
											"</td>" .
											"<td class='auto-width'>" .
												"\"+title+\"<br />" .
												"<a href='javascript://' class='shopping-order-new-order-custom-option comment'>" .
													_("Custom Options") .
												"</a><br />" .
												"<textarea name='ShoppingItemCustomOption[]' " .
													"style='display: none; width: 90%;'>" .
												"</textarea>" .
											"</td>" .
											"<td style='text-align: right;'>" . 
												"<input type='text' name='ShoppingItemQuantity[]' " .
													"value='\"+quantity+\"' " .
													"style='width: 30px;' />" .
											"</td>" .
											"<td style='text-align: right;'>" .
												"<span class='nowrap'>" .
												$currencyleft."<input type='text' " .
													"name='ShoppingItemPrice[]' " .
													"value='\"+price+\"' " .
													"style='width: 50px;' />" .
												$currencyright .
												"</span>" .
											"</td>" .
											"<td style='text-align: right;'>" .
												"<span class='shopping-order-new-order-item-total-price nowrap'>" .
												$currencyleft."\" + " .
													"jQuery.numberFormat(" .
														"(quantity*price), 2)" .
												" +\"".$currencyright .
												"</span>" .
											"</td>" .
											"<td align='center'>" .
												"<a class='shopping-order-new-order-remove-item' " .
													"href='javascript://'></a>" . 
											"</td>" .
										"</tr>\");" .
									
									"newitem.find('.shopping-order-new-order-remove-item').click(function() {" .
										"jQuery(this).parent().parent().remove();" .
									"});" .
									
									"newitem.find('.shopping-order-new-order-custom-option').click(function() {" .
										"jQuery(this).next().next().toggle();" .
									"});" .
									
									"newitem.find('.shopping-order-new-order-remove-item, .shopping-order-new-order-custom-option').click(function() {" .
										"jQuery.jCore.modules.shoppingOrders.admin.newOrder.cart.refresh();" .
									"});" .
									
									"cart.append(newitem);" .
									"jQuery.jCore.modules.shoppingOrders.admin.newOrder.cart.refresh();" .
									
									"return false;" .
								"}," .
								"refresh: function() {" .
									"cart = jQuery('#neworderform .shopping-order-cart tbody');" .
									"cart.find('tr').each(function () {" .
										"jthis = jQuery(this);" .
										"jthis.find('.shopping-order-new-order-item-total-price').html(" .
											"'".$currencyleft."' + " .
											"jQuery.numberFormat(jthis.find('input[name^=ShoppingItemQuantity]').val()*" .
											"jthis.find('input[name^=ShoppingItemPrice]').val(), 2) + '".$currencyright."');" .
									"});" .
									"jQuery.post('".url::uri('ALL')."?request=modules/shoppingorders&admin=1&totals=1&ajax=1', jQuery('#neworderform form').serialize(), function(data) {" .
										"jQuery('#neworderform .shopping-order-cart-totals .shopping-order-cart-subtotal span.bold')." .
											"html('".$currencyleft."' + jQuery.numberFormat(data.Subtotal, 2) + '".$currencyright."');" .
										"jQuery('#neworderform .shopping-order-cart-totals .shopping-order-cart-tax span.bold')." .
											"html('".$currencyleft."' + jQuery.numberFormat(data.Tax, 2) + '".$currencyright."');" .
										"jQuery('#neworderform .shopping-order-cart-totals .shopping-order-cart-discount span.bold')." .
											"html('".$currencyleft."' + jQuery.numberFormat(data.Discount, 2) + '".$currencyright."');" .
										"jQuery('#neworderform .shopping-order-cart-totals .shopping-order-cart-fee span.bold')." .
											"html('".$currencyleft."' + jQuery.numberFormat(data.Fee, 2) + '".$currencyright."');" .
										"jQuery('#neworderform .shopping-order-cart-totals .shopping-order-cart-grand-total span.bold')." .
											"html('".$currencyleft."' + jQuery.numberFormat(data.GrandTotal, 2) + '".$currencyright."');" .
									"}, 'json');" .
									(JCORE_VERSION >= '0.7'?
										"if (jQuery('.shopping-orders-cart-add-item').data('tipsy'))" .
											"jQuery('.shopping-orders-cart-add-item').tipsy('update');":
										"if (jQuery('.shopping-orders-cart-add-item').data('qtip'))" .
											"jQuery('.shopping-orders-cart-add-item').qtip('api').updatePosition();") .
								"}" .
							"}" .
						"}" .
					"}" .
				"};" .
				"jQuery('.shopping-order-new-order-remove-item').click(function() {" .
					"jQuery(this).parent().parent().remove();" .
				"});" .
				"jQuery('.shopping-order-new-order-custom-option').click(function() {" .
					"jQuery(this).next().next().toggle();" .
				"});" .
				"jQuery('.shopping-orders-cart-refresh, .shopping-order-new-order-remove-item, .shopping-order-new-order-custom-option').click(function() {" .
					"jQuery.jCore.modules.shoppingOrders.admin.newOrder.cart.refresh();" .
				"});" .
				"jQuery.jCore.modules.shoppingOrders.admin.newOrder.cart.refresh();" .
			"});" .
			"</script>":
			null),
			'ShoppingCart',
			FORM_STATIC_TEXT);
	}
	
	function getAdminNewOrderTotals($values = null) {
		if (!$values)
			$values = $_POST;
		
		$userid = 0;
		$totals = array(
			'Subtotal' => 0,
			'Tax' => 0,
			'Discount' => 0,
			'Fee' => 0,
			'GrandTotal' => 0,
			'Weight' => 0);
		
		if (!isset($values['ShoppingItemID']) || !is_array($values['ShoppingItemID']) || 
			!count($values['ShoppingItemID']))
			return $totals;
		
		if (isset($values['UserName']) && $values['UserName']) {
			$user = sql::fetch(sql::run(
				" SELECT `ID` FROM `{users}` " .
				" WHERE `UserName` = '".sql::escape($values['UserName'])."'"));
			
			if ($user)
				$userid = $user['ID'];
		}
		
		if (!$userid && $GLOBALS['USER']->loginok)
			$userid = $GLOBALS['USER']->data['ID'];
		
		$taxpercentage = shoppingCart::getTax();
		
		foreach($values['ShoppingItemID'] as $key => $itemid) {
			$item = sql::fetch(sql::run(
				" SELECT `Price`, `Weight`, `Taxable`" .
				" FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$itemid."'"));
			
			if (!$item)
				continue;
			
			if (!isset($values['ShoppingItemQuantity'][$key]))
				$values['ShoppingItemQuantity'][$key] = 1;
			
			if (!isset($values['ShoppingItemPrice'][$key]))
				$values['ShoppingItemPrice'][$key] = $item['Price'];
			
			if ($taxpercentage > 0 && $item['Taxable'] &&
				$values['ShoppingItemQuantity'][$key] * $values['ShoppingItemPrice'][$key] > 0)
			{
				$totals['Tax'] += round(($values['ShoppingItemQuantity'][$key] *
					$values['ShoppingItemPrice'][$key])*$taxpercentage/100, 2);
			}
			
			$totals['Subtotal'] += round($values['ShoppingItemQuantity'][$key] * 
				$values['ShoppingItemPrice'][$key], 2);
			$totals['Weight'] += $values['ShoppingItemQuantity'][$key]*$item['Weight'];
		}
		
		$totals['Discount'] = shoppingCart::getDiscount($totals['Subtotal']+$totals['Tax'],
			$userid);
		
		$totals['Fee'] = shoppingCart::getFee($totals['Subtotal']+$totals['Tax'], 
			$totals['Weight']);
		
		$totals['GrandTotal'] = round($totals['Subtotal']+$totals['Tax']-
			$totals['Discount']+$totals['Fee'], 2);
		
		return $totals;
	}
	
	function verifyAdmin(&$form = null) {
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				_("Order has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray()))
				return false;
				
			tooltip::display(
				_("Order has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		$ordermethodclass = 'shoppingOrderMethod'.
			$form->get('ordermethod');
		
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
		
		$ordermethod = new $ordermethodclass;
		$ordermethod->checkoutForm = $form;
		
		$paymentstatus = $ordermethod->process();
		$paymentresult = $ordermethod->processResult;
		
		unset($ordermethod);
		
		if (!$paymentstatus)
			return false;
		
		$items = null;
		$quantities = null;
		$customoptions = null;
		$prices = null;
		
		if (isset($_POST['ShoppingItemID']))
			$items = $_POST['ShoppingItemID'];
		
		if (isset($_POST['ShoppingItemQuantity']))
			$quantities = $_POST['ShoppingItemQuantity'];
		
		if (isset($_POST['ShoppingItemCustomOption']))
			$customoptions = $_POST['ShoppingItemCustomOption'];
		
		if (isset($_POST['ShoppingItemPrice']))
			$prices = $_POST['ShoppingItemPrice'];
		
		if (!$items || !is_array($items) || !count($items)) {
			tooltip::display(
				_("No items added / selected for this order! " .
					"Please select / add at least one item for the " .
					"new order to proceed."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$subtotal = 0;
		$weight = 0;
		$tax = 0;
		$taxpercentage = shoppingCart::getTax();
		
		foreach($items as $key => $itemid) {
			$item = sql::fetch(sql::run(
				" SELECT * FROM `{shoppingitems}`" .
				" WHERE `ID` = '".(int)$itemid."'"));
			
			if (!$item)
				continue;
			
			if (JCORE_VERSION >= '0.7') {
				$weight += $item['Weight']*$quantities[$key];
				
				if ($taxpercentage > 0 && $item['Taxable'] &&
					$quantities[$key]*$prices[$key] > 0)
					$tax += round(($quantities[$key]*$prices[$key])*$taxpercentage/100, 2);
			}
			
			$subtotal += ($prices[$key]*$quantities[$key]);
		}
		
		$ordernumber = shoppingOrders::genOrderID();
		$ordervalues = $form->getPostArray();
		
		$ordervalues['OrderID'] = $ordernumber;
		$ordervalues['UserID'] = $user['ID'];
		$ordervalues['PaymentStatus'] = $paymentstatus;
		$ordervalues['OrderMethod'] = $form->get('ordermethod');
		$ordervalues['OrderMethodDetails'] = $paymentresult;
		$ordervalues['Discount'] = shoppingCart::getDiscount($subtotal+$tax);
		$ordervalues['Fee'] = shoppingCart::getFee($subtotal+$tax, $weight);
		$ordervalues['Tax'] = $tax;
		$ordervalues['Subtotal'] = $subtotal;
		
		$orderid = $this->add($ordervalues);
		
		if (!$orderid)
			return false;
			
		$orderitems = new shoppingOrderItems();
		
		foreach($items as $key => $itemid) {
			$item = sql::fetch(sql::run(
				" SELECT * FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$itemid."'"));
			
			$itemvalues['ShoppingOrderID'] = $orderid;
			$itemvalues['ShoppingItemID'] = $itemid;
			$itemvalues['Price'] = $prices[$key];
			$itemvalues['Quantity'] = $quantities[$key];
			
			if (JCORE_VERSION >= '0.7')
				$itemvalues['ShoppingItemOptions'] = $customoptions[$key];
			
			$newid = $orderitems->add($itemvalues);
			
			if ($newid) {
				// Update items AvailableQuantity value
				sql::run(
					" UPDATE `{shoppingitems}` SET " .
					" `AvailableQuantity` = `AvailableQuantity` - ".(int)$quantities[$key].", " .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".$itemid."'");
			
			} else {
				tooltip::display(
					_("There were some errors while processing your order (some " .
						"items couldn't be added to your order). Please contact " .
						"us with this error and your order number as soon as possible."),
					TOOLTIP_ERROR);
			}
		}
		
		unset($orderitems);
		
		tooltip::display(
			sprintf(_("Order has been successfully created.<br /> " .
					"The confirmation / tracking number is <b>%s</b>."), $ordernumber) .
				"<br /><br />".
				"<a href='".url::uri('id, edit, delete, limit, search, status') .
					"&id=".$orderid."'>" .
					_("View Order") .
				"</a>" .
				" - " .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$orderid."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
				
		shoppingOrders::sendNotificationEmail($orderid);
		
		unset($_POST['ShoppingItemID']);
		unset($_POST['ShoppingItemQuantity']);
		unset($_POST['ShoppingItemCustomOption']);
		unset($_POST['ShoppingItemPrice']);
		
		$form->reset();
		return true;
	}
	
	function displayAdminShoppingItems() {
		$shoppingid = null;
		$search = null;
		
		$shoppingids = null;
		$shoppingurl = shopping::getURL();
		
		if (isset($_POST['ajaxshoppingid']))
			$shoppingid = (int)$_POST['ajaxshoppingid'];
		
		if (isset($_GET['ajaxshoppingid']))
			$shoppingid = (int)$_GET['ajaxshoppingid'];
		
		if (isset($_POST['ajaxsearch']))
			$search = trim(strip_tags($_POST['ajaxsearch']));
		
		if (isset($_GET['ajaxsearch']))
			$search = trim(strip_tags($_GET['ajaxsearch']));
		
		if (!isset($search) && !isset($_GET['ajaxlimit']))
			echo 
				"<div class='shopping-order-new-order-items'>";
		
		echo
				"<div class='shopping-order-new-order-items-search' " .
					"style='margin-right: 20px;'>" .
					"<form action='".url::uri('ajaxshoppingid, ajaxsearch, ajaxlimit, ajax')."' method='post' " .
						"class='ajax-form' " .
						"target='.shopping-order-new-order-items'>" .
					__("Search").": " .
					"<select name='ajaxshoppingid' " .
						"onchange=\"jQuery('.shopping-order-new-order-items form').ajaxSubmit();\">" .
					"<option value=''></option>";
					
		foreach(shopping::getTree() as $row)
			echo
				"<option value='".$row['ID']."' " .
					($row['ID'] == $shoppingid?
						"selected='selected'":
						null) .
					">" . 
					($row['SubCategoryOfID']?
						str_replace(' ', '&nbsp;', 
							str_pad('', $row['PathDeepnes']*4, ' ')).
						"|- ":
						null) .
					$row['Title'] .
				"</option>";
		
		echo
					"</select> " .
					"<input type='search' " .
						"name='ajaxsearch' " .
						"value='".
							htmlspecialchars($search, ENT_QUOTES).
						"' results='5' placeholder='".htmlspecialchars(__("search..."), ENT_QUOTES)."' />" .
					"</form>" .
				"</div>" .
				"<br />";
				
		if ($shoppingid) {
			$category = sql::fetch(sql::run(
				" SELECT * FROM `{shoppings}` " .
				" WHERE !`Deactivated`" .
				" AND `ID` = '".$shoppingid."'"));
				
			if (!$category['Items']) {
				$shoppingids[] = $shoppingid;
				
				foreach(shopping::getTree($shoppingid) as $category)
					$shoppingids[] = $category['ID'];
			}
		}
		
		echo
				"<table cellpadding='0' cellspacing='0' class='list'>" .
					"<thead>" .
					"<tr>" .
						"<th>" .
							"<span class='nowrap'>".
							_("Add").
							"</span>" .
						"</th>" .
						"<th>" .
							"<span class='nowrap'>".
							_("Ref. Number").
							"</span>" .
						"</th>" .
						"<th>" .
							"<span class='nowrap'>".
							_("Item").
							"</span>" .
						"</th>" .
						"<th style='text-align: right;'>" .
							"<span class='nowrap'>".
							_("Quantity").
							"</span>" .
						"</th>" .
						"<th style='text-align: right;'>" .
							"<span class='nowrap'>".
							_("Unit Price").
							"</span>" .
						"</th>" .
						"<th style='text-align: right;'>" .
							"<span class='nowrap'>".
							_("Stock").
							"</span>" .
						"</th>" .
					"</tr>" .
					"</thead>" .
					"<tbody>";
					
		$paging = new paging(10,
			'&amp;ajaxsearch='.urlencode($search) .
			'&amp;ajaxshoppingid='.$shoppingid);
		
		$paging->track('ajaxlimit');
		$paging->ajax = true;
		
		$rows = sql::run(
			" SELECT * FROM `{shoppingitems}`" .
			" WHERE !`Deactivated`" .
			($shoppingid && !$shoppingids?
				" AND `ShoppingID` = '".$shoppingid."'":
				null) .
			($shoppingids?
				" AND `ShoppingID` IN (".implode(',',$shoppingids).")":
				null) .
			($search?
				" AND (`Title` LIKE '%".sql::escape($search)."%'" .
					" OR `Description` LIKE '%".sql::escape($search)."%'" .
					" OR `Keywords` LIKE '%".sql::escape($search)."%')":
				null) .
			" ORDER BY `OrderID`, `ID` DESC" .
			" LIMIT ".$paging->limit);
		
		$paging->setTotalItems(sql::count());
		
		$i = 1;
		$total = sql::rows($rows);
		
		while ($row = sql::fetch($rows)) {
			$price = $row['Price'];
			$specialprice = null;
			
			if (JCORE_VERSION >= '0.7' && $row['SpecialPrice'] != '') {
				if ((!$row['SpecialPriceStartDate'] || 
						$row['SpecialPriceStartDate'] <= date('Y-m-d')) &&
					(!$row['SpecialPriceEndDate'] || 
						$row['SpecialPriceEndDate'] >= date('Y-m-d')))
				{
					$specialprice = $row['SpecialPrice'];
				}
			}
			
			echo
				"<tr id='shoppingorderneworderitemlistrow".$row['ID']."' ".
					($i%2?" class='pair'":NULL).">" .
					"<td align='center'>" .
						"<a href='javascript://' " .
							"onclick=\"jQuery.jCore.modules.shoppingOrders.admin.newOrder.cart.add(" .
								$row['ID']."," .
								"'".$row['RefNumber']."'," .
								"'".htmlspecialchars($row['Title'], ENT_QUOTES)."'," .
								"jQuery('#newordershoppingitemquantity".$row['ID']."').val()," .
								"'".
									(isset($specialprice)?
										$specialprice:
										$price) .
									"');\" " .
							"class='shopping-order-new-order-add-item'>" .
						"</a>" .
					"</td>" .
					"<td>" .
						$row['RefNumber'] .
					"</td>" .
					"<td class='auto-width'>" .
						"<a href='".$shoppingurl .
							"&amp;shoppingid=".$row['ShoppingID'].
							"&amp;shoppingitemid=".$row['ID'] ."' " .
							"target='_blank' class='bold'>" .
							$row['Title'] .
						"</a>" .
					"</td>" .
					"<td style='text-align: right;'>";
				
			if ($row['ShowQuantityPicker']) {
				echo
						"<select id='newordershoppingitemquantity".$row['ID']."'>";
				
				if (!$row['MaxQuantityAtOnce'])
					$row['MaxQuantityAtOnce'] = 30;
				
				for($i = 1; $i < $row['MaxQuantityAtOnce']; $i++) {	
					echo
							"<option>".$i."</option>";
				}
							
				echo
						"</select>";
				
			} else {
				echo	"1" .
						"<input id='newordershoppingitemquantity".$row['ID']."' " .
							"type='hidden' value='1' />";
			}
			
			echo
					"</td>" .
					"<td style='text-align: right;'>" .
						"<span class='nowrap'>";
			
			if (isset($specialprice)) {
				shoppingOrders::displayPrice($specialprice);
				
				echo 
					"<br />" .
					"<span class='comment' style='text-decoration: line-trough;'>" .
						shoppingOrders::constructPrice($price) .
					"</span>";
				
			} else {
				shoppingOrders::displayPrice($price);
			}
			
			echo
						"</span>" .
						"<input type='hidden' value='".
							(isset($specialprice)?
								$specialprice:
								$price) .
							"' />" .
					"</td>" .
					"<td style='text-align: right;'>" .
						"<span class='nowrap'>" .
						(!isset($row['AvailableQuantity']) || $row['AvailableQuantity']?
							_("Available"):
							_("Unavailable")) .
						"</span>" .
					"</td>" .
				"</tr>";
			
			$i++;
		}
		
		echo
					"</tbody>" .
				"</table>" .
				"<br />";
				
		$paging->display();
		
		if (!isset($search) && !isset($_GET['ajaxlimit']))
			echo
				"</div>";
	}
	
	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				_("Order ID / Submitted on")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				_("Grand Total")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				_("Status")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
		echo
			"<th><span class='nowrap'>".
				__("Comments")."</span></th>";
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
			"<td class='auto-width'>" .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."' " .
				" class='bold'>" .
				$row['OrderID'] .
				"</a>" .
				"<div class='comment' style='padding-left: 10px;'>" .
				calendar::datetime($row['TimeStamp'])." ";
		
		$GLOBALS['USER']->displayUserName($user, __('by %s'));
		
		echo
				"</div>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				"<span class='nowrap'>";
		
		shoppingOrders::displayPrice($row['Subtotal']+
			(isset($row['Tax'])?$row['Tax']:0)-$row['Discount']+$row['Fee']);
		
		echo
				"</span>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				"<div class='shopping-order-status nowrap" .
				($row['OrderStatus'] == SHOPPING_ORDER_STATUS_NEW?
					" bold'":
					null) .
					"'>" .
					$this->status2Text($row['OrderStatus']) .
				"</div>" .
				"<div class='shopping-order-payment-status nowrap'>";
		
		$this->displayOrderMethodStatus($row);
		
		echo
				"</div>" .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='admin-link comments' " .
					"title='".htmlspecialchars(__("Comments"), ENT_QUOTES).
						" (".$row['Comments'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/shoppingordercomments'>" .
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
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}

	function displayAdminListItemSelected(&$row) {
		$this->displayCart($row);
		$this->displayOrderInfo($row);
		$this->displayOrderMethod($row);
		
		if (JCORE_VERSION >= '0.5')		
			$this->displayDownloads($row);
	}
	
	function displayAdminList(&$rows) {
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
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
				"<tr class='shopping-order-".
					strtolower($this->paymentStatus2Text($row['PaymentStatus'])) .
					($i%2?" pair":null)."'>";
			
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
			"</table>";
		
		echo "<br />";
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}

	function displayAdminListSearch($ordertypes = null) {
		$search = null;
		$status = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['status']))
			$status = (int)$_GET['status'];
		
		if (!$ordertypes)
			$ordertypes = array(
				SHOPPING_ORDER_STATUS_NEW,
				SHOPPING_ORDER_STATUS_PROCESSING,
				SHOPPING_ORDER_STATUS_ACCEPTED,
				SHOPPING_ORDER_STATUS_DELIVERED,
				SHOPPING_ORDER_STATUS_CANCELLED,
				SHOPPING_ORDER_STATUS_REJECTED);
		
		echo
			"<input type='hidden' name='path' value='".admin::path()."' />" .
			"<input type='search' name='search' value='".
				htmlspecialchars($search, ENT_QUOTES).
				"' results='5' placeholder='".htmlspecialchars(__("search..."), ENT_QUOTES)."' /> " .
			"<select name='status' style='width: 100px;' " .
				"onchange='this.form.submit();'>" .
				"<option value=''>".__("All")."</option>";
		
		foreach($ordertypes as $type)
			echo
				"<option value='".$type."'" .
					($status == $type?" selected='selected'":null) .
					">".$this->status2Text($type)."</option>";
		
		echo
			"</select> " .
			"<input type='submit' value='" .
				htmlspecialchars(__("Search"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminOrders($ordertypes = null) {
		$search = null;
		$status = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['status']))
			$status = (int)$_GET['status'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		echo
			"<div style='float: right;'>" .
				"<form action='".url::uri('ALL')."' method='get'>";
		
		$this->displayAdminListSearch();
		
		echo
				"</form>" .
			"</div>";
		
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		$this->shoppingURL = shopping::getURL();
		
		echo
			"<div class='admin-content'>";
				
		$form = new form(
				($edit?
					_("Edit Order"):
					_("New Order")),
				($edit?
					'editorder':
					'neworder'));
		
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
		
		$paging = new paging(20);
		
		$rows = sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE 1" .
			($ordertypes && is_array($ordertypes)?
				" AND (`OrderStatus` IN (".implode(',', $ordertypes).") " .
				($id?
					" OR `ID` = '".(int)$id."'":
					null) .
				") ":
				null) .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			($status?
				" AND `OrderStatus` = '".(int)$status."'":
				null) .
			($search?
				sql::search(
					$search,
					array('OrderID', 'OrderMethodDetails')):
				null) .
			" ORDER BY `ID` DESC" .
			" LIMIT ".$paging->limit);
			
		$paging->setTotalItems(sql::count());
				
		if ($paging->items)
			$this->displayAdminList($rows);
		else
			tooltip::display(
				_("No orders found."),
				TOOLTIP_NOTIFICATION);
		
		$paging->display();
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{shoppingorders}` " .
					" WHERE `ID` = '".$id."'"));
					
				$form->setValues($row);
			}
			
			if (!$edit)
				$this->setupAdminFormCart($form);
			
			echo
				"<a name='adminform'></a>";
			
			if (!$ordertypes || !is_array($ordertypes) || $edit ||
				in_array(SHOPPING_ORDER_STATUS_NEW, $ordertypes))
				$this->displayAdminForm($form);
			
			if (JCORE_VERSION < '0.7') {
				echo
					"<script type='text/javascript'>" .
						"window.onload = function() {" .
						"jQuery.jCore.modules.shoppingOrders.admin.newOrder.cart.currency = '".SHOPPING_CART_CURRENCY."';";
						
				$items = null;
				$quantities = null;
				$prices = null;
				
				if (isset($_POST['ShoppingItemID']))
					$items = $_POST['ShoppingItemID'];
				
				if (isset($_POST['ShoppingItemQuantity']))
					$quantities = $_POST['ShoppingItemQuantity'];
				
				if (isset($_POST['ShoppingItemPrice']))
					$prices = $_POST['ShoppingItemPrice'];
			
				if ($items && is_array($items)) {			
					foreach($items as $key => $itemid) {
						$item = sql::fetch(sql::run(
							" SELECT * FROM `{shoppingitems}`" .
							" WHERE `ID` = '".(int)$itemid."'"));
						
						echo
							"jQuery.jCore.modules.shoppingOrders.admin.newOrder.cart.add(".
								$itemid.", " .
								"'".htmlspecialchars($item['RefNumber'], ENT_QUOTES)."', " .
								"'<a href=\"".$this->shoppingURL .
									"&amp;shoppingid=".$item['ShoppingID'].
									"&amp;shoppingitemid=".$item['ID'] ."\" " .
									"target=\"_blank\" class=\"bold\">" .
									htmlspecialchars($item['Title'], ENT_QUOTES) .
								"</a>', " .
								$quantities[$key].", " .
								$prices[$key].");";
					}
				}
				
				echo 		
						"}" .
					"</script>";
			}
		}
		
		unset($form);
		
		echo
			"</div>"; //admin-content
	}
	
	function displayAdminTitle($ownertitle = null) {
		echo
			_('Shopping Orders Administration');
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdminSections() {
		$new = 0;
		$processed = 0;
		
		if (ADMIN_ITEMS_COUNTER_ENABLED) {
			$new = $this->countAdminItems(array(
				SHOPPING_ORDER_STATUS_NEW,
				SHOPPING_ORDER_STATUS_PROCESSING));
			$processed = $this->countAdminItems(array(
				SHOPPING_ORDER_STATUS_ACCEPTED,
				SHOPPING_ORDER_STATUS_CANCELLED,
				SHOPPING_ORDER_STATUS_DELIVERED,
				SHOPPING_ORDER_STATUS_REJECTED));
		}
			
		echo
			"<div class='admin-section-item as-modules-shoppingorders as-shopping-new-orders'>" .
				($new?
					"<span class='counter'>" .
						"<span>" .
							"<span>" .
							(int)$new .
							"</span>" .
						"</span>" .
					"</span>":
					null) .
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/shoppingneworders' " .
					"title='".htmlspecialchars(_("Handle pending and processing orders"), ENT_QUOTES).
					"'>" .
					"<span>" .
					_("New Orders")."" .
					"</span>" .
				"</a>" .
			"</div>" .
			"<div class='admin-section-item as-modules-shoppingorders as-shopping-processed-orders'>" .
				($processed?
					"<span class='counter'>" .
						"<span>" .
							"<span>" .
							(int)$processed .
							"</span>" .
						"</span>" .
					"</span>":
					null) .
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/shoppingprocessedorders' " .
					"title='".htmlspecialchars(_("Lookup and update processed orders"), ENT_QUOTES).
					"'>" .
					"<span>" .
					_("Processed Orders")."" .
					"</span>" .
				"</a>" .
			"</div>";
	}
	
	function displayAdminDashboard() {
		echo
			"<div class='admin-content'>";
		
		echo 
			"<div tabindex='0' class='fc" .
				form::fcState('fcshod', true) .
				"'>" .
				"<a class='fc-title' name='fcshod'>";
		
		$this->displayAdminTitle();
		
		echo
				"</a>" .
				"<div class='fc-content'>";
		
		$this->displayAdminDashboardSales();
		$this->displayAdminSections();
		
		echo
					"<div class='clear-both'></div>" .
				"</div>" .
			"</div>";
		
		$this->displayAdminDashboardOrders();
		$this->displayAdminDashboardBestsellers();
		$this->displayAdminDashboardMostViewedProducts();
		
		echo
				"<div class='clear-both'></div>" .
			"</div>"; //admin-content
	}
	
	function displayAdminDashboardSales() {
		$startdate = date("Y-m-01");
		$enddate = date("Y-m-d");
		
		if (isset($_GET['startdate']))
			$startdate = $_GET['startdate'];
		
		if (isset($_GET['enddate']))
			$enddate = $_GET['enddate'];
		
		$sales = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`, " .
			" SUM(`Subtotal`" .
				(JCORE_VERSION >= '0.7'?
					"+`Tax`":
					null) .
				"-`Discount`+`Fee`) AS `Total`" .
			" FROM `{shoppingorders}`" .
			" WHERE DATE(`TimeStamp`) >= " .
				($startdate?
					"'".sql::escape($startdate)."'":
					"DATE_FORMAT(NOW(), '%Y-%m-01')") .
			($enddate?
				" AND DATE(`TimeStamp`) <= " .
					"'".sql::escape($enddate)."'":
				null) .
			" AND `PaymentStatus` = '".SHOPPING_ORDER_PAYMENT_STATUS_PAID."'" .
			" LIMIT 1"));
		
		echo
			"<div class='shopping-orders-sales align-right'>" .
				"<form action='?' method='get'>" .
				"<input type='hidden' name='path' value='".admin::path()."' />" .
				"<table>" .
				"<tr>" .
					"<td class='shopping-orders-sales-total' style='text-align: right;'>" .
						"<div class='nowrap'>" .
							(int)$sales['Rows']." ".strtolower(_("Sales")) .
						"</div>" .
						"<h1 class='nowrap' style='margin: 0;'><b>" .
							$this->constructPrice($sales['Total']) .
						"</b></h1>" .
						"<input type='submit' name='refresh' value='" .
							__("Refresh")."' class='button' style='margin: 0;' />" .
					"</td>" .
					"<td>" .
						"<span class='nowrap'>&nbsp; &nbsp;</span>" .
					"</td>" .
					"<td class='shopping-orders-sales-range'>" .
						"<div class='nowrap'>" .
							"<div class='shopping-orders-sales-range-title'>" .
								_("Start / End Date") .
							"</div>" .
							"<div class='spacer'></div>" .
							"<div class='shopping-orders-sales-range-start'>" .
								"<input type='date' class='calendar-input' style='width: 90px;' " .
									"title='".__("e.g. 2010-07-21")."' name='startdate' " .
									"value='".htmlspecialchars($startdate, ENT_QUOTES)."' />" .
							"</div>" .
							"<div class='shopping-orders-sales-range-end'>" .
								"<input type='date' class='calendar-input' style='width: 90px;' " .
									"title='".__("e.g. 2010-07-21")."' name='enddate' " .
									"value='".htmlspecialchars($enddate, ENT_QUOTES)."' />" .
							"</div>" .
						"</div>" .
					"</td>" .
				"</tr>" .
				"</table>" .
				"</form>" .
			"</div>";
	}
	
	function displayAdminDashboardOrders() {
		$startdate = null;
		$enddate = null;
		
		if (isset($_GET['startdate']))
			$startdate = $_GET['startdate'];
		
		if (isset($_GET['enddate']))
			$enddate = $_GET['enddate'];
		
		$paging = new paging(10,
			"&amp;request=admin/modules/shoppingorders" .
			"&amp;orders=1");
		
		$paging->ajax = true;
		
		$rows = sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE DATE(`TimeStamp`) >= " .
				($startdate?
					"'".sql::escape($startdate)."'":
					"DATE_FORMAT(NOW(), '%Y-%m-01')") .
			($enddate?
				" AND DATE(`TimeStamp`) <= " .
					"'".sql::escape($enddate)."'":
				null) .
			" ORDER BY `ID` DESC" .
			" LIMIT ".$paging->limit);
		
		$paging->setTotalItems(sql::count());
		
		if (!$this->ajaxRequest) {
			echo 
			"<div tabindex='0' class='fc" .
				form::fcState('fcshos') .
				"'>" .
				"<a class='fc-title' name='fcshos'>" .
					"<div class='align-right'>" .
						$paging->items." ".strtolower(_("Orders")) .
					"</div>" .
					_("Orders") .
				"</a>" .
				"<div class='fc-content'>";
		
			$totals = sql::fetch(sql::run(
				" SELECT" .
				" SUM(`Subtotal`" .
					(JCORE_VERSION >= '0.7'?
						"+`Tax`":
						null) .
					"-`Discount`+`Fee`) AS `Total`," .
				(JCORE_VERSION >= '0.7'?
					" SUM(`Tax`) AS `Tax`,":
					null) .
				" SUM(`Fee`) AS `Fee`," .
				" SUM(`Discount`) AS `Discount`" .
				" FROM `{shoppingorders}`" .
				" WHERE DATE(`TimeStamp`) >= " .
					($startdate?
						"'".sql::escape($startdate)."'":
						"DATE_FORMAT(NOW(), '%Y-%m-01')") .
				($enddate?
					" AND DATE(`TimeStamp`) <= " .
						"'".sql::escape($enddate)."'":
					null) .
				" LIMIT 1"));
			
			echo 
				"<table width='100%' style='position: relative; top: -7px;'>" .
				"<tr>" .
					"<td style='text-align: center;'>" .
						"<div class='nowrap'>" .
							_("Grand Total") .
						"</div>" .
						"<h3 class='nowrap' style='margin: 0;'><b>" .
							shoppingOrders::constructPrice($totals['Total']) .
						"</b></h3>" .
					"</td>" .
					(JCORE_VERSION >= '0.7'?
						"<td style='text-align: center;'>" .
							"<div class='nowrap'>" .
								_("Tax") .
							"</div>" .
							"<h3 class='nowrap' style='margin: 0;'><b>" .
								shoppingOrders::constructPrice($totals['Tax']) .
							"</b></h3>" .
						"</td>":
						null) .
					"<td style='text-align: center;'>" .
						"<div class='nowrap'>" .
							_("Shipping & Handling") .
						"</div>" .
						"<h3 class='nowrap' style='margin: 0;'><b>" .
							shoppingOrders::constructPrice($totals['Fee']) .
						"</b></h3>" .
					"</td>" .
					"<td style='text-align: center;'>" .
						"<div class='nowrap'>" .
							_("Discount") .
						"</div>" .
						"<h3 class='nowrap' style='margin: 0;'><b>" .
							shoppingOrders::constructPrice($totals['Discount']) .
						"</b></h3>" .
					"</td>" .
				"</tr>" .
				"</table>" .
				"<div class='shopping-orders-dashboard-orders'>";
		}
		
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
				"<tr class='shopping-order-".
					strtolower($this->paymentStatus2Text($row['PaymentStatus'])) .
					($i%2?" pair":null)."'>";
			
			$user = $GLOBALS['USER']->get($row['UserID']);
					
			echo
				"<td class='auto-width'>" .
					"<a href='?path=".admin::path()."/" .
						(in_array($row['OrderStatus'], array(
							SHOPPING_ORDER_STATUS_NEW,
							SHOPPING_ORDER_STATUS_PROCESSING))?
							"shoppingneworders":
							"shoppingprocessedorders") .
						"&amp;id=".$row['ID']."" .
						"&amp;search=".$row['OrderID']."' " .
					" class='bold' target='_blank'>" .
					$row['OrderID'] .
					"</a>" .
					"<div class='comment' style='padding-left: 10px;'>" .
					calendar::datetime($row['TimeStamp'])." ";
			
			$GLOBALS['USER']->displayUserName($user, __('by %s'));
			
			echo
					"</div>" .
				"</td>" .
				"<td style='text-align: right;'>" .
					"<span class='nowrap'>";
			
			shoppingOrders::displayPrice($row['Subtotal']+
				(isset($row['Tax'])?$row['Tax']:0)-$row['Discount']+$row['Fee']);
			
			echo
					"</span>" .
				"</td>" .
				"<td style='text-align: right;'>" .
					"<div class='shopping-order-status nowrap" .
					($row['OrderStatus'] == SHOPPING_ORDER_STATUS_NEW?
						" bold'":
						null) .
						"'>" .
						$this->status2Text($row['OrderStatus']) .
					"</div>" .
					"<div class='shopping-order-payment-status nowrap'>";
			
			$this->displayOrderMethodStatus($row);
			
			echo
					"</div>" .
				"</td>" .
				"<td align='center'>" .
					"<a class='admin-link comments' " .
						"title='".htmlspecialchars(__("Comments"), ENT_QUOTES).
							" (".$row['Comments'].")' " .
						"href='?path=".admin::path()."/" .
						(in_array($row['OrderStatus'], array(
							SHOPPING_ORDER_STATUS_NEW,
							SHOPPING_ORDER_STATUS_PROCESSING))?
							"shoppingneworders":
							"shoppingprocessedorders") .
						"/".$row['ID']."/shoppingordercomments' target='_blank'>" .
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
				"</td>";
			
			echo
				"</tr>";
				
			$i++;
		}
		
		echo 
				"</tbody>" .
			"</table>";
		
		echo "<br />";
		
		$paging->display();
		
		if (!$this->ajaxRequest)
			echo
					"</div>" .
					"<div class='clear-both'></div>" .
				"</div>" .
			"</div>";
	}
	
	function displayAdminDashboardBestsellers() {
		$startdate = null;
		$enddate = null;
		
		if (isset($_GET['startdate']))
			$startdate = $_GET['startdate'];
		
		if (isset($_GET['enddate']))
			$enddate = $_GET['enddate'];
		
		$paging = new paging(10,
			"&amp;request=admin/modules/shoppingorders" .
			"&amp;orderedproducts=1");
		
		$paging->ajax = true;
		
		sql::run(
			" CREATE TEMPORARY TABLE `{TMPMostOrderedItems}`" .
			" (`ShoppingItemID` mediumint(8) unsigned NOT NULL default '0'," .
			" `Sales` int(10) unsigned NOT NULL default '0')");
		
		sql::run(
			" INSERT INTO `{TMPMostOrderedItems}`" .
			" SELECT `ShoppingItemID`," .
			" COUNT(`ShoppingItemID`) AS `Sales`" .
			" FROM `{shoppingorders}`" .
			" LEFT JOIN `{shoppingorderitems}` ON `{shoppingorderitems}`.`ShoppingOrderID` = " .
				"`{shoppingorders}`.`ID`" .
			" WHERE DATE(`{shoppingorders}`.`TimeStamp`) >= " .
				($startdate?
					"'".sql::escape($startdate)."'":
					"DATE_FORMAT(NOW(), '%Y-%m-01')") .
			($enddate?
				" AND DATE(`{shoppingorders}`.`TimeStamp`) <= " .
					"'".sql::escape($enddate)."'":
				null) .
			" GROUP BY `ShoppingItemID`");
		
		$rows = sql::run(
			" SELECT * FROM `{TMPMostOrderedItems}`" .
			" LEFT JOIN `{shoppingitems}` ON `{shoppingitems}`.`ID` =" .
				" `{TMPMostOrderedItems}`.`ShoppingItemID`" .
			" ORDER BY `Sales` DESC" .
			" LIMIT ".$paging->limit);
		
		$paging->setTotalItems(sql::count());
		
		if (!$this->ajaxRequest)
			echo 
			"<div tabindex='0' class='fc" .
				form::fcState('fcshob') .
				"'>" .
				"<a class='fc-title' name='fcshob'>" .
					"<div class='align-right'>" .
						$paging->items." ".strtolower(_("Products")) .
					"</div>" .
					_("Bestsellers") .
				"</a>" .
				"<div class='fc-content'>";
		
		echo 
			"<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>" .
					"<th><span class='nowrap'>".
						__("Title / Created on")."</span></th>" .
					"<th><span class='nowrap'>".
						__("Orders")."</span></th>" .
					"<th><span class='nowrap'>".
						__("Comments")."</span></th>" .
				"</tr>" .
				"</thead>" .
				"<tbody>";
		
		$i = 0;		
		while($row = sql::fetch($rows)) {
			$item = sql::fetch(sql::run(
				" SELECT * FROM `{shoppingitems}`" .
				" WHERE `ID` = '".$row['ShoppingItemID']."'"));
			
			if (!$item)
				continue;
			
			$row += $item;
			
			echo 
				"<tr".($i%2?" class='pair'":NULL).">";
			
			$user = $GLOBALS['USER']->get($row['UserID']);
			
			echo
				"<td class='auto-width' " .
					($row['Deactivated']?
						"style='text-decoration: line-through;' ":
						null).
					">" .
					"<a href='?path=admin/modules/shopping/" .
						$row['ShoppingID']."/shoppingitems" .
						"&amp;id=".$row['ID']."" .
						"&amp;search=".htmlspecialchars($row['Title'], ENT_QUOTES)."' " .
					" class='bold' target='_blank'>" .
						$row['Title'] .
					"</a>" .
					"<div class='comment' style='padding-left: 10px;'>" .
						calendar::dateTime($row['TimeStamp'])." " .
						($user?
							$GLOBALS['USER']->constructUserName($user, __('by %s')):
							null) .
						", ".sprintf(__("%s views"), $row['Views']) .
					"</div>" .
				"</td>" .
				"<td style='text-align: right;'>" .
					"<span class='nowrap'>" .
						$row['Sales'] .
					"</span>" .
				"</td>" .
				"<td align='center'>" .
					"<a class='admin-link comments' " .
						"title='".htmlspecialchars(__("Comments"), ENT_QUOTES).
							" (".$row['Comments'].")' " .
						"href='?path=admin/modules/shopping/" .
							$row['ShoppingID']."/shoppingitems/" .
							$row['ID']."/shoppingitemcomments' " .
						"target='_blank'>" .
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
				"</td>";
			
			echo
				"</tr>";
				
			$i++;
		}
		
		sql::run("DROP TEMPORARY TABLE `{TMPMostOrderedItems}`");
		
		echo 
				"</tbody>" .
			"</table>";
		
		echo "<br />";
		
		$paging->display();
		
		if (!$this->ajaxRequest)
			echo
					"<div class='clear-both'></div>" .
				"</div>" .
			"</div>";
	}
	
	function displayAdminDashboardMostViewedProducts() {
		$startdate = null;
		$enddate = null;
		
		if (isset($_GET['startdate']))
			$startdate = $_GET['startdate'];
		
		if (isset($_GET['enddate']))
			$enddate = $_GET['enddate'];
		
		$paging = new paging(10,
			"&amp;request=admin/modules/shoppingorders" .
			"&amp;viewedproducts=1");
		
		$paging->ajax = true;
		
		sql::run(
			" CREATE TEMPORARY TABLE `{TMPMostViewedOrderItems}`" .
			" (`ShoppingItemID` mediumint(8) unsigned NOT NULL default '0')");
		
		sql::run(
			" INSERT INTO `{TMPMostViewedOrderItems}`" .
			" SELECT `ShoppingItemID` FROM `{shoppingorders}`" .
			" LEFT JOIN `{shoppingorderitems}` ON `{shoppingorderitems}`.`ShoppingOrderID` = " .
				"`{shoppingorders}`.`ID`" .
			" WHERE DATE(`{shoppingorders}`.`TimeStamp`) >= " .
				($startdate?
					"'".sql::escape($startdate)."'":
					"DATE_FORMAT(NOW(), '%Y-%m-01')") .
			($enddate?
				" AND DATE(`{shoppingorders}`.`TimeStamp`) <= " .
					"'".sql::escape($enddate)."'":
				null) .
			" GROUP BY `ShoppingItemID`");
		
		$rows = sql::run(
			" SELECT * FROM `{TMPMostViewedOrderItems}`" .
			" LEFT JOIN `{shoppingitems}` ON `{shoppingitems}`.`ID` =" .
				" `{TMPMostViewedOrderItems}`.`ShoppingItemID`" .
			" ORDER BY `Views` DESC" .
			" LIMIT ".$paging->limit);
		
		$paging->setTotalItems(sql::count());
		
		if (!$this->ajaxRequest)
			echo 
			"<div tabindex='0' class='fc" .
				form::fcState('fcshom') .
				"'>" .
				"<a class='fc-title' name='fcshom'>" .
					"<div class='align-right'>" .
						$paging->items." ".strtolower(_("Products")) .
					"</div>" .
					_("Most Viewed Products") .
				"</a>" .
				"<div class='fc-content'>";
		
		echo 
			"<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>" .
					"<th><span class='nowrap'>".
						__("Title / Created on")."</span></th>" .
					"<th><span class='nowrap'>".
						__("Views")."</span></th>" .
					"<th><span class='nowrap'>".
						__("Comments")."</span></th>" .
				"</tr>" .
				"</thead>" .
				"<tbody>";
		
		$i = 0;		
		while($row = sql::fetch($rows)) {
			echo 
				"<tr".($i%2?" class='pair'":NULL).">";
			
			$user = $GLOBALS['USER']->get($row['UserID']);
			
			echo
				"<td class='auto-width' " .
					($row['Deactivated']?
						"style='text-decoration: line-through;' ":
						null).
					">" .
					"<a href='?path=admin/modules/shopping/" .
						$row['ShoppingID']."/shoppingitems" .
						"&amp;id=".$row['ID']."" .
						"&amp;search=".htmlspecialchars($row['Title'], ENT_QUOTES)."' " .
					" class='bold' target='_blank'>" .
						$row['Title'] .
					"</a>" .
					"<div class='comment' style='padding-left: 10px;'>" .
						calendar::dateTime($row['TimeStamp'])." " .
						($user?
							$GLOBALS['USER']->constructUserName($user, __('by %s')):
							null) .
					"</div>" .
				"</td>" .
				"<td style='text-align: right;'>" .
					"<span class='nowrap'>" .
						$row['Views'] .
					"</span>" .
				"</td>" .
				"<td align='center'>" .
					"<a class='admin-link comments' " .
						"title='".htmlspecialchars(__("Comments"), ENT_QUOTES).
							" (".$row['Comments'].")' " .
						"href='?path=admin/modules/shopping/" .
							$row['ShoppingID']."/shoppingitems/" .
							$row['ID']."/shoppingitemcomments' " .
						"target='_blank'>" .
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
				"</td>";
			
			echo
				"</tr>";
				
			$i++;
		}
		
		sql::run("DROP TEMPORARY TABLE `{TMPMostViewedOrderItems}`");
		
		echo 
				"</tbody>" .
			"</table>";
		
		echo "<br />";
		
		$paging->display();
		
		if (!$this->ajaxRequest)
			echo
					"<div class='clear-both'></div>" .
				"</div>" .
			"</div>";
	}
	
	function displayAdmin() {
		$this->displayAdminDashboard();
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		$orderform = new shoppingOrderForm();
		$newid = $orderform->addData($values);
		unset($orderform);
		
		if (!$newid) {
			tooltip::display(
				_("We are sorry for the inconvenience but the order couldn't be " .
					"added to the db. Please contact us with this error as soon as possible."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		sql::run(
			" UPDATE `{shoppingorders}` SET" .
			" `OrderID` = '".sql::escape($values['OrderID'])."'," .
			" `UserID` = '".(int)$values['UserID']."'," .
			" `OrderStatus` = 1," .
			" `PaymentStatus` = '".(int)$values['PaymentStatus']."'," .
			" `OrderMethod` = '".sql::escape($values['OrderMethod'])."'," .
			" `OrderMethodDetails` = '".sql::escape($values['OrderMethodDetails'])."'," .
			" `Discount` = '".sql::escape($values['Discount'])."'," .
			" `Fee` = '".sql::escape($values['Fee'])."'," .
			(JCORE_VERSION >= '0.7'?
				" `Tax` = '".sql::escape($values['Tax'])."',":
				null) .
			" `Subtotal` = '".sql::escape($values['Subtotal'])."'" .
			" WHERE `ID` = '".$newid."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(_("Order couldn't be created / updated! Error: %s"), 
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
		
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}`" .
			" WHERE `ID` = '".$id."'" .
			" LIMIT 1"));
		
		$orderform = new shoppingOrderForm();
		$orderform->edit($id, $values);
		unset($orderform);
		
		if (in_array($values['OrderStatus'], array(
				SHOPPING_ORDER_STATUS_NEW,
				SHOPPING_ORDER_STATUS_PROCESSING,
				SHOPPING_ORDER_STATUS_ACCEPTED,
				SHOPPING_ORDER_STATUS_DELIVERED)) &&
			in_array($order['OrderStatus'], array(
				SHOPPING_ORDER_STATUS_CANCELLED,
				SHOPPING_ORDER_STATUS_REJECTED)))
		{
			$orderitems = sql::run(
				" SELECT * FROM `{shoppingorderitems}`" .
				" WHERE `ShoppingOrderID` = '".$id."'");
			
			while($orderitem = sql::fetch($orderitems)) {
				sql::run(
					" UPDATE `{shoppingitems}` SET " .
					" `AvailableQuantity` = `AvailableQuantity` - ".(int)$orderitem['Quantity'].", " .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".$orderitem['ShoppingItemID']."'");
			}
		}
		
		if (in_array($values['OrderStatus'], array(
				SHOPPING_ORDER_STATUS_CANCELLED,
				SHOPPING_ORDER_STATUS_REJECTED)) &&
			in_array($order['OrderStatus'], array(
				SHOPPING_ORDER_STATUS_NEW,
				SHOPPING_ORDER_STATUS_PROCESSING,
				SHOPPING_ORDER_STATUS_ACCEPTED,
				SHOPPING_ORDER_STATUS_DELIVERED)))
		{
			$orderitems = sql::run(
				" SELECT * FROM `{shoppingorderitems}`" .
				" WHERE `ShoppingOrderID` = '".$id."'");
			
			while($orderitem = sql::fetch($orderitems)) {
				sql::run(
					" UPDATE `{shoppingitems}` SET " .
					" `AvailableQuantity` = `AvailableQuantity` + ".(int)$orderitem['Quantity'].", " .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".$orderitem['ShoppingItemID']."'");
			}
		}
		
		sql::run(
			" UPDATE `{shoppingorders}` SET" .
			" `OrderStatus` = ".(int)$values['OrderStatus']."," .
			" `PaymentStatus` = '".(int)$values['PaymentStatus']."'," .
			(isset($values['OrderMethod']) && $values['OrderMethod']?
				" `OrderMethod` = '".sql::escape($values['OrderMethod'])."',":
				null) .
			" `OrderMethodDetails` = '".sql::escape($values['OrderMethodDetails'])."'," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".$id."'");
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(_("Order couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
			
		$comments = new shoppingOrderComments();
		
		$rows = sql::run(
			" SELECT * FROM `{shoppingordercomments}`" .
			" WHERE `ShoppingOrderID` = '".$id."'");
		
		while($row = sql::fetch($rows))
			$comments->delete($row['ID']);
			
		unset($comments);
		
		$orderitems = new shoppingOrderItems();
		
		$rows = sql::run(
			" SELECT * FROM `{shoppingorderitems}`" .
			" WHERE `ShoppingOrderID` = '".$id."'");
		
		while($row = sql::fetch($rows))
			$orderitems->delete($row['ID']);
		
		unset($orderitems);
		
		sql::run(
			" DELETE FROM `{shoppingorderdownloads}` " .
			" WHERE `ShoppingOrderID` = '".$id."'");
			
		sql::run(
			" DELETE FROM `{shoppingorders}` " .
			" WHERE `ID` = '".$id."'");
			
		return true;
	}
	
	// ************************************************   Client Part
	static function getURL() {
		$url = modules::getOwnerURL('shoppingOrders');
		
		if (!$url)
			return url::site().'?';
		
		return $url;	
	}
	
	static function genOrderID() {
		return "O".date('YmdHis').security::randomChars();
	}
	
	static function status2Text($status) {
		if (!$status)
			return;
		
		switch($status) {
			case SHOPPING_ORDER_STATUS_ACCEPTED:
				return _('Accepted');
			case SHOPPING_ORDER_STATUS_CANCELLED:
				return _('Cancelled');
			case SHOPPING_ORDER_STATUS_NEW:
				return _('New');
			case SHOPPING_ORDER_STATUS_PROCESSING:
				return _('Processing');
			case SHOPPING_ORDER_STATUS_REJECTED:
				return _('Rejected');
			case SHOPPING_ORDER_STATUS_DELIVERED:
				return _('Delivered');
			default:
				return _('Undefined!');
		}
	}
	
	static function paymentStatus2Text($status) {
		if (!$status)
			return;
		
		switch($status) {
			case SHOPPING_ORDER_PAYMENT_STATUS_CANCELLED:
				return _('Cancelled');
			case SHOPPING_ORDER_PAYMENT_STATUS_PAID:
				return _('Paid');
			case SHOPPING_ORDER_PAYMENT_STATUS_PENDING:
				return _('Pending');
			case SHOPPING_ORDER_PAYMENT_STATUS_FAILED:
				return _('Failed');
			case SHOPPING_ORDER_PAYMENT_STATUS_EXPIRED:
				return _('Expired');
			case SHOPPING_ORDER_PAYMENT_STATUS_PROCESSING:
				return _('Processing');
			default:
				return _('Undefined!');
		}
	}
	
	static function sendNotificationEmails($orderid) {
		if (!shoppingOrders::sendNotificationEmail($orderid))
			return false;
	
		if (!defined('SHOPPING_CART_SEND_NOTIFICATION_EMAIL_ON_NEW_ORDER') ||
			SHOPPING_CART_SEND_NOTIFICATION_EMAIL_ON_NEW_ORDER) 
		{
			if (defined('SHOPPING_CART_SEND_NOTIFICATION_EMAIL_TO') &&
				SHOPPING_CART_SEND_NOTIFICATION_EMAIL_TO)
				shoppingOrders::sendNotificationEmail($orderid, 
					'ShoppingOrderToWebmaster', 
					SHOPPING_CART_SEND_NOTIFICATION_EMAIL_TO);
			else
				shoppingOrders::sendNotificationEmail($orderid,
					'ShoppingOrderToWebmaster',
					WEBMASTER_EMAIL);
		}
		
		return true;
	}
	
	static function sendNotificationEmail($orderid, $emailid = 'ShoppingOrder', $toemail = null) {
		if (!(int)$orderid)
			return false;
			
		$order = sql::fetch(sql::run(
			" SELECT * FROM `{shoppingorders}` " .
			" WHERE `ID` = '".(int)$orderid."'" .
			" LIMIT 1"));
			
		if (!$order) {
			tooltip::display(
				_("Email couldn't be sent! The defined order cannot be found."),
				TOOLTIP_ERROR);
			return false;
		}
		
		$user = sql::fetch(sql::run(
			" SELECT * FROM `{users}`" .
			" WHERE `ID` = '".$order['UserID']."'"));
			
		$orderitems = sql::run(
			" SELECT * FROM `{shoppingorderitems}`" .
			" WHERE `ShoppingOrderID` = '".$order['ID']."'");
		
		$ordermethod = shoppingOrderMethods::get($order['OrderMethod']);
		
		$email = new email();
		
		$email->load($emailid);
		
		if ($toemail)
			$email->to = $toemail;
		else
			$email->to = $user['Email'];
		
		$email->variables = array(
			'UserName' => $user['UserName'],
			'OrderID' => $order['ID'],
			'OrderNumber' => $order['OrderID'],
			'OrderForm' => '',
			'OrderItems' => '',
			'LinkToDigitalGoods' => '',
			'LinkToOrders' => shoppingOrders::getURL(),
			'PaymentStatus' => '',
			'PaymentStatusNote' => '');
		
		if ($order['PaymentStatus'] == SHOPPING_ORDER_PAYMENT_STATUS_PAID) {
			$email->variables['PaymentStatus'] = 
				strtoupper(_("Paid"));
			$email->variables['PaymentStatusNote'] = 
				_("It's now safe to ship the goods if necessary.");
			
		} elseif ($order['PaymentStatus'] == SHOPPING_ORDER_PAYMENT_STATUS_CANCELLED) {
			$email->variables['PaymentStatus'] = 
				strtoupper(_("Cancelled"));
			$email->variables['PaymentStatusNote'] = 
				_("IMPORTANT: payment has been cancelled so " .
					"shipment should be cancelled too!");
			
		} else {
			$email->variables['PaymentStatus'] = 
				strtoupper(_("Pending"));
			$email->variables['PaymentStatusNote'] = 
				_("IMPORTANT: do NOT ship any goods until payment has been " .
					"confirmed / processed!");
		}
		
		$orderform = new shoppingOrderForm();
		$orderform->load(false);
				
		foreach($orderform->elements as $element) {
			if (form::isInput($element)) {
				if (!isset($order[$element['Name']]))
					$value = null;
				elseif ($element['ValueType'] == FORM_VALUE_TYPE_ARRAY)
					$value = str_replace('|', '; ', $order[$element['Name']]);
				elseif ($element['ValueType'] == FORM_VALUE_TYPE_BOOL)
					$value = ($order[$element['Name']]?__("Yes"):__("No"));
				else
					$value = $order[$element['Name']];
				
				if ($element['Type'] == FORM_INPUT_TYPE_TEXTAREA)
					$email->variables['OrderForm'] .= 
						"\n".$element['Title'].":\n".$value."\n\n";
				else
					$email->variables['OrderForm'] .= 
						$element['Title'].": ".$value."\n";
			
			} elseif ($element['Type'] == FORM_STATIC_TEXT) {
				$email->variables['OrderForm'] .= 
					strip_tags(str_replace("<br />", "\n", $element['Title']))."\n".
					(strip_tags($element['Title'])?
						"\n":
						null);
			
			} elseif ($element['Type'] == FORM_OPEN_FRAME_CONTAINER) {
				$email->variables['OrderForm'] .= 
					"\n".$element['Title']."\n" .
					"-----------------------------------------------------------------\n";
			}
		}
		
		unset($orderform);
		
		$email->variables['OrderForm'] .= 
			"\n"._("Order Method")."\n" .
			"-----------------------------------------------------------------\n" .
			$ordermethod['Title']."\n" .
			$ordermethod['Description'] .
			($emailid == 'ShoppingOrderToWebmaster'?
				"\n\n".$order['OrderMethodDetails']:
				null);
		
		$email->variables['OrderItems'] .= 
			_("Items ordered")."\n" .
			"-----------------------------------------------------------------\n\n";
		
		$digitalgoods = false;
		
		while($orderitem = sql::fetch($orderitems)) {
			$item = sql::fetch(sql::run(
				" SELECT * FROM `{shoppingitems}` " .
				" WHERE `ID` = '".$orderitem['ShoppingItemID']."'"));
			
			if (isset($item['DigitalGoods']) && $item['DigitalGoods'])
				$digitalgoods = true;
			
			$email->variables['OrderItems'] .= 
				$item['RefNumber']." - ".
				$item['Title']."\n" .
				(isset($orderitem['ShoppingItemOptions']) &&
				 $orderitem['ShoppingItemOptions']?
					$orderitem['ShoppingItemOptions']."\n":
					null);
				
			if ($orderitem['Quantity'] > 1)
				$email->variables['OrderItems'] .=
					strip_tags(shoppingOrders::constructPrice(
						$orderitem['Quantity']*$orderitem['Price'])) . 
					" (".$orderitem['Quantity']." x ".
					strip_tags(shoppingOrders::constructPrice(
						$orderitem['Price'])).")\n\n";
			else
				$email->variables['OrderItems'] .= 
					strip_tags(shoppingOrders::constructPrice(
						$orderitem['Price']))."\n\n";
		}
		
		$email->variables['OrderItems'] .= 
			"-----------------------------------------------------------------\n" .
			_("Subtotal").": ".
				strip_tags(shoppingOrders::constructPrice(
					$order['Subtotal']))."\n";
				
		if (isset($order['Tax']) && $order['Tax'] > 0)
			$email->variables['OrderItems'] .= 
				_("Tax").": ".
					strip_tags(shoppingOrders::constructPrice(
						$order['Tax']))."\n";
		
		if ($order['Discount'] > 0)
			$email->variables['OrderItems'] .= 
				_("Discount").": ".
					strip_tags(shoppingOrders::constructPrice(
						$order['Discount']))."\n";
		
		if ($order['Fee'] > 0)
			$email->variables['OrderItems'] .= 
				_("Shipping & Handling").": ".
					strip_tags(shoppingOrders::constructPrice(
						$order['Fee']))."\n";
		
		$email->variables['OrderItems'] .= 
			"==============================\n" .
			_("Grand Total").": ".
				strip_tags(shoppingOrders::constructPrice($order['Subtotal']+
					(isset($order['Tax'])?$order['Tax']:0)-$order['Discount']+$order['Fee']));
		
		if ($digitalgoods && 
			$order['PaymentStatus'] == SHOPPING_ORDER_PAYMENT_STATUS_PAID)
			$email->variables['LinkToDigitalGoods'] .= 
				_("You can access / download your digital goods at:")."\n" .
				$email->variables['LinkToOrders'] .
				"\n\n";
		
		$emailsent = $email->send();
		unset($email);
		
		return $emailsent;
	}
	
	function ajaxRequest() {
		$users = null;
		$items = null;
		$discount = null;
		$fee = null;
		$subtotal = null;
		$totals = null;
		$orders = null;
		$orderedproducts = null;
		$viewedproducts = null;
		
		if (isset($_GET['users']))
			$users = $_GET['users'];
		
		if (isset($_GET['shoppingitems']))
			$items = $_GET['shoppingitems'];
		
		if (isset($_GET['discount']))
			$discount = $_GET['discount'];
		
		if (isset($_GET['fee']))
			$fee = $_GET['fee'];
		
		if (isset($_GET['subtotal']))
			$subtotal = $_GET['subtotal'];
		
		if (isset($_GET['totals']))
			$totals = $_GET['totals'];
		
		if (isset($_GET['orders']))
			$orders = $_GET['orders'];
		
		if (isset($_GET['orderedproducts']))
			$orderedproducts = $_GET['orderedproducts'];
		
		if (isset($_GET['viewedproducts']))
			$viewedproducts = $_GET['viewedproducts'];
		
		if ($users || $items || $orders || $orderedproducts || $viewedproducts) {
			if (!$GLOBALS['USER']->loginok || 
				!$GLOBALS['USER']->data['Admin']) 
			{
				tooltip::display(
					__("Request can only be accessed by administrators!"),
					TOOLTIP_ERROR);
				return true;
			}
			
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
			
			if ($users) {
				$GLOBALS['USER']->displayQuickList('#neworderform #entryUserName');
				return true;
			}
			
			if ($items) {
				$this->displayAdminShoppingItems();
				return true;
			}
			
			if ($orders) {
				$this->displayAdminDashboardOrders();
				return true;
			}
			
			if ($orderedproducts) {
				$this->displayAdminDashboardBestsellers();
				return true;
			}
			
			if ($viewedproducts) {
				$this->displayAdminDashboardMostViewedProducts();
				return true;
			}
		}
		
		if ($discount) {
			echo shoppingCart::getDiscount($subtotal);
			return true;
		}
		
		if ($fee) {
			echo shoppingCart::getFee($subtotal);
			return true;
		}
		
		if ($totals) {
			$totals = $this->getAdminNewOrderTotals();
			echo json::encode($totals);
			return true;
		}
		
		$this->ajaxPaging = true;
		$this->display();
		return true;
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
		echo shoppingOrders::constructPrice($price);
	}
	
	function displayDigitalGoods($row) {
		if (!in_array($row['PaymentStatus'], array(
				SHOPPING_ORDER_PAYMENT_STATUS_PAID,
				SHOPPING_ORDER_PAYMENT_STATUS_PENDING)))
			return;
		
		$orderitems = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(ShoppingItemID SEPARATOR ',') AS `ItemIDs` " .
			" FROM `{shoppingorderitems}`" .
			" WHERE `ShoppingOrderID` = '".$row['ID']."'" .
			" LIMIT 1"));
		
		if (!$orderitems)
			return;
		
		$items = sql::run(
			" SELECT * FROM `{shoppingitems}`" .
			" WHERE `ID` IN (".$orderitems['ItemIDs'].")" .
			" AND !`Deactivated`" .
			" AND `DigitalGoods`");
		
		if (!sql::rows($items))
			return;
		
		if ($row['PaymentStatus'] != SHOPPING_ORDER_PAYMENT_STATUS_PAID) {
			echo "<br />";
			
			tooltip::display(
				_("Your payment is still pending. To access your Digital Goods " .
					"please finalize your payments below or wait until we receive " .
					"the confirmation for your payment."),
				TOOLTIP_NOTIFICATION);
			return;
		}
		
		$digitalgoods = new shoppingItemDigitalGoods();
		
		echo
			"<div class='shopping-order-digital-goods'>";			
		
		while ($item = sql::fetch($items)) {
			$downloadable = sql::fetch(sql::run(
				" SELECT `DigitalGoodsExpiration` FROM `{shoppings}`" .
				" WHERE `ID` = '".$item['ShoppingID']."'" .
				" AND (!`DigitalGoodsExpiration`" .
					" OR DATEDIFF(NOW(), '".$row['TimeStamp']."')" .
						" <= `DigitalGoodsExpiration`)"));
			
			if (!$downloadable)
				continue;
			
			echo
				"<h3 class='shopping-order-digital-good-item-title'>" .
					$item['Title'] .
				"</h3>";
			
			$digitalgoods->selectedOwnerID = $item['ID'];
			$digitalgoods->display();
		}
		
		echo
			"</div>";
		
		unset($digitalgoods);
	}
	
	function displayTitle(&$row) {
		echo
			_("Order #").$row['OrderID'];
		
		echo
			" (";
		$this->displayOrderMethodStatus($row);
		
		echo
			")";
	}
	
	function displayDetails(&$row) {
		$user = $GLOBALS['USER']->get($row['UserID']);
		
		echo
			calendar::datetime($row['TimeStamp'])." ";
				
		$GLOBALS['USER']->displayUserName($user, __('by %s'));
		
		echo
			", " ._($this->status2Text($row['OrderStatus']));
	}
	
	function displayDescription(&$row) {
		echo
			"<p>" .
				_("Your order details are listed below, if you " .
					"would like to change any of the submitted information " .
					"or have further question / comments please let us know " .
					"in the comments.") .
			"</p>";
	}
	
	function displayCartListHeader(&$row) {
		$cart = new shoppingCart();
		$cart->displayListHeader($row);
		unset($cart);
	}
	
	function displayCartListItem(&$row) {
		$cart = new shoppingCart();
		$cart->displayListItem($row);
		unset($cart);
	}
	
	function displayCartList(&$row) {
		echo
				"<table cellpadding='0' cellspacing='0' class='list'>" .
					"<thead>" .
					"<tr>";
		
		$this->displayCartListHeader($row);
		
		echo
					"</tr>" .
					"</thead>" .
					"<tbody>";
	
		$orderitems = sql::run(
			" SELECT * FROM `{shoppingorderitems}`" .
			" WHERE `ShoppingOrderID` = '".$row['ID']."'");
		
		$ii = 0;
		while($orderitem = sql::fetch($orderitems)) {
			$orderitem['TimeStamp'] = $row['TimeStamp'];
			
			echo
				"<tr".($ii%2?" class='pair'":NULL).">";
			
			$this->displayCartListItem($orderitem);
			
			echo
				"</tr>";
				
			$ii++;
		}
		
		echo
					"</tbody>" .
				"</table>";
		
		$totals = array(
			'SubTotal' => $row['Subtotal'],
			'Discount' => $row['Discount'],
			'Fee' => $row['Fee'],
			'Tax' => (isset($row['Tax'])?$row['Tax']:0),
			'GrandTotal' => $row['Subtotal']+
				(isset($row['Tax'])?$row['Tax']:0)-
				$row['Discount']+$row['Fee']);
		
		$cart = new shoppingCart();
		$cart->displayListTotals($totals);
		unset($cart);
	}
	
	function displayCart(&$row) {
		echo
			"<div class='shopping-cart'>";
		
		$this->displayCartList($row);
		
		echo
				"<div class='clear-both'></div>" .
			"</div>"; //shopping-cart
	}
	
	function displayOrderInfo(&$row) {
		$orderform = new shoppingOrderForm();
		$orderform->load(false);
		$orderform->displayData($row);
		unset($orderform);
	}
	
	function displayOrderMethodTitle(&$ordermethod) {
		echo $ordermethod['Title'];
	}
	
	function displayOrderMethodDescription(&$ordermethod) {
		echo $ordermethod['Description'];
	}
	
	function displayOrderMethodStatus(&$row) {
		echo
			"<span class='tip ";
		
		switch ($row['PaymentStatus']) {
			case SHOPPING_ORDER_PAYMENT_STATUS_PAID:
				echo 
					"green'" .
					" title='" .
							htmlspecialchars(_("Payment confirmed and received."), ENT_QUOTES) .
						"'";
				break;
				
			case SHOPPING_ORDER_PAYMENT_STATUS_PENDING:
				echo 
					"red'" .
					" title='" .
							htmlspecialchars(_("Payment still pending!"), ENT_QUOTES) . 
						"'";
				break;
				
			case SHOPPING_ORDER_PAYMENT_STATUS_FAILED:
				echo 
					"'" .
					" title='" .
							htmlspecialchars(_("Payment failed!"), ENT_QUOTES) . 
						"'";
				break;
				
			case SHOPPING_ORDER_PAYMENT_STATUS_EXPIRED:
				echo 
					"'" .
					" title='" .
							htmlspecialchars(_("Payment expired!"), ENT_QUOTES) . 
						"'";
				break;
				
			case SHOPPING_ORDER_PAYMENT_STATUS_PROCESSING:
				echo 
					"'" .
					" title='" .
							htmlspecialchars(_("Awaiting payment deposit!"), ENT_QUOTES) . 
						"'";
				break;
				
			default:
				echo 
					"'";
				break;
		}
					
		echo
			">" .
			$this->paymentStatus2Text($row['PaymentStatus']) .
			"</span>";
	}
	
	function displayFinalizePayments(&$row) {
		$ordermethodclass = 'shoppingOrderMethod'.
			$row['OrderMethod'];
		
		$ordermethod = new $ordermethodclass;
		$ordermethod->postProcessText = 
			_("It seems the payment has not been processed yet or you " .
				"haven't finalized the payment. If you sure the " .
				"payment has been processed please wait a few more minutes " .
				"until we receive the confirmation, otherwise please click " .
				"on the button below.");
		$ordermethod->postProcess($row['ID']);
		unset($ordermethod);
	}
	
	function displayOrderMethod(&$row) {
		$ordermethod = shoppingOrderMethods::get($row['OrderMethod']);
		
		echo
			"<div tabindex='0' class='fc" .
				($row['PaymentStatus'] != SHOPPING_ORDER_PAYMENT_STATUS_PAID?
					" expanded":
					null) .
				"'>" .
				"<a class='fc-title'>" .
					_("Order Method") .
				"</a>" .
				"<div class='fc-content'>" .
					"<div class='form-entry preview'>" .
						"<div class='form-entry-title'>" .
							_("Method").":" .
						"</div>" .
						"<div class='form-entry-content'>" .
						"<b>";
		
		$this->displayOrderMethodTitle($ordermethod);
		
		echo
						"</b><br />";
		
		if ($row['PaymentStatus'] == SHOPPING_ORDER_PAYMENT_STATUS_PENDING)
			$this->displayOrderMethodDescription($ordermethod);
		
		echo
						"</div>" .
					"</div>" .
					"<div class='form-entry preview'>" .
						"<div class='form-entry-title'>" .
							_("Status").":" .
						"</div>" .
						"<div class='form-entry-content bold'>";
		
		$this->displayOrderMethodStatus($row);
		
		echo
						"</div>" .
					"</div>";
							
		if ($row['PaymentStatus'] == SHOPPING_ORDER_PAYMENT_STATUS_PENDING)
			$this->displayFinalizePayments($row);
							
		if ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin']) {
			echo
					"<div class='form-entry preview'>" .
						"<div class='form-entry-title'>" .
							_("Details").":" .
						"</div>" .
						"<div class='form-entry-content bold'>" .
							nl2br(url::parseLinks($row['OrderMethodDetails'])) .
						"</div>" .
					"</div>" .
				"</div>";
		}
		
		echo
				"</div>" .
			"</div>";
	}
	
	function displayDownloadsList(&$downloads) {
		$previtemid = 0;
		while($download = sql::fetch($downloads)) {
			if ($previtemid != $download['ShoppingItemID']) {
				if ($previtemid)
					echo "</ul>";
				
				$downloaditem = sql::fetch(sql::run(
					" SELECT `Title` FROM `{shoppingitems}`" .
					" WHERE `ID` = '".$download['ShoppingItemID']."'"));
				
				echo
					"<b>" .
						$downloaditem['Title'] .
					"</b>" .
					"<ul>";
				
				$previtemid = $download['ShoppingItemID'];
			}
			
			$downloaddigitalgood = sql::fetch(sql::run(
				" SELECT `Title`, `FileSize` FROM `{shoppingitemdigitalgoods}`" .
				" WHERE `ID` = '".$download['ShoppingItemDigitalGoodID']."'"));
			
			echo
				"<li>\"" .
					sprintf(_("%s, started on <i>%s</i>, finished on %s, from %s"),
						$downloaddigitalgood['Title'],
						calendar::datetime($download['StartTimeStamp']),
						($download['FinishTimeStamp']?
							calendar::datetime($download['FinishTimeStamp']):
							_('unknown')),
						long2ip($download['IP']));
					
			if ($download['FinishTimeStamp']) {
				$timediff = strtotime($download['FinishTimeStamp']) - 
					strtotime($download['StartTimeStamp']);
				
				if ($timediff)
					echo
						" (" .
						files::humanSize($downloaddigitalgood['FileSize']/$timediff) .
						"/s)";
			}
				
			echo
				"</li>";
		}
		
		echo
			"</ul>";
	}
	
	function displayDownloads(&$row) {
		$downloads = sql::run(
			" SELECT * FROM `{shoppingorderdownloads}`" .
			" WHERE `ShoppingOrderID` = '".$row['ID']."'" .
			" ORDER BY `ShoppingItemID`, `ID`");
		
		if (!sql::rows($downloads))
			return;
		
		echo
			"<div tabindex='0' class='fc'>" .
				"<a class='fc-title'>" .
					_("Downloads") .
					" (".sql::rows($downloads).")" .
				"</a>" .
				"<div class='fc-content'>";
		
		$this->displayDownloadsList($downloads);
			
		echo
				"</div>" .
			"</div>";
	}
	
	function displayFunctions(&$row) {
		echo
			"<a href='".url::uri('shoppingorderid')."' class='back comment'>".
				"<span>" .
				__("Back").
				"</span>" .
			"</a>";
	}
	
	function displayComments(&$row) {
		$comments = new shoppingOrderComments();
		$comments->selectedOwnerID = $row['ID'];
		$comments->display();
		unset($comments);
	}
	
	function displaySelected(&$row) {
		echo
			"<div class='shopping-order selected'>" .
				"<h2 class='shopping-order-number'>";
		
		$this->displayTitle($row);
		
		echo
				"</h2>" .
				"<div class='shopping-order-details comment'>";
		
		$this->displayDetails($row);
		
		echo
				"</div>";
		
		if (JCORE_VERSION >= '0.5')		
			$this->displayDigitalGoods($row);
				
		echo
				"<div class='shopping-order-content'>";
		
		$this->displayDescription($row);
		$this->displayCart($row);
		$this->displayOrderInfo($row);
		$this->displayOrderMethod($row);
		
		if (JCORE_VERSION >= '0.5')		
			$this->displayDownloads($row);
		
		echo
				"</div>" . //shopping-order-content
				"<div class='shopping-order-links'>";
		
		$this->displayFunctions($row);
		
		echo
				"</div>" .
				"<div class='spacer bottom'></div>" .
				"<div class='separator bottom'></div>" .
			"</div>"; //shopping-order
		
		if ($this->selectedID == $row['ID'])
			$this->displayComments($row);
	}
	
	function displayLogin() {
		$GLOBALS['USER']->displayLogin();
	}
	
	function displayListHeader() {
		echo
			"<th><span class='nowrap'>".
				_("Order ID / Submitted on")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				_("Grand Total")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				_("Status")."</span></th>";
	}
	
	function displayListHeaderOptions() {
		echo
			"<th><span class='nowrap'>".
				__("Comments")."</span></th>";
	}
	
	function displayListHeaderFunctions() {
	}
	
	function displayListItem(&$row, $class = null) {
		$user = $GLOBALS['USER']->get($row['UserID']);
		
		echo 
			"<td class='auto-width'>" .
				"<a href='".$row['_Link']."' " .
				" class='bold'>" .
				$row['OrderID'] .
				"</a>" .
				"<div class='comment' style='padding-left: 10px;'>";
		
		$this->displayDetails($row);
		
		echo
				"</div>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				"<span class='nowrap'>";
		
		shoppingOrders::displayPrice($row['Subtotal']+
			(isset($row['Tax'])?$row['Tax']:0)-$row['Discount']+$row['Fee']);
		
		echo
				"</span>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				"<div class='shopping-order-status" .
				($row['OrderStatus'] == SHOPPING_ORDER_STATUS_NEW?
					" bold'":
					null) .
					"'>" .
					_($this->status2Text($row['OrderStatus'])) .
				"</div>" .
				"<div class='shopping-order-payment-status'>";
		
		$this->displayOrderMethodStatus($row);
		
		echo
				"</div>" .
			"</td>";
	}
	
	function displayListItemOptions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='shopping-order-comments-link' " .
					"title='".htmlspecialchars(__("Comments"), ENT_QUOTES).
						" (".$row['Comments'].")' " .
					"href='".$row['_Link']."#comments'>" .
						(JCORE_VERSION >= '0.5' && $row['Comments']?
							"<span class='counter'>" .
								"<span>" .
									"<span>" .
									$row['Comments']."" .
									"</span>" .
								"</span>" .
							"</span>":
							null) .
				"</a>" .
			"</td>";
	}
	
	function displayListItemFunctions(&$row) {
	}
	
	function displayList(&$rows) {
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
		
		$i = 0;		
		while($row = sql::fetch($rows)) {
			$row['_Link'] = $this->shoppingOrdersURL .
				(isset($_GET['shoppingorderslimit'])?
					"&amp;shoppingorderslimit=".$_GET['shoppingorderslimit']:
					null) . 
				"&amp;shoppingorderid=".$row['ID'];
			
			echo 
				"<tr class='shopping-order-".
					strtolower($this->paymentStatus2Text($row['PaymentStatus'])) .
					" ".($i%2?"pair":null)."'>";
			
			$this->displayListItem($row);
			$this->displayListItemOptions($row);
			$this->displayListItemFunctions($row);
			
			echo
				"</tr>";
			
			$i++;
		}
		
		echo 
				"</tbody>" .
			"</table>" .
			"<br />";
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		if (preg_match('/(^|\/)([0-9]+?)\/ajax($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/\/ajax/', '', $this->arguments);
			$this->ignorePaging = true;
			$this->ajaxPaging = true;
		}
		
		if (preg_match('/(^|\/)([0-9]+?)($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)[0-9]+?($|\/)/', '\2', $this->arguments);
			$this->limit = (int)$matches[2];
		}
		
		return false;
	}
	
	function display() {
		if ($this->displayArguments())
			return true;
		
		if (!$GLOBALS['USER']->loginok) {
			$this->displayLogin();
			return;
		}
		
		if (!$this->shoppingURL)
			$this->shoppingURL = shopping::getURL();
		
		if (!$this->shoppingOrdersURL)
			$this->shoppingOrdersURL = shoppingOrders::getURL();
		
		if ($this->selectedID) {
			$row = sql::fetch(sql::run(
				$this->SQL() .
				" LIMIT 1"));
			
			echo
				"<div class='shopping-orders'>";
			
			$this->displaySelected($row);
			
			echo
				"</div>";
			
			return;
		}	
		
		if (!$this->ajaxRequest)
			echo
				"<div class='shopping-orders'>";
		
		$paging = new paging($this->limit);
		$paging->track(strtolower(get_class($this)).'limit');
		
		if ($this->ajaxPaging) {
			$paging->ajax = true;
			$paging->otherArgs = "&amp;request=modules/shoppingorders";
		}
		
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
				
		if (!sql::rows($rows))
			tooltip::display(
					_("No orders found."),
					TOOLTIP_NOTIFICATION);
		else
			$this->displayList($rows);
		
		if (!$this->selectedID && $this->showPaging)
			$paging->display();
		
		if (!$this->ajaxRequest)
			echo
				"</div>"; //shopping-orders
	}
}

?>