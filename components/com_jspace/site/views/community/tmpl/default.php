<?php
/**
 * Default display for details about a single DSpace community.
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
?>

<?php if ($this->get("Data")) : ?>

	<h2><?php echo $this->get("Data")->name; ?></h2>

	<?php if ($this->get("Data")->shortDescription) : ?>
		<div><?php echo $this->get("Data")->shortDescription; ?></div>
	<?php endif; ?>

	<?php if ($this->get("Data")->introductoryText) : ?>
		<div><?php echo $this->get("Data")->introductoryText; ?></div>
	<?php endif; ?>
	
	<?php if (count($this->get("Data")->subCommunities)) : ?>
		<h2><?php echo JText::_("COM_JSPACE_COMMUNITIES_SUBCOMMUNITIES_IN_COMMUNITY"); ?></h2>
		
		<ul>
		<?php foreach ($this->get("Data")->subCommunities as $subCommunity) : ?>
			<li><a href="<?php echo JRoute::_("index.php?option=com_jspace&view=community&id=".$subCommunity->id); ?>"><?php echo $subCommunity->name; ?></a></li>
		<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<h2><?php echo JText::_("COM_JSPACE_COMMUNITIES_COLLECTIONS_IN_COMMUNITY"); ?></h2>
	
	<ul>
	<?php foreach ($this->get("Data")->collections as $collection) : ?>
		<li><a href="<?php echo JRoute::_("index.php?option=com_jspace&view=collection&id=".$collection->id); ?>"><?php echo $collection->name; ?></a></li>
	<?php endforeach; ?>
	</ul>
	
<?php endif; ?>