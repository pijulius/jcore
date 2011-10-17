<?php

/***************************************************************************
 *            templateexporter.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
include_once('lib/template.class.php');
include_once('lib/tar.class.php');

class _templateExporter {
	var $rootPath = null;
	var $adminPath = 'admin/site/template/templateexporter';
	
	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateExporter::templateExporter', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateExporter::templateExporter', $this, $handled);
			
			return $handled;
		}
		
		$this->rootPath = SITE_PATH.'template/';
		
		api::callHooks(API_HOOK_AFTER,
			'templateExporter::templateExporter', $this);
	}
	
	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateExporter::setupAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateExporter::setupAdmin', $this, $handled);
			
			return $handled;
		}
		
		favoriteLinks::add(
			__('Upload Template'), 
			'?path=admin/site/template#adminform');
		favoriteLinks::add(
			__('CSS Editor'), 
			'?path=admin/site/template/templatecsseditor');
		favoriteLinks::add(
			__('Layout Blocks'), 
			'?path=admin/site/blocks');
		
		api::callHooks(API_HOOK_AFTER,
			'templateExporter::setupAdmin', $this);
	}
	
	function setupAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateExporter::setupAdminForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateExporter::setupAdminForm', $this, $form, $handled);
			
			return $handled;
		}
		
		$row = array(
			'_Name' => preg_replace('/(-|,|;).*/i', '', strip_tags(PAGE_TITLE)),
			'_URI' => SITE_URL,
			'_Description' => "Default ".PAGE_TITLE." Template",
			'_Tags' => "default, ".strtolower(preg_replace('/(-|,|;).*/i', '', strip_tags(PAGE_TITLE))),
			'_Author' => (string)$GLOBALS['USER']->data['UserName'],
			'_Version' => "1.0");
		
		if (template::$selected)
			$row = templateManager::parseData(
				files::get($this->rootPath.template::$selected['Name'].'/template.php'));
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'templateExporter::setupAdminForm', $this, $form);
	}
	
	function verifyAdmin(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateExporter::verifyAdmin', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateExporter::verifyAdmin', $this, $form, $handled);
			
			return $handled;
		}
		
		$template = null;
		
		if (template::$selected)
			$template = template::$selected['Name'];
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'templateExporter::verifyAdmin', $this, $form);
			
			return false;
		}
		
		$file = $this->createTar($template, $form->getPostArray());
		
		if ($file) {
			tooltip::display(
				__("Template has been successfully created.")." " .
				"<a href='".url::uri('request, download') .
					"&amp;request=".url::path() .
					"&amp;download=".$file .
					"&amp;ajax=1'>" .
					__("Download") .
				"</a>" .
				"<script type='text/javascript'>" .
					"$(document).ready(function() {" .
						"window.location='".url::uri('request, download') .
							"&request=".url::path() .
							"&download=".$file .
							"&ajax=1';" .
					"});" .
				"</script>",
				TOOLTIP_SUCCESS);
			
			$form->reset();
		}
		
		api::callHooks(API_HOOK_AFTER,
			'templateExporter::verifyAdmin', $this, $form, $file);
		
		return $file;
	}
	
	function displayAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateExporter::displayAdminForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateExporter::displayAdminForm', $this, $form, $handled);
			
			return $handled;
		}
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'templateExporter::displayAdminForm', $this, $form);
	}
	
	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateExporter::displayAdminTitle', $this, $ownertitle);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateExporter::displayAdminTitle', $this, $ownertitle, $handled);
			
			return $handled;
		}
		
		admin::displayTitle(
			__('Export Template'),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'templateExporter::displayAdminTitle', $this, $ownertitle);
	}
	
	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateExporter::displayAdminDescription', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateExporter::displayAdminDescription', $this, $handled);
			
			return $handled;
		}
		
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
		
		api::callHooks(API_HOOK_AFTER,
			'templateExporter::displayAdminDescription', $this);
	}
	
	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateExporter::displayAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateExporter::displayAdmin', $this, $handled);
			
			return $handled;
		}
		
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
		
		$this->verifyAdmin($form);
		$this->displayAdminForm($form);
		
		unset($form);
		
		echo
			"</div>";
		
		api::callHooks(API_HOOK_AFTER,
			'templateExporter::displayAdmin', $this);
	}
	
	function createTar($template, $details = null, $gzip = true) {
		include_once('lib/tar.class.php');
		
		if (!$details || !is_array($details))
			$details = array(
				'Name' => preg_replace('/(-|,|;).*/i', '', strip_tags(PAGE_TITLE)),
				'URI' => SITE_URL,
				'Description' => "Default ".PAGE_TITLE." Template",
				'Tags' => "default, ".strtolower(preg_replace('/(-|,|;).*/i', '', strip_tags(PAGE_TITLE))),
				'Author' => (string)$GLOBALS['USER']->data['UserName'],
				'Version' => "1.0");
		
		$templatephp = null;
		$blockqueries = null;
		$layoutqueries = null;
		
		if (JCORE_VERSION >= '0.9') {
			$layouts = sql::run(
				" SELECT * FROM `{layouts}`" .
				" WHERE `TemplateID` = '".
					(template::$selected?
						(int)template::$selected['ID']:
						0) .
					"'" .
				" ORDER BY `OrderID`");
			
			while($layout = sql::fetch($layouts))
				$layoutqueries .= $this->generateLayoutCode($layout);
		}
		
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
			" AND `SubBlockOfID` = 0" .
			" ORDER BY `OrderID`");
		
		while($block = sql::fetch($blocks))
			$blockqueries .= $this->generateBlockCode($block);
		
		$templateheaderphp = 
			'/***************************************************************************
 * 
 *  Name: '.$details['Name'].'
 *  URI: '.$details['URI'].'
 *  Description: '.$details['Description'].' 
 *  Author: '.$details['Author'].'
 *  Version: '.$details['Version'].'
 *  Tags: '.$details['Tags'].'
 * 
 ****************************************************************************/';
		
		$installSQLphp = 
			'function installSQL() {
		$languageids = array();
		$homepageids = array();
		
		if (JCORE_VERSION >= \'0.8\') {
			$languageids = (array)languages::getIDs();
			$homepageids = (array)pages::getHomeIDs();
		} else {
			$mainmenu = menuItems::getMainMenu();
			if ($mainmenu)
				$homepageids = array($mainmenu[\'ID\']);
		}
		
		'.$layoutqueries.$blockqueries.'return true;
	}';
		
		if (template::$selected) {
			$oldtemplate = files::get($this->rootPath.template::$selected['Name'].'/template.php');
			
			if (preg_match('/class( |\t|\r|\n)+?templateInstaller( |\t|\r|\n)+?.*?\{/is', $oldtemplate)) {
				if (preg_match('/function( |\t|\r|\n)+?installSQL( |\t|\r|\n)*?\(.*?\)/is', $oldtemplate)) {
					$templatephp = preg_replace('/function( |\t|\r|\n)+?installSQL( |\t|\r|\n)*?\(.*?\).*?\{([^{}]+|\{([^{}]+|\{([^{}]+|\{([^{}]+|\{([^{}]+|\{([^{}]+|\{([^{}]+)*\})*\})*\})*\})*\})*\})*\}/is', 
						$installSQLphp, $oldtemplate);
				} else {
					$templatephp = preg_replace('/(class( |\t|\r|\n)+?templateInstaller( |\t|\r|\n)+?.*?\{)/i', 
						'\1'."\n\t".$installSQLphp."\n\n", $oldtemplate);
				}
				
				if (preg_match('/\/\*.*?\*\//s', $templatephp)) {
					$variables = array(
						'Tags', 'Version', 'Author',
						'Description', 'URI', 'Name');
					
					foreach($variables as $variable) {
						if (preg_match('/(\/\*.*?'.$variable.':) .*?(((\r|\n) ?\*|\*\/).*?\*\/)/si', $templatephp))
							$templatephp = preg_replace('/(\/\*.*?'.$variable.':) .*?(((\r|\n) ?\*|\*\/).*?\*\/)/si', 
								'\1 '.$details[$variable].'\2', $templatephp);
						else
							$templatephp = preg_replace('/(\/\*.*?(\r|\n))(.*?\*\/)/si', 
								'\1 *  '.$variable.": ".$details[$variable].'\2\3', $templatephp);
					}
					
				} else {
					$templatephp = preg_replace('/(class( |\t|\r|\n)+?templateInstaller( |\t|\r|\n)+?.*?\{)/i', 
						$templateheaderphp."\n\n".'\1', $templatephp);
				}
			}
		}
		
		if (!$templatephp) {
			$templatephp = 
				'<?php

'.$templateheaderphp.'

class templateInstaller extends template {
	// This will be automatically set when activating template so 
	// you can use it to associate with blocks or other things
	var $templateID = 0;
	
	'.$installSQLphp.'
}

?>';
		}
		
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
		
		dirs::create(SITE_PATH.'sitefiles/var/templates/');
		$filename = $templatedir.'-'.$details['Version'].'.tar'.($gzip?'.gz':null);
		$result = $tar->toTar(SITE_PATH.'sitefiles/var/templates/'.$filename, $gzip);
		unset($tar);
		
		if (!$result) {
			tooltip::display(
				__("File couldn't be saved!")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					SITE_PATH.'sitefiles/var/templates/'),
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
				$tar->pushDirectory(
					trim($dir.'/'.$file, '/'),
					array(
						'mode' => fileperms($this->rootPath.'/'.$template.'/'.$file),
						'mtime' => filemtime($this->rootPath.'/'.$template.'/'.$file)));
					
				$this->pushTemplateDir($tar, $template.'/'.$file, $dir.'/'.$file);
				continue;
			}
			
			$tar->pushFile(trim($dir.'/'.$file, '/'), 
				files::get($this->rootPath.$template.'/'.$file),
				array(
					'mode' => fileperms($this->rootPath.$template.'/'.$file),
					'mtime' => filemtime($this->rootPath.'/'.$template.'/'.$file)));
		}
		
		closedir($dh);
		return true;
	}
	
	function generateLayoutCode($layout) {
		if (!is_array($layout) || !$layout['ID'])
			return false;
		
		$code = null;
		foreach($layout as $fieldid => $fieldvalue) {
			if (in_array($fieldid, array(
				'ID', 
				'TemplateID')))
				continue;
			
			if (!$fieldvalue)
				continue;
			
			$code .= '
			" `'.$fieldid.'` = \''.sql::escape($fieldvalue).'\'," .';
		}
		
		if (!$code)
			return false;
		
		return
			'$layout'.$layout['ID'].' = sql::run(
			" INSERT INTO `{layouts}` SET" .' .
			$code.'
			" `TemplateID` = \'".$this->templateID."\'");
		
		if (sql::error())
			return false;
		
		';
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
			
			if ($fieldid == 'LayoutID' && $fieldvalue) {
				$code .= '
			" `'.$fieldid.'` = \'".$layout'.$fieldvalue.'."\'," .';
				continue;
			}
			
			if (($fieldid == 'PageExcept' || $fieldid == 'MenuItemExcept') && $fieldvalue) {
				$code .= '
			" `".(JCORE_VERSION >= \'0.8\'?\'PageExcept\':\'MenuItemExcept\')."` = \''.sql::escape($fieldvalue).'\'," .';
				continue;
			}
			
			if (($fieldid == 'PageIDs' || $fieldid == 'MenuItemIDs') && $fieldvalue) {
				$homepageids = pages::getHomeIDs();
				$fieldvalues = explode('|', $fieldvalue);
				
				if (!count(array_diff(array_merge($fieldvalues, $homepageids), 
					array_intersect($fieldvalues, $homepageids))))
				{
					$code .= '
			" `".(JCORE_VERSION >= \'0.8\'?\'PageIDs\':\'MenuItemIDs\')."` = \'".implode(\'|\', $homepageids)."\'," .';
					continue;
				}
				
				$homepageidcodes = null;
				
				if (!count(array_diff($homepageids, $fieldvalues))) {
					$homepageidcodes[] = 'implode(\'|\', $homepageids)';
					
					if (count(array_diff($fieldvalues, $homepageids)))
						$fieldvalues = array_diff($fieldvalues, $homepageids);
				}
				
				foreach($fieldvalues as $value) {
					$key = array_search($value, $homepageids);
					
					if ($key === false) {
						$homepageidcodes[] = "'".$value."'";
						continue;
					}
					
					$homepageidcodes[] = '(isset($homepageids['.$key.'])?$homepageids['.$key.']:\'-\')';
				}
				
				if ($homepageidcodes) {
					$code .= '
			" `".(JCORE_VERSION >= \'0.8\'?\'PageIDs\':\'MenuItemIDs\')."` = \'".implode(\'|\', array(' .
			implode(',', $homepageidcodes).'))."\'," .';
					continue;
				}
				
				$code .= '
			" `".(JCORE_VERSION >= \'0.8\'?\'PageIDs\':\'MenuItemIDs\')."` = \''.sql::escape($fieldvalue).'\'," .';
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
		
		if (sql::error())
			return false;
		
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
		$file = SITE_PATH.'sitefiles/var/templates/'.$filename;
		
		if (!is_file($file)) {
			tooltip::display(
				sprintf(__("File \"%s\" cannot be found!"),
					$filename),
				TOOLTIP_ERROR);
				
			return false;
		}

		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateExporter::download', $this, $filename);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateExporter::download', $this, $filename, $handled);
			
			return $handled;
		}
		
		session_write_close();
		$result = files::display($file, true);
		
		api::callHooks(API_HOOK_AFTER,
			'templateExporter::download', $this, $filename, $result);
		
		return true;
	}
	
	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'templateExporter::ajaxRequest', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'templateExporter::ajaxRequest', $this, $handled);
			
			return $handled;
		}
		
		$download = null;
		
		if (isset($_GET['download'])) {
			preg_match('/([^(\/|\\\)]*)$/', (string)$_GET['download'], $matches);
			
			if (isset($matches[1]) && $matches[1] != '.' && $matches[1] != '..')
				$download = $matches[1];
		}
		
		$result = true;
		if ($download)
			$result = $this->download($download);
		
		api::callHooks(API_HOOK_AFTER,
			'templateExporter::ajaxRequest', $this, $result);
		
		return $result;
	}
}

?>