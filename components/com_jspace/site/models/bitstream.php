<?php 
/**
 * A model that displays information about a single bitstreams.
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

class JSpaceModelBitstream extends JModel
{
	var $configPath = null;
	
	var $configuration = null;

	var $id = 0;
	
	var $data = null;
	
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
	 * Gets a bitstream.
	 * 
	 * @return stdClass A bitstream object.
	 */
	public function getData()
	{
		if (!$this->data) {
			$request = new JSpaceRestRequestHelper($this->getConfig()->rest_url.'/bitstream/'. $this->getId() .'.json', 'GET');
			$request->execute();

			if (JArrayHelper::getValue($request->getResponseInfo(), "http_code") == 200) {
				$this->data = json_decode($request->getResponseBody());
			} else {
				$this->data = new stdClass();
				$log = JLog::getInstance();
				$log->addEntry(array("c-ip"=>JArrayHelper::getValue($request->getResponseInfo(), "http_code", 0), "comment"=>$request->getResponseBody()));
			}
		}
		
		return $this->data;
	}
	
	public function getTextPreview()
	{
	    $ch = curl_init();
	    curl_setopt ($ch, CURLOPT_URL, $this->getConfig()->rest_url . "/bitstream/" . $this->getId() . "/receive");
	    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 0);
	    $contents = curl_exec($ch);
	    curl_close($ch);

	    return nl2br(htmlentities($contents));
	}
}