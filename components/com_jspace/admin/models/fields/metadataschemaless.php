<?php
defined('JPATH_BASE') or die;

/**
 * Provides a mechanism for adding and deleting multiple metadata fields.
 */
class JSpaceFormFieldMetadataSchemaless extends JFormField
{
    /**
     * The form field type.
     *
     * @var     string
     */
    protected $type = 'JSpace.MetadataSchemaless';
    
    /**
     * Method to get the field input markup
     */
    protected function getInput()
    {
        $html = JLayoutHelper::render("jspace.form.fields.metadata.schemaless", $this);
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