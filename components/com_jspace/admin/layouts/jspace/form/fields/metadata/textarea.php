<?php
defined('JPATH_BASE') or die;
?>
<button 
    class="btn jspace-add-field hasTooltip" 
    type="button"
    data-jspace-prefix="<?php echo $displayData->name; ?>"
    data-jspace-maximum="10"
    data-title="<?php echo JText::_('COM_JSPACE_ADD_DESC'); ?>">
    <span class="icon-plus"></span>
</button>

<?php foreach ($displayData->value as $key=>$value) : ?>
<div
    data-jspace-name="<?php echo $displayData->name; ?>[<?php echo $key; ?>]">
    <textarea 
        name="<?php echo $displayData->name; ?>[]"
        cols="<?php echo $displayData->columns; ?>"
        rows="<?php echo $displayData->rows; ?>"
        <?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>><?php echo $value; ?></textarea>
        
    <button class="btn jspace-remove-field" type="button"><span class="icon-minus"></span></button>
</div>
<?php endforeach; ?>

<?php if (!count($displayData->value)) : ?>
<div
    data-jspace-name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>]">
    <textarea 
        name="<?php echo $displayData->name; ?>"
        cols="<?php echo $displayData->columns; ?>"
        rows="<?php echo $displayData->rows; ?>"
        <?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>></textarea>
        
    <button class="btn jspace-remove-field" type="button"><span class="icon-minus"></span></button>
</div>
<?php endif; ?>