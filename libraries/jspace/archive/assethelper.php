<?php
/**
 * @package     JSpace
 * @subpackage  Archive
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

jimport('jspace.factory');
jimport('jspace.filesystem.file');

abstract class JSpaceAssetHelper extends JObject
{
	public static function getMetadata($asset, $schema, $extractionMap)
	{
		if (!$extractionMap || $extractionMap == 'none')
		{
			return array();
		}
	
		$file = JArrayHelper::getValue($asset, 'tmp_name');
		
		$metadata = JSpaceFile::getMetadata($file);
		
		// set the file name to the original file name (it is using the upload name in the metadata).
		if ($fileName = JArrayHelper::getValue($asset, 'name'))
		{
			$metadata->set('resourceName', $fileName);
		}
		
		$metadata = JSpaceFactory::getCrosswalk($metadata, array('name'=>'datastream'))->walk();
		
		$metadata = new JRegistry($metadata);
		
		$fileInfo = array();
		
		if ($extractionMap == 'metadata')
		{
			$form = new JForm('jform');
			$form->loadFile(JSpaceSchemaHelper::getPath($schema));

			// try to match form fields to retrieved file metadata.
			foreach ($metadata->toArray() as $key=>$value)
			{
				if ($form->getField($key, 'metadata'))
				{
					$fileInfo[$key][] = $value;
				}
			}
		}
		else
		{
			$fileInfo[$extractionMap][] = $metadata->toString('ini');
		}
		
		return $fileInfo;
	}
}