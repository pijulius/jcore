<?php

/***************************************************************************
 *            email.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

_email::add('WebmasterWarning',
		"WARNING at %PAGE_TITLE%",
		"Dear Webmaster,\n\n" .
		"A warning has been triggered on".
		" ".date("Y-m-d H:i:s")." at %PAGE_TITLE%\n\n" .
		"URL: %CURRENT_URL%\n" .
		"IP: %REMOTE_ADDR%\n\n" .
		"%WARNING%\n\n" .
		"Sincerely,\n" .
		"%PAGE_TITLE%");

class _email {
	static $templates = array();

	var $from = WEBMASTER_EMAIL;
	var $to = null;
	var $cc = null;
	var $bcc = null;
	var $subject = '';
	var $message = '';
	var $variables = array();
	var $toUser = array();
	var $toUserID = null;
	var $force = false;
	var $quiet = false;
	var $html = false;

	function __construct() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'email::email', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'email::email', $this, $handled);

			return $handled;
		}

		$this->from = email::genWebmasterEmail();

		if (defined('HTML_EMAILS') && HTML_EMAILS)
			$this->html = true;

		api::callHooks(API_HOOK_AFTER,
			'email::email', $this);
	}

	static function add($id, $subject, $body, $save = true) {
		if (!$id)
			return false;

		if (isset(_email::$templates[$id]))
			exit($id." email template couldn't be added as it's " .
				"id is already used by another template!");

		$handled = api::callHooks(API_HOOK_BEFORE,
			'email::add', $_ENV, $id, $subject, $body, $save);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'email::add', $_ENV, $id, $subject, $body, $save, $handled);

			return $handled;
		}

		_email::$templates[$id]['Subject'] = $subject;
		_email::$templates[$id]['Body'] = $body;
		_email::$templates[$id]['Save'] = $save;

		api::callHooks(API_HOOK_AFTER,
			'email::add', $_ENV, $id, $subject, $body, $save);

		return true;
	}

	static function edit($id, $subject, $body) {
		if (!$id)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'email::edit', $_ENV, $id, $subject, $body);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'email::edit', $_ENV, $id, $subject, $body, $handled);

			return $handled;
		}

		email::$templates[$id]['Subject'] = $subject;
		email::$templates[$id]['Body'] = $body;

		api::callHooks(API_HOOK_AFTER,
			'email::edit', $_ENV, $id, $subject, $body);

		return true;
	}

	static function get($id) {
		if (!$id)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'email::get', $_ENV, $id);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'email::get', $_ENV, $id, $handled);

			return $handled;
		}

		$result = email::$templates[$id];

		api::callHooks(API_HOOK_AFTER,
			'email::get', $_ENV, $id, $result);

		return $result;
	}

	static function genWebmasterEmail() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'email::genWebmasterEmail', $_ENV);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'email::genWebmasterEmail', $_ENV, $handled);

			return $handled;
		}

		$result = preg_replace('/(-|,|;).*/i', '', strip_tags(PAGE_TITLE)).
			" <".WEBMASTER_EMAIL.">";

		api::callHooks(API_HOOK_AFTER,
			'email::genWebmasterEmail', $_ENV, $result);

		return $result;
	}

	function load($id) {
		if (!$id)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'email::load', $this, $id);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'email::load', $this, $id, $handled);

			return $handled;
		}

		$email = null;

		if (JCORE_VERSION >= '0.7' && class_exists('notificationEmails'))
			$email = notificationEmails::get($id);

		if (!$email)
			$email = email::get($id);

		if ($email) {
			$this->subject = $email['Subject'];
			$this->message = $email['Body'];
		}

		api::callHooks(API_HOOK_AFTER,
			'email::load', $this, $id, $email);

		return $email;
	}

	function getToUser() {
		if (!$this->toUserID && !$this->toUser)
			return false;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'email::getToUser', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'email::getToUser', $this, $handled);

			return $handled;
		}

		if ($this->toUser) {
			if (!$this->toUser['Email']) {
				api::callHooks(API_HOOK_AFTER,
					'email::getToUser', $this);

				return false;
			}

			$this->to = $this->toUser['UserName'].
				" <".$this->toUser['Email'].">";

			$this->toUserID = $this->toUser['ID'];
			$result = $this->toUser['ID'];

			api::callHooks(API_HOOK_AFTER,
				'email::getToUser', $this, $result);

			return $result;
		}

		$user = sql::fetch(sql::run(
			" SELECT " .
			" `ID`," .
			" `UserName`," .
			" `Password`," .
			" `Email`" .
			" FROM `{users}`" .
			" WHERE `ID` = '".(int)$this->toUserID."'"));

		$result = null;

		if ($user) {
			$this->to = $user['UserName'] .
				" <".$user['Email'].">";

			$this->toUserID = $user['ID'];
			$this->toUser = $user;
			$result = $user['ID'];
		}

		api::callHooks(API_HOOK_AFTER,
			'email::getToUser', $this, $result);

		return $result;
	}

	static function verify($email, $withname = false) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'email::verify', $_ENV, $email, $withname);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'email::verify', $_ENV, $email, $withname, $handled);

			return $handled;
		}

		if ($withname && strpos($email, '<') !== false) {
			preg_match('/(.*)<(.*)>/', $email, $matches);

			if (isset($matches[2])) {
				if (preg_match('/(,|;)/', $matches[1])) {
					api::callHooks(API_HOOK_AFTER,
						'email::verify', $_ENV, $email, $withname);

					return false;
				}

				$email = $matches[2];
			}
		}

		$user = '[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\']+';
		$domain = '(?:(?:[a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.?)+';
		$ipv4 = '[0-9]{1,3}(\.[0-9]{1,3}){3}';
		$ipv6 = '[0-9a-fA-F]{1,4}(\:[0-9a-fA-F]{1,4}){7}';

		$result = preg_match("/^$user@($domain|(\[($ipv4|$ipv6)\]))$/", $email);

		api::callHooks(API_HOOK_AFTER,
			'email::verify', $_ENV, $email, $withname, $result);

		return $result;
	}

	function reset() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'email::reset', $this);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'email::reset', $this, $handled);

			return $handled;
		}

		$this->from = email::genWebmasterEmail();
		$this->to = null;
		$this->cc = null;
		$this->bcc = null;
		$this->toUser = array();
		$this->toUserID = null;

		api::callHooks(API_HOOK_AFTER,
			'email::reset', $this);
	}

	function send($debug = false) {
		if (isset($GLOBALS['IGNORE_EMAILS']) && (bool)$GLOBALS['IGNORE_EMAILS'])
			return true;

		$handled = api::callHooks(API_HOOK_BEFORE,
			'email::send', $this, $debug);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'email::send', $this, $debug, $handled);

			return $handled;
		}

		$subject = $this->subject;
		$message = $this->message;

		if (!$this->html)
			$message = strip_tags($message);

		foreach($this->variables as $key => $value) {
			$subject = str_replace(
				"%".strtoupper($key)."%", $value, $subject);

			$message = str_replace(
				"%".strtoupper($key)."%", $value, $message);
		}

		$this->getToUser();

		if ($this->to == WEBMASTER_EMAIL && !$this->bcc &&
			defined('WEBMASTER_BCC_EMAIL') && WEBMASTER_BCC_EMAIL)
			$this->bcc = WEBMASTER_BCC_EMAIL;

		if (isset($this->toUser['DisableNotificationEmails']) &&
			$this->toUser['DisableNotificationEmails'] && !$this->force)
		{
			api::callHooks(API_HOOK_AFTER,
				'email::send', $this, $debug);

			return false;
		}

		foreach($this->toUser as $key => $value) {
			$subject = str_replace(
				"%".strtoupper($key)."%", $value, $subject);

			$message = str_replace(
				"%".strtoupper($key)."%", $value, $message);
		}

		contentCodes::replaceDefinitions($subject);
		contentCodes::replaceDefinitions($message);

		if ($this->html && !preg_match('/<[a-zA-Z]>/', $message))
			$message = form::text2HTML($message);

		if ($debug) {
			tooltip::display(
				"<textarea rows='10' cols='50' style='width: 100%; height: 400px;'>" .
				"From: ".$this->from."\n" .
				"To: ".$this->to."\n\n".
				"Subject: ".$subject."\n\n".
				$message .
				"</textarea>",
				TOOLTIP_NOTIFICATION);

			api::callHooks(API_HOOK_AFTER,
				'email::send', $this, $debug);

			return false;
		}

		$sent = false;

		if (defined('EMAIL_PGP_ENCRYPT') && EMAIL_PGP_ENCRYPT)
			$sent = $this->pgpMail($subject, $message);
		elseif (defined('EMAIL_USE_SMTP') && EMAIL_USE_SMTP)
			$sent = $this->smtpMail($subject, $message);
		else
			$sent = $this->phpMail($subject, $message);

		if (!$sent && !$this->quiet)
			tooltip::display(
				sprintf(__("Email couldn't be sent. Please try again or " .
					"send your email <a href='mailto:%s'>manually</a>."),
					htmlchars($this->to, ENT_QUOTES)),
				TOOLTIP_ERROR);

		api::callHooks(API_HOOK_AFTER,
			'email::send', $this, $debug, $sent);

		return $sent;
	}

	function phpMail($subject, $message) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'email::phpMail', $this, $subject, $message);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'email::phpMail', $this, $subject, $message, $handled);

			return $handled;
		}

		$result =
			@mail(
				$this->to,
				$subject,
				preg_replace('/\r?\n/', "\r\n", $message),
				"From: ".$this->from."\r\n" .
				"Return-path: ".WEBMASTER_EMAIL."\r\n".
				($this->cc?
					"Cc: ".$this->cc."\r\n":
					null) .
				($this->bcc?
					"Bcc: ".$this->bcc."\r\n":
					null) .
				($this->html?
					"Content-Type: text/html;":
					"Content-Type: text/plain;") .
				" charset=".PAGE_CHARSET."\r\n",
				"-f".WEBMASTER_EMAIL);

		api::callHooks(API_HOOK_AFTER,
			'email::phpMail', $this, $subject, $message, $result);

		return $result;
	}

	function smtpMail($subject, $message) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'email::smtpMail', $this, $subject, $message);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'email::smtpMail', $this, $subject, $message, $handled);

			return $handled;
		}

		if (!defined('EMAIL_SMTP_HOST') || !defined('EMAIL_SMTP_PORT') ||
			!EMAIL_SMTP_HOST || !EMAIL_SMTP_PORT)
		{
			if ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'])
				tooltip::display(
					__("Couldn't send email using SMTP as SMTP Host and/or SMTP Port " .
						"are not defined! Please go to your administration panel and " .
						"in the Global Settings define both required values."),
					TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'email::smtpMail', $this, $subject, $message);

			return false;
		}

		$fromemail = $this->from;
		$toemail = $this->to;

		preg_match('/<(.*)>/', $this->from, $matches);
		if (isset($matches[1]))
			$fromemail = $matches[1];

		preg_match('/<(.*)>/', $this->to, $matches);
		if (isset($matches[1]))
			$toemail = $matches[1];

		$connect = @fsockopen(
			(defined('EMAIL_USE_SSL_SMTP') && EMAIL_USE_SSL_SMTP?
				"ssl://":
				null).
			EMAIL_SMTP_HOST,
			EMAIL_SMTP_PORT,
			$errno, $errstr, 30);

		if (!$connect) {
			if ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'])
				tooltip::display(
					sprintf(__("SMTP connection couldn't be established. %s"),
						"#".$errno." - ".htmlchars($errstr, ENT_QUOTES)),
					TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'email::smtpMail', $this, $subject, $message);

			return false;
		}

		$rcv = @fgets($connect, 1024);

		@fputs($connect, "HELO ".(string)$_SERVER['SERVER_NAME']."\r\n");
		$rcv .= @fgets($connect, 1024);

		if (defined('EMAIL_SMTP_USERNAME') && EMAIL_SMTP_USERNAME) {
			@fputs($connect, "auth login\r\n");
			$rcv .= @fgets($connect, 256);

			@fputs($connect, base64_encode(EMAIL_SMTP_USERNAME)."\r\n");
			$rcv .= @fgets($connect, 256);

			@fputs($connect, base64_encode(EMAIL_SMTP_PASSWORD)."\r\n");
			$rcv .= @fgets($connect, 256);
		}

		@fputs($connect, "MAIL FROM: <".$fromemail.">\r\n");
		$rcv = @fgets($connect, 1024);

		@fputs($connect, "RCPT TO: <".$toemail.">\r\n");
		$rcv .= @fgets($connect, 1024);

		@fputs($connect, "DATA\r\n");
		$rcv .= @fgets($connect, 1024);

		@fputs($connect, "Subject: ".$subject."\r\n");
		@fputs($connect, "From: ".$this->from."\r\n");
		@fputs($connect, "To: ".$this->to."\r\n");

		if ($this->cc)
			@fputs($connect, "Cc: ".$this->cc."\r\n");
		if ($this->bcc)
			@fputs($connect, "Bcc: ".$this->bcc."\r\n");

		@fputs($connect, "X-Sender: <".WEBMASTER_EMAIL.">\r\n");
		@fputs($connect, "Return-Path: <".WEBMASTER_EMAIL.">\r\n");
		@fputs($connect, "Errors-To: <".WEBMASTER_EMAIL.">\r\n");
		@fputs($connect, "X-Mailer: PHP\r\n");
		@fputs($connect, "X-Priority: 3\r\n");

		@fputs($connect,
			($this->html?
				"Content-Type: text/html;":
				"Content-Type: text/plain;") .
			" charset=".PAGE_CHARSET."\r\n");

		@fputs($connect, "\r\n");
		@fputs($connect, preg_replace('/\r?\n/', "\r\n", $message)."\r\n");

		@fputs($connect, ".\r\n");
		$rcv .= $sent = @fgets($connect, 1024);

		@fputs($connect, "RSET\r\n");
		$rcv .= @fgets($connect, 1024);

		@fputs ($connect, "QUIT\r\n");
		$rcv .= @fgets ($connect, 1024);

		fclose($connect);

		if ((int)$sent != 250) {
			if ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin']) {
				if (!$rcv)
					$rcv = __("Connection broken up. Please make sure to define the " .
						"right SMTP port, 465 for SSL and 25 for normal connections.");

				tooltip::display(
					sprintf(__("SMTP error: %s"), htmlchars($rcv, ENT_QUOTES)),
					TOOLTIP_ERROR);
			}

			$result = false;
		} else {
			$result = true;
		}

		api::callHooks(API_HOOK_AFTER,
			'email::smtpMail', $this, $subject, $message, $result);

		return $result;
	}

	function pgpMail($subject, $message) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'email::pgpMail', $this, $subject, $message);

		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'email::pgpMail', $this, $subject, $message, $handled);

			return $handled;
		}

		if (!defined('EMAIL_PGP_PUBLIC_KEYS_DIRECTORY') || !defined('EMAIL_PGP_BINARY') ||
			!EMAIL_PGP_PUBLIC_KEYS_DIRECTORY || !EMAIL_PGP_BINARY)
		{
			if ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'])
				tooltip::display(
					__("Couldn't encrypt email as PGP Public Keys directory and/or PGP Binary " .
						"file are not defined! Please go to your administration panel and " .
						"in the Global Settings define both required values."),
					TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'email::pgpMail', $this, $subject, $message);

			return false;
		}

		$command = 'echo '.escapeshellarg($message).' | '.escapeshellarg(EMAIL_PGP_BINARY) .
			' -a --homedir '.escapeshellarg(EMAIL_PGP_PUBLIC_KEYS_DIRECTORY) .
			' --always-trust --batch --no-secmem-warning -e -u ' .
			escapeshellarg($this->from).' -r '.escapeshellarg($this->to) .
			' 2>&1';

		$encrypted = null;
		$errorcode = null;
		$result = @exec($command, $encrypted, $errorcode);

		$message = implode("\n", $encrypted);
		if(!preg_match("/-----BEGIN PGP MESSAGE-----.*-----END PGP MESSAGE-----/s", $message)) {
			if ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'])
				tooltip::display(
					sprintf(__("Email couldn't be encrypted! Please make sure you have the " .
						"right paths set for the PGP binary and public key files. %s"),
						"#".$errorcode." - ".htmlchars($message, ENT_QUOTES)),
					TOOLTIP_ERROR);

			api::callHooks(API_HOOK_AFTER,
				'email::pgpMail', $this, $subject, $message);

			return false;
		}

		if (defined('EMAIL_USE_SMTP') && EMAIL_USE_SMTP)
			$result = $this->smtpMail($subject, $message);
		else
			$result = $this->phpMail($subject, $message);

		api::callHooks(API_HOOK_AFTER,
			'email::pgpMail', $this, $subject, $message, $result);

		return $result;
	}
}

?>