<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Helper
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Versions helper class, provides methods to perform various tasks relevant
 * versioning of records.
 *
 * @package     JSpace
 * @subpackage  Helper
 */
class JSpaceHelperRecordhistory extends JHelperContenthistory
{
    /**
     * Method to save a version snapshot to the content history table.
     *
     * @param   JTable  $table  JTable object being versioned
     *
     * @return  boolean  True on success, otherwise false.
     *
     * @since   3.2
     */
    public function store($table)
    {
        $dataObject = $this->getDataObject($table);
        $historyTable = JTable::getInstance('Recordhistory', 'JSpaceTable');

        $typeTable = JTable::getInstance('Contenttype', 'JTable');
        $typeTable->load(array('type_alias' => $this->typeAlias));
        $historyTable->set('ucm_type_id', $typeTable->type_id);

        $key = $table->getKeyName();
        $historyTable->set('ucm_item_id', $table->$key);

        // Don't store unless we have a non-zero item id
        if (!$historyTable->ucm_item_id)
        {
            return true;
        }

        $historyTable->set('version_data', json_encode($dataObject));
        $input = JFactory::getApplication()->input;
        $data = $input->get('jform', array(), 'array');
        $versionName = false;

        if (isset($data['version_note']))
        {
            $versionName = JFilterInput::getInstance()->clean($data['version_note'], 'string');
            $historyTable->set('version_note', $versionName);
        }

        // Don't save if hash already exists and same version note
        $historyTable->set('sha1_hash', $historyTable->getSha1($dataObject, $typeTable));

        if ($historyRow = $historyTable->getHashMatch())
        {
            if (!$versionName || ($historyRow->version_note == $versionName))
            {
                return true;
            }
            else
            {
                // Update existing row to set version note
                $historyTable->set('version_id', $historyRow->version_id);
            }
        }

        $result = $historyTable->store();

        if ($maxVersions = JComponentHelper::getParams('com_content')->get('history_limit', 0))
        {
            $historyTable->deleteOldVersions($maxVersions);
        }

        return $result;
    }
}
