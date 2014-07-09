<?php
/**
 * @package     JSpace
 * @subpackage  Metadata
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('jspace.metadata.registry.formats');

/**
 * Provides a way to access a INI-based metadata registry.
 *
 * @package     JSpace
 * @subpackage  Metadata
 */
class JSpaceMetadataRegistry extends JObject
{
	protected $crosswalk;
	
	protected $format;
	
	public function __construct($format)
	{
		$this->format = $format;
	
		$path = JPATH_ROOT.'/administrator/components/com_jspace/crosswalks/'.$this->format;
		
		$found = false;
		
		$formats = JSpaceMetadataRegistryFormats::getValues();
		
		while (($format = current($formats)) && !$found)
		{
			if ($found = JFile::exists($path.'.'.$format))
			{
				$path = $path.'.'.$format;
			}
			
			next($formats);
		}
		
		if (!$found)
		{
			throw new Exception('No crosswalk file could be found.');
		}
		
		$this->crosswalk = new JRegistry();
		$this->crosswalk->loadFile($path, JFile::getExt($path));
	}
	
	public function getKey($value)
	{
		return array_search($value, $this->crosswalk->toArray());
	}
	
	public function getValue($key)
	{
		$this->crosswalk->get($key);
	}
}