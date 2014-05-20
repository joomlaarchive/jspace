<?php
class JSpaceTableRecordCategory extends JTable
{
	public function __construct(&$db)
	{
		parent::__construct('#__jspace_record_categories', array('catid', 'record_id'), $db);
	}
	
	public function load($keys = null, $reset = true)
	{
		// Implement JObservableInterface: Pre-processing by observers
		$this->_observers->update('onBeforeLoad', array($keys, $reset));

		if (empty($keys))
		{
			$empty = true;
			$keys  = array();
		
			// If empty, use the value of the current key
			foreach ($this->_tbl_keys as $key)
			{
				$empty      = $empty && empty($this->$key);
				$keys[$key] = $this->$key;
			}
		
			// If empty primary key there's is no need to load anything
			if ($empty)
			{
				return true;
			}
		}
		elseif (!is_array($keys))
		{
			// Load by primary key.
			$keyCount = count($this->_tbl_keys);
		
			if ($keyCount)
			{
				if ($keyCount > 1)
				{
					throw new InvalidArgumentException('Table has multiple primary keys specified, only one primary key value provided.');
				}
				$keys = array($this->getKeyName() => $keys);
			}
			else
			{
				throw new RuntimeException('No table keys defined.');
			}
		}

		if ($reset)
		{
			$this->reset();
		}
		
		// Initialise the query.
		$query = $this->_db->getQuery(true)
		->select('*')
		->from($this->_tbl);		

		if (count($keys) == 1)
		{
			$key = JArrayHelper::getValue(array_keys($keys), 0);
		
			if (array_search($key, $this->_tbl_keys) === false)
			{
				throw new InvalidArgumentException($key." does not exist in the table's key list");
			}
		
			$query->where($key.'='.JArrayHelper::getValue($keys, $key));
		}
		else
		{
			$fields = array_keys($this->getProperties());
			
			foreach ($keys as $field => $value)
			{
				// Check that $field is in the table.
				if (!in_array($field, $fields))
				{
					throw new UnexpectedValueException(sprintf('Missing field in database: %s &#160; %s.', get_class($this), $field));
				}
				// Add the search tuple to the query.
				$query->where($this->_db->quoteName($field) . ' = ' . $this->_db->quote($value));
			}
		}

		$this->_db->setQuery($query);
		
		$row = $this->_db->loadAssoc();
		
		// Check that we have a result.
		if (empty($row))
		{
			$result = false;
		}
		else
		{
			// Bind the object with the row and return.
			$result = $this->bind($row);
		}

		// Implement JObservableInterface: Post-processing by observers
		$this->_observers->update('onAfterLoad', array(&$result, $row));
		
		return $result;
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
}