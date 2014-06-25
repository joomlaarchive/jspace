<?php
defined('_JEXEC') or die;

/**
 * @package     JSpace
 * @subpackage  Archive
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
class JSpaceArchiveAssetHelper extends JObject
{
	/**
	 * Builds a hash-based storage path based on the specified id.
	 * 
	 * @param   int     $id  The asset id to base the path on.
	 * @param   string  $root  An optional root directory. The JPATH_ROOT constant is also suported.
	 *
	 * @return  string  An hash-based storage path based on the specified id.
	 */
	public static function buildStoragePath($id, $root = null)
	{
		$hashcode = self::getHashCode((string)$id);
		
		$mask = 255;
		
		$parts = array();
		$parts[] = str_pad(($hashcode & $mask), 3, '0', STR_PAD_LEFT);
		$parts[] = str_pad((($hashcode >> 8) & $mask), 3, '0', STR_PAD_LEFT);
		$parts[] = str_pad((($hashcode >> 16) & $mask), 3, '0', STR_PAD_LEFT);
		
		return self::preparePath($root).implode("/", $parts)."/";
	}
	
	/**
	 * Prepare's the given path.
	 * 
	 * The path constant JPATH_ROOT can be passed within the $path parameter and will be converted to the
	 * corresponding value. It also appends a "/" on the end of the path parameter.
	 *
	 * The path does not have to exist on the local file system.
	 *
	 * @param   string  $path  $the path to prepare.
	 *
	 * @return  string  The prepared path.
	 */
	public function preparePath($path)
	{
		if (strpos($path, 'JPATH_ROOT') === 0)
		{
			$path = str_replace('JPATH_ROOT', JPATH_ROOT, $path);
		}
		
		if (strpos(strrev($path), '/') !== 0)
		{
			$path .= '/';
		}
        
        return $path;
	}
	
	public static function getHashCode($s)
	{
		$h = 0;
		$len = strlen($s);
		for($i = 0; $i < $len; $i++)
		{
			$h = (int)(31 * $h + ord($s[$i])) & 0xffffffff;
		}

		return $h;
	}
}
?>