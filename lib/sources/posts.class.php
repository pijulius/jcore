<?php

/***************************************************************************
 *            posts.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/

include_once('lib/paging.class.php');
include_once('lib/calendar.class.php');
include_once('lib/rss.class.php');
include_once('lib/ixr.class.php');
include_once('lib/contentcodes.class.php');
include_once('lib/comments.class.php');
include_once('lib/attachments.class.php');
include_once('lib/pictures.class.php');

class _postsForm extends dynamicForms {
	function __construct() {
		parent::__construct(
			__('Posts'), 'posts');
		
		$this->textsDomain = 'messages';
	}
	
	function verify($customdatahandling = true) {
		if (!parent::verify(true))
			return false;
		
		return true;
	}
}

class _postsCalendar extends monthCalendar {
	var $searchURL = null;
	var $menuItemID = null;
	var $weekDaysFormat = 'D';
	
	function __construct($menuid = null) {
		parent::__construct();
		
		$this->uriRequest = "posts/" .
			($menuid?
				$menuid."/":
				null) .
			$this->uriRequest;
		
		if (isset($_GET['searchin']) && isset($_GET['search']) && 
			$_GET['searchin'] == 'posts' && !isset($_GET['postscalendartime']))
		{
			$search = trim(strip_tags($_GET['search']));
			
			if (preg_match('/.*?:date=([0-9\-]+)/', $search))
				$this->time = strtotime(preg_replace('/.*?:date=([0-9\-]+)/', '\1', 
					$search));
		}
	}
	
	function displayDay($time) {
		$posts = sql::rows(sql::run(
			" SELECT `ID` FROM `{posts}`" .
			" WHERE `TimeStamp` LIKE '".date('Y-m-d', $time)."%'" .
			($this->menuItemID?
				" AND `MenuItemID` = '".(int)$this->menuItemID."'":
				null) .
			" LIMIT 1"));
		
		if ($posts)
			echo "<a href='".$this->searchURL."&amp;search=:date=".
					date('Y-m-d', $time) .
					"&amp;searchin=posts'>";
		
		parent::displayDay($time);
		
		if ($posts)
			echo "</a>";
	}
	
	function display() {
		if (!$this->menuItemID)
			$this->menuItemID = url::getPathID(0, $this->uriRequest);
		
		$this->searchURL = modules::getOwnerURL('Search');
		
		if (!$this->searchURL)
			$this->searchURL = url::site()."index.php?";
		
		parent::display();
	}
}

class _postRating extends starRating {
	var $sqlTable = 'postratings';
	var $sqlRow = 'PostID';
	var $sqlOwnerTable = 'posts';
	var $adminPath = 'admin/content/menuitems/posts/postrating';
	
	function __construct() {
		parent::__construct();
		
		$this->selectedOwner = __('Post');
		$this->uriRequest = "posts/".$this->uriRequest;
	}
}

class _postComments extends comments {
	var $sqlTable = 'postcomments';
	var $sqlRow = 'PostID';
	var $sqlOwnerTable = 'posts';
	var $adminPath = 'admin/content/menuitems/posts/postcomments';
	
	function __construct() {
		parent::__construct();
		
		$this->selectedOwner = __('Post');
		$this->uriRequest = "posts/".$this->uriRequest;
		
		if ($GLOBALS['ADMIN'])
			$this->commentURL = SITE_URL .
				"?menuid=".admin::getPathID(2) . 
				"&postid=".admin::getPathID();
	}
}

class _postAttachments extends attachments {
	var $sqlTable = 'postattachments';
	var $sqlRow = 'PostID';
	var $sqlOwnerTable = 'posts';
	var $adminPath = 'admin/content/menuitems/posts/postattachments';
	
	function __construct() {
		parent::__construct();
		
		$this->selectedOwner = __('Post');
		$this->uriRequest = "posts/".$this->uriRequest;
	}
}

class _postPictures extends pictures {
	var $sqlTable = 'postpictures';
	var $sqlRow = 'PostID';
	var $sqlOwnerTable = 'posts';
	var $adminPath = 'admin/content/menuitems/posts/postpictures';
	
	function __construct() {
		parent::__construct();
		
		$this->selectedOwner = __('Post');
		$this->uriRequest = "posts/".$this->uriRequest;
	}
}

class _posts {
	var $arguments;
	var $selectedID;
	var $selectedMenuID;
	var $selectedLanguageID;
	var $selectedBlockID;
	var $selectedMenu;
	var $selectedLanguage;
	var $limit = 0;
	var $keywordsCloudLimit = 21;
	var $randomize = false;
	var $ignorePaging = false;
	var $showPaging = true;
	var $search = null;
	var $ajaxPaging = AJAX_PAGING;
	var $ajaxRequest = null;
	var $adminPath = 'admin/content/menuitems/posts';
	
	static $selected = null;
	
	function __construct() {
		if (isset($_GET['postid']))
			$this->selectedID = (int)$_GET['postid'];
		
		if (isset($_GET['languageid']))
			$this->selectedLanguageID = (int)$_GET['languageid'];
		
		if (isset($_GET['menuid']))
			$this->selectedMenuID = (int)$_GET['menuid'];
		
		if (isset($_GET['searchin']) && isset($_GET['search']) && 
			$_GET['searchin'] == 'posts')
			$this->search = trim(strip_tags($_GET['search']));
			
		if (isset($_GET['arguments']) && isset($_GET['ajax']))
			$this->arguments = urldecode($_GET['arguments']);
	}
	
	function SQL() {
		$mainmenu = 
			menuitems::getMainMenu($this->selectedLanguageID);
		
		$searchignoremenuids = null;
		if ($this->search || !$this->selectedMenuID) {
			$menuids = sql::fetch(sql::run(
				" SELECT GROUP_CONCAT(`ID` SEPARATOR ',') AS MenuIDs" .
				" FROM `{menuitems}`" .
				" WHERE `Deactivated`" .
				(!$GLOBALS['USER']->loginok?
					" OR `ViewableBy` > 1":
					null)));
			
			if (isset($menuids['MenuIDs']) && $menuids['MenuIDs'])
				$searchignoremenuids = $menuids['MenuIDs'];
		}
		
		return
			" SELECT * " .
			" FROM `{posts}`" .
			" WHERE !`Deactivated`" .
			($this->selectedID?
				" AND `ID` = '".$this->selectedID."'":
				" AND !`BlockID`") .
			($searchignoremenuids?
				" AND `MenuItemID` NOT IN (".$searchignoremenuids.")":
				null) .
			($this->search && !$this->selectedID?
				sql::search(
					$this->search,
					(JCORE_VERSION >= '0.7'? 
						dynamicForms::searchableFields('posts'):
						array('Title', 'Content', 'Keywords')),
					'AND', array('date' => 'TimeStamp')):
				($this->selectedMenuID?
					" AND (`MenuItemID` = '".$this->selectedMenuID."'" .
					($mainmenu['ID'] == $this->selectedMenuID?
						" OR `OnMainPage` ":
						" OR (`MenuItemID` = '".$mainmenu['ID']."'" .
							" AND `OnMainPage`) ") .
					" ) ":
					null)) .
			" ORDER BY" .
			($this->randomize?
				" RAND()":
				($this->search && !$this->selectedID?
					" `Views` DESC,":
					null) .
				" `OrderID`, `StartDate`, `ID` DESC");
	}
	
	static function populate() {
		if (!isset($_GET['postid']))
			$_GET['postid'] = 0;
		
		$selected = sql::fetch(sql::run(
			" SELECT `ID`, `Title`, `Path`, `Keywords`" .
			" FROM `{posts}`" .
			" WHERE !`Deactivated`" .
			" AND `MenuItemID` = '".(int)$_GET['menuid']."'" .
			(SEO_FRIENDLY_LINKS && !(int)$_GET['postid']?
				" AND '".sql::escape(url::path())."/' LIKE CONCAT(`Path`,'/%')":
				" AND `ID` = '".(int)$_GET['postid']."'") .
			" ORDER BY `OrderID`, `StartDate`, `ID` DESC" .
			" LIMIT 1"));
		
		if (SEO_FRIENDLY_LINKS && $selected)
			url::setPath(preg_replace(
				'/'.addcslashes($selected['Path'], '/').'(\/|$)/i', '', 
				url::path(), 1));
		
		if ($selected) {
			posts::$selected = $selected;
			url::addPageTitle($selected['Title']);			
			url::addPageDescription($selected['Title'].'.');
			url::addPageKeywords($selected['Keywords']);
				
			$_GET['postid'] = $selected['ID'];
			return;
		}
		
		$_GET['postid'] = 0;
	}
	
	// ************************************************   Admin Part
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Post'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Content Files'), 
			'?path=admin/content/contentfiles');
		favoriteLinks::add(
			__('View Website'), 
			SITE_URL);
	}
	
	function setupAdminForm(&$form, $isownermainmenu = false) {
		$edit = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		$form->add(
			'MenuItemID',
			'MenuItemID',
			FORM_INPUT_TYPE_HIDDEN,
			true,
			admin::getPathID());
		$form->setValueType(FORM_VALUE_TYPE_INT);
					
		if (JCORE_VERSION >= '0.7') {
			$postsform = new postsForm();
			$postsform->load(false);
			
			foreach($postsform->elements as $element)
				$form->elements[] = $element;
			
			$form->addValue('BlockID', '','');
			$disabledblocks = array();
			
			foreach(blocks::getTree() as $block) {
				$form->addValue(
					'BlockID',
					$block['ID'], 
					($block['SubBlockOfID']?
						str_replace(' ', '&nbsp;', 
							str_pad('', $block['PathDeepnes']*4, ' ')).
						"|- ":
						null) .
					$block['Title']);
				
				if ($block['TypeID'] != BLOCK_TYPE_CONTENT)
					$disabledblocks[] = $block['ID'];
			}
			
			$form->disableValues('BlockID', $disabledblocks);
			
			$form->edit(
				'OnMainPage',
				($isownermainmenu?
					__('Display on All pages'):
					__('Display on Main page')));
			
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
					__("(will create a new post)").
					"</span>");
			}
			
			$form->insert(
				'OrderID',
				__('Owner'),
				'Owner',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('Owner', 'width: 110px;');
			
			$form->addAdditionalText(
				'Owner',
				"<a style='zoom: 1;' href='".url::uri('request, users') .
					"&amp;request=".$this->adminPath .
					"&amp;users=1' " .
					"class='select-owner-link ajax-content-link'>" .
					_("Select User") .
				"</a>");
			
			unset($postsform);
			return;
		}
		
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 350px;');
		
		$form->add(
			__('Content'),
			'Content',
			FORM_INPUT_TYPE_EDITOR);
		$form->setStyle('height: 400px;');
		$form->setValueType(FORM_VALUE_TYPE_HTML);
					
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
			$form->addAdditionalText(" (".__("e.g. oranges, lemons, limes").")");
		
		if (JCORE_VERSION >= '0.6') {
			$form->add(
				__('Link to URL'),
				'URL',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 300px;');
			$form->setValueType(FORM_VALUE_TYPE_URL);
			$form->setTooltipText(__("e.g. http://domain.com"));
		}
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Announcement Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Start Date'),
			'StartDate',
			FORM_INPUT_TYPE_DATE);
		$form->setStyle('width: 100px;');
		$form->setValueType(FORM_VALUE_TYPE_DATE);
		
		$form->add(
			__('End Date'),
			'EndDate',
			FORM_INPUT_TYPE_DATE);
		$form->setStyle('width: 100px;');
		$form->setValueType(FORM_VALUE_TYPE_DATE);
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
		
		$form->add(
			__('Display Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			($isownermainmenu?
				__('Display on All pages'):
				__('Display on Main page')),
			'OnMainPage',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
			
		$form->add(
			__('In Block'),
			'BlockID',
			FORM_INPUT_TYPE_SELECT,
			false);
		$form->setValueType(FORM_VALUE_TYPE_INT);
		$form->addValue('','');
		
		$disabledblocks = array();
		
		foreach(blocks::getTree() as $block) {
			$form->addValue($block['ID'], 
				($block['SubBlockOfID']?
					str_replace(' ', '&nbsp;', 
						str_pad('', $block['PathDeepnes']*4, ' ')).
					"|- ":
					null) .
				$block['Title']);
			
			if ($block['TypeID'] != BLOCK_TYPE_CONTENT)
				$disabledblocks[] = $block['ID'];
		}
		
		$form->disableValues($disabledblocks);
		
		$form->add(
			__('Partial Content'),
			'PartialContent',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			'1');
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
		
		if (JCORE_VERSION >= '0.3') {
			$form->add(
				__('Display Related Posts'),
				'DisplayRelatedPosts',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				'1');
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
		}
			
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
			
		if (JCORE_VERSION >= '0.6') {
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
		}
		
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
				__("(will create a new post)").
				"</span>");
		}	
			
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
				"&amp;request=".$this->adminPath .
				"&amp;users=1' " .
				"class='select-owner-link ajax-content-link'>" .
				_("Select User") .
			"</a>");
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
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
				sql::run(
					" UPDATE `{posts}` " .
					" SET `OrderID` = '".(int)$ovalue."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".(int)$oid."'");
			}
			
			tooltip::display(
				__("Posts have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
				
			tooltip::display(
				__("Post has been successfully deleted."),
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
			
		if ($edit) {
			if (!$this->edit($id, $form->getPostArray()))
				return false;
			
			$post = sql::fetch(sql::run(
				" SELECT * FROM `{posts}`" .
				" WHERE `ID` = '".(int)$id."'"));
			
			tooltip::display(
				__("Post has been successfully updated.")." ".
				"<a href='".$this->generateLink($post)."' target='_blank'>" .
					__("View Post") .
				"</a>" .
				" - " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
					
		if (!$newid = $this->add($form->getPostArray()))
			return false;
			
		$post = sql::fetch(sql::run(
			" SELECT * FROM `{posts}`" .
			" WHERE `ID` = '".(int)$newid."'"));
		
		tooltip::display(
			__("Post has been successfully created.")." " .
				"<a href='".$this->generateLink($post)."' target='_blank'>" .
					__("View Post") .
				"</a>" .
				" - " .
				"<a href='".url::uri('id, edit, delete') .
					"&amp;id=".$newid."&amp;edit=1#adminform'>" .
					__("Edit") .
				"</a>",
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminListItemSelected(&$row, $isownermainmenu = null) {
		$blockroute = null;
		
		admin::displayItemData(
			__("Path"),
			$row['Path']);
		
		if ($row['Keywords'])
			admin::displayItemData(
				__("Keywords"),
				$row['Keywords']);
		
		if (JCORE_VERSION >= '0.6' && $row['URL'])
			admin::displayItemData(
				__("Link to URL"),
				"<a href='".$row['URL']."' target='_blank'>" . 
					$row['URL'] . 
				"</a>");
		
		if ($row['StartDate'])
			admin::displayItemData(
				__("Start Date"),
				$row['StartDate']);
		
		if ($row['EndDate'])
			admin::displayItemData(
				__("End Date"),
				$row['EndDate']);
		
		if ($row['OnMainPage'])
			admin::displayItemData(
				($isownermainmenu?
					__("Display on All pages"):
					__("Display on Main page")),
				__("Yes"));
		
		if ($row['BlockID'])
			foreach(blocks::getBackTraceTree($row['BlockID']) as $block)
				$blockroute .=
					"<div ".
						($block['ID'] != $row['BlockID']?
							"class='comment'":
							null) .
						">" . 
					($block['SubBlockOfID']?
						str_replace(' ', '&nbsp;', 
							str_pad('', $block['PathDeepnes']*4, ' ')).
						"|- ":
						null). 
					$block['Title'].
					"</div>";
	
		if ($row['BlockID'])
			admin::displayItemData(
				__("Display in Block"),
				$blockroute);
		
		if ($row['PartialContent'])
			admin::displayItemData(
				__("Partial Content"),
				__("Yes"));
		
		if (isset($row['DisplayRelatedPosts']) && $row['DisplayRelatedPosts'])
			admin::displayItemData(
				__("Display Related Posts"),
				__("Yes"));
		
		if (isset($row['EnableRating']) && $row['EnableRating'])
			admin::displayItemData(
				__("Enable Rating"),
				__("Yes") .
				($row['EnableGuestRating']?
					" ".__("(Guests can rate too!)"):
					null));
		
		if (isset($row['EnableComments']) && $row['EnableComments'])
			admin::displayItemData(
				__("Enable Comments"),
				__("Yes") .
				($row['EnableGuestComments']?
					" ".__("(Guests can comment too!)"):
					null));
		
		admin::displayItemData(
			"<hr />");
		admin::displayItemData(
			str_replace(
				'<div style="page-break-after: always',
				'<div class="page-break" style="page-break-after: always',
				$row['Content']));
		
		if (JCORE_VERSION >= '0.7')
			$this->displayCustomFields($row);
	}
	
	function displayAdminListHeader($isownermainmenu = false) {
		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Created on")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
		echo
			"<th><span class='nowrap'>".
				__("Comments")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Attachments")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Pictures")."</span></th>";
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
					$GLOBALS['USER']->constructUserName($user, __('by %s')) .
					", ".sprintf(__("%s views"), $row['Views']) .
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
					"?path=".admin::path()."/".$row['ID']."/postcomments'>" .
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
					"?path=".admin::path()."/".$row['ID']."/postattachments'>" .
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
					"?path=".admin::path()."/".$row['ID']."/postpictures'>" .
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
	
	function displayAdminList(&$rows, $isownermainmenu = false) {
		$id = null;
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		echo
			"<form action='".
				url::uri('edit, delete').
				"' method='post'>";
			
		echo "<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>";
				
		$this->displayAdminListHeader($isownermainmenu);
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
				
			if ($row['ID'] == $id) {
				echo
					"<tr".($i%2?" class='pair'":NULL).">" .
						"<td class='auto-width' colspan='10'>" .
							"<div class='admin-content-preview'>";
				
				$this->displayAdminListItemSelected($row, $isownermainmenu);
				
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
			__('Posts'), 
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$search = null;
		$edit = null;
		$id = null;
		
		if (isset($_GET['search']))
			$search = trim(strip_tags($_GET['search']));
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
			
		if ($edit && isset($_POST['InsertAsNew']) && $_POST['InsertAsNew']) {
			$_GET['limit'] = null;
			$_GET['edit'] = null;
			$_GET['id'] = null;
			
			$edit = false;
			$id = null;
		}
		
		$selectedowner = sql::fetch(sql::run(
			" SELECT `Title`, `LanguageID` " .
			" FROM `{menuitems}` " .
			" WHERE `ID` = '".admin::getPathID()."'"));
			
		$isownermainmenu = menuitems::isMainMenu(
			admin::getPathID(), $selectedowner['LanguageID']);
		
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
					__("Edit Post"):
					__("New Post")),
				'neweditpost');
		
		if (!$edit)
			$form->action = url::uri('id, delete, limit');
					
		$this->setupAdminForm($form, $isownermainmenu);
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
		
		$rows = sql::run(
				" SELECT * FROM `{posts}`" .
				" WHERE `MenuItemID` = '".admin::getPathID()."'" .
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
			$this->displayAdminList($rows, $isownermainmenu);
		else
			tooltip::display(
				__("No posts found."),
				TOOLTIP_NOTIFICATION);
		
		$paging->display();
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{posts}`" .
					" WHERE `MenuItemID` = '".admin::getPathID()."'" .
					" AND `ID` = '".$id."'"));
		
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
			sql::run(
				" UPDATE `{posts}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `MenuItemID` = '".(int)$values['MenuItemID']."'");
			
			$values['OrderID'] = 1;
			
		} else {
			sql::run(
				" UPDATE `{posts}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `MenuItemID` = '".(int)$values['MenuItemID']."'" .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		if (stripos($values['Content'], '<div style="page-break-after: always') !== false)
			$values['PartialContent'] = true;
		
		if (JCORE_VERSION >= '0.7') {
			if (!isset($values['UserID']))
				$values['UserID'] = (int)$GLOBALS['USER']->data['ID'];
			
			$postsform = new postsForm();
			$newid = $postsform->addData($values);
			unset($postsform);
		
		} else {
			$newid = sql::run(
				" INSERT INTO `{posts}` SET ".
				" `MenuItemID` = '".
					(int)$values['MenuItemID']."'," .
				" `Title` = '".
					sql::escape($values['Title'])."'," .
				" `Content` = '".
					sql::escape($values['Content'])."'," .
				" `Path` = '".
					sql::escape($values['Path'])."'," .
				(JCORE_VERSION >= '0.6'?
					" `URL` = '".
						sql::escape($values['URL'])."',":
					null) .
				" `Keywords` = '".
					sql::escape($values['Keywords'])."'," .
				" `TimeStamp` = " .
					($values['TimeStamp']?
						"'".sql::escape($values['TimeStamp'])."'":
						"NOW()").
					"," .
				" `StartDate` = " .
					($values['StartDate']?
						"'".sql::escape($values['StartDate'])."'":
						"NULL").
					"," .
				" `EndDate` = " .
					($values['EndDate']?
						"'".sql::escape($values['EndDate'])."'":
						"NULL").
					"," .
				" `Deactivated` = '".
					(int)$values['Deactivated']."'," .
				" `PartialContent` = '".
					(int)$values['PartialContent']."'," .
				" `OnMainPage` = '".
					(int)$values['OnMainPage']."'," .
				(JCORE_VERSION >= '0.3'?
					" `DisplayRelatedPosts` = '".
						(int)$values['DisplayRelatedPosts']."',":
					null) .
				(JCORE_VERSION >= '0.6'?
					" `EnableRating` = '".
						(int)$values['EnableRating']."'," .
					" `EnableGuestRating` = '".
						(int)$values['EnableGuestRating']."',":
					null) .
				" `EnableComments` = '".
					(int)$values['EnableComments']."'," .
				" `EnableGuestComments` = '".
					(int)$values['EnableGuestComments']."'," .
				" `UserID` = '".
					(isset($values['UserID']) && (int)$values['UserID']?
						(int)$values['UserID']:
						(int)$GLOBALS['USER']->data['ID']) .
					"'," .
				" `BlockID` = '".
					(int)$values['BlockID']."'," .
				" `OrderID` = '".
					(int)$values['OrderID']."'");
		}
		
		if (!$newid) {
			tooltip::display(
				sprintf(__("Post couldn't be created! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (JCORE_VERSION >= '0.5') {
			sql::run(
				" UPDATE `{menuitems}` SET " .
				" `Posts` = `Posts` + 1" .
				" WHERE `ID` = '".(int)$values['MenuItemID']."'");
			
			$this->updateKeywordsCloud(
				$values['Keywords'], null,
				(JCORE_VERSION >= '0.7'?$values['MenuItemID']:null));
		}
				
		$sitemap = new siteMap();
		$sitemap->load();
		
		$menuitem = sql::fetch(sql::run(
			" SELECT * FROM `{menuitems}`" .
			" WHERE `ID` = '".(int)$values['MenuItemID']."'"));
			
		if (SEO_FRIENDLY_LINKS)
			$menuitemurl = SITE_URL.
				$menuitem['Path'];
		else
			$menuitemurl = SITE_URL.'index.php' .
				'?menuid='.$menuitem['ID'];
		
		$sitemap->edit($menuitemurl, array(
			'LastModified' => 
				($values['TimeStamp']?
					$values['TimeStamp']:
					date('Y-m-d H:i:s'))));
		
		if (!$sitemap->save())
			tooltip::display(
				__("Post successfully created but xml sitemap file couldn't be updated.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"sitemap.xml"),
				TOOLTIP_NOTIFICATION);
		
		unset($sitemap);
		
		if (!$this->updateRSS() || !$this->updateRSS((int)$values['MenuItemID']))
			tooltip::display(
				__("Post successfully created but rss feed couldn't be updated.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"rss/"),
				TOOLTIP_NOTIFICATION);
		
		if (defined('BLOG_PING_ON_NEW_POSTS') && BLOG_PING_ON_NEW_POSTS && 
			!$this->blogPing((int)$values['MenuItemID']))
			tooltip::display(
				__("Post successfully created but couldn't ping blog servers. " .
					"Please define at least one blog ping server or multiple " .
					"servers separated by commas in Global Settings."),
				TOOLTIP_NOTIFICATION);
		
		return $newid;
	}
	
	function edit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		$post = sql::fetch(sql::run(
			" SELECT * FROM `{posts}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		if (stripos($values['Content'], '<div style="page-break-after: always') !== false &&
			stripos($post['Content'], '<div style="page-break-after: always') === false)
			$values['PartialContent'] = true;
		
		if (stripos($values['Content'], '<div style="page-break-after: always') === false &&
			stripos($post['Content'], '<div style="page-break-after: always') !== false)
			$values['PartialContent'] = false;
		
		if (JCORE_VERSION >= '0.7') {
			$postsform = new postsForm();
			$postsform->editData($id, $values);
			unset($postsform);
		
		} else {
			sql::run(
				" UPDATE `{posts}` SET ".
				" `Title` = '".
					sql::escape($values['Title'])."'," .
				" `Content` = '".
					sql::escape($values['Content'])."'," .
				" `Path` = '".
					sql::escape($values['Path'])."'," .
				(JCORE_VERSION >= '0.6'?
					" `URL` = '".
						sql::escape($values['URL'])."',":
					null) .
				" `Keywords` = '".
					sql::escape($values['Keywords'])."'," .
				" `TimeStamp` = " .
					($values['TimeStamp']?
						"'".sql::escape($values['TimeStamp'])."'":
						"NOW()").
					"," .
				" `StartDate` = " .
					($values['StartDate']?
						"'".sql::escape($values['StartDate'])."'":
						"NULL").
					"," .
				" `EndDate` = " .
					($values['EndDate']?
						"'".sql::escape($values['EndDate'])."'":
						"NULL").
					"," .
				" `Deactivated` = '".
					(int)$values['Deactivated']."'," .
				" `PartialContent` = '".
					(int)$values['PartialContent']."'," .
				" `OnMainPage` = '".
					(int)$values['OnMainPage']."'," .
				(JCORE_VERSION >= '0.3'?
					" `DisplayRelatedPosts` = '".
						(int)$values['DisplayRelatedPosts']."',":
					null) .
				(JCORE_VERSION >= '0.6'?
					" `EnableRating` = '".
						(int)$values['EnableRating']."'," .
					" `EnableGuestRating` = '".
						(int)$values['EnableGuestRating']."',":
					null) .
				" `EnableComments` = '".
					(int)$values['EnableComments']."'," .
				" `EnableGuestComments` = '".
					(int)$values['EnableGuestComments']."'," .
				" `BlockID` = '".
					(int)$values['BlockID']."'," .
				(isset($values['UserID']) && (int)$values['UserID']?
					" `UserID` = '".(int)$values['UserID']."',":
					null) .
				" `OrderID` = '".
					(int)$values['OrderID']."'" .
				" WHERE `ID` = '".(int)$id."'");
		}
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Post couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		if (JCORE_VERSION >= '0.5')
			$this->updateKeywordsCloud(
				$values['Keywords'], $post['Keywords'],
				(JCORE_VERSION >= '0.7'?$values['MenuItemID']:null),
				(JCORE_VERSION >= '0.7'?$post['MenuItemID']:null));
		
		$sitemap = new siteMap();
		$sitemap->load();
		
		$menuitem = sql::fetch(sql::run(
			" SELECT * FROM `{menuitems}`" .
			" WHERE `ID` = '".(int)$values['MenuItemID']."'"));
			
		if (SEO_FRIENDLY_LINKS)
			$menuitemurl = SITE_URL.
				$menuitem['Path'];
		else
			$menuitemurl = SITE_URL.'index.php' .
				'?menuid='.$menuitem['ID'];
		
		$sitemap->edit($menuitemurl, array(
			'LastModified' => 
				($values['TimeStamp']?
					$values['TimeStamp']:
					date('Y-m-d H:i:s'))));
		
		if (!$sitemap->save())
			tooltip::display(
				__("Post successfully updated but xml sitemap file couldn't be updated.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"sitemap.xml"),
				TOOLTIP_NOTIFICATION);
		
		unset($sitemap);
		
		if (!$this->updateRSS() || !$this->updateRSS((int)$values['MenuItemID']))
			tooltip::display(
				__("Post successfully updated but rss feed couldn't be updated.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"rss/"),
				TOOLTIP_NOTIFICATION);
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
			
		$post = sql::fetch(sql::run(
			" SELECT * FROM `{posts}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		$comments = new postComments();
		
		$rows = sql::run(
			" SELECT * FROM `{postcomments}`" .
			" WHERE `PostID` = '".$id."'");
		
		while($row = sql::fetch($rows))
			$comments->delete($row['ID']);
			
		unset($comments);
		
		$pictures = new postPictures();
		
		$rows = sql::run(
			" SELECT * FROM `{postpictures}`" .
			" WHERE `PostID` = '".$id."'");
		
		while($row = sql::fetch($rows))
			$pictures->delete($row['ID']);
		
		unset($pictures);
		
		$attachments = new postAttachments();
		
		$rows = sql::run(
			" SELECT * FROM `{postattachments}`" .
			" WHERE `PostID` = '".$id."'");
		
		while($row = sql::fetch($rows))
			$attachments->delete($row['ID']);
		
		unset($attachments);
		
		if (JCORE_VERSION >= '0.6')
			sql::run(
				" DELETE FROM `{postratings}` " .
				" WHERE `PostID` = '".$id."'");
			
		$menuitem = sql::fetch(sql::run(
			" SELECT `MenuItemID` FROM `{posts}` " .
			" WHERE `ID` = '".$id."'"));
			
		sql::run(
			" DELETE FROM `{posts}` " .
			" WHERE `ID` = '".$id."'");
		
		if (JCORE_VERSION >= '0.5') {
			$row = sql::fetch(sql::run(
				" SELECT COUNT(`ID`) AS `Rows` FROM `{posts}`" .
				" WHERE `MenuItemID` = '".$menuitem['MenuItemID']."'"));
			
			sql::run("UPDATE `{menuitems}`" .
				" SET `Posts` = '".(int)$row['Rows']."'" .
				" WHERE `ID` = '".$menuitem['MenuItemID']."'");
			
			$this->updateKeywordsCloud(
				null, $post['Keywords'],
				null, (JCORE_VERSION >= '0.7'?$post['MenuItemID']:null));
		}
					
		if (!$this->updateRSS() || !$this->updateRSS($menuitem['MenuItemID']))
			tooltip::display(
				__("Post successfully deleted but rss feed couldn't be updated.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					"rss/"),
				TOOLTIP_NOTIFICATION);
		
		return true;
	}
	
	static function updateRSS($menuid = null) {
		$rss = new rss();
		$rss->file = SITE_PATH.'rss/posts.xml';
		
		if ($menuid) {
			$menuitem = sql::fetch(sql::run(
				" SELECT `Title`, `Path` " .
				" FROM `{menuitems}` " .
				" WHERE `ID` = '".(int)$menuid."'"));
			
			if (!$menuitem['Path'])
				return false;
			
			$rss->channel['Title'] = $menuitem['Title']." - ".
				$rss->channel['Title'];
			
			$rss->file = SITE_PATH.'rss/posts-'.preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '',
					str_replace('/', '-', $menuitem['Path'])).'.xml';
		}
		
		$rows = sql::run(
			" SELECT * FROM `{posts}`" .
			" WHERE !`Deactivated`" .
			" AND !`BlockID`" .
			($menuid?
				" AND `MenuItemID` = '".(int)$menuid."'":
				null) .
			" ORDER BY `OrderID`, `StartDate`, `ID` DESC" .
			" LIMIT 10");
			
		while($row = sql::fetch($rows)) {
			$menu = sql::fetch(sql::run(
				" SELECT `ID`, `Path` " .
				" FROM `{menuitems}` " .
				" WHERE `ID` = '".$row['MenuItemID']."'"));
			
			$user = $GLOBALS['USER']->get($row['UserID']);
		
			if (SEO_FRIENDLY_LINKS) {
				$postlink = SITE_URL.
					$menu['Path'].'/'.$row['Path'];
			
			} else {
				$postlink = SITE_URL.'index.php' .
					'?menuid='.$menu['ID'].
					'&amp;postid='.$row['ID'];
			}
			
			$rss->add(array(
				'Title' => $row['Title'],
				'Link' => $postlink,
				'Description' => posts::generateTeaser($row['Content']),
				'TimeStamp' => $row['TimeStamp'],
				'Comments' => $row['Comments'],
				'Author' => 
					($user?
						$user['Email']." (".$user['UserName'].")":
						null)));
		}
		
		if (!$rss->save()) {
			unset($rss);
			return false;
		}
		
		unset($rss);
		return true;
	}
	
	static function updateKeywordsCloud($newkeywords = null, $oldkeywords = null, 
		$newmenuid = null, $oldmenuid = null) 
	{
		$oldkeywords = array_map('trim', explode(',', $oldkeywords));
		$newkeywords = array_map('trim', explode(',', $newkeywords));
		
		if (count($oldkeywords)) {
			foreach($oldkeywords as $oldkeyword) {
				if (!$oldkeyword || (in_array($oldkeyword, $newkeywords) && 
					$oldmenuid == $newmenuid))
					continue;
				
				$menuids = null;
				if ($oldmenuid) {
					$menuids = sql::fetch(sql::run(
						" SELECT `MenuItemIDs` FROM `{postkeywords}`" .
						" WHERE `Keyword` = '".sql::escape($oldkeyword)."'"));
					
					if ($menuids) {
						$menuids = explode('|', $menuids['MenuItemIDs']);
						
						$exists = sql::fetch(sql::run(
							" SELECT `ID` FROM `{posts}`" .
							" WHERE `MenuItemID` = '".(int)$oldmenuid."'" .
							" AND CONCAT(',', `Keywords`, ',') LIKE '%," .
								sql::escape($oldkeyword).",%'" .
							" LIMIT 1"));
						
						if (!$exists) {
							$menuids = array_flip($menuids);
							unset($menuids[(int)$oldmenuid]);
							$menuids = array_flip($menuids);
						}
					}
				}
				
				sql::run(
					" UPDATE `{postkeywords}` SET " .
					(is_array($menuids)?
						"`MenuItemIDs` = '".implode('|', $menuids)."',":
						null) .
					" `Counter` = `Counter` - 1" .
					" WHERE `Keyword` = '".sql::escape($oldkeyword)."'");
			}
		}
			
		if (count($newkeywords)) {
			foreach($newkeywords as $newkeyword) {
				if (!$newkeyword || (in_array($newkeyword, $oldkeywords) &&
					$newmenuid == $oldmenuid))
					continue;
				
				$menuids = null;
				if ($newmenuid) {
					$menuids = sql::fetch(sql::run(
						" SELECT `MenuItemIDs` FROM `{postkeywords}`" .
						" WHERE `Keyword` = '".sql::escape($newkeyword)."'"));
					
					if ($menuids) {
						if ($menuids['MenuItemIDs'])
							$menuids = explode('|', $menuids['MenuItemIDs']);
						else
							$menuids = array();
						
						if (!in_array((int)$newmenuid, $menuids))
							$menuids[] = (int)$newmenuid;
						
					} else {
						$menuids[] = (int)$newmenuid;
					}
				}
				
				sql::run(
					" UPDATE `{postkeywords}` SET " .
					($menuids?
						"`MenuItemIDs` = '".implode('|', $menuids)."',":
						null) .
					" `Counter` = `Counter` + 1" .
					" WHERE `Keyword` = '".sql::escape($newkeyword)."'");
				
				if (!sql::affected())
					sql::run(
						" INSERT INTO `{postkeywords}` SET" .
						($menuids?
							"`MenuItemIDs` = '".implode('|', $menuids)."',":
							null) .
						" `Keyword` = '".sql::escape($newkeyword)."'," .
						" `Counter` = 1");
			}
		}
		
		sql::run(
			" DELETE FROM `{postkeywords}`" .
			" WHERE !`Counter`");
		
		return true;
	}
	
	static function blogPing($menuid = null) {
		if (!defined('BLOG_PING_SERVERS') || !BLOG_PING_SERVERS)
			return false;
		
		$rssfile = SITE_URL.'rss/posts.xml';
		
		if ($menuid) {
			$menuitem = sql::fetch(sql::run(
				" SELECT `Title`, `Path` " .
				" FROM `{menuitems}` " .
				" WHERE `ID` = '".(int)$menuid."'"));
			
			if (!$menuitem['Path'])
				return false;
			
			$rssfile = SITE_URL.'rss/posts-'.preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '',
					str_replace('/', '-', $menuitem['Path'])).'.xml';
		}
		
		$servers = explode(",", BLOG_PING_SERVERS);
		foreach ($servers as $server) {
			if (!trim($server))
				continue;
				
			// using a timeout of 3 seconds should be enough to cover slow servers
			$client = new IXR_Client($server);
			$client->timeout = 3;
			$client->useragent .= ' -- jCore/'.JCORE_VERSION;
		
			// when set to true, this outputs debug messages by itself
			$client->debug = false;
			
			if (!$client->query('weblogUpdates.extendedPing', PAGE_TITLE, 
				SITE_URL, $rssfile)) 
			{
				// then try a normal ping
				$client->query('weblogUpdates.ping', PAGE_TITLE, 
					SITE_URL);
			}
			
			unset($client);
		}
		
		return true;
	}
	
	// ************************************************   Client Part
	static function generateTeaser($description) {
		if (stripos($description, '<div style="page-break-after: always') !== false)
			preg_match('/(.*?)(<div style="page-break-after: always)/is', $description, $matches);
		elseif (stripos($description, '<hr') !== false)
			preg_match('/(.*?)(<hr.*?>)/is', $description, $matches);
		else
			preg_match('/(.*?)((<\/p>))/is', $description, $matches);
		
		if (isset($matches[1])) {
			$teaser = $matches[1];
			
			if (isset($matches[3]))
				$teaser .= $matches[3];
			
		} else {
			$teaser = $description;
		}
		
		return $teaser;
	}
	
	function generateLink(&$row) {
		$language = $this->selectedLanguage;
		$menu = $this->selectedMenu;
		
		if (!$menu)
			$menu = sql::fetch(sql::run(
				" SELECT `ID`, `Path`, `LanguageID` FROM `{menuitems}`" .
				" WHERE `ID` = '".$row['MenuItemID']."'"));
		
		if (!$language && $menu['LanguageID'])
			$language = sql::fetch(sql::run(
				" SELECT `ID`, `Path` FROM `{languages}`" .
				" WHERE `ID` = '".$menu['LanguageID']."'"));
		
		if (SEO_FRIENDLY_LINKS)
			return 
				url::site() .
				($language?
					$language['Path'].'/':
					null) .
				$menu['Path'].'/' .
				$row['Path'] .
				($this->selectedMenuID == $this->selectedMenu['ID'] &&
				 url::arg('postslimit')?
					'?'.url::arg('postslimit'):
					null);
			
		return 
			url::site().'index.php?' .
			($language?
				'&amp;languageid='.$language['ID']:
				null) .
			'&amp;menuid='.$menu['ID'].
			'&amp;postid='.$row['ID'];
			($this->selectedMenuID == $this->selectedMenu['ID'] &&
			 url::arg('postslimit')?
				'&amp;'.url::arg('postslimit'):
				null);
	}
	
	function generateMenuLink(&$row) {
		$language = $this->selectedLanguage;
		$menu = $this->selectedMenu;
		
		if (!$menu)
			$menu = sql::fetch(sql::run(
				" SELECT `ID`, `Path`, `LanguageID` FROM `{menuitems}`" .
				" WHERE `ID` = '".$row['MenuItemID']."'"));
		
		if (!$language && $menu['LanguageID'])
			$language = sql::fetch(sql::run(
				" SELECT `ID`, `Path` FROM `{languages}`" .
				" WHERE `ID` = '".$menu['LanguageID']."'"));
		
		if (SEO_FRIENDLY_LINKS)
			return 
				url::site() .
				($language?
					$language['Path'].'/':
					null) .
				$menu['Path'].
				($this->selectedID == $row['ID'] &&
				 $this->selectedMenuID == $menu['ID'] &&
				 url::arg('postslimit')?
					'?'.url::arg('postslimit'):
					null);
		
		return 
			url::site().'index.php?' .
			($language?
				'&amp;languageid='.$language['ID']:
				null) .
			'&amp;menuid='.$menu['ID'] .
			($this->selectedID == $row['ID'] &&
			 $this->selectedMenuID == $menu['ID'] &&
			 url::arg('postslimit')?
				'&amp;'.url::arg('postslimit'):
				null);
	}
	
	function generateCSSClass(&$row) {
		$class = null;
		$paths = explode('/', $this->selectedMenu['Path']);
		
		foreach($paths as $path)
			$class .= " menu-" .
				preg_replace('/[^a-zA-Z0-9\@\.\_\-]/', '', $path);
		
		return $class;
	}
	
	function incViews(&$row) {
		sql::run(
			" UPDATE `{posts}` SET " .
			" `Views` = `Views` + 1," .
			" `TimeStamp` = `TimeStamp`" .
			" WHERE `ID` = '".$row['ID']."'");
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
			
			$GLOBALS['USER']->displayQuickList('#neweditpostform #entryOwner');
			return true;
		}
		
		if (preg_match('/[0-9]/', $this->uriRequest))
			$this->selectedMenuID = url::getPathID(0, $this->uriRequest);
		
		$this->ajaxPaging = true;
		$this->display();
		return true;
	}
	
	function displayTitle(&$row) {
		echo
			"<a href='" .
				(JCORE_VERSION >= '0.6' && $row['URL']?
					url::generateLink($row['URL']):
					$row['_Link']) .
				"'>" .
				$row['Title'] .
			"</a>";
	}
	
	function displaySelectedTitle(&$row) {
		echo $row['Title'];
	}
	
	function displayMenuTitle() {
		echo
				"<h1 class='post-title'>";
		
		$this->displaySelectedTitle($this->selectedMenu);
		
		echo
				"</h1>" .
				"<div class='post-content place-holder'>" .
					"<p></p>" .
				"</div>";
	}
	
	function displayDetails(&$row) {
		$user = $GLOBALS['USER']->get($row['UserID']);
		
		echo
			calendar::datetime($row['TimeStamp'])." ";
		
		$GLOBALS['USER']->displayUserName($user, __('by %s'));
		
		if ((JCORE_VERSION < '0.6' || !$row['URL']) && $row['Views'])
			echo
				"<span class='post-details-separator separator-1'>" .
				", " .
				"</span>" .
				"<span class='post-views-number'>" .
					sprintf(__("%s views"), $row['Views']) .
				"</span>";
	}
	
	function displayAnnouncementInfo(&$row) {
		if ($row['StartDate'] && $row['EndDate']) {
			echo
				sprintf(__("Starting <b>%s</b> until <b>%s</b>"),
					calendar::date($row['StartDate']),
					calendar::date($row['EndDate']));
			return;
		}
		
		if ($row['StartDate']) {
			echo
				sprintf(__("Starting <b>%s</b>"),
					calendar::date($row['StartDate']));
			return;
		}
		
		if ($row['EndDate']) {
			echo
				sprintf(__("Until <b>%s</b>"),
					calendar::date($row['EndDate']));
			return;
		}
	}
	
	function displayPictures(&$row) {
		$pictures = new postPictures();
		$pictures->selectedOwnerID = $row['ID'];
		$pictures->display();
		unset($pictures);
	}
	
	function displayLatestPicture(&$row) {
		$pictures = new postPictures();
		$pictures->selectedOwnerID = $row['ID'];
		$pictures->limit = 1;
		$pictures->showPaging = false;
		$pictures->customLink = $row['_Link'];
		$pictures->display();
		unset($pictures);
	}
	
	function displayContent(&$row) {
		$codes = new contentCodes();
		$codes->fixParagraph = true;
		
		if ($this->search)
			$codes->ignoreCodes = array(
				'menus', 'posts', 'blocks', 'modules', 'forms');
		
		$codes->display($row['Content']);
		unset($codes);
	}
	
	function displayBody(&$row) {
		if ($row['Pictures'])
			$this->displayPictures($row);
		
		if ($row['Content']) {
			echo
				"<div class='post-content'>";
			
			$this->displayContent($row);
		
			echo
				"</div>";
		}
		
		if (JCORE_VERSION >= '0.7') {
			echo
				"<div class='post-custom-fields'>";
			
			$this->displayCustomFields($row);
			
			echo
				"</div>";
		}
	}
	
	function displayTeaserBody(&$row) {
		if ($row['Pictures'])
			$this->displayLatestPicture($row);
		
		if ($row['Content']) {	
			echo
				"<div class='post-content'>";
			
			$row['Content'] = posts::generateTeaser($row['Content']);
			$this->displayContent($row);
			
			echo
				"</div>";
		}
	}
	
	function displayCustomFields(&$row) {
		$postsform = new postsForm();
		$postsform->load(false);
		
		$postsform->displayData($row, array(
			'Title', 'Content', 'TimeStamp', 'Path',
			'Keywords', 'URL', 'StartDate', 'EndDate',
			'OnMainPage', 'BlockID', 'PartialContent', 
			'DisplayRelatedPosts', 'EnableRating', 
			'EnableGuestRating', 'EnableComments', 
			'EnableGuestComments', 'Deactivated', 'OrderID'));
		
		unset($postsform);
	}
	
	function displayRating(&$row) {
		$rating = new postRating();
		$rating->guestRating = $row['EnableGuestRating'];
		$rating->selectedOwnerID = $row['ID'];
		$rating->display();
		unset($rating);	
	}
	
	function displayAttachments(&$row) {
		$attachments = new postAttachments();
		$attachments->selectedOwnerID = $row['ID'];
		$attachments->display();
		unset($attachments);
	}
	
	function displayComments(&$row) {
		$comments = new postComments();
		$comments->guestComments = $row['EnableGuestComments'];
		$comments->selectedOwnerID = $row['ID'];
		$comments->display();
		unset($comments);
	}
	
	function displayKeywordsCloudLink(&$row) {
		echo  
			"<a href='".$row['_SearchURL']."&amp;search=".
				urlencode('"'.trim($row['Keyword']).'"') .
				"&amp;searchin=posts' " .
				"style='font-size: ".$row['_FontPercent']."%;'>" .
				ucfirst(trim($row['Keyword'])) .
			"</a> ";
	}
	
	function displayKeywordsCloud($arguments = null) {
		$menu = null;
		$byranks = false;
		
		if (preg_match('/(^|\/)byranks($|\/)/', $arguments)) {
			$arguments = preg_replace('/(^|\/)byranks($|\/)/', '\2', $arguments);
			$byranks = true;
		}
		
		if ($arguments)
			$menu = sql::fetch(sql::run(
				" SELECT `ID` FROM `{menuitems}` " .
				" WHERE !`Deactivated`" .
				($this->selectedLanguageID?
					" AND `LanguageID` = '".$this->selectedLanguageID."'":
					null) .
				" AND '".sql::escape($arguments)."/' LIKE CONCAT(`Path`,'/%')" .
				" ORDER BY `Path` DESC," .
					(menus::$order?
						" FIELD(`MenuID`, ".menus::$order."),":
						" `MenuID`,") .
					" `OrderID`" .
				" LIMIT 1"));
		
		sql::run(
			" CREATE TEMPORARY TABLE `{TMPKeywordsCloud}` " .
			" (`Keyword` varchar(100) NOT NULL default ''," .
			"  `Counter` mediumint(8) unsigned NOT NULL default '0'," .
			"  `ID` tinyint(2) unsigned NOT NULL auto_increment," .
			" PRIMARY KEY  (`ID`)" .
			")");
			
		sql::run(
			" INSERT INTO `{TMPKeywordsCloud}` " .
			" SELECT `Keyword`, `Counter`, NULL FROM `{postkeywords}`" .
			($menu?
				" WHERE CONCAT('|', `MenuItemIDs`, '|') LIKE '%|".$menu['ID']."|%'":
				null) .
			" ORDER BY `Counter` DESC" .
			" LIMIT ".$this->keywordsCloudLimit);
		
		$rows = sql::run(
			" SELECT * FROM `{TMPKeywordsCloud}`" .
			" ORDER BY " .
			($byranks?
				" `Counter` DESC, ID,":
				null) .
			" `Keyword`");
			
		$hrow = sql::fetch(sql::run(
			" SELECT `Counter` FROM `{TMPKeywordsCloud}`" .
			" ORDER BY `Counter` DESC" .
			" LIMIT 1"));
		
		echo "<div class='post-keywords-cloud'>";
		
		$searchurl = modules::getOwnerURL('Search');
		
		if (!$searchurl)
			$searchurl = url::site()."index.php?";
		
		while($row = sql::fetch($rows)) {
			$row['_SearchURL'] = $searchurl;
			$row['_FontPercent'] = round(
				($row['Counter']*70/$hrow['Counter']))+30;
			
			$this->displayKeywordsCloudLink($row);
		}
		
		sql::run(" DROP TEMPORARY TABLE `{TMPKeywordsCloud}` ");
		
		echo "</div>";
	}
	
	function displayCalendar($menupath = null) {
		$menu = null;
		
		if ($menupath)
			$menu = sql::fetch(sql::run(
				" SELECT `ID` FROM `{menuitems}` " .
				" WHERE !`Deactivated`" .
				($this->selectedLanguageID?
					" AND `LanguageID` = '".$this->selectedLanguageID."'":
					null) .
				" AND '".sql::escape($menupath)."/' LIKE CONCAT(`Path`,'/%')" .
				" ORDER BY `Path` DESC," .
					(menus::$order?
						" FIELD(`MenuID`, ".menus::$order."),":
						" `MenuID`,") .
					" `OrderID`" .
				" LIMIT 1"));
		
		$calendar = new postsCalendar(($menu?$menu['ID']:null));
		$calendar->display();
		unset($calendar);
	}
	
	function displayKeywordLinks(&$row) {
		$words = explode(',', $row['Keywords']);
		foreach($words as $key => $word) {
			if ($key)
				echo ", ";
			
			echo  
				"<a href='".$row['_MenuLink']."?search=".
					($this->search?
						urlencode($this->search.","):
						null) .
					urlencode('"'.trim($word).'"') .
					"&amp;searchin=posts" .
					"' class='keyword'>" .
					ucfirst(trim($word)) .
				"</a>";
		}
	}
	
	function displayKeywords(&$row) {
		echo
			"<span class='keywords-title'>" .
				__("Keywords").": " .
			"</span> ";
		
		$this->displayKeywordLinks($row);
	}
	
	function displayFunctions(&$row) {
		if ($this->selectedID == $row['ID']) {
			echo
				"<a href='".$row['_MenuLink']."' class='back comment'>" .
					"<span>".
					__("Back").
					"</span>" .
				"</a>";
		
			if (JCORE_VERSION >= '0.6' && $row['URL'])
				echo
					"<a href='" .
						url::generateLink($row['URL']) .
						"' class='read-more comment'>".
						"<span>" .
						__("Read more").
						"</span>" .
					"</a>";
			
		} else {
			if ($row['PartialContent'] || 
				($this->search && !$this->selectedBlockID) ||
				(JCORE_VERSION >= '0.6' && $row['URL']))
				echo
					"<a href='" .
						(JCORE_VERSION >= '0.6' && $row['URL']?
							url::generateLink($row['URL']):
							$row['_Link']) .
						"' class='read-more comment'>".
						"<span>" .
						__("Read more").
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
				"<a href='".SITE_URL."admin/?path=admin/content/menuitems/" .
					$row['MenuItemID']."/posts&amp;id=".$row['ID'] .
					"&amp;edit=1#adminform' " .
					"class='edit comment' target='_blank'>" .
					"<span>".
					__("Edit").
					"</span>" .
				"</a>";
	}
	
	function displayRelatedPostDate(&$row) {
		echo
			calendar::date($row['TimeStamp']);
	}
	
	function displayRelatedPosts(&$row) {
		if ($row['Keywords'])
			$searches = explode(',', $row['Keywords']);
		else
			$searches = explode(' ', $row['Title']);
			
		if (!count($searches))
			return;
			
		$posts = sql::run(
			" SELECT * " .
			" FROM `{posts}`" .
			" WHERE !`Deactivated`" .
			" AND ID != '".$row['ID']."'" .
			" AND (`Title` REGEXP '".sql::escape(implode('|', $searches))."'" .
			" OR `Keywords` REGEXP '".sql::escape(implode('|', $searches))."')" .
			" ORDER BY `ID` DESC" .
			" LIMIT 10");
			
		if (!sql::rows($posts))
			return;
		
		echo 
			"<div class='related-posts'>" .
				"<h3>".__("Related Posts")."</h3>" .
				"<ul class='related-posts-list'>";
		
		while($post = sql::fetch($posts)) {
			$language = null;
			
			$menuitem = sql::fetch(sql::run(
				" SELECT `ID`, `Path`, `LanguageID` FROM `{menuitems}`" .
				" WHERE `ID` = '".$post['MenuItemID']."'" .
				" AND !`Deactivated`" .
				" AND (!`ViewableBy` OR " .
					($GLOBALS['USER']->loginok?
						($GLOBALS['USER']->data['Admin']?
							" `ViewableBy` IN (2, 3)":
							" `ViewableBy` = 2"):
						" `ViewableBy` = 1") .
				" )"));
			
			if (!$menuitem)
				continue;
			
			if ($menuitem['LanguageID']) {
				$language = sql::fetch(sql::run(
					" SELECT `ID`, `Path` FROM `{languages}`" .
					" WHERE `ID` = '".$menuitem['LanguageID']."'" .
					" AND !`Deactivated`"));
					
				if (!$language)
					continue;
			}
			
			if (SEO_FRIENDLY_LINKS) {
				$post['_Link'] = url::site().
					($language?
						$language['Path'].'/':
						null) .
					$menuitem['Path'].'/' .
					$post['Path'];
			
			} else {
				$post['_Link'] = url::site().'index.php?' .
					($language?
						'&amp;languageid='.$language['ID']:
						null) .
					'&amp;menuid='.$menuitem['ID'].
					'&amp;postid='.$post['ID'];
			}
		
			echo
					"<li class='related-post'>";
			
			$this->displayTitle($post);
			
			echo
						" " .
						"<span class='related-post-date comment'>";
			
			$this->displayRelatedPostDate($post);
			
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
			"<div" .
				(!isset($this->arguments)?
					" id='post".$row['ID']."'":
					null) .
				" class='post one".
				" post".$row['ID'] .
				" post-num".$row['_PostNumber'] .
				(isset($row['_CSSClass'])?
					" ".$row['_CSSClass']:
					null) .
				"'>" .
				"<h" .
				(JCORE_VERSION >= '0.6'?
					'1':
					'2') .
				" class='post-title'>";
				
		$this->displayTitle($row);
		
		echo
				"</h" .
				(JCORE_VERSION >= '0.6'?
					'1':
					'2') .
				">";
				
		echo
			"<div class='post-details comment'>";
		
		$this->displayDetails($row);
		
		echo
			"</div>";
		
		if ($row['StartDate'] || $row['EndDate']) {
			echo
				"<h3 class='post-announcement-dates'>";
					
			$this->displayAnnouncementInfo($row);
			
			echo
				"</h3>";
		}
		
		$this->displayTeaserBody($row);
		
		ob_start();
		$this->displayFunctions($row);
		$links = ob_get_contents();
		ob_end_clean();
		
		if (isset($row['EnableRating']) && $row['EnableRating']) {
			echo
				"<div class='post-rating'>";
			
			$this->displayRating($row);
		
			echo
				"</div>";
		}
		
		if ($links)
			echo
				"<div class='post-links'>" .
				$links .
				"<div class='clear-both'></div>" .
				"</div>";
		
		echo
				"<div class='spacer bottom'></div>" .
				"<div class='separator bottom'></div>";
			
		echo
			"</div>";
	}
	
	function displaySelected(&$row) {
		$this->incViews($row);
		
		echo
			"<div" .
				(!isset($this->arguments)?
					" id='post".$row['ID']."'":
					null) .
				" class='post" .
				($this->selectedID == $row['ID']?
					" selected":
					null) .
				" post".$row['ID'] .
				" post-num".$row['_PostNumber'] .
				(isset($row['_CSSClass'])?
					" ".$row['_CSSClass']:
					null) .
				"'>";
			
		echo
				"<h" .
				(JCORE_VERSION >= '0.6'?
					'1':
					'2') .
				" class='post-title'>";
		
		$this->displaySelectedTitle($row);
		
		echo
				"</h" .
				(JCORE_VERSION >= '0.6'?
					'1':
					'2') .
				">";
				
		if (isset($row['EnableRating']) && $row['EnableRating']) {
			echo
				"<div class='post-rating'>";
			
			$this->displayRating($row);
		
			echo
				"</div>";
		}
		
		echo
			"<div class='post-details comment'>";
		
		$this->displayDetails($row);
		
		echo
			"</div>";
			
		if ($row['StartDate'] || $row['EndDate']) {
			echo
				"<h3 class='post-announcement-dates'>";
					
			$this->displayAnnouncementInfo($row);
			
			echo
				"</h3>";
		}
		
		$this->displayBody($row);
		
		if ($row['Attachments'])
			$this->displayAttachments($row);
		
		if (trim($row['Keywords'])) {
			echo
				"<div class='keywords post-keywords'>";
			
			$this->displayKeywords($row);
			
			echo
				"</div>";
		}
		
		ob_start();
		$this->displayFunctions($row);
		$links = ob_get_contents();
		ob_end_clean();
		
		if ($links)
			echo
				"<div class='post-links'>" .
				$links .
				"<div class='clear-both'></div>" .
				"</div>";
		
		if (isset($row['DisplayRelatedPosts']) && $row['DisplayRelatedPosts'])
			$this->displayRelatedPosts($row);
				
		echo
			"<div class='spacer bottom'></div>" .
			"<div class='separator bottom'></div>";
			
		echo
			"</div>";
			
		if ($this->selectedID == $row['ID'] && $row['EnableComments'])
			$this->displayComments($row);
	}
	
	function displayBlockPosts($blockid) {
		if (!(int)$blockid)
			return false;
		
		$mainmenu = 
			menuitems::getMainMenu($this->selectedLanguageID);
		
		$rows = sql::run(
			" SELECT * " .
			" FROM `{posts}`" .
			" WHERE !`Deactivated`" .
			" AND `BlockID` = '".(int)$blockid."'" .
			" AND (`MenuItemID` = '".$this->selectedMenuID."'" .
			($mainmenu['ID'] == $this->selectedMenuID?
				" OR `OnMainPage` ":
				" OR (`MenuItemID` = '".$mainmenu['ID']."'" .
					" AND `OnMainPage`) ") .
			" ) " .
			" ORDER BY `OrderID`, `StartDate`, `ID` DESC" .
			($this->limit?
				" LIMIT ".$this->limit:
				null));
	
		$this->selectedID = null;
		
		$i = 1;
		$total = sql::rows($rows);
		$menuitemid = 0;
			
		$cssclass = null;
		$menulink = null;
		
		if (!$total)
			return false;
			
		while ($row = sql::fetch($rows)) {
			if ($row['MenuItemID'] != $menuitemid) {
				$this->selectedMenu = sql::fetch(sql::run(
					" SELECT * FROM `{menuitems}` " .
					" WHERE `ID` = '".$row['MenuItemID']."'"));
			
				if ($this->selectedMenu['LanguageID'] && 
					$this->selectedMenu['LanguageID'] != $this->selectedLanguageID)
					continue;
		
				if ($this->selectedMenu['LanguageID'])	
					$this->selectedLanguage = sql::fetch(sql::run(
						" SELECT * FROM `{languages}` " .
						" WHERE `ID` = '".$this->selectedMenu['LanguageID']."'"));
				
				$menuitemid = $row['MenuItemID'];
				$menulink = $this->generateMenuLink($row);
				$cssclass = $this->generateCSSClass($row);
			}
			
			$row['_PostNumber'] = $i;
			$row['_Link'] = $this->generateLink($row);
			$row['_MenuLink'] = $menulink;
			$row['_CSSClass'] = $cssclass;
			
			if ($i == 1)
				$row['_CSSClass'] .= ' first';
			if ($i == $total)
				$row['_CSSClass'] .= ' last';
			 
			if ($row['PartialContent'])
				$this->displayOne($row);
			else
				$this->displaySelected($row);
		
			$i++;
		}
		
		return $total;
	}
	
	function displayLogin() {
		tooltip::display(
			__("This area is limited to members only. " .
				"Please login below."),
			TOOLTIP_NOTIFICATION);
		
		$GLOBALS['USER']->displayLogin();
	}
	
	function displayModules() {
		menuitems::displayModules($this->selectedMenuID);
	}
	
	function displayArguments() {
		if (!$this->arguments)
			return false;
		
		if (preg_match('/(^|\/)rand($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)rand($|\/)/', '\2', $this->arguments);
			$this->randomize = true;
		}
		
		if (preg_match('/(^|\/)latest($|\/)/', $this->arguments)) {
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
			
			if (isset($matches[2]))
				$this->keywordsCloudLimit = (int)$matches[2];
			
			$this->displayKeywordsCloud($this->arguments);
			return true;
		}
		
		if (preg_match('/(^|\/)calendar($|\/)/', $this->arguments)) {
			$this->arguments = preg_replace('/(^|\/)calendar($|\/)/', '\2', $this->arguments);
			
			$this->displayCalendar($this->arguments);
			return true;
		}
		
		if (!$this->arguments)
			return false;
		
		$this->selectedMenuID = null;
		$this->selectedID = null;
		
		$menu = sql::fetch(sql::run(
			" SELECT `ID`, `Path` FROM `{menuitems}` " .
			" WHERE !`Deactivated`" .
			($this->selectedLanguageID?
				" AND `LanguageID` = '".$this->selectedLanguageID."'":
				null) .
			" AND '".sql::escape($this->arguments)."/' LIKE CONCAT(`Path`,'/%')" .
			" ORDER BY `Path` DESC," .
				(menus::$order?
					" FIELD(`MenuID`, ".menus::$order."),":
					" `MenuID`,") .
				" `OrderID`" .
			" LIMIT 1"));
		
		if (!$menu)
			return true;
		
		$this->selectedMenuID = $menu['ID'];
		$this->arguments = preg_replace(
			'/'.addcslashes($menu['Path'], '/').'(\/|$)/i', '', 
			$this->arguments, 1);
		
		if (!$this->arguments)
			return false;
		
		$post = sql::fetch(sql::run(
			" SELECT `ID`, `MenuItemID` FROM `{posts}` " .
			" WHERE !`Deactivated`" .
			" AND `MenuItemID` = '".$menu['ID']."'" .
			" AND '".sql::escape($this->arguments)."/' LIKE CONCAT(`Path`,'/%')" .
			" ORDER BY `OrderID`" .
			" LIMIT 1"));
			
		if (!$post)
			return true;
			
		$this->selectedID = $post['ID'];
		$this->selectedMenuID = $post['MenuItemID'];
	}
	
	function display() {
		if ($this->displayArguments())
			return true;
		
		if ($this->selectedBlockID)
			return $this->displayBlockPosts($this->selectedBlockID);
		
		if (!$this->selectedMenuID && !isset($this->arguments)) {
			url::displayError();
			return false;
		}
		
		if ($this->selectedMenuID) {
			$this->selectedMenu = sql::fetch(sql::run(
				" SELECT * FROM `{menuitems}` " .
				" WHERE `ID` = '".(int)$this->selectedMenuID."'"));
				
			if ($this->selectedMenu['Deactivated']) {
				tooltip::display(
					__("This page has been deactivated."),
					TOOLTIP_NOTIFICATION);
				return false;
			}
			
			if ($this->selectedMenu['ViewableBy'] > MENU_GUESTS_ONLY && 
				!$GLOBALS['USER']->loginok) 
			{
				if (JCORE_VERSION >= '0.7')
					$this->displayMenuTitle();
				
				$this->displayLogin();
				return true;
			}
			
			if ($this->selectedMenu['LanguageID'] && 
				$this->selectedMenu['LanguageID'] != $this->selectedLanguageID)
				return false;
			
			if ($this->selectedMenu['LanguageID'])	
				$this->selectedLanguage = sql::fetch(sql::run(
					" SELECT * FROM `{languages}` " .
					" WHERE `ID` = '".$this->selectedMenu['LanguageID']."'"));
			
			if (!$this->limit)
				$this->limit = $this->selectedMenu['Limit'];
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
			$paging->otherArgs = "&amp;request=posts" .
				($this->selectedMenuID?
					"/".$this->selectedMenuID:
					null) .
				(isset($this->arguments)?
					"&amp;arguments=".urlencode($this->arguments) .
					($this->arguments && $this->limit?
						"/":
						null) .
					$this->limit:
					null);
		}
		
		$paging->track(strtolower(get_class($this)) .
			($this->search?
				'search':
				null) .
			'limit');
		
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
		
		if ($this->search && !$this->selectedID && !$this->ajaxRequest)
			url::displaySearch($this->search, $paging->items);
		
		if (!$this->ajaxRequest)
			echo
				"<div class='posts'>";
		
		if (JCORE_VERSION >= '0.7' && !$paging->items && !$this->search && $this->selectedMenu)
			$this->displayMenuTitle();
		
		$i = 1;
		$total = sql::rows($rows);
		$menuitemid = $this->selectedMenuID;
		
		$cssclass = null;
		$menulink = null;
		
		while ($row = sql::fetch($rows)) {
			if ($row['MenuItemID'] != $menuitemid || !$menulink) {
				if ($row['MenuItemID'] != $menuitemid) {
					$this->selectedMenu = sql::fetch(sql::run(
						" SELECT * FROM `{menuitems}`" .
						" WHERE `ID` = '".$row['MenuItemID']."'"));
					
					if ($this->selectedMenu['LanguageID'])
						$this->selectedLanguage = sql::fetch(sql::run(
							" SELECT * FROM `{languages}`" .
							" WHERE `ID` = '".$this->selectedMenu['LanguageID']."'"));
					else
						$this->selectedLanguage = null;
					
					$menuitemid = $row['MenuItemID'];
				}
				
				$menulink = $this->generateMenuLink($row);
				$cssclass = $this->generateCSSClass($row);
			}
			
			$row['_PostNumber'] = $i;
			$row['_Link'] = $this->generateLink($row);
			$row['_MenuLink'] = $menulink;
			$row['_CSSClass'] = $cssclass;
			
			if ($i == 1)
				$row['_CSSClass'] .= ' first';
			if ($i == $total)
				$row['_CSSClass'] .= ' last';
			
			if ($this->search || ($row['PartialContent'] && 
				$row['ID'] != $this->selectedID))
				$this->displayOne($row);
			else
				$this->displaySelected($row);
			
			$i++;
		}
		
		if (!$this->selectedID && !$this->randomize && $this->showPaging)
			$paging->display();
		
		if (!$this->search)
			$this->displayModules();
		
		if (!$this->ajaxRequest)
			echo
				"</div>"; //posts
		
		return $paging->items;
	}
}

?>