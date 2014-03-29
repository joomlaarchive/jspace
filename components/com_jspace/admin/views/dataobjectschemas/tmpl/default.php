<?php
defined('_JEXEC') or die;

$input = JFactory::getApplication()->input;
$document = JFactory::getDocument();

$tmpl = $input->getCmd('tmpl', '');
?>

<script type="text/javascript">
	setDataObjectSchema = function(schema)
	{
		<?php if ($tmpl) : ?>
			window.parent.Joomla.submitbutton('dataobject.setSchema', schema);
			window.parent.SqueezeBox.close();
		<?php else : ?>
			window.location="index.php?option=com_jspace&view=dataobject&task=dataobject.setSchema&layout=edit&schema="+('item.setSchema', schema);
		<?php endif; ?>
	}
</script>
<ul class="nav nav-tabs nav-stacked">
	<?php
	$i = 0;
	foreach ($this->items as $item) : ?>
	<li>
		<a class="choose_schema" href="#" title="<?php echo JText::_($item->description); ?>"
			onclick="javascript:setDataObjectSchema('<?php echo base64_encode(json_encode(array('id'=>$this->recordId, 'label'=>JText::_($item->label), 'name'=>$item->name, 'parent'=>$this->parentId))); ?>')">
			<?php echo JText::_($item->label);?> <small class="muted"><?php echo JText::_($item->description); ?></small>
		</a>
	</li>
	<?php endforeach; ?>
</ul>