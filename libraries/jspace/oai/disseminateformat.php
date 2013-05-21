<?php
/**
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
 * @subpackage  OAI
 */
abstract class JSpaceOAIDisseminateFormat extends JObject
{
	protected $_xmlns = array();
	
	/**
	 * 
	 * @var string
	 */
	protected $_schema = '';
	
	/**
	 * 
	 * @var string
	 */
	protected $_metadataNamespace = '';
	
	
	/**
	 * 
	 * @param string $type
	 * @return JSpaceOAIDisseminateFormat
	 */
	public static function getInstance( $type ) {
		$type = ucfirst( $type );
		$class = 'JSpaceOAIDisseminateFormat' . $type;
		try {
			if( class_exists($class) ) {
				return new $class( $type );
			}
		}
		catch( Exception $e ) {
			//catch any exception
		}
		throw new JSpaceOAIExceptionCannotDisseminateFormat( $type );
	}
	
	/**
	 * 
	 * @var string
	 */
	protected $_type = '';
	
	/**
	 * 
	 * @var JSpaceCrosswalk
	 */
	protected $_crosswalk = null;
	
	/**
	 * 
	 * @param string $type
	 */
	public function __construct( $type ) {
		$this->_type = $type;
		try {
			$this->_crosswalk = JSpaceFactory::getCrosswalk( $this->_type );
		}
		catch( Exception $e ) {
			throw new JSpaceOAIExceptionCannotDisseminateFormat( $this->_type );
		}
	}
	
	/**
	 * Get disseminate format crosswalk.
	 * 
	 * @return JSpaceCrosswalk
	 */
	public function getCrosswalk() {
		return $this->_crosswalk;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getFormat() {
		return $this->_type;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getSchema() {
		return $this->_schema;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getMetadataNamespace() {
		return $this->_metadataNamespace;
	}
	
	/**
	 * Create main tag for data.
	 * 
	 * @param SimpleXMLElement $parent
	 * @return SimpleXMLElement
	 */
	abstract public function createChild( SimpleXMLElement $parent );
	
	/**
	 * Get array of expected fields.
	 * 
	 * @return array
	 */
	abstract public function getExpectedFields();
	
	/**
	 * 
	 * @param string $title
	 * @param string $value
	 * @param SimpleXMLElement $parent
	 * 
	 * @return SimpleXMLElement
	 */
	abstract public function createDataChild( $element, $value, SimpleXMLElement $parent );
}

