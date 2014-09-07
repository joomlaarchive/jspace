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

$item = $this->child;
?>
<div 
    class="item-page<?php echo $this->pageclass_sfx; ?>" 
    itemscope
    itemtype="http://schema.org/ScholarlyArticle">
    <meta 
        itemprop="inLanguage" 
        content="<?php echo $this->get('Language'); ?>"/>
    
    <h2 itemprop="name"><?php echo $this->escape($this->child->title); ?></a></h2>
    
    <?php 
    if ($this->item->params->get('show_tags', 1) && !empty($item->getTags()->itemTags)) : 
        $tagLayout = new JLayoutFile('joomla.content.tags');
        echo $tagLayout->render($item->getTags()->itemTags);
    endif;
    ?>

    <div itemprop="articleBody">
        <?php foreach ($this->child->get('metadata')->toArray() as $key=>$value) : ?>
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
        foreach ($item->getChildren() as $child) :
            $this->child = $child;
            echo $this->loadTemplate('child');
        endforeach;
        ?>
        
        <?php
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin("content");

        foreach ($item->getAssets() as $asset) :
            $downloads = $this->getDownloadLinks($asset);
            
            foreach ($downloads as $download) :
                echo $download;
            endforeach;
        endforeach;
        ?>
    </div>
</div>