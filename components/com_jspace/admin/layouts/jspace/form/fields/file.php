<?php
defined('JPATH_BASE') or die;
?>
<input 
	type="file" 
	id="<?php echo $displayData->id; ?>"
	name="<?php echo $displayData->name; ?>" 
	<?php echo ($displayData->multiple) ? 'multiple="multiple"' : ''; ?>/>

<input 
	type="hidden" 
	name="<?php echo $displayData->formControl."[".$displayData->group."][".$displayData->fieldname."][bundle]"; ?>" 
	value="<?php echo $displayData->bundle; ?>"/>
	
<input 
	type="hidden" 
	name="<?php echo $displayData->formControl."[".$displayData->group."][".$displayData->fieldname."][metadataextractionmapping]"; ?>" 
	value="<?php echo $displayData->metadataextractionmapping; ?>"/>
	
<ul>
<?php $i = 0; ?>
<?php foreach ($displayData->getFileList() as $key=>$value) : ?>	
	<li>
		<label>
			<input 
				type="checkbox" 
				id="<?php echo $displayData->formControl."_".$displayData->group."_delete_".$displayData->fieldname."_".$i++; ?>"
				name="<?php echo $displayData->formControl."[".$displayData->group."][".$displayData->fieldname."][delete][]"; ?>" 
				value="<?php echo JArrayHelper::getValue($value, 'fileName'); ?>"/><?php echo JArrayHelper::getValue($value, 'fileName'); ?>
		</label>
	</li>
<?php endforeach; ?>
</ul>