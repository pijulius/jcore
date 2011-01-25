<?php

/***************************************************************************
 *            ckeditor.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
include_once('lib/filemanager.class.php');

class _ckEditorFileManager extends fileManager {
	var $picturesPreview = true;
	var $directLinks = true;
	
	function __construct() {
		parent::__construct();
		
		$this->rootPath = SITE_PATH.'sitefiles/';
		$this->uriRequest = "ckeditor/".$this->uriRequest;
	}
	
	function ajaxRequest() {
		if (!$GLOBALS['USER']->loginok || 
			!$GLOBALS['USER']->data['Admin']) 
		{
			tooltip::display(
				__("Request can only be accessed by administrators!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		return parent::ajaxRequest();
	}
	
	function displayTitle(&$row) {
		$ckeditorfuncnum = 1;
		
		if (isset($_GET['CKEditorFuncNum']))
			$ckeditorfuncnum = (int)$_GET['CKEditorFuncNum'];
		
		if (!$row['_IsDir']) {
			echo
				"<a href='javascript://' " .
					"onclick=\"window.opener.CKEDITOR.tools.callFunction(" .
						$ckeditorfuncnum.", '" .
						$row['_URL']."');window.close();\" " .
					"title='".htmlspecialchars(sprintf(__("Link to %s"), 
						$row['_File']), ENT_QUOTES)."'>" .
					$row['_File'] .
				"</a>";
			
			return;
		}
		
		parent::displayTitle($row);
	}
	
	function display() {
		include_once('lib/userpermissions.class.php');
		
		$permission = userPermissions::check(
			$GLOBALS['USER']->data['ID'],
			'admin/content/contentfiles');
		
		if (!$permission['PermissionType'])
			$permission = userPermissions::check(
				$GLOBALS['USER']->data['ID'],
				'admin/content/menuitems');
		
		if ($permission['PermissionType'] != USER_PERMISSION_TYPE_WRITE ||
			$permission['PermissionIDs'])
			$this->readOnly = true;
		
		if (AJAX_PAGING && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
			parent::display();
			return;
		}
		
		echo 
			"<!DOCTYPE html>" .
			"<html>" .
			"<head>" .
			"<title>File Mananger - CKEditor - ".PAGE_TITLE."</title>";
		
		jQuery::display();
		css::display();
		
		echo
			"</head>" .
			"<body>" .
				"<div class='ckeditor-file-manager'>";
		
		parent::display();
		
		echo 
				"</div>";
				
		jQuery::displayPlugins();
		
		echo
			"</body>" .
			"</html>";
	}
}
 
class _ckEditor {
	static $loaded = false;
	var $ckFuncNum = 1;
	var $ajaxRequest = null;
	
	static function compress($buffer, $mode) {
		// Fix scrollbar on file browser window, see 
		// http://cksource.com/forums/viewtopic.php?f=11&t=15966
		$buffer = str_replace('resizable=yes', 'resizable=yes,scrollbars=yes', $buffer);
		
		if (false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
			header('Vary: Accept-Encoding');
			header('Content-Encoding: gzip');
			return gzencode($buffer);
		}
		
		if (false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate')) {
			header('Vary: Accept-Encoding');
			header('Content-Encoding: deflate');
			return gzdeflate($buffer);
		}
		
		return $buffer;
	}
	
	function upload() {
		$url = null;
		$message = null;
		
		$attachments = new attachments();
		
		$filename = $attachments->upload('upload', $attachments->rootPath);
		$message = strip_tags(tooltip::$cache);
		
		if ($filename)
			$url = $attachments->rootURL.$attachments->subFolder .
				'/'.$filename;
			
		unset($attachments);
		
		$this->setUploadResult($url, $message);
	}
	
	function uploadFlash() {
		include_once('lib/flash.class.php');
		
		$url = null;
		$message = null;
		
		$flash = new flash();
		
		$filename = $flash->upload('upload', $flash->rootPath);
		$message = strip_tags(tooltip::$cache);
		
		if ($filename)
			$url = $flash->rootURL.$flash->subFolder .
				'/'.$filename;
			
		unset($flash);
		
		$this->setUploadResult($url, $message);
	}
	
	function uploadImage() {
		$url = null;
		$message = null;
		
		$pictures = new pictures();
		
		$filename = $pictures->upload('upload', $pictures->rootPath);
		$message = strip_tags(tooltip::$cache);
		
		if ($filename)
			$url = $pictures->rootURL.$pictures->subFolder .
				'/'.$filename;
			
		unset($pictures);
		
		$this->setUploadResult($url, $message);
	}
	
	function setUploadResult($url, $message = null) {
		if (!$this->ckFuncNum)
			return false;
		
		echo
			"<script type='text/javascript'>" .
			"window.parent.CKEDITOR.tools.callFunction(" .
				$this->ckFuncNum.", '" . 
				$url."', '" .
				htmlspecialchars($message, ENT_QUOTES) .
				"');" .
			"</script>";
		
		return true;
	}
	
	function ajaxRequest() {
		$upload = null;
		$file = null;
		$image = null;
		$flash = null;
		$ckeditorfuncnum = 1;
		
		if (isset($_GET['upload']))
			$upload = $_GET['upload'];
			
		if (isset($_GET['image']))
			$image = $_GET['image'];
			
		if (isset($_GET['flash']))
			$flash = $_GET['flash'];
			
		if (isset($_FILES['upload']))
			$file = $_FILES['upload'];
			
		if (isset($_GET['CKEditorFuncNum']))
			$this->ckFuncNum = (int)$_GET['CKEditorFuncNum'];
		
		if ($upload) {
			if (!$GLOBALS['USER']->loginok || 
				!$GLOBALS['USER']->data['Admin']) 
			{
				tooltip::display(
					__("Request can only be accessed by administrators!"),
					TOOLTIP_ERROR);
				return false;
			}
		
			include_once('lib/userpermissions.class.php');
			
			$permission = userPermissions::check(
				$GLOBALS['USER']->data['ID'],
				'admin/content/contentfiles');
			
			if (!$permission['PermissionType'])
				$permission = userPermissions::check(
					$GLOBALS['USER']->data['ID'],
					'admin/content/menuitems');
			
			echo 
				"<html>" .
				"<body>";
			
			tooltip::caching(true);
			
			if ($permission['PermissionType'] != USER_PERMISSION_TYPE_WRITE ||
				$permission['PermissionIDs'])
			{
				$this->setUploadResult('',
					__("You do not have permission to access this path!"));
				
			} else {
				if ($file) {
					if ($flash)
						$result = $this->uploadFlash();
					elseif ($image)
						$result = $this->uploadImage();
					else
						$result = $this->upload();
					
				} else {
					$this->setUploadResult('',
						__("No file selected!"));
				}
			}
			
			tooltip::caching(false);
			
			echo 
				"</body>" .
				"</html>";
			
			return true;
		}
		
		session_write_close();
		$cachetime = 60*60*24*365;
		
		header('Pragma: public');
		header('Cache-Control: public, max-age='.$cachetime);
		header('Expires: '.gmdate('D, d M Y H:i:s', time()+$cachetime).' GMT');
		
		ckEditor::displayJS();
		
		return true;
	}
	
	static function displayJS($compress = true) {
		if ($compress)
			ob_start(array('ckEditor', 'compress'));
		
		if (defined('JCORE_PATH'))
			$filemtime = @filemtime(JCORE_PATH.'lib/ckeditor/ckeditor.js');
		else
			$filemtime = @filemtime(SITE_PATH.'lib/ckeditor/ckeditor.js');
		
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $filemtime).' GMT');
		header('Content-Type: application/x-javascript');
		
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && 
			(strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $filemtime)) 
		{
			header('HTTP/1.0 304 Not Modified');
			return true;
		}
		
		echo 
			@file_get_contents('lib/ckeditor/ckeditor.js', 
				FILE_USE_INCLUDE_PATH)."\n";
		
		if ($compress)
			ob_end_flush();
		
		return true;
	}
	
	static function display($inputelement = null) {
		if (!ckEditor::$loaded) {
			if (defined('JCORE_PATH'))
				$filemtime = @filemtime(JCORE_PATH.'lib/ckeditor/ckeditor.js');
			else
				$filemtime = @filemtime(SITE_PATH.'lib/ckeditor/ckeditor.js');
			
			echo 
				"<script type='text/javascript'>" .
					"var CKEDITOR_BASEPATH='".url::jCore()."lib/ckeditor/';" .
				"</script>";
			
			if (JCORE_VERSION >= '0.6')
				echo
					"<script src='".url::site()."static.php?request=ckeditor&amp;" .
						$filemtime.'-v'.JCORE_VERSION."' " .
						"type='text/javascript'>" .
					"</script>\n";
			else
				echo
					"<script src='".url::site()."index.php?request=ckeditor&amp;ajax=1&amp;" .
						$filemtime.'-v'.JCORE_VERSION."' " .
						"type='text/javascript'>" .
					"</script>\n";
					
			ckEditor::$loaded = true;
		}
		
		if ($inputelement) {
			$url = url::uri('ALL');
			
			echo
				"<script type='text/javascript'>" .
				"CKEDITOR.replace('".$inputelement."'" .
					(isset($GLOBALS['ADMIN']) && $GLOBALS['ADMIN']?
						", {" .
							"filebrowserWindowWidth : '640'," .
							"filebrowserBrowseUrl : '".$url."?request=ckeditor/ckeditorfilemanager&ajax=1'," .
							"filebrowserImageBrowseUrl : '".$url."?request=ckeditor/ckeditorfilemanager&dir=image&ajax=1'," .
							"filebrowserFlashBrowseUrl : '".$url."?request=ckeditor/ckeditorfilemanager&dir=flash&ajax=1'," .
							"filebrowserUploadUrl : '".$url."?request=ckeditor&upload=1&ajax=1'," .
							"filebrowserImageUploadUrl : '".$url."?request=ckeditor&upload=1&image=1&ajax=1'," .
							"filebrowserFlashUploadUrl : '".$url."?request=ckeditor&upload=1&flash=1&ajax=1'" .
						"}":
						null) .
				");" .
				"</script>";
		}
	}
}

?>