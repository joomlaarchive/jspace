<?php
defined('_JEXEC') or die;

/**
 * Adapted from the work of Hiroaki Kawai <hiroaki.kawai@gmail.com>. Original Github project at 
 * https://github.com/ned14/ClamAV-Plugin-for-PKP-Open-Journal-Systems.
 * 
 * @package     JSpace
 * @subpackage  ClamAV
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

class JSpaceClamAVClient
{
	private $_hostname;
	private $_port;
	private $_timeout;
 
	/**
	 * Initiates an instance of the JSpaceClamAVClient class.
	 *
	 * @param  string  $hostname  hostname, tcp://hostname or unix://path
	 * @param  int     $port      This value will be ignored with unix domain socket.
	 * @param  int     $timeout   Timeout seconds. default is default_socket_timeout ini value.
	 */
	public function __construct($hostname = '127.0.0.1', $port = 3310, $timeout = null)
	{
		$this->_hostname = $hostname;
		$this->_port = $port;
	
		if (substr($hostname,0,7) == 'unix://')
		{
			$this->_port = -1;
		}
		
		$this->_timeout = $timeout;
	
		if ($timeout === null)
		{
			$this->_timeout = ini_get("default_socket_timeout");
		}
	}
	
	/**
	 * Opens a socket to the configured Clam AV server.
	 *
	 * @return  resource  An open file handle.
	 */
	private function _open()
	{
		if ($f = fsockopen($this->_hostname, $this->_port, $errno, $errstr, $this->_timeout)) 
		{
			return $f;
		}

		throw new InvalidArgumentException($errstr.' ('.$errno.')', E_USER_ERROR);
	}
	
	/**
	 * Reads the output from the configured Clam AV server.
	 * 
	 * @param  resource  $handle  The opened file handle.  
	 */
	private function _read($handle)
	{
		$contents = '';
		
		while (($buffer = fread($handle, 8192)) !== false)
		{
			if(!strlen($buffer))
			{ 
				break;
			}

			$contents .= $buffer;
		}

		$array = explode("\0", $contents, 2);

		if(count($array) == 2)
		{
			return $array[0];
		}
		
		throw new InvalidArgumentException('clamd response is not NULL terminated.', E_USER_ERROR);
	}
 
	/**
	 * Pings the Clam AV server.
	 * 
	 * @return  string  "PONG" on success or false on failure.
	 */
	public function ping()
	{
		if ($handle = $this->_open())
		{
			fwrite($handle, "zPING\0");
			$ping = $this->_read($handle);
			fclose($handle);
			
			return $ping;
		}
		
		return false;
	}
 
	/**
	 * Requests version information from the Clam AV server.
	 * 
	 * @return  string  Version information. E.g. "ClamAV 0.95.3/10442/Wed Feb 24 07:09:42 2010".
	 */
	function version()
	{
		if ($handle = $this->_open())
		{
			fwrite($handle, "zVERSION\0");
			$version = $this->_read($handle);
			fclose($handle);
			
			return $version;
		}
		
		return false;
	}
 
	/**
	 * Scan a file or directory.
	 *
	 * Only a file can be scanned when using INSTREAM.
	 *
	 * @param   string  $path  Absolute path (file or directory).
	 * @param   string  $mode  One of SCAN/RAWSCAN/CONTSCAN/MULTISCAN/INSTREAM. Default is "MULTI".
	 * 
	 * @return  string  "path: OK" will be returned if OK. false on failure.
	 */
	function scan($path, $mode='MULTI')
	{
		if (!in_array($mode, array('','RAW','CONT','MULTI', 'INSTREAM')))
		{
			throw new InvalidArgumentException("invalid mode ".$mode, E_USER_ERROR);
			return false;
		}
		
		if ($mode == "INSTREAM")
		{
			$reader = fopen($path, "rb");

			if($writer = $this->_open())
			{
				fwrite($writer, "zINSTREAM\0");
				
				while (!feof($reader))
				{
					$data = fread($reader, 8192);
					fwrite($writer, pack("N",strlen($data)).$data);
				}
				
				fclose($reader);

				fwrite($writer, pack("N",0)); // chunk termination
				$result = $this->_read($writer);
				fclose($writer);
				
				return $result;
			}
			
			return false;
		}
		
		if ($handle = $this->_open())
		{
			fwrite($handle, "z".$mode."SCAN ".$path."\0");
			$result = $this->_read($handle);
			fclose($handle);
		
			return $result;
		}
		
		return false;
	}
	
	/**
	* issue STATS command
	* @return string The status information of clamd.
	*/
	function stats()
	{
		if ($handle = $this->_open())
		{
			fwrite($handle, "zSTATS\0");
			$stats = $this->_read($handle);
			fclose($handle);
			
			return $stats;
		}
		
		return false;
	}
}