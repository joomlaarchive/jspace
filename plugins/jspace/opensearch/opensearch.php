<?php
/**
 * @package     JSpace.Plugin
 * @subpackage  JSpace
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('joomla.registry.registry');

jimport('jspace.factory');
jimport('jspace.archive.record');
jimport('jspace.ingestion.plugin');

/**
 * Handles importing items via an OpenSearch compliant search engine.
 *
 * @package     JSpace.Plugin
 * @subpackage  JSpace
 */
class PlgJSpaceOpenSearch extends JSpaceIngestionPlugin
{    
    /**
     * Attempts to discover whether the harvest configuration points to an OpenSearch-enabled url.
     * 
     * @param   string     $sourceUrl  The url to discover.
     *
     * @return  JRegistry  A OpenSearch description as a JRegistry or false if no description 
     * can be found.
     */
    public function onJSpaceHarvestDiscover($sourceUrl)
    {
        return $this->_discover($sourceUrl);
    }
    
    /**
     * Recursively discovers the atom/rss OpenSearch url.
     *
     * @param   string     $sourceUrl  The url to discover.
     *
     * @return  JRegistry  A OpenSearch description as a JRegistry or false if no description 
     * can be found.
     */
    private function _discover($sourceUrl)
    {
        $discovered = false;
    
        $url = new JUri($sourceUrl);
        $http = JHttpFactory::getHttp();
        $response = $http->get($url);

        if ((int)$response->code === 200)
        {
            $contentType = JArrayHelper::getValue($response->headers, 'Content-Type');
            $contentType = $this->parseContentType($contentType);
            
            if ($contentType === 'text/html')
            {
                $dom = new DomDocument();
                $dom->loadHTML($response->body);
                $xpath = new DOMXPath($dom);
                
                $opendocument = '//html/head/link[@type="application/opensearchdescription+xml"]';
                $links = $xpath->query($opendocument);
                
                if (count($links))
                {
                    $link = $dom->importNode($links->item(0), true);
                    
                    $href = new JUri($link->getAttribute('href'));
                    
                    // sometimes the opensearch url is not complete so complete it.
                    if (!$href->getScheme())
                    {
                        $url->setQuery(array());
                        $url->setPath($href);
                        $url->setQuery($href->getQuery());
                    }
                    
                    $url->setVar('source', $sourceUrl);

                    $discovered = $this->_discover((string)$url);
                }
            }
            else if ($contentType === 'application/opensearchdescription+xml')
            {
                $originalUrl = $url->getVar('source');
                
                $xml = new SimpleXMLElement($response->body);

                if (isset($xml->Url))
                {
                    $urls = $xml->Url;
                    $i = 0;
                    while (($url = $urls[$i]) && !$discovered)
                    {
                        $template = JArrayHelper::getValue($url, 'template', null, 'string');
                        $type = JArrayHelper::getValue($url, 'type', null, 'string');
                    
                        $link = new JUri($template);
                        $queries = $link->getQuery(true);
                        //@todo a search/replace will probably suffice.
                        foreach ($queries as $keyq => &$valueq)
                        {
                            if ($valueq === "{searchTerms}")
                            {
                                $queries[$keyq] = JUri::getInstance($originalUrl)->getVar($keyq);
                                break;
                            }
                        }
                        
                        $link->setQuery($queries);
                        
                        // don't try and discover the html search.
                        if (strpos($type, 'text/html') === false)
                        {
                            $discovered = $this->_discover((string)$link);
                        }
                        
                        $i++;
                    }
                }
            }
            else if (array_search($contentType, array('application/atom+xml', 'application/rss+xml')) !== false)
            {
                //@todo JUri not updating url via setVar. May need more testing.
                $discovered = new JRegistry;
                $discovered->set('discovery.type', 'opensearch');
                $discovered->set('discovery.url', (string)$sourceUrl);
                $discovered->set('discovery.plugin.type', (string)$contentType);
            }
            
        }
        
        return $discovered;
    }
    
    /**
     * Captures the onJSpaceHarvestRetrieve event, retrieving records via the configured 
     * OpenSearch results.
     *
     * @param  JObject  $harvest  The harvest information.
     */
    public function onJSpaceHarvestRetrieve($harvest)
    {
        if ($harvest->get('params')->get('discovery.type') != 'opensearch')
        {
            return;
        }
    
        $templateUrl = $harvest->get('params')->get('discovery.url');

        $http = JHttpFactory::getHttp();
        $parameters = array(
            "{startIndex?}"=>0,
            "{startPage?}"=>0,
            "{count?}"=>100,
            "{language}"=>urlencode(JFactory::getLanguage()->getName()),
            "{inputEncoding}"=>"UTF-8",
            "{outputEncoding}"=>"UTF-8");
        
        $dom = new DomDocument();        
        $count = 0; // the number of records to retrieve.
        
        do
        {
            $url = new JUri($templateUrl);
            $queries = $url->getQuery(true);
            
            foreach ($queries as $keyq=>$valueq)
            {
                if (array_key_exists($valueq, $parameters))
                {
                    $queries[$keyq] = $parameters[$valueq];
                }
            }
            
            $url->setQuery($queries);
            
            $response = $http->get($url);
            
            if ((int)$response->code === 200)
            {
                $reader = new XMLReader;
                $reader->xml($response->body);
            
                while($reader->read())
                {
                    if ($reader->localName == 'entry')
                    {
                        $entry = simplexml_import_dom($dom->importNode($reader->expand(), true));
                        $this->cache($harvest, $entry);
                    }
                    
                    if ($reader->localName == 'item')
                    {
                        $entry = simplexml_import_dom($dom->importNode($reader->expand(), true));
                        $this->cache($harvest, $entry);
                    }
                    
                    if ($reader->localName == 'totalResults')
                    {
                        $totalResults = simplexml_import_dom($dom->importNode($reader->expand(), true));
                        
                        if (!$count)
                        {
                            $count = (int)$totalResults;
                        }
                    }
                }
            }
            
            $parameters['{startIndex?}']+=$parameters['{count?}'];
            $parameters['{startPage?}']+=1;
        }
        while($parameters['{startIndex?}'] < $count);
    }
    
    protected function cache($harvest, $data)
    {    
        if ($harvest->get('params')->get('discovery.plugin.type') == 'application/atom+xml')
        {
            $identifier = (string)$data->id;
        }
        else
        {
            $identifier = (string)$data->link;
        }
    
        if ($identifier)
        {
            $tags = get_meta_tags($identifier);
            $metadata = new JRegistry;
            
            foreach ($tags as $key=>$value)
            {
                $metadata->set($key, $value);
            }
            
            $metadata = JSpaceFactory::getCrosswalk($metadata, array('name'=>'dc'))->walk();
            
            $table = JTable::getInstance('Cache', 'JSpaceTable');
            $table->set('id', $identifier);
            $table->set('data', json_encode(array("metadata"=>$metadata)));
            $table->set('harvest_id', (int)$harvest->id);
            $table->store();
        }
    }
}