<?php
/**
 * @package     JSpace
 * @subpackage  Archive
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('joomla.table.category');

jimport('jspace.factory');
jimport('jspace.archive.asset');
jimport('jspace.filesystem.file');

/**
 * Represents a JSpace record.
 *
 * @package     JSpace
 * @subpackage  Archive
 */
class JSpaceRecord extends JObject
{
    protected static $context = 'com_jspace.record';
    
	protected static $instances = array();

    /**
     * Instatiates an instance of the PlgJSpaceGlacier class.
     * 
     * @param   int  $identifier  A JSpace record identifier if provided, otherwise creates an empty 
     * JSpace record.
     */
	public function __construct($identifier = 0)
	{
		JLog::addLogger(array());
	
		$this->_metadata = new JRegistry;

		if (!empty($identifier))
		{
			$this->load($identifier);
		}
		else
		{
			$this->id = null;
		}
	}

	/**
	 * Gets an instance of the JSpaceRecord class, creating it if it doesn't exist.
	 *
	 * @param   int  $identifier  The record id to retrieve.
	 * 
	 * @return  JSpaceRecord      An instance of the JSpaceRecord class.
	 */
	public static function getInstance($identifier = 0)
	{
		if (!is_numeric($identifier))
		{
			JLog::add(JText::sprintf('JLIB_USER_ERROR_ID_NOT_EXISTS', $identifier), JLog::WARNING, 'jspace');

			return false;
		}
		else
		{
			$id = $identifier;
		}

		if ($id === 0)
		{
			return new JSpaceRecord;
		}

		if (empty(self::$instances[$id]))
		{
			$record = new JSpaceRecord($id);
			self::$instances[$id] = $record;
		}

		return self::$instances[$id];
	}
	
	/**
	 * Gets an instance of JTable.
	 *
	 * This function uses a static variable to store the table name of the record table to
	 * instantiate. You can call this function statically to set the table name if
	 * needed.
	 *
	 * @param   string  $type    The table name to use. Defaults to Record.
	 * @param   string  $prefix  The table prefix to use. Defaults to JSpaceTable.
	 *
	 * @return  JTable  The table specified by type, or a JSpaceTableRecord if no type is specified.
	 */
	public static function getTable($type = null, $prefix = 'JSpaceTable')
	{
		static $tabletype;
		
		// Set the default tabletype;
		if (!isset($tabletype))
		{
			$tabletype['name'] = 'record';
			$tabletype['prefix'] = 'JSpaceTable';
		}

		// Set a custom table type is defined
		if (isset($type))
		{
			$tabletype['name'] = $type;
			$tabletype['prefix'] = $prefix;
		}
		
		// Create the user table object
		return JTable::getInstance($tabletype['name'], $tabletype['prefix']);
	}
	
	/**
	 * Bind an associative array of data to this instance of the JSpaceRecord class.
	 *
	 * @param   array      $array  The associative array to bind to the object.
	 *
	 * @return  boolean    True on success
	 *
	 * @throw   Exception  If the $array to be bound is not an object or array.
	 */
	public function bind(&$array)
	{
		if (array_key_exists('metadata', $array))
		{
			if (is_array($array['metadata']))
			{
				$this->_metadata->loadArray($array['metadata']);
				$metadata = (string)$this->_metadata;
			}
			else
			{
				$this->_metadata->loadString($array['metadata']);
				$metadata = $array['metadata'];
			}
			
			$this->metadata = $metadata;
		}

		// Bind the array
		if (!$this->setProperties($array))
		{
			throw new Exception('Data to be bound is neither an array nor an object');
		}

		return true;
	}

	/**
	 * Saves the current record.
	 *
	 * @param   array   $collection An array of assets and other asset-related information.
	 * @param   string  $updateOnly
	 * 
	 * @return  bool    True on success, false otherwise.
	 */
	public function save($collection = array(), $updateOnly = false)
	{
		// separate assets which should be saved with this record vs those which should be stored as a child.
		$children = array();
		
		foreach ($collection as $bkey=>$bundle)
		{
			$schema = JArrayHelper::getValue($bundle, 'schema', null);
			$assets = JArrayHelper::getValue($bundle, 'assets', array(), 'array');
			
			foreach ($assets as $dkey=>$derivative)
			{
				foreach ($derivative as $akey=>$asset)
				{
					$metadata = self::_getMetadataCrosswalk($asset)->toArray();
					$collection[$bkey]['assets'][$dkey][$akey]['metadata'] = $metadata;
				}
			}
			
			// if the schema is set, make the asset a child record.
			if ($schema)
			{
				$children[$bkey] = $collection[$bkey];
				unset($collection[$bkey]);
			}
		}
		
		$dispatcher = JEventDispatcher::getInstance();	
		JPluginHelper::importPlugin('content');
		
		$table = JTable::getInstance('Record', 'JSpaceTable');
		
		$this->metadata = (string)$this->_metadata;
		
		$table->bind($this->getProperties());
		
		$isNew = empty($this->id);
		
		$result = $dispatcher->trigger('onContentBeforeSave', array(static::$context, $this, $isNew));
		
		if (in_array(false, $result, true))
		{
			return false;
		}
		
		if (!$result = $table->store())
		{
			JLog::add(JText::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $table->getError()), JLog::CRITICAL, 'jspace');
			return false;
		}
		
		if (empty($this->id))
		{
			$this->id = $table->get('id');
		}

		$this->_saveAssets($collection);
		
		$dispatcher->trigger('onContentAfterSave', array(static::$context, $this, $isNew));
		
		$this->_saveChildren($children);
		
		return $result;	
	}
	
	/**
	 * Saves the record's assets.
	 *
	 * @param  array  $collection  An array of assets to save with the record.
	 */
	private function _saveAssets($collection)
	{
		foreach ($collection as $bkey=>$bundle)
		{
			$assets = JArrayHelper::getValue($bundle, 'assets', array(), 'array');
		
			foreach ($assets as $dkey=>$derivative)
			{
				foreach ($derivative as $akey=>$asset)
				{					
					$new = JSpaceAsset::getInstance();
					$new->bind($asset);
					
					$new->set('id', null);
					$new->set('record_id', $this->id);
					$new->set('hash', sha1_file(JArrayHelper::getValue($asset, 'tmp_name')));
					$new->set('bundle', $bkey);
					$new->set('derivative', $dkey);
					$new->save();
				}
			}
		}
	}
	
	/**
	 * Save children as separate sub records.
	 * 
	 * @param  array  $children  An array of children to save a sub records.
	 */	 
	private function _saveChildren($children)
	{
		$result = true;
		
		foreach ($children as $bkey=>$bundle)
		{
			$record = JSpaceRecord::getInstance();
			
			$array = array();
			$array['title'] = 'child record';
			$array['schema'] = JArrayHelper::getValue($bundle, 'schema');
			
			// The schema has been set. Remove.
			if (isset($bundle['schema']))
			{
				unset($bundle['schema']);
			}
			
			$array['parent_id'] = $this->id;
			$array['published'] = $this->published;
			$array['access'] = $this->access;
			$array['language'] = $this->language;
			
			// Get the first file for retrieving metadata from.
			$first = JArrayHelper::getValue($bundle, 'assets');
			$first = JArrayHelper::getValue($first, JArrayHelper::getValue(array_keys($first), 0));
			$first = JArrayHelper::getValue($first, 0);
			
			$metadata = self::_getMetadataCrosswalk($first);
			
			$array['title'] = $metadata->get('fileName', 'Untitled');
			$array['metadata'] = self::_crosswalkSchema($metadata->toArray(), $array['schema'])->toArray();
			
			$record->bind($array);
			$record->save(array($bkey=>$bundle));
		}
	}
	
	/**
	 * Deletes a record.
	 *
	 * @throw  Exception  When delete fails.
	 */
	public function delete()
	{
		JPluginHelper::importPlugin('content');
		$dispatcher = JEventDispatcher::getInstance();
		
		$table = self::getTable('Record');
		$table->load($this->id);
		
		$dispatcher->trigger('onContentBeforeDelete', array(static::$context, $table));
		
		foreach ($this->getAssets() as $asset)
		{
			$asset->delete();
		}
		
		if (!$table->delete())
		{
			throw new Exception($table->getError());
		}
		
		$dispatcher->trigger('onContentAfterDelete', array(static::$context, $table));

		return true;
	}
	
	public function load($keys)
	{
		$table = self::getTable('Record');
		
		if (!$table->load($keys))
		{
			return false;
		}
		
		$this->_metadata->loadString($table->metadata);
		
		$this->setProperties($table->getProperties());

		return true;
	}
	
	/**
	 * Walks a schema, copying values from the source to a new metadata registry.
	 *
	 * @params  array      $source  An array of metadata values to crosswalk.
	 * @params  string     $schema  The schema against which to crosswalk the metadata.
	 *
	 * @return  JRegistry  A new metadata registry of values found in the schema.
	 */
	private static function _crosswalkSchema($source, $schema)
	{
		$metadata = new JRegistry();
	
		$schemaPath = JPATH_ROOT."/administrator/components/com_jspace/models/forms/schemas/".$schema.".xml";
		
		$form = new JForm('jform');
		$form->loadFile($schemaPath);

		// try to match form fields to retrieved file metadata.
		foreach ($source as $key=>$value)
		{
			if ($form->getField($key, 'metadata'))
			{
				$metadata->set($key, $value);
			}
		}
		
		return $metadata;
	}
	
	/**
	 * Crosswalks the asset metadata.
	 * 
	 * @param    array     $asset  A record asset expressed as an array.
	 * 
	 * @return  JRegistry  The crosswalked asset metadata.
	 */
	private static function _getMetadataCrosswalk($asset)
	{
		$metadata = JSpaceFile::getMetadata(JArrayHelper::getValue($asset, 'tmp_name'));
		
		// set the file name to the original file name (it is using the upload name in the metadata).
		if ($fileName = JArrayHelper::getValue($asset, 'name'))
		{
			$metadata->set('resourceName', $fileName);
		}
		
		$metadata = JSpaceFactory::getCrosswalk($metadata, array('name'=>'datastream'))->walk();
		
		$metadata = new JRegistry($metadata);
		
		$metadata->set('checksumSHA1', sha1_file(JArrayHelper::getValue($asset, 'tmp_name')));
		$metadata->set('checksumMD5', md5_file(JArrayHelper::getValue($asset, 'tmp_name')));
		
		return $metadata;
	}
	
	/**
	 * Gets a list of assets associated with this record.
	 *
	 * The list of assets can be filtered by passing an array of key, value pairs:
	 * 
	 * E.g.
	 * 
	 * $filters = array('bundle'=>'videos','derivative'=>'original');
	 * $record->getAssets($filters);
	 * 
	 * @param   array  $filters  An array of filters.
	 *
	 * @return  JSpaceAsset[]  An array of JSpaceAsset objects.
	 */
	public function getAssets($filters = array())
	{
		$database = JFactory::getDbo();
		$query = $database->getQuery(true);
		
		$query
			->select(array('id', 'hash', 'metadata', 'derivative', 'bundle', 'record_id'))
			->from('#__jspace_assets')
			->where('record_id='.(int)$this->id);
			
		foreach ($filters as $key=>$value)
		{
			$query->where($database->qn($key).'='.$database->q($value));
		}
		
		$database->setQuery($query);
		
		return $database->loadObjectList('id', 'JSpaceAsset');
	}
	
	public function bindAssetMetadata($assetId)
	{
		$asset = JSpaceAsset::getInstance($assetId);
		
		if ($asset->id)
		{
			$this->_metadata = self::_crosswalkSchema($asset->getMetadata()->toArray(), $this->schema);
			$this->metadata = (string)$this->_metadata;
			$this->save();
		}
	}
}