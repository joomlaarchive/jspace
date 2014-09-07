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

/**
 * A model for retrieving a list of JSpace categories.
 *
 * @package     JSpace.Component
 * @subpackage  Model
 */
class JSpaceModelCategories extends JModelList
{
    public function __construct($config = array())
    {
        parent::__construct($config);
        
        $this->typeAlias = $this->context;
    }
    
    protected function populateState($ordering = null, $direction = null)
    {
        $app = JFactory::getApplication();
        $this->setState('filter.extension', $this->option);

        // Get the parent id if defined.
        $parentId = $app->input->getInt('id');
        $this->setState('filter.parentId', $parentId);

        $params = $app->getParams();
        $this->setState('params', $params);

        $this->setState('filter.published', 1);
        $this->setState('filter.access',    true);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id A prefix for the store id.
     *
     * @return  string  A store id.
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':'.$this->getState('filter.extension');
        $id .= ':'.$this->getState('filter.published');
        $id .= ':'.$this->getState('filter.access');
        $id .= ':'.$this->getState('filter.parentId');

        return parent::getStoreId($id);
    }

    /**
     * redefine the function an add some properties to make the styling more easy
     *
     * @return mixed An array of data items on success, false on failure.
     */
    public function getItems()
    {
        if (!count($this->get('items')))
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
            $options['countItems'] = $params->get('show_cat_num_records', 1) || !$params->get('show_empty_categories_cat', 0);
            
            $categories = JCategories::getInstance('JSpace', $options);
            
            $this->_parent = $categories->get($this->getState('filter.parentId', 'root'));
            
            if (is_object($this->_parent))
            {
                $this->set('items', $this->_parent->getChildren());
            }
            else
            {
                $this->set('items', false);
            }
        }

        return $this->get('items');
    }

    public function getParent()
    {
        if (!is_object($this->_parent))
        {
            $this->getItems();
        }
        
        return $this->_parent;
    }
}