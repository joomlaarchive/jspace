<?php
/**
 * A RESTful API client.
 * 
 * @package		JRest
 * @copyright	Copyright (C) 2011-2012 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JRest library for Joomla!.

   The JRest library for Joomla! is free software: you can redistribute it 
   and/or modify it under the terms of the GNU General Public License as 
   published by the Free Software Foundation, either version 3 of the License, 
   or (at your option) any later version.

   The JRest library for Joomla! is distributed in the hope that it will be 
   useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with the JRest library for Joomla!.  If not, see 
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 * Name							Email
 * Hayden Young					<haydenyoung@wijiti.com> 
 * 
 */

class JRestClient
{
	protected $url;
	protected $verb;
	protected $requestBody;
	protected $requestLength;
	protected $username;
	protected $password;
	protected $acceptType;
	protected $responseBody;
	protected $responseInfo;
	protected $timeout;
	
	public function __construct ($url = null, $verb = 'GET', $requestBody = null)
	{
		JSpaceLog::add('Constructing JRestClient for: (' . $verb . ') ' . $url , JLog::DEBUG, JSpaceLog::CAT_JREST);
		$this->url				= $url;
		$this->verb				= strtoupper($verb);
		$this->requestBody		= $requestBody;
		$this->requestLength	= 0;
		$this->username			= null;
		$this->password			= null;
		$this->acceptType		= 'application/json';
		$this->responseBody		= null;
		$this->responseInfo		= null;
		$this->timeout			= 10;
	}
	
	public function flush ()
	{
		$this->requestBody		= null;
		$this->requestLength	= 0;
		$this->verb				= 'GET';
		$this->responseBody		= null;
		$this->responseInfo		= null;
	}
	
	public function execute ()
	{
		JSpaceLog::add('JRestClient Executing' , JLog::DEBUG, JSpaceLog::CAT_JREST);
		$ch = curl_init();
		$this->setAuth($ch);
		
		try
		{
			switch (strtoupper($this->verb))
			{
				case 'GET':
					$this->executeGet($ch);
					break;
				case 'POST':
					$this->executePost($ch);
					break;
				case 'PUT':
					$this->executePut($ch);
					break;
				case 'DELETE':
					$this->executeDelete($ch);
					break;
				default:
					$msg = 'Current verb (' . $this->verb . ') is an invalid REST verb.';
					JSpaceLog::add($msg, JLog::ERROR, JSpaceLog::CAT_JREST);
					throw new InvalidArgumentException( $msg );
			}
		}
		catch (InvalidArgumentException $e)
		{
			curl_close($ch);
			throw $e;
		}
		catch (Exception $e)
		{
			curl_close($ch);
			throw $e;
		}
		
	}
	
	protected function executeGet ($ch)
	{		
		$this->doExecute($ch);	
	}
	
	protected function executePost ($ch)
	{		
		
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);
		$this->doExecute($ch);	
	}
	
	protected function executePut ($ch)
	{
		if (!is_string($this->requestBody))
		{
			$this->buildPostBody();
		}
		
		$this->requestLength = strlen($this->requestBody);
		
		/** use a max of 256KB of RAM before going to disk */
		$fh = fopen('php://temp/maxmemory:256000', 'rw');
		fwrite($fh, $this->requestBody);
		fseek($fh, 0);
		
		
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_INFILE, $fh);
		curl_setopt($ch, CURLOPT_INFILESIZE, $this->requestLength);
		curl_setopt($ch, CURLOPT_PUT, true);
		
		$this->doExecute($ch);
		
		fclose($fh);
	}
	
	protected function executeDelete ($ch)
	{
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		
		$this->doExecute($ch);
	}
	
	protected function doExecute (&$curlHandle)
	{
		$this->setCurlOpts($curlHandle);
		$this->responseBody = curl_exec($curlHandle);
		$this->responseInfo	= curl_getinfo($curlHandle);
		
		$headerSent = curl_getinfo($curlHandle, CURLINFO_HEADER_OUT ); // request headers
		JSpaceLog::add("Headers sent: " . $headerSent, JLog::DEBUG, JSpaceLog::CAT_JREST);
		
		curl_close($curlHandle);
	}
	
	protected function setCurlOpts (&$curlHandle)
	{
		curl_setopt($curlHandle, CURLINFO_HEADER_OUT, true); // enable tracking

		curl_setopt($curlHandle, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($curlHandle, CURLOPT_URL, $this->url);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
	}
	
	protected function setAuth (&$curlHandle)
	{
		if ($this->username != null && $this->password != null)
		{
			JSpaceLog::add('JRestClient Auth: setting' , JLog::DEBUG, JSpaceLog::CAT_JREST);
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array ("Expect:", "user:".$this->username, "pass:".$this->password ));
            curl_setopt($curlHandle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		}
		else {
			JSpaceLog::add('JRestClient Auth: anonymous' , JLog::DEBUG, JSpaceLog::CAT_JREST);
			curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array("Expect:"));
		}
	}
	
	public function getRequestBody()
	{
		return $this->requestBody;
	}
	
	public function setRequestBody($requestBody)
	{
		JSpaceLog::add('Setting request body: ' . $requestBody , JLog::DEBUG, JSpaceLog::CAT_JREST);
		$this->requestBody = $requestBody;
	}
	
	public function getAcceptType ()
	{
		return $this->acceptType;
	} 
	
	public function setAcceptType ($acceptType)
	{
		$this->acceptType = $acceptType;
	} 
	
	public function getPassword ()
	{
		return $this->password;
	} 
	
	public function setPassword ($password)
	{
		JSpaceLog::add('Setting password', JLog::DEBUG, JSpaceLog::CAT_JREST);
		$this->password = $password;
	} 
	
	public function getResponseBody ()
	{
		return $this->responseBody;
	} 
	
	public function getResponseInfo ()
	{
		return $this->responseInfo;
	} 
	
	public function getUrl ()
	{
		return $this->url;
	} 
	
	public function setUrl ($url)
	{
		$this->url = $url;
	} 
	
	public function getUsername ()
	{
		return $this->username;
	} 
	
	public function setUsername ($username)
	{
		JSpaceLog::add('Setting username: ' . $username, JLog::DEBUG, JSpaceLog::CAT_JREST);
		$this->username = $username;
	} 
	
	public function getVerb ()
	{
		return $this->verb;
	} 
	
	public function setVerb ($verb)
	{
		$this->verb = $verb;
	}
	
	public function setTimeout( $timeout ) {
		JSpaceLog::add("Setting timeout: " . $timeout, JLog::DEBUG, JSpaceLog::CAT_JREST);
		$this->timeout = $timeout;
	}
	
	public static function isCURLInstalled()
	{
		if (extension_loaded('curl')) {
			JSpaceLog::add("JRestClient CURL found", JLog::DEBUG, JSpaceLog::CAT_JREST);
			return true;
		} else {
			JSpaceLog::add("JRestClient CURL MISSING", JLog::ERROR, JSpaceLog::CAT_JREST);
			return false;
		}
	}
}