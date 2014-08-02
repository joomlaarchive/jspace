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
 * Represents an alternative record identifier.
 *
 * @package     JSpace
 * @subpackage  Table
 */
class JSpaceTableRecordIdentifier extends JTable
{
    /**
     * Constructor
     *
     * @param  JDatabaseDriver  $db  Database connector object.
     */
    public function __construct(&$db)
    {
        parent::__construct('#__jspace_record_identifiers', 'id', $db);
        $this->_autoincrement = false;
    }
}