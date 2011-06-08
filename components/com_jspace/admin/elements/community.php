<?php
/**
 * A menu element for selecting a community.
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

defined('_JEXEC') or die( 'Restricted access' );

jimport("joomla.filesystem.file");
jimport('joomla.error.log');
jimport('joomla.utilities');

require_once(JPATH_ROOT.DS."administrator".DS."components".DS."com_jspace".DS."helpers".DS."restrequest.php");

class JElementCommunity extends JElement
{
	/**
	 * Element name
	 *
	 * @access      protected
	 * @var         string
	 */
	var $_name = 'Community';

	function fetchElement($name, $value, &$node, $control_name)
	{
		$array = array();
		$array[] = JHTML::_("select.option", 0, JText::_("Select a community"));
		
		require_once(JPATH_ROOT.DS."administrator".DS."components".DS."com_jspace".DS."configuration.php");
		
		$configuration = new JSpaceConfig();
		
		$request = new JSpaceRestRequestHelper($configuration->rest_url.'/communities.json?topLevelOnly=false', 'GET');
		$request->execute();

		if (JArrayHelper::getValue($request->getResponseInfo(), "http_code") == 200) {
			$response = json_decode($request->getResponseBody());
			
			foreach ($response->communities_collection as $community) {
				$array[] = JHTML::_("select.option", $community->id, $community->name);
			}
		} else {
			$this->data = array();
			$log = JLog::getInstance();
			$log->addEntry(array("c-ip"=>JArrayHelper::getValue($request->getResponseInfo(), "http_code", 0), "comment"=>$request->getResponseBody()));
		}
		
		$fieldName = $control_name.'['.$name.']';

		return JHTML::_("select.genericlist", $array, $fieldName, null, "value", "text", intval($value), "'.$name.'_id");
	}
}