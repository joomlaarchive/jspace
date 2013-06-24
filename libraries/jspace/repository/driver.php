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
		if( !isset( JSpaceRepositoryDriver::$_drivers[ $driver ] ) ) {
			$msg = "Requested driver <$driver> was not registered.";
			JSpaceLog::add($msg, JLog::CRITICAL, JSpaceLog::CAT_REPOSITORY);
			throw new Exception( $msg );
		}
		return JSpaceRepositoryDriver::$_drivers[ $driver ];
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
	
	public function __construct( $driver, $options ) {
		$this->_driver = $driver;
		$this->_configXmlPath = JArrayHelper::getValue($options, 'configXlPath', '');
		$this->_classPrefix = JArrayHelper::getValue($options, 'classPrefix', '');
		if( !JFile::exists($this->_configXmlPath) ) {
			$msg = "Config XML file not found " . $this->_configXmlPath;
			JSpaceLog::add($msg, JLog::CRITICAL, JSpaceLog::CAT_INIT);
			throw new Exception( $msg );
		}
	}
	
	public function getDriver() {
		return $this->_driver;
	}
	
	public function getConfigXmlPath() {
		return $this->_configXmlPath;
	}
	
	public function getClassName( $class ) {
		
	}
}