<?php 
/**
 * A model that displays a list of communities.
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

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');
jimport("joomla.filesystem.file");
jimport('joomla.error.log');
jimport('joomla.utilities');

require_once(JPATH_COMPONENT_ADMINISTRATOR.DS."helpers".DS."restrequest.php");

class JSpaceModelCommunities extends JModel
{
	var $configPath = null;
	
	var $configuration = null;
	
	var $data = null;
	
	function __construct()
	{
		$this->configPath = JPATH_ROOT.DS."administrator".DS."components".DS."com_jspace".DS."configuration.php";
		
		require_once($this->configPath);
		
		parent::__construct();
	}

	/**
	 * Gets the configuration file path.
	 * 
	 * @return The configuration file path.
	 */
	public function getConfig()
	{
		if (!$this->configuration) {
			$this->configuration = new JSpaceConfig();	
		}
		
		return $this->configuration;
	}
	
	/**
	 * Gets a list of communities.
	 */
	public function getList()
	{
		if (!$this->data) {
			$this->data = array();
			
			$request = new JSpaceRestRequestHelper($this->getConfig()->rest_url.'/communities.json?topLevelOnly=false', 'GET');
			$request->execute();

			if (JArrayHelper::getValue($request->getResponseInfo(), "http_code") == 200) {
				$response = json_decode($request->getResponseBody());
				
				// get the root communities.
				foreach ($response->communities_collection as $community) {
					if (!$community->parentCommunity) {
						$this->data[$community->id] = $community;
						$this->_getSubCommunities($this->data[$community->id], $response->communities_collection);
					}
				}
				
			} else {
				$log = JLog::getInstance();
				$log->addEntry(array("c-ip"=>JArrayHelper::getValue($request->getResponseInfo(), "http_code", 0), "comment"=>$request->getResponseBody()));
			}
		}
		
		return $this->data;
	}
	
	private function _getSubCommunities($root, $list)
	{
		$root->subCommunities = array();
		
		foreach ($list as $community) {
			// if the community has a parent and the parent is the root.
			if ($community->parentCommunity) {
				if ($community->parentCommunity->id == $root->id) {
					$root->subCommunities[$community->id] = $community;
				}
			}
			
			if (count($community->subCommunities)) {
				$this->_getSubCommunities($community, $list);
			}
		}
	}
}