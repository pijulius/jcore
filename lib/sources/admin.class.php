<?php

/***************************************************************************
 *            admin.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

if (!defined('ADMIN_ITEMS_COUNTER_ENABLED'))
	define('ADMIN_ITEMS_COUNTER_ENABLED', false);
 
include_once('lib/modules.class.php');
include_once('lib/ads.class.php');
include_once('lib/postsatglance.class.php');
include_once('lib/postshandling.class.php');
include_once('lib/contentfiles.class.php');
include_once('lib/dynamicformdata.class.php');
include_once('lib/dynamicformfields.class.php');
include_once('lib/template.class.php');
include_once('lib/notes.class.php');
include_once('lib/massemail.class.php');
include_once('lib/userpermissions.class.php');
include_once('lib/usergroups.class.php');
include_once('lib/usergrouppermissions.class.php');
include_once('lib/sitemap.class.php');
include_once('lib/sitemapfileeditor.class.php');
include_once('lib/templatecsseditor.class.php');
include_once('lib/templatejseditor.class.php');
include_once('lib/templateimages.class.php');
include_once('lib/templateexporter.class.php');

if (JCORE_VERSION < '0.7')
	include_once('lib/dynamicformfieldvalues.class.php');

url::setPageTitle('Admin - '.PAGE_TITLE);

_admin::$sections = array(
	'Content' => array(
		'Title' => __('Content Management'),
		'Items' => array()),
	'Modules' => array(
		'Title' => __('Module Management'),
		'Items' => array()),
	'Site' => array(
		'Title' => __('Site Layout and Functionality'),
		'Items' => array()),
	'Members' => array(
		'Title' => __('Member Management'),
		'Items' => array()));

class _admin {
	static $sections = null;
	
	static function add($section, $itemid, $item) {
		preg_match('/(\?|&)path=(.*?)(&|\'|")/i', $item, $matches);
		
		$userpermission = userPermissions::check($GLOBALS['USER']->data['ID'], 
			(isset($matches[2])?
				$matches[2]:
				null));
		
		if ($userpermission['PermissionType'])
			admin::$sections[$section]['Items'][$itemid] = $item;
	}
	
	function load() {
		$this->add('Content', 
			(JCORE_VERSION >= '0.8'?'Pages':'MenuItems'), 
			"<a href='".url::uri('ALL')."?path=" .
				(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems')."' " .
				"title='".
					htmlspecialchars(
						__("Manage the content (pages) of your site"), 
						ENT_QUOTES).
				"'>" .
				"<span>".__("Content Management")."</span>" .
			"</a>");
		
		if (JCORE_VERSION >= '0.3')
			$this->add('Content', 'PostsAtGlance', 
				"<a href='".url::uri('ALL')."?path=admin/content/postsatglance' " .
					"title='".
						htmlspecialchars(
							__("Quickly create / modify posts"), 
							ENT_QUOTES).
					"'>" .
					"<span>".__("Posts at Glance")."</span>" .
				"</a>");
		
		$this->add('Content', 'Ads', 
			"<a href='".url::uri('ALL')."?path=admin/content/ads' " .
				"title='".
					htmlspecialchars(
						__("Upload / Add advertisements"), 
						ENT_QUOTES).
				"'>" .
				"<span>".htmlspecialchars(__("Ads & Banners"))."</span>" .
			"</a>");
		
		$this->add('Content', 'DynamicForms', 
			"<a href='".url::uri('ALL')."?path=admin/content/dynamicforms' " .
				"title='".
					htmlspecialchars(
						__("Create / Modify custom submit forms"), 
						ENT_QUOTES).
				"'>" .
				"<span>".__("Dynamic Forms")."</span>" .
			"</a>");
		
		if (JCORE_VERSION >= '0.3') {
			$this->add('Content', 'PostsHandling', 
				"<a href='".url::uri('ALL')."?path=admin/content/postshandling' " .
					"title='".
						htmlspecialchars(
							__("Copy and/or Move posts from one page to another"), 
							ENT_QUOTES).
					"'>" .
					"<span>".__("Moving Posts")."</span>" .
				"</a>");
			
			$this->add('Content', 'ContentFiles', 
				"<a href='".url::uri('ALL')."?path=admin/content/contentfiles' " .
					"title='".
						htmlspecialchars(
							__("Manage / Upload files separately"), 
							ENT_QUOTES).
					"'>" .
					"<span>".__("File Manager")."</span>" .
				"</a>");
		}
		
		$this->add('Site', 'Settings', 
			"<a href='".url::uri('ALL')."?path=admin/site/settings' " .
				"title='".
					htmlspecialchars(
						__("Change settings like Webmaster Email, " .
							"Site title and so on"), 
						ENT_QUOTES).
				"'>" .
				"<span>".__("Global Settings")."</span>" .
			"</a>");
		
		$this->add('Site', 'Blocks', 
			"<a href='".url::uri('ALL')."?path=admin/site/blocks' " .
				"title='".
					htmlspecialchars(
						__("Set up your site's layout / look"), 
						ENT_QUOTES).
				"'>" .
				"<span>".__("Layout Blocks")."</span>" .
			"</a>");
		
		$this->add('Site', 'Menus', 
			"<a href='".url::uri('ALL')."?path=admin/site/menus' " .
				"title='".
					htmlspecialchars(
						__("Create multiple menu areas"), 
						ENT_QUOTES).
				"'>" .
				"<span>".__("Menu Blocks")."</span>" .
			"</a>");
		
		if (JCORE_VERSION >= '0.7')
			$this->add('Site', 'Template-Manager', 
				"<a href='".url::uri('ALL')."?path=admin/site/template' " .
					"title='".
						htmlspecialchars(
							__("Change the look of your website"), 
							ENT_QUOTES).
					"'>" .
					"<span>".__("Template Manager")."</span>" .
				"</a>");
		elseif (JCORE_VERSION >= '0.3')
			$this->add('Site', 'Template', 
				"<a href='".url::uri('ALL')."?path=admin/site/template' " .
					"title='".
						htmlspecialchars(
							__("Edit template CSS / JS files and upload images"), 
							ENT_QUOTES).
					"'>" .
					"<span>".__("Template Editor")."</span>" .
				"</a>");
		
		$this->add('Site', 'RSS', 
			"<a href='".url::uri('ALL')."?path=admin/site/rss' " .
				"title='".
					htmlspecialchars(
						__("Share your site's content in a standardized format"), 
						ENT_QUOTES).
				"'>" .
				"<span>".__("RSS Feeds")."</span>" .
			"</a>");
		
		if (JCORE_VERSION >= '0.4')
			$this->add('Site', 'Notes', 
				"<a href='".url::uri('ALL')."?path=admin/site/notes' " .
					"title='".
						htmlspecialchars(
							__("Store notes and/or todos"), 
							ENT_QUOTES).
					"'>" .
					"<span>".__("Admin Notes")."</span>" .
				"</a>");
		
		if (JCORE_VERSION >= '0.7') {
			$this->add('Site', 'FavoriteLinks', 
				"<a href='".url::uri('ALL')."?path=admin/site/favoritelinks' " .
					"title='".
						htmlspecialchars(
							__("Links to quickly access your favorite places"), 
							ENT_QUOTES).
					"'>" .
					"<span>".__("Favorite Links")."</span>" .
				"</a>");
			
			$this->add('Site', 'NotificationEmails', 
				"<a href='".url::uri('ALL')."?path=admin/site/notificationemails' " .
					"title='".
						htmlspecialchars(
							__("Modify emails that are sent out by the system"), 
							ENT_QUOTES).
					"'>" .
					"<span>".__("Notification Emails")."</span>" .
				"</a>");
		}
		
		$this->add('Site', 'Languages', 
			"<a href='".url::uri('ALL')."?path=admin/site/languages' " .
				"title='".
					htmlspecialchars(
						__("Define additional languages"), 
						ENT_QUOTES).
				"'>" .
				"<span>".__("Site Languages")."</span>" .
			"</a>");
		
		if (JCORE_VERSION >= '0.3') {
			$this->add('Site', 'Security', 
				"<a href='".url::uri('ALL')."?path=admin/site/security' " .
					"title='".
						htmlspecialchars(
							__("Manage Brute Force and Password Trading protection"), 
							ENT_QUOTES).
					"'>" .
					"<span>".__("Security Alerts")."</span>" .
				"</a>");
			
			$this->add('Site', 'Sitemap', 
				"<a href='".url::uri('ALL')."?path=admin/site/sitemap' " .
					"title='".
						htmlspecialchars(
							__("Regenerate and/or edit XML Sitemap file"), 
							ENT_QUOTES).
					"'>" .
					"<span>".__("XML Sitemap")."</span>" .
				"</a>");
		}
		
		$this->add('Members', 'Users', 
			"<a href='".url::uri('ALL')."?path=admin/members/users' " .
				"title='".
					htmlspecialchars(
						__("Manage users, set permissions"), 
						ENT_QUOTES).
				"'>" .
				"<span>".__("Users")."</span>" .
			"</a>");
		
		if (JCORE_VERSION >= '0.8')
			$this->add('Members', 'UserGroups', 
				"<a href='".url::uri('ALL')."?path=admin/members/usergroups' " .
					"title='".
						htmlspecialchars(
							__("Manage user groups, set group permissions"), 
							ENT_QUOTES).
					"'>" .
					"<span>".__("User Groups")."</span>" .
				"</a>");
		
		$this->add('Members', 'MassEmail', 
			"<a href='".url::uri('ALL')."?path=admin/members/massemail' " .
				"title='".
					htmlspecialchars(
						__("Contact site members all at once"), 
						ENT_QUOTES).
				"'>" .
				"<span>".__("Mass Email")."</span>" .
			"</a>");
			
		$this->add('Members', 'Logout', 
			"<a href='".url::uri('ALL')."?logout=1' " .
				"title='".
					htmlspecialchars(
						__("Logout and close this session"), 
						ENT_QUOTES).
				"'>" .
				"<span>".__("Logout")."</span>" .
			"</a>");
			
		modules::loadAdmin();
	}
	
	static function path($level= 0) {
		return url::path($level);
	}
	
	static function getPathID($level = 0) {
		return url::getPathID($level);
	}
	
	function checkPath($adminpath) {
		// We return ok if the path is not set for compatibility reasons
		if (!$adminpath)
			return true;
			
		$curpath = trim(preg_replace('/\/[0-9]+/', '', $this->path()), '/');
		
		if ((is_array($adminpath) && in_array($curpath, $adminpath)) ||
			$curpath == $adminpath)
			return true;
		
		return false;
	}
	
	static function displayCSSLinks() {
		if (defined('JCORE_URL'))
			echo
				"<link rel='stylesheet' href='".url::jCore()."template/admin.css?revision=".JCORE_VERSION."' " .
					"type='text/css' />\n";
		
		echo
			"<link rel='stylesheet' href='".url::site()."template/admin.css?revision=".JCORE_VERSION."' " .
				"type='text/css' />\n";
	}
	
	static function displayPath() {
		url::displayPath();
	}
	
	static function displayTitle($title, $ownertitle = null) {
		echo
			"<h3 class='admin-title'>" .
				$title .
				($ownertitle?
					": <span>" .
					$ownertitle.
					"</span>":
					null) .
			"</h3>";
	}
	
	static function displayHeader() {
		if (JCORE_VERSION <= '0.2')
			admin::displayCSSLinks();
		
		echo
			"<div class='posts'>" .
			"<div class='post admin'>";
		
		if (JCORE_VERSION >= '0.7' && 
			$GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'] &&
			(!defined('ADMIN_FAVORITE_LINKS_ENABLED') || ADMIN_FAVORITE_LINKS_ENABLED))
		{
			$links = new favoriteLinks();
			$links->display();
			unset($links);
		}
		
		echo
				"<h2 class='post-title'>" .
					__("Administration Section") .
				"</h2>" .
				"<div class='admin-location'>" .
				"<span class='path-separator-start'>// </span>";
		
		admin::displayPath();
					
		echo
				"</div>" .
				"<div class='post-content'>";
	}
	
	static function displayItemData($title, $value = null) {
		if (!isset($value)) {
			echo
				"<div class='form-entry preview'>" .
					$title .
				"</div>";
			return;
		}
		
		echo
			"<div class='form-entry preview'>" .
				"<div class='form-entry-title'>" .
					$title.":" .
				"</div>" .
				"<div class='form-entry-content bold'>" .
					$value .
				"</div>" .
			"</div>";
	}
	
	static function displayFooter() {
		echo
				"</div>" .
			"</div>" .
			"</div>";
	}
	
	static function displaySection($sectionid) {
		if (!count(admin::$sections[$sectionid]['Items']))
			return;
		
		echo 
		"<div tabindex='0' class='fc" .
			form::fcState('fc'.substr(strtolower(trim($sectionid)), 0, 3), true) .
			"'>" .
			"<a class='fc-title' name='fc" .
				substr(strtolower(trim($sectionid)), 0, 3) .
				"'>" .
				admin::$sections[$sectionid]['Title'] .
			"</a>" .
			"<div class='fc-content'>";
		
		foreach(admin::$sections[$sectionid]['Items'] as $itemid => $item) {
			$itemscount = 0;
			$class = strtolower($itemid);
			
			if (ADMIN_ITEMS_COUNTER_ENABLED &&
				class_exists($class) && method_exists($class, "countAdminItems")) 
			{
				$class = new $class;
				$itemscount = $class->countAdminItems();
				unset($class);
			}
			
			echo "<div class='admin-section-item " .
						"as-".strtolower($sectionid)."-".strtolower($itemid)."'>" .
					($itemscount?
						"<span class='counter'>" .
							"<span>" .
								"<span>" .
								(int)$itemscount .
								"</span>" .
							"</span>" .
						"</span>":
						null) .
					$item .
				"</div>";
		}
		
		echo
				"<div class='clear-both'></div>" .
			"</div>" .
		"</div>";
	}
	
	function display() {
		$this->load();
		
		if (!$GLOBALS['USER']->loginok) {
			$this->displayHeader();
			
			$GLOBALS['USER']->displayLogin();
			
			$this->displayFooter();
			return;
		}
		
		if (!$GLOBALS['USER']->data['Admin']) {
			$this->displayHeader();
		
			echo
				"<p></p>";
			
			tooltip::display(
				__("You do not have enough permission to access this area!") .
				" <a href='?logout=1'>" .
				__("Logout") .
				"</a>",
				TOOLTIP_NOTIFICATION);
				
			$this->displayFooter();
			return;
		}
		
		$userpermission = userPermissions::check($GLOBALS['USER']->data['ID']);
		
		if (!$userpermission['PermissionType']) {
			$this->displayHeader();
			
			echo
				"<p></p>";
			
			tooltip::display(
				__("You do not have permission to access this path!") .
				" <a href='".url::referer()."'>" .
				__("Go Back") .
				"</a>",
				TOOLTIP_ERROR);
			
			$this->displayFooter();
			return;
		}
		
		$expitems = array();
		$path = url::path();
		
		if ($path)
			$expitems = explode('/', $path);
		
		// If admin/section/xxx selected
		if (count($expitems) > 2) {
			$class = preg_replace('/[^a-zA-Z0-9\_\-]/', '', 
				$expitems[count($expitems)-1]);
				
			if (!class_exists($class) || !method_exists($class,'displayAdmin')) {
				$this->displayHeader();
		
				echo
					"<p></p>";
				
				tooltip::display(
					__("Invalid path!"),
					TOOLTIP_ERROR);
				
				$this->displayFooter();
				return;
			}
			
			$item = new $class;
			
			if (!$this->checkPath($item->adminPath)) {
				$this->displayHeader();
				
				echo
					"<p></p>";
				
				tooltip::display(
					__("Invalid path!"),
					TOOLTIP_ERROR);
					
				$this->displayFooter();
				return;
			}
			
			$item->userPermissionType = $userpermission['PermissionType'];
			$item->userPermissionIDs = $userpermission['PermissionIDs'];
			
			if (method_exists($class,'setupAdmin'))
				$item->setupAdmin();
				
			$this->displayHeader();
			
			if (isset($expitems[count($expitems)-2]) && 
				$expitems[count($expitems)-2] == 'modules' && !modules::installed($class)) 
			{
				$item->displayInstall();
			} else {
				$item->displayAdmin();
			}
			
			unset($item);
			
			$this->displayFooter();
			return;
		}
		
		$this->displayHeader();
		
		echo "<div class='admin-content'>";
		
		foreach(admin::$sections as $sectionid => $section) {
			if (!count($expitems) || count($expitems) == 1 || 
				in_array(strtolower($sectionid), $expitems))
				$this->displaySection($sectionid);
		}
		
		echo "</div>";
		
		$this->displayFooter();
	}
}

?>
