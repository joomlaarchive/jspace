<?php
/**
 * @package		JSpace
 * @subpackage  Search
 * @copyright	Copyright (C) 2012 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSolrSearch component for Joomla!.

   The JSolrSearch component for Joomla! is free software: you can redistribute it 
   and/or modify it under the terms of the GNU General Public License as 
   published by the Free Software Foundation, either version 3 of the License, 
   or (at your option) any later version.

   The JSolrSearch component for Joomla! is distributed in the hope that it will be 
   useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with the JSolrSearch component for Joomla!.  If not, see 
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 * Name							Email
 * Michał Kocztorz				<michalkocztorz@wijiti.com> 
 * 
 */

// no direct access
defined('_JEXEC') or die;

/**
 * JSolrSearch Component Route Helper
 *
 * @static
 * @package		JSolr
 * @subpackage  Search
 */
abstract class JSpaceHelperRoute
{
	protected static $lookup = array();
	
	public static function getCategoryUrl( $id ) {
		$Itemid = self::_findItem('categories');
		
		$link = new JURI('index.php');
		$link->setVar('option', 'com_jspace');
		$link->setVar('view', 'categories');
		$link->setVar('id', $id);
		$link->setVar('Itemid', $Itemid);
		return (string)$link;
	}
	
	/**
	 * Get full route to item (with domain name).
	 * @param mixed $id
	 */
	public static function getItemFullRoute( $id ) {
		return JURI::getInstance()->toString(array('scheme', 'host', 'port')) . JRoute::_( self::getItemRoute($id) );
	}

	public static function getItemUrl( $id ) {
		$Itemid = self::_findItem('item');
		
		$link = new JURI( 'index.php');
		$link->setVar('option', 'com_jspace');
		$link->setVar('view', 'item');
		$link->setVar('id', $id);
		$link->setVar('Itemid', $Itemid);
		return (string)$link; 
	}
	
	protected static function _findItem($view = 'basic')
 	{
		$app = JFactory::getApplication();
		$menus = $app->getMenu('site');
		$found = false;
		$itemId = 0;
		

		if( !isset(self::$lookup[$view]) ) {
			$component = JComponentHelper::getComponent('com_jspace');
			$items = $menus->getItems('component_id', $component->id);

			while (($item = current($items)) && !$found) {
				if (isset($item->query) && isset($item->query['view'])) {
					if ($view == $item->query['view']) {
						$found = true;
						self::$lookup[$view] = $item->id;					
					}
				}
				
				next($items);
			}
		}

		if ($itemId = JArrayHelper::getValue(self::$lookup, $view, null)) {
			return $itemId;
		} else {
			$active = $menus->getActive();
			
			if ($active) {
				return $active->id;
			}
		}

		return null;
	}
}