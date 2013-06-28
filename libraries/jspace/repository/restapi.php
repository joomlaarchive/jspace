<?php
/**
 * Description of repository rest api.
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
 * Michał Kocztorz				<michalkocztorz@wijiti.com> 
 * 
 */
 
defined('JPATH_PLATFORM') or die;


/**
 * @package     JSpace
 * @subpackage  Repository
 */
abstract class JSpaceRepositoryRestAPI
{
	protected $_endpoints = array();
	
	/**
	 * Get endpoint object.
	 * 
	 * @param string $name
	 * @param array $config
	 * @throws Exception
	 * @return JSpaceRepositoryEndpoint
	 */
	public function getEndpoint( $name, $config=array() ) {
		JSpaceLog::add("Getting endpoint: $name", JLog::DEBUG, JSpaceLog::CAT_JREST);
		$api = $this->_getEndpointAPI( $name );
		JSpaceLog::add("RestAPI: " . print_r($api, true), JLog::DEBUG, JSpaceLog::CAT_JREST);
		JSpaceLog::add("RestAPI config: " . print_r($config, true), JLog::DEBUG, JSpaceLog::CAT_JREST);
		$urlElements = array();
		foreach( $api['urlElements'] as $key ) {
			if( isset($config[$key]) ) {
				$urlElements[] = $config[$key];
			}
			else {
				JSpaceLog::add("Required for url config element missing: $key", JLog::ERROR, JSpaceLog::CAT_JREST);
				throw new Exception(JText::_('LIB_JSPACE_ERROR_RESTAPI_GETENDPOINT_CONFIG_ERROR_URL'));
			}
		}

		$cacheGroup = $this->_getCacheGroup($api, $config);

		
		$vars = array();
		foreach( $api['vars'] as $key => $required ) {
			if( isset($config[$key]) ) {
				$vars[$key] = $config[$key];
			}
			else {
				if( $required ) {
					JSpaceLog::add("Required for url var missing: $key", JLog::ERROR, JSpaceLog::CAT_JREST);
					throw new Exception(JText::_('LIB_JSPACE_ERROR_RESTAPI_GETENDPOINT_CONFIG_ERROR_VARS'));
				}
			}
		}

		$data = array();
		foreach( $api['data'] as $key => $required ) {
			if( isset($config[$key]) ) {
				if( $key == 'data' && count($api['data'])==1 ) {//if data array has only data key, then it is passed to data variable
					$data = $config[$key];
				}
				else {
					$data[$key] = $config[$key];
				}
			}
			else {
				if( $required ) {
					JSpaceLog::add("Required data missing: $key", JLog::ERROR, JSpaceLog::CAT_JREST);
					throw new Exception(JText::_('LIB_JSPACE_ERROR_RESTAPI_GETENDPOINT_CONFIG_ERROR_DATA'));
				}
			}
		}
		
		$url = vsprintf($api['url'], $urlElements);
		$vars = (count($vars) > 0)? $vars : null ;
		
		
		$anonymous = (bool)JArrayHelper::getValue($config, 'anonymous', $api['anonymous']);
		$data = (count($data)>0) ? $data : null;
		
		JSpaceLog::add("RestAPI anonymous: " . print_r($anonymous, true), JLog::DEBUG, JSpaceLog::CAT_JREST);
		JSpaceLog::add("RestAPI setting data: " . print_r($data, true), JLog::DEBUG, JSpaceLog::CAT_JREST);
		
		//get timeout from $config or $api or set default
		$timeout = JArrayHelper::getValue($config, 'timeout', JArrayHelper::getValue($api, 'timeout', 10));
		JSpaceLog::add("Getting timeout: " . $timeout, JLog::DEBUG, JSpaceLog::CAT_JREST);

		$endpoint = new JSpaceRepositoryEndpoint($url, $vars, $anonymous, $data, $timeout);
		

		if( JArrayHelper::getValue($api, 'cache', true) ) {
			$group = $cacheGroup;
			JSpaceLog::add("Endpoint cache group: $group", JLog::DEBUG, JSpaceLog::CAT_JREST);
			$endpoint->set('group', $group);
		}
		else {
			$endpoint->set('cacheable',false);
		}
		
		JSpaceLog::add("Returning endpoint: $name", JLog::DEBUG, JSpaceLog::CAT_JREST);
		return $endpoint;
	}
	
	/**
	 * 
	 * @param string $name
	 * @param array $config
	 * @return string
	 */
	public function getCacheGroup( $name, $config=array() ) {
		$api = $this->_getEndpointAPI( $name );
		return $this->_getCacheGroup($api, $config);
	}
	
	/**
	 * 
	 * Get information from _endpoints or throw exception.
	 * 
	 * @param string $name
	 * @throws Exception
	 * @return multitype:
	 */
	protected function _getEndpointAPI( $name ) {
		if( !isset($this->_endpoints[ $name ]) ) {
			JSpaceLog::add("Requested endpoint not found: $name", JLog::CRITICAL, JSpaceLog::CAT_JREST);
			throw new Exception(JText::_('LIB_JSPACE_CRITICAL_ERROR_RESTAPI_GETENDPOINT_NO_ENDPOINT_FOUND'));
		}
		return $this->_endpoints[ $name ];
	}
	
	/**
	 * Gets defined (cache) group elements from $config.
	 * 
	 * @param array $api
	 * @param array $config
	 * @throws Exception
	 * @return array
	 */
	protected function _getCacheGroup($api, $config) {
		$groupElements = array();
		foreach(JArrayHelper::getValue($api, 'groupElements', array()) as $key ) {
			if( isset($config[$key]) ) {
				$groupElements[] = $config[$key];
			}
			else {
				JSpaceLog::add("Required for group config element missing: $key", JLog::ERROR, JSpaceLog::CAT_JREST);
				throw new Exception(JText::_('LIB_JSPACE_ERROR_RESTAPI_GETENDPOINT_CONFIG_ERROR_GROUP'));
			}
		}
		
		return vsprintf($api['group'], $groupElements);
	}
}




