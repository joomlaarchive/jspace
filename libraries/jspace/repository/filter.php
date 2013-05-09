<?php
/**
 * A filter class.
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
 * @package     JSpace
 * @subpackage  Repository
 */
abstract class JSpaceRepositoryFilter extends JObject
{
	const SORT_BY_EXT_POPULARITY = 1;
	
	/**
	 * This field has no default purpose, but to allow the subclasses to use different mechanisms for retrieveing data.
	 * 
	 * @var string
	 */
	protected $_name = 'default';
	
	/**
	 * 
	 * @var number
	 */
	protected $_limitstart = 0;
	
	/**
	 * 
	 * @var number
	 */
	protected $_limit = 10;

	/**
	 * 
	 * @var array
	 */
	protected $_filterBy = array();
	
	/**
	 * 
	 * @var array
	 */
	protected $_sortBy = array();
	
	/**
	 * 
	 * @var array
	 */
	protected $_sortByExt = array();
	
	/**
	 * 
	 * @var JSpaceRepository
	 */
	protected $_repository = null;
	
	/**
	 * 
	 * $options['limitstart'] 	- limit start condition
	 * $options['limit'] 		- limit count condition
	 * $options['filterBy'] 	- filter by fields stored with item (all those that are translated by crosswalk)
	 * $options['sortBy'] 		- sort by fields stored with item (all those that are translated by crosswalk)
	 * 
	 * $options['sortByExt'] 	- sort by fields NOT stored with item (all those that are not translated by crosswalk)
	 * 
	 * @param JSpaceRepository $repository
	 * @param array $options
	 */
	public function __construct( $repository, $options ) {
		$this->_repository = $repository;
		
		$this->_name		= JArrayHelper::getValue($options, 'name', 0);

		$this->_limitstart 	= JArrayHelper::getValue($options, 'limitstart', 0);
		$this->_limit		= JArrayHelper::getValue($options, 'limit', 0);
		
		$this->_filterBy	= JArrayHelper::getValue($options, 'filterBy', array());
		$this->_sortBy		= JArrayHelper::getValue($options, 'sortBy', array());

		$this->_sortByExt	= JArrayHelper::getValue($options, 'sortByExt', array());
		
		$this->_init($options);
	}
	
	/**
	 * 
	 */
	public function _init( $options ) {
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->_name;
	}
	
	/**
	 * 
	 * @return JSpaceRepository
	 */
	public function getRepository() {
		return $this->_repository;
	}
	
	/**
	 * 
	 * @return number
	 */
	public function getLimitstart() {
		return $this->_limitstart;
	}
	
	/**
	 * 
	 * @return number
	 */
	public function getLimit() {
		return $this->_limit;
	}
	
	/**
	 * 
	 * @return array
	 */
	public function getFilterBy() {
		return $this->_filterBy;
	}
	
	/**
	 * @return array
	 */
	public function getSortBy() {
		return $this->_sortBy;
	}
	
	/**
	 * 
	 * @return array
	 */
	public function getSortByExt() {
		return $this->_sortByExt;
	}
	
	/**
	 * @return array of JSpaceRepositoryItem
	 */
	abstract public function getItems();
}




