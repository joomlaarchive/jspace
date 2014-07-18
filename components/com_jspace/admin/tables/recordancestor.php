<?php
class JSpaceTableRecordAncestor extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  Database connector object
	 *
	 * @since   1.0
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__jspace_record_ancestors', array('decendant', 'ancestor'), $db);
	}
	
	/**
	 * Delete all records which match the decendant.
	 * 
	 * @param mixed $id The id of the decendant or null if the instance property value should be used.
	 * 
	 * @return bool True on success.
	 * 
	 * @throws Exception
	 */
	public function deleteByDecendant($id = null)
	{
		if (is_null($id))
		{
			$id = $this->decendant;
		}
		
		if (!$id)
		{
			throw new Exception('Cannot delete empty decendant.');
		}
		
		// Delete the row by primary key.
		$query = $this->_db->getQuery(true)
		->delete($this->_tbl)
		->where('`decendant` = '.$id);
		
		$this->_db->setQuery($query);
		
		// Check for a database error.
		$this->_db->execute();
		
		return true;
	}

	public function delete($pk = null)
	{
		if (is_null($pk))
		{
			$pk = array();

			foreach ($this->_tbl_keys AS $key)
			{
				$pk[$key] = $this->$key;
			}
		}
		elseif (!is_array($pk))
		{
			$pk = array($this->_tbl_key => $pk);
		}
		else
		{
			if (count($pk) != 1)
			{
				foreach ($this->_tbl_keys AS $key)
				{
					$pk[$key] = is_null($pk[$key]) ? $this->$key : $pk[$key];
		
					if ($pk[$key] === null)
					{
						throw new UnexpectedValueException('Null primary key not allowed.');
					}
					$this->$key = $pk[$key];
				}
			}
		}

		// Implement JObservableInterface: Pre-processing by observers
		$this->_observers->update('onBeforeDelete', array($pk));
		
		// Delete the row by primary key.
		$query = $this->_db->getQuery(true)
			->delete($this->_tbl);
	
		if (count($pk) == 1)
		{
			$key = JArrayHelper::getValue(array_keys($pk), 0);
					
			if (array_search($key, $this->_tbl_keys) === false)
			{
				throw new InvalidArgumentException($key." does not exist in the table's key list");
			}
			
			$query->where($key.'='.JArrayHelper::getValue($pk, $key));
		}
		else
		{
			$this->appendPrimaryKeys($query, $pk);
		}

		$this->_db->setQuery($query);

		$this->_db->execute();

		// Implement JObservableInterface: Post-processing by observers
		$this->_observers->update('onAfterDelete', array($pk));

		return true;
	}

	/**
	 * Get all ancestors for the parent.
	 *
	 * @param mixed $id The id of the parent.
	 *
	 * @return mixed An array of node objects on success or null on failure.
	 *
	 * @throws Exception
	 */
	public function getAncestors($parent)
	{
		$query = $this->_db->getQuery(true)
		->select('ancestor')
		->from($this->_tbl)
		->where('decendant = '.(int)$parent);
		
		$ancestors = $this->_db->setQuery($query)->loadColumn();
		
		return $ancestors;
	}
	
	public function storeAncestors($decendant, $ancestors)
	{
		$query = $this->_db->getQuery(true)
		->insert($this->_tbl);
		
		// add decendant as ancestor.
		$ancestors[] = $decendant;
		
		foreach ($ancestors as $ancestor)
		{
			$query->values($decendant.','.$ancestor);
		}
		
		$this->_db->setQuery($query);

		$this->_db->execute();
		
		return true;
	}
}