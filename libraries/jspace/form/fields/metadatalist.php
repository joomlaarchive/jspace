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
JFormHelper::loadFieldClass('list');


class JSpaceFormFieldMetadatalist extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var         string
	 * @since       1.6
	 */
	protected $type = 'JSpace.Metadatalist';
	
	/**
	 * 
	 * @var JSpaceTableMetadata
	 */
	protected $value;
	
	public $isMetadata = true;
	
	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   11.1
	 */
	protected function getOptions()
	{
		// Initialize variables.
		$options = array();
	
		if( $this->element['insertEmptyOption'] ) {
			$options[] = JHtml::_('select.option', "", (string) JText::_("COM_JSPACE_EMPTY_SELECT_OPTION"));
		}
		
		foreach ($this->element->children() as $option)
		{
	
			// Only add <option /> elements.
			if ($option->getName() != 'option')
			{
				continue;
			}
	
			// Create a new option object based on the <option /> element.
			$tmp = JHtml::_('select.option', (string) JText::_($option));
	
			// Set some option attributes.
			$tmp->class = (string) $option['class'];
	
			// Set some JavaScript option attributes.
			$tmp->onclick = (string) $option['onclick'];
	
			// Add the option object to the result set.
			$options[] = $tmp;
		}
	
		reset($options);
	
		return $options;
	}
	
		
// 	/**
// 	 * Method to get the field options.
// 	 *
// 	 * @return  array  The field option objects.
// 	 *
// 	 * @since   11.1
// 	 */
// 	protected function getOptions() {
// 		// Initialize variables.
// 		$options = array();
		
// 		$values = JSpaceTableMetadata::options( $this->element['name'] );
		
// 		var_dump($values);
// 		foreach ($values as $option)
// 		{
// 			// Create a new option object based on the <option /> element.
// 			$tmp = JHtml::_('select.option', (string) $option);
		
// 			// Add the option object to the result set.
// 			$options[] = $tmp;
// 		}
		
// 		reset($options);
		
// 		return $options;
// 	}
}









