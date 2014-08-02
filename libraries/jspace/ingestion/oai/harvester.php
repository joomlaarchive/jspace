<?php
/**
 * @package     JSpace
 * @subpackage  Ingestion
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
jimport('jspace.ingestion.oai.harvestable');
jimport('jspace.table.harvest');

/**
 * Provides an metadata-only harvester.
 *
 * @package     JSpace
 * @subpackage  Ingestion
 */
class JSpaceIngestionOAIHarvester extends JObject implements JSpaceIngestionOAIHarvestable
{
	const FOLLOW_ON = 0;

	/**
	 * @var  JTable  $category  An instance of the JTableCategory class.
	 */
	protected $category;
	
	/**
	 * @var  JDate  $harvested  The date the harvest last executed.
	 */
	protected $harvested;
	
	/**
	 * @var  JUri  $url  AN OAI-aware url.
	 */
	protected $url;
	
	/**
	 * Initiates an instance of the JSpaceIngestionOAIHarvester class.
	 *
	 * @param  JObject  $category  Category information as an instance of the JObject class.
	 */
	public function __construct($category)
	{
		$this->category = $category;
		$this->url = new JUri($category->params->get('oai_url'));
		
		$this->params = JComponentHelper::getParams('com_jspace');
		
		$table = JTable::getInstance('Harvest', 'JSpaceTable');
		
		if ($table->load((int)$this->category->id))
		{
			$this->harvested = JFactory::getDate($table->harvested);
		}
		else
		{
			$this->harvested = JFactory::getDate(JFactory::getDbo()->getNullDate());
		}
	}
	
	/**
	 * Harvests a collection of OAI records.
	 *
	 * The harvest method logs all errors that are generated during the harvesting process and rethrows
	 * the exception for further processing by the calling class.
	 *
	 * @return  JSpaceIngestionOAIHarvester  The instance of this class for chaining other methods. Mainly 
	 * used for chaining the ingest method.
	 */
	public function harvest()
	{
		$resumptionToken = null;
	
		try
		{
			$client = new JSpaceIngestionOAIClient($this->url);
			
			$dispatcher = JEventDispatcher::getInstance();
			JPluginHelper::importPlugin("joai");
			$this->formats['metadata'] = $dispatcher->trigger('onJSpaceQueryMetadataFormat');
			
			if (!$availableFormats = $client->hasMetadataFormats($this->formats['metadata']))
			{
				throw new Exception('the requested url does not contain a metadata format that the JSpace harvester understands.');
			}
			
			$metadataPrefix = (string)current($availableFormats)->metadataPrefix;
			
			do
			{
				$queries = array();
				
				if ($resumptionToken)
				{
					$queries['resumptionToken'] = $resumptionToken;
					
					// take a break to avoid any timeout issues.
					if (($sleep = $this->params->get('follow_on', self::FOLLOW_ON)) != 0)
					{
						sleep($sleep);
					}
				}
				else
				{
					$queries['metadataPrefix'] = $metadataPrefix;
					
					if ($this->harvested != JFactory::getDate(JFactory::getDbo()->getNullDate()))
					{
						$queries['from'] = $this->harvested->toISO8601();
					}
					
					if ($this->harvested != JFactory::getDate(JFactory::getDbo()->getNullDate()))
					{
						$queries['from'] = $this->harvested->toISO8601();
					}
				}
			
				$reader = $client->listRecords($queries);
				
				$doc = new DOMDocument;
				
				$prefix = null;
				$identifier = null;
				
				while ($reader->read())
				{
					if ($reader->nodeType == XMLReader::ELEMENT)
					{
						$node = simplexml_import_dom($doc->importNode($reader->expand(), true));
						
						switch ($reader->name)
						{
							case "record":
								$this->cache($node);
								
								break;
								
							case 'responseDate':
								// only get the response date if fresh harvest.
								if (!$resumptionToken)
								{
									$this->harvested = JFactory::getDate($node);
								}
								
								break;
							
							case 'request':
								$prefix = JArrayHelper::getValue($node, 'metadataPrefix', null, 'string');
								
								break;
								
							case 'error':
								if (JArrayHelper::getValue($node, 'code', null, 'string') !== "noRecordsMatch")
								{
									throw new Exception((string)$node, 500);
								}
								
								break;
						
							case 'resumptionToken':
								$resumptionToken = (string)$node;
								break;
								
							default:
								
								break;
						}
					}
				}
			}
			while ($resumptionToken);
			
			$this->save();
		} 
		catch(Exception $e)
		{
			switch ($e->getCode())
			{
				case 500: // something's seriously wrong with the request. Fail immediately.
					$this->failures = $this->params->get('request_failures', 3);
					break;
					
				default:
					
					break;
			}
			
			$this->rollback();
			
			JLog::add(__METHOD__.' '.$e->getMessage()."\n".$e->getTraceAsString(), JLog::ERROR, 'jspace');
			
			// rethrow the exception so OAI events can use it to report back to the user.
			throw $e;
		}
		
		return $this;
	}
	
	/**
	 * Caches a harvested record.
	 * 
	 * @param  SimpleXmlElement  $data  An OAI record as an instance of the SimpleXmlElement class.
	 */
	protected function cache($data)
	{
		$context = 'joai.'.current($this->formats['metadata']);
		
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin("joai");
		$dispatcher->trigger('onJSpaceHarvestMetadata', array($context, $data, $this->category));
	}
	
	/** 
	 * Ingest records, moving them from the cache to the JSpace data store.
	 */
	public function ingest()
	{
		$items = $this->getCache(0);
		
		$i = count($items);
		
		while (count($items) > 0)
		{
			foreach ($items as $item)
			{
				$metadata = JArrayHelper::fromObject(json_decode($item->metadata));
				
				$identifier = JTable::getInstance('RecordIdentifier', 'JSpaceTable');
				
				$id = 0;
				
				// see if there is already a record we can update.
				if ($identifier->load(array('id'=>$item->id)))
				{
                    $id = (int)$identifier->record_id;
				}
				
				$array['identifiers'] = array($item->id);
				$array['catid'] = $this->category->id;
				$array['metadata'] = $item->metadata;
				
				$array['title'] = JArrayHelper::getValue($metadata, 'title');
				
				// if title has more than one value, grab the first.
				if (is_array($array['title']))
				{
					$array['title'] = JArrayHelper::getValue($array['title'], 0);
				}
				
				$array['published'] = $this->category->published;
				$array['access'] = $this->category->access;
				$array['language'] = $this->category->language;
				$array['created_by'] = $this->category->created_user_id;
				$array['schema'] = 'basic';
				
				$record = JSpaceRecord::getInstance($id);
				$record->bind($array);
				$record->save();
			}
			
			$items = $this->getCache($i);
			$i+=count($items);
		}
		
		$this->clean();
	}
	
	/**
	 * Saves the current state of the harvester.
	 */
	public function save()
	{
		$table = JTable::getInstance('Category');
		
		if ($table->load($this->category->id))
		{
			$params = new JRegistry();
			$params->loadString($table->params);
			
			$params->set('oai_harvested', JFactory::getDate($this->harvested)->toSql());
			$table->params = (string)$params;
			$table->store();
		}
	}
	
	/**
	 * Discard the cached records for the current category.
	 */
	public function clean()
	{
		$database = JFactory::getDbo();
		
		$query = $database->getQuery(true);
		$query
			->delete($database->qn('#__jspace_cache'))
			->where($database->qn('catid').'='.(int)$this->category->id);
			
		$database->setQuery($query);
		$database->execute();
	}
	
	/**
	 * Reset the category's harvesting details, forcing the harvest to start from the beginning.
	 */
	public function reset()
	{
		$table = JTable::getInstance('Category');
		
		if ($table->load($this->category->id))
		{
			$old = new JRegistry();
			$old->loadString($table->params);
			$this->harvested = JFactory::getDate(JFactory::getDbo()->getNullDate());
			
			$array = $old->toArray();
			unset($array['oai_harvested']);
			
			$new = new JRegistry();
			$table->params = (string)$new->loadArray($array);
			
			$table->store();
		}
		
		$this->clean();
	}
	
	/**
	 * Gets the cached records for a particular category.
	 *
	 * The cache can be returned in chunks to avoid performance issues.
	 *
	 * @param   int        $start  The cache offset.
	 * @param   int        $limit  The size of the cache to return.
	 * 
	 * @return  JObject[]  An array of cached records.
	 */
	public function getCache($start = 0, $limit = 100)
	{
		$database = JFactory::getDbo();
		
		$query = $database->getQuery(true);
		
		$select = array(
			$database->qn('id'),
			$database->qn('catid'),
			$database->qn('metadata'));
		
		$query
			->select($select)
			->from($database->qn('#__jspace_cache', 'jc'))
			->where($database->qn('jc.catid').'='.(int)$this->category->id);
		
		$database->setQuery($query, $start, $limit);
		
		return $database->loadObjectList('id', 'JObject');
	}
	
	/**
	 * Rolls back harvest to previous "good" state.
	 */
	public function rollback()
	{
		$table = JTable::getInstance('Category');
		
		if ($table->load($this->category->id))
		{
			$params = new JRegistry();
			$params->loadString($table->params);
			$this->harvested = $params->get('oai_harvested');
			$params->set('oai_harvested', JFactory::getDate($this->harvested)->toSql());
			$table->params = (string)$params;
			
			$table->store();
		}
		
		$this->clean();
	}
}