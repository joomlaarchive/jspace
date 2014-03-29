<?php
defined('_JEXEC') or die;

class JSpaceControllerDataObjects extends JControllerAdmin
{
	public function __construct($config = array())
	{
		parent::__construct($config);
		
		$this->set('model_prefix', 'JSpaceModel');
		$this->set('name', 'DataObject');
	}
	
	public function addChild()
	{
		$cid = JFactory::getApplication()->input->get('cid', array(), 'array');

		$url = JUri::getInstance();
		
		$url->setVar('option', $this->option);
		$url->setVar('view', 'dataobject');
		$url->setVar('layout', 'edit');
		
		if ($parent = JArrayHelper::getValue($cid, 0)) 
		{
			$url->setVar('parent', $parent);
		}
		
		$this->setRedirect((string)$url);
	}
}