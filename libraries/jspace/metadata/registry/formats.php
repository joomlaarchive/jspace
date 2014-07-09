<?php
/**
 * @package     JSpace
 * @subpackage  Metadata
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

/**
 * Provides a way to enumerate over a list of metadata formats.
 *
 * @package     JSpace
 * @subpackage  Metadata
 */
class JSpaceMetadataRegistryFormats extends JObject
{
	const INI = 'ini';
	const JSON = 'json';
	const XML = 'xml';
	
	/**
	 * @var array A cache of all enum values to increase performance
	 */
	protected static $cache = array();

	/**
	 * Returns the names (or keys) of all formats.
	 *
	 * @return array
	 */
	public static function getKeys()
	{
		return array_keys(static::values());
	}

	/**
	 * Return the names and values of all formats.
	 *
	 * @return array
	 */
	public static function getValues()
	{
		$class = get_called_class();

		if (!isset(self::$cache[$class])) {
			$reflected = new ReflectionClass($class);
			self::$cache[$class] = $reflected->getConstants();
		}

		return self::$cache[$class];
	}
}