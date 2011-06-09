<?php
/**
 * Default display for details about a single DSpace item.
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

JHTML::_('behavior.modal');

require_once(JPATH_COMPONENT.DS.'helpers'.DS.'metadata.php');
?>

<?php if ($this->get("Data")) : ?>

	<h2><?php echo JSpaceMetadata::getElementAsString($this->get("Data")->metadata, "DC.title"); ?></h2>

	<?php if (JSpaceMetadata::getElementAsString($this->get("Data")->metadata, "DC.title")) : ?>
		<div class="jspace-item">
			<div class="dc-element-name"><?php echo JText::_("COM_JSPACE_DC_TITLE_LABEL"); ?>:</div>
			<div class="dc-element-value"><?php echo JSpaceMetadata::getElementAsString($this->get("Data")->metadata, "DC.title"); ?></div>
		</div>
	<?php endif; ?>

	<?php if (JSpaceMetadata::getElementAsString($this->get("Data")->metadata, "DC.creator")) : ?>
		<div class="jspace-item">
			<div class="dc-element-name"><?php echo JText::_("COM_JSPACE_DC_CREATOR_LABEL"); ?>:</div>
			<div class="dc-element-value">
				<?php foreach (JSpaceMetadata::getElementAsArray($this->get("Data")->metadata, "DC.creator") as $creator) : ?>
					<div><?php echo $creator; ?></div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if (JSpaceMetadata::getElementAsString($this->get("Data")->metadata, "DCTERMS.abstract")) : ?>
		<div class="jspace-item">
			<div class="dc-element-name"><?php echo JText::_("COM_JSPACE_DC_ABSTRACT_LABEL"); ?>:</div>
			<div class="dc-element-value"><?php echo JSpaceMetadata::getElementAsString($this->get("Data")->metadata, "DCTERMS.abstract"); ?></div>
		</div>
	<?php endif; ?>

	<?php if (JSpaceMetadata::getElementAsString($this->get("Data")->metadata, "DC.identifier", "DCTERMS.URI")) : ?>
		<div class="jspace-item">
			<div class="dc-element-name"><?php echo JText::_("COM_JSPACE_DC_HANDLE_LABEL"); ?>:</div>
			<div class="dc-element-value"><?php echo JSpaceMetadata::getElementAsString($this->get("Data")->metadata, "DC.identifier", "DCTERMS.URI"); ?></div>
		</div>
	<?php endif; ?>
	
	<?php if (JSpaceMetadata::getElementAsString($this->get("Data")->metadata, "DCTERMS.issued")) : ?>
		<div class="jspace-item">
			<div class="dc-element-name"><?php echo JText::_("COM_JSPACE_DC_DATE_ISSUED_LABEL"); ?>:</div>
			<div class="dc-element-value">
				<?php foreach (JSpaceMetadata::getElementAsArray($this->get("Data")->metadata, "DCTERMS.issued") as $issued) : ?>
					<div><?php echo $issued; ?></div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if (JSpaceMetadata::getElementAsString($this->get("Data")->metadata, "DC.subject")) : ?>
		<div class="jspace-item">
			<div class="dc-element-name"><?php echo JText::_("COM_JSPACE_DC_SUBJECT_LABEL"); ?>:</div>
			<div class="dc-element-value">
				<?php foreach (JSpaceMetadata::getElementAsArray($this->get("Data")->metadata, "DC.subject") as $subject) : ?>
					<div><?php echo $subject; ?></div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
	
	<div class="jspace-item">
		<div class="dc-element-name"><?php echo JText::_("COM_JSPACE_DC_COLLECTION_LABEL"); ?>:</div>
		<div class="dc-element-value">
			<?php echo JHTML::link(JRoute::_("index.php?option=com_jspace&view=collection&id=".$this->get("Data")->owningCollection->id), $this->get("Data")->owningCollection->name); ?>
		</div>
	</div>	

	<div id="jspaceBitstreams" class="jspace-bitstreams">
	<?php foreach ($this->get("OriginalBitstreams") as $bitstream) : ?>
		<div class="jspace-bitstream">
			<?php if ($thumbnail = $this->getModel()->getThumbnail($bitstream)) : ?>
			<div class="jspace-bitstream-thumbnail">
				<img src="<?php echo $thumbnail; ?>"/>
			</div>
			<?php endif; ?>
			
			<ul class="jspace-bitstream-details">
				<li class="jspace-file-name"><a href="<?php echo $bitstream->url; ?>"><?php echo $bitstream->name; ?></a></li>
				<li class="jspace-file-description"><?php echo $bitstream->description; ?></li>
				<li class="jspace-file-size"><?php echo $this->getModel()->formatFileSize($bitstream->size); ?></li>
				<li class="jspace-file-type"><?php echo $bitstream->formatDescription; ?></li>
				<li class="jspace-file-actions">
					<?php echo $this->getModel()->getPreviewLink($bitstream); ?>
					<?php echo JHTML::link($bitstream->url, "", array("class"=>"jspace-download", "title"=>JText::_("COM_JSPACE_BITSTREAM_DOWNLOAD"))); ?>
				</li>
			</ul>
		</div>
	<?php endforeach; ?>
	</div>
<?php endif; ?>