<?php
/**
 * A repository config class.
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

jimport('jspace.repository.configuration');

/**
 * @package     JSpace
 * @subpackage  Repository
 */
class JSpaceConfiguration
{
	const ADMIN_EMAIL					= 'admin_email';
	const LIMIT_ITEMS					= 'limit_items';
	const DRIVER						= 'driver';
	const STORAGE_DIRECTORY				= 'storage_directory';
	const SHOW_TRANSLATION_KEYS 		= 'show_translation_keys';
	const SHOW_UNMAPPED_METADATA		= 'show_unmapped_metadata';
	const ON_ARCHIVE_GROUP_TO_NOTIFY	= 'onarchive';
	const ON_ARCHIVE_NOTIFY_RECURSIVE	= 'onarchiverecursive';
	
	/**
	 * 
	 * @var JSpaceConfiguration
	 */
	protected static $_instance = null;
	
	/**
	 * 
	 * @return JSpaceConfiguration
	 */
	public static function getInstance() {
		if( is_null(self::$_instance) ) {
			self::$_instance = new JSpaceConfiguration();
		}
		return self::$_instance;
	}
	
	/**
	 * 
	 * @var JRegistry
	 */
	protected $_componentConfiguration;
	
	public function __construct() {
		$this->_componentConfiguration = JSpaceFactory::getConfig();
	}
	
	public function get( $key, $default = null ) {
		return $this->_componentConfiguration->get($key, $default);
	}
}




