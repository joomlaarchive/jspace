<?php
/**
 * @package     JSpace
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

jimport('jspace.archive.asset');
 
class JSpaceRecord extends JObject
{
	protected static $instances = array();

	public function __construct($identifier = 0)
	{
		$this->_metadata = new JRegistry;

		if (!empty($identifier))
		{
			$this->load($identifier);
		}
		else
		{
			$this->id = 0;
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
			JLog::add(JText::sprintf('JLIB_USER_ERROR_ID_NOT_EXISTS', $identifier), JLog::WARNING, 'jerror');

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

	/**
	 * Saves the current record.
	 *
	 * @param   array   $collection An array of assets and other asset-related information.
	 * @param   string  $updateOnly
	 * 
	 * @return  bool    True on success, false otherwise.
	 */
	public function save($collection = null, $updateOnly = false)
	{
		// separate assets which should be saved with this record vs those which should be stored as a child.
		$children = array();
		
		// Get the metadata derivative of the first asset.
		$metadataDerivative = JArrayHelper::getValue(current($collection), 'metadata', null);
		
		foreach ($collection as $bkey=>$bundle)
		{
			$schema = JArrayHelper::getValue($bundle, 'schema', null);
		
			foreach ($bundle as $dkey=>$derivative)
			{
				$assets = JArrayHelper::getValue($derivative, 'assets', array(), 'array');
				
				foreach ($assets as $akey=>$asset)
				{
					$metadata = self::_getMetadataCrosswalk($asset)->toArray();
					$collection[$bkey][$dkey]['assets'][$akey]['metadata'] = $metadata;
					
					if ($schema && $this->schema != $schema)
					{
						$children[] = $collection[$bkey];
						unset($collection[$bkey]);
					}
				}
			}
		}
		
		$dispatcher = JEventDispatcher::getInstance();	
		JPluginHelper::importPlugin('jspace');
		
		$table = $this->getTable();
		
		// Set metadata to file info if not already set and if metadataDerivative has been specified.
		if ($metadataDerivative)
		{
			if (!count($this->getAssets(array('derivative'=>$metadataDerivative))))
			{
				// get the first asset.
				$first = JArrayHelper::getValue($collection, current(array_keys($collection)));
				
				// get the specified derivative.
				$first = JArrayHelper::getValue($first, $metadataDerivative, array(), 'array');
				
				// get the first file.
				$first = current(JArrayHelper::getValue($first, 'assets', array(), 'array'));
				
				if (JArrayHelper::getValue($first, 'metadata', null))
				{
					$metadata = JArrayHelper::getValue($first, 'metadata', array(), 'array');				
					$this->_metadata = self::_crosswalkSchema($metadata, $this->schema);
				}
			}
		}
		
		$this->metadata = (string)$this->_metadata;
		
		$table->bind($this->getProperties());
		
		$isNew = empty($this->id);
		
		$result = $dispatcher->trigger('onJSpaceRecordBeforeSave', array($table, $isNew));

		if (in_array(false, $result, true))
		{
			return false;
		}
		
		$result = $table->store();
		
		if (empty($this->id))
		{
			$this->id = $table->get('id');
		}
		
		if ($result)
		{
			// if record is a parent, store with its category.
			if ($this->catid)
			{
				$recordCategory = $this->getTable('RecordCategory');
				$recordCategory->record_id = $table->id;
				$recordCategory->catid = $this->catid;
				$recordCategory->store();
			}
		}
		
		foreach ($collection as $bkey=>$bundle)
		{
			foreach ($bundle as $dkey=>$derivative)
			{
				$assets = JArrayHelper::getValue($derivative, 'assets', array(), 'array');
				
				foreach ($assets as $akey=>$asset)
				{					
					$new = JSpaceAsset::getInstance();
					$new->bind($asset);
					
					$new->set('record_id', $this->id);
					$new->set('hash', sha1_file(JArrayHelper::getValue($asset, 'tmp_name')));
					$new->set('bundle', $bkey);
					$new->set('derivative', $dkey);
					$new->save();
				}
			}
		}
		
		$dispatcher->trigger('onJSpaceRecordAfterSave', array($table, $isNew));
		/*
		foreach ($children as $asset)
		{
			$record = JSpaceRecord::getInstance();
			$record->title = 'test';
			$record->schema = JArrayHelper::getValue($asset, 'schema');
			$recordCategory->parent_id = $table->id;
			$record->save(array($asset));
		}
		*/
		return $result;	
	}
	
	public function delete()
	{
		JPluginHelper::importPlugin('jspace');
		$dispatcher = JEventDispatcher::getInstance();
		
		$table = $this->getTable();
		$table->load($this->id);
		
		$dispatcher->trigger('onJSpaceRecordBeforeDelete', array($table));
		
		if (!$result = $table->delete())
		{
			throw new Exception($table->getError());
		}
		
		$dispatcher->trigger('onJSpaceRecordAfterDelete', array($table));

		return $result;
	}
	
	public function load($id)
	{
		$table = $this->getTable();
		
		if (!$table->load($id))
		{
			JLog::add(JText::sprintf('COM_JSPACE_ERROR_UNABLETOLOADRECORD', $id), JLog::WARNING, 'jerror');

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
	 * Crosswalks the asset metadata to a metadata schema.
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
			->where('record_id='.$this->id);
			
		foreach ($filters as $key=>$value)
		{
			$query->where($database->qn($key).'='.$database->q($value));
		}
		
		$database->setQuery($query);
		
		return $database->loadObjectList('id', 'JSpaceAsset');
	}
}