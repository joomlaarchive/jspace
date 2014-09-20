<?php
/**
 * @package     JSpace
 * @subpackage  Ingestion
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('joomla.registry.registry');

jimport('jspace.factory');
jimport('jspace.archive.record');

/**
 * Handles importing items via a harvest url.
 *
 * @package     JSpace
 * @subpackage  Ingestion
 */
abstract class JSpaceIngestionPlugin extends JPlugin
{
    const METADATA  = 0;
    const LINKS     = 1;
    const ASSETS    = 2;

    public function __construct($subject, $config = array())
    {
        $this->autoloadLanguage;
        parent::__construct($subject, $config);        
        
        JLog::addLogger(array());
    }
    
    /** 
     * Ingest records, moving them from the cache to the JSpace data store.
     *
     * @param  JObject  $harvest  An instance of the harvest class.
     */
    public function onJSpaceHarvestIngest($harvest)
    {
        $items = $harvest->getCache(0);
        
        $i = count($items);

        while (count($items) > 0)
        {
            foreach ($items as $item)
            {
                $data = json_decode($item->data);
            
                if (!isset($data->metadata))
                {
                    throw new Exception("No metadata to ingest.");
                }
                
                $array = array();

                $array['schema'] = $this->params->get('default.schema', 'record');

                $id = $this->_mapIdentifierToId($item, $array['schema']); 
        
                $crosswalk = JSpaceFactory::getCrosswalk(new JRegistry($data->metadata));

                $array['identifiers'] = $crosswalk->getIdentifiers();
                $array['identifiers'][] = $item->id;
                
                $array['catid'] = $harvest->catid;
                $array['metadata'] = $crosswalk->walk();
                $array['title'] = $array['metadata']->get('title');
                
                // if title has more than one value, grab the first.
                if (is_array($array['title']))
                {
                    $array['title'] = JArrayHelper::getValue($array['title'], 0);
                }
                
                $array['created_by'] = $harvest->created_by;
                
                $array['tags'] = array();
                
                foreach ($crosswalk->getTags() as $tag)
                {
                    $array['tags'][] = "#new#".$tag;
                }

                $record = JSpaceRecord::getInstance($id);
                $record->bind($array);
                $record->set('access', $harvest->get('params')->get('default.access', 0));
                $record->set('language', $harvest->get('params')->get('default.language', '*'));
                $record->set('state', $harvest->get('params')->get('default.state', 1));
                
                $this->preSaveHook($record, $data, $harvest);
                $this->saveAssets($record, $data, $harvest);
                $this->postSaveHook($record, $data, $harvest);
            }

            $items = $harvest->getCache($i);
            $i+=count($items);
        }
    }
    
    /**
     * Override to carry out custom functionality before the record is saved.
     */
    protected function preSaveHook($record, $data, $harvest)
    {
    
    }

    protected function saveAssets($record, $data, $harvest)
    {
        $collection = array();

        if (isset($data->assets))
        {
            try
            {
                $assets = $data->assets;
                
                $bundle = 'oai';
                
                if ($harvest->get('params')->get('harvest_type') == self::LINKS)
                {
                    // harvest as weblinks.
                    $weblinks = array();
                    $weblinks[$bundle] = array();

                    foreach ($assets as $asset)
                    {
                        $derivative = $asset->derivative;
                        
                        $weblink = array(
                            'url'=>$asset->url,
                            'title'=>$asset->name
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

                    foreach ($assets as $asset)
                    {
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
    
    protected function postSaveHook($record, $data, $harvest)
    {
    
    }
    
    public abstract function onJSpaceHarvestDiscover($harvest);
    
    public abstract function onJSpaceHarvestRetrieve($harvest);
    
    /**
     * Parse the content type, removing any additional type settings.
     *
     * @param   string  $contentType  The content type to parse.
     *
     * @return  string  The parsed content type.
     */
    protected function parseContentType($contentType)
    {
        $parts = explode(';', $contentType);
        return trim(JArrayHelper::getValue($parts, 0));
    }
    
    private function _mapIdentifierToId($item, $schema)
    {
        $database = JFactory::getDbo();
        $query = $database->getQuery(true);
        
        $query
            ->select($database->qn('r.id'))
            ->from($database->qn('#__jspace_record_identifiers', 'i'))
            ->join('inner', $database->qn('#__jspace_records', 'r').' ON ('.$database->qn('i.record_id').'='.$database->qn('r.id').')')
            ->where($database->qn('i.id').'='.$database->q($item->id), 'and')
            ->where($database->qn('r.schema').'='.$database->q($schema));

        return (int)$database->setQuery($query)->loadResult();
    }

    private function _download($asset)
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