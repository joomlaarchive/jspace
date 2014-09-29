<?php
defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$app		= JFactory::getApplication();
$user		= JFactory::getUser();
$userId		= $user->get('id');
$archived	= $this->state->get('filter.published') == 2 ? true : false;
$trashed	= $this->state->get('filter.published') == -2 ? true : false;

$assoc		= JLanguageAssociations::isEnabled();
?>
<form 
	action="<?php echo JRoute::_('index.php?option=com_jspace&view=records'); ?>" 
	method="post" 
	name="adminForm" 
	id="adminForm">
	<?php if (!empty( $this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
	<?php else : ?>
	<div id="j-main-container">
	<?php endif;?>
		<?php
		echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		?>
		<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
		<?php else : ?>
		<table class="table table-striped" id="recordList">
			<thead>
				<tr>
					<th width="1%" class="hidden-phone">
						<?php echo JHtml::_('grid.checkall'); ?>
					</th>
					<th width="10%" style="min-width:55px" class="nowrap center">
						<?php echo JText::_('JSTATUS'); ?>
					</th>
					<th>
						<?php echo JText::_('JGLOBAL_TITLE'); ?>
					</th>
					<th width="10%" class="nowrap hidden-phone">
						<?php echo JText::_('JGRID_HEADING_ACCESS'); ?>
					</th>
					<?php if ($assoc) : ?>
					<th width="5%" class="nowrap hidden-phone">
						<?php echo JText::_('COM_JSPACE_RECORDS_HEADING_ASSOCIATION'); ?>
					</th>
					<?php endif;?>
					<th width="10%" class="nowrap hidden-phone">
						<?php echo JText::_('COM_JSPACE_RECORDS_HEADING_CREATED_BY'); ?>
					</th>
					<th width="5%" class="nowrap hidden-phone">
						<?php echo JText::_('JGRID_HEADING_LANGUAGE'); ?>
					</th>
					<th width="10%" class="nowrap hidden-phone">
						<?php echo JText::_('JDATE'); ?>
					</th>
					<th width="1%" class="nowrap hidden-phone">
						<?php echo JText::_('JGRID_HEADING_ID'); ?>
					</th>
				</tr>
			</thead>
			<tbody>		
				<?php foreach ($this->items as $i => $item) :
				$canCreate  = $user->authorise('core.create',     $this->option.'.category.'.$item->catid);
				$canEdit    = $user->authorise('core.edit',       $this->option.'.record.'.$item->id);
				$canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $userId || 
$item->checked_out == 0;
				$canEditOwn = $user->authorise('core.edit.own',   $this->option.'.record.'.$item->id) && 
$item->created_by == $userId;
				$canChange  = $user->authorise('core.edit.state', $this->option.'.record.'.$item->id) && $canCheckin;
				?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="center hidden-phone">
						<?php echo JHtml::_('grid.id', $i, $item->id); ?>
					</td>
					<td class="center">
						<div class="btn-group">
							<?php echo JHtml::_('jgrid.published', $item->published, $i, 'records.', $canChange, 'cb', 
$item->publish_up, $item->publish_down); ?>
							<?php
							if ($canEdit || $canEditOwn) :
							JHtml::_('actionsdropdown.addCustomItem', 'New', 'edit', 'cb' . $i, 'records.addChild');
							endif;
							
							$action = $archived ? 'unarchive' : 'archive';
							JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'records');
	
							$action = $trashed ? 'untrash' : 'trash';
							JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'records');
	
							// Render dropdown list
							echo JHtml::_('actionsdropdown.render', $this->escape($item->title));
							?>
						</div>
					</td>
	
					<td class="has-context">
						<div class="pull-left">
							<?php if ($item->checked_out) : ?>
								<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 
'records.', $canCheckin); ?>
							<?php endif; ?>
							
							<?php if ($item->language == '*'):?>
								<?php $language = JText::alt('JALL', 'language'); ?>
							<?php else:?>
								<?php $language = $item->language_title ? $this->escape($item->language_title) : 
JText::_('JUNDEFINED'); ?>
							<?php endif;?>
							
							<?php if ($canEdit || $canEditOwn) : ?>
								<?php echo str_repeat('<span class="gi">|&mdash;</span>', (int)$item->level-1) ?>
								<a href="<?php echo JRoute::_('index.php?option=com_jspace&task=record.edit&id=' . 
$item->id); ?>" title="<?php echo JText::_('JACTION_EDIT'); ?>">
									<?php echo $this->escape($item->title); ?></a>
							<?php else : ?>
								<?php echo str_repeat('<span class="gi">|&mdash;</span>', (int)$item->level-1) ?>
								<span title="<?php echo JText::sprintf('JFIELD_ALIAS_LABEL', 
$this->escape($item->alias)); ?>"><?php echo 'title='.$this->escape($item->title); ?></span>
							<?php endif; ?>
							
							<div class="small">
								<?php
								if ($item->catid) :
									echo JText::_('JCATEGORY') . ": " . $this->escape($item->category_title);
								else :
									echo JText::_('COM_JSPACE_RECORDS_PARENT_TITLE_LABEL') . ": " . 
$this->escape($item->parent_title);
								endif;
								?>
							</div>
						</div>						
					</td>
					
					<td class="small hidden-phone">
						<?php echo $this->escape($item->access_level); ?>
					</td>
					
					<?php if ($assoc) : ?>
					<td class="hidden-phone">
						<?php if ($item->association) : ?>
							<?php echo JHtml::_('contentadministrator.association', $item->id); ?>
						<?php endif; ?>
					</td>
					<?php endif;?>
			
					<td class="small hidden-phone">
						<a href="<?php echo JRoute::_('index.php?option=com_users&task=user.edit&id='.(int) $item->created_by); ?>" title="<?php echo JText::_('JAUTHOR'); ?>">
						<?php echo $this->escape($item->author_name); ?></a>
					</td>
					
					<td class="small hidden-phone">
						<?php if ($item->language == '*'):?>
							<?php echo JText::alt('JALL', 'language'); ?>
						<?php else:?>
							<?php echo $item->language_title ? $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
						<?php endif;?>
					</td>
					
					<td class="nowrap small hidden-phone">
						<?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC4')); ?>
					</td>
					
					<td class="center hidden-phone">
						<?php echo (int) $item->id; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<?php endif; ?>
		
		<?php echo $this->pagination->getListFooter(); ?>
		
		<?php echo $this->loadTemplate('batch'); ?>
	</div>
	
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHtml::_('form.token'); ?>
</form>