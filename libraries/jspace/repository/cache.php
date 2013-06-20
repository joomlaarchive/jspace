<?php
/**
 * A cache error class.
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

JLoader::discover('JSpaceRepositoryCache', dirname(__FILE__) . DIRECTORY_SEPARATOR . "cache");

/**
 * @package     JSpace
 * @subpackage  Repository
 */
abstract class JSpaceRepositoryCache
{
	const DEFAULT_VALID_PERIOD = 172800; //2 days
	/**
	 * 
	 * @var array
	 */
	protected static $_instances = array();
	
	/**
	 *
	 * @param array $options
	 * @throws Exception
	 * @return JSpaceCache
	*/
	public static function getInstance( $options ) {
		$driver = JArrayHelper::getValue($options, 'driver');

		if( !isset(self::$_instances[ $driver ]) ) {
			$class = "JSpaceRepositoryCache" . ucfirst(strtolower($driver));
			if( !class_exists($class) ) {
				//attempt import
				jimport('jspace.repository.cache.' . strtolower($driver) . '.cache' );
				if( !class_exists($class) ) {
					throw new Exception(JText::_('LIB_JSPACE_MISSING_CACHE_CLASS') . ' ' . $class );
				}
			}
			self::$_instances[ $driver ] = new $class( $options );
		}
		return self::$_instances[ $driver ];
	}
	
	/**
	 *
	 * @var string
	 */
	protected $_driver = '';
	
	/**
	 * If null then cache is valid indefinately, unless overridden in set method. 
	 * 
	 * @var int|null
	 */
	protected $_valid = null;
	
	/**
	 * 
	 * @param array $options
	 */
	public function __construct( $options ) {
		$this->_driver = JArrayHelper::getValue($options, 'driver');
		$this->_valid = JArrayHelper::getValue($options, 'valid', self::DEFAULT_VALID_PERIOD); //by default cache is valid for 3 hours.
	}
	
	/**
	 *
	 * @param JSpaceRepositoryCacheKey $key
	 * @return string|NULL
	 */
	public function get( JSpaceRepositoryCacheKey $key ){}
	
	/**
	 * 
	 * @param JSpaceRepositoryCacheKey $key
	 * @param string $value
	 * @param int $time
	 * @return bool
	 */
	public function set( JSpaceRepositoryCacheKey $key, $value, $valid=null ){}
	
	/**
	 * Clean cache related to the key if driver allows that.
	 * Returns true on success or false if failed or not supported.
	 * 
	 * @param JSpaceRepositoryCacheKey $key
	 * @return bool
	 */
	public function clean( JSpaceRepositoryCacheKey $key ){ return false; }
}




