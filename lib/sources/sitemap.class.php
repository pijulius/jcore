<?php

/***************************************************************************
 *            sitemap.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
include_once('lib/fileeditor.class.php');

class _sitemapFileEditor extends fileEditor {
	function displayForm(&$form) {
		$form->add(
			__("Regenerate"),
			'regenerate',
			FORM_INPUT_TYPE_BUTTON);
		
		$form->addAttributes(
			"onclick=\"window.location='".url::uri('regenerate') .
				"&amp;regenerate=1'\"");
		
		parent::displayForm($form);
	}
}
 
class _sitemap {
	var $file;
	var $urls = array();
	var $ajaxRequest = null;
	var $adminPath = 'admin/site/sitemap';
	
	function __construct() {
		$this->file = SITE_PATH.'sitemap.xml';
	}
	
	function setupAdmin() {
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=admin/content/pages');
		favoriteLinks::add(
			__('Languages'), 
			'?path=admin/site/languages');
		favoriteLinks::add(
			__('View Website'), 
			SITE_URL);
	}
	
	function verifyAdmin() {
		$regenerate = null;
		if (isset($_GET['regenerate']) && $_GET['regenerate'])
			$regenerate = true;
		
		if ($regenerate) {
			$pages = new pages();
			
			if (!$pages->updateSitemap()) {
				tooltip::display(
					__("Couldn't regenerate sitemap XML file.")." " .
					sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
						"sitemap.xml"),
					TOOLTIP_ERROR);
			
				unset($pages);
				return false;
			}
			
			tooltip::display(
				__("Sitemap XML file has been successfully regenerated."),
				TOOLTIP_SUCCESS);
			
			unset($pages);
			return true;
		}
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__("Sitemap XML File Administration"),
			$ownertitle);
	}
	
	function displayAdminDescription() {
		echo
			"<p>" .
				sprintf(__("This file (%s) gets updated automatically every time you " .
					"create / edit / delete a page but you can regenerate or modify it " .
					"manually below."),
					"<a href='".SITE_URL."sitemap.xml' target='_blank'>" .
						SITE_URL."sitemap.xml</a>") .
			"</p>";
	}
	
	function displayAdmin() {
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
	}
	
	function getUrlID($link = null) {
		if (!$link)
			return count($this->urls)-1;
		
		$urlid = null;
		
		foreach($this->urls as $urlnum => $url) {
			if (!isset($url['Link']))
				continue;
			
			if ($url['Link'] == $link) {
				$urlid = $urlnum;
				break;
			}
		}
		
		return $urlid;
	}
	
	function add($url) {
		if (!isset($url) || !is_array($url))
			return false;
		
		if (!isset($url['Link']))
			$url['Link'] = SITE_URL;
		
		if (!isset($url['LastModified']))
			$url['LastModified'] = date("Y-m-d H:i:s");
		
		if (!isset($url['ChangeFreq']))
			$url['ChangeFreq'] = 'daily';
		
		if (!isset($url['Priority']))
			$url['Priority'] = '0.5';
		
		$this->urls[] = $url;
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
		
		if (isset($url['Link']))
			$this->urls[$urlid]['Link'] = $url['Link'];
		
		if (isset($url['LastModified']))
			$this->urls[$urlid]['LastModified'] = $url['LastModified'];
		
		if (isset($url['ChangeFreq']))
			$this->urls[$urlid]['ChangeFreq'] = $url['ChangeFreq'];
		
		if (isset($url['Priority']))
			$this->urls[$urlid]['Priority'] = $url['Priority'];
		
		return true;
	}
	
	function delete($link) {
		if (!isset($link))
			return false;
		
		$urlid = $this->getUrlID($link);
		if (!isset($urlid))
			return false;
		
		array_splice($this->urls, $urlid, 1);
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
	}
	
	function save($file = null) {
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
		
		if (!files::save($file, $sitemap))
			return false;
		
		return true;
	}
	
	function clear() {
		$this->urls = array();
	}
	
	function ajaxRequest() {
		$editor = new fileEditor();
		$editor->file = $this->file;
		$editor->uriRequest = url::path();
		$editor->ajaxRequest = $this->ajaxRequest;
		$editor->ajaxRequest();
		unset($editor);
		
		return true;
	}
}

?>