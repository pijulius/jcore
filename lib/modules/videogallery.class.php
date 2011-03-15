<?php

/***************************************************************************
 * 
 *  Name: Video Gallery Module
 *  URI: http://jcore.net
 *  Description: Display videos in a folder/gallery like structure. Released under the GPL, LGPL, and MPL Licenses.
 *  Author: Istvan Petres
 *  Version: 0.3
 *  Tags: video gallery module, gpl, lgpl, mpl
 * 
 ****************************************************************************/

modules::register(
	'videoGallery', 
	_('Video Gallery'),
	_('Share videos in a directory like structure'));
	
include_once('lib/videos.class.php');

class videoGalleryRating extends starRating {
	var $sqlRow = 'VideoGalleryID';
	var $sqlTable = 'videogalleryratings';
	var $sqlOwnerTable = 'videogalleries';
	var $adminPath = 'admin/modules/videogallery/videogalleryrating';
	
	function __construct() {
		languages::load('videogallery');
		
		parent::__construct();
		
		$this->selectedOwner = _('Gallery');
		$this->uriRequest = "modules/videogallery/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('videogallery');
	}
}

class videoGalleryVideos extends videos {
	var $search;
	var $sqlTable = 'videogalleryvideos';
	var $sqlRow = 'VideoGalleryID';
	var $sqlOwnerTable = 'videogalleries';
	var $adminPath = 'admin/modules/videogallery/videogalleryvideos';
	
	function __construct() {
		languages::load('videogallery');
		
		parent::__construct();
		
		if (isset($_GET['searchin']) && isset($_GET['search']) && 
			$_GET['searchin'] == 'modules/videogallery')
			$this->search = trim(strip_tags($_GET['search']));
			
		$this->rootPath = $this->rootPath.'videogallery/';
		$this->rootURL = $this->rootURL.'videogallery/';
		
		$this->selectedOwner = _('Gallery');
		$this->uriRequest = "modules/videogallery/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('videogallery');
	}
	
	function SQL() {
		if (!$this->search)
			return parent::SQL();
		
		$folders = null;
		$ignorefolders = null;
		
		if (!$GLOBALS['USER']->loginok) {
			$row = sql::fetch(sql::run(
				" SELECT GROUP_CONCAT(`ID` SEPARATOR ',') AS `FolderIDs`" .
				" FROM `{videogalleries}`" .
				" WHERE !`Deactivated`" .
				" AND `MembersOnly` " .
				" AND !`ShowToGuests`" .
				" LIMIT 1"));
				
			if ($row['FolderIDs'])
				$ignorefolders = explode(',', $row['FolderIDs']);
		}
		
		$row = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(`ID` SEPARATOR ',') AS `FolderIDs`" .
			" FROM `{videogalleries}`" .
			" WHERE !`Deactivated`" .
			($ignorefolders?
				" AND `ID` NOT IN (".implode(',', $ignorefolders).")":
				null) .
			sql::search(
				$this->search,
				array('Title', 'Description')) .
			" LIMIT 1"));
			
		if ($row['FolderIDs']) {
			foreach(explode(',', $row['FolderIDs']) as $id) {
				$folders[] = $id;
				foreach(videoGallery::getTree($id) as $folder)
					$folders[] = $folder['ID'];
			}
		}
		
		return
			" SELECT * FROM `{" .$this->sqlTable."}`" .
			" WHERE ((1" .
			sql::search(
				$this->search,
				array('Title', 'Location')) .
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
	
	function download($id) {
		if (!(int)$id) {
			tooltip::display(
				_("No video selected!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{" .$this->sqlTable . "}`" .
			" WHERE `ID` = '".(int)$id."'" .
			" LIMIT 1"));
		
		if (!$row) {
			tooltip::display(
				_("The selected video cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$folder = sql::fetch(sql::run(
			" SELECT * FROM `{" .$this->sqlOwnerTable . "}`" .
			" WHERE `ID` = '".(int)$row[$this->sqlRow]."'" .
			" LIMIT 1"));
		
		if (!$GLOBALS['USER']->loginok && $folder['MembersOnly']) {
			tooltip::display(
				_("You need to be logged in to view this video. " .
					"Please login or register."),
				TOOLTIP_ERROR);
			return false;
		}
		
		return videos::download($id);
	}
	
	function displayGalleryPreview($gallery) {
		echo
			"<div class='".
				strtolower(preg_replace('/([A-Z])/', '-\\1', get_class($this))).
				" videos'>";
		
		$row = array();
		$row['ID'] = "preview";
		$row['Title'] = $gallery['Title'];
		$row['Location'] = "none";
		$row['CapLocation'] = $gallery['PreviewPicURL'];
		$row['TimeStamp'] = $gallery['TimeStamp'];
		$row['URL'] = "";
		$row['Views'] = 0;
		$row['_VideoNumber'] = 'preview';
		
		$this->displayOne($row);
		
		echo
			"<div class='clear-both'></div>";
		
		echo
			"</div>"; //pictures
	}
}

class videoGalleryYouTubeVideos extends videoGalleryVideos {
	function __construct() {
		languages::load('videogallery');
		
		parent::__construct();
		
		if (isset($_GET['videoid']))
			$this->selectedID = $_GET['videoid'];
	}
	
	function __destruct() {
		languages::unload('videogallery');
	}
	
	static function genAPIURL($values) {
		if (!$values || !is_array($values))
			return null;
		
		$youtubeuser = null;
		$youtubeapiurl = null;
		
		if ($values['YouTubeChannelURL']) {
			if (in_array($values['YouTubeVideos'], array(
				'top_rated', 'top_favorites', 'most_viewed',
				'most_popular', 'most_recent', 'most_discussed',
				'most_responded', 'recently_featured', 'watch_on_mobile')))
				$values['YouTubeChannelURL'] = "";
			
			preg_match('/youtube\.com\/user\/(.*?)(\/|$)/', 
				preg_replace('/\?.*$/', '', $values['YouTubeChannelURL']), 
				$matches);
			
			if (isset($matches[1]))
				$youtubeuser = $matches[1];
		}
		
		if (!$youtubeuser && !$values['YouTubeTags'] && !$values['YouTubeSearch'] && 
			!$values['YouTubeCategory'] && !$values['YouTubeVideos'])
			return null;
		
		if (in_array($values['YouTubeVideos'], array(
			'uploads', 'favorites')) && !$youtubeuser)
			$youtubeuser = "default";
		
		if ($youtubeuser) {
			$youtubeapiurl .= "users/".$youtubeuser."/";
			
			if (!$values['YouTubeVideos'])
				$values['YouTubeVideos'] = "uploads";
		}
		
		if (in_array($values['YouTubeVideos'], array(
			'top_rated', 'top_favorites', 'most_viewed',
			'most_popular', 'most_recent', 'most_discussed',
			'most_responded', 'recently_featured', 'watch_on_mobile')))
			$youtubeapiurl .= "standardfeeds/".$values['YouTubeVideos']."/";
		elseif ($values['YouTubeVideos'])
			$youtubeapiurl .= $values['YouTubeVideos']."/";
		
		if (!$youtubeapiurl)
			$youtubeapiurl .= "videos/";
		
		$youtubeapiurl = 
			"http://gdata.youtube.com/feeds/api/" .
			$youtubeapiurl."?";
		
		if ($values['YouTubeCategory'] || $values['YouTubeTags'])
			$youtubeapiurl .= "&category=" .
				urlencode(
					$values['YouTubeCategory'] .
					($values['YouTubeCategory'] && $values['YouTubeTags']?
						",":
						null).
					strtolower($values['YouTubeTags']));
		
		if ($values['YouTubeSearch'])
			$youtubeapiurl .= "&q=".urlencode($values['YouTubeSearch']);
		
		if ($values['YouTubeTime'])
			$youtubeapiurl .= "&time=".$values['YouTubeTime'];
		
		if ($values['YouTubeOrderBy'])
			$youtubeapiurl .= "&orderby=".$values['YouTubeOrderBy'];
		
		return $youtubeapiurl;
	}
	
	static function genPreviewURL($apiurl) {
		if (!$apiurl)
			return null;
		
		$values = videoGalleryYouTubeVideos::parseAPIURL($apiurl);
		
		if (!$values['YouTubeChannelURL']) {
			$values['YouTubeChannelURL'] = "http://www.youtube.com";
			
			if ($values['YouTubeTags'] || $values['YouTubeSearch']) {
				$values['YouTubeChannelURL'] .= "/results?";
				
				$values['YouTubeChannelURL'] .= "&amp;search_query=" .
					urlencode($values['YouTubeSearch']) .
					($values['YouTubeSearch'] && $values['YouTubeTags']?
						",":
						null) .
					urlencode($values['YouTubeTags']);
				
				if ($values['YouTubeCategory'])
					$values['YouTubeChannelURL'] .= "&amp;search_category=" .
						videoGalleryYouTubeVideos::categoryToInt($values['YouTubeCategory']);
				
				if ($values['YouTubeOrderBy'])
					$values['YouTubeChannelURL'] .= "&amp;search_sort=".
						videoGalleryYouTubeVideos::orderByToVal($values['YouTubeOrderBy']);
				
				if ($values['YouTubeTime'])
					$values['YouTubeChannelURL'] .= "&amp;uploaded=".
						substr($values['YouTubeTime'], 0, 1);
				
			} else {
				$values['YouTubeChannelURL'] .= "/videos?";
			}
			
		} else {
			$values['YouTubeChannelURL'] .= "?";
		}
		
		if ($values['YouTubeCategory'])
			$values['YouTubeChannelURL'] .= "&amp;c=" .
				videoGalleryYouTubeVideos::categoryToInt($values['YouTubeCategory']);
		
		if ($values['YouTubeTime'])
			$values['YouTubeChannelURL'] .= "&amp;t=".
				substr($values['YouTubeTime'], 0, 1);
		
		if ($values['YouTubeVideos'] == 'uploads')
			$values['YouTubeChannelURL'] .= "#p/u";
		
		elseif ($values['YouTubeVideos'] == 'favorites')
			$values['YouTubeChannelURL'] .= "#p/f";
		
		elseif ($values['YouTubeVideos'])
			$values['YouTubeChannelURL'] .= "&amp;s=" .
				videoGalleryYouTubeVideos::typeToVal($values['YouTubeVideos']);
		
		return $values['YouTubeChannelURL'];
	}
	
	static function parseAPIURL($url) {
		$values = array(
			'YouTubeChannelURL' => '',
			'YouTubeTags' => '',
			'YouTubeSearch' => '',
			'YouTubeCategory' => '',
			'YouTubeVideos' => '',
			'YouTubeTime' => '',
			'YouTubeOrderBy' => '');
		
		if (!$url)
			return $values;
		
		$youtubeuser = null;
		
		preg_match('/gdata\.youtube\.com\/feeds\/api\/users\/(.*?)(\/|$)/', 
			$url, $matches);
		
		if (isset($matches[1]))
			$youtubeuser = $matches[1];
		
		if ($youtubeuser) {
			$values['YouTubeChannelURL'] .= "user/".$youtubeuser."/";
			
			preg_match('/gdata\.youtube\.com\/feeds\/api\/users\/.*?\/(.*?)(\/|$)/', 
				$url, $matches);
			
			if (isset($matches[1]))
				$values['YouTubeVideos'] = $matches[1];
			
		} else {
			preg_match('/gdata\.youtube\.com\/feeds\/api\/standardfeeds\/(.*?)(\/|$)/', 
				$url, $matches);
			
			if (isset($matches[1]))
				$values['YouTubeVideos'] = $matches[1];
		}
		
		if ($values['YouTubeChannelURL'])
			$values['YouTubeChannelURL'] = 
				"http://www.youtube.com/" .
				$values['YouTubeChannelURL'];
		
		list(, $arguments) = explode('?', $url);
		parse_str($arguments, $arguments);
		
		foreach($arguments as $key => $value) {
			switch ($key) {
				case 'category':
					if (preg_match('/^[A-Z]/', $value)) {
						list($values['YouTubeCategory']) = explode(',', $value);
						$value = preg_replace('/^.*?(,|$)/', '', $value);
					}
					
					$values['YouTubeTags'] = $value;
					continue;
				case 'q':
					$values['YouTubeSearch'] = $value;
					continue;
				case 'time':
					$values['YouTubeTime'] = $value;
					continue;
				case 'orderby':
					$values['YouTubeOrderBy'] = $value;
					continue;
			}
		}
		
		return $values;
	}
		
	static function categoryToInt($category) {
		switch ($category) {
			case 'Autos':
				return 2;
			case 'Comedy':
				return 9;
			case 'Education':
				return 27;
			case 'Entertainment':
				return 5;
			case 'Film':
				return 1;
			case 'Games':
				return 20;
			case 'Howto':
				return 3;
			case 'Music':
				return 10;
			case 'News':
				return 7;
			case 'Nonprofit':
				return 29;
			case 'People':
				return 4;
			case 'Animals':
				return 15;
			case 'Tech':
				return 28;
			case 'Sports':
				return 17;
			case 'Travel':
				return 19;
			default:
				return 0;
		}
	}
	
	static function typeToVal($type) {
		switch ($type) {
			case 'top_rated':
				return 'tr';
			case 'top_favorites':
				return 'mf';
			case 'most_viewed':
				return 'mp';
			case 'most_popular':
				return 'pop';
			case 'most_recent':
				return 'mr';
			case 'most_discussed':
				return 'md';
			case 'most_responded':
				return 'ms';
			case 'recently_featured':
				return 'rf';
			default:
				return '';
		}
	}
	
	static function orderByToVal($type) {
		switch ($type) {
			case 'date':
			case 'published':
				return 'video_date_uploaded';
			case 'viewcount':
				return 'video_view_count';
			case 'rating':
				return 'video_avg_rating';
			case 'relevance':
			default:
				return '';
		}
	}
	
	function display() {
		if ($this->selectedID) {
			$row = array(
				'ID' => "yt".$this->selectedID,
				'Location' => "http://www.youtube.com/v/".$this->selectedID."?");
			
			$this->displaySelected($row);
			
			if ($this->ajaxRequest)
				return true;
			
			$this->selectedID = 0;
			url::delargs('videoid');
		}
		
		$gallery = sql::fetch(sql::run(
			" SELECT * FROM `{" .$this->sqlOwnerTable . "}`" .
			" WHERE `ID` = '".(int)$this->selectedOwnerID."'" .
			" LIMIT 1"));
		
		if (!$gallery)
			return false;
		
		if (!$this->limit)
			$this->limit = 50;
		
		$paging = new paging($this->limit);
		
		if ($this->ajaxPaging) {
			$paging->ajax = true;
			$paging->otherArgs = "&amp;request=".$this->uriRequest .
				($this->sqlRow?
					"&amp;".strtolower($this->sqlRow)."=".$this->selectedOwnerID:
					null);
		}
		
		$paging->track(strtolower(get_class($this)).'limit');
		
		if ($this->ignorePaging && $this->limit)
			$gallery['YouTubeAPIURL'] .= "&max-results=".$this->limit;
		
		if (!$this->ignorePaging) {
			list($offset, $limit) = explode(',', $paging->limit);
			$gallery['YouTubeAPIURL'] .= "&start-index=".($offset+1) .
				"&max-results=".$limit;
		}
		
		$gdata = new GData();
		$gdata->token = $gallery['GDataToken'];
		$data = $gdata->get($gallery['YouTubeAPIURL']);
		unset($gdata);
		
		preg_match('/<openSearch:totalResults>(.*?)</is', $data, $matches);
		preg_match('/<entry.*?' .
			'<media:thumbnail.*?url=.([^ \'"]+0\.jpg).*?' .
			'<\/entry>/is', $data, $newestvideo);
	
		if (isset($matches[1]))
			$paging->setTotalItems((int)$matches[1]);
		
		if (!$paging->getStart())
			sql::run(
				" UPDATE `{" .$this->sqlOwnerTable . "}` SET" .
				" `Videos` = '" .
					(isset($matches[1])?
						(int)$matches[1]:
						0)."'," .
				(JCORE_VERSION >= '0.8'?
					" `PreviewPicURL` = '" .
						(isset($newestvideo[1])?
							$newestvideo[1]:
							null)."',":
					null) .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".(int)$this->selectedOwnerID."'");
		
		if (!$paging->items) {
			if (!isset($matches[1]) && $data)
				tooltip::display(
					sprintf(_("Couldn't fetch video list. Error: %s"),
						strip_tags($data)),
					TOOLTIP_NOTIFICATION);
			
			return false;
		}
		
		preg_match_all('/<entry.*?<updated>(.*?)<\/updated>.*?' .
			'<media:player.*?url=.([^ \'"]+).*?' .
			'<media:thumbnail.*?url=.([^ \'"]+0\.jpg).*?' .
			'<media:title.*?(\/>|>(.*?)<\/media:title>).*?' .
			'<\/entry>/is', $data, $rows);
		
		if (!$this->ajaxRequest)
			echo
				"<div class='".
					strtolower(preg_replace('/([A-Z])/', '-\\1', get_class($this))).
					" videos'>";
		
		$i = 1;
		foreach($rows[1] as $key => $row) {
			if (!$row)
				continue;
			
			preg_match('/(v\/|v=)(.*?)(\?|&|$)/', $rows[2][$key], $matches);
			
			$id = null;
			if (isset($matches[2]))
				$id = $matches[2];
			
			if (!$id)
				continue;
			
			$row = array();
			$row['ID'] = "yt".$id;
			$row['Title'] = $rows[5][$key];
			$row['Location'] = $rows[2][$key];
			$row['CapLocation'] = $rows[3][$key];
			$row['TimeStamp'] = date('Y-m-d H:i:s', strtotime($rows[1][$key]));
			$row['Views'] = 0;
			$row['_VideoNumber'] = $i;
			$row['_Link'] = url::uri('videoid').
				"&amp;request=".$this->uriRequest .
				"&amp;videoid=".urlencode($id);
			
			if ((!$row['Title'] || strlen($row['Title']) > 100) &&
				isset($rows[7][$key]))
				$row['Title'] = $rows[7][$key];
			
			$this->displayOne($row);
			
			if ($this->columns == $i) {
				echo "<div class='clear-both'></div>";
				$i = 0;
			}
			
			$i++;
		}
		
		echo
			"<div class='clear-both'></div>";
		
		if (!$this->randomize && $this->showPaging)
			$paging->display();
		
		if (!$this->ajaxRequest)
			echo
				"</div>"; //videos
		
		return $paging->items;
	}
}

class videoGalleryComments extends comments {
	var $sqlTable = 'videogallerycomments';
	var $sqlRow = 'VideoGalleryID';
	var $sqlOwnerTable = 'videogalleries';
	var $adminPath = 'admin/modules/videogallery/videogallerycomments';
	
	function __construct() {
		languages::load('videogallery');
		
		parent::__construct();
		
		$this->selectedOwner = _('Gallery');
		$this->uriRequest = "modules/videogallery/".$this->uriRequest;
		
		if ($GLOBALS['ADMIN'])
			$this->commentURL = videoGallery::getURL().
				"&videogalleryid=".admin::getPathID();
	}
	
	function __destruct() {
		languages::unload('videogallery');
	}
}

class videoGalleryIcons extends pictures {
	var $previewPicture = false;
	var $sqlTable = 'videogalleryicons';
	var $sqlRow = 'VideoGalleryID';
	var $sqlOwnerTable = 'videogalleries';
	var $sqlOwnerCountField = 'Icons';
	var $adminPath = 'admin/modules/videogallery/videogalleryicons';
	
	function __construct() {
		languages::load('videogallery');
		
		parent::__construct();
		
		$this->rootPath = $this->rootPath.'icons/';
		$this->rootURL = $this->rootURL.'icons/';
		
		$this->selectedOwner = _('Gallery');
		$this->uriRequest = "modules/videogallery/".$this->uriRequest;
	}
	
	function __destruct() {
		languages::unload('videogallery');
	}
}

class videoGallery extends modules {
	static $uriVariables = 'videoid, videogalleryid, videogallerylimit, videogalleryvideoslimit, videogalleryyoutubevideoslimit, videogalleryrating, rate, ajax, request';
	var $searchable = true;
	var $limit = 0;
	var $limitGalleries = 0;
	var $selectedID;
	var $search = null;
	var $ignorePaging = false;
	var $showPaging = true;
	var $ajaxPaging = AJAX_PAGING;
	var $ajaxRequest = null;
	var $randomizeVideos = false;
	var $videosPath;
	var $adminPath = 'admin/modules/videogallery';
	
	function __construct() {
		languages::load('videogallery');
		
		if (isset($_GET['videogalleryid']))
			$this->selectedID = (int)$_GET['videogalleryid'];
		
		if (isset($_GET['searchin']) && isset($_GET['search']) && 
			$_GET['searchin'] == 'modules/videogallery')
			$this->search = trim(strip_tags($_GET['search']));
		
		$this->videosPath = SITE_PATH.'sitefiles/media/videogallery/';
	}
	
	function __destruct() {
		languages::unload('videogallery');
	}
	
	function SQL() {
		return
			" SELECT * FROM `{videogalleries}`" .
			" WHERE !`Deactivated`" .
			(!$GLOBALS['USER']->loginok?
				" AND (!`MembersOnly` " .
				"	OR `ShowToGuests`)":
				null) .
			((int)$this->selectedID?
				" AND `SubGalleryOfID` = '".(int)$this->selectedID."'":
				" AND !`SubGalleryOfID`") .
			" ORDER BY `OrderID`, `TimeStamp` DESC, `ID`";
	}
	
	function installSQL() {
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{videogalleries}` (" .
			" `ID` smallint(5) unsigned NOT NULL auto_increment," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `Description` mediumtext NULL," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Path` varchar(255) NOT NULL default ''," .
			" `URL` varchar(255) NOT NULL default ''," .
			" `YouTubeAPIURL` VARCHAR( 255 ) NOT NULL DEFAULT ''," .
			" `GDataToken` VARCHAR( 100 ) NOT NULL DEFAULT ''," .
			" `SubGalleryOfID` smallint(5) unsigned NOT NULL default '0'," .
			" `Preview` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'," .
			(JCORE_VERSION >= '0.8'?
				" `PreviewPicURL` VARCHAR( 255 ) NOT NULL DEFAULT '',":
				null) .
			" `Comments` smallint(5) unsigned NOT NULL default '0'," .
			" `Videos` mediumint(8) unsigned NOT NULL default '0'," .
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
			" KEY `Path` (`Path`, `UserID`, `TimeStamp`,`SubGalleryOfID`,`Deactivated`,`OrderID`)," .
			" KEY `MembersOnly` (`MembersOnly`, `ShowToGuests`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{videogalleryicons}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" `Location` varchar(255) NOT NULL default ''," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `URL` varchar(255) NOT NULL default ''," .
			" `VideoGalleryID` smallint(5) unsigned NOT NULL default '1'," .
			" `Views` int(10) unsigned NOT NULL default '0'," .
			" `Thumbnail` tinyint(1) unsigned NOT NULL default '0'," .
			" KEY `ID` (`ID`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `VideoGalleryID` (`VideoGalleryID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{videogallerycomments}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `VideoGalleryID` smallint(5) unsigned NOT NULL default '0'," .
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
			" KEY `VideoGalleryID` (`VideoGalleryID`,`UserName`,`UserID`)," .
			" KEY `Pending` (`Pending`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{videogallerycommentsratings}` (" .
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
			" CREATE TABLE IF NOT EXISTS `{videogalleryvideos}` (" .
			" `ID` int(10) unsigned NOT NULL auto_increment," .
			" `OrderID` mediumint(9) NOT NULL default '0'," .
			" `Location` varchar(255) NOT NULL default ''," .
			" `CapLocation` varchar(255) NOT NULL default ''," .
			" `Title` varchar(255) NOT NULL default ''," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `VideoGalleryID` smallint(5) unsigned NOT NULL default '1'," .
			" `Views` int(10) unsigned NOT NULL default '0'," .
			" KEY `ID` (`ID`)," .
			" KEY `OrderID` (`OrderID`)," .
			" KEY `TimeStamp` (`TimeStamp`)," .
			" KEY `VideoGalleryID` (`VideoGalleryID`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		sql::run(
			" CREATE TABLE IF NOT EXISTS `{videogalleryratings}` (" .
			" `VideoGalleryID` smallint(5) unsigned NOT NULL default '0'," .
			" `UserID` mediumint(8) unsigned NOT NULL default '0'," .
			" `IP` bigint(20) NOT NULL default '0'," .
			" `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP," .
			" `Rating` tinyint(1) NOT NULL default '0'," .
			" KEY `Rating` (`Rating`)," .
			" KEY `VideoGalleryID` (`VideoGalleryID`,`UserID`,`IP`,`TimeStamp`)" .
			" ) ENGINE=MyISAM;");
		
		if (sql::display())
			return false;
			
		return true;
	}
	
	function installFiles() {
		$css = 
			".videogallery-selected {\n" .
			"	margin-bottom: 15px;\n" .
			"}\n" .
			"\n" .
			".videogallery-title {\n" .
			"	margin: 0;\n" .
			"}\n" .
			"\n" .
			".video-preview {\n" .
			"	float: left;\n" .
			"	margin: 10px;\n" .
			"}\n" .
			"\n" .
			".videogallery-details {\n" .
			"	margin: 3px 0 7px 0;\n" .
			"}\n" .
			"\n" .
			".videogallery-rating {\n" .
			"	float: right;\n" .
			"}\n" .
			"\n" .
			".videogallery-links a {\n" .
			"	display: inline-block;\n" .
			"	text-decoration: none;\n" .
			"	padding: 5px 0px 5px 20px;\n" .
			"	background: url(\"http://icons.jcore.net/16/link.png\") 0px 50% no-repeat;\n" .
			"	margin-right: 10px;\n" .
			"}\n" .
			"\n" .
			".videogallery-links .back {\n" .
			"	background-image: url(\"http://icons.jcore.net/16/doc_page_previous.png\");\n" .
			"}\n" .
			"\n" .
			".videogallery-links .videos {\n" .
			"	background-image: url(\"http://icons.jcore.net/16/films.png\");\n" .
			"}\n" .
			"\n" .
			".videogallery-links .comments {\n" .
			"	background-image: url(\"http://icons.jcore.net/16/comment.png\");\n" .
			"}\n" .
			"\n" .
			".videogallery-folder {\n" .
			"	padding: 5px;\n" .
			"	margin: 1px 0px 5px 0px;\n" .
			"}\n" .
			"\n" .
			".videogallery-folder .videogallery-title,\n" .
			".videogallery-folder .videogallery-details,\n" .
			".videogallery-folder .videogallery-description,\n" .
			".videogallery-folder .videogallery-links\n" .
			"{\n" .
			"	margin-left: 60px;\n" .
			"}\n" .
			"\n" .
			".videogallery-folder .video-title,\n" .
			".videogallery-folder .video-details,\n" .
			".videogallery-folder .picture-title,\n" .
			".videogallery-folder .picture-details\n" .
			"{\n" .
			"	display: none;\n" .
			"}\n" .
			"\n" .
			".videogallery-folder .video-preview,\n" .
			".videogallery-folder .video-preview a,\n" .
			".videogallery-folder .picture\n" .
			"{\n" .
			"	width: auto;\n" .
			"	height: auto;\n" .
			"	margin: 0;\n" .
			"}\n" .
			"\n" .
			".videogallery-folder .video-preview img,\n" .
			".videogallery-folder .picture img\n" .
			"{\n" .
			"	width: 48px;\n" .
			"	height: auto;\n" .
			"}\n" .
			"\n" .
			".videogallery-folder-icon {\n" .
			"	display: block;\n" .
			"	float: left;\n" .
			"	width: 48px;\n" .
			"	height: 48px;\n" .
			"	background: url(\"http://icons.jcore.net/48/folder-videos.png\");\n" .
			"}\n" .
			"\n" .
			".videogallery-folder-icon.subfolders {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/folder-subfolders-videos.png\");\n" .
			"}\n" .
			"\n" .
			".videogallery-folder-icon.icon,\n" .
			".videogallery-folder-icon.preview\n" .
			"{\n" .
			"	background-image: none;\n" .
			"}\n" .
			"\n" .
			".videogallery-selected .videogallery-folder-icon {\n" .
			"	width: auto;\n" .
			"	height: auto;\n" .
			"	margin-right: 15px;\n" .
			"}\n" .
			"\n" .
			".as-modules-videogallery a {\n" .
			"	background-image: url(\"http://icons.jcore.net/48/folder-videos.png\");\n" .
			"}\n";
		
		if (!files::save(SITE_PATH.'template/modules/css/videogallery.css', $css, true)) {
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
			" FROM `{videogalleries}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				_('New Folder'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			_('Pages / Posts'), 
			'?path=' .
			(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));
		favoriteLinks::add(
			_('Settings'), 
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
		
		$form->add(
			_('Display Preview'),
			'Preview',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
		$form->addAdditionalText(_("(will show the latest video as icon)"));
		
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
				_('YouTube Options'),
				null,
				FORM_OPEN_FRAME_CONTAINER);
			
			$form->add(
				_('Channel URL'),
				'YouTubeChannelURL',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 300px;');
			$form->setTooltipText(_("e.g. http://youtube.com/user/Channel_ID"));
			
			$form->add(
				_('Tags'),
				'YouTubeTags',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 150px;');
			$form->setTooltipText(_("e.g. foo, bar"));
			
			$form->add(
				__('Search'),
				'YouTubeSearch',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 100px;');
			$form->setTooltipText(_("e.g. puppy"));
			
			$form->add(
				_('Category'),
				'YouTubeCategory',
				FORM_INPUT_TYPE_SELECT);
			
			$form->addValue(
				"", "");
			$form->addValue(
				"Autos", _("Autos & Vehicles"));
			$form->addValue(
				"Comedy", _("Comedy"));
			$form->addValue(
				"Education", _("Education"));
			$form->addValue(
				"Entertainment", _("Entertainment"));
			$form->addValue(
				"Film", _("Film & Animation"));
			$form->addValue(
				"Games", _("Gaming"));
			$form->addValue(
				"Howto", _("Howto & Style"));
			$form->addValue(
				"Music", _("Music"));
			$form->addValue(
				"News", _("News & Politics"));
			$form->addValue(
				"Nonprofit", _("Nonprofits & Activism"));
			$form->addValue(
				"People", _("People & Blogs"));
			$form->addValue(
				"Animals", _("Pets & Animals"));
			$form->addValue(
				"Tech", _("Science & Technology"));
			$form->addValue(
				"Sports", _("Sports"));
			$form->addValue(
				"Travel", _("Travel & Events"));
			
			$form->add(
				_('Videos'),
				'YouTubeVideos',
				FORM_INPUT_TYPE_SELECT);
			
			$form->addValue(
				"", "");
			$form->addValue(
				"uploads", _("My Videos"));
			$form->addValue(
				"favorites", _("My Favorites"));
			$form->addValue(
				"top_rated", _("Top rated"));
			$form->addValue(
				"top_favorites", _("Top favorites"));
			$form->addValue(
				"most_viewed", _("Most viewed"));
			$form->addValue(
				"most_popular", _("Most popular"));
			$form->addValue(
				"most_recent", _("Most recent"));
			$form->addValue(
				"most_discussed", _("Most discussed"));
			$form->addValue(
				"most_responded", _("Most responded"));
			$form->addValue(
				"recently_featured", _("Recently featured"));
			$form->addValue(
				"watch_on_mobile", _("Videos for mobile"));
			
			$form->add(
				_('Time'),
				'YouTubeTime',
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
				'YouTubeOrderBy',
				FORM_INPUT_TYPE_SELECT);
			
			$form->addValue(
				"", "");
			$form->addValue(
				"date", _("Date"));
			$form->addValue(
				"published", _("Published"));
			$form->addValue(
				"relevance", _("Relevance"));
			$form->addValue(
				"viewcount", _("Views"));
			$form->addValue(
				"rating", _("Rating"));
			
			$gdata = new GData();
			$gdata->scopes = array("http://gdata.youtube.com");
			
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
		
		$form->add(
			__('Link to URL'),
			'URL',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 300px;');
		$form->setValueType(FORM_VALUE_TYPE_URL);
		$form->setTooltipText(__("e.g. http://domain.com"));
		
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
					" UPDATE `{videogalleries}` " .
					" SET `OrderID` = '".(int)$ovalue."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".(int)$oid."'");
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
			foreach(videoGallery::getBackTraceTree($form->get('SubGalleryOfID')) as $gallery) {
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
					" SELECT `Path` FROM `{videogalleries}`" .
					" WHERE `ID` = ".(int)$form->get('SubGalleryOfID')));
				
				$path .= $subgalleryof['Path'].'/'; 
			}
			
			$path .= url::genPathFromString($form->get('Title'));
			
			$form->set('Path', $path);
		}
				
		if (JCORE_VERSION >= '0.7') {
			$form->add(
				'YouTube API URL',
				'YouTubeAPIURL',
				FORM_INPUT_TYPE_HIDDEN);
			
			$form->set('YouTubeAPIURL', 
				videoGalleryYouTubeVideos::genAPIURL($form->getPostArray()));
		}
		
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray()))
				return false;
				
			tooltip::display(
				_("Gallery has been successfully updated.")." " .
				(modules::getOwnerURL('videoGallery')?
					"<a href='".videoGallery::getURL($id).
						"&amp;videogalleryid=".$id."' target='_blank'>" .
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
		
		if (!$newid = $this->add($form->getPostArray()))
			return false;
				
		tooltip::display(
			_("Gallery has been successfully created.")." " .
			(modules::getOwnerURL('videoGallery')?
				"<a href='".videoGallery::getURL().
					"&amp;videogalleryid=".$newid."' target='_blank'>" .
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
				_("Videos")."</span></th>";
		
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
					"?path=".admin::path()."/".$row['ID']."/videogallerycomments'>" .
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
				"<a class='admin-link videos' " .
					"title='".htmlspecialchars(_("Videos"), ENT_QUOTES) .
						" (".$row['Videos'].")' ";
		
		if (JCORE_VERSION >= '0.7' && $row['YouTubeAPIURL']) {
			echo
					"target='_blank' href='" .
						videoGalleryYouTubeVideos::genPreviewURL($row['YouTubeAPIURL'])."'";
		} else  {
			echo
					"href='".url::uri('ALL') .
						"?path=".admin::path()."/".$row['ID']."/videogalleryvideos'";
		}
		
		echo
					">" .
					(ADMIN_ITEMS_COUNTER_ENABLED && $row['Videos']?
						"<span class='counter'>" .
							"<span>" .
								"<span>" .
								$row['Videos']."" .
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
						"?path=".admin::path()."/".$row['ID']."/videogalleryicons'>" .
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
			calendar::dateTime($row['TimeStamp']) ." ". 
			$GLOBALS['USER']->constructUserName($user, __('by %s')));
		
		if ($row['URL'])
			admin::displayItemData(
				__("Link to URL"),
				"<a href='".$row['URL']."' target='_blank'>" . 
					$row['URL'] . 
				"</a>");
		
		if (JCORE_VERSION >= '0.7' && $row['DisplayIcons'])
			admin::displayItemData(
				_("Show Icons"),
				__("Yes"));
		
		if ($row['Preview'])
			admin::displayItemData(
				_("Display Preview"),
				__("Yes"));
		
		if ($row['Columns'])
			admin::displayItemData(
				_("Columns"),
				$row['Columns']);
		
		if (JCORE_VERSION >= '0.7' && $row['YouTubeAPIURL']) {
			$picasa = videoGalleryYouTubeVideos::parseAPIURL(
				$row['YouTubeAPIURL']);
			
			if ($picasa['YouTubeChannelURL'])
				admin::displayItemData(
					_("YouTube Channel URL"),
					$picasa['YouTubeChannelURL']);
			
			if ($picasa['YouTubeTags'])
				admin::displayItemData(
					_("YouTube Video Tags"),
					$picasa['YouTubeTags']);
			
			if ($picasa['YouTubeSearch'])
				admin::displayItemData(
					_("YouTube Search"),
					$picasa['YouTubeSearch']);
			
			if ($picasa['YouTubeCategory'])
				admin::displayItemData(
					_("YouTube Video Category"),
					ucfirst($picasa['YouTubeCategory']));
			
			if ($picasa['YouTubeVideos'])
				admin::displayItemData(
					_("YouTube Videos"),
					ucfirst($picasa['YouTubeVideos']));
			
			if ($picasa['YouTubeTime'])
				admin::displayItemData(
					_("YouTube Video Time"),
					ucfirst($picasa['YouTubeTime']));
			
			if ($picasa['YouTubeOrderBy'])
				admin::displayItemData(
					_("YouTube Order By"),
					ucfirst($picasa['YouTubeOrderBy']));
		}
		
		if (JCORE_VERSION >= '0.7' && $row['GDataToken'])
			admin::displayItemData(
				_("GData Auth Token"),
				$row['GDataToken']);
		
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
			
			if (!$this->userPermissionIDs) {
				$subrows = sql::run(
					" SELECT * FROM `{videogalleries}`" .
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
			_('Video Gallery Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		if (modules::displayAdmin())
			return;
		
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
		
		$verifyok = false;
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			$verifyok = $this->verifyAdmin($form);
		}
		
		foreach(videoGallery::getTree() as $row) {
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
			" SELECT * FROM `{videogalleries}`" .
			" WHERE 1" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				" AND !`SubGalleryOfID`") .
			" ORDER BY `OrderID`, `TimeStamp` DESC, `ID`");
		
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
				_("No galleries found."),
				TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{videogalleries}` " .
					" WHERE `ID` = '".$id."'" .
					($this->userPermissionIDs?
						" AND `ID` IN (".$this->userPermissionIDs.")":
						null)));
				
				if (JCORE_VERSION >= '0.7' && $row['YouTubeAPIURL'])
					$row += videoGalleryYouTubeVideos::parseAPIURL($row['YouTubeAPIURL']);
				
				$form->setValues($row);
				
				$user = $GLOBALS['USER']->get($row['UserID']);
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
				" SELECT `OrderID` FROM `{videogalleries}` " .
				" WHERE `SubGalleryOfID` = '".(int)$values['SubGalleryOfID']."'" .
				" ORDER BY `OrderID` DESC"));
			
			$values['OrderID'] = (int)$row['OrderID']+1;
			
		} else {
			sql::run(
				" UPDATE `{videogalleries}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `SubGalleryOfID` = '".(int)$values['SubGalleryOfID']."'" .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		if ((int)$values['SubGalleryOfID']) {
			$parentgallery = sql::fetch(sql::run(
				" SELECT * FROM `{videogalleries}`" .
				" WHERE `ID` = '".(int)$values['SubGalleryOfID']."'"));
			
			if ($parentgallery['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;
			
			if ($parentgallery['MembersOnly'] && !$values['MembersOnly'])
				$values['MembersOnly'] = true;
			
			if ($parentgallery['ShowToGuests'] && !$values['ShowToGuests'])
				$values['ShowToGuests'] = true;
		}
		
		$newid = sql::run(
			" INSERT INTO `{videogalleries}` SET ".
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
			" `URL` = '".
				sql::escape($values['URL'])."'," .
			(JCORE_VERSION >= '0.7'?
				" `YouTubeAPIURL` = '".
					sql::escape($values['YouTubeAPIURL'])."'," .
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
			" `Preview` = '".
				(int)$values['Preview']."'," .
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
				sprintf(_("Gallery couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (JCORE_VERSION >= '0.7' && $values['YouTubeAPIURL']) {
			$gdata = new GData();
			$gdata->token = $values['GDataToken'];
			$data = $gdata->get($values['YouTubeAPIURL'] .
				"&max-results=1");
			unset($gdata);
			
			preg_match('/<openSearch:totalResults>(.*?)</is', $data, $matches);
			preg_match('/<entry.*?' .
				'<media:thumbnail.*?url=.([^ \'"]+0\.jpg).*?' .
				'<\/entry>/is', $data, $newestvideo);
			
			sql::run(
				" UPDATE `{videogalleries}` SET" .
				" `Videos` = '" .
					(isset($matches[1])?
						(int)$matches[1]:
						0)."'," .
				(JCORE_VERSION >= '0.8'?
					" `PreviewPicURL` = '" .
						(isset($newestvideo[1])?
							$newestvideo[1]:
							null)."',":
					null) .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".(int)$newid."'");
		}
		
		$this->protectVideos();
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		$gallery = sql::fetch(sql::run(
			" SELECT * FROM `{videogalleries}`" .
			" WHERE `ID` = '".$id."'"));
			
		if ((int)$values['SubGalleryOfID'] && 
			(int)$values['SubGalleryOfID'] != $gallery['SubGalleryOfID']) 
		{
			$parentgallery = sql::fetch(sql::run(
				" SELECT * FROM `{videogalleries}`" .
				" WHERE `ID` = '".(int)$values['SubGalleryOfID']."'"));
			
			if ($parentgallery['Deactivated'] && !$values['Deactivated'])
				$values['Deactivated'] = true;
			
			if ($parentgallery['MembersOnly'] && !$values['MembersOnly'])
				$values['MembersOnly'] = true;
			
			if ($parentgallery['ShowToGuests'] && !$values['ShowToGuests'])
				$values['ShowToGuests'] = true;
		}
		
		sql::run(
			" UPDATE `{videogalleries}` SET ".
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
			" `URL` = '".
				sql::escape($values['URL'])."'," .
			(JCORE_VERSION >= '0.7'?
				" `YouTubeAPIURL` = '".
					sql::escape($values['YouTubeAPIURL'])."'," .
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
			" `Preview` = '".
				(int)$values['Preview']."'," .
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
				sprintf(_("Gallery couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		foreach(videoGallery::getTree((int)$id) as $row) {
			$updatesql = null;
			
			if (($gallery['Deactivated'] && !$values['Deactivated']) ||
				(!$gallery['Deactivated'] && $values['Deactivated'])) 
			{
				if (!$row['Deactivated'] && $values['Deactivated'])
					$updatesql[] = " `Deactivated` = 1";
				if ($row['Deactivated'] && !$values['Deactivated'])
					$updatesql[] = " `Deactivated` = 0";
			}
			
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
			
			if ($updatesql)
				sql::run(
					" UPDATE `{videogalleries}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
		}
		
		foreach(videoGallery::getBackTraceTree((int)$id) as $row) {
			$updatesql = null;
			
			if ($row['Deactivated'] && !$values['Deactivated'])
				$updatesql[] = " `Deactivated` = 0";
			
			if ($row['MembersOnly'] && !$values['MembersOnly'])
				$updatesql[] = " `MembersOnly` = 0";
			
			if ($updatesql)
				sql::run(
					" UPDATE `{videogalleries}` SET" .
					implode(',', $updatesql) .
					" WHERE `ID` = '".$row['ID']."'");
		}

		if (JCORE_VERSION >= '0.7' && $values['YouTubeAPIURL']) {
			$gdata = new GData();
			$gdata->token = $values['GDataToken'];
			$data = $gdata->get($values['YouTubeAPIURL'] .
				"&max-results=1");
			unset($gdata);
			
			preg_match('/<openSearch:totalResults>(.*?)</is', $data, $matches);
			preg_match('/<entry.*?' .
				'<media:thumbnail.*?url=.([^ \'"]+0\.jpg).*?' .
				'<\/entry>/is', $data, $newestvideo);
			
			sql::run(
				" UPDATE `{videogalleries}` SET" .
				" `Videos` = '" .
					(isset($matches[1])?
						(int)$matches[1]:
						0)."'," .
				(JCORE_VERSION >= '0.8'?
					" `PreviewPicURL` = '" .
						(isset($newestvideo[1])?
							$newestvideo[1]:
							null)."',":
					null) .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".(int)$id."'");
		}
		
		$this->protectVideos();
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		$galleryvideos = new videoGalleryVideos();
		$gallerycomments = new videoGalleryComments();
		$galleryids = array($id);
		
		foreach(videoGallery::getTree((int)$id) as $row)
			$galleryids[] = $row['ID'];
		
		
		foreach($galleryids as $galleryid) {
			$rows = sql::run(
				" SELECT * FROM `{videogalleryvideos}` " .
				" WHERE `VideoGalleryID` = '".$galleryid."'");
			
			while($row = sql::fetch($rows))
				$galleryvideos->delete($row['ID']);
			
			$rows = sql::run(
				" SELECT * FROM `{videogallerycomments}` " .
				" WHERE `VideoGalleryID` = '".$galleryid."'");
			
			while($row = sql::fetch($rows))
				$gallerycomments->delete($row['ID']);
			
			sql::run(
				" DELETE FROM `{videogalleryratings}` " .
				" WHERE `VideoGalleryID` = '".$galleryid."'");
			
			sql::run(
				" DELETE FROM `{videogalleries}` " .
				" WHERE `ID` = '".(int)$id."'");
		}
		
		unset($gallerycomments);
		unset($galleryvideos);
		
		if (JCORE_VERSION >= '0.6') {
			$icons = new videoGalleryIcons();
			
			$rows = sql::run(
				" SELECT * FROM `{videogalleryicons}`" .
				" WHERE `VideoGalleryID` = '".$id."'");
			
			while($row = sql::fetch($rows))
				$icons->delete($row['ID']);
			
			unset($icons);
		}
		
		$this->protectVideos();
		
		return true;
	}
	
	function protectVideos() {
		if (!$this->videosPath)
			return false;
		
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows` FROM `{videogalleries}` " .
			" WHERE `MembersOnly` = 1" .
			" LIMIT 1"));
			
		if ($row['Rows']) {
			if (!files::exists($this->videosPath.'.htaccess') &&
				!files::create($this->videosPath.'.htaccess',
					'deny from all'))
			{
				tooltip::display(
					_("Directory couldn't be protected!")." " .
					sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
						$this->videosPath),
					TOOLTIP_ERROR);
				
				return false;
			}
			
			return true;
		}
		
		if (files::exists($this->videosPath.'.htaccess'))
			files::delete($this->videosPath.'.htaccess');
		
		return true;
	}
	
	static function getTree($galleryid = 0, $firstcall = true,
		&$tree = array('Tree' => array(), 'PathDeepnes' => 0)) 
	{
		$rows = sql::run(
			" SELECT *, `SubGalleryOfID` AS `SubItemOfID` " .
			" FROM `{videogalleries}` " .
			($galleryid?
				" WHERE `SubGalleryOfID` = '".$galleryid."'":
				" WHERE !`SubGalleryOfID`") .
			" ORDER BY `OrderID`, `TimeStamp` DESC, `ID`");
		
		while($row = sql::fetch($rows)) {
			$row['PathDeepnes'] = $tree['PathDeepnes'];
			$tree['Tree'][] = $row;
			
			$tree['PathDeepnes']++;
			videoGallery::getTree($row['ID'], false, $tree);
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
			" FROM `{videogalleries}` " .
			" WHERE `ID` = '".(int)$id."'"));
		
		if (!$row)
			return array();
		
		if ($row['SubItemOfID'])	
			videoGallery::getBackTraceTree($row['SubItemOfID'], false, $tree);
		
		$row['PathDeepnes'] = $tree['PathDeepnes'];
		$tree['Tree'][] = $row;
		$tree['PathDeepnes']++;
		
		if ($firstcall)
			return $tree['Tree'];
	}
	
	// ************************************************   Client Part
	static function getURL($id = 0) {
		$url = modules::getOwnerURL('videoGallery', $id);
		
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
			
			if ($permission['PermissionType'] != USER_PERMISSION_TYPE_WRITE ||
				$permission['PermissionIDs'])
			{
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
			"<div class='videogallery-folder-icon preview'>";
		
		if (isset($row['PreviewPicURL']) && $row['PreviewPicURL'])
			$videos = new videoGalleryVideos();
		elseif (isset($row['YouTubeAPIURL']) && $row['YouTubeAPIURL'])
			$videos = new videoGalleryYouTubeVideos();
		else
			$videos = new videoGalleryVideos();
		
		$videos->selectedOwnerID = $row['ID'];
		$videos->showPaging = false;
		$videos->limit = 1;
		
		if (JCORE_VERSION >= '0.6' && $row['URL'])
			$videos->customLink = url::generateLink($row['URL']);
		else
			$videos->customLink = $row['_Link'];
		
		if (isset($row['PreviewPicURL']) && $row['PreviewPicURL'])
			$videos->displayGalleryPreview($row);
		else
			$videos->display();
		
		unset($videos);
		
		echo
			"</div>";
	}
	
	function displayIcon($row) {
		if (JCORE_VERSION >= '0.6' && $row['Icons']) {
			echo
				"<div class='videogallery-folder-icon icon'>";
		
			$icons = new videoGalleryIcons();
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
			"<a href='".$row['_Link']."' " .
				"title='".htmlspecialchars($row['Title'], ENT_QUOTES)."' " .
				"class='videogallery-folder-icon" .
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
			"<a href='".url::uri(videoGallery::$uriVariables)."'>" .
				_("Videos").
			"</a>";
			
		foreach(videoGallery::getBackTraceTree($row['ID']) as $gallery) {
			$href = url::uri(videoGallery::$uriVariables).
				"&amp;videogalleryid=".$gallery['ID'];
			
			echo 
				"<span class='path-separator'> / </span>" .
				"<a href='".$href."'>".
					$gallery['Title'] .
				"</a>";
		}
	}
	
	function displayDetails(&$row) {
		$user = $GLOBALS['USER']->get($row['UserID']);
		
		echo
			calendar::datetime($row['TimeStamp'])." ";
					
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
	
	function displayVideos(&$row) {
		if ((!isset($row['YouTubeAPIURL']) || !$row['YouTubeAPIURL']) &&
			!$row['Videos'])
			return;
		
		if (isset($row['YouTubeAPIURL']) && $row['YouTubeAPIURL'])
			$videos = new videoGalleryYouTubeVideos();
		else
			$videos = new videoGalleryVideos();
		
		if (isset($row['MembersOnly']) && $row['MembersOnly'] && 
			!$GLOBALS['USER']->loginok)
			$videos->customLink = 
				"javascript:jQuery.jCore.tooltip.display(\"" .
				"<div class=\\\"tooltip error\\\"><span>" .
				htmlspecialchars(_("You need to be logged in to view this video. " .
					"Please login or register."), ENT_QUOTES)."</span></div>\", true)";
		
		$videos->ignorePaging = $this->ignorePaging;
		$videos->showPaging = $this->showPaging;
		$videos->ajaxPaging = $this->ajaxPaging;
		$videos->selectedOwnerID = $row['ID'];
		$videos->columns = $row['Columns'];
		$videos->limit = $row['Limit'];
		$videos->randomize = $this->randomizeVideos;
		
		if ($this->limit)
			$videos->limit = $this->limit;
	
		$videos->display();
		unset($videos);
	}
	
	function displayComments(&$row) {
		$gallerycomments = new videoGalleryComments();
		$gallerycomments->guestComments = $row['EnableGuestComments'];
		$gallerycomments->selectedOwnerID = $row['ID'];
		$gallerycomments->display();
		unset($gallerycomments);
	}
	
	function displayRating(&$row) {
		$rating = new videoGalleryRating();
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
				"' class='videos comment'>" .
				"<span>".
				($row['_SubGalleries']?
					_("Videos / Galleries"):
					_("Videos")).
				"</span> " .
				"<span>" .
				"(".($row['Videos']+$row['_SubGalleries']).")" .
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
		$row['_Link'] = url::uri(videoGallery::$uriVariables).
			"&amp;videogalleryid=".$row['ID'];
		
		$row['_SubGalleries'] = sql::count(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{videogalleries}`" .
			" WHERE !`Deactivated`" .
			" AND `SubGalleryOfID` = '".(int)$row['ID']."'");
		
		echo 
			"<div" .
				(JCORE_VERSION < '0.6'?
					" id='videogallery".$row['ID']."'":
					null) .
				" class='videogallery-folder" .
				($row['SubGalleryOfID']?
					" videogallery-sub-folder":
					null) .
				($row['_SubGalleries']?
					" videogallery-has-sub-folders":
					null) .
				" videogallery".$row['ID'] .
				" rounded-corners'>";
		
		if ($row['Preview'])
			$this->displayPreviewIcon($row);
		else
			$this->displayIcon($row);
				
		echo
				"<h3 class='videogallery-title'>";
		
		$this->displayTitle($row);
		
		echo
				"</h3>" .
				"<div class='videogallery-details comment'>";
		
		$this->displayDetails($row);
			
		echo
				"</div>";
		
		if ($row['Description']) {
			echo
				"<div class='videogallery-description'>";
			
			$this->displayDescription($row);
			
			echo
				"</div>";
		}
		
		if ($row['EnableRating']) {
			echo
				"<div class='videogallery-rating'>";
			
			$this->displayRating($row);
		
			echo
				"</div>";
		}
		
		echo
				"<div class='videogallery-links'>";
		
		$this->displayFunctions($row);
			
		echo
				"</div>" .
				"<div class='clear-both'></div>" .
			"</div>";
	}
	
	function displaySelected(&$row) {
		if (!videoGallery::verifyPermission($row)) {
			$this->displayLogin();
			return false;
		}
		
		echo 
			"<div class='videogallery videogallery".$row['ID']."'>" .
				"<div class='videogallery-selected'>" .
					"<h3 class='videogallery-title'>";
		
		$this->displaySelectedTitle($row);
		
		echo
					"</h3>";
	
		if ($row['EnableRating']) {
			echo
					"<div class='videogallery-rating'>";
			
			$this->displayRating($row);
			
			echo
					"</div>";
		}
			
		echo
					"<div class='videogallery-details comment'>";
		
		$this->displayDetails($row);
		
		echo
					"</div>";
	
		if (JCORE_VERSION >= '0.7' && $row['DisplayIcons'] && $row['Icons'])
			$this->displayIcon($row);
		
		if ($row['Description']) {
			echo
					"<div class='videogallery-description'>";
			
			$this->displayDescription($row);
			
			echo
					"</div>";
		}
		
		echo
				"</div>" .
				"<div class='clear-both'></div>";
		
		$this->displayGalleries();
		$this->displayVideos($row);
			
		echo 
				"<div class='clear-both'></div>";
		
		if ($row['EnableComments'])
			$this->displayComments($row);
		
		echo 
			"</div>"; //.videogallery
			
		return true;
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		if (preg_match('/(^|\/)rand($|\/)/', $this->arguments, $matches)) {
			$this->arguments = preg_replace('/(^|\/)rand($|\/)/', '\2', $this->arguments);
			$this->randomizeVideos = true;
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
		
		$gallery = sql::fetch(sql::run(
			" SELECT * FROM `{videogalleries}` " .
			" WHERE !`Deactivated`" .
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
		$videos = new videoGalleryVideos();
		
		$videos->limit = $this->limit;
		$videos->search = $this->search;
		
		ob_start();
		$itemsfound = $videos->display();
		$content = ob_get_contents();
		ob_end_clean();
		
		unset($videos);
		url::displaySearch($this->search, $itemsfound);
	
		echo
			"<div class='videogallery'>" .
			$content .
			"</div>";
		
		return $itemsfound;
	}
	
	function displayGalleries() {
		$paging = new paging($this->limitGalleries);
		
		if ($this->ajaxPaging) {
			$paging->ajax = true;
			$paging->otherArgs = "&amp;request=modules/videogallery";
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
				"<div class='videogallery-folders'>";
		
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
		
		if ((int)$this->selectedID) {
			$row = sql::fetch(sql::run(
				" SELECT * FROM `{videogalleries}`" .
				" WHERE !`Deactivated`" .
				" AND `ID` = '".(int)$this->selectedID."'" .
				" LIMIT 1"));
				
			return $this->displaySelected($row);
		}
		
		if ($this->search)
			return $this->displaySearch();
		
		echo 
			"<div class='videogallery'>";
		
		$items = $this->displayGalleries();
		
		echo
			"</div>";
			
		return $items;
	}
}

?>