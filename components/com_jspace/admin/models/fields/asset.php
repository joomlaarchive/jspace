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
	
		$fieldName = $fieldName.']['.$this->derivative;
		
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
		$name = $this->getName($this->fieldname).'[assets]';
		
		if ($this->multiple)
		{
			$name .= '[]';
		}
		
		return $name;
	}
	
	public function getMetadataFieldName()
	{
		return parent::getName($this->fieldname).'[metadata]';
	}
	
	public function getSchemaFieldName()
	{
		return parent::getName($this->fieldname).'[schema]';
	}
	
	public function getDeleteFieldName()
	{
		return $this->getName($this->fieldname).'[delete]';
	}
	
	public function getDeleteFieldId()
	{
		return $this->id."_".$this->derivative."_delete";
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
			
			case 'metadataFieldName':
			case 'schemaFieldName':
			case 'assetsFieldName':
			case 'bundleFieldName':
			case 'deleteFieldName':
			case 'deleteFieldId':
				$method = 'get'.ucfirst($name);
			
				return $this->__call($method);
				
			default:
				return parent::__get($name);
				break;
		}
	}
}