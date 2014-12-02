<?php
/**
 * A script for intercepting calls to this component and handling them appropriately.
 *
 * @copyright	Copyright (C) 2011-2014 Wijiti Pty Ltd. All rights reserved.
 */

defined('_JEXEC') or die();

require_once JPATH_COMPONENT.'/helpers/route.php';

JLoader::registerNamespace('JSpace', JPATH_PLATFORM);

$controller = JControllerLegacy::getInstance('JSpace');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();