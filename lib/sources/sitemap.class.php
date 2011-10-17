<?php

/***************************************************************************
 *            sitemap.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
class _sitemap {
	var $file;
	var $urls = array();
	var $ajaxRequest = null;
	var $adminPath = 'admin/site/sitemap';
	
	function __construct() {
		$this->file = SITE_PATH.'sitemap.xml';
	}
	
	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'sitemap::setupAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'sitemap::setupAdmin', $this, $handled);
			
			return $handled;
		}
		
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=' .
			(JCORE_VERSION >= '0.8'?'admin/content/pages':'admin/content/menuitems'));
		favoriteLinks::add(
			__('Languages'), 
			'?path=admin/site/languages');
		favoriteLinks::add(
			__('View Website'), 
			SITE_URL);
		
		api::callHooks(API_HOOK_AFTER,
			'sitemap::setupAdmin', $this);
	}
	
	function verifyAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'sitemap::verifyAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'sitemap::verifyAdmin', $this, $handled);
			
			return $handled;
		}
		
		$regenerate = null;
		if (isset($_POST['regenerate']) && (string)$_POST['regenerate'])
			$regenerate = true;
		
		if ($regenerate) {
			if (!isset($_POST['_FormSecurityToken']) || 
				!security::checkToken($_POST['_FormSecurityToken'])) 
			{
				api::callHooks(API_HOOK_AFTER,
					'comments::verifyAdmin', $this);
				return false;
			}
			
			$pages = new pages();
			
			if (!$pages->updateSitemap()) {
				tooltip::display(
					__("Couldn't regenerate sitemap XML file.")." " .
					sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
						"sitemap.xml"),
					TOOLTIP_ERROR);
			
				unset($pages);
				
				api::callHooks(API_HOOK_AFTER,
					'sitemap::verifyAdmin', $this);
				
				return false;
			}
			
			tooltip::display(
				__("Sitemap XML file has been successfully regenerated."),
				TOOLTIP_SUCCESS);
			
			unset($pages);
			
			api::callHooks(API_HOOK_AFTER,
				'sitemap::verifyAdmin', $this, $regenerate);
			
			return true;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'sitemap::verifyAdmin', $this);
		
		return false;
	}
	
	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'sitemap::displayAdminTitle', $this, $ownertitle);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'sitemap::displayAdminTitle', $this, $ownertitle, $handled);
			
			return $handled;
		}
		
		admin::displayTitle(
			__("Sitemap XML File Administration"),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'sitemap::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'sitemap::displayAdminDescription', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'sitemap::displayAdminDescription', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<p>" .
				sprintf(__("This file (%s) gets updated automatically every time you " .
					"create / edit / delete a page but you can regenerate or modify it " .
					"manually below."),
					"<a href='".SITE_URL."sitemap.xml' target='_blank'>" .
						SITE_URL."sitemap.xml</a>") .
			"</p>";
		
		api::callHooks(API_HOOK_AFTER,
			'sitemap::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'sitemap::displayAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'sitemap::displayAdmin', $this, $handled);
			
			return $handled;
		}
		
		$editor = new sitemapFileEditor();
		
		$editor->file = $this->file;
		$editor->uriRequest = url::path();
		
		$this->displayAdminTitle();
		$this->displayAdminDescription();
			
		$this->verifyAdmin();
		
		echo
			"<div class='admin-content'>";
			
		$editor->display();
		unset($editor);
		
		echo
			"</div>"; //admin-content
		
		api::callHooks(API_HOOK_AFTER,
			'sitemap::displayAdmin', $this);
	}
	
	function getUrlID($link = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'sitemap::getUrlID', $this, $link);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'sitemap::getUrlID', $this, $link, $handled);
			
			return $handled;
		}
		
		if (!$link) {
			$result = count($this->urls)-1;
			
		} else {
			$urlid = null;
			
			foreach($this->urls as $urlnum => $url) {
				if (!isset($url['Link']))
					continue;
				
				if ($url['Link'] == $link) {
					$urlid = $urlnum;
					break;
				}
			}
			
			$result = $urlid;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'sitemap::getUrlID', $this, $link, $result);
		
		return $result;
	}
	
	function add($url) {
		if (!isset($url) || !is_array($url))
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'sitemap::add', $this, $url);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'sitemap::add', $this, $url, $handled);
			
			return $handled;
		}
		
		if (!isset($url['Link']))
			$url['Link'] = SITE_URL;
		
		if (!isset($url['LastModified']))
			$url['LastModified'] = date("Y-m-d H:i:s");
		
		if (!isset($url['ChangeFreq']))
			$url['ChangeFreq'] = 'daily';
		
		if (!isset($url['Priority']))
			$url['Priority'] = '0.5';
		
		$this->urls[] = $url;
		
		api::callHooks(API_HOOK_AFTER,
			'sitemap::add', $this, $url, $url);
		
		return true;
	}
	
	function edit($link, $url) {
		if (!isset($link))
			return false;
		
		if (!isset($url) || !is_array($url))
			return false;
		
		$urlid = $this->getUrlID($link);
		if (!isset($urlid))
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'sitemap::edit', $this, $link ,$url);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'sitemap::edit', $this, $link ,$url, $handled);
			
			return $handled;
		}
		
		if (isset($url['Link']))
			$this->urls[$urlid]['Link'] = $url['Link'];
		
		if (isset($url['LastModified']))
			$this->urls[$urlid]['LastModified'] = $url['LastModified'];
		
		if (isset($url['ChangeFreq']))
			$this->urls[$urlid]['ChangeFreq'] = $url['ChangeFreq'];
		
		if (isset($url['Priority']))
			$this->urls[$urlid]['Priority'] = $url['Priority'];
		
		api::callHooks(API_HOOK_AFTER,
			'sitemap::edit', $this, $link ,$url, $urlid);
		
		return true;
	}
	
	function delete($link) {
		if (!isset($link))
			return false;
		
		$urlid = $this->getUrlID($link);
		if (!isset($urlid))
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'sitemap::delete', $this, $link);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'sitemap::delete', $this, $link, $handled);
			
			return $handled;
		}
		
		array_splice($this->urls, $urlid, 1);
		
		api::callHooks(API_HOOK_AFTER,
			'sitemap::delete', $this, $link);
		
		return true;
	}
	
	function load($file = null) {
		if (!$file)
			$file = $this->file;
		
		$this->clear();
		
		preg_match_all('/<url\b[^>]*>(.*?)<\/url>/is', 
			@file_get_contents($file), $urls);
		
		if (!isset($urls[1]))
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'sitemap::load', $this, $file);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'sitemap::load', $this, $file, $handled);
			
			return $handled;
		}
		
		foreach($urls[1] as $url) {
			preg_match_all('/<(loc|lastmod|changefreq|priority)\b[^>]*>(.*?)<\/\1>/is', 
				$url, $matches);
			
			if (!isset($matches[1]))
				continue;
			
			$loadurl = array();
			foreach($matches[1] as $key => $value) {
				
				switch(strtolower($value)) {
					case 'loc':
						$loadurl['Link'] = $matches[2][$key];
						break;
					case 'lastmod':
						$loadurl['LastModified'] = date('Y-m-d H:i:s', 
							strtotime($matches[2][$key]));
						break;
					case 'changefreq':
						$loadurl['ChangeFreq'] = $matches[2][$key];
						break;
					case 'priority':
						$loadurl['Priority'] = $matches[2][$key];
						break;
				}
			}
			
			$this->add($loadurl);
		}
		
		api::callHooks(API_HOOK_AFTER,
			'sitemap::load', $this, $file);
	}
	
	function save($file = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'sitemap::save', $this, $file);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'sitemap::save', $this, $file, $handled);
			
			return $handled;
		}
		
		if (!$file)
			$file = $this->file;
		
		$sitemap = 
			"<?xml version=\"1.0\" encoding=\"".PAGE_CHARSET."\"?>\n" .
			"<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n" .
			"	xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"	xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n" .
			"		http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";
		
		foreach($this->urls as $url) {
			$sitemap .=
				"	<url>\n" .
				"		<loc>".$url['Link']."</loc>\n" .
				"		<lastmod>".
							calendar::datetime($url['LastModified'], "%Y-%m-%dT%H:%M:%S") .
							preg_replace('/^(.[0-9]{2})([0-9]{2})/', '\1:\2', calendar::datetime($url['LastModified'], "%z")) .
						"</lastmod>\n" .
				"		<changefreq>".$url['ChangeFreq']."</changefreq>\n" .
				"		<priority>".$url['Priority']."</priority>\n" .
				"	</url>\n";
		}
				    
		$sitemap .= 
			"</urlset>\n";
		
		$result = false;
		if (files::save($file, $sitemap))
			$result = true;
		
		api::callHooks(API_HOOK_AFTER,
			'sitemap::save', $this, $file, $result);
		
		return $result;
	}
	
	function clear() {
		$this->urls = array();
	}
	
	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'sitemap::ajaxRequest', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'sitemap::ajaxRequest', $this, $handled);
			
			return $handled;
		}
		
		$editor = new fileEditor();
		$editor->file = $this->file;
		$editor->uriRequest = url::path();
		$editor->ajaxRequest = $this->ajaxRequest;
		$editor->ajaxRequest();
		unset($editor);
		
		$result = true;
		api::callHooks(API_HOOK_AFTER,
			'sitemap::ajaxRequest', $this, $result);
		
		return $result;
	}
}

?>