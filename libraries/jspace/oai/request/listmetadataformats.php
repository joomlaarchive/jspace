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
class JSpaceOAIRequestListMetadataFormats extends JSpaceOAIRequest
{
	/**
	 * Required HTTP OAI-PMH request arguments.
	 * "required, the argument must be included with the request (the verb argument is always required, as described in HTTP Request Format)"
	 *
	 * @var array
	 */
	protected $_required = array('verb');
	
	/**
	 * Optional HTTP OAI-PMH request arguments.
	 * "optional, the argument may be included with the request"
	 *
	 * @var array
	 */
	protected $_optional = array('identifier');
	
	/**
	 * 
	 * @var JSpaceRepositoryItem
	 */
	protected $_item = null;
	
	/**
	 * (non-PHPdoc)
	 * @see JSpaceOAIRequest::_load()
	 */
	protected function _load() {
		/*
		 * Identifier in this implementation is irrelevant because all items have the same metadata formats as 
		 * entire JSpace. To keep implementetion protocol conformant it checks if item exists if identifier is
		 * passed as an argument. 
		 */
		$item_id = $this->_input->getString('identifier', null);
		if( !is_null( $item_id ) ) {
			$this->_item = $this->_getItem( $item_id );
		}
		$this->_setResponseBody();
	}
	
	/**
	 * Set the body in response xml.
	 */
	public function _setResponseBody() {
		$listMetadataFormats = $this->_responseXml->addChild('ListMetadataFormats');
		$formats = JSpaceOAI::getAllDisseminateFormats();
		
		if( count($formats) == 0 ) {
			/*
			 * Should only happen if formats per item will be implemented and no format will match particular item.
			 */
			throw new JSpaceOAIExceptionNoMetadataFormats();
		}
		
		foreach( $formats as $type => $format ) {
			/* @var $format JSpaceOAIDisseminateFormat */
			$metadataFormat = $listMetadataFormats->addChild( 'metadataFormat' );
			
			$metadataPrefix = $metadataFormat->addChild( 'metadataPrefix', $type );
			$schema = $metadataFormat->addChild( 'schema', $format->getSchema() );
			$metadataNamespace = $metadataFormat->addChild( 'metadataNamespace', $format->getMetadataNamespace() );
		}
	}
}




