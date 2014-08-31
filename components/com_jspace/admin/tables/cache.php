<?php
/**
 * @package     JSpace
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;
 
/**
 * Represents a JSpace cache item.
 *
 * @package     JSpace
 * @subpackage  Table
 */
class JSpaceTableCache extends JTable
{
	/**
	 * Instantiates an instance of the JSpaceTableCache table.
	 *
	 * @param  JDatabaseDriver  $db  Database connector object.
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__jspace_cache', 'id', $db);
		$this->_autoincrement = false;
	}
}