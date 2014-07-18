<?php
defined('_JEXEC') or die;

abstract class JSpaceHtmlAssets
{
	public static function getCollection()
	{
		$post = JFactory::getApplication()->input->post->get('jform', array(), 'array');
		$collection = JArrayHelper::getValue($post, 'collection', array(), 'array');
		
		$assets = JFactory::getApplication()->input->files->get('jform', array(), 'array');
		
		$assets = JArrayHelper::getValue($assets, 'collection', array(), 'array');
		
		$assets = self::clean($assets);
		
		return $assets;
	}

	/**
	 * Cleans an array of assets, removing empty assets and converting single assets into multi-file arrays.
	 *
	 * @param  array  $collection An array of assets to clean.
	 *
	 * @return  array  The cleaned array of assets.
	 */
	public static function clean($collection)
	{
		$cleaned = $collection;
	
		foreach ($cleaned as $bkey=>$bundle)
		{
			$assets = JArrayHelper::getValue($bundle, 'assets', array(), 'array');
		
			foreach ($assets as $dkey=>$derivative)
			{
				if (array_key_exists('tmp_name', $derivative))
				{
					$tmp = $derivative;
					$derivative = array();
					$derivative[] = $tmp;
 					$cleaned[$bkey]['assets'][$dkey] = $derivative;
				}
				
				// Strip empty uploads.
				foreach ($derivative as $akey=>$asset)
				{
					if (JArrayHelper::getValue($asset, 'error') == 4)
					{
						unset($cleaned[$bkey]['assets'][$dkey][$akey]);
					}
				}
				
				if (count($cleaned[$bkey]['assets'][$dkey]) == 0)
				{
					unset($cleaned[$bkey]['assets'][$dkey]);
				}
			}
			
			if (count($cleaned[$bkey]['assets']) == 0)
			{
				unset($cleaned[$bkey]);
			}
		}
		
		return $cleaned;
	}
}