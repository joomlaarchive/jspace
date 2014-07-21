<?php
defined('_JEXEC') or die;

jimport('jspace.archive.asset');

class JSpaceControllerAsset extends JControllerForm
{
    public function stream()
    {
        $id = JFactory::getApplication()->input->get('id');
        $plugin = JFactory::getApplication()->input->get('type');
        
        $asset = JSpaceAsset::getInstance($id);
        
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('content', $plugin);
        
        // Trigger the data preparation event.
        $dispatcher->trigger('onJSpaceAssetDownload', array($asset));
    }
}