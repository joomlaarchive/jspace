<?php
/**
 * @package     JSpace
 * @subpackage  Archive
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

abstract class JSpaceSchemaHelper extends JObject
{
	public static function getPath($schema)
	{
		return JPATH_ROOT."/administrator/components/com_jspace/models/forms/".$schema.".xml";
	}
}