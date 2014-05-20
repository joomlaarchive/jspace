<?php
defined('_JEXEC') or die;

class JSpaceViewRecordSchemas extends JViewLegacy
{
	protected $items;
	
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{	
		$this->items = $this->get('Items');
		$this->recordId = JFactory::getApplication()->input->getInt('recordId');
		$this->parentId = JFactory::getApplication()->input->getInt('parent');

		parent::display($tpl);
	}
}