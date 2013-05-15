<?php
/**
 * Fedora Data Streams list representation
 * 
 * /objects/{pid}/datastreams
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
class JSpaceRepositoryFedoraDatastreams extends JObject
{
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
	 * Array of JSpaceRepositoryFedoraDatastream objects
	 * 
	 * @var array
	 */
	protected $_datastreams = array();
	
	/**
	 * 
	 * @param JSpaceRepositoryFedoraItem $item
	 */
	public function __construct( JSpaceRepositoryFedoraItem $item ) {
		$this->_item = $item;
		$item_id = base64_decode($this->_item->getId()); 
		$endpoint = JSpaceFactory::getEndpoint('objects/' . $item_id . '/datastreams?format=xml');
		$resp = $this->getItem()->getRepository()->getConnector()->get($endpoint);
		
		$this->_xmlParser = new SimpleXMLElement( $resp );
// 		var_dump($resp);
// 		var_dump($this->_xmlParser);
	}
	
	/**
	 * 
	 * @return JSpaceRepositoryFedoraItem
	 */
	public function getItem() {
		return $this->_item;
	}
	
	/**
	 * Return array of DSIDs (datastreams ids) that are present in this item.
	 * 
	 * @param bool $withReserved
	 * @return array
	 */
	public function getDatastreamsIdList( $withReserved=false ) {
		$ret = array();
		$datastreams = $this->_xmlParser->datastream;
		foreach($datastreams as $datastream) {
			$id = (string)$datastream['dsid'];
			if( $withReserved ) {
				$ret[] = $id;
			}
			else {
				if( !in_array($id, JSpaceRepositoryFedoraDatastream::$_reservedDatastreams) ) {
					$ret[] = $id;
				}
			}
		}
		return $ret;
	}
	
	/**
	 * 
	 * @param string $dsid
	 * @return JSpaceRepositoryFedoraDatastream
	 */
	public function getDatastream( $dsid ) {
		if( !isset( $this->_datastreams[ $dsid ]  ) ) {
			$this->_datastreams[ $dsid ] = JSpaceRepositoryFedoraDataStream::getInstance($this->getItem(), $dsid);
		}
		return $this->_datastreams[ $dsid ];
	}
}




