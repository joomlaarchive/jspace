<?php
defined('_JEXEC') or die;

class JSpaceViewHarvest extends JViewLegacy
{
    protected $form;

    protected $item;

    protected $state;
    
    protected $option;
    
    protected $context;

    /**
     * Display the view
     */
    public function display($tpl = null)
    {
        $this->form     = $this->get('Form');
        $this->item     = $this->get('Item');
        $this->state    = $this->get('State');
        $this->option   = JFactory::getApplication()->input->getCmd('option');
        $this->context  = $this->option.'.'.JFactory::getApplication()->input->getCmd('view');
        $this->canDo    = JSpaceHelper::getActions($this->state->get('filter.category_id'), 0, $this->option);

        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @since   1.6
     */
    protected function addToolbar()
    {
        JFactory::getApplication()->input->set('hidemainmenu', true);
        
        $user       = JFactory::getUser();
        $userId     = $user->get('id');
        $isNew      = ($this->item->id == 0);
        $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);

        $canDo      = $this->canDo;
        $discovered = $this->item->discovered;
        
        JToolbarHelper::title(JText::_('COM_JSPACE_PAGE_' . ($checkedOut ? 'VIEW_HARVEST' : ($isNew ? 
'ADD_HARVEST' : 'EDIT_HARVEST'))), 'pencil-2 harvest-add');

        if ($isNew && (count($user->getAuthorisedCategories($this->option, 'core.create')) > 0))
        {
            if ($discovered)
            {
                JToolbarHelper::apply('harvest.apply');
                JToolbarHelper::save('harvest.save');
                JToolbarHelper::save2new('harvest.save2new');
                JToolbarHelper::custom('harvest.discover', 'refresh', '', 'COM_JSPACE_HARVEST_BUTTON_REDISCOVER', false);
            }
            else
            {
                JToolbarHelper::apply('harvest.discover', 'COM_JSPACE_HARVEST_BUTTON_DISCOVER');
            }
            
            JToolbarHelper::cancel('harvest.cancel');
        }
        else
        {
            if (!$checkedOut)
            {
                if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
                {
                    if ($discovered)
                    {
                        JToolbarHelper::apply('harvest.apply');
                        JToolbarHelper::save('harvest.save');
                        
                        if ($canDo->get('core.create'))
                        {
                            JToolbarHelper::save2new('harvest.save2new');
                        }
                        
                        JToolbarHelper::custom('harvest.discover', 'refresh', '', 'COM_JSPACE_HARVEST_BUTTON_REDISCOVER', false);
                    }
                    else
                    {
                        JToolbarHelper::apply('harvest.discover', 'COM_JSPACE_HARVEST_BUTTON_DISCOVER');
                    }
                }
            }

            JToolbarHelper::cancel('harvest.cancel', 'JTOOLBAR_CLOSE');
        }

        JToolbarHelper::divider();
        JToolbarHelper::help('JHELP_CONTENT_ARTICLE_MANAGER_EDIT');
    }
}