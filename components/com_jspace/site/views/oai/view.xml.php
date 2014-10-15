<?php
/**
 * OAI representation of item
 *
 * @package     JSpace
 * @copyright   Copyright (C) 2011-2014 Wijiti Pty Ltd. All rights reserved.
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
 * @author Hayden Young <haydenyoung@wijiti.com>
 * @author Michał Kocztorz <michalkocztorz@wijiti.com>
 */

defined('_JEXEC' ) or die( 'Restricted access');

jimport('joomla.application.component.view');
jimport('jspace.factory');

class JSpaceViewOAI extends JViewLegacy
{
    function display($tpl = null)
    {
        JFactory::getDocument()->setMimeEncoding('text/xml');
        JFactory::getApplication()->setHeader('Content-Type', 'text/xml', true);

        $this->set('oai', $this->get('Request'));

        parent::display($tpl);
    }
}