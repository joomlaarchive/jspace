<?php
/**
 * @copyright   Copyright (C) 2014-2015 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
namespace JSpace\Archive;

use \Exception;

\JLoader::import('joomla.table.category');
use \JCategories;
use \JArrayHelper;
use \JLog;
use \JText;
use JSpace\Archive\Object;
use JSpace\Archive\Asset;

/**
 * Represents a JSpace record.
 */
class Record extends Object
{
    protected static $context = 'com_jspace.record';

    protected static $instances = array();

    public $parent_id = 0;

    protected $checked_out = 0;

    protected $checked_out_time = null;

    protected $schema = null;

    protected $publish_up = null;

    protected $publish_down = null;

    /**
     * External identifiers ensure JSpace records are unique when used with other systems such as
     * Handle.net.
     *
     * @var  string[]  $identifiers  An array of external identifiers.
     */
    protected $identifiers = array();

    /**
     * Gets an instance of the JSpace\Archive\Record class, creating it if it doesn't exist.
     *
     * @param   int  $identifier  The record id to retrieve.
     *
     * @return  JSpace\Archive\Record      An instance of the JSpace\Archive\Record class.
     */
    public static function getInstance($identifier = 0)
    {
        if (!is_numeric($identifier)) {
            JLog::add(JText::sprintf('JLIB_USER_ERROR_ID_NOT_EXISTS', $identifier), JLog::WARNING, 'jspace');

            return false;
        } else {
            $id = $identifier;
        }

        if ($id === 0) {
            return new Record;
        }

        if (empty(self::$instances[$id])) {
            $record = new Record($id);
            self::$instances[$id] = $record;
        }

        return self::$instances[$id];
    }

    /**
     * Bind an associative array of data to this instance of the JSpace\Archive\Record class.
     *
     * @param   array      $array  The associative array to bind to the object.
     *
     * @return  boolean    True on success
     *
     * @throw   Exception  If the $array to be bound is not an object or array.
     */
    public function bind(&$array)
    {
        if ((!empty($array['tags']) && $array['tags'][0] != '')) {
            $this->newTags = $array['tags'];
        }

        if (array_key_exists('identifiers', $array)) {
            if (!is_array(JArrayHelper::getValue($array, 'identifiers'))) {
                $array['identifiers'] = array();
            }

            $this->identifiers = array_merge($this->identifiers, $array['identifiers']);
        }

        $this->metadata = JArrayHelper::getValue($array, 'metadata', array());

        // Bind the array
        if (!$this->setProperties($array)) {
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
        $dispatcher = \JEventDispatcher::getInstance();
        \JPluginHelper::importPlugin('jspace');

        $table = \JTable::getInstance('Record', 'JSpaceTable');

        if (!($isNew = empty($this->id))) {
            $table->load($this->id);
        }

        if ($isNew || $table->parent_id != $this->parent_id) {
            $table->setLocation($this->parent_id, 'last-child');
        }

        $table->bind($this->getProperties());

        if (isset($this->newTags)) {
            $table->newTags = $this->newTags;
        }

        $result = $dispatcher->trigger('onJSpaceBeforeSave', array(static::$context, $this, $isNew));

        if (in_array(false, $result, true)) {
            return false;
        }

        if (!$result = $table->store()) {
            JLog::add(__METHOD__." Cannot save. ".$table->getError(), JLog::CRITICAL, 'jspace');
            return false;
        }

        if (empty($this->id)) {
            $this->id = $table->get('id');
        }

        // update $this fields with internally changed table fields.
        $this->parent_id = $table->get('parent_id');

        // Rebuild the tree path.
        if (!$table->rebuildPath($table->id)) {
            JLog::add(__METHOD__." Cannot rebuild path. ".$table->getError(), JLog::CRITICAL, 'jspace');
            return false;
        }

        $this->saveIdentifiers();
        $this->saveAssets($collection);

        $dispatcher->trigger('onJSpaceAfterSave', array(static::$context, $this, $isNew));

        return $result;
    }

    /**
     * Saves the record's assets.
     *
     * @param  array  $collection  An array of assets to save with the record.
     */
    private function saveAssets($collection)
    {
        foreach ($collection as $dkey=>$derivative) {
            foreach ($derivative as $akey=>$asset) {
                $new = Asset::getInstance();
                $new->bind($asset);

                $new->set('id', null);
                $new->set('record_id', $this->id);
                $new->set('title', \JSpace\FileSystem\File::makeSafe(\JArrayHelper::getValue($asset, 'name')));
                $new->set('contentLength', \JArrayHelper::getValue($asset, 'size', 0, 'int'));
                $new->set('contentType', \JArrayHelper::getValue($asset, 'type', null, 'string'));
                $new->set('hash', \JSpace\FileSystem\File::getHash(\JArrayHelper::getValue($asset, 'tmp_name')));
                $new->set('derivative', $dkey);

                $metadata = \JSpace\FileSystem\File::getMetadata(\JArrayHelper::getValue($asset, 'tmp_name'));

                $crosswalk = \JSpace\Factory::getCrosswalk($metadata);
                $new->set('metadata', $crosswalk->walk());

                $new->save();
            }
        }
    }

    /**
     * Saves the alternative identifiers.
     *
     * @todo Investigate moving to JSpaceTableRecord.
     */
    private function saveIdentifiers()
    {
        foreach ($this->identifiers as $identifier) {
            $table = \JTable::getInstance('RecordIdentifier', 'JSpaceTable');
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
        \JPluginHelper::importPlugin('content');
        $dispatcher = \JEventDispatcher::getInstance();

        $table = \JTable::getInstance('Record', 'JSpaceTable');
        $table->load($this->id);

        $dispatcher->trigger('onJSpaceBeforeDelete', array(static::$context, $table));

        $database = \JFactory::getDbo();
        $query = $database->getQuery(true);

        $query
            ->select(array($database->qn('id'), $database->qn('record_id')))
            ->from($database->qn('#__jspace_record_identifiers'))
            ->where($database->qn('record_id').'='.(int)$this->id);

        $identifiers = $database->setQuery($query)->loadObjectList();

        foreach ($identifiers as $identifier) {
            $table = \JTable::getInstance('RecordIdentifier', 'JSpaceTable');
            $table->delete($identifier->id);
        }

        foreach ($this->getAssets() as $asset) {
            $asset->delete();
        }

        if (!$table->delete()) {
            throw new Exception($table->getError());
        }

        $dispatcher->trigger('onJSpaceAfterDelete', array(static::$context, $table));

        return true;
    }

    public function load($keys)
    {
        $table = \JTable::getInstance('Record', 'JSpaceTable');

        if (!$table->load($keys)) {
            return false;
        }

        $this->metadata = $table->metadata;

        $this->setProperties($table->getProperties());

        return true;
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
     * @return  Asset[]  An array of Asset objects.
     */
    public function getAssets($filters = array())
    {
        $database = \JFactory::getDbo();
        $query = $database->getQuery(true);

        $select = array(
            $database->qn('id'),
            $database->qn('title'),
            $database->qn('hash'),
            $database->qn('contentType'),
            $database->qn('contentLength'),
            $database->qn('metadata'),
            $database->qn('derivative'),
            $database->qn('record_id'));

        $query
            ->select($select)
            ->from($database->qn('#__jspace_assets'))
            ->where($database->qn('record_id').'='.(int)$this->id);

        foreach ($filters as $key=>$value) {
            $query->where($database->qn($key).'='.$database->q($value));
        }

        $database->setQuery($query);

        return $database->loadObjectList('id', '\JSpace\Archive\Asset');
    }

    public function getTags()
    {
        $tags = new JHelperTags;
        $tags->getItemTags('com_jspace.record', $this->id);

        return $tags;
    }

    /**
     * Get JSpace record, its children and assets as a tree structure.
     * @param   int        $id  The id of the root record to fetch.
     *
     * @return  stdClass   The top record node along with all children.
     * The record contains its children as an array within a children property with each child
     * node having its own children and so on until the leaf node is reached.
     *
     * @throw   Exception  If a record cannot be found or if the $id parameter equals the root node.
     */
    public static function getTree($id)
    {
        \JTable::addIncludePath(JPATH_BASE.'/administrator/components/com_jspace/tables/');

        $table = \JTable::getInstance('Record', 'JSpaceTable');

        if (!$table->load($id)) {
            throw new Exception('The record cannot be found.', 404);
        }

        if ($table->title == 'JSpace_Record_Root') {
            throw new Exception('Direct access to root node not allowed', 403);
        }

        $items = $table->getTree();
        return self::buildTree($items);
    }

    private static function buildTree($items, $parent = 0, $level = 0)
    {
        if ($level > 1000) return ''; // Make sure not to have an endless recursion

        $tree = array();

        if ($parent == 0 && $level == 0) {
            $tree = null;
        }

        foreach ($items as $key=>$value) {
            if(is_null($tree) || (int)$value->parent_id == $parent) {
                $item = $value;
                unset($items[$key]);
                $item->children = self::buildTree($items, $value->id, $value->level);

                if (is_array($tree)) {
                    $tree[] = $item;
                }
                else {
                    $tree = $item;
                }
            }
        }

        return $tree;
    }

    /**
     * Gets the parent record this record belongs to.
     *
     * @return  JSpace\Archive\Record  The parent record this record belongs to.
     * If the current user does not have access to this parent, null is returned.
     */
    public function getParent()
    {
        $viewLevels = \JFactory::getUser()->getAuthorisedViewLevels();

        $parent = Record::getInstance($this->parent_id);

        if ($parent->alias == 'root' || ($parent->access && !in_array($parent->access, $viewLevels))) {
            $parent = null;
        }

        return $parent;
    }

    /**
     * Gets the category the current record belongs to.
     *
     * @return  JCategory  The category the current record belongs to.
     * If the current user does not have access to this category, null is returned.
     */
    public function getCategory()
    {
         return JCategories::getInstance('JSpace')->get($this->catid);
    }

    /**
     * Loads and returns the children of the current Record.
     *
     * @return  Record[]  An array of Record objects which are children of the current node.
     */
    public function getChildren()
    {
        \JTable::addIncludePath(JPATH_BASE.'/administrator/components/com_jspace/tables/');

        $database = \JFactory::getDbo();
        $table = \JTable::getInstance('Record', 'JSpaceTable');
        $query = $database->getQuery(true);

        $fields = array();
        foreach ($table->getFields() as $field) {
            $fields[] = $database->qn($field->Field);
        }

        $query
            ->select($fields)
            ->from($table->getTableName())
            ->where($database->qn('parent_id').'='.(int)$this->id);

        return $database->setQuery($query)->loadObjectList('id', 'JSpace\Archive\Record');
    }

    public function getIdentifiers()
    {
        \JTable::addIncludePath(JPATH_BASE.'/administrator/components/com_jspace/tables/');

        $database = \JFactory::getDbo();
        $table = \JTable::getInstance('RecordIdentifier', 'JSpaceTable');
        $query = $database->getQuery(true);

        $query
            ->select($database->qn('id'))
            ->from($table->getTableName())
            ->where($database->qn('record_id').'='.(int)$this->id);

        return $database->setQuery($query)->loadColumn();
    }

    public function getCreatedBy()
    {
        return JUser::getInstance($this->created_by);
    }

    public function getReferences()
    {
        \JTable::addIncludePath(JPATH_BASE.'/administrator/components/com_jspace/tables/');

        $database = \JFactory::getDbo();
        $table = \JTable::getInstance('Reference', 'JSpaceTable');
        $query = $database->getQuery(true);

        $fields = array();
        foreach ($table->getFields() as $field) {
            $fields[] = $database->qn($field->Field);
        }

        $query
            ->select($fields)
            ->from($table->getTableName())
            ->where($database->qn('record_id').'='.(int)$this->id);

        return $database->setQuery($query)->loadObjectList('id', 'JObject');
    }

    public function bindAssetMetadata($assetId)
    {
        $asset = Asset::getInstance($assetId);

        if ($asset->id) {
            $crosswalk = \JSpace\Factory::getCrosswalk($asset->get('metadata'));
            $this->set('metadata', $crosswalk->walk());
            $this->save();
        }
    }
}