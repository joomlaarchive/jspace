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

 defined('_JEXEC') or die;

/**
 * A view for displaying a JSpace category and its records.
 *
 * @package     JSpace.Component
 * @subpackage  View
 */
class JSpaceViewCategory extends JViewCategory
{
    /**
     * @var    array  Array of leading items for blog display
     * @since  3.2
     */
    protected $lead_items = array();

    /**
     * @var    array  Array of intro (multicolumn display) items for blog display
     * @since  3.2
     */
    protected $intro_items = array();

    /**
     * @var    array  Array of links in blog display
     * @since  3.2
     */
    protected $link_items = array();

    /**
     * @var    integer  Number of columns in a multi column display
     * @since  3.2
     */
    protected $columns = 1;

    /**
     * @var    string  The name of the extension for the category
     * @since  3.2
     */
    protected $extension = 'com_jspace';

    /**
     * @var    string  Default title to use for page title
     * @since  3.2
     */
    protected $defaultPageTitle = 'JGLOBAL_ARTICLES';

    /**
     * @var    string  The name of the view to link individual items to
     * @since  3.2
     */
    protected $viewName = 'record';

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise a Error object.
     */
    public function display($tpl = null)
    {
        parent::commonCategoryDisplay();

        // Prepare the data
        // Get the metrics for the structural page layout.
        $params     = $this->params;
        $numLeading = $params->def('num_leading_records', 1);
        $numIntro   = $params->def('num_intro_records', 4);
        $numLinks   = $params->def('num_links', 4);

        // Compute the record slugs and prepare introtext (runs content plugins).
        foreach ($this->items as $item)
        {
            $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;

            $item->parent_slug = ($item->parent_alias) ? ($item->parent_id . ':' . $item->parent_alias) : $item->parent_id;

            // No link for ROOT category
            if ($item->parent_alias == 'root')
            {
                $item->parent_slug = null;
            }

            $item->catslug = $item->category_alias ? ($item->catid.':'.$item->category_alias) : $item->catid;
        }

        // Check for layout override only if this is not the active menu item
        // If it is the active menu item, then the view and category id will match
        $app = JFactory::getApplication();
        $active = $app->getMenu()->getActive();

        if ((!$active) || ((strpos($active->link, 'view=category') === false) || (strpos($active->link, '&id=' . (string) $this->category->id) === false)))
        {
            // Get the layout from the merged category params
            if ($layout = $this->category->params->get('category_layout'))
            {
                $this->setLayout($layout);
            }
        }
        // At this point, we are in a menu item, so we don't override the layout
        elseif (isset($active->query['layout']))
        {
            // We need to set the layout from the query in case this is an alternative menu item (with an alternative layout)
            $this->setLayout($active->query['layout']);
        }

        // For blog layouts, preprocess the breakdown of leading, intro and linked records.
        // This makes it much easier for the designer to just interrogate the arrays.
        if (($params->get('layout_type') == 'blog') || ($this->getLayout() == 'blog'))
        {
            //$max = count($this->items);

            foreach ($this->items as $i => $item)
            {
                if ($i < $numLeading)
                {
                    $this->lead_items[] = $item;
                }

                elseif ($i >= $numLeading && $i < $numLeading + $numIntro)
                {
                    $this->intro_items[] = $item;
                }

                elseif ($i < $numLeading + $numIntro + $numLinks)
                {
                    $this->link_items[] = $item;
                }
                else
                {
                    continue;
                }
            }

            $this->columns = max(1, $params->def('num_columns', 1));

            $order = $params->def('multi_column_order', 1);

            if ($order == 0 && $this->columns > 1)
            {
                // call order down helper
                $this->intro_items = ContentHelperQuery::orderDownColumns($this->intro_items, $this->columns);
            }
        }

        return parent::display($tpl);
    }

    /**
     * Prepares the document
     *
     * @return  void
     */
    protected function prepareDocument()
    {
        parent::prepareDocument();
        $menu = $this->menu;
        $id = (int) @$menu->query['id'];

        if ($menu && ($menu->query['option'] != 'com_jspace' || $menu->query['view'] == 'record' || $id != $this->category->id))
        {
            $path = array(array('title' => $this->category->title, 'link' => ''));
            $category = $this->category->getParent();

            while (($menu->query['option'] != 'com_jspace' || $menu->query['view'] == 'record' || $id != $category->id) && $category->id > 1)
            {
                $path[] = array('title' => $category->title, 'link' => ContentHelperRoute::getCategoryRoute($category->id));
                $category = $category->getParent();
            }

            $path = array_reverse($path);

            foreach ($path as $item)
            {
                $this->pathway->addItem($item['title'], $item['link']);
            }
        }

        parent::addFeed();
    }
}