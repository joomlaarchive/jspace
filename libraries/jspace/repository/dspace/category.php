<?php
/**
 * Category class
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
 * 
 * @author Michał Kocztorz
 * @package     JSpace
 * @subpackage  Repository
 */
class JSpaceRepositoryDspaceCategory extends JSpaceRepositoryCategory
{
	/**
	 * All the communities/collections are loaded with one request. Result is placed in static variable 
	 * to avoid unnecessary requests.
	 * 
	 * @var unknown_type
	 */
	protected static $_dspaceRawCommunities = null;
	
	protected $_dspaceRawCollection = null;
	protected $_dspaceRawCommunity = null;
	
	/**
	 * 
	 * @var JSpaceRepositoryDspaceCollection
	 */
	protected $_dspaceCollection = null;
	
	protected $_dspaceParentId = 0;

	public function _init() {
		if( is_null(self::$_dspaceRawCommunities) ) {
			try {
				$resp = $this->getRepository()->restCallJSON('communities');
			} catch (Exception $e) {
				throw JSpaceRepositoryError::raiseError($this, JText::sprintf('COM_JSPACE_JSPACE_COMMUNITY_ERROR_CANNOT_FETCH', $this->getId()));
			}
			self::$_dspaceRawCommunities = $resp;
		}
		
		if( $this->_id === 0 ) {
			$this->_name = JText::_('COM_JSPACE_CATEGORY_ROOT_NAME');
		}

		$this->_dspaceSearchCommunity( self::$_dspaceRawCommunities->communities );
		
		if( $this->_id === 0 ) {
			$this->_dspaceParentId = 0;
		}
		
// 		var_dump($this->_dspaceParentId);
	}
	
	/**
	 * Recursive looking for category data in raw dspace response.
	 * 
	 * @param unknown_type $rawCommunities
	 * @return boolean
	 */
	protected function _dspaceSearchCommunity( $rawCommunities ) {
		$previousParent = $this->_dspaceParentId;
// 		echo "Previous: " .$previousParent;
		foreach( $rawCommunities as $community ) {
// 			var_dump('community_' . $community->id);
			if( $this->_id === 'community_' . $community->id ) {
				$this->_dspaceRawCommunity = $community;
				$this->_name = $community->name;
				return true;
			}
			
			$this->_dspaceParentId = 'community_' . $community->id;
// 			var_dump('sub');
			if( $this->_dspaceSearchCommunity( $community->subCommunities ) ) {
				return true;
			}
			$this->_dspaceParentId = 'community_' . $community->id;
			if( $this->_dspaceSearchCollection( $community->collections ) ) {
				return true;
			}
			$this->_dspaceParentId = $previousParent;
		}
		$this->_dspaceParentId = $previousParent;
		return false;
	}
	
	protected function _dspaceSearchCollection( $rawCollections ) {
		foreach( $rawCollections as $collection ) {
// 			var_dump('collection_' . $collection->id);
			if( $this->_id === 'collection_' . $collection->id ) {
				$this->_dspaceRawCollection = $collection;
				$this->_dspaceCollection = $this->getRepository()->dspaceGetCollection( $collection->id );
				$this->_name = $this->_dspaceCollection->getName();
				return true;
			}
		}
		return false;
	}

	protected function _getChildren() {
		$ret = array();
		$communities = array();
		$collections = array();
		if( $this->getId() !== 0 ) {
			if( !is_null( $this->_dspaceRawCommunity ) ) {
				$communities = $this->_dspaceRawCommunity->subCommunities;
				$collections = $this->_dspaceRawCommunity->collections;
			}
		}
		else {
			$communities = self::$_dspaceRawCommunities->communities;
		}
		
		if( !is_null( $communities ) ) {
			foreach( $communities as $community ) {
				$id = 'community_' . $community->id;
				$ret[ $id ] = $this->getRepository()->getCategory( $id );
			}
			foreach( $collections as $collection ) {
				$id = 'collection_' . $collection->id;
				$ret[ $id ] = $this->getRepository()->getCategory( $id );
			}
		}
		return $ret;
	}
	
	protected function _getItems( $limitstart=0 ) {
		if( !$this->dspaceIsCollection() ) {
			return array();
		}
		
		$config = JSpaceFactory::getConfig();
		$limit = (int)$config->get('limit_items');
		
		try {
			$resp = $this->getRepository()->restCallJSON('collection.items', array(
				'id'	=>$this->dspaceGetCollection()->getId(),
				'start'	=> $limitstart,
				'limit'	=> $limit,
			));
		} catch (Exception $e) {
			throw JSpaceRepositoryError::raiseError($this, JText::sprintf('COM_JSPACE_JSPACE_CATEGORY_ERROR_CANNOT_FETCH_ITEMS', $this->getId()));
		}

		$ret = array();
		foreach( $resp as $rawItem ) {
			try {
				$ret[ $rawItem->id ] = $this->getRepository()->getItem( $rawItem->id );
			}
			catch( Exception $e ) {
				//item was in list but can't load it
// 				var_dump($rawItem);
			}
		}
		return $ret;
	}
	
	protected function _getItemsCount() {
		if( !$this->dspaceIsCollection() ) {
			return 0;
		}
		
		try {
			$resp = $this->getRepository()->restCallJSON('collection.countitems', array('id'=>$this->dspaceGetCollection()->getId()));
		} catch (Exception $e) {
			throw JSpaceRepositoryError::raiseError($this, JText::sprintf('COM_JSPACE_JSPACE_CATEGORY_ERROR_CANNOT_FETCH_ITEMS_COUNT', $this->getId()));
		}
		
		return $resp;
	}
	
	protected function _getParent() {
		return $this->getRepository()->getCategory( $this->_dspaceParentId );
		
	}
	
	public function isRoot() {
		return $this->_id === 0;
	}
	
/*
 * DSpace specyfic functions.
 */
	/**
	 * Check if category is dspace collection.
	 * NOTE: use only in dspace specyfic project.
	 * 
	 * @return boolean
	 */
	public function dspaceIsCollection() {
		return !is_null( $this->_dspaceCollection);
	}
	
	/**
	 * Get JSpaceRepositoryDspaceCollection object.
	 * NOTE: use only in dspace specyfic project.
	 * 
	 * @return JSpaceRepositoryDspaceCollection
	 */
	public function dspaceGetCollection() {
		return $this->_dspaceCollection;
	}
}




