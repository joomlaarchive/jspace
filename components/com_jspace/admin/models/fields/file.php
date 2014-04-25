<?php
defined('JPATH_BASE') or die;

/**
 * A stream manager for a data object.
 * 
 * Provides the ability to upload, manage and delete one or more files as part of a data object.
 */
class JSpaceFormFieldFile extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since   1.6
	 */
	protected $type = 'JSpace.File';
	
	/**
	 * (non-PHPdoc)
	 * @see JFormField::getInput()
	 */
	protected function getInput()
	{
		$html = JLayoutHelper::render("jspace.form.fields.file", $this);
		return $html;
	}
	
	public function getFileList()
	{
		$dispatcher = JEventDispatcher::getInstance();
		
		JPluginHelper::importPlugin('jspacestorage');
		return JArrayHelper::getValue($dispatcher->trigger('onJSpaceFilesPrepare', array($this->form->getData()->toObject())), 0, array());
	}
	
	public function __get($name)
	{
		switch ($name) {
			case 'bundle':
			case 'metadataextractionmapping':
				return JArrayHelper::getValue($this->element, $name, null, 'string');
				break;
	
			default:
				return parent::__get($name);
				break;
		}
	}
}