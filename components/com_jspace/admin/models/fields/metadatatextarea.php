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
}