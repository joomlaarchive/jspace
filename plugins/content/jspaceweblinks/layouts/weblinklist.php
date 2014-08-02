<?php
defined('JPATH_BASE') or die;

JFactory::getDocument()->addScript(JUri::root().'media/com_jspace/js/jspace.js');
?>
<button 
    class="btn jspace-add-field" 
    type="button"
    data-jspace-prefix="<?php echo $displayData->name; ?>"
    data-jspace-maximum="4">
    <?php echo JText::_('PLG_CONTENT_JSPACEWEBLINKS_ADD'); ?>&nbsp;<span class="icon-plus"></span>
</button>

<?php foreach ($displayData->value as $key=>$weblink) : ?>
<div data-jspace-name="<?php echo $displayData->name.'['.$key.']'; ?>">
	<input 
		type="hidden" 
		name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][id]"
		value="<?php echo JArrayHelper::getValue($weblink, 'id'); ?>"/>
	<input
		type="url" 
		name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][url]"
		<?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
		value="<?php echo JArrayHelper::getValue($weblink, 'url'); ?>"/>
		
	<input
		type="text" 
		name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][title]"
		<?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
		value="<?php echo JArrayHelper::getValue($weblink, 'title'); ?>"/>
		
	<button class="btn jspace-remove-field" type="button"><span class="icon-minus"></span></button>
</div>
<?php endforeach; ?>

<div data-jspace-name="<?php echo $displayData->name.'['.count($displayData->value).']'; ?>">
    <input
        type="url" 
        name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>][url]"
        <?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
        value=""/>
        
    <input
        type="text" 
        name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>][title]"
        <?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
        value=""/>
        
    <button class="btn jspace-remove-field" type="button"><span class="icon-minus"></span></button>
</div>