<?php
/**
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
namespace JSpace\Ingestion;

use \Joomla\Registry\Registry;

/**
 * Represents a JSpace harvest.
 */
class Harvest extends \JObject
{
    protected static $context = 'com_jspace.harvest';

    protected static $instances = array();

    /**
     * Instatiates an instance of the JSpaceIngestionHarvest class.
     *
     * @param   int  $identifier  A JSpace record identifier if provided, otherwise creates an empty
     * JSpace record.
     */
    public function __construct($properties = null)
    {
        parent::__construct($properties);

        \JLog::addLogger(array());

        // match class property defaults to db defaults.
        $this->total = 0;
        $this->harvested = '0000-00-00 00:00:00';
        $this->modified = '0000-00-00 00:00:00';
        $this->modified_by = 0;
        $this->checked_out = 0;
        $this->checked_out_time = '0000-00-00 00:00:00';

        if ($this->get('id', null))
        {
            $this->load($this->id);
        }
    }

    /**
     * Gets an instance of the JSpaceIngestionHarvest class, creating it if it doesn't exist.
     *
     * @param   int      $identifier  The record id to retrieve.
     *
     * @return  JObject  An instance of the Record class.
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
            return new \JSpace\Ingestion\Harvest;
        }

        if (empty(self::$instances[$id]))
        {
            $record = new \JSpace\Ingestion\Harvest(array('id'=>$id));
            self::$instances[$id] = $record;
        }

        return self::$instances[$id];
    }

    /**
     * Saves the current harvest.
     *
     * @param   string  $updateOnly
     *
     * @return  bool    True on success, false otherwise.
     */
    public function save($updateOnly = false)
    {
        $dispatcher = \JEventDispatcher::getInstance();
        \JPluginHelper::importPlugin('content');

        $table = \JTable::getInstance('Harvest', 'JSpaceTable');

        if (!($isNew = empty($this->id)))
        {
            $table->load($this->id);
        }

        $table->bind($this->getProperties());
        $table->params = (string)$this->params;

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

        $dispatcher->trigger('onContentAfterSave', array(static::$context, $this, $isNew));

        return $result;
    }


    /**
     * Deletes a harvest.
     *
     * @throw  Exception  When delete fails.
     */
    public function delete()
    {
        \JPluginHelper::importPlugin('content');
        $dispatcher = \JEventDispatcher::getInstance();

        $table = \JTable::getInstance('Harvest', 'JSpaceTable');
        $table->load($this->id);

        $dispatcher->trigger('onContentBeforeDelete', array(static::$context, $table));

        if (!$table->delete())
        {
            throw new Exception($table->getError());
        }

        $dispatcher->trigger('onContentAfterDelete', array(static::$context, $table));

        return true;
    }

    /**
     * Bind an associative array of data to this instance of the Record class.
     *
     * @param   array      $array  The associative array to bind to the object.
     *
     * @return  boolean    True on success
     *
     * @throw   Exception  If the $array to be bound is not an object or array.
     */
    public function bind(&$array)
    {
        $this->set('params', \JArrayHelper::getValue($array, 'params', null));

        // Bind the array
        if (!$this->setProperties($array))
        {
            throw new Exception('Data to be bound is neither an array nor an object');
        }

        return true;
    }

    public function load($keys)
    {
        $table = \JTable::getInstance('Harvest', 'JSpaceTable');

        if (!$table->load($keys))
        {
            return false;
        }

        $this->params = new Registry;
        $this->params->loadString($table->params);

        $this->setProperties($table->getProperties());

        return true;
    }


    /**
     * Gets the cached records belonging to this harvest.
     *
     * The cache can be returned in chunks to avoid performance issues.
     *
     * @param   int        $start  The cache offset.
     * @param   int        $limit  The size of the cache to return.
     *
     * @return  JObject[]  An array of cached records.
     */
    public function getCache($start = 0, $limit = 100)
    {
        $database = \JFactory::getDbo();

        $query = $database->getQuery(true);

        $select = array(
            $database->qn('id'),
            $database->qn('harvest_id'),
            $database->qn('data'));

        $query
            ->select($select)
            ->from($database->qn('#__jspace_cache', 'jc'))
            ->where($database->qn('jc.harvest_id').'='.(int)$this->id);

        $database->setQuery($query, $start, $limit);

        return $database->loadObjectList('id', 'JObject');
    }

    // @todo Rename when JObject replaces with __set.
    public function set($name, $value = null)
    {
        switch ($name)
        {
            case 'params':
                // reset the registry when params set.
                $this->$name = new Registry;

                if (is_array($value))
                {
                    $this->$name->loadArray($value);
                }
                else if (is_a($value, '\Joomla\Registry\Registry'))
                {
                    $this->$name = $value;
                }
                else if (is_string($value))
                {
                    $this->$name->loadString($value);
                }
                else
                {
                    throw new Exception('Invalid params format. Not a JRegistry, array or string.');
                }

                break;

            default:
                return parent::set($name, $value);
                break;
        }
    }

    // @todo Rename when JObject replaces with __get.
    public function get($name, $default = null)
    {
        switch ($name)
        {
            case 'params':
                if (!isset($this->$name))
                {
                    $this->set($name, '');
                }

                return $this->$name;

                break;

            default:
                return parent::get($name, $default);
                break;
        }
    }
}