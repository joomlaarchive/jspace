<?php
/**
 * A script for intercepting calls to this component and handling them appropriately.
 *
 * @copyright	Copyright (C) 2011-2014 KnowledgeARC Ltd. All rights reserved.
 * @license     This file is part of the JSpace component for Joomla!.
 */
defined('_JEXEC') or die;

JHtml::_('behavior.tabstate');

if (!JFactory::getUser()->authorise('core.manage', 'com_jspace'))
{
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'), 404);
}

JLoader::register('JSpaceHelper', __DIR__ . '/helpers/jspace.php');
JLoader::registerNamespace('JSpace', JPATH_PLATFORM);

$controller = JControllerLegacy::getInstance('JSpace');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();