<?php
/**
 * @version	$Id$
 * @copyright	Copyright (C) 2012 Wijiti Pty Ltd, Inc. All rights reserved.
 * @license	http://www.gnu.org/licenses/gpl-3.0.html
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');
jimport('joomla.application.component.helper');

/**
 * DSpace Authentication plugin.
 *
 * @package		JSpace.Plugin
 * @subpackage	Authentication.DSpace
 * @since 1.5
 */
class plgAuthenticationDSpace extends JPlugin {
	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @access	public
	 * @param   array	$credentials Array holding the user credentials
	 * @param	array   $options	Array of extra options
	 * @param	object	$response	Authentication response object
	 * @return	boolean
	 * @since 1.5
	 */
	function onUserAuthenticate($credentials, $options, & $response) {
		$message = '';
		$success = 0;
		$dspaceId = 0;

		$component = JComponentHelper::getComponent("com_jspace", true);

		if ($component->enabled) {
			// check if we have a username and password
			if (strlen($credentials['username']) && strlen($credentials['password'])) {
				$blacklist = explode(',', $this->params->get('user_blacklist', ''));
				// check if the username isn't blacklisted
				if (!in_array($credentials['username'], $blacklist)) {	
					$params = $component->params;

					$path = JPATH_BASE.DS."administrator".DS."components".DS."com_jspace".DS."helpers".DS."restrequest.php";

					if (JFile::exists($path)) {
						require_once($path);

						$request = new stdClass();
						$request->email = $credentials['username'];
						$request->password = $credentials['password'];
						$request->action = "login";

						$rest = new JSpaceRestRequestHelper($params->get("rest_url").'/users.json', 'POST', json_encode($request));
						$rest->execute();

						if (intval($rest->getResponseBody()) > 0) {
							$dspaceId = intval($rest->getResponseBody());
							$message = JText::_('JGLOBAL_AUTH_ACCESS_GRANTED');
							$success = 1;
						} else {
							$message = JText::_('JGLOBAL_AUTH_ACCESS_DENIED');
						}
					} else {
						$message = "There was a error while trying to authenticate your details.";
					}
				} else {
					// the username is black listed
					$message = 'User is blacklisted';
				}
			} else {
				$message = JText::_('JGLOBAL_AUTH_USER_BLACKLISTED');
			}
		} else {
			$message = JText::_('PLG_AUTHENTICATION_DSPACE_COM_JSPACE_NOT_INSTALLED');
		}

		$response->type = 'DSpace';

		if ($success) {
			$response->status = JAUTHENTICATE_STATUS_SUCCESS;
			$response->error_message = '';

			$response->email = $credentials['username'];

			// reset the username to what we ended up using
			$response->username = $credentials['username'];
			$response->fullname = $credentials['username'];
			$response->dspacePassword = $credentials['password'];
			$response->dspaceId = $dspaceId;
		} else {
			$response->status = JAUTHENTICATE_STATUS_FAILURE;
			$response->error_message = JText::sprintf('JGLOBAL_AUTH_FAILED', $message);
		}
	}
}
