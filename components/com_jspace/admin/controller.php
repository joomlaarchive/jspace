<?php
/**
 * A controller for managing the retrieval of items from an OAI compliant archive.
 * 
 * @author		$LastChangedBy$
 * @package		JSpace
 * @copyright	Copyright (C) 2011 Wijiti Pty Ltd. All rights reserved.
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
 * Name							Email
 * Hayden Young					<haydenyoung@wijiti.com> 
 * 
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controllerform');

class JSpaceController extends JControllerForm 
{
	protected $default_view = 'configuration';
	
	function __construct()
	{
		parent::__construct();
	}

	public function save($key = null)
	{
		$model = $this->getModel(JRequest::getWord("view", $this->default_view));
		
		$model->save(JRequest::getVar('jform', array(), 'post', 'array'));

		$view = $this->getView(JRequest::getWord("view", $this->default_view), JRequest::getWord("format", "html"));
		$view->setModel($model, true);
		
		$url = new JURI("index.php");
		$url->setVar("option", JRequest::getWord("option"));
		$url->setVar("view", JRequest::getWord("view", $this->default_view));

		$this->setRedirect($url->toString(), JText::_("COM_JSPACE_".strtoupper(JRequest::getWord("view", $this->default_view))."_SAVE_SUCCESSFUL"));
	}	
}