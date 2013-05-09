<?php
/**
 * Category class
 * 
 * @package		JSpace
 * @subpackage	Repository
 * @copyright	Copyright (C) 2012 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace library for Joomla!.

   The JSpace library for Joomla! is free software: you can redistribute it 
   and/or modify it under the terms of the GNU General Public License as 
   published by the Free Software Foundation, either version 3 of the License, 
   or (at your option) any later version.

   The JSpace library for Joomla! is distributed in the hope that it will be 
   useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with the JSpace library for Joomla!.  If not, see 
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 * Name							Email
 * Michał Kocztorz				<michalkocztorz@wijiti.com> 
 * 
 */
 
defined('JPATH_PLATFORM') or die;


/**
 * 
 * @author Michał Kocztorz
 * @package     JSpace
 * @subpackage  Repository
 */
abstract class JSpaceRepositoryCategory extends JObject
{
	/**
	 * 
	 * @var JSpaceRepository
	 */
	protected $_repository = null;
	
	protected $_id = null;
	
	protected $_name = null;
	
	protected $_children = null;

	/**
	 * @param JSpaceRepositoryItem $item
	 * @param mixed $id
	 */
	public function __construct( $id, $repository ) {
		$this->_id = $id;
		$this->_repository = $repository;
		$this->_init();
	}
	
	/**
	 * 
	 * @return JSpaceRepository
	 */
	public function getRepository() {
		return $this->_repository;
	}
	
	abstract public function _init();

	
	public function getId() {
		return $this->_id;
	}
	
	public function getName() {
		return $this->_name;
	}
	
	/**
	 * 
	 * @return array
	 */
	public function getChildren() {
		if( is_null( $this->_children ) ) {
			$this->_children = $this->_getChildren();
		}
		return $this->_children;
	}
	
	abstract protected function _getChildren();
	
	public function getItems() {
		$this->_getItems();
	}
	
	abstract protected function _getItems();
	
	/**
	 * 
	 * @return JSpaceRepositoryCategory
	 */
	public function getParent() {
		return $this->_getParent();
	}

	abstract protected function _getParent(); 
	
	abstract public function isRoot(); 
}




