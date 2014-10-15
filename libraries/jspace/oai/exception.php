<?php
/**
 *
 * @package		JSpace
 * @subpackage	Repository
 * @copyright	Copyright (C) 2012 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace library for Joomla!.

   The JSpace library for Joomla! is free software: you can redistribute it
   and/or modify it under the terms of the GNU General Public License as
   published by the Free Software Foundation, either version 3 of the License,
   or (at your option) any later version.

   The JSpace library for Joomla! is distributed in the hope that it will be
   useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with the JSpace library for Joomla!.  If not, see
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have
 * contributed any source code changes.
 * Michał Kocztorz <michalkocztorz@wijiti.com>
 * Hayden Young <hayden@knowledgearc.com>
 */

defined('JPATH_PLATFORM') or die;

/**
 * @package     JSpace
 * @subpackage  OAI
 */
class JSpaceOAIException extends InvalidArgumentException
{
    /**
     * @var  string
     */
    protected $code;

    /**
     * Initiates an OAI-specific exception.
     *
     * @param  string     $message   A human-readable error message.
     * @param  string     $code      An OAI-compatible error.
     * @param  Exception  $previous  The previous exception.
     */
    public function __construct($message, $code, $previous = null)
    {
        parent::__construct($message, 500, $previous);
        $this->code = $code;
    }
}

