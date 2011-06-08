<?php
/**
 * Display for a flash video.
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

JHTML::_('behavior.mootools');

$document = JFactory::getDocument();

$document->addScript(JURI::base()."media/com_jspace/js/flowplayer-3.2.6.min.js");

$document->addScriptDeclaration("
window.addEvent(\"domready\", function() {
	switch (MooTools.version) {
		case \"1.11\":
		case \"1.12\":
			\$(\"video\").setStyle(\"width\", window.getWidth());
			\$(\"video\").setStyle(\"height\", window.getHeight());
			break;
			
		case \"1.2.4\":
		default:
			\$(\"video\").setStyle(\"width\", window.getSize().x);
			\$(\"video\").setStyle(\"height\", window.getSize().y);
			break;
	}

	\$f(
		\"video\", 
		\"".JURI::base()."/media/com_jspace/flowplayer/flowplayer-3.2.7.swf"."\", {
	   		clip: {
	       		autoPlay: false,
	   			autoBuffering: true,
	   			scale: \"fit\"     		
	       	}
		}
	);
});
");
?>
<a 
	href="<?php echo $this->get("Config")->rest_url . "/bitstream/" . $this->get("Id") . "/receive"; ?>"
	style="display:block;"  
	id="video">
</a>