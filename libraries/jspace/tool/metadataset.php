<?php
/**
 * This tool is to help in situations where some metadata information is displayed
 * separately and the remaining metadata is displayed in list.
 * E.g:
 * <display item title>
 * <display list of remaining metadata>
 * 
 * Using foreach on items metadata would display the title again.
 * 
 * Usage of JSpaceToolMetadataSet:
 * $ms = JSpaceFactory::getMetadataSet( $item );
 * echo $ms->getMetadata( 'title' );
 * foreach( $ms as $metadata ) {
 * 	//deal with the rest of metadata
 * }
 * 
 * You can get JSpaceToolMetadataSet object from JspaceRepositoryItem too:
 * $ms = $item->getMetadataSet();
 * It always returns fresh JSpaceToolMetadataSet object.
 * 
 * - Looping the JSpaceToolMetadataSet will return only items that were not yet explicitly requested.
 * - You can explicitly request the same metadata more than once (e.g. use $ms->getMetadata('title') twice and that's ok).
 * - You can explicitly request metadata after doing the loop.
 * - You may loop the JSpaceToolMetadataSet object more than once and it will return metadata not requested explicitly.
 * - Don't getMetadata(...) inside the loop. 
 * 
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


class JSpaceToolMetadataSet implements Iterator, Countable  {
	/**
	 * 
	 * @var JSpaceRepositoryItem
	 */
	protected $_item = null;
	
	/**
	 * 
	 * @var array
	 */
	protected $_metadata = array();
	
	/**
	 * 
	 * @var int
	 */
	protected $_count = 0;
	
	public function __construct( JSpaceRepositoryItem $item ) {
		$this->_item = $item;
		$this->_init();
	}
	
	protected function _init() {
		$meta = $this->_item->getMetadata();
		foreach( $meta as $key => $val) {
			$this->_metadata[ $key ] = array(
				'meta'	=> $val,
				'used'	=> false,
				'key'	=> $key,
			);
			$this->_count++;
		}
	}
	
	/**
	 * 
	 * @param string $key
	 * return JSpaceRepositoryMetadata
	 */
	public function getMetadata( $key ) {
		$ckey = $this->_item->getCrosswalkValue( $key );
		$meta = null;
		if( isset( $this->_metadata[$ckey] ) ) {
			$meta = $this->_metadata[$ckey]['meta'];
			if( !$this->_metadata[$ckey]['used'] ) {
				$this->_count--;
			}
			$this->_metadata[$ckey]['used'] = true;
		}
		return $meta;
	}
	
	
	
	/*
	 * Iterator functions to walk the map.
	*/
	function rewind() {
		reset($this->_metadata);
		
		$row = current($this->_metadata);
		$used = JArrayHelper::getValue($row, 'used', false);
		$valid = $this->valid();
		
		if( $used && $valid ) {
			$this->next();
		}
	}
	
	function current() {
		$current = current($this->_metadata);
		return JArrayHelper::getValue($current,'meta',null);
	}
	
	function key() {
		$current = current($this->_metadata);
		return JArrayHelper::getValue($current,'key', null);
	}
	
	function next() {
		$done = true;
		do {
			next($this->_metadata);
			if( $this->valid() ) {
				$row = current($this->_metadata);
				if( !JArrayHelper::getValue($row, 'used', false) ) {
					$done = false;
				}
			}
			else {
				$done = false;
			}
		}
		while( $done );
	}
	
	function valid() {
		return !is_null($this->key());
	}
	
	/**
	 * @return int
	 */
	public function count() {
		return $this->_count;
	}
	
}






