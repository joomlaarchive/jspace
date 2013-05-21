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
class JSpaceOAIDisseminateFormatOai_dc extends JSpaceOAIDisseminateFormat
{
	protected $_expected = array(
			"title",
			"creator",
			"subject",
			"description",
			"publisher",
			"contributor",
			"date",
			"type",
			"format",
			"identifier",
			"source",
			"language",
			"relation",
			"coverage",
			"rights",
	);
	

	/**
	 *
	 * @var string
	 */
	protected $_schema = 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd';
	
	/**
	 *
	 * @var string
	 */
	protected $_metadataNamespace = 'http://www.openarchives.org/OAI/2.0/oai_dc/';
	

	/**
	 * Create main tag for data.
	 *
	 * @param SimpleXMLElement $parent
	 * @return SimpleXMLElement
	 */
	public function createChild( SimpleXMLElement $parent ){
		$parent->registerXPathNamespace('oai_dc', $this->getMetadataNamespace() );
		$oai_dc = $parent->addChild('oai_dc:dc', '', $this->getMetadataNamespace() );
		$oai_dc->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
		return $oai_dc;
	}
	
	/**
	 * Get array of expected fields.
	 *
	 * @return array
	*/
	public function getExpectedFields() {
		return $this->_expected;
	}
	
	/**
	 *
	 * @param string $title
	 * @param string $value
	 * @param SimpleXMLElement $parent
	 *
	 * @return SimpleXMLElement
	*/
	public function createDataChild( $element, $value, SimpleXMLElement $parent ) {
		return $parent->addChild('dc:' . $element, $value, 'http://purl.org/dc/elements/1.1/');
	}
}

