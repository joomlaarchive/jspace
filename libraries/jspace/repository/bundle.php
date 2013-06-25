<?php
/**
 * Bundle class. Bundle is a set of files (bitstreams) that have common purpose.
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
 * @author Michał Kocztorz
 * @package     JSpace
 * @subpackage  Repository
 */
abstract class JSpaceRepositoryBundle extends JObject
{
	const BUNDLE_TYPE_ORIGINAL	= "ORIGINAL";
	const BUNDLE_TYPE_PREVIEW	= "PREVIEW";
	const BUNDLE_TYPE_THUMBNAIL = "THUMBNAIL";
	
	/**
	 * JSpaceRepository<Driver>Item object that bundle belongs to.
	 * 
	 * @var JSpaceRepositoryItem
	 */
	protected $_item = null;
	
	/**
	 * Type of bundle. May be any string representing bundle. Must be reckognizable
	 * by the underlying repository.
	 * 
	 * @var string
	 */
	protected $_type = null;
	
	/**
	 * Array of JSpaceRepository<Driver>Bitstream objects that belong to this bundle.
	 * 
	 * @var array of JSpaceRepositoryBitstream
	 */
	protected $_bitstreams = array();
	
	/**
	 * Construct bundle object with item 
	 * 
	 * @param JSpaceRepositoryItem $item
	 */
	public function __construct( $item, $type ) {
		$this->_item = $item;
		$this->_type = $type;
		$this->_init();
	}
	
	/**
	 * Subclass should use this method to initialize driver-specyfic data.
	 *
	 * @return null
	 */
	abstract protected function _init();

	
	/**
	 * Return JSpaceRepository<Driver>Item object that bundle belongs to.
	 * 
	 * @return JSpaceRepositoryItem
	 */
	public function getItem() {
		return $this->_item;
	}
	
	/**
	 * Test if biststream exists. If id is null, test if primary bitstream exists.
	 * 
	 * @param mixed $id
	 * @return bool
	 */
	public function hasBitstream( $id = null ) {
		return $this->_hasBitstream( $id );
	}
	
	/**
	 * Called by public version of this method. 
	 * Subclass should provide this method. Value can be calculated only based on 
	 * driver-specyfic data.
	 * 
	 * @param mixed $id
	 * @return bool
	 */
	abstract protected function _hasBitstream( $id = null );
	
	/**
	 * Get bitstream by id. If id is null, get prmimary bitstream.
	 * 
	 * @throws JException if bitstream not found
	 * @param mixed $id
	 * @return JSpaceRepositoryBitstream
	 */
	public function getBitstream( $id = null ) {
		if( !isset( $this->_bitstreams[ $id ] ) ) {
			try {
				$this->_bitstreams[ $id ] = $this->_getBitstream( $id );
			}
			catch( Exception $e ) {
				throw JSpaceRepositoryError::raiseError( $this, $e );
			}
		}
		
		return $this->_bitstreams[ $id ];
	}

	/**
	 *
	 * @param mixed $id
	 * @return JSpaceRepositoryBitstream
	 */
	protected function _getBitstream( $id = null ) {
		if( is_null($id) ) {
			return $this->_getPrimaryBitstream();
		}
		$class = $this->getItem()->getRepository()->getClassName( JSpaceRepositoryDriver::CLASS_BITSTREAM );
		return new $class( $this, $id );
	}
	
	/**
	 * @return array od JSpaceRepositoryBitstream
	 */
	public function getBitstreams() {
		return $this->_getBitstreams();
	}
	
	/**
	 * @return array od JSpaceRepositoryBitstream
	 */
	abstract public function _getBitstreams();

	
	/**
	 * Get primary bitstream.
	 *
	 * @throws JException if bitstream not found
	 * @param mixed $id
	 * @return JSpaceRepositoryBitstream
	 */
	abstract protected function _getPrimaryBitstream();
}




