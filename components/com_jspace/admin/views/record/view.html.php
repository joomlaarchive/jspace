<?php
defined('_JEXEC') or die;

class JSpaceViewRecord extends JViewLegacy
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
		$this->form		= $this->get('Form');
		$this->item		= $this->get('Item');
		$this->state	= $this->get('State');
		$this->option	= JFactory::getApplication()->input->getCmd('option');
		$this->context	= $this->option.'.'.JFactory::getApplication()->input->getCmd('view');
		$this->canDo	= JSpaceHelper::getActions($this->state->get('filter.category_id'), 0, $this->option);

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

		$user		= JFactory::getUser();
		$userId		= $user->get('id');
		$isNew		= ($this->item->id == 0);
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $userId);

		// Built the actions for new and existing records.
		$canDo		= $this->canDo;
			JToolbarHelper::title(JText::_('COM_JSPACE_PAGE_' . ($checkedOut ? 'VIEW_RECORD' : ($isNew ?
'ADD_RECORD' : 'EDIT_RECORD'))), 'pencil-2 record-add');

		// For new records, check the create permission.
		if ($isNew && (count($user->getAuthorisedCategories($this->option, 'core.create')) > 0))
		{
			JToolbarHelper::apply('record.apply');
			JToolbarHelper::save('record.save');
			JToolbarHelper::save2new('record.save2new');
			JToolbarHelper::cancel('record.cancel');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
					JToolbarHelper::apply('record.apply');
					JToolbarHelper::save('record.save');

					if ($canDo->get('core.create')) {
						JToolbarHelper::save2new('record.save2new');
					}
				}
			}

			// If checked out, we can still save
			if ($canDo->get('core.create')) {
				JToolbarHelper::save2copy('record.save2copy');
			}

			if ($this->state->params->get('save_history', 1) && $user->authorise('core.edit')) {
				JToolbarHelper::versions($this->context, $this->item->id);
			}

			JToolbarHelper::cancel('record.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolbarHelper::divider();
		JToolbarHelper::help('JHELP_CONTENT_ARTICLE_MANAGER_EDIT');
	}
}