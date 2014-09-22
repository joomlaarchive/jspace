<?php
/**
 * @package     JSpace
 * @subpackage  FileSystem
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

jimport('joomla.filesystem.file');

/**
 * Provides additional file manipulation.
 *
 * @package     JSpace
 * @subpackage  FileSystem
 */
class JSpaceFile extends JFile
{
    /**
     * Gets a file's metadata.
     *
     * The getMetadata method uses the configured Apache Tika application to extract metadata.
     * 
     * @param   string     $file  A file path.
     * 
     * @return  JRegistry  The file's metadata.
     */
	public static function getMetadata($file)
	{
		$params = JComponentHelper::getParams('com_jspace', true);
		
		if (!$params)
		{
			throw new Exception("LIB_JSPACE_COM_JSPACE_NOT_FOUND");
		}
		
		if (!$params->get('local_tika_app_path', null))
		{
			throw new Exception("LIB_JSPACE_TIKA_NOT_FOUND");
		}

		ob_start();		
		passthru("java -jar ".$params->get('local_tika_app_path')." -j \"".$file."\" 2> /dev/null");
		$result = ob_get_contents();
		ob_end_clean();
		
		$metadata = new JRegistry();
		
		$metadata->loadString($result);

		return $metadata;
	}
}