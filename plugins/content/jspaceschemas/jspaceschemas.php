<?php
/**
 * @package     JSpace.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

/**
 * Provides schema configuration options per JSpace category.
 *
 * @package     JSpace.Plugin
 */
class PlgContentJSpaceSchemas extends JPlugin
{
    /**
     * Instatiates an instance of the PlgContentJSpaceSchemas class.
     *
     * @param   object  &$subject  The object to observe
     * @param   array   $config    An optional associative array of configuration settings.
     *                             Recognized key values include 'name', 'group', 'params', 'language'
     *                             (this list is not meant to be comprehensive).
     */
    public function __construct($subject, $config = array())
    {   
        parent::__construct($subject, $config);
        $this->loadLanguage();
        
        JLog::addLogger(array());
    }
    
    public function onContentPrepareForm($form, $data)
    {
        if (!($form instanceof JForm))
        {
            $this->_subject->setError('JERROR_NOT_A_FORM');
            return false;
        }

        $name = $form->getName();
        
        if (!in_array($name, array('com_categories.categorycom_jspace')))
        {
            return true;
        }

        JForm::addFormPath(__DIR__.'/forms');
        $form->loadFile('jspaceschemas', false);
        return true;
    }
}