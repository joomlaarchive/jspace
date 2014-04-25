<?php
defined('_JEXEC') or die('Restricted access');
 
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('text');

/**
 * An extension of the JFormFieldText field, adding the ability to handle an array of values.
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
}