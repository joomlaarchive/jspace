<?php
/**
 * A repository item class.
 * Contains a business logic for particular repository type.
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
 * JSpace connector endpoint class.
 *
 * @package     JSpace
 * @subpackage  Connector
 */
class JSpaceRepositoryDspaceItem extends JSpaceRepositoryItem
{
	/**
	 * DSpace specyfic. Raw data object retrieved from DSpace.
	 * @var Object
	 */
	protected $_dspaceRaw = null;
	
	/**
	 * DSpace specyfic. Raw data object retrieved from DSpace when requesting for bundles.
	 * @var Object
	 */
	protected $_dspaceRawBundles = null;
	
	
	/**
	 * (non-PHPdoc)
	 * @see JSpaceRepositoryItem::_load()
	 */
	protected function _load() {
		try {
			$resp = $this->getRepository()->restCallJSON('item',array('id'=>$this->getId()));
		} catch (Exception $e) {
			throw JSpaceRepositoryError::raiseError($this, JText::sprintf('COM_JSPACE_JSPACEITEM_ERROR_CANNOT_FETCH', $this->getId()));
		}
		$this->_dspaceRaw = $resp;
		$this->_loaded = true;
	}
	
	
	protected function _getPackageUrl() {
		return $this->getOryginalBundlePackageURL();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see JSpaceRepositoryItem::_getMetadataArray()
	 */
	protected function _getMetadataArray() {
		$rawMetadata = $this->_dspaceRaw->metadata;
		$crosswalk = $this->getRepository()->getMapper()->getCrosswalk();
		$arr = array();
		foreach( $rawMetadata as $meta ) {
			$key =  $meta->schema . "." . $meta->element . (is_null($meta->qualifier)?"":("." . $meta->qualifier)); //build the key
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
	 * @deprecated
	 * (non-PHPdoc)
	 * @see JSpaceRepositoryItem::_getCollection()
	 */
	protected function _getCollection() {
		return $this->getRepository()->dspaceGetCollection( $this->_dspaceRaw->collection->id );
	}
	
	/**
	 * 
	 * @return JSpaceRepositoryCategory
	 */
	public function _getCategory() {
		return $this->getRepository()->getCategory( 'collection_'. $this->_dspaceRaw->collection->id );
	}
	
	/**
	 * It is public only for the JSpaceRepositoryDspaceBundle object to use it.
	 * 
	 * @return object
	 */
	public function dspaceGetBundles() {
		return $this->_dspaceGetBundles();
	}
	
	/**
	 * Get raw bundles object. Load if not loaded yet.
	 * 
	 * Bundles are loaded in item because DSpace allows to get all bundle information at once. 
	 * Each bundle object doesn't have to request dspace by itself.
	 */
	protected function _dspaceGetBundles() {
		if( is_null( $this->_dspaceRawBundles ) ) {
			try {
				$this->_dspaceRawBundles = $this->getRepository()->restCallJSON('item.bundles', array('id'=>$this->getId()));
			} catch (Exception $e) {
				throw JSpaceRepositoryError::raiseError( $this, JText::sprintf('COM_JSPACE_JSPACEITEM_ERROR_CANNOT_FETCH_BUNDLE', $this->getId()) );
			}
		}
		
		return $this->_dspaceRawBundles;
	}
	
	/**
	 * For backwards compatibility. Returns dspace raw object.
	 */
	public function dspaceGetRaw() {
		if( $this->isLoaded() ) {
			return $this->_dspaceRaw;
		}
		throw new Exception($this->getError());
	}
	

	/*
	 * DSpace specyfic functions
	*/
	public function getOryginalBundlePackageURL() {
		return $this->getRepository()->getConnector()->getRepositoryUrl() . '/items/' . $this->getId() . '/package';
	}
	
	/**
	 * 
	 * @return array
	 */
	protected function _getBundles() {
		$bundles = array();
		$rawBundles = $this->_dspaceGetBundles();
		foreach( $rawBundles as $bundle ) {
			$bundles[ $bundle->name ] = $this->getBundle( $bundle->name );
		}
		
		return $bundles;
	}
	
	/**
	 * Uses https://jspace.atlassian.net/wiki/display/DSPACEAPI/PUT_items_id_metadata
	 * to update all existing metadata in repository item.
	 */
	public function dspaceUpdateMetadata( $metadata=array(), $removeMetadata=array() ) {
		/*
		 * Prepare Key => array of values
		 * Where key is crosswalked value e.g. 'dc.title'.
		 */
		$crosswalked = array();
		foreach( $metadata as $key => $arr ) {
			$ckey = $this->getCrosswalkValue($key);
			if( !isset($crosswalked[$ckey]) ) {
				$crosswalked[$ckey] = array();
			}
			$crosswalked[$ckey] = array_merge($crosswalked[$ckey], $arr);
		}
		
		
		$toParse = array(
			"metadata" => array()
		);

		/* @var $val JSpaceRepositoryDspaceMetadata */
		foreach( $this->getMetadata() as $ckey2 => $val) {
			/*
			 * Add current metadata but only if it is not yet set.
			 */
			if( !isset($crosswalked[$ckey2]) ) {
				$crosswalked[$ckey2] = $val->getValues();
			}
		}
		
		/*
		 * WARNING: this may remove too much in some cases.
		 * Example:
		 * In crosswalk:
		 * 'author': 'dc.author'
		 * 'contributor': 'dc.contributor'
		 * 
		 * We get:
		 * 'author' => 'John Doe'
		 * 'contributor' => 'Jane Doe'
		 * 
		 * After crosswalking we get:
		 * 'dc.author' => array('John Doe', 'Jane Doe')
		 * 
		 * If we would get:
		 * $removeMetadata = array('contributor');
		 * 
		 * Then it will get crosswalked to: 'dc.author' and it would remove both 'author' and 'contributor'.
		 * 
		 */
		
		foreach($removeMetadata as $key) {
			$ckey3 = $this->getCrosswalkValue($key);
			if( isset( $crosswalked[$ckey3] ) ) {
				unset( $crosswalked[$ckey3] );
			}
		}
		
		foreach( $crosswalked as $key => $val ) {
			foreach( $val as $singleVal ) {
				$toParse["metadata"][] = array("name"=>$key,"value"=>$singleVal);
			}
		}
		$json = json_encode($toParse);
		try {
			$endpoint = $this->getRepository()->getRestAPI()->getEndpoint('updateitem',array('id'=>$this->getId(),'data'=>$json));
			$connector = $this->getRepository()->getConnector();
			$connector->put($endpoint);
			return true;
		}
		catch( Exception $e ) {
			JSpaceLogger::log("Edit item failed: " . $e->getMessage());
			return false;
		}
	}
}





