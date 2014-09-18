<?php
/**
 * @package     JSpace.Component
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

/**
 * Models the display and management of multiple JSpace harvests.
 *
 * @package     JSpace.Component
 * @subpackage  Model
 */
class JSpaceModelHarvests extends JModelList
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id', 'h.id',
                'state', 'h.state',
                'category_id',
                'h.harvested'
            );
        }
        
        parent::__construct($config);
    }

    public function getItems()
    {
        $items = parent::getItems();
        
        for ($i=0; $i<count($items); $i++)
        {
            // format the dates using the localized format.
            $items[$i]->created = JHtml::_('date', $items[$i]->created, JText::_('DATE_FORMAT_LC4'));
            
            if ($items[$i]->harvested == JFactory::getDbo()->getNullDate())
            {
                $items[$i]->harvested = JText::_('COM_JSPACE_HARVESTS_HARVESTED_NEVER');
            }
            else
            {
                $items[$i]->harvested = JHtml::_('date', $items[$i]->harvested, JText::_('DATE_FORMAT_LC4'));
            }

            $params = new JRegistry;
            $params->loadString($items[$i]->params);
            $items[$i]->params = $params;
        }
        
        return $items;
    }
    
    protected function getListQuery()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $user = JFactory::getUser();

        $table = $this->getTable('Harvest', 'JSpaceTable');
        $fields = array();

        foreach ($table->getFields() as $field)
        {
            $fields[] = 'h.'.$db->qn($field->Field);
        }

        $query->select($this->getState('list.select', $fields));

        $query          
            ->from('#__jspace_harvests AS h');

        // Join over the users for the checked out user.
        $query->select('uc.name AS editor')
        ->join('LEFT', '#__users AS uc ON uc.id=h.checked_out');
        
        // Join over the users for the author.
        $query->select('ua.name AS author_name')
        ->join('LEFT', '#__users AS ua ON ua.id = h.created_by');
    
        // Join over the categories.
        $query->select('c.id as catid, c.title AS category_title')
        ->join('LEFT', '#__categories AS c ON c.id = h.catid');
        
        // Filter by search in title.
        $search = $this->getState('filter.search');

        if (!empty($search))
        {
            if (stripos($search, 'id:') === 0)
            {
                $query->where('h.id = ' . (int) substr($search, 3));
            }
            elseif (stripos($search, 'author:') === 0)
            {
                $search = $db->quote('%' . $db->escape(substr($search, 7), true) . '%');
                $query->where('(ua.name LIKE ' . $search . ' OR ua.username LIKE ' . $search . ')');
            }
            else
            {
                $search = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where('(h.originating_url LIKE ' . $search . ' OR h.params LIKE ' . $search . ')');
            }
        }
        
        // Filter by published state
        $state = $this->getState('filter.state');

        if (is_numeric($state))
        {
            $query->where('h.state = ' . (int)$state);
        }
        elseif ($state === '')
        {
            $query->where('(h.state=0 OR h.state=1)');
        }
        
        // Filter by category
        $categoryId = $this->getState('filter.category_id');
        
        if (is_numeric($categoryId))
        {
            $query->where('h.catid IN ('.(int)$categoryId.')');
        }
        
        return $query;
    }
}