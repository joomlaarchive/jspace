<?php
/**
 * @copyright	Copyright (C) 2011-2013 Wijiti Pty Ltd. All rights reserved.
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
        $app    = JFactory::getApplication();
        $menu   = $app->getMenu();
        $view = JArrayHelper::getValue($query, 'view');

        if (!JArrayHelper::getValue($query, "Itemid")) {
                $menuItem = $menu->getActive();
        } else {
                $menuItem = $menu->getItem(JArrayHelper::getValue($query, 'Itemid'));
        }

        $mView = JArrayHelper::getValue($menuItem->query, 'view', null);
        $mId = JArrayHelper::getValue($menuItem->query, 'id', null);

        if ($view) {
                if (!JArrayHelper::getValue($query, 'Itemid') || $view != $mView) {
                        $segments[] = JArrayHelper::getValue($query, 'view');
                }

                unset($query['view']);
        }

        if ($view && $mView == $view) {
                if ($mId == JArrayHelper::getValue($query, 'id', 0, 'int')) {
                        unset($query['view']);
                        unset($query['id']);

                        return $segments;
                } else {
                        $segments[] = $query['id'];

                        unset($query['id']);
                }
        } else {
                // check the querystring. If the current view matches the querystring, set the querystring id (if it exists).
                if ($mView == $app->input->get('view', null) && $mView == 'item') {
                        $segments[] = $app->input->get('id', null);
                }
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

        $app    = JFactory::getApplication();
        $menu   = $app->getMenu();
        $item   = $menu->getActive();

        // Count route segments
        $count = count($segments);
        if (!isset($item)) {
                $vars['view'] = JArrayHelper::getValue($segments, 0);

                if ($count > 1) {
                        $vars['id'] = JArrayHelper::getValue($segments, $count - 1);
                }

                return $vars;
        }

        // if count is 2 then we have all the vars we require to parse the route.
        if (count($segments) == 2) {
                if ($var = array_shift($segments)) {
                        $vars['view'] = $var;
                }

                if ($var = array_shift($segments)) {
                        $vars['id'] = $var;
                }
        }

        // if the count is 1 then we need to check whether we need to pull the view,
        // or whether the view is already available as part of the active menu.
        if (count($segments) == 1) {
                $vars['view'] = JArrayHelper::getValue($item->query, 'view');

                $var = array_shift($segments);
                $vars['id'] = $var;
        }

        return $vars;
}