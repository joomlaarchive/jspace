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
        parent::__construct('#__jspace_harvests', 'id', $db);
    }
    
    /**
     * Stores a record.
     *
     * @param   boolean  True to update fields even if they are null.
     *
     * @return  boolean  True on success, false on failure.
     *
     * @since   1.6
     */
    public function store($updateNulls = false)
    {
        $date   = JFactory::getDate();
        $user   = JFactory::getUser();

        if ($this->id)
        {
            // Existing item
            $this->modified     = $date->toSql();
            $this->modified_by  = $user->get('id');
        }
        else
        {
            if (!(int) $this->created)
            {
                $this->created = $date->toSql();
            }
            if (empty($this->created_by))
            {
                $this->created_by = $user->get('id');
            }
        }

        $result = parent::store($updateNulls);
        
        return $result;
    }
    
    /**
     * Method to set the publishing state for a row or list of rows in the database
     * table.  The method respects checked out rows by other users and will attempt
     * to checkin rows that it can after adjustments are made.
     *
     * @param   mixed    $pks     An optional array of primary key values to update.
     *                            If not set the instance property value is used.
     * @param   integer  $state   The publishing state. eg. [0 = unpublished, 1 = published]
     * @param   integer  $userId  The user id of the user performing the operation.
     *
     * @return  boolean  True on success; false if $pks is empty.
     *
     * @link    http://docs.joomla.org/JTable/publish
     * @since   11.1
     */
    public function publish($pks = null, $state = 1, $userId = 0)
    {
        $k = $this->_tbl_keys;

        if (!is_null($pks))
        {
            foreach ($pks AS $key => $pk)
            {
                if (!is_array($pk))
                {
                    $pks[$key] = array($this->_tbl_key => $pk);
                }
            }
        }

        $userId = (int) $userId;
        $state  = (int) $state;

        // If there are no primary keys set check to see if the instance key is set.
        if (empty($pks))
        {
            $pk = array();

            foreach ($this->_tbl_keys AS $key)
            {
                if ($this->$key)
                {
                    $pk[$this->$key] = $this->$key;
                }
                // We don't have a full primary key - return false
                else
                {
                    return false;
                }
            }

            $pks = array($pk);
        }

        foreach ($pks AS $pk)
        {
            // Update the publishing state for rows with the given primary keys.
            $query = $this->_db->getQuery(true)
                ->update($this->_tbl)
                ->set('state = ' . (int) $state);

            // Determine if there is checkin support for the table.
            if (property_exists($this, 'checked_out') || property_exists($this, 'checked_out_time'))
            {
                $query->where('(checked_out = 0 OR checked_out = ' . (int) $userId . ')');
                $checkin = true;
            }
            else
            {
                $checkin = false;
            }

            // Build the WHERE clause for the primary keys.
            $this->appendPrimaryKeys($query, $pk);

            $this->_db->setQuery($query);
            $this->_db->execute();

            // If checkin is supported and all rows were adjusted, check them in.
            if ($checkin && (count($pks) == $this->_db->getAffectedRows()))
            {
                $this->checkin($pk);
            }

            $ours = true;

            foreach ($this->_tbl_keys AS $key)
            {
                if ($this->$key != $pk[$key])
                {
                    $ours = false;
                }
            }

            if ($ours)
            {
                $this->state = $state;
            }
        }

        $this->setError('');

        return true;
    }
}