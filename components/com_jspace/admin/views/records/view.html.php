<?php
defined('_JEXEC') or die;

/**
 * View class for a list of articles.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_content
 * @since       1.6
 */
class JSpaceViewRecords extends JViewLegacy
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
			JSpaceHelper::addSubmenu('records');
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
		}

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		$canDo = JSpaceHelper::getActions($this->state->get('filter.category_id'), 0, $this->option);
		$user  = JFactory::getUser();

		// Get the toolbar object instance
		$bar = JToolBar::getInstance('toolbar');

		JToolbarHelper::title(JText::_('COM_JSPACE_RECORDS_TITLE'), 'stack article');

		if ($canDo->get('core.create') || (count($user->getAuthorisedCategories($this->option, 'core.create'))) > 0)
		{
			JToolbarHelper::addNew('record.add');
		}

		if (($canDo->get('core.edit')) || ($canDo->get('core.edit.own')))
		{
			JToolbarHelper::editList('record.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::publish('records.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('records.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolbarHelper::archiveList('records.archive');
			JToolbarHelper::checkin('records.checkin');
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('', 'records.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::trash('records.trash');
		}

		// Add a batch button
		if ($user->authorise('core.create', $this->option) && $user->authorise('core.edit', $this->option) && 
$user->authorise('core.edit.state', $this->option))
		{
			JHtml::_('bootstrap.modal', 'collapseModal');
			$title = JText::_('JTOOLBAR_BATCH');

			// Instantiate a new JLayoutFile instance and render the batch button
			$layout = new JLayoutFile('joomla.toolbar.batch');

			$dhtml = $layout->render(array('title' => $title));
			$bar->appendButton('Custom', $dhtml, 'batch');
		}
		
		if ($user->authorise('core.admin', $this->option))
		{
			JToolbarHelper::preferences($this->option);
		}

		JToolbarHelper::help('JHELP_JSPACE_RECORDS_MANAGER');
	}
}