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
	 * 
	 * (non-PHPdoc)
	 * @see JSpaceOAIDisseminateFormat::createMetadata()
	 */
	public function createRecordMetadata( SimpleXMLElement $parent, JSpaceRepositoryItem $item ) {
		$parent->registerXPathNamespace('oai_dc', $this->getMetadataNamespace() );
		$dataTag = $parent->addChild('oai_dc:dc', '', $this->getMetadataNamespace() );
		$dataTag->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
		
		$crosswalk = $this->getCrosswalk();
		foreach( $this->_expected as $element ) {
			try {
				$value = $item->getMetadata($element, false, $crosswalk->getType());
				foreach( $value as $val ) {
					//get rid of & from element value
					$val = str_replace("&", "&amp;", $val);
					$dataTag->addChild('dc:' . $element, $val, 'http://purl.org/dc/elements/1.1/');
				}
			}
			catch( Exception $e ) {
				//not found
			}
		}
	}
}

