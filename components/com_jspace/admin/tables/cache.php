<?php
class JSpaceTableCache extends JTable
{
	public $metadata;
	
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
	
	public function store($updateNulls = false)
	{
		if ($this->metadata instanceof JRegistry)
		{
			$this->metadata = (string)$this->metadata;
		}
		
		return parent::store($updateNulls);
	}
}