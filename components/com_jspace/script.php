<?php
/**
 * Installation scripts.
 * 
 * @package     JSpace
 * @subpackage  Installer
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     This file is part of the JSpace component for Joomla!.

   The JSpace component for Joomla! is free software: you can redistribute it 
   and/or modify it under the terms of the GNU General Public License as 
   published by the Free Software Foundation, either version 3 of the License, 
   or (at your option) any later version.

   The JSpace component for Joomla! is distributed in the hope that it will be 
   useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with the JSpace component for Joomla!.  If not, see 
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 * Name                         Email
 * Hayden Young                 <hayden@knowledgearc.com> 
 * 
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.installer.helper');
jimport('joomla.filesystem.folder');

class Com_JSpaceInstallerScript
{       
    public function install($parent)
    {

    }
    
    public function update($parent) 
    {
        
    }
    
    public function uninstall($parent)
    {
        $src = JPATH_ROOT."/cli/jspace.php";
        
        if (JFile::exists($src)){
            if (JFile::delete($src)) {
                echo "<p>JSpace CLI uninstalled from ".$src." successfully.</p>";
            } else {
                echo "<p>Could not uninstall JSpace CLI from ".$src.". You will need to manually remove it.</p>";
            }
        }
    }
    
    public function postflight($type, $parent)
    {       
        $crawler = $this->_installCLI($parent);
    }
    
    private function _installCLI($parent)
    {
        $success = false; 
        
        $src = $parent->getParent()->getPath('extension_administrator').'/cli/jspace.php';
        
        $cli = JPATH_ROOT.'/cli/jspace.php';
        
        if (JFile::exists($src)) {
            if ($success = JFile::move($src, $cli)) {
                JFolder::delete($parent->getParent()->getPath('extension_administrator').'/cli');
            }
        }
        
        return $success;
    }
}