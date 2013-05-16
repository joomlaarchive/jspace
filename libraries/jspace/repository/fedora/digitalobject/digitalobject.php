<?php
/**
 * Fedora Digital Object representation
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
jimport('joomla.utilities.simplexml');

/**
 * @author Michał Kocztorz
 * @package     JSpace
 * @subpackage  Repository
 */
class JSpaceRepositoryFedoraDigitalObject extends JObject
{
	/**
	 * 
	 * @var SimpleXMLElement
	 */
	protected $_xmlParser = null;
	
	protected $_data = array();
	
	protected $_objectField = null;
	
	public function __construct( $xml ) {
		$this->_xmlParser = new SimpleXMLElement( $xml );
		
		if( count($this->_xmlParser->resultList[0]->objectFields) == 0 ) {
			throw JSpaceRepositoryError::raiseError($this, JText::_('COM_JSPACE_JSPACEITEM_ERROR_CANNOT_FETCH'));
		}
	}
	
	/**
	 * 
	 */
	public function getObjectField() {
		if( is_null( $this->_objectField ) ) {
			$this->_objectField = $this->_xmlParser->resultList[0]->objectFields[0];
		}
		return $this->_objectField;
	}
	
	/**
	 * Get the data stored in xml in result->resultList->objectFields->$key
	 * 
	 * @param string $key
	 * @return string
	 */
	public function getData( $key ) {
		if( is_null( $this->_data[ $key ] ) ) {
			try {
				$val = $this->getObjectField()->$key;
				if( $val ) {
					$this->_data[ $key ] = (string)$val;
				}
				else {
					$this->_data[ $key ] = '';
				}
			}
			catch( Exception $e ) {
				$this->_data[ $key ] = '';
			}
		}
		return $this->_data[ $key ];
	}
}




