<?php
defined('JPATH_BASE') or die;

JFactory::getDocument()->addScript(JUri::root().'media/com_jspace/js/jspace.js');

$values = $displayData->value;

if (!is_array($values))
{
    $values = array($displayData->value);
}
?>
<div 
    class="jspace-control-group" 
    data-jspace-maximum="<?php echo $displayData->maximum; ?>">

    <?php foreach ($values as $key=>$value) : ?>
    <div class="jspace-control">
        <input
            type="text" 
            name="<?php echo $displayData->name; ?>"
            <?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
            value="<?php echo $value; ?>"/>
            
        <?php if ((bool)$displayData->multiple) : ?>
        <button class="btn jspace-remove-field" type="button">
            <span class="icon-minus"></span>
        </button>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if (!count($values)) : ?>
    <div class="jspace-control">
        <input
            type="text" 
            name="<?php echo $displayData->name; ?>"
            <?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>/>
        
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