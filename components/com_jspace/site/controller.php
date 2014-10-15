<?php
/**
 * @package     JSpace.Component
 * @subpackage  Controller
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 *
 * Contributors
 * Please feel free to add your name and email (optional) here if you have
 * contributed any source code changes.
 *
 * Name                         Email
 * Hayden Young                 <haydenyoung@wijiti.com>
 */

defined('_JEXEC') or die;

/**
 * JSpace Component Controller
 *
 * @package     JSpace.Component
 * @subpackage  Model
 */
class JSpaceController extends JControllerLegacy
{
    /**
     * Method to display a view.
     *
     * @param   boolean      If true, the view output will be cached
     * @param   array        An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return  JController  This object to support chaining.
     */
    public function display($cachable = false, $urlparams = false)
    {
        $cachable = true;
        $user = JFactory::getUser();
        $id = $this->input->getInt('w_id');
        $vName = $this->input->get('view', 'categories');

        $this->input->set('view', $vName);

        if ($user->get('id') ||($this->input->getMethod() == 'POST' && $vName = 'categories'))
        {
            $cachable = false;
        }

        $safeurlparams = array(
            'id'                => 'INT',
            'limit'             => 'UINT',
            'limitstart'        => 'UINT',
            'filter_order'      => 'CMD',
            'filter_order_Dir'  => 'CMD',
            'lang'              => 'CMD'
        );

        if ($vName == 'form' && !$this->checkEditId('com_jspace.edit.record', $id))
        {
            return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
        }

        return parent::display($cachable, $safeurlparams);
    }
}