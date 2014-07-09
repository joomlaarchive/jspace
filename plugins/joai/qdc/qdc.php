<?php
/**
 * @package    JOAI.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('jspace.metadata.registry');
jimport('jspace.table.cache');

/**
 * Harvests metadata in the Qualified Dublin Core format.
 *
 * @package  JSpace.Plugin
 */
class PlgJOAIQDC extends JPlugin
{
	/**
	 * Instatiates an instance of the PlgJOAIQDC class.
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
	public function onJSpaceQueryMetadataFormat()
	{
		return 'qdc';
	}
	
	/**
	 * Harvests a single qdc metadata item, saving it to the cache.
	 * 
	 * @param  string            $context   The current metadata item context.
	 * @param  SimpleXmlElement  $data      The metadata to consume.
	 * @param  JObject           $category  The category this metadata belongs to.
	 */
	public function onJSpaceHarvestMetadata($context, $data, $category)
	{
		if ($context != 'joai.qdc')
		{
			return;
		}
	
		$metadata = new JRegistry();
		
		$registry = new JSpaceMetadataRegistry('qdc');
		
		$identifier = (string)$data->header->identifier;
		
		$data = $data->metadata;
		
		foreach ($data->getNamespaces(true) as $keyns=>$valuens)
		{
			foreach ($data->children($valuens) as $key=>$value)
			{
				if ($key = $registry->getKey($keyns.':'.$key))
				{
					if (is_array($metadata->get($key)))
					{
						$array = $metadata->get($key);
					}
					else
					{
						$array = array();
					}
					
					$array[] = (string)$value;
					
					$metadata->set($key, $array);
				}
			}
		}
		
		$table = JTable::getInstance('Cache', 'JSpaceTable');
		$table->set('id', $identifier);
		$table->set('metadata', $metadata);
		$table->set('catid', $category->id);
		$table->store();
	}
}