<?php
/**
 * @package     JSpace.Plugin
 * @subpackage  JSpace
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('jspace.ingestion.oai.client');
jimport('jspace.ingestion.oai.harvester');
jimport('jspace.ingestion.oai.assetharvester');
jimport('jspace.ingestion.plugin');

/**
 * Handles OAI harvesting from the command line.
 *
 * @package     JSpace.Plugin
 * @subpackage  JSpace
 */
class PlgJSpaceOAI extends JSpaceIngestionPlugin
{
    const METADATA  = 0;
    const LINKS     = 1;
    const ASSETS    = 2;
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
        
        if ((int)$response->code === 200 && $contentType == 'application/xml')
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
            
            $doc = new DOMDocument;
            
            $prefix = null;
            $identifier = null;
            $resumptionToken = null; // empty the resumptionToken to force a reload per page.
            
            while ($reader->read())
            {
                if ($reader->nodeType == XMLReader::ELEMENT)
                {
                    $node = simplexml_import_dom($doc->importNode($reader->expand(), true));
                    
                    switch ($reader->name)
                    {
                        case "record":
                            $this->cache($harvest, $node, $metadataPrefix);
                            
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
                            if (JArrayHelper::getValue($node, 'code', null, 'string') !== "noRecordsMatch")
                            {
                                throw new Exception((string)$node, 500);
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
     * @param  SimpleXmlElement  $data  An OAI record as an instance of the SimpleXmlElement class.
     */
    protected function cache($harvest, $data)
    {
        $context = 'joai.'.$harvest->get('params')->get('discovery.plugin.metadata');
        
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin("joai");
        $dispatcher->trigger('onJSpaceHarvestMetadata', array($context, $harvest, $data));
        
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
            
            if ($response->code !== 200)
            {
                throw new Exception('Cannot retrieve asset from OAI url.');
            }
            
            $context = 'joai.'.$metadataPrefix;
            
            $node = simplexml_load_string($response->body);
            
            $dispatcher->trigger('onJSpaceHarvestAssets', array($context, $harvest, $node));
        }
    }
    

    protected function saveHook($record, $data, $harvest)
    {
        $collection = array();
        
        if (isset($data->files))
        {
            try
            {
                $files = $data->files;
                
                $bundle = 'oai';
                
                if ($harvest->get('params')->get('harvest_type') == self::LINKS)
                {
                    // harvest as weblinks.
                    $weblinks = array();
                    $weblinks[$bundle] = array();

                    foreach ($files as $file)
                    {
                        $derivative = $file->derivative;
                        
                        $weblink = array(
                            'url'=>$file->url,
                            'title'=>$file->name
                        );
                        
                        $weblinks[$bundle][] = $weblink;
                    }

                    $record->weblinks = $weblinks;
                }
                elseif ($harvest->get('params')->get('harvest_type') == self::ASSETS)
                {
                    // download assets.
                    // set up a bundle of assets for each entry.
                    $collection[$bundle] = array();
                    $collection[$bundle]['assets'] = array();

                    foreach ($files as $file)
                    {
                        $asset = $file;
                        $asset->tmp_name = $this->_download($asset->url);
                        
                        $derivative = $asset->derivative;
                        
                        $collection[$bundle]['assets'][$derivative][] = JArrayHelper::fromObject($asset);
                    }
                }
            }
            catch (Exception $e)
            {
                JLog::add(__METHOD__.' '.$e->getMessage()."\n".$e->getTraceAsString(), JLog::ERROR, 'jspace');
                
                throw $e;
            }
        }

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