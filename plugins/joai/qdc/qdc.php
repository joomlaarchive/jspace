<?php
/**
 * @package    JOAI.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

jimport('jspace.metadata.registry');

/**
 * Harvests metadata in the Qualified Dublin Core format.
 *
 * @package  JSpace.Plugin
 */
class PlgJoaiQdc extends JPlugin
{
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->params->set('metadataPrefix', 'qdc');
    }

    public function onJSpaceQueryMetadataFormat()
    {
        return $this->params->get('metadataPrefix');
    }

    /**
     * Harvests a single qdc metadata item, saving it to the cache.
     *
     * @param   string            $context   The current metadata item context.
     * @param   SimpleXmlElement  $data      The metadata to consume.
     *
     * @return  array             An associative array of metadata.
     */
    public function onJSpaceHarvestMetadata($context, $data)
    {
        if ($context != "joai.".$this->params->get('metadataPrefix')) {
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