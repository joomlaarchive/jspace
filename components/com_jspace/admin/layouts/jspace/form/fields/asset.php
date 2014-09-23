<?php
defined('JPATH_BASE') or die;
?>
<?php if ($displayData->multiple || count($displayData->getAssets()) == 0) : ?>
<input 
	type="file" 
	id="<?php echo $displayData->id; ?>"
	name="<?php echo $displayData->name; ?>" 
	<?php echo ($displayData->multiple) ? 'multiple="multiple"' : ''; ?>/>
<?php endif; ?>

<?php $i = 0; ?>
<?php foreach ($displayData->getAssets() as $key=>$value) : ?>
<div>
    <div>
        <span class="chzn-container"><?php echo $value->get('title'); ?></span>    
        <a 
            class="btn btn-small btn-success hasTooltip" 
            data-title="<?php echo JText::_('COM_JSPACE_FORMFIELD_ASSET_METADATA_DESC'); ?>"
            href="<?php echo JRoute::_('index.php?option=com_jspace&task=record.useAssetMetadata&id='.$value->id.'&'.JSession::getFormToken().'=1'); ?>"><?php echo JText::_('COM_JSPACE_FORMFIELD_ASSET_METADATA_TITLE'); ?></a>
        <a 
            class="btn btn-small btn-warning hasTooltip" 
            data-title="<?php echo JText::_('COM_JSPACE_FORMFIELD_ASSET_DELETE_DESC'); ?>" 
            href="<?php echo JRoute::_('index.php?option=com_jspace&task=record.deleteAsset&id='.$value->id.'&'.JSession::getFormToken().'=1'); ?>"><?php echo JText::_('COM_JSPACE_FORMFIELD_ASSET_DELETE_TITLE'); ?></a>
    </div>
    <div>
        <?php foreach ($displayData->getDownloadLinks($value) as $download) : ?>
            <?php echo $download; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>