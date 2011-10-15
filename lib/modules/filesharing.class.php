<?php

/***************************************************************************
 * 
 *  Name: File Sharing Module
 *  URI: http://jcore.net
 *  Description: Implements a directory like structure to share files. Released under the GPL, LGPL, and MPL Licenses.
 *  Author: Istvan Petres
 *  Version: 1.0
 *  Tags: file sharing module, gpl, lgpl, mpl
 * 
 ****************************************************************************/

class fileSharingRating extends starRating {
	var $sqlTable = 'filesharingratings';
	var $sqlRow = 'FileSharingID';
	var $sqlOwnerTable = 'filesharings';
	var $adminPath = 'admin/modules/filesharing/filesharingrating';
	
	function __construct() {
		languages::load('filesharing');
		
		parent::__construct();
		
		$this->selectedOwner = _('Folder');
		$this->uriRequest = "modules/filesharing/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('filesharing');
	}
	
	function ajaxRequest() {
		if (!fileSharing::checkAccess((int)$this->selectedOwnerID)) {
			$folder = new fileSharing();
			$folder->displayLogin();
			unset($folder);
			return true;
		}
		
		return parent::ajaxRequest();
	}
}

class fileSharingAttachments extends attachments {
	var $search;
	var $sqlTable = 'filesharingattachments';
	var $sqlRow = 'FileSharingID';
	var $sqlOwnerTable = 'filesharings';
	var $adminPath = 'admin/modules/filesharing/filesharingattachments';
	
	function __construct() {
		languages::load('filesharing');
		
		parent::__construct();
		
		if (isset($_GET['searchin']) && isset($_GET['search']) && 
			$_GET['searchin'] == 'modules/filesharing')
			$this->search = trim(strip_tags((string)$_GET['search']));
			
		if (JCORE_VERSION >= '0.5') {
			$this->rootPath = $this->rootPath.'filesharing/';
			$this->rootURL = $this->rootURL.'filesharing/';
		}
		
		$this->selectedOwner = _('Folder');
		$this->uriRequest = "modules/filesharing/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('filesharing');
	}
	
	function SQL() {
		if (!$this->search)
			return parent::SQL();
		
		$folders = null;
		$ignorefolders = null;
		
		if (!$GLOBALS['USER']->loginok) {
			$row = sql::fetch(sql::run(
				" SELECT GROUP_CONCAT(`ID` SEPARATOR ',') AS `FolderIDs`" .
				" FROM `{filesharings}`" .
				" WHERE `Deactivated` = 0" .
				" AND `MembersOnly` = 1 " .
				" AND `ShowToGuests` = 0" .
				" LIMIT 1"));
					
			if ($row['FolderIDs'])
				$ignorefolders = explode(',', $row['FolderIDs']);
		}
		
		$row = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(`ID` SEPARATOR ',') AS `FolderIDs`" .
			" FROM `{filesharings}`" .
			" WHERE `Deactivated` = 0" .
			($ignorefolders?
				" AND `ID` NOT IN (".implode(',', $ignorefolders).")":
				null) .
			(!$this->latests?
				sql::search(
					$this->search,
					array('Title', 'Description')):
				null) .
			" LIMIT 1"));
		
		if ($row['FolderIDs']) {
			foreach(explode(',', $row['FolderIDs']) as $id) {
				$folders[] = $id;
				foreach(fileSharing::getTree($id) as $folder)
					$folders[] = $folder['ID'];
			}
		}
		
		return
			" SELECT * FROM `{" .$this->sqlTable."}`" .
			" WHERE ((1" .
			(!$this->latests?
				sql::search(
					$this->search,
					array('Title', 'Location')):
				null) .
			" )" .
			($folders?
				" OR (`".$this->sqlRow."` IN (".implode(',', $folders)."))":
				null) .
			" )" .
			($ignorefolders?
				" AND `".$this->sqlRow."` NOT IN (".implode(',', $ignorefolders).")":
				null) .
			" ORDER BY `Downloads` DESC, `ID` DESC";
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
		
		if (!fileSharing::checkAccess((int)$row[$this->sqlRow], true)) {
			tooltip::display(
				_("You need to be logged in to download this file. " .
					"Please login or register."),
				TOOLTIP_ERROR);
			return false;
		}
		
		return attachments::download($id);
	}
	
	function ajaxRequest() {
		if (!$row = fileSharing::checkAccess((int)$this->selectedOwnerID)) {
			$folder = new fileSharing();
			$folder->displayLogin();
			unset($folder);
			return true;
		}
		
		if (isset($row['MembersOnly']) && $row['MembersOnly'] && !$GLOBALS['USER']->loginok)
			$attachments->customLink = 
				"javascript:$.jCore.tooltip.display(\"" .
				"<div class=\\\"tooltip error\\\"><span>" .
				htmlspecialchars(_("You need to be logged in to download this file. " .
					"Please login or register."), ENT_QUOTES)."</span></div>\", true)";
		
		return parent::ajaxRequest();
	}
}

class fileSharingComments extends comments {
	var $sqlTable = 'filesharingcomments';
	var $sqlRow = 'FileSharingID';
	var $sqlOwnerTable = 'filesharings';
	var $adminPath = 'admin/modules/filesharing/filesharingcomments';
	
	function __construct() {
		languages::load('filesharing');
		
		parent::__construct();
		
		$this->selectedOwner = _('Folder');
		$this->uriRequest = "modules/filesharing/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('filesharing');
	}
	
	static function getCommentURL($comment = null) {
		if ($comment)
			return fileSharing::getURL($comment['FileSharingID']).
				"&filesharingid=".$comment['FileSharingID'];
		
		if ((bool)$GLOBALS['ADMIN'])
			return fileSharing::getURL(admin::getPathID()).
				"&filesharingid=".admin::getPathID();
		
		return 
			parent::getCommentURL();
	}
	
	function ajaxRequest() {
		if (!fileSharing::checkAccess((int)$this->selectedOwnerID)) {
			$folder = new fileSharing();
			$folder->displayLogin();
			unset($folder);
			return true;
		}
		
		return parent::ajaxRequest();
	}
}

class fileSharingIcons extends pictures {
	var $previewPicture = false;
	var $sqlTable = 'filesharingicons';
	var $sqlRow = 'FileSharingID';
	var $sqlOwnerTable = 'filesharings';
	var $sqlOwnerCountField = 'Icons';
	var $adminPath = 'admin/modules/filesharing/filesharingicons';
	
	function __construct() {
		languages::load('filesharing');
		
		parent::__construct();
		
		$this->rootPath = $this->rootPath.'icons/';
		$this->rootURL = $this->rootURL.'icons/';
		
		$this->selectedOwner = _('Folder');
		$this->uriRequest = "modules/filesharing/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('filesharing');
	}
}

class fileSharing extends modules {
	static $uriVariables = 'filesharingid, filesharinglimit, filesharingattachmentslimit, filesharingrating, rate, ajax, request';
	var $searchable = true;
	var $format = null;
	var $limit = 0;
	var $limitFolders = 0;
	var $selectedID;
	var $search = null;
	var $ignorePaging = false;
	var $showPaging = true;
	var $latests = false;
	var $attachmentsPath;
	var $ajaxPaging = AJAX_PAGING;
	var $ajaxRequest = null;
	var $adminPath = 'admin/modules/filesharing';
	
	function __construct() {
		languages::load('filesharing');
		
		if (isset($_GET['filesharingid']))
			$this->selectedID = (int)$_GET['filesharingid'];
		
		if (isset($_GET['searchin']) && isset($_GET['search']) && 
			$_GET['searchin'] == 'modules/filesharing')
			$this->search = trim(strip_tags((string)$_GET['search']));
			
		$this->attachmentsPath = SITE_PATH.'sitefiles/file/filesharing/';
	}
	
	function __destruct() {
		languages::unload('filesharing');
	}
	
	function SQL() {
		return
			" SELECT * FROM `{filesharings}`" .
			" WHERE `Deactivated` = 0" .
			(!$GLOBALS['USER']->loginok?
				" AND (`MembersOnly` = 0 " .
				"	OR `ShowToGuests` = 1)":
				null) .
			($this->search?
				sql::search(
					$this->search,
					array('Title', 'Description')):
				((int)$this->selectedID?
					" AND `SubFolderOfID` = '".(int)$this->selectedID."'":
					" AND `SubFolderOfID` = 0")) .
			" ORDER BY `OrderID`, `TimeStamp` DESC, `ID`";
	}
	
	function installSQL() {
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{filesharings}` (" .
			" `ID` smallint(5) unsigned NOT NULL auto_increment," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `Description` mediumtext NULL," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Path` varchar(255) NOT NULL default ''," .
			" `URL` varchar(255) NOT NULL default ''," .
			" `SubFolderOfID` smallint(5) unsigned NOT NULL default '0'," .
			" `Comments` smallint(5) unsigned NOT NULL default '0'," .
			" `Attachments` smallint(5) unsigned NOT NULL default '0'," .
			" `Icons` SMALLINT UNSIGNED NOT NULL DEFAULT '0'," .
			" `Deactivated` tinyint(1) unsigned NOT NULL default '0'," .
			" `EnableComments` tinyint(1) unsigned NOT NULL default '0'," .
			" `EnableGuestComments` tinyint(1) unsigned NOT NULL default '0'," .
			" `Rating` tinyint(1) unsigned NOT NULL default '0'," .
			" `EnableRating` tinyint(1) unsigned NOT NULL default '0'," .
			" `EnableGuestRating` tinyint(1) unsigned NOT NULL default '0'," .
			" `MembersOnly` tinyint(1) unsigned NOT NULL default '0'," .
			" `ShowToGuests` tinyint(1) unsigned NOT NULL default '0'," .
			" `DisplayIcons` tinyint(1) unsigned NOT NULL default '0'," .
			" `Limit` tinyint(3) unsigned NOT NULL default '0'," .
			" `UserID` mediumint(8) unsigned NOT NULL default '1'," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `Path` (`Path`)," .
			" KEY `UserID` (`UserID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `SubFolderOfID` (`SubFolderOfID`)," .
			" KEY `Deactivated` (`Deactivated`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `MembersOnly` (`MembersOnly`)," .
			" KEY `ShowToGuests` (`ShowToGuests`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
		
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{filesharingicons}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" `Location` varchar(255) NOT NULL default ''," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `URL` varchar(255) NOT NULL default ''," .
			" `FileSharingID` smallint(5) unsigned NOT NULL default '1'," .
			" `Views` int(10) unsigned NOT NULL default '0'," .
			" `Thumbnail` tinyint(1) unsigned NOT NULL default '0'," .
			" PRIMARY KEY (`ID`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `FileSharingID` (`FileSharingID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
		
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{filesharingcomments}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `FileSharingID` smallint(5) unsigned NOT NULL default '0'," .
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
			" KEY `FileSharingID` (`FileSharingID`)," .
			" KEY `SubCommentOfID` (`SubCommentOfID`)," .
			" KEY `UserID` (`UserID`)," .
			" KEY `UserName` (`UserName`)," .
			" KEY `Pending` (`Pending`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
		
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{filesharingcommentsratings}` (" .
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
			" CREATE TABLE IF NOT EXISTS `{filesharingattachments}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" `Location` varchar(255) NOT NULL default ''," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `HumanMimeType` varchar(255) NOT NULL default ''," .
			" `FileSize` int(10) unsigned NOT NULL default '0'," .
			" `FileSharingID` smallint(5) unsigned NOT NULL default '1'," .
			" `Downloads` int(10) unsigned NOT NULL default '0'," .
			" PRIMARY KEY (`ID`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `FileSharingID` (`FileSharingID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
		
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{filesharingratings}` (" .
			" `FileSharingID` smallint(5) unsigned NOT NULL default '0'," .
			" `UserID` mediumint(8) unsigned NOT NULL default '0'," .
			" `IP` DECIMAL(39, 0) NOT NULL default '0'," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Rating` tinyint(1) NOT NULL default '0'," .
			" KEY `Rating` (`Rating`)," .
			" KEY `FileSharingID` (`FileSharingID`)," .
			" KEY `UserID` (`UserID`)," .
			" KEY `IP` (`IP`)," .
			" KEY `TimeStamp` (`TimeStamp`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
		
		return true;
	}
	
	function installFiles() {
		$css = 
			".file-sharing-selected {\n" .
			"	margin-bottom: 15px;\n" .
			"}\n" .
			"\n" .
			".file-sharing .attachments-title {\n" .
			"	display: none;\n" .
			"}\n" .
			"\n" .
			".file-sharing .attachments {\n" .
			"	margin-bottom: 10px;\n" .
			"}\n" .
			"\n" .
			".file-sharing-title {\n" .
			"	margin: 0;\n" .
			"}\n" .
			"\n" .
			".file-sharing-details {\n" .
			"	margin: 3px 0 7px 0;\n" .
			"}\n" .
			"\n" .
			".file-sharing-rating {\n" .
			"	float: right;\n" .
			"}\n" .
			"\n" .
			".file-sharing-links a {\n" .
			"	display: inline-block;\n" .
			"	text-decoration: none;\n" .
			"	padding: 5px 0px 5px 20px;\n" .
			"	background: url(\"http://icons.jcore.net/16/link.png\") 0px 50% no-repeat;\n" .
			"	margin-right: 10px;\n" .
			"}\n" .
			"\n" .
			".file-sharing-links .back {\n" .
			"	background-image: url(\"http://icons.jcore.net/16/doc_page_previous.png\");\n" .
			"}\n" .
			"\n" .
			".file-sharing-links .files {\n" .
			"	background-image: url(\"http://icons.jcore.net/16/drawer.png\");\n" .
			"}\n" .
			"\n" .
			".file-sharing-links .comments {\n" .
			"	background-image: url(\"http://icons.jcore.net/16/comment.png\");\n" .
			"}\n" .
			"\n" .
			".file-sharing-folder {\n" .
			"	padding: 5px 10px 5px 5px;\n" .
			"	margin: 1px 0px 5px 0px;\n" .
			"}\n" .
			"\n" .
			".file-sharing-folder .file-sharing-title,\n" .
			".file-sharing-folder .file-sharing-details,\n" .
			".file-sharing-folder .file-sharing-description,\n" .
			".file-sharing-folder .file-sharing-links\n" .
			"{\n" .
			"	margin-left: 60px;\n" .
			"}\n" .
			"\n" .
			".file-sharing-folder .picture-title,\n" .
			".file-sharing-folder .picture-details\n" .
			"{\n" .
			"	display: none;\n" .
			"}\n" .
			"\n" .
			".file-sharing-folder .picture {\n" .
			"	width: auto;\n" .
			"	height: auto;\n" .
			"	margin: 0;\n" .
			"}\n" .
			"\n" .
			".file-sharing-folder .picture img {\n" .
			"	width: 48px;\n" .
			"	height: auto;\n" .
			"}\n" .
			"\n" .
			".file-sharing-folder-icon {\n" .
			"	display: block;\n" .
			"	float: left;\n" .
			"	width: 48px;\n" .
			"	height: 48px;\n" .
			"	background: url(\"http://icons.jcore.net/48/folder-files.png\");\n" .
			"}\n" .
			"\n" .
			".file-sharing-folder-icon.subfolders {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/folder-subfolders-files.png\");\n" .
			"}\n" .
			"\n" .
			".file-sharing-folder-icon.icon {\n" .
			"	background-image: none;\n" .
			"}\n" .
			"\n" .
			".file-sharing-selected .file-sharing-folder-icon {\n" .
			"	width: auto;\n" .
			"	height: auto;\n" .
			"	margin-right: 15px;\n" .
			"}\n" .
			"\n" .
			".as-modules-filesharing a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/document-open.png\");\n" .
			"}\n";
		
		return 
			files::save(SITE_PATH.'template/modules/css/filesharing.css', $css);
	}
	
	function uninstallSQL() {
		sql::run(
			" DROP TABLE IF EXISTS `{filesharings}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{filesharingicons}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{filesharingcomments}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{filesharingcommentsratings}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{filesharingattachments}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{filesharingratings}`;");
		
		return true;
	}
	
	function uninstallFiles() {
		return 
			files::delete(SITE_PATH.'template/modules/css/filesharing.css');
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		if (!parent::installed($this))
			return 0;
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{filesharings}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				_('New Folder'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=' .
			(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));
		favoriteLinks::add(
			__('Settings'), 
			'?path=admin/site/settings');
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 250px;');
		
		$form->add(
			_('Sub Folder of'),
			'SubFolderOfID',
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
		$form->setStyle('width: ' .
			(JCORE_VERSION >= '0.7'?
				'90%':
				'350px') .
			'; height: 200px;');
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
				_("(display icons when folder selected)"));
		}
		
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
			$form->setTooltipText(_("e.g. http://domain.com"));
		}
		
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
		
		if ($reorder) {
			if (!security::checkToken())
				return false;
			
			foreach((array)$orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{filesharings}` " .
					" SET `OrderID` = '".(int)$ovalue."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".(int)$oid."'" .
					($this->userPermissionIDs?
						" AND `ID` IN (".$this->userPermissionIDs.")":
						null) .
					($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
						" AND `UserID` = '".(int)$GLOBALS['USER']->data['ID']."'":
						null));
			}
			
			tooltip::display(
				_("Folders have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!security::checkToken())
				return false;
			
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				_("Folder has been successfully deleted."),
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
		
		if ($edit && $form->get('SubFolderOfID')) {
			foreach(fileSharing::getBackTraceTree($form->get('SubFolderOfID')) as $folder) {
				if ($folder['ID'] == $id) {
					tooltip::display(
						_("Folder cannot be subfolder of itself!"),
						TOOLTIP_ERROR);
					
					return false;
				}
			}
		}
			
		if (!$form->get('Path')) {
			$path = '';
			
			if ($form->get('SubFolderOfID')) {
				$subfolderof = sql::fetch(sql::run(
					" SELECT `Path` FROM `{filesharings}`" .
					" WHERE `ID` = ".(int)$form->get('SubFolderOfID')));
				
				$path .= $subfolderof['Path'].'/'; 
			}
			
			$path .= url::genPathFromString($form->get('Title'));
			
			$form->set('Path', $path);
		}
				
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray()))
				return false;
			
			tooltip::display(
				_("Folder has been successfully updated.")." " .
				(modules::getOwnerURL('fileSharing')?
					"<a href='".fileSharing::getURL($id).
						"&amp;filesharingid=".$id."' target='_blank'>" .
						_("View Folder") .
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
		
		if (!$newid = $this->add($form->getPostArray()))
			return false;
				
		tooltip::display(
			_("Folder has been successfully created.")." " .
			(modules::getOwnerURL('fileSharing')?
				"<a href='".fileSharing::getURL().
					"&amp;filesharingid=".$newid."' target='_blank'>" .
					_("View Folder") .
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
				__("Comments")."</span></th>" .
			"<th><span class='nowrap'>".
				_("Files")."</span></th>";
		
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
					(!$row['SubFolderOfID']?
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
				"<a class='admin-link comments' " .
					"title='".htmlspecialchars(__("Comments"), ENT_QUOTES).
						" (".$row['Comments'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/filesharingcomments'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $row['Comments'])
			counter::display($row['Comments']);
		
		echo
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link files' " .
					"title='".htmlspecialchars(_("Files"), ENT_QUOTES) .
						" (".$row['Attachments'].")' " .
					"href='".url::uri('ALL') .
					"?path=".admin::path()."/".$row['ID']."/filesharingattachments'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $row['Attachments'])
			counter::display($row['Attachments']);
		
		echo
				"</a>" .
			"</td>";
		
		if (JCORE_VERSION >= '0.6') {
			echo
				"<td align='center'>" .
					"<a class='admin-link icons' " .
						"title='".htmlspecialchars(_("Icons"), ENT_QUOTES) .
							" (".$row['Icons'].")' " .
						"href='".url::uri('ALL') .
						"?path=".admin::path()."/".$row['ID']."/filesharingicons'>";
			
			if (ADMIN_ITEMS_COUNTER_ENABLED && $row['Icons'])
				counter::display($row['Icons']);
			
			echo
					"</a>" .
				"</td>";
		}
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
		
		if ($row['MembersOnly'])
			admin::displayItemData(
				_("Members Only"),
				__("Yes"));
		
		if ($row['ShowToGuests'])
			admin::displayItemData(
				_("Show to Guests"),
				__("Yes"));
		
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
	
	function displayAdminList(&$rows, $rowpair = null) {
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
				"<form action='".url::uri('edit, delete')."' method='post'>" .
					"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";
		}
				
		echo "<table cellpadding='0' cellspacing='0' class='list'>";
		
		if (!isset($rowpair)) {
			echo
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
		}
		
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
			
			if (!$this->userPermissionIDs) {
				$subrows = sql::run(
					" SELECT * FROM `{filesharings}`" .
					" WHERE `SubFolderOfID` = '".$row['ID']."'" .
					" ORDER BY `OrderID`, `TimeStamp` DESC, `ID`");
				
				if (sql::rows($subrows))
					$this->displayAdminList($subrows, $i%2);
			}
			
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
		
			if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
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
			_('File Sharing Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
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
				
		$form = new form(
				($edit?
					_("Edit Folder"):
					_("New Folder")),
				'neweditfolder');
		
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
		
		if ($id) {
			$selected = sql::fetch(sql::run(
				" SELECT `ID`, `Title` FROM `{filesharings}`" .
				" WHERE `ID` = '".$id."'" .
				($this->userPermissionIDs?
					" AND `ID` IN (".$this->userPermissionIDs.")":
					null) .
				($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
					" AND `UserID` = '".(int)$GLOBALS['USER']->data['ID']."'":
					null)));
			
			if ($delete && empty($_POST['delete']))
				url::displayConfirmation(
					'<b>'.__('Delete').'?!</b> "'.$selected['Title'].'"');
		}
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			((!$edit && !$delete) || $selected))
			$verifyok = $this->verifyAdmin($form);
		
		foreach(fileSharing::getTree() as $row) {
			$form->addValue('SubFolderOfID',
				$row['ID'], 
				($row['SubItemOfID']?
					str_replace(' ', '&nbsp;', 
						str_pad('', $row['PathDeepnes']*4, ' ')).
					"|- ":
					null) .
				$row['Title']);
		}
		
		$rows = sql::run(
			" SELECT * FROM `{filesharings}`" .
			" WHERE 1" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
				" AND `UserID` = '".(int)$GLOBALS['USER']->data['ID']."'":
				null) .
			(!$this->userPermissionIDs && ~$this->userPermissionType & USER_PERMISSION_TYPE_OWN?
				" AND `SubFolderOfID` = 0":
				null) .
			" ORDER BY `OrderID`, `TimeStamp` DESC, `ID`");
		
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				_("No folders found."),
				TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && $selected)))
		{
			if ($edit && $selected && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{filesharings}`" .
					" WHERE `ID` = '".$id."'"));
				
				$form->setValues($selected);
				
				$user = $GLOBALS['USER']->get($selected['UserID']);
				$form->setValue('Owner', $user['UserName']);
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
				" SELECT `OrderID` FROM `{filesharings}` " .
				" WHERE `SubFolderOfID` = '".(int)$values['SubFolderOfID']."'" .
				" ORDER BY `OrderID` DESC"));
			
			$values['OrderID'] = (int)$row['OrderID']+1;
			
		} else {
			sql::run(
				" UPDATE `{filesharings}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `SubFolderOfID` = '".(int)$values['SubFolderOfID']."'" .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		if ((int)$values['SubFolderOfID']) {
			$parentfolder = sql::fetch(sql::run(
				" SELECT * FROM `{filesharings}`" .
				" WHERE `ID` = '".(int)$values['SubFolderOfID']."'"));
			
			if ($parentfolder['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;
			
			if ($parentfolder['MembersOnly'] && !$values['MembersOnly'])
				$values['MembersOnly'] = true;
			
			if ($parentfolder['ShowToGuests'] && !$values['ShowToGuests'])
				$values['ShowToGuests'] = true;
		}
		
		$newid = sql::run(
			" INSERT INTO `{filesharings}` SET ".
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
					sql::escape($values['URL'])."',":
				null) .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `SubFolderOfID` = '".
				(int)$values['SubFolderOfID']."'," .
			" `EnableComments` = '".
				(int)$values['EnableComments']."'," .
			" `EnableGuestComments` = '".
				(int)$values['EnableGuestComments']."'," .
			" `EnableRating` = '".
				(int)$values['EnableRating']."'," .
			" `EnableGuestRating` = '".
				(int)$values['EnableGuestRating']."'," .
			" `MembersOnly` = '".
				(int)$values['MembersOnly']."'," .
			" `ShowToGuests` = '".
				(int)$values['ShowToGuests']."'," .
			(JCORE_VERSION >= '0.7'?
				" `DisplayIcons` = '".
					(int)$values['DisplayIcons']."',":
				null) .
			" `Limit` = '".
				(int)$values['Limit']."'," .
			" `UserID` = '".
				(isset($values['UserID']) && (int)$values['UserID']?
					(int)$values['UserID']:
					(int)$GLOBALS['USER']->data['ID']) .
				"'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
		
		if (!$newid) {
			tooltip::display(
				sprintf(_("Folder couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		$this->protectAttachments();
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		$folder = sql::fetch(sql::run(
			" SELECT * FROM `{filesharings}`" .
			" WHERE `ID` = '".$id."'"));
			
		if ((int)$values['SubFolderOfID'] && 
			(int)$values['SubFolderOfID'] != $folder['SubFolderOfID']) 
		{
			$parentfolder = sql::fetch(sql::run(
				" SELECT * FROM `{filesharings}`" .
				" WHERE `ID` = '".(int)$values['SubFolderOfID']."'"));
			
			if ($parentfolder['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;
			
			if ($parentfolder['MembersOnly'] && !$values['MembersOnly'])
				$values['MembersOnly'] = true;
			
			if ($parentfolder['ShowToGuests'] && !$values['ShowToGuests'])
				$values['ShowToGuests'] = true;
		}
		
		sql::run(
			" UPDATE `{filesharings}` SET ".
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
					sql::escape($values['URL'])."',":
				null) .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `SubFolderOfID` = '".
				(int)$values['SubFolderOfID']."'," .
			" `EnableComments` = '".
				(int)$values['EnableComments']."'," .
			" `EnableGuestComments` = '".
				(int)$values['EnableGuestComments']."'," .
			" `EnableRating` = '".
				(int)$values['EnableRating']."'," .
			" `EnableGuestRating` = '".
				(int)$values['EnableGuestRating']."'," .
			" `MembersOnly` = '".
				(int)$values['MembersOnly']."'," .
			" `ShowToGuests` = '".
				(int)$values['ShowToGuests']."'," .
			(JCORE_VERSION >= '0.7'?
				" `DisplayIcons` = '".
					(int)$values['DisplayIcons']."',":
				null) .
			" `Limit` = '".
				(int)$values['Limit']."'," .
			(isset($values['UserID']) && (int)$values['UserID']?
				" `UserID` = '".(int)$values['UserID']."',":
				null) .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(_("Folder couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		foreach(fileSharing::getTree((int)$id) as $row) {
			$updatesql = null;
			
			if (($folder['Deactivated'] && !$values['Deactivated']) ||
				(!$folder['Deactivated'] && $values['Deactivated'])) 
			{
				if (!$row['Deactivated'] && $values['Deactivated'])
					$updatesql[] = " `Deactivated` = 1";
				if ($row['Deactivated'] && !$values['Deactivated'])
					$updatesql[] = " `Deactivated` = 0";
			}
			
			if (($folder['MembersOnly'] && !$values['MembersOnly']) ||
				(!$folder['MembersOnly'] && $values['MembersOnly'])) 
			{
				if (!$row['MembersOnly'] && $values['MembersOnly'])
					$updatesql[] = " `MembersOnly` = 1";
				if ($row['MembersOnly'] && !$values['MembersOnly'])
					$updatesql[] = " `MembersOnly` = 0";
			}
			
			if (($folder['ShowToGuests'] && !$values['ShowToGuests']) ||
				(!$folder['ShowToGuests'] && $values['ShowToGuests'])) 
			{
				if (!$row['ShowToGuests'] && $values['ShowToGuests'])
					$updatesql[] = " `ShowToGuests` = 1";
				if ($row['ShowToGuests'] && !$values['ShowToGuests'])
					$updatesql[] = " `ShowToGuests` = 0";
			}
			
			if ($updatesql)
				sql::run(
					" UPDATE `{filesharings}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
		}
		
		foreach(fileSharing::getBackTraceTree((int)$id) as $row) {
			$updatesql = null;
			
			if ($row['Deactivated'] && !$values['Deactivated'])
				$updatesql[] = " `Deactivated` = 0";
			if ($row['MembersOnly'] && !$values['MembersOnly'])
				$updatesql[] = " `MembersOnly` = 0";
			
			if ($updatesql)
				sql::run(
					" UPDATE `{filesharings}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
		}

		$this->protectAttachments();
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		$filesharingattachments = new fileSharingAttachments();
		$filesharingcomments = new fileSharingComments();
		$folderids = array($id);
		
		foreach(fileSharing::getTree((int)$id) as $row)
			$folderids[] = $row['ID'];
		
		
		foreach($folderids as $folderid) {
			$rows = sql::run(
				" SELECT * FROM `{filesharingattachments}` " .
				" WHERE `FileSharingID` = '".$folderid."'");
			
			while($row = sql::fetch($rows))
				$filesharingattachments->delete($row['ID']);
			
			$rows = sql::run(
				" SELECT * FROM `{filesharingcomments}` " .
				" WHERE `FileSharingID` = '".$folderid."'");
			
			while($row = sql::fetch($rows))
				$filesharingcomments->delete($row['ID']);
			
			sql::run(
				" DELETE FROM `{filesharingratings}` " .
				" WHERE `FileSharingID` = '".$folderid."'");
			
			sql::run(
				" DELETE FROM `{filesharings}` " .
				" WHERE `ID` = '".(int)$id."'");
		}
		
		unset($filesharingcomments);
		unset($filesharingattachments);
		
		if (JCORE_VERSION >= '0.6') {
			$icons = new fileSharingIcons();
			
			$rows = sql::run(
				" SELECT * FROM `{filesharingicons}`" .
				" WHERE `FileSharingID` = '".$id."'");
			
			while($row = sql::fetch($rows))
				$icons->delete($row['ID']);
			
			unset($icons);
		}
		
		$this->protectAttachments();
		
		return true;
	}
	
	function protectAttachments() {
		if (!$this->attachmentsPath)
			return false;
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows` FROM `{filesharings}` " .
			" WHERE `MembersOnly` = 1" .
			" LIMIT 1"));
			
		if ($row['Rows'])
			return dirs::protect($this->attachmentsPath);
		
		return dirs::unprotect($this->attachmentsPath);
	}
	
	static function getTree($folderid = 0, $firstcall = true,
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0)) 
	{
		$rows = sql::run(
			" SELECT *, `SubFolderOfID` AS `SubItemOfID` " .
			" FROM `{filesharings}` " .
			($folderid?
				" WHERE `SubFolderOfID` = '".$folderid."'":
				" WHERE `SubFolderOfID` = 0") .
			" ORDER BY `OrderID`, `TimeStamp` DESC, `ID`");
		
		while($row = sql::fetch($rows)) {
			$row['PathDeepnes'] = $tree['PathDeepnes'];
			$tree['Tree'][] = $row;
			
			$tree['PathDeepnes']++;
			fileSharing::getTree($row['ID'], false, $tree);
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
			" SELECT *, `SubFolderOfID` AS `SubItemOfID` " .
			" FROM `{filesharings}` " .
			" WHERE `ID` = '".(int)$id."'"));
		
		if (!$row)
			return array();
		
		if ($row['SubItemOfID'])	
			fileSharing::getBackTraceTree($row['SubItemOfID'], false, $tree);
		
		$row['PathDeepnes'] = $tree['PathDeepnes'];
		$tree['Tree'][] = $row;
		$tree['PathDeepnes']++;
		
		if ($firstcall)
			return $tree['Tree'];
	}
	
	// ************************************************   Client Part
	static function getURL($id = 0) {
		$url = modules::getOwnerURL('fileSharing', $id);
		
		if (!$url)
			return url::site() .
				url::uri(fileSharing::$uriVariables);
		
		return $url;	
	}
	
	static function checkAccess($row, $full = false) {
		if ($GLOBALS['USER']->loginok)
			return true;
		
		if ($row && !is_array($row))
			$row = sql::fetch(sql::run(
				" SELECT `MembersOnly`, `ShowToGuests`" .
				" FROM `{filesharings}`" .
				" WHERE `ID` = '".(int)$row."'"));
		
		if (!$row)
			return true;
			
		if ($row['MembersOnly'] && ($full || !$row['ShowToGuests']))
			return false;
		
		return $row;
	}
	
	function ajaxRequest() {
		$users = null;
		
		if (isset($_GET['users']))
			$users = (int)$_GET['users'];
		
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
			
			$GLOBALS['USER']->displayQuickList('#neweditfolderform #entryOwner');
			return true;
		}
		
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
	
	function displayIcon(&$row) {
		if (JCORE_VERSION >= '0.6' && $row['Icons']) {
			echo
				"<div class='file-sharing-folder-icon icon'>";
		
			$icons = new fileSharingIcons();
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
				"class='file-sharing-folder-icon" .
				($row['_SubFolders']?
					" subfolders":
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
			"<a href='".url::uri(fileSharing::$uriVariables)."'>" .
				_("Files") .
			"</a>";
			
		foreach(fileSharing::getBackTraceTree($row['ID']) as $folder) {
			$href = url::uri(fileSharing::$uriVariables).
				"&amp;filesharingid=".$folder['ID'];
			
			echo 
				"<span class='path-separator'> / </span>" .
				"<a href='".$href."'>".
					$folder['Title'] .
				"</a>";
		}
	}
	
	function displayDetails(&$row) {
		$user = $GLOBALS['USER']->get($row['UserID']);
			
		echo
			"<span class='details-date'>" .
			calendar::datetime($row['TimeStamp']) .
			" </span>";
					
		$GLOBALS['USER']->displayUserName($user, __('by %s'));
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
	
	function displayAttachments(&$row = null) {
		$attachments = new fileSharingAttachments();
		
		if ($row) {
			$attachments->selectedOwnerID = $row['ID'];
			$attachments->limit = $row['Limit'];
		} else {
			$attachments->latests = true;
			$attachments->format = $this->format;
		}
		
		$attachments->ignorePaging = $this->ignorePaging;
		$attachments->showPaging = $this->showPaging;
		$attachments->ajaxPaging = $this->ajaxPaging;
		
		if ($this->limit)
			$attachments->limit = $this->limit;
	
		if (isset($row['MembersOnly']) && $row['MembersOnly'] && !$GLOBALS['USER']->loginok)
			$attachments->customLink = 
				"javascript:$.jCore.tooltip.display(\"" .
				"<div class=\\\"tooltip error\\\"><span>" .
				htmlspecialchars(_("You need to be logged in to download this file. " .
					"Please login or register."), ENT_QUOTES)."</span></div>\", true)";
		
		$attachments->display();
		unset($attachments);
	}
	
	function displayComments(&$row = null) {
		$comments = new fileSharingComments();
		
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
	
	function displayRating(&$row) {
		$rating = new fileSharingRating();
		$rating->guestRating = $row['EnableGuestRating'];
		$rating->selectedOwnerID = $row['ID'];
		$rating->display();
		unset($rating);	
	}
	
	function displayFunctions(&$row) {
		echo
			"<a href='" .
				(JCORE_VERSION >= '0.6' && $row['URL']?
					url::generateLink($row['URL']):
					$row['_Link']) .
				"' class='files comment'>" .
				"<span>".
				($row['_SubFolders']?
					_("Files / Folders"):
					_("Files")).
				"</span> " .
				"<span>" .
				"(".($row['Attachments']+$row['_SubFolders']).")" .
				"</span>" .
			"</a>";
			
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
	
	function displayOne(&$row) {
		$row['_Link'] = url::uri(fileSharing::$uriVariables).
			"&amp;filesharingid=".$row['ID'];
		
		$row['_SubFolders'] = sql::count(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{filesharings}`" .
			" WHERE `Deactivated` = 0" .
			" AND `SubFolderOfID` = '".(int)$row['ID']."'");
		
		echo 
			"<div" .
				(JCORE_VERSION < '0.6'?
					" id='file-sharing".$row['ID']."'":
					null) .
				" class='file-sharing-folder" .
				($row['SubFolderOfID']?
					" file-sharing-sub-folder":
					null) .
				($row['_SubFolders']?
					" file-sharing-has-sub-folders":
					null) .
				" file-sharing".$row['ID'] .
				" rounded-corners'>";
		
		$this->displayIcon($row);
		
		echo
				"<h3 class='file-sharing-title'>";
		
		$this->displayTitle($row);
		
		echo
				"</h3>" .
				"<div class='file-sharing-details comment'>";
		
		$this->displayDetails($row);
		
		echo
				"</div>";
		
		if ($row['Description']) {
			echo
				"<div class='file-sharing-description'>";
			
			$this->displayDescription($row);
			
			echo
				"</div>";
		}
		
		if ($row['EnableRating']) {
			echo
				"<div class='file-sharing-rating'>";
			
			$this->displayRating($row);
		
			echo
				"</div>";
		}
		
		echo
				"<div class='file-sharing-links'>";
		
		$this->displayFunctions($row);
			
		echo
				"</div>" .
				"<div class='clear-both'></div>" .
			"</div>";
	}
	
	function displaySelected(&$row = null) {
		if (!$this->checkAccess($row)) {
			$this->displayLogin();
			return false;
		}
		
		echo "<div class='file-sharing file-sharing".$row['ID']."'>" .
				"<div class='file-sharing-selected'>" .
					"<h3 class='file-sharing-title'>";
		
		$this->displaySelectedTitle($row);
		
		echo
					"</h3>";
				
		if ($row['EnableRating']) {
			echo
					"<div class='file-sharing-rating'>";
			
			$this->displayRating($row);
			
			echo
					"</div>";
		}
		
		echo
					"<div class='file-sharing-details comment'>";
		
		$this->displayDetails($row);
		
		echo
					"</div>";
		
		if (JCORE_VERSION >= '0.7' && $row['DisplayIcons'] && $row['Icons'])
			$this->displayIcon($row);
		
		if ($row['Description']) {
			echo
					"<div class='file-sharing-description'>";
			
			$this->displayDescription($row);
			
			echo
					"</div>";
		}
					
		echo
				"<div class='clear-both'></div>" .
			"</div>";
		
		$this->displayFolders();
		
		if ($row && $row['Attachments'])
			$this->displayAttachments($row);
			
		echo 
				"<div class='clear-both'></div>";
		
		if ($row['EnableComments'])
			$this->displayComments($row);
		
		echo 
			"</div>"; //file-sharing
		
		return true;
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		if (preg_match('/(^|\/)latest($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)latest($|\/)/', '\2', $this->arguments);
			$this->latests = true;
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
		
		if (!$this->arguments && $this->latests)
			return false;
		
		$folder = sql::fetch(sql::run(
			" SELECT * FROM `{filesharings}` " .
			" WHERE `Deactivated` = 0" .
			((int)$this->selectedID?
				" AND `ID` = '".(int)$this->selectedID."'":
				" AND `Path` LIKE '".sql::escape($this->arguments)."'") .
			" ORDER BY `OrderID`, `TimeStamp` DESC, `ID`" .
			" LIMIT 1"));
		
		if (!$folder)
			return true;
			
		$this->selectedID = $folder['ID'];	
		$this->displaySelected($folder);
		return true;
	}
	
	function displaySearch() {
		$attachments = new fileSharingAttachments();
		
		$attachments->limit = $this->limit;
		$attachments->search = $this->search;
		
		ob_start();
		$itemsfound = $attachments->display();
		$content = ob_get_contents();
		ob_end_clean();
		
		unset($attachments);
		
		if (!isset($this->arguments))
			url::displaySearch($this->search, $itemsfound);
		
		echo
			"<div class='file-sharing'>" .
			$content .
			"</div>";
		
		return $itemsfound;
	}
	
	function displayFolders() {
		$paging = new paging($this->limitFolders);
		
		if ($this->ajaxPaging) {
			$paging->ajax = true;
			$paging->otherArgs = "&amp;request=modules/filesharing";
		}
		
		$limitarg = strtolower(get_class($this)).'limit';
		$paging->track($limitarg);
		
		$folders = sql::run(
			$this->SQL() .
			" LIMIT ".$paging->limit);
		
		if (!sql::rows($folders))
			return false;
		
		$paging->setTotalItems(sql::count());
		
		if (!$this->ajaxRequest)
			echo
				"<div class='file-sharing-folders'>";
		
		while ($folder = sql::fetch($folders))
			$this->displayOne($folder);
		
		echo
			"<div class='clear-both'></div>";
		
		$paging->display();
		
		if (!$this->ajaxRequest)
			echo
				"</div>";
		
		return true;
	}
	
	function display() {
		if ($this->displayArguments())
			return true;
		
		if (!$this->limitFolders && $this->owner['Limit'])
			$this->limitFolders = $this->owner['Limit'];
			
		if (!$this->latests && (int)$this->selectedID) {
			$row = sql::fetch(sql::run(
				" SELECT * FROM `{filesharings}`" .
				" WHERE `Deactivated` = 0" .
				" AND `ID` = '".(int)$this->selectedID."'" .
				" LIMIT 1"));
			
			if (!$row)
				return false;
			
			return $this->displaySelected($row);
		}
		
		if (!$this->latests && $this->search)
			return $this->displaySearch();
		
		echo 
			"<div class='file-sharing'>";
		
		if ($this->latests)
			$this->displayAttachments();
		else
			$items = $this->displayFolders();
		
		echo 
			"</div>";
		
		if ($this->latests)
			return true;
		
		return $items;
	}
}

modules::register(
	'fileSharing',
	_('File Sharing'), 
	_('Share files in a directory like structure'));

?>