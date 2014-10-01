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

defined('_JEXEC') or die;

JHtml::_('behavior.framework');
JHtml::_('behavior.modal');
?>

<div id="cpanel" class="span12">
	<?php if (!empty($this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2"><?php echo $this->sidebar; ?></div>
	<div id="j-main-container" class="span10">
	<?php else : ?>
    <div id="j-main-container">
	<?php endif;?>
        <div class="well well-small">
            <h2 class="module-title nav-header"><?php echo JText::_('COM_JSPACE_STATUS_LABEL'); ?></h2>
            <div class="row-fluid">
                <div class="span3"><strong><?php echo JText::_('COM_JSPACE_STATUS_RECORD_COUNT_LABEL'); ?></strong></div>
                <div class="span9">
                    <table class="table table-striped">
                        <?php foreach ($this->item->records as $record) : ?>
                        <tr>
                            <td>
                            <?php
                            switch ($record->published) :
                                case '-1':
                                    echo JText::_('JTRASHED');
                                    break;
                                case '0':
                                    echo JText::_('JUNPUBLISHED');
                                    break;
                                case '1':
                                    echo JText::_('JPUBLISHED');
                                    break;

                                case '2':
                                    echo JText::_('JARCHIVED');
                                    break;

                            endswitch;
                            ?>
                            </td>
                            <td><?php echo $record->total; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span3"><strong><?php echo JText::_('COM_JSPACE_STATUS_ASSET_COUNT_LABEL'); ?></strong></div>
                <div class="span9"><?php echo $this->item->assets->total; ?></div>
            </div>
            <div class="row-fluid">
                <div class="span3"><strong><?php echo JText::_('COM_JSPACE_STATUS_ASSET_SIZE_LABEL'); ?></strong></div>
                <div class="span9"><?php echo $this->item->assets->size; ?></div>
            </div>
        </div>

        <div class="well well-small">
            <h2 class="module-title nav-header"><?php echo JText::_('COM_JSPACE_STORAGE_LABEL'); ?></h2>
            <?php if (count($this->item->storage)) : ?>
                <?php foreach ($this->item->storage as $storage) : ?>
                <div class="row-fluid">
                    <div class="span3">
                        <p><strong><?php echo $storage->name; ?></strong></p>
                    </div>
                    <div class="span9">
                        <p><strong><?php echo JText::_('COM_JSPACE_STORAGE_CONFIG_LABEL'); ?></strong></p>

                        <table class="table table-striped">
                            <?php foreach ($storage->config as $key=>$value) : ?>
                            <tr>
                                <td><?php echo $key; ?></td>
                                <td><?php echo $value; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>

                        <?php if (count($storage->errors)) : ?>
                            <p><strong><?php echo JText::_('COM_JSPACE_STORAGE_ERRORS_LABEL'); ?></strong></p>
                            <?php foreach ($storage->errors as $error) : ?>
                            <p class="text-error"><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else : ?>
            <p class="text-warning"><?php echo JText::_('COM_JSPACE_STORAGE_NONE'); ?></p>
            <?php endif; ?>
        </div>
        <div class="well well-small">
            <h2 class="module-title nav-header"><?php echo JText::_('COM_JSPACE_HARVEST_LABEL'); ?></h2>
            <?php
            if ($this->item->harvesting) :
                $harvesting = '<strong class="text-success">'.JText::_('JENABLED').'</strong>';
            else :
                $harvesting = '<strong class="text-error">'.JText::_('JDISABLED').'</strong>';
            endif;

            echo JText::sprintf('COM_JSPACE_HARVESTING_LABEL', $harvesting);
            ?>
        </div>
    </div>
</div>