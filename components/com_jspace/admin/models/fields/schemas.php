<?php
defined('_JEXEC') or die('Restricted access');

jimport('jspace.archive.schema');
 
class JSpaceFormFieldSchemas extends JFormField
{
    protected $type = 'JSpace.Schemas';
    
    /**
     * Get a list of the user groups.
     *
     * @return  array
     */
    public function getUserGroups()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('a.id AS value, a.title AS text, COUNT(DISTINCT b.id) AS level, a.parent_id')
            ->from('#__usergroups AS a')
            ->join('LEFT', $db->quoteName('#__usergroups') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt')
            ->group('a.id, a.title, a.lft, a.rgt, a.parent_id')
            ->order('a.lft ASC');
        $db->setQuery($query);
        $options = $db->loadObjectList();

        return $options;
    }
    
    public function getCategoryId()
    {
        return JFactory::getApplication()->input->get('id');
    }

    /**
     * Method to get the field input markup for Schema access.
     *
     * @return  string  The field input markup.
     */
    protected function getInput()
    {
        JHtml::_('bootstrap.tooltip');

        return JLayoutHelper::render("jspace.form.fields.schemas", $this, JPATH_ROOT.'/administrator/components/com_jspace/layouts');
    }
}