<?php
/**
 * Default display for details about a single record.
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

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');

// Create shortcuts to some parameters.
$user    = JFactory::getUser();

JHtml::_('behavior.caption');

$useDefList = ($this->item->params->get('show_modify_date') || $this->item->params->get('show_publish_date') || 
    $this->item->params->get('show_create_date') || $this->item->params->get('show_category') 
    || $this->item->params->get('show_parent_category') || $this->item->params->get('show_author'));
?>
<div 
    class="item-page<?php echo $this->pageclass_sfx; ?>" 
    itemscope
    itemtype="http://schema.org/ScholarlyArticle">
    <meta 
        itemprop="inLanguage" 
        content="<?php echo $this->get('Language'); ?>"/>
        
    <?php if ($this->params->get('show_page_heading', 1)) : ?>
    <div class="page-header">
        <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
    </div>
    <?php endif; ?>

    <?php if ($this->item->params->get('show_title') || $this->item->params->get('show_author')) : ?>
    <div class="page-header">
        <h2 itemprop="name">
            <?php if ($this->item->params->get('show_title')) : ?>
                <?php echo $this->escape($this->item->title); ?></a>
            <?php endif; ?>
        </h2>
        
        <?php if ($this->item->published == 0) : ?>
        <span class="label label-warning"><?php echo JText::_('JUNPUBLISHED'); ?></span>
        <?php endif; ?>
        
        <?php if (strtotime($this->item->get('publish_up')) > strtotime(JFactory::getDate())) : ?>
        <span class="label label-warning"><?php echo JText::_('JNOTPUBLISHEDYET'); ?></span>
        <?php endif; ?>
        
        <?php if ((strtotime($this->item->get('publish_down')) < strtotime(JFactory::getDate())) && $this->item->get('publish_down') != '0000-00-00 00:00:00') : ?>
        <span class="label label-warning"><?php echo JText::_('JEXPIRED'); ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($this->item->params->get('access-view')) :?>
        <?php if ($useDefList) : ?>
        <div class="article-info muted">
            <dl class="article-info">
                <dt class="article-info-term"><?php echo JText::_('COM_JSPACE_RECORD_INFO'); ?></dt>
                
                <?php if ($this->item->params->get('show_author') && $this->item->getCreatedBy()->get('name')) : ?>
                <dd 
                    class="createdby" 
                    itemprop="author" 
                    itemscope 
                    itemtype="http://schema.org/Person">                
                    <?php echo JText::sprintf('COM_JSPACE_RECORD_CREATED_BY', '<span 
                        itemprop="name">'.$this->item->getCreatedBy()->get('name').'</span>'); ?>
                </dd>
                <?php endif; ?>
                
                <?php if ($this->item->params->get('show_parent') && $this->parentslug) : ?>
                <dd class="parent-category-name">
                    <?php $title = $this->escape($this->item->getParent()->title); ?>
                    <?php if ($this->item->params->get('link_parent') && !empty($this->parentslug)) : ?>
                        <?php $url = '<a href="' . JRoute::_(JSpaceHelperRoute::getRecordRoute($this->parentslug)) . '" itemprop="genre">' . $title . '</a>'; ?>
                        <?php echo JText::sprintf('COM_JSPACE_RECORD_PARENT', $url); ?>
                    <?php else : ?>
                        <?php echo JText::sprintf('COM_JSPACE_RECORD_PARENT', '<span itemprop="genre">' . $title . '</span>'); ?>
                    <?php endif; ?>
                </dd>
                <?php endif; ?>
                
                <?php if ($this->item->params->get('show_category') && $this->catslug) : ?>
                <dd class="category-name">
                    <?php $title = $this->escape($this->item->getCategory()->title); ?>
                    <?php if ($this->item->params->get('link_category')) : ?>
                        <?php $url = '<a href="' . JRoute::_(JSpaceHelperRoute::getCategoryRoute($this->catslug)) . '" itemprop="genre">' . $title . '</a>'; ?>
                        <?php echo JText::sprintf('COM_JSPACE_RECORD_CATEGORY', $url); ?>
                    <?php else : ?>
                        <?php echo JText::sprintf('COM_JSPACE_RECORD_CATEGORY', '<span itemprop="genre">' . $title . '</span>'); ?>
                    <?php endif; ?>
                </dd>
                <?php endif; ?>

                <?php if ($this->item->params->get('show_publish_date')) : ?>
                <dd class="published">
                    <span class="icon-calendar"></span>
                    <time 
                        datetime="<?php echo JHtml::_('date', $this->item->get('publish_up'), 'c'); ?>" 
                        itemprop="datePublished">
                        <?php echo JText::sprintf('COM_JSPACE_PUBLISHED_DATE_ON', JHtml::_('date', $this->item->get('publish_up'), JText::_('DATE_FORMAT_LC3'))); ?>
                    </time>
                </dd>
                <?php endif; ?>

                <?php if ($this->item->params->get('show_modify_date')) : ?>
                <dd class="modified">
                    <span class="icon-calendar"></span>
                    <time 
                        datetime="<?php echo JHtml::_('date', $this->item->modified, 'c'); ?>" 
                        itemprop="dateModified">
                        <?php echo JText::sprintf('COM_JSPACE_LAST_UPDATED', JHtml::_('date', $this->item->modified, JText::_('DATE_FORMAT_LC3'))); ?>
                    </time>
                </dd>
                <?php endif; ?>
                
                <?php if ($this->item->params->get('show_create_date')) : ?>
                <dd class="create">
                    <span class="icon-calendar"></span>
                    <time 
                        datetime="<?php echo JHtml::_('date', $this->item->created, 'c'); ?>" 
                        itemprop="dateCreated">
                        <?php echo JText::sprintf('COM_JSPACE_CREATED_DATE_ON', JHtml::_('date', $this->item->created, JText::_('DATE_FORMAT_LC3'))); ?>
                    </time>
                </dd>
                <?php endif; ?>
            </dl>
        </div>
        <?php endif; ?>

        <?php 
        if ($this->item->params->get('show_tags', 1) && !empty($this->item->getTags()->itemTags)) : 
            $tagLayout = new JLayoutFile('joomla.content.tags');
            echo $tagLayout->render($this->item->getTags()->itemTags);
        endif;
        ?>

        <?php if ($this->item->params->get('access-view')):?>    
        <div itemprop="articleBody">
            <?php foreach ($this->item->get('metadata')->toArray() as $key=>$value) : ?>
            <dl>
                <dt><?php echo $key; ?></dt>
                <dd>
                    <?php if (is_array($value)) : ?>
                        <?php echo implode('<br/>', $value); ?>
                    <?php elseif (is_string($value)) : ?>
                        <?php echo $value; ?>
                    <?php endif; ?>
                </dd>
            </dl>
            <?php endforeach; ?>
            
            <?php 
            foreach ($this->item->getChildren() as $child) :
                $this->child = $child;
                echo $this->loadTemplate('child');
            endforeach;
            ?>
        </div>
        <?php endif; ?>

    <?php elseif ($this->item->params->get('show_noauth') == true && $user->get('guest')) : ?>
        <p class="readmore">
            <a href="<?php echo JRoute::_('index.php?option=com_users&view=login'); ?>">
            <?php
            echo JText::_('COM_JSPACE_READ_MORE');
            ?>
            </a>
        </p>
    <?php endif; ?>
</div>