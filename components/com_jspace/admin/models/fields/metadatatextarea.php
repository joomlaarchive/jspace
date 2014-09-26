<?php
/**
 * @package     JSpace.Component
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die('Restricted access');
 
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('textarea');

/**
 * An extension of the textarea form field to handle JSpace metadata.
 *
 * @package     JSpace.Component
 * @subpackage  Form
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

            case 'maximum':
                return JArrayHelper::getValue($this->element, 'maximum', 5);
                break;
            
            default:
                return parent::__get($name);
                break;
        }
    }
}