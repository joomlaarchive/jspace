<?php
/**
 * Metadata class
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
 * 
 * Metadata object is a container for values represented by JSpace key passed as constructor argument. 
 * It is an array of values becouse a single repository key may be stored multiple times with different values:
 * 	'dc.keywords' => 'keyword 1'
 * 	'dc.keywords' => 'keyword 2'
 * 	'dc.keywords' => 'keyword 3'
 * 
 * 
 * @author Michał Kocztorz
 * @package     JSpace
 * @subpackage  Repository
 */
abstract class JSpaceRepositoryMetadata extends JObject implements Iterator, Countable 
{
	/**
	 * @var JSpaceRepositoryItem
	 */
	protected $_item = null;
	
	protected $_key = null;
	
	/**
	 * Metadata value array. A single metadata entity may have many values. 
	 * E.g. 'keywords' is an array of keywords. Every metadata key may be used in archived
	 * item multiple times.  
	 * @var array
	 */
	protected $_value = array();

	/**
	 * @param JSpaceRepositoryItem $item
	 * @param mixed $id
	 */
	public function __construct( $item, $key ) {
		$this->_item = $item;
		$this->_key = $key;
		$this->_init();
	}
	
	/**
	 * 
	 * @return JSpaceRepositoryBundle
	 */
	public function getItem() {
		return $this->_item;
	}
	
	/**
	 * Get metadata JSpace key.
	 * @return string
	 */
	public function getKey() {
		return $this->_key;
	}
	
	/**
	 * Get metadata value
	 * @return string
	 */
	public function getValue() {
		return $this->current();
	}
	
	public function getValues() {
		$this->rewind();
		$ret = array();
		foreach( $this as $meta ) {
			$ret[] = $meta;
		}
		$this->rewind();
		return $ret;
	}
	
	abstract protected function _init();
	
	public function __toString() {
		$str = $this->getValue();
		return empty($str) ? '' : $str;
	}
	
	

	protected $position = 0;
	/*
	 * Iterator functions to walk the map.
	*/
	function rewind() {
		$this->position = 0;
	}
	
	function current() {
		return $this->_value[$this->position];
	}
	
	function key() {
		return $this->position;
	}
	
	function next() {
		$this->position++;
	}
	
	function valid() {
		return isset($this->_value[$this->position]);
	}
	
	/**
	 * @return int
	 */
	public function count() {
		return count( $this->_value );
	}

}




