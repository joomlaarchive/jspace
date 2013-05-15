<?php
/**
 * Fedora Data Stream representation
 * 
 * /objects/{pid}/datastreams/{dsid}
 * 
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
class JSpaceRepositoryFedoraDatastream extends JObject
{
	protected $_dsid = null;
	/**
	 * 
	 * @var JSpaceRepositoryFedoraItem
	 */
	protected $_item = null;
	/**
	 * 
	 * @var SimpleXMLElement
	 */
	protected $_xmlParser = null;
	

	/**
	 * Array of reserved datastreams according to Fedora docs.
	 *
	 * @var unknown_type
	 */
	public static $_reservedDatastreams = array(
			'DC',
			'RELS-EXT',
			'AUDIT'
	);
	
	/**
	 * 
	 * @param JSpaceRepositoryFedoraItem $item
	 * @param string $dsid
	 * @return JSpaceRepositoryFedoraDatastream
	 */
	public static function getInstance( JSpaceRepositoryFedoraItem $item, $dsid) {
		if( in_array($dsid, self::$_reservedDatastreams) ) {
			switch( $dsid ) {
				case 'DC':
					return new JSpaceRepositoryFedoraDatastreamDC($item, $dsid);
					break;
				case 'RELS-EXT':
					break;
				case 'AUDIT':
					break;
			}
		}
		else {
			//determine datastream type and create object
			$item_id = base64_decode($item->getId());
			$endpoint = JSpaceFactory::getEndpoint('objects/' . $item_id . '/datastreams/' . $dsid . '?format=xml');
			$resp = JSpaceFactory::getRepository()->getConnector()->get($endpoint);
			$xml = new SimpleXMLElement( $resp );
// 			var_dump((string)$xml->dsControlGroup);
			switch( (string)$xml->dsControlGroup ) {
				case 'X': //inline XML
					return new JSpaceRepositoryFedoraDatastream($item, $dsid);
					break;
				case 'M': //Managed content
					break;
				case 'R': //Redirect
					break;
				case 'E': //External Reference
					break;
				default:
					//it will load it again, but it is cached anyway
					return new JSpaceRepositoryFedoraDatastream($item, $dsid);
					break;
			}
		}
	}

	/**
	 * 
	 * @param JSpaceRepositoryFedoraItem $item
	 * @param string $dsid
	 */
	public function __construct( JSpaceRepositoryFedoraItem $item, $dsid ) {
		$this->_dsid = $dsid;
		$this->_item = $item;
		var_dump($dsid);
// 		$item_id = base64_decode($this->_item->getId()); 
// 		$endpoint = JSpaceFactory::getEndpoint('objects/' . $item_id . '/datastreams/' . base64_decode($dsid) . '?format=xml');
// 		$resp = $this->getItem()->getRepository()->getConnector()->get($endpoint);
		
		//dont know yet which datastream type is it, creating datastream should be done in static factory method
		 
// 		$this->_xmlParser = new SimpleXMLElement( $resp );
// 		var_dump($this->_xmlParser);

		$this->_load();
	}
	
	protected function _load() {}
	
	/**
	 * 
	 * @return JSpaceRepositoryFedoraItem
	 */
	public function getItem() {
		return $this->_item;
	}
}




