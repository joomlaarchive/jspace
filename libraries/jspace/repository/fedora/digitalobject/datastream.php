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
	
	protected $_elements = array(
			'dsLabel',
			'dsVersionID',
			'dsCreateDate',
			'dsState',
			'dsMIME',
			'dsFormatURI',
			'dsControlGroup',
			'dsSize',
			'dsVersionable',
			'dsInfoType',
			'dsLocation',
			'dsLocationType',
			'dsChecksumType',
			'dsChecksum',
			'dsChecksumValid',
			'dsAltID',
	);
	
    public $dsLabel = '';
    public $dsVersionID = '';
    public $dsCreateDate = '';
    public $dsState = '';
    public $dsMIME = '';
    public $dsFormatURI = '';
    public $dsControlGroup = '';
    public $dsSize = '';
    public $dsVersionable = '';
    public $dsInfoType = '';
    public $dsLocation = '';
    public $dsLocationType = '';
    public $dsChecksumType = '';
    public $dsChecksum = '';
    public $dsChecksumValid = '';
    public $dsAltID = '';
	

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
		//determine datastream type and create object
		$item_id = base64_decode($item->getId());
		$endpoint = JSpaceFactory::getEndpoint('objects/' . $item_id . '/datastreams/' . $dsid . '?format=xml');
		$resp = JSpaceFactory::getRepository()->getConnector()->get($endpoint);
		$xml = new SimpleXMLElement( $resp );

		if( in_array($dsid, self::$_reservedDatastreams) ) {
			/*
			 * Instantiate approppriate subclass of JSpaceRepositoryFedoraDatastream based on reserved dsid value
			*  - JSpaceRepositoryFedoraDatastreamDC
			*  - JSpaceRepositoryFedoraDatastreamRELSEXT
			*  - JSpaceRepositoryFedoraDatastreamAUDIT
			*/
			$class = 'JSpaceRepositoryFedoraDatastream' . str_replace("-", "", $dsid);
		}
		else {
			/*
			 * Instantiate approppriate subclass of JSpaceRepositoryFedoraDatastream based on control group value
			 *  - JSpaceRepositoryFedoraDatastreamX
			 *  - JSpaceRepositoryFedoraDatastreamM
			 *  - JSpaceRepositoryFedoraDatastreamR
			 *  - JSpaceRepositoryFedoraDatastreamE
			 */
			$controlGroup = in_array((string)$xml->dsControlGroup, array('X','M','R','E')) ? (string)$xml->dsControlGroup : '';
			$class = 'JSpaceRepositoryFedoraDatastream' . $controlGroup;
		}
		if( !class_exists($class) ) {
			throw new Exception(JText::_('COM_JSPACE_JSPACE_FEDORA_DATASTREAM_CLASS_NOT_FOUND'));
		}
		return new $class($item, $dsid, $xml);
	}

	/**
	 * 
	 * @param JSpaceRepositoryFedoraItem $item
	 * @param string $dsid
	 */
	public function __construct( JSpaceRepositoryFedoraItem $item, $dsid, SimpleXMLElement $xml ) {
		$this->_dsid = $dsid;
		$this->_item = $item;
		$this->_xmlParser = $xml;
		foreach( $this->_elements as $key ) {
			$this->$key = (string)$xml->$key;
		}
		$this->_load();
// 		var_dump($this);
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




