<?php
/**
 * @package     JSpace.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('joomla.registry.registry');

jimport('jspace.ingestion.harvest');

/**
 * Handles RESTful calls to the JSpace application.
 *
 * @package     JSpace.Plugin
 */
class plgAPIJSpace extends ApiPlugin
{
    public function __construct(&$subject, $config = array())
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
        
        JLog::addLogger(array());
        
        ApiResource::addIncludePath(dirname(__FILE__).'/jspace');
    }
}