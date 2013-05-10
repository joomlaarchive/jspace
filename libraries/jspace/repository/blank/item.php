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
 *
 * @package     JSpace
 * @subpackage  Connector
 */
class JSpaceRepositoryBlankItem extends JSpaceRepositoryItem
{
	/**
	 * (non-PHPdoc)
	 * @see JSpaceRepositoryItem::_load()
	 */
	protected function _load() {
// 		try {
// 			$endpoint = JSpaceFactory::getEndpoint('/items/'. $this->getId() .'.json');
// 			$resp = json_decode($this->getRepository()->getConnector()->get($endpoint));
// 		} catch (Exception $e) {
// 			throw JSpaceRepositoryError::raiseError($this, JText::sprintf('COM_JSPACE_JSPACEITEM_ERROR_CANNOT_FETCH', $this->getId()));
// 		}
// 		$this->_dspaceRaw = $resp;
// 		$this->_loaded = true;
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
		return $bundles;
	}
}





