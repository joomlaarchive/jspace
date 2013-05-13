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
 * Michał Kocztorz				<michalkocztorz@wijiti.com> 
 * 
 */

jimport('jspace.factory');

JLoader::discover('JSpaceView', JPATH_BASE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_jspace' . DIRECTORY_SEPARATOR . 'views', true, true);

class JSpaceHelperTree
{
	/**
	 * 
	 * @var JSpaceViewTree
	 */
	private $_view = null;
	
	/**
	 * 
	 * @var JSpaceRepositoryCategory
	 */
	protected $_category = null;
	
	public function __construct( $root, $class ) {
		$this->_category = $root;
		$this->_view = new JSpaceViewTree();
		$this->_view->addTemplatePath(array(
				JPATH_BASE . "/components/com_jspace/views/tree/tmpl/",
				JPATH_THEMES . "/" . JFactory::getApplication()->getTemplate() . "/html/com_jspace/tree/tmpl/"
		));
		$this->_view->assignRef('category', $this->_category);
		$this->_view->assignRef('class', $class);		
	}
	
	/**
	 * 
	 * @param JSpaceRepositoryCategory $root
	 * @param string $class
	 * 
	 * @return string
	 */
	public static function html( JSpaceRepositoryCategory $root, $class = null) {
		$tree = new JSpaceHelperTree($root, $class);
		return $tree->categoryTreeNode();
	}
	
	/**
	 * 
	 * @param JSpaceRepositoryCategory $category
	 * 
	 * @return string
	 */
	public function categoryTreeNode() {
		$children = array();
		foreach( $this->_category->getChildren() as $id => $sub ) {
			$tree = new JSpaceHelperTree($sub, $class);
			$children[ $id ] = $tree->categoryTreeNode();
		}
		$this->_view->assignRef('children', $children);
		return $this->_view->loadTemplate('node');
	}
}