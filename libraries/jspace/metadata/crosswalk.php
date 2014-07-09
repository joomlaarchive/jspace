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
	 * Gets a list of crosswalked values.
	 *
	 * @param   bool   $reverse  True if the crosswalk should be reversed, false otherwise. Defaults to false.
	 *
	 * @return  array  An array of metadata values.
	 */
	public function walk($reverse = false)
	{
		foreach ($this->source->toArray() as $skey=>$svalue)
		{
			$found = false;
			
			$items = $this->crosswalk->get('crosswalk')->toArray();
			
			while (($citem = current($items)) != null && !$found)
			{
				$citems = explode(',', $citem);
				
				if (array_search($skey, $citems) !== false)
				{
					if ($reverse)
					{
						$key = JArrayHelper::getValue($citems, 0);
					}
					else
					{
						$key = key($items);
					}
					
					if ($svalue)
					{
						$this->metadata[$key] = $svalue;
					}
					
					$found = true;
				}
				
				next($items);
			}
		}
		
		return $this->metadata;
	}
}