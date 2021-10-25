<?php

/***************************************************************************
 *            files.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

define('FILE_TYPE_UPLOAD', 1);
define('FILE_TYPE_IMAGE', 2);
define('FILE_TYPE_VIDEO', 3);
define('FILE_TYPE_BANNER', 4);
define('FILE_TYPE_AUDIO', 5);

class _files {
	static $debug = false;
	static $allowedFileTypes = array(
		FILE_TYPE_UPLOAD => '\.(7z|aiff|asf|avi|bmp|csv|doc|fla|flv|gif|gz|gzip|jpeg|jpg|mid|mov|mp3|mp4|mpc|mpeg|mpg|ods|odt|pdf|png|ppt|eps|pxd|qt|ram|rar|rm|rmi|rmvb|rtf|sdc|sitd|swf|sxc|sxw|tar|tgz|tif|tiff|txt|vsd|wav|wma|wmv|xls|xml|zip|patch|sql|mo|po|docx|docm|dotx|dotm|xlsx|xlsm|xltx|xltm|xlsb|xlam|xll|pptx|pptm|potx|potm|ppam|ppsx|ppsm|sldx|sldm|thmx)$',
		FILE_TYPE_IMAGE => '\.(jpg|gif|jpeg|png|bmp)$',
		FILE_TYPE_VIDEO => '\.(avi|wmv|swf|flv|mov|mp4|webm|ogv|mpeg|mpg|qt|rm)$',
		FILE_TYPE_BANNER => '\.(jpg|gif|jpeg|png|bmp|swf)$',
		FILE_TYPE_AUDIO => '\.(mid|mp3|rmi|wav|wma|weba)$');

	static $mimeTypes = array(
		"323" => "text/h323",
		"acx" => "application/internet-property-stream",
		"ai" => "application/postscript",
		"aif" => "audio/x-aiff",
		"aifc" => "audio/x-aiff",
		"aiff" => "audio/x-aiff",
		"asf" => "video/x-ms-asf",
		"asr" => "video/x-ms-asf",
		"asx" => "video/x-ms-asf",
		"au" => "audio/basic",
		"avi" => "video/x-msvideo",
		"axs" => "application/olescript",
		"bas" => "text/plain",
		"bcpio" => "application/x-bcpio",
		"bin" => "application/octet-stream",
		"bmp" => "image/bmp",
		"c" => "text/plain",
		"cat" => "application/vnd.ms-pkiseccat",
		"cdf" => "application/x-cdf",
		"cer" => "application/x-x509-ca-cert",
		"class" => "application/octet-stream",
		"clp" => "application/x-msclip",
		"cmx" => "image/x-cmx",
		"cod" => "image/cis-cod",
		"cpio" => "application/x-cpio",
		"crd" => "application/x-mscardfile",
		"crl" => "application/pkix-crl",
		"crt" => "application/x-x509-ca-cert",
		"csh" => "application/x-csh",
		"css" => "text/css",
		"dcr" => "application/x-director",
		"der" => "application/x-x509-ca-cert",
		"dir" => "application/x-director",
		"dll" => "application/x-msdownload",
		"dms" => "application/octet-stream",
		"doc" => "application/msword",
		"dot" => "application/msword",
		"dvi" => "application/x-dvi",
		"dxr" => "application/x-director",
		"eps" => "application/postscript",
		"etx" => "text/x-setext",
		"evy" => "application/envoy",
		"exe" => "application/octet-stream",
		"fif" => "application/fractals",
		"flr" => "x-world/x-vrml",
		"gif" => "image/gif",
		"gtar" => "application/x-gtar",
		"gz" => "application/x-gzip",
		"h" => "text/plain",
		"hdf" => "application/x-hdf",
		"hlp" => "application/winhlp",
		"hqx" => "application/mac-binhex40",
		"hta" => "application/hta",
		"htc" => "text/x-component",
		"htm" => "text/html",
		"html" => "text/html",
		"htt" => "text/webviewhtml",
		"ico" => "image/x-icon",
		"ief" => "image/ief",
		"iii" => "application/x-iphone",
		"ins" => "application/x-internet-signup",
		"isp" => "application/x-internet-signup",
		"jfif" => "image/pipeg",
		"jpe" => "image/jpeg",
		"jpeg" => "image/jpeg",
		"jpg" => "image/jpeg",
		"js" => "application/x-javascript",
		"latex" => "application/x-latex",
		"lha" => "application/octet-stream",
		"lsf" => "video/x-la-asf",
		"lsx" => "video/x-la-asf",
		"lzh" => "application/octet-stream",
		"m13" => "application/x-msmediaview",
		"m14" => "application/x-msmediaview",
		"m3u" => "audio/x-mpegurl",
		"man" => "application/x-troff-man",
		"mdb" => "application/x-msaccess",
		"me" => "application/x-troff-me",
		"mht" => "message/rfc822",
		"mhtml" => "message/rfc822",
		"mid" => "audio/mid",
		"mny" => "application/x-msmoney",
		"mov" => "video/quicktime",
		"movie" => "video/x-sgi-movie",
		"mp2" => "video/mpeg",
		"mp3" => "audio/mpeg",
		"mp4" => "video/mp4",
		"mpa" => "video/mpeg",
		"mpe" => "video/mpeg",
		"mpeg" => "video/mpeg",
		"mpg" => "video/mpeg",
		"mpp" => "application/vnd.ms-project",
		"mpv2" => "video/mpeg",
		"ms" => "application/x-troff-ms",
		"mvb" => "application/x-msmediaview",
		"nws" => "message/rfc822",
		"oda" => "application/oda",
		"ogg" => "video/ogg",
		"p10" => "application/pkcs10",
		"p12" => "application/x-pkcs12",
		"p7b" => "application/x-pkcs7-certificates",
		"p7c" => "application/x-pkcs7-mime",
		"p7m" => "application/x-pkcs7-mime",
		"p7r" => "application/x-pkcs7-certreqresp",
		"p7s" => "application/x-pkcs7-signature",
		"pbm" => "image/x-portable-bitmap",
		"pdf" => "application/pdf",
		"pfx" => "application/x-pkcs12",
		"pgm" => "image/x-portable-graymap",
		"pko" => "application/ynd.ms-pkipko",
		"pma" => "application/x-perfmon",
		"pmc" => "application/x-perfmon",
		"pml" => "application/x-perfmon",
		"pmr" => "application/x-perfmon",
		"pmw" => "application/x-perfmon",
		"pnm" => "image/x-portable-anymap",
		"pot" => "application/vnd.ms-powerpoint",
		"ppm" => "image/x-portable-pixmap",
		"pps" => "application/vnd.ms-powerpoint",
		"ppt" => "application/vnd.ms-powerpoint",
		"prf" => "application/pics-rules",
		"ps" => "application/postscript",
		"pub" => "application/x-mspublisher",
		"qt" => "video/quicktime",
		"ra" => "audio/x-pn-realaudio",
		"ram" => "audio/x-pn-realaudio",
		"ras" => "image/x-cmu-raster",
		"rgb" => "image/x-rgb",
		"rmi" => "audio/mid",
		"roff" => "application/x-troff",
		"rtf" => "application/rtf",
		"rtx" => "text/richtext",
		"scd" => "application/x-msschedule",
		"sct" => "text/scriptlet",
		"setpay" => "application/set-payment-initiation",
		"setreg" => "application/set-registration-initiation",
		"sh" => "application/x-sh",
		"shar" => "application/x-shar",
		"sit" => "application/x-stuffit",
		"snd" => "audio/basic",
		"spc" => "application/x-pkcs7-certificates",
		"spl" => "application/futuresplash",
		"src" => "application/x-wais-source",
		"sst" => "application/vnd.ms-pkicertstore",
		"stl" => "application/vnd.ms-pkistl",
		"stm" => "text/html",
		"svg" => "image/svg+xml",
		"sv4cpio" => "application/x-sv4cpio",
		"sv4crc" => "application/x-sv4crc",
		"t" => "application/x-troff",
		"tar" => "application/x-tar",
		"tcl" => "application/x-tcl",
		"tex" => "application/x-tex",
		"texi" => "application/x-texinfo",
		"texinfo" => "application/x-texinfo",
		"tgz" => "application/x-compressed",
		"tif" => "image/tiff",
		"tiff" => "image/tiff",
		"tr" => "application/x-troff",
		"trm" => "application/x-msterminal",
		"tsv" => "text/tab-separated-values",
		"txt" => "text/plain",
		"uls" => "text/iuls",
		"ustar" => "application/x-ustar",
		"vcf" => "text/x-vcard",
		"vrml" => "x-world/x-vrml",
		"wav" => "audio/x-wav",
		"webm" => "video/webm",
		"wcm" => "application/vnd.ms-works",
		"wdb" => "application/vnd.ms-works",
		"wks" => "application/vnd.ms-works",
		"wma" => "audio/x-ms-wma",
		"wmf" => "application/x-msmetafile",
		"wmv" => "video/x-ms-wmv",
		"wps" => "application/vnd.ms-works",
		"wri" => "application/x-mswrite",
		"wrl" => "x-world/x-vrml",
		"wrz" => "x-world/x-vrml",
		"xaf" => "x-world/x-vrml",
		"xbm" => "image/x-xbitmap",
		"xla" => "application/vnd.ms-excel",
		"xlc" => "application/vnd.ms-excel",
		"xlm" => "application/vnd.ms-excel",
		"xls" => "application/vnd.ms-excel",
		"xlt" => "application/vnd.ms-excel",
		"xlw" => "application/vnd.ms-excel",
		"xof" => "x-world/x-vrml",
		"xpm" => "image/x-xpixmap",
		"xwd" => "image/x-xwindowdump",
		"z" => "application/x-compress",
		"zip" => "application/zip");

	static function getUploadMaxFilesize() {
		return settings::iniGet('upload_max_filesize', true);
	}

 	static function upload($file, $to, $filetype = FILE_TYPE_UPLOAD) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'files::upload', $_ENV, $file, $to, $filetype);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'files::upload', $_ENV, $file, $to, $filetype, $handled);

			return $handled;
		}

		$topath = preg_replace('/(.*(\/|\\\)).*/', '\1', $to);
		$tofilename = preg_replace('/.*(\/|\\\)/', '', $to);

 		if (strpos($file, '://') !== false) {
			$filename = preg_replace('/.*(\/|\\\)/', '', $file);

			if (!$tofilename)
				$tofilename = preg_replace("/[^A-Za-z0-9._-]/", "", $filename);

 		} elseif (strpos($file, '/') !== false || strpos($file, '\\') !== false) {
			$filename = preg_replace('/.*(\/|\\\)/', '', $file);

			if (!$tofilename) {
				foreach($_FILES as $f) {
					if (is_array($f['tmp_name'])) {
						foreach($f['tmp_name'] as $key => $fi) {
							if ($fi == $file) {
								$tofilename = preg_replace("/[^A-Za-z0-9._-]/", "",
									$f['name'][$key]);
								break;
							}
						}

					} elseif ($f['tmp_name'] == $file) {
						$tofilename = preg_replace("/[^A-Za-z0-9._-]/", "",
							$f['name']);
						break;
					}
				}
			}

			if (!$tofilename)
				$tofilename = preg_replace("/[^A-Za-z0-9._-]/", "", $filename);

 		} else {
	 		$fileid = preg_replace('/\[.*?\]/', '', $file);
	 		$filearrayid = null;

 			preg_match('/\[(.*?)\]/', $file, $matches);

 			if (isset($matches[1]))
	 			$filearrayid = $matches[1];

 			if (!isset($_FILES[$fileid])) {
				tooltip::display(
					sprintf(__("File couldn't be saved! This usually means that your " .
						"file is larger than the allowed upload limit (%s) or something " .
						"went wrong while saving the file to it's permanent place. Please " .
						"try again or contact webmaster."),
							files::humanSize(files::getUploadMaxFilesize())),
					TOOLTIP_ERROR);

				api::callHooks(API_HOOK_AFTER,
					'files::upload', $_ENV, $file, $to, $filetype);

 				return false;
 			}

	 		if (isset($filearrayid)) {
	 			$file = $_FILES[$fileid]['tmp_name'][$filearrayid];
				$filename = $_FILES[$fileid]['name'][$filearrayid];
	 		} else {
	 			$file = $_FILES[$fileid]['tmp_name'];
				$filename = $_FILES[$fileid]['name'];
	 		}

			if (!$tofilename)
				$tofilename = preg_replace("/[^A-Za-z0-9._-]/", "", $filename);
 		}

		//if uploader is not admin we won't allow files to be overwritten
		if ((!$GLOBALS['USER']->loginok || !$GLOBALS['USER']->data['Admin']) &&
			@file_exists($topath.$tofilename))
		{
			tooltip::display(
				sprintf(__("The file you are trying to upload \"%s\" already exists " .
					"on our site. Please rename and reselect the file you " .
					"would like to upload."), $tofilename),
				TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'files::upload', $_ENV, $file, $to, $filetype);

			return false;
		}

		if (!preg_match("/".files::$allowedFileTypes[$filetype]."/i", $tofilename)) {
			tooltip::display(
				sprintf(__("Unsupported file format! Supported formats are: %s."),
					str_replace('|', ', ', files::$allowedFileTypes[$filetype])),
				TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'files::upload', $_ENV, $file, $to, $filetype);

			return false;
		}

		if ((!is_dir($topath) && !@mkdir($topath, 0777, true)) || !is_writable($topath)) {
			tooltip::display(
				__("File couldn't be saved!")." " .
				sprintf(__("Please make sure \"%s\" is writable by me or contact webmaster."),
					$topath),
				TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'files::upload', $_ENV, $file, $to, $filetype);

			return false;
		}

 		if (strpos($file, '://') !== false) {
			$uploaded = files::save($topath.$tofilename, files::get($file));
 		} else {
			$uploaded = @move_uploaded_file($file, $topath.$tofilename);
 		}

		if (!$uploaded)
			tooltip::display(
				sprintf(__("File couldn't be saved! This usually means that your " .
					"file is larger than the allowed upload limit (%s) or something " .
					"went wrong while saving the file to it's permanent place. Please " .
					"try again or contact webmaster."),
						files::humanSize(files::getUploadMaxFilesize())),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'files::upload', $_ENV, $file, $to, $filetype, $uploaded, $tofilename);

		if ($uploaded)
			return $tofilename;

		return $uploaded;
 	}

 	static function display($file, $forcedownload = false, $resumable = true) {
 		if (!@is_file($file))
 			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'files::display', $_ENV, $file, $forcedownload, $resumable);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'files::display', $_ENV, $file, $forcedownload, $resumable, $handled);

			return $handled;
		}

		$size = @filesize($file);
		$fileinfo = @pathinfo($file);
		$filemtime = @filemtime($file);

		$filename = (strpos((string)$_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) ?
			preg_replace('/\./', '%2e', $fileinfo['basename'], substr_count($fileinfo['basename'], '.') - 1) :
			$fileinfo['basename'];

		$ctype='application/force-download';

		if (!$forcedownload && isset(files::$mimeTypes[strtolower($fileinfo['extension'])]))
			$ctype = files::$mimeTypes[strtolower($fileinfo['extension'])];

		if($resumable && isset($_SERVER['HTTP_RANGE'])) {
			list($size_unit, $range_orig) = explode('=', (string)$_SERVER['HTTP_RANGE'], 2);

			if ($size_unit == 'bytes')
				list($range, $extra_ranges) = explode(',', $range_orig, 2);
			else
				$range = '';
		} else {
			$range = '';
		}

		$seek_end = null;
		$seek_start = null;

		$exprange = explode('-', $range, 2);

		if (isset($exprange[0]))
			$seek_start = $exprange[0];

		if (isset($exprange[1]))
			$seek_end = $exprange[1];

		$seek_end = (empty($seek_end)) ? ($size - 1) : min(abs(intval($seek_end)),($size - 1));
		$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);

		if ($forcedownload && $resumable) {
			if ($seek_start > 0 || $seek_end < ($size - 1)) {
				header('HTTP/1.1 206 Partial Content');
			}

			header('Accept-Ranges: bytes');
			header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$size);
		}

		header('Cache-Control: public');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $filemtime).' GMT');

		header('Content-Type: ' . $ctype);
		header('Content-Length: '.($seek_end - $seek_start + 1));

		if ($forcedownload)
			header('Content-Disposition: attachment; filename="' . $filename . '"');
		else
			header('Content-Disposition: filename="' . $filename . '"');

		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
			(strtotime((string)$_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $filemtime))
		{
			header('HTTP/1.0 304 Not Modified');

			api::callHooks(API_HOOK_AFTER,
				'files::display', $_ENV, $file, $forcedownload, $resumable);

			return false;
		}

		$fp = fopen($file, 'rb');
		fseek($fp, $seek_start);

		while(!feof($fp)) {
			if (!ini_get('safe_mode'))
	        	@set_time_limit(0);

    	    print(fread($fp, 1024*8));
        	flush();
        	ob_flush();
		}

		fclose($fp);

		api::callHooks(API_HOOK_AFTER,
			'files::display', $_ENV, $file, $forcedownload, $resumable);

		return true;
 	}

 	static function humanSize($size) {
		$sizetext = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');

		$i = 0;
		for ($i = 0; $size >= 1024; $i++)
			$size /= 1024;

		return round($size).' '.$sizetext[$i];
 	}

 	static function mimeType($file) {
        $type = @exec("file -bi ".escapeshellarg($file));

        if(!$type)
	        $type = files::$mimeTypes[preg_replace('/.*\./', '', $file)];

        if (!$type)
        	$type = __("unknown/file");

		return $type;
 	}

 	static function humanMimeType($file) {
 		$type = @exec("file -b ".escapeshellarg($file));

 		if (!$type)
	        $type = files::$mimeTypes[preg_replace('/.*\./', '', $file)];

        if (!$type)
        	$type = __("Unknown File Type");

        return $type;
 	}

 	static function ext2MimeClass($file) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'files::ext2MimeClass', $_ENV, $file);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'files::ext2MimeClass', $_ENV, $file, $handled);

			return $handled;
		}

		if (preg_match('/\.(7z|rar|gz|gzip|tar|tgz|zip)$/i', $file))
			$result = "mime-type-package";

		else if (preg_match('/\.(gif|bmp|jpeg|jpg|png|tif|tiff)$/i', $file))
			$result = "mime-type-photo";

		else if (preg_match('/\.(asf|avi|mov|fla|flv|mid|mp3|mp4|mpc|mpeg|mpg|rm|qt|ram|swf|wav|wma|wmv)$/i', $file))
			$result = "mime-type-multimedia";

		else if (preg_match('/\.(csv|doc|pdf|ppt|rtf|xls)$/i', $file))
			$result = "mime-type-office";

		else if (preg_match('/\.(txt|xml)$/i', $file))
			$result = "mime-type-text";

		else if (preg_match('/\.(patch)$/i', $file))
			$result = "mime-type-patch";

		else if (preg_match('/\.(sql)$/i', $file))
			$result = "mime-type-db";

		else
			$result = "mime-type-unknown";

		api::callHooks(API_HOOK_AFTER,
			'files::ext2MimeClass', $_ENV, $file, $result);

		return $result;
 	}

 	static function exists($file) {
 		return @file_exists($file);
 	}

 	static function isWritable($file) {
 		if (!$file)
 			return false;

 		if (@is_file($file))
 			return @is_writable($file);

 		if (@is_dir($file))
 			return false;

 		return dirs::isWritable(substr($file, 0, strrpos($file, '/')));
 	}

 	static function delete($file, $debug = false) {
 		if ($debug || files::$debug)
 			echo sprintf(__("Deleting file %s"), $file)." ... ";

 		if (@is_dir($file)) {
			$d = dir($file);

			while (false !== ($entry = $d->read()))
				if ($entry != '.' && $entry != '..')
					files::delete($file.'/'.$entry);

			$d->close();
			$result = @rmdir($file);

 		} elseif (@file_exists($file)) {
	 		$result = @unlink($file);
 		} else {
 			$result = true;
 		}

 		if (!$result) {
 			if ($debug || files::$debug)
 				echo "<b class='red'>" .
 					strtoupper(__("Error")) .
					"</b>" .
					" (".__("not writable").")<br />";
 		} else {
	 		if ($debug || files::$debug)
				echo "<b>".strtoupper(__("Ok"))."</b><br />";
 		}

		return $result;
 	}

 	static function rename($file, $to) {
 		$dir = preg_replace('/((.*(\/|\\\))|^).*$/', '\2', $to);

		if ($dir && !is_dir($dir) && !@mkdir($dir, 0777, true))
			return false;

 		return @rename($file, $to);
 	}

 	static function copy($file, $to) {
 		$dir = preg_replace('/((.*(\/|\\\))|^).*$/', '\2', $to);

		if ($dir && !is_dir($dir) && !@mkdir($dir, 0777, true))
			return false;

		return @copy($file, $to);
 	}

 	static function get($file, $httpheader = null, $timeout = null, $postdata = null) {
		if (strpos($file, '://') !== false) {
			if (extension_loaded('curl')) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $file);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

				curl_setopt($ch, CURLOPT_USERAGENT,
					'Mozilla/5.0 (X11; Linux x86_64; rv:69.0) Gecko/20100101 Firefox/69.0');

				if (isset($timeout))
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

				if ($httpheader && is_array($httpheader))
					curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);

				if ($postdata)
					curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);

				$data = curl_exec($ch);

				if (empty($data)) {
					$header = curl_getinfo($ch);

					if (!empty($header['redirect_url']) && !empty($header['http_code']) &&
						strpos($header['http_code'], '3') === 0)
					{
						curl_close($ch);
						return fetch_url($header['redirect_url']);
					}
				}

				curl_close($ch);

				return $data;
			}

			if (@ini_get('allow_url_fopen')) {
				if ($postdata)
					$postdata = http_build_query($postdata);

				$options = array(
					'http' => array(
						'method' => ($postdata?'POST':'GET'),
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false),
						'header' =>
							($httpheader && is_array($httpheader)?
								implode("\r\n", $httpheader)."\r\n":
								null) .
							($postdata?
								"Content-Type: application/x-www-form-urlencoded\r\n" .
								"Content-Length: ".strlen($postdata)."\r\n":
								null) .
							"User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:69.0) Gecko/20100101 Firefox/69.0\r\n" .
							"Connection: Close"));

				if ($postdata)
					$options['http']['content'] = $postdata;

				if (isset($timeout))
					$options['http']['timeout'] = $timeout;

				$context = stream_context_create($options);
				return @file_get_contents($file, false, $context);
			}

			$url = parse_url($file);
			$errno = null;
			$errstr = null;

			if (!isset($url['port']))
				$url['port'] = (isset($url['scheme']) && $url['scheme'] == 'https'?443:80);

			$fp = fsockopen($url['host'], $url['port'], $errno, $errstr, (isset($timeout)?$timeout:@ini_get("default_socket_timeout")));

			if ($fp) {
				if ($postdata)
					$postdata = http_build_query($postdata);

				fwrite($fp,
					($postdata?'POST ':'GET ') .
						(isset($url['path']) && $url['path']?
							$url['path']:
							"/") .
						(isset($url['query']) && $url['query']?
							"?".$url['query']:
							null) .
						" HTTP/1.1\r\n" .
					"Host: ".$url['host']."\r\n" .
					($httpheader && is_array($httpheader)?
						implode("\r\n", $httpheader)."\r\n":
						null) .
					($postdata?
						"Content-Type: application/x-www-form-urlencoded\r\n" .
						"Content-Length: ".strlen($postdata)."\r\n":
						null) .
					"User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:69.0) Gecko/20100101 Firefox/69.0\r\n" .
					"Connection: Close\r\n\r\n");

				if ($postdata)
					fwrite($fp, $postdata);

				while($header = @fgets($fp)) {
					if($header == "\r\n")
						break;
				}

				$data = null;
				while (!feof($fp))
					$data .= @fread($fp, 8192);

				fclose($fp);

				return $data;
			}
		}

		return @file_get_contents($file);
 	}

 	static function create($file, $data) {
 		$dir = preg_replace('/((.*(\/|\\\))|^).*$/', '\2', $file);

		if ($dir && !is_dir($dir) && !@mkdir($dir, 0777, true))
			return false;

 		return (@file_put_contents($file, $data) !== false);
 	}

 	static function save($file, $data = null, $debug = false) {
 		if ($debug || files::$debug)
 			echo sprintf(__("Writing file %s"), $file)." ... ";

 		$result = @files::create($file, $data);

 		if ($debug || files::$debug) {
	 		if (!$result)
 				echo "<b class='red'>" .
 					strtoupper(__("Error")) .
					"</b>" .
					" (".__("not writable").")<br />";

 			else
				echo "<b>" .
					strtoupper(__("Ok")).
					"</b>" .
					" (".files::humanSize(strlen($data)).")<br />";
 		}

 		return $result;
 	}
}

?>