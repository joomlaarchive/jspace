<?php
defined('JPATH_BASE') or die;
?>
<div 
    class="jspace-control-group" 
    data-jspace-name="<?php echo $displayData->name; ?>"
    data-jspace-maximum="<?php echo $displayData->maximum; ?>">
    
    <?php foreach ($displayData->value as $key=>$value) : ?>
    <div
        class="jspace-control"
        data-jspace-name="<?php echo $displayData->name; ?>[<?php echo $key; ?>]">
        <textarea 
            name="<?php echo $displayData->name; ?>[<?php echo $key; ?>]"
            cols="<?php echo $displayData->columns; ?>"
            rows="<?php echo $displayData->rows; ?>"
            <?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>><?php echo $value; ?></textarea>
            
        <?php if ((bool)$displayData->multiple) : ?>
        <button class="btn jspace-remove-field" type="button">
            <span class="icon-minus"></span>
        </button>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if (!count($displayData->value)) : ?>
    <div
        class="jspace-control"
        data-jspace-name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>]">
        <textarea 
            name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>]"
            cols="<?php echo $displayData->columns; ?>"
            rows="<?php echo $displayData->rows; ?>"
            <?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>></textarea>
        
        <?php if ((bool)$displayData->multiple) : ?>
        <button class="btn jspace-remove-field" type="button">
            <span class="icon-minus"></span>
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ((bool)$displayData->multiple) : ?>
    <button 
        class="btn jspace-add-field hasTooltip" 
        type="button"
        data-title="<?php echo JText::_('COM_JSPACE_ADD_DESC'); ?>">
        <span class="icon-plus"></span>
    </button>
    <?php endif; ?>
</div>