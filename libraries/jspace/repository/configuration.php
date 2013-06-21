<?php
/**
 * A repository config class.
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
abstract class JSpaceRepositoryConfiguration
{
	protected static $_instances = array();
	public static function getInstance( $driver, $options = null ) {
		if( !isset( self::$_instances[ $driver ] ) ) {
			$driverLower = strtolower($driver);
			$driverUCfirst = ucfirst($driverLower);
			$class = "JSpaceRepository" . $driverUCfirst . "Configuration";
			if( !class_exists($class) ) {
				jimport('jspace.repository.' . $driverLower . '.configuration');
			}
			self::$_instances[ $driver ] = new $class( $options );
		}
		return self::$_instances[ $driver ];
	}
	
	protected $_options = array();
	protected $_configuration = array();
	
	public function __construct( $options = null ) {
		$this->buildOptionsArray( $options );
	}
	
	public function get( $key, $default = null ) {
		return JArrayHelper::getValue($this->_configuration, $key, $default );
	}
	
	public function getOptions() {
		return $this->_options;
	}
	
	abstract public function buildOptionsArray( $options = null );
}




