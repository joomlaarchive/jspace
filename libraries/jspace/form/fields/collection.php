<?php
/**
 * Supports a collection picker.
 * 
 * @author		$LastChangedBy$
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

jimport('joomla.html.html');
// jimport('joomla.form.formfield');
jimport('joomla.form.helper');
// jimport('joomla.error.log');
// jimport('joomla.utilities');
// jimport('joomla.application.component.helper');
// jimport('joomla.environment.uri');
jimport('jspace.factory');

JFormHelper::loadFieldClass('list');

class JSpaceFormFieldCollection extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var         string
	 * @since       1.6
	 */
	protected $type = 'JSpace.Collection';

	protected function getOptions()
	{
		$options = array();

		try {
			$endpoint = JSpaceFactory::getEndpoint('/collections.json');
			$client = JSpaceFactory::getConnector();

			$response = json_decode($client->get($endpoint));
			
			$params = JComponentHelper::getParams('com_jspace', true);
			$default = $params->get('defaultcollectionid');
			if( empty($this->value) ){
				$this->value = $default;
			}

			if( is_array($response->collections) ) {
				foreach ($response->collections as $collection) {
					$options[] = JHTML::_("select.option", $collection->id, $collection->name);
				}
			}
			if( count($options) == 0 ) {//no options found, use the default one 
				$options[] = JHTML::_("select.option", $default, JTEXT::_("COM_JSPACE_FORMFIELD_COLLECTION_DEFAULT_COLLECTION"));
			}
		} catch (Exception $e) {
			// do nothing
		}
		
		return $options;
	}
}