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
class JSpaceRepositoryFedoraCategory extends JSpaceRepositoryCategory
{
	public function _init() {
		$this->_name = JText::_('COM_JSPACE_CATEGORY_ROOT_NAME');
	}

	protected function _getChildren() {
		$ret = array();
		return $ret;
	}
	
	protected function _getItems( $limitstart=0 ) {
		try {
// 			$endpoint = JSpaceFactory::getEndpoint('objects?query=' . urlencode('pid=*'), array(
// 						//not stored in DC
// 						'label'			=> 'true',
// 						'state'			=> 'true',
// 						'ownerId'		=> 'true',
// 						'cDate'			=> 'true',
// 						'mDate'			=> 'true',
// 						'dcmDate'		=> 'true',
// 						'pid'			=> 'true',
// 						'maxResults' 	=> '10',
// 						'resultFormat'	=> 'xml'
// 				)
// 			);
			
// 			$endpoint = JSpaceFactory::getEndpoint('objects?query=' . urlencode('pid=' . base64_decode($this->getId()) ), array(
			$vars = array(
				'label'			=> 'true',
				'state'			=> 'true',
				'ownerId'		=> 'true',
				'cDate'			=> 'true',
				'mDate'			=> 'true',
				'dcmDate'		=> 'true',
				'query'			=> '',
				'pid'			=> 'true',
				'maxResults' 	=> '10',
				'page' 			=> '1',
				'resultFormat'	=> 'xml'
			);
			
			
			$endpoint = JSpaceFactory::getEndpoint('objects?' . http_build_query($vars));
			$resp = $this->getRepository()->getConnector()->get($endpoint);
			$this->_fcRaw = $resp;
				
			echo $this->_fcRaw; exit;
// 			$this->_fdo = new JSpaceRepositoryFedoraDigitalObject( $resp );
				
			//load DC datastream
// 			$endpoint = JSpaceFactory::getEndpoint('objects/' . urlencode(base64_decode($this->getId())) . '/datastreams/DC/content' );
// 			$resp = $this->getRepository()->getConnector()->get($endpoint);
// 			$this->_dcDatastream = new JSpaceRepositoryFedoraDCDataStream( $resp );
				
// 			$this->_dcDatastream->set( $this->getRepository()->getMapper()->getCrosswalk()->_('fedora_label'), $this->_fdo->getData( 'label' ));
// 			$this->_dcDatastream->set( $this->getRepository()->getMapper()->getCrosswalk()->_('fedora_state'), $this->_fdo->getData( 'state' ));
// 			$this->_dcDatastream->set( $this->getRepository()->getMapper()->getCrosswalk()->_('fedora_ownerid'), $this->_fdo->getData( 'ownerId' ));
// 			$this->_dcDatastream->set( $this->getRepository()->getMapper()->getCrosswalk()->_('date_issued'), $this->_fdo->getData( 'cDate' ));
// 			$this->_dcDatastream->set( $this->getRepository()->getMapper()->getCrosswalk()->_('fedora_mdate'), $this->_fdo->getData( 'mDate' ));
// 			$this->_dcDatastream->set( $this->getRepository()->getMapper()->getCrosswalk()->_('fedora_dcmdate'), $this->_fdo->getData( 'dcmDate' ));
				
		} catch (Exception $e) {
						var_dump($e);exit;
			throw JSpaceRepositoryError::raiseError($this, JText::sprintf('COM_JSPACE_JSPACE_CATEGORY_ERROR_CANNOT_FETCH', $this->getId()));
		}
		$ret = array();
		return $ret;
	}
	
	protected function _getItemsCount() {
	}
	
	protected function _getParent() {
		return $this->getRepository()->getCategory(); //just to return any category object
	}
	
	public function isRoot() {
		return true;
	}
}




