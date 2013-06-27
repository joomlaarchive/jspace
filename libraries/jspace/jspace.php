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
class JSpace {
	/**
	 * 
	 * @var JSpace
	 */
	protected static $_current = null;
	
	public static function getInstance() {
		if( is_null( JSpace::$_current ) ) {
			JSpace::$_current = new JSpace();
			JSpace::$_current->init();
		}
		return JSpace::$_current;
	}
	
	/**
	 * 
	 * @var JDispatcher
	 */
	protected $_dispatcher = null;
	
	/**
	 * 
	 * @var JSpaceCacheManager
	 */
	protected $_cacheManager = null;
	
	public function __construct() {
		JPluginHelper::importPlugin('jspace');
		$this->_dispatcher = JDispatcher::getInstance();
	}
	
	public function init() {
		//init loggers
		JSpaceLog::initInstance();
		JSpaceLog::add('Initialized jspace logger', JLog::DEBUG, JSpaceLog::CAT_INIT);
		
		//register cache drivers
		$this->registerCacheDrivers();
		
		//register repository drivers
		$this->initRepositoryDrivers();
	}
	
	/**
	 * Shortcut for triggering events in JSpace.
	 * 
	 */
	public function trigger($event, $args = array()) {
		JSpaceLog::add('JSpace::trigger ' . $event, JLog::DEBUG, JSpaceLog::CAT_EVENT);
		return $this->_dispatcher->trigger($event, $args);
	}
	
	/**
	 * Register cache drivers.
	 */
	protected function registerCacheDrivers() {
		JSpaceLog::add('JSpace::registerCacheDrivers', JLog::DEBUG, JSpaceLog::CAT_INIT);
		$this->_cacheManager = JSpaceCacheManager::getInstance();
		/*
		 * The default cache instance is jselective.
		 */
		$basePath = JPATH_LIBRARIES . DIRECTORY_SEPARATOR . 'jspace' . DIRECTORY_SEPARATOR . 'repository' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'jselective' . DIRECTORY_SEPARATOR;
		$options = array(
			'options'		=> array('driver'	=> 'jselective'),
			'classPrefix'	=> 'JSpaceRepositoryCacheJselective',	
			'basePath'		=> $basePath,
		);
		$this->_cacheManager->registerDriver('default', $options);
		
		$drivers = $this->trigger('onJSpaceRegisterCacheDrivers');
		foreach( $drivers as $list ) {
			foreach( $list as $instance => $options ) {
				$this->_cacheManager->registerDriver($instance, $options);
			}
		}
	}
	
	/**
	 * 
	 * @return JSpaceCacheManager
	 */
	public function getCacheManager() {
		return $this->_cacheManager;
	}
	
	/**
	 * Register all repository drivers
	 *
	 */
	protected function initRepositoryDrivers() {
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

			$drivers = $this->trigger('onJSpaceRegisterDrivers');
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