<?php
/**
 * @version	$Id$
 * @copyright	Copyright (C) 2012 Wijiti Pty Ltd, Inc. All rights reserved.
 * @license	http://www.gnu.org/licenses/gpl-3.0.html
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
jimport('joomla.user.helper');
jimport('joomla.utilities.arrayhelper');

/**
 * DSpace User plugin.
 *
 * Sets user data so that JSpace can carry out 3rd party authentication and 
 * authorization.
 *
 * @package	JSpace.Plugin
 * @subpackage	User.DSpace
 */
class plgUserDSpace extends JPlugin
{
	/**
	 * This method should handle any login logic and report back to the subject
	 *
	 * @param	array	$user		Holds the user data
	 * @param	array	$options	Array holding options (remember, autoregister, group)
	 *
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	public function onUserLogin($user, $options = array())
	{
		// Register the needed session variables
		$session = JFactory::getSession();

		$dspacePassword = JArrayHelper::getValue($user, "dspacePassword");
		$dspaceId = JArrayHelper::getValue($user, "dspaceId");

		$password = JUserHelper::getCryptedPassword($dspacePassword);

		$session->set('jspace.user.password', $password);		
		$session->set('jspace.user.id', $dspaceId);

		return true;
	}
}
