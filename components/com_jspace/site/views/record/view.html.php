<?php
/**
 * @package     JSpace.Component
 * @subpackage  View
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
 * A view for displaying a JSpace record, its children, assets and references.
 *
 * @package     JSpace.Component
 * @subpackage  View
 */
class JSpaceViewRecord extends JViewLegacy
{
    protected $item;

    protected $params;

    protected $print;

    protected $state;

    protected $user;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise a Error object.
     */
    public function display($tpl = null)
    {
        $app        = JFactory::getApplication();
        $user       = JFactory::getUser();
        $dispatcher = JEventDispatcher::getInstance();

        $this->item     = $this->get('Item');
        $this->print    = $app->input->getBool('print');
        $this->state    = $this->get('State');
        $this->user     = $user;
        
        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            JError::raiseWarning(500, implode("\n", $errors));
            return false;
        }

        // Create a shortcut for $item.
        $item = $this->item;
        $item->tagLayout = new JLayoutFile('joomla.content.tags');

        // Add router helpers.
        $this->slug = $item->id.':'.$item->alias;
        $this->catslug = $item->getCategory() ? ($item->catid.':'.$item->getCategory()->alias) : null;
        $this->parentslug  = $item->getParent() ? ($item->parent_id . ':' . $item->getParent()->alias) : null;

        // No link for ROOT category
        if ($item->getParent() && $item->getParent()->alias == 'root')
        {
            $this->parentslug = null;
        }

        // Merge record params. If this is single-record view, menu params override record params
        // Otherwise, record params override menu item params
        $this->params = $this->state->get('params');
        $active = $app->getMenu()->getActive();
        $temp = clone ($this->params);

        // Check to see which parameters should take priority
        if ($active)
        {
            $currentLink = $active->link;

            // If the current view is the active item and an record view for this record, then the menu item params take priority
            if (strpos($currentLink, 'view=record') && (strpos($currentLink, '&id='.(string) $item->id)))
            {
                // Load layout from active query (in case it is an alternative menu item)
                if (isset($active->query['layout']))
                {
                    $this->setLayout($active->query['layout']);
                }
                // Check for alternative layout of record
                elseif ($layout = $item->params->get('record_layout'))
                {
                    $this->setLayout($layout);
                }

                // $item->params are the record params, $temp are the menu item params
                // Merge so that the menu item params take priority
                $item->params->merge($temp);
            }
            else
            {
                // Current view is not a single record, so the record params take priority here
                // Merge the menu item params with the record params so that the record params take priority
                $temp->merge($item->params);
                $item->params = $temp;

                // Check for alternative layouts (since we are not in a single-record menu item)
                // Single-record menu item layout takes priority over alt layout for an record
                if ($layout = $item->params->get('record_layout'))
                {
                    $this->setLayout($layout);
                }
            }
        }
        else
        {
            // Merge so that record params take priority
            $temp->merge($item->params);
            $item->params = $temp;

            // Check for alternative layouts (since we are not in a single-record menu item)
            // Single-record menu item layout takes priority over alt layout for an record
            if ($layout = $item->params->get('record_layout'))
            {
                $this->setLayout($layout);
            }
        }

        // Check the view access to the record (the model has already computed the values).
        if ($item->params->get('access-view') == false && ($item->params->get('show_noauth', '0') == '0'))
        {
            JError::raiseWarning(403, JText::_('JERROR_ALERTNOAUTHOR'));
            return;
        }

        //Escape strings for HTML output
        $this->pageclass_sfx = htmlspecialchars($this->item->params->get('pageclass_sfx'));

        $this->_prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document
     */
    protected function _prepareDocument()
    {
        $app        = JFactory::getApplication();
        $menus      = $app->getMenu();
        $pathway    = $app->getPathway();
        $title      = null;

        // Because the application sets a default page title,
        // we need to get it from the menu item itself
        $menu = $menus->getActive();

        if ($menu)
        {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        }
        else
        {
            $this->params->def('page_heading', JText::_('JGLOBAL_RECORDS'));
        }

        $title = $this->params->get('page_title', '');

        $id = (int) @$menu->query['id'];

        // if the menu item does not concern this record
        if ($menu && ($menu->query['option'] != 'com_jspace' || $menu->query['view'] != 'record' || $id != $this->item->id))
        {
            // If this is not a single record menu item, set the page title to the record title
            if ($this->item->title)
            {
                $title = $this->item->title;
            }
            $path = array(array('title' => $this->item->title, 'link' => ''));
            $category = JCategories::getInstance('JSpace')->get($this->item->catid);

            while ($category && ($menu->query['option'] != 'com_jspace' || $menu->query['view'] == 'record' || $id != $category->id) && $category->id > 1)
            {
                $path[] = array('title' => $category->title, 'link' => JSpaceHelperRoute::getCategoryRoute($category->id));
                $category = $category->getParent();
            }
            $path = array_reverse($path);

            foreach ($path as $item)
            {
                $pathway->addItem($item['title'], $item['link']);
            }
        }

        // Check for empty title and add site name if param is set
        if (empty($title))
        {
            $title = $app->get('sitename');
        }
        elseif ($app->get('sitename_pagetitles', 0) == 1)
        {
            $title = JText::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        }
        elseif ($app->get('sitename_pagetitles', 0) == 2)
        {
            $title = JText::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        if (empty($title))
        {
            $title = $this->item->title;
        }
        
        $this->document->setTitle($title);

        foreach ($this->item->get('metadata')->toArray() as $a=>$b)
        {
            if (is_array($b))
            {
                foreach ($b as $x)
                {
                    $this->document->setMetadata($a, $x);
                }
            }
            else if (is_string($b))
            {
                $this->document->setMetadata($a, $b);
            }
        }
    }
    
    public function getDownloadLinks($asset)
    {
        $dispatcher = JEventDispatcher::getInstance();
        
        JPluginHelper::importPlugin("content");
        
        return $dispatcher->trigger('onJSpaceAssetPrepareDownload', array($asset));
    }
}