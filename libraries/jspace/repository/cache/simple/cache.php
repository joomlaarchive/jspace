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

jimport('jspace.repository.cache');
/**
 * @package     JSpace
 * @subpackage  Repository
 */
class JSpaceRepositoryCacheSimpleCache extends JSpaceRepositoryCache
{
	protected $_storageDirectory = null;
	/**
	 * 
	 * @param array $options
	 */
	public function __construct( $options ) {
		parent::__construct( $options );
		$this->_storageDirectory = JArrayHelper::getValue($options, 'storageDirectory');
		if( !JFolder::exists($this->_storageDirectory) ) {
			JFolder::create($this->_storageDirectory);
		}
	}
	
	/**
	 * Get file name where property is stored.
	 * 
	 * @param string $property
	 * @return string
	 */
	protected function _fileName( $property ) {
		return $this->_storageDirectory . $property;
	}
	
	/**
	 * 
	 * @param JSpaceRepositoryCacheKey $key
	 * @return string|NULL
	 */
	public function get( JSpaceRepositoryCacheKey $key ) {
		$property = (string)$key;
		$ok = false;
		$res = null;
		$file = $this->_fileName( $property );
		if( JFile::exists( $file ) ) {
			if( JFile::exists( $file . '.time' ) ) {
				$timestamp = JFile::read($file . '.time');
				if( $timestamp < time() ) {
					JFile::delete( $file );
				}
				else {
					$ok = true;
				}
			}
			else {
				//to .time file == indefinite
				$ok = true;
			}
		}
		
		if( $ok ) {
			$res = JFile::read( $file );
		}
		
		return $res;
	}
	
	/**
	 *
	 * @param JSpaceRepositoryCacheKey $key
	 * @param string $value
	 * @param int $valid cache valid in seconds
	 * @return bool
	 */
	public function set( JSpaceRepositoryCacheKey $key, $value, $valid=null ) {
		$property = (string)$key;
		$file = $this->_fileName( $property );
		$valid = is_null($valid) ? $this->_valid : $valid; //get default validity period
		$res = false;
		
		if( JFile::write($file, $value) ) {
			if( !is_null( $valid ) ) {
				$valid += time();
				if( JFile::write($file . '.time', $valid) ) {
					$res = true;
				}
			}
			else {
				$res = true;
			}
		}
		
		return $res;
	}
	
}




