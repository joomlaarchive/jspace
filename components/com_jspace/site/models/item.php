<?php 
/**
 * A model that displays information about a single item and its bitstreams.
 * 
 * @author		$LastChangedBy$
 * @package		JSpace
 * @copyright	Copyright (C) 2011 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace component for Joomla!.

   The JSpace component for Joomla! is free software: you can redistribute it 
   and/or modify it under the terms of the GNU General Public License as 
   published by the Free Software Foundation, either version 3 of the License, 
   or (at your option) any later version.

   The JSpace component for Joomla! is distributed in the hope that it will be 
   useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with the JSpace component for Joomla!.  If not, see 
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 * Name							Email
 * Hayden Young					<haydenyoung@wijiti.com> 
 * Michał Kocztorz				<michalkocztorz@wijiti.com> 
 * 
 */

defined('_JEXEC') or die('Restricted access');
jimport('jspace.factory');

class JSpaceModelItem extends JModelLegacy
{
	/**
	 * 
	 * @var int
	 */
	protected $_item_id = 0;
	
	/**
	 * 
	 * @var JSpaceRepositoryItem
	 */
	protected $_item = null;
	
	/**
	 * 
	 * @param int $item_id
	 */
	public function setItemId( $item_id ) {
		$this->_item_id = $item_id;
	}
	
	public function getItem() {
		if( is_null( $this->_item ) ) {
			try {
				$this->_item = JSpaceFactory::getRepository()->getItem( $this->_item_id );
			}
			catch( Exception $e ) {
				//
			}
		}
		
		return $this->_item;
	}
	
	public function getItemMetadataKeyTranslation( $key ) {
		$config = JSpaceFactory::getConfig();
		$show_keys = (bool)$config->get('show_translation_keys',false);
		$tkey = 'COM_JSPACE_ITEM_METADATA_' . strtoupper( JSpaceFactory::getRepository()->getMapper()->getCrosswalk()->getKey($key) );
		$translated = JText::_( $tkey );
		return  $translated !== $tkey || $show_keys ? $translated : $key;
	}
	
	public function formatFileSize($size)
	{
		if ($size > 1024) {
			return intval($size/1024) . JText::_("Kb");
		} else {
			return $size . JText::_("bytes");
		}
	}
}