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
	 * Harvests a single oai_dc metadata item.
	 * 
	 * @param   string            $context   The current metadata item context.
	 * @param   SimpleXmlElement  $data      The metadata to consume.
	 * 
	 * @return  JRegistry         A registry of metadata.
	 */
    public function onJSpaceHarvestMetadata($context, $data)
    {
        if ($context != 'joai.oai_dc')
        {
            return;
        }

        $metadata = new JRegistry();
        $namespaces = $data->getDocNamespaces(true);

        foreach ($namespaces as $prefix=>$namespace)
        {
            if ($prefix)
            {
                $data->registerXPathNamespace($prefix, $namespace);
                $tags = $data->xpath('//oai_dc:dc/'.$prefix.':*');
            
                foreach ($tags as $tag)
                {
                    $metadata->set($prefix.'.'.(string)$tag->getName(), (string)$tag);
                }
            }
        }

        return $metadata;
    }
}