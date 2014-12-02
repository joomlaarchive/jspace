<?php
/**
 * @package     JSpace.Component
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Models a list of schemas.
 *
 * @package     JSpace.Component
 * @subpackage  Model
 */
class JSpaceModelRecordSchemas extends JModelLegacy
{
	public function __construct($config = array())
	{
		parent::__construct($config);
	}

	public function getItems()
	{
        return \JSpace\Factory::getSchemas();
	}
}