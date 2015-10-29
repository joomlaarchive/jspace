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
 * An extension of the text form field to handle JSpace metadata.
 *
 * @package     JSpace.Component
 * @subpackage  Form
 */
class JSpaceFormFieldMetadataText extends JFormFieldText
{
	/**
	 * field type
	 * @var string
	 */
	protected $type = 'JSpace.MetadataText';

	/**
	 * Method to get the field input markup
	 */
	protected function getInput()
	{
		$html = JLayoutHelper::render("jspace.form.fields.metadata.text", $this);
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