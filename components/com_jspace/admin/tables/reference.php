<?php
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