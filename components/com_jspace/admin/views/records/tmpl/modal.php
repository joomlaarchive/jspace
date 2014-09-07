<?php
defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.framework', true);

$app = JFactory::getApplication();

if ($app->isSite())
{
    JSession::checkToken('get') or die(JText::_('JINVALID_TOKEN'));
}

$function  = $app->input->getCmd('function', 'jSelectArticle');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$assoc      = JLanguageAssociations::isEnabled();

require_once JPATH_ROOT.'/components/com_jspace/helpers/route.php';
?>
<form 
    action="<?php echo JRoute::_('index.php?option=com_jspace&view=records&layout=modal&tmpl=component&function='.$function.'&'.JSession::getFormToken().'=1'); ?>" 
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
                    <th width="10%">
                        <?php echo JText::_('JGLOBAL_HITS'); ?>
                    </th>
                    <th width="1%" class="nowrap hidden-phone">
                        <?php echo JText::_('JGRID_HEADING_ID'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>     
                <?php foreach ($this->items as $i => $item) :
                if ($item->language && JLanguageMultilang::isEnabled())
                {
                    $tag = strlen($item->language);
                    if ($tag == 5)
                    {
                        $lang = substr($item->language, 0, 2);
                    }
                    elseif ($tag == 6)
                    {
                        $lang = substr($item->language, 0, 3);
                    }
                    else {
                        $lang = "";
                    }
                }
                elseif (!JLanguageMultilang::isEnabled())
                {
                    $lang = "";
                }
                ?>
                <tr class="row<?php echo $i % 2; ?>">
                    <td class="has-context">
                        <div class="pull-left">
                            <?php if ($item->language == '*'):?>
                                <?php $language = JText::alt('JALL', 'language'); ?>
                            <?php else:?>
                                <?php $language = $item->language_title ? $this->escape($item->language_title) : 
JText::_('JUNDEFINED'); ?>
                            <?php endif;?>
                            
                            <?php echo str_repeat('<span class="gi">|&mdash;</span>', (int)$item->level-1) ?>
                            <a href="javascript:void(0)" onclick="if (window.parent) window.parent.<?php echo $this->escape($function);?>('<?php echo $item->id; ?>', '<?php echo $this->escape(addslashes($item->title)); ?>', '<?php echo $this->escape($item->catid); ?>', null, '<?php echo $this->escape(JSpaceHelperRoute::getRecordRoute($item->id, $item->catid, $item->language)); ?>', '<?php echo $this->escape($lang); ?>', null);">
                                <?php echo $this->escape($item->title); ?></a>

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
                    
                    <td class="center">
                        <?php echo (int) $item->hits; ?>
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
    </div>
    
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
    <?php echo JHtml::_('form.token'); ?>
</form>