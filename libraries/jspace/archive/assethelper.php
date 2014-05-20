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
		$file = JArrayHelper::getValue($asset, 'tmp_name');
		
		$metadata = JSpaceFactory::getCrosswalk(
			JSpaceFile::getMetadata($file), array('name'=>'datastream'))->walk();
		
		$metadata = new JRegistry($metadata);
		
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
		print_r($metadata);
			$fileInfo[$extractionMap][] = $metadata->toString('ini');
		}
		
		return $fileInfo;
	}
}