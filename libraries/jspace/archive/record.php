<?php
/**
 * @package     JSpace
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
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

	public function save($assets = null, $updateOnly = false)
	{
		// separate assets which should be saved with this record vs those which should be stored as a child record.
		$children = array();
		
		foreach ($assets as $key=>$asset)
		{
			
			$assets[$key]['metadata'] = array();
			
			foreach ($asset as $bundle)
			{
				$extractionMap = JArrayHelper::getValue($bundle, 'extractionmap', null);
				$schema = JArrayHelper::getValue($bundle, 'schema', null);
				$files = JArrayHelper::getValue($bundle, 'files');
				
				// bundle is a single file. Redefine bundle to centralize file manipulation.
				if (array_key_exists('tmp_name', $files))
				{
					$tmp = $files;
					$files = array();
					$files[] = $tmp;
				}

				foreach ($files as $file)
				{
					$assets[$key]['metadata'] = 
array_merge_recursive($assets[$key]['metadata'], JSpaceAssetHelper::getMetadata($file, $schema, $extractionMap));
				
					if ($schema && $this->schema != $schema)
					{
						$children[] = $assets[$key];
					}
				}
			}
		}
		
		$assets = array_values($assets);
		print_r($assets);
		exit();
		
		$dispatcher = JEventDispatcher::getInstance();	
		JPluginHelper::importPlugin('jspace');
		
		try
		{
			$table = $this->getTable();
			
			$this->metadata = (string)$this->_metadata;
			
			$table->bind($this->getProperties());
			
			$isNew = empty($this->id);
			
			$result = $dispatcher->trigger('onJSpaceRecordBeforeSave', array($table, $assets, $isNew));
	
			if (in_array(false, $result, true))
			{
				return false;
			}
			
			$result = $table->store();
			
			if ($result)
			{
				if ($this->catid)
				{
					$recordCategory = $this->getTable('RecordCategory');
					$recordCategory->record_id = $table->id;
					$recordCategory->catid = $this->catid;
					$recordCategory->store();
				}
			}
			
			$dispatcher->trigger('onJSpaceRecordAfterSave', array($table, $assets, $isNew));
			/*
			foreach ($children as $asset)
			{
				$record = JSpaceRecord::getInstance();
				$fileMetadata = JSpaceAssetHelper::getMetadata($asset);
				$record->title = $fileMetadata->title;
				$record->metadata = ;
				$record->save($asset);
			}
			*/
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
		
		return $result;	
	}
	
	public function load($id)
	{
		$table = $this->getTable();
		
		if (!$table->load($id))
		{
			JLog::add(JText::sprintf('JLIB_USER_ERROR_UNABLE_TO_LOAD_USER', $id), JLog::WARNING, 'jerror');

			return false;
		}
		
		$this->_metadata->loadString($table->metadata);
		
		$this->setProperties($table->getProperties());

		return true;
	}
}