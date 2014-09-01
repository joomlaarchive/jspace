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
    
    public $parent_id = 0;

    /**
     * External identifiers ensure JSpace records are unique when used with other systems such as 
     * Handle.net.
     * 
     * @var  string[]  $identifiers  An array of external identifiers.
     */
    protected $identifiers = array();

    /**
     * Instatiates an instance of the JSpaceRecord class.
     * 
     * @param   int  $identifier  A JSpace record identifier if provided, otherwise creates an empty 
     * JSpace record.
     */
	public function __construct($identifier = 0)
	{
        JTable::addIncludePath(JPATH_BASE.'/administrator/components/com_jspace/tables/');
        
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
        if ((!empty($array['tags']) && $array['tags'][0] != ''))
        {
            $this->newTags = $array['tags'];
        }
        
        if (array_key_exists('identifiers', $array))
        {
            if (!is_array(JArrayHelper::getValue($array, 'identifiers')))
            {
                $array['identifiers'] = array();
            }
            
            $this->identifiers = array_merge($this->identifiers, $array['identifiers']);
        }        
        
        $this->metadata = JArrayHelper::getValue($array, 'metadata', array());

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
		
		if (!($isNew = empty($this->id)))
        {
            $table->load($this->id);
        }
        
        if ($isNew || $table->parent_id != $this->parent_id)
        {
            $table->setLocation($this->parent_id, 'last-child');
        }
		
		$table->bind($this->getProperties());
		
		if (isset($this->newTags))
		{
            $table->newTags = $this->newTags;
		}
		
		$result = $dispatcher->trigger('onContentBeforeSave', array(static::$context, $this, $isNew));
		
		if (in_array(false, $result, true))
		{
			return false;
		}
		
		if (!$result = $table->store())
		{
			JLog::add(__METHOD__." Cannot save. ".$table->getError(), JLog::CRITICAL, 'jspace');
			return false;
		}

        if (empty($this->id))
        {
            $this->id = $table->get('id');
        }

        // Rebuild the tree path.
        if (!$table->rebuildPath($table->id))
        {
            JLog::add(__METHOD__." Cannot rebuild path. ".$table->getError(), JLog::CRITICAL, 'jspace');
            return false;
        }

        $this->_saveIdentifiers();
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
     * Saves the alternative identifiers.
     * 
     * @todo Investigate moving to JSpaceTableRecord.
     */
    private function _saveIdentifiers()
    {
        foreach ($this->identifiers as $identifier)
        {
            $table = JTable::getInstance('RecordIdentifier', 'JSpaceTable');
            $table->id = $identifier;
            $table->record_id = $this->id;
            $table->store();
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

        $table = JTable::getInstance('Record', 'JSpaceTable');
        $table->load($this->id);

        $dispatcher->trigger('onContentBeforeDelete', array(static::$context, $table));
    
        $database = JFactory::getDbo();
        $query = $database->getQuery(true);
        
        $query
            ->select(array($database->qn('id'), $database->qn('record_id')))
            ->from($database->qn('#__jspace_record_identifiers'))
            ->where($database->qn('record_id').'='.(int)$this->id);

        $identifiers = $database->setQuery($query)->loadObjectList();
            
        foreach ($identifiers as $identifier)
        {
            $table = JTable::getInstance('RecordIdentifier', 'JSpaceTable');
            $table->delete($identifier->id);
        }
		
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
		$table = JTable::getInstance('Record', 'JSpaceTable');
		
		if (!$table->load($keys))
		{
			return false;
		}
		
		$this->metadata = $table->metadata;
		
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
	
	/**
	 * Get JSpace record, its children and assets as a tree structure.
	 */
	public static function getTree($id)
	{
        JTable::addIncludePath(JPATH_BASE.'/administrator/components/com_jspace/tables/');
        
        $table = JTable::getInstance('Record', 'JSpaceTable');
        
        if (!$table->load($id))
        {
            throw new Exception('The record cannot be found.', 404);
        }
        
        if ($table->title == 'JSpace_Record_Root')
        {
            throw new Exception('Direct access to root node not allowed', 403);
        }

        $items = $table->getTree();
        return self::_buildTree($items);
	}
	
	private static function _buildTree($items, $parent = 0, $level = 0)
	{
        if ($level > 1000) return ''; // Make sure not to have an endless recursion
        
        $tree = array();
        
        if ($parent == 0 && $level == 0)
        {
            $tree = null;
        }
        
        foreach ($items as $key=>$value)
        {
            if(is_null($tree) || (int)$value->parent_id == $parent)
            {
                $item = $value;
                unset($items[$key]);
                $item->children = self::_buildTree($items, $value->id, $value->level);
                
                if (is_array($tree))
                {
                    $tree[] = $item;
                }
                else
                {
                    $tree = $item;
                }
            }
        }
        
        return $tree;
    }

    // @todo Override until JObject declares __set.
    public function set($name, $value = null)
    {
        switch ($name)
        {
            case 'metadata':
                // reset the registry when metadata set.
                $this->_metadata = new JRegistry;

                if (is_array($value))
                {
                    $this->_metadata->loadArray($value);
                }
                else if (is_a($value, 'JRegistry'))
                {
                    $this->_metadata = $value;
                }
                else if (is_string($value))
                {
                    $this->_metadata->loadString($value);
                }
                else
                {
                    throw new Exception('Invalid metadata format. Not a JRegistry, array or string.');
                }

                $this->$name = (string)$this->_metadata;

                break;
                
            default:
                return parent::set($name, $value);
                break;
        }
    }
    
    // @todo Override until JObject declares __get.
    public function get($name, $default = null)
    {
        switch ($name)
        {
            case 'metadata':
                return $this->_metadata;
                break;
                
            default:
                return parent::get($name, $default);
                break;
        }
    }
}