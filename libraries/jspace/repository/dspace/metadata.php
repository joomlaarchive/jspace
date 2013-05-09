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
 * @author Michał Kocztorz
 * @package     JSpace
 * @subpackage  Repository
 */
class JSpaceRepositoryDspaceMetadata extends JSpaceRepositoryMetadata
{
	protected $_dspaceRawMetadata = null;
	
	protected function _init() {
		$rawMetadata = $this->getItem()->dspaceGetRaw()->metadata;
		$crosswalkValue = $this->getItem()->getCrosswalkValue( $this->getKey() );
		foreach( $rawMetadata as $meta ) {
			if( $meta->schema . "." . $meta->element . (is_null($meta->qualifier)?"":("." . $meta->qualifier)) == $crosswalkValue) {
				$this->_dspaceRawMetadata[] = $meta;
				$this->_value[] = $meta->value;
			}
		}
		if( count($this->_value) == 0 ) {
			throw JSpaceRepositoryError::raiseError($this, JText::sprintf("COM_JSPACE_REPOSITORY_METADATA_NOT_FOUND", $this->getKey()));
		}
	}
	

	public function getSchema() {
		return $this->_dspaceRawMetadata[$this->position]->schema;
	}
	
	public function getElement() {
		return $this->_dspaceRawMetadata[$this->position]->element;
	}
	
	public function getQualifier() {
		return $this->_dspaceRawMetadata[$this->position]->qualifier;
	}
	
	public function dspaceGetRawMetadata() {
		return $this->_dspaceRawMetadata;
	}
}




