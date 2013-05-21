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
class JSpaceOAIRequestIdentify extends JSpaceOAIRequest
{
	/**
	 * Required HTTP OAI-PMH request arguments.
	 * "required, the argument must be included with the request (the verb argument is always required, as described in HTTP Request Format)"
	 *
	 * @var array
	 */
	protected $_required = array('verb');
	
	protected function _load() {
		$this->_setResponseBody();
	}
	
	/**
	 * Set the body in response xml.
	 */
	public function _setResponseBody() {
		$config = JSpaceFactory::getConfig();
		$earliestDatestamp = new JSpaceDate( $config->get('oai_earliest_datestamp', '') );
		$granularity = $config->get('oai_granularity', 'Y-m-d');
		$granularityXML = '';
		switch( $granularity ) {
			case JSpaceOAI::DATE_GRANULARITY_SECOND:
				$granularityXML = 'YYYY-MM-DD';
				break;
			case JSpaceOAI::DATE_GRANULARITY_DAY:
			default:
				$granularityXML = 'YYYY-MM-DDThh:mm:ssZ';
				break;
		}
		
		$admins = JSpaceOAI::adminEmails(); 
		
		$identify = $this->_responseXml->addChild('Identify');
		$identify->addChild('repositoryName', $config->get('oai_repository_name', ''));
		$identify->addChild('baseURL', JUri::current());
		$identify->addChild('protocolVersion', '2.0');
		foreach( $admins as $email ) {
			$identify->addChild('adminEmail', $email);
		}
		$identify->addChild('earliestDatestamp', $earliestDatestamp->format( $granularity ));
		$identify->addChild('deletedRecord', 'transient');
		$identify->addChild('granularity', $granularityXML);
	}
}




