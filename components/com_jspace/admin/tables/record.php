<?php
/**
 * @package     JSpace
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;
 
/**
 * Represents a JSpace record.
 *
 * @package     JSpace
 * @subpackage  Table
 */
class JSpaceTableRecord extends JTable
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
		parent::__construct('#__jspace_records', 'id', $db);
		
		JObserverMapper::addObserverClassToClass(
			'JTableObserverContenthistory', 
			get_class($this), 
			array('typeAlias'=>'com_jspace.record'));
	}
	
	/**
	 * (non-PHPdoc)
	 * @see JTable::bind()
	 */
	public function bind($array, $ignore = '')
	{
		// set the metadata as a json string.
		if (isset($array['metadata']) && is_array($array['metadata']))
		{
			$registry = new JRegistry;
			$registry->loadArray($array['metadata']);
			$array['metadata'] = (string) $registry;
		}

		return parent::bind($array, $ignore);
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
		$date	= JFactory::getDate();
		$user	= JFactory::getUser();

		if ($this->id)
		{
			// Existing item
			$this->modified		= $date->toSql();
			$this->modified_by	= $user->get('id');
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

		// Set publish_up to null date if not set
		if (!$this->publish_up)
		{
			$this->publish_up = $this->_db->getNullDate();
		}

		// Set publish_down to null date if not set
		if (!$this->publish_down)
		{
			$this->publish_down = $this->_db->getNullDate();
		}
		
		if (trim($this->alias) == '')
		{
			$this->alias = $this->title;
		}
	
		$this->alias = JApplicationHelper::stringURLSafe($this->alias);
	
		if (trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = JFactory::getDate()->format('Y-m-d-H-i-s');
		}
		
		$this->version++;

		$result = parent::store($updateNulls);
		
		// Add the ancestry if it exists.
		if ($result) 
		{
			if ($this->id > 0)
			{
				// TODO: At some stage we should check whether the parent has changed to save this db operation 
				// from ocurring every time.
				$table = JTable::getInstance('RecordAncestor', 'JSpaceTable');
				$table->deleteByDecendant($this->id);
				
				$ancestors = $table->getAncestors($this->parent_id);

				// just ignore if this table update fails.
				$table->storeAncestors($this->id, $ancestors);
			}
		}
		
		return $result;
	}
	
	/**
	 * Method to set the publishing state for a node or list of nodes in the database
	 * table.  The method respects rows checked out by other users and will attempt
	 * to checkin rows that it can after adjustments are made. The method will not
	 * allow you to set a publishing state higher than any ancestor node and will
	 * not allow you to set a publishing state on a node with a checked out child.
	 *
	 * @param   mixed    $pks     An optional array of primary key values to update.  If not
	 *                            set the instance property value is used.
	 * @param   integer  $state   The publishing state. eg. [0 = unpublished, 1 = published]
	 * @param   integer  $userId  The user id of the user performing the operation.
	 *
	 * @return  boolean  True on success.
	 *
	 * @link    http://docs.joomla.org/JTableNested/publish
	 * @since   11.1
	 * @throws UnexpectedValueException
	 */
	public function publish($pks = null, $state = 1, $userId = 0)
	{
		$k = $this->_tbl_key;
		$query = $this->_db->getQuery(true);
	
		// Sanitize input.
		JArrayHelper::toInteger($pks);
		$userId = (int) $userId;
		$state = (int) $state;
	
		// If $state > 1, then we allow state changes even if an ancestor has lower state
		// (for example, can change a child state to Archived (2) if an ancestor is Published (1)
		$compareState = ($state > 1) ? 1 : $state;

		// If there are no primary keys set check to see if the instance key is set.
		if (empty($pks))
		{
			if ($this->$k)
			{
				$pks = explode(',', $this->$k);
			}
			// Nothing to set publishing state on, return false.
			else
			{
				throw new UnexpectedValueException(sprintf('%s::publish(%s, %d, %d) empty.', get_class($this), $pks, 
$state, $userId));

				return false;
			}
		}
	
		// Determine if there is checkout support for the table.
		$checkoutSupport = (property_exists($this, 'checked_out') || property_exists($this, 'checked_out_time'));

		// Iterate over the primary keys to execute the publish action if possible.
		foreach ($pks as $pk)
		{
			// Get the node by primary key.
			if (!$node = $this->_getNode($pk))
			{
				// Error message set in getNode method.
				return false;
			}
	
			// If the table has checkout support, verify no children are checked out.
			if ($checkoutSupport)
			{
				// Ensure that children are not checked out.
				$query->clear()
				->select('COUNT(' . $k . ')')
				->from($this->_tbl.' AS r')
				->join('INNER', ' #__jspace_record_ancestors AS ra ON (r.id = ra.ancestor)')
				->where('ra.ancestor = '.$node->id.' AND NOT ra.decendant = '.$node->id)
				->where('(checked_out <> 0 AND checked_out <> ' . (int) $userId . ')');
				$this->_db->setQuery($query);

				// Check for checked out children.
				if ($this->_db->loadResult())
				{
					// TODO Convert to a conflict exception when available.
					$e = new RuntimeException(sprintf('%s::publish(%s, %d, %d) checked-out conflict.', 
get_class($this), $pks, $state, $userId));
	
					$this->setError($e);
	
					return false;
				}
			}
	
			// If any parent nodes have lower published state values, we cannot continue.
			if ($node->parent_id)
			{
				// Get any ancestor nodes that have a lower publishing state.
				$query->clear()
				->select('r.'.$k)
				->from($this->_tbl.' AS r')
				->join('INNER', ' #__jspace_record_ancestors AS ra ON (r.id = ra.ancestor)')
				->where('ra.decendant = '.$node->id.' AND NOT ra.ancestor = '.$node->id)
				->where('r.published < ' . (int) $compareState);

				// Just fetch one row (one is one too many).
				$this->_db->setQuery($query, 0, 1);
	
				$rows = $this->_db->loadColumn();

				if (!empty($rows))
				{
					throw new UnexpectedValueException(
							sprintf('%s::publish(%s, %d, %d) ancestors have lower state.', get_class($this), $pks, 
$state, $userId)
					);
	
					return false;
				}
			}

			// Update and cascade the publishing state.
			$query->clear()
			->update($this->_db->qn($this->_tbl, 'r'))
			->join('INNER',
				$this->_db->qn('#__jspace_record_ancestors', 'ra').
				' ON ('.$this->_db->qn('r.id').'='.$this->_db->qn('ra.decendant').')')
			->set($this->_db->qn('r.published').' = '.(int) $state)
			->where($this->_db->qn('ra.ancestor').'='.(int)$pk);

			$this->_db->setQuery($query)->execute();
	
			// If checkout support exists for the object, check the row in.
			if ($checkoutSupport)
			{
				$this->checkin($pk);
			}
		}
	
		// If the JTable instance value is in the list of primary keys that were set, set the instance.
		if (in_array($this->$k, $pks))
		{
			$this->published = $state;
		}
	
		$this->setError('');
	
		return true;
	}
	
	/**
	 * Method to delete a node and, optionally, its child nodes from the table.
	 *
	 * @param   integer  $pk        The primary key of the node to delete.
	 * @param   boolean  $children  True to delete child nodes, false to move them up a level.
	 *
	 * @return  boolean  True on success.
	 */
	public function delete($pk = null, $children = true)
	{
		$k = $this->_tbl_key;
		$pk = (is_null($pk)) ? $this->$k : $pk;
	
		// Implement JObservableInterface: Pre-processing by observers
		$this->_observers->update('onBeforeDelete', array($pk));

		// If tracking assets, remove the asset first.
		if ($this->_trackAssets)
		{
			$name = $this->_getAssetName();
			$asset = JTable::getInstance('Asset');
	
			// Lock the table for writing.
			if (!$asset->_lock())
			{
				// Error message set in lock method.
				return false;
			}
	
			if ($asset->loadByName($name))
			{
				// Delete the node in assets table.
				if (!$asset->delete(null, $children))
				{
					$this->setError($asset->getError());
					$asset->_unlock();
	
					return false;
				}
				$asset->_unlock();
			}
			else
			{
				$this->setError($asset->getError());
				$asset->_unlock();
	
				return false;
			}
		}

		// Get the node by id.
		$node = $this->_getNode($pk);
	
		if (empty($node))
		{	
			return false;
		}
	
		$query = $this->_db->getQuery(true);

		// Should we delete all children along with the node?
		if ($children)
		{
			// Delete the node and all of its children.
			$query = 
			"DELETE r FROM ".$this->_db->qn($this->_tbl, 'r')." ".
			"INNER JOIN ". 
				$this->_db->qn('#__jspace_record_ancestors', 'ra')." ".
				"ON (r.".$this->_db->qn('id')."=ra.".$this->_db->qn('decendant').") ".
			"WHERE ra.ancestor = ".$node->id.";";
			
			$this->_db->setQuery($query);
			
			$this->_db->execute();
			
			// TODO: Not the most economical method for deleting (or safest) but ensures all the extra joomla
			// functions are executed for each table object (E.g. fire all correct events).
			$ancestor = JTable::getInstance('RecordAncestor', 'JSpaceTable');
			$ancestor->delete(array('ancestor'=>$node->id));
			
			$recordCategory = JTable::getInstance('RecordCategory', 'JSpaceTable');
			$recordCategory->delete(array("record_id"=>$node->id));
		}
		// Leave the children and move them up a level.
		else
		{
			// Delete the node.
			$query->clear()
			->delete($this->_tbl)
			->where('lft = ' . (int) $node->lft);
			$this->_runQuery($query, 'JLIB_DATABASE_ERROR_DELETE_FAILED');
	
			// Shift all node's children up a level.
			$query->clear()
			->update($this->_tbl)
			->set('lft = lft - 1')
			->set('rgt = rgt - 1')
			->set('level = level - 1')
			->where('lft BETWEEN ' . (int) $node->lft . ' AND ' . (int) $node->rgt);
			$this->_runQuery($query, 'JLIB_DATABASE_ERROR_DELETE_FAILED');
	
			// Adjust all the parent values for direct children of the deleted node.
			$query->clear()
			->update($this->_tbl)
			->set('parent_id = ' . (int) $node->parent_id)
			->where('parent_id = ' . (int) $node->$k);
			$this->_runQuery($query, 'JLIB_DATABASE_ERROR_DELETE_FAILED');
	
			// Shift all of the left values that are right of the node.
			$query->clear()
			->update($this->_tbl)
			->set('lft = lft - 2')
			->where('lft > ' . (int) $node->rgt);
			$this->_runQuery($query, 'JLIB_DATABASE_ERROR_DELETE_FAILED');
	
			// Shift all of the right values that are right of the node.
			$query->clear()
			->update($this->_tbl)
			->set('rgt = rgt - 2')
			->where('rgt > ' . (int) $node->rgt);
			$this->_runQuery($query, 'JLIB_DATABASE_ERROR_DELETE_FAILED');			
			
			// Unlock the table for writing.
			$this->_unlock();
		}
	
		// Implement JObservableInterface: Post-processing by observers
		$this->_observers->update('onAfterDelete', array($pk));
	
		return true;
	}
	
	protected function _getNode($id, $key = null)
	{
		// Determine which key to get the node base on.
		switch ($key)
		{
			case 'parent':
				$k = 'parent_id';
				break;
	
			default:
				$k = $this->_tbl_key;
				break;
		}
	
		// Get the node data.
		$query = $this->_db->getQuery(true)
		->select($this->_tbl_key . ', parent_id')
		->from($this->_tbl)
		->where($k . ' = ' . (int) $id);
		
		$row = $this->_db->setQuery($query, 0, 1)->loadObject();

		// Check for no $row returned
		if (empty($row))
		{
			throw new UnexpectedValueException(sprintf('%s::_getNode(%d, %s) failed.', get_class($this), $id, $key));
	
			return false;
		}
	
		return $row;
	}
	
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;
	
		return 'com_jspace.record.' . (int) $this->$k;
	}
	
	/**
	 * Method to return the title to use for the asset table.
	 *
	 * @return  string
	 *
	 * @since   11.1
	 */
	protected function _getAssetTitle()
	{
		return $this->title;
	}
}