<?php
/**
 * @version	$Id$
 * @copyright	Copyright (C) 2012 Wijiti Pty Ltd, Inc. All rights reserved.
 * @license	http://www.gnu.org/licenses/gpl-3.0.html
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
jimport('jspace.factory');

/**
 *
 * @package	JSpace.Plugin
 * @subpackage	JSpace.Init
 */
class plgSystemJspaceconfig extends JPlugin
{
	public function onContentPrepareForm($form, $data)
	{
		if( !($form instanceof JForm) ) {
			return;
		}
		
		$app = JFactory::getApplication();
		/*
		 * Make sure to manipulate only the config of JSpace.
		 */
		if( $app->isAdmin() && $form->getName() == 'com_config.component' && $app->input->getString('component') == 'com_jspace' ) {
			JSpaceLog::add('System Config plugin: Adding repository tabs co configuration', JLog::DEBUG, JSpaceLog::CAT_INIT);
			JSpaceInit::init(); //make sure it is initialized (drivers registered). If already initialized, then no harm done.
			$drivers = JSpaceRepositoryDriver::listDriverKeys();
			JSpaceLog::add('System Config plugin: Found registered drivers: ' . count($drivers), JLog::DEBUG, JSpaceLog::CAT_INIT );
			foreach( $drivers as $key ) {
				JSpaceLog::add('System Config plugin: Loading config tab for ' . $key, JLog::DEBUG, JSpaceLog::CAT_INIT );
				$driver = JSpaceRepositoryDriver::getInstance( $key );
				$form->loadFile( $driver->getConfigXmlPath() );
			}
		}
	}
}

