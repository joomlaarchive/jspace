<?php
defined('_JEXEC') or die;

abstract class JSpaceHtmlAssets
{
	public function getCollection()
	{
		$post = JFactory::getApplication()->input->post->get('jform', array(), 'array');
		$collection = JArrayHelper::getValue($post, 'collection', array(), 'array');
		
		$assets = JFactory::getApplication()->input->files->get('jform', array(), 'array');
		
		$assets = JArrayHelper::getValue($assets, 'collection', array(), 'array');
		
		$assets = JSpaceHtmlAssets::clean($assets);
		
		return array_merge_recursive($collection, $assets);
	}

	/**
	 * Cleans an array of assets, removing empty assets and converting single assets into multi-file arrays.
	 *
	 * @param  array  $collection An array of assets to clean.
	 *
	 * @return  array  The cleaned array of assets.
	 */
	public function clean($collection)
	{
		$cleaned = $collection;
	
		foreach ($cleaned as $bkey=>$bundle)
		{			
			foreach ($bundle as $dkey=>$derivative)
			{
				$assets = JArrayHelper::getValue($derivative, 'assets', array(), 'array');
				
				if (array_key_exists('tmp_name', $assets))
				{
					$tmp = $assets;
					$assets = array();
					$assets[] = $tmp;
				}
				
				// Strip empty uploads.
				foreach ($assets as $akey=>$asset)
				{
					if (JArrayHelper::getValue($asset, 'error') == 4)
					{
						unset($assets[$akey]);
					}
				}
					
				$assets = array_values($assets);
				
				$cleaned[$bkey][$dkey]['assets'] = $assets;
			}
		}
		
		return $cleaned;
	}
}