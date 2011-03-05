<?php

/***************************************************************************
 *            email.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
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
		$this->from = email::genWebmasterEmail();
	}
	
	static function add($id, $subject, $body, $save = true) {
		if (!$id)
			return false;
		
		if (isset(_email::$templates[$id])) 
			exit($id." email template couldn't be added as it's " .
				"id is already used by another template!");
		
		_email::$templates[$id]['Subject'] = $subject;
		_email::$templates[$id]['Body'] = $body;
		_email::$templates[$id]['Save'] = $save;
		
		return true;
	}
	
	static function edit($id, $subject, $body) {
		if (!$id)
			return false;
		
		email::$templates[$id]['Subject'] = $subject;
		email::$templates[$id]['Body'] = $body;
		
		return true;
	}
	
	static function get($id) {
		if (!$id)
			return false;
		
		return email::$templates[$id];
	}
	
	static function genWebmasterEmail() {
		return preg_replace('/(-|,|;).*/i', '', strip_tags(PAGE_TITLE)).
			" <".WEBMASTER_EMAIL.">";
	}
	
	function load($id) {
		if (!$id)
			return false;
		
		$email = null;
		
		if (JCORE_VERSION >= '0.7' && class_exists('notificationEmails'))
			$email = notificationEmails::get($id);
		
		if (!$email)
			$email = email::get($id);
		
		if (!$email)
			return false;
		
		$this->subject = $email['Subject'];
		$this->message = $email['Body'];
		
		return true;
	}
	
	function getToUser() {
		if (!$this->toUserID && !$this->toUser)
			return false;
			
		if ($this->toUser) {
			if (!$this->toUser['Email'])
				return false;
			
			$this->to = $this->toUser['UserName'].
				" <".$this->toUser['Email'].">";
				
			$this->toUserID = $this->toUser['ID'];
			return $this->toUser['ID'];
		}
		
		$user = sql::fetch(sql::run(
			" SELECT " .
			" `ID`," .
			" `UserName`," .
			" `Password`," .
			" `Email`" .
			" FROM `{users}`" .
			" WHERE `ID` = '".(int)$this->toUserID."'"));
	
		if (!$user)
			return false;
		
		$this->to = $user['UserName'] .
			" <".$user['Email'].">";
			
		$this->toUserID = $user['ID'];
		$this->toUser = $user;
		
		return $user['ID'];
	}
	
	static function verify($email, $withname = false) {
		if ($withname && strpos($email, '<') !== false) {
			preg_match('/(.*)<(.*)>/', $email, $matches);
			
			if (isset($matches[2])) {
				if (preg_match('/(,|;)/', $matches[1]))
					return false;
				
				$email = $matches[2];
			}
		}
		
		$user = '[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\']+';
		$domain = '(?:(?:[a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.?)+';
		$ipv4 = '[0-9]{1,3}(\.[0-9]{1,3}){3}';
		$ipv6 = '[0-9a-fA-F]{1,4}(\:[0-9a-fA-F]{1,4}){7}';

		return preg_match("/^$user@($domain|(\[($ipv4|$ipv6)\]))$/", $email);
	}
	
	function reset() {
		$this->from = email::genWebmasterEmail;
		$this->to = null;
		$this->cc = null;
		$this->bcc = null;
		$this->toUser = array();
		$this->toUserID = null;
	}
	
	function send($debug = false) {
		if (isset($GLOBALS['IGNORE_EMAILS']) && $GLOBALS['IGNORE_EMAILS'])
			return true;
		
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
			return false;
		
		foreach($this->toUser as $key => $value) {
			$subject = str_replace(
				"%".strtoupper($key)."%", $value, $subject); 
			
			$message = str_replace(
				"%".strtoupper($key)."%", $value, $message); 
		}
		
		contentCodes::replaceDefinitions($subject);
		contentCodes::replaceDefinitions($message);
		
		if ($debug) {
			tooltip::display(
				"<textarea rows='10' cols='50' style='width: 100%; height: 400px;'>" .
				"From: ".$this->from."\n" .
				"To: ".$this->to."\n\n".
				"Subject: ".$subject."\n\n". 
				$message .
				"</textarea>",
				TOOLTIP_NOTIFICATION);
			
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
					htmlspecialchars($this->to, ENT_QUOTES)),
				TOOLTIP_ERROR);
		
		return $sent;
	}
	
	function phpMail($subject, $message) {
		return
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
				" charset=".PAGE_CHARSET."\r\n");
	}
	
	function smtpMail($subject, $message) {
		if (!defined('EMAIL_SMTP_HOST') || !defined('EMAIL_SMTP_PORT') ||
			!EMAIL_SMTP_HOST || !EMAIL_SMTP_PORT) 
		{
			if ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'])
				tooltip::display(
					__("Couldn't send email using SMTP as SMTP Host and/or SMTP Port " .
						"are not defined! Please go to your administration panel and " .
						"in the Global Settings define both required values."),
					TOOLTIP_ERROR);
			
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
						"#".$errno." - ".htmlspecialchars($errstr, ENT_QUOTES)),
					TOOLTIP_ERROR);
			
			return false;
		} 

		$rcv = @fgets($connect, 1024);
		 
		@fputs($connect, "HELO ".$_SERVER['SERVER_NAME']."\r\n");
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
					sprintf(__("SMTP error: %s"), htmlspecialchars($rcv, ENT_QUOTES)),
					TOOLTIP_ERROR);
			}
			
			return false;
		}
		
		return true;
	}
	
	function pgpMail($subject, $message) {
		if (!defined('EMAIL_PGP_PUBLIC_KEYS_DIRECTORY') || !defined('EMAIL_PGP_BINARY') ||
			!EMAIL_PGP_PUBLIC_KEYS_DIRECTORY || !EMAIL_PGP_BINARY) 
		{
			if ($GLOBALS['USER']->loginok && $GLOBALS['USER']->data['Admin'])
				tooltip::display(
					__("Couldn't encrypt email as PGP Public Keys directory and/or PGP Binary " .
						"file are not defined! Please go to your administration panel and " .
						"in the Global Settings define both required values."),
					TOOLTIP_ERROR);
			
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
						"#".$errorcode." - ".htmlspecialchars($message, ENT_QUOTES)),
					TOOLTIP_ERROR);
			
			return false;
		}
		
		if (defined('EMAIL_USE_SMTP') && EMAIL_USE_SMTP)
			return $this->smtpMail($subject, $message);
		
		return $this->phpMail($subject, $message);
	}
}

?>