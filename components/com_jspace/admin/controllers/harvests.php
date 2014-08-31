<?php
defined('_JEXEC') or die;

class JSpaceControllerHarvests extends JControllerAdmin
{
    public function __construct($config = array())
    {
        parent::__construct($config);
        
        $this->set('model_prefix', 'JSpaceModel');
        $this->set('name', 'Harvest');
    }
}