<?php
/**
 * @package     JSpace.Component
 * @subpackage  View
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

/**
 * Provides a view for displaying and managing multiple JSpace harvests.
 *
 * @package     JSpace.Component
 * @subpackage  View
 */
class JSpaceViewHarvests extends JViewLegacy
{
    protected $items;

    protected $pagination;

    protected $state;
    
    protected $option;
    
    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        if ($this->getLayout() !== 'modal')
        {
            JSpaceHelper::addSubmenu('harvests');
        }
        
        $this->option = JFactory::getApplication()->input->getCmd('option');
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->authors       = $this->get('Authors');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            JError::raiseError(500, implode("\n", $errors));

            return false;
        }

        // We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal')
        {
            $this->addToolbar();
            $this->sidebar = JHtmlSidebar::render();
        }

        parent::display($tpl);
    }
    
    protected function addToolbar()
    {
        $canDo = JSpaceHelper::getActions($this->state->get('filter.category_id'), 0, $this->option);
        $user  = JFactory::getUser();

        // Get the toolbar object instance
        $bar = JToolBar::getInstance('toolbar');

        JToolbarHelper::title(JText::_('COM_JSPACE_HARVESTS_TITLE'), 'stack article');

        if ($canDo->get('core.create') || (count($user->getAuthorisedCategories($this->option, 'core.create'))) > 0)
        {
            JToolbarHelper::addNew('harvest.add');
        }

        if (($canDo->get('core.edit')) || ($canDo->get('core.edit.own')))
        {
            JToolbarHelper::editList('harvest.edit');
        }

        if ($canDo->get('core.edit.state'))
        {
            JToolbarHelper::publish('harvests.publish', 'JTOOLBAR_PUBLISH', true);
            JToolbarHelper::unpublish('harvests.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            JToolbarHelper::checkin('harvests.checkin');
        }

        if ($this->state->get('filter.state') == -2 && $canDo->get('core.delete'))
        {
            JToolbarHelper::deleteList('', 'harvests.delete', 'JTOOLBAR_EMPTY_TRASH');
        }
        elseif ($canDo->get('core.edit.state'))
        {
            JToolbarHelper::trash('harvests.trash');
        }
        
        if ($user->authorise('core.admin', $this->option))
        {
            JToolbarHelper::preferences($this->option);
        }

        JToolbarHelper::help('JHELP_JSPACE_HARVESTS_MANAGER');
    }
}