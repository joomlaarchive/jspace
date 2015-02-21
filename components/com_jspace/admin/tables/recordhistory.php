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
class JSpaceTableRecordHistory extends JTableContenthistory
{
    protected $hierarchicalVersionData = null;

    /**
     * Adds flattened metadata to the content history.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  boolean  True on success.
     */
    public function store($updateNulls = false)
    {
        $this->set('hierarchicalVersionData', $this->get('version_data'));

        $data = json_decode($this->get('hierarchicalVersionData'));

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
        $data->metadatapairs = json_encode($registry);

        $this->set('version_data', json_encode($data));

        return parent::store($updateNulls);
    }

    public function get($property, $default = null)
    {
        if ($property == 'version_data' && $this->hierarchicalVersionData != null)
        {
            return $this->hierarchicalVersionData;
        }

        return parent::get($property, $default);
    }
}
