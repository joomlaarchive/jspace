<?php
defined('JPATH_BASE') or die;
?>
<?php if ($displayData->multiple) : ?>
	<?php if (is_array($displayData->value)) : ?>
		<?php $i = 0; ?>
		<?php foreach ($displayData->value as $item) : ?>
		<div>
			<textarea 
				id="<?php echo $displayData->id."_".$i++; ?>" 
				name="<?php echo $displayData->name; ?>[]"
				cols="<?php echo $displayData->columns; ?>"
				rows="<?php echo $displayData->rows; ?>"
				<?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>><?php echo $item; ?></textarea>
		</div>
		<?php endforeach; ?>
	<?php endif; ?>
<?php else : ?>
<textarea 
	id="<?php echo $displayData->id; ?>" 
	name="<?php echo $displayData->name; ?>"
	cols="<?php echo $displayData->columns; ?>"
	rows="<?php echo $displayData->rows; ?>"
	<?php echo ($displayData->readonly) ? 'readonly="readonly"' : ''; ?>><?php echo $item; ?></textarea>	
<?php endif; ?>