<?php
## PhpPatcher class
# @author legolas558
# @version 0.1.1
# Licensed under GNU General Public License (GPL)
#
# Facility to merge unified diff files
# First use Merge() and then ApplyPatch() to commit changes
# files will be created, updated and/or deleted

define('_PHPP_INVALID_INPUT', 'Invalid input');
define('_PHPP_UNEXPECTED_EOF', 'Unexpected end of file');
define('_PHPP_UNEXPECTED_ADD_LINE', 'Unexpected add line at line %d');
define('_PHPP_UNEXPECTED_REMOVE_LINE', 'Unexpected remove line at line %d');
define('_PHPP_INVALID_DIFF', 'Invalid unified diff block');
define('_PHPP_FAILED_VERIFY', 'Failed source verification of file %s at line %d');

class _patch {
	var $root;
	var $msg;
	var $sources = array();
	var $destinations = array();
	var $removals = array();
	var $newline = "\n";
	
	function __construct($root_path) {
		// if you specify a root path all paths will be intended as relative to it (and not written, too)
		$this->root = $root_path;
	}
	
	function &_get_source($src) {
		if (isset($this->sources[$src]))
			return $this->sources[$src];
		if (!is_readable($src)) {
			$n = null;
			return $n;
		}
		$this->sources[$src] = $this->_linesplit(file_get_contents($src));
		return $this->sources[$src];
	}
	
	function &_get_destin($dst, $src) {
		if (isset($this->destinations[$dst]))
			return $this->destinations[$dst];
		$this->destinations[$dst] = $this->_get_source($src);
		return $this->destinations[$dst];
	}

	// separate CR or CRLF lines
	function &_linesplit(&$data) {
		$lines = preg_split('/(\r\n)|(\r)|(\n)/', $data);
		return $lines;
	}
	
	function merge($udiff) {
		// (1) Separate the input into lines
		$lines = $this->_linesplit($udiff);
		if (!isset($lines)) {
			$this->msg = _PHPP_INVALID_INPUT;
			return false;
		}
		unset($udiff);
	
		$line = current($lines);
		do {
			if (strlen($line)<5)
				continue;
			// start recognition when a new diff block is found
			if (substr($line, 0, 4)!='--- ')
				continue;
			$p = strpos($line, "\t", 4);
			if ($p===false)	$p = strlen($line);
			$src = $this->root.substr($line, 4, $p-4);
			$line = next($lines);
			if (!isset($line)) {
				$this->msg = _PHPP_UNEXPECTED_EOF;
				return false;
			}
			if (substr($line, 0, 4)!='+++ ') {
				$this->msg = _PHPP_INVALID_DIFF;
				return false;
			}
			$p = strpos($line, "\t", 4);
			if ($p===false)	$p = strlen($line);
			$dst = $this->root.substr($line, 4, $p-4);
			
			$line = next($lines);
			if (!isset($line)) {
				$this->msg = _PHPP_UNEXPECTED_EOF;
				return false;
			}
			
			$done=0;
			while (preg_match('/@@ -(\\d+)(,(\\d+))?\\s+\\+(\\d+)(,(\\d+))?\\s+@@($)/A', $line, $m)) {
			
				if ($m[3]==='')
					$src_size = 1;
				else $src_size = (int)$m[3];
				if ($m[6]==='')
					$dst_size = 1;
				else $dst_size = (int)$m[6];
				if (!$this->_apply_diff($lines, $src, $dst,
									(int)$m[1], $src_size, (int)$m[4],
									$dst_size))
					return false;
				$done++;
				$line = next($lines);
				if ($line === FALSE)
					break 2;
			}
			if ($done==0) {
				$this->msg = _PHPP_INVALID_DIFF;
				return false;
			}
			
		} while (FALSE !== ($line = next($lines)));
		
		//NOTE: previously opened files are still cached
		return true;
	}
	
	function clearCache() {
		$this->sources = array();
		$this->destinations = array();
		$this->removals = array();
	}
	
	function applyPatch() {
		if (empty($this->destinations))
			return 0;
		$done = 0;
		$files = array_keys($this->destinations);
		foreach($files as $file) {
			$f = @fopen($file, 'w');
			if ($f===null)
				continue;
			fwrite($f, implode($this->newline, $this->destinations[$file]));
			fclose($f);
			$done++;
		}
		foreach($this->removals as $file) {
			if (@unlink($file))
				$done++;
			if (isset($this->sources[$file]))
				unset($this->sources[$file]);
		}
		$this->destinations = array(); // clear the destinations cache
		$this->removals = array();
		return $done;
	}
	
	function _apply_diff(&$lines, $src, $dst, $src_line, $src_size, $dst_line, $dst_size) {
		$src_line--;
		$dst_line--;
		$line = next($lines);
		if ($line === false) {
			$this->msg = _PHPP_UNEXPECTED_EOF;
			return false;
		}
		$source = array();		// source lines (old file)
		$destin = array();		// new lines (new file)
		$src_left = $src_size;
		$dst_left = $dst_size;
		do {
			if (!isset($line{0})) {
				$source[] = '';
				$destin[] = '';
				$src_left--;
				$dst_left--;
				continue;
			}
			if ($line{0}=='-') {
				if ($src_left==0) {
					$this->msg = sprintf(_PHPP_UNEXPECTED_REMOVE_LINE, key($lines));
					return false;
				}
				$source[] = substr($line, 1);
				$src_left--;
			} else if ($line{0}=='+') {
				if ($dst_left==0) {
					$this->msg = sprintf(_PHPP_UNEXPECTED_ADD_LINE, key($lines));
					return false;
				}
				$destin[] = substr($line, 1);
				$dst_left--;
			} else {
				if (!isset($line{1}))
					$line = '';
				else if ($line{0}=='\\') {
					if ($line=='\\ No newline at end of file')
						continue;
				} else
					$line = substr($line, 1);
				$source[] = $line;
				$destin[] = $line;
				$src_left--;
				$dst_left--;
			}
			
			if (($src_left==0) && ($dst_left==0)) {
				// now apply the patch, finally!
				if ($src_size>0) {
					$src_lines =& $this->_get_source($src);
					if (!isset($src_lines))
						return false;
				}
				if ($dst_size>0) {
					if ($src_size>0) {
						$dst_lines =& $this->_get_destin($dst, $src);
						if (!isset($dst_lines))
							return false;
						$src_bottom=$src_line+count($source);
						$dst_bottom=$dst_line+count($destin);
						
						for ($l=$src_line;$l<$src_bottom;$l++) {
							if ($src_lines[$l]!=$source[$l-$src_line]) {
								$this->msg = sprintf(_PHPP_FAILED_VERIFY, $src, $l);
								return false;
							}
						}
						array_splice($dst_lines, $dst_line, count($source), $destin);
					} else
						$this->destinations[$dst] = $destin;
				} else
					$this->removals[] = $src;
				
				return true;
			}
		} while (FALSE !== ($line = next($lines)));

		$this->msg = _PHPP_UNEXPECTED_EOF;
		return false;
	}
	
	function diff(&$latest, &$udiff) {
		$this->msg = 'Not available';
		return false;
	}

}

?>