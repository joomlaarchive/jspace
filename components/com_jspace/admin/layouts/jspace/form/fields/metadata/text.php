<?php
defined('JPATH_BASE') or die;
?>
<?php if ($displayData->multiple || is_array($displayData->value)) : ?>
	<?php $i = 0; ?>
	<?php foreach ($displayData->value as $item) : ?>
	<div>
		<input
			type="text" 
			id="<?php echo $displayData->id; ?>" 
			name="<?php echo $displayData->name; ?>"
			<?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
			value="<?php echo $item; ?>"/>
	</div>
	<?php endforeach; ?>
<?php else : ?>
<input
	type="text" 
	id="<?php echo $displayData->id; ?>" 
	name="<?php echo $displayData->name; ?>"
	<?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>
	value="<?php echo $displayData->value; ?>"/>	
<?php endif; ?>