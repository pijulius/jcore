<?php

/***************************************************************************
 *            ftpclient.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/

class _ftpClient {
	var $connection = null;
	var $port = 21;
	var $passive = true;
	var $ssl = false;
	var $error = null;
	
	function __destruct() {
		if ($this->connection)
			@ftp_close($this->connection);
	}
	
	function connect($server, $user, $pass = null) {
		if ($this->ssl)
			$this->connection = @ftp_ssl_connect($server, $this->port);
		else
			$this->connection = @ftp_connect($server, $this->port);
		
		if (!$this->connection)
			return false;
		
		if (!$this->cmd('USER '.$user))
			return false;
		
		if ($pass && !$this->cmd('PASS '.$pass))
			return false;
		
		@ftp_pasv($this->connection, $this->passive);
		return true;
	}
	
	function mkdir($dir) {
		if (!$dir)
			return false;
		
		if ($this->exists($dir) || @ftp_mkdir($this->connection, $dir))
			return true;
		
		if (!$this->mkdir(substr($dir, 0, strrpos($dir, '/'))))
			return false;
		
		return
			@ftp_mkdir($this->connection, $dir);
	}
	
	function chdir($dir) {
		if (!$dir)
			return false;
		
		return 
			@ftp_chdir($this->connection, $dir);
	}
	
	function chmod($path) {
		if (!$path)
			return false;
		
		return 
			@ftp_chmod($this->connection, $path);
	}
	
	function ls($dir = '.') {
		return
			@ftp_nlist($this->connection, $dir);
	}
	
	function cmd($command) {
		if (!$command)
			return false;
		
		$result = ftp_raw($this->connection, $command);
		if (!$result)
			return false;
		
		$resultcode = (int)$result[0];
		
		if ($resultcode >= 400 && $resultcode < 600) {
			$this->error = $result[0];
			return false;
		}
		
		return true;
	}
	
	function exists($path) {
		if (!$path)
			return false;
		
		if (@ftp_size($this->connection, $path) != -1)
			return true;
		
		$curdir = @ftp_pwd($this->connection);
		if (@ftp_chdir($this->connection, $path)) {
			@ftp_chdir($this->connection, $curdir);
			return true;
		}
		
		return false;
	}
	
	function isWritable($path) {
 		if (!$path)
 			return false;
 		
 		if ($this->exists($path))
 			return @ftp_rename($this->connection, $path, $path);
 		
 		return $this->isWritable(substr($path, 0, strrpos($path, '/')));
	}
	
	function get($file, $target) {
		if (!$file || !$target)
			return false;
		
 		$dir = preg_replace('/((.*(\/|\\\))|^).*$/', '\2', $target);
 		
		if ($dir && !is_dir($dir) && !@mkdir($dir, 0777, true))
			return false;
 		
		if (preg_match('/\.(txt|csv)$/i', $file))
			$mode = FTP_ASCII;		
		else
			$mode = FTP_BINARY;
		
		return
			@ftp_get($this->connection, $target, $file, $mode, 0);
	}
	
	function save($file, $target) {
		if (!$file || !$target)
			return false;
		
		if (!is_resource($file) && !files::exists($file))
			return false;
		
 		$dir = preg_replace('/((.*(\/|\\\))|^).*$/', '\2', $target);
 		
		if ($dir && !$this->exists($dir) && !$this->mkdir($dir))
			return false;
		
		if (!is_resource($file) && preg_match('/\.(txt|csv)$/i', $file))
			$mode = FTP_ASCII;		
		else
			$mode = FTP_BINARY;
		
		if (is_resource($file))
			return
				@ftp_fput($this->connection, $target, $file, $mode);
		
		return
			@ftp_put($this->connection, $target, $file, $mode);
	}
}

?>