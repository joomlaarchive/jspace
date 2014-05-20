<?php
defined('_JEXEC') or die;

class JSpaceController extends JControllerLegacy
{
	protected $default_view = 'cpanel';

	public function display($cachable = false, $urlparams = false)
	{
		$view   = $this->input->get('view', $this->default_view);
		$layout = $this->input->get('layout', $this->default_view);
		$id     = $this->input->getInt('id');

		// Check for edit form.
		if ($view == 'record' && $layout == 'edit' && !$this->checkEditId('com_jspace.edit.record', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_jspace&view=records', false));

			return false;
		}

		parent::display();

		return $this;
	}
}