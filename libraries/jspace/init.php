<?php
/**
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
class JSpaceInit {
	/**
	 * Indicates if init was done.
	 * @var bool
	 */
	protected static $_done = false;
	
	public static function init() {
		if( JSpaceInit::$_done ) {
			return;
		}
		
		//init loggers
		JSpaceLog::initInstance();
		JSpaceLog::add('Initialized jspace logger', JLog::INFO, JSpaceLog::CAT_INIT);
		
		//register repository drivers
		JSpaceInit::initRepositoryDrivers();
		
		JSpaceInit::$_done = true;
	}
	
	/**
	 * Register all repository drivers
	 * 
	 */
	protected static function initRepositoryDrivers() {
		JSpaceLog::add('Registering repository drivers', JLog::DEBUG, JSpaceLog::CAT_INIT);
		try {
			/*
			 * Register default drivers for JSpace
			 */
			JSpaceLog::add('Registering DSpace', JLog::DEBUG, JSpaceLog::CAT_INIT);
			$dspacePath = JPATH_LIBRARIES . DIRECTORY_SEPARATOR . 'jspace' . DIRECTORY_SEPARATOR . 'repository' . DIRECTORY_SEPARATOR . 'dspace' . DIRECTORY_SEPARATOR;
			JSpaceRepositoryDriver::registerDriver('DSpace', array(
				'configXmlPath'	=> $dspacePath . 'adminConfig.xml',
				'classPrefix'	=> 'JSpaceRepositoryDspace',
				'basePath'		=> $dspacePath,
			));
			JSpaceLog::add('Registering fedora', JLog::DEBUG, JSpaceLog::CAT_INIT);
			$fedoraPath = JPATH_LIBRARIES . DIRECTORY_SEPARATOR . 'jspace' . DIRECTORY_SEPARATOR . 'repository' . DIRECTORY_SEPARATOR . 'fedora' . DIRECTORY_SEPARATOR;
			JSpaceRepositoryDriver::registerDriver('fedora', array(
				'configXmlPath'	=> $fedoraPath . 'adminConfig.xml',
				'classPrefix'	=> 'JSpaceRepositoryFedora',
				'basePath'		=> $fedoraPath,
			));
			
			JSpaceLog::add('Trigger onJSpaceRegisterDrivers', JLog::DEBUG, JSpaceLog::CAT_INIT);
			JPluginHelper::importPlugin('jspace');
			$dispatcher = JDispatcher::getInstance();
			$drivers = $dispatcher->trigger('onJSpaceRegisterDrivers');
			foreach( $drivers as $list ) {
				foreach( $list as $key => $options ) {
					try {
						JSpaceLog::add('Registering ' . $key, JLog::DEBUG, JSpaceLog::CAT_INIT);
						JSpaceRepositoryDriver::registerDriver($key, $options);
					}
					catch( Exception $e ) {
						JSpaceLog::add('Registering ' . $key . ' failed with exception: ' . $e->getMessage(), JLog::ERROR, JSpaceLog::CAT_INIT);
					}
				}
			}
		}
		catch( Exception $e ) {
			JSpaceLog::add('Registering drivers failed with exception: ' . $e->getMessage(), JLog::ERROR, JSpaceLog::CAT_INIT);
		}
		JSpaceLog::add('Finished registering repository drivers', JLog::DEBUG, JSpaceLog::CAT_INIT);
	}
}