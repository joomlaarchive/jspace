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


/**
 * @package     JSpace
 * @subpackage  Repository
 */
class JSpaceRepositoryDspaceConfiguration extends JSpaceRepositoryConfiguration
{
	public function buildOptionsArray( $options = null ){
		$config = JSpaceFactory::getConfig();
		
		$options = is_null($options) ? array() : $options;
		$this->_options = array(
			'driver'	=> 'DSpace',
			'url' 		=> $config->get( 'DSpace_rest_url'),
			'username' 	=> $config->get( 'DSpace_username'),
			'password' 	=> $config->get( 'DSpace_password'),
			'base_url' 	=> $config->get( 'DSpace_base_url'),
			'mapper' 	=> JSpaceFactory::getMapper( $config->get('DSpace_crosswalk') ),
			'cache' 	=> array(
					'enabled' 	=> (bool)$config->get( 'DSpace_cache_enabled', false ),
					'instance'	=> $config->get( 'DSpace_cache_instance', 'default' ),
			),
		);
		
		$this->_options = array_replace_recursive($this->_options, $options);
		
		$this->_configuration = array(
			'driver'	=> $this->_options['driver'],
			'url' 		=> $this->_options['url'],
			'username' 	=> $this->_options['username'],
			'password' 	=> $this->_options['password'],
			'base_url' 	=> $this->_options['base_url'],
		);
	}
}




