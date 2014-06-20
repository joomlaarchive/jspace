<?php
defined('_JEXEC') or die;

class JSpaceMetadataCrosswalk extends JObject
{
	protected $source;
	
	protected $metadata;

	/**
	 * The crosswalk registry.
	 * @var JRegistry
	 */
	protected $crosswalk;
	
	/**
	 * Instantiates an instance of the file metadata crosswalk based on a crosswalk file. 
	 * 
	 * @param string $source The source metadata to crosswalk.
	 * @param string $crosswalk The path to the crosswalk file.
	 */
	public function __construct($source, $crosswalk)
	{
		parent::__construct();
		
		$this->source = $source;

		$this->crosswalk = new JRegistry();
		$this->crosswalk->loadFile($crosswalk, JFile::getExt($crosswalk));
	}
	
	/**
	 * Gets a list of crosswalked values.
	 */
	public function walk($reverse = false)
	{
		foreach ($this->source->toArray() as $skey=>$svalue)
		{
			$found = false;
			
			$items = $this->crosswalk->toArray();
			
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
					
					if (trim($svalue))
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