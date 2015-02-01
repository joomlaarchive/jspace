<?php
/**
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
namespace JSpace\Html;

use \JFactory;
use \JArrayHelper;

/**
 * Provides helpers for manipulating HTML data.
 */
abstract class Assets
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

        foreach ($cleaned as $dkey=>$derivative)
        {
            if (array_key_exists('tmp_name', $derivative))
            {
                $tmp = $derivative;
                $derivative = array();
                $derivative[] = $tmp;
                $cleaned[$dkey] = $derivative;
            }

            // Strip empty uploads.
            foreach ($derivative as $akey=>$asset)
            {
                if (JArrayHelper::getValue($asset, 'error') == 4)
                {
                    unset($cleaned[$dkey][$akey]);
                }
            }

            // strip empty derivatives.
            if (count($cleaned[$dkey]) == 0)
            {
                unset($cleaned[$dkey]);
            }
        }

        return $cleaned;
    }
}