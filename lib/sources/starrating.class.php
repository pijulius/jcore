<?php

/***************************************************************************
 *            starrating.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/

class _starRating {
	var $sqlTable;
	var $sqlRow;
	var $sqlOwnerTable;
	var $selectedOwner;
	var $selectedOwnerID;
	var $guestRating = false;
	var $uriRequest;
	
	function __construct() {
		$this->uriRequest = strtolower(get_class($this));
		
		if (isset($_GET[strtolower($this->sqlRow)]))
			$this->selectedOwnerID = (int)$_GET[strtolower($this->sqlRow)];
	}
	
	// ************************************************   Client Part
	function verify() {
		$rate = null;
		
		if (isset($_GET['rate']))
			$rate = (int)$_GET['rate'];
		
		if (!$this->selectedOwnerID || !$rate)
			return;
		
		if (!$this->guestRating && !$GLOBALS['USER']->loginok) {
			tooltip::display(
					__("Only registered users can rate."),
					TOOLTIP_ERROR);
			
			return false;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT `TimeStamp` FROM `{".$this->sqlTable."}`" .
			" WHERE `".$this->sqlRow."` = '".$this->selectedOwnerID."'" .
			($this->guestRating?
				" AND `IP` = '".ip2long($_SERVER['REMOTE_ADDR'])."'" .
				" AND `TimeStamp` > DATE_SUB(NOW(), INTERVAL 1 DAY)":
				" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'")));
			
		if ($row) {
			tooltip::display(
				sprintf(__("You already rated this %s."), $this->selectedOwner),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		sql::run(
			" INSERT INTO `{".$this->sqlTable."}` SET " .
			" `".$this->sqlRow."` = '".$this->selectedOwnerID."'," .
			" `Rating` = '".$rate."'," .
			($GLOBALS['USER']->loginok?
				" `UserID` = '".
					(int)$GLOBALS['USER']->data['ID']."',":
				null) .
			" `IP` = '".
				ip2long($_SERVER['REMOTE_ADDR'])."'," .
			" `TimeStamp` = NOW()");
		
		if (!sql::affected()) {
			tooltip::display(
				sprintf(__("Rating couldn't be stored! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		$totals = sql::fetch(sql::run(
			" SELECT " .
			" COUNT(`".$this->sqlRow."`) AS `Votes`, " .
			" SUM(`Rating`) AS `Ratings` " .
			" FROM `{".$this->sqlTable."}`" .
			" WHERE `".$this->sqlRow."` = '".$this->selectedOwnerID."'" .
			" GROUP BY `".$this->sqlRow."`"));
			
		sql::run(
			" UPDATE `{" .$this->sqlOwnerTable . "}` SET " .
			" `Rating` = '".
				(int)round($totals['Ratings']/$totals['Votes'])."', " .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".$this->selectedOwnerID."'");
		
		tooltip::display(
			__("Thank you for your vote."),
			TOOLTIP_SUCCESS);
		
		return true;
	}
	
	function ajaxRequest() {
		$selectedowner = sql::fetch(sql::run(
			" SELECT `EnableGuestRating` FROM `{" .$this->sqlOwnerTable . "}`" .
			" WHERE `ID` = '".$this->selectedOwnerID."'"));
		
		if ($selectedowner['EnableGuestRating'])
			$this->guestRating = true;
		
		$this->verify();
		return true;
	}
	
	function display() {
		$owner = sql::fetch(sql::run(
			" SELECT * FROM `{" .$this->sqlOwnerTable. "}`" .
			" WHERE `ID` = '".$this->selectedOwnerID."'" .
			" LIMIT 1"));
		
		$ratings = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Total` FROM `{".$this->sqlTable."}`" .
			" WHERE `".$this->sqlRow."` = '".$this->selectedOwnerID."'" .
			" LIMIT 1"));
		
		$utime = microtime(true);
		
		for ($i = 1; $i <= 10; $i++) {
			echo
				"<a class='star-rating' " .
					"title='" .
						htmlspecialchars(sprintf(__("Your vote: %s (votes: %s, average: %s out of %s)"),
							$i, $ratings['Total'], $owner['Rating'], 10), ENT_QUOTES) .
						"' " .
					"name='".strtolower(get_class($this)).$this->selectedOwnerID.$utime."' " .
					"href='".url::uri(strtolower(get_class($this)).', rate').
						"&amp;request=".$this->uriRequest .
						"&amp;".strtolower($this->sqlRow)."=".$this->selectedOwnerID .
						"&amp;rate=".$i."'" .
					($i == $owner['Rating']?
						(JCORE_VERSION <= '0.2'?
							" checked='checked'":
							" rel='checked'"):
						null) .
					"></a>";
		}
	}
}

?>