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

    public function onJSpaceProviderMetadataFormat()
    {
        return array(
            'metadataPrefix'=>'oai_dc',
            'schema'=>'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
            'metadataNamespace'=>'http://www.openarchives.org/OAI/2.0/oai_dc/');
    }

    public function onJSpaceCrosswalkMetadata($context, $data)
    {
        if ($context != 'joai.oai_dc')
        {
            return;
        }

        $xml = new DomDocument();
        $oaiDc = $xml->createElement('oai_dc:dc');
        $xml->appendChild($oaiDc);
        $oaiDc->setAttribute("xmlns:oai_dc", "http://www.openarchives.org/OAI/2.0/oai_dc/");
        $oaiDc->setAttribute("xmlns:dc", "http://purl.org/dc/elements/1.1/");
        $oaiDc->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $oaiDc->setAttribute("xsi:schemaLocation", "http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd");

        $properties = $data->getProperties();
        unset($properties['metadata']);

        $registry = new JRegistry;
        $registry->loadArray($properties);
        $registry->loadString($data->get('metadata'));

        $crosswalk = JSpaceFactory::getCrosswalk($registry);
        $metadata = $crosswalk->getSpecialMetadata(array('dc'), true);

        $keys = JSpaceMetadataCrosswalk::getKeys($metadata);

        foreach ($keys as $key) {
            foreach ($metadata->get($key) as $value) {
                if ($value) {
                    $oaiDc->appendChild($xml->createElement($key, $value));
                }
            }
        }

        return $oaiDc;
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

        $metadata = new JRegistry;
        $namespaces = $data->getDocNamespaces(true);

        foreach ($namespaces as $prefix=>$namespace)
        {
            if ($prefix)
            {
                $data->registerXPathNamespace($prefix, $namespace);
                $tags = $data->xpath('//'.$prefix.':*');

                foreach ($tags as $tag)
                {
                    if (JString::trim((string)$tag))
                    {
                        $values = $metadata->get($prefix.'.'.(string)$tag->getName());

                        if (!is_array($values))
                        {
                            $values = array();
                        }

                        $values[] = JString::trim((string)$tag);

                        $metadata->set($prefix.'.'.(string)$tag->getName(), $values);
                    }
                }
            }
        }

        return $metadata;
    }
}