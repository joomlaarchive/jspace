<?php
/**
 * @version	$Id$
 * @copyright	Copyright (C) 2012 Wijiti Pty Ltd, Inc. All rights reserved.
 * @license	http://www.gnu.org/licenses/gpl-3.0.html
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * Jomsocial community alerts JSpace plugin.
 *
 * Pushes posts to the Jomsocial wall.
 *
 * @package	JSpace.Plugin
 * @subpackage	JSpace.Community
 */
class plgJspaceCommunityposts extends JPlugin
{
	/**
	 * This event is triggered when a package is being prepared for SWORD deposit.
	 *
	 * @param SwordPackagerMetsSwap $package The package to be deposited.
	 */
	public function onDepositPreparePackage($package)
	{
		
	}
	
	/**
	 * This event is triggered after an item has been successfully deposited into the repository.
	 *
	 * @param SwordPackagerMetsSwap $package The deposited package.
	 * @param SwordAppEntry $response The SWORD response.
	 */
	public function onDepositPreparePackage($package, $response)
	{
		
	}
}
