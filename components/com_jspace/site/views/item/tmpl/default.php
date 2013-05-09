<?php
/**
 * Default display for details about a single repository item.
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
 * Michał Kocztorz				<michalkocztorz@wijiti.com> 
 * 
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

JHTML::_('behavior.modal');

/*
 * Available variables:
 */

/* @var $item JSpaceRepositoryItem */
$item = $this->item;

/* @var $repository JSpaceRepository */
$repository = $this->repository;

/* @var $model JSpaceModelItem */
$model = $this->model;
?>

<?php if( $item instanceof JSpaceRepositoryItem ): ?>
	<div class="jspace-item-body">
		<!-- one special metadata value -->
		<h2><?php echo $item->getMetadata('title'); ?></h2>
		
		<div class="jspace-item-metadata">
			<?php foreach( $item->getMetadata() as $key => $value ): ?>
				<div class="jspace-item">
					<div class="dc-element-name"><?php echo JText::_( $model->getItemMetadataTranslationKey( $key ) ); ?>:</div>
					<div class="dc-element-value"><?php echo $value; ?></div>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="jspace-item-bundles">
			<?php foreach( $item->getBundles() as $type => $bundle ): /* @var $bundle JSpaceRepositoryBundle */ ?>
				<h3><?php echo JText::_( 'COM_JSPACE_ITEM_BUNDLE_TYPE_' . strtoupper( $type ) ); ?></h3>
				<ul class="jspace-item-bundle">
					<?php foreach( $bundle->getBitstreams() as $bitstream ): /* @var $bitstream JSpaceRepositoryBitstream */ ?>
						<li>
							<ul class="jspace-bitstream-details">
								<li class="jspace-file-name"><a href="<?php echo $bitstream->getUrl(); ?>"><?php echo $bitstream->getName(); ?></a></li>
								<li class="jspace-file-description"><?php echo $bitstream->getDescription(); ?></li>
								<li class="jspace-file-size"><?php echo $model->formatFileSize( $bitstream->getSize() ); ?></li>
								<li class="jspace-file-type"><?php echo $bitstream->getFormatDescription(); ?></li>
								<!-- 
								<li class="jspace-file-actions">
									<?php //echo $this->getModel()->getPreviewLink($bitstream); ?>
									<?php //echo JHTML::link($bitstream->url, "", array("class"=>"jspace-download", "title"=>JText::_("COM_JSPACE_BITSTREAM_DOWNLOAD"))); ?>
								</li>
								 -->
							</ul>						
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
		</div>
		
		<div class="jspace-item">
			<div class="dc-element-name"><?php echo JText::_("COM_JSPACE_DC_COLLECTION_LABEL"); ?>:</div>
			<div class="dc-element-value">
				<?php echo JHTML::link(JRoute::_("index.php?option=com_jspace&view=collection&id=" . $item->getCollection()->getId()), $item->getCollection()->getName() ); ?>
			</div>
		</div>	
		
	</div>
<?php else: ?>
	<div class="warning"><?php echo JText::_('COM_JSPACE_ITEM_NOT_FOUND'); ?></div>
<?php endif; ?>



