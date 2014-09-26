<?php
/**
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('JPATH_BASE') or die;

JFactory::getDocument()->addScript(JUri::root().'media/com_jspace/js/jspace.js');
?>
<div 
    class="jspace-control-group" 
    data-jspace-name="<?php echo $displayData->name; ?>"
    data-jspace-maximum="<?php echo $displayData->maximum; ?>">
    <?php foreach ($displayData->value as $key=>$value) : ?>
    <div
        class="jspace-control"
        data-jspace-name="<?php echo $displayData->name; ?>[<?php echo $key; ?>]">
        <input 
            type="text" 
            name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][name]"
            value="<?php echo JArrayHelper::getValue($value, 'name'); ?>"/>
            
        <textarea 
            name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][value]"><?php echo JArrayHelper::getValue($value, 'value'); ?></textarea>
        
        <button class="btn jspace-remove-field" type="button">
            <span class="icon-minus"></span>
        </button>
    </div>
    <?php endforeach; ?>

    <?php if (!count($displayData->value)) : ?>
    <div
        class="jspace-control"
        data-jspace-name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>]">
        <input 
            type="text" 
            name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>][name]"/>
        <textarea 
            name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>][value]"></textarea>

        <button class="btn jspace-remove-field" type="button">
            <span class="icon-minus"></span>
        </button>
    </div>
    <?php endif; ?>
    
    <button 
        class="btn jspace-add-field hasTooltip" 
        type="button"
        data-title="<?php echo JText::_('COM_JSPACE_ADD_DESC'); ?>">
        <span class="icon-plus"></span>
    </button>
</div>