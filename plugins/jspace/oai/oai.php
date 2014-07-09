<?php
/**
 * @package     JSpace.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('joomla.registry.registry');

jimport('jspace.factory');
jimport('jspace.archive.record');
jimport('jspace.ingestion.oai.client');
jimport('jspace.ingestion.oai.harvester');
jimport('jspace.ingestion.oai.assetharvester');

/**
 * Handles OAI harvesting from the command line.
 *
 * @package     JSpace.Plugin
 */
class PlgJSpaceOAI extends JPlugin
{
	private $identified = false;
	
	public function __construct($subject, $config = array())
	{	
		parent::__construct($subject, $config);
		$this->loadLanguage();
		
		JLog::addLogger(array());
	}
	
	public function onJSpaceExecuteCliCommand($action, $options = array())
	{
		$application = JFactory::getApplication('cli');
		
		$cli = (get_class($application) === 'JApplicationCli');
		$q = array_key_exists('q', $options);
		$quiet = array_key_exists('quiet', $options);
		$help = ($action == 'help');
		
		$verbose = !($q && $quiet && $help && !$cli);
		
		$start = new JDate('now');
		
		if ($verbose)
		{
			$application->out($action.' started '.(string)$start);
		}
		
		try
		{
			switch ($action)
			{
				case 'harvest': // harvest archives.
					$this->_fireHarvester(array('harvest', 'ingest'));
					
					break;
					
				case 'clean': // clear the cache.
					$this->_fireHarvester(array('rollback'));
					
					break;
					
				case 'reset': // reset harvest (forces harvest to start at beginning).
					$this->_fireHarvester(array('reset'));
					
					break;
				
				default:
					$this->_help();
					
					break;
			}
		}
		catch (Exception $e)
		{
			$application = JFactory::getApplication('cli');
		
			if (get_class($application) !== 'JApplicationCli')
			{
				return;
			}
			
			if ($verbose)
			{
				$application->out($e->getMessage());
			}
		}
		
		$end = new JDate('now');
		
		if ($verbose)
		{
			$application->out($action.' ended '.(string)$end);
			$application->out($start->diff($end)->format("%H:%I:%S"));
		}
	}
	
	/**
	 * Gets the harvester.
	 * 
	 * @param   JTable  $category            An instance of the category table.
	 *
	 * @return  JSpaceIngestionOAIHarvester  An instance of the harvester class configured in the category's OAI settings.
	 */
	private function _getHarvester($category)
	{
		switch ($category->params->get('oai_harvest', 0))
		{
			case 2:
				$class = "JSpaceIngestionOAIAssetHarvester";
				break;
				
			case 1:
			
			default:
				$class = "JSpaceIngestionOAIHarvester";
				break;
		}
		
		return new $class($category);
	}
	
	/**
	 * A convenience method for firing methods against the currently configured harvester.
	 *
	 * @param  string[]  $methods  An array of methods to fire in sequential order.
	 */
	private function _fireHarvester($methods)
	{
		foreach ($this->_getOAICategories() as $category)
		{
			$harvester = $this->_getHarvester($category);
			
			foreach ($methods as $method)
			{
				$harvester->$method();
			}
		}
	}
	
	/**
	 * Get a list of categories which are populated via an OAI-PMH endpoint.
	 *
	 * The _getOAICategories() method will retrieve any categories set via the command line args "-c"
	 * and "--category=". If no category args have been passed, _getOAICategories() will return all 
	 * OAI-aware JSpace categories.
	 *
	 * @return mixed  A list of OAI-enabled categories or null if there is a problem fetching the categories.
	 */
	private function _getOAICategories()
	{
		$id = JArrayHelper::getValue($options, 'category', JArrayHelper::getValue($options, 'c', 0));
				
		$keys = array();
		
		if ($id)
		{
			$keys[] = $id; 
		}
	
		$database = JFactory::getDbo();
		
		$query = $database->getQuery(true);
		
		$select = array(
			$database->qn('c.id'), 
			$database->qn('c.language'), 
			$database->qn('c.access'), 
			$database->qn('c.published'), 
			$database->qn('c.created_user_id'), 
			$database->qn('c.params'));
		
		$query
			->select($select)
			->from($database->qn('#__categories', 'c'))
			->where($database->qn('c.published').'='.$database->q('1'))
			->where($database->qn('c.extension').'='.$database->q('com_jspace'));
			
		foreach ($keys as $key)
		{
			$query->where($database->qn('c.id').'='.(int)$key);
		}
		
		$database->setQuery($query);
		
		$categories = $database->loadObjectList('id', 'JObject');
		
		foreach ($categories as $key=>$value)
		{
			$params = new JRegistry();
			$params->loadString($value->params);
			
			if ($params->get('oai_url'))
			{
				
				$categories[$key]->params = $params;
			}
			else 
			{
				unset($categories[$key]);
			}
		}
		
		return $categories;
	}
	
	/**
	 * Prints out the plugin's help and usage information.
	 */
	private function _help()
	{
		$application = JFactory::getApplication('cli');
		
		if (get_class($application) !== 'JApplicationCli')
		{
			return;
		}
		
    	$out = <<<EOT
Usage: jspace oai [action] [OPTIONS]

Provides OAI-based functions within JSpace.

[action]
  clean               Discards the cached records.
  harvest             Harvest records from another archive. Harvesting 
                      information is configured via JSpace's Category Manager.
  help                Prints this help.
  reset               Reset the harvesting information. Forces the harvester 
                      to retrieve all records from the source archive.

[OPTIONS]
  -c, --c=categoryId  Specify a single category to execute an OAI action against.
  -q, --quiet         Suppress all output including errors.
  
EOT;

		$application->out($out);
	}
}