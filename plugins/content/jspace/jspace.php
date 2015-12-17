<?php
/**
 * @copyright   Copyright (C) 2015 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

\JLoader::import('joomla.filesystem.folder');

/**
 * Integrates JSpace functionality into an article.
 *
 * @package  JSpace.Plugin
 */
class PlgContentJSpace extends JPlugin
{
    protected $autoloadLanguage = true;

    public function onContentPrepareData($context, $data)
    {
        if (!in_array($context, array('com_content.article'))) {
            return true;
        }
    }

    public function onContentPrepareForm($form, $data)
    {
        if (!($form instanceof JForm)) {
            $this->_subject->setError('JERROR_NOT_A_FORM');

            return false;
        }

        $name = $form->getName();

        if (!in_array($name, array('com_content.article'))) {
            return true;
        }

        JForm::addFormPath(__DIR__ . '/forms');

        $app = JFactory::getApplication();

        $form->loadFile('jspace', false);

        return true;
    }
}
