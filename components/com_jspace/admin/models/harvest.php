<?php
/**
 * @package     JSpace.Component
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('jspace.ingestion.harvest');

/**
 * Models the management of a JSpace harvest.
 *
 * @package     JSpace.Component
 * @subpackage  Model
 */
class JSpaceModelHarvest extends JModelAdmin
{
    protected $typeAlias;

    public function __construct($config = array())
    {
        parent::__construct($config);
        
        $this->typeAlias = $this->get('option').'.'.$this->getName();
    }

    public function getItem($pk = null)
    {
        $app = JFactory::getApplication();
        $item = parent::getItem($pk);

        // Override the base user data with any data in the session.
        $data = $app->getUserState('com_jspace.edit.harvest.data', array());

        foreach ($data as $k => $v)
        {
            $item->$k = $v;
        }

        // provide a quick way to detect if discovery has occurred.
        if (JArrayHelper::getvalue($item->params, 'discovery'))
        {
            $item->discovered = true;
        }
        else
        {
            $item->discovered = false;
        }
        
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('content');
        
        // Trigger the data preparation event.
        $dispatcher->trigger('onContentPrepareData', array($this->typeAlias, $item));

        return $item;
    }
    
    public function getForm($data = array(), $loadData = true)
    {
        $form = $this->loadForm($this->typeAlias, $this->getName(), array('control'=>'jform', 'load_data'=>$loadData));

        if (empty($form))
        {
            return false;
        }
        
        return $form;
    }
    
    protected function loadFormData()
    {
        $app = JFactory::getApplication();

        $data = $this->getItem();

        $this->preprocessData($this->typeAlias, $data);

        return $data;
    }
    
    protected function preprocessForm(JForm $form, &$data, $group = 'content')
    {
        // if no data, grab the posted form data.
        if (!$data instanceof JObject)
        {
            $data = JFactory::getApplication()->input->get('jform', $data, 'array');
            $data = JArrayHelper::toObject($data);
        }

        $params = new JRegistry;
        $params->loadArray($data->params);
        
        if ($params->get('discovery.url'))
        {
            $plugin = $params->get('discovery.type');
            
            $language = JFactory::getLanguage();
            $language->load('plg_jspace_'.$plugin);
            $path = JPATH_ROOT.'/plugins/jspace/'.$plugin.'/forms/'.$plugin.'.xml';
            $form->loadFile($path, false);
        }
        else
        {
            $form->removeField('state');
            $form->removeField('catid');
        }

        parent::preprocessForm($form, $data, $group);
    }
    
    public function getTable($type = 'Harvest', $prefix = 'JSpaceTable', $config = array())
    {
        return parent::getTable($type, $prefix, $config);
    }
    
    public function save($data)
    {
        $pk = JArrayHelper::getValue($data, 'id', (int)$this->getState('harvest.id'));
        $harvest = JSpaceIngestionHarvest::getInstance($pk);
        
        try
        {
            $harvest->bind($data);
            $harvest->save();
        }
        catch (Exception $e)
        {
            JLog::addLogger(array());
            JLog::add($e->getMessage(), JLog::ERROR, 'jspace');
            $this->setError(JText::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            return false;
        }
        
        $this->setState('harvest.id', $harvest->id);

        $this->setState($this->getName() . '.new', ($pk ? false : true));

        return true;
    }
    
    public function discover($data)
    {
        $discovered = false;
        $pk = JArrayHelper::getValue($data, 'id', (int)$this->getState('harvest.id'));
        $harvest = JSpaceIngestionHarvest::getInstance($pk);
        $harvest->bind($data);

        $harvest->state = ($pk ? $harvest->state : 0);

        $plugin = ($harvest->harvester) ? $harvest->harvester : null;

        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('jspace', $plugin);

        try
        {
            $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));
        
            foreach ($result as $item)
            {
                if ($item)
                {
                    $discovered = $item;
                    break;
                }
            }

            if ($discovered)
            {
                $harvest->get('params')->merge($discovered);
                
                $data = $harvest->getProperties();
                $data['params'] = $harvest->get('params')->toArray();

                JFactory::getApplication()->setUserState('com_jspace.edit.harvest.data', $data);
                return true;
            }
        }
        catch (Exception $e)
        {
            JLog::addLogger(array());
            JLog::add($e->getMessage(), JLog::ERROR, 'jspace');
            $this->setError(JText::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }
        
        return false;
    }
}