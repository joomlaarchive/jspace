<?php
class JSpaceTableHarvest extends JTable
{
    /**
     * Instantiates an instance of the JSpaceTableHarvest table.
     *
     * @param  JDatabaseDriver  $db  Database connector object.
     */
    public function __construct(&$db)
    {
        parent::__construct('#__jspace_harvests', 'catid', $db);
        $this->_autoincrement = false;
    }
}