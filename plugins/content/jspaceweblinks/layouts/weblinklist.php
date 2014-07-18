<?php
defined('JPATH_BASE') or die;

JFactory::getDocument()->addScript(JUri::root().'media/com_jspace/js/jspace.js');
?>

<?php foreach ($displayData->value as $key=>$weblink) : ?>
<div
    data-id="<?php echo $displayData->id; ?>"
    data-name="<?php echo $displayData->name; ?>"
    data-position="<?php echo $key; ?>"
    data-maximum="4">
	<input 
		type="hidden" 
		name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][id]"
		value="<?php echo JArrayHelper::getValue($weblink, 'id'); ?>"/>
	<input
		type="url" 
		id="<?php echo $displayData->id.'_'.$key; ?>" 
		name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][url]"
		<?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
		value="<?php echo JArrayHelper::getValue($weblink, 'url'); ?>"/>
		
	<input
		type="text" 
		id="<?php echo $displayData->id.'_'.$key; ?>" 
		name="<?php echo $displayData->name; ?>[<?php echo $key; ?>][title]"
		<?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
		value="<?php echo JArrayHelper::getValue($weblink, 'title'); ?>"/>
		
	<button class="btn jspace-remove-field" type="button"><span class="icon-minus"></span></button>
</div>
<?php endforeach; ?>

<div
    data-id="<?php echo $displayData->id; ?>"
    data-name="<?php echo $displayData->name; ?>"
    data-position="<?php echo count($displayData->value); ?>"
    data-maximum="4">
    <input
        type="url" 
        id="<?php echo $displayData->id.'_'.count($displayData->value).'_url'; ?>" 
        name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>][url]"
        <?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
        value=""/>
        
    <input
        type="text" 
        id="<?php echo $displayData->id.'_'.count($displayData->value).'_title'; ?>" 
        name="<?php echo $displayData->name; ?>[<?php echo count($displayData->value); ?>][title]"
        <?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
        value=""/>
    <button class="btn jspace-add-field" type="button">
        <span class="icon-plus"></span>
    </button>
</div>