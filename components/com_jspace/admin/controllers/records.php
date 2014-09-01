<?php
defined('_JEXEC') or die;

class JSpaceControllerRecords extends JControllerAdmin
{
	public function __construct($config = array())
	{
		parent::__construct($config);
		
		$this->set('model_prefix', 'JSpaceModel');
		$this->set('name', 'Record');
	}
	
    /**
     * Rebuild the nested set tree.
     *
     * @return  bool  False on failure or error, true on success.
     */
    public function rebuild()
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $this->setRedirect(JRoute::_('index.php?option=com_jspace&view=records', false));

        $model = $this->getModel();

        if ($model->rebuild())
        {
            // Rebuild succeeded.
            $this->setMessage(JText::_('COM_JSPACE_RECORDS_REBUILD_SUCCESS'));

            return true;
        }
        else
        {
            // Rebuild failed.
            $this->setMessage(JText::_('COM_JSPACE_RECORDS_REBUILD_FAILURE'));

            return false;
        }
    }
	
	public function addChild()
	{
		$cid = JFactory::getApplication()->input->get('cid', array(), 'array');

		$url = JUri::getInstance();
		
		$url->setVar('option', $this->option);
		$url->setVar('view', 'record');
		$url->setVar('layout', 'edit');
		
		if ($parent = JArrayHelper::getValue($cid, 0)) 
		{
			$url->setVar('parent', $parent);
		}
		
		$this->setRedirect((string)$url);
	}
}