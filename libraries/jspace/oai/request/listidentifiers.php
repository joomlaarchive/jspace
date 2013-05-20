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
class JSpaceOAIRequestListIdentifiers extends JSpaceOAIRequest
{
	/**
	 *
	 * @var JSpaceOAIDisseminateFormat
	 */
	protected $_disseminateFormat = null;
	
	/**
	 * Required HTTP OAI-PMH request arguments.
	 * "required, the argument must be included with the request (the verb argument is always required, as described in HTTP Request Format)"
	 *
	 * @var array
	 */
	protected $_required = array('verb', 'metadataPrefix');
	

	/**
	 * Optional HTTP OAI-PMH request arguments.
	 * "optional, the argument may be included with the request"
	 *
	 * @var array
	 */
	protected $_optional = array('from', 'until', 'set');
	
	/**
	 * Optional HTTP OAI-PMH request arguments. When present it should be the only argument apart from verb.
	 * "exclusive, the argument may be included with request, but must be the only argument (in addition to the verb argument)"
	 *
	 * @var array
	 */
	protected $_exclusive = array('resumptionToken');
	
	/**
	 * 
	 * @var string
	 */
	protected $_setParam = null;

	/**
	 * 
	 * @var string
	 */
	protected $_fromParam = null;

	/**
	 * 
	 * @var string
	 */
	protected $_untilParam = null;
	
	public function __construct( JInput $input ) {
		try {
			parent::__construct( $input );
			$this->_disseminateFormat = $this->_getDisseminateFormat( $input->get('metadataPrefix', 0 ) );
			$this->_setParam = $input->getString('set', null);
			$this->_fromParam = $input->get('from', null);
			$this->_untilParam = $input->get('until', null);
			
			$this->_setResponseBody();
		}
		catch( JSpaceOAIException $e ) {
			$this->_error = $e;
		}
	}
	
	/**
	 * Set the body in response xml.
	 */
	public function _setResponseBody() {
		$listIdentifiers = $this->_responseXml->addChild('ListIdentifiers');
		$list = array();
		
		/*
		 * ToDo:
		 * Add support for from and until to be standard compliant.
		 * 
		 */
		
		$repository = JSpaceFactory::getRepository();
		if( !is_null($this->_setParam) ) {
			try {
				$id = explode(':', $this->_setParam);
				$id = $id[count($id)-1];
				$category = $repository->getCategory( $id );
				$config = JSpaceFactory::getConfig();
				/*
				 * ToDo needs better implementation or 
				 * https://jspace.atlassian.net/browse/JSPACE-36
				 */
				$config->set('limit_items', $category->getItemsCount());
				$list = $category->getItems();
			}
			catch( Exception $e ) {
			}
		}
		
		if( count($list) == 0 ) {
			throw new JSpaceOAIExceptionNoRecordsMatch();
		}
		
		$crosswalk = $this->_disseminateFormat->getCrosswalk();
		foreach( $list as $item ) {
			$header = $listIdentifiers->addChild('header');
			
			$header->addChild('identifier', $item->getId());
			$datestamp = new JSpaceDate( $item->getMetadata('date', false, $crosswalk->getType()) );
			$header->addChild('datestamp', $datestamp->format(JSpaceOAI::DATE_GRANULARITY_SECOND));
			$header->addChild('setSpec', JSpaceOAI::getSetID( $category ));
		}
	}
}




