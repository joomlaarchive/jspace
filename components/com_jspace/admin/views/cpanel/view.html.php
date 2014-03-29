<?php
defined('_JEXEC') or die;

class JSpaceViewCPanel extends JViewLegacy
{
	protected $option;
	
	public function display($tpl = null)
	{
		$this->option = JFactory::getApplication()->input->getCmd('option');

		JSpaceHelper::addSubmenu('cpanel');
		$this->addToolbar();
		$this->sidebar = JHtmlSidebar::render();
		
		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		$user  = JFactory::getUser();
		
		JToolbarHelper::title(JText::_('COM_JSPACE_CPANEL_TITLE'), 'stack article');

		if ($user->authorise('core.admin', $this->option))
		{
			JToolbarHelper::preferences($this->option);
		}
	}
}