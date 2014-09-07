<?php
/**
 * Display JSpace category items.
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

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

// Create some shortcuts.
$params     = &$this->item->params;
$n          = count($this->items);
$listOrder  = $this->escape($this->state->get('list.ordering'));
$listDirn   = $this->escape($this->state->get('list.direction'));

// Check for at least one editable article
$isEditable = false;

if (!empty($this->items))
{
    foreach ($this->items as $item)
    {
        if ($item->params->get('access-edit'))
        {
            $isEditable = true;
            break;
        }
    }
}
?>

<?php if (empty($this->items)) : ?>

    <?php if ($this->params->get('show_no_records', 1)) : ?>
    <p><?php echo JText::_('COM_JSPACE_NO_RECORDS'); ?></p>
    <?php endif; ?>

<?php else : ?>

<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm" class="form-inline">
    <?php if ($this->params->get('show_headings') || $this->params->get('filter_field') != 'hide' || $this->params->get('show_pagination_limit')) :?>
    <fieldset class="filters btn-toolbar clearfix">
        <?php if ($this->params->get('filter_field') != 'hide') :?>
            <div class="btn-group">
                <label class="filter-search-lbl element-invisible" for="filter-search">
                    <?php echo JText::_('COM_JSPACE_'.$this->params->get('filter_field').'_FILTER_LABEL').'&#160;'; ?>
                </label>
                <input type="text" name="filter-search" id="filter-search" value="<?php echo $this->escape($this->state->get('list.filter')); ?>" class="inputbox" onchange="document.adminForm.submit();" title="<?php echo JText::_('COM_JSPACE_FILTER_SEARCH_DESC'); ?>" placeholder="<?php echo JText::_('COM_JSPACE_'.$this->params->get('filter_field').'_FILTER_LABEL'); ?>" />
            </div>
        <?php endif; ?>
        <?php if ($this->params->get('show_pagination_limit')) : ?>
            <div class="btn-group pull-right">
                <label for="limit" class="element-invisible">
                    <?php echo JText::_('JGLOBAL_DISPLAY_NUM'); ?>
                </label>
                <?php echo $this->pagination->getLimitBox(); ?>
            </div>
        <?php endif; ?>

        <input type="hidden" name="filter_order" value="" />
        <input type="hidden" name="filter_order_Dir" value="" />
        <input type="hidden" name="limitstart" value="" />
        <input type="hidden" name="task" value="" />
    </fieldset>
    <?php endif; ?>

    <table class="category table table-striped table-bordered table-hover">
        <?php if ($this->params->get('show_headings')) : ?>
        <thead>
            <tr>
                <th id="categorylist_header_title">
                    <?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                </th>
                <?php if ($date = $this->params->get('list_show_date')) : ?>
                    <th id="categorylist_header_date">
                        <?php if ($date == "created") : ?>
                            <?php echo JHtml::_('grid.sort', 'COM_JSPACE_'.$date.'_DATE', 'a.created', $listDirn, $listOrder); ?>
                        <?php elseif ($date == "modified") : ?>
                            <?php echo JHtml::_('grid.sort', 'COM_JSPACE_'.$date.'_DATE', 'a.modified', $listDirn, $listOrder); ?>
                        <?php elseif ($date == "published") : ?>
                            <?php echo JHtml::_('grid.sort', 'COM_JSPACE_'.$date.'_DATE', 'a.publish_up', $listDirn, $listOrder); ?>
                        <?php endif; ?>
                    </th>
                <?php endif; ?>
                <?php if ($this->params->get('list_show_author')) : ?>
                    <th id="categorylist_header_author">
                        <?php echo JHtml::_('grid.sort', 'JAUTHOR', 'author', $listDirn, $listOrder); ?>
                    </th>
                <?php endif; ?>
            </tr>
        </thead>
        <?php endif; ?>
        <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <?php if ($this->items[$i]->published == 0) : ?>
                 <tr class="system-unpublished cat-list-row<?php echo $i % 2; ?>">
                <?php else: ?>
                <tr class="cat-list-row<?php echo $i % 2; ?>" >
                <?php endif; ?>
                    <td headers="categorylist_header_title" class="list-title">
                        <?php echo str_repeat('<span class="gi">|&mdash;</span>', (int)$item->level-1) ?>
                        <?php if (in_array($item->access, $this->user->getAuthorisedViewLevels())) : ?>
                            <a href="<?php echo JRoute::_(JSpaceHelperRoute::getRecordRoute($item->slug, $item->catid)); ?>">
                                <?php echo $this->escape($item->title); ?>
                            </a>
                        <?php else: ?>
                            <?php
                            echo $this->escape($item->title).' : ';
                            $menu       = JFactory::getApplication()->getMenu();
                            $active     = $menu->getActive();
                            $itemId     = $active->id;
                            $link = JRoute::_('index.php?option=com_users&view=login&Itemid='.$itemId);
                            $returnURL = JRoute::_(JSpaceHelperRoute::getRecordRoute($item->slug));
                            $fullURL = new JUri($link);
                            $fullURL->setVar('return', base64_encode($returnURL));
                            ?>
                            <a href="<?php echo $fullURL; ?>" class="register">
                                <?php echo JText::_('COM_JSPACE_REGISTER_TO_READ_MORE'); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($item->published == 0) : ?>
                            <span class="list-published label label-warning">
                                <?php echo JText::_('JUNPUBLISHED'); ?>
                            </span>
                        <?php endif; ?>
                        <?php if (strtotime($item->publish_up) > strtotime(JFactory::getDate())) : ?>
                            <span class="list-published label label-warning">
                                <?php echo JText::_('JNOTPUBLISHEDYET'); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ((strtotime($item->publish_down) < strtotime(JFactory::getDate())) && $item->publish_down != '0000-00-00 00:00:00') : ?>
                            <span class="list-published label label-warning">
                                <?php echo JText::_('JEXPIRED'); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <?php if ($this->params->get('list_show_date')) : ?>
                        <td headers="categorylist_header_date" class="list-date small">
                            <?php
                            echo JHtml::_(
                                'date', $item->displayDate,
                                $this->escape($this->params->get('date_format', JText::_('DATE_FORMAT_LC3')))
                            ); ?>
                        </td>
                    <?php endif; ?>
                    <?php if ($this->params->get('list_show_author', 1)) : ?>
                        <td headers="categorylist_header_author" class="list-author">
                            <?php if (!empty($item->author) || !empty($item->created_by_alias)) : ?>
                                <?php $author = $item->author ?>
                                <?php $author = ($item->created_by_alias ? $item->created_by_alias : $author);?>
                                <?php if (!empty($item->contact_link) && $this->params->get('link_author') == true) : ?>
                                    <?php echo JText::sprintf('COM_JSPACE_WRITTEN_BY', JHtml::_('link', $item->contact_link, $author)); ?>
                                <?php else: ?>
                                    <?php echo JText::sprintf('COM_JSPACE_WRITTEN_BY', $author); ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php // Add pagination links ?>
<?php if (!empty($this->items)) : ?>
    <?php if (($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
    <div class="pagination">

        <?php if ($this->params->def('show_pagination_results', 1)) : ?>
            <p class="counter pull-right">
                <?php echo $this->pagination->getPagesCounter(); ?>
            </p>
        <?php endif; ?>

        <?php echo $this->pagination->getPagesLinks(); ?>
    </div>
    <?php endif; ?>
</form>
<?php  endif; ?>