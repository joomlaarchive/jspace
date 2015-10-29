<?php
/**
 * @package    JOai.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Harvests metadata and assets in the Object Reuse and Exchange format.
 *
 * @package  JOai.Plugin
 */
class PlgJOaiOre extends JPlugin
{
    /**
     * Instatiates an instance of the PlgJOaiOre class.
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
    public function onJOaiQueryAssetFormat()
    {
        return 'ore';
    }

    /**
     * Harvests a single record's assets if available in ore format.
     *
     * @param   string                       $context  The current record context.
     * @param   SimpleXmlElement             $data     The metadata to parse for associate assets.
     *
     * @return  \Joomla\Registry\Registry[]  Returns an array of asset metadata as Registry objects.
     */
    public function onJOaiHarvestAssets($context, $data)
    {
        if ($context != 'joai.ore') {
            return;
        }

        // set up an array of files for each entry.
        $assets = array();

        $data->registerXPathNamespace('default', 'http://www.openarchives.org/OAI/2.0/');
        $data->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
        $data->registerXPathNamespace('oreatom', 'http://www.openarchives.org/ore/atom/');
        $data->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        $data->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/');

        $links = $data->xpath('//atom:link[@rel="http://www.openarchives.org/ore/terms/aggregates"]');

        foreach ($links as $link) {
            $attributes = $link->attributes();

            $href = JArrayHelper::getvalue($attributes, 'href', null, 'string');
            $name = JArrayHelper::getValue($attributes, 'title', null, 'string');
            $type = JArrayHelper::getValue($attributes, 'type', null, 'string');
            $size = JArrayHelper::getValue($attributes, 'length', null, 'string');

            $asset = new JRegistry;
            $asset->set('url', urldecode($href));
            $asset->set('name', $name);
            $asset->set('type', $type);
            $asset->set('size', $size);

            $derivatives = $data->xpath('//oreatom:triples/rdf:Description[@rdf:about="'.$asset->get('url').'"]/dcterms:description');
            $derivative = strtolower(JArrayHelper::getValue($derivatives, 0, 'original', 'string'));

            $asset->set('derivative', $derivative);

            $assets[] = $asset;
        }

        return $assets;
    }
}
