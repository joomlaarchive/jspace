<?php
/**
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
namespace JSpace\Table\Observer;

\JLoader::import('jspace.helper.recordhistory');

/**
 * Table class supporting modified pre-order tree traversal behavior.
 *
 * @link        http://docs.joomla.org/JTableObserver
 */
class RecordHistory extends \JTableObserverContenthistory
{
    public static function createObserver(\JObservableInterface $observableObject, $params = array())
    {
        $typeAlias = $params['typeAlias'];

        $observer = new self($observableObject);

        $observer->contenthistoryHelper = new \JSpace\Helper\RecordHistory($typeAlias);
        $observer->typeAliasPattern = $typeAlias;

        return $observer;
    }
}
