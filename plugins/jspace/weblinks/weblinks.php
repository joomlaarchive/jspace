<?php
/**
 * @package    JSpace.Plugin
 *
 * @copyright   Copyright (C) 2014-2015 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

\JTable::addIncludePath(JPATH_ROOT.'/administrator/components/com_jspace/tables/');

/**
 * Stores weblinks in the Joomla! Weblinks component.
 *
 * @package  JSpace.Plugin
 */
class PlgJSpaceWeblinks extends JPlugin
{
	/**
	 * Instatiates an instance of the PlgJSpaceWeblinks class.
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
	 * Prepares the JSpace form for including web link fields.
     *
     * @param   JForm    $form   The form being prepared.
     * @param   JObject  $data  The form data.
     *
     * @return  bool     True if the form is prepared, false otherwise.
     */
	public function onContentPrepareForm($form, $data)
	{
		$form->addFieldPath(JPATH_PLUGINS."/content/jspaceweblinks/fields");
		return true;
	}

    /**
	 * Fetches the weblinks associated with the record and attaches it to the form data.
     *
     * @param   string  $context  The context of the content being passed. Will be com_jspace.record.
     * @param   JObject $data     The form data.
     *
     * @return  bool    True if the data is prepared, false otherwise.
     */
    public function onContentPrepareData($context, $data)
    {
        if ($context != 'com_jspace.record') {
            return true;
        }

        if (!$data) {
            return true;
        }

        if ($data->id) {
            $database = JFactory::getDbo();
            $query = $database->getQuery(true);

            $query
                ->select(array('a.*', 'b.*'))
                ->from($database->qn('#__jspace_references', 'a'))
                ->join('INNER', $database->qn('#__weblinks', 'b').' ON '.$database->qn('b.id').'='.$database->qn('a.id'))
                ->where($database->qn('context').'='.$database->q('com_weblinks.weblink'))
                ->where($database->qn('a.record_id').'='.$data->id);

            $weblinks = $database->setQuery($query)->loadAssocList();

            // restructure weblinks; $data->[component without com_][view]
            $data->weblinks = array('weblink'=>array());

            for ($i=0; $i < count($weblinks); $i++) {
                $data->weblinks['weblink'][] = $weblinks[$i];
            }
        }
    }

    /**
     * Checks for for existing web links with the same alias.
     *
     * @param  JForm  $form
     * @param  array  $data
     * @param  array  $group
     */
    public function onJSpaceAfterValidate($form, $data, $group = null)
    {
        //JFactory::getApplication()->enqueueMessage(JText::_('PLG_JSPACE_WEBLINKS_ERROR_INVALID'), 'error');
        return true;
    }

	/**
	 * Saves a record's weblinks in the Joomla! weblinks component.
	 *
	 * @param   string   $context  The context of the content being passed. Will be com_jspace.record.
	 * @param   JObject  $item     A derivative of the JObject class.
     * @param   bool     $isNew    True if the record being saved is new, false otherwise.
	 *
	 * @return  bool     True if the weblinks are successfully saved, false otherwise.
	 */
	public function onJSpaceAfterSave($context, $item, $isNew)
	{
        if ($context != 'com_jspace.record') {
            return true;
        }

		if (!isset($item->weblinks)) {
			return true;
		}

        $path = JPATH_ROOT.'/administrator/components/com_weblinks/';
        JTable::addIncludePath($path.'tables');

        $database = JFactory::getDbo();
        $query = $database->getQuery(true);

        $query
            ->select(array('a.id'))
            ->from($database->qn('#__jspace_references', 'a'))
            ->join('INNER', $database->qn('#__weblinks', 'b').' ON '.$database->qn('b.id').'='.$database->qn('a.id'))
            ->where($database->qn('context').'='.$database->q('com_weblinks.weblink'))
            ->where($database->qn('record_id').'='.$item->id);

        $ids = $database->setQuery($query)->loadColumn();

        foreach ($item->weblinks as $link) {
            foreach ($link as $key=>$data) {
                // ignore empty urls.
                if (!JArrayHelper::getValue($data, 'url')) {
                    continue;
                }

                $weblink = JTable::getInstance('Weblink', 'WeblinksTable');
                $weblink->load(JArrayHelper::getValue($data, 'id'));

                $weblink->url = JArrayHelper::getValue($data, 'url');
                $weblink->catid = $this->params->get('catid', null);
                $weblink->title = JArrayHelper::getValue($data, 'title', $weblink->url);
                $weblink->alias = (int)$item->id.'-'.JFilterOutput::stringURLSafe($weblink->title);
                $weblink->state = 1;
                $weblink->access = $item->access;
                $weblink->language = $item->language;

                if (!$weblink->store()) {
                    throw new Exception($weblink->getError());
                }

                $reference = JTable::getInstance('Reference', 'JSpaceTable');

                $reference->id = $weblink->id;
                $reference->context = 'com_weblinks.weblink';
                $reference->record_id = $item->id;

                if (!$reference->store()) {
                    throw new Exception($reference->getError());
                }

                if (($index = array_search(JArrayHelper::getValue($data, 'id', null), $ids)) !== false) {
                    unset($ids[$index]);
                }
            }
        }

        foreach ($ids as $id) {
            $reference = JTable::getInstance('Reference', 'JSpaceTable');
            $reference->delete($id);

            $weblink = JTable::getInstance('Weblink', 'WeblinksTable');
            $weblink->delete($id);
        }

        return true;
    }

    /**
     * Deletes a record's weblinks from the Joomla! weblinks component.
     *
     * @param   string  $context  The context of the content being passed. Will be com_jspace.record * or com_weblinks.weblink.
	 * @param   Object  $item  A derivative of the JObject class.
	 */
	public function onJSpaceBeforeDelete($context, $item)
	{
        $path = JPATH_ROOT.'/administrator/components/com_jspace/';
        JTable::addIncludePath($path.'tables');

        $reference = JTable::getInstance('Reference', 'JSpaceTable');

        if ($context == 'com_weblinks.weblink') {
            if ($reference->load($item->id)) {
                $reference->delete($item->id);
            }
        } else if ($context == 'com_jspace.record') {
            $path = JPATH_ROOT.'/administrator/components/com_weblinks/';
            JTable::addIncludePath($path.'tables');

            $weblink = JTable::getInstance('Weblink', 'WeblinksTable');

            $database = JFactory::getDbo();
            $query = $database->getQuery(true);
            $query->select('w.id')->from($database->qn('#__weblinks', 'w'))->join('inner', $database->qn('#__jspace_references', 'r').' ON `w`.`id`=`r`.`id`')->where($database->qn('r.record_id').'='.(int)$item->id)->where($database->qn('r.context').'='.$database->q('com_weblinks.weblink'));

            foreach ($database->setQuery($query)->loadColumn() as $id) {
                $reference->delete(array('id'=>$id, 'context'=>'com_weblinks.weblink'));
                $weblink->delete($id);
            }
        }

        return true;
	}
}