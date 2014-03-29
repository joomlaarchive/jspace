<?php
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

$assoc = JLanguageAssociations::isEnabled();
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task, schema)
	{
		if (task == 'dataobject.setSchema')
		{
			document.id('dataobject-form').elements['jform[schema]'].value = schema;
			Joomla.submitform('dataobject.setSchema', document.id('dataobject-form'));
		}
		else if (task == 'dataobject.cancel' || document.formvalidator.isValid(document.id('dataobject-form')))
		{
			Joomla.submitform(task, document.id('dataobject-form'));
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_jspace&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="dataobject-form" class="form-validate">
	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>
	
	<div class="form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>
					
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_JSPACE_DATAOBJECT_DETAILS_LABEL', true)); ?>
			<div class="row-fluid">
				<div class="span12">
					<?php foreach ($this->form->getFieldset('details') as $field) : ?>
						<?php echo $field->getControlGroup(); ?>				
					<?php endforeach; ?>
				</div>
			</div>
			<?php echo JHtml::_('bootstrap.endTab'); ?>

			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('COM_JSPACE_DATAOBJECT_PUBLISHING_LABEL', true)); ?>
			<div class="row-fluid">
				<div class="span12">
					<?php foreach ($this->form->getFieldset('publishing') as $field) : ?>
						<?php echo $field->getControlGroup(); ?>				
					<?php endforeach; ?>
				</div>
			</div>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
			
			<?php foreach ($this->form->getFieldsets('metadata') as $fieldset) : ?>
				<?php echo JHtml::_('bootstrap.addTab', 'myTab', $fieldset->name, JText::_($fieldset->label, true)); ?>
				<div class="row-fluid">
					<div class="span12">
						<?php foreach ($this->form->getFieldset($fieldset->name) as $field) : ?>
							<?php echo $field->getControlGroup(); ?>				
						<?php endforeach; ?>
					</div>
				</div>
				<?php echo JHtml::_('bootstrap.endTab'); ?>
			<?php endforeach; ?>

			<?php foreach ($this->form->getFieldsets('files') as $fieldset) : ?>
				<?php echo JHtml::_('bootstrap.addTab', 'myTab', $fieldset->name, JText::_($fieldset->label, true)); ?>
				<div>
					<?php foreach ($this->form->getFieldset($fieldset->name) as $field) : ?>
						<?php echo $field->getControlGroup(); ?>				
					<?php endforeach; ?>
				</div>
				<?php echo JHtml::_('bootstrap.endTab'); ?>
			<?php endforeach; ?>
			
			<?php if ($assoc) : ?>
				<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'associations', JText::_('JGLOBAL_FIELDSET_ASSOCIATIONS', true)); ?>
					<?php echo $this->loadTemplate('associations'); ?>
				<?php echo JHtml::_('bootstrap.endTab'); ?>
			<?php endif; ?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
	</div>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="return" value="<?php echo JFactory::getApplication()->input->getCmd('return'); ?>" />
	<?php echo JHtml::_('form.token'); ?>
</form>