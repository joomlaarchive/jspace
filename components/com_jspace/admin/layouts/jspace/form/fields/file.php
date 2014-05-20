<?php
defined('JPATH_BASE') or die;
?>
<input 
	type="file" 
	id="<?php echo $displayData->id; ?>"
	name="<?php echo $displayData->name; ?>" 
	<?php echo ($displayData->multiple) ? 'multiple="multiple"' : ''; ?>/>

<?php if ($displayData->extractionmap) : ?>
<input 
	type="hidden" 
	name="<?php echo $displayData->name."[extractionmap]"; ?>" 
	value="<?php echo $displayData->extractionmap; ?>"/>
<?php endif; ?>

<?php if ($displayData->schema) : ?>
<input 
	type="hidden" 
	name="<?php echo $displayData->name."[schema]"; ?>" 
	value="<?php echo $displayData->schema; ?>"/>
<?php endif; ?>

<ul>
<?php $i = 0; ?>
<?php foreach ($displayData->getFileList() as $key=>$value) : ?>	
	<li>
		<label>
			<input 
				type="checkbox" 
				id="<?php echo 
$displayData->formControl."_".$displayData->bundle."_delete_".$displayData->fieldname."_".$i++; ?>"
				name="<?php echo 
$displayData->formControl."[".$displayData->bundle."][".$displayData->fieldname."][delete][]"; ?>" 
				value="<?php echo JArrayHelper::getValue($value, 'fileName'); ?>"/><?php echo JArrayHelper::getValue($value, 'fileName'); ?>
		</label>
	</li>
<?php endforeach; ?>
</ul>