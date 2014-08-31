<?php
/**
 * @package     JSpace
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('jspace.table.observer.recordhistory');

/**
 * Represents a JSpace record.
 *
 * @package     JSpace
 * @subpackage  Table
 */
class JSpaceTableRecord extends JTableNested
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

		$observerParams = array('typeAlias'=>'com_jspace.record');
        JTableObserverTags::createObserver($this, $observerParams);
        JTableObserverRecordhistory::createObserver($this, $observerParams);
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
		
		$this->path = $this->alias;
		
		$this->version++;

		$result = parent::store($updateNulls);
		
		return $result;
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