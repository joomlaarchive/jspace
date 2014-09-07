<?php
/**
 * Default display for details about a single category.
 * 
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 *
 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 *
 * Name							Email
 * Michał Kocztorz				<michalkocztorz@wijiti.com> 
 * Hayden Young                 <haydenyoung@wijiti.com>
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');
JHtml::_('behavior.caption');

?>
<div class="categories-list<?php echo $this->pageclass_sfx;?>">
<?php if ($this->params->get('show_page_heading')) : ?>
<h1>
    <?php echo $this->escape($this->params->get('page_heading')); ?>
</h1>
<?php endif; ?>

<?php if ($this->params->get('show_base_description')) : ?>
    <?php //If there is a description in the menu parameters use that; ?>
        <?php if($this->params->get('categories_description')) : ?>
            <div class="category-desc base-desc">
            <?php echo JHtml::_('content.prepare', $this->params->get('categories_description'), '',  $this->get('extension') . '.categories'); ?>
            </div>
        <?php else : ?>
            <?php //Otherwise get one from the database if it exists. ?>
            <?php  if ($this->parent->description) : ?>
                <div class="category-desc base-desc">
                    <?php echo JHtml::_('content.prepare', $this->parent->description, '', $this->parent->extension . '.categories'); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php echo $this->loadTemplate('items'); ?>
</div>