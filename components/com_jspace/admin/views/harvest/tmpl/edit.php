<?php
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

$this->ignore_fieldsets = array('details', 'publishing', 'discovery');

$assoc = JLanguageAssociations::isEnabled();
?>
<script type="text/javascript">
    Joomla.submitbutton = function(task, schema)
    {
        if (task == 'harvest.cancel' || document.formvalidator.isValid(document.id('harvest-form')))
        {
            Joomla.submitform(task, document.id('harvest-form'));
        }
    }
</script>

<form 
    action="<?php echo JRoute::_('index.php?option=com_jspace&layout=edit&id='.(int)$this->item->id); ?>" 
    method="post" 
    name="adminForm" 
    id="harvest-form" 
    class="form-validate"
    enctype="multipart/form-data">
    
    <div class="form-horizontal">
        <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active'=>'details')); ?>
                    
            <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_JSPACE_HARVEST_FIELDSET_DETAILS_LABEL', 
true)); ?>
            <div class="row-fluid">
                <div class="span9">
                    <?php
                    foreach ($this->form->getFieldset('details') as $field) :    
                        echo $field->renderField();
                    endforeach;
                    ?>
                    <?php if ($this->item->harvested) : ?>
                    <div class="control-group">
                        <div class="control-label">
                            <label><?php echo JText::_('COM_JSPACE_HARVEST_HARVESTED_LABEL'); ?></label>
                        </div>
                        <div class="controls"><?php echo $this->item->harvested; ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="span3">
                    <fieldset class="form-vertical">
                    <?php
                    $this->fieldset = 'discovery';
                    echo JLayoutHelper::render('joomla.edit.fieldset', $this);
                    ?>
                    </fieldset>
                </div>
            </div>
            <?php echo JHtml::_('bootstrap.endTab'); ?>

            <?php if ($this->item->discovered) : ?>
            <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', 
JText::_('COM_JSPACE_HARVEST_FIELDSET_PUBLISHING_LABEL', true)); ?>
            <div class="row-fluid">
                <div class="span12">
                    <?php foreach ($this->form->getFieldset('publishing') as $field) : ?>
                        <?php echo $field->renderField(); ?>                
                    <?php endforeach; ?>
                </div>
            </div>
            <?php echo JHtml::_('bootstrap.endTab'); ?>
            
            <?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>
            
            <?php endif; ?>

        <?php echo JHtml::_('bootstrap.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="return" value="<?php echo JFactory::getApplication()->input->getCmd('return'); ?>" />
    <?php echo JHtml::_('form.token'); ?>
</form>