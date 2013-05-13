<?php
/**
 * Supports a collection picker.
 * 
 * @author		$LastChangedBy: spauldingsmails $
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

defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');
jimport("joomla.filesystem.file");
jimport('joomla.error.log');
jimport('joomla.utilities');
jimport('jspace.factory');

class JSpaceFormFieldCollection extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var         string
	 * @since       1.6
	 */
	protected $type = 'Collection';
	
	protected $_collections = array();

	protected function getInput()
	{
		$this->_collections[] = JHTML::_("select.option", 0, JText::_("Select a collection"));
		
		$rootCategory = JSpaceFactory::getRepository()->getCategory();
		$this->_getCollections( $rootCategory );
		
		return JHTML::_("select.genericlist", $this->_collections, $this->name, null, "value", "text", intval($this->value), $this->id);
	}

	/**
	 * 
	 * @param JSpaceRepositoryCategory $category
	 */
	protected function _getCollections( JSpaceRepositoryDspaceCategory $category ) {
		if( $category->dspaceIsCollection() ) {
			$this->_collections[] = JHTML::_("select.option", $category->dspaceGetCollection()->getId(), $category->dspaceGetCollection()->getName()); 
		}
		foreach( $category->getChildren() as $sub ) {
			$this->_getCollections($sub);
		}
	}
}




