<?php
/**
 * 
 * @author		$LastChangedBy$
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
 * 
 */

/**
 * @param	array
 * @return	array
 */
function JSpaceBuildRoute(&$query)
{
	$segments = array();

	// if no item id specified, try and get it.
	if (!JArrayHelper::getValue($query, "Itemid")) {
		$application = JFactory::getApplication("site");
		$menus = $application->getMenu();
		$items = $menus->getItems("link", "index.php?option=com_jspace&view=".$query["view"]);

		if (count($items) > 0) {
			$query["Itemid"] = $items[0]->id;
		}
	}
	
	if (isset($query['view'])) {
		$segments[] = JArrayHelper::getValue($query, "view");
		unset($query['view']);
	}

	if (isset($query['id'])) {
		$segments[] = JArrayHelper::getValue($query, "id");
		unset($query['id']);
	}
		
	return $segments;
}

/**
 * @param	array
 * @return	array
 */
function JSpaceParseRoute($segments)
{
	$vars = array();

	$vars['option'] = 'com_jspace';

	if (count($segments) == 2) {
		if ($var = array_shift($segments)) {
			$vars['view'] = $var;	
		}
	}

	if ($var = array_shift($segments)) {
		$vars['id'] = $var;	
	}

	return $vars;
}