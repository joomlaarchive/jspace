<?php
defined('JPATH_BASE') or die;

JFactory::getDocument()->addScript(JUri::root().'media/com_jspace/js/jspace.js');
?>
<div 
    class="jspace-control-group" 
    data-jspace-name="<?php echo $displayData->name; ?>"
    data-jspace-maximum="<?php echo $displayData->maximum; ?>">

    <?php foreach ($displayData->value as $key=>$weblink) : ?>
    <div class="jspace-control" data-jspace-name="<?php echo $displayData->name.'['.$key.']'; ?>">
        <input 
            type="hidden" 
            name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][id]"
            value="<?php echo JArrayHelper::getValue($weblink, 'id'); ?>"/>
        <input
            type="url" 
            name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][url]"
            placeholder="<?php echo JText::_('PLG_CONTENT_JSPACEWEBLINKS_URL_LABEL'); ?>"
            <?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
            value="<?php echo JArrayHelper::getValue($weblink, 'url'); ?>"/>

        <input
            type="text" 
            name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][title]"
            placeholder="<?php echo JText::_('PLG_CONTENT_JSPACEWEBLINKS_TITLE_LABEL'); ?>"
            <?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
            value="<?php echo JArrayHelper::getValue($weblink, 'title'); ?>"/>
            
        <button class="btn jspace-remove-field" type="button"><span class="icon-minus"></span></button>
    </div>
    <?php endforeach; ?>

    <?php if (!count($displayData->value)) : ?>
    <div 
        class="jspace-control" 
        data-jspace-name="<?php echo $displayData->name.'['.count($displayData->value).']'; ?>">
        <input
            type="url" 
            name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>][url]"
            placeholder="<?php echo JText::_('PLG_CONTENT_JSPACEWEBLINKS_URL_LABEL'); ?>"
            <?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
            value=""/>
            
        <input
            type="text" 
            name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>][title]"
            placeholder="<?php echo JText::_('PLG_CONTENT_JSPACEWEBLINKS_TITLE_LABEL'); ?>"
            <?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
            value=""/>
            
        <button class="btn jspace-remove-field" type="button">
            <span class="icon-minus"></span>
        </button>
    </div>
    <?php endif; ?>
    
    <button 
        class="btn jspace-add-field" 
        type="button">
        <?php echo JText::_('PLG_CONTENT_JSPACEWEBLINKS_ADD'); ?>&nbsp;<span class="icon-plus"></span>
    </button>
</div>