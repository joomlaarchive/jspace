<?php
defined('_JEXEC') or die;

jimport('joomla.filesystem.file');

jimport('jspace.metadata.registry');

class JSpaceMetadataCrosswalk extends JObject
{
    protected $source;
    
    protected $metadata;

    /**
     * The metadata crosswalk.
     * @var JRegistry
     */
    protected $crosswalk;
    
    /**
     * Instantiates an instance of the file metadata crosswalk based on a registry file. 
     * 
     * @param  string  $source    The source metadata to crosswalk.
     * @param  string  $registry  The name of the registry file.
     */
    public function __construct($source, $registry)
    {
        parent::__construct();

        $this->source = $source;
        
        $this->crosswalk = new JSpaceMetadataRegistry($registry);
    }
    
    /**
     * Walks the source metadata, remapping each metadata value from its current metadata key to a
     * different metadata key name as defined in the crosswalk registry file.
     *
     * @param   bool   $reverse  True if the crosswalk should be reversed, false otherwise. 
     * Defaults to false.
     *
     * @return  array  An array of metadata values.
     */
    public function walk($reverse = false)
    {
        foreach ($this->source->toArray() as $skey=>$svalue)
        {
            $found = false;

            $pairs = $this->crosswalk->get('crosswalk')->toArray();
            
            while ((list($key, $value) = each($pairs)) != null && !$found)
            {            
                $values = explode(',', $value);
                
                if ($reverse)
                {
                    $keys = array($key);                    
                    // swap the key with the first value.
                    $key = current($values);
                }
                else 
                {
                    $keys = $values;
                }                
                
                if (array_search($skey, $keys) !== false)
                {
                    if ($svalue)
                    {
                        $this->metadata[$key] = $svalue;
                    }

                    $found = true;
                }
            }
        }
        
        return $this->metadata;
    }
}