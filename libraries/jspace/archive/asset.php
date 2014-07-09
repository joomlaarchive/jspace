<?php
defined('_JEXEC') or die;

jimport('jspace.table.asset');

/**
 * @package     JSpace
 * @subpackage  Archive
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
class JSpaceAsset extends JObject
{
	protected static $instances = array();
	
	public function __construct($identifier = 0)
	{
		$this->_metadata = new JRegistry;
		
		// if identifier is empty, try and load from bound id (for methods such as listObjectList).
		if (!empty($identifier))
		{
			$this->id = $identifier;
		}
		
		if (isset($this->id) && $this->id)
		{
			$this->load($this->id);
		}
		else
		{
			$this->id = 0;
		}
	}

	public static function getInstance($identifier = 0)
	{
		if (!is_numeric($identifier))
		{
			JLog::add(JText::sprintf('JLIB_USER_ERROR_ID_NOT_EXISTS', $identifier), JLog::WARNING, 'jerror');

			return false;
		}
		else
		{
			$id = $identifier;
		}

		if ($id === 0)
		{
			return new JSpaceAsset;
		}

		if (empty(self::$instances[$id]))
		{
			$asset = new JSpaceAsset($id);
			self::$instances[$id] = $asset;
		}

		return self::$instances[$id];
	}
	
	public function bind(&$array)
	{
		if (array_key_exists('metadata', $array))
		{
			$this->_metadata->loadArray($array['metadata']);

			if (is_array($array['metadata']))
			{
				$metadata = (string)$this->_metadata;
			}
			else
			{
				$metadata = $array['metadata'];
			}

			$this->metadata = $metadata;
		}
		
		// Bind the array
		if (!$this->setProperties($array))
		{
			throw new Exception('Data to be bound is neither an array nor an object');
			return false;
		}
		
		$this->id = (int)$this->id;

		return true;
	}
	
	public function save($updateOnly = false)
	{
		$dispatcher = JEventDispatcher::getInstance();	
		JPluginHelper::importPlugin('jspace');
		
		$isNew = empty($this->id);
		
		$dispatcher->trigger('onJSpaceAssetBeforeSave', array($this));
		
		$this->metadata = (string)$this->_metadata;
		
		$table = JTable::getInstance('Asset', 'JSpaceTable');
		$table->bind($this->getProperties());
		$table->store();
		
		if (empty($this->id))
		{
			$this->id = $table->get('id');
		}
		
		$dispatcher->trigger('onJSpaceAssetAfterSave', array($this));
	}
	
	public function load($keys)
	{
		$table = $this->getTable();
		
		if (!$table->load($keys))
		{
			return false;
		}
		
		$this->_metadata->loadString($table->metadata);
		
		$this->setProperties($table->getProperties());

		return true;
	}
	
	/**
	 * Deletes an asset.
	 *
	 * @throws  Exception  If the asset cannot be deleted from the database.
	 */
	public function delete()
	{
		JPluginHelper::importPlugin('jspace');
		
		// Trigger the onUserBeforeDelete event
		$dispatcher = JEventDispatcher::getInstance();
		$dispatcher->trigger('onJSpaceAssetBeforeDelete', array($this));

		// Create the user table object
		$table = $this->getTable();

		if (!$result = $table->delete($this->id))
		{
			throw new Exception($table->getError());
		}

		// Trigger the onUserAfterDelete event
		$dispatcher->trigger('onJSpaceAssetAfterDelete', array($this));
	}
	
	/**
	 * Gets the asset's metadata registry.
	 * 
	 * @return  JRegistry  The asset's metadata registry.
	 */
	public function getMetadata()
	{
		return $this->_metadata;
	}
}