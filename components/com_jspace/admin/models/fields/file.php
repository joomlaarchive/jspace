<?php
defined('JPATH_BASE') or die;

/**
 * A stream manager for a record.
 * 
 * Provides the ability to upload, manage and delete one or more files as part of a record.
 */
class JSpaceFormFieldFile extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 */
	protected $type = 'JSpace.File';
	
	public function getName($fieldName)
	{
		if (!$this->bundle)
		{
			throw new Exception(JText::sprintf('COM_JSPACE_FATAL_NOBUNDLE', $this->type));
		}
	
		$fieldName = $fieldName.']['.$this->bundle;
		
		return str_replace('[]', '', parent::getName($fieldName));
	}
	
	public function __call($name, $arguments = null)
	{
		if ($arguments)
		{
			return call_user_func_array(array($this, $name), $arguments);
		}
		else 
		{
			return call_user_func(array($this, $name));	
		}
		
	}
	
	public function getFilesName()
	{
		$name = $this->getName($this->fieldname).'[files]';
		
		if ($this->multiple)
		{
			$name .= '[]';
		}
		
		return $name;
	}
	
	public function getExtractionMapName()
	{
		return $this->getName($this->fieldname).'[extractionmap]';
	}
	
	public function getSchemaName()
	{
		return $this->getName($this->fieldname).'[schema]';
	}
	
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
		
		JPluginHelper::importPlugin('jspace');
		return JArrayHelper::getValue($dispatcher->trigger('onJSpaceFilesPrepare', array($this->form->getData()->toObject())), 0, array());
	}
	
	public function __get($name)
	{
		switch ($name) {
			case 'schema':
			case 'bundle':			
				return JArrayHelper::getValue($this->element, $name, null, 'string');
				break;
				
			case 'extractionmap':
				$schema = JArrayHelper::getValue($this->element, $name, null, 'string');
				
				if (array_search($schema, array('metadata', 'none', 'source')) === false)
				{
					$schema = 'none';
				}
				
				return $schema;
				
				break;
				
			case 'extractionMapName':
			case 'schemaName':
			case 'filesName':
				$method = 'get'.ucfirst($name);
			
				return $this->__call($method);
	
			default:
				return parent::__get($name);
				break;
		}
	}
}