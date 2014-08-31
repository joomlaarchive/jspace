<?php
/**
 * @package    JSpace.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('jspace.table.reference');

/**
 * Stores weblinks in the Joomla! Weblinks component.
 *
 * @package  JSpace.Plugin
 */
class PlgContentJSpaceWeblinks extends JPlugin
{
	/**
	 * Instatiates an instance of the PlgContentJSpaceWeblinks class.
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
        if ($context != 'com_jspace.record')
        {
            return true;
        }
        
        if (!$data)
        {
            return true;
        }

        if ($data->id)
        {
            $database = JFactory::getDbo();
            $query = $database->getQuery(true);
            
            $query
                ->select(array('a.*', 'b.*'))
                ->from($database->qn('#__jspace_references', 'a'))
                ->join('INNER', $database->qn('#__weblinks', 'b').' ON '.$database->qn('b.id').'='.$database->qn('a.id'))
                ->where($database->qn('context').'='.$database->q('com_weblinks.weblink'))
                ->where($database->qn('a.record_id').'='.$data->id);
            
            $weblinks = $database->setQuery($query)->loadAssocList();
            
            // restructure weblinks in a format the WebLinkList form field will understand.
            $data->weblinks = array();
            for ($i=0; $i < count($weblinks); $i++)
            {
                $data->weblinks[$weblinks[$i]['bundle']][] = $weblinks[$i];
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
    public function onJSpaceRecordAfterValidate($form, $data, $group = null)
    {
        //JFactory::getApplication()->enqueueMessage(JText::_('PLG_JSPACE_WEBLINKS_ERROR_INVALID'), 'error');
        return true;
    }
	
	/**
	 * Saves a record's weblinks in the Joomla! weblinks component.
	 *
	 * @param   string   $context  The context of the content being passed. Will be com_jspace.record.
	 * @param   JObject  $record   An instance of the JSpaceRecord class.
     * @param   bool     $isNew    True if the record being saved is new, false otherwise.
	 *
	 * @return  bool     True if the weblinks are successfully saved, false otherwise.
	 */
	public function onContentAfterSave($context, $record, $isNew)
	{
        if ($context != 'com_jspace.record')
        {
            return true;
        }
	
		if (!isset($record->weblinks))
		{
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
            ->where($database->qn('record_id').'='.$record->id);
        
        $ids = $database->setQuery($query)->loadColumn();
        
		foreach ($record->weblinks as $wkey=>$weblink)
		{
            foreach ($weblink as $data)
            {
                // ignore empty urls.
                if (!$data['url'])
                {
                    continue;
                }
                
                $weblink = JTable::getInstance('Weblink', 'WeblinksTable');
                
                $weblink->id = JArrayHelper::getValue($data, 'id', null);
                $weblink->url = JArrayHelper::getValue($data, 'url');
                $weblink->title = JArrayHelper::getValue($data, 'title', $data['url']);
                $weblink->alias = JFilterOutput::stringURLSafe($data['title']);
                $weblink->catid = $this->params->get('catid', null);
                $weblink->state = 1;
                $weblink->access = $record->access;
                $weblink->language = $record->language;

                if (!$weblink->store())
                {
                    throw new Exception($weblink->getError());
                }
                
                $reference = JTable::getInstance('Reference', 'JSpaceTable');
                
                $reference->id = $weblink->id;
                $reference->context = 'com_weblinks.weblink';
                $reference->bundle = $wkey;
                $reference->record_id = $record->id;
                
                if (!$reference->store())
                {
                    throw new Exception($reference->getError());
                }
                
                if (($index = array_search(JArrayHelper::getValue($data, 'id', null), $ids)) !== false)
                {
                    unset($ids[$index]);
                }
			}
		}
		
		foreach ($ids as $id)
		{
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
	 * @param   Object  $record  An instance of the JSpaceRecord class.
	 */
	public function onContentBeforeDelete($context, $record)
	{
        if ($context == 'com_weblinks.weblink')
        {
            $reference = JTable::getInstance('Reference', 'JSpaceTable');
            $reference->delete($record->id);
        }
        else if ($context == 'com_jspace.record')
        {        
            $path = JPATH_ROOT.'/administrator/components/com_weblinks/';
            JTable::addIncludePath($path.'tables');

            $database = JFactory::getDbo();
            $query = $database->getQuery(true);
            
            //@TODO Is it safe to delete web links here? What happens to history?
            $query = 'DELETE r, w FROM '.$database->qn('#__jspace_references', 'r').' '.
                     'INNER JOIN '.$database->qn('#__weblinks', 'w').' ON r.id = w.id '.
                     'WHERE '.$database->qn('r.context').'='.$database->q('com_weblinks.weblink').' '.
                     'AND '.$database->qn('r.record_id').'='.(int)$record->id;

            $database->setQuery($query)->execute();
        }
        
        return true;
	}
}