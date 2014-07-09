<?php
/**
 * @package     JSpace
 * @subpackage  Ingestion
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('jspace.ingestion.oai.harvester');

/**
 * Provides an asset-based harvester.
 *
 * @package     JSpace
 * @subpackage  Ingestion
 */
class JSpaceIngestionOAIAssetHarvester extends JSpaceIngestionOAIHarvester
{
	protected function cache($data)
	{
		parent::cache($data);
		
		$client = new JSpaceIngestionOAIClient($this->url);
		
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin("joai");
		$this->formats['assets'] = $dispatcher->trigger('onJSpaceQueryAssetFormat');
		
		if (!$availableFormats = $client->hasMetadataFormats($this->formats['assets']))
		{
			throw new Exception('The requested url does not contain a metadata format that the JSpace harvester understands.');
		}
		
		$metadataPrefix = (string)current($availableFormats)->metadataPrefix;
		
		$reader = $client->getRecord((string)$data->header->identifier, $metadataPrefix);
		
		$data = null;
		
		while ($reader->read() && !$data)
		{
			if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'record')
			{
				$doc = new DOMDocument;
		
				$data = simplexml_import_dom($doc->importNode($reader->expand(), true));
			}
		}
		
		$context = 'joai.'.current($this->formats['assets']);
		
		$dispatcher->trigger('onJSpaceHarvestAssets', array($context, $data));
	}
	
	/** 
	 * Ingests records, moving them from the cache to the JSpace data store.
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
				
				$collection = JArrayHelper::getValue($metadata, 'collection', array(), 'array');
				
				unset($metadata->collection);
				
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
				
				$record = JSpaceRecord::getInstance();
				$record->bind($array);
				
				try
				{
					$record->save($collection);
					
					foreach ($collection as $bundle)
					{
						$assets = JArrayHelper::getValue($bundle, 'assets');
						
						foreach ($assets as $derivative)
						{
							foreach ($derivative as $asset)
							{
								JFile::delete(JArrayHelper::getValue($asset, 'tmp_name'));
							}
						}
					}
				}
				catch (Exception $e)
				{
					JLog::add(__METHOD__.' '.$e->getMessage()."\n".$e->getTraceAsString(), JLog::ERROR, 'jspace');
					
					throw $e;
				}
			}
			
			$items = $this->getCache($i);
			$i+=count($items);
		}
		
		$this->clean();
	}
}