<?php
/**
 * @package     JSpace.Component
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 *
 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 *
 * Name                         Email
 * Michał Kocztorz              <michalkocztorz@wijiti.com> 
 * Hayden Young                 <haydenyoung@wijiti.com>
 */
 
defined('_JEXEC') or die;

JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/tables');

/**
 * A model for retrieving a single JSpace category.
 *
 * @package     JSpace.Component
 * @subpackage  Model
 */
class JSpaceModelCategory extends JModelList
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id', 'r.id',
                'title', 'r.title',
                'numarticles', 'r.numarticles',
                'link', 'r.link',
                'ordering', 'r.ordering',
            );
        }

        parent::__construct($config);
    }
    
    public function getItems()
    {
        $limit = $this->getState('list.limit');

        if ($this->get('records') === null && $category = $this->getCategory())
        {
            $model = JModelLegacy::getInstance('Records', 'JSpaceModel', array('ignore_request'=>true));
            $model->setState('params', JFactory::getApplication()->getParams());
            $model->setState('filter.category_id', $category->id);
            $model->setState('filter.published', $this->getState('filter.published'));
            $model->setState('filter.access', $this->getState('filter.access'));
            $model->setState('filter.language', $this->getState('filter.language'));
            $model->setState('list.start', $this->getState('list.start'));
            $model->setState('list.limit', $limit);
            $model->setState('list.direction', $this->getState('list.direction'));
            $model->setState('list.filter', $this->getState('list.filter'));
            // filter.subcategories indicates whether to include articles from subcategories in the list or blog
            $model->setState('filter.subcategories', $this->getState('filter.subcategories'));
            $model->setState('filter.max_category_levels', $this->getState('filter.max_category_levels'));
            $model->setState('list.links', $this->getState('list.links'));

            if ($limit >= 0)
            {
                $this->set('records', $model->getItems());

                if ($this->get('records') === false)
                {
                    $this->setError($model->getError());
                }
            }
            else
            {
                $this->set('records', array());
            }

            $this->_pagination = $model->getPagination();
        }

        return $this->get('records');
    }
    
    protected function getListQuery()
    {
        $user = JFactory::getUser();
        $groups = implode(',', $user->getAuthorisedViewLevels());

        // Create a new query object.
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        // Select required fields from the categories.
        $query->select($this->getState('list.select', 'r.*'))
            ->from($db->quoteName('#__jspace_records') . ' AS r')
            ->where('r.access IN (' . $groups . ')');

        // Filter by category.
        if ($categoryId = $this->getState('category.id'))
        {
            $query->where('r.catid = ' . (int) $categoryId)
                ->join('LEFT', '#__categories AS c ON c.id = r.catid')
                ->where('c.access IN (' . $groups . ')');
        }

        // Filter by state
        $state = $this->getState('filter.published');
        if (is_numeric($state))
        {
            $query->where('r.published = ' . (int) $state);
        }

        // Filter by start and end dates.
        $nullDate = $db->quote($db->getNullDate());
        $date = JFactory::getDate();
        $nowDate = $db->quote($date->format($db->getDateFormat()));

        if ($this->getState('filter.publish_date'))
        {
            $query->where('(r.publish_up = ' . $nullDate . ' OR r.publish_up <= ' . $nowDate . ')')
                ->where('(r.publish_down = ' . $nullDate . ' OR r.publish_down >= ' . $nowDate . ')');
        }

        // Filter by search in title
        $search = $this->getState('list.filter');
        if (!empty($search))
        {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where('(r.title LIKE ' . $search . ')');
        }

        // Filter by language
        if ($this->getState('filter.language'))
        {
            $query->where('r.language in (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
        }

        // Add the list ordering clause.
        $query->order($db->escape($this->getState('list.ordering', 'r.ordering')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

        return $query;
    }
    
    protected function populateState($ordering = null, $direction = null)
    {
        $app = JFactory::getApplication();
        $params = JComponentHelper::getParams('com_jspace');

        // List state information
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'), 'uint');
        $this->setState('list.limit', $limit);

        $limitstart = $app->input->get('limitstart', 0, 'uint');
        $this->setState('list.start', $limitstart);

        // Optional filter text
        $this->setState('list.filter', $app->input->getString('filter-search'));

        $orderCol = $app->input->get('filter_order', 'ordering');
        if (!in_array($orderCol, $this->filter_fields))
        {
            $orderCol = 'ordering';
        }
        $this->setState('list.ordering', $orderCol);

        $listOrder = $app->input->get('filter_order_Dir', 'ASC');
        if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
        {
            $listOrder = 'ASC';
        }
        $this->setState('list.direction', $listOrder);

        $id = $app->input->get('id', 0, 'int');
        $this->setState('category.id', $id);

        $user = JFactory::getUser();
        if ((!$user->authorise('core.edit.state', 'com_jspace')) && (!$user->authorise('core.edit', 'com_jspace')))
        {
            // limit to published for people who can't edit or edit.state.
            $this->setState('filter.published', 1);

            // Filter by start and end dates.
            $this->setState('filter.publish_date', true);
        }

        $this->setState('filter.language', JLanguageMultilang::isEnabled());

        // Load the parameters.
        $this->setState('params', $params);
    }
    
    public function getCategory()
    {
        if (!is_object($this->get('item')))
        {
            $app = JFactory::getApplication();
            $menu = $app->getMenu();
            $active = $menu->getActive();
            $params = new JRegistry;

            if ($active)
            {
                $params->loadString($active->params);
            }

            $options = array();
            $options['countItems'] = $params->get('show_cat_items', 1) || $params->get('show_empty_categories', 0);
            
            $categories = JCategories::getInstance('JSpace', $options);
            
            $this->set('item', $categories->get($this->getState('category.id', 'root')));
            
            if (is_object($this->get('item')))
            {
                $this->set('children', $this->get('item')->getChildren());
                $this->set('parent', false);
                if ($this->get('item')->getParent())
                {
                    $this->set('parent', $this->get('item')->getParent());
                }
                $this->set('rightsibling', $this->get('item')->getSibling());
                $this->set('leftsibling', $this->get('item')->getSibling(false));
            }
            else
            {
                $this->set('children', false);
                $this->set('parent', false);
            }
        }

        return $this->get('item');
    }
    
    /**
     * Get the parent category
     *
     * @param   integer  An optional category id. If not supplied, the model state 'category.id' will be used.
     *
     * @return  mixed  An array of categories or false if an error occurs.
     */
    public function getParent()
    {
        if (!is_object($this->get('item')))
        {
            $this->getCategory();
        }
        
        return $this->get('parent');
    }

    /**
     * Get the sibling (adjacent) categories.
     *
     * @return  mixed  An array of categories or false if an error occurs.
     */
    public function getLeftSibling()
    {
        if (!is_object($this->get('item')))
        {
            $this->getCategory();
        }

        return $this->get('leftsibling');
    }

    public function getRightSibling()
    {
        if (!is_object($this->get('item')))
        {
            $this->getCategory();
        }
        
        return $this->get('rightsibling');
    }

    /**
     * Get the child categories.
     *
     * @param   integer  An optional category id. If not supplied, the model state 'category.id' will be used.
     *
     * @return  mixed  An array of categories or false if an error occurs.
     */
    public function getChildren()
    {
        if (!is_object($this->get('item')))
        {
            $this->getCategory();
        }

        return $this->get('children');
    }
    
    public function hit($pk = 0)
    {
        $input    = JFactory::getApplication()->input;
        $hitcount = $input->getInt('hitcount', 1);

        if ($hitcount)
        {
            $pk    = (!empty($pk)) ? $pk : (int) $this->getState('category.id');
            $table = JTable::getInstance('Category', 'JTable');
            $table->load($pk);
            $table->hit($pk);
        }

        return true;
    }
}