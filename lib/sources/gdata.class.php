<?php

/***************************************************************************
 *            gdata.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

class _GData {
	var $requestURL;
	var $token = null;
	var $scopes = null;
	
	function __construct() {
		$this->requestURL = SITE_URL."?request=gdata";
	}
	
	function get($url) {
		$data = files::get($url,
			($this->token? 
				array('Authorization: AuthSub token="'.$this->token.'"'):
				null));
		
		if (!$data) {
			tooltip::display(
				sprintf(__("Couldn't connect to %s!"),
					url::rootDomain($url)),
				TOOLTIP_NOTIFICATION);
			
			return null;
		}
		
		return $data;
	}
	
	function getToken() {
		if (!$this->scopes)
			return false;
		
		if (is_array($this->scopes))
			$scope = implode(' ', $this->scopes);
		else
			$scope = $this->scopes;
		
		return "http://www.google.com/accounts/AuthSubRequest?scope=" .
			urlencode($scope)."&session=1&secure=0&next=" .
			urlencode($this->requestURL);
	}
	
	function exchangeToken($token) {
		if (!$token)
			return false;
		
		$result = files::get(
			"https://www.google.com/accounts/AuthSubSessionToken",
				array('Authorization: AuthSub token="'.$token.'"'));
		
		preg_match('/Token=(.*?)$/', $result, $matches);
		
		if (!isset($matches[1]) || !$matches[1]) {
			tooltip::display(
				sprintf(__("GData auth token couldn't be exchanged! Result: %s"),
					strip_tags($result)),
				TOOLTIP_ERROR);
			return false;
		}
		
		return trim($matches[1]);
	}
	
	function request() {
		$token = null;
		
		if (isset($_GET['token']))
			$token = $_GET['token'];
		
		if (!$token)
			return false;
		
		$newtoken = $this->exchangeToken($token);
		
		if (!$newtoken)
			return false;
		
       	tooltip::display(
       		sprintf(__("GData auth token has been successfully retrieved. <br />" .
       			"<b>Your new GData auth token is: %s</b>"),
       			$newtoken),
       		TOOLTIP_SUCCESS);
       	return true;
	}
}

?>
