<?php
/**
 * @package     JSpace
 * @subpackage  Metadata
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('joomla.filesystem.file');

jimport('jspace.metadata.registry');

/**
 * Provides crosswalks based on a preconfigured crosswalk ruleset.
 *
 * @package     JSpace
 * @subpackage  Metadata
 */
class JSpaceMetadataCrosswalk extends JObject
{
    protected $source;
    
    protected $metadata;

    /**
     * The crosswalk registry;
     * @var array
     */
    protected $registry;
    
    /**
     * Instantiates an instance of the file metadata crosswalk based on a registry file. 
     * 
     * @param  string  $source    The source metadata to crosswalk.
     * @param  string  $registry  The name of the crosswalk registry.
     */
    public function __construct($source, $registry = 'crosswalk')
    {
        parent::__construct();

        $this->source = $source;
        
        $path = JPATH_ROOT.'/administrator/components/com_jspace/crosswalks/'.$registry.'.json';

        if (!JFile::exists($path))
        {
            throw new Exception('No crosswalk file found.');
        }
        
        $crosswalk = json_decode(file_get_contents($path), true);

        $this->registry = $crosswalk;
    }
    
    /**
     * Walks the source metadata, remapping each metadata value from its current metadata key to a
     * different metadata key name as defined in the crosswalk registry file.
     *
     * @param   bool   $reverse  True if the crosswalk should be reversed, false otherwise. 
     * Defaults to false
     *
     * @return  JRegistry  A registry of metadata values.
     */
    public function walk($reverse = false)
    {
        $metadata = new JRegistry();
        $metadata->merge($this->getSpecialMetadata($reverse));
        $metadata->merge($this->getCommonMetadata($reverse));
        
        return $metadata;
    }

    /**
     * Gets metadata based on "special" crosswalk settings.
     *
     * Use this method when crosswalking from a named schema such as Dublin Core to JSpace's
     * internal metadata schema.
     *
     * @return  JRegistry  A registry of metadata based on "special" crosswalk settings.
     */
    public function getSpecialMetadata($reverse = false)
    {        
        $metadata = JArrayHelper::getValue($this->registry, 'metadata', array());
        $schemas = JArrayHelper::getValue($metadata, 'special', array());
        
        $data = new JRegistry();
        
        foreach ($schemas as $key=>$schema)
        {
            $source = new JRegistry;
            $source->loadObject($this->source->get($key));

            $data->merge($this->_mapMetadata($source, $schema, $reverse));
        }
        
        return $data;
    }

    /**
     * Gets metadata based on "common" crosswalk settings.
     *
     * Use this method when crosswalking from internal metadata to an external metadata schema 
     * such as Dublin Core.
     *
     * @return  JRegistry  A registry of metadata based on "common" crosswalk settings.
     */
    public function getCommonMetadata($reverse = false)
    {
        $metadata = JArrayHelper::getValue($this->registry, 'metadata', array());
        $schema = JArrayHelper::getValue($metadata, 'common', array());
        
        return $this->_mapMetadata($this->source, $schema, $reverse);
    }
    
    /**
     * Gets tags based on specified metadata.
     *
     * @return  string[]  An array of tags based on specific metadata. 
     */
    public function getTags()
    {
        $tags = array();
        
        foreach (JArrayHelper::getValue($this->registry, 'tags', array()) as $tag)
        {
            $field = $this->source->get($tag);
   
            if (is_array($field))
            {
                $tags = array_merge($tags, $field);
            }
            else if (!is_null($field))
            {
                $tags[] = $field;
            }
            
        }
        
        return $tags;
    }
    
    /**
     * Gets a list of identifiers based on identifier settings.
     *
     * @return  string[]  A list of identifiers based on identifier settings.
     */
    public function getIdentifiers()
    {
        $identifiers = array();

        foreach (JArrayHelper::getValue($this->registry, 'identifiers', array()) as $key=>$config)
        {
            $field = $this->source->get($key);
            
            if (is_array($field))
            {
                $identifiers = array_merge($identifiers, $field);
            }
            else if (!is_null($field))
            {
                $identifiers[] = $field;
            }
            
            
            // clean up identifiers based on prefix settings.
            foreach (JArrayHelper::getValue($config, 'prefix', array()) as $prefix)
            {
                $found = false;
                
                while (current($identifiers) && !$found)
                {
                    if (JString::strpos(current($identifiers), $prefix) === 0)
                    {                    
                        $found = true;
                    }
                    
                    next($identifiers);
                }
                
                if (!$found)
                {
                    unset($identifiers[key($identifiers)]);
                }
                
                reset($identifiers);
            }
        }

        return $identifiers;
    }
    
    /**
     * Maps a schema's metadata.
     *
     * @param   JRegistry  $registry  A registry of metadata keys and values.
     * @param   array      $schema    The schema section of the crosswalk configuration.
     * @param   bool       $reverse   True to map targets to sources, false otherwise. Defaults to 
     * false.
     *
     * @return  JRegistry  A registry of mapped metadata keys and values.
     */
    private function _mapMetadata($registry, $schema, $reverse = false)
    {
        if ($reverse)
        {
            $skey = 'target';
            $tkey = 'source';
        }
        else
        {
            $skey = 'source';
            $tkey = 'target';
        }
        
        $metadata = new JRegistry();
        
        foreach (JArrayHelper::getValue($schema, 'map', array()) as $mappable)
        {
            // sometimes the key needs to be lower case to avoid poorly marked up HTML.
            $source = $registry->get($mappable[$skey], $registry->get(JString::strtolower($mappable[$skey])));

            if ($source)
            {
                $target = $metadata->get($mappable[$tkey]);

                if (!is_array($target))
                {
                    $target = array();
                }
                
                if (!is_array($source))
                {   
                    $array = array();
                    $array[] = $source;
                    $source = $array;
                }
                
                $metadata->set($mappable[$tkey], array_merge($target, $source));
            }
        }
        
        return $metadata;
    }
}