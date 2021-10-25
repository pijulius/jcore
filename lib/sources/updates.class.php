<?php

/***************************************************************************
 *            updates.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

include_once('lib/tar.class.php');
include_once('lib/ftpclient.class.php');

class _updates {
	var $testing = true;
	var $selectedUpdate = '';
	var $rootPath;
	var $adminPath = 'admin/site/updates';

	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::updates', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::updates', $this, $handled);

			return $handled;
		}

		$this->rootPath = SITE_PATH.'sitefiles/var/updates/';

		if (isset($_GET['update']))
			$this->selectedUpdate = strip_tags((string)$_GET['update']);

		api::callHooks(API_HOOK_AFTER,
			'updates::updates', $this);
	}

	function install(&$update) {
		if (!$update)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::install', $this, $update);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::install', $this, $update, $handled);

			return $handled;
		}

		if ($update['SQL'] || (defined('JCORE_PATH') && JCORE_PATH && $update['Client']) ||
			((!defined('JCORE_PATH') || !JCORE_PATH) && $update['Server']))
		{
			tooltip::display(
				"<span id='jcoreupdateprocess'></span>",
				TOOLTIP_NOTIFICATION);
		}

		$packagefile = '';
		$sqlfile = '';

		if (defined('JCORE_PATH') && JCORE_PATH) {
			if ($update['Client']) {
				$packagefile = $this->download($update['Client'], __("Update"));

				if (!$packagefile) {
					tooltip::display(
						__("Download proccess couldn't be completed!")." " .
						__("Please make sure you have an active internet connection " .
							"and it is accessible by me or contact webmaster."),
						TOOLTIP_ERROR);

					api::callHooks(API_HOOK_AFTER,
						'updates::install', $this, $update);

					return false;
				}
			}

		} elseif ($update['Server']) {
			$packagefile = $this->download($update['Server'], __("Update"));

			if (!$packagefile) {
				tooltip::display(
					__("Download proccess couldn't be completed!")." " .
					__("Please make sure you have an active internet connection " .
						"and it is accessible by me or contact webmaster."),
					TOOLTIP_ERROR);

				api::callHooks(API_HOOK_AFTER,
					'updates::install', $this, $update);

				return false;
			}
		}

		if ($update['SQL']) {
			$sqlfile = $this->download($update['SQL'], __("SQL"));

			if (!$sqlfile) {
				tooltip::display(
					__("Download proccess couldn't be completed!")." " .
					__("Please make sure you have an active internet connection " .
						"and it is accessible by me or contact webmaster."),
					TOOLTIP_ERROR);

				api::callHooks(API_HOOK_AFTER,
					'updates::install', $this, $update);

				return false;
			}
		}

		echo
			"<script type='text/javascript'>" .
				"$('#jcoreupdateprocess').append('" .
						__("Running update")." ... " .
						"<span class=\"update-running-process\" style=\"font-weight: bold;\">" .
						"</span> " .
					"');" .
			"</script>";

		url::flushDisplay();

		if (!$this->testing)
			settings::set('Maintenance_Website_Suspended', '1');

		ob_start();

		$obcontent = null;
		$successfiles = $this->installFiles($packagefile);
		$obcontent = ob_get_contents();

		ob_end_clean();

		$this->displayInstallResults(
			__("Installing files"),
			$obcontent,
			$successfiles);

		if (!$successfiles)
			$this->testing = true;

		ob_start();

		$obcontent = null;
		$successsql = $this->installSQL($sqlfile);
		$obcontent = ob_get_contents();

		ob_end_clean();

		$this->displayInstallResults(
			__("Running SQL Queries"),
			$obcontent,
			$successsql);

		if ($successfiles && $successsql)
			echo
				"<script type='text/javascript'>" .
					"$('#jcoreupdateprocess .update-running-process').html('" .
							strtoupper(__("Ok"))."');" .
				"</script>";
		else
			echo
				"<script type='text/javascript'>" .
					"$('#jcoreupdateprocess .update-running-process').html('<b class=\"red\">" .
							strtoupper(__("Error")) .
						"</b>');" .
				"</script>";

		if (!$successfiles || ((isset($_POST['ftpuser']) && $_POST['ftpuser']) && $this->testing))
			$this->displayInstallFTPForm();

		if (!$successfiles || !$successsql) {
			api::callHooks(API_HOOK_AFTER,
				'updates::install', $this, $update);

			return false;
		}

		if (!$this->testing)
			settings::set('Maintenance_Website_Suspended', '0');

		$result = $successfiles;
		if (!$packagefile)
			$result = false;

		api::callHooks(API_HOOK_AFTER,
			'updates::install', $this, $update, $result);

		return $result;
	}

	function installSQL($sqlfile) {
		if (!$sqlfile) {
			echo "<p>".__("No SQL queries to run.")."</p>";
			return true;
		}

		if (!@file_exists($this->rootPath.$sqlfile)) {
			tooltip::display(
				sprintf(__("File \"%s\" couldn't be found!"),
					$sqlfile),
				TOOLTIP_ERROR);
			return false;
		}

		echo
			sprintf(__("Opening %s"), $sqlfile)." ... ";

		if (!$sqlqueries = files::get($this->rootPath.$sqlfile)) {
			echo
				"<b class=\"red\">" .
					strtoupper(__("Error")) .
				"</b>" .
				" (".__("Empty or invalid update!").")<br />";
			return false;
		}

		echo
			"<b>".strtoupper(__("Ok"))."</b><br />";

		echo
			"<h3>".__("Running SQL queries")."</h3>";

		sql::$quiet = true;
		$success = true;
		$customdelimiter = false;
		$querybuffer = null;
		$queryrest = null;
		$queries = preg_split('/;(\r\n|\n)/', $sqlqueries);

		foreach($queries as $query) {
			if ($queryrest) {
				$query = $queryrest.";\r\n".$query;
				$queryrest = null;
			}

			if ($this->testing) {
				preg_match_all('/(((CREATE|ALTER|RENAME) TABLE|INSERT INTO|UPDATE|DELETE|SELECT[ \r\n\t]+.*?[ \r\n\t]+FROM) [a-zA-Z0-9\_\- ]*?)`([a-zA-Z0-9\_\-]*?)`/s',
					$query, $matches);

				foreach($matches[4] as $tmptable) {
					sql::run(
						" SELECT COUNT(*) FROM `{".sql::escape($tmptable)."TMP}`" .
						" LIMIT 1");

					if (sql::error()) {
						sql::run(
							" CREATE TEMPORARY TABLE `{".sql::escape($tmptable)."TMP}`" .
							" LIKE `{".sql::escape($tmptable)."}`");

						sql::run(
							" INSERT INTO `{".sql::escape($tmptable)."TMP}`" .
							" SELECT * FROM `{".sql::escape($tmptable)."}`");
					}
				}
			}

			$query = preg_replace(
				'/(((DROP|CREATE|ALTER) TABLE|INSERT INTO|UPDATE|DELETE|SELECT[ \r\n\t]+.*?[ \r\n\t]+FROM) [a-zA-Z0-9\_\- ]*?)`([a-zA-Z0-9\_\-]*?)`/s',
				'\1`{\4'.($this->testing?'TMP':null).'}`',
				$query);

			$query = preg_replace(
				'/(RENAME TABLE [a-zA-Z0-9\_\- ]*?)`([a-zA-Z0-9\_\-]*?)`([ \r\n\t]+TO[ \r\n\t]+)`([a-zA-Z0-9\_\-]*?)`/s',
				'\1`{\2'.($this->testing?'TMP':null).'}`\3`{\4'.($this->testing?'TMP':null).'}`',
				$query);

			if ($this->testing) {
				$query = str_replace('CREATE TABLE ', 'CREATE TEMPORARY TABLE ', $query);
				$query = preg_replace('/RENAME TABLE (.*?)[ \r\n\t]+TO[ \r\n\t]+(.*)/', 'ALTER TABLE \1 RENAME TO \2', $query);
			}

			$splittedquery = null;

			if ($customdelimiter && preg_match('/'.preg_quote($customdelimiter, '/').'[\r\n]/s', $query)) {
				$splittedquery = preg_split('/'.preg_quote($customdelimiter, '/').'(\r\n|\n)/', $query);
				$query = $splittedquery[0];

				if (isset($splittedquery[1]))
					$queryrest = $splittedquery[1];
			}

			if (preg_match('/delimiter ([^ \r\n]*)/is', $query, $matches)) {
				if ($matches[1] == ';')
					$customdelimiter = false;
				else
					$customdelimiter = $matches[1];

				$query = preg_replace('/delimiter ([^ \r\n]*)/is', '', $query);
			}

			if ($customdelimiter)
				$querybuffer .= ($querybuffer?";\r\n":null).$query;
			else
				$querybuffer = $query;

			if ($customdelimiter && !$splittedquery)
				continue;

			sql::run($querybuffer, true);

			if (sql::error())
				$success = false;

			$querybuffer = null;
		}

		sql::$quiet = false;
		return $success;
	}

	function installFiles($packagefile) {
		if (!$packagefile) {
			echo "<p>".__("No files to install.")."</p>";
			return true;
		}

		if (!@file_exists($this->rootPath.$packagefile)) {
			tooltip::display(
				sprintf(__("File \"%s\" couldn't be found!"),
					$packagefile),
				TOOLTIP_ERROR);
			return false;
		}

		echo
			sprintf(__("Uncompressing %s"), $packagefile)." ... ";

		if (security::checkOutOfMemory(@filesize($this->rootPath.$packagefile), 6)) {
			echo
				"<b class=\"red\">" .
					strtoupper(__("Error")) .
				"</b>" .
				" (".__("Out of Memory!").")<br />";
			return false;
		}

		clearstatcache();

		$success = true;
		$errorfiles = null;

		$tar = new tar();
		$tar->openTar($this->rootPath.$packagefile);

		if (!$tar->numFiles && !$tar->numDirectories) {
			echo
				"<b class=\"red\">" .
					strtoupper(__("Error")) .
				"</b>" .
				" (".__("Empty or invalid update!").")<br />";

			unset($tar);
			return false;
		}

		$ftp = null;

		if (isset($_POST['ftphost']) && $_POST['ftphost'] &&
			isset($_POST['ftpuser']) && $_POST['ftpuser'])
		{
			$ftphost = strip_tags((string)$_POST['ftphost']);
			$ftpuser = strip_tags((string)$_POST['ftpuser']);
			$ftppass = isset($_POST['ftppass'])?strip_tags((string)$_POST['ftppass']):null;
			$ftpport = isset($_POST['ftpport'])?(int)$_POST['ftpport']:21;
			$ftpssl = isset($_POST['ftpssl'])?(bool)$_POST['ftpssl']:false;

			$ftp = new ftpClient();
			$ftp->port = $ftpport;
			$ftp->ssl = $ftpssl;
			$ftp->rootDir = null;
		}

		echo
			"<b>".strtoupper(__("Ok"))."</b><br />";

		if ($ftp) {
			echo
				sprintf(__("FTP Connecting to %s"), $ftphost)." ... ";

			if (!$ftp->connect($ftphost, $ftpuser, $ftppass)) {
				echo
					"<b class=\"red\">" .
						strtoupper(__("Error")) .
					"</b> (" .
					($ftp->error?
						$ftp->error:
						__("Couldn't connect!")) .
					")<br />";

				unset($ftp);
				return false;
			}

			echo
				"<b>".strtoupper(__("Ok"))."</b><br />" .
				__("FTP Locate Root directory")." ... ";

			if ($ftp->exists((defined('JCORE_PATH') && JCORE_PATH?'jcore':'config').'.inc.php')) {
				$ftp->rootDir = '/';

			} else {
				$ftpdirs = $ftp->ls();

				foreach((array)$ftpdirs as $ftpdir) {
					if (preg_match('/(\/'.preg_quote($ftpdir, '/').'\/.*$)/', SITE_PATH, $matches)) {
						if ($ftp->exists($matches[1].(defined('JCORE_PATH') && JCORE_PATH?'jcore':'config').'.inc.php')) {
							$ftp->rootDir = $matches[1];
							break;
						}
					}
				}
			}

			if (!$ftp->rootDir) {
				echo
					"<b class=\"red\">" .
						strtoupper(__("Error")) .
					"</b>" .
					" (".__("Couldn't locate Root directory!").")<br />";

				unset($ftp);
				return false;
			}

			echo
				"<b>".strtoupper(__("Ok"))."</b><br />";
		}

		echo
			"<h3>".__("Creating directories")."</h3>";

		foreach($tar->directories as $directory) {
			$subdir = preg_replace('/^.*?\//', '', $directory['name']);
			$topdir = preg_replace('/^.*?\//', '', $subdir);

			if (!$subdir)
				continue;

			echo
				SITE_PATH.$subdir." ... ";

			if (!@is_dir(SITE_PATH.$subdir) && !dirs::isWritable(SITE_PATH.$subdir) && (!$ftp ||
				(!$ftp->exists($ftp->rootDir.$subdir) && !$ftp->isWritable($ftp->rootDir.$subdir))))
			{
				echo
					"<b class='red'>" .
						strtoupper(__("Error")) .
					"</b>" .
					" (".__("not writable").")<br />";

				$success = false;
				$errorfiles[] = $subdir;

			} else {
				if (!$this->testing) {
					if (@is_dir(SITE_PATH.$subdir) || @mkdir(SITE_PATH.$subdir) ||
						($ftp && $ftp->mkdir($ftp->rootDir.$subdir)))
					{

						if (!@chmod(SITE_PATH.$subdir,
								octdec(substr(trim($directory['mode']), -4))) && $ftp)
							$ftp->chmod($ftp->rootDir.$subdir,
								octdec(substr(trim($directory['mode']), -4)));

						echo
							" <b>".strtoupper(__("Ok"))."</b><br />";

					} else {
						echo
							"<b class='red'>" .
								strtoupper(__("Error")) .
							"</b>" .
							" (".__("not writable").")<br />";

						$success = false;
					}

				} else {
					echo
						" <b>".strtoupper(__("Ok"))."</b><br />";
				}
			}
		}

		echo
			"<h3>".__("Writing files")."</h3>";

		foreach($tar->files as $file) {
			$subfile = preg_replace('/^.*?\//', '', $file['name']);
			$topdir = preg_replace('/(.*\/).*?$/', '\1', $subfile);

			echo
				SITE_PATH.$subfile." ... ";

			if (!template::$selected && preg_match('/' .
				'^template\/template\.css$|' .
				'^template\/template\.js$|' .
				(defined('JCORE_PATH') && JCORE_PATH?
					'^template\/admin\.css$|':
					null) .
				'^template\/images\/[^\/]+$' .
				'/', $subfile) &&
				@is_file(SITE_PATH.$subfile))
			{
				echo
					"<b>" .
						strtoupper(__("Skipped")) .
					"</b>" .
					" (".__("for maintaining your template").")<br />";

				continue;
			}

			if ((!defined('JCORE_PATH') || !JCORE_PATH) &&
				preg_match('/^lib\/[^\/]*?\.(class|words)\.php$/', $subfile) &&
				@is_file(SITE_PATH.$subfile))
			{
				echo
					"<b>" .
						strtoupper(__("Skipped")) .
					"</b>" .
					" (".__("for maintaining your changes").")<br />";

				continue;
			}

			if (!files::isWritable(SITE_PATH.$subfile) && (!$ftp ||
				(!$ftp->isWritable($ftp->rootDir.$subfile))))
			{
				echo
					"<b class='red'>" .
						strtoupper(__("Error")) .
					"</b>" .
					" (".__("not writable").")<br />";

				$success = false;
				$errorfiles[] = $subfile;

			} else {
				if (!$this->testing) {
					if (preg_match('/jcore\.inc\.php$/', $subfile)) {
						$file['file'] = str_replace(
							'localhost', SQL_HOST, $file['file']);
						$file['file'] = str_replace(
							'yourclient_DB', SQL_DATABASE, $file['file']);
						$file['file'] = str_replace(
							'yourclient_mysqlusername', SQL_USER, $file['file']);
						$file['file'] = str_replace(
							'mysqlpassword', SQL_PASS, $file['file']);
						$file['file'] = str_replace(
							'http://yourclient.com/', SITE_URL, $file['file']);
						$file['file'] = str_replace(
							'/home/yourclient/public_html/', SITE_PATH, $file['file']);
						$file['file'] = str_replace(
							'http://jcore.yourdomain.com/', JCORE_URL, $file['file']);
						$file['file'] = str_replace(
							'/var/www/jcore/', JCORE_PATH, $file['file']);

						$file['file'] = preg_replace(
							'/(SQL_PREFIX.*?)\'\'/', '\1\''.SQL_PREFIX.'\'', $file['file']);

						if (!SEO_FRIENDLY_LINKS)
							$file['file'] = preg_replace(
								'/(SEO_FRIENDLY_LINKS.*?,).*?\)/', '\1 false)', $file['file']);
					}

					if (preg_match('/config\.inc\.php$/', $subfile)) {
						$file['file'] = str_replace(
							'localhost', SQL_HOST, $file['file']);
						$file['file'] = str_replace(
							'yourdomain_DB', SQL_DATABASE, $file['file']);
						$file['file'] = str_replace(
							'yourdomain_mysqluser', SQL_USER, $file['file']);
						$file['file'] = str_replace(
							'mysqlpass', SQL_PASS, $file['file']);
						$file['file'] = str_replace(
							'http://yourdomain.com/', SITE_URL, $file['file']);
						$file['file'] = str_replace(
							'/home/yourdomain/public_html/', SITE_PATH, $file['file']);

						$file['file'] = preg_replace(
							'/(SQL_PREFIX.*?)\'\'/', '\1\''.SQL_PREFIX.'\'', $file['file']);

						if (!SEO_FRIENDLY_LINKS)
							$file['file'] = preg_replace(
								'/(SEO_FRIENDLY_LINKS.*?,).*?\)/', '\1 false)', $file['file']);
					}

					$fp = @fopen(SITE_PATH.$subfile, 'w');

					if (!$fp && $ftp) {
						$ftmp = tmpfile();
						if ($ftmp) {
							fwrite($ftmp, $file['file']);
							fseek($ftmp, 0);
						}
					}

					if ($fp || ($ftp && $ftmp && $ftp->save($ftmp, $ftp->rootDir.$subfile))) {
						if ($fp) {
							@fwrite($fp, $file['file']);
							fclose($fp);
						}

						if (!@chmod(SITE_PATH.$subfile,
								octdec(substr(trim($file['mode']), -4))) && $ftp)
							$ftp->chmod($ftp->rootDir.$subfile,
								octdec(substr(trim($file['mode']), -4)));

						echo
							" <b>".strtoupper(__("Ok"))."</b><br />";

					} else {
						echo
							"<b class='red'>" .
								strtoupper(__("Error")) .
							"</b>" .
							" (".__("not writable").")<br />";

						$success = false;
					}

				} else {
					echo
						" <b>".strtoupper(__("Ok"))."</b><br />";
				}
			}
		}

		unset($tar);

		if ($errorfiles && count($errorfiles) < 30) {
			echo
				"<h3>".__("Fixing errors")."</h3>" .
				"<p>" .
					__("Run the following command to fix the above errors.") .
				"</p>" .
				"<p>" .
				"<code>" .
					"chmod o+w " .
					SITE_PATH .
					implode(" ".SITE_PATH, $errorfiles) .
				"</code>" .
				"</p>" .
				"<p>" .
					__("Run the following command to revert permissions after done.") .
				"</p>" .
				"<p>" .
				"<code>" .
					"chmod o-w " .
					SITE_PATH .
					implode(" ".SITE_PATH, $errorfiles) .
				"</code>" .
				"</p>";
		}

		return $success;
	}

	function displayInstallResults($title, $results, $success = false) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::displayInstallResults', $this, $title, $results, $success);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::displayInstallResults', $this, $title, $results, $success, $handled);

			return $handled;
		}

		echo
			"<div tabindex='0' class='fc" .
				(!$success?
					" expanded":
					null) .
				"'>" .
				"<a class='fc-title'>" .
				($success?
					" <span class='align-right'>[".strtoupper(__("Success"))."]</span>":
					" <span class='align-right'>[".strtoupper(__("Error"))."]</span>") .
				$title .
				"</a>" .
				"<div class='fc-content'>" .
					$results .
				"</div>" .
			"</div>";

		api::callHooks(API_HOOK_AFTER,
			'updates::displayInstallResults', $this, $title, $results, $success);
	}

	function displayInstallFTPForm() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::displayInstallFTPForm', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::displayInstallFTPForm', $this, $handled);

			return $handled;
		}

		$form = new form(
			__("Update using FTP"),
			'updateinstallftp');

		$form->displayFormElement = false;
		$form->rememberPasswords = true;

		$form->add(
			__('Hostname'),
			'ftphost',
			FORM_INPUT_TYPE_TEXT,
			false,
			'localhost');
		$form->setStyle('width: 250px;');

		$form->add(
			__('FTP Username'),
			'ftpuser',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 150px;');

		$form->add(
			__('FTP Password'),
			'ftppass',
			FORM_INPUT_TYPE_PASSWORD);
		$form->setStyle('width: 150px;');

		$form->add(
			__('FTP Port'),
			'ftpport',
			FORM_INPUT_TYPE_TEXT,
			false,
			'21');
		$form->setStyle('width: 30px;');

		$form->add(
			__('Use SSL'),
			'ftpssl',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			1);

		$form->display();
		unset($form);

		echo "<br />";

		api::callHooks(API_HOOK_AFTER,
			'updates::displayInstallFTPForm', $this);
	}

	function displayInstallFunctions($installbutton = false) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::displayInstallFunctions', $this, $installbutton);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::displayInstallFunctions', $this, $installbutton, $handled);

			return $handled;
		}

		if ($installbutton)
			echo
				"<input type='submit' name='install' value='" .
					htmlchars(__("Install Update"), ENT_QUOTES) .
					"' class='button submit' /> ";

		echo
			"<input type='submit' name='refresh' value='" .
				htmlchars(__("Test Update"), ENT_QUOTES) .
				"' class='button' />";

		api::callHooks(API_HOOK_AFTER,
			'updates::displayInstallFunctions', $this, $installbutton);
	}

	function displayInstall($update = null) {
		if (!$update && !$this->selectedUpdate)
			return false;

		if (!$update && !$update = $this->get($this->selectedUpdate))
			return false;

		if (isset($_POST['install']) && $_POST['install'] && security::checkToken())
			$this->testing = false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::displayInstall', $this, $update);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::displayInstall', $this, $update, $handled);

			return $handled;
		}

		echo
			"<form action='".url::uri()."' id='updateinstallform' method='post'>" .
				"<input type='hidden' name='_SecurityToken' value='".security::genToken()."' />";

		$success = $this->install($update);

		if ($success) {
			if ($this->testing)
				tooltip::display(
					__("Test has been successfully completed."),
					TOOLTIP_NOTIFICATION);
			else
				tooltip::display(
					__("Update has been successfully installed.")." " .
					"<a href='".url::uri('update, check')."'>" .
						__("Refresh") .
					"</a>",
					TOOLTIP_SUCCESS);

		} else {
			tooltip::display(
				($this->testing?
					__("Test couldn't be successfully completed!"):
					__("Update couldn't be installed!"))." " .
				__("Please see detailed error messages above and try again."),
				TOOLTIP_ERROR);
		}

		if (!$this->testing && $success) {
			echo "</form>";

		} else {
			$this->displayInstallFunctions($success);

			echo
				"</form>" .
				"<div class='clear-both'></div>";
		}

		api::callHooks(API_HOOK_AFTER,
			'updates::displayInstall', $this, $update);
	}

	// ************************************************   Admin Part
	function countAdminItems() {
		if (!isset($_SESSION['UPDATES_TIMESTAMP']) ||
			time() - $_SESSION['UPDATES_TIMESTAMP'] > 60*60*24 ||
			(isset($_SESSION['UPDATES']) && !count($_SESSION['UPDATES']) &&
			time() - $_SESSION['UPDATES_TIMESTAMP'] > 60*60))
		{
			echo
				"<script type='text/javascript'>" .
				"$(document).ready(function() {" .
					"$.get('".url::uri('ALL') .
						"?request=admin/site/updates&counter=1&ajax=1', function(data){" .
						"$('.admin-section-item.as-site-updates').prepend(data);" .
					"});" .
				"});" .
				"</script>";

			return 0;
		}

		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::countAdminItems', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::countAdminItems', $this, $handled);

			return $handled;
		}

		$result = 0;
		if ($updates = $this->get())
			$result = array(
				'Rows' => count($updates),
				'Type' => COUNTER_IMPORTANT);

		api::callHooks(API_HOOK_AFTER,
			'updates::countAdminItems', $this, $result);

		return $result;
	}

	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::setupAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::setupAdmin', $this, $handled);

			return $handled;
		}

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('Check for Updates'),
				'?path='.admin::path().'&amp;check=1');

		favoriteLinks::add(
			__('View Website'),
			SITE_URL);

		api::callHooks(API_HOOK_AFTER,
			'updates::setupAdmin', $this);
	}

	function displayAdminListHeader() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::displayAdminListHeader', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::displayAdminListHeader', $this, $handled);

			return $handled;
		}

		echo
			"<th><span class='nowrap'>".
				__("Updates")."</span></th>";

		api::callHooks(API_HOOK_AFTER,
			'updates::displayAdminListHeader', $this);
	}

	function displayAdminListItem(&$row) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::displayAdminListItem', $this, $row);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::displayAdminListItem', $this, $row, $handled);

			return $handled;
		}

		echo
			"<td class='auto-width'>" .
				"<div class='admin-content-preview'>" .
					"<h2 class='update-title' style='margin: 0;'>" .
						sprintf(__("Update ver. %s"), $row['Ver']) .
					"</h2>" .
					"<div class='update-description'>" .
						"<p>" .
							url::parseLinks($row['Description']) .
						"</p>" .
					"</div>";

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE &&
			$row['Installable'] && $row['Ver'] && !$this->selectedUpdate)
			echo
					"<div class='button submit'>" .
						"<a href='".url::uri('update, check') .
							"&amp;update=".$row['Ver']."' " .
							"title='".htmlchars(
								__("Test run the update for problems"), ENT_QUOTES)."'>" .
							__("Test Update") .
						"</a>" .
					"</div>";

		if (isset($row['URL']) && $row['URL'])
			echo
					"<div class='post-links'>" .
						"<a href='".$row['URL']."' " .
							"target='_blank' " .
							"class='comment read-more'>" .
							__("Read More") .
						"</a>" .
					"</div>";

		echo
					"<div class='clear-both'></div>" .
				"</div>" .
			"</td>";

		api::callHooks(API_HOOK_AFTER,
			'updates::displayAdminListItem', $this, $row);
	}

	function displayAdminList(&$rows) {
		if (!$rows || !is_array($rows) || !count($rows))
			return;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::displayAdminList', $this, $rows);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::displayAdminList', $this, $rows, $handled);

			return $handled;
		}

		echo "<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>";

		$this->displayAdminListHeader();

		echo
				"</tr>" .
				"</thead>" .
				"<tbody>";

		$i = 0;
		ksort($rows);

		if ($this->selectedUpdate && !isset($rows[$this->selectedUpdate]))
			$this->selectedUpdate = '';

		foreach($rows as $row) {
			if ($this->selectedUpdate && $this->selectedUpdate != $row['Ver'])
				continue;

			echo
				"<tr".($i%2?" class='pair'":NULL).">";

			$this->displayAdminListItem($row);

			echo
				"</tr>";

			$i++;
		}

		echo
				"</tbody>" .
			"</table>" .
			"<br />";

		if ($this->userPermissionType & USER_PERMISSION_TYPE_WRITE && $this->selectedUpdate)
			$this->displayInstall($rows[$this->selectedUpdate]);

		api::callHooks(API_HOOK_AFTER,
			'updates::displayAdminList', $this, $rows);
	}

	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::displayAdminTitle', $this, $ownertitle);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::displayAdminTitle', $this, $ownertitle, $handled);

			return $handled;
		}

		admin::displayTitle(
			__('Updates Administration'),
			$ownertitle);

		api::callHooks(API_HOOK_AFTER,
			'updates::displayAdminTitle', $this, $ownertitle);
	}

	function displayAdminDescription() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::displayAdminDescription', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::displayAdminDescription', $this, $handled);

			return $handled;
		}

		echo
			"<p>" .
				__("Please note, " .
					"while your site is being updated, it will enter " .
					"maintenance mode but as soon as the update process " .
					"is complete, it will return to normal mode. By testing " .
					"an update no changes will be made to your system!") .
			"</p>";

		if (isset($_SESSION['UPDATES_TIMESTAMP']))
			echo
				"<p>" .
				sprintf(__("Last checked on %s"),
					calendar::dateTime($_SESSION['UPDATES_TIMESTAMP'])) .
				"</p>";

		api::callHooks(API_HOOK_AFTER,
			'updates::displayAdminDescription', $this);
	}

	function displayAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::displayAdmin', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::displayAdmin', $this, $handled);

			return $handled;
		}

		$check = false;

		if (isset($_GET['check']))
			$check = (bool)$_GET['check'];

		$verifyok = false;
		$updates = $this->get(null, $check);

		$this->displayAdminTitle(
			sprintf(__('ver. %s'), JCORE_VERSION));
		$this->displayAdminDescription();

		echo
			"<div class='admin-content'>";

		if (count($updates))
			$this->displayAdminList($updates);
		else
			tooltip::display(
				__("No updates found."),
				TOOLTIP_NOTIFICATION);

		echo
			"</div>"; //admin-content

		api::callHooks(API_HOOK_AFTER,
			'updates::displayAdmin', $this);
	}

	// ************************************************   Client Part
	static function get($updatever = null, $refreshcache = false) {
		$currentver = JCORE_VERSION;
		$serverver = 0;

		if (!isset($_SESSION['UPDATES_TIMESTAMP'])) {
			$_SESSION['UPDATES_TIMESTAMP'] = time();

		} elseif (time() - $_SESSION['UPDATES_TIMESTAMP'] > 60*60*24 ||
			(isset($_SESSION['UPDATES']) && !count($_SESSION['UPDATES']) &&
			time() - $_SESSION['UPDATES_TIMESTAMP'] > 60*60))
		{
			$refreshcache = true;
		}

		if (defined('JCORE_PATH') && JCORE_PATH) {
			$config = files::get(JCORE_PATH.'config.inc.php');

			if (!$config || !preg_match('/JCORE_VERSION.*?([0-9\.]+)(\'|"| )/i', $config, $matches)) {
				tooltip::display(
					__("Couldn't check jCore server's version number!")." " .
					sprintf(__("Please make sure \"%s\" is readable by me or contact webmaster."),
						JCORE_PATH.'config.inc.php'),
					TOOLTIP_ERROR);
				return false;
			}

			if ($currentver == $matches[1] && !$refreshcache)
				return array();

			$serverver = $matches[1];
		}

		if (!isset($_SESSION['UPDATES']) || $refreshcache) {
			$_SESSION['UPDATES_TIMESTAMP'] = time();
			$_SESSION['UPDATES'] = array();

			$pad = files::get('http://jcore.net/pad.xml');
			if (!$pad) {
				tooltip::display(
					__("Couldn't check for updates!")." " .
					__("Please make sure you have an active internet connection " .
						"and it is accessible by me or contact webmaster."),
					TOOLTIP_ERROR);
				return false;
			}

			if (!preg_match('/<Program_Updates>(.*?)<\/Program_Updates>/is', $pad, $matches))
				return false;

			if (!preg_match_all('/<Update>(.*?)<\/Update>/is', $matches[1], $matches))
				return false;

			foreach($matches[1] as $match) {
				preg_match_all('/<(.*?)>(.*?)<\/\1>/is', $match, $details);

				$update = array();
				foreach($details[2] as $key => $value)
					$update[$details[1][$key]] = $value;

				$_SESSION['UPDATES'][$update['Ver']] = $update;
			}
		}

		$siteupdates = array();
		foreach($_SESSION['UPDATES'] as $update) {
			if ($update['Ver'] <= $currentver)
				continue;

			if ($serverver && $update['Ver'] > $serverver)
				continue;

			$update['Installable'] = false;

			if ((float)$update['Ver']-(float)$currentver < 0.2)
				$update['Installable'] = true;

			$siteupdates[$update['Ver']] = $update;
		}

		if ($updatever) {
			if (!isset($siteupdates[$updatever]))
				return false;

			return $siteupdates[$updatever];
		}

		return $siteupdates;
	}

	function downloadCheckTimeOut($fp) {
		$status = socket_get_status($fp);

		if ($status["timed_out"]) {
			echo
				"<script type='text/javascript'>" .
					"$('#jcoreupdateprocess').append('<b class=\"red\">" .
							strtoupper(__("Error")) .
						"</b> (".__("connection timed out").")<br />');" .
				"</script>";

			url::flushDisplay();

			return true;
		}

		return false;
	}

	function download($downloadid, $title = null, $savefile = true) {
		if (!$downloadid)
			return false;

		dirs::create($this->rootPath);
		$downloadstatusclass = "download-".$downloadid;

		echo
			"<script type='text/javascript'>" .
				"$('#jcoreupdateprocess').append('" .
						sprintf(__("Downloading %s"), $title)." ... " .
						"<span class=\"".$downloadstatusclass."\" style=\"font-weight: bold;\">" .
							__("connecting") .
						"</span> " .
					"');" .
			"</script>";

		url::flushDisplay();

		$fp = @fsockopen(
				'jcore.net',
				80, $errno, $errstr);

		if (!$fp) {
			echo
				"<script type='text/javascript'>" .
					"$('#jcoreupdateprocess').append('<b class=\"red\">" .
							strtoupper(__("Error")) .
						"</b> (".$errno.": ".$errstr.")<br />');" .
				"</script>";

			url::flushDisplay();
			return false;
		}

		echo
			"<script type='text/javascript'>" .
				"$('#jcoreupdateprocess .".$downloadstatusclass."').html('" .
						__("sending request")."');" .
			"</script>";

		url::flushDisplay();

		stream_set_timeout($fp, 10);

		$filename = null;
		$filesize = null;
		$header = null;
		$content = null;

		if ((int)$downloadid)
			$geturl =
				"?request=modules/filesharing/filesharingattachments" .
				"&download=".$downloadid .
				"&downloading=1&ajax=1";
		else
			$geturl = $downloadid;

		@fwrite($fp,
			"GET /".$geturl." HTTP/1.1\r\n" .
			"Host: jcore.net\r\n" .
			"Content-type: text/html\r\n" .
			"Connection: close\r\n\r\n");

		while($data = @fgets($fp)) {
			if ($this->downloadCheckTimeOut($fp, $title))
				return false;

			if($data == "\r\n")
				break;

			$header .= $data;
		}

		preg_match('/filename="(.*?)"/i',
			$header, $matches);

		if (isset($matches[1]))
			$filename = $matches[1];

		preg_match('/Content-Length:(.*)/i',
			$header, $matches);

		if (isset($matches[1]))
			$filesize = (int)$matches[1];

		$fl = null;

		if ($filename && @file_exists($this->rootPath.$filename) &&
			$filesize == @filesize($this->rootPath.$filename))
		{
			echo
				"<script type='text/javascript'>" .
					"$('#jcoreupdateprocess .".$downloadstatusclass."').html('');" .
				"</script>";

			echo
				"<script type='text/javascript'>" .
					"$('#jcoreupdateprocess').append('<b>" .
							strtoupper(__("Ok")) .
						"</b> (".files::humanSize(@filesize($this->rootPath.$filename))." " .
						__("cached").")<br />');" .
				"</script>";

			url::flushDisplay();

			@fclose($fp);

			if ($savefile)
				return $filename;

			return files::get($this->rootPath.$filename);
		}

		if ($savefile && $filename) {
			$fl = @fopen($this->rootPath.$filename, 'w');

			if (!$fl) {
				echo
					"<script type='text/javascript'>" .
						"$('#jcoreupdateprocess').append('<b class=\"red\">" .
								strtoupper(__("Error")) .
							"</b><br />');" .
					"</script>";

				url::flushDisplay();

				tooltip::display(
					__("Local file couldn't be created!")." " .
					sprintf(__("Please make sure \"%s\" is writable by me " .
						"or contact webmaster."), $this->rootPath),
					TOOLTIP_ERROR);
				return false;
			}
		}

    	if (!$savefile || !$fl)
			@fgets($fp);

		$time = null;
		$percentage = 0;
		$downloadsize = 0;

		while (true) {
			if ($filesize)
				$percentage = round($downloadsize * 100 / $filesize);

			if ($this->downloadCheckTimeOut($fp, $title))
				return false;

			if (!$time || time() - $time > 1) {
				echo
					"<script type='text/javascript'>" .
						"$('#jcoreupdateprocess .".$downloadstatusclass."').html('" .
								$percentage."%');" .
					"</script>";

				$time = time();
				url::flushDisplay();
			}

   			$data = @fread($fp, 8192);
   			$downloadsize += strlen($data);

    		if (strlen($data) == 0) {
				echo
					"<script type='text/javascript'>" .
						"$('#jcoreupdateprocess .".$downloadstatusclass."').html('" .
								$percentage."%');" .
					"</script>";

				url::flushDisplay();
   	    		break;
    		}

   	    	if ($fl)
   	    		@fwrite($fl, $data, 8192);
   	    	else
	   	    	$content .= $data;
		}

		fclose($fp);

		if ($fl)
			fclose($fl);

		if ($savefile && !$filename) {
			echo
				"<script type='text/javascript'>" .
					"$('#jcoreupdateprocess').append('<b class=\"red\">" .
							strtoupper(__("Error")) .
						"</b><br />');" .
				"</script>";

			url::flushDisplay();

			tooltip::display(
				__("Invalid response returned by jCore.net!"),
				TOOLTIP_ERROR);

			echo substr(preg_replace('/\r\n0\r\n/', '', $content),
					0, 1024);

			return false;
		}

		echo
			"<script type='text/javascript'>" .
				"$('#jcoreupdateprocess').append('<b>" .
						strtoupper(__("Ok")) .
					"</b> (".files::humanSize($downloadsize).")<br />');" .
			"</script>";

		url::flushDisplay();

		if ($savefile)
			return $filename;

		return $content;
	}

	function ajaxRequest() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'updates::ajaxRequest', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'updates::ajaxRequest', $this, $handled);

			return $handled;
		}

		$counter = null;

		if (isset($_GET['counter']))
			$counter = (int)$_GET['counter'];

		if ($counter) {
			if (!$GLOBALS['USER']->loginok ||
				!$GLOBALS['USER']->data['Admin'])
			{
				tooltip::display(
					__("Request can only be accessed by administrators!"),
					TOOLTIP_ERROR);

				api::callHooks(API_HOOK_AFTER,
					'updates::ajaxRequest', $this);

				return true;
			}

			$updates = $this->get();

			if (is_array($updates)) {
				if (count($updates))
					counter::display(count($updates), COUNTER_IMPORTANT);
			} else {
				counter::display('!', COUNTER_IMPORTANT);
			}

			api::callHooks(API_HOOK_AFTER,
				'updates::ajaxRequest', $this, $counter);

			return true;
		}

		api::callHooks(API_HOOK_AFTER,
			'updates::ajaxRequest', $this);

		return true;
	}
}

?>