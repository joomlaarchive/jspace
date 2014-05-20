<?php
defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');

class JSpaceModelRecordSchemas extends JModelLegacy
{
	public function __construct($config = array())
	{
		parent::__construct($config);
	}
	
	public function getItems()
	{
		$items = array();
		
		$formPath = JPATH_ROOT.'/administrator/components/com_jspace/models/forms/schemas';

		foreach (JFolder::files($formPath, '..*\.xml', false, true) as $file) {
			$xml = simplexml_load_file($file);
			
			$item = new stdClass();
			$item->name = JArrayHelper::getValue($xml, 'name', null, 'string');
			
			if (!$item->name) {
				throw new Exception('COM_JSPACE_RECORDSCHEMA_NO_NAME_ATTRIBUTE');
			}
			
			$item->label = JArrayHelper::getValue($xml, 'label', null, 'string');
			$item->description = JArrayHelper::getValue($xml, 'description', null, 'string');
			
			$items[] = $item;
		}
		
		return $items;
	}
}