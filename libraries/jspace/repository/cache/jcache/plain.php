<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Cache
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Joomla Cache output type object
 *
 * @package     Joomla.Platform
 * @subpackage  Cache
 * @since       11.1
 */
class JCacheControllerPlain extends JCacheController
{
	/**
	 * Start the cache
	 *
	 * @param   string  $id     The cache data id
	 * @param   string  $group  The cache data group
	 *
	 * @return  boolean  True if the cache is hit (false else)
	 *
	 * @since   11.1
	 */
	public function get($id, $group = null)
	{
		
		// If we have data in cache use that.
		return $this->cache->get($id, $group);
	}

	/**
	 * Stop the cache buffer and store the cached data
	 *
	 * @return  boolean  True if cache stored
	 *
	 * @since   11.1
	 */
	public function set($id, $value, $group=null)
	{

		// Get the storage handler and store the cached data
		$ret = $this->cache->store($value, $id, $group);

		return $ret;
	}
}
