<?php
/**
 * @package     JSpace.Component
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');
jimport('jspace.ingestion.harvest');

/**
 * Models a control panel containing various JSpace information.
 *
 * @package     JSpace.Component
 * @subpackage  Model
 */
class JSpaceModelCPanel extends JModelLegacy
{
    public function getItem()
    {
        $database = JFactory::getDbo();
        $query = $database->getQuery(true);

        $query
            ->select(array('published', 'COUNT(id) AS total'))
            ->from($database->qn('#__jspace_records'))
            ->group($database->qn('published'));

        $item = new JObject;

        $item->set('records', $database->setQuery($query)->loadObjectList());

        $database = JFactory::getDbo();
        $query = $database->getQuery(true);

        $query
            ->select(array('COUNT(id) AS total', 'SUM(contentLength) AS size'))
            ->from($database->qn('#__jspace_assets'));

        $item->set('assets', $database->setQuery($query)->loadObject());

        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin("content", null, true, $dispatcher);

        $polled = $dispatcher->trigger('onJSpacePollStorage', array());

        $item->set('storage', $polled);

        $item->set('harvesting', JFolder::exists(JPATH_ROOT.'/plugins/content/harvest'));

        return $item;
    }
}