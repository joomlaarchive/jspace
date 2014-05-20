<?php
defined('JPATH_BASE') or die;

jimport('joomla.form.formrule');

class JFormRuleJSpaceGreaterThan extends JFormRule
{
    public function test($element, $value, $group = null, $input = null, $form = null)
    {
		$field = JArrayHelper::getValue($element, 'field', null, 'string');
		
		if (!$field)
		{
			throw new UnexpectedValueException(sprintf('$field empty in %s::test', get_class($this)));
		}
		
		// if a value has been set AND value is less than matching field.
		if ((int)$value > 0 && $value < $input->get($field))
		{
			return false;
		}
		
		return true;
    }
}