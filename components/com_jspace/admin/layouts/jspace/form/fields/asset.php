<?php
defined('JPATH_BASE') or die;

JFactory::getDocument()->addStyleSheet(JUri::root().'media/com_jspace/css/admin.jspace.css');
?>
<?php if ($displayData->multiple || count($displayData->getAssets()) == 0) : ?>
<div>
    <input 
        type="file" 
        id="<?php echo $displayData->id; ?>"
        name="<?php echo $displayData->name; ?>" 
        <?php echo ($displayData->multiple) ? 'multiple="multiple"' : ''; ?>/>
</div>
<?php endif; ?>

<?php $i = 0; ?>
<?php foreach ($displayData->getAssets() as $key=>$value) : ?>

<table class="table table-striped">
    <tbody>
        <tr>
            <td class="span3">
                <b><?php echo JText::_('COM_JSPACE_ASSET_TITLE_LABEL'); ?></b>
            </td>
            <td>
                <?php echo $value->get('title'); ?>
            </td>
        </tr>
        <tr>
            <td class="span3">
                <b><?php echo JText::_('COM_JSPACE_ASSET_CONTENTTYPE_LABEL'); ?></b>
            </td>
            <td>
                <?php echo $value->get('contentType'); ?>
            </td>
        </tr>
        <tr>
            <td class="span3">
                <b><?php echo JText::_('COM_JSPACE_ASSET_CONTENTLENGTH_LABEL'); ?></b>
            </td>
            <td>
                <?php echo $value->get('contentLength'); ?>
            </td>
        </tr>
        <tr>
            <td class="span3">
                <b><?php echo JText::_('COM_JSPACE_ASSET_HASH_LABEL'); ?></b>
            </td>
            <td>
                <?php echo $value->get('hash'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <b><?php echo JText::_('COM_JSPACE_ASSET_METADATA_LABEL'); ?></b>
            </td>
            <td>
                <div class="jspace-metadata-dialog">
                    <table class="table table-condensed">
                        <tbody>
                            <?php foreach ($value->get('metadata')->toArray() as $mkey=>$mvalue) : ?>
                            <tr>
                                <td><?php echo $mkey; ?></td>
                                <td><?php echo $mvalue; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
    </tbody>
</table>

<div class="span12">
    <a 
        class="btn btn-small btn-success hasTooltip" 
        data-title="<?php echo JText::_('COM_JSPACE_FORMFIELD_ASSET_METADATA_DESC'); ?>"
        href="<?php echo JRoute::_('index.php?option=com_jspace&task=record.useAssetMetadata&id='.$value->id.'&'.JSession::getFormToken().'=1'); ?>"><?php echo JText::_('COM_JSPACE_FORMFIELD_ASSET_METADATA_TITLE'); ?></a>
    <a 
        class="btn btn-small btn-warning hasTooltip" 
        data-title="<?php echo JText::_('COM_JSPACE_FORMFIELD_ASSET_DELETE_DESC'); ?>" 
        href="<?php echo JRoute::_('index.php?option=com_jspace&task=record.deleteAsset&id='.$value->id.'&'.JSession::getFormToken().'=1'); ?>"><?php echo JText::_('COM_JSPACE_FORMFIELD_ASSET_DELETE_TITLE'); ?></a>
</div>

<div class="span12">
    <?php foreach ($displayData->getDownloadLinks($value) as $download) : ?>
        <?php echo $download; ?>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>