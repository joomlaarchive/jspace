<?php
/**
 * @package     JSpace.Plugin
 * @subpackage  JSpace
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

use JSpace\Ingestion\Oai\Client;
use JSpace\Ingestion\Oai\Harvester;
use JSpace\Ingestion\Oai\AssetHarvester;
use JSpace\Ingestion\Plugin as JSpaceIngestionPlugin;

/**
 * Handles OAI harvesting from the command line.
 *
 * @package     JSpace.Plugin
 * @subpackage  JSpace
 */
class PlgJSpaceOAI extends JSpaceIngestionPlugin
{
    const FOLLOW_ON = 0;

    /**
     * Attempts to discover whether the harvest configuration points to an OAI-enabled url.
     *
     * @param   string     $sourceUrl  The source url to discover.
     *
     * @return  JRegistry  An OAI description as a JRegistry or false if no description can be
     * found.
     */
    public function onJSpaceHarvestDiscover($sourceUrl)
    {
        $discovered = false;

        $url = new JUri($sourceUrl);
        $url->setVar('verb', 'Identify');

        $http = JHttpFactory::getHttp();
        $response = $http->get($url);

        $contentType = JArrayHelper::getValue($response->headers, 'Content-Type');
        $contentType = $this->parseContentType($contentType);

        $validContentType = (in_array($contentType, array('text/xml', 'application/xml')) !== false);

        if ((int)$response->code === 200 && $validContentType)
        {
            $url->setVar('verb', 'ListMetadataFormats');

            $http = JHttpFactory::getHttp();
            $response = $http->get($url);

            if ((int)$response->code === 200)
            {
                $dom = new DomDocument();
                $dom->loadXML($response->body);

                $nodes = $dom->getElementsByTagName('metadataPrefix');
                $availablePrefixes = array();

                foreach ($nodes as $node)
                {
                    $availablePrefixes[] = ((string)$node->nodeValue);
                }

                $dispatcher = JEventDispatcher::getInstance();
                JPluginHelper::importPlugin("joai");
                $result = $dispatcher->trigger('onJSpaceQueryMetadataFormat');
                $found = false;
                while (($metadataPrefix = current($result)) && !$found)
                {
                    if (array_search($metadataPrefix, $availablePrefixes) !== false)
                    {
                        $discovered = new JRegistry;
                        $discovered->set('discovery.type', 'oai');
                        $discovered->set('discovery.url', (string)$sourceUrl);
                        $discovered->set('discovery.plugin.metadata', (string)$metadataPrefix);

                        $found = true;
                    }

                    next($result);
                }

                // if a metadata format can be discovered, also discover the asset format.
                if ($discovered)
                {
                    $result = $dispatcher->trigger('onJSpaceQueryAssetFormat');

                    $found = false;
                    while (($assetPrefix = current($result)) && !$found)
                    {
                        if (array_search($assetPrefix, $availablePrefixes) !== false)
                        {
                            $discovered->set('discovery.plugin.assets', (string)$assetPrefix);

                            $found = true;
                        }

                        next($result);
                    }
                }
            }
        }

        return $discovered;
    }

    /**
     * Retrieves items from an OAI-enabled url.
     *
     * @param  JObject  $harvest  The harvesting details.
     */
    public function onJSpaceHarvestRetrieve($harvest)
    {
        if ($harvest->get('params')->get('discovery.type') != 'oai')
        {
            return;
        }

        $resumptionToken = null;

        $http = JHttpFactory::getHttp();

        $metadataPrefix = $harvest->get('params')->get('discovery.plugin.metadata');

        do
        {
            $queries = array();

            if ($resumptionToken)
            {
                $queries['resumptionToken'] = $resumptionToken;

                // take a break to avoid any timeout issues.
                if (($sleep = $harvest->get('params')->get('follow_on', self::FOLLOW_ON)) != 0)
                {
                    sleep($sleep);
                }
            }
            else
            {
                $queries['metadataPrefix'] = $metadataPrefix;

                if ($harvest->harvested != JFactory::getDbo()->getNullDate())
                {
                    $queries['from'] = JFactory::getDate($harvest->harvested)->toISO8601();
                }

                if ($set = $harvest->get('params')->get('set'))
                {
                    $queries['set'] = $set;
                }
            }

            $url = new JUri($harvest->get('params')->get('discovery.url'));
            $url->setQuery($queries);
            $url->setVar('verb', 'ListRecords');

            $response = $http->get($url);

            $reader = new XMLReader;
            $reader->xml($response->body);

            $prefix = null;
            $identifier = null;
            $resumptionToken = null; // empty the resumptionToken to force a reload per page.

            while ($reader->read())
            {
                if ($reader->nodeType == XMLReader::ELEMENT)
                {
                    $doc = new DOMDocument;
                    //$node = simplexml_import_dom($doc->importNode($reader->expand(), true));
                    $doc->appendChild($doc->importNode($reader->expand(), true));

                    $node = simplexml_load_string($doc->saveXML());

                    switch ($reader->name)
                    {
                        case "record":
                            $this->cache($harvest, $node);

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
                            if (JArrayHelper::getValue($node, 'code', null, 'string') !== "noRecordsMatch") {
                                throw new Exception((string)$node, 500);
                            }
                            else {
                                throw new Exception(JArrayHelper::getValue($node, 'code', null, 'string'));
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
    }

    /**
     * Caches a harvested record.
     *
     * @param  JObject           $harvest  The harvest configuration.
     * @param  SimpleXmlElement  $data     An OAI record as an instance of the SimpleXmlElement class.
     */
    protected function cache($harvest, $data)
    {
        if (isset($data->header->identifier))
        {
            $context = 'joai.'.$harvest->get('params')->get('discovery.plugin.metadata');

            $dispatcher = JEventDispatcher::getInstance();
            JPluginHelper::importPlugin("joai");

            $array = $dispatcher->trigger('onJSpaceHarvestMetadata', array($context, $data->metadata));

            $cache = array("metadata"=>JArrayHelper::getValue($array, 0));

            if ($harvest->get('params')->get('harvest_type') !== self::METADATA)
            {
                $metadataPrefix = $harvest->get('params')->get('discovery.plugin.assets');

                $queries = array(
                    'verb'=>'GetRecord',
                    'identifier'=>(string)$data->header->identifier,
                    'metadataPrefix'=>$metadataPrefix);

                $url = new JUri($harvest->get('params')->get('discovery.url'));
                $url->setQuery($queries);

                $http = JHttpFactory::getHttp();
                $response = $http->get($url);

                if ((int)$response->code == 200)
                {
                    $context = 'joai.'.$metadataPrefix;

                    $node = simplexml_load_string($response->body);

                    $array = $dispatcher->trigger('onJSpaceHarvestAssets', array($context, $node));

                    $cache["assets"] = JArrayHelper::getValue($array, 0, array());
                }
                else
                {
                    JLog::add('Cannot retrieve asset from OAI url.', JLog::$WARNING, 'jspace');
                }

            }

            $table = JTable::getInstance('Cache', 'JSpaceTable');
            $table->set('id', (string)$data->header->identifier);
            $table->set('data', json_encode($cache));
            $table->set('harvest_id', (int)$harvest->id);
            $table->store();
        }
    }
}