<?php
/**
 * @package     Joomla.Site
 * @subpackage  
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');
$this->subtemplatename = 'records';
echo JLayoutHelper::render('joomla.content.category_default', $this);