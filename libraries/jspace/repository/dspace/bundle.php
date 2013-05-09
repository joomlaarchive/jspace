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
class JSpaceRepositoryDspaceBundle extends JSpaceRepositoryBundle
{
	protected $_dspaceRawBundle = null;
	
	protected function _init(){
		$raw = $this->getItem()->dspaceGetBundles();
		foreach( $raw as $rawBundle ) {
			if( $rawBundle->name == $this->_type ) {
				$this->_dspaceRawBundle = $rawBundle;
				return;
			}
		}
		throw JSpaceRepositoryError::raiseError( $this, JText::sprintf("COM_JSPACE_REPOSITORY_BUNDLE_NOT_FOUND", $this->_type) );
	}
	
	/**
	 * Test if biststream exists. If id is null, test if primary bitstream exists.
	 * @param mixed $id
	 * @return bool
	 */
	protected function _hasBitstream( $id = null ){
		if( is_null( $id ) ) {
			$id = $this->_dspaceRawBundle->primaryBitstreamId;
// 			JSpaceLogger::log("Primary bitstream: $id for " . $this->_type);
		}
		foreach( $this->_dspaceRawBundle->bitstreams as $rawBitstream ) {
			if( $rawBitstream->id == $id ) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Get primary bitstream.
	 *
	 * @throws JException if bitstream not found
	 * @param mixed $id
	 * @return JSpaceRepositoryBitstream
	*/
	protected function _getPrimaryBitstream() {
		return $this->getBitstream( $this->_dspaceRawBundle->primaryBitstreamId );
	}
	
	/**
	 * DSpace specyfic. Get raw bundle data.
	 * @return array
	 */
	public function dspaceGetRawBundle() {
		return $this->_dspaceRawBundle;
	}
	
	/**
	 * @return array od JSpaceRepositoryBitstream
	 */
	public function _getBitstreams() {
		$arr = array();
		foreach( $this->_dspaceRawBundle->bitstreams as $rawBitstream ) {
			$key = isset($arr[ $rawBitstream->sequenceId ]) ? uniqid($rawBitstream->sequenceId . "_") : $rawBitstream->sequenceId; //duplacate sequence ID protection
			$arr[ $key ] = $this->getBitstream( $rawBitstream->id ); 
		}
		return $arr;
	}
}




