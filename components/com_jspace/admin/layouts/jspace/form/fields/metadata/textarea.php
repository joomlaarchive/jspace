<?php
defined('JPATH_BASE') or die;

JFactory::getDocument()->addScript(JUri::root().'media/com_jspace/js/jspace.js');

$values = $displayData->value;

if (!is_array($values))
{
    $values = array($displayData->value);
}
?>
<div class="jspace-control-group">
    <?php foreach ($values as $key=>$value) : ?>
    <div class="jspace-control">
        <textarea 
            name="<?php echo $displayData->name; ?>"
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

    <?php if (!count($values)) : ?>
    <div class="jspace-control">
        <textarea 
            name="<?php echo $displayData->name; ?>"
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