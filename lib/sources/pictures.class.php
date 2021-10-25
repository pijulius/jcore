<?php

/***************************************************************************
 *            pictures.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/files.class.php');
include_once('lib/calendar.class.php');

define('PICTURE_WATERMARK_TYPE_TEXT', 1);
define('PICTURE_WATERMARK_TYPE_IMAGE', 2);

if (!defined('PICTURE_WATERMARK_POSITION'))
	define('PICTURE_WATERMARK_POSITION', '100% 100%');

class _pictures {
	var $limit = 0;
	var $latests = false;
	var $format = null;
	var $ignorePaging = false;
	var $showPaging = true;
	var $randomize = false;
	var $columns = 0;
	var $sqlTable;
	var $sqlRow;
	var $sqlOwnerTable;
	var $sqlOwnerField = 'Title';
	var $sqlOwnerCountField = 'Pictures';
	var $selectedOwner;
	var $selectedOwnerID;
	var $customLink;
	var $uriRequest;
	var $subFolder;
	var $thumbnailsFolder = 'thumbnail/';
	var $thumbnailsDisabled = false;
	var $rootPath;
	var $rootURL;
	var $thumbnailWidth = PICTURE_THUMBNAIL_WIDTH;
	var $thumbnailHeight = PICTURE_THUMBNAIL_HEIGHT;
	var $watermarkPosition = PICTURE_WATERMARK_POSITION;
	var $ajaxPaging = AJAX_PAGING;
	var $ajaxRequest = null;

	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::pictures', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::pictures', $this, $handled);

			return $handled;
		}

		$this->uriRequest = strtolower(get_class($this));
		$this->subFolder = date('Ym');
		$this->rootPath = SITE_PATH.'sitefiles/image/';
		$this->rootURL = url::site().'sitefiles/image/';

		if ($this->sqlRow && isset($_GET[strtolower($this->sqlRow)]))
			$this->selectedOwnerID = (int)$_GET[strtolower($this->sqlRow)];

		api::callHooks(API_HOOK_AFTER,
			'pictures::pictures', $this);
	}

	function SQL() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::SQL', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::SQL', $this, $handled);

			return $handled;
		}

		$sql =
			" SELECT * FROM `{" .$this->sqlTable."}`" .
			" WHERE 1" .
			($this->sqlRow && !$this->latests?
				" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
				null) .
			" ORDER BY" .
			($this->randomize?
				" RAND()":
				($this->latests?
					" `TimeStamp` DESC,":
					" `OrderID`,") .
				" `ID` DESC");

		api::callHooks(API_HOOK_AFTER,
			'pictures::SQL', $this, $sql);

		return $sql;
	}

	// ************************************************   Admin Part
	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::setupAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::setupAdmin', $this, $handled);

			return $handled;
		}

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Picture'),
				'?path='.admin::path().'#adminform');

		favoriteLinks::add(
			__('Settings'),
			'?path=admin/site/settings');
		favoriteLinks::add(
			__('Content Files'),
			'?path=admin/content/contentfiles');

		api::callHooks(API_HOOK_AFTER,
			'pictures::setupAdmin', $this);
	}

	function setupAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::setupAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::setupAdminForm', $this, $form, $handled);

			return $handled;
		}

		$edit = null;

		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];

		if (!$edit) {
			$form->add(
				"<b>".__("Upload a new picture")."</b>",
				'',
				FORM_STATIC_TEXT);

			$form->add(
				__('Picture'),
				'Files[]',
				FORM_INPUT_TYPE_FILE);
			$form->setValueType(FORM_VALUE_TYPE_FILE);
			$form->setAttributes("multiple='multiple'");

			if (JCORE_VERSION >= '0.6')
				$form->setTooltipText(__("e.g. image.jpg, image.gif"));
			else
				$form->addAdditionalText(" (".__("e.g. image.jpg, image.gif").")");

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
						__("Upload another picture") .
					"</a>" .
				"</div>",
				null,
				FORM_STATIC_TEXT);
		}

		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 300px;');

		if ($edit) {
			$form->add(
				__('Picture'),
				'File',
				FORM_INPUT_TYPE_FILE);
			$form->setValueType(FORM_VALUE_TYPE_FILE);

			if (JCORE_VERSION >= '0.6')
				$form->setTooltipText(__("e.g. image.jpg, image.gif"));
			else
				$form->addAdditionalText(" (".__("e.g. image.jpg, image.gif").")");
		}

		if (!$edit) {
			$form->add(
				__('Already uploaded picture'),
				null,
				FORM_OPEN_FRAME_CONTAINER);

			$form->add(
				__('Picture'),
				'PictureID',
				FORM_INPUT_TYPE_SELECT);
			$form->setValueType(FORM_VALUE_TYPE_INT);
			$form->setStyle('width: 300px;');

			$form->addValue(
				'',
				'');

			$form->add(
				__('Existing Picture (URL)'),
				'Location',
				FORM_INPUT_TYPE_TEXT);
			$form->setStyle('width: 350px;');
			$form->setTooltipText(__("e.g. http://domain.com/image.jpg"));

			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER);
		}

		if ($this->thumbnailsDisabled) {
			$form->add(
				__('No Thumbnail'),
				'NoThumbnail',
				FORM_INPUT_TYPE_HIDDEN,
				false,
				1);

		} else {
			$form->add(
				__('Thumbnail Options'),
				null,
				FORM_OPEN_FRAME_CONTAINER);

			if ($edit) {
				$form->add(
					__('Regenerate'),
					'RegenerateThumbnail',
					FORM_INPUT_TYPE_CHECKBOX,
					false,
					1);
				$form->setValueType(FORM_VALUE_TYPE_BOOL);
				$form->addAdditionalText(" ".__("(create a new thumbnail)"));
			}

			$form->add(
				__('No Thumbnail'),
				'NoThumbnail',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				1);
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			$form->addAdditionalText(" ".__("(do not generate thumbnail)"));

			$form->add(
				__('No Sharpen'),
				'NoSharpen',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				1);
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			$form->addAdditionalText(" ".__("(do not apply sharpen effect to thumbnail)"));

			$form->add(
				__('Width'),
				'ThumbnailWidth',
				FORM_INPUT_TYPE_TEXT,
				false,
				$this->thumbnailWidth);
			$form->setStyle('width: 50px;');
			$form->setValueType(FORM_VALUE_TYPE_INT);

			$form->add(
				__('Height'),
				'ThumbnailHeight',
				FORM_INPUT_TYPE_TEXT,
				false,
				$this->thumbnailHeight);
			$form->setStyle('width: 50px;');
			$form->setValueType(FORM_VALUE_TYPE_INT);

			$form->add(
				__('Custom'),
				'ThumbnailFile',
				FORM_INPUT_TYPE_FILE);
			$form->setValueType(FORM_VALUE_TYPE_FILE);
			$form->setTooltipText(__("e.g. image.jpg, image.gif"));

			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER);
		}

		if (defined('PICTURE_WATERMARK') && PICTURE_WATERMARK) {
			$form->add(
				__('Watermark Options'),
				null,
				FORM_OPEN_FRAME_CONTAINER);

			$form->add(
				__('No Watermark'),
				'NoWatermark',
				FORM_INPUT_TYPE_CHECKBOX,
				false,
				1);
			$form->setValueType(FORM_VALUE_TYPE_BOOL);
			$form->addAdditionalText(" ".__("(do not add watermark)"));

			$form->add(
				__('Position'),
				'WatermarkPosition',
				FORM_INPUT_TYPE_TEXT,
				false,
				$this->watermarkPosition);
			$form->setStyle('width: 100px;');
			$form->setTooltipText(__("e.g. 100% 100% (right-bottom), 0% 0% (left-top)"));

			$form->add(
				null,
				null,
				FORM_CLOSE_FRAME_CONTAINER);
		}

		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);

		$form->add(
			__('Link to URL'),
			'URL',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 300px;');
		$form->setValueType(FORM_VALUE_TYPE_URL);

		if (JCORE_VERSION >= '0.6')
			$form->setTooltipText(__("e.g. http://domain.com"));
		else
			$form->addAdditionalText(" (".__("e.g. http://domain.com").")");

		$form->add(
			__('Uploaded on'),
			'TimeStamp',
			FORM_INPUT_TYPE_TIMESTAMP);
		$form->setStyle('width: 170px;');
		$form->setValueType(FORM_VALUE_TYPE_TIMESTAMP);

		$form->add(
			__('Order'),
			'OrderID',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);

		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);

		api::callHooks(API_HOOK_AFTER,
			'pictures::setupAdminForm', $this, $form);
	}

	function verifyAdmin(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::verifyAdmin', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::verifyAdmin', $this, $form, $handled);

			return $handled;
		}

		$reorder = null;
		$orders = null;
		$delete = null;
		$edit = null;
		$id = null;

		if (isset($_POST['reordersubmit']))
			$reorder = (string)$_POST['reordersubmit'];

		if (isset($_POST['orders']))
			$orders = (array)$_POST['orders'];

		if (isset($_POST['delete']))
			$delete = (int)$_POST['delete'];

		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];

		if (isset($_GET['id']))
			$id = (int)$_GET['id'];

		if ($reorder) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'pictures::verifyAdmin', $this, $form);
				return false;
			}

			foreach((array)$orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{".$this->sqlTable ."}`" .
					" SET `OrderID` = '".(int)$ovalue."'," .
					" `TimeStamp` = `TimeStamp`" .
					" WHERE `ID` = '".(int)$oid."'");
			}

			tooltip::display(
				__("Pictures have been successfully re-ordered."),
				TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'pictures::verifyAdmin', $this, $form, $orders);

			return true;
		}

		if ($delete) {
			if (!security::checkToken()) {
				api::callHooks(API_HOOK_AFTER,
					'pictures::verifyAdmin', $this, $form);
				return false;
			}

			$result = $this->delete($id);

			if ($result)
				tooltip::display(
					__("Picture has been successfully deleted."),
					TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'pictures::verifyAdmin', $this, $form, $result);

			return $result;
		}

		if (!$form->verify()) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::verifyAdmin', $this, $form);

			return false;
		}

		if (!$edit) {
			if (!$form->get('Files') && !$form->get('PictureID') &&
				!$form->get('Location'))
			{
				tooltip::display(
					__("No file selected to be uploaded as a new picture! " .
						"Please select a file / picture to upload or define an already " .
						"uploaded picture."),
					TOOLTIP_ERROR);

				api::callHooks(API_HOOK_AFTER,
					'pictures::verifyAdmin', $this, $form);

				return false;
			}

			if (!$form->get('Files') && $form->get('Location'))
				$form->set('Files', array($form->get('Location')));

			$form->add(
				'Picture',
				'File',
				FORM_INPUT_TYPE_HIDDEN);
		}

		$thumbnail = true;
		if ($form->get('NoThumbnail'))
			$thumbnail = false;

		$watermark = true;
		if ($form->get('NoWatermark'))
			$watermark = false;

		$sharpen = true;
		if ($form->get('NoSharpen'))
			$sharpen = false;

		if ($form->get('ThumbnailWidth'))
			$this->thumbnailWidth = $form->get('ThumbnailWidth');

		if ($form->get('ThumbnailHeight'))
			$this->thumbnailHeight = $form->get('ThumbnailHeight');

		if ($form->get('WatermarkPosition'))
			$this->watermarkPosition = $form->get('WatermarkPosition');

		if ($form->get('ThumbnailFile')) {
			$form->set('NoThumbnail', false);
			$thumbnail = false;
		}

		if ($edit) {
			$filename = null;

			if ($form->get('File')) {
				if (!$filename = $this->upload(
						$form->getFile('File'),
						$this->rootPath,
						$thumbnail, $watermark,
						$sharpen))
				{
					api::callHooks(API_HOOK_AFTER,
						'pictures::verifyAdmin', $this, $form);

					return false;
				}

				$form->set('File', $filename);
			}

			if ($form->get('ThumbnailFile')) {
				if (!$filename) {
					$picture = sql::fetch(sql::run(
						" SELECT * FROM `{".$this->sqlTable . "}`" .
						" WHERE `ID` = '".(int)$id."'"));

					if (!$picture) {
						tooltip::display(
							__("Thumbnail couldn't be updated as the " .
								"selected picture cannot be found!"),
							TOOLTIP_ERROR);

						api::callHooks(API_HOOK_AFTER,
							'pictures::verifyAdmin', $this, $form);

						return false;
					}

					$filename = preg_replace('/.*(\/|\\\)/', '',
						$picture['Location']);
				}

				$this->uploadThumbnail(
					$form->getFile('ThumbnailFile'),
					$this->rootPath,
					$filename);
			}

			if ($form->get('RegenerateThumbnail') &&
				!$this->regenerateThumbnail($id, $sharpen))
			{
				api::callHooks(API_HOOK_AFTER,
					'pictures::verifyAdmin', $this, $form);

				return false;
			}

			$form->setValue('RegenerateThumbnail', false);

			$result = $this->edit($id, $form->getPostArray());

			if ($result)
				tooltip::display(
					__("Picture has been successfully updated.")." " .
					"<a href='#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);

			api::callHooks(API_HOOK_AFTER,
				'pictures::verifyAdmin', $this, $form, $result);

			return $result;
		}

		if (!$form->get('Files') && $form->get('PictureID')) {
			$picture = sql::fetch(sql::run(
				" SELECT * FROM `{".$this->sqlTable . "}`" .
				" WHERE `ID` = '".$form->get('PictureID')."'"));

			$form->set('Title', $picture['Title']);
			$form->set('File', $picture['Location']);

			if (!$form->get('URL'))
				$form->set('URL', $picture['URL']);

			if (JCORE_VERSION >= '0.5' && !$picture['Thumbnail'])
				$form->set('NoThumbnail', 1);

			$newid = $this->add($form->getPostArray());

			if ($newid) {
				tooltip::display(
					__("Picture has been successfully added.")." " .
					"<a href='".url::uri('id, edit, delete') .
						"&amp;id=".$newid."&amp;edit=1#adminform'>" .
						__("Edit") .
					"</a>",
					TOOLTIP_SUCCESS);

				$form->reset();
			}

			api::callHooks(API_HOOK_AFTER,
				'pictures::verifyAdmin', $this, $form, $newid);

			return $newid;
		}

		$files = $form->getFile('Files');
		$filenames = $form->get('Files');
		$customtitle = $form->get('Title');
		$successfiles = null;
		$failedfiles = null;
		$noorderid = false;

		if (!$files || !count($files))
			$files = $filenames;

		if (!$form->get('OrderID'))
			$noorderid = true;

		$i = 1;
		foreach($filenames as $key => $filename) {
			if (!$newfilename = $this->upload(
					@$files[$key], $this->rootPath, $thumbnail, $watermark, $sharpen))
			{
				$failedfiles[] = $filename;
				continue;
			}

			if ($form->get('ThumbnailFile'))
				$this->uploadThumbnail(
					$form->getFile('ThumbnailFile'),
					$this->rootPath,
					$newfilename);

			$form->set('File', $newfilename);
			$form->set('Title',
				($customtitle?
					$customtitle .
					(count($filenames) > 1?
						' ('.$i.')':
						null):
					preg_replace('/(.*)\..*/', '\1', $filename)));

			if ($noorderid)
				$form->set('OrderID', $i);

			if (!$mnewid = $this->add($form->getPostArray())) {
				$failedfiles[] = $filename;
				continue;
			}

			$successfiles[] = $filename;
			$i++;
		}

		if ($failedfiles && count($failedfiles)) {
			tooltip::display(
				sprintf(__("There were problems uploading some of the files you selected. " .
					"The following files couldn't be uploaded: %s."),
					implode(', ', $failedfiles)),
				TOOLTIP_ERROR);

			if (!$successfiles || !count($successfiles)) {
				api::callHooks(API_HOOK_AFTER,
					'pictures::verifyAdmin', $this, $form);

				return false;
			}
		}

		tooltip::display(
			sprintf(__("Picture(s) have been successfully uploaded. " .
				"The following files have been uploaded: %s."),
				implode(', ', $successfiles)),
			TOOLTIP_SUCCESS);

		$form->reset();

		api::callHooks(API_HOOK_AFTER,
			'pictures::verifyAdmin', $this, $form, $successfiles);

		return true;
	}

	function displayAdminListHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayAdminListHeader', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayAdminListHeader', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Picture")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Details")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'pictures::displayAdminListHeader', $this);
	}

	function displayAdminListHeaderOptions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayAdminListHeaderOptions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayAdminListHeaderOptions', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'pictures::displayAdminListHeaderOptions', $this);
	}

	function displayAdminListHeaderFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayAdminListHeaderFunctions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayAdminListHeaderFunctions', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'pictures::displayAdminListHeaderFunctions', $this);
	}

	function displayAdminListItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayAdminListItem', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayAdminListItem', $this, $row, $handled);

			return $handled;
		}

		$href = url::uri().
			"&amp;request=".$this->uriRequest .
			"&amp;view=".$row['ID']."&amp;ajax=1";

		$pic = $this->rootURL.
			(JCORE_VERSION >= '0.5' && $row['Thumbnail']?
				$this->thumbnailsFolder:
				null) .
			$row['Location'].
			(JCORE_VERSION >= '0.5'?
				"?".@filemtime($this->rootPath.
					($row['Thumbnail']?
						$this->thumbnailsFolder:
						null) .
					$row['Location']):
				null);

		$thumb_data = null;

		if (JCORE_VERSION >= '0.5' && $row['Thumbnail'])
			$thumb_data = @getimagesize($this->rootPath.
				$this->thumbnailsFolder.$row['Location']);

		$file_data = @getimagesize($this->rootPath.
			$row['Location']);

		$thumbnailwidth = PICTURE_THUMBNAIL_WIDTH;
		$thumbnailheight = PICTURE_THUMBNAIL_HEIGHT;

		if ($file_data[0] && $file_data[0] < $thumbnailwidth)
			$thumbnailwidth = $file_data[0];

		if ($file_data[1] && $file_data[1] < $thumbnailheight)
			$thumbnailheight = $file_data[1];

		echo
			"<td>" .
				"<input type='text' name='orders[".$row['ID']."]' " .
					"value='".$row['OrderID']."' " .
					"class='order-id-entry' tabindex='1' />" .
			"</td>" .
			"<td>" .
				"<div class='picture picture".$row['ID']."'>" .
				"<a href='".$href."' " .
					"title='".htmlchars($row['Title'], ENT_QUOTES)."' " .
					"rel='lightbox[".$this->selectedOwnerID."]'>" .
				"<img src='".$pic."' " .
					"alt='".htmlchars($row['Title'], ENT_QUOTES)."' " .
					($thumbnailwidth?
						"width='".$thumbnailwidth."' ":
						null) .
					($thumbnailheight?
						"height='".$thumbnailheight."' ":
						null) .
					" />" .
				"</a>" .
				"</div>" .
			"</td>" .
			"<td class='auto-width'>" .
				"<div class='bold'>".
					$row['Title'] .
				"</div>" .
				"<div class='comment' style='padding-left: 10px;'>" .
					calendar::dateTime($row['TimeStamp']) .
					(isset($row['Views'])?
						", ".sprintf(__("%s views"), $row['Views']):
						null) .
				"</div>" .
				"<br />" .
				"<div style='padding-left: 10px;'>" .
				__("Dimension").": <b>" .
				$file_data[0]." x ".$file_data[1]."</b><br />" .
				($thumb_data?
					__("Thumbnail").": <b>".__("Yes")."</b> (" .
					$thumb_data[0]." x ".$thumb_data[1].")<br />":
					null) .
				($row['URL']?
					__("Link to URL").": " .
					"<a href='".$row['URL']."' target='_blank'><b>" .
						$row['URL'] .
					"</b></a>" .
					"<br />":
					null) .
				"<br />" .
				"</div>" .
			"</td>";

		api::callHooks(API_HOOK_AFTER,
			'pictures::displayAdminListItem', $this, $row);
	}

	function displayAdminListItemOptions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayAdminListItemOptions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayAdminListItemOptions', $this, $row, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'pictures::displayAdminListItemOptions', $this, $row);
	}

	function displayAdminListItemFunctions(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayAdminListItemFunctions', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayAdminListItemFunctions', $this, $row, $handled);

			return $handled;
		}

		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";

		api::callHooks(API_HOOK_AFTER,
			'pictures::displayAdminListItemFunctions', $this, $row);
	}

	function displayAdminListFunctions() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayAdminListFunctions', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayAdminListFunctions', $this, $handled);

			return $handled;
		}

		echo
			"<input type='submit' name='reordersubmit' value='" .
				htmlchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlchars(__("Reset"), ENT_QUOTES)."' class='button' />";

		api::callHooks(API_HOOK_AFTER,
			'pictures::displayAdminListFunctions', $this);
	}

	function displayAdminList(&$rows) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayAdminList', $this, $rows);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayAdminList', $this, $rows, $handled);

			return $handled;
		}

		echo
			"<form action='".
				url::uri('edit, delete')."' method='post'>" .
				"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";

		echo "<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>";

		$this->displayAdminListHeader();
		$this->displayAdminListHeaderOptions();

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
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

			if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
				$this->displayAdminListItemFunctions($row);

			echo
				"</tr>";

			$i++;
		}

		echo
				"</tbody>" .
			"</table>" .
			"<br />";

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			$this->displayAdminListFunctions();

			echo
				"<div class='clear-both'></div>" .
				"<br />";
		}

		echo
			"</form>";

		api::callHooks(API_HOOK_AFTER,
			'pictures::displayAdminList', $this, $rows);
	}

	function displayAdminForm(&$form) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayAdminForm', $this, $form);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayAdminForm', $this, $form, $handled);

			return $handled;
		}

		$form->display();

		api::callHooks(API_HOOK_AFTER,
			'pictures::displayAdminForm', $this, $form);
	}

	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayAdminTitle', $this, $ownertitle);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayAdminTitle', $this, $ownertitle, $handled);

			return $handled;
		}

		admin::displayTitle(
			__(trim(ucfirst(preg_replace('/([A-Z])/', ' \1',
				$this->sqlOwnerCountField)))),
			$ownertitle);

		api::callHooks(API_HOOK_AFTER,
			'pictures::displayAdminTitle', $this, $ownertitle);
	}

	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayAdminDescription', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayAdminDescription', $this, $handled);

			return $handled;
		}
		api::callHooks(API_HOOK_AFTER,
			'pictures::displayAdminDescription', $this);
	}

	function displayAdmin() {
		if (!$this->sqlTable) {
			tooltip::display(
				__("Storage table not defined."),
				TOOLTIP_NOTIFICATION);

			return;
		}

		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayAdmin', $this, $handled);

			return $handled;
		}

		$delete = null;
		$edit = null;
		$id = null;

		if (isset($_GET['delete']))
			$delete = (int)$_GET['delete'];

		if (isset($_GET['edit']))
			$edit = (int)$_GET['edit'];

		if (isset($_GET['id']))
			$id = (int)$_GET['id'];

		if ($this->sqlOwnerTable) {
			$this->selectedOwnerID = admin::getPathID();

			$selectedowner = sql::fetch(sql::run(
				" SELECT `".$this->sqlOwnerField."`" .
				" FROM `{" .$this->sqlOwnerTable."}`" .
				" WHERE `ID` = '".$this->selectedOwnerID."'"));

			$this->displayAdminTitle($selectedowner[$this->sqlOwnerField]);

		} else {
			$this->displayAdminTitle($this->selectedOwner);
		}

		$this->displayAdminDescription();

		echo
			"<div class='admin-content'>";

		if ($delete && $id && empty($_POST['delete'])) {
			$selected = sql::fetch(sql::run(
				" SELECT `Title` FROM `{".$this->sqlTable."}`" .
				" WHERE `ID` = '".$id."'"));

			url::displayConfirmation(
				'<b>'.__('Delete').'?!</b> "'.$selected['Title'].'"');
		}

		$form = new form(
				($edit?
					__("Edit Picture"):
					__("New Picture")),
				'neweditpicture');

		if (!$edit)
			$form->action = url::uri('id, delete, limit');

		$this->setupAdminForm($form);
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

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			$verifyok = $this->verifyAdmin($form);

		$paging = new paging(10);
		$paging->ignoreArgs = 'id, edit, delete';

		$rows = sql::run(
				" SELECT * FROM `{".$this->sqlTable."}`" .
				" WHERE 1" .
				($this->sqlRow?
					" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
					null) .
				" ORDER BY `OrderID`, `ID` DESC" .
				" LIMIT ".$paging->limit);

		$paging->setTotalItems(sql::count());

		if ($paging->items)
			$this->displayAdminList($rows);
		else
			tooltip::display(
					sprintf(__("No %s found for this %s."),
						strtolower(__(trim(preg_replace('/([A-Z])/', ' \1',
							$this->sqlOwnerCountField)))),
						$this->selectedOwner),
					TOOLTIP_NOTIFICATION);

		$paging->display();

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE) {
			if ($edit && ($verifyok || !$form->submitted())) {
				$selected = sql::fetch(sql::run(
					" SELECT * FROM `{".$this->sqlTable."}`" .
					" WHERE `ID` = '".$id."'"));

				$form->setValues($selected);
				$form->setValue('File', $selected['Location']);

				if (JCORE_VERSION >= '0.5' && !$selected['Thumbnail'])
					$form->setValue('NoThumbnail', 1);

				if (JCORE_VERSION >= '0.5' && $selected['Thumbnail'])
					$file_data = @getimagesize($this->rootPath.
						$this->thumbnailsFolder.$selected['Location']);
				else
					$file_data = @getimagesize($this->rootPath.
						$selected['Location']);

				if ($this->thumbnailWidth)
					$form->setValue('ThumbnailWidth', $file_data[0]);

				if ($this->thumbnailHeight)
					$form->setValue('ThumbnailHeight', $file_data[1]);
			}

			if (!$edit) {
				$pics = sql::run(
					" SELECT * FROM `{".$this->sqlTable."}`" .
					" GROUP BY `Location`" .
					" ORDER BY `ID` DESC" .
					" LIMIT 100");

				while($pic = sql::fetch($pics))
					$form->addValue(
						'PictureID',
						$pic['ID'],
						$pic['Title']);
			}

			echo
				"<a name='adminform'></a>";

			$this->displayAdminForm($form);
		}

		unset($form);

		echo
			"</div>";	//admin-content

		api::callHooks(API_HOOK_AFTER,
			'pictures::displayAdmin', $this);
	}

	function add($values) {
		if (!is_array($values))
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::add', $this, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::add', $this, $values, $handled);

			return $handled;
		}

		if ($values['OrderID'] == '') {
			sql::run(
				" UPDATE `{".$this->sqlTable."}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`".
				($this->sqlRow?
					" WHERE `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
					null));

			$values['OrderID'] = 1;

		} else {
			sql::run(
				" UPDATE `{".$this->sqlTable."}` SET " .
				" `OrderID` = `OrderID` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE 1" .
				($this->sqlRow?
					" AND `".$this->sqlRow."` = '".$this->selectedOwnerID."'":
					null) .
				" AND `OrderID` >= '".(int)$values['OrderID']."'");
		}

		$newid = sql::run(
			" INSERT INTO `{".$this->sqlTable."}` SET ".
			($this->sqlRow?
				" `".$this->sqlRow."` = '".$this->selectedOwnerID."',":
				null) .
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `TimeStamp` = " .
				($values['TimeStamp']?
					"'".sql::escape($values['TimeStamp'])."'":
					"NOW()").
				"," .
			" `URL` = '".
				sql::escape($values['URL'])."'," .
			($values['File']?
				" `Location` = '".
					(strpos($values['File'], '/') === false?
						$this->subFolder.'/':
						null) .
					sql::escape($values['File']).
					"',":
					null) .
			(JCORE_VERSION >= '0.5'?
				" `Thumbnail` = " .
					($values['NoThumbnail']?
						"0":
						"1").
					",":
				null) .
			" `OrderID` = '".
				(int)$values['OrderID']."'");

		if (!$newid) {
			tooltip::display(
				sprintf(__("Picture couldn't be created! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

		} else if ($this->sqlOwnerTable) {
			sql::run(
				" UPDATE `{".$this->sqlOwnerTable."}` SET " .
				" `".$this->sqlOwnerCountField."` = `".
					$this->sqlOwnerCountField."` + 1," .
				" `TimeStamp` = `TimeStamp`" .
				" WHERE `ID` = '".$this->selectedOwnerID."'");
		}

		api::callHooks(API_HOOK_AFTER,
			'pictures::add', $this, $values, $newid);

		return $newid;
	}

	function edit($id, $values) {
		if (!$id)
			return false;

		if (!is_array($values))
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::edit', $this, $id, $values);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::edit', $this, $id, $values, $handled);

			return $handled;
		}

		sql::run(
			" UPDATE `{".$this->sqlTable."}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `TimeStamp` = " .
				($values['TimeStamp']?
					"'".sql::escape($values['TimeStamp'])."'":
					"NOW()").
				"," .
			" `URL` = '".
				sql::escape($values['URL'])."'," .
			($values['File']?
				" `Location` = '".
					(strpos($values['File'], '/') === false?
						$this->subFolder.'/':
						null) .
					sql::escape($values['File']).
					"',":
				null) .
			(JCORE_VERSION >= '0.5'?
				" `Thumbnail` = " .
					($values['NoThumbnail']?
						"0":
						"1").
					",":
				null) .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");

		$result = (sql::affected() != -1);

		if (!$result)
			tooltip::display(
				sprintf(__("Picture couldn't be updated! Error: %s"),
					sql::error()),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'pictures::edit', $this, $id, $values, $result);

		return $result;
	}

	function delete($id) {
		if (!$id)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::delete', $this, $id);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::delete', $this, $id, $handled);

			return $handled;
		}

		$row = sql::fetch(sql::run(
			" SELECT `Location` FROM `{".$this->sqlTable."}`" .
			" WHERE `ID` = '".(int)$id."'"));

		if ($row &&
			!sql::count("SELECT COUNT(`ID`) AS `Rows` FROM `{".$this->sqlTable."}`" .
				" WHERE `Location` = '".$row['Location']."'" .
				" AND `ID` != '".(int)$id."'"))
		{
			if (JCORE_VERSION >= '0.5') {
				files::delete($this->rootPath.$row['Location']);
				files::delete($this->rootPath.$this->thumbnailsFolder.
					$row['Location']);
			} else {
				files::delete($this->rootPath.$row['Location']);
				files::delete($this->rootPath.
					$this->largePicture($row['Location']));
			}
		}

		sql::run(
			" DELETE FROM `{".$this->sqlTable."}`" .
			" WHERE `ID` = '".(int)$id."'");

		if ($this->sqlOwnerTable) {
			$row = sql::fetch(sql::run(
				" SELECT COUNT(`ID`) AS `Rows` FROM `{".$this->sqlTable."}`" .
				" WHERE `".$this->sqlRow."` = '".$this->selectedOwnerID."'"));

			sql::run("UPDATE `{".$this->sqlOwnerTable."}`" .
				" SET `".$this->sqlOwnerCountField."` = '".(int)$row['Rows']."'," .
				" `TimeStamp` = `TimeStamp` " .
				" WHERE `ID` = '".$this->selectedOwnerID."'");
		}

		api::callHooks(API_HOOK_AFTER,
			'pictures::delete', $this, $id);

		return true;
	}

	function unsharpMask($img, $amount, $radius, $threshold)    {
		////////////////////////////////////////////////////////////////////////////////////////////////
		////
		////                  Unsharp Mask for PHP - version 2.1.1
		////
		////    Unsharp mask algorithm by Torstein Hønsi 2003-07.
		////             thoensi_at_netcom_dot_no.
		////               Please leave this notice.
		////
		///////////////////////////////////////////////////////////////////////////////////////////////

	    // $img is an image that is already created within php using
    	// imgcreatetruecolor. No url! $img must be a truecolor image.

    	// Attempt to calibrate the parameters to Photoshop:
	    if ($amount > 500)    $amount = 500;
    	$amount = $amount * 0.016;
    	if ($radius > 50)    $radius = 50;
    	$radius = $radius * 2;
	    if ($threshold > 255)    $threshold = 255;

	    $radius = abs(round($radius));     // Only integers make sense.
    	if ($radius == 0)
        	return $img;

	    $w = imagesx($img); $h = imagesy($img);
    	$imgCanvas = imagecreatetruecolor($w, $h);
    	$imgBlur = imagecreatetruecolor($w, $h);

    	// Gaussian blur matrix:
	    //
    	//    1    2    1
	    //    2    4    2
    	//    1    2    1
	    //
    	//////////////////////////////////////////////////

    	if (function_exists('imageconvolution')) { // PHP >= 5.1
            $matrix = array(
            array( 1, 2, 1 ),
            array( 2, 4, 2 ),
            array( 1, 2, 1 )
	        );
	        imagecopy ($imgBlur, $img, 0, 0, 0, 0, $w, $h);
    	    imageconvolution($imgBlur, $matrix, 16, 0);
    	} else {

	    // Move copies of the image around one pixel at the time and merge them with weight
    	// according to the matrix. The same matrix is simply repeated for higher radii.
        	for ($i = 0; $i < $radius; $i++)    {
            	imagecopy ($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h); // left
	            imagecopymerge ($imgBlur, $img, 1, 0, 0, 0, $w, $h, 50); // right
    	        imagecopymerge ($imgBlur, $img, 0, 0, 0, 0, $w, $h, 50); // center
        	    imagecopy ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);

    	        imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333 ); // up
        	    imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25); // down
        	}
    	}

	    if($threshold>0){
    	    // Calculate the difference between the blurred pixels and the original
        	// and set the pixels
	        for ($x = 0; $x < $w-1; $x++)    { // each row
    	        for ($y = 0; $y < $h; $y++)    { // each pixel

            	    $rgbOrig = ImageColorAt($img, $x, $y);
                	$rOrig = (($rgbOrig >> 16) & 0xFF);
	                $gOrig = (($rgbOrig >> 8) & 0xFF);
    	            $bOrig = ($rgbOrig & 0xFF);

            	    $rgbBlur = ImageColorAt($imgBlur, $x, $y);

	                $rBlur = (($rgbBlur >> 16) & 0xFF);
    	            $gBlur = (($rgbBlur >> 8) & 0xFF);
        	        $bBlur = ($rgbBlur & 0xFF);

	                // When the masked pixels differ less from the original
    	            // than the threshold specifies, they are set to their original value.
        	        $rNew = (abs($rOrig - $rBlur) >= $threshold)
            	        ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))
                	    : $rOrig;
	                $gNew = (abs($gOrig - $gBlur) >= $threshold)
    	                ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))
        	            : $gOrig;
            	    $bNew = (abs($bOrig - $bBlur) >= $threshold)
                	    ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))
	                    : $bOrig;

	                if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
    	                    $pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
        	                ImageSetPixel($img, $x, $y, $pixCol);
            	        }
	            }
    	    }
	    } else{
	        for ($x = 0; $x < $w; $x++)    { // each row
    	        for ($y = 0; $y < $h; $y++)    { // each pixel
        	        $rgbOrig = ImageColorAt($img, $x, $y);
            	    $rOrig = (($rgbOrig >> 16) & 0xFF);
                	$gOrig = (($rgbOrig >> 8) & 0xFF);
	                $bOrig = ($rgbOrig & 0xFF);

        	        $rgbBlur = ImageColorAt($imgBlur, $x, $y);

                	$rBlur = (($rgbBlur >> 16) & 0xFF);
	                $gBlur = (($rgbBlur >> 8) & 0xFF);
    	            $bBlur = ($rgbBlur & 0xFF);

            	    $rNew = ($amount * ($rOrig - $rBlur)) + $rOrig;
                	    if($rNew>255){$rNew=255;}
                    	elseif($rNew<0){$rNew=0;}
	                $gNew = ($amount * ($gOrig - $gBlur)) + $gOrig;
    	                if($gNew>255){$gNew=255;}
        	            elseif($gNew<0){$gNew=0;}
            	    $bNew = ($amount * ($bOrig - $bBlur)) + $bOrig;
                	    if($bNew>255){$bNew=255;}
                    	elseif($bNew<0){$bNew=0;}
	                $rgbNew = ($rNew << 16) + ($gNew <<8) + $bNew;
    	                ImageSetPixel($img, $x, $y, $rgbNew);
        	    }
	        }
    	}

    	imagedestroy($imgCanvas);
	    imagedestroy($imgBlur);

    	return $img;
	}

	function uploadThumbnail($file, $to, $filename = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::uploadThumbnail', $this, $file, $to, $filename);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::uploadThumbnail', $this, $file, $to, $filename, $handled);

			return $handled;
		}

		$result = files::upload(
			$file,
			$to.$this->thumbnailsFolder.$this->subFolder.'/'.$filename,
			FILE_TYPE_IMAGE);

		api::callHooks(API_HOOK_AFTER,
			'pictures::uploadThumbnail', $this, $file, $to, $filename, $result);

		return $result;
	}

	function createThumbnail($image, $thumb_width = NULL, $thumb_height = NULL, $save_image = NULL, $sharpen = true, $timeout = 60) {
		if (!$image)
			return false;

		if (!(int)$thumb_width && !(int)$thumb_height)
			return false;

		$thumb_path = preg_replace('/(.*(\/|\\\)).*/', '\1', $save_image);

		if ($save_image && $thumb_path &&
			!is_dir($thumb_path) && !@mkdir($thumb_path, 0777, true))
		{
			tooltip::display(
				__("Thumbnail couldn't be created!")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					$thumb_path),
				TOOLTIP_ERROR);

			return false;
		}

		$file_data = @getimagesize($image);

		$img_type = $file_data[2];
		$img_width = $file_data[0];
		$img_height = $file_data[1];

		if (((int)$thumb_width && !(int)$thumb_height && $img_width <= (int)$thumb_width) ||
			((int)$thumb_height && !(int)$thumb_width && $img_height <= (int)$thumb_height) ||
			($img_width <= (int)$thumb_width && $img_height <= (int)$thumb_height))
		{
			if ($save_image) {
				if ($image != $save_image)
					copy($image, $save_image);

				return true;

			} else {
				return false;
			}
		}

		if (security::checkOutOfMemory(Round(($file_data[0] * $file_data[1] *
			@$file_data['bits'] * @$file_data['channels'] / 8 +
			Pow(2, 16)) * 1.65)))
		{
			tooltip::display(
				__("Couldn't create thumbnail as the defined picture " .
					"is to big to be processed with the current memory limit " .
					"set. Please try to upload a smaller image instead."),
				TOOLTIP_ERROR);

			return false;
		}

		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::createThumbnail', $this, $image, $thumb_width, $thumb_height, $save_image, $sharpen, $timeout);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::createThumbnail', $this, $image, $thumb_width, $thumb_height, $save_image, $sharpen, $timeout, $handled);

			return $handled;
		}

		$src_img = null;

	   	if ($img_type == IMAGETYPE_GIF)
	   		$src_img = @imagecreatefromgif($image);
   		elseif ($img_type == IMAGETYPE_JPEG)
   			$src_img = @imagecreatefromjpeg($image);
	   	elseif ($img_type == IMAGETYPE_PNG)
	   		$src_img = @imagecreatefrompng($image);
	   	elseif ($img_type == IMAGETYPE_WBMP)
	   		$src_img = @imagecreatefromwbmp($image);

		if (!$src_img) return false;

		if (!ini_get('safe_mode') && $timeout)
			@set_time_limit($timeout);

		if ((int)$thumb_width && (int)$thumb_height) {
		   	$dst_img = ImageCreateTrueColor($thumb_width, $thumb_height);

			$img_thumb_width = round($img_width*(int)$thumb_height/$img_height);
			$img_thumb_height = round($img_height*(int)$thumb_width/$img_width);

			$img_x = 0;
			$img_y = 0;

			if ($img_thumb_width < $thumb_width)
				$img_y = round((($img_thumb_height - $thumb_height)*100/$img_thumb_height)*$img_height/100/2);

			if ($img_thumb_height < $thumb_height)
				$img_x = round((($img_thumb_width - $thumb_width)*100/$img_thumb_width)*$img_width/100/2);

			imagecolortransparent($dst_img, imagecolorallocate($dst_img, 0, 0, 0));
			imagealphablending($dst_img, false);
			imagesavealpha($dst_img, true);

			imagecopyresampled($dst_img, $src_img,
				0, 0, $img_x, $img_y,
				$thumb_width, $thumb_height, $img_width-$img_x*2, $img_height-$img_y*2);

		} else {
			if (!(int)$thumb_width)
				$thumb_width = round($img_width*(int)$thumb_height/$img_height);

			if (!(int)$thumb_height)
				$thumb_height = round($img_height*(int)$thumb_width/$img_width);

		   	$dst_img = ImageCreateTrueColor($thumb_width, $thumb_height);

			imagecolortransparent($dst_img, imagecolorallocate($dst_img, 0, 0, 0));
			imagealphablending($dst_img, false);
			imagesavealpha($dst_img, true);

			imagecopyresampled($dst_img, $src_img,
				0, 0, 0, 0,
				$thumb_width, $thumb_height, $img_width, $img_height);
		}

		if ($sharpen)
			$dst_img = $this->unsharpMask($dst_img, 80, 0.5, 3);

		$success = false;

	   	if ($img_type == IMAGETYPE_GIF)
	   		$success = imagegif($dst_img, $save_image);
   		elseif ($img_type == IMAGETYPE_JPEG)
   			$success = imagejpeg($dst_img, $save_image, 100);
	   	elseif ($img_type == IMAGETYPE_PNG)
	   		$success = imagepng($dst_img, $save_image);
	   	elseif ($img_type == IMAGETYPE_WBMP)
	   		$success = imagewbmp($dst_img, $save_image);

    	imagedestroy($dst_img);
   		imagedestroy($src_img);

		api::callHooks(API_HOOK_AFTER,
			'pictures::createThumbnail', $this, $image, $thumb_width, $thumb_height, $save_image, $sharpen, $timeout, $success);

	   	return $success;
	}

	function regenerateThumbnail($picid, $sharpen = true) {
		if (!$picid)
			return false;

		$picture = sql::fetch(sql::run(
			" SELECT * FROM `{".$this->sqlTable . "}`" .
			" WHERE `ID` = '".(int)$picid."'"));

		if (!$picture) {
			tooltip::display(
				__("Thumbnail couldn't be regenerated as the " .
					"selected picture cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}

		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::regenerateThumbnail', $this, $picid, $sharpen);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::regenerateThumbnail', $this, $picid, $sharpen, $handled);

			return $handled;
		}

		$picpath = $this->rootPath.'/';
		$thumbpath = $this->rootPath.$this->thumbnailsFolder.'/';
		$filename = $picture['Location'];

		if (JCORE_VERSION >= '0.5') {
			$result = $this->createThumbnail($picpath.$filename,
				$this->thumbnailWidth, $this->thumbnailHeight,
				$thumbpath.$filename, $sharpen);

		} else {
			copy($picpath.$filename, $picpath.$this->largePicture($filename));
			$result = $this->createThumbnail($picpath.$filename,
				$this->thumbnailWidth, $this->thumbnailHeight,
				$picpath.$filename, $sharpen);
		}

		api::callHooks(API_HOOK_AFTER,
			'pictures::regenerateThumbnail', $this, $picid, $sharpen, $result);

		return $result;
	}

	function addWatermark($file, $watermark, $watermarkx = '100%', $watermarky = '100%', $watermarktype = PICTURE_WATERMARK_TYPE_TEXT, $timeout = 60) {
		if (!$file || !$watermark)
			return false;

		if (@!file_exists($file)) {
			tooltip::display(
				sprintf(__("Couldn't add watermark to picture as the defined " .
					"\"%s\" file cannot be found!"), $file),
				TOOLTIP_ERROR);

			return false;
		}

		$file_data = @getimagesize($file);

		if (security::checkOutOfMemory(Round(($file_data[0] * $file_data[1] *
			@$file_data['bits'] * @$file_data['channels'] / 8 +
			Pow(2, 16)) * 1.65)))
		{
			tooltip::display(
				__("Couldn't add watermark to picture as the picture " .
					"is to big to be processed with the current memory limit " .
					"set. Please try to upload a smaller image instead."),
				TOOLTIP_ERROR);

			return false;
		}

		$imgwidth = $file_data[0];
		$imgheight = $file_data[1];
		$imgtype = $file_data[2];

		$img = null;

	   	if ($imgtype == 1) $img = @imagecreatefromgif($file);
   		if ($imgtype == 2) $img = @imagecreatefromjpeg($file);
	   	if ($imgtype == 3) $img = @imagecreatefrompng($file);

		if (!$img) {
			tooltip::display(
				sprintf(__("Couldn't add watermark to picture as the defined " .
					"\"%s\" file is not a compatible image file! " .
					"Please try again by uploading a JPEG file instead."), $file),
				TOOLTIP_ERROR);

			return false;
		}

		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::addWatermark', $this, $file, $watermark, $watermarkx, $watermarky, $watermarktype, $timeout);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::addWatermark', $this, $file, $watermark, $watermarkx, $watermarky, $watermarktype, $timeout, $handled);

			return $handled;
		}

		if (!ini_get('safe_mode') && $timeout)
			@set_time_limit($timeout);

		if (strpos($watermarkx, '%') !== false)
			$watermarkx = round((int)$watermarkx*$imgwidth/100);
		else
			$watermarkx = (int)$watermarkx;

		if (strpos($watermarky, '%') !== false)
			$watermarky = round((int)$watermarky*$imgheight/100);
		else
			$watermarky = (int)$watermarky;

		if ($watermarktype == PICTURE_WATERMARK_TYPE_IMAGE) {
			if (strpos($watermark, '/') !== 0 && strpos($watermark, '://') === false)
				$watermark = SITE_PATH.'template/'.$watermark;

			$file_data = @getimagesize($watermark);

			$watermarkwidth = $file_data[0];
			$watermarkheight = $file_data[1];
			$watermarktype = $file_data[2];

			$watermarkimg = null;

		   	if ($watermarktype == 1) $watermarkimg = @imagecreatefromgif($watermark);
   			if ($watermarktype == 2) $watermarkimg = @imagecreatefromjpeg($watermark);
	   		if ($watermarktype == 3) $watermarkimg = @imagecreatefrompng($watermark);

			if (!$watermarkimg) {
				tooltip::display(
					sprintf(__("Couldn't add watermark to picture as the defined " .
						"\"%s\" file is not a compatible image file! " .
						"Please try again by defining a JPEG or PNG watermark file."), $watermark),
					TOOLTIP_ERROR);

				api::callHooks(API_HOOK_AFTER,
					'pictures::addWatermark', $this, $file, $watermark, $watermarkx, $watermarky, $watermarktype, $timeout);

				return false;
			}

			$x = $watermarkx - $watermarkwidth/2;
			$y = $watermarky - $watermarkheight/2;

			if ($x >= $imgwidth-$watermarkwidth)
				$x = $imgwidth-$watermarkwidth;

			if ($y >= $imgheight-$watermarkheight)
				$y = $imgheight-$watermarkheight;

			if ($x < 0)
				$x = 0;

			if ($y < 0)
				$y = 0;

			imagecopy($img, $watermarkimg, $x, $y, 0, 0,
				$watermarkwidth, $watermarkheight);

		} else {
			if (defined('JCORE_PATH'))
				$ttffont = JCORE_PATH.'lib/fonts/arial.ttf';
			else
				$ttffont = SITE_PATH.'lib/fonts/arial.ttf';

			if (defined('PICTURE_WATERMARK_TEXT_FONT') && PICTURE_WATERMARK_TEXT_FONT) {
				if (strpos(PICTURE_WATERMARK_TEXT_FONT, '/') !== 0 &&
					strpos(PICTURE_WATERMARK_TEXT_FONT, '://') === false)
				{
					if (defined('JCORE_PATH'))
						$ttffont = JCORE_PATH.'lib/'.PICTURE_WATERMARK_TEXT_FONT;
					else
						$ttffont = SITE_PATH.'lib/'.PICTURE_WATERMARK_TEXT_FONT;

				} else {
					$ttffont = PICTURE_WATERMARK_TEXT_FONT;
				}
			}

			$black = imagecolorallocate($img, 0, 0, 0);
			$white = imagecolorallocate($img, 255, 255, 255);

			$ttffontsize = round($imgwidth*10/1024);

			if ($ttffontsize < 10)
				$ttffontsize = 10;

			$bbox = @imagettfbbox($ttffontsize, 0, $ttffont, $watermark);

			if ($bbox[4] < 0)
				$bbox[4] = $bbox[4]*-1;

			if ($bbox[5] < 0)
				$bbox[5] = $bbox[5]*-1;

			$watermarkwidth = $bbox[4];
			$watermarkheight = $bbox[5];

			$x = $watermarkx - $watermarkwidth/2;
			$y = $watermarky - $watermarkheight/2;

			if ($x >= $imgwidth-$watermarkwidth)
				$x = $imgwidth-$watermarkwidth-10;

			if ($y >= $imgheight-$watermarkheight)
				$y = $imgheight-10;

			if ($x < 10)
				$x = 10;

			if ($y < 10+$watermarkheight)
				$y = $watermarkheight+10;

			@imagettftext($img, $ttffontsize, 0, $x+1, $y+1, $black, $ttffont, $watermark);
			@imagettftext($img, $ttffontsize, 0, $x, $y, $white, $ttffont, $watermark);
		}

		$status = false;

	   	if ($imgtype == 1) $status = imagegif($img, $file);
   		if ($imgtype == 2) $status = imagejpeg($img, $file, 100);
	   	if ($imgtype == 3) $status = imagepng($img, $file);

   		imagedestroy($img);

		api::callHooks(API_HOOK_AFTER,
			'pictures::addWatermark', $this, $file, $watermark, $watermarkx, $watermarky, $watermarktype, $timeout, $status);

   		return $status;
	}

	function upload($file, $to, $thumbnail = true, $watermark = true, $sharpen = true) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::upload', $this, $file, $to, $thumbnail, $watermark, $sharpen);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::upload', $this, $file, $to, $thumbnail, $watermark, $sharpen, $handled);

			return $handled;
		}

		$picpath = $to.$this->subFolder.'/';
		$thumbpath = $to.$this->thumbnailsFolder.$this->subFolder.'/';

		if (!$filename = files::upload($file, $picpath, FILE_TYPE_IMAGE)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::upload', $this, $file, $to, $thumbnail, $watermark, $sharpen);

			return false;
		}

		if (defined('PICTURE_WATERMARK') && PICTURE_WATERMARK && $watermark) {
			$watermarkposition = explode(' ',
				str_replace('  ', '', $this->watermarkPosition));

			if (PICTURE_WATERMARK_LOGO)
				$this->addWatermark($picpath.$filename, PICTURE_WATERMARK_LOGO,
					$watermarkposition[0], $watermarkposition[1],
					PICTURE_WATERMARK_TYPE_IMAGE);
			else
				$this->addWatermark($picpath.$filename, PICTURE_WATERMARK_TEXT,
					$watermarkposition[0], $watermarkposition[1]);
		}

		if ($thumbnail) {
			if (JCORE_VERSION >= '0.5') {
				$this->createThumbnail($picpath.$filename,
					$this->thumbnailWidth, $this->thumbnailHeight,
					$thumbpath.$filename, $sharpen);

			} else {
				copy($picpath.$filename, $picpath.$this->largePicture($filename));
				$this->createThumbnail($picpath.$filename,
					$this->thumbnailWidth, $this->thumbnailHeight,
					$picpath.$filename, $sharpen);
			}
		}

		api::callHooks(API_HOOK_AFTER,
			'pictures::upload', $this, $file, $to, $thumbnail, $watermark, $sharpen, $filename);

		return $filename;
	}

	// ************************************************   Client Part
	function largePicture($location, $check = false) {
		$largefile = substr($location, 0, -4).
			"_large".substr($location, -4);

		if ($check && !is_file($this->rootPath.$largefile))
			return $location;

		return $largefile;
	}

	function download($id, $force = false) {
		if (!(int)$id) {
			tooltip::display(
				__("No picture selected to download!"),
				TOOLTIP_ERROR);
			return false;
		}

		$row = sql::fetch(sql::run(
			" SELECT * FROM `{" .$this->sqlTable . "}`" .
			" WHERE `ID` = '".(int)$id."'" .
			" LIMIT 1"));

		if (!$row) {
			tooltip::display(
				__("The selected picture cannot be found!"),
				TOOLTIP_ERROR);
			return false;
		}

		if (JCORE_VERSION >= '0.5')
			$file = $this->rootPath.$row['Location'];
		else
			$file = $this->rootPath.$this->largePicture($row['Location'], true);

		if (!is_file($file)) {
			tooltip::display(
				sprintf(__("Picture \"%s\" cannot be found!"),
					$row['Location']),
				TOOLTIP_ERROR);

			return false;
		}

		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::download', $this, $id, $force);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::download', $this, $id, $force, $handled);

			return $handled;
		}

		session_write_close();
		files::display($file, $force);

		if (JCORE_VERSION >= '0.5' && !$force && !security::isBot())
			$this->incViews($row);

		api::callHooks(API_HOOK_AFTER,
			'pictures::download', $this, $id, $force);

		return true;
	}

	function generateLink(&$row) {
		if ($this->customLink) {
			if (is_array($this->customLink))
				$link = $this->customLink[$row['ID']];
			else
				$link = $this->customLink;

		} elseif ($row['URL']) {
			$link = url::generateLink($row['URL']);

		} else {
			$link = url::uri().
				"&amp;request=".$this->uriRequest .
				"&amp;view=".$row['ID']."&amp;ajax=1";
		}

		return $link;
	}

	function incViews(&$row) {
		if (security::isBot())
			return;

		sql::run(
			" UPDATE `{" .$this->sqlTable."}` SET " .
			" `TimeStamp` = `TimeStamp`," .
			" `Views` = `Views`+1" .
			" WHERE `ID` = '".$row['ID']."'");
	}

	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::ajaxRequest', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::ajaxRequest', $this, $handled);

			return $handled;
		}

		$view = null;
		$download = null;

		if (isset($_GET['view']))
			$view = (int)$_GET['view'];

		if (isset($_GET['download']))
			$download = (int)$_GET['download'];

		if ($view) {
			$result = $this->download($view);

		} else if ($download) {
			$result = $this->download($download, true);

		} else {
			$result = true;
			$this->ajaxPaging = true;
			$this->display();
		}

		api::callHooks(API_HOOK_AFTER,
			'pictures::ajaxRequest', $this, $result);

		return $result;
	}

	function displayPicture(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayPicture', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayPicture', $this, $row, $handled);

			return $handled;
		}

		if (!isset($row['_ThumbnailLocation']) || !$row['_ThumbnailLocation'])
			$row['_ThumbnailLocation'] = $this->rootURL.
				(JCORE_VERSION >= '0.5' && $row['Thumbnail']?
					$this->thumbnailsFolder:
					null) .
				$row['Location'];

		echo
			"<img src='".
				(strpos($row['Location'], '://') !== false?
					$row['Location']:
					$row['_ThumbnailLocation']).
					"' " .
				"alt='".htmlchars($row['Title'], ENT_QUOTES)."' " .
				(JCORE_VERSION < '0.6'?
					"border='0' ":
					null) .
				"/>";

		api::callHooks(API_HOOK_AFTER,
			'pictures::displayPicture', $this, $row);
	}

	function displayTitle(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayTitle', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayTitle', $this, $row, $handled);

			return $handled;
		}

		echo $row['Title'];

		api::callHooks(API_HOOK_AFTER,
			'pictures::displayTitle', $this, $row);
	}

	function displayDetails(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayDetails', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayDetails', $this, $row, $handled);

			return $handled;
		}

		echo
			"<span class='details-date'>" .
				calendar::date($row['TimeStamp']) .
			"</span>";

		if (JCORE_VERSION >= '0.5' && $row['Views'])
			echo
				"<span class='details-separator separator-1'>" .
					", " .
				"</span>" .
				"<span class='picture-views-number'>" .
					sprintf(__("%s views"), $row['Views']) .
				"</span>";

		api::callHooks(API_HOOK_AFTER,
			'pictures::displayDetails', $this, $row);
	}

	function displayFormated(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayFormated', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayFormated', $this, $row, $handled);

			return $handled;
		}

		if (!isset($row['_Link']) || !$row['_Link'])
			$row['_Link'] = $this->generateLink($row);

		echo
			"<div " .
				(JCORE_VERSION < '0.6'?
					"id='picture".$row['ID']."' ":
					null) .
				"class='picture " .
				(JCORE_VERSION >= '0.6'?
					"picture".$row['ID']." picture-num".$row['_PictureNumber']:
					"picture".$row['_PictureNumber']) .
				"'>";

		$parts = preg_split('/%([a-z0-9-_]+?)%/', $this->format, null, PREG_SPLIT_DELIM_CAPTURE);

		foreach($parts as $part) {
			switch($part) {
				case 'picture':
					echo
						"<a href='".$row['_Link']."' " .
							"title='".htmlchars($row['Title'], ENT_QUOTES)."' " .
							(strpos($row['URL'], '://') !== false && !$this->customLink?
								"target='_blank' ":
								null) .
							(!$row['URL'] && !$this->customLink?
								"rel='lightbox[".strtolower(get_class($this))."".$this->selectedOwnerID."]'":
								null) .
							">";

					$this->displayPicture($row);

					echo
						"</a>";
					break;

				case 'title':
					echo
						"<div class='picture-title'>";

					$this->displayTitle($row);

					echo
						"</div>";
					break;

				case 'details':
					echo
						"<div class='picture-details comment'>";

					$this->displayDetails($row);

					echo
						"</div>";
					break;

				case 'link':
					echo $row['_Link'];
					break;

				default:
					echo $part;
					break;
			}
		}

		echo
			"</div>";

		api::callHooks(API_HOOK_AFTER,
			'pictures::displayFormated', $this, $row);
	}

	function displayOne(&$row) {
		if (!$row['Location'])
			return;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::displayOne', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::displayOne', $this, $row, $handled);

			return $handled;
		}

		if (!isset($row['_Link']) || !$row['_Link'])
			$row['_Link'] = $this->generateLink($row);

		echo
			"<div " .
				(JCORE_VERSION < '0.6'?
					"id='picture".$row['ID']."' ":
					null) .
				"class='picture " .
				(JCORE_VERSION >= '0.6'?
					"picture".$row['ID']." picture-num".$row['_PictureNumber']:
					"picture".$row['_PictureNumber']) .
				"'>" .
				"<a href='".$row['_Link']."' " .
					"title='".htmlchars($row['Title'], ENT_QUOTES)."' " .
					(strpos($row['URL'], '://') !== false && !$this->customLink?
						"target='_blank' ":
						null) .
					(!$row['URL'] && !$this->customLink?
						"rel='lightbox[".strtolower(get_class($this))."".$this->selectedOwnerID."]'":
						null) .
					">";

		$this->displayPicture($row);

		echo
				"</a>" .
				"<div class='picture-title'>";

		$this->displayTitle($row);

		echo
				"</div>" .
				"<div class='picture-details comment'>";

		$this->displayDetails($row);

		echo
				"</div>" .
			"</div>";

		api::callHooks(API_HOOK_AFTER,
			'pictures::displayOne', $this, $row);
	}

	function display() {
		if (!$this->sqlTable) {
			tooltip::display(
				__("Storage table not defined."),
				TOOLTIP_NOTIFICATION);

			return false;
		}

		if (!$this->latests) {
			$paging = new paging($this->limit);

			if ($this->ajaxPaging) {
				$paging->ajax = true;
				$paging->otherArgs = "&amp;request=".$this->uriRequest .
					($this->sqlRow?
						"&amp;".strtolower($this->sqlRow)."=".$this->selectedOwnerID:
						null);
			}

			$paging->track(strtolower(get_class($this)).'limit');

			if ($this->ignorePaging)
				$paging->reset();
		}

		$rows = sql::run(
			$this->SQL() .
			($this->ignorePaging || $this->latests?
				($this->limit?
					" LIMIT ".$this->limit:
					null):
				" LIMIT ".$paging->limit));

		if (!$this->latests)
			$paging->setTotalItems(sql::count());

		if (!sql::rows($rows))
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'pictures::display', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'pictures::display', $this, $handled);

			return $handled;
		}

		if (!$this->ajaxRequest)
			echo
				"<div class='".
					strtolower(preg_replace('/([A-Z])/', '-\\1', get_class($this))).
					" pictures'>";

		$i = 1;
		while ($row = sql::fetch($rows)) {
			$row['_PictureNumber'] = $i;
			$row['_Link'] = $this->generateLink($row);

			if ($this->format)
				$this->displayFormated($row);
			else
				$this->displayOne($row);

			if ($this->columns == $i) {
				echo "<div class='clear-both'></div>";
				$i = 0;
			}

			$i++;
		}

		echo
			"<div class='clear-both'></div>";

		if ($this->showPaging && !$this->randomize && !$this->latests)
			$paging->display();

		if (!$this->ajaxRequest)
			echo
				"</div>"; //pictures

		api::callHooks(API_HOOK_AFTER,
			'pictures::display', $this);

		if ($this->latests)
			return true;

		return $paging->items;
	}
}

?>