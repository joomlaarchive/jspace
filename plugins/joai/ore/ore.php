<?php
/**
 * @package    JOAI.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('jspace.table.cache');
jimport('jspace.metadata.registry');

/**
 * Harvests metadata and assets in the Object Reuse and Exchange format.
 *
 * @package  JSpace.Plugin
 */
class PlgJOAIORE extends JPlugin
{
	/**
	 * Instatiates an instance of the PlgJOAIORE class.
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
		
		JLog::addLogger(array());
	}
	
	/** 
	 * Gets this plugin's preferred metadata format.
	 * 
	 * @return  string  The preferred metadata format.
	 */
	public function onJSpaceQueryAssetFormat()
	{
		return 'ore';
	}
	
	/**
	 * Harvests a single record's assets if available in ore format.
	 * 
	 * @param  string            $context   The current record context.
	 * @param  SimpleXmlElement  $data      The metadata to parse for associate assets.
	 */
	public function onJSpaceHarvestAssets($context, $data)
	{
		if ($context != 'joai.ore')
		{
			return;
		}
		
		$bundle = 'oai';
		
		$metadata = new JRegistry();
		
		$identifier = (string)$data->header->identifier;
		
		$data = $data->metadata;
		
		$collection = array();
		
		$namespaces = array(
			'http://www.w3.org/2005/Atom', 
			'http://www.openarchives.org/ore/atom/',
			'http://purl.org/dc/terms/',
			'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
		
		$entry = $data->children('http://www.w3.org/2005/Atom')->entry;
		// set up an array of assets for each entry.
		$assets = array();
		
		// set up a bundle of assets for each entry.
		$collection = array();
		$collection[$bundle] = array();
		$collection[$bundle]['assets'] = array();
		
		foreach ($namespaces as $ns)
		{
			foreach ($entry->children($ns) as $tag=>$node)
			{
				if ($tag == 'link')
				{
					$attributes = $node->attributes();
					$rel = JArrayHelper::getvalue($attributes, 'rel', null, 'string');
					
					if ($rel == 'http://www.openarchives.org/ore/terms/aggregates')
					{
						$href = JArrayHelper::getvalue($attributes, 'href', null, 'string');

						$asset = array();
						$asset['tmp_name'] = urldecode($href);
						$asset['name'] = JArrayHelper::getvalue($attributes, 'title', null, 'string');
						$asset['type'] = JArrayHelper::getvalue($attributes, 'type', null, 'string');
						$asset['size'] = JArrayHelper::getvalue($attributes, 'length', null, 'string');
						$asset['derivative'] = 'original';
						
						$assets[$href] = $asset;
					}
				}
				
				if ($tag == 'triples')
				{
					foreach ($namespaces as $triplesns)
					{
						$descriptions = $node->children('http://www.w3.org/1999/02/22-rdf-syntax-ns#');
						
						foreach ($descriptions as $description)
						{
							$attributes = $description->attributes('http://www.w3.org/1999/02/22-rdf-syntax-ns#');
							
							$href = JArrayHelper::getValue($attributes, 'about', null, 'string');
							
							if (array_key_exists($href, $assets))
							{
								foreach ($description->children($triplesns) as $dtag=>$dnode)
								{
									if ($dtag == 'description')
									{
										$assets[$href]['derivative'] = (string)$dnode;
									}
								}
							}
						}
					}
				}
			}
		}
		
		foreach ($assets as $asset)
		{
			$asset['tmp_name'] = $this->_download($asset['tmp_name']);
			
			$derivative = strtolower($asset['derivative']);
			
			unset($asset['derivative']);
			
			$collection[$bundle]['assets'][$derivative][] = $asset;
		}
		
		$metadata->set('collection', $collection);

		$table = JTable::getInstance('Cache', 'JSpaceTable');
		
		if ($table->load($identifier))
		{
			$merged = new JRegistry();
			$merged->loadString($table->metadata);
			$merged->merge($metadata);
			
			$table->metadata = $merged;
			$table->store();
		}
	}
	
	public function _download($asset)
	{
		$tmp = tempnam(sys_get_temp_dir(), '');
		
		if ($source = @fopen($asset, 'r'))
		{
			$dest = fopen($tmp, 'w');
			
			while (!feof($source))
			{
				$chunk = fread($source, 1024);
				fwrite($dest, $chunk);
			}
			
			fclose($dest);
			fclose($source);
		}
		
		return $tmp;
	}
}