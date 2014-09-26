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
     * @param  JSpaceIngestionHarvest  $harvest  An instance of the harvest class.
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
                
                $id = $this->_getId($item->id, $harvest->catid);
        
                $crosswalk = JSpaceFactory::getCrosswalk(new JRegistry($data->metadata));

                $array = array();
                $array['catid'] = $harvest->catid;
                $array['schema'] = $this->params->get('default.schema', '__default__');
                $array['identifiers'] = $crosswalk->getIdentifiers();
                $array['identifiers'][] = $item->id; // also store the cache id.
                
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
                
                $collection = array();
                
                if (!isset($data->assets))
                {
                    $data->assets = $collection;
                }
                
                try
                {
                    if ($harvest->get('params')->get('harvest_type') == self::LINKS)
                    {
                        $record->weblinks = $this->_getWeblinks($record, $data->assets);
                    }
                    elseif ($harvest->get('params')->get('harvest_type') == self::ASSETS)
                    {
                        $collection = $this->_getAssets($record, $data->assets);
                        $this->_expungeExpiredAssets($record, $data->assets);
                    }
                }
                catch (Exception $e)
                {
                    JLog::add(__METHOD__.' '.$e->getMessage()."\n".$e->getTraceAsString(), JLog::ERROR, 'jspace');
                }
                
                $record->save($collection);

                foreach ($data->assets as $asset)
                {
                    JSpaceFile::delete($this->_getTempFile($asset));
                }
            }

            $items = $harvest->getCache($i);
            $i+=count($items);
        }
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
    
    /**
     * Gets a record id based on an existing unique identifier.
     * 
     * @param   string  $identifier  A cached record identifier.
     * @param   int     $catid       A cached record identifier.
     *
     * @return  int     The internal record id or 0 if no id exists.
     */
    private function _getId($identifier, $catid)
    {
        $database = JFactory::getDbo();
        $query = $database->getQuery(true);
        
        $query
            ->select($database->qn('r.id'))
            ->from($database->qn('#__jspace_record_identifiers', 'i'))
            ->join('inner', $database->qn('#__jspace_records', 'r').' ON ('.$database->qn('i.record_id').'='.$database->qn('r.id').')')
            ->where($database->qn('i.id').'='.$database->q($identifier), 'and')
            ->where($database->qn('r.catid').'='.(int)$catid);

        return (int)$database->setQuery($query)->loadResult();
    }
    
    /**
     * Gets the temporary location of the asset, downloading it if it doesn't already exist.
     * 
     * @param   stdClass  $asset  The harvested asset information.
     *
     * @return  string    The asset's temporary location.
     */
    private function _getTempFile($asset)
    {
        if (!isset($asset->tmp_name) || !JSpaceFile::exists($asset->tmp_name))
        {
            $this->_download($asset);
        }
        
        return $asset->tmp_name;
    }

    /**
     * Downloads an asset to a temporary location.
     * 
     * @param  stdClass  $asset  The harvested asset information. This method will add the 
     * temporary asset location to the asset as a class variable named tmp_name.
     */
    private function _download(&$asset)
    {        
        $asset->tmp_name = tempnam(sys_get_temp_dir(), '');
        
        if ($source = @fopen($asset->url, 'r'))
        {
            $dest = fopen($asset->tmp_name, 'w');
            
            while (!feof($source))
            {
                $chunk = fread($source, 1024);
                fwrite($dest, $chunk);
            }
            
            fclose($dest);
            fclose($source);
        }
    }
    
    /**
     * Gets a list of web links based on the harvested assets.
     *
     * @param   JSpaceRecord  $record  The record the harvested information will be saved to.
     * @param   stdClass[]    $assets  An array of assets.
     *
     * @return  array         An array of weblinks conforming to JSpace's weblink hierarchy format.
     */ 
    private function _getWeblinks($record, $assets)
    {
        // harvest as weblinks.
        $weblinks = array();
        $weblinks['weblink'] = array();
        
        $references = $record->getReferences();
        $table = JTable::getInstance('Weblink', 'WeblinksTable');

        foreach ($assets as $asset)
        {
            $derivative = $asset->derivative;
            
            $weblink = array(
                'url'=>$asset->url,
                'title'=>$asset->name
            );
            
            $found = false;
            
            // load weblink by reference/alias to determine whether updating or adding.
            // (jspaceweblink will handle deletes)
            while (($reference = current($references)) && !$found)
            {
                $alias = (int)$record->id.'-'.JFilterOutput::stringURLSafe($asset->name);
                $keys = 
                    array(
                        'id'=>$reference->id,
                        'alias'=>$alias);
                
                if ($table->load($keys))
                {
                    $weblink['id'] = $table->id;
                    $found = true;
                }
                
                next($references);
            }
            
            reset($references);
            
            $weblinks['weblink'][] = $weblink;
        }

        return $weblinks;   
    }
    
    /**
     * Gets a list of assets based on the harvested assets.
     *
     * @param   JSpaceIngestionHarvest  $record  The record the harvested information will be 
     * saved to.
     * @param   stdClass[]              $assets  An array of assets.
     *
     * @return  array                   An array of assets conforming to JSpace's asset hierarchy
     * format.
     */ 
    private function _getAssets($record, $assets)
    {
        foreach ($assets as $asset)
        {
            $this->_download($asset);
            
            $derivative = $asset->derivative;
            
            $collection[$derivative][] = JArrayHelper::fromObject($asset);
        }

        return $collection;
    }
    
    /**
     * Expunges (deletes) assets that are no longer found in the harvest source from the current 
     * JSpace record.
     *
     * @param   JSpaceIngestionHarvest  $record  The record the harvested information will be 
     * saved to.
     * @param   stdClass[]              $assets  An array of assets.
     */ 
    private function _expungeExpiredAssets($record, $assets)
    {
        // file hashes of assets to keep.
        $hashes = array();
        
        foreach ($assets as $asset)
        {
            $this->_download($asset);
            $hashes[] = JSpaceFile::getHash($this->_getTempFile($asset));
        }
        
        // delete assets that have been removed since last harvest.
        foreach ($hashes as $hash)
        {
            $deletes = $record->getAssets(array('hash'=>$hash));
            
            foreach ($deletes as $delete)
            {
                $delete->delete();
            }
        }
    }
}