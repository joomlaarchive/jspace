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
 * Harvests metadata in the OAI Dublin Core format.
 *
 * @package  JOAI.Plugin
 */
class PlgJOAIOAIDC extends JPlugin
{
	/**
	 * Instatiates an instance of the PlgJOAIOAIDC class.
	 *
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
		return 'oai_dc';
	}
	
	/**
	 * Harvests a single oai_dc metadata item, saving it to the cache.
	 * 
	 * @param  string            $context   The current metadata item context.
	 * @param  SimpleXmlElement  $data      The metadata to consume.
	 * @param  JObject           $category  The category this metadata belongs to.
	 */
	public function onJSpaceHarvestMetadata($context, $data, $category)
	{
		if ($context != 'joai.oai_dc')
		{
			return;
		}
		
		$metadata = new JRegistry();
		
		$registry = new JSpaceMetadataRegistry('oai_dc');
		
		$identifier = (string)$data->header->identifier;
		
		$data = $data->metadata;
		
		foreach ($data->children($registry->get('format'), true) as $item)
		{
			foreach (array_keys($item->getNamespaces(true)) as $keyns)
			{
				foreach ($item->children($keyns, true) as $key=>$value)
				{
					if ($key = $registry->getKey($keyns.':'.$key))
					{
						$metadata->set($key, (string)$value);
					}
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