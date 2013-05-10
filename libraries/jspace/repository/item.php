<?php
/**
 * A repository item class.
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

/**
 * JSpace connector endpoint class.
 *
 * @package     JSpace
 * @subpackage  Connector
 */
abstract class JSpaceRepositoryItem extends JObject
{
	/**
	 * 
	 * @var JSpaceRepository
	 */
	protected $_repository = null;
	
	/**
	 * 
	 * @var mixed
	 */
	protected $_id = null;
	
	/**
	 * Value informing if item was successfully loaded from repository
	 * 
	 * @var bool
	 */
	protected $_loaded = false;
	
	/**
	 * Array of bundles defined for item.
	 * @var array of JSpaceRepositoryBundle
	 */
	protected $_bundles = array();
	
	/**
	 * 
	 * @var array of JSpaceRepositoryMetadata
	 */
	protected $_metadata = array();
	
	/**
	 * 
	 * @var JSpaceRepositoryCollection
	 */
	protected $_collection = null;
	
	/**
	 * 
	 * @param mixed $id
	 * @param JSpaceRepository $repository
	 */
	public function __construct($id, $repository ) {
		$this->_id = $id;
		$this->_repository = $repository;
		$this->_load();
	}
	
	/**
	 * Get item's id.
	 * @return mixed
	 */
	public function getId() {
		return $this->_id;
	}
	
	/**
	 * 
	 * @return JSpaceRepository
	 */
	public function getRepository() {
		return $this->_repository;
	}
	
	/**
	 * Is item loaded correctly from repository.
	 * @return bool
	 */
	public function isLoaded() {
		return $this->_loaded;
	}
	
	/**
	 * Get url where item's files are available for download as a ZIP archive. 
	 * Files are the main files for item.
	 * 
	 * @author Michał Kocztorz
	 * @return string
	 */
	public function getPackageUrl() {
		return $this->_getPackageUrl();
	}
	
	/**
	 * Get item's bundle of given type.
	 * @param string $type
	 * @return JSpaceRepositoryBundle
	 */
	public function getBundle( $type ) {
		if( !isset( $this->_bundles[ $type ] ) ) {
			try {
				$this->_bundles[ $type ] = $this->_getBundle( $type );
			}
			catch( Exception $e ) {
				//rethrow exception and set error
				throw JSpaceRepositoryError::raiseError( $this, $e );
			}
		}
		return $this->_bundles[ $type ];
	}
	
	/**
	 * 
	 * @param string $type
	 * @return JSpaceRepositoryBundle | bool
	 */
	public function getBundleIfExists( $type ) {
		try {
			return $this->getBundle( $type );
		}
		catch( Exception $e ) {
			return false;
		}
	}

	/**
	 * Methods we decide to force on all items in all repositories
	 */
	abstract protected function _getPackageUrl();
	
	/**
 	 * Created bundle objects returned by this method will be cached by getBundle( $type ).
 	 * To be overriden if necessary.
 	 * 
 	 * @throws Exception
	 * @param string $type
	 * @return JSpaceRepositoryBundle
	 */
	protected function _getBundle( $type ) {
		$class = 'JSpaceRepository' . $this->getRepository()->getDriverUcfirst() . 'Bundle';
		return new $class( $this, $type );
	}
	
	/**
	 * Get array of bundles indexed by bundle type.
	 * @return array
	 */
	public function getBundles() {
		return $this->_getBundles();
	}
	
	/**
	 * 
	 * @return array
	 */
	abstract protected function _getBundles();
	
	/**
	 * 
	 * @param mixed $key
	 * @return JSpaceRepositoryMetadata
	 */
	public function getMetadata( $key=null, $supressExceptions=true ) {
		if( is_null( $key ) ) {
			return $this->_getMetadataArray();
		}
		if( !isset( $this->_metadata[ $key ] ) ) {
			try {
				$this->_metadata[ $key ] = $this->_getMetadata( $key );
			}
			catch( Exception $e ) {
				$e = JSpaceRepositoryError::raiseError( $this, $e );
				if( $supressExceptions ) {
					$this->_metadata[ $key ] = JText::sprintf("COM_JSPACE_REPOSITORY_METADATA_UNKNOWN", $key);
				}
				else {
					//rethrow exception and set error
					throw $e;
				} 
			}
		}
		
		return $this->_metadata[ $key ];
	}
	
	/**
	 *
	 * @param mixed $key
	 * @return JSpaceRepositoryMetadata
	 */
	protected function _getMetadata( $key ) {
		$class = 'JSpaceRepository' . $this->getRepository()->getDriverUcfirst() . 'Metadata';
		return new $class( $this, $key );
	}
	
	abstract protected function _getMetadataArray(); 
	
// 	/**
// 	 * @deprecated
// 	 * @return JSpaceRepositoryCollection
// 	 */
// 	public function getCollection() {
// 		return $this->_getCollection();
// 	}
	
// 	abstract protected function _getCollection();
	
	/**
	 * Get category item is in.
	 * 
	 * @return JSpaceRepositoryCategory
	 */
	public function getCategory() {
		return $this->_getCategory();
	}
	
	/**
	 * Get category item is in.
	 * 
	 * @return JSpaceRepositoryCategory
	 */
	abstract public function _getCategory();
	
	/**
	 * Translates JSpace metadata key with crosswalk to crosswalk key (e.g. 'author' -> 'dc.author')
	 * @param string $key
	 */
	public function getCrosswalkValue( $key ) {
		return $this->getRepository()->getMapper()->getCrosswalk()->_( $key );
	}

	/**
	 * Load item based on $this->id from $this->repository
	 */
	abstract protected function _load();
}