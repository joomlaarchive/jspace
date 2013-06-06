<?php
/**
 * A repository class.
 * Contains a business logic for particular repository type.
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

jimport('jspace.repository.item');
jimport('jspace.repository.bundle');
jimport('jspace.repository.bitstream');
jimport('jspace.repository.metadata');
jimport('jspace.repository.error');
jimport('jspace.repository.filter');
jimport('jspace.repository.category');
jimport('jspace.repository.restapi');

/**
 * JSpace connector endpoint class.
 *
 * @package     JSpace
 * @subpackage  Connector
 */
abstract class JSpaceRepository extends JObject
{
	const REPOSITORY_DSPACE = 'DSpace';
	
	protected static $_repos = array();
	
	/**
	 * 
	 * @param unknown_type $type
	 * @throws Exception
	 * @return JSpaceRepository
	 */
	public static function getInstance( $options ) {
		$type = JArrayHelper::getValue($options, 'driver');
		
		if( !isset(self::$_repos[ $type ]) ) {
			$class = "JSpaceRepository" . ucfirst(strtolower($type)) . 'Repository';
			if( !class_exists($class) ) {
				//attempt import
				jimport('jspace.repository.' . strtolower($type) . '.repository' );
				jimport('jspace.repository.' . strtolower($type) . '.item' );
				jimport('jspace.repository.' . strtolower($type) . '.bundle' );
				jimport('jspace.repository.' . strtolower($type) . '.bitstream' );
				jimport('jspace.repository.' . strtolower($type) . '.metadata' );
				jimport('jspace.repository.' . strtolower($type) . '.filter' );
				jimport('jspace.repository.' . strtolower($type) . '.category' );
				jimport('jspace.repository.' . strtolower($type) . '.restapi' );
				if( !class_exists($class) ) {
					throw new Exception(JText::_('LIB_JSPACE_MISSING_REPOSITORY_CLASS') . ' ' . $class );
				}
			}
			self::$_repos[ $type ] = new $class( $options );
		}
		return self::$_repos[ $type ];
	}
	
	/**
	 * 
	 * @var string
	 */
	protected $_driver = '';
	
	/**
	 * 
	 * @var array of JSpaceRepositoryItem
	 */
	protected $_items = array();
	
	/**
	 * A base url for retrieving bundles.
	 * @var string
	 */
	protected $_baseUrl = null;
	
	/**
	 * 
	 * @var array of JSpaceRepositoryCollection
	 */
	protected $_collections = array();
	
	/**
	 * 
	 * @var array of JSpaceRepositoryCategory
	 */
	protected $_categories = array();
	
	/**
	 * 
	 * @var JSpaceMapper
	 */
	protected $_mapper = null;
	
	/**
	 * 
	 * @var JSpaceRepositoryConnector
	 */
	protected $_connector = null;
	
	/**
	 * 
	 * @var JSpaceRepositoryCache
	 */
	protected $_cache = null;
	
	/**
	 * 
	 * @var JSpaceRepositoryRestAPI
	 */
	protected $_restAPI = null;
	

	public function __construct( $options ) {
		JSpaceLogger::log('Creating repository object');
		$this->_connector = JSpaceFactory::getConnector( $options );
		$this->_driver = JArrayHelper::getValue($options, 'driver');
		$this->_baseUrl = JArrayHelper::getValue($options, "base_url");
		$this->_mapper = JArrayHelper::getValue($options, "mapper");

		JSpaceLogger::log('Driver: ' . $this->_driver);

		$cache = JArrayHelper::getValue($options, 'cache', array('enabled' => false));
		if( JArrayHelper::getValue($cache, 'enabled', false) ) {
			JSpaceLogger::log('Getting cache object for repository');
			$options = JArrayHelper::getValue($cache, 'options', null);
			$this->_cache = JSpaceFactory::getCache( $options );
		}
	}
	
	/**
	 * Remove all current error messages.
	 */
	protected function flushErrors() {
		$this->_errors = array();
	}
	
	/**
	 * Get driver name.
	 * 
	 * @return string
	 */
	public function getDriver() {
		return $this->_driver;
	}
	
	/**
	 * Get ucfirst version of driver name.
	 * 
	 * @return string
	 */
	public function getDriverUcfirst() {
		return ucfirst( strtolower( $this->_driver ) );
	}
	
	/**
	 * Get lowercase version of driver name.
	 * 
	 * @return string
	 */
	public function getDriverStrtolower() {
		return strtolower( $this->_driver );
	}
	
	/**
	 * Get connector for repository.
	 * 
	 * @return JSpaceRepositoryConnector
	 */
	public function getConnector() {
		return $this->_connector;
	}
	
	/**
	 * Get base url for downloading files (bitstreams).
	 * 
	 * @return string
	 */
	public function getBaseUrl() {
		return $this->_baseUrl;
	}
	
	/**
	 * 
	 * @return JSpaceMapper
	 */
	public function getMapper() {
		return $this->_mapper;
	}
	
	/**
	 * Add item to the repository. Now JSpaceTableItem packages itself to DSpace - here it would be changed.
	 * The package would be created in subclass to match particular archive needs.
	 *
	 * Returns the archive item id.
	 *
	 * @param JSpaceTableItem $storageItem
	 * @return mixed
	 */
	public function storeItem( $storageItem ) {
		$this->flushErrors();
		return $this->_storeItem( $storageItem );
	}
	
	/**
	 * Get one JSpaceRepositoryItem
	 * Assuming an item is always identified by some kind of id.
	 * Override protected _getItem if needed not this one.
	 *
	 * @param mixed $id
	 * @return JSpaceRepositoryItem
	 */
	public function getItem( $id ) {
		JSpaceLogger::log('Geting item id=' . $id);
		$this->flushErrors();
		
		if( !isset( $this->_items[ $id ] ) ) { 
			try {
				$this->_items[ $id ] = $this->_getItem( $id );
			}
			catch( Exception $e ) {
				throw JSpaceRepositoryError::raiseError( $this, $e );
			}
		}
		
		return $this->_items[ $id ];
	}
	
	/**
	 * Called by getItem.
	 * Can be overriden, but doesn't have to.
	 * 
	 * @param mixed $id
	 * @return JSpaceRepositoryItem
	 */
	protected function _getItem( $id ) {
		$class = "JSpaceRepository" . ucfirst(strtolower($this->_driver)) . "Item";
		return new $class($id, $this);
	}

	/**
	 * Get list of JSpaceRepositoryItem filtered by $filter values (yet to be defined).
	 * 
	 * @param JSpaceRepositoryFilter $filter
	 * @return array of JSpaceRepositoryItem
	 */
	public function getItems( $filter ) {
		$this->flushErrors();
		return $this->_getItems( $filter );
	}
	
	/**
	 * Get list of JSpaceRepositoryItem filtered by $filter values (yet to be defined). 
	 * @param JSpaceRepositoryFilter $filter
	 * @return array of JSpaceRepositoryItem
	 */
	protected function _getItems( $filter ) {
		return $filter->getItems();
	}

	
	/**
	 * 
	 * @param mixed $id
	 * @return JSpaceRepositoryCollection
	 */
	public function getCategory( $id=0 ) {
		$this->flushErrors();
		
		if( !isset( $this->_categories[ $id ] ) ) {
			try {
				$this->_categories[ $id ] = $this->_getCategory( $id );
			}
			catch( Exception $e ) {
				throw JSpaceRepositoryError::raiseError( $this, $e );
			}
		}
		
		return $this->_categories[ $id ];
	}
	
	/**
	 * 
	 * @param mixed $id
	 * @return JSpaceRepositoryCollection
	 */
	public function _getCategory( $id=0 ) {
		$class = "JSpaceRepository" . ucfirst(strtolower($this->_driver)) . "Category";
		return new $class($id, $this);
	}
	
	/**
	 * Add item to the repository. Now JSpaceTableItem packages itself to DSpace - here it would be changed. 
	 * The package would be created in subclass to match particular archive needs.
	 * 
	 * Returns the archive item id.
	 * 
	 * @param JSpaceTableItem $storageItem
	 * @return mixed 
	 */
	abstract protected function _storeItem( $storageItem );
	
	/**
	 * @return bool
	 */
	public function hasCache() {
		return !is_null( $this->_cache );
	}
	
	/**
	 * 
	 * @return JSpaceRepositoryCache
	 */
	public function getCache() {
		return $this->_cache;
	}
	
	/**
	 * 
	 * @return JSpaceRepositoryFilter
	 */
	public function createFilter( $options ) {
		return $this->_createFilter( $options );
	}
	
	/**
	 *
	 * @param array $options
	 * @return JSpaceRepositoryFilter
	 */
	protected function _createFilter( $options ) {
		$class = "JSpaceRepository" . ucfirst(strtolower($this->_driver)) . "Filter";
		return new $class( $this, $options );
	}
	
	/**
	 * 
	 * @return JSpaceRepositoryRestAPI
	 */
	public function getRestAPI() {
		if( is_null( $this->_restAPI ) ) {
			$this->_restAPI = $this->_getRestAPI();
		}
		return $this->_restAPI;
	}
	
	/**
	 * Get the rest API object.
	 * 
	 * @return JSpaceRepositoryRestAPI
	 */
	protected function _getRestAPI() {
		$class = "JSpaceRepository" . ucfirst(strtolower($this->_driver)) . "RestAPI";
		return new $class();
	}
	
	/**
	 * Helper method to get rest api response
	 * 
	 * @param string $name
	 * @param array $config
	 * @return string
	 */
	public function restCall( $name, $config=array() ) {
		$endpoint = $this->getRestAPI()->getEndpoint($name, $config);
		$connector = $this->getConnector();
		return $connector->get($endpoint);
	}
	
	/**
	 * Helper method to get rest api JSON decoded response
	 * 
	 * @param string $name
	 * @param array $config
	 * @return json_decode result
	 */
	public function restCallJSON( $name, $config=array() ) {
		return json_decode($this->restCall($name,$config));
	}
	
}