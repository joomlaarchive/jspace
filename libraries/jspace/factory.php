<?php
/**
 * A JSpace factory class.
 *
 * @package     JSpace
 * @copyright   Copyright (C) 2012-2014 KnowledgeARC. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE

 * Contributors
 * Please feel free to add your name and email (optional) here if you have
 * contributed any source code changes.
 * Name                         Email
 * Hayden Young                 <haydenyoung@wijiti.com>
 *
 */
defined('JPATH_PLATFORM') or die;

jimport('joomla.filesystem.folder');

jimport('jspace.archive.schema');
JLoader::import('jspace.oai.request');

/**
 * Provides a factory class for initializing various data in JSpace.
 *
 * @package     JSpace
 */
class JSpaceFactory
{
	const JSPACE_NAME = 'com_jspace';

	/**
	 *
	 * @return JRegistry
	 */
	public static function getConfig()
	{
		$config = new JRegistry();
		$component = JComponentHelper::getComponent(self::JSPACE_NAME);

		if ($component->enabled) {
			$config = $component->params;
		}
		return $config;
	}

    /**
     * Gets an instance of the JSpaceMetadataCrosswalk class.
     *
     * @param   JRegistry               $metadata
     * @param   array                   $config
     * @return  JSpaceMetadataCrosswalk An instance of JSpaceMetadataCrosswalk class.
     */
    public static function getCrosswalk($metadata, $config = array())
    {
        jimport('jspace.metadata.crosswalk');

        if ($registry = JArrayHelper::getValue($config, 'name'))
        {
            return new JSpaceMetadataCrosswalk($metadata, $registry);
        }
        else
        {
            return new JSpaceMetadataCrosswalk($metadata);
        }
    }

	/**
	 * Gets a list of available JSpace schemas.
	 *
	 * @return  JSpaceSchema[]  An array of JSpaceSchema objects.
	 */
	public static function getSchemas()
    {
        $schemas = array();

        $formPath = JPATH_ROOT.'/administrator/components/com_jspace/models/forms/schemas';

        foreach (JFolder::files($formPath, '..*\.xml', false, true) as $file)
        {
            $xml = simplexml_load_file($file);

            $schema = new JSpaceSchema();
            $schema->name = JArrayHelper::getValue($xml, 'name', null, 'string');

            if (!$schema->name)
            {
                throw new Exception('COM_JSPACE_RECORDSCHEMA_NO_NAME_ATTRIBUTE');
            }

            $schema->label = JArrayHelper::getValue($xml, 'label', null, 'string');
            $schema->description = JArrayHelper::getValue($xml, 'description', null, 'string');

            $schemas[] = $schema;
        }

        return $schemas;
	}

    public static function getOAIRequest(JInput $input)
    {
        return new JSpaceOAIRequest($input->getArray());
    }
}