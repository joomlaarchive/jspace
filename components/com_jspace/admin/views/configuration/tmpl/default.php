<?php 
/**
 * A form view for adding/editing JSolrIndex configuration.
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

defined( '_JEXEC' ) or die( 'Restricted access' );

$application = JFactory::getApplication("administrator");

$document = JFactory::getDocument();

JToolBarHelper::title(JText::_('Configuration'), 'config.png');

JToolBarHelper::save();
JToolBarHelper::cancel();
?>

<form autocomplete="off" name="adminForm" method="post" action="index.php">
	<div id="config-document">
		<div id="page-site" style="display: block;">
			<table class="noshow">
				<tbody>
					<tr>
						<td width="65%">
							<fieldset class="adminform">
								<legend>DSpace Settings</legend>
	
								<table cellspacing="1" class="admintable">
									<tbody>
										<tr>
											<td class="key">
												<span class="editlinktip hasTip"><?php echo JText::_("COM_JSPACE_BASE_URL"); ?></span>
											</td>
											<td>
												<input type="text" value="<?php echo $this->getModel()->getParam("base_url"); ?>" size="50" id="base_url" name="base_url" class="text_area"/>
											</td>
										</tr>
										<tr>
											<td class="key">
												<span class="editlinktip hasTip"><?php echo JText::_("COM_JSPACE_REST_URL"); ?></span>
											</td>
											<td>
												<input type="text" value="<?php echo $this->getModel()->getParam("rest_url"); ?>" size="50" id="rest_url" name="rest_url" class="text_area"/>
											</td>
										</tr>
										<tr>
											<td class="key">
												<span class="editlinktip hasTip"><?php echo JText::_("COM_JSPACE_REST_USERNAME"); ?></span>
											</td>
											<td>
												<input type="text" value="<?php echo $this->getModel()->getParam("username"); ?>" size="50" id="username" name="username" class="text_area"/>
											</td>
										</tr>
										<tr>
											<td class="key">
												<span class="editlinktip hasTip"><?php echo JText::_("COM_JSPACE_REST_PASSWORD"); ?></span>
											</td>
											<td>
												<input type="text" value="<?php echo $this->getModel()->getParam("password"); ?>" size="50" id="password" name="password" class="text_area"/>
											</td>
										</tr>
									</tbody>																	
								</table>
							</fieldset>
							<fieldset class="adminform">
								<legend>Prerequisite Components</legend>
	
								<table cellspacing="1" class="admintable">
									<tbody>
										<tr>
											<td class="key">
												<span class="editlinktip hasTip"><?php echo JText::_("COM_JSPACE_GD_LIBRARY"); ?></span>
											</td>
											<td>
												<?php 
												if ($this->getModel()->isGDInstalled()) :
													echo JText::_("COM_JSPACE_IS_INSTALLED");
												else :
													echo JText::_("COM_JSPACE_IS_NOT_INSTALLED");
												endif;
												?>
											</td>
										</tr>
										<tr>
											<td class="key">
												<span class="editlinktip hasTip"><?php echo JText::_("COM_JSPACE_FFMPEG_LIBRARY"); ?></span>
											</td>
											<td>
												<?php 
												if ($this->getModel()->isFFMPEGInstalled()) :
													echo JText::_("COM_JSPACE_IS_INSTALLED");
												else :
													echo JText::_("COM_JSPACE_IS_NOT_INSTALLED");
												endif;
												?>
											</td>
										</tr>
									</tbody>
								</table>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<div class="clr"></div>
	
	<input type="hidden" value="com_jspace" name="option"/>
	<input type="hidden" value="" name="task"/>
	<input type="hidden" value="configuration" name="view"/>
</form>