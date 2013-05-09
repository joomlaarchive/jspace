<?php
/**
 * A mapper that will take JSpace metadata and map them using selected crosswalk.
 * Main mapper's task is to resolve naming conflicts.
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

jimport('jspace.factory');

class JSpaceMapper implements Iterator{
	const MAPPER_DUBLINCORE = 'dublincore';
	
	protected $type;
	protected $map = array();
	protected $rejected = array();
	protected $separator = ', ';
	
	/**
	 * @author Michał Kocztorz
	 * @param string $mapperType
	 */
	public function __construct( $mapperType ) {
		$this->type = $mapperType;
	}
	
	/**
	 * @author Michał Kocztorz
	 * @return JSpaceCrosswalk
	 */
	public function getCrosswalk() {
		return JSpaceFactory::getCrosswalk($this->type);
	}
	
	/**
	 * Get mapped key using current crosswalk.
	 * 
	 * @author Michał Kocztorz
	 * @param string $key
	 */
	public function _( $key ) {
		return $this->getCrosswalk()->_( $key );
	}
	
	/**
	 * Add a key-value pair. Mapper will translate using selected crosswalk and add to map.
	 * @author Michał Kocztorz
	 * @param string $key
	 * @param string $value
	 * @return bool
	 */
	public function add($key, $value) {
		$mappedKey = $this->_( $key );
		if( $mappedKey !== false ) {
			$this->map[] = array(
				'key'	=> $mappedKey,
				'value' => $value
			);
// 			if( isset( $this->map[$mappedKey] ) ) {
// 				/*
// 				 * Resolving conflict by concatenating values with separator. 
// 				 */
// 				$this->map[$mappedKey] .= $this->separator . $value;
// 			}
// 			else {
// 				$this->map[$mappedKey] = $value;
// 			}
			return true;
		}
		else {
			$this->rejected[$key] = $value;
			return false;
		}
	}
	
	/**
	 * Get created map.
	 * 
	 * @author Michał Kocztorz
	 * @return array
	 */
	public function getMap() {
		return $this->map;
	}
	
	/**
	 * Get rejected map.
	 * @author Michał Kocztorz
	 * @return array
	 */
	public function getRejected() {
		return $this->rejected;
	}
	
	
	/**
	 * Are there rejected keys.
	 * @author Michał Kocztorz
	 * @return boolean
	 */
	public function hasRejected() {
		return (bool)(count($this->rejected) > 0);
	}
	
	protected $position = 0;
	/*
	 * Iterator functions to walk the map.
	 */
	function rewind() {
		$this->position = 0;
	}
	
	function current() {
		return $this->map[$this->position]['value'];
	}
	
	function key() {
		return $this->map[$this->position]['key'];
	}
	
	function next() {
		$this->position++;
	}
	
	function valid() {
		return isset($this->map[$this->position]);
	}
	
	
}







