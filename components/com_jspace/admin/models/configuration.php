<?php 
/**
 * A model that provides configuration options for JSpace.
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
   along with the JSolrIndex component for Joomla!.  If not, see 
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 * Name							Email
 * Hayden Young					<haydenyoung@wijiti.com> 
 * 
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.registry.registry');
jimport('joomla.filesystem.file');
jimport('joomla.application.component.modeladmin');

class JSpaceModelConfiguration extends JModelAdmin
{
	var $configPath = null;
	
	public function __construct()
	{
		$this->configPath = JPATH_ROOT.DS."administrator".DS."components".DS."com_jspace".DS."configuration.php";
		
		parent::__construct();
	}
	
	/**
	 * Gets the configuration settings.
	 * 
	 * @return The configuration settings.
	 */
	public function getConfig()
	{
		require_once($this->configPath);
		
		$config = new JSpaceConfig(); 
		
		$registry = JFactory::getConfig();

		$registry->loadObject($config);

		return $config;
	}

	public function getParam($name)
	{
		return $this->getConfig()->$name;
	}
	
	public function save($data)
	{
		$config = new JRegistry('jspaceconfig');

		$config->loadObject($this->getConfig());

		foreach(array_keys($config->toArray()) as $key) {
			if ($value = JArrayHelper::getValue($data, $key)) {
				$config->setValue($key, $value);
			}
		}

		JFile::write($this->configPath, $config->toString("PHP", array("class"=>"JSpaceConfig", "closingtag"=>false)));
	}
	
	public function getForm($data = array(), $loadData = true) 
	{
		// Get the form.
		$form = $this->loadForm('com_jspace.configuration', 'configuration', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form)) {
			return false;
		}

		return $form;
	}
	
	protected function loadFormData() 
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_jspace.edit.configuration.data', array());

		if (empty($data)) {
			$data = JArrayHelper::fromObject($this->getConfig());
		}

		return $data;
	}
	
	public function isGDInstalled()
	{
		if (extension_loaded('gd') && function_exists('gd_info')) {
		    return true;
		} else {
			return false;
		}
	}
	
	public function isFFMPEGInstalled()
	{
		if (extension_loaded('ffmpeg') && class_exists('ffmpeg_movie')) {
		    return true;
		} else {
			return false;
		}
	}
}