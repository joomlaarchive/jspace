<?php
/**
 * Default display for details about a single DSpace collection.
 * 
 * @author		$LastChangedBy$
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

require_once(JPATH_COMPONENT.DS.'helpers'.DS.'metadata.php');
?>

<?php if ($this->get("Data")) : ?>

	<h2><?php echo $this->get("Data")->name; ?></h2>

	<?php if ($this->get("Data")->shortDescription) : ?>
		<div><?php echo $this->get("Data")->shortDescription; ?></div>
	<?php endif; ?>

	<?php if ($this->get("Data")->introText) : ?>
		<div><?php echo $this->get("Data")->introText; ?></div>
	<?php endif; ?>
	
	<?php foreach ($this->get("Items") as $item) : ?>
		<div class="jspace-items">
			<div class="jspace-title"><a href="<?php echo JRoute::_("index.php?option=com_jspace&view=item&id=".$item->id); ?>"><?php echo $item->name; ?></a></div>
		
			<?php if (count($item->thumbnails) == 1) : ?>
				<div class="jspace-thumbnail"><img src="<?php echo $item->thumbnails[0]->url; ?>"/></div>
			<?php endif; ?>
			<div>				
				<?php if (JSpaceMetadata::getElementAsString($item->metadata, "DC.creator")) : ?>
					<div>				
						<div><?php echo JSpaceMetadata::getElementAsString($item->metadata, "DC.creator"); ?>&nbsp;</div>
					</div>
				<?php endif; ?>
				
				<?php if (JSpaceMetadata::getElementAsString($item->metadata, "DC.description")) : ?>
					<div><?php echo JSpaceMetadata::getElementAsString($item->metadata, "DC.description"); ?></div>
				<?php endif; ?>

				<?php if (JSpaceMetadata::getElementAsString($item->metadata, "DC.identifier", "DCTERMS.URI")) : ?>
					<div><?php echo JSpaceMetadata::getElementAsString($item->metadata, "DC.identifier", "DCTERMS.URI"); ?></div>
				<?php endif; ?>
				
				<?php if (count($item->thumbnails) > 1) : ?>
					<ul class="jspace-array-thumbnails">
					<?php foreach ($item->thumbnails as $thumbnail) : ?>
						<li><img src="<?php echo $thumbnail->url; ?>"/></li>
					<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
	
<?php endif; ?>