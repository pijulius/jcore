<?php

/***************************************************************************
 *            template.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
include_once('lib/fileeditor.class.php');
include_once('lib/filemanager.class.php');
include_once('lib/tar.class.php');

class _templateExporter {
	var $rootPath = null;
	var $adminPath = 'admin/site/template/templateexporter';
	
	function __construct() {
		$this->rootPath = SITE_PATH.'template/';
	}
	
	function setupAdmin() {
		favoriteLinks::add(
			__('Upload Template'), 
			'?path=admin/site/template#adminform');
		favoriteLinks::add(
			__('CSS Editor'), 
			'?path=admin/site/template/templatecsseditor');
		favoriteLinks::add(
			__('Layout Blocks'), 
			'?path=admin/site/blocks');
	}
	
	function setupAdminForm(&$form) {
		$row = array(
			'_Name' => preg_replace('/(-|,|;).*/i', '', strip_tags(PAGE_TITLE)),
			'_URI' => SITE_URL,
			'_Description' => "Default ".PAGE_TITLE." Template",
			'_Tags' => "default, ".strtolower(preg_replace('/(-|,|;).*/i', '', strip_tags(PAGE_TITLE))),
			'_Author' => $GLOBALS['USER']->data['UserName'],
			'_Version' => "1.0");
		
		if (template::$selected) {
			$template = new template();
			$row = template::parseData(
				files::get($template->rootPath.template::$selected['Name'].'/template.php'));
			unset($template);
		}
		
		$form->add(
			__('Name'),
			'Name',
			FORM_INPUT_TYPE_TEXT,
			true,
			$row['_Name']);
		$form->setStyle('width: 200px;');
		
		$form->add(
			__('URL'),
			'URI',
			FORM_INPUT_TYPE_TEXT,
			false,
			$row['_URI']);
		$form->setStyle('width: 250px;');
		$form->setValueType(FORM_VALUE_TYPE_URL);
		$form->setTooltipText(__("e.g. http://domain.com"));
		
		$form->add(
			__('Description'),
			'Description',
			FORM_INPUT_TYPE_TEXTAREA,
			false,
			$row['_Description']);
		$form->setStyle('width: ' .
			(JCORE_VERSION >= '0.7'?
				'90%':
				'400px') .
			'; height: 110px;');
		
		$form->add(
			__('Tags'),
			'Tags',
			FORM_INPUT_TYPE_TEXT,
			false,
			$row['_Tags']);
		$form->setStyle('width: 300px;');
		
		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(__("e.g. oranges, lemons, limes"));
		else
			$form->addAdditionalText(" (".__("e.g. oranges, lemons, limes").")");
		
		$form->add(
			__('Author'),
			'Author',
			FORM_INPUT_TYPE_TEXT,
			false,
			$row['_Author']);
		$form->setStyle('width: 150px;');
		
		$form->add(
			__('Version'),
			'Version',
			FORM_INPUT_TYPE_TEXT,
			false,
			$row['_Version']);
		$form->setStyle('width: 30px;');
	}
	
	function verifyAdmin(&$form) {
		$template = null;
		
		if (template::$selected)
			$template = template::$selected['Name'];
		
		if (!$form->verify())
			return false;
		
		if (!$file = $this->createTar($template, $form->getPostArray()))
			return false;
		
		tooltip::display(
			__("Template has been successfully created.")." " .
			"<a href='".url::uri('request, download') .
				"&amp;request=".$this->adminPath .
				"&amp;download=".$file .
				"&amp;ajax=1'>" .
				__("Download") .
			"</a>" .
			"<script type='text/javascript'>" .
				"jQuery(document).ready(function() {" .
					"window.location='".url::uri('request, download') .
						"&request=".$this->adminPath .
						"&download=".$file .
						"&ajax=1';" .
				"});" .
			"</script>",
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Export Template'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
		$template = __("Default");
		
		if (template::$selected)
			$template = template::$selected['Name'];
		
		echo
			"<p>" .
				sprintf(__("By completing the form below you can export all your modifications " .
					"to the current \"<b>%s</b>\" template as an installable template package " .
					"and easily port it over to other websites. The package will include " .
					"layout blocks, images, css, js, fonts and all the custom directories " .
					"found."), $template) .
			"</p>";
	}
	
	function displayAdmin() {
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
			
		$form = new form(__("Template Details"),
				'exporttemplate');
		
		$this->setupAdminForm($form);
		
		$form->add(
			__('Export'),
			$form->id.'submit',
			FORM_INPUT_TYPE_SUBMIT);
		
		$form->add(
			__('Reset'),
			$form->id.'reset',
			FORM_INPUT_TYPE_RESET);
		
		//print_r(get_headers("http://jcore.net/modules/submit?&request=posts/postattachments&download=1&ajax=1"));
		
		$this->verifyAdmin($form);
		$this->displayAdminForm($form);
		
		unset($form);
		
		echo
			"</div>";
	}
	
	function createTar($template, $details = null, $gzip = true) {
		include_once('lib/tar.class.php');
		
		if (!$details || !is_array($details))
			$details = array(
				'Name' => preg_replace('/(-|,|;).*/i', '', strip_tags(PAGE_TITLE)),
				'URI' => SITE_URL,
				'Description' => "Default ".PAGE_TITLE." Template",
				'Tags' => "default, ".strtolower(preg_replace('/(-|,|;).*/i', '', strip_tags(PAGE_TITLE))),
				'Author' => $GLOBALS['USER']->data['UserName'],
				'Version' => "1.0");
		
		$blockqueries = null;
		$blocks = sql::run(
			" SELECT * FROM `{blocks}`" .
			" WHERE 1" .
			(JCORE_VERSION >= '0.7'?
				" AND `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0) .
					"'":
				null) .
			" AND !`SubBlockOfID`" .
			" ORDER BY `OrderID`");
		
		while($block = sql::fetch($blocks))
			$blockqueries .= $this->generateBlockCode($block);
		
		$templatephp = 
				'<?php

/***************************************************************************
 * 
 *  Name: '.$details['Name'].'
 *  URI: '.$details['URI'].'
 *  Description: '.preg_replace('/(\r\n|\r|\n)/', ' ', $details['Description']).' 
 *  Author: '.$details['Author'].'
 *  Version: '.$details['Version'].'
 *  Tags: '.$details['Tags'].'
 * 
 ****************************************************************************/
 
class templateInstaller extends template {
	// This will be automatically set when activating template so 
	// you can use it to associate with blocks or other things
	var $templateID = 0;
	
	function installSQL() {
		$mainmenuids = menuitems::getMainMenuIDs();
		$languageids = languages::getIDs();
		
		'.$blockqueries.'return true;
	}
}

?>';
		
		$tar = new tar();
		$templatedir = url::genPathFromString($details['Name'], false);
		
		$tar->pushDirectory($templatedir);
		$tar->pushFile($templatedir.'/template.php', $templatephp);
		
		$ignorefiles = array('template.php');
		
		if (!$template)
			$ignorefiles[] = 'modules';
		
		$this->pushTemplateDir($tar, $template, $templatedir, $ignorefiles);
		
		if (!$tar->getFile($templatedir.'/template.jpg'))
			$tar->pushFile($templatedir.'/template.jpg', 
				$this->createPreviewImage($details['Name']));
		
		dirs::create(SITE_PATH.'sitefiles/template/');
		$filename = $templatedir.'-'.$details['Version'].'.tar'.($gzip?'.gz':null);
		$result = $tar->toTar(SITE_PATH.'sitefiles/template/'.$filename, $gzip);
		unset($tar);
		
		if (!$result) {
			tooltip::display(
				__("File couldn't be saved!")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					SITE_PATH.'sitefiles/template/'),
				TOOLTIP_ERROR);
			return false;
		}
		
		return $filename;
	}
	
	function pushTemplateDir(&$tar, $template, $dir, $ignore = null) {
		if (!is_dir($this->rootPath.'/'.$template))
			return false;
		
		if (!$dh = opendir($this->rootPath.'/'.$template))
			return false;
		
		while (($file = readdir($dh)) !== false) {
			if (in_array($file, array('.', '..')) || 
				($ignore && is_array($ignore) && in_array($file, $ignore)))
				continue;
			
			if ($ignore && @is_file($this->rootPath.'/'.$template.'/'.$file.'/template.php'))
				continue;
			
			if (@is_dir($this->rootPath.'/'.$template.'/'.$file)) {
				$tar->pushDirectory(trim($dir.'/'.$file, '/'));
				$this->pushTemplateDir($tar, $template.'/'.$file, $dir.'/'.$file);
				continue;
			}
			
			$tar->pushFile(trim($dir.'/'.$file, '/'), 
				files::get($this->rootPath.$template.'/'.$file));
		}
		
		closedir($dh);
		return true;
	}
	
	function generateBlockCode($block) {
		if (!is_array($block) || !$block['ID'])
			return false;
		
		$code = null;
		foreach($block as $fieldid => $fieldvalue) {
			if (in_array($fieldid, array(
				'ID', 
				'TemplateID', 
				'CacheContent', 
				'CacheTimeStamp')))
				continue;
			
			if ($fieldid == 'SubBlockOfID' && $fieldvalue) {
				$code .= '
			" `'.$fieldid.'` = \'".$block'.$fieldvalue.'."\'," .';
				continue;
			}
			
			if ($fieldid == 'MenuItemIDs' && $fieldvalue) {
				$mainmenuids = menuitems::getMainMenuIDs();
				$fieldvalues = explode('|', $fieldvalue);
				
				if (!count(array_diff(array_merge($fieldvalues, $mainmenuids), 
					array_intersect($fieldvalues, $mainmenuids))))
				{
					$code .= '
			" `'.$fieldid.'` = \'".implode(\'|\', $mainmenuids)."\'," .';
					continue;
				}
				
				$mainmenuidcodes = null;
				
				if (!count(array_diff($mainmenuids, $fieldvalues))) {
					$mainmenuidcodes[] = 'implode(\'|\', $mainmenuids)';
					
					if (count(array_diff($fieldvalues, $mainmenuids)))
						$fieldvalues = array_diff($fieldvalues, $mainmenuids);
				}
				
				foreach($fieldvalues as $value) {
					$key = array_search($value, $mainmenuids);
					
					if ($key === false) {
						$mainmenuidcodes[] = "'".$value."'";
						continue;
					}
					
					$mainmenuidcodes[] = '(isset($mainmenuids['.$key.'])?$mainmenuids['.$key.']:\'-\')';
				}
				
				if ($mainmenuidcodes) {
					$code .= '
			" `'.$fieldid.'` = \'".implode(\'|\', array(' .
			implode(',', $mainmenuidcodes).'))."\'," .';
					continue;
				}
				
				$code .= '
			" `'.$fieldid.'` = \''.sql::escape($fieldvalue).'\'," .';
				continue;
			}
			
			if ($fieldid == 'LanguageIDs' && $fieldvalue) {
				$languageids = languages::getIDs();
				$fieldvalues = explode('|', $fieldvalue);
				
				if (!count(array_diff(array_merge($fieldvalues, $languageids), 
					array_intersect($fieldvalues, $languageids)))) 
				{
					$code .= '
			" `'.$fieldid.'` = \'".implode(\'|\', $languageids)."\'," .';
					continue;
				}
				
				$languageidcodes = null;
				
				foreach($fieldvalues as $value) {
					$key = array_search($value, $languageids);
					
					if ($key === false)
						continue;
					
					$languageidcodes[] = '(isset($languageids['.$key.'])?$languageids['.$key.']:'.$value.')';
				}
				
				if ($languageidcodes) {
					$code .= '
			" `'.$fieldid.'` = \'".implode(\'|\', array(' .
			implode(',', $languageidcodes).'))."\'," .';
					continue;
				}
				
				$code .= '
			" `'.$fieldid.'` = \''.sql::escape($fieldvalue).'\'," .';
				continue;
			}
			
			if (!$fieldvalue && !in_array($fieldid, array('CacheRefreshTime', 'Limit')))
				continue;
			
			$code .= '
			" `'.$fieldid.'` = \''.sql::escape($fieldvalue).'\'," .';
		}
		
		if (!$code)
			return false;
		
		$code = 
			'$block'.$block['ID'].' = sql::run(
			" INSERT INTO `{blocks}` SET" .' .
			$code.'
			" `TemplateID` = \'".$this->templateID."\'");
		
		';
		
		$rows = sql::run(
			" SELECT * FROM `{blocks}`" .
			" WHERE 1" .
			(JCORE_VERSION >= '0.7'?
				" AND `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0) .
					"'":
				null) .
			" AND `SubBlockOfID` = '".(int)$block['ID']."'" .
			" ORDER BY `OrderID`");
		
		while ($row = sql::fetch($rows))
			$code .= $this->generateBlockCode($row);
		
		return $code;
	}
	
	function createPreviewImage($title, $width = 800, $height = 600) {
		if (defined('JCORE_PATH'))
			$ttffont = JCORE_PATH."lib/fonts/arial.ttf";
		else
			$ttffont = SITE_PATH."lib/fonts/arial.ttf";
		
		$img = ImageCreateTrueColor($width, $height);
		
		$fontcolor = imagecolorallocate($img, 0, 0, 0);
		$commentcolor = imagecolorallocate($img, 100, 100, 100);
		$backcolor = imagecolorallocate($img, 255, 255, 255);
		
		imagefill($img,0,0,$backcolor);
		
		$bbox = imagettfbbox(50, 0, $ttffont, $title);
		$x = $bbox[0] + (imagesx($img) / 2) - ($bbox[4] / 2) - 25;
		$y = $bbox[1] + (imagesy($img) / 2) - ($bbox[5] / 2) - 50;
		
		$coords = imagettftext($img, 50, 0, $x, $y, 
			$fontcolor, $ttffont, $title);
		
		$bbox = imagettfbbox(30, 0, $ttffont, __('Template'));
		$x = $bbox[0] + (imagesx($img) / 2) - ($bbox[4] / 2) - 25;
		$y = $bbox[1] + $coords[1] + 20;
		
		$coords = imagettftext($img, 30, 0, $x, $y, 
			$commentcolor, $ttffont, __('Template'));
		
		ob_start();
		imagejpeg($img, null, 100);
		$image = ob_get_clean(); 
    	imagedestroy($img);
	   	
	   	return $image;
	}
	
	function download($filename) {
		$file = SITE_PATH.'sitefiles/template/'.$filename;
		
		if (!is_file($file)) {
			tooltip::display(
				sprintf(__("File \"%s\" cannot be found!"),
					$filename),
				TOOLTIP_ERROR);
				
			return false;
		}

		session_write_close();
		files::display($file, true);
		
		return true;
	}
	
	function ajaxRequest() {
		$download = null;
		
		if (isset($_GET['download'])) {
			preg_match('/([^(\/|\\\)]*)$/', $_GET['download'], $matches);
			
			if (isset($matches[1]) && $matches[1] != '.' && $matches[1] != '..')
				$download = $matches[1];
		}
		
		if ($download)
			return $this->download($download);
		
		return true;
	}
}

class _templateCSSEditor extends fileEditor {
	var $adminPath = 'admin/site/template/templatecsseditor';
	
	function __construct() {
		parent::__construct();
		
		$this->file = SITE_PATH.'template/template.css';
		$this->uriRequest = "admin/site/template&amp;csseditor=1";
		
		if (template::$selected)
			$this->file = SITE_PATH.'template/' .
				template::$selected['Name'].'/template.css';
	}
	
	function setupAdmin() {
		favoriteLinks::add(
			__('Template Files'), 
			'?path=admin/site/template/templateimages');
		favoriteLinks::add(
			__('JS Editor'), 
			'?path=admin/site/template/templatejseditor');
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=admin/content/menuitems');
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Template'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$this->displayAdminTitle(__("CSS Editor"));
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
			
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			$this->display();
		else
			tooltip::display(
				__("Write access is required to access this area!"),
				TOOLTIP_NOTIFICATION);
		
		echo
			"</div>";
	}
}

class _templateJSEditor extends fileEditor {
	var $adminPath = 'admin/site/template/templatejseditor';
	
	function __construct() {
		parent::__construct();
		
		$this->file = SITE_PATH.'template/template.js';
		$this->uriRequest = "admin/site/template&amp;jseditor=1";
		
		if (template::$selected)
			$this->file = SITE_PATH.'template/' .
				template::$selected['Name'].'/template.js';
	}
	
	function setupAdmin() {
		favoriteLinks::add(
			__('CSS Editor'), 
			'?path=admin/site/template/templatecsseditor');
		favoriteLinks::add(
			__('Template Files'), 
			'?path=admin/site/template/templateimages');
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=admin/content/menuitems');
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Template'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$this->displayAdminTitle(__("JS Editor"));
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
			
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			$this->display();
		else
			tooltip::display(
				__("Write access is required to access this area!"),
				TOOLTIP_NOTIFICATION);
		
		echo
			"</div>";
	}
}

class _templateImages extends fileManager {
	var $adminPath = 'admin/site/template/templateimages';
	
	function __construct() {
		parent::__construct();
		
		$this->rootPath = SITE_PATH.'template/images/';
		$this->uriRequest = "admin/site/template&amp;filemanager=1";
		$this->picturesPreview = true;
		$this->directLinks = true;
		
		if (template::$selected)
			$this->rootPath = SITE_PATH.'template/' .
				template::$selected['Name'].'/images/';
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New File'),
				'?path='.admin::path().
					(url::getarg('dir')?
						'&amp;'.url::arg('dir'):
						null).
					'#form');
		
		favoriteLinks::add(
			__('CSS Editor'), 
			'?path=admin/site/template/templatecsseditor');
		favoriteLinks::add(
			__('JS Editor'), 
			'?path=admin/site/template/templatejseditor');
		favoriteLinks::add(
			__('Pages / Posts'), 
			'?path=admin/content/menuitems');
		favoriteLinks::add(
			__('Content Files'), 
			'?path=admin/content/contentfiles');
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Template'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$this->displayAdminTitle(__("Images")." ".$this->selectedPath);
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
			
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			$this->display();
		else
			tooltip::display(
				__("Write access is required to access this area!"),
				TOOLTIP_NOTIFICATION);
		
		echo
			"</div>";
	}
}

class _template {
	var $rootPath = null;
	var $rootURL = null;
	var $ajaxRequest = null;
	var $adminPath = 'admin/site/template';
	
	static $selected = null;
	
	function __construct() {
		$this->rootPath = SITE_PATH.'template/';
		$this->rootURL = SITE_URL.'template/';
	}
	
	static function populate() {
		if (!defined('WEBSITE_TEMPLATE') || !WEBSITE_TEMPLATE)
			return false;
		
		$row = sql::fetch(sql::run(
			" SELECT * FROM `{templates}`" .
			" WHERE `Name` = '".sql::escape(WEBSITE_TEMPLATE)."'"));
		
		if (!$row)
			return false;
		
		template::$selected = $row;
		return true;
	}
	
	// ************************************************   Admin Part
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('Upload Template'), 
				'?path='.admin::path().'#adminform');
		
		favoriteLinks::add(
			__('Layout Blocks'), 
			'?path=admin/site/blocks');
		
		favoriteLinks::add(
			__('View Website'), 
			SITE_URL);
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			__('Template File'),
			'Files[]',
			FORM_INPUT_TYPE_FILE);
		$form->setValueType(FORM_VALUE_TYPE_FILE);
		$form->setAttributes("multiple='multiple'");
		
		$form->setTooltipText(__("e.g. template-name.tar.gz"));
		
		$form->add(
			"<div class='form-entry-upload-multi-templates-container'></div>" .
			"<div class='form-entry-title'></div>" .
			"<div class='form-entry-content'>" .
				"<a href='javascript://' class='add-link' " .
					"onclick=\"jQuery.jCore.form.appendEntryTo(" .
						"'.form-entry-upload-multi-templates-container', " .
						"'', " .
						"'Files[]', " .
						FORM_INPUT_TYPE_FILE."," .
						"false, ''," .
						"'multiple');\">" .
					__("Upload another template") .
				"</a>" .
			"</div>",
			null,
			FORM_STATIC_TEXT);
	}
	
	function verifyAdmin(&$form) {
		$activate = null;
		$deactivate = null;
		$setadmin = null;
		$unsetadmin = null;
		$delete = null;
		$id = null;
		
		if (isset($_GET['activate']))
			$activate = $_GET['activate'];
		
		if (isset($_GET['deactivate']))
			$deactivate = $_GET['deactivate'];
		
		if (isset($_GET['setadmin']))
			$setadmin = $_GET['setadmin'];
		
		if (isset($_GET['unsetadmin']))
			$unsetadmin = $_GET['unsetadmin'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['id']))
			$id = $_GET['id'];
		
		if ($delete) {
			if (!$this->delete($id))
				return false;
			
			tooltip::display(
				__("Template has been successfully deleted."),
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if ($activate) {
			if (!$this->activate($id))
				return false;
			
			tooltip::display(
				__("Template has been successfully activated.")." " .
				"<a href='".SITE_URL."' target='_blank'>" .
					__("View Website") .
				"</a>",
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if ($deactivate) {
			if (!$this->deactivate($id))
				return false;
			
			tooltip::display(
				__("Default template has been successfully reset for your website.")." " .
				"<a href='".SITE_URL."' target='_blank'>" .
					__("View Website") .
				"</a>",
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if ($setadmin) {
			if (!$this->setAdmin($id))
				return false;
			
			tooltip::display(
				__("Template has been successfully set for Admin section.")." " .
				"<a href='".url::uri('ALL').'?'.url::arg('path')."'>" .
					__("Refresh") .
				"</a>",
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if ($unsetadmin) {
			if (!$this->unsetAdmin($id))
				return false;
			
			tooltip::display(
				__("Default template has been successfully reset for Admin section.")." " .
				"<a href='".url::uri('ALL').'?'.url::arg('path')."'>" .
					__("Refresh") .
				"</a>",
				TOOLTIP_SUCCESS);
				
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if (!$form->get('Files')) {
			tooltip::display(
				__("No template selected to be uploaded! " .
					"Please select at least one template to upload."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$filenames = $form->get('Files');
		$successfiles = null;
		$failedfiles = null;
		
		foreach($form->getFile('Files') as $key => $file) {
			if (!$filename = $this->upload($file)) {
				$failedfiles[] = $filenames[$key];
				continue;
			}
			
			$successfiles[] = $filenames[$key];
		}
		
		if ($failedfiles && count($failedfiles)) {
			tooltip::display(
				sprintf(__("There were problems uploading some of the templates you selected. " .
					"The following templates couldn't be uploaded: %s."),
					implode(', ', $failedfiles)),
				TOOLTIP_ERROR);
			
			if (!$successfiles || !count($successfiles))
				return false;
		}
		
		tooltip::display(
			sprintf(__("Templates have been successfully uploaded. " .
				"The following templates have been uploaded: %s."),
				implode(', ', $successfiles)),
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				__("Template")."</span></th>" .
			"<th></th>";
	}
	
	function displayAdminListHeaderOptions() {
	}
	
	function displayAdminListHeaderFunctions() {
		echo
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
		echo
			"<td align='center' style='width: " .
				($row['_Activated']?
					"190":
					"140") .
				"px;'>" .
				"<div class='admin-content-preview'>" .
					"<div class='template-preview'>" .
						"<a href='".$row['_Preview']."' " .
							"title='".htmlspecialchars($row['_Name'], ENT_QUOTES)."' " .
							"rel='lightbox[templates]' " .
							"style='display: block; max-height: " .
								($row['_Activated']?
									"150":
									"100") .
								"px; overflow: hidden;'>" .
							"<img src='".$row['_Preview']."' " .
								"alt='".htmlspecialchars($row['_Name'], ENT_QUOTES)."' " .
								"width='" .
									($row['_Activated']?
										"150":
										"100") .
									"' />" .
						"</a>" .
					"</div>";
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			$this->displayAdminListItemActivation($row);
		
		echo
				"</div>" .
			"</td>" .
			"<td class='auto-width'>" .
				"<div class='admin-content-preview' style='padding-left: 0;'>" .
					"<h2 class='template-name' style='margin: 0;'>" .
						$row['_Name'] .
						($row['_Version']?
							" (".$row['_Version'].")":
							null) .
					"</h2>" .
					"<div class='template-details'>" .
						sprintf(__("by %s"), $row['_Author']) .
						($row['_URI']?
							" (<a href='".$row['_URI']."' target='_blank'>" .
								$row['_URI']."</a>)":
							null) .
					"</div>" .
					"<div class='template-description'>" .
						"<p>" .
							$row['_Description'] .
						"</p>" .
					"</div>" .
					($row['_Tags']?
						"<div class='template-tags'><b>" .
							__("Tags").":</b> " .
							$row['_Tags'] .
						"</div>":
						null) .
					"<div class='template-location'><b>" .
						__("Location").":</b> " .
						'template/'.$row['_ID'].'/' .
					"</div>" .
				"</div>" .
			"</td>";
	}
	
	function displayAdminListItemActivation(&$row) {
		$url = url::uri('id, delete, activate, deactivate, setadmin, unsetadmin').
			"&amp;id=".urlencode($row['ID']);
		
		if ($row['_Activated']) {
			echo
				"<div class='button' style='float: none; margin: 10px 0 0 0;'>" .
					"<a href='".$url."&amp;deactivate=1' " .
						"title='".htmlspecialchars(__("Restore default template for your " .
							"website"), ENT_QUOTES)."'>" .
						__("Deactivate") .
					"</a>" .
				"</div>";
			
			$settings = new settings();
			
			if ($settings->get('Website_Template_SetForAdmin')) {
				echo
					"<div class='button' style='float: none; margin: 10px 0 0 0;'>" .
						"<a href='".$url."&amp;unsetadmin=1' " .
							"title='".htmlspecialchars(__("Restore default template for Admin " .
								"section"), ENT_QUOTES)."'>" .
							__("Unset Admin") .
						"</a>" .
					"</div>";
			} else {
				echo
					"<div class='button' style='float: none; margin: 10px 0 0 0;'>" .
						"<a href='".$url."&amp;setadmin=1' " .
							"title='".htmlspecialchars(__("Set template as default for Admin " .
								"section"), ENT_QUOTES)."'>" .
							__("Set Admin") .
						"</a>" .
					"</div>";
			}
			
			unset($settings);
			
		} else {
			echo
				"<div class='button' style='float: none; margin: 10px 0 0 0;'>" .
					"<a href='".$url."&amp;activate=1' " .
						"title='".htmlspecialchars(__("Activate and set it as the current " .
							"template for your website"), ENT_QUOTES)."'>" .
						__("Activate") .
					"</a>" .
				"</div>";
		}
	}
	
	function displayAdminListItemOptions(&$row) {
	}
	
	function displayAdminListItemFunctions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, delete, activate, deactivate, setadmin, unsetadmin') .
					"&amp;id=".urlencode($row['ID'])."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayAdminList(&$templates, $selectedtemplate = null) {
		echo "<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>";
		
		$this->displayAdminListHeader();
		$this->displayAdminListHeaderOptions();
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			$this->displayAdminListHeaderFunctions();
		
		echo
				"</tr>" .
				"</thead>" .
				"<tbody>";
				
		$i = 0;	
		foreach($templates as $template) {
			$row = array();
			$row['ID'] = $template;
			
			$row['_ID'] = $template;
			$row['_Preview'] = $this->rootURL.$template.'/template.jpg';
			$row['_Activated'] = false;
			
			$row += template::parseData(
				files::get($this->rootPath.$template.'/template.php'));
			
			$template = sql::fetch(sql::run(
				" SELECT * FROM `{templates}`" .
				" WHERE `Name` = '".sql::escape($row['_ID'])."'"));
			
			if ($template)
				$row['ID'] = $template['ID'];
			
			if ($selectedtemplate == $row['_ID'])
				$row['_Activated'] = true;
			
			echo 
				"<tr".($i%2?" class='pair'":NULL).">";
				
			$this->displayAdminListItem($row);
			$this->displayAdminListItemOptions($row);
			
			if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
				$this->displayAdminListItemFunctions($row);
					
			echo
				"</tr>";
			
			if ($row['_Activated'])
				echo
					"</tbody>" .
					"</table>" .
					"<table cellpadding='0' cellspacing='0' class='list'>" .
					"<tbody>";
			
			$i++;
		}
		
		echo 
				"</tbody>" .
			"</table>" .
			"<br />";
		
		echo
			"</form>";
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		if (JCORE_VERSION >= '0.7') {
			admin::displayTitle(
				__('Template Administration'), 
				$ownertitle);
			return;
		}
		
		echo
			__('Template Administration');
	}
	
	function displayAdminDescription() {
		echo
			"<p>" .
				__("Below are the available templates found in the \"<b>template/</b>\" folder. " .
					"To install a new template just extract it to the " .
					"\"<b>template/</b>\" folder, or using the form below select the " .
					"template package file (e.g. template-name.tar.gz).") .
			"</p>";
	}
	
	function displayAdminSections() {
		echo
			"<div class='admin-section-item as-site-template-css-editor'>" .
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/templatecsseditor' " .
					"title='".htmlspecialchars(__("Edit the CSS template file"), ENT_QUOTES).
					"'>" .
					"<span>" .
					__("Edit CSS File")."" .
					"</span>" .
				"</a>" .
			"</div>" .
			"<div class='admin-section-item as-site-template-js-editor'>" .
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/templatejseditor' " .
					"title='".htmlspecialchars(__("Edit the JavaScript template file"), ENT_QUOTES).
					"'>" .
					"<span>" .
					__("Edit JS File")."" .
					"</span>" .
				"</a>" .
			"</div>" .
			"<div class='admin-section-item as-site-template-files'>" .
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/templateimages' " .
					"title='".htmlspecialchars(__("Browse template Images"), ENT_QUOTES).
					"'>" .
					"<span>" .
					__("Template Images")."" .
					"</span>" .
				"</a>" .
			"</div>" .
			"<div class='admin-section-item as-site-export-template'>" .
				"<a href='".url::uri('ALL') .
					"?path=".admin::path()."/templateexporter' " .
					"title='".htmlspecialchars(__("Export current template as an installable template package"), ENT_QUOTES).
					"'>" .
					"<span>" .
					__("Export Template")."" .
					"</span>" .
				"</a>" .
			"</div>";
		
		if (JCORE_VERSION >= '0.7')
			echo
				"<div class='admin-section-item as-site-template-download'>" .
					"<a href='http://jcore.net/templates' target='_blank' " .
						"title='".htmlspecialchars(__("Browse and download more templates"), ENT_QUOTES).
						"'>" .
						"<span>" .
						__("Get Templates")."" .
						"</span>" .
					"</a>" .
				"</div>";
	}
	
	function displayAdmin() {
		if (JCORE_VERSION < '0.7')
			$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
		
		if (JCORE_VERSION >= '0.7')
			$this->displayAdminTitle();
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE) {
			echo 
				"<div class='fc" .
					form::fcState('fcts', true) .
					"'>" .
					"<a class='fc-title' name='fcts'>";
			
			if (JCORE_VERSION >= '0.7')
				echo
					__("Modify Activated Template");
			else
				$this->displayAdminTitle();
			
			echo
					"</a>" .
					"<div class='fc-content'>";
			
			$this->displayAdminSections();
			
			echo
						"<div class='clear-both'></div>" .
					"</div>" .
				"</div>";
		}
		
		if (JCORE_VERSION >= '0.7') {
			$this->displayAdminDescription();
			
			$form = new form(
				__("Upload New Template"),
				'uploadnewtemplate');
			
			$form->action = url::uri('id, delete, activate, deactivate, setadmin, unsetadmin');
			
			$this->setupAdminForm($form);
			$form->addSubmitButtons();
			
			$verifyok = false;
			
			if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
				$verifyok = $this->verifyAdmin($form);
			
			$templates = array();
			$selectedtemplate = null;
			
			$d = dir($this->rootPath);
			while (false !== ($entry = $d->read())) {
				$entry = preg_replace('/[^a-zA-Z0-9\@\.\_\- ]/', '', $entry);
				
				if (strpos($entry, '.') === 0 ||
					in_array($entry, array('images', 'modules')) ||
					!@is_dir($this->rootPath.$entry) ||
					!@is_file($this->rootPath.$entry.'/template.php'))
					continue;
				
				if (template::$selected && template::$selected['Name'] && 
					template::$selected['Name'] == $entry) 
				{
					$selectedtemplate = $entry;
					continue;
				}
				
				$templates[] = $entry;
			}
			
			$d->close();
			sort($templates);
			
			if ($selectedtemplate)
				$templates = array_pad(
					$templates, -(count($templates)+1), $selectedtemplate);
			
			if (count($templates))
				$this->displayAdminList($templates, $selectedtemplate);
			else
				tooltip::display(
					__("No installed templates found."),
					TOOLTIP_NOTIFICATION);
			
			if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE) {
				echo
					"<a name='adminform'></a>";
				
				$this->displayAdminForm($form);
			}
			
			unset($form);
		}
		
		echo
			"</div>"; //admin-content
	}
	
	function add($values) {
		if (!is_array($values))
			return false;
		
		$newid = sql::run(
			" INSERT INTO `{templates}` SET" .
			" `Name` = '".
				sql::escape($values['Name'])."'");
		
		if (!$newid) {
			tooltip::display(
				sprintf(__("Template couldn't be added! Error: %s"), 
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
			" UPDATE `{templates}` SET" .
			" `Name` = '".
				sql::escape($values['Name'])."'" .
			" WHERE `ID` = '".(int)$id."'");
		
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Template couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	function delete($id) {
		if (!$id)
			return false;
		
		$template = null;
		$templatename = null;
		
		if (is_numeric($id)) {
			$template = sql::fetch(sql::run(
				" SELECT * FROM `{templates}`" .
				" WHERE `ID` = '".(int)$id."'"));
			
		} else {
			$templatename = $id;
			$template = sql::fetch(sql::run(
				" SELECT * FROM `{templates}`" .
				" WHERE `Name` = '".sql::escape($id)."'"));
		}
		
		if ($template) {
			$templatename = $template['Name'];
			
			if (!$this->deactivate($template['ID']))
				return false;
			
			@include_once($this->rootPath.$template['Name'].'/template.php');
			
			if (!class_exists('templateInstaller')) {
				tooltip::display(
					__("Invalid or template installer script cannot be found."),
					TOOLTIP_ERROR);
				return false;
			}
			
			$installer = new templateInstaller();
			$installer->templateID = $template['ID'];
			
			if (method_exists('templateInstaller', 'uninstallFiles') &&
				!$installer->uninstallFiles()) 
			{
				unset($installer);
				return false;
			}
			
			if (method_exists('templateInstaller', 'uninstallSQL') &&
				!$installer->uninstallSQL()) 
			{
				unset($installer);
				return false;
			}
			
			unset($installer);
			
			sql::run(
				" DELETE FROM `{templates}`" .
				" WHERE `ID` = '".(int)$template['ID']."'");
			
			sql::run(
				" DELETE FROM `{blocks}`" .
				" WHERE `TemplateID` = '".(int)$template['ID']."'");
		}
		
		if (is_dir($this->rootPath.$templatename) && 
			!dirs::delete($this->rootPath.$templatename)) 
		{
			tooltip::display(
				sprintf(__("Template couldn't be deleted but it is now safe " .
					"to be deleted manually by just simply removing " .
					"the \"%s\" folder."), 'template/'.$templatename.'/'),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	function activate($id) {
		if (!$id)
			return false;
		
		if (is_numeric($id)) {
			$template = sql::fetch(sql::run(
				" SELECT * FROM `{templates}`" .
				" WHERE `ID` = '".(int)$id."'"));
			
			if (!$template) {
				tooltip::display(
					__("The template you selected cannot be found!"),
					TOOLTIP_ERROR);
				return false;
			}
			
		} else {
			$template = sql::fetch(sql::run(
				" SELECT * FROM `{templates}`" .
				" WHERE `Name` = '".sql::escape($id)."'"));
		}
		
		if ($template) {
			$settings = new settings();
			$settings->set('Website_Template', $template['Name']);
			$settings->set('Website_Template_SetForAdmin', '0');
			unset($settings);
			
			$this->autoSetup($template['ID']);
			
			template::$selected = $template;
			return true;
		}
		
		$newid = $this->add(array(
			'Name' => $id));
		
		if (!$newid)
			return false;
		
		@include_once($this->rootPath.$id.'/template.php');
		
		if (!class_exists('templateInstaller')) {
			tooltip::display(
				__("Invalid or template installer script cannot be found."),
				TOOLTIP_ERROR);
			return false;
		}
		
		$installer = new templateInstaller();
		$installer->templateID = $newid;
		
		if (method_exists('templateInstaller', 'installFiles') &&
			!$installer->installFiles()) 
		{
			unset($installer);
			return false;
		}
		
		if (method_exists('templateInstaller', 'installSQL') &&
			!$installer->installSQL()) 
		{
			unset($installer);
			return false;
		}
		
		unset($installer);
		
		$settings = new settings();
		$settings->set('Website_Template', $id);
		$settings->set('Website_Template_SetForAdmin', '0');
		unset($settings);
		
		$template = sql::fetch(sql::run(
			" SELECT * FROM `{templates}`" .
			" WHERE `ID` = '".(int)$newid."'"));
		
		$this->autoSetup($template['ID']);
			
		template::$selected = $template;
		return true;
	}
	
	function deactivate($id) {
		if (!$id)
			return false;
		
		$template = sql::fetch(sql::run(
			" SELECT * FROM `{templates}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		if (!$template) {
			tooltip::display(
				__("The template you selected cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$settings = new settings();
		$settings->set('Website_Template', '');
		$settings->set('Website_Template_SetForAdmin', '0');
		unset($settings);
		
		$this->autoSetup();
		
		template::$selected = null;
		return true;
	}
	
	function setAdmin($id) {
		if (!$id)
			return false;
		
		$template = sql::fetch(sql::run(
			" SELECT * FROM `{templates}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		if (!$template) {
			tooltip::display(
				__("The template you selected cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$settings = new settings();
		$settings->set('Website_Template_SetForAdmin', '1');
		unset($settings);
		
		return true;
	}
	
	function unsetAdmin($id) {
		if (!$id)
			return false;
		
		$template = sql::fetch(sql::run(
			" SELECT * FROM `{templates}`" .
			" WHERE `ID` = '".(int)$id."'"));
		
		if (!$template) {
			tooltip::display(
				__("The template you selected cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		$settings = new settings();
		$settings->set('Website_Template_SetForAdmin', '0');
		unset($settings);
		
		return true;
	}
	
	function upload($file) {
		if (!$filename = files::upload($file, $this->rootPath, FILE_TYPE_UPLOAD))
			return false;
		
		if ($this->checkOutOfMemory($this->rootPath.$filename)) {
			tooltip::display(
				__("Couldn't extract template as it is to big to be processed " .
					"with the current memory limit set. " .
					"Please try to extract it manually or increment the PHP " .
					"memory limit."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$tar = new tar();
		$tar->openTar($this->rootPath.$filename);
		
		if (!isset($tar->directories) && !isset($tar->files)) {
			tooltip::display(
				__("Template couldn't be extracted! " .
					"Error: Invalid template! Please make sure to " .
					"upload a valid tar.gz template file."),
				TOOLTIP_ERROR);
			
			files::delete($this->rootPath.$filename);
			unset($tar);
			
			return false;
		}
		
		if (!isset($tar->directories))
			$tar->directories = array();
		
		if (!isset($tar->files))
			$tar->files = array();
		
		if (!count($tar->directories) && !count($tar->files)) {
			tooltip::display(
				__("Template couldn't be extracted! " .
					"Error: Empty template! The template you " .
					"selected seems to be an empty tar.gz file."),
				TOOLTIP_ERROR);
			
			files::delete($this->rootPath.$filename);
			unset($tar);
			
			return false;
		}
		
		foreach($tar->directories as $directory) {
			if (@is_dir($this->rootPath.$directory['name']) && 
				!@is_writable($this->rootPath.$directory['name']))
			{
				tooltip::display(
					sprintf(__("Template couldn't be extracted! " .
						"Error: \"%s\" directory couldn't be created."), $directory['name']),
					TOOLTIP_ERROR);
				
				files::delete($this->rootPath.$filename);
				unset($tar);
				
				return false;
		
			}
			
			@mkdir($this->rootPath.$directory['name']);
			@chmod($this->rootPath.$directory['name'], 0755);
		}
		
		foreach($tar->files as $tarfile) {
			if ((@is_file($this->rootPath.$tarfile['name']) && 
				!@is_writable($this->rootPath.$tarfile['name'])) ||
				!files::create($this->rootPath.$tarfile['name'], $tarfile['file']))
			{
				tooltip::display(
					sprintf(__("Template couldn't be extracted! " .
						"Error: \"%s\" file couldn't be created."), $tarfile['name']),
					TOOLTIP_ERROR);
				
				files::delete($this->rootPath.$filename);
				unset($tar);
				
				return false;
			}
		}
		
		files::delete($this->rootPath.$filename);
		unset($tar);
		
		return true;
	}
	
	function checkOutOfMemory($file) {
		$memoryneeded = round(@filesize($file)*3);
		
		$availablememory = settings::iniGet('memory_limit', true);
		
		if (!$availablememory)
			return false;
			
		if ($memoryneeded+memory_get_usage() < $availablememory)
			return false;
			
		return true;
	}
	
	// ************************************************   Client Part
	static function parseData($data) {
		$variables = array(
			'Name', 'URI', 'Description', 
			'Author', 'Version', 'Tags');
		
		$values['_Name'] = '';
		$values['_URI'] = '';
		$values['_Description'] = '';
		$values['_Author'] = '';
		$values['_Version'] = '';
		$values['_Tags'] = '';
		
		foreach($variables as $variable) {
			preg_match('/'.$variable.': (.*)$/mi', $data, $matches);
			
			if (isset($matches[1]))
				$values['_'.$variable] = $matches[1];
		}
		
		return $values;
	}
	
	function autoSetup($templateid = 0) {
		// Set menu blocks to their new places
		
		$rows = sql::run(
			" SELECT * FROM `{menus}`" .
			" ORDER BY `BlockID` = 0, `BlockID`, `OrderID`, `ID`");
		
		$ids = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(`ID` ORDER BY `ID` SEPARATOR '|') AS `IDs`" .
			" FROM `{blocks}`" .
			" WHERE `TemplateID` = '".(int)$templateid."'" .
			" AND `TypeID` = '".BLOCK_TYPE_MENU."'"));
		
		$blockids = array();
		if ($ids['IDs'])
			$blockids = explode('|', $ids['IDs']);
		
		if (count($blockids)) {
			$i = 0;
			$prev = null;
			
			while($row = sql::fetch($rows)) {
				if ($prev && $prev['BlockID'] != $row['BlockID'])
					$i++;
				
				$prev = $row;
				if (!isset($blockids[$i]))
					break;
				
				sql::run(
					" UPDATE `{menus}` SET" .
					" `BlockID` = '".(int)$blockids[$i]."'" .
					" WHERE `ID` = '".$row['ID']."'");
			}
		}
		
		// Set ads to their new places
		
		$rows = sql::run(
			" SELECT * FROM `{ads}`" .
			" ORDER BY `BlockID` = 0, `BlockID`, `OrderID`, `ID`");
		
		$ids = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(`ID` ORDER BY `ID` SEPARATOR '|') AS `IDs`" .
			" FROM `{blocks}`" .
			" WHERE `TemplateID` = '".(int)$templateid."'" .
			" AND `TypeID` = '".BLOCK_TYPE_AD."'"));
		
		$blockids = array();
		if ($ids['IDs'])
			$blockids = explode('|', $ids['IDs']);
		
		if (count($blockids)) {
			$i = 0;
			$prev = null;
			
			while($row = sql::fetch($rows)) {
				if ($prev && $prev['BlockID'] != $row['BlockID'])
					$i++;
				
				$prev = $row;
				if (!isset($blockids[$i]))
					break;
				
				sql::run(
					" UPDATE `{ads}` SET" .
					" `BlockID` = '".(int)$blockids[$i]."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".$row['ID']."'");
			}
		}
		
		// Set block posts to their new places
		
		$rows = sql::run(
			" SELECT * FROM `{posts}`" .
			" WHERE `BlockID`" .
			" ORDER BY `BlockID`, `OrderID`, `ID`");
		
		$ids = sql::fetch(sql::run(
			" SELECT GROUP_CONCAT(`ID` ORDER BY `ID` SEPARATOR '|') AS `IDs`" .
			" FROM `{blocks}`" .
			" WHERE `TemplateID` = '".(int)$templateid."'" .
			" AND `TypeID` = '".BLOCK_TYPE_CONTENT."'"));
		
		$blockids = array();
		if ($ids['IDs'])
			$blockids = explode('|', $ids['IDs']);
		
		if (count($blockids)) {
			$i = 0;
			$prev = null;
			
			while($row = sql::fetch($rows)) {
				if ($prev && $prev['BlockID'] != $row['BlockID'])
					$i++;
				
				$prev = $row;
				if (!isset($blockids[$i]))
					break;
				
				sql::run(
					" UPDATE `{posts}` SET" .
					" `BlockID` = '".(int)$blockids[$i]."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".$row['ID']."'");
			}
		}
	}
	
	function ajaxRequest() {
		if (!$GLOBALS['USER']->loginok || 
			!$GLOBALS['USER']->data['Admin']) 
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);
			return true;
		}
		
		$csseditor = null;
		$jseditor = null;
		$filemanager = null;
		
		if (isset($_GET['csseditor']))
			$csseditor = $_GET['csseditor'];
			
		if (isset($_GET['jseditor']))
			$jseditor = $_GET['jseditor'];
			
		if (isset($_GET['filemanager']))
			$filemanager = $_GET['filemanager'];
		
		if ($csseditor) {
			$editor = new templateCSSEditor();
			$editor->ajaxRequest = true;
			
			include_once('lib/userpermissions.class.php');
			
			$permission = userPermissions::check(
				$GLOBALS['USER']->data['ID'],
				$editor->adminPath);
			
			if ($permission['PermissionType'] != USER_PERMISSION_TYPE_WRITE ||
				$permission['PermissionIDs'])
			{
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);
				
				unset($editor);
				return true;
			}
			
			$editor->ajaxRequest();
			unset($editor);
		
			return true;
		}
		
		if ($jseditor) {
			$editor = new templateJSEditor();
			$editor->ajaxRequest = true;
			
			include_once('lib/userpermissions.class.php');
			
			$permission = userPermissions::check(
				$GLOBALS['USER']->data['ID'],
				$editor->adminPath);
			
			if ($permission['PermissionType'] != USER_PERMISSION_TYPE_WRITE ||
				$permission['PermissionIDs'])
			{
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);
				
				unset($editor);
				return true;
			}
			
			$editor->ajaxRequest();
			unset($editor);
			
			return true;
		}
		
		if ($filemanager) {
			$filemanager = new templateImages();
			$filemanager->ajaxRequest = true;
			
			include_once('lib/userpermissions.class.php');
			
			$permission = userPermissions::check(
				$GLOBALS['USER']->data['ID'],
				$filemanager->adminPath);
			
			if ($permission['PermissionType'] != USER_PERMISSION_TYPE_WRITE ||
				$permission['PermissionIDs'])
			{
				tooltip::display(
					__("You do not have permission to access this path!"),
					TOOLTIP_ERROR);
				
				unset($editor);
				return true;
			}
			
			$filemanager->ajaxRequest();
			unset($filemanager);
			
			return true;
		}
	}
}

?>