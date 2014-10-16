<?php
/**
 * OAI welcome page.
 *
 * @package     JSpace
 * @copyright   Copyright (C) 2011-2014 Wijiti Pty Ltd. All rights reserved.
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
 * @author Hayden Young <haydenyoung@wijiti.com>
 * @author Michał Kocztorz <michalkocztorz@wijiti.com>
 */

defined( '_JEXEC' ) or die( 'Restricted access' );
?>
<h1><?php echo JText::_('COM_JSPACE_OAI_HEADING'); ?></h1>

<p><?php echo JText::_('COM_JSPACE_OAI_INTRODUCTION'); ?></p>

<h3><a href="<?php echo JRoute::_(JSpaceHelperRoute::getOaiRoute('Identify')); ?>">Identify</a></h3>
<p><?php echo JText::_('COM_JSPACE_OAI_IDENTIFY'); ?></p>

<h3><a href="<?php echo JRoute::_(JSpaceHelperRoute::getOaiRoute('ListMetadataFormats')); ?>">ListMetadataFormats</a></h3>
<p><?php echo JText::_('COM_JSPACE_OAI_LISTMETADATAFORMATS'); ?></p>
<p><strong><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_LABEL'); ?></strong>
    <ul>
        <li><?php echo JText::_('COM_JSPACE_OAI_LISTMETADATAFORMATS_ARGUMENTS_IDENTIFIER'); ?></li>
    </ul>
</p>

<h3><a href="<?php echo JRoute::_(JSpaceHelperRoute::getOaiRoute('ListSets')); ?>">ListSets</a></h3>
<p><?php echo JText::_('COM_JSPACE_OAI_LISTSETS'); ?></p>

<h3><a href="<?php echo JRoute::_(JSpaceHelperRoute::getOaiRoute('ListIdentifiers')); ?>">ListIdentifiers</a></h3>
<p><?php echo JText::_('COM_JSPACE_OAI_LISTIDENTIFIERS'); ?></p>
<p><strong><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_LABEL'); ?></strong>
    <ul>
        <li><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_METADATAPREFIX'); ?></li>
        <li><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_FROM'); ?></li>
        <li><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_UNTIL'); ?></li>
        <li><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_SET'); ?></li>
    </ul>
</p>

<h3><a href="<?php echo JRoute::_(JSpaceHelperRoute::getOaiRoute('ListRecords')); ?>">ListRecords</a></h3>
<p><?php echo JText::_('COM_JSPACE_OAI_LISTRECORDS'); ?></p>
<p><strong><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_LABEL'); ?></strong>
    <ul>
        <li><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_METADATAPREFIX'); ?></li>
        <li><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_FROM'); ?></li>
        <li><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_UNTIL'); ?></li>
        <li><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_SET'); ?></li>
    </ul>
</p>

<h3><a href="<?php echo JRoute::_(JSpaceHelperRoute::getOaiRoute('GetRecord')); ?>">GetRecord</a></h3>
<p><?php echo JText::_('COM_JSPACE_OAI_GETRECORD'); ?></p>
<p><strong><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_LABEL'); ?></strong>
    <ul>
        <li><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_GETRECORD_IDENTIFIER'); ?></li>
        <li><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_METADATAPREFIX'); ?></li>
    </ul>
</p>

<p><?php echo JText::_('COM_JSPACE_OAI_ARGUMENTS_RESUMPTIONTOKEN'); ?></p>