<?php

/***************************************************************************
 * 
 *  Name: Photo Gallery Module
 *  URI: http://jcore.net
 *  Description: Share files in a directory/gallery like strcture. Released under the GPL, LGPL, and MPL Licenses.
 *  Author: Istvan Petres
 *  Version: 0.9
 *  Tags: photo gallery module, gpl, lgpl, mpl
 * 
 ****************************************************************************/

class photoGalleryRating extends starRating {
	var $sqlTable = 'photogalleryratings';
	var $sqlRow = 'PhotoGalleryID';
	var $sqlOwnerTable = 'photogalleries';
	var $adminPath = 'admin/modules/photogallery/photogalleryrating';
	
	function __construct() {
		languages::load('photogallery');
		
		parent::__construct();
		
		$this->selectedOwner = _('Gallery');
		$this->uriRequest = "modules/photogallery/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('photogallery');
	}
	
	function ajaxRequest() {
		if (JCORE_VERSION >= '0.5' && !photoGallery::checkAccess((int)$this->selectedOwnerID)) {
			$gallery = new photoGallery();
			$gallery->displayLogin();
			unset($gallery);
			return true;
		}
		
		return parent::ajaxRequest();
	}
}

class photoGalleryPictures extends pictures {
	var $search;
	var $sqlTable = 'photogallerypictures';
	var $sqlRow = 'PhotoGalleryID';
	var $sqlOwnerTable = 'photogalleries';
	var $adminPath = 'admin/modules/photogallery/photogallerypictures';
	
	function __construct() {
		languages::load('photogallery');
		
		parent::__construct();
		
		if (isset($_GET['searchin']) && isset($_GET['search']) && 
			$_GET['searchin'] == 'modules/photogallery')
			$this->search = trim(strip_tags($_GET['search']));
			
		if (JCORE_VERSION >= '0.5') {
			$this->rootPath = $this->rootPath.'photogallery/';
			$this->rootURL = $this->rootURL.'photogallery/';
		}
		
		$this->selectedOwner = _('Gallery');
		$this->uriRequest = "modules/photogallery/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('photogallery');
	}
	
	function SQL() {
		if (!$this->search)
			return parent::SQL();
		
		$folders = null;
		$ignorefolders = null;
		
		if (JCORE_VERSION >= '0.5' && !$GLOBALS['USER']->loginok) {
			$row = sql::fetch(sql::run(
				" SELECT GROUP_CONCAT(`ID` SEPARATOR ',') AS `FolderIDs`" .
				" FROM `{photogalleries}`" .
				" WHERE `Deactivated` = 0" .
				" AND `MembersOnly` = 1 " .
				" AND `ShowToGuests` = 0" .
				" LIMIT 1"));
				
			if ($row['FolderIDs'])
				$ignorefolders = explode(',', $row['FolderIDs']);
		}
		
		$row = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(`ID` SEPARATOR ',') AS `FolderIDs`" .
			" FROM `{photogalleries}`" .
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
				foreach(photoGallery::getTree($id) as $folder)
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
			" ORDER BY `Views` DESC, `ID` DESC";
	}
	
	function setupAdminForm(&$form) {
		pictures::setupAdminForm($form);
		$form->edit(
			'NoThumbnail', 
			__('No Thumbnail'), 
			'NoThumbnail', 
			FORM_INPUT_TYPE_HIDDEN,
			false,
			0);
	}
	
	function download($id, $force = false) {
		if (!(int)$id) {
			tooltip::display(
				_("No picture selected to download!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{" .$this->sqlTable . "}`" .
			" WHERE `ID` = '".(int)$id."'" .
			" LIMIT 1"));
		
		if (!$row) {
			tooltip::display(
				_("The selected picture cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$gallery = sql::fetch(sql::run(
			" SELECT * FROM `{" .$this->sqlOwnerTable . "}`" .
			" WHERE `ID` = '".(int)$row[$this->sqlRow]."'" .
			" LIMIT 1"));
		
		if (!$GLOBALS['USER']->loginok && 
			isset($gallery['MembersOnly']) && $gallery['MembersOnly']) 
		{
			tooltip::display(
				_("You need to be logged in to view this picture. " .
					"Please login or register."),
				TOOLTIP_ERROR);
			return false;
		}
		
		return parent::download($id, $force);
	}
	
	function ajaxRequest() {
		if (JCORE_VERSION >= '0.5' && !photoGallery::checkAccess((int)$this->selectedOwnerID)) {
			$gallery = new photoGallery();
			$gallery->displayLogin();
			unset($gallery);
			return true;
		}
		
		return parent::ajaxRequest();
	}
	
	function displayGalleryPreview($gallery) {
		echo
			"<div class='".
				strtolower(preg_replace('/([A-Z])/', '-\\1', get_class($this))).
				" pictures'>";
		
		$row = array();
		$row['ID'] = "preview";
		$row['Title'] = $gallery['Title'];
		$row['Location'] = "none";
		$row['TimeStamp'] = $gallery['TimeStamp'];
		$row['URL'] = "";
		$row['Views'] = 0;
		$row['_PictureNumber'] = 'preview';
		$row['_ThumbnailLocation'] = $gallery['PreviewPicURL'];
		
		$this->displayOne($row);
		
		echo
			"<div class='clear-both'></div>";
		
		echo
			"</div>"; //pictures
	}
}

class photoGalleryPicasaPictures extends photoGalleryPictures {
	function __construct() {	
		languages::load('photogallery');
		
		parent::__construct();
	}
	
	function __destruct() {
		languages::unload('photogallery');
	}
	
	static function genAPIURL($values) {
		if (!$values || !is_array($values))
			return null;
		
		$picasauser = null;
		$picasaalbum = null;
		$picasaapiurl = null;
		
		if ($values['PicasaAlbumURL']) {
			if ($values['PicasaPhotos'] == 'featured' ||
				$values['PicasaPhotos'] == 'G')
				$values['PicasaAlbumURL'] = "";
			
			preg_match('/picasaweb\.google\.com\/(.*?)(\/|$)(.*?)(\/|$)/', 
				preg_replace('/\?.*$/', '', $values['PicasaAlbumURL']), 
				$matches);
			
			if (isset($matches[1]))
				$picasauser = $matches[1];
			
			if (isset($matches[3]))
				$picasaalbum = $matches[3];
		}
		
		if (!$picasauser && !$picasaalbum && !$values['PicasaTags'] && 
			!$values['PicasaSearch'] && !$values['PicasaPhotos'])
			return null;
		
		if ($values['PicasaPhotos'] == 'S' && !$picasauser)
			$picasauser = "default";
		
		if ($values['PicasaPhotos'] == 'featured') {
			$values['PicasaPhotos'] = "";
			$values['PicasaSearch'] = "";
			
			if (!$picasauser)
				$picasaapiurl .= "featured/";
		}
		
		if ($picasauser)
			$picasaapiurl .= "user/".$picasauser."/";
		
		if ($picasaalbum)
			$picasaapiurl .= "album/".$picasaalbum."/";
		
		if (!$picasaapiurl)
			$picasaapiurl .= "all/";
		
		$picasaapiurl = 
			"http://picasaweb.google.com/data/feed/api/" .
			$picasaapiurl."?";
		
		if ($values['PicasaTags'])
			$picasaapiurl .= "&tag=".urlencode($values['PicasaTags']);
		
		if ($values['PicasaSearch'])
			$picasaapiurl .= "&q=".urlencode($values['PicasaSearch']);
		
		if ($values['PicasaPhotos'])
			$picasaapiurl .= "&psc=".$values['PicasaPhotos'];
		
		if ($values['PicasaType']) {
			if ($values['PicasaType'] == 'faces')
				$picasaapiurl .= "&face=true";
			else
				$picasaapiurl .= "&face=false";
		}
		
		if ($values['PicasaAspectRatio'])
			$picasaapiurl .= "&imgor=".$values['PicasaAspectRatio'];
		
		if ($values['PicasaSize'])
			$picasaapiurl .= "&imgsz=".$values['PicasaSize'];
		
		if ($values['PicasaLicense'])
			$picasaapiurl .= "&imglic=".$values['PicasaLicense'];
		
		if ($values['PicasaTime'])
			$picasaapiurl .= "&time=".$values['PicasaTime'];
		
		if ($values['PicasaOrderBy'])
			$picasaapiurl .= "&orderby=".$values['PicasaOrderBy'];
		
		return $picasaapiurl;
	}
	
	static function genPreviewURL($apiurl) {
		if (!$apiurl)
			return null;
		
		$values = photoGalleryPicasaPictures::parseAPIURL($apiurl);
		
		if (!$values['PicasaAlbumURL']) {
			$values['PicasaAlbumURL'] = "http://picasaweb.google.com";
			
			if ($values['PicasaPhotos'] == 'featured') {
				$values['PicasaPhotos'] = "";
				$values['PicasaAlbumURL'] .= "/lh/featured";
				
			} else {
				$values['PicasaAlbumURL'] .= "/lh/view";
			}
		}
		
		$values['PicasaAlbumURL'] .= "?";
		
		if ($values['PicasaTags'])
			$values['PicasaAlbumURL'] .= "&amp;tags=".
				urlencode($values['PicasaTags']);
			
		if ($values['PicasaSearch'])
			$values['PicasaAlbumURL'] .= "&amp;q=".
				$values['PicasaSearch'];
			
		if ($values['PicasaPhotos'])
			$values['PicasaAlbumURL'] .= "&amp;psc=".
				$values['PicasaPhotos'];
			
		if ($values['PicasaType'] == 'faces')
			$values['PicasaAlbumURL'] .= "&amp;face=true";
		elseif ($values['PicasaType'] == 'no-faces')
			$values['PicasaAlbumURL'] .= "&amp;face=false";
		
		if ($values['PicasaAspectRatio'])
			$values['PicasaAlbumURL'] .= "&amp;imgor=".
				$values['PicasaAspectRatio'];
		
		if ($values['PicasaSize'])
			$values['PicasaAlbumURL'] .= "&amp;imgsz=".
				$values['PicasaSize'];
		
		if ($values['PicasaLicense'])
			$values['PicasaAlbumURL'] .= "&amp;imglic=".
				$values['PicasaLicense'];
		
		if ($values['PicasaTime'])
			$values['PicasaAlbumURL'] .= "&amp;time=".
				$values['PicasaTime'];
		
		if ($values['PicasaOrderBy'])
			$values['PicasaAlbumURL'] .= "&amp;orderby=".
				$values['PicasaOrderBy'];
		
		return $values['PicasaAlbumURL'];
	}
	
	static function parseAPIURL($url) {
		$values = array(
			'PicasaAlbumURL' => '',
			'PicasaTags' => '',
			'PicasaSearch' => '',
			'PicasaPhotos' => '',
			'PicasaType' => '',
			'PicasaAspectRatio' => '',
			'PicasaSize' => '',
			'PicasaLicense' => '',
			'PicasaTime' => '',
			'PicasaOrderBy' => '');
		
		if (!$url)
			return $values;
		
		$picasauser = null;
		$picasaalbum = null;
		
		preg_match('/picasaweb\.google\.com\/data\/feed\/api\/(.*?)(\/|$)/', 
			$url, $matches);
		
		if (isset($matches[1]) && $matches[1] == 'featured')
			$values['PicasaPhotos'] = 'featured';
		
		preg_match('/picasaweb\.google\.com\/data\/feed\/api\/user\/(.*?)(\/|$)/', 
			$url, $matches);
		
		if (isset($matches[1]))
			$picasauser = $matches[1];
		
		preg_match('/picasaweb\.google\.com\/data\/feed\/api.*?\/album\/(.*?)(\/|$)/', 
			$url, $matches);
		
		if (isset($matches[1]))
			$picasaalbum = $matches[1];
		
		if ($picasauser)
			$values['PicasaAlbumURL'] .= $picasauser."/";
		
		if ($picasaalbum)
			$values['PicasaAlbumURL'] .= $picasaalbum."/";
		
		if ($values['PicasaAlbumURL'])
			$values['PicasaAlbumURL'] = 
				"http://picasaweb.google.com/" .
				$values['PicasaAlbumURL'];
		
		list(, $arguments) = explode('?', $url);
		parse_str($arguments, $arguments);
		
		foreach($arguments as $key => $value) {
			switch ($key) {
				case 'tag':
					$values['PicasaTags'] = $value;
					continue;
				case 'q':
					$values['PicasaSearch'] = $value;
					continue;
				case 'psc':
					$values['PicasaPhotos'] = $value;
					continue;
				case 'faces':
					if ($value == 'true')
						$values['PicasaType'] = 'faces';
					else
						$values['PicasaType'] = 'no-faces';
					continue;
				case 'imgor':
					$values['PicasaAspectRatio'] = $value;
					continue;
				case 'imgsz':
					$values['PicasaSize'] = $value;
					continue;
				case 'imglic':
					$values['PicasaLicense'] = $value;
					continue;
				case 'time':
					$values['PicasaTime'] = $value;
					continue;
				case 'orderby':
					$values['PicasaOrderBy'] = $value;
					continue;
			}
		}
		
		return $values;
	}
		
	function display() {
		$gallery = sql::fetch(sql::run(
			" SELECT * FROM `{" .$this->sqlOwnerTable . "}`" .
			" WHERE `ID` = '".(int)$this->selectedOwnerID."'" .
			" LIMIT 1"));
		
		if (!$gallery)
			return false;
		
		if (!$this->limit)
			$this->limit = 50;
		
		if (!$this->latests) {
			$paging = new paging($this->limit);
			
			if ($this->ajaxPaging) {
				$paging->ajax = true;
				$paging->otherArgs = "&amp;request=".$this->uriRequest .
					($this->sqlRow?
						"&amp;".strtolower($this->sqlRow)."=".$this->selectedOwnerID:
						null);
			}
			
			$paging->track(strtolower(get_class($this)).'limit');
		}
		
		if (($this->ignorePaging || $this->latests) && $this->limit)
			$gallery['PicasaAPIURL'] .= "&max-results=".$this->limit;
		
		if (!$this->ignorePaging && !$this->latests) {
			list($offset, $limit) = explode(',', $paging->limit);
			$gallery['PicasaAPIURL'] .= "&start-index=".($offset+1) .
				"&max-results=".$limit;
		}
		
		$gallery['PicasaAPIURL'] .= 
			"&thumbsize=".
				(PICTURE_THUMBNAIL_WIDTH?
					PICTURE_THUMBNAIL_WIDTH:
					PICTURE_THUMBNAIL_HEIGHT)."c" .
			"&kind=photo";
		
		$gdata = new GData();
		$gdata->token = $gallery['GDataToken'];
		$data = $gdata->get($gallery['PicasaAPIURL']);
		unset($gdata);
		
		preg_match('/<openSearch:totalResults>(.*?)</is', $data, $matches);
		preg_match('/<entry.*?' .
			'<media:thumbnail.*?url=.([^ \'"]+).*?' .
			'<\/entry>/is', $data, $newestphoto);
		
		$totalitems = 0;
		if (isset($matches[1]))
			$totalitems = (int)$matches[1];
		
		if (!$this->latests)
			$paging->setTotalItems($totalitems);
		
		if (!$this->latests && !$paging->getStart())
			sql::run(
				" UPDATE `{" .$this->sqlOwnerTable . "}` SET" .
				" `Pictures` = '".$totalitems."'," .
				(JCORE_VERSION >= '0.8'?
					" `PreviewPicURL` = '" .
						(isset($newestphoto[1])?
							$newestphoto[1]:
							null)."',":
					null) .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".(int)$this->selectedOwnerID."'");
		
		if (!$totalitems) {
			if (!isset($matches[1]) && $data)
				tooltip::display(
					sprintf(_("Couldn't fetch photo list. Error: %s"),
						strip_tags($data)),
					TOOLTIP_NOTIFICATION);
			
			return false;
		}
		
		preg_match_all('/<entry.*?<updated>(.*?)<\/updated>.*?' .
			'<content.*?src=.([^ \'"]+).*?' .
			'<media:description.*?(\/>|>(.*?)<\/media:description>).*?' .
			'<media:thumbnail.*?url=.([^ \'"]+).*?' .
			'(<gphoto:albumtitle.*?>(.*?)<|<\/entry>)/is', $data, $rows);
		
		if (!$this->ajaxRequest)
			echo
				"<div class='".
					strtolower(preg_replace('/([A-Z])/', '-\\1', get_class($this))).
					" pictures'>";
		
		$i = 1;
		$id = 1;
		
		foreach($rows[1] as $key => $row) {
			if (!$row)
				continue;
			
			$row = array();
			$row['ID'] = "picasa".$id;
			$row['Title'] = $rows[4][$key];
			$row['Location'] = "none";
			$row['TimeStamp'] = date('Y-m-d H:i:s', strtotime($rows[1][$key]));
			$row['URL'] = "";
			$row['Views'] = 0;
			$row['_PictureNumber'] = $i;
			$row['_Link'] = $rows[2][$key];
			$row['_ThumbnailLocation'] = $rows[5][$key];
			
			if ((!$row['Title'] || strlen($row['Title']) > 100) &&
				isset($rows[7][$key]))
				$row['Title'] = $rows[7][$key];
			
			if ($this->format)
				$this->displayFormated($row);
			else
				$this->displayOne($row);
			
			if ($this->columns == $i) {
				echo "<div class='clear-both'></div>";
				$i = 0;
			}
			
			$i++;
			$id++;
		}
		
		echo
			"<div class='clear-both'></div>";
		
		if ($this->showPaging && !$this->randomize && !$this->latests)
			$paging->display();
		
		if (!$this->ajaxRequest)
			echo
				"</div>"; //pictures
		
		if ($this->latests)
			return true;
		
		return $paging->items;
	}
}

class photoGalleryComments extends comments {
	var $sqlTable = 'photogallerycomments';
	var $sqlRow = 'PhotoGalleryID';
	var $sqlOwnerTable = 'photogalleries';
	var $adminPath = 'admin/modules/photogallery/photogallerycomments';
	
	function __construct() {
		languages::load('photogallery');
		
		parent::__construct();
		
		$this->selectedOwner = _('Gallery');
		$this->uriRequest = "modules/photogallery/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('photogallery');
	}
	
	static function getCommentURL($comment = null) {
		if ($comment)
			return photoGallery::getURL($comment['PhotoGalleryID']).
				"&photogalleryid=".$comment['PhotoGalleryID'];
		
		if ($GLOBALS['ADMIN'])
			return photoGallery::getURL(admin::getPathID()).
				"&photogalleryid=".admin::getPathID();
		
		return 
			parent::getCommentURL();
	}
	
	function ajaxRequest() {
		if (JCORE_VERSION >= '0.5' && !photoGallery::checkAccess((int)$this->selectedOwnerID)) {
			$gallery = new photoGallery();
			$gallery->displayLogin();
			unset($gallery);
			return true;
		}
		
		return parent::ajaxRequest();
	}
}

class photoGalleryIcons extends pictures {
	var $previewPicture = false;
	var $sqlTable = 'photogalleryicons';
	var $sqlRow = 'PhotoGalleryID';
	var $sqlOwnerTable = 'photogalleries';
	var $sqlOwnerCountField = 'Icons';
	var $adminPath = 'admin/modules/photogallery/photogalleryicons';
	
	function __construct() {
		languages::load('photogallery');
		
		parent::__construct();
		
		$this->rootPath = $this->rootPath.'icons/';
		$this->rootURL = $this->rootURL.'icons/';
		
		$this->selectedOwner = _('Gallery');
		$this->uriRequest = "modules/photogallery/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('photogallery');
	}
}

class photoGallery extends modules {
	static $uriVariables = 'photogalleryid, photogallerylimit, photogallerypictureslimit, photogallerypicasapictureslimit, photogalleryrating, rate, ajax, request';
	var $searchable = true;
	var $format = null;
	var $limit = 0;
	var $limitGalleries = 0;
	var $selectedID;
	var $search = null;
	var $ignorePaging = false;
	var $showPaging = true;
	var $latests = false;
	var $ajaxPaging = AJAX_PAGING;
	var $ajaxRequest = null;
	var $picturesPath;
	var $thumbnailsPath;
	var $randomizePictures = false;
	var $adminPath = 'admin/modules/photogallery';
	
	function __construct() {
		languages::load('photogallery');
		
		if (isset($_GET['photogalleryid']))
			$this->selectedID = (int)$_GET['photogalleryid'];
		
		if (isset($_GET['searchin']) && isset($_GET['search']) && 
			$_GET['searchin'] == 'modules/photogallery')
			$this->search = trim(strip_tags($_GET['search']));
		
		$this->picturesPath = SITE_PATH.'sitefiles/image/photogallery/';
		$this->thumbnailsPath = $this->picturesPath.'thumbnail/';
	}
	
	function __destruct() {
		languages::unload('photogallery');
	}
	
	function SQL() {
		return
			" SELECT * FROM `{photogalleries}`" .
			" WHERE `Deactivated` = 0" .
			(JCORE_VERSION >= '0.5' && !$GLOBALS['USER']->loginok?
				" AND (`MembersOnly` = 0 " .
				"	OR `ShowToGuests` = 1)":
				null) .
			((int)$this->selectedID?
				" AND `SubGalleryOfID` = '".(int)$this->selectedID."'":
				" AND `SubGalleryOfID` = 0") .
			" ORDER BY `OrderID`, `TimeStamp` DESC, `ID`";
	}
	
	function installSQL() {
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{photogalleries}` (" .
			" `ID` smallint(5) unsigned NOT NULL auto_increment," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `Description` mediumtext NULL," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Path` varchar(255) NOT NULL default ''," .
			" `URL` varchar(255) NOT NULL default ''," .
			" `PicasaAPIURL` VARCHAR( 255 ) NOT NULL DEFAULT ''," .
			" `GDataToken` VARCHAR( 100 ) NOT NULL DEFAULT ''," .
			" `SubGalleryOfID` smallint(5) unsigned NOT NULL default '0'," .
			" `Preview` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'," .
			(JCORE_VERSION >= '0.8'?
				" `PreviewPicURL` VARCHAR( 255 ) NOT NULL DEFAULT '',":
				null) .
			" `Comments` smallint(5) unsigned NOT NULL default '0'," .
			" `Pictures` mediumint(8) unsigned NOT NULL default '0'," .
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
			" `Columns` tinyint(1) unsigned NOT NULL default '0'," .
			" `Limit` tinyint(3) unsigned NOT NULL default '0'," .
			" `UserID` mediumint(8) unsigned NOT NULL default '1'," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" PRIMARY KEY  (`ID`)," .
			" KEY `Path` (`Path`)," .
			" KEY `UserID` (`UserID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `SubGalleryOfID` (`SubGalleryOfID`)," .
			" KEY `Deactivated` (`Deactivated`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `MembersOnly` (`MembersOnly`)," .
			" KEY `ShowToGuests` (`ShowToGuests`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
		
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{photogalleryicons}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" `Location` varchar(255) NOT NULL default ''," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `URL` varchar(255) NOT NULL default ''," .
			" `PhotoGalleryID` smallint(5) unsigned NOT NULL default '1'," .
			" `Views` int(10) unsigned NOT NULL default '0'," .
			" `Thumbnail` tinyint(1) unsigned NOT NULL default '0'," .
			" PRIMARY KEY (`ID`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `PhotoGalleryID` (`PhotoGalleryID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
		
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{photogallerycomments}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `PhotoGalleryID` smallint(5) unsigned NOT NULL default '0'," .
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
			" KEY `PhotoGalleryID` (`PhotoGalleryID`)," .
			" KEY `UserName` (`UserName`)," .
			" KEY `UserID` (`UserID`)," .
			" KEY `Pending` (`Pending`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
		
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{photogallerycommentsratings}` (" .
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
			" CREATE TABLE IF NOT EXISTS `{photogallerypictures}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" `Location` varchar(255) NOT NULL default ''," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `URL` varchar(255) NOT NULL default ''," .
			" `PhotoGalleryID` smallint(5) unsigned NOT NULL default '1'," .
			" `Views` int(10) unsigned NOT NULL default '0'," .
			" `Thumbnail` tinyint(1) unsigned NOT NULL default '0'," .
			" PRIMARY KEY (`ID`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `PhotoGalleryID` (`PhotoGalleryID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::error())
			return false;
		
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{photogalleryratings}` (" .
			" `PhotoGalleryID` smallint(5) unsigned NOT NULL default '0'," .
			" `UserID` mediumint(8) unsigned NOT NULL default '0'," .
			" `IP` DECIMAL(39, 0) NOT NULL default '0'," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Rating` tinyint(1) NOT NULL default '0'," .
			" KEY `Rating` (`Rating`)," .
			" KEY `PhotoGalleryID` (`PhotoGalleryID`)," .
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
			".photogallery-selected {\n" .
			"	margin-bottom: 15px;\n" .
			"}\n" .
			"\n" .
			".photogallery .picture {\n" .
			"	float: left;\n" .
			"	margin: 10px;\n" .
			"}\n" .
			"\n" .
			".photogallery-title {\n" .
			"	margin: 0;\n" .
			"}\n" .
			"\n" .
			".photogallery-details {\n" .
			"	margin: 3px 0 7px 0;\n" .
			"}\n" .
			"\n" .
			".photogallery-rating {\n" .
			"	float: right;\n" .
			"}\n" .
			"\n" .
			".photogallery-links a {\n" .
			"	display: inline-block;\n" .
			"	text-decoration: none;\n" .
			"	padding: 5px 0px 5px 20px;\n" .
			"	background: url(\"http://icons.jcore.net/16/link.png\") 0px 50% no-repeat;\n" .
			"	margin-right: 10px;\n" .
			"}\n" .
			"\n" .
			".photogallery-links .back {\n" .
			"	background-image: url(\"http://icons.jcore.net/16/doc_page_previous.png\");\n" .
			"}\n" .
			"\n" .
			".photogallery-links .pictures {\n" .
			"	background-image: url(\"http://icons.jcore.net/16/images.png\");\n" .
			"}\n" .
			"\n" .
			".photogallery-links .comments {\n" .
			"	background-image: url(\"http://icons.jcore.net/16/comment.png\");\n" .
			"}\n" .
			"\n" .
			".photogallery-folder {\n" .
			"	padding: 5px;\n" .
			"	margin: 1px 0px 5px 0px;\n" .
			"}\n" .
			"\n" .
			".photogallery-folder .photogallery-title,\n" .
			".photogallery-folder .photogallery-details,\n" .
			".photogallery-folder .photogallery-description,\n" .
			".photogallery-folder .photogallery-links\n" .
			"{\n" .
			"	margin-left: 60px;\n" .
			"}\n" .
			"\n" .
			".photogallery-folder .picture-title,\n" .
			".photogallery-folder .picture-details\n" .
			"{\n" .
			"	display: none;\n" .
			"}\n" .
			"\n" .
			".photogallery-folder .picture {\n" .
			"	width: auto;\n" .
			"	height: auto;\n" .
			"	margin: 0;\n" .
			"}\n" .
			"\n" .
			".photogallery-folder .picture img {\n" .
			"	width: 48px;\n" .
			"	height: auto;\n" .
			"}\n" .
			"\n" .
			".photogallery-folder-icon {\n" .
			"	display: block;\n" .
			"	float: left;\n" .
			"	width: 48px;\n" .
			"	height: 48px;\n" .
			"	background: url(\"http://icons.jcore.net/48/folder-photos.png\");\n" .
			"}\n" .
			"\n" .
			".photogallery-folder-icon.subfolders {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/folder-subfolders-photos.png\");\n" .
			"}\n" .
			"\n" .
			".photogallery-folder-icon.icon,\n" .
			".photogallery-folder-icon.preview\n" .
			"{\n" .
			"	background-image: none;\n" .
			"}\n" .
			"\n" .
			".photogallery-selected .photogallery-folder-icon {\n" .
			"	width: auto;\n" .
			"	height: auto;\n" .
			"	margin-right: 15px;\n" .
			"}\n" .
			"\n" .
			".as-modules-photogallery a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/folder-photos.png\");\n" .
			"}\n";
		
		return
			files::save(SITE_PATH.'template/modules/css/photogallery.css', $css);
	}
	
	function uninstallSQL() {
		sql::run(
			" DROP TABLE IF EXISTS `{photogalleries}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{photogalleryicons}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{photogallerycomments}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{photogallerycommentsratings}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{photogallerypictures}`;");
		sql::run(
			" DROP TABLE IF EXISTS `{photogalleryratings}`;");
		
		return true;
	}
	
	function uninstallFiles() {
		return
			files::delete(SITE_PATH.'template/modules/css/photogallery.css');
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		if (!parent::installed($this))
			return 0;
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{photogalleries}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				_('New Gallery'), 
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
			_('Sub Gallery of'),
			'SubGalleryOfID',
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
			_('Columns'),
			'Columns',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);
		$form->setTooltipText(_("e.g. 3 (0 = auto)"));
		
		$form->add(
			__('Limit'),
			'Limit',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		if (JCORE_VERSION >= '0.6') {
			$form->add(
				_('Display Preview'),
				'Preview',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			$form->addAdditionalText(_("(will show the latest picture as icon)"));
		}
			
		if (JCORE_VERSION >= '0.7') {
			$form->add(
				_('Show Icons'),
				'DisplayIcons',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				1);
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			$form->addAdditionalText(
				_("(display icons when gallery selected)"));
		}
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		if (JCORE_VERSION >= '0.7') {
			$form->add(
				_('Picasa Options'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
			
			$form->add(
				_('Album URL'),
				'PicasaAlbumURL',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 300px;');
			$form->setTooltipText(_("e.g. http://picasaweb.google.com/user/Album"));
			
			$form->add(
				_('Tags'),
				'PicasaTags',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 150px;');
			$form->setTooltipText(_("e.g. foo, bar"));
			
			$form->add(
				__('Search'),
				'PicasaSearch',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 100px;');
			$form->setTooltipText(_("e.g. puppy"));
			
			$form->add(
				_('Photos'),
				'PicasaPhotos',
				FORM_INPUT_TYPE_SELECT);
			
			$form->addValue(
				"", "");
			$form->addValue(
				"S", _("My Photos"));
			$form->addValue(
				"G", _("Community Photos"));
			$form->addValue(
				"C", _("Favorites"));
			$form->addValue(
				"featured", _("Featured"));
			
			$form->add(
				__('Type'),
				'PicasaType',
				FORM_INPUT_TYPE_SELECT);
			
			$form->addValue(
				"", "");
			$form->addValue(
				"faces", _("Faces"));
			$form->addValue(
				"no-faces", _("No faces"));
			
			$form->add(
				_('Aspect Ratio'),
				'PicasaAspectRatio',
				FORM_INPUT_TYPE_SELECT);
			
			$form->addValue(
				"", "");
			$form->addValue(
				"landscape", _("Landscape"));
			$form->addValue(
				"portrait", _("Portrait"));
			$form->addValue(
				"panorama", _("Panorama"));
			
			$form->add(
				_('Size'),
				'PicasaSize',
				FORM_INPUT_TYPE_SELECT);
			
			$form->addValue(
				"", "");
			$form->addValue(
				"very_small", _("Small"));
			$form->addValue(
				"small", _("Medium"));
			$form->addValue(
				"medium", _("Large"));
			$form->addValue(
				"large", _("Extra large"));
			
			$form->add(
				_('License'),
				'PicasaLicense',
				FORM_INPUT_TYPE_SELECT);
			
			$form->addValue(
				"", "");
			$form->addValue(
				"creative_commons", _("Creative Commons"));
			$form->addValue(
				"commercial", _("Commercial Use"));
			$form->addValue(
				"remix", _("Remix allowed"));
			
			$form->add(
				_('Time'),
				'PicasaTime',
				FORM_INPUT_TYPE_SELECT);
			
			$form->addValue(
				"", "");
			$form->addValue(
				"today", _("Today"));
			$form->addValue(
				"this_week", _("This Week"));
			$form->addValue(
				"this_month", _("This Month"));
			
			$form->add(
				_('Order By'),
				'PicasaOrderBy',
				FORM_INPUT_TYPE_SELECT);
			
			$form->addValue(
				"", "");
			$form->addValue(
				"date", _("Date"));
			
			$gdata = new GData();
			$gdata->scopes = array("http://picasaweb.google.com/data/");
			
			$form->add(
				_('GData Auth Token'),
				'GDataToken',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 200px;');
			$form->addAdditionalText(
				"<a href='".$gdata->getToken()."' class='gdata-token-link' target='_blank'>" .
					_("Request an Auth Token") .
				"</a>");
			
			unset($gdata);
			
			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER);
		}
		
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
					" UPDATE `{photogalleries}` " .
					" SET `OrderID` = '".(int)$ovalue."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".(int)$oid."'" .
					($this->userPermissionIDs?
						" AND `ID` IN (".$this->userPermissionIDs.")":
						null) .
					($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
						" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'":
						null));
			}
			
			tooltip::display(
				_("Galleries have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				_("Gallery has been successfully deleted."),
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
		
		if ($edit && $form->get('SubGalleryOfID')) {
			foreach(photoGallery::getBackTraceTree($form->get('SubGalleryOfID')) as $gallery) {
				if ($gallery['ID'] == $id) {
					tooltip::display(
						_("Gallery cannot be subgallery of itself!"),
						TOOLTIP_ERROR);
					
					return false;
				}
			}
		}
			
		if (!$form->get('Path')) {
			$path = '';
			
			if ($form->get('SubGalleryOfID')) {
				$subgalleryof = sql::fetch(sql::run(
					" SELECT `Path` FROM `{photogalleries}`" .
					" WHERE `ID` = ".(int)$form->get('SubGalleryOfID')));
				
				$path .= $subgalleryof['Path'].'/'; 
			}
			
			$path .= url::genPathFromString($form->get('Title'));
			
			$form->set('Path', $path);
		}
		
		if (JCORE_VERSION >= '0.7') {
			$form->add(
				_('Picasa API URL'),
				'PicasaAPIURL',
				FORM_INPUT_TYPE_HIDDEN);
			
			$form->set('PicasaAPIURL', 
				photoGalleryPicasaPictures::genAPIURL($form->getPostArray()));
		}
		
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray()))
				return false;
				
			tooltip::display(
				_("Gallery has been successfully updated.")." " .
				(modules::getOwnerURL('photoGallery')?
					"<a href='".photoGallery::getURL($id).
						"&amp;photogalleryid=".$id."' target='_blank'>" .
						_("View Gallery") .
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
			_("Gallery has been successfully created.")." " .
			(modules::getOwnerURL('photoGallery')?
				"<a href='".photoGallery::getURL().
					"&amp;photogalleryid=".$newid."' target='_blank'>" .
					_("View Gallery") .
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
				__("Pictures")."</span></th>";
		
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
					(!$row['SubGalleryOfID']?
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
					"?path=".admin::path()."/".$row['ID']."/photogallerycomments'>";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $row['Comments'])
			counter::display($row['Comments']);
		
		echo
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link pictures' " .
					"title='".htmlspecialchars(__("Pictures"), ENT_QUOTES) .
						" (".$row['Pictures'].")' ";
		
		if (JCORE_VERSION >= '0.7' && $row['PicasaAPIURL']) {
			echo
					"target='_blank' href='" .
						photoGalleryPicasaPictures::genPreviewURL($row['PicasaAPIURL'])."'";
		} else  {
			echo
					"href='".url::uri('ALL') .
						"?path=".admin::path()."/".$row['ID']."/photogallerypictures'";
		}
		
		echo
					">";
		
		if (ADMIN_ITEMS_COUNTER_ENABLED && $row['Pictures'])
			counter::display($row['Pictures']);
		
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
						"?path=".admin::path()."/".$row['ID']."/photogalleryicons'>";
			
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
		
		if (JCORE_VERSION >= '0.7' && $row['PicasaAPIURL']) {
			$picasa = photoGalleryPicasaPictures::parseAPIURL(
				$row['PicasaAPIURL']);
			
			if ($picasa['PicasaAlbumURL'])
				admin::displayItemData(
					_("Picasa Album URL"),
					$picasa['PicasaAlbumURL']);
			
			if ($picasa['PicasaTags'])
				admin::displayItemData(
					_("Picasa Photo Tags"),
					$picasa['PicasaTags']);
			
			if ($picasa['PicasaSearch'])
				admin::displayItemData(
					_("Picasa Search"),
					$picasa['PicasaSearch']);
			
			if ($picasa['PicasaPhotos'])
				admin::displayItemData(
					_("Picasa Photos"),
					($picasa['PicasaPhotos'] ==  "S"?
						_("My Photos"):
						($picasa['PicasaPhotos'] == "G"?
							_("Community Photos"):
							($picasa['PicasaPhotos'] == "C"?
								_("Favorites"):
								($picasa['PicasaPhotos'] == "featured"?
									_("Featured"):
									null)))));
			
			if ($picasa['PicasaType'])
				admin::displayItemData(
					_("Picasa Photo Type"),
					ucfirst($picasa['PicasaType']));
			
			if ($picasa['PicasaAspectRatio'])
				admin::displayItemData(
					_("Picasa Photo Aspect Ratio"),
					ucfirst($picasa['PicasaAspectRatio']));
			
			if ($picasa['PicasaSize'])
				admin::displayItemData(
					_("Picasa Photo Size"),
					ucfirst($picasa['PicasaSize']));
			
			if ($picasa['PicasaLicense'])
				admin::displayItemData(
					_("Picasa Photo License"),
					ucfirst($picasa['PicasaLicense']));
			
			if ($picasa['PicasaTime'])
				admin::displayItemData(
					_("Picasa Photo Time"),
					ucfirst($picasa['PicasaTime']));
			
			if ($picasa['PicasaOrderBy'])
				admin::displayItemData(
					_("Picasa Order By"),
					ucfirst($picasa['PicasaOrderBy']));
		}
		
		if (JCORE_VERSION >= '0.7' && $row['GDataToken'])
			admin::displayItemData(
				_("GData Auth Token"),
				$row['GDataToken']);
		
		if (JCORE_VERSION >= '0.6' && $row['Preview'])
			admin::displayItemData(
				_("Display Preview"),
				__("Yes"));
		
		if ($row['Columns'])
			admin::displayItemData(
				_("Columns"),
				$row['Columns']);
		
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
		
		if (JCORE_VERSION >= '0.5' && $row['MembersOnly'])
			admin::displayItemData(
				_("Members Only"),
				__("Yes"));
		
		if (JCORE_VERSION >= '0.5' && $row['ShowToGuests'])
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
	
	function displayAdminList($rows, $rowpair = null) {
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if (isset($rowpair)) {
			echo 
				"<tr".($rowpair?" class='pair'":NULL).">" .
					"<td></td>" .
					"<td colspan='8' class='auto-width nopadding'>";
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
					" SELECT * FROM `{photogalleries}`" .
					" WHERE `SubGalleryOfID` = '".$row['ID']."'" .
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
			_('Photo Gallery Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
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
					_("Edit Gallery"):
					_("New Gallery")),
				'neweditgallery');
		
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
				" SELECT `ID` FROM `{photogalleries}`" .
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
		
		foreach(photoGallery::getTree() as $row) {
			$form->addValue('SubGalleryOfID',
				$row['ID'], 
				($row['SubItemOfID']?
					str_replace(' ', '&nbsp;', 
						str_pad('', $row['PathDeepnes']*4, ' ')).
					"|- ":
					null) .
				$row['Title']);
		}
		
		$rows = sql::run(
			" SELECT * FROM `{photogalleries}`" .
			" WHERE 1" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			($this->userPermissionType & USER_PERMISSION_TYPE_OWN?
				" AND `UserID` = '".$GLOBALS['USER']->data['ID']."'":
				null) .
			(!$this->userPermissionIDs && ~$this->userPermissionType & USER_PERMISSION_TYPE_OWN?
				" AND `SubGalleryOfID` = 0":
				null) .
			" ORDER BY `OrderID`, `TimeStamp` DESC, `ID`");
		
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
					_("No galleries found."),
					TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && $selected)))
		{
			if ($edit && $selected && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{photogalleries}`" .
					" WHERE `ID` = '".$id."'"));
				
				if (JCORE_VERSION >= '0.7' && $selected['PicasaAPIURL'])
					$selected += photoGalleryPicasaPictures::parseAPIURL($selected['PicasaAPIURL']);
				
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
				" SELECT `OrderID` FROM `{photogalleries}` " .
				" WHERE `SubGalleryOfID` = '".(int)$values['SubGalleryOfID']."'" .
				" ORDER BY `OrderID` DESC"));
			
			$values['OrderID'] = (int)$row['OrderID']+1;
			
		} else {
			sql::run(
				" UPDATE `{photogalleries}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `SubGalleryOfID` = '".(int)$values['SubGalleryOfID']."'" .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		if ((int)$values['SubGalleryOfID']) {
			$parentgallery = sql::fetch(sql::run(
				" SELECT * FROM `{photogalleries}`" .
				" WHERE `ID` = '".(int)$values['SubGalleryOfID']."'"));
			
			if ($parentgallery['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;
			
			if (JCORE_VERSION >= '0.5') {
				if ($parentgallery['MembersOnly'] && !$values['MembersOnly'])
					$values['MembersOnly'] = true;
				
				if ($parentgallery['ShowToGuests'] && !$values['ShowToGuests'])
					$values['ShowToGuests'] = true;
			}
		}
		
		$newid = sql::run(
			" INSERT INTO `{photogalleries}` SET ".
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
			(JCORE_VERSION >= '0.7'?
				" `PicasaAPIURL` = '".
					sql::escape($values['PicasaAPIURL'])."'," .
				" `GDataToken` = '".
					sql::escape($values['GDataToken'])."',":
				null) .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `SubGalleryOfID` = '".
				(int)$values['SubGalleryOfID']."'," .
			(JCORE_VERSION >= '0.6'?
				" `Preview` = '".
					(int)$values['Preview']."',":
				null) .
			" `EnableComments` = '".
				(int)$values['EnableComments']."'," .
			" `EnableGuestComments` = '".
				(int)$values['EnableGuestComments']."'," .
			" `EnableRating` = '".
				(int)$values['EnableRating']."'," .
			" `EnableGuestRating` = '".
				(int)$values['EnableGuestRating']."'," .
			" `Columns` = '".
				(int)$values['Columns']."'," .
			(JCORE_VERSION >= '0.5'?
				" `MembersOnly` = '".
					(int)$values['MembersOnly']."'," .
				" `ShowToGuests` = '".
					(int)$values['ShowToGuests']."',":
				null) .
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
				sprintf(_("Gallery couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (JCORE_VERSION >= '0.7' && $values['PicasaAPIURL']) {
			$gdata = new GData();
			$gdata->token = $values['GDataToken'];
			$data = $gdata->get($values['PicasaAPIURL'] .
				"&thumbsize=".
					(PICTURE_THUMBNAIL_WIDTH?
						PICTURE_THUMBNAIL_WIDTH:
						PICTURE_THUMBNAIL_HEIGHT)."c" .
				"&max-results=1&kind=photo");
			unset($gdata);
			
			preg_match('/<openSearch:totalResults>(.*?)</is', $data, $matches);
			preg_match('/<entry.*?' .
				'<media:thumbnail.*?url=.([^ \'"]+).*?' .
				'<\/entry>/is', $data, $newestphoto);
			
			sql::run(
				" UPDATE `{photogalleries}` SET" .
				" `Pictures` = '" .
					(isset($matches[1])?
						(int)$matches[1]:
						0)."'," .
				(JCORE_VERSION >= '0.8'?
					" `PreviewPicURL` = '" .
						(isset($newestphoto[1])?
							$newestphoto[1]:
							null)."',":
					null) .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".(int)$newid."'");
		}
		
		if (JCORE_VERSION >= '0.5')
			$this->protectPictures();
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		$gallery = sql::fetch(sql::run(
			" SELECT * FROM `{photogalleries}`" .
			" WHERE `ID` = '".$id."'"));
			
		if ((int)$values['SubGalleryOfID'] && 
			(int)$values['SubGalleryOfID'] != $gallery['SubGalleryOfID']) 
		{
			$parentgallery = sql::fetch(sql::run(
				" SELECT * FROM `{photogalleries}`" .
				" WHERE `ID` = '".(int)$values['SubGalleryOfID']."'"));
			
			if ($parentgallery['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;
			
			if (JCORE_VERSION >= '0.5') {
				if ($parentgallery['MembersOnly'] && !$values['MembersOnly'])
					$values['MembersOnly'] = true;
				
				if ($parentgallery['ShowToGuests'] && !$values['ShowToGuests'])
					$values['ShowToGuests'] = true;
			}
		}
		
		sql::run(
			" UPDATE `{photogalleries}` SET ".
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
			(JCORE_VERSION >= '0.7'?
				" `PicasaAPIURL` = '".
					sql::escape($values['PicasaAPIURL'])."'," .
				" `GDataToken` = '".
					sql::escape($values['GDataToken'])."',":
				null) .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `SubGalleryOfID` = '".
				(int)$values['SubGalleryOfID']."'," .
			(JCORE_VERSION >= '0.6'?
				" `Preview` = '".
					(int)$values['Preview']."',":
				null) .
			" `EnableComments` = '".
				(int)$values['EnableComments']."'," .
			" `EnableGuestComments` = '".
				(int)$values['EnableGuestComments']."'," .
			" `EnableRating` = '".
				(int)$values['EnableRating']."'," .
			" `EnableGuestRating` = '".
				(int)$values['EnableGuestRating']."'," .
			" `Columns` = '".
				(int)$values['Columns']."'," .
			(JCORE_VERSION >= '0.5'?
				" `MembersOnly` = '".
					(int)$values['MembersOnly']."'," .
				" `ShowToGuests` = '".
					(int)$values['ShowToGuests']."',":
				null) .
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
				sprintf(_("Gallery couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		foreach(photoGallery::getTree((int)$id) as $row) {
			$updatesql = null;
			
			if (($gallery['Deactivated'] && !$values['Deactivated']) ||
				(!$gallery['Deactivated'] && $values['Deactivated'])) 
			{
				if (!$row['Deactivated'] && $values['Deactivated'])
					$updatesql[] = " `Deactivated` = 1";
				if ($row['Deactivated'] && !$values['Deactivated'])
					$updatesql[] = " `Deactivated` = 0";
			}
			
			if (JCORE_VERSION >= '0.5') {
				if (($gallery['MembersOnly'] && !$values['MembersOnly']) ||
					(!$gallery['MembersOnly'] && $values['MembersOnly'])) 
				{
					if (!$row['MembersOnly'] && $values['MembersOnly'])
						$updatesql[] = " `MembersOnly` = 1";
					if ($row['MembersOnly'] && !$values['MembersOnly'])
						$updatesql[] = " `MembersOnly` = 0";
				}
				
				if (($gallery['ShowToGuests'] && !$values['ShowToGuests']) ||
					(!$gallery['ShowToGuests'] && $values['ShowToGuests'])) 
				{
					if (!$row['ShowToGuests'] && $values['ShowToGuests'])
						$updatesql[] = " `ShowToGuests` = 1";
					if ($row['ShowToGuests'] && !$values['ShowToGuests'])
						$updatesql[] = " `ShowToGuests` = 0";
				}
			}
			
			if ($updatesql)
				sql::run(
					" UPDATE `{photogalleries}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
		}
		
		foreach(photoGallery::getBackTraceTree((int)$id) as $row) {
			$updatesql = null;
			
			if ($row['Deactivated'] && !$values['Deactivated'])
				$updatesql[] = " `Deactivated` = 0";
			
			if (JCORE_VERSION >= '0.5') {
				if ($row['MembersOnly'] && !$values['MembersOnly'])
					$updatesql[] = " `MembersOnly` = 0";
			}
			
			if ($updatesql)
				sql::run(
					" UPDATE `{photogalleries}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
		}

		if (JCORE_VERSION >= '0.7' && $values['PicasaAPIURL']) {
			$gdata = new GData();
			$gdata->token = $values['GDataToken'];
			$data = $gdata->get($values['PicasaAPIURL'] .
				"&thumbsize=".
					(PICTURE_THUMBNAIL_WIDTH?
						PICTURE_THUMBNAIL_WIDTH:
						PICTURE_THUMBNAIL_HEIGHT)."c" .
				"&max-results=1&kind=photo");
			unset($gdata);
			
			preg_match('/<openSearch:totalResults>(.*?)</is', $data, $matches);
			preg_match('/<entry.*?' .
				'<media:thumbnail.*?url=.([^ \'"]+).*?' .
				'<\/entry>/is', $data, $newestphoto);
			
			sql::run(
				" UPDATE `{photogalleries}` SET" .
				" `Pictures` = '" .
					(isset($matches[1])?
						(int)$matches[1]:
						0)."'," .
				(JCORE_VERSION >= '0.8'?
					" `PreviewPicURL` = '" .
						(isset($newestphoto[1])?
							$newestphoto[1]:
							null)."',":
					null) .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".(int)$id."'");
		}
		
		if (JCORE_VERSION >= '0.5')
			$this->protectPictures();
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		$gallerypictures = new photoGalleryPictures();
		$gallerycomments = new photoGalleryComments();
		$galleryids = array($id);
		
		foreach(photoGallery::getTree((int)$id) as $row)
			$galleryids[] = $row['ID'];
		
		
		foreach($galleryids as $galleryid) {
			$rows = sql::run(
				" SELECT * FROM `{photogallerypictures}` " .
				" WHERE `PhotoGalleryID` = '".$galleryid."'");
			
			while($row = sql::fetch($rows))
				$gallerypictures->delete($row['ID']);
			
			$rows = sql::run(
				" SELECT * FROM `{photogallerycomments}` " .
				" WHERE `PhotoGalleryID` = '".$galleryid."'");
			
			while($row = sql::fetch($rows))
				$gallerycomments->delete($row['ID']);
			
			sql::run(
				" DELETE FROM `{photogalleryratings}` " .
				" WHERE `PhotoGalleryID` = '".$galleryid."'");
			
			sql::run(
				" DELETE FROM `{photogalleries}` " .
				" WHERE `ID` = '".(int)$id."'");
		}
		
		unset($gallerycomments);
		unset($gallerypictures);
		
		if (JCORE_VERSION >= '0.6') {
			$icons = new photoGalleryIcons();
			
			$rows = sql::run(
				" SELECT * FROM `{photogalleryicons}`" .
				" WHERE `PhotoGalleryID` = '".$id."'");
			
			while($row = sql::fetch($rows))
				$icons->delete($row['ID']);
			
			unset($icons);
		}
		
		if (JCORE_VERSION >= '0.5')
			$this->protectPictures();
		
		return true;
	}
	
	function protectPictures() {
		if (!$this->picturesPath)
			return false;
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows` FROM `{photogalleries}` " .
			" WHERE `MembersOnly` = 1" .
			" LIMIT 1"));
			
		if ($row['Rows']) {
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
		
		if (files::exists($this->picturesPath.'.htaccess'))
			files::delete($this->picturesPath.'.htaccess');
		
		if (files::exists($this->thumbnailsPath.'.htaccess'))
			files::delete($this->thumbnailsPath.'.htaccess');
			
		return true;
	}
	
	static function getTree($galleryid = 0, $firstcall = true,
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0)) 
	{
		$rows = sql::run(
			" SELECT *, `SubGalleryOfID` AS `SubItemOfID` " .
			" FROM `{photogalleries}` " .
			($galleryid?
				" WHERE `SubGalleryOfID` = '".$galleryid."'":
				" WHERE `SubGalleryOfID` = 0") .
			" ORDER BY `OrderID`, `TimeStamp` DESC, `ID`");
		
		while($row = sql::fetch($rows)) {
			$row['PathDeepnes'] = $tree['PathDeepnes'];
			$tree['Tree'][] = $row;
			
			$tree['PathDeepnes']++;
			photoGallery::getTree($row['ID'], false, $tree);
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
			" SELECT *, `SubGalleryOfID` AS `SubItemOfID` " .
			" FROM `{photogalleries}` " .
			" WHERE `ID` = '".(int)$id."'"));
		
		if (!$row)
			return array();
		
		if ($row['SubItemOfID'])	
			photoGallery::getBackTraceTree($row['SubItemOfID'], false, $tree);
		
		$row['PathDeepnes'] = $tree['PathDeepnes'];
		$tree['Tree'][] = $row;
		$tree['PathDeepnes']++;
		
		if ($firstcall)
			return $tree['Tree'];
	}
	
	// ************************************************   Client Part
	static function getURL($id = 0) {
		$url = modules::getOwnerURL('photoGallery', $id);
		
		if (!$url)
			return url::site() .
				url::uri(photoGallery::$uriVariables);
		
		return $url;	
	}
	
	static function checkAccess($row) {
		if ($GLOBALS['USER']->loginok)
			return true;
		
		if ($row && !is_array($row))
			$row = sql::fetch(sql::run(
				" SELECT `MembersOnly`, `ShowToGuests`" .
				" FROM `{photogalleries}`" .
				" WHERE `ID` = '".(int)$row."'"));
		
		if (!$row)
			return true;
		
		if ($row['MembersOnly'] && !$row['ShowToGuests'])
			return false;
		
		return true;
	}
	
	function ajaxRequest() {
		$users = null;
		
		if (isset($_GET['users']))
			$users = $_GET['users'];
		
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
			
			$GLOBALS['USER']->displayQuickList('#neweditgalleryform #entryOwner');
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
	
	function displayPreviewIcon(&$row) {
		echo
			"<div class='photogallery-folder-icon preview'>";
		
		if (isset($row['PreviewPicURL']) && $row['PreviewPicURL'])
			$pictures = new photoGalleryPictures();
		elseif (isset($row['PicasaAPIURL']) && $row['PicasaAPIURL'])
			$pictures = new photoGalleryPicasaPictures();
		else
			$pictures = new photoGalleryPictures();
		
		$pictures->selectedOwnerID = $row['ID'];
		$pictures->showPaging = false;
		$pictures->limit = 1;
		
		if (JCORE_VERSION >= '0.6' && $row['URL'])
			$pictures->customLink = url::generateLink($row['URL']);
		else
			$pictures->customLink = $row['_Link'];
		
		if (isset($row['PreviewPicURL']) && $row['PreviewPicURL'])
			$pictures->displayGalleryPreview($row);
		else
			$pictures->display();
		
		unset($pictures);
		
		echo
			"</div>";
	}
	
	function displayIcon(&$row) {
		if (JCORE_VERSION >= '0.6' && $row['Icons']) {
			echo
				"<div class='photogallery-folder-icon icon'>";
		
			$icons = new photoGalleryIcons();
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
				"class='photogallery-folder-icon" .
				($row['_SubGalleries']?
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
			"<a href='".url::uri(photoGallery::$uriVariables)."'>" .
				_("Photos").
			"</a>";
			
		foreach(photoGallery::getBackTraceTree($row['ID']) as $gallery) {
			$href = url::uri(photoGallery::$uriVariables).
				"&amp;photogalleryid=".$gallery['ID'];
			
			echo 
				"<span class='path-separator'> / </span>" .
				"<a href='".$href."'>".
					$gallery['Title'].
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
	
	function displayPictures(&$row = null) {
		if ($row && !$row['Pictures'] && 
			(!isset($row['PicasaAPIURL']) || !$row['PicasaAPIURL']))
			return;
		
		if (isset($row['PicasaAPIURL']) && $row['PicasaAPIURL'])
			$pictures = new photoGalleryPicasaPictures();
		else
			$pictures = new photoGalleryPictures();
		
		if ($row) {
			$pictures->selectedOwnerID = $row['ID'];
			$pictures->columns = $row['Columns'];
			$pictures->limit = $row['Limit'];
		} else {
			$pictures->latests = true;
			$pictures->format = $this->format;
		}
		
		$pictures->ignorePaging = $this->ignorePaging;
		$pictures->showPaging = $this->showPaging;
		$pictures->ajaxPaging = $this->ajaxPaging;
		$pictures->randomize = $this->randomizePictures;
		
		if ($this->limit)
			$pictures->limit = $this->limit;
		
		$pictures->display();
		unset($pictures);
	}
	
	function displayComments(&$row = null) {
		$comments = new photoGalleryComments();
		
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
		$rating = new photoGalleryRating();
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
				"' class='pictures comment'>" .
				"<span>".
				($row['_SubGalleries']?
					_("Pictures / Galleries"):
					_("Pictures")).
				"</span> " .
				"<span>" .
				"(".($row['Pictures']+$row['_SubGalleries']).")" .
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
		$row['_Link'] = url::uri(photoGallery::$uriVariables).
			"&amp;photogalleryid=".$row['ID'];
		
		$row['_SubGalleries'] = sql::count(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{photogalleries}`" .
			" WHERE `Deactivated` = 0" .
			" AND `SubGalleryOfID` = '".(int)$row['ID']."'");
		
		echo 
			"<div" .
				(JCORE_VERSION < '0.6'?
					" id='photogallery".$row['ID']."'":
					null) .
				" class='photogallery-folder" .
				($row['SubGalleryOfID']?
					" photogallery-sub-folder":
					null) .
				($row['_SubGalleries']?
					" photogallery-has-sub-folders":
					null) .
				" photogallery".$row['ID'] .
				" rounded-corners'>";
		
		if (JCORE_VERSION >= '0.6' && $row['Preview'])
			$this->displayPreviewIcon($row);
		else
			$this->displayIcon($row);
				
		echo
				"<h3 class='photogallery-title'>";
		
		$this->displayTitle($row);
		
		echo
				"</h3>" .
				"<div class='photogallery-details comment'>";
		
		$this->displayDetails($row);
			
		echo
				"</div>";
		
		if ($row['Description']) {
			echo
				"<div class='photogallery-description'>";
			
			$this->displayDescription($row);
			
			echo
				"</div>";
		}
		
		if ($row['EnableRating']) {
			echo
				"<div class='photogallery-rating'>";
			
			$this->displayRating($row);
		
			echo
				"</div>";
		}
		
		echo
				"<div class='photogallery-links'>";
		
		$this->displayFunctions($row);
			
		echo
				"</div>" .
				"<div class='clear-both'></div>" .
			"</div>";
	}
	
	function displaySelected(&$row) {
		if (JCORE_VERSION >= '0.5' && !$this->checkAccess($row)) {
			$this->displayLogin();
			return false;
		}
		
		echo 
			"<div class='photogallery photogallery".$row['ID']."'>" .
				"<div class='photogallery-selected'>" .
					"<h3 class='photogallery-title'>";
		
		$this->displaySelectedTitle($row);
		
		echo
					"</h3>";
	
		if ($row['EnableRating']) {
			echo
					"<div class='photogallery-rating'>";
			
			$this->displayRating($row);
			
			echo
					"</div>";
		}
			
		echo
					"<div class='photogallery-details comment'>";
		
		$this->displayDetails($row);
		
		echo
					"</div>";
	
		if (JCORE_VERSION >= '0.7' && $row['DisplayIcons'] && $row['Icons'])
			$this->displayIcon($row);
		
		if ($row['Description']) {
			echo
					"<div class='photogallery-description'>";
			
			$this->displayDescription($row);
			
			echo
					"</div>";
		}
		
		echo
					"<div class='clear-both'></div>" .
				"</div>";
		
		$this->displayGalleries();
		$this->displayPictures($row);
			
		echo 
				"<div class='clear-both'></div>";
		
		if ($row['EnableComments'])
			$this->displayComments($row);
		
		echo 
			"</div>"; //.photogallery
			
		return true;
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		if (preg_match('/(^|\/)rand($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)rand($|\/)/', '\2', $this->arguments);
			$this->randomizePictures = true;
		}
		
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
		
		$gallery = sql::fetch(sql::run(
			" SELECT * FROM `{photogalleries}` " .
			" WHERE `Deactivated` = 0" .
			((int)$this->selectedID?
				" AND `ID` = '".(int)$this->selectedID."'":
				" AND `Path` LIKE '".sql::escape($this->arguments)."'") .
			" ORDER BY `OrderID`, `TimeStamp` DESC, `ID`" .
			" LIMIT 1"));
		
		if (!$gallery)
			return true;
		
		$this->selectedID = $gallery['ID'];	
		$this->displaySelected($gallery);
		
		return true;
	}
	
	function displaySearch() {
		$pictures = new photoGalleryPictures();
		
		$pictures->limit = $this->limit;
		$pictures->search = $this->search;
		
		ob_start();
		$itemsfound = $pictures->display();
		$content = ob_get_contents();
		ob_end_clean();
		
		unset($pictures);
		
		if (!isset($this->arguments))
			url::displaySearch($this->search, $itemsfound);
		
		echo
			"<div class='photogallery'>" .
			$content .
			"</div>";
		
		return $itemsfound;
	}
	
	function displayGalleries() {
		$paging = new paging($this->limitGalleries);
		
		if ($this->ajaxPaging) {
			$paging->ajax = true;
			$paging->otherArgs = "&amp;request=modules/photogallery";
		}
		
		$limitarg = strtolower(get_class($this)).'limit';
		$paging->track($limitarg);
		
		$galleries = sql::run(
			$this->SQL() .
			" LIMIT ".$paging->limit);
		
		if (!sql::rows($galleries))
			return false;
		
		$paging->setTotalItems(sql::count());
		
		if (!$this->ajaxRequest)
			echo
				"<div class='photogallery-folders'>";
		
		while ($gallery = sql::fetch($galleries))
			$this->displayOne($gallery);
		
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
		
		if (!$this->limitGalleries && $this->owner['Limit'])
			$this->limitGalleries = $this->owner['Limit'];
		
		if (!$this->latests && (int)$this->selectedID) {
			$row = sql::fetch(sql::run(
				" SELECT * FROM `{photogalleries}`" .
				" WHERE `Deactivated` = 0" .
				" AND `ID` = '".(int)$this->selectedID."'" .
				" LIMIT 1"));
				
			return $this->displaySelected($row);
		}
		
		if (!$this->latests && $this->search)
			return $this->displaySearch();
		
		echo 
			"<div class='photogallery'>";
		
		if ($this->latests)
			$this->displayPictures();
		else
			$items = $this->displayGalleries();
		
		echo 
			"</div>";
		
		if ($this->latests)
			return true;
		
		return $items;
	}
}

modules::register(
	'photoGallery', 
	_('Photo Gallery'),
	_('Share pictures / photos in a directory like structure'));

?>