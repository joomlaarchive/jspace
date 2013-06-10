<?php
/**
 * Supports a collection picker.
 * 
 * @author		$LastChangedBy: michalkocztorz $
 * @package		JSpace
 * @copyright	Copyright (C) 2011 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace component for Joomla!.

   The JSpace component for Joomla! is free software: you can redistribute it 
   and/or modify it under the terms of the GNU General Public License as 
   published by the Free Software Foundation, either version 3 of the License, 
   or (at your option) any later version.

   The JSpace component for Joomla! is distributed in the hope that it will be 
   useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with the JSpace component for Joomla!.  If not, see 
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 * Name							Email
 * Michał Kocztorz				<michalkocztorz@wijiti.com> 
 * 
 */

defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');
jimport('joomla.form.helper');
jimport('jspace.database.table.metadata');
JFormHelper::loadFieldClass('checkboxes');


class JSpaceFormFieldMetadatacheckboxes extends JFormFieldCheckboxes
{
	/**
	 * The form field type.
	 *
	 * @var         string
	 * @since       1.6
	 */
	protected $type = 'JSpace.Metadacheckboxes';
	
	/**
	 * 
	 * @var JSpaceTableMetadata
	 */
	protected $value;
	
	public $isMetadata = true;
	
	/**
	 * Method to get the field input markup for check boxes.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		$document = JFactory::getDocument();
		$document->addStyleSheet('media/com_jspace/css/formfields.css');
		// Initialize variables.
		$html = array();
	
		// Initialize some field attributes.
		$class = $this->element['class'] ? ' class="checkboxes metadata-checkboxes ' . (string) $this->element['class'] . '"' : ' class="checkboxes metadata-checkboxes "';
	
		// Start the checkbox field output.
		$html[] = '<fieldset id="' . $this->id . '"' . $class . '>';
	
		// Get the field options.
		$options = $this->getOptions();
// 		var_dump($this->value);
		$values = $this->value->getValueArray();
// 		var_dump($options);
	
		// Build the checkbox field output.
		$html[] = '<ul>';
		foreach ($options as $i => $option)
		{
			$value = empty($option->value) ? JText::_($option->text) : $option->value;
	
			// Initialize some option attributes.
			$checked = (in_array((string) $value, (array) $values) ? ' checked="checked"' : '');
			$class = !empty($option->class) ? ' class="' . $option->class . '"' : '';
			$disabled = !empty($option->disable) ? ' disabled="disabled"' : '';
	
			// Initialize some JavaScript option attributes.
			$onclick = !empty($option->onclick) ? ' onclick="' . $option->onclick . '"' : '';
			
	
			$html[] = '<li>';
			$html[] = '<input type="checkbox" id="' . $this->id . $i . '" name="' . $this->name . '"' . ' value="'
					. htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '"' . $checked . $class . $onclick . $disabled . '/>';
	
			$html[] = '<label for="' . $this->id . $i . '"' . $class . '>' . JText::_($option->text) . '</label>';
			$html[] = '</li>';
		}
		$html[] = '</ul>';
	
		// End the checkbox field output.
		$html[] = '</fieldset>';
	
		return implode($html);
	}
}









