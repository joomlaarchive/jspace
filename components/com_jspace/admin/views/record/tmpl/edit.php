<?php
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

$assoc = JLanguageAssociations::isEnabled();
$ignoreFieldSets = array('title','details','publishing','metadata','item_associations','identifiers', 'licensing');
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task, schema)
	{
		if (task == 'record.setSchema')
		{
			document.id('record-form').elements['jform[schema]'].value = schema;
			Joomla.submitform('record.setSchema', document.id('record-form'));
		}
		else if (task == 'record.cancel' || document.formvalidator.isValid(document.id('record-form')))
		{
			Joomla.submitform(task, document.id('record-form'));
		}
	}
</script>

<form
	action="<?php echo JRoute::_('index.php?option=com_jspace&layout=edit&id='.(int)$this->item->id); ?>"
	method="post"
	name="adminForm"
	id="record-form"
	class="form-validate"
	enctype="multipart/form-data">
	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<div class="form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_JSPACE_RECORD_DETAILS_LABEL',
true)); ?>
			<div class="row-fluid">
				<div class="span12">
					<?php foreach ($this->form->getFieldset('details') as $field) : ?>
						<?php echo $field->getControlGroup(); ?>
					<?php endforeach; ?>
				</div>
			</div>
			<?php echo JHtml::_('bootstrap.endTab'); ?>

			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing',
JText::_('COM_JSPACE_RECORD_PUBLISHING_LABEL', true)); ?>
			<div class="row-fluid">
				<div class="span6">
					<?php foreach ($this->form->getFieldset('publishing') as $field) : ?>
						<?php echo $field->getControlGroup(); ?>
					<?php endforeach; ?>
				</div>

				<div class="span6">
                    <?php foreach ($this->form->getFieldset('licensing') as $field) : ?>
                        <?php echo $field->getControlGroup(); ?>
                    <?php endforeach; ?>
				</div>
			</div>
			<?php echo JHtml::_('bootstrap.endTab'); ?>

            <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'metadata',
JText::_('COM_JSPACE_RECORD_METADATA_LABEL', true)); ?>
            <div class="row-fluid">
                <div class="span9">
                    <?php foreach ($this->form->getFieldset('metadata') as $field) : ?>
                        <?php echo $field->getControlGroup(); ?>
                    <?php endforeach; ?>
                </div>
                <div class="span3">
                    <fieldset class="form-vertical">
                        <?php
                        $displayData = $this;
                        $displayData->fieldset = 'identifiers';
                        echo JLayoutHelper::render('joomla.edit.fieldset', $this);
                        ?>
                    </fieldset>
                </div>
            </div>
            <?php echo JHtml::_('bootstrap.endTab'); ?>

            <?php foreach ($this->form->getFieldsets() as $fieldset) : ?>
                <?php if (array_search($fieldset->name, $ignoreFieldSets) === false) : ?>
                    <?php echo JHtml::_('bootstrap.addTab', 'myTab', $fieldset->name, JText::_($fieldset->label, true)); ?>
                    <div class="row-fluid">
                        <div class="span12">
                            <?php foreach ($this->form->getFieldset($fieldset->name) as $field) : ?>
                                <?php echo $field->getControlGroup(); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php echo JHtml::_('bootstrap.endTab'); ?>
                <?php endif; ?>
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