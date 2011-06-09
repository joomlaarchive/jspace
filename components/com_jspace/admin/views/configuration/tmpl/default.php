<?php 
/**
 * A form view for adding/editing JSpace configuration.
 * 
 * @author		$LastChangedBy$
 * @package		JSpace
 * @copyright	Copyright (C) 2011 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace component for Joomla!.

   The JSpace component for Joomla! is free software: you can redistribute it 
   and/or modify it under the terms of the GNU General Public License as 
   published by the Free Software Foundation, either version 3 of the License, 
   or (at your option) any later version.

   The JSpace component for Joomla! is distributed in the hope that it will be 
   useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with the JSpace component for Joomla!.  If not, see 
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 * Name							Email
 * Hayden Young					<haydenyoung@wijiti.com> 
 * 
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'cancel' || document.formvalidator.isValid(document.id('item-form'))) {
			Joomla.submitform(task, document.getElementById('item-form'));
		}
		else {
			alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_jspace&view=configuration'); ?>" method="post" name="adminForm" id="item-form" class="form-validate">
	<div class="width-100">
		<fieldset class="adminform">
			<legend><?php echo JText::_("DSpace Settings"); ?></legend>
			<ul class="adminformlist">
				<li>
					<?php echo $this->form->getLabel('base_url'); ?>
					<?php echo $this->form->getInput('base_url'); ?>
				</li>

				<li><?php echo $this->form->getLabel('rest_url'); ?></li>
				<li><?php echo $this->form->getInput('rest_url'); ?></li>
				
				<li><?php echo $this->form->getLabel('username'); ?></li>
				<li><?php echo $this->form->getInput('username'); ?></li>

				<li><?php echo $this->form->getLabel('password'); ?></li>
				<li><?php echo $this->form->getInput('password'); ?></li>				
			</ul>
		</fieldset>
		<fieldset class="adminform">
			<legend><?php echo JText::_("Prerequisite Libraries"); ?></legend>
			<ul class="adminformlist">
				<li><span class="faux-label"><?php echo JText::_("COM_JSPACE_GD_LIBRARY"); ?></span></li>
				<li><span class="readonly">
				<?php 
				if ($this->getModel()->isGDInstalled()) :
					echo JText::_("COM_JSPACE_IS_INSTALLED");
				else :
					echo JText::_("COM_JSPACE_IS_NOT_INSTALLED");
				endif;
				?>
				</span></li>	
				<li><span class="faux-label"><?php echo JText::_("COM_JSPACE_FFMPEG_LIBRARY"); ?></span></li>
				<li><span class="readonly">
				<?php 
				if ($this->getModel()->isFFMPEGInstalled()) :
					echo JText::_("COM_JSPACE_IS_INSTALLED");
				else :
					echo JText::_("COM_JSPACE_IS_NOT_INSTALLED");
				endif;
				?>
				</span></li>
			</ul>
		</fieldset>
	</div>
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd("option");?>" />
	<input type="hidden" name="task" value="" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>