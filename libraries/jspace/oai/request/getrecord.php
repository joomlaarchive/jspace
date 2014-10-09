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
class JSpaceOAIRequestGetRecord extends JSpaceOAIRequest
{
	/**
	 * Required HTTP OAI-PMH request arguments.
	 * "required, the argument must be included with the request (the verb argument is always required, as described in HTTP Request Format)"
	 *
	 * @var array
	 */
	protected $_required = array('verb', 'identifier', 'metadataPrefix');
	
	/**
	 * 
	 * @var JSpaceRepositoryItem
	 */
	protected $_item = null;
	
	/**
	 * 
	 * @var JSpaceOAIDisseminateFormat
	 */
	protected $_disseminateFormat = null;
	
	protected function _load() {
		$input = $this->_input;
		$this->_item = $this->_getItem( $input->get('identifier', 0 ) );
		$this->_disseminateFormat = $this->_getDisseminateFormat( $input->get('metadataPrefix', 0 ) );
		$this->_setResponseBody();
	}
	
	/**
	 * Set the body in response xml.
	 */
	public function _setResponseBody() {
		$getRecord = $this->_responseXml->addChild('GetRecord');
		$this->_addRecord($getRecord, $this->_item, $this->_disseminateFormat);
	}
}




