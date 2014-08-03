<?php
defined('_JEXEC') or die('Restricted access');
 
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('textarea');

/**
 * An extension of the JFormFieldTextArea field, adding the ability to handle an array of values.
 */
class JSpaceFormFieldMetadataTextArea extends JFormFieldTextArea
{
	/**
	 * field type
	 * @var string
	 */
	protected $type = 'JSpace.MetadataTextArea';

	/**
	 * Method to get the field input markup
	 */
	protected function getInput()
	{
		$html = JLayoutHelper::render("jspace.form.fields.metadata.textarea", $this);
		return $html;
	}
    
    public function __get($name)
    {
        switch ($name) 
        { 
            case 'value':
                if (!is_array($this->$name))
                {
                    $this->$name = array();
                }
                
                return $this->$name;
                
                break;
            
            default:
                return parent::__get($name);
                break;
        }
    }
}