<?php
/**
 *
 * https://jspace.atlassian.net/wiki/display/JSPACE/JSpaceLog
 *
 * @author		$LastChangedBy: michalkocztorz $
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
 * Michał Kocztorz				<michalkocztorz@wijiti.com>
 *
 */
defined('JPATH_PLATFORM') or die;

class JSpaceLog {
	/**
	 * 
	 * @var JSpaceLog
	 */
	protected static $log = null;
	
	/**
	 * 
	 * @return JSpaceLog
	 */
	public static function initInstance() {
		if( is_null(JSpaceLog::$log) ) {
			JSpaceLog::$log = new JSpaceLog();
		}
		return JSpaceLog::$log;
	}
	
	protected $_setup = array();
	
	const CAT_INIT			= 'jspace.init';
	const CAT_REPOSITORY 	= 'jspace.repo';
	const CAT_JREST 		= 'jspace.jrest';
	
	public function __construct() {
		$this->_setup = array(
			'default' => array(
				'options' => array(
					'logger'	=> 'formattedtext',
					'text_file'	=> 'jspace.log'
				),
				'priorities' => JLog::ALL,
				'categories' => array(),
			),
			'repository' => array (
				'options' => array(
					'logger'	=> 'formattedtext',
					'text_file'	=> 'jspace.repository.log'
				),
				'priorities' => JLog::ALL,
				'categories' => array(JSpaceLog::CAT_REPOSITORY),
			),
			'critical' => array (
				'options' => array(
					'logger'	=> 'formattedtext',
					'text_file'	=> 'jspace.critical.log'
				),
				'priorities' => JLog::CRITICAL,
				'categories' => array(),
			),
			'info' => array (
				'options' => array(
					'logger'	=> 'formattedtext',
					'text_file'	=> 'jspace.info.log'
				),
				'priorities' => JLog::INFO,
				'categories' => array(),
			),
// 			'debug' => array (
// 				'options' => array(
// 					'logger'	=> 'formattedtext',
// 					'text_file'	=> 'jspace.debug.log'
// 				),
// 				'priorities' => JLog::DEBUG,
// 				'categories' => array(),
// 			),
		);
		
		foreach( $this->_setup as $name => $logger ){
			$options = JArrayHelper::getValue($logger, 'options', array('text_file'	=> 'defaut.log'));
			$priorities = JArrayHelper::getValue($logger, 'priorities', JLog::ALL);
			$categories = JArrayHelper::getValue($logger, 'categories', array());
			JLog::addLogger( $options, $priorities, $categories );
			JSpaceLog::add("Logger <$name> configured", JLog::INFO, JSpaceLog::CAT_INIT);
		}
	}
	
	/**
	 * Proxy method for log class. Based on configuration it may supress some of the logging if required. 
	 * 
	 * @param string $entry
	 * @param unknown_type $priority
	 * @param unknown_type $category
	 * @param unknown_type $date
	 */
	public static function add($entry, $priority = JLog::INFO, $category = '', $date = null) {
		JLog::add($entry, $priority, $category, $date );
	}
}