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

jimport('joomla.application.component.controller');

class JSpaceController extends JControllerLegacy
{
	const DEFAULT_VIEW = "communities";
		
	public function __construct()
	{
		parent::__construct();
	}
	
	public function search()
	{
		$model = $this->getModel("search");
		$this->setRedirect($model->buildQueryURL(JRequest::get()));
	}
	
	// @todo this needs to be made more generic, perhaps some kind of handle
	// manager.
	public function resolve()
	{
		require_once(JPATH_ROOT."/components/com_jspace/helpers/route.php");
	
		$id = JFactory::getApplication()->input->getInt('id');
		$this->setRedirect(JSpaceHelperRoute::getItemFullRoute($id));
	}

	public function display($cachable = false, $urlparams = false)
	{
		$model = $this->getModel(JRequest::getWord("view", self::DEFAULT_VIEW));
		$view = $this->getView(JRequest::getWord("view", self::DEFAULT_VIEW), 'html');
		$view->setModel($model, true);
		$view->setLayout(JRequest::getWord('layout', 'default'));
		$view->display();
	}
}