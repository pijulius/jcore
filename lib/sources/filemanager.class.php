<?php

/***************************************************************************
 *            filemanager.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
class _fileManager {
	var $rootPath;
	var $uriRequest;
	var $limit = 20;
	var $showPaging = true;
	var $selectedPath;
	var $selectedFile;
	var $picturesPreview = false;
	var $directLinks = false;
	var $readOnly = false;
	var $ajaxPaging = AJAX_PAGING;
	var $ajaxRequest = null;
	
	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::fileManager', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::fileManager', $this, $handled);
			
			return $handled;
		}
		
		if (isset($_GET['file'])) {
			preg_match('/([^(\/|\\\)]*)$/', strip_tags((string)$_GET['file']), $matches);
			
			if (isset($matches[1]) && $matches[1] != '.' && $matches[1] != '..')
				$this->selectedFile = $matches[1];
		}
		
		if (isset($_GET['dir']) && $_GET['dir'])
			$this->selectedPath = rtrim(str_replace('..', '', strip_tags((string)$_GET['dir'])), '/\\').'/';
		
		$this->uriRequest = strtolower(get_class($this));
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::fileManager', $this);
	}
	
	function verifyFolder(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::verifyFolder', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::verifyFolder', $this, $form, $handled);
			
			return $handled;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::verifyFolder', $this, $form);
			
			return false;
		}
			
		$result = $this->createFolder($this->rootPath.$this->selectedPath.$form->get('FolderName'));
		
		if (!$result)
			tooltip::display(
				__("Folder couldn't be created.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					$this->rootPath.$this->selectedPath), 
				TOOLTIP_ERROR);
		else
			tooltip::display(
				__("Folder has been successfully created."),
				TOOLTIP_SUCCESS);
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::verifyFolder', $this, $form, $result);
		
		return $result;
	}
	
	function verify(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::verify', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::verify', $this, $form, $handled);
			
			return $handled;
		}
		
		$delete = null;
		$edit = null;
		
		if (isset($_POST['delete']))
			$delete = (int)$_POST['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'fileManager::verify', $this, $form);
				return false;
			}
			
			$result = $this->delete($this->rootPath.$this->selectedPath.$this->selectedFile);
			
			if (!$result)
				tooltip::display(
					__("File / Folder couldn't be deleted.")." " .
					sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
						$this->rootPath.$this->selectedPath.$this->selectedFile),
					TOOLTIP_ERROR);
			else
				tooltip::display(
					__("File / Folder has been successfully deleted."),
					TOOLTIP_SUCCESS);
			
			api::callHooks(API_HOOK_AFTER,
				'fileManager::verify', $this, $form, $result);
			
			return $result;
		}
		
		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::verify', $this, $form);
			
			return false;
		}
		
		if (!$edit && !$form->get('Files')) {
			tooltip::display(
				__("No file selected to be uploaded! " .
					"Please select at least one file to upload."),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'fileManager::verify', $this, $form);
			
			return false;
		}
		
		if ($edit) {
			$move = false;
			$path = null;
			$renameto = $form->get('FileName');
			$absolute = (preg_match('/^(\/|\\\)/', $renameto)?true:false);
			
			if (preg_match('/(\/|\\\)/', $renameto)) {
				$move = true;
		 		$path = trim(preg_replace('/\.\.?(\/|\\\)/', '', 
		 			preg_replace('/((.*(\/|\\\))|^).*$/', '\2', $renameto)), '/\\');
			}
	 		
			$filename = preg_replace('/.*(\/|\\\)/', '', $renameto);
			
			$result = $this->edit($this->rootPath.$this->selectedPath.$this->selectedFile, 
				$this->rootPath.(!$absolute?$this->selectedPath:null).($path?$path.'/':null).$filename);
			
			if (!$result) {
				tooltip::display(
					__("File / Folder couldn't be renamed.")." " .
					sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
						$this->rootPath.$this->selectedPath.$this->selectedFile),
					TOOLTIP_ERROR);
				
			} else {
				tooltip::display(
					__("File / Folder has been successfully renamed."),
					TOOLTIP_SUCCESS);
				
				$form->setValue('FileName', $filename);
				
				if ($move) {
					$this->selectedPath = (!$absolute?$this->selectedPath:null).($path?$path.'/':null);
					$form->action = url::uri('dir').'&amp;dir='.trim($this->selectedPath, '/\\');
					url::setURI($form->action);
				}
			}
			
			api::callHooks(API_HOOK_AFTER,
				'fileManager::verify', $this, $form, $result);
			
			return $result;
		}
		
		$files = $form->getFile('Files');
		$successfiles = null;
		$failedfiles = null;
		
		foreach($form->get('Files') as $key => $filename) {
			if (!$this->upload(@$files[$key], $this->rootPath.$this->selectedPath)) {
				$failedfiles[] = $filename;
				continue;
			}
			
			$successfiles[] = $filename;
		}
		
		if ($failedfiles && count($failedfiles)) {
			tooltip::display(
				sprintf(__("There were problems uploading some of the files you selected. " .
					"The following files couldn't be uploaded: %s."),
					implode(', ', $failedfiles)),
				TOOLTIP_ERROR);
			
			if (!$successfiles || !count($successfiles)) { 
				api::callHooks(API_HOOK_AFTER,
					'fileManager::verify', $this, $form);
				
				return false;
			}
		}
		
		tooltip::display(
			sprintf(__("Files have been successfully uploaded. " .
				"The following files have been uploaded: %s."),
				implode(', ', $successfiles)),
			TOOLTIP_SUCCESS);
		
		$form->reset();
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::verify', $this, $form, $successfiles);
		
		return true;
	}
	
	function createFolder($folder) {
		if (!$folder)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::createFolder', $this, $folder);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::createFolder', $this, $folder, $handled);
			
			return $handled;
		}
		
		$result = dirs::create($folder);
				
		api::callHooks(API_HOOK_AFTER,
			'fileManager::createFolder', $this, $folder, $result);
		
		return $result;
	}
	
	function upload($file, $to) {
		if (!$file || !$to)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::upload', $this, $file, $to);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::upload', $this, $file, $to, $handled);
			
			return $handled;
		}
		
		$result = files::upload($file, $to, FILE_TYPE_UPLOAD);
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::upload', $this, $file, $to, $result);
		
		return $result;
	}
	
	function edit($from, $to) {
		if (!$from || !$to)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::edit', $this, $from, $to);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::edit', $this, $from, $to, $handled);
			
			return $handled;
		}
		
		if (!@is_dir($from) && !preg_match("/".files::$allowedFileTypes[FILE_TYPE_UPLOAD]."/i", $to)) {
			tooltip::display(
				sprintf(__("Unsupported file format! Supported formats are: %s."),
					str_replace('|', ', ', files::$allowedFileTypes[FILE_TYPE_UPLOAD])),
				TOOLTIP_ERROR);
			
			api::callHooks(API_HOOK_AFTER,
				'fileManager::edit', $this, $from, $to);
			
			return false;
		}
		
		$result = files::rename($from, $to);
				
		api::callHooks(API_HOOK_AFTER,
			'fileManager::edit', $this, $from, $to, $result);
		
		return $result;
	}
	
	function delete($file) {
		if (!$file)
			return false;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::delete', $this, $file);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::delete', $this, $file, $handled);
			
			return $handled;
		}
		
		$result = files::delete($file);
				
		api::callHooks(API_HOOK_AFTER,
			'fileManager::delete', $this, $file, $result);
		
		return $result;
	}
	
	function download($file, $resumable = true) {
		if (!$file) {
			tooltip::display(
				__("No file selected to download!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::download', $this, $file, $resumable);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::download', $this, $file, $resumable, $handled);
			
			return $handled;
		}
		
		session_write_close();
		$result = files::display($file, true);
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::download', $this, $file, $resumable, $result);
		
		return $result;
	}
	
	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::ajaxRequest', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::ajaxRequest', $this, $handled);
			
			return $handled;
		}
		
		$view = null;
		$download = null;
		
		if (isset($_GET['view']))
			$view = (int)$_GET['view'];
		
		if (isset($_GET['download']))
			$download = (int)$_GET['download'];
		
		if ($download && $this->selectedFile) {
			$result = $this->download($this->rootPath.$this->selectedPath.
				$this->selectedFile);
			
			api::callHooks(API_HOOK_AFTER,
				'fileManager::ajaxRequest', $this, $result);
			
			return true;
		}
		
		if ($view && $this->selectedFile) {
			$result = files::display($this->rootPath.$this->selectedPath.
				$this->selectedFile);
			
			api::callHooks(API_HOOK_AFTER,
				'fileManager::ajaxRequest', $this, $result);
			
			return true;
		}
		
		$this->ajaxPaging = true;
		$this->display();
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::ajaxRequest', $this, $this->ajaxPaging);
		
		return true;
	}
	
	function setupFolderForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::setupFolderForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::setupFolderForm', $this, $form, $handled);
			
			return $handled;
		}
		
		$form->add(
			__('Folder name'),
			'FolderName',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 300px;');
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::setupFolderForm', $this, $form);
	}
	
	function setupForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::setupForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::setupForm', $this, $form, $handled);
			
			return $handled;
		}
		
		$edit = null;
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		if ($edit) {
			$form->add(
				__('File / Folder name'),
				'FileName',
				FORM_INPUT_TYPE_TEXT,
				true,
				$this->selectedFile);
			$form->setStyle('width: 350px;');
		
		} else {
			$form->add(
				__('File to upload'),
				'Files[]',
				FORM_INPUT_TYPE_FILE);
			$form->setValueType(FORM_VALUE_TYPE_FILE);
			$form->setAttributes("multiple='multiple'");
			
			$form->add(
				"<div class='form-entry-upload-multi-pictures-container'></div>" .
				"<div class='form-entry-title'></div>" .
				"<div class='form-entry-content'>" .
					"<a href='javascript://' class='add-link' " .
						"onclick=\"$.jCore.form.appendEntryTo(" .
							"'.form-entry-upload-multi-pictures-container', " .
							"'', " .
							"'Files[]', " .
							FORM_INPUT_TYPE_FILE."," .
							"false, ''," .
							"'multiple');\">" .
						__("Upload another file") .
					"</a>" .
				"</div>",
				null,
				FORM_STATIC_TEXT);
		}
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::setupForm', $this, $form);
	}
	
	function displayFolderForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::displayFolderForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::displayFolderForm', $this, $form, $handled);
			
			return $handled;
		}
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::displayFolderForm', $this, $form);
	}
	
	function displayForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::displayForm', $this, $form);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::displayForm', $this, $form, $handled);
			
			return $handled;
		}
		
		$form->display();
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::displayForm', $this, $form);
	}
	
	function displayPath($displaypath = null) {
		if (!$displaypath)
			$displaypath = $this->selectedPath;
			
		if (!$displaypath)
			return;
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::displayPath', $this, $displaypath);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::displayPath', $this, $displaypath, $handled);
			
			return $handled;
		}
		
		$path = null;
		$exppaths = explode('/', $displaypath);
		
		$i = 0;
		foreach($exppaths as $key => $exppath) {
			if (!$exppath)
				continue;
			
			if ($path) 
				$path .= '/';
				
			$path .= $exppath;
			
			if ($i > 0)
				echo " / ";
			
			echo
				"<a class='url-path' href='". url::uri('dir, file, edit, delete, limit') .
					"&amp;dir=".$path."'>".$exppath."</a>";
			
			$i++;
		}
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::displayPath', $this, $displaypath);
	}
	
	function displayHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::displayHeader', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::displayHeader', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<th colspan='2'><span class='nowrap'>/".
				$this->selectedPath."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Size")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::displayHeader', $this);
	}
	
	function displayHeaderOptions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::displayHeaderOptions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::displayHeaderOptions', $this, $handled);
			
			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'fileManager::displayHeaderOptions', $this);
	}
	
	function displayHeaderFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::displayHeaderFunctions', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::displayHeaderFunctions', $this, $handled);
			
			return $handled;
		}
		
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::displayHeaderFunctions', $this);
	}
	
	function displayIcon(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::displayIcon', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::displayIcon', $this, $row, $handled);
			
			return $handled;
		}
		
		if ($row['_IsDir']) {
			echo
				"<a href='".$row['_Link']."' " .
					"title='" .
						htmlspecialchars(sprintf(__("Change directory to %s"), 
							$row['_File']), ENT_QUOTES) .
					"' " .
					"class='attachment-icon mime-type-directory' " .
					"style='float: none;'>" .
				"</a>";
				
		} else {
			if ($this->picturesPreview && $row['_MimeType'] == 'mime-type-photo') {
				echo
					"<a href='".$row['_ViewLink']."' " .
						"rel='lightbox[".strtolower(get_class($this))."]' " .
						"title='".htmlspecialchars($row['_File'], ENT_QUOTES)."' " .
						"class='attachment-preview'>" .
						"<img src='" .$row['_ViewLink']."' " .
							"width='32' height='32' border='0' " .
							"alt='".htmlspecialchars($row['_File'], ENT_QUOTES)."' " .
							"title='" .
								htmlspecialchars(sprintf(__("Preview %s"), 
									$row['_File']), ENT_QUOTES) .
							"' />" .
					"</a>";
			
			} else {
				echo
					"<a href='".$row['_Link']."' " .
						"title='" .
							htmlspecialchars(sprintf(__("Download %s"), 
								$row['_File']), ENT_QUOTES) .
						"' " .
						"class='attachment-icon " .
							$row['_MimeType']."'>" .
					"</a>";
			}
		}
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::displayIcon', $this, $row);
	}
	
	function displayTitle(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::displayTitle', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::displayTitle', $this, $row, $handled);
			
			return $handled;
		}
		
		if ($row['_IsDir']) {
			echo
				"<a href='".$row['_Link']."' " .
					"title='" .
						htmlspecialchars(sprintf(__("Change directory to %s"), 
							$row['_File']), ENT_QUOTES) .
					"'>" .
					$row['_File'] .
				"</a>";
				
		} else {
			if ($this->directLinks) {
				echo
					"<a href='".$row['_URL']."' " .
						"title='" .
							htmlspecialchars(sprintf(__("Link to %s"), 
								$row['_File']), ENT_QUOTES) .
						"' " .
						"target='_blank'>" .
						$row['_File'] .
					"</a>";
			
			} else {
				echo
					"<a href='".$row['_Link']."' " .
						"title='" .
							htmlspecialchars(sprintf(__("Download %s"), 
								$row['_File']), ENT_QUOTES) .
						"'>" .
						$row['_File'] .
					"</a>";
			}
		}
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::displayTitle', $this, $row);
	}
	
	function displayDetails(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::displayDetails', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::displayDetails', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			calendar::datetime(
				@fileatime($this->rootPath.$this->selectedPath.$row['_File']));
				
		api::callHooks(API_HOOK_AFTER,
			'fileManager::displayDetails', $this, $row);
	}
	
	function displaySize(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::displaySize', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::displaySize', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			files::humanSize(
				@filesize($this->rootPath.$this->selectedPath.$row['_File']));
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::displaySize', $this, $row);
	}
	
	function displayItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::displayItem', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::displayItem', $this, $row, $handled);
			
			return $handled;
		}
		
		echo 
			"<td>";
		
		$this->displayIcon($row);
		
		echo
			"</td>" .
			"<td class='auto-width'>" .
				"<div class='bold'>";
		
		$this->displayTitle($row);			
						
		echo
				"</div>" .
				"<div class='comment' style='padding-left: 10px;'>";
		
		$this->displayDetails($row);
		
		echo
				"</div>" .
			"</td>";
		
		echo
			"<td style='text-align: right;'>" .
			"<span class='nowrap'>";
		
		$this->displaySize($row);
		
		echo
			"</span>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::displayItem', $this, $row);
	}
	
	function displayItemOptions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::displayItemOptions', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::displayItemOptions', $this, $row, $handled);
			
			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'fileManager::displayItemOptions', $this, $row);
	}
	
	function displayItemFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::displayItemFunctions', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::displayItemFunctions', $this, $row, $handled);
			
			return $handled;
		}
		
		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('file, edit, delete') .
					"&amp;file=".$row['_File']."&amp;edit=1#form'>" .
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('file, edit, delete') .
					"&amp;file=".$row['_File']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::displayItemFunctions', $this, $row);
	}
	
	function displayOne(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::displayOne', $this, $row);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::displayOne', $this, $row, $handled);
			
			return $handled;
		}
		
		$row['_IsDir'] = @is_dir($this->rootPath.$this->selectedPath.$row['_File']);
		
		if ($row['_IsDir']) {
			if ($row['_File'] == '..')
				$row['_Link'] = url::uri('dir, file, edit, delete, '.
					strtolower(get_class($this)).'limit').
					"&amp;dir=".preg_replace('/[^(\/|\\\)]*(\/|\\\)?$/', '', $this->selectedPath);
			
			elseif ($row['_File'] == '.')
				$row['_Link'] = url::uri('dir, file, edit, delete, '.
					strtolower(get_class($this)).'limit').
					"&amp;dir=";
			
			else
				$row['_Link'] = url::uri('dir, file, edit, delete, '.
					strtolower(get_class($this)).'limit').
					"&amp;dir=".$this->selectedPath.$row['_File'];
			
		} else {
			$row['_Link'] = url::uri('file, edit, delete').
				"&amp;request=".$this->uriRequest .
				"&amp;file=".$row['_File']."&amp;download=1&amp;ajax=1";
		}
		
		$row['_ViewLink'] = url::uri('file, edit, delete').
			"&amp;request=".$this->uriRequest .
			"&amp;file=".$row['_File']."&amp;view=1&amp;ajax=1";
		
		$row['_URL'] = url::site() .
					str_replace(SITE_PATH, '', $this->rootPath) .
					$this->selectedPath.$row['_File'];
					
		$row['_MimeType'] = files::ext2MimeClass($row['_File']);
		
		echo 
			"<tr".
				(isset($row['_CSSClass'])?
					" class='".$row['_CSSClass']."'":
					null) .
				">";
		
		$this->displayItem($row);
		$this->displayItemOptions($row);
		
		if (!$this->readOnly) {
			if ($row['_File'] == '.' || $row['_File'] == '..') {
				echo
					"<td colspan='2'>" .
					"</td>";
			} else {
				$this->displayItemFunctions($row);
			}
		}
		
		echo
			"</tr>";
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::displayOne', $this, $row);
	}
	
	function display() {
		if (!$this->rootPath) {
			tooltip::display(
				__("Root Path not defined!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$handled = api::callHooks(API_HOOK_BEFORE,
			'fileManager::display', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'fileManager::display', $this, $handled);
			
			return $handled;
		}
		
		$delete = null;
		$edit = null;
		
		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];
		
		$this->rootPath = rtrim($this->rootPath, '/').'/';
		
		if ($delete && $this->selectedFile && empty($_POST['delete']))
			url::displayConfirmation(
				'<b>'.__('Delete').'?!</b> "'.$this->selectedFile.'"');
		
		$folderform = new form(
					__("New Folder"),
					'newfolder');
		
		$this->setupFolderForm($folderform);
		$folderform->addSubmitButtons();
		
		if ($edit) {
			$folderform->add(
				__('Cancel'),
				'cancel',
				 FORM_INPUT_TYPE_BUTTON);
			$folderform->addAttributes("onclick=\"window.location='".
				str_replace('&amp;', '&', url::uri('file, edit, delete'))."'\"");
		}
		
		$form = new form(
				($edit?
					__("Edit File / Folder"):
					__("New File")),
				'neweditfile');
		
		if (!$edit)
			$form->action = url::uri('file, delete, limit');
		
		$this->setupForm($form);			
		$form->addSubmitButtons();
		
		if ($edit) {
			$form->add(
				__('Cancel'),
				'cancel',
				 FORM_INPUT_TYPE_BUTTON);
			$form->addAttributes("onclick=\"window.location='".
				str_replace('&amp;', '&', url::uri('file, edit, delete'))."'\"");
		}
		
		if (!$this->readOnly) {
			$this->verifyFolder($folderform);
			$this->verify($form);
		}
		
		$paging = new paging($this->limit);
		$paging->ignoreArgs = 'file, edit, delete';
		
		if ($this->ajaxPaging) {
			$paging->ajax = true;
			$paging->otherArgs = "&amp;request=".$this->uriRequest;
		}
		
		$paging->track(strtolower(get_class($this)).'limit');
		
		$files = array();
		
		if (is_dir($this->rootPath.$this->selectedPath)) {
			$d = dir($this->rootPath.$this->selectedPath);
			while (false !== ($entry = $d->read())) {
				if ((!$this->selectedPath || $this->selectedPath == '/') && 
					($entry == '.' || $entry == '..'))
					continue;
				
				if (@is_dir($this->rootPath.$this->selectedPath.$entry))
					$files[] = " ".$entry;
				else
					$files[] = $entry;
			}
			
			$d->close();
			sort($files);
				
		} else {
			if (!dirs::create($this->rootPath.$this->selectedPath))
				tooltip::display(
					__("Folder couldn't be created.")." " .
					sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
						$this->rootPath.$this->selectedPath), 
					TOOLTIP_ERROR);
		}
		
		$paging->setTotalItems(count($files));
		
		if (!$this->ajaxRequest)
			echo
				"<div class='" .
					strtolower(preg_replace('/([A-Z])/', '-\\1', get_class($this))).
					" file-manager'>";
		
		if ($paging->items) {
			echo 
				"<table cellpadding='0' cellspacing='0' class='list'>" .
					"<thead>" .
					"<tr>";
			
			$this->displayHeader();
			$this->displayHeaderOptions();
			
			if (!$this->readOnly)
				$this->displayHeaderFunctions();
					
			echo
					"</tr>" .
					"</thead>" .
					"<tbody>";
					
			for($i = $paging->getStart(); $i < $paging->getEnd(); $i++) {
				if (!isset($files[$i]) || !$files[$i])
					break;
				
				$row['_CSSClass'] = ($i%2?"pair":null);
				$row['_File'] = trim($files[$i]);
				
				$this->displayOne($row);
			}
			
			echo 
					"</tbody>" .
				"</table>" .
				"<br />";
			
		} else {
			tooltip::display(
				__("No files / directories found."),
				TOOLTIP_NOTIFICATION);
		}
		
		if ($this->showPaging)
			$paging->display();
		
		echo
			"<a name='form'></a>";
		
		if (!$this->readOnly) {
			if (!$edit)
				$this->displayFolderForm($folderform);
			
			$this->displayForm($form);
		}
		
		unset($folderform);
		unset($form);
		
		if (!$this->ajaxRequest)
			echo
				"</div>"; //file-manager
		
		api::callHooks(API_HOOK_AFTER,
			'fileManager::display', $this);
		
		return true;
	}
}

?>