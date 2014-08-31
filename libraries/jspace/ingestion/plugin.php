<?php
/**
 * @package     JSpace.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('joomla.registry.registry');

jimport('jspace.factory');
jimport('jspace.archive.record');

/**
 * Handles importing items via an OpenSearch compliant search engine.
 *
 * @package     JSpace.Plugin
 */
abstract class JSpaceIngestionPlugin extends JPlugin
{
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
                
                $metadata = JArrayHelper::fromObject($data->metadata);
                
                $identifier = JTable::getInstance('RecordIdentifier', 'JSpaceTable');
                
                $id = 0;
                
                // see if there is already a record we can update.
                if ($identifier->load(array('id'=>$item->id)))
                {
                    $id = (int)$identifier->record_id;
                }
                
                $array['identifiers'] = array($item->id);
                $array['catid'] = $harvest->catid;
                $array['metadata'] = $metadata;
                
                $array['title'] = JArrayHelper::getValue($metadata, 'title');
                
                // if title has more than one value, grab the first.
                if (is_array($array['title']))
                {
                    $array['title'] = JArrayHelper::getValue($array['title'], 0);
                }
                
                $array['created_by'] = $harvest->created_by;
                $array['schema'] = 'basic';
                
                $record = JSpaceRecord::getInstance($id);
                $record->bind($array);
                $record->set('access', $harvest->get('params')->get('default.access', 0));
                $record->set('language', $harvest->get('params')->get('default.language', '*'));
                $record->set('state', $harvest->get('params')->get('default.state', 1));
                
                $this->preSaveHook($record, $data, $harvest);
                $this->saveHook($record, $data, $harvest);
                $this->postSaveHook($record, $data, $harvest);
            }
            
            $items = $harvest->getCache($i);
            $i+=count($items);
        }
    }
    
    protected function preSaveHook($record, $data, $harvest)
    {
    
    }

    protected function saveHook($record, $data, $harvest)
    {
        $record->save();
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
}