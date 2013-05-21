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

JLoader::discover("JSpaceOAIRequest", JPATH_SITE . "/libraries/jspace/oai/request/");
JLoader::discover("JSpaceOAIException", JPATH_SITE . "/libraries/jspace/oai/exception/");
JLoader::discover("JSpaceOAIDisseminateFormat", JPATH_SITE . "/libraries/jspace/oai/disseminateformat/");

/**
 * @author Michał Kocztorz
 * @package     JSpace
 * @subpackage  OAI
 */
abstract class JSpaceOAIRequest extends JObject
{
	protected static $_verbs = array(
		'GetRecord',			// http://www.openarchives.org/OAI/openarchivesprotocol.html#GetRecord
		'Identify',				// http://www.openarchives.org/OAI/openarchivesprotocol.html#Identify
		'ListIdentifiers',		// http://www.openarchives.org/OAI/openarchivesprotocol.html#ListIdentifiers
		'ListMetadataFormats',	// http://www.openarchives.org/OAI/openarchivesprotocol.html#ListMetadataFormats
		'ListRecords',			// http://www.openarchives.org/OAI/openarchivesprotocol.html#ListRecords
		'ListSets',				// http://www.openarchives.org/OAI/openarchivesprotocol.html#ListSets
	);
	
	/**
	 * 
	 * @return array
	 */
	public static function getVerbs() {
		return self::$_verbs;
	}
	
	/**
	 * 
	 * @param JInput $input
	 * @return JSpaceOAIRequest
	 */
	public static function getInstance(JInput $input) {
		$verb = $input->get('verb', '');
		
		try {
			if( !in_array($verb, self::$_verbs) ) {
				throw new JSpaceOAIExceptionBadVerb( $verb );
			}
			$class = 'JSpaceOAIRequest' . $verb;
			return new $class( $input );
		}
		catch( JSpaceOAIExceptionBadVerb $e ) {
			return new JSpaceOAIRequestBadVerb($input, $e);
		}
	}
	
	/**
	 * An array of arguments passed with OAI-PMH request
	 * @var array
	 */
	protected $_arguments = null;
	
	/**
	 * Required HTTP OAI-PMH request arguments.
	 * "required, the argument must be included with the request (the verb argument is always required, as described in HTTP Request Format)"
	 * 
	 * @var array
	 */
	protected $_required = array();
	
	/**
	 * Optional HTTP OAI-PMH request arguments.
	 * "optional, the argument may be included with the request"
	 * 
	 * @var array
	 */
	protected $_optional = array();
	
	/**
	 * Optional HTTP OAI-PMH request arguments. When present it should be the only argument apart from verb.
	 * "exclusive, the argument may be included with request, but must be the only argument (in addition to the verb argument)"
	 * 
	 * @var array
	 */
	protected $_exclusive = array();
	
	/**
	 * 
	 * @var SimpleXMLElement
	 */
	protected $_responseXml;
	
	/**
	 * 
	 * @var JSpaceOAIException
	 */
	protected $_error = null;
	
	/**
	 * 
	 * @var JInput
	 */
	protected $_input = null;
	
	/**
	 * 
	 * @param JInput $input
	 */
	public function __construct( JInput $input ) {
		try {
			$this->_input = $input;
			$this->_init();
			$this->_testRequestArguments();
			$this->_setResponseRequestTag();
			$this->_load();
		}
		catch( JSpaceOAIException $e ) {
			$this->_error = $e;
		}
	}
	
	/**
	 * Method called by the constructor as last stage of constructiong object.
	 * JSpaceOAIExcepions thrown from it will be caught and apprpriate error
	 * xml response created.
	 */
	abstract protected function _load();
	
	/**
	 * Set the body in response xml.
	 */
	abstract public function _setResponseBody();
	
	/**
	 * Prepare a generic response XML.
	 */
	protected function _init() {
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>
<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/">
	<responseDate>' . JDate::getInstance('now', 'UTC')->format('Y-m-d\TH:i:s\Z') . '</responseDate>
</OAI-PMH>  
';
		$this->_responseXml = new SimpleXMLElement($xml);
		$this->_responseXml->registerXPathNamespace('xsi', "http://www.w3.org/2001/XMLSchema-instance");
		$this->_responseXml->addAttribute('xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd', "http://www.w3.org/2001/XMLSchema-instance");
	}
	
	protected function _setResponseRequestTag() {
		$app = JFactory::getApplication();
		$menu = $app->getMenu();
		$item = $menu->getActive();
		$request = $this->_responseXml->addChild('request', JUri::current());
		if( is_null($this->_error) ) {
			$arguments = $this->_getArguments();
			foreach( $arguments as $argument => $val ) {
				$request->addAttribute($argument, $val);
			}
		}
	}
	
	/**
	 * Tests if there is error. If error found, xml is reinitialized and error tag is added.
	 */
	protected function _updateIfError() {
		if( !is_null($this->_error) ) {
			$this->_init();
			$this->_setResponseRequestTag();
			$this->_error->addResponseErrorTag( $this->_responseXml );
		}
	}
	
	/**
	 * @return SimpleXMLElement
	 */
	public function getResponseXML() {
		$this->_updateIfError();
		return $this->_responseXml;
	}
	
	/**
	 * Calcualte OAI arguments (remove args from joomla). 
	 * 
	 * @return array
	 */
	protected function _getArguments() {
		if( is_null($this->_arguments) ) {
			$joomla = array('Itemid', 'option', 'view');
			$arguments = $this->_input->getArray($_REQUEST);
			foreach( $joomla as $jkey ) {
				unset( $arguments[ $jkey ] );
			}
			$this->_arguments = $arguments;
		}
		
		return $this->_arguments;
	}
	
	/**
	 * Test if arguments are ok based on _required, _optional and _exclusive values.
	 * The request includes illegal arguments, is missing required arguments, includes a repeated argument, or values for arguments have an illegal syntax.
	 * 
	 * @throws JSpaceOAIExceptionBadArgument
	 */
	protected function _testRequestArguments() { 
		$arguments = $this->_getArguments();
		
		if( count($this->_exclusive) == 0 ) {
			//test if all required are available
			foreach($this->_required as $required ) {
				if( !isset( $arguments[ $required ] ) ) {
					throw new JSpaceOAIExceptionBadArgument();
				}
			}
			
			//test if illegal additional arguments are used
			$allowed = array_merge($this->_required, $this->_optional, $this->_exclusive);
			foreach( $arguments as $name => $argument ) {
				if( !in_array($name, $allowed) ) {
					throw new JSpaceOAIExceptionBadArgument();
				}
			}
		}
		else {
			//test if exclusive arguments are used alone
			foreach( $this->_exclusive as $exclusive ) {
				if( isset( $arguments[$exclusive] ) && count($arguments) > 2 ) {
					throw new JSpaceOAIExceptionBadArgument();
				}
			}
		}
	}
	
	/**
	 * Get JSpaceRepositoryItem by id from repository.
	 * (helper method)
	 * 
	 * @param string $id
	 * @throws JSpaceOAIExceptionIdDoesNotExist
	 * @return JSpaceRepositoryItem
	 */
	public function _getItem( $id ) {
		try {
			return JSpaceFactory::getRepository()->getItem( $id );
		}
		catch( Exception $e ) {
			throw new JSpaceOAIExceptionIdDoesNotExist( $id );
		}
	}

	/**
	 * 
	 * @param string $type
	 * @throws JSpaceOAIExceptionCannotDisseminateFormat
	 */
	public function _getDisseminateFormat( $type ) {
		try {
			return JSpaceOAIDisseminateFormat::getInstance( $type );
		}
		catch( Exception $e ) {
			if( $e instanceof JSpaceOAIExceptionCannotDisseminateFormat ) {
				throw $e;
			}
			throw new JSpaceOAIExceptionCannotDisseminateFormat( $type );
		}
	}
	
	/**
	 * Helper method. 
	 * Add a header tag to parent passed in argument. 
	 * Used in GetRecord, ListRecords
	 * 
	 * @param SimpleXMLElement $parent
	 * @param JSpaceRepositoryItem $item
	 * @param JSpaceOAIDisseminateFormat $disseminateFormat
	 */
	protected function _addRecord( SimpleXMLElement $parent, JSpaceRepositoryItem $item, JSpaceOAIDisseminateFormat $disseminateFormat ) {
		$record = $parent->addChild('record');
		$crosswalk = $disseminateFormat->getCrosswalk();
		$this->_addRecordHeader($record, $item, $crosswalk);
		$this->_addMetadata($record, $item, $disseminateFormat);
	}
	
	/**
	 * Helper method. 
	 * Add a header tag to parent passed in argument. 
	 * Used in GetRecord, ListRecords, ListIdentifiers
	 * 
	 * @param SimpleXMLElement $parent
	 * @param JSpaceRepositoryItem $item
	 * @param JSpaceCrosswalk $crosswalk
	 */
	protected function _addRecordHeader( SimpleXMLElement $parent, JSpaceRepositoryItem $item, JSpaceCrosswalk $crosswalk ) {
		$header = $parent->addChild('header');
		
		$header->addChild('identifier', $item->getId());
		$datestamp = new JSpaceDate( $item->getMetadata('date', false, $crosswalk->getType()) );
		$header->addChild('datestamp', $datestamp->format(JSpaceOAI::DATE_GRANULARITY_SECOND));
		$category = $item->getCategory();
		$header->addChild('setSpec', JSpaceOAI::getSetID( $category ));
	}
	
	/**
	 * Helper method. 
	 * Add a metadata tag to parent passed in argument. 
	 * Used in GetRecord, ListRecords
	 * 
	 * @param SimpleXMLElement $parent
	 * @param JSpaceRepositoryItem $item
	 * @param JSpaceOAIDisseminateFormat $disseminateFormat
	 */
	protected function _addMetadata( SimpleXMLElement $parent, JSpaceRepositoryItem $item, JSpaceOAIDisseminateFormat $disseminateFormat) {
		$metadata = $parent->addChild('metadata');
		$disseminateFormat->createRecordMetadata($metadata, $item);
	}
}




