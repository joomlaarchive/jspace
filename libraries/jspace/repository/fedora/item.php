<?php
/**
 * A repository item class.
 * Contains a business logic for particular repository type.
 * 
 * Structure of objects in JSpaceRepositoryFedoraItem
 * 
 * JSpaceRepositoryFedoraItem
 *   - JSpaceRepositoryFedoraDigitalObject - Raw information about fedora item retrieved by /objects/query?pid=<pid>&... 
 *   - JSpaceRepositoryFedoraDatastreams - Raw information about list of datastreams in object, retrieved by /objects/<pid>/datastreams
 *       - JSpaceRepositoryFedoraDatastreamDC - Representation of reserved DC datastream
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

JLoader::discover('JSpaceRepositoryFedora', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'digitalobject' );

/**
 *
 * @package     JSpace
 * @subpackage  Connector
 */
class JSpaceRepositoryFedoraItem extends JSpaceRepositoryItem
{
	protected $_fcRaw = null;
	
	/**
	 * 
	 * @var JSpaceRepositoryFedoraDigitalObject
	 */
	protected $_fdo = null;
	
	/**
	 * 
	 * @var JSpaceRepositoryFedoraDatastreamDC
	 */
	protected $_dcDatastream = null;
	
	/**
	 * 
	 * @var JSpaceRepositoryFedoraDatastreams
	 */
	protected $_fcDatastreams = null;
	
	/**
	 * (non-PHPdoc)
	 * @see JSpaceRepositoryItem::_load()
	 */
	protected function _load() {
// 		var_dump(base64_encode('demo:21'));
		try {
			$endpoint = JSpaceFactory::getEndpoint('objects?query=' . urlencode('pid=' . base64_decode($this->getId()) ), array(
					//not stored in DC
					'label'			=> 'true',
					'state'			=> 'true',
					'ownerId'		=> 'true',
					'cDate'			=> 'true',
					'mDate'			=> 'true',
					'dcmDate'		=> 'true',
					'pid'			=> 'true',
					'maxResults' 	=> '1',
					'resultFormat'	=> 'xml'
				)
			);
			$resp = $this->getRepository()->getConnector()->get($endpoint);
			$this->_fcRaw = $resp;
			
// 			echo $this->_fcRaw; exit;
			$this->_fdo = new JSpaceRepositoryFedoraDigitalObject( $resp );
// 			var_dump($resp);exit;
			
			//load DC datastream
			$this->_dcDatastream = $this->fcGetDatastream('DC');
			
			$this->_dcDatastream->set( $this->getRepository()->getMapper()->getCrosswalk()->_('fedora_label'), $this->_fdo->getData( 'label' ));
			$this->_dcDatastream->set( $this->getRepository()->getMapper()->getCrosswalk()->_('fedora_state'), $this->_fdo->getData( 'state' ));
			$this->_dcDatastream->set( $this->getRepository()->getMapper()->getCrosswalk()->_('fedora_ownerid'), $this->_fdo->getData( 'ownerId' ));
			$this->_dcDatastream->set( $this->getRepository()->getMapper()->getCrosswalk()->_('date_issued'), $this->_fdo->getData( 'cDate' ));
			$this->_dcDatastream->set( $this->getRepository()->getMapper()->getCrosswalk()->_('fedora_mdate'), $this->_fdo->getData( 'mDate' ));
			$this->_dcDatastream->set( $this->getRepository()->getMapper()->getCrosswalk()->_('fedora_dcmdate'), $this->_fdo->getData( 'dcmDate' ));
			
		} catch (Exception $e) {
// 			var_dump($e);
			throw JSpaceRepositoryError::raiseError($this, JText::sprintf('COM_JSPACE_JSPACEITEM_ERROR_CANNOT_FETCH', $this->getId()));
		}
		
		$this->_loaded = true;
	}
	
	
	protected function _getPackageUrl() {
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see JSpaceRepositoryItem::_getMetadataArray()
	 */
	protected function _getMetadataArray() {
		$crosswalk = $this->getRepository()->getMapper()->getCrosswalk();
		$arr = array();
		foreach( $this->fcGetDC()->keys() as $key ) {
			$keys = $crosswalk->getKey($key, false);	//reverse crosswalk lookup. Keys found are possible keys in crosswalk. May not be a part of this item.
			foreach( $keys as $jspaceKey ) {
				try {
					$arr[ $key ] = $this->getMetadata($jspaceKey);
				}
				catch( Exception $e ) {
					//do nothing
				}
			}
		}
		return $arr;
	}
	
	/**
	 * 
	 * @return JSpaceRepositoryCategory
	 */
	public function _getCategory() {
		return $this->getRepository()->getCategory();
	}
	

	/*
	 * DSpace specyfic functions
	*/
	public function getOryginalBundlePackageURL() {
	}
	
	/**
	 * 
	 * @return array
	 */
	protected function _getBundles() {
		$bundles = array();
		$bundles[ 'DEFAULT' ] = $this->getBundle( 'DEFAULT' );
		return $bundles;
	}
	
	/*
	 * Fedora specyfic
	 */
	
	/**
	 * 
	 * @return JSpaceRepositoryFedoraDigitalObject
	 */
	public function fcGetDigitalObject() {
		return $this->_fdo;
	}
	
	/**
	 * 
	 * @return JSpaceRepositoryFedoraDCDataStream
	 */
	public function fcGetDC() {
		return $this->_dcDatastream;
	}
	
	/**
	 * Get fedora datastreams object containing a list of datastreams in item.
	 * Fetch it if not yet present.
	 * 
	 * @return JSpaceRepositoryFedoraDatastreams
	 */
	public function fcGetDatastreams() {
		if( is_null( $this->_fcDatastreams ) ) {
			try {
				$this->_fcDatastreams = new JSpaceRepositoryFedoraDatastreams( $this );
			}
			catch( Exception $e ) {
				JSpaceLogger::log("Creating JSpaceRepositoryFedoraDatastreams object failed.");
			}
		}
		return $this->_fcDatastreams;
	}
	
	/**
	 * 
	 * @param string $dsid
	 * @return JSpaceRepositoryFedoraDatastream
	 */
	public function fcGetDatastream( $dsid ) {
		return $this->fcGetDatastreams()->getDatastream($dsid);
	}
}





