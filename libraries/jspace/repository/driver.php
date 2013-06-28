<?php
/**
 * Object describing a driver.
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
class JSpaceRepositoryDriver {
	const CLASS_REPOSITORY 	= 'Repository';
	const CLASS_ITEM 		= 'Item';
	const CLASS_BUNDLE 		= 'Bundle';
	const CLASS_BITSTREAM 	= 'Bitstream';
	const CLASS_CATEGORY 	= 'Category';
	const CLASS_METADATA 	= 'Metadata';
	const CLASS_RESTAPI 	= 'RestAPI';
	const CLASS_CONNECTOR 	= 'Connector';
	
	/**
	 * 
	 * @var array
	 */
	protected static $_drivers = array();
	
	public static function registerDriver( $driver, $options ) {
		if( isset( JSpaceRepositoryDriver::$_drivers[ $driver ] ) ) {
			$msg = "Driver <$driver> was already registered.";
			JSpaceLog::add($msg, JLog::CRITICAL, JSpaceLog::CAT_INIT);
			throw new Exception( $msg );
		}
		JSpaceRepositoryDriver::$_drivers[ $driver ] = new JSpaceRepositoryDriver( $driver, $options );
	}
	
	public function hasInstance( $driver ) {
		return isset( JSpaceRepositoryDriver::$_drivers[ $driver ] );
	}
	
	/**
	 * 
	 * @param string $driver
	 * @throws Exception
	 * @return JSpaceRepositoryDriver
	 */
	public static function getInstance( $driver ) {
		JSpaceLog::add('JSpaceRepositoryDriver: getInstance ' . $driver, JLog::DEBUG, JSpaceLog::CAT_REPOSITORY);
		if( !isset( JSpaceRepositoryDriver::$_drivers[ $driver ] ) ) {
			$msg = "Requested driver <$driver> was not registered.";
			JSpaceLog::add($msg, JLog::CRITICAL, JSpaceLog::CAT_REPOSITORY);
			throw new Exception( $msg );
		}
		return JSpaceRepositoryDriver::$_drivers[ $driver ];
	}
	
	/**
	 * List all the drivers registered.
	 * 
	 * @return array
	 */
	public static function listDriverKeys() {
		return array_keys( JSpaceRepositoryDriver::$_drivers );
	}
	
	/**
	 * 
	 * @var string
	 */
	protected $_driver;
	
	/**
	 * 
	 * @var string
	 */
	protected $_configXmlPath;
	
	/**
	 * 
	 * @var string
	 */
	protected $_classPrefix;
	
	/**
	 * Base url for repository classes.
	 * @var string
	 */
	protected $_basePath;
	
	/**
	 * Array of filter objects.
	 * @var array
	 */
	protected $_filters = null;
	
	public function __construct( $driver, $options ) {
		$this->_driver = $driver;
		$this->_configXmlPath = JArrayHelper::getValue($options, 'configXmlPath', '');
		$this->_basePath = JArrayHelper::getValue($options, 'basePath', '');
		$this->_classPrefix = JArrayHelper::getValue($options, 'classPrefix', '');
		if( !JFile::exists($this->_configXmlPath) ) {
			$msg = "Config XML file not found " . $this->_configXmlPath;
			JSpaceLog::add($msg, JLog::CRITICAL, JSpaceLog::CAT_INIT);
			throw new Exception( $msg );
		}
		if( !JFolder::exists($this->_basePath) ) {
			$msg = "Classes folder not found " . $this->_basePath;
			JSpaceLog::add($msg, JLog::CRITICAL, JSpaceLog::CAT_INIT);
			throw new Exception( $msg );
		}
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
	 * Get path to config xml.
	 * 
	 * @return string
	 */
	public function getConfigXmlPath() {
		return $this->_configXmlPath;
	}
	
	/**
	 * Build class name for repository classes, checkes if class exists. If not tries to discover and checkes again.
	 * 
	 * @throws Exception
	 * @param string $class
	 * @return string
	 */
	public function getClassName( $class ) {
		$class = 'JSpaceRepository' . $this->getDriverUcfirst() . $class;
		JSpaceLog::add("JSpaceRepositoryDriver: Testing if class $class exists.", JLog::DEBUG, JSpaceLog::CAT_REPOSITORY);
		if( !class_exists($class) ) {
			$this->discover();
			if( !class_exists($class) ) {
				JSpaceLog::add("JSpaceRepositoryDriver: Load attempt failed. Class $class not found.", JLog::CRITICAL, JSpaceLog::CAT_REPOSITORY);
				throw new Exception(JText::_('LIB_JSPACE_MISSING_REPOSITORY_CLASS') . ' ' . $class );
			}
		}
		return $class;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getClassPrefix() {
		return $this->_classPrefix;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getBasePath() {
		return $this->_basePath;
	}
	
	/**
	 * Discover repository classes using JLoader.
	 * 
	 * @return void
	 */
	public function discover() {
		JSpaceLog::add('JSpaceRepositoryDriver: discover classes', JLog::DEBUG, JSpaceLog::CAT_REPOSITORY);
		JLoader::discover($this->getClassPrefix(), $this->getBasePath());
	}
	
/*
 * Filter functionaity.
 */
	/**
	 * Get instance of filter object.
	 * 
	 * @param string $type
	 * @param array $options
	 */
	public function getFilter( $type, $options ) {
		JSpaceLog::add( 'JSpaceRepositoryDriver: getFilter : ' . $type, JLog::DEBUG, JSpaceLog::CAT_REPOSITORY );
		//test if filters need to be registered
		if( is_null( $this->_filters ) ) {
			$this->registerFilters();
		}
		
		$typeLower = strtolower($type);
		
		if( isset( $this->_filters[ $typeLower ] ) ) {
			return $this->_filters[ $typeLower ];
		}
		else {
			$msg = "getFilter $typeLower failed";
			JSpaceLog::add($msg, JLog::CRITICAL, JSpaceLog::CAT_REPOSITORY);
			throw new Exception( $msg );
		}
	}
	
	/**
	 * Register all filters for this driver. 
	 * 
	 */
	protected function registerFilters() {
		JSpaceLog::add( 'JSpaceRepositoryDriver: registerFilters', JLog::DEBUG, JSpaceLog::CAT_REPOSITORY );
		$this->_filters = array();
		
		$path = JPATH_LIBRARIES . DIRECTORY_SEPARATOR . 'jspace' . DIRECTORY_SEPARATOR . 'repository' . DIRECTORY_SEPARATOR . 'dspace' . DIRECTORY_SEPARATOR . 'filters' . DIRECTORY_SEPARATOR;
		$config = array(
			'classPrefix'	=> 'JSpaceRepositoryDspaceFilters',
			'basePath'		=> $path,
			'driver'		=> 'DSpace',
		);
		$this->registerFilter('popular', $config);
		$this->registerFilter('latest', $config);
		
		/*
		 * Trigger event. 
		 */
		$filters = JSpaceFactory::getJSpace()->trigger('onJSpaceRegisterFilters');
		foreach( $filters as $list ) {
			foreach( $list as $key => $options ) {
				try {
					JSpaceLog::add('registerFilters: registering ' . $key, JLog::DEBUG, JSpaceLog::CAT_REPOSITORY);
					$this->registerFilter($key, $options);
				}
				catch( Exception $e ) {
					JSpaceLog::add('registerFilters: registering ' . $key . ' failed with exception: ' . $e->getMessage(), JLog::ERROR, JSpaceLog::CAT_REPOSITORY);
				}
			}
		}
		JSpaceLog::add( 'JSpaceRepositoryDriver: registerFilters done', JLog::DEBUG, JSpaceLog::CAT_REPOSITORY );
	}
	
	/**
	 * Register filter by type.
	 * 
	 * @param string $type
	 * @param array $config
	 */
	protected function registerFilter( $type, $config ) {
		JSpaceLog::add( "JSpaceRepositoryDriver: registerFilter <$type>", JLog::DEBUG, JSpaceLog::CAT_REPOSITORY );

		$driver = JArrayHelper::getValue($config, 'driver');
		if( $driver != $this->getDriver() ) {
			JSpaceLog::add( "registerFilter driver for another repository <$driver>, quitting ", JLog::DEBUG, JSpaceLog::CAT_REPOSITORY );
			return;
		}
		$typeLower = strtolower($type);
		$typeUCfirst = ucfirst($typeLower);
		
		if( isset( $this->_filters[ $typeLower ] ) ) {
			JSpaceLog::add("Filter <$typeLower> already registered.", JLog::ERROR, JSpaceLog::CAT_REPOSITORY);
			return;
		}
		
		$classPrefix = JArrayHelper::getValue($config, 'classPrefix', '');
		JLoader::discover($classPrefix, JArrayHelper::getValue($config, 'basePath', '') );
		$className = $classPrefix . $typeUCfirst;
		if( !class_exists($className) ) {
			JSpaceLog::add("Registering filter <$typeLower> failed. Class $className doesn't exist", JLog::ERROR, JSpaceLog::CAT_REPOSITORY);
			return;
		}
		$this->_filters[ $typeLower ] = $className;
	}
	
}