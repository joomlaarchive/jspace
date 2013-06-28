<?php
/**
 * A generic connector class.
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
 
defined('JPATH_PLATFORM') or die;

/**
 * JSpace connector interface.
 *
 * @package     JSpace
 * @subpackage  Connector
 */
interface JSpaceConnectable
{	
	public static function isRESTAvailable();
	
	public function ping();
	
	/**
	 * A convenience method for executing a get request against the 
	 * repository's REST API.
	 * 
	 * Any exceptions produced by the underlying REST API will be handled 
	 * within this method. Messages are output to Joomla's error log. 
	 *
	 * @param JSpaceEndpoint $endpoint The REST endpoint to call.
	 * @param bool Flag if execute should use repository cache object for results.
	 *
	 * @return  mixed A REST response or null if no response is available.
	 */
	public function get( $endpoint, $useCache = true);
	
	/**
	 * A convenience method for executing a post request against the 
	 * repository's REST API.
	 * 
	 * Any exceptions produced by the underlying REST API will be handled 
	 * within this method. Messages are output to Joomla's error log.
	 * 
	 * @param JSpaceEndpoint $endpoint The REST endpoint to call.
	 * 
	 * @return  bool True if the post is successful, false otherwise.
	 */
	public function post($endpoint);
	
	/**
	 * A convenience method for executing a put request against the 
	 * repository's REST API.
	 *
	 * Any exceptions produced by the underlying REST API will be handled 
	 * within this method. Messages are output to Joomla's error log.
	 *
	 * @param JSpaceEndpoint $endpoint The REST endpoint to call.
	 *    
	 * @return  bool True if the put is successful, false otherwise.
	 */
	public function put($endpoint);
	
	/**
	 * A convenience method for executing a delete request against the 
	 * repository's REST API.
	 *
	 * Any exceptions produced by the underlying REST API will be handled 
	 * within this method. Messages are output to Joomla's error log.
	 
	 * @param JSpaceEndpoint $endpoint The REST endpoint to call.
	 * 
	 * @return  bool True if the delete is successful, false otherwise.
	 */
	public function delete($endpoint);
}

jimport('jrest.client.client');

/**
 * JSpace base connector class.
 *
 * @package     JSpace
 * @subpackage  Connector
 */
abstract class JSpaceRepositoryConnector implements JSpaceConnectable
{
	protected $options;
	
	protected $client;
	
	public function __construct($options = array())
	{
		JLog::addLogger(array('text_file'=>'jspace.php'), JLog::ALL, array('library'));

		$lang = JFactory::getLanguage();
		$lang->load('lib_jspace', JPATH_ROOT);
		
		$this->options = $options;
	}
	
	/**
	 * 
	 * @param array $options
	 * @throws RuntimeException
	 * @return JSpaceRepositoryConnector
	 */
	public static function getInstance($options = array())
    {
    	if (!self::isRESTAvailable()) {
			throw new RuntimeException('Unable to load JRest API.');
		}
		
		$class = JSpaceRepositoryDriver::getInstance( JArrayHelper::getValue($options, 'driver') )->getClassName( JSpaceRepositoryDriver::CLASS_CONNECTOR );		
		if (!class_exists($class)) {
			throw new RuntimeException(sprintf('Unable to load repository driver: %s', $options['driver']));
		}
		
		try {
			$instance = new $class($options);
		} catch (RuntimeException $e) {
			throw new RuntimeException(sprintf('Unable to connect to the repository: %s', $e->getMessage()));
		}

		return $instance;
    }
    
    /**
     * Expose read only options for connection object.
     * 
     */
    public function getOptions() {
    	return $this->options;
    }
	
	public static function isRESTAvailable()
	{
		if (jimport('jrest.client.client')) {
			if (JRestClient::isCURLInstalled()) {			
		    	return true;
			}
		}
		
		return false;
	}
	
	public function ping()
	{
		
	}
	
	public function getRepositoryUrl() {
		return JArrayHelper::getValue($this->options, 'url');
	}

	/**
	 * (non-PHPdoc)
	 * @see JSpaceConnectable::get()
	 */
	public function get($endpoint, $useCache = true)
	{
		return $this->execute($endpoint, 'get', $useCache);
	}

	/**
	 * (non-PHPdoc)
	 * @see JSpaceConnectable::post()
	 */
	public function post($endpoint)
	{
		return $this->execute($endpoint, 'post');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see JSpaceConnectable::put()
	 */
	public function put($endpoint)
	{
		return $this->execute($endpoint, 'put');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see JSpaceConnectable::delete()
	 */
	public function delete($endpoint)
	{
		return $this->execute($endpoint, 'delete');
	}
	
	/**
	 * A "catch-all" method for executing requests against the REST API. 
	 * 
	 * @param JSpaceRepositoryEndpoint $endpoint
	 * @param string $action Can be one of the following; get, post, put, 
	 * delete
	 * @param $useCache Flag if execute should use repository cache object for results.
	 * @throws Exception If a valid connection cannot be made with the 
	 * repository.
	 */
	public abstract function execute($endpoint, $action, $useCache = false);
	
	protected function _execute($endpoint, $action, $useCache = false) {
		JSpaceLog::add('Executing endpoint ' . $endpoint->get('url'), JLog::DEBUG, JSpaceLog::CAT_CONNECTOR);
		$response = null;
		
		if( $useCache ) {
			JSpaceLog::add('Using cache', JLog::DEBUG, JSpaceLog::CAT_CONNECTOR);
			//create a cache key
			$repository = JSpaceFactory::getRepository();
			if( $repository->hasCache() ) {
				JSpaceLog::add('Repository uses cache', JLog::DEBUG, JSpaceLog::CAT_CONNECTOR);
				$cacheKey = JSpaceFactory::getCacheKey($endpoint, JArrayHelper::getValue($this->options, 'url'));
				$key = (string) $cacheKey;
				// 				$key = md5( JArrayHelper::getValue($this->options, 'url') . serialize( $endpoint ) );
				JSpaceLog::add('Cache key: ' . $key, JLog::DEBUG, JSpaceLog::CAT_CONNECTOR);
				$cachedResponse = $repository->getCache()->get( $cacheKey );
				if( !is_null( $cachedResponse ) ) {
					JSpaceLog::add('Found in cache. Returning.', JLog::DEBUG, JSpaceLog::CAT_CONNECTOR);
					return $cachedResponse;
				}
				JSpaceLog::add('Not found in cache.', JLog::DEBUG, JSpaceLog::CAT_CONNECTOR);
			}
		}
		
		try {
			$url = new JURI(JArrayHelper::getValue($this->options, 'url').'/'.$endpoint->get('url'));
		
			if( !is_null($endpoint->get('vars')) ){
				foreach ($endpoint->get('vars') as $var=>$value) {
					$url->setVar($var, $value);
				}
			}
				
			JSpaceLog::add($url, JLog::DEBUG, JSpaceLog::CAT_CONNECTOR);
				
			$client = new JRestClient((string)$url, $action);
				
				
			if (!$endpoint->get('anonymous')) {
				JSpaceLog::add("Not anonymous", JLog::DEBUG, JSpaceLog::CAT_CONNECTOR);
				$client->setUsername(JArrayHelper::getValue($this->options, 'username'));
				$client->setPassword(JArrayHelper::getValue($this->options, 'password'));
			}
			else {
				JSpaceLog::add("Anonymous", JLog::DEBUG, JSpaceLog::CAT_CONNECTOR);
			}
		
			JSpaceLog::add("Request data: " . print_r($endpoint->get('data'),true), JLog::DEBUG, JSpaceLog::CAT_CONNECTOR);
			if (!is_null($endpoint->get('data'))) {
				$client->setRequestBody($endpoint->get('data'));
			}
				
			$client->setTimeout( $endpoint->get('timeout') );
				
			$client->execute();
		
			$info = $client->getResponseInfo();
			$code = intval(JArrayHelper::getValue($info, "http_code", 0));
		
			switch ($code) {
				case 200:
				case 201:
					$response = $client->getResponseBody();
					JSpaceLog::add($response, JLog::DEBUG, JSpaceLog::CAT_CONNECTOR);
					if( $useCache && $repository->hasCache() ) {
						JSpaceLog::add('Setting cache. Key: ' . $key, JLog::DEBUG, JSpaceLog::CAT_CONNECTOR);
						$repository->getCache()->set($cacheKey, $response);
					}
					break;
						
				case 204:
					break;
						
				default:
					JSpaceLog::add($response, JLog::DEBUG, JSpaceLog::CAT_CONNECTOR);
					$msg = JText::_('JLIB_JSPACE_CONNECTION_ERROR_'.$code);
					JSpaceLog::add($msg, JLog::ERROR, JSpaceLog::CAT_CONNECTOR);
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