<?php
/**
 * @package     JSpace
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2014-2015 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Represents a reference asset.
 *
 * @package     JSpace
 * @subpackage  Table
 */
class JSpaceTableReference extends JTable
{
	/**
	 * Constructor
	 *
	 * @param  JDatabaseDriver  $db  Database connector object.
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__jspace_references', 'id', $db);
		$this->_autoincrement = false;
	}
}