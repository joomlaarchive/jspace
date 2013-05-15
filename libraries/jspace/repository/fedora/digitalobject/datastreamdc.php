<?php
/**
 * Fedora Data Stream representation
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
class JSpaceRepositoryFedoraDatastreamDC extends JSpaceRepositoryFedoraDatastream
{
	/**
	 * 
	 * @var SimpleXMLElement
	 */
	protected $_xmlParser = null;
	
	/**
	 * 
	 * @var array
	 */
	protected $_data = array();
	
	protected function _load() {
		$endpoint = JSpaceFactory::getEndpoint('objects/' . urlencode(base64_decode($this->getItem()->getId())) . '/datastreams/DC/content' );
		$xml = $this->getItem()->getRepository()->getConnector()->get($endpoint);
		
		$this->_xmlParser = new SimpleXMLElement( $xml );
		foreach(  $this->_xmlParser->children('dc',true) as $key => $val ) {
			$this->_data[ 'dc.' . $key ][] = (string)$val;
		}
		
// 		var_dump($this->_data);
	}
		
	/**
	 * 
	 * (non-PHPdoc)
	 * @see JObject::get()
	 */
	public function get( $key ) {
		if( isset($this->_data[ $key ]) ) {
			return $this->_data[ $key ];
		}
		return array();
	}
	
	/**
	 * Some data are outside DC but will be used as if they were part of it.
	 * Added when item is created.
	 * 
	 * 'label'			
	 * 'state'			
	 * 'ownerId'		
	 * 'cDate'			
	 * 'mDate'			
	 * 'dcmDate'		
	 * 
	 * (non-PHPdoc)
	 * @see JObject::set()
	 */
	public function set( $key, $val ) {
		$this->_data[ $key ][] = $val;
	}
	
	public function keys() {
		return array_keys($this->_data);
	}
}




