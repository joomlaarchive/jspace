<?php
/**
 * @package    JOai.Plugin
 *
 * @copyright   Copyright (C) 2014-2015 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

\JLoader::import('jspace.table.cache');

use Joomla\Registry\Registry;
use JSpace\Metadata\Crosswalk;

/**
 * Harvests metadata in the OAI Dublin Core format.
 *
 * @package  Joai.Plugin
 */
class PlgJoaiOaidc extends JPlugin
{
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->params->set('metadataPrefix', 'oai_dc');
        $this->params->set('schema', 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd');
        $this->params->set('metadataNamespace', 'http://www.openarchives.org/OAI/2.0/oai_dc/');
    }

    public function onJSpaceProviderMetadataFormat()
    {
        return $this->params->get('metadataPrefix');
    }

    public function onJSpaceCrosswalkMetadata($context, $data)
    {
        if ($context != "joai.".$this->params->get('metadataPrefix')) {
            return;
        }

        $xml = new DomDocument();
        $oaiDc = $xml->createElementNS("http://www.openarchives.org/OAI/2.0/oai_dc/", 'oai_dc:dc');
        $xml->appendChild($oaiDc);
        $oaiDc->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:dc", "http://purl.org/dc/elements/1.1/");

        $oaiDc->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $oaiDc->setAttributeNS("http://www.w3.org/2001/XMLSchema-instance", 'schemaLocation', "http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd");

        $properties = $data->getProperties();
        unset($properties['metadata']);

        $registry = new Registry;
        $registry->loadArray($properties);
        $registry->loadString($data->get('metadata'));

        $crosswalk = \JSpace\Factory::getCrosswalk($registry);
        $metadata = $crosswalk->getSpecialMetadata(array('dc'), true);

        $keys = array_keys($metadata);

        foreach ($keys as $key) {
            foreach (\JArrayHelper::getValue($metadata, $key) as $value) {
                if ($value) {
                    $oaiDc->appendChild($xml->createElementNS("http://purl.org/dc/elements/1.1/", str_replace('.', ':', $key), $value));
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
		return $this->params->get('metadataPrefix');
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
        if ($context != 'joai.oai_dc') {
            return;
        }

        $metadata = array();
        $namespaces = $data->getDocNamespaces(true);

        foreach ($namespaces as $prefix=>$namespace) {
            if ($prefix) {
                $data->registerXPathNamespace($prefix, $namespace);
                $tags = $data->xpath('//'.$prefix.':*');

                foreach ($tags as $tag) {
                    if (JString::trim((string)$tag)) {
                        $key = $prefix.':'.(string)$tag->getName();

                        $values = JArrayHelper::getValue($metadata, $key);

                        if (!is_array($values)) {
                            $values = array();
                        }

                        $values[] = (string)$tag;

                        $metadata[$key] = $values;
                    }
                }
            }
        }

        return $metadata;
    }
}