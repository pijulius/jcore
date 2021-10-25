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
include_once('lib/commentsatglance.class.php');
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
include_once('lib/updates.class.php');
include_once('lib/modulemanager.class.php');
include_once('lib/templatemanager.class.php');

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
		$handled = api::callHooks(API_HOOK_BEFORE,
			'admin::add', $_ENV, $section, $itemid, $item);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'admin::add', $_ENV, $section, $itemid, $item, $handled);

			return $handled;
		}

		preg_match('/(\?|&)path=(.*?)(&|\'|")/i', $item, $matches);

		$userpermission = userPermissions::check((int)$GLOBALS['USER']->data['ID'],
			(isset($matches[2])?
				$matches[2]:
				null));

		$result = false;

		if ($userpermission['PermissionType']) {
			$result = $itemid;
			admin::$sections[$section]['Items'][$itemid] = $item;
		}

		api::callHooks(API_HOOK_AFTER,
			'admin::add', $_ENV, $section, $itemid, $item);

		return $result;
	}

	static function remove($section, $itemid) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'admin::remove', $_ENV, $section, $itemid);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'admin::remove', $_ENV, $section, $itemid, $handled);

			return $handled;
		}

		unset(admin::$sections[$section]['Items'][$itemid]);

		api::callHooks(API_HOOK_AFTER,
			'admin::remove', $_ENV, $section, $itemid);
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

	function setup() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'admin::setup', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'admin::setup', $this, $handled);

			return $handled;
		}

		$this->add('Content',
			(JCORE_VERSION >= '0.8'?'Pages':'MenuItems'),
			"<a href='".url::uri('ALL')."?path=" .
				(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems')."' " .
				"title='".
					htmlchars(
						__("Manage the content (pages) of your site"),
						ENT_QUOTES).
				"'>" .
				"<span>".__("Content Management")."</span>" .
			"</a>");

		if (JCORE_VERSION >= '0.3')
			$this->add('Content', 'PostsAtGlance',
				"<a href='".url::uri('ALL')."?path=admin/content/postsatglance' " .
					"title='".
						htmlchars(
							__("Quickly create / modify posts"),
							ENT_QUOTES).
					"'>" .
					"<span>".__("Posts at Glance")."</span>" .
				"</a>");

		if (JCORE_VERSION >= '1.0')
			$this->add('Content', 'CommentsAtGlance',
				"<a href='".url::uri('ALL')."?path=admin/content/commentsatglance' " .
					"title='".
						htmlchars(
							__("Quickly moderate / manage comments"),
							ENT_QUOTES).
					"'>" .
					"<span>".__("Comments at Glance")."</span>" .
				"</a>");

		$this->add('Content', 'DynamicForms',
			"<a href='".url::uri('ALL')."?path=admin/content/dynamicforms' " .
				"title='".
					htmlchars(
						__("Create / Modify custom submit forms"),
						ENT_QUOTES).
				"'>" .
				"<span>".__("Dynamic Forms")."</span>" .
			"</a>");

		if (JCORE_VERSION >= '0.3') {
			$this->add('Content', 'PostsHandling',
				"<a href='".url::uri('ALL')."?path=admin/content/postshandling' " .
					"title='".
						htmlchars(
							__("Copy and/or Move posts from one page to another"),
							ENT_QUOTES).
					"'>" .
					"<span>".__("Moving Posts")."</span>" .
				"</a>");

			$this->add('Content', 'ContentFiles',
				"<a href='".url::uri('ALL')."?path=admin/content/contentfiles' " .
					"title='".
						htmlchars(
							__("Manage / Upload files separately"),
							ENT_QUOTES).
					"'>" .
					"<span>".__("File Manager")."</span>" .
				"</a>");
		}

		$this->add('Content', 'Ads',
			"<a href='".url::uri('ALL')."?path=admin/content/ads' " .
				"title='".
					htmlchars(
						__("Upload / Add advertisements"),
						ENT_QUOTES).
				"'>" .
				"<span>".htmlchars(__("Ads & Banners"))."</span>" .
			"</a>");

		$this->add('Site', 'Settings',
			"<a href='".url::uri('ALL')."?path=admin/site/settings' " .
				"title='".
					htmlchars(
						__("Change settings like Webmaster Email, " .
							"Site title and so on"),
						ENT_QUOTES).
				"'>" .
				"<span>".__("Global Settings")."</span>" .
			"</a>");

		$this->add('Site', 'Blocks',
			"<a href='".url::uri('ALL')."?path=admin/site/blocks' " .
				"title='".
					htmlchars(
						__("Set up your site's layout / look"),
						ENT_QUOTES).
				"'>" .
				"<span>".__("Layout Blocks")."</span>" .
			"</a>");

		$this->add('Site', 'Menus',
			"<a href='".url::uri('ALL')."?path=admin/site/menus' " .
				"title='".
					htmlchars(
						__("Create multiple menu areas"),
						ENT_QUOTES).
				"'>" .
				"<span>".__("Menu Blocks")."</span>" .
			"</a>");

		if (JCORE_VERSION >= '0.3')
			$this->add('Site', 'Modules',
				"<a href='".url::uri('ALL')."?path=admin/site/modules' " .
					"title='".
						htmlchars(
							__("Add additional functionality to your website"),
							ENT_QUOTES).
					"'>" .
					"<span>".__("Module Manager")."</span>" .
				"</a>");

		if (JCORE_VERSION >= '0.7')
			$this->add('Site', 'TemplateManager',
				"<a href='".url::uri('ALL')."?path=admin/site/template' " .
					"title='".
						htmlchars(
							__("Change the look of your website"),
							ENT_QUOTES).
					"'>" .
					"<span>".__("Template Manager")."</span>" .
				"</a>");
		elseif (JCORE_VERSION >= '0.3')
			$this->add('Site', 'Template',
				"<a href='".url::uri('ALL')."?path=admin/site/template' " .
					"title='".
						htmlchars(
							__("Edit template CSS / JS files and upload images"),
							ENT_QUOTES).
					"'>" .
					"<span>".__("Template Editor")."</span>" .
				"</a>");

		$this->add('Site', 'RSS',
			"<a href='".url::uri('ALL')."?path=admin/site/rss' " .
				"title='".
					htmlchars(
						__("Share your site's content in a standardized format"),
						ENT_QUOTES).
				"'>" .
				"<span>".__("RSS Feeds")."</span>" .
			"</a>");

		if (JCORE_VERSION >= '0.4')
			$this->add('Site', 'Notes',
				"<a href='".url::uri('ALL')."?path=admin/site/notes' " .
					"title='".
						htmlchars(
							__("Store notes and/or todos"),
							ENT_QUOTES).
					"'>" .
					"<span>".__("Admin Notes")."</span>" .
				"</a>");

		if (JCORE_VERSION >= '0.7') {
			$this->add('Site', 'FavoriteLinks',
				"<a href='".url::uri('ALL')."?path=admin/site/favoritelinks' " .
					"title='".
						htmlchars(
							__("Links to quickly access your favorite places"),
							ENT_QUOTES).
					"'>" .
					"<span>".__("Favorite Links")."</span>" .
				"</a>");

			$this->add('Site', 'NotificationEmails',
				"<a href='".url::uri('ALL')."?path=admin/site/notificationemails' " .
					"title='".
						htmlchars(
							__("Modify emails that are sent out by the system"),
							ENT_QUOTES).
					"'>" .
					"<span>".__("Notification Emails")."</span>" .
				"</a>");
		}

		$this->add('Site', 'Languages',
			"<a href='".url::uri('ALL')."?path=admin/site/languages' " .
				"title='".
					htmlchars(
						__("Define additional languages"),
						ENT_QUOTES).
				"'>" .
				"<span>".__("Site Languages")."</span>" .
			"</a>");

		if (JCORE_VERSION >= '0.3') {
			$this->add('Site', 'Security',
				"<a href='".url::uri('ALL')."?path=admin/site/security' " .
					"title='".
						htmlchars(
							__("Manage Brute Force and Password Trading protection"),
							ENT_QUOTES).
					"'>" .
					"<span>".__("Security Alerts")."</span>" .
				"</a>");

			$this->add('Site', 'Sitemap',
				"<a href='".url::uri('ALL')."?path=admin/site/sitemap' " .
					"title='".
						htmlchars(
							__("Regenerate and/or edit XML Sitemap file"),
							ENT_QUOTES).
					"'>" .
					"<span>".__("XML Sitemap")."</span>" .
				"</a>");
		}

		if (JCORE_VERSION >= '0.6') {
			$this->add('Site', 'Updates',
				"<a href='".url::uri('ALL')."?path=admin/site/updates' " .
					"title='".
						htmlchars(
							__("Install and check for updates"),
							ENT_QUOTES).
					"'>" .
					"<span>".__("Update Manager")."</span>" .
				"</a>");
		}

		$this->add('Members', 'Users',
			"<a href='".url::uri('ALL')."?path=admin/members/users' " .
				"title='".
					htmlchars(
						__("Manage users, set permissions"),
						ENT_QUOTES).
				"'>" .
				"<span>".__("Users")."</span>" .
			"</a>");

		if (JCORE_VERSION >= '0.8')
			$this->add('Members', 'UserGroups',
				"<a href='".url::uri('ALL')."?path=admin/members/usergroups' " .
					"title='".
						htmlchars(
							__("Manage user groups, set group permissions"),
							ENT_QUOTES).
					"'>" .
					"<span>".__("User Groups")."</span>" .
				"</a>");

		$this->add('Members', 'MassEmail',
			"<a href='".url::uri('ALL')."?path=admin/members/massemail' " .
				"title='".
					htmlchars(
						__("Contact site members all at once"),
						ENT_QUOTES).
				"'>" .
				"<span>".__("Mass Email")."</span>" .
			"</a>");

		$this->add('Members', 'Logout',
			"<a href='".url::uri('ALL')."?logout=1' " .
				"title='".
					htmlchars(
						__("Logout and close this session"),
						ENT_QUOTES).
				"'>" .
				"<span>".__("Logout")."</span>" .
			"</a>");

		modules::loadAdmin();

		api::callHooks(API_HOOK_AFTER,
			'admin::setup', $this);
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
		$handled = api::callHooks(API_HOOK_BEFORE,
			'admin::displayPath');

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'admin::displayPath', $handled);

			return $handled;
		}

		url::displayPath();

		api::callHooks(API_HOOK_AFTER,
			'admin::displayPath');
	}

	static function displayTitle($title, $ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'admin::displayTitle', $_ENV, $title, $ownertitle);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'admin::displayTitle', $_ENV, $title, $ownertitle, $handled);

			return $handled;
		}

		echo
			"<h3 class='admin-title'>" .
				$title .
				($ownertitle?
					": <span>" .
					$ownertitle.
					"</span>":
					null) .
			"</h3>";

		api::callHooks(API_HOOK_AFTER,
			'admin::displayTitle', $_ENV, $title, $ownertitle);
	}

	static function displayHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'admin::displayHeader');

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'admin::displayHeader', $handled);

			return $handled;
		}

		if (JCORE_VERSION <= '0.2')
			admin::displayCSSLinks();

		echo
			"<div class='posts'>" .
			"<div class='post admin'>";

		if (JCORE_VERSION >= '0.7' &&
			$GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'] &&
			(!defined('ADMIN_FAVORITE_LINKS_ENABLED') || ADMIN_FAVORITE_LINKS_ENABLED))
		{
			favoriteLinks::add(
				__('New Post'),
				'?path=admin/content/postsatglance#adminform');

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

		api::callHooks(API_HOOK_AFTER,
			'admin::displayHeader');
	}

	static function displayItemData($title, $value = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'admin::displayItemData', $_ENV, $title, $value);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'admin::displayItemData', $_ENV, $title, $value, $handled);

			return $handled;
		}

		if (!isset($value)) {
			echo
				"<div class='form-entry preview'>" .
					$title .
				"</div>";
		} else {
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

		api::callHooks(API_HOOK_AFTER,
			'admin::displayItemData', $_ENV, $title, $value);
	}

	static function displayFooter() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'admin::displayFooter');

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'admin::displayFooter', $handled);

			return $handled;
		}

		echo
				"</div>" .
			"</div>" .
			"</div>";

		api::callHooks(API_HOOK_AFTER,
			'admin::displayFooter');
	}

	static function displaySection($sectionid) {
		if (!count(admin::$sections[$sectionid]['Items']))
			return;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'admin::displaySection', $_ENV, $sectionid);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'admin::displaySection', $_ENV, $sectionid, $handled);

			return $handled;
		}

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

			if ($class == 'modules')
				$class = 'moduleManager';

			if ($class == 'template')
				$class = 'templateManager';

			if (ADMIN_ITEMS_COUNTER_ENABLED &&
				class_exists($class) && method_exists($class, "countAdminItems"))
			{
				$class = new $class;
				$itemscount = $class->countAdminItems();
				unset($class);
			}

			echo "<div class='admin-section-item " .
						"as-".strtolower($sectionid)."-".strtolower($itemid)."'>";

			if ($itemscount && (!is_array($itemscount) ||
				(isset($itemscount['Rows']) && $itemscount['Rows'])))
				counter::display($itemscount);

			echo
					$item .
				"</div>";
		}

		echo
				"<div class='clear-both'></div>" .
			"</div>" .
		"</div>";

		api::callHooks(API_HOOK_AFTER,
			'admin::displaySection', $_ENV, $sectionid);
	}

	function display() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'admin::display', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'admin::display', $this, $handled);

			return $handled;
		}

		$this->setup();

		if (!$GLOBALS['USER']->loginok) {
			$this->displayHeader();

			$GLOBALS['USER']->displayLogin();

			$this->displayFooter();

			api::callHooks(API_HOOK_AFTER,
				'admin::display', $this);

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

			api::callHooks(API_HOOK_AFTER,
				'admin::display', $this);

			return;
		}

		$userpermission = userPermissions::check((int)$GLOBALS['USER']->data['ID']);

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

			api::callHooks(API_HOOK_AFTER,
				'admin::display', $this);

			return;
		}

		$installmodule = false;
		$expitems = array();
		$path = url::path();

		if ($path)
			$expitems = explode('/', $path);

		// If admin/section/xxx selected
		if (count($expitems) > 2) {
			$class = preg_replace('/[^a-zA-Z0-9\_\-]/', '',
				$expitems[count($expitems)-1]);

			if (isset($expitems[1]) && isset($expitems[2]) &&
				$expitems[1] == 'modules' && !modules::installed($expitems[2]))
			{
				$class = preg_replace('/[^a-zA-Z0-9\_\-]/', '',
					$expitems[2]);

				$installmodule = true;
				url::setPath('admin/modules/'.$class);
			}

			if ($class == 'modules')
				$class = 'moduleManager';

			if ($class == 'template')
				$class = 'templateManager';

			if (!class_exists($class) || !method_exists($class,'displayAdmin')) {
				$this->displayHeader();

				echo
					"<p></p>";

				tooltip::display(
					__("Invalid path!"),
					TOOLTIP_ERROR);

				$this->displayFooter();

				api::callHooks(API_HOOK_AFTER,
					'admin::display', $this);

				return;
			}

			$item = new $class;

			if (isset($item->adminPath) && !$this->checkPath($item->adminPath)) {
				$this->displayHeader();

				echo
					"<p></p>";

				tooltip::display(
					__("Invalid path!"),
					TOOLTIP_ERROR);

				$this->displayFooter();

				api::callHooks(API_HOOK_AFTER,
					'admin::display', $this);

				return;
			}

			$item->userPermissionType = $userpermission['PermissionType'];
			$item->userPermissionIDs = $userpermission['PermissionIDs'];

			if (method_exists($class,'setupAdmin'))
				$item->setupAdmin();

			$this->displayHeader();

			if ($installmodule)
				$item->displayInstall();
			else
				$item->displayAdmin();

			unset($item);

			$this->displayFooter();

			api::callHooks(API_HOOK_AFTER,
				'admin::display', $this);

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

		api::callHooks(API_HOOK_AFTER,
			'admin::display', $this);
	}
}

?>
