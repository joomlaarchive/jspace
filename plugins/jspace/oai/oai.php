<?php
defined('JPATH_BASE') or die;

jimport('joomla.registry.registry');
jimport('jspace.archive.record');

class PlgJSpaceOAI extends JPlugin
{
	/**
	 *
	 */
	public function __construct($subject, $config = array())
	{	
		parent::__construct($subject, $config);
		$this->loadLanguage();
		
		JLog::addLogger(array());
	}
	
	public function onJSpaceExecuteCliCommand($action, $options)
	{
		switch ($action)
		{
			case 'harvest':
				foreach ($this->_getOAICategories() as $category)
				{
					$this->_harvest($category);
				}
				
				break;
				
			case 'clear':
				$id = JArrayHelper::getValue($options, 'category', JArrayHelper::getValue($options, 'c', 0));
			
				if (!$id)
				{
					throw new Exception(JText::_("Please specify a category id using -c [id] or --category=[id]."));
				}
				
				$category = JTable::getInstance('Category');
				$category->load($id);
				
				$this->_discard($category);
				
				break;
			
			default:
				$this->_help();
			
				break;
		}
	}
	
	/**
	 * Get a list of categories which are populated via an OAI-PMH endpoint.
	 *
	 * @return mixed A list of OAI-enabled categories or null if there is a problem fetching the categories.
	 */
	private function _getOAICategories()
	{
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
		
		$database->setQuery($query);
		
		$categories = $database->loadObjectList();
		
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
	 * Harvest a list of records via an OAI-PMH-enabled endpoint.
	 *
	 * @param string $url The url to harvest.
	 */
	private function _harvest($category)
	{
		$harvest = $this->_getOAIHarvest($category->id);
		$url = $category->params->get('oai_url');
		$metadataFormat = $category->params->get('oai_metadataFormat');
	
		$category->resumptionToken = null;
	
		$uri = JUri::getInstance($url);
		$uri->setVar('verb', 'ListRecords');
		
		try 
		{
			do
			{
				if ($harvest->resumptionToken)
				{
					$uri->delVar('metadataPrefix');
					$uri->setVar('resumptionToken', $harvest->resumptionToken);
					
					// take a break to avoid any timeout issues.
					sleep($this->params->get('follow_on', 30));
				}
				else
				{
					if ($harvest->harvested)
					{
						$uri->setVar('from', JFactory::getDate($harvest->harvested)->toISO8601());
					}
					
					$uri->setVar('metadataPrefix', $metadataFormat);
				}
			
				$reader = new XMLReader;
				
				if (!$reader->open((string)$uri))
				{
					throw new Exception('Could not read '.$uri, 404);
				}

				$doc = new DOMDocument;
				
				while ($reader->read())
				{
					if ($reader->nodeType == XMLReader::ELEMENT)
					{
						switch ($reader->name)
						{
							case 'responseDate':
								$node = simplexml_import_dom($doc->importNode($reader->expand(), true));
								$harvest->harvested = (string)$node;
								break;
						
							case 'error':
								$node = simplexml_import_dom($doc->importNode($reader->expand(), true));
								if (JArrayHelper::getValue($node, 'code', null, 'string') !== "noRecordsMatch")
								{
									throw new Exception((string)$node, 500);
								}
								
								break;
						
							case 'resumptionToken':
								$node = simplexml_import_dom($doc->importNode($reader->expand(), true));
								$harvest->resumptionToken = (string)$node;
								break;
						
							case 'record':
								$item = new JObject();
								
								$node = simplexml_import_dom($doc->importNode($reader->expand(), true));
								
								$item->id = $node->header->identifier;
								
								$array = array();
								
								foreach ($node->metadata->children($metadataFormat, true) as $key=>$value)
								{
									foreach ($value->getNamespaces(true) as $keyns=>$valuens)
									{
										foreach ($value->children($keyns, true) as $key2=>$value2)
										{
											if (array_key_exists($keyns.":".$key2, $array))
											{
												if (!is_array($array[$keyns.":".$key2]))
												{
													$array[$keyns.":".$key2] = array($array[$keyns.":".$key2]);
												}
												
												$array[$keyns.":".$key2][] = (string)$value2;
											}
											else
											{
												$array[$keyns.":".$key2] = (string)$value2;
											}
										}					
									}
								}
								
								$registry = new JRegistry();
								$registry->loadArray($array);
								
								$item->metadata = JSpaceFactory::getCrosswalk($registry, array('name'=>$metadataFormat))->walk();
								$item->resumptionToken = $harvest->resumptionToken;
								
								$this->_cache($item, $category);
								
								break;
								
							default:
								break;
						}
					}
				}
			}
			while ($harvest->resumptionToken);
			
			$this->_save($category);
		} 
		catch(Exception $e)
		{
			switch ($e->getCode())
			{
				case 500: // something's seriously wrong with the request. Fail immediately.
					$harvest->attempts = $this->params->get('request_attempts', 3);
					break;
					
				default:
					if ($harvest->attempts < 3)
					{
						$harvest->attempts++;
					}
					
					break;
			}
			
			if ((int)$harvest->attempts == $this->params->get('request_attempts', 3))
			{
				//$this->_rollback($harvest);
			}
			
			JLog::add($e->getMessage(), JLog::ERROR, 'jspace');
		}
		
		$this->_updateOAIHarvest($harvest);
	}
	
	/**
	 * Caches all items harvested.
	 *
	 * Items should be cached prior to writing to JSpace Records to avoid data corruption and to be able to roll 
	 * back in case of issues with the origin data source.
	 *
	 * @param  JObject  $item      The item to cache.
	 * @param  JObject  $category  The category being harvested.
	 */
	private function _cache($item, $category)
	{
		$database = JFactory::getDbo();
	
		$record = array();
		$array['id'] = $database->q($item->id);
		$array['metadata'] = $database->q(json_encode($item->metadata));
		$array['catid'] = (int)$category->id;
		$array['resumptionToken'] = $database->q($item->resumptionToken);
		
		$query = $database->getQuery(true);
		
		$query
			->insert($database->qn('#__jspaceoai_records'))
			->columns(implode(',', array_keys($array)))
			->values(implode(',', $array));
		
		$database->setQuery($query);
		$database->execute();
	}
	
	/**
	 * Gets the cached records for a particular category.
	 *
	 * @param   JObject    $category  An category object.
	 * 
	 * @return  JObject[]  An array of cached records.
	 */
	private function _getCache($category)
	{
		$database = JFactory::getDbo();
		
		$query = $database->getQuery(true);
		
		$select = array(
			$database->qn('id'),
			$database->qn('catid'),
			$database->qn('metadata'),
			$database->qn('resumptionToken'));
		
		$query
			->select($select)
			->from($database->qn('#__jspaceoai_records', 'jr'))
			->where($database->qn('jr.catid').'='.(int)$category->id);
		
		$database->setQuery($query);
		
		return $database->loadObjectList('id', 'JObject');
	}
	
	/**
	 * Discard the cached records.
	 *
	 * @param   JObject    $category  An category object.
	 */
	private function _discard($category)
	{
		$database = JFactory::getDbo();
		
		$query = $database->getQuery(true);
		$query
			->delete($database->qn('#__jspaceoai_records'))
			->where($database->qn('catid').'='.(int)$category->id);
			
		$database->setQuery($query);
		$database->execute();
	}
	
	private function _save($category)
	{
		$items = $this->_getCache($category);
		
		foreach ($items as $item)
		{
			$metadata = JArrayHelper::fromObject(json_decode($item->metadata));
		
			$array['catid'] = $category->id;
			$array['metadata'] = $item->metadata;
			$array['title'] = JArrayHelper::getValue($metadata, 'title');
			
			// if title has more than one value, grab the first.
			if (is_array($array['title']))
			{
				$array['title'] = JArrayHelper::getValue($array['title'], 0);
			}
			
			$array['published'] = $category->published;
			$array['access'] = $category->access;
			$array['language'] = $category->language;
			$array['created_by'] = $category->created_user_id;
			$array['schema'] = 'basic';
			
			$record = JSpaceRecord::getInstance();
			$record->bind($array);
			$record->save();
		}
		
		$this->_discard($category);
	}
	
	/**
	 * Update an existing harvest object or create it if it doesn't already exist.
	 *
	 * @param  JObject  $harvest  The harvest object to save.
	 */
	private function _updateOAIHarvest($harvest)
	{
		$harvest->attempts = 0;
		
		if ($this->_getOAIHarvest($harvest->catid, false))
		{
			JFactory::getDbo()->updateObject('#__jspaceoai_harvests', $harvest, 'catid', true);
		}
		else
		{
			$database = JFactory::getDbo();
			$query = $database->getQuery(true);
			
			$fields = JArrayHelper::fromObject($harvest);
			
			if (is_null(JArrayHelper::getValue($fields, 'resumptionToken')))
			{
				unset($fields['resumptionToken']);
			}
			
			$columns = array();
			$values = array();
			
			foreach ($fields as $key=>$value)
			{
				$columns[] = $database->qn($key);
				
				if (!is_numeric($value))
				{
					$value = $database->q($value);
				}
				
				$values[] = $value;
			}
			
			$query
				->insert($database->qn('#__jspaceoai_harvests'))
				->columns(implode(',', $columns))
				->values(implode(',', $values));
			
			$database->setQuery($query);
			$database->execute();
		}
	}
	
	/**
	 * Loads the OAI harvest object based on the catid param.
	 *
	 * @param    int  $catid         A category id.
	 * @param    bool  $instantiate  True if the harvest object should be created if it is not found in the 
	 * database, false otherwise. Defaults to true.
	 *
	 * @return  JObject              The OA harvest object.
	 */
	private function _getOAIHarvest($catid, $instantiate = true)
	{
		$database = JFactory::getDbo();
		$query = $database->getQuery(true);
		
		$select = array(
			$database->qn('catid'),
			$database->qn('harvested'),
			$database->qn('resumptionToken'),
			$database->qn('attempts'));
		
		$query
			->select($select)
			->from($database->qn('#__jspaceoai_harvests', 'jh'))
			->where($database->qn('jh.catid').'='.(int)$catid);
			
		$database->setQuery($query);
		
		if (!($harvest = $database->loadObject('JObject')) && $instantiate)
		{
			$harvest = new JObject();
			$harvest->catid = (int)$catid;
			$harvest->attempts = 0;
			$harvest->resumptionToken = null;
			$harvest->harvested = null;
		}
		
		return $harvest;
	}
	
	/**
	 * Prints out the plugin's help and usage information.
	 */
	private function _help()
	{
		$application = JFactory::getApplication('cli');
		
    	$out = <<<EOT
Usage: jspace oai [action] [OPTIONS]

Provides OAI-based functions within JSpace.

[action]
  harvest             Harvest records from another archive. Harvesting 
                      information is configured via JSpace's Category Manager.
  clear               Discards the cached records.
  help                Prints this help.

[OPTIONS]
  -c, --c=categoryId  Specify a single category to execute an OAI action against.
  
EOT;

		$application->out($out);
	}
}