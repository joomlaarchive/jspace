<?php
defined('JPATH_BASE') or die;

/**
 * A file uploader for a record.
 * 
 * Provides the ability to upload one or more assets as part of a record.
 */
class JSpaceFormFieldAsset extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 */
	protected $type = 'JSpace.Asset';
	
	public function getName($fieldName)
	{
		if (!$this->derivative)
		{
			throw new Exception(JText::sprintf('COM_JSPACE_ERROR_NODERIVATIVE', $this->type));
		}
	
		$fieldName = $fieldName.'][assets';
		
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
	
	public function getAssetsFieldName()
	{
		$name = $this->getName($this->fieldname).'['.$this->derivative.']';
		
		if ($this->multiple)
		{
			$name .= '[]';
		}
		
		return $name;
	}
	
	public function getSchemaFieldName()
	{
		return str_replace('[]', '', parent::getName($this->fieldname)).'[schema]';
	}
	
	protected function getInput()
	{
		$html = JLayoutHelper::render("jspace.form.fields.asset", $this);
		return $html;
	}
	
	public function getAssets()
	{
		$record = JSpaceRecord::getInstance($this->form->getData()->get('id'));
		
		return $record->getAssets(array('bundle'=>$this->bundle));
	}
	
	public function __get($name)
	{
		switch ($name) {
			case 'schema':
			case 'derivative':
			case 'metadata':
				return JArrayHelper::getValue($this->element, $name, null, 'string');
				break;
			
			case 'bundle':			
				return $this->fieldname;
				break;
				
			case 'schemaFieldName':
			case 'assetsFieldName':
				$method = 'get'.ucfirst($name);
			
				return $this->__call($method);
				
				break;
				
			default:
				return parent::__get($name);
				break;
		}
	}
}