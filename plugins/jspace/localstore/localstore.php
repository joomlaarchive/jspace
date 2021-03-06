<?php
/**
 * @package    JSpace.Plugin
 *
 * @copyright   Copyright (C) 2014-2015 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

use \JSpace\Factory as JSpaceFactory;
use \JSpace\Archive\AssetHelper;
use \JSpace\FileSystem\File as JSpaceFile;

\JLoader::import('joomla.filesystem.folder');

/**
 * Stores assets to the locally configured file system.
 *
 * @package  JSpace.Plugin
 */
class PlgJSpaceLocalstore extends JPlugin
{
    protected static $chunksize = 4096;

    /**
     * Instatiates an instance of the PlgJSpaceLocalstore class.
     * @param   object  &$subject  The object to observe
     * @param   array   $config    An optional associative array of configuration settings.
     *                             Recognized key values include 'name', 'group', 'params', 'language'
     *                             (this list is not meant to be comprehensive).
     */
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();

        JLog::addLogger(array());

        // load the jsolrindex component's params into plugin params for
        // easy access.
        $params = JComponentHelper::getParams('com_jspace', true);

        $this->params->loadArray(array('component'=>$params->toArray()));
    }

    /**
     * Returns the HTML to the JSpace Asset Store download mechanism.
     *
     * @param   \JSpace\Html\Asset  $asset  An instance of the asset being downloaded.
     *
     * @return  string              The html to the JSpace Asset Store download mechanism.
     */
    public function onJSpaceAssetPrepareDownload($asset)
    {
        $asset->url = JRoute::_('index.php?option=com_jspace&task=asset.stream&type=jspaceassetstore&id='.$asset->id);

        $layout = JPATH_PLUGINS.'/content/jspaceassetstore/layouts';
        $html = JLayoutHelper::render("jspaceassetstore", $asset, $layout);

        return $html;
    }

    /**
     * Streams a file from the JSpace Asset Store to the client's web browser
     * (or other download mechanism).
     *
     * @param  \JSpace\Html\Asset  $asset  An instance of the asset being downloaded.
     */
    public function onJSpaceAssetDownload($asset)
    {
        $root = $this->get('params')->get('path', null);
        $id = $asset->id;

        $path = AssetHelper::buildStoragePath($asset->record_id, $root).$asset->hash;

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        header("Content-Type: ".$asset->get('contentType'));
        header("Content-Disposition: attachment; filename=".$asset->get('title').";");
        header("Content-Length: ".$asset->get('contentLength'));

        while (!feof($handle)) {
            $buffer = fread($handle, static::$chunksize);

            echo $buffer;

            ob_flush();
            flush();
        }

        $status = fclose($handle);
    }

    /**
     * Checks for the existence of a similar file already archived against the current record.
     *
     * @param  JForm  $form
     * @param  array  $data
     * @param  array  $group
     */
    public function onJSpaceAfterValidate($form, $data, $group = null)
    {
        $collection = \JSpace\Html\Assets::getCollection();

        foreach ($collection as $dkey=>$derivative) {
            foreach ($derivative as $akey=>$asset) {
                $hash = JSpaceFile::getHash(JArrayHelper::getValue($asset, 'tmp_name'));

                $database = JFactory::getDbo();
                $query = $database->getQuery(true);
                $query
                    ->select('id')
                    ->from('#__jspace_assets')
                    ->where(array(
                        $database->qn('hash')."=".$database->q($hash),
                        $database->qn('record_id')."=".JArrayHelper::getValue($data, 'id', 0, 'int')));

                $database->setQuery($query);

                if ($database->loadResult()) {
                    JFactory::getApplication()->enqueueMessage(JText::_('COM_JSPACE_ERROR_FILE_EXISTS'), 'error');
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validates asset store location before the record is saved.
     *
     * @param   string   $context  The context of the content being passed. Will be com_jspace.record.
     * @param   JObject  $item     An instance of the JSpaceRecord class.
     * @param   bool     $isNew    True if the record being saved is new, false otherwise.
     *
     * @return  bool               True if all file store requirements are met, otherwise false.
     */
    public function onJSpaceBeforeSave($context, $item, $isNew)
    {
        if ($context != 'com_jspace.record') {
            return true;
        }

        $path = AssetHelper::preparePath($this->params->get('path'));

        while ($path && !JFolder::exists($path)) {
            $parts = explode('/', $path);

            array_pop($parts);

            $path = implode('/', $parts);
        }

        return true;
    }

    /**
     * Saves an asset to a locally configured asset store.
     *
     * @param   string     $context  The context of the content being passed. Will be com_jspace.asset.
     * @param   JObject    $item     An instance of the JSpaceAsset class.
     * @param   bool       $isNew    True if the asset being saved is new, false otherwise.
     *
     * @return  bool       True if the asset is successfully saved, false otherwise.
     *
     * @throws  Exception  Thrown if the asset cannot be saved for any reason.
     */
    public function onJSpaceAfterSave($context, $item, $isNew)
    {
        if ($context != 'com_jspace.asset') {
            return true;
        }

        $root = $this->get('params')->get('path', null);
        $id = $item->id;

        $path = AssetHelper::buildStoragePath($item->record_id, $root);

        if (!JFolder::create($path)) {
            throw new Exception(JText::sprintf("PLG_JSPACE_LOCALSTORE_ERROR_CREATE_STORAGE_PATH", $path));
        }

        if (!JFile::copy($item->tmp_name, $path.$item->hash)) {
            throw new Exception(JText::sprintf("PLG_JSPACE_LOCALSTORE_ERROR_COPY_FILE", $item->tmp_name));
        }

        return true;
    }

    /**
     * Deletes a record's file assets from the configured file system.
     *
     * @param   string   $context  The context of the content being passed. Will be com_jspace.asset.
     * @param   JObject  $item    An instance of the JSpaceAsset class.
     */
    public function onJSpaceBeforeDelete($context, $item)
    {
        if ($context != 'com_jspace.asset') {
            return true;
        }

        $root = $this->get('params')->get('path', null);

        $storage = AssetHelper::buildStoragePath($item->record_id, $root);

        $path = $storage.$item->hash;

        try {
            if (JFile::exists($path)) {
                if (!JFile::delete($path)) {
                    JLog::add(__METHOD__.' '.JText::sprintf('PLG_JSPACE_LOCALSTORE_WARNING_FILEDELETEFAILED', json_encode($item).", path=".$path), JLog::WARNING, 'jspace');
                }
            } else {
                JLog::add(__METHOD__.' '.JText::sprintf('PLG_JSPACE_LOCALSTORE_WARNING_FILEDOESNOTEXIST', json_encode($item).", path=".$path), JLog::WARNING, 'jspace');
            }

            // Cleanup; try to delete as much of the path as possible.
            $empty = true;

            do {
                $array = explode('/', $storage);
                array_pop($array);
                $storage = implode('/', $array);

                // once we hit a directory with files or the configured archive dir, stop.
                if (JFolder::files($storage) || $storage.'/' == AssetHelper::preparePath($root)) {
                    $empty = false;
                } else {
                    JFolder::delete($storage);
                }
            } while ($empty);
        } catch (Exception $e) {
            JLog::add($e->getMessage(), JLog::ERROR, 'jspace');
        }
    }

    public function onJSpacePollStorage()
    {
        $poll = new JObject;

        $poll->set('name', JText::_('plg_'.$this->_type.'_'.$this->_name));

        $config = array();
        $errors = array();

        $path = AssetHelper::preparePath($this->params->get('path'));

        if (JFolder::exists($path)) {
            $config['path'] = $path;

            if (JString::strpos($path, JPATH_ROOT) === 0) {
                $errors[] = JText::_('PLG_JSPACE_LOCALSTORE_PATH_NOT_RECOMMENDED');
            }
        } else {
            $errors[] = JText::_('PLG_JSPACE_LOCALSTORE_PATH_NOT_EXISTS');
        }

        $poll->set('config', $config);
        $poll->set('errors', $errors);

        return $poll;
    }
}
