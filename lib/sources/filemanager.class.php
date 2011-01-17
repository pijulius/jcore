<?php

/***************************************************************************
 *            filemanager.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
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
	
	function __construct() {
		if (isset($_GET['file'])) {
			preg_match('/([^(\/|\\\)]*)$/', $_GET['file'], $matches);
			
			if (isset($matches[1]) && $matches[1] != '.' && $matches[1] != '..')
				$this->selectedFile = $matches[1];
		}
		
		if (isset($_GET['dir']) && $_GET['dir'])
			$this->selectedPath = rtrim(str_replace('..', '', $_GET['dir']), '/').'/';
		
		$this->uriRequest = strtolower(get_class($this));
	}
	
	function verifyFolder(&$form) {
		if (!$form->verify())
			return false;
			
		if (!$this->createFolder($this->rootPath.$this->selectedPath.$form->get('FolderName'))) {
			tooltip::display(
				__("Folder couldn't be created.")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					$this->rootPath.$this->selectedPath), 
				TOOLTIP_ERROR);
		
			return false;
		}
		
		tooltip::display(
			__("Folder has been successfully created."),
			TOOLTIP_SUCCESS);
		
		return true;
	}
	
	function verify(&$form) {
		$delete = null;
		$edit = null;
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if ($delete) {
			if (!$this->selectedFile)
				return false;
			
			if (!$this->delete($this->rootPath.$this->selectedPath.$this->selectedFile)) {
				tooltip::display(
					__("File / Folder couldn't be deleted.")." " .
					sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
						$this->rootPath.$this->selectedPath.$this->selectedFile),
					TOOLTIP_ERROR);
			
				return false;
			}
			
			tooltip::display(
				__("File / Folder has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
		
		if (!$edit && !$form->get('Files')) {
			tooltip::display(
				__("No file selected to be uploaded! " .
					"Please select at least one file to upload."),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		if ($edit) {
			if (!$this->selectedFile)
				return false;
			
			if (!$this->edit($this->rootPath.$this->selectedPath.$this->selectedFile, 
				$this->rootPath.$this->selectedPath.$form->get('FileName')))
			{
				tooltip::display(
					__("File / Folder couldn't be renamed.")." " .
					sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
						$this->rootPath.$this->selectedPath.$this->selectedFile),
					TOOLTIP_ERROR);
			
				return false;
			}
				
			tooltip::display(
				__("File / Folder has been successfully renamed."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		$filenames = $form->get('Files');
		$successfiles = null;
		$failedfiles = null;
		
		foreach($form->getFile('Files') as $key => $file) {
			if (!$filename = $this->upload($file, 
					$this->rootPath.$this->selectedPath)) 
			{
				$failedfiles[] = $filenames[$key];
				continue;
			}
			
			$successfiles[] = $filenames[$key];
		}
		
		if ($failedfiles && count($failedfiles)) {
			tooltip::display(
				sprintf(__("There were problems uploading some of the files you selected. " .
					"The following files couldn't be uploaded: %s."),
					implode(', ', $failedfiles)),
				TOOLTIP_ERROR);
			
			if (!$successfiles || !count($successfiles))
				return false;
		}
		
		tooltip::display(
			sprintf(__("Files have been successfully uploaded. " .
				"The following files have been uploaded: %s."),
				implode(', ', $successfiles)),
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function createFolder($folder) {
		return dirs::create($folder);		
	}
	
	function upload($file, $to) {
		return files::upload($file, $to, FILE_TYPE_UPLOAD);
	}
	
	function edit($from, $to) {
		return files::rename($from, $to);		
	}
	
	function delete($file) {
		return files::delete($file);		
	}
	
	function download($file, $resumable = true) {
		if (!$file) {
			tooltip::display(
				__("No file selected to download!"),
				TOOLTIP_ERROR);
			return false;
		}
		
		session_write_close();
		files::display($file, true);
		
		return true;
	}
	
	function ajaxRequest() {
		$view = null;
		$download = null;
		
		if (isset($_GET['view']))
			$view = (int)$_GET['view'];
		
		if (isset($_GET['download']))
			$download = (int)$_GET['download'];
		
		if ($download && $this->selectedFile) {
			$this->download($this->rootPath.$this->selectedPath.
				$this->selectedFile);
			return true;
		}
		
		if ($view && $this->selectedFile) {
			files::display($this->rootPath.$this->selectedPath.
				$this->selectedFile);
			return true;
		}
		
		$this->ajaxPaging = true;
		$this->display();
		return true;
	}
	
	function setupFolderForm(&$form) {
		$form->add(
			__('Folder name'),
			'FolderName',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 300px;');
	}
	
	function setupForm(&$form) {
		$edit = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
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
						"onclick=\"jQuery.jCore.form.appendEntryTo(" .
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
	}
	
	function displayFolderForm(&$form) {
		$form->display();
	}
	
	function displayForm(&$form) {
		$form->display();
	}
	
	function displayHeader() {
		echo
			"<th colspan='2'><span class='nowrap'>".
				$this->selectedPath."</span></th>" .
			"<th style='text-align: right;'><span class='nowrap'>".
				__("Size")."</span></th>";
	}
	
	function displayHeaderOptions() {
	}
	
	function displayHeaderFunctions() {
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
	}
	
	function displayIcon(&$row) {
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
	}
	
	function displayTitle(&$row) {
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
	}
	
	function displayDetails(&$row) {
		echo
			calendar::datetime(
				@fileatime($this->rootPath.$this->selectedPath.$row['_File']));		
	}
	
	function displaySize(&$row) {
		echo
			files::humanSize(
				@filesize($this->rootPath.$this->selectedPath.$row['_File']));
	}
	
	function displayItem(&$row) {
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
	}
	
	function displayItemOptions(&$row) {
	}
	
	function displayItemFunctions(&$row) {
		echo
			"<td>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('file, edit, delete') .
					"&amp;file=".$row['_File']."&amp;edit=1#form'>" .
				"</a>" .
			"</td>" .
			"<td>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('file, edit, delete') .
					"&amp;file=".$row['_File']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayOne(&$row) {
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
	}
	
	function display() {
		if (!$this->rootPath) {
			tooltip::display(
				__("Root Path not defined!"),
				TOOLTIP_ERROR);
			
			return false;
		}
		
		$edit = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		$this->rootPath = rtrim($this->rootPath, '/').'/';
		
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
		
		if (!isset($this->ajaxRequest))
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
		
		if (!isset($this->ajaxRequest))
			echo
				"</div>"; //file-manager
		
		return true;
	}
}

?>