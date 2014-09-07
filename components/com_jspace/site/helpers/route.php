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
	
    public static function getCategoryRoute($catid, $language = 0)
    {
        if ($catid instanceof JCategoryNode)
        {
            $id = $catid->id;
            $category = $catid;
        }
        else
        {
            $id = (int) $catid;
            $category = JCategories::getInstance('JSpace')->get($id);
        }

        if ($id < 1 || !($category instanceof JCategoryNode))
        {
            $link = '';
        }
        else
        {
            $needles = array();

            // Create the link
            $link = 'index.php?option=com_jspace&view=category&id='.$id;

            $catids = array_reverse($category->getPath());
            $needles['category'] = $catids;
            $needles['categories'] = $catids;

            if ($language && $language != "*" && JLanguageMultilang::isEnabled())
            {
                self::buildLanguageLookup();

                if (isset(self::$lang_lookup[$language]))
                {
                    $link .= '&lang=' . self::$lang_lookup[$language];
                    $needles['language'] = $language;
                }
            }

            if ($item = self::_findItem($needles))
            {
                $link .= '&Itemid='.$item;
            }
        }

        return $link;
    }

    public static function getRecordRoute($id, $catid = 0, $language = 0)
    {
        $needles = array(
            'record'  => array((int) $id)
        );

        // Create the link
        $link = 'index.php?option=com_jspace&view=record&id=' . $id;

        if ((int) $catid > 1)
        {
            $categories = JCategories::getInstance('JSpace');
            $category   = $categories->get((int) $catid);

            if ($category)
            {
                $needles['category']   = array_reverse($category->getPath());
                $needles['categories'] = $needles['category'];
                $link .= '&catid=' . $catid;
            }
        }

        if ($language && $language != "*" && JLanguageMultilang::isEnabled())
        {
            self::buildLanguageLookup();

            if (isset(self::$lang_lookup[$language]))
            {
                $link .= '&lang=' . self::$lang_lookup[$language];
                $needles['language'] = $language;
            }
        }

        if ($item = self::_findItem($needles))
        {
            $link .= '&Itemid=' . $item;
        }

        return $link;
    }
	
    protected static function buildLanguageLookup()
    {
        if (count(self::$lang_lookup) == 0)
        {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->select('a.sef AS sef')
                ->select('a.lang_code AS lang_code')
                ->from('#__languages AS a');

            $db->setQuery($query);
            $langs = $db->loadObjectList();

            foreach ($langs as $lang)
            {
                self::$lang_lookup[$lang->lang_code] = $lang->sef;
            }
        }
    }

    protected static function _findItem($needles = null)
    {
        $app        = JFactory::getApplication();
        $menus      = $app->getMenu('site');
        $language   = isset($needles['language']) ? $needles['language'] : '*';

        // Prepare the reverse lookup array.
        if (!isset(self::$lookup[$language]))
        {
            self::$lookup[$language] = array();

            $component  = JComponentHelper::getComponent('com_jspace');

            $attributes = array('component_id');
            $values = array($component->id);

            if ($language != '*')
            {
                $attributes[] = 'language';
                $values[] = array($needles['language'], '*');
            }

            $items = $menus->getItems($attributes, $values);

            if ($items)
            {
                foreach ($items as $item)
                {
                    if (isset($item->query) && isset($item->query['view']))
                    {
                        $view = $item->query['view'];
                        if (!isset(self::$lookup[$language][$view]))
                        {
                            self::$lookup[$language][$view] = array();
                        }
                        if (isset($item->query['id']))
                        {

                            // here it will become a bit tricky
                            // language != * can override existing entries
                            // language == * cannot override existing entries
                            if (!isset(self::$lookup[$language][$view][$item->query['id']]) || $item->language != '*')
                            {
                                self::$lookup[$language][$view][$item->query['id']] = $item->id;
                            }
                        }
                    }
                }
            }
        }

        if ($needles)
        {
            foreach ($needles as $view => $ids)
            {
                if (isset(self::$lookup[$language][$view]))
                {
                    foreach ($ids as $id)
                    {
                        if (isset(self::$lookup[$language][$view][(int) $id]))
                        {
                            return self::$lookup[$language][$view][(int) $id];
                        }
                    }
                }
            }
        }

        // Check if the active menuitem matches the requested language
        $active = $menus->getActive();
        if ($active && ($language == '*' || in_array($active->language, array('*', $language)) || !JLanguageMultilang::isEnabled()))
        {
            return $active->id;
        }

        // If not found, return language specific home link
        $default = $menus->getDefault($language);
        return !empty($default->id) ? $default->id : null;
    }
}