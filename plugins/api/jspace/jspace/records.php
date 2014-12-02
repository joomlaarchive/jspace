<?php
defined('_JEXEC') or die;

use \JSpace\Archive\Record;

class JSpaceApiResourcerecords extends ApiResource
{
    /**
     * Gets an item based on the id.
     */
    public function get()
    {
        $id = JFactory::getApplication()->input->get('id', 0, 'int');

        if ($id)
        {
            $result = Record::getTree($id);
        }
        else
        {
            // just return a flat list.
            $database = JFactory::getDbo();
            $query = $database->getQuery(true);

            JTable::addIncludePath(JPATH_ROOT.'/administrator/components/com_jspace/tables');

            $table = JTable::getInstance('Record', 'JSpaceTable');
            $fields = array_keys($table->getFields());

            $select = array();

            foreach ($fields as $field)
            {
                $select[] = $database->qn($field);
            }

            $query
                ->select($select)
                ->from($database->qn($table->getTableName()))
                ->where("NOT `alias` = 'root'");

            $start = JFactory::getApplication()->input->get('start', 0);
            $limit = JFactory::getApplication()->input->get('limit', 10);

            if ($limit > 100)
            {
                throw new Exception('Limit cannot be any more than 100.', 404);
            }

            $result = $database->setQuery($query, $start, $limit)->loadObjectList('id');
        }

        $this->plugin->setResponse(array($result));
    }
}