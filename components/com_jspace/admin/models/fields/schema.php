<?php
defined('_JEXEC') or die('Restricted access');
 
class JSpaceFormFieldSchema extends JFormField
{
	/**
	 * field type
	 * @var string
	 */
	protected $type = 'JSpace.Schema';

	/**
	 * Method to get the field input markup
	 */
	protected function getInput()
	{
		$recordId = (int) $this->form->getValue('id');
		$parentId = (int) $this->form->getValue('parent_id', null, 0);
		
		JHtml::_('behavior.framework');
		JHtml::_('behavior.modal');

		$title = '';
		
		$link = 'index.php?option=com_jspace&amp;view=recordschemas&amp;layout=modal'.
				'&amp;tmpl=component&amp;recordId='.$recordId;
		
		if ($parentId)
		{
			$link.='&amp;parent='.$parentId;
		}

		if ($this->value) 
		{
			$model = JModelLegacy::getInstance('RecordSchemas', 'JSpaceModel');
			
			foreach ($model->getItems() as $item)
			{
				if ($this->value == $item->name)
				{
					$title = JText::_($item->label);
					continue;
				}
			}
		}

		// class='required' for client side validation
		$class = '';
		if ($this->required) 
		{
			$class = ' class="required modal-value"';
		}

		$select = JText::_('JSELECT');
		
		$size = ($v = $this->element['size']) ? ' size="' . $v . '"' : '';
		$class = ($v = $this->element['class']) ? ' class="' . $v . '"' : 'class="text_area"';
		
		$html = <<<HTML
<span class="input-append">
	<input 
		type="text" 
		disabled="disabled" 
		readonly="readonly" 
		id="{$this->id}_title" 
		value="$title"
		$size
		$class/>
 	<a 
 		class="btn btn-primary" 
 		onclick="SqueezeBox.fromElement(this, {handler:'iframe', size: {x: 600, y: 450}, url:'$link'})">
 		<i class="icon-list icon-white"></i>$select
 	</a>
</span>
 
		 <input class="input-small" type="hidden" name="{$this->name}" value="{$this->value}"/>
HTML;

		return $html;
	}
}