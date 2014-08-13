<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

jimport('jspace.helper.recordhistory');

/**
 * Table class supporting modified pre-order tree traversal behavior.
 *
 * @package     Joomla.Platform
 * @subpackage  Table
 * @link        http://docs.joomla.org/JTableObserver
 * @since       3.2
 */
class JTableObserverRecordhistory extends JTableObserverContenthistory
{    
    public static function createObserver(JObservableInterface $observableObject, $params = array())
    {
        $typeAlias = $params['typeAlias'];

        $observer = new self($observableObject);

        $observer->contenthistoryHelper = new JSpaceHelperRecordhistory($typeAlias);
        $observer->typeAliasPattern = $typeAlias;

        return $observer;
    }
}
