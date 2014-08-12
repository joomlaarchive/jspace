<?php
/**
 * @package     JSpace.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
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
class PlgContentOAI extends JPlugin
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
        $this->params->loadArray(array('args'=>$options));

		$application = JFactory::getApplication('cli');

		$cli = (get_class($application) === 'JApplicationCli');
		$quiet = ($this->params->get('args.q') || $this->params->get('args.quiet'));
		$help = ($this->params->get('args.h') || $this->params->get('args.help'));

		$verbose = ($cli && !($help || $quiet));
		
		$start = new JDate('now');
		
		if ($verbose)
		{
			$this->out($action.' started '.(string)$start);
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
				$this->out($e->getMessage());
			}
		}
		
		$end = new JDate('now');
		
		if ($verbose)
		{
			$this->out($action.' ended '.(string)$end);
			$this->out($start->diff($end)->format("%H:%I:%S"));
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
                $this->out('executing '.$method.' on category '.$category->title);
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
        $id = $this->params->get('args.c');
		$id = $this->params->get('args.category', $id);

		$keys = array();

		if ($id)
		{
			$keys[] = $id; 
		}

		$database = JFactory::getDbo();
		
		$query = $database->getQuery(true);
		
		$select = array(
			$database->qn('c.id'), 
            $database->qn('c.title'), 
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
			
			if ($params->get('oai_url') && $params->get('oai_status') == '1')
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
    
    public function onContentPrepareForm($form, $data)
    {
        if (!($form instanceof JForm))
        {
            $this->_subject->setError('JERROR_NOT_A_FORM');
            return false;
        }

        $name = $form->getName();
        
        if (!in_array($name, array('com_categories.categorycom_jspace')))
        {
            return true;
        }

        JForm::addFormPath(__DIR__.'/forms');
        $form->loadFile('oai', false);

        return true;
    }
    
    public function onContentBeforeSave($context, $data, $isNew)
    {
        if ($context == 'com_categories.category')
        {
            if (isset($data->extension) && $data->extension == 'com_jspace')
            {
                $category = JTable::getInstance('Category');
                if ($category->load($data->id))
                {
                    $params = new JRegistry($category->params);
                    $newParams = new JRegistry($data->params);
                    
                    if ($params->get('oai_url') != $newParams->get('oai_url'))
                    {
                        $database = JFactory::getDbo();
                        $query = $database->getQuery(true);
                        $query
                            ->delete($database->qn('#__jspace_harvests'))
                            ->where($database->qn('catid').'='.(int)$data->id);
                            
                        $database->setQuery($query);
                        $database->execute();
                    }
                }
            }
        }
    }
    
    public function onContentAfterSave($context, $data, $isNew)
    {
        if ($context == 'com_categories.category')
        {
            if (isset($data->extension) && $data->extension == 'com_jspace')
            {
                $category = JTable::getInstance('Category');
                if ($category->load($data->id))
                {
                    $params = new JRegistry($category->params);
                    $newParams = new JRegistry($data->params);

                    if ($params->get('oai_url') && (int)$newParams->get('oai_status') === -1)
                    {
                        $newParams->set('oai_url', '');
                        $newParams->set('oai_harvest', 0);
                        $newParams->set('oai_set', '');
                        
                        $category->params = (string)$newParams;

                        $category->store();
                    }
                }
            }
        }
    }
	
	/**
	 * Prints out the plugin's help and usage information.
	 */
	private function _help()
	{
    	$out = <<<EOT
Usage: jspace oai [OPTIONS] [action]

Provides OAI-based functions within JSpace.

[action]
  clean               Discards the cached records.
  harvest             Harvest records from another archive. Harvesting 
                      information is configured via JSpace's Category Manager.
  reset               Reset the harvesting information. Forces the harvester 
                      to retrieve all records from the source archive.

[OPTIONS]
  -c, --category=categoryId  Specify a single category to execute an OAI action against.
  -q, --quiet                Suppress all output including errors.
  -h, --help                 Prints this help.
  
EOT;

        $this->out($out);
	}
	
    public function out($out)
    {
        $application = JFactory::getApplication('cli');
        
        if (get_class($application) !== 'JApplicationCli')
        {
            return;
        }
 
        if (!$this->params->get('args.q') && !$this->params->get('args.quiet'))
        {
            $application->out($out);
        }
    }
}