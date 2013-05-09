<?php defined('_JEXEC') or die; 
/**
 * @author		$LastChangedBy: michalkocztorz $
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
 * Michał Kocztorz				<michalkocztorz@wijiti.com>
 *
 */

$item = $this->params['item'];
$profile = JUserHelper::getProfile($item->user_id);
$crosswalk = $this->params['crosswalk'];
JLoader::import('route', JPATH_SITE . "/components/com_jspace/helpers/" );
$link = JSpaceHelperRoute::getItemFullRoute($this->params['dspaceItemId']);
?>
<h1><?php echo JText::sprintf('COM_JSPACE_ITEM_ARCHIVED', $profile->jspace['firstName'] . ' ' . $profile->jspace['lastName']); ?></h1>

<h3><?php echo JText::_('COM_JSPACE_ITEM_METADATA'); ?></h3>
<ul>
	<?php foreach( $item->getMetadatas( true ) as $metadata ): ?>
		<li><?php echo $crosswalk->_($metadata->name) . " : " . $metadata->value; ?></li>
	<?php endforeach; ?>
</ul>

<?php $bundle = $item->getBundle( JSpaceTableBundle::BUNDLETYPE_ORIGINAL ); ?>
<h3><?php echo JText::sprintf("COM_JSPACE_ITEM_BUNDLE", $bundle->_getDisplayType()); ?></h3>
<ul>
	<?php foreach( $bundle->getBitstreams() as $bitstream ): ?>
		<li><?php echo $bitstream->file; ?></li>
	<?php endforeach; ?>
</ul>

<?php foreach( $item->getBundles() as $bundle ): ?>
	<?php if($bundle->type == JSpaceTableBundle::BUNDLETYPE_ORIGINAL ): continue; ?>
	<?php endif; ?>
	<h3><?php echo JText::sprintf("COM_JSPACE_ITEM_BUNDLE", $bundle->_getDisplayType()); ?></h3>
	<ul>
		<?php foreach( $bundle->getBitstreams() as $bitstream ): ?>
			<li><?php echo $bitstream->file; ?></li>
		<?php endforeach; ?>
	</ul>
<?php endforeach; ?>

<p><?php echo JText::_('COM_JSPACE_ITEM_LINK'); ?> <a href="<?php echo $link; ?>"><?php echo $link; ?></a></p>

