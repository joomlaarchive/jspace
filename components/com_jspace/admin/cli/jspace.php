#!/usr/bin/php
<?php
/**
 * @package JSpace
 * @subpackage CLI
 * @copyright Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 */

// Make sure we're being called from the command line, not a web interface
if (array_key_exists('REQUEST_METHOD', $_SERVER)) die();

/**
 * This is a CRON script which should be called from the command-line, not the
 * web. For example something like:
 * /usr/bin/php /path/to/site/cli/jspace.php
 */

// Set flag that this is a parent file.
define('_JEXEC', 1);

// Load system defines
if (file_exists(dirname(dirname(__FILE__)) . '/defines.php')) {
        require_once dirname(dirname(__FILE__)) . '/defines.php';
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', dirname(dirname(__FILE__)));
    require_once JPATH_BASE . '/includes/defines.php';
}

// Get the framework.
if (file_exists(JPATH_LIBRARIES . '/import.legacy.php'))
    require_once JPATH_LIBRARIES . '/import.legacy.php';
else
    require_once JPATH_LIBRARIES . '/import.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Load the configuration
require_once JPATH_CONFIGURATION . '/configuration.php';


if (version_compare(JVERSION, "3.0", "l")) {
    // Force library to be in JError legacy mode
    JError::$legacy = true;
    
    // Import necessary classes not handled by the autoloaders
    jimport('joomla.application.menu');
    jimport('joomla.environment.uri');
    jimport('joomla.event.dispatcher');
    jimport('joomla.utilities.utility');
    jimport('joomla.utilities.arrayhelper');
}

// include relevant tables.
JTable::addIncludePath(JPATH_ROOT.'/administrator/components/com_jspace/tables');

// System configuration.
$config = new JConfig;

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL & ~E_STRICT);
ini_set('display_errors', 1);

// Load Library language
$lang = JFactory::getLanguage();

$lang->load('jspace', JPATH_SITE, null, false, false) || $lang->load('jspace', JPATH_SITE, null, true);

jimport('joomla.application.component.helper');
jimport('jspace.factory');
 
/**
 * Simple command line interface application class.
 *
 * @package JSpace.CLI
 */
class JSpaceCli extends JApplicationCli
{
    public function doExecute()
    {
        // fool the system into thinking we are running as JSite with JSpace as the active component
        $_SERVER['HTTP_HOST'] = 'domain.com';
        JFactory::getApplication('cli');

        // Disable caching.
        $config = JFactory::getConfig();
        $config->set('caching', 0);
        $config->set('cache_handler', 'file');
        
        if (($this->input->get('h') || $this->input->get('help')) && count($this->input->args) == 0)
        {
            $this->help();
            return;
        }
        
        $plugin = JArrayHelper::getValue($this->input->args, 0);
        
        array_shift($this->input->args);
        $commands = $this->input->args;
        
        $options = $this->input->getArray();

        if (array_search($plugin, $this->_getPlugins()) !== false)
        {    
            try 
            {
                $this->_executeCommand($plugin, $commands, $options);
            } 
            catch (Exception $e) 
            {
                $this->out($e->getMessage());
                
                if ($this->_isVerbose())
                {
                    $this->out($e->getTraceAsString());
                }
            }
        }
        else
        {
            $this->out('No plugin specified.');
            $this->help();
        }
        
    }
 
    /**
     * Method to build and print the help screen text to stdout.
     *
     * @return void
     * @since 1.0
     */
    protected function help()
    {
        $pluginsList = implode("\n", $this->_getPlugins());
    
        echo <<<EOT
Usage: jspace [plugin] [action] [OPTIONS]
        
Provides tools for executing various JSpace actions.

[plugin] is associated with the plugin which needs to be executed.

[action] is associated with the action you wish to execute.

[OPTIONS] are one or more flags which are associated with the task.

Available Plugins:
{$pluginsList}

EOT;
    }
    
    public function out($text = '', $nl = true)
    {
        if (!($this->input->get('q', false) || $this->input->get('quiet', false))) 
        {
            parent::out($text, $nl);
        }
        
        return $this;
    }
    
    private function _executeCommand($plugin, $commands = array(), $options = array())
    {
        if ($plugin)
        {
            if (!is_a(JPluginHelper::getPlugin('content', $plugin), 'stdClass'))
            {
                throw new Exception('The specified plugin does not exist or is not enabled.');
            }
        }
        
        $dispatcher = JEventDispatcher::getInstance();
        
        JPluginHelper::importPlugin("content", $plugin, true, $dispatcher);
        
        return $dispatcher->trigger('onJSpaceExecuteCliCommand', array($commands, $options));
    }

    private function _isVerbose()
    {
        // Verbose can only be set if quiet is not set.
        if (!($this->input->get('q', false) || $this->input->get('quiet', false))) 
        {
            if ($this->input->get('v') || $this->input->get('verbose'))
            {
                return true;
            }
        }
        
        return false;
    }
    
    private function _getPlugin()
    {
        return $this->input->getString('plugin', $this->input->getString('P', null));
    }
    
    private function _getPlugins()
    {
        JPluginHelper::importPlugin('content');
        
        $dispatcher = JEventDispatcher::getInstance();
        
        $plugins = array();
        
        foreach ($dispatcher->get('_observers') as $observer)
        {
            if ($observer->get('_type') == 'content')
            {
                if (JPluginHelper::isEnabled('content', $observer->get('_name')))
                {
                    if (method_exists($observer, 'onJSpaceExecuteCliCommand'))
                    {
                        $plugins[] = $observer->get('_name');
                    }
                }
            }
        }
        
        return $plugins;
    }
}
 
JApplicationCli::getInstance('JSpaceCli')->execute();