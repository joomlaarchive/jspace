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
JFormHelper::loadFieldClass('text');

/**
 * Provides a list of text boxes for managing identifiers.
 *
 * @package     JSpace.Component
 * @subpackage  Form
 */
class JSpaceFormFieldIdentifierList extends JFormField
{
    /**
     * field type
     * @var string
     */
    protected $type = 'JSpace.IdentifierList';

    /**
     * Method to get the field input markup
     */
    protected function getInput()
    {
        $html = JLayoutHelper::render("jspace.form.fields.identifierlist", $this);
        return $html;
    }
    
    public function __get($name)
    {
        switch ($name) 
        {
            case 'maximum':
                return JArrayHelper::getValue($this->element, 'maximum', 5);
                break;
            
            default:
                return parent::__get($name);
                break;
        }
    }
}