<?php

/***************************************************************************
 * 
 *  Name: Poll Module
 *  URI: http://jcore.net
 *  Description: Allow people to vote on subjects. Released under the GPL, LGPL, and MPL Licenses.
 *  Author: Istvan Petres
 *  Version: 0.4
 *  Tags: poll module, gpl, lgpl, mpl
 * 
 ****************************************************************************/

define('POLL_TYPE_SELECT', 1);
define('POLL_TYPE_CHECK', 2);

class pollPictures extends pictures {
	var $sqlTable = 'pollpictures';
	var $sqlRow = 'PollID';
	var $sqlOwnerTable = 'polls';
	var $adminPath = 'admin/modules/poll/pollpictures';
	
	function __construct() {
		languages::load('poll');
		
		parent::__construct();
		
		$this->rootPath = $this->rootPath.'poll/';
		$this->rootURL = $this->rootURL.'poll/';
		
		$this->selectedOwner = _('Poll');
		$this->uriRequest = "modules/poll/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('poll');
	}
	
	function download($id, $force = false) {
		if (!(int)$id) {
			tooltip::display(
				_("No picture selected to download!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT `".$this->sqlRow."` FROM `{" .$this->sqlTable . "}`" .
			" WHERE `ID` = '".(int)$id."'" .
			" LIMIT 1"));
		
		if (!$row) {
			tooltip::display(
				_("The selected picture cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (!poll::checkAccess((int)$row[$this->sqlRow], true)) {
			tooltip::display(
				_("You need to be logged in to view this picture. " .
					"Please login or register."),
				TOOLTIP_ERROR);
			return false;
		}
		
		return parent::download($id, $force);
	}
	
	function ajaxRequest() {
		if (!$row = poll::checkAccess($this->selectedOwnerID)) {
			$poll = new poll();
			$poll->displayLogin();
			unset($poll);
			return true;
		}
		
		if (isset($row['MembersOnly']) && $row['MembersOnly'] && 
			!$GLOBALS['USER']->loginok)
			$this->customLink = 
				"javascript:jQuery.jCore.tooltip.display(\"" .
				"<div class=\\\"tooltip error\\\"><span>" .
				htmlspecialchars(_("You need to be logged in to view this picture. " .
					"Please login or register."), ENT_QUOTES)."</span></div>\", true)";
		
		return parent::ajaxRequest();
	}
}

class pollAttachments extends attachments {
	var $sqlTable = 'pollattachments';
	var $sqlRow = 'PollID';
	var $sqlOwnerTable = 'polls';
	var $adminPath = 'admin/modules/poll/pollattachments';
	
	function __construct() {
		languages::load('poll');
		
		parent::__construct();
		
		$this->rootPath = $this->rootPath.'poll/';
		$this->rootURL = $this->rootURL.'poll/';
		
		$this->selectedOwner = _('Poll');
		$this->uriRequest = "modules/poll/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('poll');
	}
	
	function download($id) {
		if (!(int)$id) {
			tooltip::display(
				_("No file selected to download!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT `".$this->sqlRow."` FROM `{" .$this->sqlTable . "}`" .
			" WHERE `ID` = '".(int)$id."'" .
			" LIMIT 1"));
		
		if (!$row) {
			tooltip::display(
				_("The selected file cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (!poll::checkAccess((int)$row[$this->sqlRow], true)) {
			tooltip::display(
				_("You need to be logged in to download this file. " .
					"Please login or register."),
				TOOLTIP_ERROR);
			return false;
		}
		
		return attachments::download($id);
	}
	
	function ajaxRequest() {
		if (!$row = poll::checkAccess($this->selectedOwnerID)) {
			$poll = new poll();
			$poll->displayLogin();
			unset($poll);
			return true;
		}
		
		if (isset($row['MembersOnly']) && $row['MembersOnly'] && !$GLOBALS['USER']->loginok)
			$attachments->customLink = 
				"javascript:jQuery.jCore.tooltip.display(\"" .
				"<div class=\\\"tooltip error\\\"><span>" .
				htmlspecialchars(_("You need to be logged in to download this file. " .
					"Please login or register."), ENT_QUOTES)."</span></div>\", true)";
		
		return parent::ajaxRequest();
	}
}

class pollComments extends comments {
	var $sqlTable = 'pollcomments';
	var $sqlRow = 'PollID';
	var $sqlOwnerTable = 'polls';
	var $adminPath = 'admin/modules/poll/pollcomments';
	
	function __construct() {
		languages::load('poll');
		
		parent::__construct();
		
		$this->selectedOwner = _('Poll');
		$this->uriRequest = "modules/poll/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('poll');
	}
	
	static function getCommentURL($comment = null) {
		if ($comment)
			return poll::getURL($comment['PollID']).
				"&pollid=".$comment['PollID'];
		
		if ($GLOBALS['ADMIN'])
			return poll::getURL(admin::getPathID()).
				"&pollid=".admin::getPathID();
		
		return 
			parent::getCommentURL();
	}
	
	function ajaxRequest() {
		if (!poll::checkAccess($this->selectedOwnerID)) {
			$poll = new poll();
			$poll->displayLogin();
			unset($poll);
			return true;
		}
		
		return parent::ajaxRequest();
	}
}

class pollAnswers {
	var $selectedPollID;
	var $hideResults = false;
	var $showGuestAnswers = false;
	var $adminPath = 'admin/modules/poll/pollanswers';
	
	function __construct() {
		languages::load('poll');
		
		if (isset($_GET['pollid']))
			$this->selectedPollID = (int)$_GET['pollid'];
	}
	
	function __destruct() {
		languages::unload('poll');
	}
	
	function SQL() {
		return
			" SELECT * FROM `{pollanswers}`" .
			" WHERE `PollID` = '".(int)$this->selectedPollID."'" .
			" ORDER BY `OrderID`, `ID`";
	}
	
	// ************************************************   Admin Part
	function displayAdminOne(&$row) {
		echo 
			"<div class='poll-answer" .
				($row['_CSSClass']?
					" ".$row['_CSSClass']:
					null) .
				"'>" .
				"<div class='poll-answer-select rounded-corners'>" .
					"<input type='text' name='answerorders[".$row['PollID']."][".$row['ID']."]' " .
						"value='".$row['OrderID']."' " .
						"class='order-id-entry' tabindex='1' " .
						"style='width: 15px;' />" .
				"</div>" .
				"<div class='poll-answer-details'>" .
					"<div class='poll-answer-title'>";
		
		$this->displayTitle($row);
		
		echo
					"</div>";
		
		if (!$this->hideResults)
			$this->displayProgressBar($row);
		
		if ($row['GuestAnswers']) {
			echo
					"<div class='poll-answer-guest'>";
		
			$this->displayGuestAnswer($row);
			
			echo
					"</div>";
		}
		
		echo
				"</div>" .
			"</div>";
	}
			
	function displayAdmin() {
		$rows = sql::run(
			$this->SQL());
		
		$i = 1;
		$total = sql::rows($rows);
		
		if (!$total)
			return;
		
		echo "<div class='poll-answers'>";
		
		while($row = sql::fetch($rows)) {
			$poll = sql::fetch(sql::run(
				" SELECT * FROM `{polls}`" .
				" WHERE `ID` = '".$row['PollID']."'"));
			
			$row['_CSSClass'] = null;
			$row['_PollVotes'] = $poll['Votes'];
			$row['_PollTypeID'] = $poll['TypeID'];
			
			if ($i == 1)
				$row['_CSSClass'] .= ' first';
			if ($i == $total)
				$row['_CSSClass'] .= ' last';
			
			$this->displayAdminOne($row);
			
			$i++;
		}
		
		echo 
				"<div class='clear-both'></div>" .
			"</div>";
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		if ($values['OrderID'] == '') {
			$row = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{pollanswers}` " .
				" WHERE `PollID` = '".$values['PollID']."'" .
				" ORDER BY `OrderID` DESC"));
			
			$values['OrderID'] = (int)$row['OrderID']+1;
		}
		
		$newid = sql::run(
			" INSERT INTO `{pollanswers}` SET" .
			" `PollID` = '".
				(int)$values['PollID']."'," .
			" `Answer` = '".
				sql::escape($values['Answer'])."'," .
			" `GuestAnswers` = '".
				(int)$values['GuestAnswers']."', " .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
				
		if (!$newid) {
			tooltip::display(
				sprintf(_("Poll answer couldn't be created! Error: %s"), 
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
			" UPDATE `{pollanswers}` SET" .
			" `Answer` = '".
				sql::escape($values['Answer'])."'," .
			" `GuestAnswers` = '".
				(int)$values['GuestAnswers']."', " .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(_("Poll answer couldn't be updated! Error: %s"), 
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
			" DELETE FROM `{pollanswers}`" .
			" WHERE `ID` = '".(int)$id."'");
		
		return true;
	}
	
	// ************************************************   Client Part
	function displaySelect(&$row) {
		if ($row['_PollTypeID'] == POLL_TYPE_CHECK) {
			echo
				"<input type='checkbox' " .
					"name='pollanswers[".$row['PollID']."][".$row['ID']."]' " .
					"value='".$row['ID']."' />";
		} else {
			echo
				"<input type='radio' " .
					"name='pollanswers[".$row['PollID']."]' " .
					"value='".$row['ID']."' />";
		}
	}
	
	function displayTitle(&$row) {
		echo $row['Answer'];
		
		if (!$this->hideResults)
			echo
				" <span class='comment'>" .
					"(".sprintf(_("%s votes"), $row['Votes']).")" .
				"</span>";
	}
	
	function displayProgressBar(&$row) {
		$percentage = 0;
		
		if ($row['_PollVotes'])
			$percentage = round($row['Votes']*100/$row['_PollVotes']);
		
		echo 
			"<div class='progressbar poll-answer-progressbar rounded-corners'>" .
				"<div class='progressbar-value poll-answer-progressbar-value rounded-corners' " .
					"style='width: ".$percentage."%'>" .
					"<span>".$percentage."%</span>" .
				"</div>" .
			"</div>";
	}
	
	function displayGuestAnswers(&$row) {
		$answers = sql::run(
			" SELECT `GuestAnswer` FROM `{pollvotes}`" .
			" WHERE `AnswerID` = '".$row['ID']."'" .
			" AND `GuestAnswer` != ''" .
			" ORDER BY `ID`");
			
		if (!sql::rows($answers))
			return;
		
		echo 
			"<ul class='poll-guest-answers'>";
				
		while ($answer = mysql_fetch_array($answers))
			echo 
				"<li>".
					trim($answer['GuestAnswer']) .
				"</li>";
					
		echo 
			"</ul>";
	}
	
	function displayGuestAnswer(&$row) {
		if ($this->showGuestAnswers) {
			$this->displayGuestAnswers($row);
			return;
		}
		
		if ($row['_PollClosed'])
			return; 
		
		echo
			"<input type='text' " .
				"name='pollguestanswers[".$row['PollID']."][".$row['ID']."]' " .
				"value='' />";
	}
	
	function displayOne(&$row) {
		echo 
			"<div class='poll-answer poll-answer".$row['ID'] .
				($row['_CSSClass']?
					" ".$row['_CSSClass']:
					null) .
				"'>";
		
		if (!$row['_PollClosed']) {
			echo
				"<div class='poll-answer-select rounded-corners'>";
			
			$this->displaySelect($row);
			
			echo
				"</div>";
		}
		
		echo
				"<div class='poll-answer-details'>" .
					"<div class='poll-answer-title'>";
		
		$this->displayTitle($row);
		
		echo
					"</div>";
		
		if (!$this->hideResults)
			$this->displayProgressBar($row);
		
		if ($row['GuestAnswers']) {
			echo
					"<div class='poll-answer-guest'>";
		
			$this->displayGuestAnswer($row);
			
			echo
					"</div>";
		}
		
		echo
				"</div>" .
			"</div>";
	}
			
	function display() {
		$rows = sql::run(
			$this->SQL());
		
		$i = 1;
		$total = sql::rows($rows);
		
		if (!$total)
			return;
		
		echo "<div class='poll-answers'>";
		
		while($row = sql::fetch($rows)) {
			$poll = sql::fetch(sql::run(
				" SELECT * FROM `{polls}`" .
				" WHERE `ID` = '".$row['PollID']."'"));
			
			$row['_CSSClass'] = null;
			$row['_PollVotes'] = $poll['Votes'];
			$row['_PollTypeID'] = $poll['TypeID'];
			$row['_PollClosed'] = false;
			
			if (isset($poll['VotingsClosed']))
				$row['_PollClosed'] = $poll['VotingsClosed'];
			
			if ($i == 1)
				$row['_CSSClass'] .= ' first';
			if ($i == $total)
				$row['_CSSClass'] .= ' last';
			
			$this->displayOne($row);
			
			$i++;
		}
		
		echo 
				"<div class='clear-both'></div>" .
			"</div>";
	}
}

class poll extends modules {
	static $uriVariables = 'pollid, rate';
	var $limit = 0;
	var $format = null;
	var $selectedID;
	var $votedOnID;
	var $randomize = false;
	var $ignorePaging = false;
	var $showPaging = true;
	var $ajaxPaging = AJAX_PAGING;
	var $ajaxRequest = null;
	var $adminPath = 'admin/modules/poll';
	
	function __construct() {
		languages::load('poll');
		
		if (isset($_GET['pollid']))
			$this->selectedID = (int)$_GET['pollid'];
		
		if (isset($_POST['pollanswers']))
			$this->votedOnID = (int)key((array)$_POST['pollanswers']);
	}
	
	function __destruct() {
		languages::unload('poll');
	}
	
	function SQL() {
		return
			" SELECT * FROM `{polls}`" .
			" WHERE `Deactivated` = 0" .
			((int)$this->selectedID?
				" AND `ID` = '".(int)$this->selectedID."'":
				null) .
			(!$GLOBALS['USER']->loginok?
				" AND (`MembersOnly` = 0 " .
				"	OR `ShowToGuests` = 1)":
				null) .
			" ORDER BY" .
			($this->randomize?
				" RAND()":
				" `OrderID`, `TimeStamp` DESC, `Title`");
	}
	
	function installSQL() {
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{polls}` (" .
			" `ID` smallint(5) unsigned NOT NULL auto_increment," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `Description` mediumtext NULL," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Path` varchar(255) NOT NULL default ''," .
			" `TypeID` tinyint(1) unsigned NOT NULL default '1'," .
			" `Votes` mediumint(8) unsigned NOT NULL default '0'," .
			" `Comments` smallint(5) unsigned NOT NULL default '0'," .
			" `Pictures` smallint(5) unsigned NOT NULL default '0'," .
			" `Attachments` smallint(5) unsigned NOT NULL default '0'," .
			" `Deactivated` tinyint(1) unsigned NOT NULL default '0'," .
			" `HideResults` tinyint(1) unsigned NOT NULL default '0'," .
			" `VotingsClosed` tinyint(1) unsigned NOT NULL default '0'," .
			" `VotingsClosedDate` DATE NULL DEFAULT NULL," .
			" `EnableComments` tinyint(1) unsigned NOT NULL default '0'," .
			" `EnableGuestComments` tinyint(1) unsigned NOT NULL default '0'," .
			" `MembersOnly` tinyint(1) unsigned NOT NULL default '0'," .
			" `ShowToGuests` tinyint(1) unsigned NOT NULL default '0'," .
			" `VotingInterval` smallint(5) unsigned NOT NULL default '1440'," .
			" `UserID` mediumint(8) unsigned NOT NULL default '1'," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `Path` (`Path`)," .
			" KEY `UserID` (`UserID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `Deactivated` (`Deactivated`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `MembersOnly` (`MembersOnly`)," .
			" KEY `ShowToGuests` (`ShowToGuests`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{pollanswers}` (" .
			" `ID` mediumint(8) unsigned NOT NULL auto_increment," .
			" `PollID` smallint(5) unsigned NOT NULL default '0'," .
			" `Answer` varchar(255) NOT NULL default ''," .
			" `GuestAnswers` tinyint(1) unsigned NOT NULL default '0'," .
			" `Votes` mediumint(8) unsigned NOT NULL default '0'," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `PollID` (`PollID`)," .
			" KEY `OrderID` (`OrderID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{pollvotes}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `PollID` smallint(5) unsigned NOT NULL default  '0'," .
			" `AnswerID` mediumint(8) unsigned NOT NULL default '0'," .
			" `GuestAnswer` varchar(100) NOT NULL default ''," .
			" `UserID` mediumint(8) unsigned NOT NULL default '1'," .
			" `IP` DECIMAL(39, 0) NOT NULL default '0'," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `PollID` (`PollID`)," .
			" KEY `AnswerID` (`AnswerID`)," .
			" KEY `UserID` (`UserID`)," .
			" KEY `IP` (`IP`)," .
			" KEY `TimeStamp` (`TimeStamp`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{pollcomments}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `PollID` smallint(5) unsigned NOT NULL default '0'," .
			" `UserName` varchar(100) NOT NULL default ''," .
			" `Email` varchar(100) NOT NULL default ''," .
			" `UserID` mediumint(8) unsigned NOT NULL default '0'," .
			" `Comment` text NULL," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP," .
			" `IP` DECIMAL(39, 0) NOT NULL default '0'," .
			" `SubCommentOfID` int(10) unsigned NOT NULL default '0'," .
			" `Rating` smallint(6) NOT NULL default '0'," .
			" `Pending` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `PollID` (`PollID`)," .
			" KEY `SubCommentOfID` (`SubCommentOfID`)," .
			" KEY `UserName` (`UserName`)," .
			" KEY `UserID` (`UserID`)," .
			" KEY `Pending` (`Pending`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{pollcommentsratings}` (" .
			" `CommentID` int(10) unsigned NOT NULL default '0'," .
			" `UserID` mediumint(8) unsigned NOT NULL default '0'," .
			" `IP` DECIMAL(39, 0) NOT NULL default '0'," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Rating` tinyint(1) NOT NULL default '0'," .
			" KEY `CommentID` (`CommentID`)," .
			" KEY `UserID` (`UserID`)," .
			" KEY `IP` (`IP`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `Rating` (`Rating`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{pollpictures}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" `Location` varchar(255) NOT NULL default ''," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `URL` varchar(255) NOT NULL default ''," .
			" `PollID` smallint(5) unsigned NOT NULL default '1'," .
			" `Views` int(10) unsigned NOT NULL default '0'," .
			" `Thumbnail` tinyint(1) unsigned NOT NULL default '0'," .
			" PRIMARY KEY (`ID`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `PollID` (`PollID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{pollattachments}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" `Location` varchar(255) NOT NULL default ''," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `HumanMimeType` varchar(255) NOT NULL default ''," .
			" `FileSize` int(10) unsigned NOT NULL default '0'," .
			" `PollID` smallint(5) unsigned NOT NULL default '1'," .
			" `Downloads` int(10) unsigned NOT NULL default '0'," .
			" PRIMARY KEY (`ID`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `PollID` (`PollID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
			
		return true;
	}
	
	function installFiles() {
		$css = 
			".poll {\n" .
			"	margin-bottom: 20px;\n" .
			"}\n" .
			"\n" .
			".poll-title {\n" .
			"	margin: 0;\n" .
			"}\n" .
			"\n" .
			".poll-pictures {\n" .
			"	float: right;\n" .
			"}\n" .
			"\n" .
			".poll-details {\n" .
			"	margin: 3px 0 7px 0;\n" .
			"}\n" .
			"\n" .
			".poll-answers {\n" .
			"	padding-bottom: 5px;\n" .
			"}\n" .
			"\n" .
			".poll-answer {\n" .
			"	clear: both;\n" .
			"	padding: 5px 0 5px 0;\n" .
			"}\n" .
			"\n" .
			".poll-answer-select {\n" .
			"	float: left;\n" .
			"	padding: 7px 5px;\n" .
			"}\n" .
			"\n" .
			".poll-answer-details {\n" .
			"	margin-left: 40px;\n" .
			"}\n" .
			"\n" .
			".poll.closed .poll-answer-details {\n" .
			"	margin-left: 0;\n" .
			"}\n" .
			"\n" .
			".poll-answer-guest {\n" .
			"	margin-bottom: 5px;\n" .
			"}\n" .
			"\n" .
			".poll-vote-button {\n" .
			"	margin-bottom: 15px;\n" .
			"}\n" .
			".poll-links a {\n" .
			"	display: inline-block;\n" .
			"	padding: 5px 0px 5px 20px;\n" .
			"	background: url(\"http://icons.jcore.net/16/link.png\") 0px 50% no-repeat;\n" .
			"	margin: 10px 10px 0 0;\n" .
			"}\n" .
			"\n" .
			".poll-links .back {\n" .
			"	background-image: url(\"http://icons.jcore.net/16/doc_page_previous.png\");\n" .
			"}\n" .
			"\n" .
			".poll-links .comments {\n" .
			"	background-image: url(\"http://icons.jcore.net/16/comment.png\");\n" .
			"}\n" .
			"\n" .
			".poll.last .separator.bottom,\n" .
			".poll.last .spacer.bottom\n" .
			"{\n" .
			"	display: none;\n" .
			"}\n" .
			"\n" .
			".as-modules-poll a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/eq.png\");\n" .
			"}\n";
		
		return
			files::save(SITE_PATH.'template/modules/css/poll.css', $css);
	}
	
	function uninstallSQL() {
		sql::run(
			" DROP TABLE IF EXISTS `{polls}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{pollanswers}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{pollvotes}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{pollcomments}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{pollcommentsratings}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{pollpictures}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{pollattachments}`;");
		
		return true;
	}
	
	function uninstallFiles() {
		return
			files::delete(SITE_PATH.'template/modules/css/poll.css');
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		if (!parent::installed($this))
			return 0;
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{polls}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				_('New Poll'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Layout Blocks'), 
			'?path=admin/site/blocks');
		favoriteLinks::add(
			__('Settings'), 
			'?path=admin/site/settings');
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			_('Question'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 350px;');
		
		$form->add(
			__('Type'),
			'TypeID',
			FORM_INPUT_TYPE_SELECT,
			true);
		$form->setValueType(FORM_VALUE_TYPE_INT);
			
		$form->addValue(
			POLL_TYPE_SELECT,
			poll::type2Text(POLL_TYPE_SELECT));
		
		$form->addValue(
			POLL_TYPE_CHECK,
			poll::type2Text(POLL_TYPE_CHECK));
		
		$form->add(
			"<div class='form-entry-title'>" .
				_("Answers").":</div>" .
			"<div class='form-entry-content comment'>" .
				_("(check boxes for Guest answers)") .
			"</div>",
			'Answers',
			FORM_STATIC_TEXT);
		$form->setValueType(FORM_VALUE_TYPE_ARRAY);
		
		$form->add(
			"<div class='form-entry-additional-answers-container'></div>" .
			"<div class='form-entry-title'></div>" .
			"<div class='form-entry-content'>" .
				"<a href='javascript://' class='add-link' " .
					"onclick=\"jQuery.jCore.form.appendEntryTo(" .
						"'.form-entry-additional-answers-container', " .
						"'', " .
						"'Answers[]', " .
						FORM_INPUT_TYPE_TEXT."," .
						"true," .
						"''," .
						"'style=\'width: 250px;\'');\">" .
					_("Add another answer") .
				"</a>" .
			"</div>",
			null,
			FORM_STATIC_TEXT);
			
		$form->add(
			_('Voting Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			_('Hide Results'),
			'HideResults',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
		
		if (JCORE_VERSION >= '0.7') {
			$form->add(
				_('Voting Closed'),
				'VotingsClosed',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
		}
		
		$form->add(
			_('Voting Interval'),
			'VotingInterval',
			FORM_INPUT_TYPE_TEXT,
			false,
			1440);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		$form->addAdditionalText(
			" "._("minutes (if set to 0 only one vote will be allowed per ip or user)"));
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Content Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Description'),
			'Description',
			FORM_INPUT_TYPE_TEXTAREA);
		$form->setStyle('width: ' .
			(JCORE_VERSION >= '0.7'?
				'90%':
				'350px') .
			'; height: 200px;');
		$form->setValueType(FORM_VALUE_TYPE_HTML);
		
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
			__('Path'),
			'Path',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 300px;');
		
		$form->add(
			__('Created on'),
			'TimeStamp',
			FORM_INPUT_TYPE_TIMESTAMP);
		$form->setStyle('width: 170px;');
		$form->setValueType(FORM_VALUE_TYPE_TIMESTAMP);
		
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
			__('Owner'),
			'Owner',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 110px;');
		
		$form->addAdditionalText(
			"<a style='zoom: 1;' href='".url::uri('request, users') .
				"&amp;request=".url::path() .
				"&amp;users=1' " .
				"class='select-owner-link ajax-content-link'>" .
				_("Select User") .
			"</a>");
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
	}
	
	function verifyAdmin(&$form = null) {
		$reorder = null;
		$orders = null;
		$answerorders = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_POST['reordersubmit']))
			$reorder = $_POST['reordersubmit'];
		
		if (isset($_POST['orders']))
			$orders = (array)$_POST['orders'];
		
		if (isset($_POST['answerorders']))
			$answerorders = (array)$_POST['answerorders'];
		
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
					" UPDATE `{polls}` " .
					" SET `OrderID` = '".(int)$ovalue."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".(int)$oid."'" .
					($this->userPermissionIDs?
						" AND `ID` IN (".$this->userPermissionIDs.")":
						null) .
					($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
						" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'":
						null));
				
				if (isset($answerorders[$oid]) && is_array($answerorders[$oid]))
					foreach($answerorders[$oid] as $aid => $avalue)
						sql::run(
							" UPDATE `{pollanswers}` " .
							" SET `OrderID` = '".(int)$avalue."'" .
							" WHERE `ID` = '".(int)$aid."'" .
							($this->userPermissionIDs?
								" AND `PollID` IN (".$this->userPermissionIDs.")":
								null));
			}
			
			tooltip::display(
				_("Polls and answers have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				_("Poll has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if ($form->get('Owner')) {
			$user = sql::fetch(sql::run(
				" SELECT * FROM `{users}` " .
				" WHERE `UserName` = '".sql::escape($form->get('Owner'))."'"));
			
			if (!$user) {
				tooltip::display(
					sprintf(__("User \"%s\" couldn't be found!"), 
						$form->get('Owner'))." " .
					__("Please make sure you have entered / selected the right " .
						"username or if it's a new user please first create " .
						"the user at Member Management -> Users."),
					TOOLTIP_ERROR);
				
				$form->setError('Owner', FORM_ERROR_REQUIRED);
				return false;
			}
			
			$form->add(
				'UserID',
				'UserID',
				FORM_INPUT_TYPE_HIDDEN);
			$form->setValue('UserID', $user['ID']);
		}
		
		if (!$form->get('Path'))
			$form->set('Path', url::genPathFromString($form->get('Title')));
			
		$postarray = $form->getPostArray();
		$postarray['GuestAnswers'] = (isset($_POST['GuestAnswers'])?
											$_POST['GuestAnswers']:
											array());
				
		if ($edit) {
			if (!$this->edit($id, $postarray))
				return false;
				
			tooltip::display(
				_("Poll has been successfully updated.")." " .
				(modules::getOwnerURL('poll')?
					"<a href='".poll::getURL($id).
						"&amp;pollid=".$id."' target='_blank'>" .
						_("View Poll") .
					"</a>" .
					" - ":
					null) .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($this->userPermissionIDs)
			return false;
		
		if (!$newid = $this->add($postarray))
			return false;
				
		tooltip::display(
			_("Poll has been successfully created.")." " .
			(modules::getOwnerURL('poll')?
				"<a href='".poll::getURL().
					"&amp;pollid=".$newid."' target='_blank'>" .
					_("View Poll") .
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
				__("Title / Created on")."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				_("Votes")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
		echo
			"<th><span class='nowrap'>".
				__("Comments")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Pictures")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Attachments")."</span></th>";
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
				"<a href='".
				url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."' " .
					"class='bold'>" .
					$row['Title'] .
				"</a> " .
				"<div class='comment' style='padding-left: 10px;'>" .
					calendar::dateTime($row['TimeStamp'])." ";
		
		$GLOBALS['USER']->displayUserName($user, __('by %s'));
		
		echo
				"</div>" .
			"</td>" .
			"<td style='text-align: right;'>" .
				($row['Votes']?
					$row['Votes']:
					null) .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='admin-link comments' " .
					"title='".htmlspecialchars(__("Comments"), ENT_QUOTES).
						" (".$row['Comments'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/pollcomments'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $row['Comments'])
			counter::display($row['Comments']);
		
		echo
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link pictures' " .
					"title='".htmlspecialchars(__("Pictures"), ENT_QUOTES) .
						" (".$row['Pictures'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/pollpictures'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $row['Pictures'])
			counter::display($row['Pictures']);
		
		echo
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link attachments' " .
					"title='".htmlspecialchars(__("Attachments"), ENT_QUOTES) .
						" (".$row['Attachments'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/pollattachments'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $row['Attachments'])
			counter::display($row['Attachments']);
		
		echo
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
		admin::displayItemData(
			__("Type"),
			poll::type2Text($row['TypeID']));
		
		admin::displayItemData(
			_("Voting Interval"),
			sprintf(__("%s minutes"), $row['VotingInterval']));
		
		if ($row['EnableComments'])
			admin::displayItemData(
				__("Enable Comments"),
				__("Yes") .
				($row['EnableGuestComments']?
					" ".__("(Guests can comment too!)"):
					null));
		
		if ($row['HideResults'])
			admin::displayItemData(
				_("Hide Results"),
				__("Yes"));
		
		if (JCORE_VERSION >= '0.7' && $row['VotingsClosed'])
			admin::displayItemData(
				_("Voting Closed"),
				__("Yes")." " .
				sprintf(_("(on %s)"), calendar::date($row['VotingsClosedDate'])));
		
		if ($row['MembersOnly'])
			admin::displayItemData(
				_("Members Only"),
				__("Yes"));
		
		if ($row['ShowToGuests'])
			admin::displayItemData(
				_("Show to Guests"),
				__("Yes"));
		
		admin::displayItemData(
			__("Path"),
			$row['Path']);
		
		admin::displayItemData(
			"<hr />");
		admin::displayItemData(
			nl2br($row['Description']));
		
		$this->displayAdminAnswers($row);
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
	
	function displayAdminListFunctions() {
		echo
			"<input type='submit' name='reordersubmit' value='".
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminList($rows) {
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		echo
			"<form action='".url::uri('edit, delete')."' method='post'>";
		
		echo "<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr class='lheader'>";
			
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
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			$this->displayAdminListFunctions();
			
			echo
				"<div class='clear-both'></div>" .
				"<br />";
		}
					
		echo
			"</form>";
			
		return true;
	}
	
	function displayAdminAnswers(&$row) {
		$answers = new pollAnswers();
		$answers->selectedPollID = $row['ID'];
		$answers->showGuestAnswers = true;
		$answers->displayAdmin();
		unset($answers);
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}

	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			_('Poll Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$search = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
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
		
		echo
			"<div class='admin-content'>";
				
		$form = new form(
				($edit?
					_("Edit Poll"):
					_("New Poll")),
				'neweditpoll');
		
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
		
		$selected = null;
		$verifyok = false;
		
		if ($id)
			$selected = sql::fetch(sql::run(
				" SELECT `ID` FROM `{polls}`" .
				" WHERE `ID` = '".$id."'" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
					" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'":
					null)));
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			((!$edit && !$delete) || $selected))
			$verifyok = $this->verifyAdmin($form);
		
		$rows = sql::run(
			" SELECT * FROM `{polls}`" .
			" WHERE 1" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
				" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'":
				null) .
			($search?
				sql::search(
					$search,
					array('Title', 'Description')):
				null) .
			" ORDER BY `OrderID`, `ID` DESC");
		
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
					_("No polls found."),
					TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && $selected)))
		{
			if ($edit && $selected && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{polls}`" .
					" WHERE `ID` = '".$id."'"));
				
				$form->setValues($selected);
				
				$user = $GLOBALS['USER']->get($selected['UserID']);
				$form->setValue('Owner', $user['UserName']);
				
				$answers = sql::run(
					" SELECT * FROM `{pollanswers}` " .
					" WHERE `PollID` = '".$selected['ID']."'" .
					" ORDER BY `OrderID` DESC, `ID` DESC");
				
				$i = 0;
				while($answer = sql::fetch($answers)) {
					$iname = 'Answers['.$answer['ID'].'_existing]';
					
					$form->insert(
						'Answers',
						'',
						$iname,
						FORM_INPUT_TYPE_TEXT,
						false,
						$answer['Answer']);
					$form->setValueType($iname, FORM_VALUE_TYPE_ARRAY);
					$form->setStyle($iname, 'width: 250px;');
					
					$form->addAdditionalText(
						$iname, 
						"<input type='checkbox' name='GuestAnswers[".$answer['ID']."_existing]' value='1'" .
							($answer['GuestAnswers']?
								" checked='checked'":
								null) .
							" />" .
						"<a href='javascript://' class='remove-link' " .
							"onclick=\"jQuery.jCore.form.removeEntry(this);\">" .
							__("Remove") .
						"</a>");
		
					$i++;
				}
				
			} else {
				if (isset($_POST['Answers']) && count($_POST['Answers'])) {
					$piname = 'Answers';
					
					foreach($_POST['Answers'] as $key => $answer) {
						$iname = 'Answers['.$key.']';
					
						$form->insert(
							$piname,
							'',
							$iname,
							FORM_INPUT_TYPE_TEXT,
							false,
							$answer);
						$form->setStyle($iname, 'width: 250px;');
						
						$form->addAdditionalText(
							$iname,
							"<input type='checkbox' name='GuestAnswers[".$key."]' value='1'" .
								(isset($_POST['GuestAnswers'][$key]) && 
								 $_POST['GuestAnswers'][$key]?
								 	" checked='checked'":
								 	null) .
								" />" .
							"<a href='javascript://' class='remove-link' " .
								"onclick=\"jQuery.jCore.form.removeEntry(this);\">" .
								__("Remove") .
							"</a>");
						
						$piname = $iname;
					}
					
				} else {
					for ($i = 7; $i > 0; $i--) {
						$iname = 'Answers['.$i.']';
						
						$form->insert(
							'Answers',
							'',
							$iname,
							FORM_INPUT_TYPE_TEXT,
							false);
						$form->setStyle($iname, 'width: 250px;');
						
						$form->addAdditionalText(
							$iname,
							"<input type='checkbox' name='GuestAnswers[".$i."]' value='1' />" .
							"<a href='javascript://' class='remove-link' " .
								"onclick=\"jQuery.jCore.form.removeEntry(this);\">" .
								__("Remove") .
							"</a>");
					}
				}
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
			sql::run(
				" UPDATE `{polls}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`");
			
			$values['OrderID'] = 1;
			
		} else {
			sql::run(
				" UPDATE `{polls}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		$newid = sql::run(
			" INSERT INTO `{polls}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `TypeID` = '".
				(int)$values['TypeID']."'," .
			" `Description` = '".
				sql::escape($values['Description'])."'," .
			" `TimeStamp` = " .
				($values['TimeStamp']?
					"'".sql::escape($values['TimeStamp'])."'":
					"NOW()").
				"," .
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `HideResults` = '".
				(int)$values['HideResults']."'," .
			(JCORE_VERSION >= '0.7'?
				" `VotingsClosed` = '".
					(int)$values['VotingsClosed']."'," .
				($values['VotingsClosed']?
					" `VotingsClosedDate` = NOW(),":
					" `VotingsClosedDate` = NULL,"):
				null) .
			" `EnableComments` = '".
				(int)$values['EnableComments']."'," .
			" `EnableGuestComments` = '".
				(int)$values['EnableGuestComments']."'," .
			" `MembersOnly` = '".
				(int)$values['MembersOnly']."'," .
			" `ShowToGuests` = '".
				(int)$values['ShowToGuests']."'," .
			" `VotingInterval` = '".
				(int)$values['VotingInterval']."'," .
			" `UserID` = '".
				(isset($values['UserID']) && (int)$values['UserID']?
					(int)$values['UserID']:
					(int)$GLOBALS['USER']->data['ID']) .
				"'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
		
		if (!$newid) {
			tooltip::display(
				sprintf(_("Poll couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		$answers = new pollAnswers();
		
		foreach ($values['Answers'] as $key => $answer) {
			if (!trim($answer))
				continue;
			
			$answers->add(array(
				'Answer' => $answer,
				'PollID' => $newid,
				'OrderID' => '',
				'GuestAnswers' => 
					(isset($values['GuestAnswers'][$key]) && 
					 $values['GuestAnswers'][$key]?
					 	true:
					 	false)));
		}
		
		unset($answers);
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		sql::run(
			" UPDATE `{polls}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `TypeID` = '".
				(int)$values['TypeID']."'," .
			" `Description` = '".
				sql::escape($values['Description'])."'," .
			" `TimeStamp` = " .
				($values['TimeStamp']?
					"'".sql::escape($values['TimeStamp'])."'":
					"NOW()").
				"," .
			" `Path` = '".
				sql::escape($values['Path'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `HideResults` = '".
				(int)$values['HideResults']."'," .
			(JCORE_VERSION >= '0.7'?
				" `VotingsClosed` = '".
					(int)$values['VotingsClosed']."'," .
				($values['VotingsClosed']?
					" `VotingsClosedDate` = NOW(),":
					" `VotingsClosedDate` = NULL,"):
				null) .
			" `EnableComments` = '".
				(int)$values['EnableComments']."'," .
			" `EnableGuestComments` = '".
				(int)$values['EnableGuestComments']."'," .
			" `MembersOnly` = '".
				(int)$values['MembersOnly']."'," .
			" `ShowToGuests` = '".
				(int)$values['ShowToGuests']."'," .
			" `VotingInterval` = '".
				(int)$values['VotingInterval']."'," .
			(isset($values['UserID']) && (int)$values['UserID']?
				" `UserID` = '".(int)$values['UserID']."',":
				null) .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(_("Poll couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		$answers = new pollAnswers();
		
		$rows = sql::run(
			" SELECT * FROM `{pollanswers}` " .
			" WHERE `PollID` = '".(int)$id."'" .
			" ORDER BY `OrderID`, `ID`");
		
		$i = 0;
		while($row = sql::fetch($rows)) {
			if (!isset($values['Answers'][$row['ID'].'_existing']) || 
				!$values['Answers'][$row['ID'].'_existing'])
			{
				$answers->delete($row['ID']);
				continue;
			}
			
			$answers->edit($row['ID'], array(
				'Answer' => $values['Answers'][$row['ID'].'_existing'],
				'PollID' => (int)$id,
				'OrderID' => $row['OrderID'],
				'GuestAnswers' => 
					(isset($values['GuestAnswers'][$row['ID'].'_existing']) && 
					 $values['GuestAnswers'][$row['ID'].'_existing']?
					 	true:
					 	false)));
			
			unset($values['Answers'][$row['ID'].'_existing']);
		}
		
		if (isset($values['Answers']) && is_array($values['Answers']) &&
			count($values['Answers']))
		{
			foreach ($values['Answers'] as $key => $answer) {
				if (!trim($answer))
					continue;
				
				$answers->add(array(
					'Answer' => $answer,
					'PollID' => (int)$id,
					'OrderID' => '',
					'GuestAnswers' => 
						(isset($values['GuestAnswers'][$key]) && 
						 $values['GuestAnswers'][$key]?
						 	true:
						 	false)));
			}
		}
		
		unset($answers);
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		$comments = new pollComments();
		$pictures = new pollPictures();
		$attachments = new pollAttachments();
		$answers = new pollAnswers();
		
		$rows = sql::run(
			" SELECT * FROM `{pollcomments}` " .
			" WHERE `PollID` = '".(int)$id."'");
		
		while($row = sql::fetch($rows))
			$comments->delete($row['ID']);
		
		$rows = sql::run(
			" SELECT * FROM `{pollpictures}` " .
			" WHERE `PollID` = '".(int)$id."'");
		
		while($row = sql::fetch($rows))
			$pictures->delete($row['ID']);
		
		$rows = sql::run(
			" SELECT * FROM `{pollattachments}` " .
			" WHERE `PollID` = '".(int)$id."'");
		
		while($row = sql::fetch($rows))
			$attachments->delete($row['ID']);
		
		$rows = sql::run(
			" SELECT * FROM `{pollanswers}` " .
			" WHERE `PollID` = '".(int)$id."'");
		
		while($row = sql::fetch($rows))
			$answers->delete($row['ID']);
		
		sql::run(
			" DELETE FROM `{polls}` " .
			" WHERE `ID` = '".(int)$id."'");
		
		unset($answers);
		unset($attachments);
		unset($pictures);
		unset($comments);
		
		return true;
	}
	
	// ************************************************   Client Part
	static function getURL($id = 0) {
		$url = modules::getOwnerURL('poll', $id);
		
		if (!$url)
			return url::site() .
				url::uri(poll::$uriVariables);
		
		return $url;	
	}
	
	static function checkAccess($row, $full = false) {
		if ($GLOBALS['USER']->loginok)
			return true;
		
		if ($row && !is_array($row))
			$row = sql::fetch(sql::run(
				" SELECT `MembersOnly`, `ShowToGuests`" .
				" FROM `{polls}`" .
				" WHERE `ID` = '".(int)$row."'"));
		
		if (!$row)
			return true;
			
		if ($row['MembersOnly'] && ($full || !$row['ShowToGuests']))
			return false;
		
		return $row;
	}
	
	static function type2Text($type) {
		if (!$type)
			return;
		
		switch($type) {
			case POLL_TYPE_SELECT:
				return _('Select (one answer can be chosen)');
			case POLL_TYPE_CHECK:
				return _('Check (multiple answers can be chosen)');
			default:
				return _('Undefined!');
		}
	}
	
	function verify() {
		$vote = null;
		$answers = null;
		$guestanswers = null;
		
		if (isset($_POST['pollvote']))
			$vote = $_POST['pollvote'];
		
		if (isset($_POST['pollanswers']))
			$answers = (array)$_POST['pollanswers'];
		
		if (isset($_POST['pollguestanswers']))
			$guestanswers = (array)$_POST['pollguestanswers'];
		
		if (!$vote)
			return false;
		
		if (!$answers || !is_array($answers) || !count($answers)) {
			tooltip::display(
				_("No answer selected!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$pollid = (int)key($answers);
		
		$poll = sql::fetch(sql::run(
			" SELECT * FROM `{polls}`" .
			" WHERE `ID` = '".$pollid."'" .
			" AND `Deactivated` = 0"));
		
		if (!$poll) {
			tooltip::display(
				_("Poll cannot be found or it has been deactivated!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (!$GLOBALS['USER']->loginok && $poll['MembersOnly']) {
			tooltip::display(
				_("Only registered users can vote."),
				TOOLTIP_NOTIFICATION);
			return false;
		}
			
		$row = sql::fetch(sql::run(
			" SELECT `TimeStamp` FROM `{pollvotes}`" .
			" WHERE `PollID` = '".$poll['ID']."'" .
			($poll['VotingInterval']?
				" AND `TimeStamp` > DATE_SUB(NOW(), INTERVAL ".(int)$poll['VotingInterval']." MINUTE)":
				null) .
			($GLOBALS['USER']->loginok?
				" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'":
				" AND `IP` = '".security::ip2long($_SERVER['REMOTE_ADDR'])."'")));
			
		if ($row) {
			tooltip::display(
				_("You already voted on this poll!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$answers = $answers[$poll['ID']];
		if (!is_array($answers))
			$answers = array((int)$answers => (int)$answers);
		
		$votes = 0;
		foreach($answers as $answerid => $answer) {
			if (!$answerid)
				continue;
			
			$guestanswer = null;
			if (isset($guestanswers[$poll['ID']][$answerid]))
				$guestanswer = form::parseString($guestanswers[$poll['ID']][$answerid]);
			
			$newid = sql::run(
				" INSERT INTO `{pollvotes}` SET " .
				" `PollID` = '".$poll['ID']."'," .
				" `AnswerID` = '".$answerid."'," .
				" `IP` = '".
					security::ip2long($_SERVER['REMOTE_ADDR'])."'," .
				($GLOBALS['USER']->loginok?
					" `UserID` = '".
						(int)$GLOBALS['USER']->data['ID']."',":
					null) .
				(isset($guestanswer)?
					" `GuestAnswer` = '".sql::escape($guestanswer)."',":
					null) .
				" `TimeStamp` = NOW()");
			
			if (!$newid) {
				tooltip::display(
					sprintf(_("Vote couldn't be stored! Error: %s"), 
						sql::error()),
					TOOLTIP_ERROR);
				return false;
			}
		
			sql::run(
				" UPDATE `{pollanswers}` SET" .
				" `Votes` = `Votes` + 1" .
				" WHERE `ID` = '".$answerid."'");
			
			$votes++;
		}
	
		sql::run(
			" UPDATE `{polls}` SET" .
			" `Votes` = `Votes` + ".(int)$votes."," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".$poll['ID']."'");
		
		tooltip::display(
			_("Thank you for your vote."),
			TOOLTIP_SUCCESS);
		
		if (!$poll['HideResults'] && $this->ajaxRequest) {
			$poll['Votes'] += $votes;
			
			echo
				"<script type='text/javascript'>";
			
			$answers = sql::run(
				" SELECT `ID`, `Votes` FROM `{pollanswers}`" .
				" WHERE `PollID` = '".$poll['ID']."'");
			
			while($answer = sql::fetch($answers)) {
				$percentage = 0;
				
				if ($poll['Votes'])
					$percentage = round($answer['Votes']*100/$poll['Votes']);
				
				echo
					"jQuery('.poll-answer".$answer['ID']."').each(function() {" .
						"var jthis = jQuery(this);" .
						"jthis.find('.poll-answer-progressbar, .poll-answer-title .comment').show();" .
						"jthis.find('.poll-answer-progressbar-value').animate({'width': '" .
							$percentage."%'}, 'slow').find('span').text('" .
							$percentage."%');" .
						"jthis.find('.poll-answer-title span').text('" .
							"(".sprintf(_("%s votes"), $answer['Votes']).")');" .
					"});";
			}
			
			echo
				"</script>";
		}
		
		return true;
	}
	
	function ajaxRequest() {
		$users = null;
		$vote = null;
		
		if (isset($_GET['users']))
			$users = $_GET['users'];
		
		if (isset($_POST['pollvote']))
			$vote = $_POST['pollvote'];
		
		if ($users) {
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
			
			if (~$permission['PermissionType'] & USER_PERMISSION_TYPE_WRITE) {
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);
				return true;
			}
			
			$GLOBALS['USER']->displayQuickList('#neweditpollform #entryOwner');
			return true;
		}
		
		if ($vote) {
			$this->verify();
			return true;
		}
		
		$this->ajaxPaging = true;
		$this->display();
		return true;
	}
	
	function displayLogin() {
		tooltip::display(
			_("This area is limited to members only. " .
				"Please login below."),
			TOOLTIP_NOTIFICATION);
		
		$GLOBALS['USER']->displayLogin();
	}
	
	function displayTitle(&$row) {
		echo $row['Title'];
	}
	
	function displayDetails(&$row) {
		$user = $GLOBALS['USER']->get($row['UserID']);
		
		echo
			"<span class='details-date'>" .
			calendar::datetime($row['TimeStamp']) .
			" </span>";
					
		$GLOBALS['USER']->displayUserName($user, __('by %s'));
		
		if (isset($row['VotingsClosed']) && $row['VotingsClosed']) {
			echo
				"<span class='details-separator separator-1'>" .
				", " .
				"</span>" .
				"<span class='poll-closed-text'>" .
					sprintf(_("Closed on %s"),
						calendar::date($row['VotingsClosedDate'])) .
				"</span>";
		}
	}
	
	function displayDescription(&$row) {
		echo
			"<p>";
		
		$codes = new contentCodes();
		$codes->display(nl2br($row['Description']));
		unset($codes);
		
		echo
			"</p>";
	}
	
	function displayPictures(&$row) {
		$pictures = new pollPictures();
		
		if (isset($row['MembersOnly']) && $row['MembersOnly'] && 
			!$GLOBALS['USER']->loginok)
			$pictures->customLink = 
				"javascript:jQuery.jCore.tooltip.display(\"" .
				"<div class=\\\"tooltip error\\\"><span>" .
				htmlspecialchars(_("You need to be logged in to view this picture. " .
					"Please login or register."), ENT_QUOTES)."</span></div>\", true)";
		
		$pictures->selectedOwnerID = $row['ID'];
		$pictures->display();
		unset($pictures);
	}
	
	function displayLatestPicture(&$row) {
		$pictures = new pollPictures();
		$pictures->selectedOwnerID = $row['ID'];
		$pictures->limit = 1;
		$pictures->showPaging = false;
		$pictures->display();
		unset($pictures);
	}
	
	function displayAnswers(&$row) {
		$answers = new pollAnswers();
		$answers->selectedPollID = $row['ID'];
		$answers->hideResults = $row['HideResults'];
		$answers->display();
		unset($answers);
	}
	
	function displayAttachments(&$row) {
		$attachments = new pollAttachments();
		
		if (isset($row['MembersOnly']) && $row['MembersOnly'] && !$GLOBALS['USER']->loginok)
			$attachments->customLink = 
				"javascript:jQuery.jCore.tooltip.display(\"" .
				"<div class=\\\"tooltip error\\\"><span>" .
				htmlspecialchars(_("You need to be logged in to download this file. " .
					"Please login or register."), ENT_QUOTES)."</span></div>\", true)";
		
		$attachments->selectedOwnerID = $row['ID'];
		$attachments->display();
		unset($attachments);
	}
	
	function displayComments(&$row = null) {
		$comments = new pollComments();
		
		if ($row) {
			$comments->guestComments = $row['EnableGuestComments'];
			$comments->selectedOwnerID = $row['ID'];
		} else {
			$comments->latests = true;
			$comments->limit = $this->limit;
			$comments->format = $this->format;
		}
		
		$comments->display();
		unset($comments);
	}
	
	function displayFunctions(&$row) {
		if ($this->selectedID == $row['ID']) {
			echo
				"<a href='".url::uri('pollid')."' class='back comment'>" .
					"<span>".
					__("Back").
					"</span>" .
				"</a>";
		
		} else {
			if ($row['EnableComments'])
				echo
					"<a href='".$row['_Link']."#comments' class='comments comment'>" .
						"<span>".
						__("Comments") .
						"</span> " .
						"<span>" .
						"(".$row['Comments'].")" .
						"</span>" .
					"</a>";
		}
	}
	
	function displayVoteButton(&$row) {
		echo
			"<input type='submit' class='button submit' " .
				"name='pollvote' value='".htmlspecialchars(_("Vote"), ENT_QUOTES)."' />";
	}
	
	function displayFormated(&$row) {
		echo 
			"<div class='poll one" .
				" poll".$row['ID']."" .
				" poll-num".$row['_PollNumber'] .
				(isset($row['_CSSClass'])?
					" ".$row['_CSSClass']:
					null) .
				(isset($row['VotingsClosed']) && $row['VotingsClosed']?
					" closed":
					null) .
				($row['ID'] == $this->votedOnID?
					" votedon":
					null) .
				"'>" .
			"<form action='".url::uri("request") .
				"&amp;request=modules/poll' class='ajax-form' method='post'>";
		
		$parts = preg_split('/%([a-z0-9-_]+?)%/', $this->format, null, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach($parts as $part) {
			switch($part) {
				case 'title':
					echo
						"<h2 class='poll-title'>";
					
					$this->displayTitle($row);
					
					echo
						"</h2>";
					break;
				
				case 'details':
					echo
						"<div class='poll-details comment'>";
					
					$this->displayDetails($row);
					
					echo
						"</div>";
					break;
				
				case 'preview':
					if ($row['Pictures'])
						$this->displayLatestPicture($row);
					break;
				
				case 'pictures':
					if ($row['Pictures'])
						$this->displayPictures($row);
					break;
				
				case 'description':
					if ($row['Description']) {
						echo
							"<div class='poll-description'>";
						
						$this->displayDescription($row);
						
						echo
							"</div>";
					}
					break;
				
				case 'attachments':
					if ($row['Attachments'])
						$this->displayAttachments($row);
					break;
				
				case 'answers':
					$this->displayAnswers($row);
					break;
				
				case 'buttons':
					if (!isset($row['VotingsClosed']) || !$row['VotingsClosed']) {
						echo
							"<div class='poll-vote-button'>";
						
						$this->displayVoteButton($row);
						
						echo
							"</div>";
					}
					break;
				
				case 'comments':
					if ($row['EnableComments']) {
						echo
							"<div class='poll-links'>";
						
						$this->displayFunctions($row);
						
						echo
							"<div class='clear-both'></div>" .
							"</div>";
					}
					break;
				
				case 'link':
					echo $row['_Link'];
					break;
				
				default:
					echo $part;
					break;
			}
		}
		
		echo
				"<div class='spacer bottom'></div>" .
				"<div class='separator bottom'></div>";
		
		echo
			"</form>" .
			"</div>";
	}
	
	function displayOne(&$row) {
		echo 
			"<div class='poll one" .
				" poll".$row['ID']."" .
				" poll-num".$row['_PollNumber'] .
				(isset($row['_CSSClass'])?
					" ".$row['_CSSClass']:
					null) .
				(isset($row['VotingsClosed']) && $row['VotingsClosed']?
					" closed":
					null) .
				($row['ID'] == $this->votedOnID?
					" votedon":
					null) .
				"'>" .
			"<form action='".url::uri("request") .
				"&amp;request=modules/poll' class='ajax-form' method='post'>";
		
		echo
				"<h2 class='poll-title'>";
		
		$this->displayTitle($row);
		
		echo
				"</h2>" .
				"<div class='poll-details comment'>";
				
		$this->displayDetails($row);
			
		echo
				"</div>";
				
		if ($row['Pictures'])
			$this->displayLatestPicture($row);
		
		if ($row['Description']) {
			echo
				"<div class='poll-description'>";
			
			$this->displayDescription($row);
			
			echo
				"</div>";
		}
		
		$this->displayAnswers($row);
		
		if (!isset($row['VotingsClosed']) || !$row['VotingsClosed']) {
			echo
				"<div class='poll-vote-button'>";
				
			$this->displayVoteButton($row);
			
			echo
				"</div>";
		}
		
		if ($row['Attachments'])
			$this->displayAttachments($row);
		
		if ($row['EnableComments']) {
			echo
				"<div class='poll-links'>";
		
			$this->displayFunctions($row);
				
			echo
				"<div class='clear-both'></div>" .
				"</div>";
		}
		
		echo
			"<div class='spacer bottom'></div>" .
			"<div class='separator bottom'></div>";
		
		echo
			"</form>" .
			"</div>";
	}
	
	function displaySelected(&$row) {
		if (!$this->checkAccess($row)) {
			$this->displayLogin();
			return false;
		}
		
		echo 
			"<div class='poll selected" .
				" poll".$row['ID']."" .
				" poll-num".$row['_PollNumber'] .
				(isset($row['_CSSClass'])?
					" ".$row['_CSSClass']:
					null) .
				(isset($row['VotingsClosed']) && $row['VotingsClosed']?
					" closed":
					null) .
				"'>" .
			"<form action='".url::uri("request") .
				"&amp;request=modules/poll' class='ajax-form' method='post'>";
		
		echo
				"<h2 class='poll-title'>";
		
		$this->displayTitle($row);
		
		echo
				"</h2>";
	
		echo
				"<div class='poll-details comment'>";
		
		$this->displayDetails($row);
		
		echo
				"</div>";
	
		if ($row['Pictures'])
			$this->displayPictures($row);
			
		if ($row['Description']) {
			echo
				"<div class='poll-description'>";
			
			$this->displayDescription($row);
			
			echo
				"</div>";
		}
		
		$this->displayAnswers($row);
		
		if (!isset($row['VotingsClosed']) || !$row['VotingsClosed']) {
			echo
				"<div class='poll-vote-button'>";
			
			$this->displayVoteButton($row);
			
			echo
				"</div>";
		}
		
		if ($row['Attachments'])
			$this->displayAttachments($row);
		
		echo
				"<div class='poll-links'>";
	
		$this->displayFunctions($row);
			
		echo
				"<div class='clear-both'></div>" .
				"</div>";
			
		echo
			"<div class='spacer bottom'></div>" .
			"<div class='separator bottom'></div>";
			
		echo 
			"</form>" .
			"</div>"; //.poll
			
		if ($row['EnableComments'])
			$this->displayComments($row);
		
		return true;
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		if (preg_match('/(^|\/)rand($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)rand($|\/)/', '\2', $this->arguments);
			$this->randomize = true;
		}
		
		if (preg_match('/(^|\/)latest($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)latest($|\/)/', '\2', $this->arguments);
			$this->ignorePaging = true;
			$this->showPaging = false;
			$this->limit = 1;
		}
		
		if (preg_match('/(^|\/)format\/(.*?)($|[^<]\/[^>])/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)format\/.*?($|[^<]\/[^>])/', '\2', $this->arguments);
			$this->format = trim($matches[2]);
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
		
		if (preg_match('/(^|\/)comments($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)comments($|\/)/', '\2', $this->arguments);
			$this->ignorePaging = true;
			$this->showPaging = false;
			
			$this->displayComments();
			return true;
		}
		
		$this->selectedID = null;
		
		if (!$this->arguments)
			return false;
			
		$poll = sql::fetch(sql::run(
			" SELECT `ID` FROM `{polls}` " .
			" WHERE `Deactivated` = 0" .
			" AND `Path` LIKE '".sql::escape($this->arguments)."'" .
			" LIMIT 1"));
		
		if (!$poll)
			return true;
		
		$this->selectedID = $poll['ID'];
	}
	
	function display() {
		if ($this->displayArguments())
			return true;
		
		if (!$this->limit && $this->owner['Limit'])
			$this->limit = $this->owner['Limit'];
			
		$this->verify();
			
		$paging = new paging($this->limit);
		
		if ($this->ajaxPaging) {
			$paging->ajax = true;
			$paging->otherArgs = "&amp;request=modules/poll";
		}
		
		$limitarg = strtolower(get_class($this)).'limit';
		$paging->track($limitarg);
		
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
			
		if (!$this->ajaxRequest)
			echo 
				"<div class='polls'>";
			
		$i = 1;
		$total = sql::rows($rows);
		$link = poll::getURL();
		
		while($row = sql::fetch($rows)) {
			$row['_PollNumber'] = $i;
			$row['_Link'] = $link."&amp;pollid=".$row['ID'] .
				(url::arg($limitarg)?
					'&amp;'.url::arg($limitarg):
					null);
			$row['_CSSClass'] = null;
			
			if ($i == 1)
				$row['_CSSClass'] .= ' first';
			if ($i == $total)
				$row['_CSSClass'] .= ' last';
			
			if ($this->format)
				$this->displayFormated($row);
			elseif ($row['ID'] == $this->selectedID)
				$this->displaySelected($row);
			else
				$this->displayOne($row);
			
			$i++;
		}
		
		if (!$this->selectedID && !$this->randomize && $this->showPaging)
			$paging->display();
		
		if (!$this->ajaxRequest)
			echo 
				"</div>";
			
		return $total;
	}
}

modules::register(
	'poll', 
	_('Polls'),
	_('Quickly gather information on different subjects'));
	
?>