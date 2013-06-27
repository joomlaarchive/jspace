<?php
/**
 *
 * @author		$LastChangedBy: michalkocztorz $
 * @package		JSpace
 * @copyright	Copyright (C) 2011 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace component for Joomla!.

 The JSpace component for Joomla! is free software: you can redistribute it
 and/or modify it under the terms of the GNU General Public License as
 published by the Free Software Foundation, either version 3 of the License,
 or (at your option) any later version.

 The JSpace component for Joomla! is distributed in the hope that it will be
 useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with the JSpace component for Joomla!.  If not, see
 <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have
 * contributed any source code changes.
 * Name							Email
 * Michał Kocztorz				<michalkocztorz@wijiti.com>
 *
 */
defined('JPATH_PLATFORM') or die;

class JSpaceCacheManager {
	/**
	 * 
	 * @var JSpace
	 */
	protected static $_current = null;
	
	public static function getInstance() {
		if( is_null( JSpaceCacheManager::$_current ) ) {
			JSpaceCacheManager::$_current = new JSpaceCacheManager();
		}
		return JSpaceCacheManager::$_current;
	}
	
	/**
	 * Name of selected driver
	 * @var string
	 */
	protected $_selected = null;
	
	/**
	 * 
	 * @var array
	 */
	protected $_drivers = array();
	
	/**
	 * Indicates if driver was instantiated or not.
	 * Most of the times only one cache driver will be used, so there would be 
	 * no point in instantiating them all.
	 * 
	 * @var array
	 */
	protected $_loaded = array();
	
	/**
	 * Register new cache driver.
	 * @param string $name
	 * @param array $options
	 * @throws Exception
	 */
	public function registerDriver( $name, $config ) {
		JSpaceLog::add("JSpaceCacheManager::registerDriver: <$name>", JLog::DEBUG, JSpaceLog::CAT_INIT);
		if( isset( $this->_drivers[ $name ] ) ) {
			$msg = "JSpaceCacheManager::registerDriver: Driver <$name> already registered.";
			JSpaceLog::add($msg, JLog::CRITICAL, JSpaceLog::CAT_INIT);
			throw new Exception( $msg );
		}
		
		$this->_drivers[ $name ] = $config;
		$this->_loaded[ $name ] = false;
		
		/*
		 * If this is first driver registered, select it.
		 */
		if( is_null( $this->_selected ) ) {
			$this->select($name);
		}
	}
	
	/**
	 * Select cache driver. It has to be registered first.
	 * 
	 * @param string $name
	 * @throws Exception
	 */
	public function select( $name ) {
		JSpaceLog::add("JSpaceCacheManager::select: <$name>", JLog::DEBUG, JSpaceLog::CAT_INIT);
		if( isset( $this->_drivers[ $name ] ) ) {
			$this->_selected = $name;
		}
		else {
			$msg = "JSpaceCacheManager::select: Driver <$name> not found.";
			JSpaceLog::add($msg, JLog::CRITICAL, JSpaceLog::CAT_INIT);
			throw new Exception( $msg );
		}
	}
	
	/**
	 * Get cache driver (selected if name is null).
	 *  
	 * @param string $name
	 */
	public function get( $name = null ) {
		if( is_null($name) ) {
			$name = $this->_selected;
		}
		JSpaceLog::add("JSpaceCacheManager::get: <$name>", JLog::DEBUG, JSpaceLog::CAT_REPOSITORY);
		
		if( !isset($this->_loaded[ $name ]) || !isset($this->_drivers[ $name ]) ) {
			$msg = "JSpaceCacheManager::get: Driver <$name> not found.";
			JSpaceLog::add($msg, JLog::CRITICAL, JSpaceLog::CAT_INIT);
			throw new Exception( $msg );
		}
		
		if( !$this->_loaded[ $name ] ) {
			JSpaceLog::add("JSpaceCacheManager::get: get instance <$name>", JLog::DEBUG, JSpaceLog::CAT_REPOSITORY);
			$config = $this->_drivers[ $name ];
			$classPrefix = JArrayHelper::getValue($config, 'classPrefix', 'JSpaceRepositoryCache');
			$basePath = JArrayHelper::getValue($config, 'basePath', JPATH_LIBRARIES . DIRECTORY_SEPARATOR . 'jspace' . DIRECTORY_SEPARATOR . 'repository' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'jselective');
			JSpaceLog::add("JSpaceCacheManager::get: discovering classPrefix=$classPrefix basePath=$basePath", JLog::DEBUG, JSpaceLog::CAT_REPOSITORY);
			JLoader::discover($classPrefix, $basePath);
			$options = JArrayHelper::getValue($config, 'options', array('driver'=>'jselective')); //fallback to jselective if fail to get options
			$this->_drivers[ $name ] = JSpaceRepositoryCache::getInstance( $options );
			$this->_loaded[ $name ] = true;
		}
		return $this->_drivers[ $name ];
	}
	
	/**
	 * Get array of instance names.
	 */
	public function listInstances() {
		return array_keys($this->_drivers);
	}
}