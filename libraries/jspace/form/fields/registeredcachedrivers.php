<?php
/**
 * Supports a collection picker.
 * 
 * @author		$LastChangedBy: spauldingsmails $
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
 * Hayden Young					<haydenyoung@wijiti.com> 
 * 
 */

defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');
jimport('jspace.factory');
JFormHelper::loadFieldClass('list');

class JSpaceFormFieldRegisteredCacheDrivers extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var         string
	 * @since       1.6
	 */
	protected $type = 'JSpace.RegisteredCacheDrivers';


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
		$jspace = JSpaceFactory::getJSpace();
		$instances = $jspace->getCacheManager()->listInstances();
	
		foreach ($instances as $option)
		{
			// Create a new option object based on the <option /> element.
			$tmp = JHtml::_(
					'select.option', (string) $option, $option, 'value', 'text'
			);
			// Add the option object to the result set.
			$options[] = $tmp;
		}
	
		reset($options);
	
		return $options;
	}
}




