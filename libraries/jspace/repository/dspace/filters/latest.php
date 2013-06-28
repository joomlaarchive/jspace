<?php
/**
 * A filter class.
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
jimport('jspace.factory');

/**
 * @package     JSpace
 * @subpackage  Repository
 */
class JSpaceRepositoryDspaceFiltersLatest extends JSpaceRepositoryFilter
{
	/**
	 * ToDo: Filtering is not done yet (filterBy option)
	 * ToDo: can sort for multiple columns?
	 * 
	 * (non-PHPdoc)
	 * @see JSpaceRepositoryFilter::getItems()
	 */
	public function getItems() {
		$vars = array(
			'start'	=> $this->getLimitstart(),
			'rows'	=> $this->getLimit(),
			'q'		=> '*',
			'fq'	=> 'search.resourcetype:2'
		);
		
		$sort = $this->_getSortString();
		if( $sort != '' ) {
			$vars['sort'] = $sort;
		}
		
		try {
			$response = $this->getRepository()->restCallJSON('discover',$vars);
			if (isset($response->response)) {
				$docs = $response->response->docs;
			}
			else {
				return array();
			}
			
			$items = array();
			foreach( $docs as $item ) {
				$id = $item->{'search.resourceid'};
				try {
					if( isset( $id ) ) {
						$items[ $id ] = $this->getRepository()->getItem( $id );
					}
					else {
						JSpaceRepositoryError::raiseError($this, JText::_('COM_JSPACE_REPOSITORY_FILTER_ITEM_NOT_FOUND ')  . $id);
					}
				}
				catch( Exception $ex ) {
					//item not found, keep loading other
					JSpaceRepositoryError::raiseError($this, JText::_('COM_JSPACE_REPOSITORY_FILTER_ITEM_NOT_FOUND') . $id);
				}
			}
			return $items;
		} catch (Exception $e) {
			JSpaceRepositoryError::raiseError($this, JText::_('COM_JSPACE_REPOSITORY_CANT_FILTER_ITEMS'));
			JSpaceRepositoryError::raiseError($this, JText::_($e->getMessage()));
			return array();
		}
	}
	
	protected function _getSortString() {
		$crosswalk = $this->getRepository()->getMapper()->getCrosswalk();
		
		$sort = $this->getSortBy();
		$ret = '';
		foreach( $sort as $row ) {
			$key = $crosswalk->_( $row[0] );
			$dir = $row[1];
			$ret = $key . '_dt+' . $dir;
		}
		return $ret;
	}
}




