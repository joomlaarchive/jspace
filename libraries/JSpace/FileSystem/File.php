<?php
/**
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
namespace JSpace\FileSystem;

\JLoader::import('joomla.filesystem.file');

use \JFile;

/**
 * Provides additional file manipulation.
 */
class File extends JFile
{
    /**
     * A sha1 constant.
     */
    const SHA1 = 'sha1';

    /**
     * An md5 constant.
     */
    const MD5 = 'md5';

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
		$params = \JComponentHelper::getParams('com_jspace', true);

		if (!$params)
		{
			throw new \Exception("LIB_JSPACE_COM_JSPACE_NOT_FOUND");
		}

		if (!$params->get('local_tika_app_path', null))
		{
			throw new \Exception("LIB_JSPACE_TIKA_NOT_FOUND");
		}

		ob_start();
		passthru("java -jar ".$params->get('local_tika_app_path')." -j \"".$file."\" 2> /dev/null");
		$result = ob_get_contents();
		ob_end_clean();

		$metadata = new \Joomla\Registry\Registry();

		$metadata->loadString($result);

		return $metadata;
	}

	/**
	 * Gets a file's hash value.
	 *
	 * @param   string  $file  A file path.
	 * @param   int     $hash  The hash type to use. Defaults to SHA1.
	 *
	 * @return  string  A file's hash value.
	 */
	public static function getHash($file, $hash = self::SHA1)
	{
        switch ($hash)
        {
            case self::MD5:
                return md5_file($file);
                break;

            default:
                return sha1_file($file);
                break;
        }
	}
}