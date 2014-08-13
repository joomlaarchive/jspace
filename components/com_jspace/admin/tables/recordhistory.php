<?php
/**
 * @package     JSpace
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Content History table.
 *
 * @package     JSpace
 * @subpackage  Table
 */
class JSpaceTableRecordhistory extends JTableContenthistory
{
    /**
     * Overrides JTable::store to set modified hash, user id, and save date.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  boolean  True on success.
     *
     * @since   3.2
     */
    public function store($updateNulls = false)
    {
        $this->set('character_count', strlen($this->get('version_data')));

        $this->_addCSVMetadata();
        
        $typeTable = JTable::getInstance('Contenttype');
        $typeTable->load($this->ucm_type_id);

        if (!isset($this->sha1_hash))
        {
            $this->set('sha1_hash', $this->getSha1($this->get('version_data'), $typeTable));
        }

        $this->set('editor_user_id', JFactory::getUser()->id);
        $this->set('save_date', JFactory::getDate()->toSql());

        return parent::store($updateNulls);
    }
    
    private function _addCSVMetadata()
    {
        $data = json_decode($this->get('version_data'));
        
        $metadata = json_decode($data->metadata);
        $registry = new JRegistry();
        $registry->loadObject($metadata);
        $metadata = $registry->toArray();
        
        foreach ($metadata as $key=>$value)
        {
            if (is_array($value))
            {
                $metadata[$key] = implode(',', $value);
            }
        }

        $registry = new JRegistry();
        $registry->loadArray($metadata);
        $data->csvmetadata = json_encode($registry);
        
        $this->set('version_data', json_encode($data));
    }
}
