<?php
defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$app        = JFactory::getApplication();
$user       = JFactory::getUser();
$userId     = $user->get('id');
$trashed    = $this->state->get('filter.published') == -2 ? true : false;
?>
<form 
    action="<?php echo JRoute::_('index.php?option=com_jspace&view=harvests'); ?>" 
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
        echo JLayoutHelper::render('joomla.searchtools.default', array('view'=>$this));
        ?>
        <?php if (empty($this->items)) : ?>
        <div class="alert alert-no-items">
            <?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
        </div>
        <?php else : ?>
        <table class="table table-striped" id="harvestList">
            <thead>
                <tr>
                    <th width="1%" class="hidden-phone">
                        <?php echo JHtml::_('grid.checkall'); ?>
                    </th>
                    <th width="10%" style="min-width:55px" class="nowrap center">
                        <?php echo JText::_('JSTATUS'); ?>
                    </th>
                    <th>
                        <?php echo JText::_('COM_JSPACE_HARVESTS_HEADING_DISCOVERED'); ?>
                    </th>
                    <th width="10%" class="nowrap hidden-phone">
                        <?php echo JText::_('COM_JSPACE_HARVESTS_HEADING_CREATED_BY'); ?>
                    </th>
                    <th width="10%" class="nowrap hidden-phone">
                        <?php echo JText::_('JDATE'); ?>
                    </th>
                    <th width="10%" class="nowrap">
                        <?php echo JText::_('COM_JSPACE_HARVESTS_HEADING_HARVESTED'); ?>
                    </th>
                    <th width="1%" class="nowrap hidden-phone">
                        <?php echo JText::_('JGRID_HEADING_ID'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>     
                <?php foreach ($this->items as $i => $item) :
                $canCreate  = $user->authorise('core.create',     $this->option.'.category.'.$item->catid);
                $canEdit    = $user->authorise('core.edit',       $this->option.'.harvest.'.$item->id);
                $canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $userId || 
$item->checked_out == 0;
                $canEditOwn = $user->authorise('core.edit.own',   $this->option.'.harvest.'.$item->id) && 
$item->created_by == $userId;
                $canChange  = $user->authorise('core.edit.state', $this->option.'.harvest.'.$item->id) && $canCheckin;
                ?>
                <tr class="row<?php echo $i % 2; ?>">
                    <td class="center hidden-phone">
                        <?php echo JHtml::_('grid.id', $i, $item->id); ?>
                    </td>
                    <td class="center">
                        <div class="btn-group">
                            <?php echo JHtml::_('jgrid.published', $item->state, $i, 'harvests.', $canChange, 'cb'); ?>
                            <?php    
                            $action = $trashed ? 'untrash' : 'trash';
                            JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'harvests');
    
                            // Render dropdown list
                            echo JHtml::_('actionsdropdown.render', $this->escape($item->params->get('discovery.url')));
                            ?>
                        </div>
                    </td>
    
                    <td class="has-context">
                        <div class="pull-left">
                            <?php if ($item->checked_out) : ?>
                                <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 
'havests.', $canCheckin); ?>
                            <?php endif; ?>
                            
                            <?php if ($canEdit || $canEditOwn) : ?>
                                <a href="<?php echo JRoute::_('index.php?option=com_jspace&task=harvest.edit&id=' . 
$item->id); ?>" title="<?php echo JText::_('JACTION_EDIT'); ?>">
                                    <?php echo $this->escape($item->params->get('discovery.url')); ?></a>
                            <?php else : ?>
                                <span title="<?php echo JText::sprintf('JFIELD_ALIAS_LABEL', 
$this->escape($item->alias)); ?>"><?php echo 'title='.$this->escape($item->params->get('discovery.url')); ?></span>
                            <?php endif; ?>

                        </div>                      
                    </td>
                    
                    <td class="small hidden-phone">
                        <a href="<?php echo JRoute::_('index.php?option=com_users&task=user.edit&id='.(int)$item->created_by); ?>" title="<?php echo JText::_('JAUTHOR'); ?>">
                        <?php echo $this->escape($item->author_name); ?></a>
                    </td>
                    
                    <td class="nowrap small hidden-phone">
                        <?php echo $item->created; ?>
                    </td>
                    
                    <td class="nowrap small hidden-phone">
                        <?php echo $item->harvested; ?>
                    </td>

                    <td class="center hidden-phone">
                        <?php echo $item->id; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php endif; ?>
        
        <?php echo $this->pagination->getListFooter(); ?>
    </div>
    
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <?php echo JHtml::_('form.token'); ?>
</form>