<?php
/**
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
namespace JSpace\Archive;

\JLoader::import('jspace.archive.object');
\JLoader::import('jspace.table.asset');

/**
 * A single asset.
 */
class Asset extends Object
{
    protected static $context = 'com_jspace.asset';

	protected static $instances = array();

	public static function getInstance($identifier = 0)
	{
		if (!is_numeric($identifier))
		{
			\JLog::add(JText::sprintf('JLIB_USER_ERROR_ID_NOT_EXISTS', $identifier), JLog::WARNING, 'jerror');

			return false;
		}
		else
		{
			$id = $identifier;
		}

		if ($id === 0)
		{
			return new Asset;
		}

		if (empty(self::$instances[$id]))
		{
			$asset = new Asset($id);
			self::$instances[$id] = $asset;
		}

		return self::$instances[$id];
	}

	public function bind(&$array)
	{
        $this->metadata = \JArrayHelper::getValue($array, 'metadata', array());

		// Bind the array
		if (!$this->setProperties($array))
		{
			throw new \Exception('Data to be bound is neither an array nor an object');
			return false;
		}

		$this->id = (int)$this->id;

		return true;
	}

	public function save($updateOnly = false)
	{
		$dispatcher = \JEventDispatcher::getInstance();
        \JPluginHelper::importPlugin('jspace');

		$isNew = empty($this->id);

		$dispatcher->trigger('onJSpaceBeforeSave', array(static::$context, $this, $isNew));

		$this->metadata = (string)$this->_metadata;

		$table = \JTable::getInstance('Asset', 'JSpaceTable');
		$table->bind($this->getProperties());
		$table->store();

		if (empty($this->id))
		{
			$this->id = $table->get('id');
		}

		$dispatcher->trigger('onJSpaceAfterSave', array(static::$context, $this, $isNew));
	}

	public function load($keys)
	{
		$table = \JTable::getInstance('Asset', 'JSpaceTable');

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
		\JPluginHelper::importPlugin('content');

		// Trigger the onUserBeforeDelete event
		$dispatcher = \JEventDispatcher::getInstance();
		$dispatcher->trigger('onContentBeforeDelete', array(static::$context, $this));

		// Create the user table object
		$table = \JTable::getInstance('Asset', 'JSpaceTable');

		if (!$result = $table->delete($this->id))
		{
			throw new \Exception($table->getError());
		}

		// Trigger the onUserAfterDelete event
		$dispatcher->trigger('onContentAfterDelete', array(static::$context, $this));
	}
}