<?php
defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');

class JSpaceModelDataObjectSchemas extends JModelLegacy
{
	public function __construct($config = array())
	{
		parent::__construct($config);
	}
	
	public function getItems()
	{
		$items = array();
		
		$formPath = JPATH_ROOT.'/administrator/components/com_jspace/models/forms';

		foreach (JFolder::files($formPath, 'schema\..*\.xml', false, true) as $file) {
			$xml = simplexml_load_file($file);
			
			$item = new stdClass();
			$item->name = JArrayHelper::getValue($xml, 'name', null, 'string');
			
			if (!$item->name) {
				throw new Exception('COM_JSPACE_DATAOBJECTSCHEMA_NO_NAME_ATTRIBUTE');
			}
			
			$item->label = JArrayHelper::getValue($xml, 'label', null, 'string');
			$item->description = JArrayHelper::getValue($xml, 'description', null, 'string');
			
			$items[] = $item;
		}
		
		return $items;
	}
}