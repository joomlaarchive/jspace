<?php 
/**
 * A model that displays information about a single collection.
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
jimport('jspace.factory');
require_once(JPATH_COMPONENT_ADMINISTRATOR.DS."helpers".DS."restrequest.php");

class JSpaceModelCollection extends JModel
{
	var $configPath = null;
	
	var $configuration = null;

	var $id = 0;
	
	var $data = null;
	
	var $items = null;
	
	public function __construct()
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
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * Gets a collection.
	 * 
	 * @return stdClass A collection object.
	 */
	public function getData()
	{
		if (!$this->data) {
		try {
			$endpoint = JSpaceFactory::getEndpoint('/collections/'. $this->getId() .'.json');
			$this->data = $resp = json_decode(JSpaceFactory::getRepository()->getConnector()->get($endpoint));
// 			var_dump($resp);
		} catch (Exception $e) {
			throw JSpaceRepositoryError::raiseError($this, JText::sprintf('COM_JSPACE_JSPACEITEM_ERROR_CANNOT_FETCH', $this->getId()));
		}
			
// 			$request = new JSpaceRestRequestHelper($this->getConfig()->rest_url.'/collections/'. $this->getId() .'.json', 'GET');
// 			$request->execute();

// 			if (JArrayHelper::getValue($request->getResponseInfo(), "http_code") == 200) {
// 				$this->data = json_decode($request->getResponseBody());
// 			} else {
// 				$this->data = array();
// 				$log = JLog::getInstance();
// 				$log->addEntry(array("c-ip"=>JArrayHelper::getValue($request->getResponseInfo(), "http_code", 0), "comment"=>$request->getResponseBody()));
// 			}
		}
		
		return $this->data;
	}
	
	/**
	 * Gets the collection's items.
	 * 
	 * @return array() An array of items.
	 */
	public function getItems()
	{
		if (!$this->items) {
// 			$request = new JSpaceRestRequestHelper($this->getConfig()->rest_url.'/collections/'. $this->getId() .'/items.json', 'GET');
// 			$request->execute();
			
			try {
				$endpoint = JSpaceFactory::getEndpoint('/collections/'. $this->getId() .'/items.json?_start=1&_limit=1');
				$this->items = $resp = json_decode(JSpaceFactory::getRepository()->getConnector()->get($endpoint));
				var_dump($resp);
			} catch (Exception $e) {
				throw JSpaceRepositoryError::raiseError($this, JText::sprintf('COM_JSPACE_JSPACEITEM_ERROR_CANNOT_FETCH', $this->getId()));
			}

// 			if (JArrayHelper::getValue($request->getResponseInfo(), "http_code") == 200) {
// 				$response = json_decode($request->getResponseBody());
// 				$this->items = $response->data;
				
// 				for ($i = 0; $i < count($this->items); $i++) {
// 					$this->items[$i]->thumbnails = $this->getThumbnails($this->items[$i]->id);
// 				}
// 			} else {
// 				$this->items = array();
// 				$log = JLog::getInstance();
// 				$log->addEntry(array("c-ip"=>JArrayHelper::getValue($request->getResponseInfo(), "http_code", 0), "comment"=>$request->getResponseBody()));
// 			}
		}
		
		return $this->items;		
	}
	
	/**
	 * Gets an item's thumbnails as an array of bitstream objects.
	 * 
	 * @param int $itemId A repository item id.
	 * @return array An array of bitstream objects.
	 */
	public function getThumbnails($itemId)
	{
		$thumbnails = array();

		$request = new JSpaceRestRequestHelper($this->getConfig()->rest_url.'/items/'. $itemId .'.json', 'GET');
		$request->execute();
		
		if (JArrayHelper::getValue($request->getResponseInfo(), "http_code") == 200) {
			$item = json_decode($request->getResponseBody());
			
			foreach ($item->bundles as $bundle) {
				if ($bundle->name == "THUMBNAIL") {
	
					foreach ($item->bitstreams as $bitstream) {
						$found = false;
	
						reset($bundle->bitstreams);
	
						while (!$found && $bundleBitstream = current($bundle->bitstreams)) {
							if ($bitstream->id == $bundleBitstream->id) {
								$url = $this->getConfig()->rest_url . "/bitstream/" . $bitstream->id . "/receive"; 
								
								$thumbnails[] = $bitstream;
								$thumbnails[count($thumbnails)-1]->url = $url;
								$found = true;
							}
	
							next($bundle->bitstreams);
						}
					}
				}
			}	
		} else {
			$this->items = array();
			$log = JLog::getInstance();
			$log->addEntry(array("c-ip"=>JArrayHelper::getValue($request->getResponseInfo(), "http_code", 0), "comment"=>$request->getResponseBody()));
		}
		
		return $thumbnails;
	}
}