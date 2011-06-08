<?php 
/**
 * A helper that displays a list of communities.
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

class JSpaceCommunity
{
	public static function tree($root, $class = null)
	{
		$class = "jspace-community-tree" . ($class ? " $class" : "");

		$html = "<ul class=\"$class\">";
		
		$community = new JSpaceCommunity();
		$html .= $community->_getCommunitiesHTML($root);

		$html .= "</ul>";
		
		return $html;
	}
	
	private function _getCommunitiesHTML($communities)
	{
		$html = "";
		
		foreach ($communities as $community) {
	    	$url = new JURI("index.php");
    		$url->setVar("option", "com_jspace");
    		$url->setVar("view", "community");
    		$url->setVar("id", $community->id);
    		
    		$html .= "<li class=\"jspace-community\">". JHTML::_("link", JRoute::_($url->toString()), $community->name);
    		
	    	$html .= "<ul>";
	    	
	    	if (isset($community->collections)) {
		    	foreach ($community->collections as $collection) {
		    		$url = new JURI("index.php");
		    		$url->setVar("option", "com_jspace");
		    		$url->setVar("view", "collection");
		    		$url->setVar("id", $collection->id);
		    		
		    		$html .= "<li class=\"jspace-collection\">". JHTML::_("link", JRoute::_($url->toString()), $collection->name) . "</li>";
		    	}
	    	}
	    		    	
	    	if (isset($community->subCommunities)) {
	    		$html .= $this->_getCommunitiesHTML($community->subCommunities);
	    	}
	    	$html .= "</ul>";
	    	$html .= "</li>";
		}
		
		return $html;
	}
}