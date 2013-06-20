<?php
/**
 * A DSpace connector class.
 * 
 * @package		JSpace
 * @subpackage	Repository
 * @copyright	Copyright (C) 2012 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace library for Joomla!.

   The JSpace library for Joomla! is free software: you can redistribute it 
   and/or modify it under the terms of the GNU General Public License as 
   published by the Free Software Foundation, either version 3 of the License, 
   or (at your option) any later version.

   The JSpace library for Joomla! is distributed in the hope that it will be 
   useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with the JSpace library for Joomla!.  If not, see 
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 * Name							Email
 * Hayden Young					<haydenyoung@wijiti.com> 
 * 
 */

/**
 * @deprecated
 * @see JSpaceRepositoryDspaceConnector
 */
class JSpaceRepositoryConnectorDSpace extends JSpaceRepositoryConnector
{
	public function execute($endpoint, $action, $useCache = false)
	{
		JSpaceLogger::log('Executing endpoint ' . $endpoint->get('url'));
		$response = null;
		
		if( $useCache ) {
			JSpaceLogger::log('Using cache');
			//create a cache key
			$repository = JSpaceFactory::getRepository();
			if( $repository->hasCache() ) {
				JSpaceLogger::log('Repository uses cache');
				$cacheKey = JSpaceFactory::getCacheKey($endpoint, JArrayHelper::getValue($this->options, 'url'));
				$key = (string) $cacheKey;
// 				$key = md5( JArrayHelper::getValue($this->options, 'url') . serialize( $endpoint ) );
				JSpaceLogger::log('Cache key: ' . $key);
				$cachedResponse = $repository->getCache()->get( $cacheKey );
				if( !is_null( $cachedResponse ) ) {
					JSpaceLogger::log('Found in cache. Returning.');
					return $cachedResponse;
				}
				JSpaceLogger::log('Not found in cache.');
			}
		}
		
		try {
			$url = new JURI(JArrayHelper::getValue($this->options, 'url').'/'.$endpoint->get('url'));

			if( !is_null($endpoint->get('vars')) ){
				foreach ($endpoint->get('vars') as $var=>$value) {
					$url->setVar($var, $value);	
				}
			}
			
			JSpaceLogger::log($url);
			JSpaceLogger::log(print_r($endpoint->get('vars'),true));
			
			$client = new JRestClient((string)$url, $action);
			
			
            if (!$endpoint->get('anonymous')) {
				JSpaceLogger::log("Not anonymous");
                $client->setUsername(JArrayHelper::getValue($this->options, 'username'));
                $client->setPassword(JArrayHelper::getValue($this->options, 'password'));
            }
            else {
				JSpaceLogger::log("Anonymous");
            }
            
			JSpaceLogger::log("Request data: " . print_r($endpoint->get('data'),true));
			if (!is_null($endpoint->get('data'))) {
                $client->setRequestBody($endpoint->get('data'));
			}
			
			$client->execute();

			$info = $client->getResponseInfo();
			$code = intval(JArrayHelper::getValue($info, "http_code", 0));
			


// 			$options = array('text_file'=>'restclient.php');
// 			$log = new JLoggerFormattedText($options);
// 			$log->addEntry(new JLogEntry("RESPONSE:") );
// 			$log->addEntry(new JLogEntry($code) );
// 			$log->addEntry(new JLogEntry($client->getResponseBody()) );
// 			$log->addEntry(new JLogEntry(print_r($this->responseInfo,true)) );

			switch ($code) {
				case 200:
				case 201:
					$response = $client->getResponseBody();
					JSpaceLogger::log($response);
					if( $useCache && $repository->hasCache() ) {
						JSpaceLogger::log('Setting cache. Key: ' . $key);
						$repository->getCache()->set($cacheKey, $response);
					}
					break;
					
				case 204:
					break;
					
				default:
					JSpaceLogger::log($response);
					$msg = JText::_('JLIB_JSPACE_CONNECTION_ERROR_'.$code);
					throw new Exception($msg, $code);
					break;
			}
			
			$client->flush();
		} catch (Exception $e) {
			JLog::add($e->getCode().": ".$e->getMessage(), JLog::ERROR, 'library');
			throw $e;
		}
		
		return $response;
	}
}