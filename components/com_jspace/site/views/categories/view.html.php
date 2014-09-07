<?php
/**
 * @package     JSpace.Component
 * @subpackage  View
 * @copyright   Copyright (C) 2014 Wijiti Pty Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 *
 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 * Name                         Email
 * Hayden Young                 <haydenyoung@wijiti.com> 
 * Michał Kocztorz              <michalkocztorz@wijiti.com>
 */
 
defined('_JEXEC') or die('Restricted access');
 
jimport('joomla.application.component.view');

/**
 * A view for listing JSpace categories.
 *
 * @package     JSpace.Component
 * @subpackage  View
 */
class JSpaceViewCategories extends JViewCategories
{
    protected $item = null;

    public function __construct($config = array())
    {
        parent::__construct($config);
        
        $this->pageHeading = 'COM_JSPACE_DEFAULT_PAGE_TITLE';
    }
    
    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise a Error object.
     */
    public function display($tpl = null)
    {
        $state      = $this->get('State');
        $items      = $this->get('Items');
        $parent     = $this->get('Parent');

        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            JError::raiseWarning(500, implode("\n", $errors));
            return false;
        }

        if ($items === false)
        {
            return JError::raiseError(404, JText::_('JGLOBAL_CATEGORY_NOT_FOUND'));
        }

        if ($parent == false)
        {
            return JError::raiseError(404, JText::_('JGLOBAL_CATEGORY_NOT_FOUND'));
        }

        $params = &$state->params;

        $items = array($parent->id => $items);

        // Escape strings for HTML output
        $this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx'));

        $this->maxLevelcat = $params->get('maxLevelcat', -1);
        $this->params = &$params;
        $this->parent = &$parent;
        $this->items  = &$items;

        return parent::display($tpl);
    }
}