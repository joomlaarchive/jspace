<?php
defined('JPATH_BASE') or die;

JFactory::getDocument()->addScript(JUri::root().'media/com_jspace/js/jspace.js');
?>
<button 
    class="btn jspace-add-field hasTooltip" 
    type="button"
    data-jspace-prefix="<?php echo $displayData->name; ?>"
    data-jspace-maximum="4"
    data-title="<?php echo JText::_('COM_JSPACE_ADD_DESC'); ?>">
    <span class="icon-plus"></span>
</button>

<?php foreach ($displayData->value as $key=>$value) : ?>
<div
    data-jspace-name="<?php echo $displayData->name; ?>[<?php echo $key; ?>]">
    <input 
        type="text" 
        name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][name]"
        value="<?php echo JArrayHelper::getValue($value, 'name'); ?>"/>
        
    <textarea 
        name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][value]"><?php echo JArrayHelper::getValue($value, 'value'); ?></textarea>
    
    <button class="btn jspace-remove-field" type="button"><span class="icon-minus"></span></button>
</div>
<?php endforeach; ?>

<div
    data-jspace-name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>]">
    <input 
        type="text" 
        name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>][name]"/>
    <textarea 
        name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>][value]"></textarea>

    <button class="btn jspace-remove-field" type="button"><span class="icon-minus"></span></button>
</div>